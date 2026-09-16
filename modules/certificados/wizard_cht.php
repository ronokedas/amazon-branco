<?php
/**
 * Assistente específico do Certificado de Homologação Técnica.
 * O CHT homologa uma empresa/profissional e não depende de vistoria de embarcação.
 */
require_once __DIR__ . '/../../includes/emissao_certificados.php';

$erro = '';
$profissional_empresa = trim($_POST['profissional_empresa'] ?? '');
$cpf_cnpj = trim($_POST['cpf_cnpj'] ?? '');
$email_destinatario = trim($_POST['email_destinatario'] ?? '');
$atividade_homologada = trim($_POST['atividade_homologada'] ?? '');
$relatorio_homologacao_numero = trim($_POST['relatorio_homologacao_numero'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $relatorio_homologacao_numero !== '') {
    $stmtRelatorio = $pdo->prepare("SELECT id FROM vistorias WHERE numero = :numero ORDER BY criado_em DESC, id DESC LIMIT 1");
    $stmtRelatorio->execute([':numero' => $relatorio_homologacao_numero]);
    $vistoriaRelacionada = $stmtRelatorio->fetchColumn();
    if ($vistoriaRelacionada) {
        $liberacao = avaliarLiberacaoCertificacao($pdo, $vistoriaRelacionada);
        if (empty($liberacao['permitido'])) $erro = $liberacao['mensagem'];
    }
}
$data_validade = $_POST['data_validade'] ?? '';
$local_emissao = $_POST['local_emissao'] ?? 'Belém-PA';
$responsavel_id = $_POST['responsavel_id'] ?? '';
$cliente_id = $_POST['cliente_id'] ?? '';
$embarcacao_id = $_POST['embarcacao_id'] ?? '';

$stmtClientes = $pdo->query("SELECT id, nome, cpf_cnpj, email FROM clientes WHERE status = 'ATIVO' ORDER BY nome");
$clientes_lista = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

$stmtResponsaveis = $pdo->query("
    SELECT id, nome_completo as nome, cargo_titulo as cargo, registro_profissional
    FROM responsaveis_assinatura
    WHERE ativo = 1
    ORDER BY nome_completo
");
$responsaveis = $stmtResponsaveis->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } elseif ($profissional_empresa === '') {
        $erro = 'Informe o nome da empresa ou do profissional homologado.';
    } elseif ($cpf_cnpj === '') {
        $erro = 'Informe o CPF ou CNPJ da empresa/profissional.';
    } elseif ($email_destinatario === '' || !filter_var($email_destinatario, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido para o envio do certificado.';
    } elseif ($atividade_homologada === '') {
        $erro = 'Informe a atividade técnica homologada.';
    } elseif ($relatorio_homologacao_numero === '') {
        $erro = 'Informe o número do Relatório de Homologação Técnica.';
    } elseif ($data_validade === '') {
        $erro = 'Informe a validade da homologação.';
    } elseif ($local_emissao === '') {
        $erro = 'Selecione o local de emissão.';
    } elseif ($responsavel_id === '') {
        $erro = 'Selecione o responsável pela assinatura.';
    } else {
        $stmtResp = $pdo->prepare("
            SELECT nome_completo, cargo_titulo, registro_profissional
            FROM responsaveis_assinatura
            WHERE id = :id AND ativo = 1
        ");
        $stmtResp->execute([':id' => $responsavel_id]);
        $responsavel = $stmtResp->fetch(PDO::FETCH_ASSOC);

        if (!$responsavel) {
            $erro = 'Responsável pela assinatura inválido ou inativo.';
        } else {
            try {
                // Integridade relacional garantida no motor: :embarcacao_id, :cliente_id
                $dadosEmissao = [
                    'responsavel_assinatura_id' => $responsavel_id,
                    'profissional_empresa' => $profissional_empresa,
                    'cpf_cnpj' => $cpf_cnpj,
                    'email_destinatario' => $email_destinatario,
                    'atividade_homologada' => $atividade_homologada,
                    'relatorio_homologacao_numero' => $relatorio_homologacao_numero,
                    'data_validade' => $data_validade,
                    'local_emissao' => $local_emissao,
                    'cliente_id' => $cliente_id ?: null,
                    'embarcacao_id' => $embarcacao_id ?: null,
                    'observacoes' => trim($_POST['observacoes'] ?? ''),
                ];
                $resUnificado = emitirCertificadoUnificado($pdo, 'CHT', $dadosEmissao, (string)($_SESSION['usuario_id'] ?? ''));
                $numero_certificado = $resUnificado['numero'] ?? '';

                setMensagem('success', "Certificado CHT criado com sucesso! Número: {$numero_certificado}");
                redirecionar(APP_URL . 'documentacao/cht');
            } catch (Throwable $e) {
                $erro = 'Não foi possível gerar o CHT: ' . $e->getMessage();
            }
        }
    }
}

$titulo_page = 'Emitir Certificado de Homologação Técnica';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content" id="mainContent">
    <div class="page-header">
        <div>
            <h1 class="page-title">Emitir Certificado de Homologação Técnica</h1>
            <p class="page-subtitle">CHT · empresa ou profissional prestador de serviços</p>
        </div>
        <a href="<?= APP_URL ?>certificados" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar aos modelos
        </a>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><i class="fas fa-circle-xmark"></i> <?= h($erro) ?></div>
    <?php endif; ?>

    <div class="cert-workspace cert-workspace--wizard cert-workspace--wide">
        <aside class="cert-flow-sidebar">
            <div class="cert-flow-title">
                <i class="fas fa-route"></i>
                <div><strong>Etapas da emissão</strong><span>Homologação técnica</span></div>
            </div>
            <ol class="cert-step-list">
                <li class="is-done"><span><i class="fas fa-check"></i></span><div><strong>Modelo</strong><small>CHT</small></div></li>
                <li class="is-active"><span>02</span><div><strong>Dados da homologação</strong><small>Identificação e atividade.</small></div></li>
                <li><span>03</span><div><strong>Gerar documento</strong><small>Validade e assinatura.</small></div></li>
            </ol>
        </aside>

        <section class="cert-main-panel">
            <div class="cert-panel-header">
                <div>
                    <h2>1. Empresa ou profissional homologado</h2>
                    <p>Estes dados serão impressos no certificado oficial.</p>
                </div>
            </div>

            <form method="POST" class="cert-issue-form">
                <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">

                <div class="form-group" style="margin-bottom: 18px;">
                    <label for="cliente_select"><i class="fas fa-building"></i> Empresa ou Profissional Cadastrado (Entidade Principal)</label>
                    <select class="form-control" id="cliente_select" name="cliente_id" onchange="selecionarClienteCht(this)">
                        <option value="">-- Selecione do cadastro de clientes ou digite os dados avulsos abaixo --</option>
                        <?php foreach ($clientes_lista as $cli): ?>
                            <option value="<?= h($cli['id']) ?>"
                                    data-nome="<?= h($cli['nome']) ?>"
                                    data-cpf="<?= h($cli['cpf_cnpj']) ?>"
                                    data-email="<?= h($cli['email'] ?? '') ?>"
                                    <?= (string)$cliente_id === (string)$cli['id'] ? 'selected' : '' ?>>
                                <?= h($cli['nome']) ?> (<?= h($cli['cpf_cnpj']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">A seleção vincula o certificado à entidade original do sistema e pré-preenche nome, CPF/CNPJ e e-mail.</small>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="profissional_empresa">Empresa / profissional <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="profissional_empresa" name="profissional_empresa" value="<?= h($profissional_empresa) ?>" required>
                    </div>
                    <div class="form-group col-3">
                        <label for="cpf_cnpj">CPF / CNPJ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" value="<?= h($cpf_cnpj) ?>" required>
                    </div>
                    <div class="form-group col-3">
                        <label for="email_destinatario">E-mail <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email_destinatario" name="email_destinatario" value="<?= h($email_destinatario) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="atividade_homologada">Atividade técnica homologada <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="atividade_homologada" name="atividade_homologada" rows="3" required placeholder="Ex.: Medição de espessura (NORMAM 202/DPC)"><?= h($atividade_homologada) ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="relatorio_homologacao_numero">Relatório de Homologação Técnica <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="relatorio_homologacao_numero" name="relatorio_homologacao_numero" value="<?= h($relatorio_homologacao_numero) ?>" placeholder="Ex.: AM-REL-HT-101/26" required>
                    </div>
                    <div class="form-group col-3">
                        <label for="data_validade">Válido até <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="data_validade" name="data_validade" value="<?= h($data_validade) ?>" required>
                    </div>
                    <div class="form-group col-3">
                        <label for="local_emissao">Local de emissão <span class="text-danger">*</span></label>
                        <select class="form-control" id="local_emissao" name="local_emissao" required>
                            <?php foreach (['Belém-PA', 'Manaus-AM', 'Santarém-PA', 'Macapá-AP', 'Porto Velho-RO'] as $local): ?>
                                <option value="<?= h($local) ?>" <?= $local_emissao === $local ? 'selected' : '' ?>><?= h($local) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="observacoes">Observação adicional</label>
                    <textarea class="form-control" id="observacoes" name="observacoes" rows="2"><?= h($_POST['observacoes'] ?? '') ?></textarea>
                </div>

                <div class="cert-section-divider"></div>
                <div class="cert-panel-header cert-panel-header--compact">
                    <div><h2>2. Responsável pela emissão</h2><p>Selecione quem assinará o documento.</p></div>
                </div>

                <div class="form-group">
                    <label for="responsavel_id">Responsável pela assinatura <span class="text-danger">*</span></label>
                    <select class="form-control" id="responsavel_id" name="responsavel_id" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($responsaveis as $resp): ?>
                            <option value="<?= h($resp['id']) ?>" <?= (string)$responsavel_id === (string)$resp['id'] ? 'selected' : '' ?>>
                                <?= h($resp['nome']) ?> · <?= h($resp['cargo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="cert-action-bar">
                    <a href="<?= APP_URL ?>certificados" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
                    <button type="submit" class="btn btn-primary">Salvar e gerar CHT <i class="fas fa-file-pdf"></i></button>
                </div>
            </form>
        </section>

        <aside class="cert-help-panel">
            <strong>Como funciona</strong>
            <div class="cert-summary-list">
                <span><b>Modelo</b>CHT</span>
                <span><b>Origem</b>Homologação técnica</span>
                <span><b>Vistoria naval</b>Não se aplica</span>
            </div>
            <div class="cert-help-note">
                <i class="fas fa-circle-info"></i>
                <span>O número do certificado é gerado automaticamente; o relatório informado é a base técnica da homologação.</span>
            </div>
        </aside>
    </div>
</div>
<script>
function selecionarClienteCht(sel) {
    var opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        var nome = opt.getAttribute('data-nome') || '';
        var cpf = opt.getAttribute('data-cpf') || '';
        var email = opt.getAttribute('data-email') || '';
        if (nome) document.getElementById('profissional_empresa').value = nome;
        if (cpf) document.getElementById('cpf_cnpj').value = cpf;
        if (email) document.getElementById('email_destinatario').value = email;
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
