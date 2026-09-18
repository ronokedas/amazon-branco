<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

requireClienteSenhaDefinitiva();

$clienteId = clientePortalId();
$embarcacoes = clientePortalEmbarcacoes($pdo, $clienteId);

$filtros = [
    'busca' => trim($_GET['busca'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'embarcacao_id' => trim($_GET['embarcacao_id'] ?? ''),
];

$dossieSelecionadoId = trim($_GET['id'] ?? '');

$protocolos = clientePortalSelectProtocolos($pdo, $clienteId, $filtros);

// Se houver um dossiê selecionado, carregar suas movimentações e itens
$dossieDetalhe = null;
$movimentacoesDetalhe = [];
$itensCustodiaDetalhe = [];

if ($dossieSelecionadoId !== '') {
    foreach ($protocolos as $p) {
        if ($p['id'] === $dossieSelecionadoId) {
            $dossieDetalhe = $p;
            break;
        }
    }
    // Se não estiver na lista filtrada, busca diretamente garantindo autorização
    if (!$dossieDetalhe) {
        $todos = clientePortalSelectProtocolos($pdo, $clienteId);
        foreach ($todos as $p) {
            if ($p['id'] === $dossieSelecionadoId) {
                $dossieDetalhe = $p;
                break;
            }
        }
    }
    if ($dossieDetalhe) {
        $stMov = $pdo->prepare("SELECT * FROM protocolo_movimentacoes WHERE dossie_id = :id AND status IN ('CONFIRMADA', 'RETIFICADA') ORDER BY sequencia ASC, movimentado_em ASC");
        $stMov->execute([':id' => $dossieDetalhe['id']]);
        $movimentacoesDetalhe = $stMov->fetchAll(PDO::FETCH_ASSOC);

        $stItens = $pdo->prepare("SELECT i.*, m.sequencia AS mov_seq, m.movimentado_em 
                                   FROM protocolo_movimentacao_itens i 
                                   JOIN protocolo_movimentacoes m ON m.id = i.movimentacao_id 
                                  WHERE m.dossie_id = :id AND i.requer_devolucao = 1 
                               ORDER BY i.criado_em ASC");
        $stItens->execute([':id' => $dossieDetalhe['id']]);
        $itensCustodiaDetalhe = $stItens->fetchAll(PDO::FETCH_ASSOC);
    }
}

$statusProtocoloMap = [
    'EM_PREPARACAO' => ['label' => 'Em Preparação', 'class' => 'is-warning', 'icon' => 'fa-pen-to-square'],
    'ENVIADO_AO_ORGAO' => ['label' => 'Enviado à Capitania', 'class' => 'is-analysis', 'icon' => 'fa-paper-plane'],
    'PROTOCOLADO' => ['label' => 'Protocolado no SISAP', 'class' => 'is-valid', 'icon' => 'fa-stamp'],
    'EM_ANALISE_NO_ORGAO' => ['label' => 'Em Análise na Capitania', 'class' => 'is-analysis', 'icon' => 'fa-magnifying-glass'],
    'EM_EXIGENCIA' => ['label' => 'Notificação de Exigência', 'class' => 'is-expired', 'icon' => 'fa-triangle-exclamation'],
    'A_DISPOSICAO' => ['label' => 'Disponível p/ Retirada', 'class' => 'is-valid', 'icon' => 'fa-box-archive'],
    'RETIRADO' => ['label' => 'Retirado na Capitania', 'class' => 'is-valid', 'icon' => 'fa-check-double'],
    'ENTREGUE_AO_CLIENTE' => ['label' => 'Entregue ao Cliente', 'class' => 'is-valid', 'icon' => 'fa-circle-check'],
    'ENCERRADO' => ['label' => 'Concluído', 'class' => 'is-valid', 'icon' => 'fa-circle-check'],
    'CANCELADO' => ['label' => 'Cancelado', 'class' => 'is-expired', 'icon' => 'fa-ban'],
];

// Métricas
$totalGeral = count($protocolos);
$totalTramitando = count(array_filter($protocolos, fn($p) => in_array($p['status'], ['ENVIADO_AO_ORGAO', 'PROTOCOLADO', 'EM_ANALISE_NO_ORGAO'], true)));
$totalExigencia = count(array_filter($protocolos, fn($p) => $p['status'] === 'EM_EXIGENCIA'));
$totalConcluidos = count(array_filter($protocolos, fn($p) => in_array($p['status'], ['RETIRADO', 'ENTREGUE_AO_CLIENTE', 'ENCERRADO'], true)));
$totalCustodia = 0;
foreach ($protocolos as $p) {
    $totalCustodia += (int)($p['originais_sob_custodia'] ?? 0);
}

$titulo_page = 'Trâmites na Capitania (SISAP & Protocolos) - Portal do Cliente';
require_once __DIR__ . '/../../includes/portal_header.php';
?>
<section class="portal-page-header">
    <div>
        <h1>Trâmites na Capitania dos Portos (SISAP)</h1>
        <p>Acompanhe o andamento oficial dos seus processos na Capitania dos Portos, Delegacias e a custódia de documentos originais.</p>
    </div>
    <div class="portal-page-header-mark"><i class="fa-solid fa-landmark-flag"></i></div>
</section>

<!-- KPIs DOS PROTOCOLOS -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-solid fa-folder-tree" style="color:var(--p-green)"></i>
        <strong><?php echo $totalGeral; ?></strong>
        <span>Processos<br>no histórico</span>
    </div>
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-solid fa-landmark-flag" style="color:#0284c7"></i>
        <strong><?php echo $totalTramitando; ?></strong>
        <span>Em trâmite<br>na Marinha</span>
    </div>
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-solid fa-triangle-exclamation" style="color:var(--p-danger)"></i>
        <strong><?php echo $totalExigencia; ?></strong>
        <span>Notificações<br>de exigência</span>
    </div>
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-solid fa-box-archive" style="color:#eab308"></i>
        <strong><?php echo $totalCustodia; ?></strong>
        <span>Vias originais<br>sob custódia</span>
    </div>
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-regular fa-circle-check" style="color:#10b981"></i>
        <strong><?php echo $totalConcluidos; ?></strong>
        <span>Processos<br>concluídos</span>
    </div>
</div>

<!-- FILTROS -->
<form method="GET" class="portal-filters" style="grid-template-columns: minmax(200px, 1fr) minmax(180px, auto) minmax(180px, auto) auto auto; margin-bottom: 20px;">
    <div class="form-group" style="margin:0;">
        <label for="busca">Buscar Processo</label>
        <input type="text" id="busca" name="busca" value="<?php echo h($filtros['busca']); ?>" placeholder="Nº dossiê, SISAP ou assunto...">
    </div>
    <div class="form-group" style="margin:0;">
        <label for="embarcacao_id">Embarcação</label>
        <select id="embarcacao_id" name="embarcacao_id">
            <option value="">Todas as embarcações</option>
            <?php foreach ($embarcacoes as $emb): ?>
                <option value="<?php echo h($emb['id']); ?>" <?php echo $filtros['embarcacao_id'] === $emb['id'] ? 'selected' : ''; ?>>
                    <?php echo h($emb['nome']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group" style="margin:0;">
        <label for="status">Situação</label>
        <select id="status" name="status">
            <option value="">Todas as situações</option>
            <?php foreach ($statusProtocoloMap as $stKey => $stCfg): ?>
                <option value="<?php echo h($stKey); ?>" <?php echo $filtros['status'] === $stKey ? 'selected' : ''; ?>>
                    <?php echo h($stCfg['label']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
    <a href="<?php echo APP_URL; ?>portal/protocolos" class="btn btn-secondary"><i class="fas fa-xmark"></i> Limpar</a>
</form>

<?php if ($dossieDetalhe): ?>
    <!-- DETALHE EXPANDIDO DO DOSSIÊ SELECIONADO -->
    <section class="portal-panel" style="margin-bottom: 24px; border-left: 5px solid var(--p-green)!important;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:16px; border-bottom:1px solid var(--p-line); padding-bottom:12px;">
            <div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="font-size:0.85rem; color:var(--p-muted); text-transform:uppercase; font-weight:700;">Detalhes do Dossiê</span>
                    <span class="portal-status <?php echo ($statusProtocoloMap[$dossieDetalhe['status']]['class'] ?? 'is-valid'); ?>">
                        <?php echo h($statusProtocoloMap[$dossieDetalhe['status']]['label'] ?? $dossieDetalhe['status']); ?>
                    </span>
                </div>
                <h2 style="margin:4px 0; font-size:1.4rem; color:var(--p-ink); font-weight:800;"><?php echo h($dossieDetalhe['numero']); ?> - <?php echo h($dossieDetalhe['assunto']); ?></h2>
                <div style="font-size:0.86rem; color:var(--p-muted);">
                    <i class="fa-solid fa-ship"></i> <strong><?php echo h($dossieDetalhe['embarcacao_nome'] ?: 'Embarcação não vinculada'); ?></strong> &bull;
                    <i class="fa-solid fa-landmark"></i> <?php echo h($dossieDetalhe['unidade_maritima_nome'] ?: 'Capitania dos Portos'); ?>
                </div>
            </div>
            <div>
                <a href="<?php echo APP_URL; ?>portal/protocolos" class="btn btn-sm btn-secondary">
                    <i class="fa-solid fa-xmark"></i> Fechar Detalhes
                </a>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; background:var(--p-soft); padding:14px; border-radius:10px; margin-bottom:20px; font-size:0.85rem;">
            <div>
                <span style="color:var(--p-muted); display:block;">Processo SISAP Marinha</span>
                <strong style="color:var(--p-ink); font-size:1rem;">
                    <?php echo !empty($dossieDetalhe['protocolo_externo_numero']) ? h($dossieDetalhe['protocolo_externo_numero']) : 'Aguardando protocolo no órgão'; ?>
                </strong>
            </div>
            <div>
                <span style="color:var(--p-muted); display:block;">Data de Protocolo</span>
                <strong style="color:var(--p-ink);"><?php echo !empty($dossieDetalhe['protocolo_externo_em']) ? formatarData($dossieDetalhe['protocolo_externo_em']) : '-'; ?></strong>
            </div>
            <div>
                <span style="color:var(--p-muted); display:block;">Total de Movimentações</span>
                <strong style="color:var(--p-ink);"><?php echo count($movimentacoesDetalhe); ?> evento(s) confirmados</strong>
            </div>
            <div>
                <span style="color:var(--p-muted); display:block;">Custódia de Documentos Físicos</span>
                <strong style="color:var(--p-ink);"><?php echo count($itensCustodiaDetalhe); ?> via(s) original(is) registradas</strong>
            </div>
        </div>

        <!-- CUSTÓDIA DE ORIGINAIS -->
        <?php if (!empty($itensCustodiaDetalhe)): ?>
            <div style="margin-bottom: 20px;">
                <h3 style="font-size:1rem; color:var(--p-ink); margin-bottom:8px;">
                    <i class="fa-solid fa-lock text-warning"></i> Vias Físicas Originais sob Custódia
                </h3>
                <div class="portal-table-wrap">
                    <table class="portal-table portal-table-compact">
                        <thead>
                            <tr>
                                <th>Item / Documento</th>
                                <th>Categoria</th>
                                <th>Condição</th>
                                <th>Situação da Custódia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itensCustodiaDetalhe as $it): ?>
                                <tr>
                                    <td><strong><?php echo h($it['descricao']); ?></strong></td>
                                    <td><?php echo h($it['categoria'] ?: 'DOCUMENTO_PESSOAL'); ?></td>
                                    <td><?php echo h($it['condicao_documento'] ?: 'BOM_ESTADO'); ?></td>
                                    <td>
                                        <?php if (!empty($it['devolvido_em'])): ?>
                                            <span class="portal-status is-valid"><i class="fa-solid fa-check"></i> Devolvido em <?php echo formatarData($it['devolvido_em']); ?></span>
                                        <?php else: ?>
                                            <span class="portal-status is-warning"><i class="fa-solid fa-lock"></i> Sob Custódia Operacional</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- LINHA DO TEMPO DAS MOVIMENTAÇÕES -->
        <div>
            <h3 style="font-size:1rem; color:var(--p-ink); margin-bottom:12px;">
                <i class="fa-solid fa-timeline text-primary"></i> Histórico Oficial de Movimentações
            </h3>
            <?php if (empty($movimentacoesDetalhe)): ?>
                <p style="color:var(--p-muted); font-size:0.86rem;">Nenhuma movimentação pública registrada ainda.</p>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <?php foreach ($movimentacoesDetalhe as $m): ?>
                        <div style="border:1px solid var(--p-line); border-radius:8px; padding:12px 16px; background:#fff; display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                            <div>
                                <span style="font-size:0.78rem; font-weight:700; color:var(--p-green-dark); text-transform:uppercase;">
                                    Movimentação #<?php echo sprintf('%02d', $m['sequencia']); ?> &bull; <?php echo h(str_replace('_', ' ', $m['natureza'])); ?>
                                </span>
                                <div style="font-size:0.92rem; color:var(--p-ink); font-weight:700; margin:2px 0;">
                                    Origem: <?php echo h($m['origem_nome'] ?: $m['origem_tipo']); ?> &rarr; Destino: <?php echo h($m['destino_nome'] ?: $m['destino_tipo']); ?>
                                </div>
                                <?php if (!empty($m['observacoes'])): ?>
                                    <p style="margin:4px 0 0; font-size:0.84rem; color:var(--p-muted);"><?php echo nl2br(h($m['observacoes'])); ?></p>
                                <?php endif; ?>
                            </div>
                            <div style="text-align:right; font-size:0.82rem; color:var(--p-muted); white-space:nowrap;">
                                <i class="fa-regular fa-clock"></i> <?php echo formatarDataHora($m['movimentado_em']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<!-- LISTA DE PROTOCOLOS -->
<section class="portal-panel">
    <?php if (empty($protocolos)): ?>
        <div class="portal-empty">
            <i class="fa-solid fa-landmark-flag"></i>
            <h2>Nenhum processo encontrado</h2>
            <p><?php echo ($filtros['busca'] !== '' || $filtros['status'] !== '' || $filtros['embarcacao_id'] !== '') ? 'Nenhum processo encontrado para os filtros selecionados. Tente limpar os filtros.' : 'Quando houver dossiês abertos para suas embarcações na Capitania, eles aparecerão aqui com status em tempo real.'; ?></p>
        </div>
    <?php else: ?>
        <div class="portal-table-wrap">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Dossiê / Assunto</th>
                        <th>Embarcação</th>
                        <th>Capitania / Órgão</th>
                        <th>Processo SISAP</th>
                        <th>Situação</th>
                        <th>Custódia de Vias</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($protocolos as $prot): ?>
                        <?php $stInfo = $statusProtocoloMap[$prot['status']] ?? ['label' => $prot['status'], 'class' => 'is-warning', 'icon' => 'fa-clock']; ?>
                        <tr>
                            <td data-label="Dossiê / Assunto">
                                <a href="<?php echo APP_URL; ?>portal/protocolos?id=<?php echo urlencode($prot['id']); ?>" style="text-decoration:none; color:inherit;">
                                    <strong><?php echo h($prot['numero']); ?></strong>
                                    <small class="portal-doc-sub"><?php echo h($prot['assunto']); ?></small>
                                </a>
                            </td>
                            <td data-label="Embarcação">
                                <i class="fa-solid fa-ship portal-table-icon"></i>
                                <strong><?php echo h($prot['embarcacao_nome'] ?: '-'); ?></strong>
                                <small style="display:block; font-size:11px; color:var(--p-muted);"><?php echo h($prot['embarcacao_registro'] ?: ''); ?></small>
                            </td>
                            <td data-label="Órgão"><?php echo h($prot['unidade_maritima_nome'] ?: 'Capitania dos Portos'); ?></td>
                            <td data-label="SISAP">
                                <?php if (!empty($prot['protocolo_externo_numero'])): ?>
                                    <span class="badge-sisap"><i class="fa-solid fa-fingerprint"></i> <?php echo h($prot['protocolo_externo_numero']); ?></span>
                                    <?php if (!empty($prot['protocolo_externo_em'])): ?>
                                        <small style="display:block; font-size:11px; color:var(--p-muted); margin-top:2px;">Entrada: <?php echo formatarData($prot['protocolo_externo_em']); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:var(--p-muted); font-size:12px;">Aguardando entrada</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Situação">
                                <span class="portal-status <?php echo $stInfo['class']; ?>">
                                    <i class="fa-solid <?php echo $stInfo['icon']; ?>" style="margin-right:4px;"></i> <?php echo h($stInfo['label']); ?>
                                </span>
                            </td>
                            <td data-label="Custódia">
                                <?php if ((int)$prot['originais_sob_custodia'] > 0): ?>
                                    <span class="badge-custodia-warn" title="Via original sob custódia">
                                        <i class="fa-solid fa-lock"></i> <?php echo (int)$prot['originais_sob_custodia']; ?> original(is)
                                    </span>
                                <?php else: ?>
                                    <span style="color:var(--p-muted); font-size:12px;">Sem pendências</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Ações" style="text-align: right;">
                                <a class="btn btn-sm btn-outline-secondary btn-has-text" href="<?php echo APP_URL; ?>portal/protocolos?id=<?php echo urlencode($prot['id']); ?>" title="Ver detalhes do processo">
                                    <i class="fa-solid fa-eye"></i> Ver Trâmite
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../../includes/portal_footer.php'; ?>
