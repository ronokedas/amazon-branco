<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

requireClienteSenhaDefinitiva();

$clienteId = clientePortalId();

$filtros = [
    'busca' => trim($_GET['busca'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
];

$propostas = clientePortalSelectPropostas($pdo, $clienteId, $filtros);

$totalPropostas = count($propostas);
$totalAssinadas = count(array_filter($propostas, fn($p) => in_array($p['status'], ['aprovada', 'assinada'], true)));
$totalEnviadas = count(array_filter($propostas, fn($p) => $p['status'] === 'enviada'));

$valorTotalAprovado = 0.0;
foreach ($propostas as $p) {
    if (in_array($p['status'], ['aprovada', 'assinada'], true)) {
        $valorTotalAprovado += (float)($p['valor_total'] ?? 0);
    }
}

$statusPropostaMap = [
    'rascunho' => ['label' => 'Em Elaboração', 'class' => 'is-warning', 'icon' => 'fa-pen'],
    'enviada' => ['label' => 'Enviada p/ Análise', 'class' => 'is-analysis', 'icon' => 'fa-paper-plane'],
    'aprovada' => ['label' => 'Aprovada', 'class' => 'is-valid', 'icon' => 'fa-circle-check'],
    'assinada' => ['label' => 'Contratada / Assinada', 'class' => 'is-valid', 'icon' => 'fa-file-signature'],
    'recusada' => ['label' => 'Recusada', 'class' => 'is-expired', 'icon' => 'fa-ban'],
    'cancelada' => ['label' => 'Cancelada', 'class' => 'is-expired', 'icon' => 'fa-xmark'],
];

$titulo_page = 'Propostas & Orçamentos - Portal do Cliente';
require_once __DIR__ . '/../../includes/portal_header.php';
?>
<section class="portal-page-header">
    <div>
        <h1>Propostas Comerciais & Orçamentos</h1>
        <p>Consulte suas propostas comerciais, valores de serviços náuticos e acesse os PDFs oficiais.</p>
    </div>
    <div class="portal-page-header-mark"><i class="fa-solid fa-file-invoice-dollar"></i></div>
</section>

<!-- KPIs DE PROPOSTAS -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-solid fa-file-invoice" style="color:var(--p-green)"></i>
        <strong><?php echo $totalPropostas; ?></strong>
        <span>Propostas no<br>cadastro</span>
    </div>
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-solid fa-file-signature" style="color:#10b981"></i>
        <strong><?php echo $totalAssinadas; ?></strong>
        <span>Propostas<br>contratadas</span>
    </div>
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-solid fa-paper-plane" style="color:#0284c7"></i>
        <strong><?php echo $totalEnviadas; ?></strong>
        <span>Em análise<br>comercial</span>
    </div>
    <div class="portal-metric" style="border: 1px solid var(--p-line)!important; border-radius: var(--p-radius)!important;">
        <i class="fa-solid fa-coins" style="color:#16a34a"></i>
        <strong style="font-size:1.4rem;">R$ <?php echo number_format($valorTotalAprovado, 2, ',', '.'); ?></strong>
        <span>Total aprovado<br>em serviços</span>
    </div>
</div>

<!-- FILTROS -->
<form method="GET" class="portal-filters" style="grid-template-columns: 1fr auto auto auto; margin-bottom: 20px;">
    <div class="form-group" style="margin:0;">
        <label for="busca">Buscar Proposta</label>
        <input type="text" id="busca" name="busca" value="<?php echo h($filtros['busca']); ?>" placeholder="Nº da proposta ou observações...">
    </div>
    <div class="form-group" style="margin:0;">
        <label for="status">Situação</label>
        <select id="status" name="status">
            <option value="">Todas as situações</option>
            <?php foreach ($statusPropostaMap as $stKey => $stCfg): ?>
                <option value="<?php echo h($stKey); ?>" <?php echo $filtros['status'] === $stKey ? 'selected' : ''; ?>>
                    <?php echo h($stCfg['label']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
    <a href="<?php echo APP_URL; ?>portal/propostas" class="btn btn-secondary"><i class="fas fa-xmark"></i> Limpar</a>
</form>

<!-- LISTA DE PROPOSTAS -->
<section class="portal-panel">
    <div class="portal-panel-header">
        <h2><i class="fa-solid fa-file-invoice text-success"></i> Relação de Propostas e Orçamentos</h2>
        <span style="color:var(--p-muted); font-size:0.86rem;"><?php echo count($propostas); ?> registro(s)</span>
    </div>

    <?php if (empty($propostas)): ?>
        <div class="portal-empty">
            <i class="fa-solid fa-file-circle-question"></i>
            <h2>Nenhuma proposta comercial encontrada</h2>
            <p>Quando nossa equipe comercial emitir uma proposta para seus serviços náuticos, ela ficará disponível aqui para consulta e download.</p>
        </div>
    <?php else: ?>
        <div class="portal-table-wrap">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Nº Proposta</th>
                        <th>Emissão</th>
                        <th>Validade</th>
                        <th>Condições de Pagamento</th>
                        <th>Valor Total</th>
                        <th>Situação</th>
                        <th style="text-align: right;">Documento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($propostas as $p): ?>
                        <?php $stInfo = $statusPropostaMap[$p['status']] ?? ['label' => ucfirst($p['status']), 'class' => 'is-warning', 'icon' => 'fa-clock']; ?>
                        <tr>
                            <td data-label="Proposta">
                                <strong><?php echo h($p['numero']); ?></strong>
                                <?php if (!empty($p['total_embarcacoes'])): ?>
                                    <small class="portal-doc-sub"><i class="fa-solid fa-ship"></i> <?php echo (int)$p['total_embarcacoes']; ?> embarcação(ões)</small>
                                <?php endif; ?>
                            </td>
                            <td data-label="Emissão"><?php echo formatarData($p['data_emissao']); ?></td>
                            <td data-label="Validade"><?php echo !empty($p['data_validade']) ? formatarData($p['data_validade']) : '-'; ?></td>
                            <td data-label="Condições">
                                <?php 
                                    $forma = $p['forma_pagamento'] ?? 'a_vista';
                                    $formaLabel = match($forma) {
                                        'parcelado' => 'Parcelado em ' . ($p['parcelas'] ?: 1) . 'x',
                                        'boleto' => 'Boleto Bancário',
                                        'pix' => 'Chave PIX',
                                        default => 'À Vista'
                                    };
                                    echo h($formaLabel);
                                ?>
                            </td>
                            <td data-label="Valor Total">
                                <strong style="color:var(--p-green-dark); font-size:1.05rem;">
                                    R$ <?php echo number_format((float)$p['valor_total'], 2, ',', '.'); ?>
                                </strong>
                            </td>
                            <td data-label="Situação">
                                <span class="portal-status <?php echo $stInfo['class']; ?>">
                                    <i class="fa-solid <?php echo $stInfo['icon']; ?>" style="margin-right:4px;"></i> <?php echo h($stInfo['label']); ?>
                                </span>
                            </td>
                            <td data-label="Documento" style="text-align: right;">
                                <a class="btn btn-sm btn-outline-success portal-btn-pdf" target="_blank" href="<?php echo APP_URL; ?>comercial/pdf?id=<?php echo urlencode($p['id']); ?>" title="Visualizar Proposta em PDF">
                                    <i class="fa-solid fa-file-pdf"></i> Proposta PDF
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
