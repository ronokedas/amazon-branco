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

$vistorias = clientePortalSelectVistorias($pdo, $clienteId, $filtros);
$agendamentos = clientePortalSelectAgendamentos($pdo, $clienteId);

$totalVistorias = count($vistorias);
$totalAprovadas = count(array_filter($vistorias, fn($v) => in_array($v['status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS'], true)));
$totalExigencias = count(array_filter($vistorias, fn($v) => in_array($v['status'], ['APROVADA_COM_EXIGENCIAS', 'RETORNO_AS'], true)));

$statusVistoriaMap = [
    'PENDENTE' => ['label' => 'Aguardando Aprovação', 'class' => 'is-warning'],
    'AGUARDANDO_APROVACAO' => ['label' => 'Em Análise Técnica', 'class' => 'is-analysis'],
    'APROVADA' => ['label' => 'Aprovada', 'class' => 'is-valid'],
    'APROVADA_COM_EXIGENCIAS' => ['label' => 'Aprovada c/ Exigências', 'class' => 'is-warning'],
    'RETORNO_AS' => ['label' => 'Exigências Cumpridas', 'class' => 'is-valid'],
    'REPROVADA' => ['label' => 'Reprovada', 'class' => 'is-expired'],
    'CANCELADA' => ['label' => 'Cancelada', 'class' => 'is-expired'],
];

$titulo_page = 'Vistorias & Agendamentos - Portal do Cliente';
require_once __DIR__ . '/../../includes/portal_header.php';
?>
<section class="portal-page-header">
    <div>
        <h1>Vistorias & Agendamentos Técnicos</h1>
        <p>Acompanhe vistorias de campo agendadas para sua frota e consulte relatórios técnicos concluídos.</p>
    </div>
    <div class="portal-page-header-mark"><i class="fa-solid fa-clipboard-check"></i></div>
</section>

<!-- KPIs DE VISTORIAS -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-regular fa-calendar-check" style="color:#0284c7"></i>
        <strong><?php echo count($agendamentos); ?></strong>
        <span>Vistorias<br>agendadas</span>
    </div>
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-solid fa-clipboard-list" style="color:var(--p-green)"></i>
        <strong><?php echo $totalVistorias; ?></strong>
        <span>Relatórios no<br>histórico</span>
    </div>
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-regular fa-circle-check" style="color:#10b981"></i>
        <strong><?php echo $totalAprovadas; ?></strong>
        <span>Vistorias<br>homologadas</span>
    </div>
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-solid fa-triangle-exclamation" style="color:#eab308"></i>
        <strong><?php echo $totalExigencias; ?></strong>
        <span>Com exigências<br>técnicas</span>
    </div>
</div>

