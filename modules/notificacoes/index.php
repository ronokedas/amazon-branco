<?php
/**
 * MÓDULO: NOTIFICAÇÕES
 * Arquivo: index.php - Painel central de notificações do usuário
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
$usuarioId = $_SESSION['usuario_id'] ?? '';

// Buscar notificações do usuário
$stmt = $pdo->prepare('SELECT * FROM notificacoes WHERE usuario_id = :usuario ORDER BY criado_em DESC LIMIT 100');
$stmt->execute([':usuario' => $usuarioId]);
$notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contadores para filtros
$totalNaoLidas = 0;
foreach ($notificacoes as $n) {
    if (empty($n['lida_em'])) {
        $totalNaoLidas++;
    }
}
$totalNotificacoes = count($notificacoes);
$totalLidas = $totalNotificacoes - $totalNaoLidas;

$filtroAtivo = $_GET['filtro'] ?? 'todas';
if (!in_array($filtroAtivo, ['todas', 'nao_lidas', 'lidas'], true)) {
    $filtroAtivo = 'todas';
}

$notificacoesFiltradas = array_filter($notificacoes, function ($n) use ($filtroAtivo) {
    if ($filtroAtivo === 'nao_lidas') return empty($n['lida_em']);
    if ($filtroAtivo === 'lidas') return !empty($n['lida_em']);
    return true;
});

function iconeNotificacao(string $titulo, ?string $url): string {
    $t = strtolower($titulo . ' ' . (string)$url);
    if (str_contains($t, 'vistoria')) return 'fa-clipboard-check';
    if (str_contains($t, 'proposta') || str_contains($t, 'comercial')) return 'fa-file-invoice-dollar';
    if (str_contains($t, 'certificado')) return 'fa-award';
    if (str_contains($t, 'protocolo') || str_contains($t, 'dossie')) return 'fa-arrow-right-arrow-left';
    if (str_contains($t, 'exigencia') || str_contains($t, 'alerta') || str_contains($t, 'urgente')) return 'fa-triangle-exclamation';
    if (str_contains($t, 'agendamento')) return 'fa-calendar-days';
    return 'fa-bell';
}

$titulo_page = 'Notificações (' . $totalNaoLidas . ') - Amazon Certificadora';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content" id="mainContent">
    <div class="conteudo-principal" style="max-width: 980px; margin: 0 auto; padding: 10px 15px;">
        
        <!-- Cabeçalho Padronizado -->
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; margin-bottom: 24px;">
            <div>
                <span class="flow-eyebrow" style="color: var(--cor-destaque, #2596be); font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">
                    <i class="fa-solid fa-bell"></i> Central de Avisos
                </span>
                <h1 class="page-title" style="margin: 4px 0 6px 0; font-size: 1.6rem; color: var(--cor-texto, #1e293b); display: flex; align-items: center; gap: 10px;">
                    Minhas Notificações
                    <?php if ($totalNaoLidas > 0): ?>
                        <span class="badge badge-warning" style="font-size: 0.8rem; background: #f59e0b; color: #fff; padding: 3px 9px; border-radius: 20px; font-weight: 600;">
                            <?= $totalNaoLidas ?> nova<?= $totalNaoLidas === 1 ? '' : 's' ?>
                        </span>
                    <?php endif; ?>
                </h1>
                <p class="page-subtitle" style="color: var(--cor-texto-secundario, #64748b); margin: 0; font-size: 0.95rem;">
                    Acompanhe alertas operacionais de vistorias, aprovações, emissões de certificados e prazos da Marinha.
                </p>
            </div>

            <?php if ($totalNaoLidas > 0): ?>
                <form method="post" action="<?= APP_URL ?>notificacoes/actions" style="margin: 0;">
                    <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                    <button type="submit" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-check-double"></i> Marcar todas como lidas
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Abas / Filtros Rápidos (Chips) -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid var(--cor-borda, #e2e8f0); padding-bottom: 12px;">
            <a href="<?= APP_URL ?>notificacoes?filtro=todas" 
               class="btn btn-sm <?= $filtroAtivo === 'todas' ? 'btn-primary' : 'btn-secondary' ?>" 
               style="border-radius: 20px; padding: 6px 16px;">
                Todas (<?= $totalNotificacoes ?>)
            </a>
            <a href="<?= APP_URL ?>notificacoes?filtro=nao_lidas" 
               class="btn btn-sm <?= $filtroAtivo === 'nao_lidas' ? 'btn-primary' : 'btn-secondary' ?>" 
               style="border-radius: 20px; padding: 6px 16px;">
                Não lidas (<?= $totalNaoLidas ?>)
            </a>
            <a href="<?= APP_URL ?>notificacoes?filtro=lidas" 
               class="btn btn-sm <?= $filtroAtivo === 'lidas' ? 'btn-primary' : 'btn-secondary' ?>" 
               style="border-radius: 20px; padding: 6px 16px;">
                Lidas (<?= $totalLidas ?>)
            </a>
        </div>

        <!-- Lista de Notificações -->
        <?php if (empty($notificacoesFiltradas)): ?>
            <div class="card" style="background: var(--cor-painel, #fff); border: 1px solid var(--cor-borda, #e2e8f0); border-radius: 12px; padding: 50px 20px; text-align: center;">
                <div style="width: 60px; height: 60px; margin: 0 auto 16px auto; border-radius: 50%; background: rgba(37, 150, 190, 0.1); color: #2596be; display: flex; align-items: center; justify-content: center; font-size: 1.6rem;">
                    <i class="fa-regular fa-bell"></i>
                </div>
                <h3 style="margin: 0 0 6px 0; font-size: 1.15rem; color: var(--cor-texto, #1e293b);">
                    <?= $filtroAtivo === 'nao_lidas' ? 'Você não tem notificações pendentes!' : 'Nenhuma notificação encontrada' ?>
                </h3>
                <p style="color: var(--cor-texto-secundario, #64748b); font-size: 0.9rem; margin: 0;">
                    <?= $filtroAtivo === 'nao_lidas' ? 'Todas as suas mensagens e alertas operacionais já foram visualizados.' : 'Quando houver novos agendamentos, vistorias ou atualizações de processos, eles aparecerão aqui.' ?>
                </p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($notificacoesFiltradas as $n): ?>
                    <?php 
                    $naoLida = empty($n['lida_em']); 
                    $icone = iconeNotificacao($n['titulo'] ?? '', $n['url'] ?? '');
                    ?>
                    <div class="card notification-item" style="background: <?= $naoLida ? 'var(--cor-painel, #fff)' : '#f8fafc' ?>; border: 1px solid <?= $naoLida ? '#2596be' : 'var(--cor-borda, #e2e8f0)' ?>; border-left: 4px solid <?= $naoLida ? '#2596be' : '#94a3b8' ?>; border-radius: 10px; padding: 16px 20px; transition: all 0.2s ease;">
                        <div style="display: flex; align-items: flex-start; gap: 15px;">
                            <div style="width: 38px; height: 38px; border-radius: 8px; background: <?= $naoLida ? 'rgba(37, 150, 190, 0.12)' : 'rgba(148, 163, 184, 0.15)' ?>; color: <?= $naoLida ? '#2596be' : '#64748b' ?>; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; margin-top: 2px;">
                                <i class="fa-solid <?= $icone ?>"></i>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 8px; margin-bottom: 4px;">
                                    <h4 style="margin: 0; font-size: 1rem; color: var(--cor-texto, #1e293b); font-weight: <?= $naoLida ? '700' : '600' ?>;">
                                        <?= h($n['titulo']) ?>
                                        <?php if ($naoLida): ?>
                                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #2596be; margin-left: 6px;" title="Não lida"></span>
                                        <?php endif; ?>
                                    </h4>
                                    <span style="font-size: 0.8rem; color: var(--cor-texto-secundario, #64748b); white-space: nowrap;">
                                        <i class="fa-regular fa-clock"></i> <?= formatarDataCompleta($n['criado_em']) ?>
                                    </span>
                                </div>
                                <p style="margin: 0 0 10px 0; font-size: 0.9rem; color: <?= $naoLida ? 'var(--cor-texto, #334155)' : '#64748b' ?>; line-height: 1.5;">
                                    <?= nl2br(h($n['mensagem'])) ?>
                                </p>
                                <?php if (!empty($n['url'])): ?>
                                    <div>
                                        <a href="<?= h(APP_URL . ltrim($n['url'], '/')) ?>" class="btn btn-outline-primary btn-sm" style="font-size: 0.8rem; padding: 4px 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                                            Acessar registro <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
