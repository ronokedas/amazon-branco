<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

verificar_sessao();
exigirAcesso('portal_clientes');

$busca = trim($_GET['busca'] ?? '');
$perfil = in_array($_GET['perfil'] ?? '', ['proprietario', 'despachante'], true) ? $_GET['perfil'] : '';
$selecionadoId = trim($_GET['id'] ?? '');
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 12;

$params = [];
$whereBusca = '';
if ($busca !== '') {
    $whereBusca = " AND (c.nome LIKE :busca_nome OR c.email LIKE :busca_email OR c.cpf_cnpj LIKE :busca_doc)";
    $params[':busca_nome'] = '%' . $busca . '%';
    $params[':busca_email'] = '%' . $busca . '%';
    $params[':busca_doc'] = '%' . $busca . '%';
}
$wherePerfil = $perfil ? ' AND c.perfil = :perfil' : " AND c.perfil IN ('proprietario','despachante')";
if ($perfil) $params[':perfil'] = $perfil;

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM clientes c WHERE c.status = 'ATIVO' {$wherePerfil} {$whereBusca}");
$stmtTotal->execute($params);
$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));
$pagina = min($pagina, $totalPaginas);
$offset = ($pagina - 1) * $porPagina;

$stmt = $pdo->prepare("
    SELECT c.id, c.nome, c.email, c.cpf_cnpj, c.telefone, c.perfil,
           (
               SELECT COUNT(DISTINCT e.id)
               FROM embarcacoes e
               LEFT JOIN clientes_embarcacoes ce2 ON ce2.embarcacao_id = e.id AND ce2.cliente_id = c.id AND ce2.status = 'ATIVO'
               WHERE e.ativo = 1
                 AND (e.proprietario_id = c.id OR e.cliente_id = c.id OR ce2.cliente_id IS NOT NULL)
           ) AS total_embarcacoes,
           a.login, a.ativo AS portal_ativo,
           a.forcar_troca_senha,
           a.ultimo_login_em,
           a.atualizado_em AS portal_atualizado_em
    FROM clientes c
    LEFT JOIN cliente_portal_acessos a ON a.cliente_id = c.id
    WHERE c.status = 'ATIVO'
      {$wherePerfil}
      {$whereBusca}
    GROUP BY c.id
    ORDER BY c.nome ASC
    LIMIT {$porPagina} OFFSET {$offset}
");
$stmt->execute($params);
$proprietarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selecionado = null;
$embarcacoesSelecionado = [];
if ($selecionadoId !== '') {
    $stmtSel = $pdo->prepare("
        SELECT c.*, a.ativo AS portal_ativo, a.forcar_troca_senha, a.ultimo_login_em
        FROM clientes c
        LEFT JOIN cliente_portal_acessos a ON a.cliente_id = c.id
        WHERE c.id = :id AND c.perfil IN ('proprietario','despachante') AND c.status = 'ATIVO'
        LIMIT 1
    ");
    $stmtSel->execute([':id' => $selecionadoId]);
    $selecionado = $stmtSel->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($selecionado) {
        $embarcacoesSelecionado = clientePortalEmbarcacoes($pdo, $selecionado['id'], $selecionado['perfil']);
    }
}

$titulo_page = 'Portal do Cliente - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="conteudo-principal">
    <div class="tabela-header">
        <h3><i class="fas fa-user-shield"></i> Portal do Cliente</h3>
        <a href="<?php echo APP_URL; ?>portal/login" class="btn btn-secondary btn-sm" target="_blank">
            <i class="fas fa-arrow-up-right-from-square"></i> Abrir portal
        </a>
    </div>

    <div class="portal-admin-layout">
        <section class="portal-admin-list">
            <form method="GET" class="filtros">
                <div class="form-group" style="flex:1; min-width:240px;">
                    <label for="busca">Buscar cliente ou despachante</label>
                    <input type="text" id="busca" name="busca" value="<?php echo h($busca); ?>" placeholder="Nome, e-mail ou CPF/CNPJ">
                </div>
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Buscar</button>
                <select name="perfil" aria-label="Perfil do portal">
                    <option value="">Todos</option>
                    <option value="proprietario" <?php echo $perfil === 'proprietario' ? 'selected' : ''; ?>>Proprietários</option>
                    <option value="despachante" <?php echo $perfil === 'despachante' ? 'selected' : ''; ?>>Despachantes</option>
                </select>
                <a class="btn btn-secondary" href="<?php echo APP_URL; ?>portal-clientes"><i class="fas fa-xmark"></i> Limpar</a>
            </form>

            <div class="tabela-container portal-admin-table-container" data-testid="portal-clientes-lista">
                <table class="tabela portal-admin-table">
                    <colgroup>
                        <col class="portal-admin-col-owner">
                        <col class="portal-admin-col-profile">
                        <col class="portal-admin-col-email">
                        <col class="portal-admin-col-boats">
                        <col class="portal-admin-col-access">
                        <col class="portal-admin-col-action">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Perfil</th>
                            <th>E-mail</th>
                            <th>Embarcações</th>
                            <th>Acesso</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($proprietarios as $p): ?>
                        <tr class="<?php echo $selecionadoId === $p['id'] ? 'is-selected' : ''; ?>">
                            <td data-label="Cliente"><strong><?php echo h($p['nome']); ?></strong></td>
                            <td data-label="Perfil"><?php echo h(ucfirst($p['perfil'])); ?></td>
                            <td data-label="E-mail"><?php echo h($p['email']); ?></td>
                            <td data-label="Embarcações" class="text-center"><?php echo (int)$p['total_embarcacoes']; ?></td>
                            <td data-label="Acesso">
                                <?php if ($p['portal_ativo'] === null): ?>
                                    <span class="badge badge-secondary">Não criado</span>
                                <?php elseif ((int)$p['portal_ativo'] === 1): ?>
                                    <span class="badge badge-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Ação" class="portal-admin-actions">
                                <a class="btn btn-primary btn-sm" title="Gerenciar acesso de <?php echo h($p['nome']); ?>" href="<?php echo APP_URL; ?>portal-clientes?id=<?php echo urlencode($p['id']); ?>&busca=<?php echo urlencode($busca); ?>&perfil=<?php echo urlencode($perfil); ?>&pagina=<?php echo $pagina; ?>">
                                    <i class="fas fa-key"></i> Acesso
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPaginas > 1): ?>
                <nav class="portal-admin-pagination" aria-label="Paginação de clientes">
                    <span><?php echo $totalRegistros; ?> clientes</span>
                    <div>
                        <?php if ($pagina > 1): ?><a class="btn btn-secondary btn-sm" href="?busca=<?php echo urlencode($busca); ?>&perfil=<?php echo urlencode($perfil); ?>&pagina=<?php echo $pagina - 1; ?>"><i class="fas fa-chevron-left"></i> Anterior</a><?php endif; ?>
                        <strong>Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?></strong>
                        <?php if ($pagina < $totalPaginas): ?><a class="btn btn-secondary btn-sm" href="?busca=<?php echo urlencode($busca); ?>&perfil=<?php echo urlencode($perfil); ?>&pagina=<?php echo $pagina + 1; ?>">Próxima <i class="fas fa-chevron-right"></i></a><?php endif; ?>
                    </div>
                </nav>
            <?php endif; ?>
        </section>

        <aside class="portal-admin-detail" data-testid="portal-clientes-detalhe">
            <?php if (!$selecionado): ?>
                <div class="portal-empty">Selecione um cliente ou despachante para criar ou reenviar o acesso ao portal.</div>
            <?php else: ?>
                <h4><?php echo h($selecionado['nome']); ?></h4>
                <p><?php echo h($selecionado['email']); ?></p>

                <div class="portal-admin-summary">
                    <span><i class="fas fa-ship"></i> <strong><?php echo count($embarcacoesSelecionado); ?></strong> embarcação(ões)</span>
                    <span><i class="fas fa-clock"></i> <strong><?php echo !empty($selecionado['ultimo_login_em']) ? formatarDataCompleta($selecionado['ultimo_login_em']) : 'Nunca'; ?></strong> último login</span>
                </div>

                <div class="portal-admin-boats">
                    <?php foreach ($embarcacoesSelecionado as $emb): ?>
                        <span><i class="fas fa-ship"></i> <?php echo h($emb['nome']); ?></span>
                    <?php endforeach; ?>
                </div>

                <form method="POST" action="<?php echo APP_URL; ?>portal-clientes/actions" class="form-padrao">
                    <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                    <input type="hidden" name="action" value="enviar_acesso">
                    <input type="hidden" name="cliente_id" value="<?php echo h($selecionado['id']); ?>">

                    <div class="form-group">
                        <label for="senha_temporaria">Senha temporária</label>
                        <div class="portal-password-row">
                            <input type="text" id="senha_temporaria" name="senha_temporaria" value="<?php echo h(clientePortalGerarSenhaFacil()); ?>" minlength="8" maxlength="20" required>
                            <button class="btn btn-secondary" type="button" onclick="gerarSenhaPortal()"><i class="fas fa-wand-magic-sparkles"></i> Gerar</button>
                        </div>
                        <small class="text-muted">O cliente será obrigado a trocar essa senha no primeiro acesso.</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full" <?php echo empty($selecionado['email']) ? 'disabled' : ''; ?>>
                        <i class="fas fa-envelope"></i> Enviar dados para o e-mail
                    </button>
                </form>
            <?php endif; ?>
        </aside>
    </div>
</div>

<script>
function gerarSenhaPortal() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let senha = '';
    for (let i = 0; i < 8; i++) {
        senha += chars[Math.floor(Math.random() * chars.length)];
    }
    document.getElementById('senha_temporaria').value = senha;
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