<!-- AGENDAMENTOS FUTUROS (SE HOUVER) -->
<?php if (!empty($agendamentos)): ?>
    <section class="portal-panel" style="margin-bottom: 24px;">
        <div class="portal-panel-header">
            <h2><i class="fa-regular fa-calendar-days text-primary"></i> Próximos Agendamentos de Vistoria</h2>
            <span class="portal-status is-analysis"><?php echo count($agendamentos); ?> agendamento(s)</span>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:16px; margin-top:14px;">
            <?php foreach ($agendamentos as $ag): ?>
                <div style="border:1px solid var(--p-line); border-radius:10px; padding:16px; background:var(--p-soft);">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                        <div>
                            <strong style="font-size:1.1rem; color:var(--p-ink); display:block;">
                                <i class="fa-solid fa-ship"></i> <?php echo h($ag['embarcacao_nome'] ?: 'Embarcação a confirmar'); ?>
                            </strong>
                            <small style="color:var(--p-muted);"><?php echo h($ag['tipo_vistoria'] ?: 'Vistoria Estatutária Naval'); ?></small>
                        </div>
                        <span class="portal-status is-valid" style="text-transform:capitalize;"><?php echo h($ag['status']); ?></span>
                    </div>
                    <div style="font-size:0.86rem; color:var(--p-muted); display:grid; gap:4px; margin-bottom:8px;">
                        <span><i class="fa-regular fa-calendar"></i> <strong>Data:</strong> <?php echo formatarData($ag['data_vistoria']); ?><?php echo !empty($ag['hora_vistoria']) ? ' às ' . substr($ag['hora_vistoria'], 0, 5) : ''; ?></span>
                        <span><i class="fa-solid fa-location-dot"></i> <strong>Local:</strong> <?php echo h($ag['local'] ?: 'A combinar com o cliente'); ?></span>
                        <?php if (!empty($ag['vistoriador_nome'])): ?>
                            <span><i class="fa-solid fa-user-tie"></i> <strong>Vistoriador:</strong> <?php echo h($ag['vistoriador_nome']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- FILTROS DE RELATÓRIOS -->
<form method="GET" class="portal-filters" style="grid-template-columns: minmax(200px, 1fr) minmax(180px, auto) minmax(180px, auto) auto auto; margin-bottom: 20px;">
    <div class="form-group" style="margin:0;">
        <label for="busca">Buscar Relatório</label>
        <input type="text" id="busca" name="busca" value="<?php echo h($filtros['busca']); ?>" placeholder="Nº do relatório ou embarcação...">
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
            <?php foreach ($statusVistoriaMap as $stKey => $stCfg): ?>
                <option value="<?php echo h($stKey); ?>" <?php echo $filtros['status'] === $stKey ? 'selected' : ''; ?>>
                    <?php echo h($stCfg['label']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
    <a href="<?php echo APP_URL; ?>portal/vistorias" class="btn btn-secondary"><i class="fas fa-xmark"></i> Limpar</a>
</form>

<!-- LISTA DE RELATÓRIOS DE VISTORIA -->
<section class="portal-panel">
    <div class="portal-panel-header">
        <h2><i class="fa-solid fa-clipboard-list text-success"></i> Relatórios Técnicos de Vistoria</h2>
        <span style="color:var(--p-muted); font-size:0.86rem;"><?php echo count($vistorias); ?> relatório(s)</span>
    </div>

    <?php if (empty($vistorias)): ?>
        <div class="portal-empty">
            <i class="fa-solid fa-clipboard-question"></i>
            <h2>Nenhum relatório de vistoria encontrado</h2>
            <p>Quando os vistoriadores concluírem as vistorias de campo e a homologação técnica, os relatórios oficiais aparecerão aqui.</p>
        </div>
    <?php else: ?>
        <div class="portal-table-wrap">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Relatório / Finalidade</th>
                        <th>Embarcação</th>
                        <th>Data da Vistoria</th>
                        <th>Vistoriador</th>
                        <th>Situação</th>
                        <th style="text-align: right;">Documento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vistorias as $v): ?>
                        <?php $stInfo = $statusVistoriaMap[$v['status']] ?? ['label' => $v['status'], 'class' => 'is-warning']; ?>
                        <tr>
                            <td data-label="Relatório">
                                <strong><?php echo h($v['numero']); ?></strong>
                                <small class="portal-doc-sub"><?php echo h($v['finalidade'] === 'CUMPRIMENTO_EXIGENCIAS' ? 'Cumprimento de Exigências' : 'Vistoria Técnica Naval'); ?></small>
                            </td>
                            <td data-label="Embarcação">
                                <i class="fa-solid fa-ship portal-table-icon"></i>
                                <strong><?php echo h($v['embarcacao_nome'] ?: '-'); ?></strong>
                                <small style="display:block; font-size:11px; color:var(--p-muted);"><?php echo h($v['embarcacao_registro'] ?: ''); ?></small>
                            </td>
                            <td data-label="Data"><?php echo formatarData($v['data_vistoria']); ?></td>
                            <td data-label="Vistoriador"><?php echo h($v['vistoriador_nome'] ?: 'Vistoriador Amazon'); ?></td>
                            <td data-label="Situação">
                                <span class="portal-status <?php echo $stInfo['class']; ?>">
                                    <?php echo h($stInfo['label']); ?>
                                </span>
                            </td>
                            <td data-label="Documento" style="text-align: right;">
                                <a class="btn btn-sm btn-outline-success portal-btn-pdf" target="_blank" href="<?php echo APP_URL; ?>portal/documentos/pdf?tipo=rel_vistoria&id=<?php echo urlencode($v['id']); ?>" title="Visualizar Relatório em PDF">
                                    <i class="fa-solid fa-file-pdf"></i> PDF
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
