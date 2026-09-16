<?php
/**
 * MODULO: CLIENTES E ATORES NAVAIS
 * Arquivo: index.php - Listagem unificada com filtragem inteligente por perfil,
 * busca avançada no banco, paginação de alto desempenho e botão de contato via WhatsApp.
 * Suporta Armadores, Proprietários e Despachantes Marítimos.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('clientes');

// 1. Capturar e sanitizar parâmetros de paginação e filtros
$perfilFiltro = $_GET['perfil'] ?? 'todos';
if (!in_array($perfilFiltro, ['todos', 'armador', 'proprietario', 'despachante'], true)) {
    $perfilFiltro = 'todos';
}

$busca = trim($_GET['busca'] ?? '');

$porPagina = (int)($_GET['por_pagina'] ?? 15);
if (!in_array($porPagina, [5, 10, 15, 25, 50, 100], true)) {
    $porPagina = 15;
}

$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));

// Helper para gerar URLs mantendo filtros e paginação
if (!function_exists('clienteUrl')) {
    function clienteUrl(array $overrides = []): string {
        global $perfilFiltro, $busca, $porPagina, $paginaAtual;
        $params = [
            'perfil'     => $perfilFiltro !== 'todos' ? $perfilFiltro : null,
            'busca'      => $busca !== '' ? $busca : null,
            'por_pagina' => $porPagina !== 15 ? $porPagina : null,
            'pagina'     => $paginaAtual > 1 ? $paginaAtual : null,
        ];
        foreach ($overrides as $k => $v) {
            if ($v === null || $v === '' || ($k === 'perfil' && $v === 'todos') || ($k === 'pagina' && (int)$v <= 1) || ($k === 'por_pagina' && (int)$v === 15)) {
                unset($params[$k]);
            } else {
                $params[$k] = $v;
            }
        }
        $qs = http_build_query(array_filter($params, fn($val) => $val !== null && $val !== ''));
        return APP_URL . 'clientes' . ($qs ? '?' . $qs : '');
    }
}

// 2. Métricas globais para os Cards de KPI no topo
try {
    $stmtKPI = $pdo->query("
        SELECT 
            COUNT(*) AS total_geral,
            SUM(IF(perfil = 'armador', 1, 0)) AS total_armadores,
            SUM(IF(perfil = 'proprietario', 1, 0)) AS total_proprietarios,
            SUM(IF(perfil = 'despachante', 1, 0)) AS total_despachantes
        FROM clientes 
        WHERE ativo = 1 AND excluido_em IS NULL
    ");
    $kpis = $stmtKPI->fetch(PDO::FETCH_ASSOC) ?: [
        'total_geral' => 0,
        'total_armadores' => 0,
        'total_proprietarios' => 0,
        'total_despachantes' => 0
    ];

    $totalVinculos = (int)$pdo->query("
        SELECT COUNT(DISTINCT cliente_id) 
        FROM clientes_embarcacoes 
        WHERE status = 'ATIVO'
    ")->fetchColumn();
} catch (Exception $e) {
    error_log('Erro ao calcular KPIs de clientes: ' . $e->getMessage());
    $kpis = ['total_geral' => 0, 'total_armadores' => 0, 'total_proprietarios' => 0, 'total_despachantes' => 0];
    $totalVinculos = 0;
}

// 3. Montar condições da consulta (filtro de perfil + busca textual)
$whereClauses = ["c.ativo = 1", "c.excluido_em IS NULL"];
$queryParams = [];

if ($perfilFiltro !== 'todos') {
    $whereClauses[] = "c.perfil = :perfil";
    $queryParams[':perfil'] = $perfilFiltro;
}

if ($busca !== '') {
    $whereClauses[] = "(c.nome LIKE :busca OR c.cpf_cnpj LIKE :busca OR c.email LIKE :busca OR c.telefone LIKE :busca)";
    $queryParams[':busca'] = '%' . $busca . '%';
}

$sqlWhere = ' WHERE ' . implode(' AND ', $whereClauses);

// 4. Obter total de registros para cálculo das páginas
try {
    $sqlCount = "SELECT COUNT(DISTINCT c.id) FROM clientes c {$sqlWhere}";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($queryParams);
    $totalClientes = (int)$stmtCount->fetchColumn();
} catch (Exception $e) {
    error_log('Erro ao contar clientes: ' . $e->getMessage());
    $totalClientes = 0;
}

$totalPaginas = max(1, (int)ceil($totalClientes / $porPagina));
if ($paginaAtual > $totalPaginas) {
    $paginaAtual = $totalPaginas;
}
$offset = ($paginaAtual - 1) * $porPagina;

$registroInicio = $totalClientes > 0 ? $offset + 1 : 0;
$registroFim = min($offset + $porPagina, $totalClientes);

// 5. Buscar dados paginados dos clientes com relações agregadas
try {
    $sql = "
        SELECT c.*, 
               COUNT(DISTINCT ce.id) AS total_embarcacoes,
               GROUP_CONCAT(DISTINCT te.nome ORDER BY te.nome SEPARATOR ', ') AS tipos_atendidos
        FROM clientes c
        LEFT JOIN clientes_embarcacoes ce ON ce.cliente_id = c.id AND ce.status = 'ATIVO'
        LEFT JOIN clientes_tipos_embarcacao cte ON cte.cliente_id = c.id
        LEFT JOIN tipos_embarcacao te ON te.id = cte.tipo_embarcacao_id
        {$sqlWhere}
        GROUP BY c.id 
        ORDER BY c.criado_em DESC, c.nome ASC
        LIMIT {$porPagina} OFFSET {$offset}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($queryParams);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar clientes paginados: ' . $e->getMessage());
    $clientes = [];
}

$titulo_page = 'Clientes e Atores Navais - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <!-- CARDS DE KPIS NO TOPO -->
    <div class="kpi-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <div class="kpi-card" style="background: var(--cor-card-bg, #fff); border: 1px solid var(--cor-borda); border-radius: 8px; padding: 16px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
            <div style="width: 48px; height: 48px; border-radius: 8px; background: rgba(30, 77, 63, 0.1); color: var(--cor-primaria, #1e4d3f); display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <span class="text-muted" style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase;">Total Geral Ativo</span>
                <h3 style="margin: 2px 0 0; font-size: 1.5rem; font-weight: 700;"><?php echo (int)$kpis['total_geral']; ?></h3>
            </div>
        </div>

        <div class="kpi-card" style="background: var(--cor-card-bg, #fff); border: 1px solid var(--cor-borda); border-radius: 8px; padding: 16px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
            <div style="width: 48px; height: 48px; border-radius: 8px; background: rgba(0, 123, 255, 0.1); color: #007bff; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fas fa-building-user"></i>
            </div>
            <div>
                <span class="text-muted" style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase;">Armadores</span>
                <h3 style="margin: 2px 0 0; font-size: 1.5rem; font-weight: 700;"><?php echo (int)$kpis['total_armadores']; ?></h3>
            </div>
        </div>

        <div class="kpi-card" style="background: var(--cor-card-bg, #fff); border: 1px solid var(--cor-borda); border-radius: 8px; padding: 16px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
            <div style="width: 48px; height: 48px; border-radius: 8px; background: rgba(23, 162, 184, 0.1); color: #17a2b8; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fas fa-id-card"></i>
            </div>
            <div>
                <span class="text-muted" style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase;">Proprietários</span>
                <h3 style="margin: 2px 0 0; font-size: 1.5rem; font-weight: 700;"><?php echo (int)$kpis['total_proprietarios']; ?></h3>
            </div>
        </div>

        <div class="kpi-card" style="background: var(--cor-card-bg, #fff); border: 1px solid var(--cor-borda); border-radius: 8px; padding: 16px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
            <div style="width: 48px; height: 48px; border-radius: 8px; background: rgba(255, 193, 7, 0.15); color: #856404; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fas fa-briefcase"></i>
            </div>
            <div>
                <span class="text-muted" style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase;">Despachantes</span>
                <h3 style="margin: 2px 0 0; font-size: 1.5rem; font-weight: 700;"><?php echo (int)$kpis['total_despachantes']; ?></h3>
            </div>
        </div>

        <div class="kpi-card" style="background: var(--cor-card-bg, #fff); border: 1px solid var(--cor-borda); border-radius: 8px; padding: 16px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
            <div style="width: 48px; height: 48px; border-radius: 8px; background: rgba(40, 167, 69, 0.1); color: #28a745; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fas fa-ship"></i>
            </div>
            <div>
                <span class="text-muted" style="font-size: 0.82rem; font-weight: 600; text-transform: uppercase;">Com Embarcações</span>
                <h3 style="margin: 2px 0 0; font-size: 1.5rem; font-weight: 700;"><?php echo $totalVinculos; ?></h3>
            </div>
        </div>
    </div>

    <!-- CONTAINER DA TABELA E ABAS -->
    <div class="tabela-container">
        <!-- ABAS DE NAVEGAÇÃO RÁPIDA POR PERFIL (PILLS) -->
        <div class="nav-tabs-wrapper" style="padding: 15px 20px 0; border-bottom: 1px solid var(--cor-borda); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div class="nav-pills" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="<?php echo h(clienteUrl(['perfil' => 'todos', 'pagina' => 1])); ?>" 
                   class="btn btn-sm <?php echo $perfilFiltro === 'todos' ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                   style="border-radius: 20px; padding: 6px 16px; font-weight: 600;">
                    <i class="fas fa-list"></i> Todos os Clientes (<?php echo (int)$kpis['total_geral']; ?>)
                </a>
                <a href="<?php echo h(clienteUrl(['perfil' => 'armador', 'pagina' => 1])); ?>" 
                   class="btn btn-sm <?php echo $perfilFiltro === 'armador' ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                   style="border-radius: 20px; padding: 6px 16px; font-weight: 600;">
                    <i class="fas fa-building-user"></i> Armadores (<?php echo (int)$kpis['total_armadores']; ?>)
                </a>
                <a href="<?php echo h(clienteUrl(['perfil' => 'proprietario', 'pagina' => 1])); ?>" 
                   class="btn btn-sm <?php echo $perfilFiltro === 'proprietario' ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                   style="border-radius: 20px; padding: 6px 16px; font-weight: 600;">
                    <i class="fas fa-id-card"></i> Proprietários (<?php echo (int)$kpis['total_proprietarios']; ?>)
                </a>
                <a href="<?php echo h(clienteUrl(['perfil' => 'despachante', 'pagina' => 1])); ?>" 
                   class="btn btn-sm <?php echo $perfilFiltro === 'despachante' ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                   style="border-radius: 20px; padding: 6px 16px; font-weight: 600;">
                    <i class="fas fa-briefcase"></i> Despachantes (<?php echo (int)$kpis['total_despachantes']; ?>)
                </a>
            </div>

            <!-- BOTÃO CONTEXTUAL DE NOVO CADASTRO -->
            <a href="<?php echo APP_URL; ?>clientes/form<?php echo $perfilFiltro !== 'todos' ? '?perfil=' . urlencode($perfilFiltro) : ''; ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Novo <?php echo $perfilFiltro === 'armador' ? 'Armador' : ($perfilFiltro === 'despachante' ? 'Despachante' : ($perfilFiltro === 'proprietario' ? 'Proprietário' : 'Cliente')); ?>
            </a>
        </div>

        <!-- FORMULÁRIO DE BUSCA INTEGRADA (BANCO DE DADOS + FILTRO EM TELA) -->
        <form method="GET" action="<?php echo APP_URL; ?>clientes" class="filtros-busca-form" style="margin: 15px 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="perfil" value="<?php echo h($perfilFiltro); ?>">
            <?php if ($porPagina !== 15): ?>
                <input type="hidden" name="por_pagina" value="<?php echo (int)$porPagina; ?>">
            <?php endif; ?>

            <div style="flex: 1; min-width: 280px; position: relative;">
                <input type="text" 
                       id="buscaCliente" 
                       name="busca"
                       value="<?php echo h($busca); ?>"
                       placeholder="Pesquisar por nome, CPF/CNPJ, e-mail, telefone..." 
                       class="form-control"
                       style="padding-left: 38px; padding-right: <?php echo !empty($busca) ? '38px' : '12px'; ?>; height: 40px; border-radius: 6px;"
                       onkeyup="filtrarTabelaClientesInstantaneo()">
                <i class="fas fa-search" style="position: absolute; left: 13px; top: 12px; color: #94a3b8; font-size: 0.95rem;"></i>
                <?php if (!empty($busca)): ?>
                    <a href="<?php echo h(clienteUrl(['busca' => '', 'pagina' => 1])); ?>" 
                       style="position: absolute; right: 12px; top: 11px; color: #ef4444; font-size: 1.1rem; text-decoration: none;" 
                       title="Limpar busca">
                        <i class="fas fa-times-circle"></i>
                    </a>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary" style="height: 40px; display: inline-flex; align-items: center; gap: 7px; padding: 0 18px; font-weight: 600;">
                <i class="fas fa-search"></i> Buscar
            </button>

            <?php if (!empty($busca)): ?>
                <a href="<?php echo h(clienteUrl(['busca' => '', 'pagina' => 1])); ?>" class="btn btn-outline-secondary" style="height: 40px; display: inline-flex; align-items: center; gap: 6px; padding: 0 14px;">
                    <i class="fas fa-undo"></i> Limpar
                </a>
            <?php endif; ?>
        </form>

        <!-- TABELA DE RESULTADOS -->
        <?php if (empty($clientes)): ?>
            <div class="tabela-vazia" style="padding: 45px 20px; text-align: center;">
                <i class="fas fa-user-tie" style="font-size: 3.2rem; color: var(--cor-texto-mutado); opacity: 0.45;"></i>
                <h3 style="margin-top: 15px; font-size: 1.3rem;">Nenhum cliente encontrado</h3>
                <p class="text-muted" style="max-width: 500px; margin: 8px auto 20px;">
                    <?php if (!empty($busca)): ?>
                        Nenhum registro corresponde ao termo "<strong><?php echo h($busca); ?></strong>"
                        <?php if ($perfilFiltro !== 'todos'): ?>
                            no perfil <strong><?php echo ucfirst($perfilFiltro); ?></strong>.
                        <?php else: ?>
                            no cadastro geral.
                        <?php endif; ?>
                    <?php elseif ($perfilFiltro !== 'todos'): ?>
                        Não há <?php echo h($perfilFiltro); ?>s ativos cadastrados no momento.
                    <?php else: ?>
                        Nenhum cliente ou ator naval ativo registrado no sistema.
                    <?php endif; ?>
                </p>
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <?php if (!empty($busca)): ?>
                        <a href="<?php echo h(clienteUrl(['busca' => '', 'pagina' => 1])); ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Ver Todos os Clientes
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo APP_URL; ?>clientes/form<?php echo $perfilFiltro !== 'todos' ? '?perfil=' . urlencode($perfilFiltro) : ''; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Cadastrar Novo <?php echo $perfilFiltro !== 'todos' ? ucfirst($perfilFiltro) : 'Cliente'; ?>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive" style="overflow-x: auto;">
                <table id="tabelaClientes" class="table" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th>Nome / Razão Social</th>
                            <th>Perfil Naval</th>
                            <th>CPF / CNPJ</th>
                            <th>Contato (Telefone / E-mail)</th>
                            <th class="text-center">Embarcações</th>
                            <?php if ($perfilFiltro === 'despachante' || $perfilFiltro === 'todos'): ?>
                                <th>Tipos Atendidos</th>
                            <?php endif; ?>
                            <th class="text-center">Financeiro / PIX</th>
                            <th class="text-right" style="min-width: 145px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $c): 
                            // Tratamento de WhatsApp
                            $telefoneDigitos = preg_replace('/\D/', '', $c['telefone'] ?? '');
                            $temWhats = false;
                            $linkWhats = '#';
                            if (strlen($telefoneDigitos) >= 10) {
                                $temWhats = true;
                                if (!str_starts_with($telefoneDigitos, '55') && (strlen($telefoneDigitos) === 10 || strlen($telefoneDigitos) === 11)) {
                                    $whatsNum = '55' . $telefoneDigitos;
                                } else {
                                    $whatsNum = $telefoneDigitos;
                                }
                                $primeiroNome = trim(explode(' ', $c['nome'] ?? '')[0]);
                                $msgWhats = "Olá, {$primeiroNome}! Aqui é da equipe da Amazon Certificadora Naval. Em que podemos lhe ajudar hoje?";
                                $linkWhats = "https://api.whatsapp.com/send?phone={$whatsNum}&text=" . rawurlencode($msgWhats);
                            }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo h($c['nome']); ?></strong>
                                <?php if (!empty($c['tipo_pessoa'])): ?>
                                    <span class="badge badge-light" style="font-size: 0.75rem; margin-left: 5px;"><?php echo h($c['tipo_pessoa']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $badgeClass = match($c['perfil']) {
                                    'armador' => 'badge-primary',
                                    'despachante' => 'badge-warning',
                                    default => 'badge-info'
                                };
                                $labelPerfil = match($c['perfil']) {
                                    'armador' => 'Armador',
                                    'despachante' => 'Despachante',
                                    default => 'Proprietário'
                                };
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>" style="font-size: 0.85rem;">
                                    <?php echo $labelPerfil; ?>
                                </span>
                            </td>
                            <td>
                                <?php echo h($c['cpf_cnpj'] ?? '-'); ?>
                            </td>
                            <td>
                                <div>
                                    <?php if (!empty($c['telefone'])): ?>
                                        <?php if ($temWhats): ?>
                                            <a href="<?php echo h($linkWhats); ?>" 
                                               target="_blank" 
                                               rel="noopener noreferrer" 
                                               class="link-whats" 
                                               title="Conversar com <?php echo h($c['nome']); ?> no WhatsApp">
                                                <i class="fab fa-whatsapp"></i> 
                                                <span><?php echo h($c['telefone']); ?></span>
                                            </a>
                                        <?php else: ?>
                                            <span><i class="fas fa-phone text-muted" style="width: 14px;"></i> <?php echo h($c['telefone']); ?></span>
                                        <?php endif; ?>
                                        <br>
                                    <?php endif; ?>
                                    <?php if (!empty($c['email'])): ?>
                                        <small class="text-muted"><i class="fas fa-envelope text-muted" style="width: 14px;"></i> <?php echo h($c['email']); ?></small>
                                    <?php elseif (empty($c['telefone'])): ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if ((int)$c['total_embarcacoes'] > 0): ?>
                                    <span class="badge badge-success" title="Total de embarcações vinculadas ativas">
                                        <i class="fas fa-ship"></i> <?php echo (int)$c['total_embarcacoes']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($perfilFiltro === 'despachante' || $perfilFiltro === 'todos'): ?>
                                <td>
                                    <?php if (!empty($c['tipos_atendidos'])): ?>
                                        <small style="color: var(--cor-primaria); font-weight: 500;">
                                            <?php echo h($c['tipos_atendidos']); ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td class="text-center">
                                <?php if (!empty($c['chave_pix'])): ?>
                                    <span class="badge badge-light" title="Chave PIX: <?php echo h($c['chave_pix']); ?>">
                                        <i class="fas fa-qrcode text-success"></i> PIX
                                    </span>
                                <?php elseif (!empty($c['banco'])): ?>
                                    <span class="badge badge-light" title="Banco: <?php echo h($c['banco']); ?> Ag: <?php echo h($c['agencia']); ?>">
                                        <i class="fas fa-university text-info"></i> Banco
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <div class="d-flex gap-1 justify-content-end align-items-center">
                                    <!-- BOTÃO DE CONTATO DIRETO VIA WHATSAPP -->
                                    <?php if ($temWhats): ?>
                                        <a href="<?php echo h($linkWhats); ?>" 
                                           target="_blank" 
                                           rel="noopener noreferrer" 
                                           class="btn btn-whatsapp btn-sm" 
                                           title="Conversar via WhatsApp com <?php echo h($c['nome']); ?> (<?php echo h($c['telefone']); ?>)">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    <?php else: ?>
                                        <button type="button" 
                                                class="btn btn-whatsapp btn-sm disabled" 
                                                title="Telefone não informado ou inválido para WhatsApp" 
                                                disabled>
                                            <i class="fab fa-whatsapp"></i>
                                        </button>
                                    <?php endif; ?>

                                    <!-- BOTÃO EDITAR -->
                                    <a href="<?php echo APP_URL; ?>clientes/form?id=<?php echo urlencode($c['id']); ?>" 
                                       class="btn btn-secondary btn-sm" 
                                       title="Editar dados cadastrais e vínculos">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <!-- BOTÃO INATIVAR -->
                                    <a href="<?php echo APP_URL; ?>clientes/actions?action=desativar&id=<?php echo urlencode($c['id']); ?>" 
                                       class="btn btn-danger btn-sm" 
                                       title="Desativar cadastro"
                                       onclick="return confirm('Tem certeza que deseja inativar este cadastro? Os vínculos de embarcações serão preservados no histórico.')">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- RODAPÉ COM RESUMO E BARRA DE PAGINAÇÃO COMPLETA -->
        <div class="card-footer paginacao-footer" style="padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; border-top: 1px solid var(--cor-borda);">
            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Mostrando <strong><?php echo $registroInicio; ?></strong> a <strong><?php echo $registroFim; ?></strong> de <strong><?php echo $totalClientes; ?></strong> cliente(s)
                    <?php if ($perfilFiltro !== 'todos'): ?>
                        no perfil <strong><?php echo ucfirst($perfilFiltro); ?></strong>
                    <?php endif; ?>
                    <?php if (!empty($busca)): ?>
                        (filtrando por "<strong><?php echo h($busca); ?></strong>")
                    <?php endif; ?>
                </small>

                <!-- SELETOR DE ITENS POR PÁGINA -->
                <div style="display: inline-flex; align-items: center; gap: 6px;">
                    <label for="selectPorPagina" style="margin: 0; font-size: 0.8rem; color: var(--cor-texto-mutado); white-space: nowrap;">Exibir:</label>
                    <select id="selectPorPagina" 
                            class="form-control form-control-sm" 
                            style="width: auto; height: 32px; padding: 2px 8px; font-size: 0.82rem; border-radius: 4px;" 
                            onchange="window.location.href=this.value">
                        <?php foreach ([5, 10, 15, 25, 50, 100] as $qtd): ?>
                            <option value="<?php echo h(clienteUrl(['por_pagina' => $qtd, 'pagina' => 1])); ?>" <?php echo $porPagina === $qtd ? 'selected' : ''; ?>>
                                <?php echo $qtd; ?> por pág.
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- NAVEGAÇÃO DE PÁGINAS (PAGINAÇÃO COMPLETA) -->
            <?php if ($totalPaginas > 1): ?>
                <nav aria-label="Navegação de páginas de clientes">
                    <ul class="paginacao-clientes">
                        <!-- Primeira página -->
                        <li class="paginacao-item <?php echo $paginaAtual <= 1 ? 'disabled' : ''; ?>">
                            <a class="paginacao-link" 
                               href="<?php echo $paginaAtual <= 1 ? 'javascript:void(0)' : h(clienteUrl(['pagina' => 1])); ?>" 
                               title="Ir para a primeira página">
                                <i class="fas fa-angles-left"></i>
                            </a>
                        </li>

                        <!-- Página anterior -->
                        <li class="paginacao-item <?php echo $paginaAtual <= 1 ? 'disabled' : ''; ?>">
                            <a class="paginacao-link" 
                               href="<?php echo $paginaAtual <= 1 ? 'javascript:void(0)' : h(clienteUrl(['pagina' => $paginaAtual - 1])); ?>" 
                               title="Página anterior">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>

                        <!-- Janela de números de página -->
                        <?php
                        $janelaInicio = max(1, $paginaAtual - 2);
                        $janelaFim = min($totalPaginas, $paginaAtual + 2);

                        if ($janelaInicio > 1) {
                            echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h(clienteUrl(['pagina' => 1])) . '">1</a></li>';
                            if ($janelaInicio > 2) {
                                echo '<li class="paginacao-ellipsis">...</li>';
                            }
                        }

                        for ($p = $janelaInicio; $p <= $janelaFim; $p++) {
                            if ($p === $paginaAtual) {
                                echo '<li class="paginacao-item active"><span class="paginacao-link">' . $p . '</span></li>';
                            } else {
                                echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h(clienteUrl(['pagina' => $p])) . '">' . $p . '</a></li>';
                            }
                        }

                        if ($janelaFim < $totalPaginas) {
                            if ($janelaFim < $totalPaginas - 1) {
                                echo '<li class="paginacao-ellipsis">...</li>';
                            }
                            echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h(clienteUrl(['pagina' => $totalPaginas])) . '">' . $totalPaginas . '</a></li>';
                        }
                        ?>

                        <!-- Próxima página -->
                        <li class="paginacao-item <?php echo $paginaAtual >= $totalPaginas ? 'disabled' : ''; ?>">
                            <a class="paginacao-link" 
                               href="<?php echo $paginaAtual >= $totalPaginas ? 'javascript:void(0)' : h(clienteUrl(['pagina' => $paginaAtual + 1])); ?>" 
                               title="Próxima página">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>

                        <!-- Última página -->
                        <li class="paginacao-item <?php echo $paginaAtual >= $totalPaginas ? 'disabled' : ''; ?>">
                            <a class="paginacao-link" 
                               href="<?php echo $paginaAtual >= $totalPaginas ? 'javascript:void(0)' : h(clienteUrl(['pagina' => $totalPaginas])); ?>" 
                               title="Ir para a última página">
                                <i class="fas fa-angles-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Estilo dedicado para o Botão do WhatsApp */
