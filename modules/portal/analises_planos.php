<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';
require_once __DIR__ . '/../../includes/analise_planos.php';

requireClienteSenhaDefinitiva();

$clienteId = (string) clientePortalId();
$embarcacaoIds = clientePortalEmbarcacaoIds($pdo, $clienteId);
$analises = [];

if ($embarcacaoIds) {
    $params = [];
    $in = clientePortalSqlIn($embarcacaoIds, 'ap_emb_', $params);
    $sql = "SELECT ap.id, ap.numero, ap.tipo_processo, ap.enquadramento, ap.status,
                   ap.prazo_agendado_em, e.nome AS embarcacao_nome, u.nome AS analista_nome,
                   (SELECT COALESCE(MAX(s.revisao), 0)
                      FROM analise_planos_submissoes s
                     WHERE s.analise_id = ap.id) AS ultima_revisao
              FROM analises_planos ap
              JOIN embarcacoes e ON e.id = ap.embarcacao_id
         LEFT JOIN usuarios u ON u.id = ap.analista_id
             WHERE ap.embarcacao_id IN ({$in})
               AND ap.status IN ('AGENDADA', 'EM_ANALISE', 'AGUARDANDO_DOCUMENTOS')
          ORDER BY ap.atualizado_em DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $analises = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$statusLabels = [
    'AGENDADA' => 'Aguardando envio de planos / Em análise',
    'EM_ANALISE' => 'Em análise técnica',
    'AGUARDANDO_DOCUMENTOS' => 'Aguardando documentos / revisão',
];

$titulo_page = 'Análise de Planos - Portal do Cliente';
require_once __DIR__ . '/../../includes/portal_header.php';
?>
<section class="portal-page-header">
    <div>
        <h1>Análise de Planos</h1>
        <p>Envie os planos de engenharia e memoriais da embarcação (Arranjo Geral, Linhas, Estabilidade, Borda Livre e ART) para conferência do Analista Naval.</p>
    </div>
    <a class="btn btn-secondary" href="<?php echo APP_URL; ?>portal/documentos">
        <i class="fas fa-file-lines"></i> Meus documentos
    </a>
</section>

<?php if (!$analises): ?>
    <section class="portal-panel">
        <div class="portal-empty">
            <i class="fas fa-folder-open"></i>
            <h2>Nenhuma análise recebendo documentos</h2>
            <p>Quando o analista solicitar uma revisão, ela aparecerá aqui.</p>
        </div>
    </section>
<?php else: ?>
    <div class="portal-preserve">
        <i class="fas fa-shield-check"></i>
        <div>
            <strong>Seus arquivos anteriores serão preservados.</strong>
            <span>Ao enviar uma nova revisão, os arquivos já analisados não serão substituídos.</span>
        </div>
    </div>

    <?php foreach ($analises as $a): ?>
        <?php
        $statusLabel = $statusLabels[$a['status']] ?? ucfirst(strtolower(str_replace('_', ' ', $a['status'])));
        $statusClass = $a['status'] === 'AGUARDANDO_DOCUMENTOS' ? 'is-warning' : 'is-analysis';

        $stmtSubHist = $pdo->prepare("
            SELECT s.revisao, s.descricao, s.recebido_em, s.origem,
                   ar.id AS arquivo_id, ar.nome_original, ar.categoria, ar.tamanho_bytes, ar.extensao, ar.classificacao, ar.criado_em
            FROM analise_planos_submissoes s
            INNER JOIN analise_planos_arquivos ar ON ar.submissao_id = s.id
            WHERE s.analise_id = :id
            ORDER BY s.revisao DESC, ar.criado_em ASC
        ");
        $stmtSubHist->execute([':id' => $a['id']]);
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
        ?>
        <section class="portal-analysis-layout">
            <div class="portal-analysis-main">
                <form class="portal-analysis-form" data-portal-upload method="post" enctype="multipart/form-data" action="<?php echo APP_URL; ?>portal/analises-planos/actions">
                    <input type="hidden" name="csrf_token" value="<?php echo h(gerarCSRF()); ?>">
                    <input type="hidden" name="analise_id" value="<?php echo h($a['id']); ?>">

                    <div class="portal-analysis-identify">
                        <div class="portal-analysis-summary">
                            <strong><?php echo h($a['numero']); ?> · <?php echo h($a['embarcacao_nome']); ?></strong>
                            <span>
                                <?php echo h($a['tipo_processo'] ?: 'Processo em enquadramento'); ?>
                                · <span class="portal-status <?php echo $statusClass; ?>"><?php echo h($statusLabel); ?></span>
                                · Analista: <?php echo h($a['analista_nome'] ?: 'A definir'); ?>
                            </span>
                        </div>

                        <div class="portal-analysis-fields" style="display: flex; gap: 16px; flex-wrap: wrap;">
                            <div style="flex: 1.2; min-width: 260px;">
                                <label for="categoria-<?php echo h($a['id']); ?>" style="font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 6px;">
                                    <i class="fa-solid fa-file-contract text-primary"></i> Tipo de Documento / Categoria *
                                </label>
                                <select id="categoria-<?php echo h($a['id']); ?>" name="categoria" class="form-control" required style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-weight: 500; background: #fff;">
                                    <?php foreach ($todasCategorias as $cat): ?>
                                        <option value="<?php echo h($cat); ?>"><?php echo h($cat); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted" style="display: block; margin-top: 4px; font-size: 0.78rem;">
                                    Selecione o tipo oficial do documento conforme exigido pela NORMAM-202/DPC.
                                </small>
                            </div>
                            <div style="flex: 1; min-width: 240px;">
                                <label for="descricao-<?php echo h($a['id']); ?>" style="font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 6px;">
                                    <i class="fa-regular fa-comment-dots text-secondary"></i> Descrição / Observações (Opcional)
                                </label>
                                <input id="descricao-<?php echo h($a['id']); ?>" name="descricao" maxlength="500" placeholder="Ex.: Prancha inicial ou revisão solicitada" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px;">
                                <small class="text-muted" style="display: block; margin-top: 4px; font-size: 0.78rem;">
                                    Opcional. Se vazio, será identificado automaticamente como Envio do Documento.
                                </small>
                            </div>
                        </div>

                        <!-- Atalhos Rápidos (Chips/Pills) para seleção em 1 clique -->
                        <div style="margin-top: 14px; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <div style="font-size: 0.78rem; font-weight: 600; color: #475569; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-bolt text-warning"></i>
                                <span>Atalhos Rápidos de Documentos (clique para selecionar no formulário):</span>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                <?php foreach ($todasCategorias as $catChip): ?>
                                    <?php 
                                    if ($catChip === 'OUTROS') continue; 
                                    $chipEnviado = isset($catsEnviadas[$catChip]) || isset($catsEnviadas[rtrim($catChip, '.')]) || isset($catsEnviadas[mb_strtoupper($catChip, 'UTF-8')]);
                                    ?>
                                    <button type="button"
                                            class="btn btn-sm"
                                            style="font-size: 0.74rem; padding: 3px 9px; border-radius: 14px; border: 1px solid <?php echo $chipEnviado ? '#86efac' : '#cbd5e1'; ?>; background: <?php echo $chipEnviado ? '#f0fdf4' : '#ffffff'; ?>; color: <?php echo $chipEnviado ? '#166534' : '#334155'; ?>; font-weight: <?php echo $chipEnviado ? '600' : '400'; ?>; cursor: pointer; transition: all 0.15s ease;"
                                            onclick="selecionarCategoriaPortal('<?php echo h($a['id']); ?>', '<?php echo addslashes($catChip); ?>')">
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
                    </div>

                    <div class="portal-upload-section">
                        <h3>Anexe os arquivos</h3>
                        <p>Selecione ou arraste todos os arquivos que fazem parte deste documento ou revisão.</p>
                        <div class="portal-upload-zone" role="button" tabindex="0" aria-label="Selecionar arquivos para a revisão">
                            <input type="file" name="arquivos[]" multiple required accept=".pdf,.jpg,.jpeg,.png,.dwg,.dxf,.doc,.docx,.xls,.xlsx">
                            <div>
                                <i class="fas fa-cloud-arrow-up"></i>
                                <strong>Arraste e solte os arquivos aqui</strong>
                                <span>ou</span><br>
                                <span class="portal-upload-button">Escolher arquivos</span>
                            </div>
                        </div>
                        <p class="portal-upload-help">
                            Formatos aceitos: PDF, DWG, DXF, DOC, DOCX, XLS, XLSX, JPG e PNG.<br>
                            Tamanho máximo por arquivo: 50 MB.
                        </p>
                    </div>

                    <div class="portal-selected-files">
                        <div class="portal-selected-files-header">
                            <span>Arquivos selecionados</span>
                            <span><span data-file-count>0</span> arquivo(s)</span>
                        </div>
                        <div class="portal-file-empty">Nenhum arquivo selecionado.</div>
                        <ul class="portal-file-list" aria-live="polite"></ul>
                    </div>

                    <button class="btn btn-primary portal-analysis-submit" type="submit">
                        <i class="fas fa-upload"></i> Enviar documento para análise
                    </button>
                </form>

                <?php if (!empty($arquivosHistorico)): ?>
                    <div class="portal-uploaded-history" style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                        <h3 style="font-size: 1.05rem; margin-bottom: 6px; color: #1e293b;">
                            <i class="fa-solid fa-clock-rotate-left text-primary"></i> Documentos Já Enviados (Histórico Preservado)
                        </h3>
                        <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 14px;">
                            Abaixo estão os arquivos enviados nas revisões anteriores. Cada arquivo é preservado integralmente no sistema da Amazon Naval.
                        </p>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
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
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; flex-wrap: wrap; gap: 8px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <i class="fa-solid <?php echo $iconeH; ?>" style="font-size: 1.2rem;"></i>
                                        <div>
                                            <strong style="font-size: 0.9rem; color: #1e293b;"><?php echo h($histArq['nome_original']); ?></strong>
                                            <div style="font-size: 0.78rem; color: #64748b;">
                                                <span>Revisão <?php echo (int)$histArq['revisao']; ?></span> ·
                                                <span class="badge" style="background: #e0f2fe; color: #0369a1;"><?php echo h($histArq['categoria'] ?: 'Projeto'); ?></span> ·
                                                <span><?php echo $tFmt; ?></span> ·
                                                <span>Enviado em <?php echo formatarData($histArq['recebido_em']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="badge" style="background: #e2e8f0; color: #334155; font-size: 0.75rem; padding: 4px 8px; border-radius: 4px;">
                                            <i class="fa-solid fa-check"></i> <?php echo h($histArq['classificacao'] ?: 'Recebido / Em análise'); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="portal-analysis-side">
                <!-- Relação NORMAM-202 com status e 1 clique -->
                <section class="portal-side-card">
                    <h2 style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span><i class="fa-solid fa-clipboard-list text-primary"></i> Relação de Documentos</span>
                        <span class="badge" style="background: <?php echo $enviadosCount === $totalObrigatorios ? '#22c55e' : '#2596be'; ?>; color: #fff; font-size: 0.78rem; padding: 3px 8px; border-radius: 6px;">
                            <?php echo $enviadosCount; ?> / <?php echo $totalObrigatorios; ?>
                        </span>
                    </h2>
                    <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 12px;">
                        Relação oficial de documentos de engenharia para enquadramento da embarcação (NORMAM-202). Clique em qualquer item para preencher:
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 6px; max-height: 480px; overflow-y: auto; padding-right: 2px;">
                        <?php foreach ($todasCategorias as $docNormam): ?>
                            <?php 
                            if ($docNormam === 'OUTROS') continue; 
                            $jaEnviado = isset($catsEnviadas[$docNormam]) || isset($catsEnviadas[rtrim($docNormam, '.')]) || isset($catsEnviadas[mb_strtoupper($docNormam, 'UTF-8')]);
                            ?>
                            <div role="button"
                                 tabindex="0"
                                 title="Clique para selecionar este documento para envio"
                                 onclick="selecionarCategoriaPortal('<?php echo h($a['id']); ?>', '<?php echo addslashes($docNormam); ?>')"
                                 style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; border-radius: 6px; font-size: 0.76rem; cursor: pointer; border: 1px solid <?php echo $jaEnviado ? '#bbf7d0' : '#e2e8f0'; ?>; background: <?php echo $jaEnviado ? '#f0fdf4' : '#ffffff'; ?>; transition: all 0.2s ease;">
                                <div style="display: flex; align-items: center; gap: 7px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 75%;">
                                    <i class="fa-solid <?php echo $jaEnviado ? 'fa-check-circle text-success' : 'fa-clock text-secondary'; ?>" style="font-size: 0.85rem; flex-shrink: 0;"></i>
                                    <span style="font-weight: <?php echo $jaEnviado ? '600' : '400'; ?>; color: <?php echo $jaEnviado ? '#166534' : '#334155'; ?>; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo h($docNormam); ?>
                                    </span>
                                </div>
                                <span class="badge" style="flex-shrink: 0; font-size: 0.68rem; padding: 2px 6px; border-radius: 4px; background: <?php echo $jaEnviado ? '#dcfce7' : '#f1f5f9'; ?>; color: <?php echo $jaEnviado ? '#15803d' : '#64748b'; ?>;">
                                    <?php echo $jaEnviado ? 'Enviado' : 'Pendente'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="portal-side-card">
                    <h2>Como funciona</h2>
                    <ol class="portal-steps">
                        <li class="portal-step"><span class="portal-step-number">1</span><div><strong>Selecione o documento</strong><p>Escolha o tipo correspondente na lista da NORMAM-202.</p></div></li>
                        <li class="portal-step"><span class="portal-step-number">2</span><div><strong>Anexe os arquivos</strong><p>Adicione o PDF, pranchas DWG ou memoriais calculados.</p></div></li>
                        <li class="portal-step"><span class="portal-step-number">3</span><div><strong>Envie para análise</strong><p>O Analista Naval é notificado imediatamente no sistema.</p></div></li>
                    </ol>
                </section>

                <section class="portal-side-card">
                    <h2>Resumo da análise atual</h2>
                    <div class="portal-current-summary">
                        <div><span>Análise</span><strong><?php echo h($a['numero']); ?></strong></div>
                        <div><span>Embarcação</span><strong><?php echo h($a['embarcacao_nome']); ?></strong></div>
                        <div><span>Processo</span><strong><?php echo h($a['tipo_processo'] ?: 'Em enquadramento'); ?></strong></div>
                        <div><span>Status</span><strong><span class="portal-status <?php echo $statusClass; ?>"><?php echo h($statusLabel); ?></span></strong></div>
                        <div><span>Analista</span><strong><?php echo h($a['analista_nome'] ?: 'A definir'); ?></strong></div>
                        <div><span>Última revisão</span><strong><?php echo (int) $a['ultima_revisao'] > 0 ? 'Revisão ' . (int) $a['ultima_revisao'] : 'Nenhuma enviada'; ?></strong></div>
                    </div>
                </section>
            </aside>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

<script>
function selecionarCategoriaPortal(analiseId, categoria) {
    const sel = document.getElementById('categoria-' + analiseId);
    if (!sel) return;
    sel.value = categoria;
    sel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    sel.focus();
    sel.style.transition = 'all 0.3s ease';
    sel.style.borderColor = '#2596be';
    sel.style.boxShadow = '0 0 0 3px rgba(37, 150, 190, 0.25)';
    setTimeout(() => {
        sel.style.borderColor = '';
        sel.style.boxShadow = '';
    }, 1200);
}
</script>

<?php require_once __DIR__ . '/../../includes/portal_footer.php'; ?>
