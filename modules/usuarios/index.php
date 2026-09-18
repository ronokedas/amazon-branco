<?php
/**
 * MODULO: USUARIOS
 * Arquivo: index.php - Listagem de usuarios (apenas ADMIN) com busca e paginação
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e cargo ADMIN
verificar_sessao();
exigirAcesso('usuarios');

// Filtros e paginação
$busca = trim($_GET['busca'] ?? '');
$filtro_cargo = trim($_GET['cargo'] ?? '');
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = (int)($_GET['por_pagina'] ?? 15);
if (!in_array($porPagina, [10, 15, 25, 50, 100], true)) {
    $porPagina = 15;
}

$usuarioUrl = function(array $novos = []) use (&$busca, &$filtro_cargo, &$paginaAtual, &$porPagina): string {
    $params = [
        'busca' => $busca !== '' ? $busca : null,
        'cargo' => $filtro_cargo !== '' ? $filtro_cargo : null,
        'por_pagina' => (int)$porPagina !== 15 ? (int)$porPagina : null,
        'pagina' => (int)$paginaAtual > 1 ? (int)$paginaAtual : null,
    ];
    foreach ($novos as $k => $v) {
        if ($v === null || $v === '' || ($k === 'pagina' && (int)$v <= 1) || ($k === 'por_pagina' && (int)$v === 15)) {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    $query = http_build_query($params);
    return APP_URL . 'usuarios' . ($query ? '?' . $query : '');
};

$totalUsuariosGeral = 0;
$totalAtivosGeral = 0;
try {
    $kpi = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN ativo=1 THEN 1 ELSE 0 END) as ativos FROM usuarios WHERE excluido_em IS NULL")->fetch(PDO::FETCH_ASSOC);
    $totalUsuariosGeral = (int)($kpi['total'] ?? 0);
    $totalAtivosGeral = (int)($kpi['ativos'] ?? 0);
} catch (Throwable $e) {}

$totalFiltrados = 0;
$totalPaginas = 1;
$offset = 0;
$registroInicio = 0;
$registroFim = 0;
$usuarios = [];

// Buscar usuarios com paginação
try {
    $where = "WHERE u.excluido_em IS NULL";
    $params = [];

    if ($busca !== '') {
        $where .= " AND (u.nome LIKE :busca1 OR u.email LIKE :busca2)";
        $params[':busca1'] = '%' . $busca . '%';
        $params[':busca2'] = '%' . $busca . '%';
    }

    if ($filtro_cargo !== '' && in_array($filtro_cargo, ['ADMIN', 'VENDEDOR', 'VISTORIADOR', 'ANALISTA'], true)) {
        $where .= " AND u.cargo = :cargo";
        $params[':cargo'] = $filtro_cargo;
    }

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM usuarios u {$where}");
    foreach ($params as $k => $v) {
        $stmtCount->bindValue($k, $v);
    }
    $stmtCount->execute();
    $totalFiltrados = (int)$stmtCount->fetchColumn();

    $totalPaginas = max(1, (int)ceil($totalFiltrados / $porPagina));
    if ($paginaAtual > $totalPaginas) {
        $paginaAtual = $totalPaginas;
    }
    $offset = ($paginaAtual - 1) * $porPagina;
    $registroInicio = $totalFiltrados > 0 ? $offset + 1 : 0;
    $registroFim = min($offset + $porPagina, $totalFiltrados);

    $sql = "SELECT u.id, u.nome, u.email, u.cargo, u.ativo, u.criado_em, u.atualizado_em,
            (SELECT GROUP_CONCAT(CONCAT(e.nome, IF(ue.principal=1,' (principal)','')) ORDER BY ue.principal DESC, e.nome SEPARATOR ', ')
             FROM usuario_escritorios ue JOIN escritorios e ON e.id=ue.escritorio_id WHERE ue.usuario_id=u.id) AS escritorios
            FROM usuarios u 
            {$where} 
            ORDER BY u.nome ASC
            LIMIT :limite OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar usuarios: ' . $e->getMessage());
    $usuarios = [];
}

// Verificar se veio mensagem de erro de permissao
$erro_permissao = isset($_GET['erro']) && $_GET['erro'] === 'sem_permissao';

$titulo_page = 'Usuários - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <style>
        .paginacao-usuarios { display: flex; align-items: center; gap: 5px; margin: 0; padding: 0; list-style: none; }
        .paginacao-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #334155;
            font-size: 0.84rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .paginacao-link:hover:not(.active-link):not(.disabled) {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
        }
        .paginacao-item.disabled .paginacao-link {
            opacity: 0.45;
            cursor: not-allowed;
            background: #f8fafc;
        }
    </style>

    <div class="tabela-container">
        <div class="tabela-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <a href="<?php echo APP_URL; ?>configuracoes" class="btn btn-secondary btn-sm" style="margin-bottom: 6px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-arrow-left"></i> Voltar para Configurações
                </a>
                <h3 style="margin: 0;"><i class="fas fa-users"></i> Gerenciar Funcionários & Usuários</h3>
            </div>
            <a href="<?php echo APP_URL; ?>usuarios/form" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-user-plus"></i> Cadastrar Funcionário
            </a>
        </div>

        <?php if ($erro_permissao): ?>
            <div class="message error" style="position: relative; top: 0; right: 0; margin: 15px 20px 0;">
                <i class="fas fa-exclamation-circle"></i>
                <span>Acesso negado. Você não tem permissão para acessar este módulo.</span>
                <button class="close-msg" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Filtro de busca com submissão de formulário -->
        <form method="get" action="<?php echo APP_URL; ?>usuarios" class="filtros" style="margin: 15px 20px; display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
            <?php if ($porPagina !== 15): ?>
                <input type="hidden" name="por_pagina" value="<?php echo (int)$porPagina; ?>">
            <?php endif; ?>
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 220px;">
                <label for="buscaUsuario"><i class="fas fa-search"></i> Buscar usuário</label>
                <input type="search" 
                       name="busca"
                       id="buscaUsuario" 
                       value="<?php echo h($busca); ?>"
                       placeholder="Nome ou e-mail...">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 160px;">
                <label for="filtroCargo"><i class="fas fa-filter"></i> Cargo</label>
                <select name="cargo" id="filtroCargo" class="form-control" style="height: 42px; border-radius: 6px;">
                    <option value="">Todos os cargos</option>
                    <option value="ADMIN" <?php echo $filtro_cargo === 'ADMIN' ? 'selected' : ''; ?>>Administrador</option>
                    <option value="VENDEDOR" <?php echo $filtro_cargo === 'VENDEDOR' ? 'selected' : ''; ?>>Vendedor</option>
                    <option value="VISTORIADOR" <?php echo $filtro_cargo === 'VISTORIADOR' ? 'selected' : ''; ?>>Vistoriador</option>
                    <option value="ANALISTA" <?php echo $filtro_cargo === 'ANALISTA' ? 'selected' : ''; ?>>Analista</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height: 42px; display: inline-flex; align-items: center; gap: 6px; padding: 0 16px; border-radius: 6px; font-weight: 600;">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <?php if ($busca !== '' || $filtro_cargo !== ''): ?>
                <a href="<?php echo h($usuarioUrl(['busca' => '', 'cargo' => '', 'pagina' => 1])); ?>" class="btn btn-secondary" style="height: 42px; display: inline-flex; align-items: center; gap: 6px; padding: 0 14px; border-radius: 6px;" title="Limpar filtros">
                    <i class="fas fa-times"></i> Limpar
                </a>
            <?php endif; ?>
        </form>

        <?php if (empty($usuarios)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-users"></i>
                <h3>Nenhum usuário encontrado</h3>
                <p><?php echo ($busca !== '' || $filtro_cargo !== '') ? 'Nenhum resultado para os termos pesquisados.' : 'Clique em "Cadastrar Funcionário" para criar o primeiro usuário.'; ?></p>
            </div>
        <?php else: ?>
            <table id="tabelaUsuarios">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Cargo</th>
                        <th>Escritório(s)</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td>
                            <strong><?php echo h($u['nome']); ?></strong>
                        </td>
                        <td><?php echo h($u['email']); ?></td>
                        <td>
                            <?php
                                $cargoLabels = [
                                    'ADMIN' => ['Administrador', 'badge-success', 'fa-user-shield'],
                                    'VENDEDOR' => ['Vendedor', 'badge-primary', 'fa-user-tie'],
                                    'VISTORIADOR' => ['Vistoriador', 'badge-info', 'fa-user-check'],
                                    'ANALISTA' => ['Analista', 'badge-warning', 'fa-user-pen'],
                                ];
                                [$cargoLabel, $cargoBadge, $cargoIcon] = $cargoLabels[$u['cargo']] ?? [$u['cargo'], 'badge-secondary', 'fa-user'];
                            ?>
                            <span class="badge <?php echo h($cargoBadge); ?>">
                                <i class="fas <?php echo h($cargoIcon); ?>"></i>
                                <?php echo h($cargoLabel); ?>
                            </span>
                        </td>
                        <td><?= h($u['escritorios'] ?: 'Sem vínculo') ?></td>
                        <td>
                            <?php if ($u['ativo']): ?>
                                <span class="badge badge-success"><i class="fas fa-check-circle"></i> Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatarDataCompleta($u['criado_em']); ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?php echo APP_URL; ?>usuarios/form?id=<?php echo urlencode($u['id']); ?>" 
                                   class="btn btn-secondary btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($u['id'] !== $_SESSION['usuario_id']): ?>
                                    <a href="<?php echo APP_URL; ?>usuarios/actions?action=alternar_status&id=<?php echo urlencode($u['id']); ?>" 
                                       class="btn btn-sm <?php echo $u['ativo'] ? 'btn-danger' : 'btn-success'; ?>" 
                                       title="<?php echo $u['ativo'] ? 'Desativar' : 'Ativar'; ?>"
                                       onclick="return confirm('<?php echo $u['ativo'] ? 'Desativar' : 'Ativar'; ?> este usuário?')">
                                        <i class="fas <?php echo $u['ativo'] ? 'fa-ban' : 'fa-check'; ?>"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Resumo e Paginação -->
        <div class="card-footer" style="padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Mostrando <strong><?php echo $registroInicio; ?></strong> a <strong><?php echo $registroFim; ?></strong> de <strong><?php echo $totalFiltrados; ?></strong> usuário(s)
                </small>
                <small class="text-muted" style="border-left: 1px solid #cbd5e1; padding-left: 14px;">
                    Total no sistema: <strong><?php echo $totalUsuariosGeral; ?></strong> (<?php echo $totalAtivosGeral; ?> ativos, <?php echo $totalUsuariosGeral - $totalAtivosGeral; ?> inativos)
                </small>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <label for="selectPorPagina" style="font-size: 0.82rem; color: #64748b; margin: 0;">Exibir:</label>
                    <select id="selectPorPagina" class="form-control form-control-sm" style="width: auto; height: 32px; padding: 2px 8px; font-size: 0.82rem; border-radius: 6px; border: 1px solid #cbd5e1;" onchange="window.location.href=this.value">
                        <?php foreach ([10, 15, 25, 50, 100] as $qtd): ?>
                            <option value="<?php echo h($usuarioUrl(['por_pagina' => $qtd, 'pagina' => 1])); ?>" <?php echo $porPagina === $qtd ? 'selected' : ''; ?>>
                                <?php echo $qtd; ?> por pág.
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ($totalPaginas > 1): ?>
                <nav aria-label="Navegação de páginas de usuários">
                    <ul class="paginacao-usuarios">
                        <!-- Primeira página -->
                        <li class="paginacao-item <?php echo $paginaAtual <= 1 ? 'disabled' : ''; ?>">
                            <a class="paginacao-link" href="<?php echo $paginaAtual <= 1 ? 'javascript:void(0)' : h($usuarioUrl(['pagina' => 1])); ?>" title="Primeira página">
                                <i class="fas fa-angles-left"></i>
                            </a>
                        </li>

                        <!-- Página anterior -->
                        <li class="paginacao-item <?php echo $paginaAtual <= 1 ? 'disabled' : ''; ?>">
                            <a class="paginacao-link" href="<?php echo $paginaAtual <= 1 ? 'javascript:void(0)' : h($usuarioUrl(['pagina' => $paginaAtual - 1])); ?>" title="Página anterior">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>

                        <!-- Janela de páginas -->
                        <?php
                        $janelaInicio = max(1, $paginaAtual - 2);
                        $janelaFim = min($totalPaginas, $paginaAtual + 2);

                        if ($janelaInicio > 1) {
                            echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h($usuarioUrl(['pagina' => 1])) . '">1</a></li>';
                            if ($janelaInicio > 2) {
                                echo '<li class="paginacao-ellipsis" style="padding: 0 4px; color: #94a3b8;">...</li>';
                            }
                        }

                        for ($p = $janelaInicio; $p <= $janelaFim; $p++) {
                            if ($p === $paginaAtual) {
                                echo '<li class="paginacao-item active"><span class="paginacao-link active-link" style="background: var(--cor-primaria, #0d9488); color: #ffffff; border-color: var(--cor-primaria, #0d9488); font-weight: 700;">' . $p . '</span></li>';
                            } else {
                                echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h($usuarioUrl(['pagina' => $p])) . '">' . $p . '</a></li>';
                            }
                        }

                        if ($janelaFim < $totalPaginas) {
                            if ($janelaFim < $totalPaginas - 1) {
                                echo '<li class="paginacao-ellipsis" style="padding: 0 4px; color: #94a3b8;">...</li>';
                            }
                            echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h($usuarioUrl(['pagina' => $totalPaginas])) . '">' . $totalPaginas . '</a></li>';
                        }
                        ?>

                        <!-- Próxima página -->
                        <li class="paginacao-item <?php echo $paginaAtual >= $totalPaginas ? 'disabled' : ''; ?>">
                            <a class="paginacao-link" href="<?php echo $paginaAtual >= $totalPaginas ? 'javascript:void(0)' : h($usuarioUrl(['pagina' => $paginaAtual + 1])); ?>" title="Próxima página">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>

                        <!-- Última página -->
                        <li class="paginacao-item <?php echo $paginaAtual >= $totalPaginas ? 'disabled' : ''; ?>">
                            <a class="paginacao-link" href="<?php echo $paginaAtual >= $totalPaginas ? 'javascript:void(0)' : h($usuarioUrl(['pagina' => $totalPaginas])); ?>" title="Última página">
                                <i class="fas fa-angles-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
