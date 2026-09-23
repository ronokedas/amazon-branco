<?php
/**
 * MODULO: EMBARCACOES
 * Arquivo: index.php - Listagem de embarcacoes (ADMIN e VISTORIADOR)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissao do modulo
verificar_sessao();
$cargo = getCargo();
exigirAcesso('embarcacoes');

// Filtros e paginação
$busca = trim($_GET['busca'] ?? '');
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = (int)($_GET['por_pagina'] ?? 15);
if (!in_array($porPagina, [10, 15, 25, 50, 100], true)) {
    $porPagina = 15;
}

$embarcacaoUrl = function(array $novos = []) use (&$busca, &$paginaAtual, &$porPagina): string {
    $params = [
        'busca' => $busca !== '' ? $busca : null,
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
    return APP_URL . 'embarcacoes' . ($query ? '?' . $query : '');
};

$totalEmbarcacoes = 0;
$totalPaginas = 1;
$offset = 0;
$registroInicio = 0;
$registroFim = 0;

// Buscar embarcacoes ativas com paginação e busca
try {
    $params = [];
    $busca_filter = '';
    if ($busca !== '') {
        $busca_filter = " AND (nome LIKE :b1 OR numero_inscricao LIKE :b2 OR proprietario LIKE :b3 OR tipo_embarcacao LIKE :b4 OR tipo LIKE :b5)";
        $params[':b1'] = '%' . $busca . '%';
        $params[':b2'] = '%' . $busca . '%';
        $params[':b3'] = '%' . $busca . '%';
        $params[':b4'] = '%' . $busca . '%';
        $params[':b5'] = '%' . $busca . '%';
    }

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM embarcacoes WHERE ativo = 1 {$busca_filter}");
    foreach ($params as $k => $v) {
        $stmtCount->bindValue($k, $v);
    }
    $stmtCount->execute();
    $totalEmbarcacoes = (int)$stmtCount->fetchColumn();

    $totalPaginas = max(1, (int)ceil($totalEmbarcacoes / $porPagina));
    if ($paginaAtual > $totalPaginas) {
        $paginaAtual = $totalPaginas;
    }
    $offset = ($paginaAtual - 1) * $porPagina;
    $registroInicio = $totalEmbarcacoes > 0 ? $offset + 1 : 0;
    $registroFim = min($offset + $porPagina, $totalEmbarcacoes);

    $sql = "SELECT id, nome, tipo, tipo_embarcacao, numero_inscricao, proprietario, ano, observacoes, foto_url, ativo, criado_em, atualizado_em 
            FROM embarcacoes 
            WHERE ativo = 1 {$busca_filter}
            ORDER BY criado_em DESC, nome ASC
            LIMIT :limite OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $embarcacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar embarcacoes: ' . $e->getMessage());
    $embarcacoes = [];
}

$titulo_page = 'Embarcações - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <style>
        .embarcacao-identidade { display: flex; align-items: center; gap: 11px; }
        .embarcacao-identidade img { width: 54px; height: 44px; object-fit: cover; border: 1px solid var(--cor-borda); border-radius: 8px; background: #edf2f0; }
        .embarcacao-identidade span { display: grid; gap: 2px; }
        .embarcacao-identidade small { color: var(--cor-texto-secundario); }

        .paginacao-embarcacoes { display: flex; align-items: center; gap: 5px; margin: 0; padding: 0; list-style: none; }
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
        <div class="tabela-header">
            <h3><i class="fas fa-ship"></i> Gerenciar Embarcações</h3>
            <a href="<?php echo APP_URL; ?>embarcacoes/form" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nova Embarcação
            </a>
        </div>

        <!-- Filtro de busca com envio de formulário -->
        <form method="get" action="<?php echo APP_URL; ?>embarcacoes" class="filtros" style="margin: 15px 20px; display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
            <?php if ($porPagina !== 15): ?>
                <input type="hidden" name="por_pagina" value="<?php echo (int)$porPagina; ?>">
            <?php endif; ?>
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 240px;">
                <label for="buscaEmbarcacao"><i class="fas fa-search"></i> Buscar embarcação</label>
                <input type="search" 
                       name="busca"
                       id="buscaEmbarcacao" 
                       value="<?php echo h($busca); ?>"
                       placeholder="Nome, número de inscrição, proprietário ou tipo...">
            </div>
            <button type="submit" class="btn btn-primary" style="height: 42px; display: inline-flex; align-items: center; gap: 6px; padding: 0 16px; border-radius: 6px; font-weight: 600;">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <?php if ($busca !== ''): ?>
                <a href="<?php echo h($embarcacaoUrl(['busca' => '', 'pagina' => 1])); ?>" class="btn btn-secondary" style="height: 42px; display: inline-flex; align-items: center; gap: 6px; padding: 0 14px; border-radius: 6px;" title="Limpar busca">
                    <i class="fas fa-times"></i> Limpar
                </a>
            <?php endif; ?>
        </form>

        <?php if (empty($embarcacoes)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-ship"></i>
                <h3>Nenhuma embarcação encontrada</h3>
                <p><?php echo $busca !== '' ? 'Nenhum resultado para os termos pesquisados.' : 'Clique em "Nova Embarcação" para cadastrar a primeira embarcação.'; ?></p>
            </div>
        <?php else: ?>
            <table id="tabelaEmbarcacoes">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Número de Inscrição</th>
                        <th>Proprietário</th>
                        <th>Ano</th>
                        <th style="min-width: 180px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($embarcacoes as $e): ?>
                    <tr>
                        <td>
                            <span class="embarcacao-identidade">
                                <img src="<?= h($e['foto_url'] ?: APP_URL . 'assets/img/portal-hero-ship.png') ?>" alt="Foto de <?= h($e['nome']) ?>" loading="lazy">
                                <span><strong><?php echo h($e['nome']); ?></strong><small><?= $e['foto_url'] ? 'Foto oficial' : 'Sem foto oficial' ?></small></span>
                            </span>
                        </td>
                        <td><?php echo h($e['tipo_embarcacao'] ?: ($e['tipo'] ?? '-')); ?></td>
                        <td><?php echo h($e['numero_inscricao'] ?? '-'); ?></td>
                        <td><?php echo h($e['proprietario'] ?? '-'); ?></td>
                        <td><?php echo h($e['ano'] ?? '-'); ?></td>
                        <td>
                            <div class="d-flex gap-1" style="white-space: nowrap;">
                                <a href="<?php echo APP_URL; ?>comercial/nova?embarcacao_id=<?php echo urlencode($e['id']); ?>" 
                                   class="btn btn-primary btn-sm" 
                                   title="Gerar Proposta Comercial para esta embarcação"
                                   style="display: inline-flex; align-items: center; gap: 4px; white-space: nowrap;">
                                    <i class="fas fa-file-invoice-dollar"></i> Proposta
                                </a>
                                <a href="<?php echo APP_URL; ?>embarcacoes/form?id=<?php echo urlencode($e['id']); ?>" 
                                   class="btn btn-secondary btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?php echo APP_URL; ?>embarcacoes/actions?action=desativar&id=<?php echo urlencode($e['id']); ?>" 
                                   class="btn btn-danger btn-sm" 
                                   title="Desativar"
                                   onclick="return confirm('Tem certeza que deseja desativar esta embarcação?')">
                                    <i class="fas fa-ban"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Resumo e Paginação -->
        <div class="card-footer" style="padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Mostrando <strong><?php echo $registroInicio; ?></strong> a <strong><?php echo $registroFim; ?></strong> de <strong><?php echo $totalEmbarcacoes; ?></strong> embarcação(ões) ativa(s)
                </small>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <label for="selectPorPagina" style="font-size: 0.82rem; color: #64748b; margin: 0;">Exibir:</label>
                    <select id="selectPorPagina" class="form-control form-control-sm" style="width: auto; height: 32px; padding: 2px 8px; font-size: 0.82rem; border-radius: 6px; border: 1px solid #cbd5e1;" onchange="window.location.href=this.value">
                        <?php foreach ([10, 15, 25, 50, 100] as $qtd): ?>
                            <option value="<?= h($embarcacaoUrl(['por_pagina' => $qtd, 'pagina' => 1])) ?>" <?= $porPagina === $qtd ? 'selected' : '' ?>>
                                <?= $qtd ?> por pág.
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ($totalPaginas > 1): ?>
                <nav aria-label="Navegação de páginas de embarcações">
                    <ul class="paginacao-embarcacoes">
                        <!-- Primeira página -->
                        <li class="paginacao-item <?= $paginaAtual <= 1 ? 'disabled' : '' ?>">
                            <a class="paginacao-link" href="<?= $paginaAtual <= 1 ? 'javascript:void(0)' : h($embarcacaoUrl(['pagina' => 1])) ?>" title="Primeira página">
                                <i class="fas fa-angles-left"></i>
                            </a>
                        </li>

                        <!-- Página anterior -->
                        <li class="paginacao-item <?= $paginaAtual <= 1 ? 'disabled' : '' ?>">
                            <a class="paginacao-link" href="<?= $paginaAtual <= 1 ? 'javascript:void(0)' : h($embarcacaoUrl(['pagina' => $paginaAtual - 1])) ?>" title="Página anterior">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>

                        <!-- Janela de páginas -->
                        <?php
                        $janelaInicio = max(1, $paginaAtual - 2);
                        $janelaFim = min($totalPaginas, $paginaAtual + 2);

                        if ($janelaInicio > 1) {
                            echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h($embarcacaoUrl(['pagina' => 1])) . '">1</a></li>';
                            if ($janelaInicio > 2) {
                                echo '<li class="paginacao-ellipsis" style="padding: 0 4px; color: #94a3b8;">...</li>';
                            }
                        }

                        for ($p = $janelaInicio; $p <= $janelaFim; $p++) {
                            if ($p === $paginaAtual) {
                                echo '<li class="paginacao-item active"><span class="paginacao-link active-link" style="background: var(--cor-primaria, #0d9488); color: #ffffff; border-color: var(--cor-primaria, #0d9488); font-weight: 700;">' . $p . '</span></li>';
                            } else {
                                echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h($embarcacaoUrl(['pagina' => $p])) . '">' . $p . '</a></li>';
                            }
                        }

                        if ($janelaFim < $totalPaginas) {
                            if ($janelaFim < $totalPaginas - 1) {
                                echo '<li class="paginacao-ellipsis" style="padding: 0 4px; color: #94a3b8;">...</li>';
                            }
                            echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h($embarcacaoUrl(['pagina' => $totalPaginas])) . '">' . $totalPaginas . '</a></li>';
                        }
                        ?>

                        <!-- Próxima página -->
                        <li class="paginacao-item <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>">
                            <a class="paginacao-link" href="<?= $paginaAtual >= $totalPaginas ? 'javascript:void(0)' : h($embarcacaoUrl(['pagina' => $paginaAtual + 1])) ?>" title="Próxima página">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>

                        <!-- Última página -->
                        <li class="paginacao-item <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>">
                            <a class="paginacao-link" href="<?= $paginaAtual >= $totalPaginas ? 'javascript:void(0)' : h($embarcacaoUrl(['pagina' => $totalPaginas])) ?>" title="Última página">
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
