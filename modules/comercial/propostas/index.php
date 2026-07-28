<?php
/**
 * MÓDULO: COMERCIAL > PROPOSTAS
 * Arquivo: index.php - Listagem de propostas com filtros
 * Acesso: ADMIN (completo) | VISTORIADOR (apenas visualização)
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

verificar_sessao();
$cargo = getCargo();
exigirAcesso('comercial');

// ============================================
// FILTROS
// ============================================
$filtro_status   = $_GET['status'] ?? 'todos';
$filtro_busca    = trim($_GET['busca'] ?? '');
$filtro_data_ini = $_GET['data_ini'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';

$where = [];
$params = [];

if ($cargo === 'VENDEDOR') {
    $where[] = 'p.criado_por = :vendedor_id';
    $params[':vendedor_id'] = $_SESSION['usuario_id'];
}

if ($filtro_status !== 'todos') {
    $where[] = 'p.status = :status';
    $params[':status'] = $filtro_status;
}

if (!empty($filtro_busca)) {
    $where[] = '(c.nome LIKE :busca OR p.numero LIKE :busca2)';
    $params[':busca']  = '%' . $filtro_busca . '%';
    $params[':busca2'] = '%' . $filtro_busca . '%';
}

if (!empty($filtro_data_ini)) {
    $where[] = 'p.data_emissao >= :data_ini';
    $params[':data_ini'] = $filtro_data_ini;
}

if (!empty($filtro_data_fim)) {
    $where[] = 'p.data_emissao <= :data_fim';
    $params[':data_fim'] = $filtro_data_fim;
}

$sqlWhere = '';
if (!empty($where)) {
    $sqlWhere = 'WHERE ' . implode(' AND ', $where);
}

// ============================================
// BUSCAR PROPOSTAS
// ============================================
try {
    $sql = "SELECT p.*, c.nome AS cliente_nome, c.cpf_cnpj AS cliente_cpfcnpj,
                   c.email AS cliente_email
            FROM propostas p
            INNER JOIN clientes c ON c.id = p.cliente_id
            {$sqlWhere}
            ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $propostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar embarcações de cada proposta
    $embarcacoesPorProposta = [];
    if (!empty($propostas)) {
        $ids = array_column($propostas, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtEmb = $pdo->prepare("
            SELECT pe.proposta_id, e.nome, e.registro
            FROM propostas_embarcacoes pe
            INNER JOIN embarcacoes e ON e.id = pe.embarcacao_id
            WHERE pe.proposta_id IN ({$placeholders})
            ORDER BY e.nome ASC
        ");
        $stmtEmb->execute(array_values($ids));
        $vinculos = $stmtEmb->fetchAll(PDO::FETCH_ASSOC);

        foreach ($vinculos as $v) {
            $pid = $v['proposta_id'];
            if (!isset($embarcacoesPorProposta[$pid])) {
                $embarcacoesPorProposta[$pid] = [];
            }
            $embarcacoesPorProposta[$pid][] = $v['nome'] . (!empty($v['registro']) ? ' (' . $v['registro'] . ')' : '');
        }
    }
} catch (Exception $e) {
    error_log('Erro ao listar propostas: ' . $e->getMessage());
    $propostas = [];
    $embarcacoesPorProposta = [];
}

// Status com labels e cores
$statusConfig = [
    'rascunho'  => ['label' => 'Rascunho',  'cor' => 'secondary'],
    'enviada'   => ['label' => 'Enviada',   'cor' => 'info'],
    'aprovada'  => ['label' => 'Aprovada',  'cor' => 'success'],
    'assinada'  => ['label' => 'Assinada',  'cor' => 'success'],
    'recusada'  => ['label' => 'Recusada',  'cor' => 'danger'],
    'cancelada' => ['label' => 'Cancelada', 'cor' => 'warning'],
];

// Modo visualização de detalhe?
$visualizar = isset($_GET['visualizar']) && !empty($_GET['id']);
$propostaDetalhe = null;
$servicosDetalhe = [];

if ($visualizar) {
    $idDetalhe = $_GET['id'] ?? '';
    if (!empty($idDetalhe)) {
        // Buscar proposta
        $stmtDet = $pdo->prepare("
            SELECT p.*, c.nome AS cliente_nome, c.cpf_cnpj AS cliente_cpfcnpj,
                   c.telefone AS cliente_telefone, c.email AS cliente_email
            FROM propostas p
            INNER JOIN clientes c ON c.id = p.cliente_id
            WHERE p.id = :id
        ");
        $stmtDet->execute([':id' => $idDetalhe]);
        $propostaDetalhe = $stmtDet->fetch(PDO::FETCH_ASSOC);

        if ($propostaDetalhe) {
            // Buscar serviços do detalhe
            $stmtServDet = $pdo->prepare("
                SELECT ps.*, s.nome AS servico_nome, e.nome AS embarcacao_nome, e.registro AS embarcacao_registro
                FROM propostas_servicos ps
                INNER JOIN servicos s ON s.id = ps.servico_id
                LEFT JOIN embarcacoes e ON e.id = ps.embarcacao_id
                WHERE ps.proposta_id = :pid
                ORDER BY ps.embarcacao_id, s.nome ASC
            ");
            $stmtServDet->execute([':pid' => $idDetalhe]);
            $servicosDetalhe = $stmtServDet->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

$titulo_page = 'Propostas - ERP Sistema';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">

    <?php if ($visualizar && $propostaDetalhe): ?>
    <!-- ===== MODO VISUALIZAÇÃO DE DETALHE ===== -->
    <div class="welcome-section" style="margin-bottom: 20px;">
        <div>
            <h1><i class="fas fa-file-invoice"></i> Proposta <?php echo h($propostaDetalhe['numero']); ?></h1>
            <p>
                <span class="badge badge-<?php echo $statusConfig[$propostaDetalhe['status']]['cor'] ?? 'secondary'; ?>">
                    <?php echo $statusConfig[$propostaDetalhe['status']]['label'] ?? $propostaDetalhe['status']; ?>
                </span>
                &middot; Emitida em <?php echo date('d/m/Y', strtotime($propostaDetalhe['data_emissao'])); ?>
                &middot; Válida até <?php echo date('d/m/Y', strtotime($propostaDetalhe['data_validade'] ?? '')); ?>
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <?php
                $podeEditarDetalhe = in_array($cargo, ['ADMIN', 'VENDEDOR'], true)
                    && ($cargo === 'ADMIN' || ($propostaDetalhe['criado_por'] ?? '') === ($_SESSION['usuario_id'] ?? ''))
                    && ($propostaDetalhe['status'] ?? '') === 'rascunho'
                    && empty($propostaDetalhe['assinado']);
            ?>
            <?php if ($podeEditarDetalhe): ?>
            <a href="<?php echo APP_URL; ?>comercial/nova?id=<?php echo urlencode($idDetalhe); ?>" class="btn btn-primary">
                <i class="fas fa-pen"></i> Editar
            </a>
            <?php endif; ?>
            <a href="<?php echo APP_URL; ?>comercial/pdf?id=<?php echo urlencode($idDetalhe); ?>" class="btn btn-primary" target="_blank">
                <i class="fas fa-file-pdf"></i> Gerar PDF
            </a>
            <?php if (in_array($cargo, ['ADMIN', 'VENDEDOR']) && !empty($propostaDetalhe['cliente_email'])): ?>
            <form method="POST" action="<?php echo APP_URL; ?>comercial/propostas/actions" style="display: inline;"
                  onsubmit="return confirm('Enviar proposta <?php echo h(addslashes($propostaDetalhe['numero'])); ?> por e-mail para <?php echo h(addslashes($propostaDetalhe['cliente_email'])); ?>?')">
                <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                <input type="hidden" name="action" value="enviar_proposta">
                <input type="hidden" name="id" value="<?php echo h($idDetalhe); ?>">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-envelope"></i> Enviar Proposta
                </button>
            </form>
            <?php elseif (in_array($cargo, ['ADMIN', 'VENDEDOR'])): ?>
            <button type="button" class="btn btn-secondary" disabled title="Cliente sem e-mail cadastrado">
                <i class="fas fa-envelope"></i> Sem e-mail
            </button>
            <?php endif; ?>
            <a href="<?php echo APP_URL; ?>comercial/propostas" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Dados do Cliente -->
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">
            <h3><i class="fas fa-user-tie"></i> Dados do Cliente</h3>
        </div>
        <div class="card-body" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 16px 20px;">
            <div>
                <small class="text-muted">Nome</small>
                <div style="font-weight: 600;"><?php echo h($propostaDetalhe['cliente_nome']); ?></div>
            </div>
            <div>
                <small class="text-muted">CPF/CNPJ</small>
                <div><?php echo h($propostaDetalhe['cliente_cpfcnpj'] ?? '-'); ?></div>
            </div>
            <div>
                <small class="text-muted">Telefone</small>
                <div><?php echo h($propostaDetalhe['cliente_telefone'] ?? '-'); ?></div>
            </div>
            <div>
                <small class="text-muted">Email</small>
                <div><?php echo h($propostaDetalhe['cliente_email'] ?? '-'); ?></div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">
            <h3><i class="fas fa-user-check"></i> Responsável pelo fechamento da proposta</h3>
        </div>
        <div class="card-body" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 16px 20px;">
            <div>
                <small class="text-muted">Nome</small>
                <div style="font-weight: 600;"><?php echo h($propostaDetalhe['responsavel_fechamento_nome'] ?? '-'); ?></div>
            </div>
            <div>
                <small class="text-muted">Telefone</small>
                <div><?php echo h($propostaDetalhe['responsavel_fechamento_telefone'] ?? '-'); ?></div>
            </div>
        </div>
    </div>

    <!-- Serviços -->
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">
            <h3><i class="fas fa-list-check"></i> Serviços Contratados</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($servicosDetalhe)): ?>
                <div style="padding: 30px; text-align: center; color: var(--cor-texto-secundario);">
                    <i class="fas fa-info-circle"></i> Nenhum serviço vinculado.
                </div>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--cor-sidebar); border-bottom: 2px solid var(--cor-borda);">
                            <th style="text-align: left; padding: 10px 15px;">Serviço</th>
                            <th style="text-align: center; padding: 10px 15px; width: 80px;">Qtd</th>
                            <th style="text-align: right; padding: 10px 15px; width: 120px;">Preço Unit.</th>
                            <th style="text-align: right; padding: 10px 15px; width: 120px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotalDetalhe = 0;
                        $ultimaEmb = '';
                        foreach ($servicosDetalhe as $sv): 
                            $preco = (float)$sv['preco_aplicado'];
                            $qtd = (int)$sv['quantidade'];
                            $sub = round($preco * $qtd, 2);
                            $subtotalDetalhe += $sub;
                            $embLabel = !empty($sv['embarcacao_nome']) ? $sv['embarcacao_nome'] . (!empty($sv['embarcacao_registro']) ? ' (' . $sv['embarcacao_registro'] . ')' : '') : '';
                        ?>
                            <?php if ($embLabel !== '' && $embLabel !== $ultimaEmb): $ultimaEmb = $embLabel; ?>
                            <tr style="background: rgba(46,204,113,0.05);">
                                <td colspan="4" style="padding: 8px 15px; font-weight: 600; color: var(--cor-destaque); font-size: 0.85rem;">
                                    <i class="fas fa-ship"></i> <?php echo h($embLabel); ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr style="border-bottom: 1px solid var(--cor-borda);">
                                <td style="padding: 8px 15px;"><?php echo h($sv['servico_nome']); ?></td>
                                <td style="text-align: center; padding: 8px 15px;"><?php echo $qtd; ?></td>
                                <td style="text-align: right; padding: 8px 15px;">R$ <?php echo number_format($preco, 2, ',', '.'); ?></td>
                                <td style="text-align: right; padding: 8px 15px; font-weight: 600;">R$ <?php echo number_format($sub, 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Totais do detalhe -->
                <div style="padding: 16px 20px; background: var(--cor-sidebar); border-top: 2px solid var(--cor-borda);">
                    <?php
                    $descPercDet = (float)($propostaDetalhe['desconto_percentual'] ?? 0);
                    $descValDet = round($subtotalDetalhe * ($descPercDet / 100), 2);
                    $totalDet = round($subtotalDetalhe - $descValDet, 2);
                    $parcDet = (int)$propostaDetalhe['parcelas'];
                    $valParcDet = ($parcDet > 0) ? round($totalDet / $parcDet, 2) : $totalDet;
                    ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span class="text-muted">Subtotal</span>
                        <span style="font-weight: 600;">R$ <?php echo number_format($subtotalDetalhe, 2, ',', '.'); ?></span>
                    </div>
                    <?php if ($descValDet > 0): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span class="text-muted">Desconto (<?php echo number_format($descPercDet, 2, ',', '.'); ?>%)</span>
                        <span style="font-weight: 600; color: var(--cor-erro);">- R$ <?php echo number_format($descValDet, 2, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>
                    <div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px solid var(--cor-borda);">
                        <span style="font-weight: 700; color: var(--cor-destaque);">TOTAL GERAL</span>
                        <span style="font-size: 1.3rem; font-weight: 700; color: var(--cor-destaque);">R$ <?php echo number_format($totalDet, 2, ',', '.'); ?></span>
                    </div>
                    <div style="margin-top: 8px; font-size: 0.85rem; color: var(--cor-texto-secundario);">
                        <?php echo $parcDet; ?>x de R$ <?php echo number_format($valParcDet, 2, ',', '.'); ?> &middot; Forma: <?php
                            $fpMap = ['a_vista' => 'À Vista', 'parcelado' => 'Parcelado', 'boleto' => 'Boleto', 'pix' => 'PIX'];
                            echo $fpMap[$propostaDetalhe['forma_pagamento']] ?? $propostaDetalhe['forma_pagamento'];
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Observações -->
    <?php if (!empty($propostaDetalhe['observacoes'])): ?>
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">
            <h3><i class="fas fa-sticky-note"></i> Observações</h3>
        </div>
        <div class="card-body" style="padding: 16px 20px;">
            <p style="white-space: pre-wrap; margin: 0;"><?php echo nl2br(h($propostaDetalhe['observacoes'])); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- ===== MODO LISTAGEM ===== -->
    <div class="welcome-section" style="margin-bottom: 20px;">
        <div>
            <h1><i class="fas fa-file-invoice"></i> Propostas</h1>
            <p>Gerencie as propostas comerciais do sistema.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="<?php echo APP_URL; ?>comercial/nova" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nova Proposta
            </a>
            <a href="<?php echo APP_URL; ?>comercial" class="btn btn-secondary">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
        </div>
    </div>

    <div class="tabela-container">
        <div class="tabela-header">
            <h3><i class="fas fa-list"></i> Todas as Propostas</h3>
        </div>

        <!-- Filtros -->
        <div class="filtros" style="margin: 15px 20px; display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label><i class="fas fa-search"></i> Buscar</label>
                <input type="text" id="buscaProposta" placeholder="Nome do cliente ou número da proposta..."
                       value="<?php echo h($filtro_busca); ?>"
                       onkeydown="if(event.key==='Enter')filtrarPropostas()">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                <label><i class="fas fa-filter"></i> Status</label>
                <select id="filtroStatus" onchange="filtrarPropostas()">
                    <option value="todos" <?php echo $filtro_status === 'todos' ? 'selected' : ''; ?>>Todos</option>
                    <?php foreach ($statusConfig as $val => $cfg): ?>
                        <option value="<?php echo $val; ?>" <?php echo $filtro_status === $val ? 'selected' : ''; ?>>
                            <?php echo $cfg['label']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 130px;">
                <label><i class="fas fa-calendar"></i> Data Início</label>
                <input type="date" id="filtroDataIni" value="<?php echo h($filtro_data_ini); ?>" onchange="filtrarPropostas()">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 130px;">
                <label><i class="fas fa-calendar"></i> Data Fim</label>
                <input type="date" id="filtroDataFim" value="<?php echo h($filtro_data_fim); ?>" onchange="filtrarPropostas()">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 90px; display: flex; align-items: flex-end;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="limparFiltros()" style="width: 100%;">
                    <i class="fas fa-times"></i> Limpar
                </button>
            </div>
        </div>

        <?php if (empty($propostas)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-file-invoice"></i>
                <h3>Nenhuma proposta encontrada</h3>
                <p>
                    <?php if ($filtro_status !== 'todos' || !empty($filtro_busca)): ?>
                        Nenhum resultado para os filtros aplicados.
                    <?php else: ?>
                        Clique em "Nova Proposta" para criar a primeira proposta.
                    <?php endif; ?>
                </p>
                <?php if (in_array($cargo, ['ADMIN', 'VENDEDOR'])): ?>
                <a href="<?php echo APP_URL; ?>comercial/nova" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nova Proposta
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table id="tabelaPropostas">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Embarcação(ões)</th>
                        <th>Valor Total</th>
                        <th>Parcelas</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th style="width: 170px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($propostas as $p): ?>
                    <?php
                        $pid = $p['id'];
                        $embarcacoesLista = $embarcacoesPorProposta[$pid] ?? [];
                        $embNomes = !empty($embarcacoesLista) ? implode(', ', $embarcacoesLista) : '<em class="text-muted">N/I</em>';
                        $statusCfg = $statusConfig[$p['status']] ?? ['label' => $p['status'], 'cor' => 'secondary'];
                        $podeEditar = in_array($cargo, ['ADMIN', 'VENDEDOR'], true)
                            && ($cargo === 'ADMIN' || ($p['criado_por'] ?? '') === ($_SESSION['usuario_id'] ?? ''))
                            && ($p['status'] ?? '') === 'rascunho'
                            && empty($p['assinado']);
                    ?>
                    <tr>
                        <td>
                            <strong style="font-family: monospace; font-size: 0.9rem;"><?php echo h($p['numero']); ?></strong>
                        </td>
                        <td>
                            <div style="font-weight: 600;"><?php echo h($p['cliente_nome']); ?></div>
                            <small style="color: var(--cor-texto-secundario);"><?php echo h($p['cliente_cpfcnpj'] ?? ''); ?></small>
                        </td>
                        <td><small><?php echo $embNomes; ?></small></td>
                        <td>
                            <strong style="color: var(--cor-destaque);">R$ <?php echo number_format((float)$p['valor_total'], 2, ',', '.'); ?></strong>
                        </td>
                        <td class="text-center"><?php echo (int)$p['parcelas']; ?>x</td>
                        <td>
                            <span class="badge badge-<?php echo $statusCfg['cor']; ?>">
                                <?php echo $statusCfg['label']; ?>
                            </span>
                        </td>
                        <td><small><?php echo date('d/m/Y', strtotime($p['data_emissao'])); ?></small></td>
                        <td>
                            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                <a href="<?php echo APP_URL; ?>comercial/propostas?id=<?php echo urlencode($pid); ?>&visualizar=1"
                                   class="btn btn-secondary btn-sm" title="Visualizar" style="padding: 4px 8px;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($podeEditar): ?>
                                <a href="<?php echo APP_URL; ?>comercial/nova?id=<?php echo urlencode($pid); ?>"
                                   class="btn btn-primary btn-sm" title="Editar proposta" style="padding: 4px 8px;">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <?php endif; ?>
                                <a href="<?php echo APP_URL; ?>comercial/pdf?id=<?php echo urlencode($pid); ?>"
                                   class="btn btn-primary btn-sm" title="Gerar PDF" target="_blank" style="padding: 4px 8px;">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                <?php if (!empty($p['cliente_email'])): ?>
                                <form method="POST" action="<?php echo APP_URL; ?>comercial/propostas/actions" style="display: inline;"
                                      onsubmit="return confirm('Enviar proposta <?php echo h(addslashes($p['numero'])); ?> por e-mail para <?php echo h(addslashes($p['cliente_email'])); ?>?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                    <input type="hidden" name="action" value="enviar_proposta">
                                    <input type="hidden" name="id" value="<?php echo h($p['id']); ?>">
                                    <button type="submit" class="btn btn-success btn-sm" title="Enviar por e-mail para <?php echo h($p['cliente_email']); ?>" style="padding: 4px 8px;">
                                        <i class="fas fa-envelope"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <button type="button" class="btn btn-secondary btn-sm" disabled title="Cliente sem e-mail cadastrado" style="padding: 4px 8px;">
                                    <i class="fas fa-envelope"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($cargo === 'ADMIN'): ?>
                                    <?php if ($p['status'] === 'enviada' || $p['status'] === 'rascunho'): ?>
                                    <form method="POST" action="<?php echo APP_URL; ?>comercial/propostas/actions" style="display: inline;"
                                          onsubmit="return confirm('Marcar proposta <?php echo h(addslashes($p['numero'])); ?> como APROVADA?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                        <input type="hidden" name="action" value="aprovar_proposta">
                                        <input type="hidden" name="id" value="<?php echo h($p['id']); ?>">
                                        <button type="submit" class="btn btn-success btn-sm" title="Aprovar Proposta" style="padding: 4px 8px;">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="<?php echo APP_URL; ?>comercial/propostas/actions" style="display: inline;"
                                          onsubmit="return confirm('Marcar proposta <?php echo h(addslashes($p['numero'])); ?> como RECUSADA?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                        <input type="hidden" name="action" value="recusar_proposta">
                                        <input type="hidden" name="id" value="<?php echo h($p['id']); ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Recusar Proposta" style="padding: 4px 8px;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($p['status'] === 'aprovada'): ?>
                                    <form method="POST" action="<?php echo APP_URL; ?>comercial/propostas/actions" style="display: inline;"
                                          onsubmit="return confirm('Cancelar proposta <?php echo h(addslashes($p['numero'])); ?>?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                        <input type="hidden" name="action" value="cancelar_proposta">
                                        <input type="hidden" name="id" value="<?php echo h($p['id']); ?>">
                                        <button type="submit" class="btn btn-warning btn-sm" title="Cancelar Proposta" style="padding: 4px 8px;">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($p['status'] === 'recusada' || $p['status'] === 'cancelada'): ?>
                                    <form method="POST" action="<?php echo APP_URL; ?>comercial/propostas/actions" style="display: inline;"
                                          onsubmit="return confirm('Reabrir proposta <?php echo h(addslashes($p['numero'])); ?>?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                        <input type="hidden" name="action" value="reabrir_proposta">
                                        <input type="hidden" name="id" value="<?php echo h($p['id']); ?>">
                                        <button type="submit" class="btn btn-info btn-sm" title="Reabrir Proposta" style="padding: 4px 8px;">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="card-footer" style="padding: 12px 20px; display: flex; justify-content: space-between; align-items: center;">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                Total: <?php echo count($propostas); ?> proposta(s)
            </small>
            <small class="text-muted">
                <strong>Soma:</strong> R$ <?php echo number_format(array_sum(array_column($propostas, 'valor_total')), 2, ',', '.'); ?>
            </small>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function filtrarPropostas() {
    const status   = document.getElementById('filtroStatus').value;
    const busca    = document.getElementById('buscaProposta').value.trim();
    const dataIni  = document.getElementById('filtroDataIni').value;
    const dataFim  = document.getElementById('filtroDataFim').value;

    let url = '<?php echo APP_URL; ?>comercial/propostas?';
    const params = [];
    if (status !== 'todos') params.push('status=' + encodeURIComponent(status));
    if (busca !== '')       params.push('busca=' + encodeURIComponent(busca));
    if (dataIni !== '')     params.push('data_ini=' + encodeURIComponent(dataIni));
    if (dataFim !== '')     params.push('data_fim=' + encodeURIComponent(dataFim));

    window.location.href = url + params.join('&');
}

function limparFiltros() {
    window.location.href = '<?php echo APP_URL; ?>comercial/propostas';
}
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
