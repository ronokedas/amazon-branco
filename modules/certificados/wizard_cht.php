<?php
/**
 * Assistente específico do Certificado de Homologação Técnica.
 * O CHT homologa uma empresa/profissional e não depende de vistoria de embarcação.
 */

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
                $pdo->beginTransaction();
                $id = gerarUUID();
                $numero_certificado = gerarNumeroDocumento('CHT', 'AM-CHT');
                $token = bin2hex(random_bytes(32));

                $stmt = $pdo->prepare("
                    INSERT INTO certificados_cht (
                        id, numero_certificado, numero_relatorio_ht, token_assinatura,
                        profissional_empresa, cpf_cnpj, email_destinatario, atividade_homologada,
                        relatorio_homologacao_numero, observacoes,
                        data_emissao, data_validade, local_emissao,
                        assinante_nome, assinante_titulo, assinante_registro,
                        status, ativo, criado_por
                    ) VALUES (
                        :id, :numero_certificado, :numero_relatorio_ht, :token_assinatura,
                        :profissional_empresa, :cpf_cnpj, :email_destinatario, :atividade_homologada,
                        :relatorio_homologacao_numero, :observacoes,
                        :data_emissao, :data_validade, :local_emissao,
                        :assinante_nome, :assinante_titulo, :assinante_registro,
                        'emitido', 1, :criado_por
                    )
                ");
                $stmt->execute([
                    ':id' => $id,
                    ':numero_certificado' => $numero_certificado,
                    ':numero_relatorio_ht' => $relatorio_homologacao_numero,
                    ':token_assinatura' => $token,
                    ':profissional_empresa' => $profissional_empresa,
                    ':cpf_cnpj' => $cpf_cnpj,
                    ':email_destinatario' => $email_destinatario,
                    ':atividade_homologada' => $atividade_homologada,
                    ':relatorio_homologacao_numero' => $relatorio_homologacao_numero,
                    ':observacoes' => trim($_POST['observacoes'] ?? ''),
                    ':data_emissao' => date('Y-m-d'),
                    ':data_validade' => $data_validade,
                    ':local_emissao' => $local_emissao,
                    ':assinante_nome' => $responsavel['nome_completo'],
                    ':assinante_titulo' => $responsavel['cargo_titulo'],
                    ':assinante_registro' => $responsavel['registro_profissional'],
                    ':criado_por' => $_SESSION['usuario_id'] ?? null,
                ]);

                $pdo->prepare('UPDATE certificados_cht SET responsavel_assinatura_id = ? WHERE id = ?')->execute([(int)$responsavel_id, $id]);
                $pdo->commit();
                log_atividade('certificado_cht_criado', "{$numero_certificado} - {$profissional_empresa}");
                setMensagem('success', "Certificado CHT criado com sucesso! Número: {$numero_certificado}");
                redirecionar(APP_URL . 'documentacao/cht');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
