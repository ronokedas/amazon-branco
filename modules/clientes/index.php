<?php
/**
 * MODULO: CLIENTES E ATORES NAVAIS
 * Arquivo: index.php - Listagem unificada com filtragem inteligente por perfil
 * Suporta Armadores, Proprietários e Despachantes Marítimos
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('clientes');

// Capturar filtro de perfil ativo na aba (padrao: todos)
$perfilFiltro = $_GET['perfil'] ?? 'todos';
if (!in_array($perfilFiltro, ['todos', 'armador', 'proprietario', 'despachante'], true)) {
    $perfilFiltro = 'todos';
}

// 1. Métricas para os Cards de KPI no topo
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

// 2. Buscar clientes com dados relacionais conforme o perfil selecionado
try {
    $sql = "
        SELECT c.*, 
               COUNT(DISTINCT ce.id) AS total_embarcacoes,
               GROUP_CONCAT(DISTINCT te.nome ORDER BY te.nome SEPARATOR ', ') AS tipos_atendidos
        FROM clientes c
        LEFT JOIN clientes_embarcacoes ce ON ce.cliente_id = c.id AND ce.status = 'ATIVO'
        LEFT JOIN clientes_tipos_embarcacao cte ON cte.cliente_id = c.id
        LEFT JOIN tipos_embarcacao te ON te.id = cte.tipo_embarcacao_id
        WHERE c.ativo = 1 AND c.excluido_em IS NULL
    ";

    $params = [];
    if ($perfilFiltro !== 'todos') {
        $sql .= " AND c.perfil = :perfil";
        $params[':perfil'] = $perfilFiltro;
    }

    $sql .= " GROUP BY c.id ORDER BY c.criado_em DESC, c.nome ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar clientes: ' . $e->getMessage());
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
                <a href="<?php echo APP_URL; ?>clientes?perfil=todos" 
                   class="btn btn-sm <?php echo $perfilFiltro === 'todos' ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                   style="border-radius: 20px; padding: 6px 16px; font-weight: 600;">
                    <i class="fas fa-list"></i> Todos os Clientes (<?php echo (int)$kpis['total_geral']; ?>)
                </a>
                <a href="<?php echo APP_URL; ?>clientes?perfil=armador" 
                   class="btn btn-sm <?php echo $perfilFiltro === 'armador' ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                   style="border-radius: 20px; padding: 6px 16px; font-weight: 600;">
                    <i class="fas fa-building-user"></i> Armadores (<?php echo (int)$kpis['total_armadores']; ?>)
                </a>
                <a href="<?php echo APP_URL; ?>clientes?perfil=proprietario" 
                   class="btn btn-sm <?php echo $perfilFiltro === 'proprietario' ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                   style="border-radius: 20px; padding: 6px 16px; font-weight: 600;">
                    <i class="fas fa-id-card"></i> Proprietários (<?php echo (int)$kpis['total_proprietarios']; ?>)
                </a>
                <a href="<?php echo APP_URL; ?>clientes?perfil=despachante" 
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

        <!-- FILTRO DE BUSCA INSTANTÂNEO -->
        <div class="filtros" style="margin: 15px 20px;">
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label><i class="fas fa-search"></i> Filtrar lista em tempo real</label>
                <input type="text" 
                       id="buscaCliente" 
                       placeholder="Filtrar por nome, CPF/CNPJ, e-mail, telefone ou tipo de embarcação..." 
                       onkeyup="filtrarTabelaClientes()">
            </div>
        </div>

        <!-- TABELA DE RESULTADOS -->
        <?php if (empty($clientes)): ?>
            <div class="tabela-vazia" style="padding: 40px; text-align: center;">
                <i class="fas fa-user-tie" style="font-size: 3rem; color: var(--cor-texto-mutado); opacity: 0.5;"></i>
                <h3 style="margin-top: 15px;">Nenhum registro encontrado</h3>
                <p class="text-muted">
                    <?php if ($perfilFiltro !== 'todos'): ?>
                        Não há <?php echo h($perfilFiltro); ?>s ativos cadastrados nesta aba.
                    <?php else: ?>
                        Nenhum cliente ou ator naval ativo no sistema.
                    <?php endif; ?>
                </p>
                <a href="<?php echo APP_URL; ?>clientes/form<?php echo $perfilFiltro !== 'todos' ? '?perfil=' . urlencode($perfilFiltro) : ''; ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Cadastrar Agora
                </a>
            </div>
        <?php else: ?>
            <table id="tabelaClientes" class="table">
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
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $c): ?>
                    <tr>
                        <td>
                            <strong><?php echo h($c['nome']); ?></strong>
                            <?php if ($c['tipo_pessoa']): ?>
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
                                    <span><i class="fas fa-phone text-muted" style="width: 14px;"></i> <?php echo h($c['telefone']); ?></span><br>
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
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="<?php echo APP_URL; ?>clientes/form?id=<?php echo urlencode($c['id']); ?>" 
                                   class="btn btn-secondary btn-sm" title="Editar dados e vínculos">
                                    <i class="fas fa-edit"></i>
                                </a>
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
        <?php endif; ?>

        <!-- RESUMO NO RODAPÉ -->
        <div class="card-footer" style="padding: 12px 20px; display: flex; justify-content: space-between; align-items: center;">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                Exibindo <?php echo count($clientes); ?> registro(s) ativo(s)
                <?php if ($perfilFiltro !== 'todos'): ?>
                    no perfil <strong><?php echo ucfirst($perfilFiltro); ?></strong>
                <?php endif; ?>
            </small>
            <small class="text-muted">
                Padrão NORMAM / DPC · Dados integrados à Capitania
            </small>
        </div>
    </div>
</div>

<script>
function filtrarTabelaClientes() {
    const termo = document.getElementById('buscaCliente').value.toLowerCase().trim();
    const linhas = document.querySelectorAll('#tabelaClientes tbody tr');
    
    linhas.forEach(linha => {
        const texto = linha.textContent.toLowerCase();
        linha.style.display = texto.includes(termo) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
