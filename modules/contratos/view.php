<?php
/**
 * MODULO: CONTRATOS
 * Arquivo: view.php - Visualizar / Assinar Contrato
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
if (getCargo() !== 'ADMIN' && getCargo() !== 'VENDEDOR') {
    setMensagem('error', 'Acesso negado.');
    redirecionar(APP_URL . 'dashboard');
}

$id = $_GET['id'] ?? '';
if (!$id) redirecionar(APP_URL . 'contratos');

$stmt = $pdo->prepare("
    SELECT c.*, p.nome AS nome_completo, p.cpf_cnpj, p.endereco, p.telefone, p.email,
           pr.numero as proposta_numero
    FROM contratos c
    JOIN clientes p ON c.cliente_id = p.id
    LEFT JOIN propostas pr ON c.proposta_id = pr.id
    WHERE c.id = :id AND c.ativo = 1
");
$stmt->execute([':id' => $id]);
$contrato = $stmt->fetch();

if (!$contrato) {
    setMensagem('error', 'Contrato nÃ£o encontrado.');
    redirecionar(APP_URL . 'contratos');
}

$doc_cliente = $contrato['cpf_cnpj'] ?? '';
$contrato_expirado = !empty($contrato['data_vencimento'])
    && date('Y-m-d') > $contrato['data_vencimento']
    && $contrato['status'] !== 'ASSINADO';

if (isset($_POST['assinar'])) {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token invÃ¡lido.');
        redirecionar(APP_URL . "contratos/view?id=$id");
    }

    if ($contrato_expirado) {
        setMensagem('error', 'Este contrato expirou e nÃ£o pode mais ser assinado.');
        redirecionar(APP_URL . "contratos/view?id=$id");
    }

    $ip = obterIpCliente();
    $nome = $_SESSION['usuario_nome'] ?? 'Interno';

    $stmtAssinar = $pdo->prepare("
        UPDATE contratos
        SET status = 'ASSINADO', assinado_por = :nome, assinado_ip = :ip, assinado_em = NOW()
        WHERE id = :id
    ");
    $stmtAssinar->execute([
        ':nome' => $nome,
        ':ip' => $ip,
        ':id' => $id
    ]);

    setMensagem('success', 'Contrato marcado como Assinado!');
    redirecionar(APP_URL . "contratos/view?id=$id");
}

$titulo_page = 'Contrato ' . $contrato['numero'] . ' - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<style>
.documento-papel {
    background: #fff;
    color: #333;
    padding: 40px;
    border-radius: 4px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    max-width: 800px;
    margin: 0 auto;
    font-family: 'Times New Roman', Times, serif;
    line-height: 1.6;
}
.documento-papel h1, .documento-papel h2, .documento-papel h3 {
    color: #000;
    text-align: center;
}
@media print {
    body * { visibility: hidden; }
    .documento-papel, .documento-papel * { visibility: visible; }
    .documento-papel { position: absolute; left: 0; top: 0; padding: 0; box-shadow: none; max-width: 100%; }
}
</style>

<div class="main-content">
    <div class="page-header d-flex" style="justify-content: space-between; align-items: center;">
        <div>
            <a href="<?= APP_URL ?>contratos" class="btn-link mb-1"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
            <h1 class="page-title">Contrato <?= h($contrato['numero']) ?></h1>
            <p class="page-subtitle">VisualizaÃ§Ã£o e impressÃ£o do contrato</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fa-solid fa-print"></i> Imprimir
            </button>
            <?php if ($contrato['status'] !== 'ASSINADO'): ?>
                <a href="<?= APP_URL ?>contratos/form?id=<?= $id ?>" class="btn btn-secondary">
                    <i class="fa-solid fa-pen"></i> Editar
                </a>
                <?php if (!$contrato_expirado): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Marcar este contrato como assinado?');">
                        <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                        <button type="submit" name="assinar" class="btn btn-success">
                            <i class="fa-solid fa-signature"></i> Assinar
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($contrato_expirado): ?>
        <div class="alert alert-warning mb-3">
            Este contrato expirou em <?= formatarData($contrato['data_vencimento']) ?> e precisa ser reemitido para uma nova assinatura.
        </div>
    <?php endif; ?>

    <div class="documento-papel">
        <h3>CONTRATO DE PRESTACAO DE SERVICOS</h3>
        <p style="text-align: right;"><strong>NÃºmero:</strong> <?= h($contrato['numero']) ?></p>

        <hr style="border:1px solid #ccc; margin: 20px 0;">

        <p><strong>CONTRATANTE:</strong> <?= h($contrato['nome_completo']) ?>, inscrito(a) no CPF/CNPJ <?= h($doc_cliente) ?>, residente/sediado(a) em <?= h($contrato['endereco'] ?: 'N/A') ?>.</p>
        <p><strong>CONTRATADA:</strong> <?= APP_NAME ?>, com sede e dados conforme registro oficial.</p>

        <?php if ($contrato['proposta_numero']): ?>
            <p><strong>PROPOSTA REFERÃŠNCIA:</strong> <?= h($contrato['proposta_numero']) ?></p>
        <?php endif; ?>

        <p><strong>VALOR TOTAL:</strong> R$ <?= number_format($contrato['valor_total'], 2, ',', '.') ?></p>
        <p><strong>DATA DE EMISS?O:</strong> <?= formatarData($contrato['data_emissao']) ?></p>
        <p><strong>VALIDADE PARA ASSINATURA:</strong> <?= $contrato['data_vencimento'] ? formatarData($contrato['data_vencimento']) : 'N/A' ?></p>

        <hr style="border:1px solid #ccc; margin: 20px 0;">

        <div style="white-space: pre-wrap;">
            <?= h($contrato['conteudo']) ?>
        </div>

        <hr style="border:1px solid #ccc; margin: 40px 0;">

        <?php if ($contrato['status'] === 'ASSINADO'): ?>
            <div style="text-align: center; color: green; border: 2px solid green; padding: 20px; border-radius: 8px;">
                <i class="fa-solid fa-check-circle" style="font-size: 24px;"></i><br>
                <strong>ASSINADO ELETRONICAMENTE</strong><br>
                Por: <?= h($contrato['assinado_por']) ?><br>
                IP: <?= h($contrato['assinado_ip']) ?><br>
                Data/Hora: <?= formatarDataCompleta($contrato['assinado_em']) ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; margin-top: 50px;">
                <p>________________________________________________</p>
                <p><strong><?= h($contrato['nome_completo']) ?></strong></p>
                <p>CONTRATANTE</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
