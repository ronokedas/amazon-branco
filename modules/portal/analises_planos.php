<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';
require_once __DIR__ . '/../../includes/analise_planos.php';

requireClienteSenhaDefinitiva();

$clienteId = (string) clientePortalId();
$embarcacaoIds = clientePortalEmbarcacaoIds($pdo, $clienteId);
$analises = [];
$totalAtivas = 0;
$totalConcluidas = 0;
$abaStatus = trim($_GET['aba'] ?? 'ativas');

if ($embarcacaoIds) {
    // Contadores para abas
    $pParamsCount = [];
    $pInCount = clientePortalSqlIn($embarcacaoIds, 'ap_cnt_', $pParamsCount);
    $stCount = $pdo->prepare("SELECT 
        SUM(CASE WHEN ap.status IN ('AGUARDANDO_AGENDAMENTO', 'AGENDADA', 'EM_ANALISE', 'AGUARDANDO_DOCUMENTOS', 'AGUARDANDO_ASSINATURA_ANALISTA', 'AGUARDANDO_APROVACAO_ADMIN', 'EM_EXIGENCIA') THEN 1 ELSE 0 END) AS total_ativas,
        SUM(CASE WHEN ap.status IN ('CONCLUIDA', 'APROVADA', 'DEFERIDA', 'ARQUIVADA', 'REPROVADA', 'CANCELADA') THEN 1 ELSE 0 END) AS total_concluidas
        FROM analises_planos ap WHERE ap.embarcacao_id IN ({$pInCount})");
    $stCount->execute($pParamsCount);
    $countsRow = $stCount->fetch(PDO::FETCH_ASSOC) ?: ['total_ativas' => 0, 'total_concluidas' => 0];
    $totalAtivas = (int)($countsRow['total_ativas'] ?? 0);
    $totalConcluidas = (int)($countsRow['total_concluidas'] ?? 0);

    $params = [];
    $in = clientePortalSqlIn($embarcacaoIds, 'ap_emb_', $params);
    $statusWhere = $abaStatus === 'concluidas' 
        ? "ap.status IN ('CONCLUIDA', 'APROVADA', 'DEFERIDA', 'ARQUIVADA', 'REPROVADA', 'CANCELADA')"
        : "ap.status IN ('AGUARDANDO_AGENDAMENTO', 'AGENDADA', 'EM_ANALISE', 'AGUARDANDO_DOCUMENTOS', 'AGUARDANDO_ASSINATURA_ANALISTA', 'AGUARDANDO_APROVACAO_ADMIN', 'EM_EXIGENCIA')";

    $sql = "SELECT ap.id, ap.numero, ap.tipo_processo, ap.enquadramento, ap.status,
                   ap.prazo_agendado_em, e.nome AS embarcacao_nome, u.nome AS analista_nome,
                   (SELECT COALESCE(MAX(s.revisao), 0)
                      FROM analise_planos_submissoes s
                     WHERE s.analise_id = ap.id) AS ultima_revisao
              FROM analises_planos ap
              JOIN embarcacoes e ON e.id = ap.embarcacao_id
         LEFT JOIN usuarios u ON u.id = ap.analista_id
             WHERE ap.embarcacao_id IN ({$in})
               AND {$statusWhere}
          ORDER BY ap.atualizado_em DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $analises = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$statusLabels = [
    'AGUARDANDO_AGENDAMENTO' => 'Aguardando agendamento técnico',
    'AGENDADA' => 'Agendada / Em análise',
    'EM_ANALISE' => 'Em análise técnica',
    'AGUARDANDO_DOCUMENTOS' => 'Aguardando documentos / revisão',
    'AGUARDANDO_ASSINATURA_ANALISTA' => 'Em homologação / Assinatura do analista',
    'AGUARDANDO_APROVACAO_ADMIN' => 'Aguardando validação da diretoria',
    'EM_EXIGENCIA' => 'Em exigência técnica',
    'APROVADA' => 'Aprovada / Homologada',
    'CONCLUIDA' => 'Concluída / Deferida',
    'DEFERIDA' => 'Deferida',
    'ARQUIVADA' => 'Arquivada',
    'REPROVADA' => 'Reprovada',
    'CANCELADA' => 'Cancelada',
];

// Identifica a análise selecionada
$analiseIdSel = trim($_GET['id'] ?? '');
$analiseAtiva = null;
if (!empty($analises)) {
    if ($analiseIdSel !== '') {
        foreach ($analises as $anItem) {
            if ($anItem['id'] === $analiseIdSel) {
                $analiseAtiva = $anItem;
                break;
            }
        }
    }
    if (!$analiseAtiva) {
        $analiseAtiva = $analises[0];
    }
}

$titulo_page = 'Análise de Planos - Portal do Cliente';
require_once __DIR__ . '/../../includes/portal_header.php';
?>
<section class="portal-page-header">
    <div>
        <h1>Análise de Planos</h1>
        <p>Envie os planos de engenharia e memoriais da embarcação (Arranjo Geral, Linhas, Estabilidade, Borda Livre e ART) para conferência do Analista Naval.</p>
    </div>
    <div style="display:flex; gap:8px;">
        <a class="btn btn-secondary" href="<?php echo APP_URL; ?>portal/documentos">
            <i class="fas fa-file-lines"></i> Meus documentos
        </a>
    </div>
</section>

<!-- ABAS DE NAVEGAÇÃO INTERNA -->
<div style="display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap;">
    <a href="<?php echo APP_URL; ?>portal/analises-planos?aba=ativas" class="btn btn-sm <?php echo $abaStatus !== 'concluidas' ? 'btn-primary' : 'btn-secondary'; ?>">
        <i class="fa-solid fa-spinner"></i> Em Andamento (<?php echo $totalAtivas; ?>)
    </a>
    <a href="<?php echo APP_URL; ?>portal/analises-planos?aba=concluidas" class="btn btn-sm <?php echo $abaStatus === 'concluidas' ? 'btn-primary' : 'btn-secondary'; ?>">
        <i class="fa-solid fa-circle-check"></i> Concluídas / Homologadas (<?php echo $totalConcluidas; ?>)
    </a>
</div>

<?php if (!$analises || !$analiseAtiva): ?>
    <section class="portal-panel">
        <div class="portal-empty">
            <i class="fas fa-folder-open"></i>
            <h2>Nenhuma análise disponível nesta categoria</h2>
            <p>Quando houver processos abertos ou revisões solicitadas para suas embarcações, elas aparecerão aqui.</p>
        </div>
    </section>
<?php else: ?>
    <?php
    // Carrega dados da análise ativa
    $statusLabel = $statusLabels[$analiseAtiva['status']] ?? ucfirst(strtolower(str_replace('_', ' ', $analiseAtiva['status'])));
    $statusClass = $analiseAtiva['status'] === 'AGUARDANDO_DOCUMENTOS' ? 'is-warning' : 'is-analysis';

    $stmtSubHist = $pdo->prepare("
        SELECT s.revisao, s.descricao, s.recebido_em, s.origem,
               ar.id AS arquivo_id, ar.nome_original, ar.categoria, ar.tamanho_bytes, ar.extensao, ar.classificacao, ar.criado_em
        FROM analise_planos_submissoes s
        INNER JOIN analise_planos_arquivos ar ON ar.submissao_id = s.id
        WHERE s.analise_id = :id
        ORDER BY s.revisao DESC, ar.criado_em ASC
    ");
    $stmtSubHist->execute([':id' => $analiseAtiva['id']]);
    $arquivosHistorico = $stmtSubHist->fetchAll(PDO::FETCH_ASSOC);

    $catsEnviadas = [];
    foreach ($arquivosHistorico as $h) {
        $c = trim($h['categoria'] ?? '');
        if ($c !== '') {
            $catsEnviadas[$c] = true;
            $catsEnviadas[rtrim($c, '.')] = true;
            $catsEnviadas[mb_strtoupper($c, 'UTF-8')] = true;
            $catsEnviadas[rtrim(mb_strtoupper($c, 'UTF-8'), '.')] = true;
        }
    }
    $todasCategorias = analisePlanosCategoriasPadrao();
    $totalObrigatorios = 16;
    $enviadosCount = 0;
    foreach ($todasCategorias as $docItem) {
        if ($docItem === 'OUTROS') continue;
        if (isset($catsEnviadas[$docItem]) || isset($catsEnviadas[rtrim($docItem, '.')]) || isset($catsEnviadas[mb_strtoupper($docItem, 'UTF-8')])) {
            $enviadosCount++;
        }
    }
    $percentualEnviado = min(100, round(($enviadosCount / $totalObrigatorios) * 100));

    // Categorias mais frequentes para atalhos rápidos
    $atalhosFrequentes = [
        'ART',
        'FOLHA DE ROSTO',
        'DECLARAÇÃO',
        'MEMORIAL DESCRITO',
        'NOTAS DE ARQUEAÇÃO',
        'NOTAS DE BORDA LIVRE',
        'PLANO DE ARRANJO GERAL, LUZES, SEGURANÇA E CAPACIDADE.',
        'PLANOS DE LINHAS',
        'ESTUDO DE ESTABILIDADE',
        'CURVAS HIDROSTÁTICAS',
        'CURVAS CRUZADAS',
        'PROVA DE INCLINAÇÃO OU PORTE BRUTO'
    ];
    ?>

    <!-- SELETOR DE PROCESSO QUANDO HOUVER MÚLTIPLOS -->
    <?php if (count($analises) > 1): ?>
        <div style="background: #fff; border: 1px solid var(--p-line); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; box-shadow: var(--p-shadow);">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--p-muted); text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-ship text-primary"></i> Selecione a Embarcação / Processo em Enquadramento:
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <?php foreach ($analises as $anPill): ?>
                    <?php $isActive = $anPill['id'] === $analiseAtiva['id']; ?>
                    <a href="<?php echo APP_URL; ?>portal/analises-planos?aba=<?php echo urlencode($abaStatus); ?>&id=<?php echo urlencode($anPill['id']); ?>"
                       class="btn btn-sm"
                       style="padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 0.84rem; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; transition: all 0.2s ease; <?php echo $isActive ? 'background: var(--p-green); color: #fff; border: 1px solid var(--p-green); box-shadow: 0 2px 8px rgba(41, 175, 126, 0.35);' : 'background: #f8fafc; color: #334155; border: 1px solid #cbd5e1;'; ?>">
                        <i class="fa-solid <?php echo $isActive ? 'fa-circle-check' : 'fa-ship'; ?>"></i>
                        <strong><?php echo h($anPill['numero']); ?></strong>
                        <span>&bull; <?php echo h($anPill['embarcacao_nome']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- CARD PRINCIPAL DE IDENTIFICAÇÃO E PROGRESSO -->
    <div style="background: #fff; border: 1px solid var(--p-line); border-radius: 14px; padding: 20px 24px; margin-bottom: 20px; box-shadow: var(--p-shadow); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                <h2 style="margin: 0; font-size: 1.35rem; color: var(--p-ink); font-weight: 800;">
                    <?php echo h($analiseAtiva['numero']); ?> &bull; <?php echo h($analiseAtiva['embarcacao_nome']); ?>
                </h2>
                <span class="portal-status <?php echo $statusClass; ?>">
                    <?php echo h($statusLabel); ?>
                </span>
            </div>
            <div style="font-size: 0.88rem; color: var(--p-muted); display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <span><i class="fa-solid fa-compass-drafting text-primary"></i> <?php echo h($analiseAtiva['tipo_processo'] ?: 'Processo em enquadramento'); ?> (NORMAM-202)</span>
                <span>&bull;</span>
                <span><i class="fa-solid fa-user-tie text-secondary"></i> Analista Naval: <strong><?php echo h($analiseAtiva['analista_nome'] ?: 'Equipe Técnica Amazon'); ?></strong></span>
                <span>&bull;</span>
                <span><i class="fa-solid fa-clock-rotate-left"></i> <?php echo (int)$analiseAtiva['ultima_revisao'] > 0 ? 'Revisão ' . (int)$analiseAtiva['ultima_revisao'] : 'Envio inicial'; ?></span>
            </div>
        </div>

        <!-- INDICADOR DE PROGRESSO NORMAM-202 -->
        <div style="min-width: 250px; background: var(--p-soft); border: 1px solid var(--p-line); padding: 12px 16px; border-radius: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; font-size: 0.82rem; margin-bottom: 6px;">
                <span style="font-weight: 700; color: var(--p-ink);">Progresso NORMAM-202</span>
                <strong style="color: var(--p-green-dark);"><?php echo $enviadosCount; ?> de <?php echo $totalObrigatorios; ?> docs</strong>
            </div>
            <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                <div style="width: <?php echo $percentualEnviado; ?>%; height: 100%; background: linear-gradient(90deg, #22c55e, #16a34a); border-radius: 4px; transition: width 0.4s ease;"></div>
            </div>
            <small style="font-size: 0.74rem; color: var(--p-muted); display: block; margin-top: 4px;">
                <?php echo $enviadosCount === $totalObrigatorios ? '✓ Todos os documentos oficiais foram enviados.' : 'Envie os memoriais e pranchas pendentes.'; ?>
            </small>
        </div>
    </div>

    <!-- AVISO DE PRESERVAÇÃO CRIPTOGRÁFICA -->
    <div class="portal-preserve" style="margin-bottom: 24px;">
        <i class="fas fa-shield-check"></i>
        <div>
            <strong>Seus arquivos anteriores serão preservados.</strong>
            <span>Ao enviar uma nova revisão, os arquivos já analisados não serão substituídos. Cada documento recebe auditoria com hash criptográfico SHA-256.</span>
        </div>
    </div>

    <!-- WORKSTATION ORGANIZADA EM 2 COLUNAS -->
    <div class="portal-analysis-layout">
        <!-- COLUNA PRINCIPAL: FORMULÁRIO DE ENVIO E HISTÓRICO -->
        <div class="portal-analysis-main" style="display: flex; flex-direction: column; gap: 24px;">
            
            <!-- CARD DE NOVO ENVIO / REVISÃO -->
            <section style="background: #fff; border: 1px solid var(--p-line); border-radius: 12px; padding: 22px 24px; box-shadow: var(--p-shadow);">
                <div style="border-bottom: 1px solid var(--p-line); padding-bottom: 14px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0; font-size: 1.15rem; color: var(--p-ink); font-weight: 750; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-cloud-arrow-up text-primary"></i> Enviar Documento ou Revisão Técnica
                        </h3>
                        <p style="margin: 4px 0 0; font-size: 0.85rem; color: var(--p-muted);">
                            Selecione o documento exigido pela NORMAM-202 e anexe as pranchas ou memoriais para análise.
                        </p>
                    </div>
                </div>

                <form class="portal-analysis-form" data-portal-upload method="post" enctype="multipart/form-data" action="<?php echo APP_URL; ?>portal/analises-planos/actions">
                    <input type="hidden" name="csrf_token" value="<?php echo h(gerarCSRF()); ?>">
                    <input type="hidden" name="analise_id" value="<?php echo h($analiseAtiva['id']); ?>">

                    <!-- LINHA 1: CAMPOS DO FORMULÁRIO -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                        <div class="form-group">
                            <label for="categoria-<?php echo h($analiseAtiva['id']); ?>" style="font-weight: 700; color: var(--p-ink); font-size: 0.88rem; display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                                <i class="fa-solid fa-file-contract text-primary"></i> Tipo de Documento / Categoria NORMAM *
                            </label>
                            <select id="categoria-<?php echo h($analiseAtiva['id']); ?>" name="categoria" class="form-control" required style="width: 100%; height: 44px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; font-weight: 600; background: #fff; color: var(--p-ink);">
                                <?php foreach ($todasCategorias as $cat): ?>
                                    <option value="<?php echo h($cat); ?>"><?php echo h($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted" style="display: block; margin-top: 4px; font-size: 0.78rem;">
                                Selecione o documento oficial ou use os atalhos rápidos abaixo.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="descricao-<?php echo h($analiseAtiva['id']); ?>" style="font-weight: 700; color: var(--p-ink); font-size: 0.88rem; display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                                <i class="fa-regular fa-comment-dots text-secondary"></i> Descrição / Observações (Opcional)
                            </label>
                            <input id="descricao-<?php echo h($analiseAtiva['id']); ?>" name="descricao" maxlength="500" placeholder="Ex.: Prancha inicial ou revisão conforme solicitação" style="width: 100%; height: 44px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; font-size: 0.9rem;">
                            <small class="text-muted" style="display: block; margin-top: 4px; font-size: 0.78rem;">
                                Opcional. Se vazio, será identificado como Envio do Documento.
                            </small>
                        </div>
                    </div>

                    <!-- LINHA 2: ATALHOS RÁPIDOS EM 1 CLIQUE -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; margin-top: 4px;">
                        <div style="font-size: 0.78rem; font-weight: 700; color: #475569; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-bolt text-warning"></i>
                            <span>Atalhos rápidos para seleção em 1 clique:</span>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                            <?php foreach ($atalhosFrequentes as $catChip): ?>
                                <?php 
                                $chipEnviado = isset($catsEnviadas[$catChip]) || isset($catsEnviadas[rtrim($catChip, '.')]) || isset($catsEnviadas[mb_strtoupper($catChip, 'UTF-8')]);
                                ?>
                                <button type="button"
                                        class="btn btn-sm portal-chip-btn"
                                        style="font-size: 0.75rem; padding: 4px 10px; border-radius: 16px; border: 1px solid <?php echo $chipEnviado ? '#86efac' : '#cbd5e1'; ?>; background: <?php echo $chipEnviado ? '#f0fdf4' : '#ffffff'; ?>; color: <?php echo $chipEnviado ? '#166534' : '#334155'; ?>; font-weight: <?php echo $chipEnviado ? '600' : '500'; ?>; cursor: pointer; transition: all 0.15s ease;"
                                        onclick="selecionarCategoriaPortal('<?php echo h($analiseAtiva['id']); ?>', '<?php echo addslashes($catChip); ?>')">
                                    <?php if ($chipEnviado): ?>
                                        <i class="fa-solid fa-circle-check text-success" style="font-size: 0.72rem;"></i>
                                    <?php else: ?>
                                        <i class="fa-regular fa-circle text-muted" style="font-size: 0.72rem;"></i>
                                    <?php endif; ?>
                                    <?php echo h($catChip); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- LINHA 3: ZONA DE ANEXO DE ARQUIVOS -->
                    <div class="portal-upload-section" style="margin-top: 8px;">
                        <div class="portal-upload-zone" role="button" tabindex="0" aria-label="Selecionar arquivos para a revisão">
                            <input type="file" name="arquivos[]" multiple required accept=".pdf,.jpg,.jpeg,.png,.dwg,.dxf,.doc,.docx,.xls,.xlsx">
                            <div>
                                <i class="fas fa-cloud-arrow-up" style="font-size: 36px; color: var(--p-green); margin-bottom: 8px;"></i>
                                <strong style="font-size: 1.05rem;">Arraste e solte os arquivos aqui</strong>
                                <span style="font-size: 0.85rem; color: var(--p-muted);">ou</span><br>
                                <span class="portal-upload-button" style="margin-top: 6px;">Escolher arquivos</span>
                            </div>
                        </div>
                        <p class="portal-upload-help" style="font-size: 0.8rem; color: var(--p-muted); margin-top: 8px;">
                            Formatos aceitos: PDF, DWG, DXF, DOC, DOCX, XLS, XLSX, JPG e PNG.<br>
                            Tamanho máximo por arquivo: 50 MB.
                        </p>
                    </div>

                    <!-- LINHA 4: LISTA DE ARQUIVOS SELECIONADOS -->
                    <div class="portal-selected-files">
                        <div class="portal-selected-files-header">
                            <span>Arquivos selecionados</span>
                            <span><span data-file-count>0</span> arquivo(s)</span>
                        </div>
                        <div class="portal-file-empty">Nenhum arquivo selecionado.</div>
                        <ul class="portal-file-list" aria-live="polite"></ul>
                    </div>

                    <!-- LINHA 5: BOTÃO DE SUBMISSÃO -->
                    <button class="btn btn-primary portal-analysis-submit" type="submit" style="min-height: 46px; font-size: 1rem; width: 100%;">
                        <i class="fas fa-upload"></i> Enviar documento para análise
                    </button>
                </form>
            </section>

            <!-- CARD DE HISTÓRICO DE DOCUMENTOS ENVIADOS -->
            <section style="background: #fff; border: 1px solid var(--p-line); border-radius: 12px; padding: 22px 24px; box-shadow: var(--p-shadow);">
                <h3 style="font-size: 1.1rem; margin: 0 0 6px; color: var(--p-ink); font-weight: 750; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-clock-rotate-left text-primary"></i> Documentos Já Enviados (Histórico Preservado)
                </h3>
                <p style="font-size: 0.85rem; color: var(--p-muted); margin-bottom: 16px;">
                    Abaixo estão os arquivos recebidos em cada revisão. Cada versão é mantida integralmente com selo temporal e hash SHA-256.
                </p>

                <?php if (empty($arquivosHistorico)): ?>
                    <div style="padding: 24px; text-align: center; background: var(--p-soft); border-radius: 8px; border: 1px dashed #cbd5e1; color: var(--p-muted); font-size: 0.88rem;">
                        <i class="fa-regular fa-folder-open" style="font-size: 28px; margin-bottom: 8px; display: block; color: #94a3b8;"></i>
                        Nenhum arquivo enviado ainda para este processo.<br>
                        Utilize o formulário acima para enviar a primeira remessa de memoriais e pranchas.
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($arquivosHistorico as $histArq): ?>
                            <?php
                            $extH = strtolower($histArq['extensao'] ?? 'pdf');
                            $iconeH = match($extH) {
                                'pdf' => 'fa-file-pdf text-danger',
                                'dwg', 'dxf' => 'fa-drafting-compass text-primary',
                                'doc', 'docx' => 'fa-file-word text-info',
                                'xls', 'xlsx' => 'fa-file-excel text-success',
                                default => 'fa-file text-secondary'
                            };
                            $tByte = (int)($histArq['tamanho_bytes'] ?? 0);
                            $tFmt = $tByte < 1024 ? $tByte . ' B' : ($tByte < 1048576 ? round($tByte / 1024, 1) . ' KB' : round($tByte / 1048576, 2) . ' MB');
                            ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; flex-wrap: wrap; gap: 10px;">
                                <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                                    <i class="fa-solid <?php echo $iconeH; ?>" style="font-size: 1.4rem; flex-shrink: 0;"></i>
                                    <div style="min-width: 0;">
                                        <strong style="font-size: 0.92rem; color: var(--p-ink); display: block; word-break: break-word;"><?php echo h($histArq['nome_original']); ?></strong>
                                        <div style="font-size: 0.78rem; color: var(--p-muted); display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 2px;">
                                            <span style="font-weight: 700; color: var(--p-green-dark);">Revisão <?php echo (int)$histArq['revisao']; ?></span> &bull;
                                            <span class="badge" style="background: #e0f2fe; color: #0369a1; font-weight: 600;"><?php echo h($histArq['categoria'] ?: 'Projeto'); ?></span> &bull;
                                            <span><?php echo $tFmt; ?></span> &bull;
                                            <span>Enviado em <?php echo formatarData($histArq['recebido_em']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div style="flex-shrink: 0;">
                                    <span class="badge" style="background: #e2e8f0; color: #334155; font-size: 0.75rem; padding: 4px 8px; border-radius: 4px;">
                                        <i class="fa-solid fa-check"></i> <?php echo h($histArq['classificacao'] ?: 'Recebido / Em análise'); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- COLUNA LATERAL (SIDEBAR): CHECKLIST NORMAM-202 E ORIENTAÇÕES -->
        <aside class="portal-analysis-side" style="display: flex; flex-direction: column; gap: 20px;">
            <!-- CHECKLIST NORMAM-202 INTERATIVO -->
            <section class="portal-side-card" style="background: #fff; border: 1px solid var(--p-line); border-radius: 12px; padding: 18px; box-shadow: var(--p-shadow);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <h3 style="margin: 0; font-size: 1rem; color: var(--p-ink); font-weight: 750; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-clipboard-check text-primary"></i> Checklist NORMAM-202
                    </h3>
                    <span class="badge" style="background: <?php echo $enviadosCount === $totalObrigatorios ? '#22c55e' : '#0284c7'; ?>; color: #fff; font-size: 0.78rem; padding: 3px 8px; border-radius: 6px; font-weight: 700;">
                        <?php echo $enviadosCount; ?> / <?php echo $totalObrigatorios; ?>
                    </span>
                </div>
                <p style="font-size: 0.78rem; color: var(--p-muted); margin-bottom: 12px; line-height: 1.35;">
                    Documentos oficiais de engenharia para enquadramento. Clique em qualquer item pendente para preenchê-lo no formulário:
                </p>

                <div style="display: flex; flex-direction: column; gap: 6px; max-height: 520px; overflow-y: auto; padding-right: 2px;">
                    <?php foreach ($todasCategorias as $docNormam): ?>
                        <?php 
                        if ($docNormam === 'OUTROS') continue; 
                        $jaEnviado = isset($catsEnviadas[$docNormam]) || isset($catsEnviadas[rtrim($docNormam, '.')]) || isset($catsEnviadas[mb_strtoupper($docNormam, 'UTF-8')]);
                        ?>
                        <div role="button"
                             tabindex="0"
                             title="Clique para selecionar este documento para envio"
                             onclick="selecionarCategoriaPortal('<?php echo h($analiseAtiva['id']); ?>', '<?php echo addslashes($docNormam); ?>')"
                             style="display: flex; justify-content: space-between; align-items: flex-start; padding: 8px 10px; border-radius: 8px; font-size: 0.78rem; cursor: pointer; border: 1px solid <?php echo $jaEnviado ? '#bbf7d0' : '#e2e8f0'; ?>; background: <?php echo $jaEnviado ? '#f0fdf4' : '#ffffff'; ?>; transition: all 0.15s ease; gap: 8px;">
                            <div style="display: flex; align-items: flex-start; gap: 8px; flex: 1; min-width: 0;">
                                <i class="fa-solid <?php echo $jaEnviado ? 'fa-circle-check text-success' : 'fa-clock text-muted'; ?>" style="font-size: 0.85rem; margin-top: 2px; flex-shrink: 0;"></i>
                                <span style="font-weight: <?php echo $jaEnviado ? '600' : '500'; ?>; color: <?php echo $jaEnviado ? '#166534' : 'var(--p-ink)'; ?>; line-height: 1.3; word-break: break-word;">
                                    <?php echo h($docNormam); ?>
                                </span>
                            </div>
                            <span class="badge" style="flex-shrink: 0; font-size: 0.68rem; padding: 2px 6px; border-radius: 4px; background: <?php echo $jaEnviado ? '#dcfce7' : '#f1f5f9'; ?>; color: <?php echo $jaEnviado ? '#15803d' : '#64748b'; ?>; font-weight: 600; margin-top: 1px;">
                                <?php echo $jaEnviado ? 'Enviado' : 'Pendente'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- COMO FUNCIONA -->
            <section class="portal-side-card" style="background: #fff; border: 1px solid var(--p-line); border-radius: 12px; padding: 18px; box-shadow: var(--p-shadow);">
                <h3 style="margin: 0 0 12px; font-size: 1rem; color: var(--p-ink); font-weight: 750;">
                    <i class="fa-solid fa-circle-info text-info"></i> Como funciona o fluxo
                </h3>
                <ol class="portal-steps" style="padding-left: 0; margin: 0; list-style: none; display: flex; flex-direction: column; gap: 12px;">
                    <li style="display: flex; gap: 10px; align-items: flex-start;">
                        <span style="width: 24px; height: 24px; border-radius: 50%; background: var(--p-mint-soft); color: var(--p-green-dark); font-weight: 700; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">1</span>
                        <div style="font-size: 0.82rem; color: var(--p-muted);">
                            <strong style="color: var(--p-ink); display: block;">Selecione o documento</strong>
                            Escolha o item oficial exigido pela NORMAM-202.
                        </div>
                    </li>
                    <li style="display: flex; gap: 10px; align-items: flex-start;">
                        <span style="width: 24px; height: 24px; border-radius: 50%; background: var(--p-mint-soft); color: var(--p-green-dark); font-weight: 700; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">2</span>
                        <div style="font-size: 0.82rem; color: var(--p-muted);">
                            <strong style="color: var(--p-ink); display: block;">Anexe os arquivos</strong>
                            Formatos PDF, DWG ou memoriais calculados até 50 MB.
                        </div>
                    </li>
                    <li style="display: flex; gap: 10px; align-items: flex-start;">
                        <span style="width: 24px; height: 24px; border-radius: 50%; background: var(--p-mint-soft); color: var(--p-green-dark); font-weight: 700; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">3</span>
                        <div style="font-size: 0.82rem; color: var(--p-muted);">
                            <strong style="color: var(--p-ink); display: block;">Envio imediato</strong>
                            O Analista Naval é notificado em tempo real no ERP.
                        </div>
                    </li>
                </ol>
            </section>
        </aside>
    </div>

    <!-- SE HOUVER OUTROS PROCESSOS, LISTA RESUMIDA AO FINAL -->
    <?php if (count($analises) > 1): ?>
        <section style="margin-top: 32px; background: #fff; border: 1px solid var(--p-line); border-radius: 12px; padding: 20px 24px; box-shadow: var(--p-shadow);">
            <div style="margin-bottom: 14px;">
                <h3 style="margin: 0; font-size: 1.1rem; color: var(--p-ink); font-weight: 750;">
                    <i class="fa-solid fa-list-check text-primary"></i> Outros Processos de Análise em Andamento
                </h3>
                <p style="margin: 4px 0 0; font-size: 0.84rem; color: var(--p-muted);">
                    Clique em qualquer processo para alternar a visualização e gerenciar os envios daquela embarcação.
                </p>
            </div>
            <div class="portal-table-wrap">
                <table class="portal-table">
                    <thead>
                        <tr>
                            <th>Processo / RAP</th>
                            <th>Embarcação</th>
                            <th>Tipo de Processo</th>
                            <th>Status</th>
                            <th>Analista Naval</th>
                            <th style="text-align: right;">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analises as $outraAn): ?>
                            <?php 
                            $isCurrent = $outraAn['id'] === $analiseAtiva['id'];
                            $stLbl = $statusLabels[$outraAn['status']] ?? $outraAn['status'];
                            ?>
                            <tr style="<?php echo $isCurrent ? 'background: #f0fdf4;' : ''; ?>">
                                <td>
                                    <strong><?php echo h($outraAn['numero']); ?></strong>
                                    <?php if ($isCurrent): ?>
                                        <span class="badge" style="background: var(--p-green); color: #fff; font-size: 0.7rem; margin-left: 4px;">Em Exibição</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="fa-solid fa-ship text-muted"></i>
                                    <strong><?php echo h($outraAn['embarcacao_nome']); ?></strong>
                                </td>
                                <td><?php echo h($outraAn['tipo_processo'] ?: 'Enquadramento'); ?></td>
                                <td>
                                    <span class="portal-status is-analysis" style="font-size: 0.78rem;">
                                        <?php echo h($stLbl); ?>
                                    </span>
                                </td>
                                <td><?php echo h($outraAn['analista_nome'] ?: 'A definir'); ?></td>
                                <td style="text-align: right;">
                                    <?php if ($isCurrent): ?>
                                        <button class="btn btn-sm btn-secondary btn-has-text" disabled style="opacity: 0.7;">
                                            <i class="fa-solid fa-check"></i> Selecionado
                                        </button>
                                    <?php else: ?>
                                        <a class="btn btn-sm btn-outline-primary btn-has-text" href="<?php echo APP_URL; ?>portal/analises-planos?aba=<?php echo urlencode($abaStatus); ?>&id=<?php echo urlencode($outraAn['id']); ?>">
                                            <i class="fa-solid fa-folder-open"></i> Abrir Processo
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>

<script>
function selecionarCategoriaPortal(analiseId, categoria) {
    const sel = document.getElementById('categoria-' + analiseId);
    if (!sel) return;
    sel.value = categoria;
    sel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    sel.focus();
    sel.style.transition = 'all 0.3s ease';
    sel.style.borderColor = 'var(--p-green)';
    sel.style.boxShadow = '0 0 0 3px rgba(41, 175, 126, 0.3)';
    setTimeout(() => {
        sel.style.borderColor = '';
        sel.style.boxShadow = '';
    }, 1200);
}
</script>

<?php require_once __DIR__ . '/../../includes/portal_footer.php'; ?>
