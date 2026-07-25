<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
if (!podeAcessar('documentacao')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

$agendamento_id = $_GET['agendamento_id'] ?? '';

if (empty($agendamento_id)) {
    setMensagem('error', 'ID do agendamento não informado.');
    redirecionar(APP_URL . 'vistorias');
}

$stmt = $pdo->prepare("
    SELECT a.*,
           e.nome AS emb_nome,
           e.registro,
           e.tipo_embarcacao,
           e.arqueacao_bruta,
           e.indicativo_chamada,
           v.id AS vistoria_id,
           v.status,
           v.numero AS relatorio_numero
    FROM agendamentos a
    JOIN embarcacoes e ON a.embarcacao_id = e.id
    LEFT JOIN vistorias v ON v.id = (
        SELECT v2.id FROM vistorias v2
         WHERE v2.agendamento_id = a.id
         ORDER BY v2.criado_em DESC, v2.id DESC LIMIT 1
    )
    WHERE a.id = :agendamento_id
    LIMIT 1
");
$stmt->execute([':agendamento_id' => $agendamento_id]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dados) {
    setMensagem('error', 'Agendamento não encontrado.');
    redirecionar(APP_URL . 'agendamentos');
}

$status = $dados['status'] ?? '';
$pode_etapa2 = in_array($status, ['APROVADA', 'APROVADA_COM_EXIGENCIAS'], true);
if (!$pode_etapa2) {
    setMensagem('error', 'Este relatório não está aprovado para emissão de certificado.');
    redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id));
}
$liberacao = avaliarLiberacaoCertificacao($pdo, (string)$dados['vistoria_id']);
if (empty($liberacao['permitido'])) {
    setMensagem('error', $liberacao['mensagem']);
    redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id) . '&vistoria_id=' . urlencode((string)$liberacao['vistoria_id']));
}

$modelos_permitidos = certificadoModelosPermitidosPorAgendamento($pdo, (string)$agendamento_id);

$tipos = [
    'CSN'   => ['label' => 'Certificado de Segurança da Navegação', 'icone' => 'fa-ship', 'url' => APP_URL . 'documentacao/certificados/form?agendamento_id=' . urlencode($agendamento_id)],
    'CNBL'  => ['label' => 'Certificado Nacional de Borda Livre', 'icone' => 'fa-water', 'url' => APP_URL . 'documentacao/cnbl/form?agendamento_id=' . urlencode($agendamento_id)],
    'CNARQ' => ['label' => 'Certificado Nacional de Arqueação', 'icone' => 'fa-ruler-combined', 'url' => APP_URL . 'documentacao/cnarq/form?agendamento_id=' . urlencode($agendamento_id)],
];
$tipos = array_filter($tipos, static fn(array $info, string $tipo): bool => !empty($modelos_permitidos[$tipo]), ARRAY_FILTER_USE_BOTH);

$titulo_page = 'Emitir Certificado - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal flow-shell">
    <div class="flow-hero">
        <div>
            <span class="flow-eyebrow"><i class="fas fa-route"></i> Etapa 5 do fluxo</span>
            <h1><i class="fas fa-certificate"></i> Emitir Certificado</h1>
            <p>O relatório já foi aprovado. Escolha o documento correto para gerar o certificado com os dados da vistoria.</p>
        </div>
        <div class="flow-actions">
            <a href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode($agendamento_id) ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Voltar ao relatório
            </a>
        </div>
    </div>

    <div class="flow-track">
        <div class="flow-track-step"><span>01</span>Proposta</div>
        <div class="flow-track-step"><span>02</span>Agendamento</div>
        <div class="flow-track-step"><span>03</span>Vistoria</div>
        <div class="flow-track-step"><span>04</span>Aprovação</div>
        <div class="flow-track-step is-active"><span>05</span>Certificados</div>
    </div>

    <div class="smart-callout smart-callout--success" style="margin-bottom: 18px;">
        <strong><?= h($dados['emb_nome']) ?></strong>
        <span class="text-muted">
            · Registro: <?= h($dados['registro'] ?: 'S/N') ?>
            · Relatório: <?= h($dados['relatorio_numero'] ?? 'S/N') ?>
            · Status: <?= h($status) ?>
        </span>
    </div>

    <div class="form-container">
        <div class="form-header">
            <h3><i class="fas fa-file-signature"></i> Selecione o tipo de certificado</h3>
            <span class="help-text">A próxima tela virá pré-preenchida pelo agendamento e relatório.</span>
        </div>

        <div class="smart-form-body">
            <?php if (empty($tipos)): ?>
                <div class="alert alert-warning" style="margin:0;">
                    <strong>Nenhum certificado disponível.</strong>
                    A proposta desta embarcação não possui serviço contratado classificado para emissão de CSN, CNBL ou CNARQ.
                </div>
            <?php else: ?>
                <div class="certificate-choice-grid">
                    <?php foreach ($tipos as $tipo => $info): ?>
                        <a href="<?= APP_URL ?>certificados/wizard?modelo=<?= urlencode($tipo) ?>&agendamento_id=<?= urlencode($agendamento_id) ?>" class="certificate-choice-card">
                            <span class="certificate-choice-icon"><i class="fas <?= h($info['icone']) ?>"></i></span>
                            <strong><?= h($tipo) ?></strong>
                            <small><?= h($info['label']) ?></small>
                            <span class="certificate-choice-action">Gerar <i class="fas fa-arrow-right"></i></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
