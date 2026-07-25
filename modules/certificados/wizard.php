<?php
/**
 * MODULO: CERTIFICADOS
 * Arquivo: wizard.php - Passo 1: selecionar o tipo do certificado
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

exigirAcesso('certificados');

$modelo = $_GET['modelo'] ?? '';
if (empty($modelo)) {
    header('Location: ' . APP_URL . 'certificados');
    exit;
}

$agendamento_id = $_GET['agendamento_id'] ?? ($_SESSION['wizard_certificado']['agendamento_id'] ?? '');

$modelo_nomes = [
    'CSN' => 'Certificado de Segurança da Navegação',
    'CNBL' => 'Certificado Nacional de Borda Livre',
    'CNARQ' => 'Certificado Nacional de Arqueação',
    'LP' => 'Licença Provisória',
    'LC' => 'Licença de Construção',
    'CHT' => 'Certificado de Habilitação ao Transporte',
];

$modelo_nome = $modelo_nomes[$modelo] ?? $modelo;
$erro = '';
$tipo_selecionado = $_POST['tipo'] ?? ($_SESSION['wizard_certificado']['tipo'] ?? '');
$modelos_sem_tipo = ['LP', 'LC', 'CHT'];
$relatorio_status = '';
$vistoria_id = '';

if (in_array($modelo, ['CSN', 'CNBL', 'CNARQ'], true)) {
    if ($agendamento_id === '' || !certificadoModeloPermitidoPorAgendamento($pdo, (string)$agendamento_id, $modelo)) {
        setMensagem('error', certificadoMensagemServicoObrigatorio($modelo));
        redirecionar($agendamento_id !== ''
            ? APP_URL . 'documentacao/novo_certificado?agendamento_id=' . urlencode((string)$agendamento_id)
            : APP_URL . 'certificados');
    }
}

if (!empty($agendamento_id)) {
    try {
        $stmtRelatorioStatus = $pdo->prepare("
            SELECT id, status
            FROM vistorias
            WHERE agendamento_id = :agendamento_id
            ORDER BY criado_em DESC
            LIMIT 1
        ");
        $stmtRelatorioStatus->execute([':agendamento_id' => $agendamento_id]);
        $relatorioVigente = $stmtRelatorioStatus->fetch(PDO::FETCH_ASSOC) ?: [];
        $vistoria_id = (string)($relatorioVigente['id'] ?? '');
        $relatorio_status = (string)($relatorioVigente['status'] ?? '');
    } catch (Exception $e) {
        error_log('Erro ao buscar status do relatorio no wizard: ' . $e->getMessage());
    }
}

if (!empty($vistoria_id)) {
    $liberacaoCertificacao = avaliarLiberacaoCertificacao($pdo, $vistoria_id);
    if (empty($liberacaoCertificacao['permitido'])) {
        $erro = $liberacaoCertificacao['mensagem'];
    }
}

$bloquear_definitivo = ($relatorio_status === 'APROVADA_COM_EXIGENCIAS');
if ($bloquear_definitivo && $tipo_selecionado === 'Definitivo') {
    $tipo_selecionado = '';
}

if (in_array($modelo, $modelos_sem_tipo, true) && $_SERVER['REQUEST_METHOD'] !== 'POST' && $erro === '') {
    $_SESSION['wizard_certificado'] = [
        'modelo' => $modelo,
        'modelo_nome' => $modelo_nome,
        'tipo' => 'Documento',
        'agendamento_id' => $agendamento_id,
        'vistoria_id' => $vistoria_id,
    ];

    header('Location: ' . APP_URL . 'certificados/wizard_step2');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';

    if ($erro !== '') {
        // Mantem a mensagem da regra central de certificacao.
    } elseif (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } elseif (empty($tipo)) {
        $erro = 'Selecione o tipo do certificado antes de avançar.';
    } elseif ($bloquear_definitivo && $tipo === 'Definitivo') {
        $erro = 'Relatorio aprovado com exigencias nao permite certificado Definitivo. Use Provisorio ou Condicional.';
    } else {
        $_SESSION['wizard_certificado'] = [
            'modelo' => $modelo,
            'modelo_nome' => $modelo_nome,
            'tipo' => $tipo,
            'agendamento_id' => $agendamento_id,
            'vistoria_id' => $vistoria_id,
        ];

        header('Location: ' . APP_URL . 'certificados/wizard_step2');
        exit;
    }
}

$tipos = [
    [
        'valor' => 'Provisório',
        'titulo' => 'Provisório',
        'icone' => 'fa-hourglass-half',
        'resumo' => 'Ideal quando ainda existem condicionantes a cumprir.',
        'descricao' => 'Possui validade reduzida e quadro de observações com exigências do relatório. Se as exigências não forem cumpridas, o certificado pode ser cancelado.',
    ],
    [
        'valor' => 'Condicional',
        'titulo' => 'Condicional',
        'icone' => 'fa-triangle-exclamation',
        'resumo' => 'Usado quando o documento depende de condições específicas.',
        'descricao' => 'Semelhante ao provisório, mas identificado como condicional. Mantém vínculo com exigências ou restrições até a emissão definitiva.',
    ],
    [
        'valor' => 'Definitivo',
        'titulo' => 'Definitivo',
        'icone' => 'fa-circle-check',
        'resumo' => 'Para relatório aprovado sem pendências impeditivas.',
        'descricao' => 'Usado quando não há pendências impeditivas. Pode conter o quadro completo de convalidações anuais e intermediárias.',
    ],
];

$titulo_page = 'Wizard de Emissão - Passo 1';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content" id="mainContent">
    <div class="page-header">
        <div>
            <h1 class="page-title">Assistente de emissão</h1>
            <p class="page-subtitle"><?= h($modelo) ?> · <?= h($modelo_nome) ?></p>
        </div>
        <div class="page-actions">
            <a href="<?= APP_URL ?>certificados" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Trocar modelo
            </a>
        </div>
    </div>

    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-xmark"></i> <?= h($erro) ?>
        </div>
    <?php endif; ?>

    <div class="cert-workspace cert-workspace--wizard">
        <aside class="cert-flow-sidebar">
            <div class="cert-flow-title">
                <i class="fas fa-route"></i>
                <div>
                    <strong>Etapas da emissão</strong>
                    <span>Você está escolhendo o tipo</span>
                </div>
            </div>

            <ol class="cert-step-list">
                <li class="is-done">
                    <span><i class="fas fa-check"></i></span>
                    <div>
                        <strong>Modelo</strong>
                        <small><?= h($modelo) ?></small>
                    </div>
                </li>
                <li class="is-active">
                    <span>02</span>
                    <div>
                        <strong>Tipo</strong>
                        <small>Provisório, condicional ou definitivo.</small>
                    </div>
                </li>
                <li>
                    <span>03</span>
                    <div>
                        <strong>Relatório e dados</strong>
                        <small>Selecionar relatório aprovado.</small>
                    </div>
                </li>
                <li>
                    <span>04</span>
                    <div>
                        <strong>Gerar certificado</strong>
                        <small>Finalizar emissão.</small>
                    </div>
                </li>
            </ol>
        </aside>

        <section class="cert-main-panel">
            <div class="cert-panel-header">
                <div>
                    <h2>Escolha o tipo do certificado</h2>
                    <p>Essa escolha define as regras de emissão e quais relatórios podem ser usados na próxima etapa.</p>
                </div>
            </div>

            <form method="POST" action="" class="cert-wizard-form">
                <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">

                <div class="cert-type-grid">
                    <?php foreach ($tipos as $tipo): ?>
                        <?php if ($bloquear_definitivo && ($tipo['valor'] ?? '') === 'Definitivo') continue; ?>
                        <?php $checked = $tipo_selecionado === $tipo['valor']; ?>
                        <label class="cert-type-card">
                            <input type="radio" name="tipo" value="<?= h($tipo['valor']) ?>" <?= $checked ? 'checked' : '' ?> required>
                            <span class="cert-type-icon"><i class="fas <?= h($tipo['icone']) ?>"></i></span>
                            <span class="cert-type-content">
                                <strong><?= h($tipo['titulo']) ?></strong>
                                <em><?= h($tipo['resumo']) ?></em>
                                <small><?= h($tipo['descricao']) ?></small>
                            </span>
                            <span class="cert-type-check"><i class="fas fa-check"></i></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="cert-action-bar">
                    <a href="<?= !empty($agendamento_id) ? APP_URL . 'documentacao/novo_certificado?agendamento_id=' . urlencode($agendamento_id) : APP_URL . 'certificados' ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Avançar para relatório <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </section>

        <aside class="cert-help-panel">
            <strong>Como decidir?</strong>
            <p>Se o relatório foi aprovado com exigências, use Provisório ou Condicional. Para Definitivo, o relatório precisa estar aprovado sem pendências impeditivas.</p>
            <div class="cert-help-note">
                <i class="fas fa-lightbulb"></i>
                <span>Na próxima etapa o sistema bloqueia combinações inválidas para reduzir erro operacional.</span>
            </div>
        </aside>
    </div>
</div>

<script>
document.querySelectorAll('.cert-type-card input[type="radio"]').forEach((radio) => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.cert-type-card').forEach((card) => {
            const input = card.querySelector('input[type="radio"]');
            card.classList.toggle('is-selected', Boolean(input && input.checked));
        });
    });
});

document.querySelectorAll('.cert-type-card').forEach((card) => {
    const input = card.querySelector('input[type="radio"]');
    card.classList.toggle('is-selected', Boolean(input && input.checked));
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
