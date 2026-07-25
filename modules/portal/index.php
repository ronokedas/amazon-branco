<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

requireClienteSenhaDefinitiva();

$clienteId = clientePortalId();
$embarcacoes = clientePortalEmbarcacoes($pdo, $clienteId);
$documentos = clientePortalSelectDocumentos($pdo, $clienteId);
$vencendo = clientePortalSelectDocumentos($pdo, $clienteId, ['vencendo_dias' => 90]);
$documentosRecentes = array_slice($documentos, 0, 5);
$documentosAssinados = array_filter($documentos, fn($doc) => ($doc['status'] ?? '') === 'assinado');
$documentosPendentes = array_filter($documentos, fn($doc) => ($doc['status'] ?? '') === 'emitido');

$diasAteVencer = function (?string $data): ?int {
    if (empty($data)) {
        return null;
    }
    $hoje = new DateTimeImmutable('today');
    $validade = new DateTimeImmutable($data);
    return (int)$hoje->diff($validade)->format('%r%a');
};

$titulo_page = 'Portal do Cliente';
require_once __DIR__ . '/../../includes/portal_header.php';
?>
<div class="portal-dashboard">
    <section class="portal-hero">
        <div class="portal-hero-content">
            <span>Bem-vindo, <?php echo h(clientePortalNome()); ?></span>
            <h1>Portal do Cliente</h1>
            <p>Acesse seus certificados e acompanhe a situação dos seus documentos e embarcações.</p>
            <a class="portal-hero-button" href="<?php echo APP_URL; ?>portal/documentos">
                Ver documentos <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </section>

    <aside class="portal-panel portal-expiry-panel">
        <div class="portal-panel-header">
            <h2><i class="fa-regular fa-calendar"></i> Próximos vencimentos</h2>
            <a href="<?php echo APP_URL; ?>portal/documentos?vencendo=1">Ver todos</a>
        </div>
        <?php if (empty($vencendo)): ?>
            <div class="portal-empty">Nenhum certificado vencendo nos próximos 90 dias.</div>
        <?php else: ?>
            <div class="portal-expiry-list">
                <?php foreach (array_slice($vencendo, 0, 4) as $doc): ?>
                    <?php $dias = $diasAteVencer($doc['data_validade']); ?>
                    <a class="portal-expiry-row <?php echo ($dias !== null && $dias < 0) ? 'is-expired' : ''; ?>" href="<?php echo APP_URL; ?>portal/documentos/pdf?tipo=<?php echo h($doc['tipo']); ?>&id=<?php echo h($doc['id']); ?>" target="_blank">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>
                            <strong><?php echo h($doc['tipo_label'] . ' - ' . $doc['numero']); ?></strong>
                            <small><?php echo h($doc['embarcacao_nome']); ?></small>
                        </span>
                        <em>
                            <?php if ($dias === null): ?>
                                Validade<br><strong>Sem data</strong>
                            <?php elseif ($dias < 0): ?>
                                Vencido há<br><strong><?php echo h((string)abs($dias)); ?> dias</strong>
                            <?php else: ?>
                                Vence em<br><strong><?php echo h((string)$dias); ?> dias</strong>
                            <?php endif; ?>
                        </em>
                    </a>
                <?php endforeach; ?>
            </div>
            <a class="portal-panel-footer-link" href="<?php echo APP_URL; ?>portal/documentos?vencendo=1">Ver todos os vencimentos</a>
        <?php endif; ?>
    </aside>

    <section class="portal-metrics">
        <div class="portal-metric">
            <i class="fa-regular fa-file-lines"></i>
            <strong><?php echo count($documentos); ?></strong>
            <span>Total de documentos<br>ativos</span>
        </div>
        <div class="portal-metric">
            <i class="fa-regular fa-circle-check"></i>
            <strong><?php echo count($documentosAssinados); ?></strong>
            <span>Documentos<br>válidos</span>
        </div>
        <div class="portal-metric">
            <i class="fa-regular fa-clock"></i>
            <strong><?php echo count($vencendo); ?></strong>
            <span>Documentos<br>a vencer</span>
        </div>
        <div class="portal-metric">
            <i class="fa-regular fa-file-arrow-up"></i>
            <strong><?php echo count($documentosPendentes); ?></strong>
            <span>Documentos<br>em pendência</span>
        </div>
    </section>

    <section class="portal-panel portal-boats-panel">
        <div class="portal-panel-header">
            <h2>Embarcações</h2>
            <a href="<?php echo APP_URL; ?>portal/embarcacoes">Ver todas</a>
        </div>
        <?php if (empty($embarcacoes)): ?>
            <div class="portal-empty">Nenhuma embarcação vinculada ao seu cadastro.</div>
        <?php else: ?>
            <div class="portal-table-wrap">
                <table class="portal-table portal-table-compact">
                    <thead>
                        <tr>
                            <th>Nome da embarcação</th>
                            <th>Registro</th>
                            <th>Tipo</th>
                            <th>Situação</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($embarcacoes, 0, 4) as $emb): ?>
                            <tr>
                                <td data-label="Embarcação">
                                    <i class="fa-solid fa-ship portal-table-icon"></i>
                                    <strong><?php echo h($emb['nome']); ?></strong>
                                </td>
                                <td data-label="Registro"><?php echo h($emb['registro'] ?: ($emb['numero_inscricao'] ?: '-')); ?></td>
                                <td data-label="Tipo"><?php echo h($emb['tipo_embarcacao'] ?: '-'); ?></td>
                                <td data-label="Situação"><span class="portal-status is-valid">Ativa</span></td>
                                <td data-label="Documentos"><a class="portal-icon-action" href="<?php echo APP_URL; ?>portal/documentos?embarcacao_id=<?php echo urlencode($emb['id']); ?>" title="Ver documentos"><i class="fa-solid fa-arrow-right"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a class="portal-panel-footer-link" href="<?php echo APP_URL; ?>portal/embarcacoes">Ver todas as embarcações</a>
        <?php endif; ?>
    </section>

    <section class="portal-panel portal-docs-panel">
        <div class="portal-panel-header">
            <h2>Documentos recentes</h2>
            <a href="<?php echo APP_URL; ?>portal/documentos">Ver todos</a>
        </div>
        <?php if (empty($documentosRecentes)): ?>
            <div class="portal-empty">Nenhum documento emitido foi encontrado para o seu cadastro.</div>
        <?php else: ?>
            <div class="portal-table-wrap">
                <table class="portal-table portal-table-compact">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Embarcação</th>
                            <th>Emissão</th>
                            <th>Validade</th>
                            <th>Situação</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documentosRecentes as $doc): ?>
                            <?php $dias = $diasAteVencer($doc['data_validade']); ?>
                            <tr>
                                <td data-label="Documento">
                                    <i class="fa-regular fa-file-lines portal-table-icon"></i>
                                    <strong><?php echo h($doc['tipo_label'] . ' - ' . $doc['numero']); ?></strong>
                                </td>
                                <td data-label="Embarcação"><?php echo h($doc['embarcacao_nome']); ?></td>
                                <td data-label="Emissão"><?php echo formatarData($doc['data_emissao']); ?></td>
                                <td data-label="Validade"><?php echo formatarData($doc['data_validade']); ?></td>
                                <td data-label="Situação">
                                    <span class="portal-status <?php echo ($dias !== null && $dias < 0) ? 'is-expired' : (($dias !== null && $dias <= 90) ? 'is-warning' : 'is-valid'); ?>">
                                        <?php echo ($dias !== null && $dias < 0) ? 'Vencido' : (($dias !== null && $dias <= 90) ? 'A vencer' : 'Válido'); ?>
                                    </span>
                                </td>
                                <td data-label="Ação">
                                    <a class="portal-icon-action" target="_blank" href="<?php echo APP_URL; ?>portal/documentos/pdf?tipo=<?php echo h($doc['tipo']); ?>&id=<?php echo h($doc['id']); ?>" title="Visualizar PDF">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a class="portal-panel-footer-link" href="<?php echo APP_URL; ?>portal/documentos">Ver documentos</a>
        <?php endif; ?>
    </section>

    <footer class="portal-dashboard-footer">
        <span><i class="fa-regular fa-shield-check"></i> Conformidade e segurança em cada certificação.</span>
        <span>© <?php echo date('Y'); ?> Amazon Certificadora. Todos os direitos reservados.</span>
        <strong><i class="fa-solid fa-lock"></i> Ambiente seguro</strong>
    </footer>
</div>
<?php require_once __DIR__ . '/../../includes/portal_footer.php'; ?>
