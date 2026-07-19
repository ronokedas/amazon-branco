<?php
/**
 * MÓDULO: Documentação > Certificados CHT
 * Formulário de Criação/Edição do Certificado de Homologação Técnica
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

verificar_sessao();
if (!podeAcessar('documentacao')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

$editando = false;
$certificado = null;

// Se tem ID, é edição
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $editando = true;

    $stmt = $pdo->prepare("SELECT * FROM certificados_cht WHERE id = :id AND ativo = 1");
    $stmt->execute([':id' => $id]);
    $certificado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$certificado) {
        setMensagem('error', 'Certificado não encontrado.');
        redirecionar(APP_URL . 'documentacao/cht');
    }
}

// Gerar próximo número (se não estiver editando)
$proximo_numero = '';
if (!$editando) {
    $ano = date('y');
    $ano4 = date('Y');
    $stmt_num = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_cht WHERE YEAR(criado_em) = :ano");
    $stmt_num->execute([':ano' => $ano4]);
    $total = $stmt_num->fetch()['total'];
    $seq = $total + 1;
    $proximo_numero = "AM-CHT-{$seq}/{$ano}";
}

// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---
$preenchimento = [
    'embarcacao_id'      => '',
    'nome_embarcacao'    => '',
    'numero_inscricao'   => '',
    'indicativo_chamada' => '',
    'tipo_embarcacao'    => '',
    'ano_construcao'     => '',
    'comprimento_total'  => '',
    'comprimento_casco'  => '',
    'boca_moldada'       => '',
    'pontal_moldado'     => '',
    'arqueacao_bruta'    => '',
    'material_casco'     => '',
    'relatorio_numero'   => '',
    'proprietario'       => ''
];
$dadosPre = null;
if (!$editando && !empty($_GET['agendamento_id'])) {
    $stmtPre = $pdo->prepare("
        SELECT 
            e.id as embarcacao_id, e.nome as emb_nome, e.registro, e.indicativo_chamada, e.tipo_embarcacao, e.ano as emb_ano,
            e.comprimento_total, e.comprimento_casco, e.boca_moldada, e.pontal_moldado, 
            e.arqueacao_bruta, e.material_casco, e.observacoes as atividades, e.proprietario,
            v.numero as relatorio_numero
        FROM agendamentos a
        JOIN embarcacoes e ON a.embarcacao_id = e.id
        LEFT JOIN vistorias v ON v.id = (SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC, v2.id DESC LIMIT 1)
        WHERE a.id = :aid
    ");
    $stmtPre->execute([':aid' => $_GET['agendamento_id']]);
    $dadosPre = $stmtPre->fetch(PDO::FETCH_ASSOC);

    if ($dadosPre) {
        $preenchimento['embarcacao_id']      = h($dadosPre['embarcacao_id'] ?? '');
        $preenchimento['nome_embarcacao']    = h($dadosPre['emb_nome'] ?? '');
        $preenchimento['numero_inscricao']   = h($dadosPre['registro'] ?? '');
        $preenchimento['indicativo_chamada'] = h($dadosPre['indicativo_chamada'] ?? '');
        $preenchimento['tipo_embarcacao']    = h($dadosPre['tipo_embarcacao'] ?? '');
        $preenchimento['ano_construcao']     = h($dadosPre['emb_ano'] ?? '');
        $preenchimento['comprimento_total']  = h($dadosPre['comprimento_total'] ?? '');
        $preenchimento['comprimento_casco']  = h($dadosPre['comprimento_casco'] ?? '');
        $preenchimento['boca_moldada']       = h($dadosPre['boca_moldada'] ?? '');
        $preenchimento['pontal_moldado']     = h($dadosPre['pontal_moldado'] ?? '');
        $preenchimento['arqueacao_bruta']    = h($dadosPre['arqueacao_bruta'] ?? '');
        $preenchimento['material_casco']     = h($dadosPre['material_casco'] ?? '');
        $preenchimento['relatorio_numero']   = h($dadosPre['relatorio_numero'] ?? '');
        $preenchimento['proprietario']       = h($dadosPre['proprietario'] ?? '');
    }
}


// Buscar lista de despachantes ativos
$stmt_desp = $pdo->prepare("SELECT id, nome FROM clientes WHERE perfil = 'despachante' AND status = 'ATIVO' ORDER BY nome");
$stmt_desp->execute();
$despachantes_list = $stmt_desp->fetchAll(PDO::FETCH_ASSOC);

$titulo_page = ($editando ? 'Editar' : 'Novo') . ' Certificado CHT - ' . APP_NAME;
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-header">
        <h2>
            <i class="fas fa-file-certificate"></i> 
            <?php echo $editando ? 'Editar Certificado CHT' : 'Novo Certificado CHT'; ?>
        </h2>
        <a href="<?php echo APP_URL; ?>documentacao/cht" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Se já estiver assinado, mostrar aviso -->
    <?php if ($editando && $certificado['assinado']): ?>
        <div class="card mb-3" style="border-left: 4px solid var(--cor-destaque);">
            <div class="card-body">
                <p style="margin:0;">
                    <i class="fas fa-lock" style="color: var(--cor-destaque);"></i>
                    <strong>Este certificado já foi assinado digitalmente.</strong><br>
                    Assinado por: <?php echo h($certificado['assinante_nome']); ?> em 
                    <?php echo formatarDataCompleta($certificado['assinatura_em']); ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo APP_URL; ?>documentacao/cht/actions" id="formCertificado">
        <input type="hidden" name="action" value="salvar">
        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?php echo h($certificado['id']); ?>">
        <?php endif; ?>

        <!-- SEÇÃO 1: Identificação -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-id-card"></i> Identificação do Certificado</h3>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Número do Certificado</label>
                        <input type="text" class="form-control" value="<?php echo $editando ? h($certificado['numero_certificado'] ?: $certificado['numero_relatorio_ht']) : h($proximo_numero); ?>" readonly 
                               style="background: var(--cor-sidebar); font-weight: bold;">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <?php
                            $statuses = ['rascunho' => 'Rascunho', 'emitido' => 'Emitido', 'cancelado' => 'Cancelado'];
                            $current_status = $editando ? $certificado['status'] : 'rascunho';
                            foreach ($statuses as $val => $label):
                            ?>
                                <option value="<?php echo $val; ?>" <?php echo $current_status === $val ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label for="data_emissao">Data de Emissão</label>
                        <input type="date" name="data_emissao" id="data_emissao" class="form-control" required
                               value="<?php echo $editando ? h($certificado['data_emissao']) : date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="data_validade">Válido até *</label>
                        <input type="date" name="data_validade" id="data_validade" class="form-control" required
                               value="<?php echo $editando ? h($certificado['data_validade']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="local_emissao">Local de Emissão *</label>
                        <select name="local_emissao" id="local_emissao" class="form-control" required>
                            <?php $local_atual = $editando ? ($certificado['local_emissao'] ?? '') : 'Belém-PA'; ?>
                            <?php foreach (['Belém-PA', 'Manaus-AM', 'Santarém-PA', 'Macapá-AP', 'Porto Velho-RO'] as $local): ?>
                                <option value="<?php echo h($local); ?>" <?php echo $local_atual === $local ? 'selected' : ''; ?>><?php echo h($local); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 2: Dados do Profissional/Empresa -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-user-tie"></i> Profissional / Empresa</h3>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label for="profissional_empresa">Nome / Razão Social *</label>
                        <input type="text" name="profissional_empresa" id="profissional_empresa" class="form-control" required
                               value="<?php echo $editando ? h($certificado['profissional_empresa']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="cpf_cnpj">CPF / CNPJ *</label>
                        <input type="text" name="cpf_cnpj" id="cpf_cnpj" class="form-control" required
                               value="<?php echo $editando ? h($certificado['cpf_cnpj']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="email_destinatario">E-mail para envio *</label>
                    <input type="email" name="email_destinatario" id="email_destinatario" class="form-control" required
                           value="<?php echo $editando ? h($certificado['email_destinatario']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="atividade_homologada">Atividade Homologada *</label>
                    <textarea name="atividade_homologada" id="atividade_homologada" class="form-control" rows="3" required
                              placeholder="Ex.: Medição de espessura (NORMAM 202/DPC)"><?php echo $editando ? h($certificado['atividade_homologada']) : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label for="relatorio_homologacao_numero">Relatório de Homologação Técnica *</label>
                    <input type="text" name="relatorio_homologacao_numero" id="relatorio_homologacao_numero" class="form-control" required
                           placeholder="Ex.: AM-REL-HT-101/26"
                           value="<?php echo $editando ? h($certificado['relatorio_homologacao_numero'] ?: $certificado['numero_relatorio_ht']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea name="observacoes" id="observacoes" class="form-control" rows="4"
                              placeholder="Observações adicionais..."><?php echo $editando ? h($certificado['observacoes']) : ''; ?></textarea>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 3: Responsável pela Assinatura -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-user-tie"></i> Responsável pela Assinatura</h3>
            </div>
            <div class="card-body">
                <div class="grid-3">
                    <div class="form-group">
                        <label for="assinante_nome">Nome Completo</label>
                        <input type="text" name="assinante_nome" id="assinante_nome" class="form-control"
                               value="<?php echo $editando ? h($certificado['assinante_nome']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="assinante_titulo">Título/Cargo</label>
                        <input type="text" name="assinante_titulo" id="assinante_titulo" class="form-control"
                               placeholder="Ex: Engenheira Naval"
                               value="<?php echo $editando ? h($certificado['assinante_titulo']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="assinante_registro">Registro Profissional</label>
                        <input type="text" name="assinante_registro" id="assinante_registro" class="form-control"
                               placeholder="Ex: CREA: 22.482"
                               value="<?php echo $editando ? h($certificado['assinante_registro']) : ''; ?>">
                    </div>
                </div>
            </div>
        </div>

        
        <!-- SEÇÃO: Despachante -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-briefcase"></i> Responsável pelo Trâmite (Despachante) - Opcional</h3>
            </div>
            <div class="card-body">
                <div class="form-group col-md-6">
                    <label for="despachante_id">Despachante</label>
                    <select name="despachante_id" id="despachante_id" class="form-control">
                        <option value="">-- Sem Despachante / Não se aplica --</option>
                        <?php foreach ($despachantes_list as $desp): ?>
                            <option value="<?php echo h($desp['id']); ?>" <?php echo ($editando && ($certificado['despachante_id'] ?? '') === $desp['id']) ? 'selected' : ''; ?>>
                                <?php echo h($desp['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Selecione quem será responsável por protocolar ou retirar este certificado junto ao órgão.</small>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="card mb-3">
            <div class="card-footer" style="display: flex; gap: 10px; justify-content: flex-end;">
                <a href="<?php echo APP_URL; ?>documentacao/cht" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> <?php echo $editando ? 'Atualizar Certificado' : 'Salvar Certificado'; ?>
                </button>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
