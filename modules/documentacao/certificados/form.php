<?php
/**
 * MÓDULO: Documentação > Certificados CSN
 * Formulário de Criação/Edição do Certificado
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
$distribuicao = [];
$convalidacoes = [];
$tipo_embarcacao_convalidacoes = '';

// Se tem ID, é edição
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $editando = true;

    $stmt = $pdo->prepare("SELECT * FROM certificados_csn WHERE id = :id AND ativo = 1");
    $stmt->execute([':id' => $id]);
    $certificado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$certificado) {
        setMensagem('error', 'Certificado não encontrado.');
        redirecionar(APP_URL . 'documentacao/certificados');
    }

    // Buscar distribuição de passageiros
    $stmt_dist = $pdo->prepare("SELECT * FROM csn_distribuicao_passageiros WHERE certificado_id = :cert_id ORDER BY id");
    $stmt_dist->execute([':cert_id' => $id]);
    $distribuicao = $stmt_dist->fetchAll(PDO::FETCH_ASSOC);

    // Buscar convalidações
    $stmt_conv = $pdo->prepare("SELECT * FROM csn_convalidacoes WHERE certificado_id = :cert_id ORDER BY id");
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

// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---
$preenchimento = [
    'nome_embarcacao'    => '', 'numero_inscricao'   => '', 'indicativo_chamada' => '',
    'atividades_servicos'=> '', 'tipo_embarcacao'    => '', 'ano_construcao'     => '',
    'comprimento_m'      => '', 'arqueacao_bruta'    => '', 'material_casco'     => '',
    'relatorio_numero'   => '', 'data_vistoria_seco' => '', 'data_vistoria_flutuando' => '',
    'local_vistoria'     => '', 'armador_nome'       => ''
];

if (!$editando && !empty($_GET['agendamento_id'])) {
    $stmtPre = $pdo->prepare("
        SELECT e.nome as emb_nome, e.registro, e.tipo_embarcacao, e.ano as emb_ano,
               e.comprimento_total, e.arqueacao_bruta, e.material_casco,
               v.numero as relatorio_numero, arm.nome as armador_nome
        FROM agendamentos a
        JOIN embarcacoes e ON a.embarcacao_id = e.id
        LEFT JOIN vistorias v ON v.id = (SELECT v2.id FROM vistorias v2 WHERE v2.agendamento_id=a.id ORDER BY v2.criado_em DESC, v2.id DESC LIMIT 1)
        LEFT JOIN clientes arm ON v.armador_id = arm.id
        WHERE a.id = :aid
    ");
    $stmtPre->execute([':aid' => $_GET['agendamento_id']]);
    $dadosPre = $stmtPre->fetch(PDO::FETCH_ASSOC);

    if ($dadosPre) {
        $preenchimento['nome_embarcacao']    = $dadosPre['emb_nome'];
        $preenchimento['numero_inscricao']   = $dadosPre['registro'];
        $preenchimento['tipo_embarcacao']    = $dadosPre['tipo_embarcacao'];
        $preenchimento['ano_construcao']     = $dadosPre['emb_ano'];
        $preenchimento['comprimento_m']      = $dadosPre['comprimento_total'];
        $preenchimento['arqueacao_bruta']    = $dadosPre['arqueacao_bruta'];
        $preenchimento['material_casco']     = $dadosPre['material_casco'];
        $preenchimento['relatorio_numero']   = $dadosPre['relatorio_numero'];
        $preenchimento['armador_nome']       = $dadosPre['armador_nome'];
    }
}


// Gerar próximo número (se não estiver editando)
$proximo_numero = '';
if (!$editando) {
    $ano = date('y');
    $ano4 = date('Y');
    $stmt_num = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_csn WHERE YEAR(criado_em) = :ano");
    $stmt_num->execute([':ano' => $ano4]);
    $total = $stmt_num->fetch()['total'];
    $seq = $total + 1;
    $proximo_numero = "AM-CSN-{$seq}/{$ano}";
}


// Buscar lista de despachantes ativos
$stmt_desp = $pdo->prepare("SELECT id, nome FROM clientes WHERE perfil = 'despachante' AND status = 'ATIVO' ORDER BY nome");
$stmt_desp->execute();
$despachantes_list = $stmt_desp->fetchAll(PDO::FETCH_ASSOC);

$documento_bloqueado = documentoEstaAssinado($pdo, 'certificados_csn', $certificado['id'] ?? null);
$vistoriadores_convalidacao = certificadoVistoriadoresAtivos($pdo);
$locais_convalidacao = certificadoLocaisVistoria($pdo);
$convalidacoes = certificadoAplicarUltimaVistoria(
    $convalidacoes,
    certificadoUltimaVistoriaParaConvalidacao($pdo, $certificado['vistoria_id'] ?? null)
);

$titulo_page = ($editando ? 'Editar' : 'Novo') . ' Certificado CSN - ' . APP_NAME;
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-header">
        <h2>
            <i class="fas fa-file-certificate"></i> 
            <?php echo $editando ? 'Editar Certificado CSN' : 'Novo Certificado CSN'; ?>
        </h2>
        <a href="<?php echo APP_URL; ?>documentacao/certificados" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

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

    <?php if (($_SESSION['usuario_cargo'] ?? '') !== 'ADMIN'): ?>
        <div class="alert alert-info" style="margin-bottom: 20px;">
            <i class="fas fa-eye"></i> <strong>Modo de visualização</strong> — Você não tem permissão para editar este certificado.
        </div>
    <?php endif; ?>
    <form method="POST" action="<?php echo APP_URL; ?>documentacao/certificados/actions" id="formCertificado" <?php echo ($_SESSION['usuario_cargo'] ?? '') !== 'ADMIN' ? 'style="pointer-events: none; opacity: 0.7;"' : ''; ?>>
        <input type="hidden" name="action" value="<?php echo $documento_bloqueado ? 'atualizar_convalidacoes' : 'salvar'; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?php echo h($certificado['id']); ?>">
            <input type="hidden" name="vistoria_id" value="<?php echo h($certificado['vistoria_id'] ?? ''); ?>">
        <?php endif; ?>

        <!-- SEÇÃO 1: Identificação do Certificado -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-id-card"></i> Identificação do Certificado</h3>
            </div>
            <div class="card-body">
                <div class="grid-3">
                    <div class="form-group">
                        <label>Número do Certificado</label>
                        <input type="text" class="form-control" value="<?php echo $editando ? h($certificado['numero']) : h($proximo_numero); ?>" readonly 
                               style="background: var(--cor-sidebar); font-weight: bold;">
                    </div>
                    <div class="form-group">
                        <label for="tipo">Tipo do Certificado</label>
                        <select name="tipo" id="tipo" class="form-control" required>
                            <option value="Definitivo" <?php echo ($editando && ($certificado['tipo'] ?? '') === 'Definitivo') || !$editando ? 'selected' : ''; ?>>Definitivo</option>
                            <option value="Condicional" <?php echo $editando && ($certificado['tipo'] ?? '') === 'Condicional' ? 'selected' : ''; ?>>Condicional</option>
                            <option value="Provisório" <?php echo $editando && ($certificado['tipo'] ?? '') === 'Provisório' ? 'selected' : ''; ?>>Provisório</option>
                        </select>
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
                <div class="grid-3">
                    <div class="form-group">
                        <label for="emitente">Emitente</label>
                        <input type="text" name="emitente" id="emitente" class="form-control"
                               placeholder="Capitania, Delegacia, Agência, Certificadora ou Sociedade Classificadora"
                               value="<?php echo $editando ? h($certificado['emitente'] ?? '') : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="normam_aplicavel">NORMAM aplicável</label>
                        <?php $normamAtual = $editando ? ($certificado['normam_aplicavel'] ?? '') : ''; ?>
                        <select name="normam_aplicavel" id="normam_aplicavel" class="form-control">
                            <option value="">-- Selecione --</option>
                            <option value="NORMAM-01" <?php echo $normamAtual === 'NORMAM-01' ? 'selected' : ''; ?>>NORMAM-01</option>
                            <option value="NORMAM-02" <?php echo $normamAtual === 'NORMAM-02' ? 'selected' : ''; ?>>NORMAM-02</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tipo_vistoria_certificado">Tipo de vistoria no certificado</label>
                        <?php $tipoVistoriaAtual = $editando ? ($certificado['tipo_vistoria_certificado'] ?? '') : ''; ?>
                        <select name="tipo_vistoria_certificado" id="tipo_vistoria_certificado" class="form-control">
                            <option value="">-- Selecione --</option>
                            <option value="Inicial" <?php echo $tipoVistoriaAtual === 'Inicial' ? 'selected' : ''; ?>>Inicial</option>
                            <option value="Renovacao" <?php echo $tipoVistoriaAtual === 'Renovacao' ? 'selected' : ''; ?>>Renovação</option>
                            <option value="Intermediaria" <?php echo $tipoVistoriaAtual === 'Intermediaria' ? 'selected' : ''; ?>>Intermediária</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 2: Dados da Embarcação -->
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
                        <label for="atividades_servicos">Atividades ou Serviços</label>
                        <input type="text" name="atividades_servicos" id="atividades_servicos" class="form-control"
                               value="<?php echo $editando ? h($certificado['atividades_servicos']) : h($preenchimento['atividades_servicos']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="tipo_embarcacao">Tipo de Embarcação</label>
                        <input type="text" name="tipo_embarcacao" id="tipo_embarcacao" class="form-control"
                               value="<?php echo $editando ? h($certificado['tipo_embarcacao']) : h($preenchimento['tipo_embarcacao']); ?>">
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
                        <label for="comprimento_m">Comprimento (m)</label>
                        <input type="number" name="comprimento_m" id="comprimento_m" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($certificado['comprimento_m']) : h($preenchimento['comprimento_m']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="arqueacao_bruta">Arqueação Bruta</label>
                        <input type="text" name="arqueacao_bruta" id="arqueacao_bruta" class="form-control"
                               value="<?php echo $editando ? h($certificado['arqueacao_bruta']) : h($preenchimento['arqueacao_bruta']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="material_casco">Material do Casco</label>
                        <input type="text" name="material_casco" id="material_casco" class="form-control"
                               value="<?php echo $editando ? h($certificado['material_casco']) : h($preenchimento['material_casco']); ?>">
                    </div>
                </div>

                <!-- Tipo de Navegação (checkboxes) -->
                <div class="form-group">
                    <label>Tipo de Navegação</label>
                    <div class="d-flex gap-2" style="flex-wrap: wrap;">
                        <?php
                        $tipos_nav = ['MAR ABERTO', 'INTERIOR', 'APOIO PORTUÁRIO'];
                        $selected_tipos = $editando ? explode(',', $certificado['tipo_navegacao'] ?? '') : [];
                        foreach ($tipos_nav as $tn):
                        ?>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="checkbox" name="tipo_navegacao[]" value="<?php echo $tn; ?>"
                                       <?php echo in_array($tn, $selected_tipos) ? 'checked' : ''; ?>>
                                <?php echo $tn; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Área de Navegação (checkboxes) -->
                <div class="form-group">
                    <label>Área de Navegação</label>
                    <div class="d-flex gap-2" style="flex-wrap: wrap;">
                        <?php
                        $areas_nav = ['Longo Curso', 'Cabotagem', 'Apoio Marítimo', 'Área 1', 'Área 2'];
                        $selected_areas = $editando ? explode(',', $certificado['area_navegacao'] ?? '') : [];
                        foreach ($areas_nav as $an):
                        ?>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="checkbox" name="area_navegacao[]" value="<?php echo $an; ?>"
                                       <?php echo in_array($an, $selected_areas) ? 'checked' : ''; ?>>
                                <?php echo $an; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Motor -->
                <div class="grid-2">
                    <div class="form-group">
                        <label for="fabricante_motor">Fabricante, Modelo e Número do Motor</label>
                        <input type="text" name="fabricante_motor" id="fabricante_motor" class="form-control"
                               value="<?php echo $editando ? h($certificado['fabricante_motor']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="potencia_kw">Potência Propulsiva Total (kW)</label>
                        <input type="text" name="potencia_kw" id="potencia_kw" class="form-control"
                               value="<?php echo $editando ? h($certificado['potencia_kw']) : ''; ?>">
                    </div>
                </div>

                <!-- Carga e Passageiros -->
                <div class="grid-3">
                    <div class="form-group">
                        <label>Autorizado a Transportar Carga no Convés</label>
                        <div class="d-flex gap-2">
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="radio" name="autorizado_carga" value="1"
                                       <?php echo ($editando && $certificado['autorizado_carga']) ? 'checked' : ''; ?>> SIM
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="radio" name="autorizado_carga" value="0"
                                       <?php echo !$editando || !$certificado['autorizado_carga'] ? 'checked' : ''; ?>> NÃO
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="qtd_passageiros">Qtd. Autorizada de Passageiros</label>
                        <input type="number" name="qtd_passageiros" id="qtd_passageiros" class="form-control"
                               value="<?php echo $editando ? h($certificado['qtd_passageiros']) : '0'; ?>">
                    </div>
                    <div class="form-group">
                        <label for="obs_passageiros">Observação Passageiros</label>
                        <input type="text" name="obs_passageiros" id="obs_passageiros" class="form-control"
                               placeholder="Ex: Vide verso"
                               value="<?php echo $editando ? h($certificado['obs_passageiros']) : ''; ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 3: Vistoria e Certificação -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-clipboard-check"></i> Vistoria e Certificação</h3>
            </div>
            <div class="card-body">
                <div class="grid-3">
                    <div class="form-group">
                        <label for="relatorio_numero">N° Relatório de Vistorias</label>
                        <input type="text" name="relatorio_numero" id="relatorio_numero" class="form-control"
                               placeholder="Ex: AM-REL-AP:100/26"
                               value="<?php echo $editando ? h($certificado['relatorio_numero']) : h($preenchimento['relatorio_numero']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="armador_nome">Armador (da Vistoria)</label>
                        <input type="text" id="armador_nome" class="form-control" readonly
                               style="background: var(--cor-sidebar);"
                               value="<?php echo $editando ? '' : h($preenchimento['armador_nome']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="local_vistoria">Local da Vistoria</label>
                        <input type="text" name="local_vistoria" id="local_vistoria" class="form-control"
                               value="<?php echo $editando ? h($certificado['local_vistoria']) : h($preenchimento['local_vistoria']); ?>">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="data_vistoria_seco">Data da Vistoria em Seco</label>
                        <input type="date" name="data_vistoria_seco" id="data_vistoria_seco" class="form-control"
                               value="<?php echo $editando ? h($certificado['data_vistoria_seco']) : h($preenchimento['data_vistoria_seco']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="data_vistoria_flutuando">Data da Vistoria Flutuando</label>
                        <input type="date" name="data_vistoria_flutuando" id="data_vistoria_flutuando" class="form-control"
                               value="<?php echo $editando ? h($certificado['data_vistoria_flutuando']) : h($preenchimento['data_vistoria_flutuando']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Acessibilidade para Transporte Coletivo Aquaviário</label>
                    <div class="d-flex gap-2">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="acessibilidade" value="sim"
                                   <?php echo ($editando && $certificado['acessibilidade_sim']) ? 'checked' : ''; ?>> SIM
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="acessibilidade" value="nao"
                                   <?php echo !$editando || $certificado['acessibilidade_nao'] ? 'checked' : ''; ?>> NÃO
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="observacoes_verso">Observações do verso do CSN</label>
                    <textarea name="observacoes_verso" id="observacoes_verso" class="form-control" rows="4"><?php echo $editando ? h($certificado['observacoes_verso'] ?? '') : ''; ?></textarea>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 4: Responsável pela Assinatura -->
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

        <!-- SEÇÃO 5: Distribuição de Passageiros -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-users"></i> Distribuição de Passageiros</h3>
            </div>
            <div class="card-body">
                <?php
                $linhasDistribuicaoPadrao = [
                    ['passageiros_sentados', 'Passageiros sentados', 'passageiros'],
                    ['passageiros_camarote', 'Passageiros em camarote', 'passageiros'],
                    ['passageiros_redes', 'Passageiros em redes', 'passageiros'],
                    ['passageiros_em_pe', 'Passageiros em pé', 'passageiros'],
                    ['porao_carga_01', 'Porão de carga 01 (carga geral)', 't'],
                    ['paiol_casco', 'Paiol no casco (mantimentos e materiais diversos)', 't'],
                    ['almoxarifado_conves_principal', 'Almoxarifado no convés principal', 't'],
                    ['deposito_conves_principal', 'Depósito no convés principal', 't'],
                    ['deposito_conves_superior', 'Depósito no convés superior', 't'],
                ];
                $distribuicaoPorCodigo = [];
                foreach ($distribuicao as $distItem) {
                    $codigo = $distItem['item_codigo'] ?? '';
                    if ($codigo !== '') {
                        $distribuicaoPorCodigo[$codigo] = $distItem;
                    }
                }
                ?>
                <table class="tabela" id="tabelaPassageiros">
                    <thead>
                        <tr>
                            <th style="width: 34%;">Item oficial</th>
                            <th>Convés principal</th>
                            <th>Convés superior</th>
                            <th>Área de lazer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($linhasDistribuicaoPadrao as $linhaDist): ?>
                            <?php
                            [$codigoDist, $rotuloDist, $unidadeDist] = $linhaDist;
                            $d = $distribuicaoPorCodigo[$codigoDist] ?? [];
                            $valorLegado = empty($d['item_codigo'] ?? '') ? ($d['quantidade'] ?? '') : '';
                            ?>
                            <tr class="row-passageiro">
                                <td>
                                    <input type="hidden" name="passageiro_codigo[]" value="<?php echo h($codigoDist); ?>">
                                    <input type="hidden" name="passageiro_unidade[]" value="<?php echo h($unidadeDist); ?>">
                                    <input type="hidden" name="passageiro_qtd[]" value="<?php echo h($valorLegado); ?>">
                                    <input type="text" name="passageiro_local[]" class="form-control"
                                           value="<?php echo h($rotuloDist); ?>" readonly style="background: var(--cor-sidebar);">
                                </td>
                                <td>
                                    <input type="text" name="passageiro_conves_principal[]" class="form-control"
                                           value="<?php echo h($d['conves_principal'] ?? $valorLegado); ?>">
                                </td>
                                <td>
                                    <input type="text" name="passageiro_conves_superior[]" class="form-control"
                                           value="<?php echo h($d['conves_superior'] ?? ''); ?>">
                                </td>
                                <td>
                                    <input type="text" name="passageiro_area_lazer[]" class="form-control"
                                           value="<?php echo h($d['area_lazer'] ?? ''); ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SEÇÃO 6: Convalidações -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-calendar-check"></i> Convalidações</h3>
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
                <a href="<?php echo APP_URL; ?>documentacao/certificados" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> <?php echo $documento_bloqueado ? 'Salvar Convalidações' : ($editando ? 'Atualizar Certificado' : 'Salvar Certificado'); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<?php if ($documento_bloqueado): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Se o certificado já estiver assinado, travar todos os campos exceto convalidações
    const form = document.getElementById('formCertificado');
    if (form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            const name = input.getAttribute('name');
            // Se não for das convalidações e nem do controle do form, trava!
            if (name && !name.startsWith('conv_local') && !name.startsWith('conv_data_realizacao') && !name.startsWith('conv_vistoriador_id') && name !== 'conv_id[]' && name !== 'csrf_token' && name !== 'action' && name !== 'id') {
                input.setAttribute('readonly', 'true');
                if (input.tagName === 'SELECT') {
                    input.style.pointerEvents = 'none';
                    input.style.background = '#f3f4f6';
                }
                if (input.getAttribute('type') === 'checkbox' || input.getAttribute('type') === 'radio') {
                    input.setAttribute('disabled', 'true');
                }
            }
        });

        // Trava também as datas das convalidações, deixando apenas Lugar/Data e Vistoriador editáveis
        const convDates = form.querySelectorAll('input[name="conv_data_inicio[]"], input[name="conv_data_fim[]"]');
        convDates.forEach(input => {
            input.setAttribute('readonly', 'true');
        });

        // Esconder botões de adicionar/remover passageiros ou outros elementos de modificação estrutural
        const btnAddPass = document.querySelector('button[onclick="adicionarLinhaPassageiro()"]');
        if (btnAddPass) btnAddPass.style.display = 'none';

        const btnsRemover = document.querySelectorAll('.btn-danger');
        btnsRemover.forEach(btn => btn.style.display = 'none');
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