.btn-whatsapp {
    background-color: #25d366 !important;
    border-color: #25d366 !important;
    color: #ffffff !important;
    width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 1.05rem !important;
    border-radius: 6px !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 2px 4px rgba(37, 211, 102, 0.2) !important;
}
.btn-whatsapp:hover:not(.disabled) {
    background-color: #1ebc59 !important;
    border-color: #1ebc59 !important;
    color: #ffffff !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(37, 211, 102, 0.4) !important;
}
.btn-whatsapp.disabled,
.btn-whatsapp:disabled {
    background-color: #f1f5f9 !important;
    border-color: #e2e8f0 !important;
    color: #cbd5e1 !important;
    opacity: 0.65 !important;
    cursor: not-allowed !important;
    box-shadow: none !important;
    transform: none !important;
}

/* Link direto de WhatsApp na coluna de contato */
.link-whats {
    color: #15803d !important;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: color 0.15s ease;
}
.link-whats i {
    color: #25d366;
    font-size: 1.05rem;
}
.link-whats:hover {
    color: #166534 !important;
    text-decoration: underline;
}

/* Componente de Paginação Profissional */
.paginacao-clientes {
    display: flex;
    align-items: center;
    gap: 5px;
    margin: 0;
    padding: 0;
    list-style: none;
}
.paginacao-item {
    display: inline-block;
}
.paginacao-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 8px;
    border-radius: 6px;
    border: 1px solid var(--cor-borda, #e2e8f0);
    background: var(--cor-card-bg, #ffffff);
    color: var(--cor-texto, #334155);
    font-size: 0.85rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.15s ease;
    user-select: none;
}
.paginacao-link:hover:not(.disabled) {
    border-color: var(--cor-primaria, #1e4d3f);
    color: var(--cor-primaria, #1e4d3f);
    background: rgba(30, 77, 63, 0.06);
}
.paginacao-item.active .paginacao-link {
    background: var(--cor-primaria, #1e4d3f) !important;
    border-color: var(--cor-primaria, #1e4d3f) !important;
    color: #ffffff !important;
    font-weight: 700;
    box-shadow: 0 2px 4px rgba(30, 77, 63, 0.25);
}
.paginacao-item.disabled .paginacao-link {
    color: #cbd5e1 !important;
    border-color: #f1f5f9 !important;
    background: #f8fafc !important;
    cursor: not-allowed;
    opacity: 0.7;
}
.paginacao-ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 34px;
    color: #94a3b8;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .paginacao-footer {
        flex-direction: column;
        align-items: stretch !important;
    }
    .paginacao-clientes {
        justify-content: center;
        flex-wrap: wrap;
    }
}
</style>

<script>
// Filtro instantâneo na tabela para digitação rápida na página ativa
function filtrarTabelaClientesInstantaneo() {
    const termo = document.getElementById('buscaCliente').value.toLowerCase().trim();
    const linhas = document.querySelectorAll('#tabelaClientes tbody tr');
    
    linhas.forEach(linha => {
        const texto = linha.textContent.toLowerCase();
        linha.style.display = texto.includes(termo) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
