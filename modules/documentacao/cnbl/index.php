<?php
/**
 * MÓDULO: Documentação > Certificados CNBL
 * Listagem de Certificados de Navegação para Embarcações de Borda Livre
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/aprovacao_ui.php';

// Verificar permissão
verificar_sessao();
if (!podeAcessar('documentacao')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

// Filtros e paginação
$busca = trim($_GET['busca'] ?? '');
$filtro_status = trim($_GET['status'] ?? '');
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = (int)($_GET['por_pagina'] ?? 15);
if (!in_array($porPagina, [10, 15, 25, 50, 100], true)) {
    $porPagina = 15;
}

$cnblUrl = function(array $novos = []) use (&$busca, &$filtro_status, &$paginaAtual, &$porPagina): string {
    $params = [
        'busca' => $busca !== '' ? $busca : null,
        'status' => $filtro_status !== '' ? $filtro_status : null,
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
    return APP_URL . 'documentacao/cnbl' . ($query ? '?' . $query : '');
};

// Construir query
$whereBase = "WHERE c.ativo = 1";
$params = [];

if (!empty($busca)) {
    $whereBase .= " AND (c.numero LIKE :busca OR c.nome_embarcacao LIKE :busca2)";
    $params[':busca'] = "%{$busca}%";
    $params[':busca2'] = "%{$busca}%";
}

if (!empty($filtro_status) && in_array($filtro_status, ['rascunho', 'emitido', 'assinado', 'cancelado'])) {
    $whereBase .= " AND c.status = :status";
    $params[':status'] = $filtro_status;
}

// Contagem total
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM certificados_cnbl c {$whereBase}");
foreach ($params as $k => $v) {
    $stmtCount->bindValue($k, $v);
}
$stmtCount->execute();
$totalCertificados = (int)$stmtCount->fetchColumn();

$totalPaginas = max(1, (int)ceil($totalCertificados / $porPagina));
if ($paginaAtual > $totalPaginas) {
    $paginaAtual = $totalPaginas;
}
$offset = ($paginaAtual - 1) * $porPagina;
$registroInicio = $totalCertificados > 0 ? $offset + 1 : 0;
$registroFim = min($offset + $porPagina, $totalCertificados);

$sql = "SELECT c.id, c.numero, c.nome_embarcacao, c.data_emissao, c.data_validade, 
               c.status, c.assinado, c.local_emissao, c.criado_em
        FROM certificados_cnbl c
        {$whereBase}
        ORDER BY c.criado_em DESC
        LIMIT :limite OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titulo_page = 'Certificados CNBL - ' . APP_NAME;
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-header">
        <h2><i class="fas fa-file-certificate"></i> Certificados de Navegação para Embarcações de Borda Livre (CNBL)</h2>
        <div class="d-flex gap-2">
            <a href="<?php echo APP_URL; ?>certificados" class="btn btn-success">
                <i class="fas fa-plus"></i> Novo Certificado
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="<?php echo APP_URL; ?>documentacao/cnbl" class="d-flex gap-2" style="flex-wrap: wrap; align-items: flex-end;">
                <?php if ($porPagina !== 15): ?>
                    <input type="hidden" name="por_pagina" value="<?php echo (int)$porPagina; ?>">
                <?php endif; ?>
                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label for="busca"><i class="fas fa-search"></i> Buscar</label>
                    <input type="text" id="busca" name="busca" class="form-control" 
                           placeholder="Número ou nome da embarcação..." 
                           value="<?php echo h($busca); ?>">
                </div>
                <div class="form-group" style="min-width: 180px;">
                    <label for="status"><i class="fas fa-filter"></i> Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">Todos</option>
                        <option value="rascunho" <?php echo $filtro_status === 'rascunho' ? 'selected' : ''; ?>>Rascunho</option>
                        <option value="emitido" <?php echo $filtro_status === 'emitido' ? 'selected' : ''; ?>>Emitido</option>
                        <option value="assinado" <?php echo $filtro_status === 'assinado' ? 'selected' : ''; ?>>Assinado</option>
                        <option value="cancelado" <?php echo $filtro_status === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="<?php echo APP_URL; ?>documentacao/cnbl" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Certificados -->
    <div class="tabela-container">
        <?php if (empty($certificados)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-file-certificate" style="font-size: 3rem; opacity: 0.3;"></i>
                <p>Nenhum certificado CNBL encontrado.</p>
                <a href="<?php echo APP_URL; ?>certificados" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Criar Primeiro Certificado
                </a>
            </div>
        <?php else: ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Embarcação</th>
                        <th>Emissão</th>
                        <th>Validade</th>
                        <th>Status</th>
                        <th>Assinado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($certificados as $c): ?>
                        <tr>
                            <td><strong><?php echo h($c['numero']); ?></strong></td>
                            <td><?php echo h($c['nome_embarcacao']); ?></td>
                            <td><?php echo formatarData($c['data_emissao']); ?></td>
                            <td><?php echo formatarData($c['data_validade']); ?></td>
                            <td>
                                <?php
                                $badge_class = [
                                    'rascunho'  => 'badge-secondary',
                                    'emitido'   => 'badge-warning',
                                    'assinado'  => 'badge-success',
                                    'cancelado' => 'badge-danger',
                                ];
                                $bc = $badge_class[$c['status']] ?? 'badge-secondary';
                                ?>
                                <span class="badge <?php echo $bc; ?>">
                                    <?php echo h(ucfirst($c['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($c['assinado']): ?>
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Sim</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Não</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1" style="flex-wrap: nowrap;">
                                    <!-- Editar -->
                                    <a href="<?php echo APP_URL; ?>documentacao/cnbl/form?id=<?php echo h($c['id']); ?>" 
                                       class="btn btn-sm btn-primary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <!-- Gerar PDF -->
                                    <a href="<?php echo APP_URL; ?>documentacao/cnbl/pdf?id=<?php echo h($c['id']); ?>" 
                                       class="btn btn-sm btn-secondary" title="Gerar PDF" target="_blank">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    <?php renderBotaoAprovacaoDocumento($pdo,'CNBL',$c['id'],$c['status'],(bool)$c['assinado']); ?>

                                    <!-- Enviar por E-mail -->
                                    <form method="POST" action="<?php echo APP_URL; ?>documentacao/cnbl/actions" 
                                          style="display:inline;" 
                                          onsubmit="return confirm('Enviar certificado <?php echo h(addslashes($c['numero'])); ?> por e-mail para o cliente?')">
                                        <input type="hidden" name="action" value="enviar_certificado">
                                        <input type="hidden" name="id" value="<?php echo h($c['id']); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Enviar por E-mail">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                    </form>

                                    <!-- Enviar Link de Assinatura por E-mail -->
                                    <form method="POST" action="<?php echo APP_URL; ?>documentacao/cnbl/actions" 
                                          style="display:inline;" 
                                          onsubmit="return confirm('Enviar link de assinatura do certificado <?php echo h(addslashes($c['numero'])); ?> por e-mail?')">
                                        <input type="hidden" name="action" value="enviar_assinatura">
                                        <input type="hidden" name="id" value="<?php echo h($c['id']); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                        <button type="submit" class="btn btn-sm btn-warning" title="Enviar Link de Assinatura por E-mail">
                                            <i class="fas fa-file-signature"></i>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Resumo e Paginação -->
        <div class="card-footer" style="padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: #ffffff; border-top: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Mostrando <strong><?php echo $registroInicio; ?></strong> a <strong><?php echo $registroFim; ?></strong> de <strong><?php echo $totalCertificados; ?></strong> certificado(s)
                </small>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <label for="selectPorPagina" style="font-size: 0.82rem; color: #64748b; margin: 0;">Exibir:</label>
                    <select id="selectPorPagina" class="form-control form-control-sm" style="width: auto; height: 32px; padding: 2px 8px; font-size: 0.82rem; border-radius: 6px; border: 1px solid #cbd5e1;" onchange="window.location.href=this.value">
                        <?php foreach ([10, 15, 25, 50, 100] as $qtd): ?>
                            <option value="<?php echo h($cnblUrl(['por_pagina' => $qtd, 'pagina' => 1])); ?>" <?php echo $porPagina === $qtd ? 'selected' : ''; ?>>
                                <?php echo $qtd; ?> por pág.
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ($totalPaginas > 1): ?>
                <nav aria-label="Navegação de páginas de certificados CNBL">
                    <ul class="paginacao-certificados" style="display: flex; align-items: center; gap: 5px; margin: 0; padding: 0; list-style: none;">
                        <!-- Primeira página -->
                        <li class="paginacao-item <?php echo $paginaAtual <= 1 ? 'disabled' : ''; ?>">
                            <a class="paginacao-link" href="<?php echo $paginaAtual <= 1 ? 'javascript:void(0)' : h($cnblUrl(['pagina' => 1])); ?>" title="Primeira página">
                                <i class="fas fa-angles-left"></i>
                            </a>
                        </li>

                        <!-- Página anterior -->
                        <li class="paginacao-item <?php echo $paginaAtual <= 1 ? 'disabled' : ''; ?>">
                            <a class="paginacao-link" href="<?php echo $paginaAtual <= 1 ? 'javascript:void(0)' : h($cnblUrl(['pagina' => $paginaAtual - 1])); ?>" title="Página anterior">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>

                        <!-- Janela de páginas -->
                        <?php
                        $janelaInicio = max(1, $paginaAtual - 2);
                        $janelaFim = min($totalPaginas, $paginaAtual + 2);

                        if ($janelaInicio > 1) {
                            echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h($cnblUrl(['pagina' => 1])) . '">1</a></li>';
                            if ($janelaInicio > 2) {
                                echo '<li class="paginacao-ellipsis" style="padding: 0 4px; color: #94a3b8;">...</li>';
                            }
                        }

                        for ($p = $janelaInicio; $p <= $janelaFim; $p++) {
                            if ($p === $paginaAtual) {
                                echo '<li class="paginacao-item active"><span class="paginacao-link active-link" style="background: var(--cor-primaria, #0d9488); color: #ffffff; border-color: var(--cor-primaria, #0d9488); font-weight: 700;">' . $p . '</span></li>';
                            } else {
                                echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h($cnblUrl(['pagina' => $p])) . '">' . $p . '</a></li>';
                            }
                        }

                        if ($janelaFim < $totalPaginas) {
                            if ($janelaFim < $totalPaginas - 1) {
                                echo '<li class="paginacao-ellipsis" style="padding: 0 4px; color: #94a3b8;">...</li>';
                            }
                            echo '<li class="paginacao-item"><a class="paginacao-link" href="' . h($cnblUrl(['pagina' => $totalPaginas])) . '">' . $totalPaginas . '</a></li>';
                        }
                        ?>

                        <!-- Próxima página -->
                        <li class="paginacao-item <?php echo $paginaAtual >= $totalPaginas ? 'disabled' : ''; ?>">
                            <a class="paginacao-link" href="<?php echo $paginaAtual >= $totalPaginas ? 'javascript:void(0)' : h($cnblUrl(['pagina' => $paginaAtual + 1])); ?>" title="Próxima página">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>

                        <!-- Última página -->
                        <li class="paginacao-item <?php echo $paginaAtual >= $totalPaginas ? 'disabled' : ''; ?>">
                            <a class="paginacao-link" href="<?php echo $paginaAtual >= $totalPaginas ? 'javascript:void(0)' : h($cnblUrl(['pagina' => $totalPaginas])) ?>" title="Última página">
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

<script>
function copiarLink(url, btn) {
    navigator.clipboard.writeText(url).then(function() {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.remove('btn-info');
        btn.classList.add('btn-success');
        setTimeout(function() {
            btn.innerHTML = originalHtml;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-info');
        }, 2000);
    }).catch(function() {
        const input = document.createElement('input');
        input.value = url;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        alert('Link copiado!');
    });
}
</script>

<?php renderAprovacaoUi($pdo); require_once __DIR__ . '/../../../includes/footer.php'; ?>
