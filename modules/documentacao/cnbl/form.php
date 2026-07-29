<?php
/**
 * MÓDULO: Documentação > Certificados CNBL
 * Formulário de Criação/Edição do Certificado de Borda Livre
 * Dados da embarcação puxados automaticamente do cadastro
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

// Verificar permissão
verificar_sessao();
if (!podeAcessar('documentacao')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

$editando = false;
$certificado = null;
$convalidacoes = [];
$tipo_embarcacao_convalidacoes = '';

// Se tem ID, é edição
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $editando = true;

    $stmt = $pdo->prepare("SELECT * FROM certificados_cnbl WHERE id = :id AND ativo = 1");
    $stmt->execute([':id' => $id]);
    $certificado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$certificado) {
        setMensagem('error', 'Certificado não encontrado.');
        redirecionar(APP_URL . 'documentacao/cnbl');
    }

    // Buscar convalidações
    $stmt_conv = $pdo->prepare("SELECT * FROM cert_convalidacoes WHERE certificado_id = :cert_id AND tipo_certificado = 'CNBL' ORDER BY id");
    $stmt_conv->execute([':cert_id' => $id]);
    $convalidacoes = $stmt_conv->fetchAll(PDO::FETCH_ASSOC);

    $tipo_embarcacao_convalidacoes = $certificado['tipo_embarcacao'] ?? '';
    if (!empty($certificado['vistoria_id'])) {
        $stmt_tipo_conv = $pdo->prepare("
            SELECT COALESCE(te.nome, e.tipo_embarcacao) AS tipo_embarcacao_real
            FROM vistorias v
            JOIN agendamentos a ON v.agendamento_id = a.id
            JOIN embarcacoes e ON a.embarcacao_id = e.id
            LEFT JOIN tipos_embarcacao te ON e.tipo_embarcacao_id = te.id
            WHERE v.id = :vistoria_id
            LIMIT 1
        ");
        $stmt_tipo_conv->execute([':vistoria_id' => $certificado['vistoria_id']]);
        $tipo_embarcacao_real = $stmt_tipo_conv->fetchColumn();
        if (!empty($tipo_embarcacao_real)) {
            $tipo_embarcacao_convalidacoes = $tipo_embarcacao_real;
        }
    }
}

if (!$editando) {
    header('Location: ' . APP_URL . 'certificados');
    exit;
}

// Gerar próximo número (se não estiver editando)
$proximo_numero = '';
if (!$editando) {
    $ano = date('y');
    $ano4 = date('Y');
    $stmt_num = $pdo->prepare('SELECT COUNT(*) as total FROM certificados_cnbl WHERE YEAR(criado_em) = :ano');
    $stmt_num->execute([':ano' => $ano4]);
    $total = $stmt_num->fetch()['total'];
    $seq = $total + 1;
    $proximo_numero = "AM-CNBL-{$seq}/{$ano}";
}

// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---
$preenchimento = [
    'embarcacao_id'      => '',
    'nome_embarcacao'    => '',
    'numero_inscricao'   => '',
    'indicativo_chamada' => '',
    'atividades_servicos'=> '',
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
        $preenchimento['atividades_servicos']= h($dadosPre['atividades'] ?? '');
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
}// Buscar lista de embarcações ativas para o select
$stmt_emb = $pdo->prepare("SELECT id, nome, tipo, registro
                           FROM embarcacoes WHERE ativo = 1 ORDER BY nome");
$stmt_emb->execute();
$embarcacoes = $stmt_emb->fetchAll(PDO::FETCH_ASSOC);


// Buscar lista de despachantes ativos
$stmt_desp = $pdo->prepare("SELECT id, nome FROM clientes WHERE perfil = 'despachante' AND status = 'ATIVO' ORDER BY nome");
$stmt_desp->execute();
$despachantes_list = $stmt_desp->fetchAll(PDO::FETCH_ASSOC);

$documento_bloqueado = documentoEstaAssinado($pdo, 'certificados_cnbl', $certificado['id'] ?? null);
$vistoriadores_convalidacao = certificadoVistoriadoresAtivos($pdo);
$locais_convalidacao = certificadoLocaisVistoria($pdo);
$convalidacoes = certificadoAplicarUltimaVistoria(
    $convalidacoes,
    certificadoUltimaVistoriaParaConvalidacao($pdo, $certificado['vistoria_id'] ?? null)
);

$valorCampoCnbl = function (string $campo, string $padrao = '') use ($editando, $certificado, $preenchimento): string {
    if ($editando && isset($certificado[$campo]) && $certificado[$campo] !== null && $certificado[$campo] !== '') {
        return (string)$certificado[$campo];
    }
    if (!$editando && isset($preenchimento[$campo]) && $preenchimento[$campo] !== null && $preenchimento[$campo] !== '') {
        return (string)$preenchimento[$campo];
    }
    return $padrao;
};

$normalizarOpcaoCnbl = static function (string $valor): string {
    $valor = trim($valor);
    $valor = str_replace(['Á', 'À', 'Â', 'Ã', 'Ä', 'á', 'à', 'â', 'ã', 'ä'], 'A', $valor);
    $valor = str_replace(['É', 'È', 'Ê', 'Ë', 'é', 'è', 'ê', 'ë'], 'E', $valor);
    $valor = str_replace(['Í', 'Ì', 'Î', 'Ï', 'í', 'ì', 'î', 'ï'], 'I', $valor);
    $valor = str_replace(['Ó', 'Ò', 'Ô', 'Õ', 'Ö', 'ó', 'ò', 'ô', 'õ', 'ö'], 'O', $valor);
    $valor = str_replace(['Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'û', 'ü'], 'U', $valor);
    $valor = str_replace(['Ç', 'ç'], 'C', $valor);
    return mb_strtoupper($valor, 'UTF-8');
};

$optionSelectedCnbl = static function (string $atual, string $opcao) use ($normalizarOpcaoCnbl): string {
    return $normalizarOpcaoCnbl($atual) === $normalizarOpcaoCnbl($opcao) ? 'selected' : '';
};

$valorAtividadeCnbl = $valorCampoCnbl('atividades_servicos');
$valorTipoEmbarcacaoCnbl = $valorCampoCnbl('tipo_embarcacao');
$valorPortoInscricaoCnbl = $valorCampoCnbl('porto_inscricao');
$valorTipoNavegacaoCnbl = $valorCampoCnbl('tipo_navegacao');
$valorAreaNavegacaoCnbl = $valorCampoCnbl('area_navegacao');

$titulo_page = ($editando ? 'Editar' : 'Novo') . ' Certificado CNBL - ' . APP_NAME;
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-header">
        <h2>
            <i class="fas fa-file-certificate"></i> 
            <?php echo $editando ? 'Editar Certificado CNBL' : 'Novo Certificado CNBL'; ?>
        </h2>
        <a href="<?php echo APP_URL; ?>documentacao/cnbl" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Sistema de Abas -->
    <div class="card mb-3">
        <div class="card-body" style="padding: 10px;">
            <ul class="nav nav-tabs" id="formTabs" style="border-bottom: 2px solid #0891b2;">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#tab-selecao" style="color: #0891b2; font-weight: 500;">
                        <i class="fas fa-ship"></i> 1. Seleção da Embarcação
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-dados" style="color: #6b7280; font-weight: 500;">
                        <i class="fas fa-info-circle"></i> 2. Dados da Embarcação
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-medicoes" style="color: #6b7280; font-weight: 500;">
                        <i class="fas fa-ruler-combined"></i> 3. Medições Técnicas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-convalidacoes" style="color: #6b7280; font-weight: 500;">
                        <i class="fas fa-calendar-check"></i> 4. Convalidações
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="tab-content">

    <!-- Se já estiver assinado, mostrar aviso -->
    <?php if ($documento_bloqueado): ?>
        <div class="card mb-3" style="border-left: 4px solid var(--cor-destaque);">
            <div class="card-body">
                <p style="margin:0;">
                    <i class="fas fa-lock" style="color: var(--cor-destaque);"></i>
                    <strong>Este certificado foi aprovado/assinado e seus dados estão bloqueados.</strong><br>
                    Somente Lugar, data da realização e Vistoriador das Convalidações podem ser atualizados.<br>
                    Assinado por: <?php echo h($certificado['assinante_nome']); ?> em 
                    <?php echo formatarDataCompleta($certificado['assinatura_em']); ?> 
                    (IP: <?php echo h($certificado['assinatura_ip']); ?>)
                </p>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo APP_URL; ?>documentacao/cnbl/actions" id="formCertificado">
        <input type="hidden" name="action" value="<?php echo $documento_bloqueado ? 'atualizar_convalidacoes' : 'salvar'; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?php echo h($certificado['id']); ?>">
            <input type="hidden" name="vistoria_id" value="<?php echo h($certificado['vistoria_id'] ?? ''); ?>">
            <input type="hidden" name="despachante_id" value="<?php echo h($certificado['despachante_id'] ?? ''); ?>">
        <?php endif; ?>

        <!-- SEÇÃO 1: Identificação do Certificado -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-id-card"></i> Identificação do Certificado</h3>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Número do Certificado</label>
                        <input type="text" class="form-control" value="<?php echo $editando ? h($certificado['numero']) : h($proximo_numero); ?>" readonly 
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
                        <label for="data_validade">Data de Validade</label>
                        <input type="date" name="data_validade" id="data_validade" class="form-control" required
                               value="<?php echo $editando ? h($certificado['data_validade']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="local_emissao">Local de Emissão</label>
                        <input type="text" name="local_emissao" id="local_emissao" class="form-control"
                               value="<?php echo $editando ? h($certificado['local_emissao']) : 'Belém-PA'; ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- ABA 1: Seleção da Embarcação -->
        <div class="tab-pane fade show active" id="tab-selecao">
            <div class="card mb-3">
                <div class="card-header">
                    <h3><i class="fas fa-search"></i> Selecionar Embarcação</h3>
                </div>
                <div class="card-body">
                    <?php if (!$editando): ?>
                    <div class="form-group">
                        <label for="embarcacao_id"><i class="fas fa-ship"></i> Escolha a Embarcação no Cadastro</label>
                        <select id="embarcacao_id" class="form-control" onchange="carregarDadosEmbarcacao(this.value)" style="font-size: 16px; padding: 12px;">
                            <option value="">-- Selecione uma embarcação --</option>
                            <?php foreach ($embarcacoes as $emb): ?>
                                <option value="<?php echo h($emb['id']); ?>"
                                        data-numero_inscricao="<?php echo h($emb['numero_inscricao'] ?? ''); ?>"
                                        data-indicativo_chamada="<?php echo h($emb['indicativo_chamada'] ?? ''); ?>"
                                        data-comprimento_total="<?php echo h($emb['comprimento_total'] ?? ''); ?>"
                                        data-comprimento_casco="<?php echo h($emb['comprimento_casco'] ?? ''); ?>"
                                        <?php echo (!empty($_GET['agendamento_id']) && isset($dadosPre['embarcacao_id']) && $dadosPre['embarcacao_id'] == $emb['id']) ? 'selected' : ''; ?>
                                        data-boca_moldada="<?php echo h($emb['boca_moldada'] ?? ''); ?>"
                                        data-pontal_moldado="<?php echo h($emb['pontal_moldado'] ?? ''); ?>"
                                        data-arqueacao_bruta="<?php echo h($emb['arqueacao_bruta'] ?? ''); ?>"
                                        data-material_casco="<?php echo h($emb['material_casco'] ?? ''); ?>"
                                    data-nome="<?php echo h($emb['nome']); ?>"
                                    data-tipo="<?php echo h($emb['tipo']); ?>">
                                    <?php echo h($emb['nome']) . ' (' . h($emb['tipo']) . ' - ' . h($emb['registro']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" style="font-size: 13px;">
                            <i class="fas fa-info-circle"></i> Ao selecionar, os dados da embarcação serão preenchidos automaticamente.
                        </small>
                    </div>
                    <hr>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- ABA 2: Dados da Embarcação -->
        <div class="tab-pane fade" id="tab-dados">
            <div class="card mb-3">
                <div class="card-header">
                    <h3><i class="fas fa-ship"></i> Dados da Embarcação</h3>
                </div>
                <div class="card-body">

                    <div class="grid-3">
                        <div class="form-group">
                            <label for="nome_embarcacao">Nome da Embarcação *</label>
                            <input type="text" name="nome_embarcacao" id="nome_embarcacao" class="form-control" required
                                   value="<?php echo $editando ? h($certificado['nome_embarcacao']) : h($preenchimento['nome_embarcacao']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="numero_inscricao">N° de Inscrição</label>
                            <input type="text" name="numero_inscricao" id="numero_inscricao" class="form-control"
                                   value="<?php echo $editando ? h($certificado['numero_inscricao']) : h($preenchimento['numero_inscricao']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="indicativo_chamada">Indicativo de Chamada</label>
                            <input type="text" name="indicativo_chamada" id="indicativo_chamada" class="form-control"
                                   value="<?php echo $editando ? h($certificado['indicativo_chamada']) : h($preenchimento['indicativo_chamada']); ?>">
                        </div>
                    </div>
                    <div class="grid-3">
                        <div class="form-group">
                            <label for="atividades_servicos">Atividade/Serviço <span style="color: #dc2626;">*</span></label>
                            <select name="atividades_servicos" id="atividade_servico" class="form-control" required>
                                <option value="">-- Selecione --</option>
                                <?php
                                $atividadesCnbl = ['Carga Geral', 'Carga Geral sobre o Convés', 'Passageiros', 'Transporte de Passageiros', 'Transporte de Carga', 'Pesca', 'Reboque', 'Tanque', 'Granel', 'Apoio Portuário', 'Outro'];
                                if ($valorAtividadeCnbl !== '' && !in_array($valorAtividadeCnbl, $atividadesCnbl, true)) {
                                    array_unshift($atividadesCnbl, $valorAtividadeCnbl);
                                }
                                foreach (array_unique($atividadesCnbl) as $atividadeCnbl):
                                ?>
                                    <option value="<?php echo h($atividadeCnbl); ?>" <?php echo $optionSelectedCnbl($valorAtividadeCnbl, $atividadeCnbl); ?>>
                                        <?php echo h($atividadeCnbl); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="tipo_embarcacao">Tipo de Embarcação <span style="color: #dc2626;">*</span></label>
                            <select name="tipo_embarcacao" id="tipo_embarcacao" class="form-control" required>
                                <option value="">-- Selecione --</option>
                                <?php
                                $tiposEmbarcacaoCnbl = [
                                    'A' => 'A - Carga Geral',
                                    'B' => 'B - Tanque',
                                    'C' => 'C - Granel',
                                    'D' => 'D - Passageiros',
                                    'E' => 'E - Empurrador/Empurrado',
                                ];
                                if ($valorTipoEmbarcacaoCnbl !== '' && !array_key_exists($valorTipoEmbarcacaoCnbl, $tiposEmbarcacaoCnbl)) {
                                    echo '<option value="' . h($valorTipoEmbarcacaoCnbl) . '" selected>' . h($valorTipoEmbarcacaoCnbl) . '</option>';
                                }
                                foreach ($tiposEmbarcacaoCnbl as $tipoValorCnbl => $tipoLabelCnbl):
                                ?>
                                    <option value="<?php echo h($tipoValorCnbl); ?>" <?php echo $optionSelectedCnbl($valorTipoEmbarcacaoCnbl, $tipoValorCnbl); ?>>
                                        <?php echo h($tipoLabelCnbl); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ano_construcao">Ano de Construção</label>
                            <input type="text" name="ano_construcao" id="ano_construcao" class="form-control"
                                   maxlength="4" placeholder="AAAA"
                                   value="<?php echo $editando ? h($certificado['ano_construcao']) : h($preenchimento['ano_construcao']); ?>">
                        </div>
                    </div>

                    <div class="grid-3">
                        <div class="form-group">
                            <label for="numero_inscricao">Número de Inscrição</label>
                            <input type="text" name="numero_inscricao" id="numero_inscricao" class="form-control"
                                   value="<?php echo $editando ? h($certificado['numero_inscricao']) : h($preenchimento['numero_inscricao']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="porto_inscricao">Porto de Inscrição <span style="color: #dc2626;">*</span></label>
                            <select name="porto_inscricao" id="porto_inscricao" class="form-control" required>
                                <option value="">-- Selecione --</option>
                                <?php
                                $portosCnbl = ['Belém/PA', 'Belém - PA', 'Manaus/AM', 'Manaus - AM', 'Porto Velho/RO', 'Porto Velho - RO', 'Santarém - PA'];
                                if ($valorPortoInscricaoCnbl !== '' && !in_array($valorPortoInscricaoCnbl, $portosCnbl, true)) {
                                    array_unshift($portosCnbl, $valorPortoInscricaoCnbl);
                                }
                                foreach (array_unique($portosCnbl) as $portoCnbl):
                                ?>
                                    <option value="<?php echo h($portoCnbl); ?>" <?php echo $optionSelectedCnbl($valorPortoInscricaoCnbl, $portoCnbl); ?>>
                                        <?php echo h($portoCnbl); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="arqueacao_bruta">Arqueação Bruta <span style="color: #dc2626;">*</span></label>
                            <input type="number" name="arqueacao_bruta" id="arqueacao_bruta" class="form-control" required min="0" step="0.01"
                                   value="<?php echo $editando ? h($certificado['arqueacao_bruta']) : h($preenchimento['arqueacao_bruta']); ?>">
                        </div>
                    </div>

                    <div class="grid-4">
                        <div class="form-group">
                            <label for="comprimento_total">Comprimento Total (m)</label>
                            <input type="number" name="comprimento_total" id="comprimento_total" class="form-control" step="0.01"
                                   value="<?php echo $editando ? h($certificado['comprimento_total']) : h($preenchimento['comprimento_total']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="comprimento_casco">Comprimento do Casco (m)</label>
                            <input type="number" name="comprimento_casco" id="comprimento_casco" class="form-control" step="0.01"
                                   value="<?php echo $editando ? h($certificado['comprimento_casco']) : h($preenchimento['comprimento_casco']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="boca_moldada">Boca Moldada (m)</label>
                            <input type="number" name="boca_moldada" id="boca_moldada" class="form-control" step="0.01"
                                   value="<?php echo $editando ? h($certificado['boca_moldada']) : h($preenchimento['boca_moldada']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="pontal_moldado">Pontal Moldado (m)</label>
                            <input type="number" name="pontal_moldado" id="pontal_moldado" class="form-control" step="0.01"
                                   value="<?php echo $editando ? h($certificado['pontal_moldado']) : h($preenchimento['pontal_moldado']); ?>">
                        </div>
                    </div>

                    <div class="grid-3">
                        <div class="form-group">
                            <label for="material_casco">Material do Casco</label>
                            <input type="text" name="material_casco" id="material_casco" class="form-control"
                                   value="<?php echo $editando ? h($certificado['material_casco']) : h($preenchimento['material_casco']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="ano_construcao" style="visibility:hidden;">.</label>
                            <input type="text" style="visibility:hidden;">
                        </div>
                    </div>

                    <!-- Tipo de Navegação (checkboxes) -->
                    <div class="form-group">
                        <label>Tipo de Navegação</label>
                        <div class="d-flex gap-2" style="flex-wrap: wrap;">
                            <?php
                            $tipos_nav = ['MAR ABERTO', 'INTERIOR', 'APOIO PORTUÁRIO'];
                            $selected_tipos = array_map($normalizarOpcaoCnbl, array_filter(array_map('trim', explode(',', $valorTipoNavegacaoCnbl))));
                            foreach ($tipos_nav as $tn):
                            ?>
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input type="checkbox" name="tipo_navegacao[]" value="<?php echo $tn; ?>"
                                           <?php echo in_array($normalizarOpcaoCnbl($tn), $selected_tipos, true) ? 'checked' : ''; ?>>
                                    <?php echo $tn; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Área de Navegação Interior -->
                    <div class="form-group">
                        <label>Área de Navegação Interior <span style="color: #dc2626;">*</span></label>
                        <div class="d-flex gap-3" style="flex-wrap: wrap; margin-top: 8px;">
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 10px 20px; border: 2px solid #0891b2; border-radius: 6px; background: white; font-weight: 500;">
                                <input type="radio" name="area_navegacao" value="Área 1" required
                                       <?php echo $normalizarOpcaoCnbl($valorAreaNavegacaoCnbl) === $normalizarOpcaoCnbl('Área 1') ? 'checked' : ''; ?>>
                                <strong>Área 1</strong>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 10px 20px; border: 2px solid #d1d5db; border-radius: 6px; background: white; font-weight: 500;">
                                <input type="radio" name="area_navegacao" value="Área 2"
                                       <?php echo $normalizarOpcaoCnbl($valorAreaNavegacaoCnbl) === $normalizarOpcaoCnbl('Área 2') ? 'checked' : ''; ?>>
                                <strong>Área 2</strong>
                            </label>
                            <?php if ($valorAreaNavegacaoCnbl !== '' && !in_array($normalizarOpcaoCnbl($valorAreaNavegacaoCnbl), [$normalizarOpcaoCnbl('Área 1'), $normalizarOpcaoCnbl('Área 2')], true)): ?>
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 10px 20px; border: 2px solid #10b981; border-radius: 6px; background: rgba(16,185,129,.08); font-weight: 500;">
                                    <input type="radio" name="area_navegacao" value="<?php echo h($valorAreaNavegacaoCnbl); ?>" checked>
                                    <strong><?php echo h($valorAreaNavegacaoCnbl); ?></strong>
                                </label>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ABA 3: Medições Técnicas -->
        <div class="tab-pane fade" id="tab-medicoes">
            <div class="card mb-3">
                <div class="card-header">
                    <h3><i class="fas fa-ruler-combined"></i> Medições Técnicas de Borda Livre</h3>
                </div>
                <div class="card-body">

                    <div class="grid-3">
                        <div class="form-group">
                            <label for="centro_disco_situado">Distância até Centro do Disco (mm) <span style="color: #dc2626;">*</span></label>
                            <input type="text" inputmode="numeric" name="centro_disco_situado" id="dist_centro_disco" class="form-control" required
                                   value="<?php echo h($valorCampoCnbl('centro_disco_situado', '476')); ?>">
                        </div>
                        <div class="form-group">
                            <label for="marca_linha_carga_area1">Distância até Marca Linha Área 1 (mm) <span style="color: #dc2626;">*</span></label>
                            <input type="text" inputmode="numeric" name="marca_linha_carga_area1" id="marca_linha_area1" class="form-control" required
                                   value="<?php echo h($valorCampoCnbl('marca_linha_carga_area1', '0')); ?>">
                        </div>
                        <div class="form-group">
                            <label for="marca_linha_carga_area2">Distância até Marca Linha Área 2 (mm) <span style="color: #dc2626;">*</span></label>
                            <input type="text" inputmode="numeric" name="marca_linha_carga_area2" id="marca_linha_area2" class="form-control" required
                                   value="<?php echo h($valorCampoCnbl('marca_linha_carga_area2', '476')); ?>">
                        </div>
                    </div>
                    <div class="grid-3">
                        <div class="form-group">
                            <label for="aresta_superior_linha_conves">Aresta Superior da Linha do Convés (mm) <span style="color: #dc2626;">*</span></label>
                            <input type="text" inputmode="numeric" name="aresta_superior_linha_conves" id="aresta_conves" class="form-control" required
                                   value="<?php echo h($valorCampoCnbl('aresta_superior_linha_conves', '0')); ?>">
                        </div>
                        <div class="form-group">
                            <label for="dist_linha_conves_bico_proa">Centro do Disco até Bico de Proa (mm) <span style="color: #dc2626;">*</span></label>
                            <input type="text" inputmode="numeric" name="dist_linha_conves_bico_proa" id="dist_bico_proa" class="form-control" required
                                   value="<?php echo h($valorCampoCnbl('dist_linha_conves_bico_proa', '25440')); ?>">
                        </div>
                        <div class="form-group">
                            <label for="acrescimo_agua_salgada">Acréscimo para Água Salgada (mm) <span style="color: #dc2626;">*</span></label>
                            <input type="text" inputmode="numeric" name="acrescimo_agua_salgada" id="acrescimo_agua_salgada" class="form-control" required
                                   value="<?php echo h($valorCampoCnbl('acrescimo_agua_salgada', '0')); ?>">
                        </div>
                    </div>
                    <div class="grid-3">
                        <div class="form-group">
                            <label for="borda_livre_mm">Número da Linha de Verão (Borda Livre) <span style="color: #dc2626;">*</span></label>
                            <input type="number" name="borda_livre_mm" id="borda_livre_mm" class="form-control" required min="0" step="0.1"
                                   value="<?php echo h($valorCampoCnbl('borda_livre_mm', '2')); ?>">
                            <small class="text-muted">Número que aparece dentro do círculo no desenho do Disco de Plimsoll</small>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ABA 4: Convalidações e Finalização -->
        <div class="tab-pane fade" id="tab-convalidacoes">
            <div class="card mb-3">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-check"></i> Convalidações Anuais</h3>
                </div>
                <div class="card-body">
                    <div class="tabela-container">
                        <table class="tabela" id="tabelaConvalidacoes">
                            <thead>
                                <tr>
                                    <th>N° Vistoria</th>
                                    <th>A Realizar Entre</th>
                                    <th>E</th>
                                    <th>Lugar e Data da Realização</th>
                                    <th>Vistoriador</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $vistorias_padrao = certificadoNomesConvalidacoes($tipo_embarcacao_convalidacoes);
                                $convalidacoes_por_numero = certificadoConvalidacoesPorNumero($convalidacoes);
                                $usar_mapa_convalidacoes = !empty($convalidacoes_por_numero);
                                foreach ($vistorias_padrao as $i => $nome_vistoria):
                                    $numero_vistoria = $i + 1;
                                    $conv = $usar_mapa_convalidacoes
                                        ? ($convalidacoes_por_numero[$numero_vistoria] ?? null)
                                        : ($convalidacoes[$i] ?? null);
                                    $realizacao = certificadoSepararLocalDataConvalidacao($conv['local_data'] ?? '');
                                    $vistoriador_selecionado = '';
                                    foreach ($vistoriadores_convalidacao as $vistoriador_opcao) {
                                        if (($conv['vistoriador'] ?? '') === $vistoriador_opcao['nome']) $vistoriador_selecionado = $vistoriador_opcao['id'];
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="conv_id[]" value="<?php echo h($conv['id'] ?? ''); ?>">
                                            <input type="text" name="conv_numero[]" class="form-control" 
                                                   value="<?php echo h($nome_vistoria); ?>" readonly
                                                   style="background: var(--cor-sidebar);">
                                        </td>
                                        <td>
                                            <input type="date" name="conv_data_inicio[]" class="form-control"
                                                   value="<?php echo h($conv['data_inicio'] ?? ''); ?>">
                                        </td>
                                        <td>
                                            <input type="date" name="conv_data_fim[]" class="form-control"
                                                   value="<?php echo h($conv['data_fim'] ?? ''); ?>">
                                        </td>
                                        <td>
                                            <select name="conv_local[]" class="form-control">
                                                <option value="">Selecione o local</option>
                                                <?php foreach ($locais_convalidacao as $local_opcao): ?>
                                                    <option value="<?php echo h($local_opcao); ?>" <?php echo $realizacao['local'] === $local_opcao ? 'selected' : ''; ?>><?php echo h($local_opcao); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="date" name="conv_data_realizacao[]" class="form-control" style="margin-top:6px" value="<?php echo h($realizacao['data']); ?>">
                                        </td>
                                        <td>
                                            <select name="conv_vistoriador_id[]" class="form-control">
                                                <option value="">Selecione o vistoriador</option>
                                                <?php foreach ($vistoriadores_convalidacao as $vistoriador_opcao): ?>
                                                    <option value="<?php echo h($vistoriador_opcao['id']); ?>" <?php echo $vistoriador_selecionado === $vistoriador_opcao['id'] ? 'selected' : ''; ?>><?php echo h($vistoriador_opcao['nome']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO 4: Vistoria e Certificação -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3><i class="fas fa-clipboard-check"></i> Vistoria e Certificação</h3>
                </div>
                <div class="card-body">
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="relatorio_numero">N° Relatório de Vistorias Base</label>
                            <input type="text" name="relatorio_numero" id="relatorio_numero" class="form-control"
                                   placeholder="Ex: AM-REL-AP:100/26"
                                   value="<?php echo $editando ? h($certificado['relatorio_numero']) : h($preenchimento['relatorio_numero']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="local_vistoria">Local da Vistoria Flutuante</label>
                            <input type="text" name="local_vistoria" id="local_vistoria" class="form-control"
                                   placeholder="Ex: Belém/PA"
                                   value="<?php echo $editando ? h($certificado['local_vistoria']) : h($preenchimento['local_vistoria'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="data_vistoria">Data da Vistoria Flutuante</label>
                            <input type="date" name="data_vistoria" id="data_vistoria" class="form-control"
                                   value="<?php echo $editando ? h($certificado['data_vistoria']) : h($preenchimento['data_vistoria'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO 5: Observações -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3><i class="fas fa-sticky-note"></i> Observações</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="observacoes">Observações Gerais</label>
                        <textarea name="observacoes" id="observacoes" class="form-control" rows="4" 
                                  placeholder="Digite observações adicionais sobre o certificado..."><?php echo $editando ? h($certificado['observacoes'] ?? '') : ''; ?></textarea>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO 6: Responsável pela Assinatura -->
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

            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="$('#tab-medicoes').tab('show')" style="padding: 10px 20px;">
                    <i class="fas fa-arrow-left"></i> Voltar
                </button>
                <button type="submit" class="btn btn-success" style="padding: 10px 20px;">
                    <i class="fas fa-save"></i> <?php echo $documento_bloqueado ? 'Salvar Convalidações' : ($editando ? 'Atualizar Certificado' : 'Salvar Certificado'); ?>
                </button>
            </div>
        </div>
    </div>
</form>
</div>

<script>
<?php if ($documento_bloqueado): ?>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formCertificado');
    form.querySelectorAll('input, select, textarea').forEach(function(input) {
        const name = input.getAttribute('name') || '';
        const permitido = name.startsWith('conv_local') || name.startsWith('conv_data_realizacao') || name.startsWith('conv_vistoriador_id') || name === 'conv_id[]' || ['csrf_token','action','id'].includes(name);
        if (!permitido) {
            input.disabled = true;
            input.style.background = '#f3f4f6';
        }
    });
});
<?php endif; ?>
function carregarDadosEmbarcacao(embarcacaoId) {
    if (!embarcacaoId) return;
    
    const select = document.getElementById('embarcacao_id');
    const option = select.options[select.selectedIndex];
    
    if (!option) return;
    
    const campos = {
        'nome_embarcacao': 'nome',
        'numero_inscricao': 'numero_inscricao',
        'indicativo_chamada': 'indicativo_chamada',
        'tipo_embarcacao': 'tipo',
        'comprimento_total': 'comprimento_total',
        'comprimento_casco': 'comprimento_casco',
        'boca_moldada': 'boca_moldada',
        'pontal_moldado': 'pontal_moldado',
        'arqueacao_bruta': 'arqueacao_bruta',
        'material_casco': 'material_casco'
    };
    
    for (const [fieldId, dataAttr] of Object.entries(campos)) {
        const input = document.getElementById(fieldId);
        if (input) {
            const value = option.dataset[dataAttr] || '';
            input.value = value;
        }
    }
}

<?php if (!empty($_GET['agendamento_id'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('embarcacao_id');
    if (select && select.value) {
        carregarDadosEmbarcacao(select.value);
    }
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

<!-- Modal de Prévia do Certificado -->
<div class="modal" id="modalPrevia">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-eye"></i> Prévia do Certificado CNBL</h2>
            <button class="close-btn" onclick="fecharModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="certificate-preview watermark" id="certificadoPreview">
                <!-- Conteúdo será preenchido via JavaScript -->
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="fecharModal()">
                <i class="fas fa-times"></i> Fechar
            </button>
            <button type="button" class="btn btn-primary" onclick="imprimirCertificado()">
                <i class="fas fa-print"></i> Imprimir / Salvar PDF
            </button>
        </div>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 10000;
    overflow-y: auto;
    padding: 20px;
}

.modal.active {
    display: block;
}

.modal-content {
    background: white;
    max-width: 900px;
    margin: 0 auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.modal-header {
    background: #0891b2;
    color: white;
    padding: 15px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 10;
}

.modal-header h2 {
    font-size: 18px;
    font-weight: 500;
    margin: 0;
}

.close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 32px;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.close-btn:hover {
    background: rgba(255,255,255,0.2);
}

.modal-body {
    padding: 0;
}

.modal-footer {
    padding: 15px 25px;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    position: sticky;
    bottom: 0;
}

.certificate-preview {
    background: white;
    padding: 25px;
    font-family: 'Courier New', monospace;
    position: relative;
    line-height: 1.3;
}

.certificate-preview.watermark::before {
    content: 'MINUTA';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-45deg);
    font-size: 140px;
    font-weight: 700;
    color: rgba(220, 38, 38, 0.06);
    z-index: 1;
    pointer-events: none;
    letter-spacing: 20px;
    font-family: Arial, sans-serif;
}

.cert-preview > * {
    position: relative;
    z-index: 2;
}

.cert-header {
    text-align: center;
    margin-bottom: 12px;
    position: relative;
}

.cert-brasao {
    position: absolute;
    left: 10px;
    top: 0;
    width: 45px;
    height: 55px;
    background: #f0f0f0;
    border: 1px solid #999;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
    color: #666;
}

.cert-number {
    position: absolute;
    right: 10px;
    top: 0;
    font-size: 9px;
    font-weight: bold;
    background: #e8e8e8;
    padding: 3px 8px;
    border: 1px solid #999;
}

.cert-title {
    font-size: 13px;
    font-weight: bold;
    margin-top: 35px;
    margin-bottom: 4px;
    letter-spacing: 0.5px;
}

.cert-subtitle {
    font-size: 11px;
    font-weight: bold;
    margin-bottom: 2px;
}

.cert-info {
    font-size: 8px;
    color: #333;
    line-height: 1.3;
}

.cert-empresa {
    font-size: 9px;
    font-weight: bold;
    margin-top: 4px;
    margin-bottom: 8px;
}

.cert-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
    font-size: 9px;
}

.cert-table th,
.cert-table td {
    border: 1px solid #000;
    padding: 5px 6px;
    text-align: center;
    vertical-align: middle;
}

.cert-table th {
    background: #e0e0e0;
    font-weight: bold;
    font-size: 8px;
}

.cert-table td {
    font-weight: 500;
    min-height: 20px;
}

.cert-section-title {
    font-size: 8px;
    font-weight: bold;
    background: #e0e0e0;
    padding: 3px 6px;
    border: 1px solid #000;
    text-align: center;
    margin-bottom: 0;
}

.cert-section-content {
    border: 1px solid #000;
    border-top: none;
    padding: 5px 6px;
    font-size: 9px;
    font-weight: 500;
    min-height: 20px;
}

.cert-checkbox-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
    font-size: 9px;
}

.cert-checkbox-table th,
.cert-checkbox-table td {
    border: 1px solid #000;
    padding: 4px;
    text-align: center;
}

.cert-checkbox-table th {
    background: #e0e0e0;
    font-weight: bold;
    font-size: 8px;
}

.cert-checkbox-table td {
    font-weight: 500;
}

.cert-measurements {
    margin: 12px 0;
    padding-left: 15px;
}

.cert-measurement-item {
    font-size: 9px;
    font-weight: 500;
    margin-bottom: 3px;
    text-transform: uppercase;
}

.cert-diagram {
    text-align: center;
    margin: 12px 0;
    padding: 8px;
    border: 1px solid #ccc;
    background: #f9f9f9;
}

.cert-diagram-placeholder {
    width: 280px;
    height: 70px;
    background: #e8e8e8;
    border: 1px solid #999;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: #666;
}

.cert-narrative {
    font-size: 9px;
    font-weight: bold;
    text-transform: uppercase;
    text-align: justify;
    margin: 8px 0;
    line-height: 1.4;
}

.cert-certification {
    font-size: 9px;
    font-weight: bold;
    text-align: justify;
    margin: 8px 0;
    line-height: 1.4;
}

.cert-validity {
    text-align: center;
    font-size: 11px;
    font-weight: bold;
    margin: 12px 0 4px;
}

.cert-expedition {
    text-align: center;
    font-size: 9px;
    font-weight: 500;
    margin-bottom: 12px;
}

.cert-signature-area {
    display: flex;
    justify-content: center;
    margin: 15px 0;
}

.cert-signature-box {
    border: 2px solid #0891b2;
    width: 170px;
    height: 65px;
    position: relative;
    background: white;
}

.cert-signature-box::before {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    right: 2px;
    bottom: 2px;
    border: 1px solid #0891b2;
    opacity: 0.5;
}

.cert-signature-line {
    position: absolute;
    top: 42px;
    left: 8px;
    right: 8px;
    border-top: 1px solid #000;
}

.cert-signature-name {
    position: absolute;
    bottom: 4px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 8px;
    font-weight: bold;
}

.cert-signature-title {
    position: absolute;
    bottom: 16px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 7px;
}

.cert-signature-reg {
    position: absolute;
    bottom: 26px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 7px;
    font-weight: bold;
    color: #0891b2;
}

@media print {
    body * {
        visibility: hidden;
    }
    .modal, .modal * {
        visibility: visible;
    }
    .modal {
        position: absolute;
        left: 0;
        top: 0;
        background: white;
        padding: 0;
        max-height: none;
        overflow: visible;
    }
    .modal-header, .modal-footer {
        display: none !important;
    }
    .modal-content {
        box-shadow: none;
        max-width: 100%;
    }
    .certificate-preview {
        padding: 10px;
    }
    .certificate-preview.watermark::before {
        display: none;
    }
}
</style>

<script>
// Máscaras de entrada
document.addEventListener('DOMContentLoaded', function() {
    // Validação ao digitar
    const requiredInputs = document.querySelectorAll('input[required], select[required]');
    requiredInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validarCampo(this);
        });
        input.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                validarCampo(this);
            }
        });
    });
});

function validarCampo(campo) {
    const formGroup = campo.closest('.form-group');
    if (!formGroup) return true;
    
    let valido = true;
    
    if (campo.hasAttribute('required') && !campo.value.trim()) {
        valido = false;
    }
    
    if (valido) {
        formGroup.classList.remove('error');
    } else {
        formGroup.classList.add('error');
    }
    
    return valido;
}

function validarFormulario() {
    let formularioValido = true;
    const requiredInputs = document.querySelectorAll('#formCNBL input[required], #formCNBL select[required]');
    
    requiredInputs.forEach(input => {
        if (!validarCampo(input)) {
            formularioValido = false;
        }
    });
    
    if (!formularioValido) {
        alert('Por favor, preencha todos os campos obrigatórios corretamente.');
        return false;
    }
    
    return true;
}

function abrirModalPrevia() {
    if (!validarFormulario()) {
        return;
    }
    
    const preview = document.getElementById('certificadoPreview');
    
    // Coletar dados do formulário
    const dados = {
        nome_embarcacao: document.getElementById('nome_embarcacao').value || 'PROJETO AMAZON TESTE 01',
        numero_inscricao: document.getElementById('numero_inscricao').value || 'Não Fornecido',
        porto_inscricao: document.getElementById('porto_inscricao').value || 'Belém/PA',
        arqueacao_bruta: document.getElementById('arqueacao_bruta').value || '366',
        atividade_servico: document.getElementById('atividade_servico').value || 'Carga Geral sobre o Convés',
        tipo_embarcacao: document.getElementById('tipo_embarcacao').value || 'X',
        area_navegacao: document.querySelector('input[name="area_navegacao"]:checked')?.value || 'Área 1',
        dist_centro_disco: document.getElementById('dist_centro_disco').value || '476',
        marca_linha_area1: document.getElementById('marca_linha_area1').value || '0',
        marca_linha_area2: document.getElementById('marca_linha_area2').value || '476',
        aresta_conves: document.getElementById('aresta_conves').value || '0',
        dist_bico_proa: document.getElementById('dist_bico_proa').value || '25440',
        acrescimo_agua_salgada: document.getElementById('acrescimo_agua_salgada').value || '0',
        validade: document.getElementById('data_validade').value || '10/04/2026',
        expedicao: document.getElementById('data_emissao').value || '09/02/2026',
        local_expedicao: document.getElementById('local_emissao').value || 'Belém-PA',
        engenheiro: document.getElementById('assinante_nome').value || 'Aracelli Suzane Andrade Ferreira',
        crea: document.getElementById('assinante_registro').value || '22.482',
        cargo: document.getElementById('assinante_titulo').value || 'Engenheira Naval'
    };
    
    // Formatar data para exibição
    function formatarDataExtenso(dataStr) {
        if (!dataStr) return '___/___/______';
        const partes = dataStr.split('/');
        if (partes.length === 3) {
            const dia = partes[0];
            const mes = parseInt(partes[1]);
            const ano = partes[2];
            const meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
                          'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
            return dia + ' de ' + meses[mes - 1] + ' de ' + ano;
        }
        return dataStr;
    }
    
    const validadeFormatada = formatarDataExtenso(dados.validade);
    const expedicaoFormatada = formatarDataExtenso(dados.expedicao);
    
    // Determinar letra do tipo
    const tipoMap = {
        'A': 'CARGA GERAL',
        'B': 'TANQUE',
        'C': 'GRANELEIRO',
        'D': 'PASSAGEIROS',
        'E': 'EMPURRADOR/EMPURRADO'
    };
    const tipoDesc = tipoMap[dados.tipo_embarcacao] || dados.tipo_embarcacao;
    
    preview.innerHTML = `
        <div class="cert-preview">
            <div class="cert-header">
                <div class="cert-brasao">BRASÃO</div>
                <div class="cert-number">CERTIFICADO AM-CNBL - ${dados.numero_certificado || '001/26'}</div>
                <div class="cert-title">CERTIFICADO NACIONAL DE BORDA LIVRE</div>
                <div class="cert-subtitle">PARA NAVEGAÇÃO INTERIOR</div>
                <div class="cert-info">(EMITIDO DE ACORDO COM A NORMAM-202)</div>
                <div class="cert-info" style="font-weight: bold;">REPÚBLICA FEDERATIVA DO BRASIL</div>
                <div class="cert-info" style="font-weight: bold;">MARINHA DO BRASIL</div>
                <div class="cert-info" style="font-weight: bold;">DIRETORIA DE PORTOS E COSTAS</div>
            <div class="cert-empresa">AMAZON NAVAL LTDA</div>
            </div>
            
            <table class="cert-table">
                <tr>
                    <th style="width: 58%;">Nome da Embarcação</th>
                    <th style="width: 18%;">N° de Inscrição</th>
                    <th style="width: 24%;">Porto de Inscrição</th>
                </tr>
                <tr>
                    <td><strong>${dados.nome_embarcacao.toUpperCase()}</strong></td>
                    <td>${dados.numero_inscricao}</td>
                    <td>${dados.porto_inscricao}</td>
                </tr>
            </table>
            
            <div class="cert-section-title">Arqueação Bruta</div>
            <div class="cert-section-content" style="text-align: center; font-weight: bold;">${dados.arqueacao_bruta}</div>
            
            <div class="cert-section-title" style="margin-top: 8px;">Atividade ou Serviço</div>
            <div class="cert-section-content" style="text-align: center;">${dados.atividade_servico}</div>
            
            <table class="cert-checkbox-table" style="margin-top: 8px;">
                <tr>
                    <th style="width: 50%;">Tipo de Embarcação</th>
                    <th style="width: 10%;">A</th>
                    <th style="width: 10%;">B</th>
                    <th style="width: 10%;">C</th>
                    <th style="width: 10%;">D</th>
                    <th style="width: 10%;">E</th>
                </tr>
                <tr>
                    <td></td>
                    <td>${dados.tipo_embarcacao === 'A' ? 'X' : ''}</td>
                    <td>${dados.tipo_embarcacao === 'B' ? 'X' : ''}</td>
                    <td>${dados.tipo_embarcacao === 'C' ? 'X' : ''}</td>
                    <td>${dados.tipo_embarcacao === 'D' ? 'X' : ''}</td>
                    <td>${dados.tipo_embarcacao === 'E' ? 'X' : ''}</td>
                </tr>
            </table>
            
            <table class="cert-checkbox-table" style="margin-top: 8px;">
                <tr>
                    <th style="width: 50%;">Área de Navegação Interior</th>
                    <th style="width: 25%;">Área 1</th>
                    <th style="width: 25%;">Área 2</th>
                </tr>
                <tr>
                    <td></td>
                    <td>${dados.area_navegacao === 'Área 1' ? 'X' : ''}</td>
                    <td>${dados.area_navegacao === 'Área 2' ? 'X' : ''}</td>
                </tr>
            </table>
            
            <div style="margin-top: 12px; font-weight: bold; font-size: 9px;">DISTÂNCIA DA PARTE SUPERIOR DA LINHA DO CONVÉS ATÉ:</div>
            
            <div class="cert-measurements">
                <div class="cert-measurement-item">CENTRO DO DISCO: ${dados.dist_centro_disco} mm</div>
                <div class="cert-measurement-item">MARCA DA LINHA DE CARGA PARA A ÁREA 1: ${dados.marca_linha_area1} mm</div>
                <div class="cert-measurement-item">MARCA DA LINHA DE CARGA PARA A ÁREA 2: ${dados.marca_linha_area2} mm</div>
            </div>
            
            <div class="cert-diagram">
                <div class="cert-diagram-placeholder">[Diagrama do Disco de Plimsoll]</div>
            </div>
            
            <div class="cert-narrative">
                A ARESTA SUP. DA LINHA DO CONVÉS ESTÁ SITUADA A ${dados.aresta_conves} mm DA FACE SUPERIOR DO CONVÉS AO LADO. O CENTRO DO DISCO ESTÁ SITUADO A ${dados.dist_centro_disco} mm DO BICO DE PROA. ACRÉSCIMO PARA NAVEGAÇÃO EM ÁGUA SALGADA ${dados.acrescimo_agua_salgada} mm ABAIXO DO DISCO DE PLIMSOLL.
            </div>
            
            <div class="cert-certification">
                O PRESENTE CERTIFICADO É EXPEDIDO PARA ATESTAR QUE A EMBARCAÇÃO FOI VISTORIADA E QUE A SUA BORDA LIVRE E LINHA DE CARGA INDICADAS FORAM APOSTAS E SERÃO CONTROLADAS CONFORME AS DISPOSIÇÕES EM VIGOR.
            </div>
            
            <div class="cert-validity">VÁLIDO até: ${validadeFormatada}</div>
            
            <div class="cert-expedition">
                Expedido em ${dados.local_expedicao}, em ${expedicaoFormatada}
            </div>
            
            <div class="cert-signature-area">
                <div class="cert-signature-box">
                    <div class="cert-signature-reg">${dados.crea}</div>
                    <div class="cert-signature-line"></div>
                    <div class="cert-signature-name">${dados.engenheiro.toUpperCase()}</div>
                    <div class="cert-signature-title">${dados.cargo}</div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('modalPrevia').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function fecharModal() {
    document.getElementById('modalPrevia').classList.remove('active');
    document.body.style.overflow = 'auto';
}

function imprimirCertificado() {
    window.print();
}

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        fecharModal();
    }
});

// Fechar modal clicando fora
document.getElementById('modalPrevia').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModal();
    }
});
</script>
