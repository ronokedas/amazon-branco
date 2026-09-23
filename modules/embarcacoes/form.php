<?php
/**
 * MODULO: EMBARCACOES
 * Arquivo: form.php - Formulario para criar / editar embarcacao
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/dados_teste_embarcacoes.php';

// Exigir login e permissao do modulo
verificar_sessao();
$cargo = getCargo();
exigirAcesso('embarcacoes');

// Buscar tipos de embarcacao
$tipos_embarcacao = [];
try {
    $stmt = $pdo->query("SELECT id, nome FROM tipos_embarcacao WHERE ativo = 1 ORDER BY nome ASC");
    $tipos_embarcacao = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao buscar tipos de embarcacao: ' . $e->getMessage());
}

$dadosTesteAtivos = dadosTesteEmbarcacoesAtivos($pdo);
$perfisTeste = $dadosTesteAtivos ? perfisDadosTesteEmbarcacoes($tipos_embarcacao) : [];

// Buscar embarcacao se for edicao
$id = $_GET['id'] ?? '';
$embarcacao = null;
$isEdicao = false;

if (!empty($id)) {
    $isEdicao = true;
    try {
        $stmt = $pdo->prepare("SELECT * FROM embarcacoes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $embarcacao = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$embarcacao) {
            setMensagem('error', 'Embarcacao nao encontrada.');
            redirecionar(APP_URL . 'embarcacoes');
        }
    } catch (Exception $e) {
        error_log('Erro ao buscar embarcacao: ' . $e->getMessage());
        setMensagem('error', 'Erro ao carregar dados da embarcacao.');
        redirecionar(APP_URL . 'embarcacoes');
    }
}

// Preservar valores de envio anterior caso tenha ocorrido erro de validação
$valoresPreservados = $_SESSION['mensagem']['valores'] ?? [];
if (!empty($valoresPreservados)) {
    if (!$embarcacao) {
        $embarcacao = $valoresPreservados;
    } else {
        $embarcacao = array_merge($embarcacao, $valoresPreservados);
    }
}

// Buscar clientes (proprietarios e armadores) ativos para vinculo direto
$clientesProprietarios = [];
try {
    $stmtCli = $pdo->query("SELECT id, nome, perfil, cpf_cnpj FROM clientes WHERE (status = 'ATIVO' OR status IS NULL) AND (ativo = 1 OR ativo IS NULL) AND excluido_em IS NULL ORDER BY criado_em DESC, nome ASC");
    $clientesProprietarios = $stmtCli->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao buscar clientes no form de embarcacao: ' . $e->getMessage());
}

$clienteVinculadoId = $embarcacao['proprietario_id'] ?? $embarcacao['cliente_id'] ?? '';
if (empty($clienteVinculadoId) && !empty($embarcacao['id'])) {
    try {
        $stmtCe = $pdo->prepare("SELECT cliente_id FROM clientes_embarcacoes WHERE embarcacao_id = :emb_id AND status = 'ATIVO' ORDER BY vinculado_em DESC LIMIT 1");
        $stmtCe->execute([':emb_id' => $embarcacao['id']]);
        $clienteVinculadoId = $stmtCe->fetchColumn() ?: '';
    } catch (Exception $e) {
        // ignore
    }
}

// Gerar CSRF token
$csrf = gerarCSRF();

$titulo_page = ($isEdicao ? 'Editar' : 'Nova') . ' Embarcacao - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

function renderSelectOptions($optionsList, $currentValue) {
    $found = false;
    $html = '<option value="">-- Selecione --</option>';
    foreach ($optionsList as $opt) {
        $selected = '';
        if ((string)$currentValue === (string)$opt) {
            $selected = 'selected';
            $found = true;
        }
        $html .= '<option value="' . h($opt) . '" ' . $selected . '>' . h($opt) . '</option>';
    }
    if (!empty($currentValue) && !$found) {
        $html .= '<option value="' . h($currentValue) . '" selected>' . h($currentValue) . ' (Personalizado)</option>';
    }
    return $html;
}

$portos_inscricao = [
    'Rio de Janeiro - RJ', 'Santos - SP', 'Itajaí - SC', 'Paranaguá - PR', 
    'Manaus - AM', 'Belém - PA', 'Salvador - BA', 'Vitória - ES', 'Rio Grande - RS',
    'São Francisco do Sul - SC', 'Recife - PE', 'Maceió - AL', 'Santarém - PA',
    'São Luís - MA', 'Fortaleza - CE', 'Natal - RN', 'João Pessoa - PB', 'Macaé - RJ',
    'Porto Alegre - RS', 'Angra dos Reis - RJ'
];
$materiais_casco = ['Aço', 'Alumínio', 'Fibra de Vidro', 'Madeira', 'Borracha / Inflável', 'Misto', 'Ferrocimento'];
$tipos_navegacao = ['Mar Aberto', 'Interior', 'Apoio Marítimo', 'Apoio Portuário'];
$areas_navegacao = ['Área 1', 'Área 2', 'Área 3', 'Navegação Costeira', 'Longo Curso', 'Cabotagem'];
$tipos_servico = ['Esporte e Recreio', 'Transporte de Passageiros', 'Transporte de Carga', 'Pesca', 'Apoio Marítimo', 'Apoio Portuário', 'Serviços Governamentais', 'Pesquisa / Científica', 'Turismo', 'Praticagem', 'Empurra'];
$metodos_arqueacao = ['Regra I', 'Regra II', 'Convenção Internacional 1969', 'Isento'];
$tipos_borda_livre = ['Tipo A', 'Tipo B', 'Tipo B-60', 'Tipo B-100', 'Especial', 'N/A'];
$marcas_linha_carga = ['T', 'V', 'I', 'IAN', 'AD', 'ADT'];
?>

<style>
.tabs-nav {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0 0 20px 0;
    border-bottom: 2px solid var(--cor-borda);
    overflow-x: auto;
}
.tab-item {
    padding: 10px 20px;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    font-weight: 500;
    color: var(--cor-texto-secundario);
    white-space: nowrap;
    position: relative;
    transition: all 0.2s ease;
}
.tab-item.active {
    color: #fff !important;
    background: var(--cor-destaque) !important;
    border-radius: 6px 6px 0 0;
    border-bottom-color: var(--cor-destaque) !important;
}
.tab-item:hover:not(.active) {
    color: var(--cor-texto);
    background: rgba(0,0,0,0.02);
}
.required-star {
    color: #e74c3c;
    font-weight: bold;
    margin-left: 2px;
}
.tab-error-badge {
    display: none;
    background: #e74c3c;
    color: #fff;
    font-size: 0.72rem;
    font-weight: bold;
    padding: 2px 7px;
    border-radius: 10px;
    margin-left: 6px;
    line-height: 1.2;
    vertical-align: middle;
}
.tab-item.has-error .tab-error-badge {
    display: inline-block;
}
/* Aba inativa com erro: fundo sutilmente rosado e texto avermelhado legível */
.tab-item:not(.active).has-error {
    border-color: #e74c3c !important;
    color: #c0392b !important;
    background: #fff5f5 !important;
}
.tab-item:not(.active).has-error .required-star {
    color: #e74c3c;
}
.tab-item:not(.active).has-error .tab-error-badge {
    background: #e74c3c;
    color: #fff;
}
/* Aba ativa com erro: NUNCA usa fundo vermelho sólido, mantém verde oficial do tema com texto branco */
.tab-item.active.has-error {
    color: #fff !important;
    background: var(--cor-destaque) !important;
    border-bottom: 3px solid #ff7675 !important;
}
.tab-item.active .required-star {
    color: #ffeaa7;
}
.tab-item.active.has-error .tab-error-badge {
    background: #ffffff;
    color: #c0392b;
    font-weight: 700;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
}
.field-invalid {
    border-color: #e74c3c !important;
    box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2) !important;
}
.mensagem-erro, .field-error {
    color: #e74c3c;
    font-size: 0.8rem;
    margin-top: 4px;
    display: block;
    font-weight: 500;
}
.tab-pane {
    display: none;
}
.tab-pane.active {
    display: block;
}
.test-data-panel {
    margin-bottom: 22px;
    padding: 16px;
    border: 1px dashed #d99b1d;
    border-radius: 10px;
    background: #fff9e8;
}
.test-data-controls {
    display: grid;
    grid-template-columns: minmax(220px, 1fr) auto;
    gap: 12px;
    align-items: end;
}
.test-data-controls .form-group { margin: 0; }
@media (max-width: 767px) {
    .tabs-nav {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        overflow: visible;
        border-bottom: 0;
    }
    .tab-item {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 52px;
        padding: 8px 10px;
        margin: 0;
        white-space: normal;
        text-align: center;
        line-height: 1.25;
        border: 1px solid var(--cor-borda);
        border-radius: 10px;
    }
    .tab-item.active { border-radius: 10px; }
    .test-data-controls { grid-template-columns: 1fr; }
    .test-data-controls .btn { width: 100%; }
}
</style>

<div class="conteudo-principal">
    <div class="card" style="max-width: 900px; margin: 0 auto;">
        <div class="card-header">
            <h3 style="color: var(--cor-destaque); margin: 0;">
                <i class="fas <?php echo $isEdicao ? 'fa-edit' : 'fa-plus-circle'; ?>"></i>
                <?php echo $isEdicao ? 'Editar Embarcação' : 'Nova Embarcação'; ?>
            </h3>
        </div>
        <div class="card-body">
            <!-- Banner de Alerta SGQ ISO 9001:2015 & NORMAM -->
            <div style="display: flex; align-items: flex-start; gap: 14px; margin-bottom: 22px; padding: 14px 18px; border-radius: 8px; background: rgba(9, 155, 112, 0.08); border-left: 4px solid var(--cor-destaque);">
                <i class="fas fa-certificate" style="font-size: 1.5rem; color: var(--cor-destaque); margin-top: 3px;"></i>
                <div>
                    <strong style="color: var(--cor-destaque); font-size: 0.96rem; display: block; margin-bottom: 4px;">
                        Padrão de Qualidade ISO 9001:2015 & NORMAM
                    </strong>
                    <span style="font-size: 0.88rem; color: var(--cor-texto-secundario); line-height: 1.45; display: block;">
                        Para garantir que a embarcação fique imediatamente apta para emissão de propostas comerciais, agendamentos, vistorias e certificados, todos os campos com <strong style="color: #e74c3c;">*</strong> são de preenchimento obrigatório pelo Sistema de Gestão da Qualidade (incluindo dimensões principais, propulsão e arqueação).
                    </span>
                </div>
            </div>

            <form method="POST"
                  action="<?php echo APP_URL; ?>embarcacoes/actions?action=salvar" 
                  id="formEmbarcacao"
                  autocomplete="off"
                  novalidate
                  onsubmit="return validarFormularioEmbarcacao(event)">
                
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="_submission_token" value="<?php echo h(bin2hex(random_bytes(24))); ?>">
                <input type="hidden" name="id" value="<?php echo h($embarcacao['id'] ?? ''); ?>">
                <input type="hidden" name="aba" id="input_aba_ativa_emb" value="<?php echo h($_GET['aba'] ?? 'tab-gerais'); ?>">

                <?php if ($dadosTesteAtivos && !$isEdicao): ?>
                <section class="test-data-panel" aria-labelledby="titulo-preenchimento-teste">
                    <strong id="titulo-preenchimento-teste" style="display:block;margin-bottom:5px;color:#755000;">
                        <i class="fas fa-flask"></i> Preenchimento rápido para testes
                    </strong>
                    <p style="margin:0 0 13px;color:#6b5a2b;font-size:.92rem;">
                        Selecione um modelo fictício. Os dados serão colocados no formulário, mas o cadastro só será criado quando você clicar em “Criar Embarcação”.
                    </p>
                    <div class="test-data-controls">
                        <div class="form-group">
                            <label for="perfil_dados_teste">Modelo de embarcação</label>
                            <select id="perfil_dados_teste">
                                <option value="">-- Escolha um modelo --</option>
                                <?php foreach ($perfisTeste as $chavePerfil => $perfil): ?>
                                <option value="<?php echo h($chavePerfil); ?>">
                                    <?php echo h($perfil['label'] . ' — ' . $perfil['description']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" class="btn btn-warning" id="btn-preencher-dados-teste">
                            <i class="fas fa-magic"></i> Preencher formulário
                        </button>
                    </div>
                    <small id="status-dados-teste" style="display:block;margin-top:10px;color:#496b2f;" role="status" aria-live="polite"></small>
                </section>
                <?php endif; ?>

                <ul class="tabs-nav" id="embarcacoesTabs">
                    <li class="tab-item active" data-tab="tab-gerais" onclick="openTab('tab-gerais', this)">
                        Dados Gerais <span class="required-star">*</span>
                        <span class="tab-error-badge"></span>
                    </li>
                    <li class="tab-item" data-tab="tab-tecnicos" onclick="openTab('tab-tecnicos', this)">
                        Dados Técnicos e Propulsão <span class="required-star">*</span>
                        <span class="tab-error-badge"></span>
                    </li>
                    <li class="tab-item" data-tab="tab-dimensoes" onclick="openTab('tab-dimensoes', this)">
                        Arqueação e Dimensões <span class="required-star">*</span>
                        <span class="tab-error-badge"></span>
                    </li>
                    <li class="tab-item" data-tab="tab-bordalivre" onclick="openTab('tab-bordalivre', this)">
                        Linha de Carga (CNBL)
                        <span class="tab-error-badge"></span>
                    </li>
                </ul>

                <!-- TAB: DADOS GERAIS -->
                <div id="tab-gerais" class="tab-pane active">
                    <?php if ($isEdicao): ?>
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px;padding:12px;border:1px solid var(--cor-borda);border-radius:10px;background:rgba(9,155,112,.05)">
                        <img src="<?= h($embarcacao['foto_url'] ?: APP_URL . 'assets/img/portal-hero-ship.png') ?>" alt="Foto oficial da embarcação" style="width:104px;height:78px;object-fit:cover;border-radius:9px;border:1px solid var(--cor-borda)">
                        <span><strong style="display:block">Foto oficial da embarcação</strong><small class="text-muted"><?= $embarcacao['foto_url'] ? 'Capturada durante a vistoria técnica.' : 'Ainda não capturada. O vistoriador poderá adicionar durante a vistoria.' ?></small></span>
                    </div>
                    <?php endif; ?>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="nome"><i class="fas fa-ship"></i> Nome da embarcação *</label>
                            <input type="text" id="nome" name="nome" required maxlength="150" value="<?php echo h($embarcacao['nome'] ?? ''); ?>" placeholder="Ex: FB AMAZON I">
                        </div>
                        <div class="form-group">
                            <label for="proprietario_id"><i class="fas fa-user-tie"></i> Proprietário / Armador Responsável</label>
                            <select id="proprietario_id" name="proprietario_id" class="form-control">
                                <option value="">-- Selecione o Proprietário / Armador (opcional) --</option>
                                <?php foreach ($clientesProprietarios as $cp): ?>
                                    <?php 
                                        $cpPerfil = match($cp['perfil'] ?? 'proprietario') {
                                            'armador' => 'Armador',
                                            'despachante' => 'Despachante',
                                            default => 'Proprietário'
                                        };
                                        $selected = ((string)$clienteVinculadoId === (string)$cp['id']) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo h($cp['id']); ?>" <?php echo $selected; ?>>
                                        <?php echo h($cp['nome']); ?> (<?php echo h($cpPerfil); ?><?php echo !empty($cp['cpf_cnpj']) ? ' - ' . h($cp['cpf_cnpj']) : ''; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted"><i class="fas fa-info-circle"></i> Vincula diretamente a embarcação ao cliente para elaboração de propostas e vistorias.</small>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="tipo_embarcacao_id"><i class="fas fa-tags"></i> Tipo de Embarcação <span class="required-star">*</span></label>
                            <select id="tipo_embarcacao_id" name="tipo_embarcacao_id" required>
                                <option value="">-- Selecione o Tipo --</option>
                                <?php foreach ($tipos_embarcacao as $t): ?>
                                    <option value="<?php echo h($t['id']); ?>" <?php echo (isset($embarcacao['tipo_embarcacao_id']) && $embarcacao['tipo_embarcacao_id'] == $t['id']) ? 'selected' : ''; ?>>
                                        <?php echo h($t['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ano"><i class="fas fa-calendar"></i> Ano de Construção</label>
                            <input type="number" id="ano" name="ano" min="1900" max="2099" value="<?php echo h($embarcacao['ano'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="porto_inscricao"><i class="fas fa-anchor"></i> Porto de Inscrição</label>
                            <select id="porto_inscricao" name="porto_inscricao">
                                <?php echo renderSelectOptions($portos_inscricao, $embarcacao['porto_inscricao'] ?? ''); ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="numero_inscricao"><i class="fas fa-id-card"></i> Número de Inscrição</label>
                            <input type="text" id="numero_inscricao" name="numero_inscricao" maxlength="80" autocomplete="new-password" aria-autocomplete="none" autocapitalize="characters" spellcheck="false" data-lpignore="true" data-1p-ignore value="<?php echo h($embarcacao['numero_inscricao'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="indicativo_chamada"><i class="fas fa-satellite-dish"></i> Indicativo de Chamada</label>
                            <input type="text" id="indicativo_chamada" name="indicativo_chamada" maxlength="80" value="<?php echo h($embarcacao['indicativo_chamada'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="observacoes"><i class="fas fa-sticky-note"></i> Observacoes</label>
                        <textarea id="observacoes" name="observacoes" rows="3"><?php echo h($embarcacao['observacoes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- TAB: DADOS TECNICOS -->
                <div id="tab-tecnicos" class="tab-pane">
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="possui_propulsao" style="font-weight:bold; color:var(--cor-destaque);">
                                <i class="fas fa-cogs"></i> Possui Propulsão? (Crítico para Validade) *
                            </label>
                            <select id="possui_propulsao" name="possui_propulsao" required>
                                <option value="">-- Selecione --</option>
                                <option value="1" <?php echo (isset($embarcacao['possui_propulsao']) && $embarcacao['possui_propulsao'] == 1) ? 'selected' : ''; ?>>SIM (Com propulsão)</option>
                                <option value="0" <?php echo (isset($embarcacao['possui_propulsao']) && $embarcacao['possui_propulsao'] == 0) ? 'selected' : ''; ?>>NÃO (Sem propulsão)</option>
                            </select>
                            <small class="text-muted">Usado para cálculo da validade do certificado (5 anos s/ prop., 10 anos c/ prop.)</small>
                        </div>
                        <div class="form-group">
                            <label for="fabricante_motor"><i class="fas fa-industry"></i> Fabricante do Motor</label>
                            <input type="text" id="fabricante_motor" name="fabricante_motor" maxlength="300" autocomplete="off" value="<?php echo h($embarcacao['fabricante_motor'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="modelo_motor"><i class="fas fa-cog"></i> Modelo do Motor</label>
                            <input type="text" id="modelo_motor" name="modelo_motor" maxlength="150" autocomplete="off" value="<?php echo h($embarcacao['modelo_motor'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="numero_motor"><i class="fas fa-hashtag"></i> Número do Motor</label>
                            <input type="text" id="numero_motor" name="numero_motor" maxlength="100" autocomplete="new-password" aria-autocomplete="none" autocapitalize="characters" spellcheck="false" data-lpignore="true" data-1p-ignore value="<?php echo h($embarcacao['numero_motor'] ?? ''); ?>">
                        </div>
                    </div>

                    <small class="text-muted" id="ajuda_propulsao" style="display:block; margin: -4px 0 14px;">
                        Para embarcações com propulsão, fabricante, modelo, número do motor e potência são obrigatórios e alimentam o CSN.
                    </small>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="potencia_kw"><i class="fas fa-bolt"></i> Potência (kW / HP)</label>
                            <input type="text" id="potencia_kw" name="potencia_kw" maxlength="50" autocomplete="off" inputmode="decimal" placeholder="Ex.: 450 kW" value="<?php echo h($embarcacao['potencia_kw'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="material_casco"><i class="fas fa-layer-group"></i> Material do Casco</label>
                            <select id="material_casco" name="material_casco">
                                <?php echo renderSelectOptions($materiais_casco, $embarcacao['material_casco'] ?? ''); ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="tipo_navegacao"><i class="fas fa-compass"></i> Tipo de Navegação</label>
                            <select id="tipo_navegacao" name="tipo_navegacao">
                                <?php echo renderSelectOptions($tipos_navegacao, $embarcacao['tipo_navegacao'] ?? ''); ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="area_navegacao"><i class="fas fa-map"></i> Área de Navegação</label>
                            <select id="area_navegacao" name="area_navegacao">
                                <?php echo renderSelectOptions($areas_navegacao, $embarcacao['area_navegacao'] ?? ''); ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="tipo_servico"><i class="fas fa-briefcase"></i> Atividades / Serviço</label>
                            <select id="tipo_servico" name="tipo_servico">
                                <?php echo renderSelectOptions($tipos_servico, $embarcacao['tipo_servico'] ?? ''); ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="autorizado_carga"><i class="fas fa-box"></i> Autorizado Transporte de Carga?</label>
                            <select id="autorizado_carga" name="autorizado_carga">
                                <option value="">-- Selecione --</option>
                                <option value="1" <?php echo (isset($embarcacao['autorizado_carga']) && $embarcacao['autorizado_carga'] == 1) ? 'selected' : ''; ?>>SIM</option>
                                <option value="0" <?php echo (isset($embarcacao['autorizado_carga']) && $embarcacao['autorizado_carga'] == '0') ? 'selected' : ''; ?>>NÃO</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid-3">
                        <div class="form-group">
                            <label for="numero_tripulantes">Qtd Tripulantes</label>
                            <input type="number" id="numero_tripulantes" name="numero_tripulantes" min="0" value="<?php echo h($embarcacao['numero_tripulantes'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="numero_passageiros_n1">Passageiros N1</label>
                            <input type="number" id="numero_passageiros_n1" name="numero_passageiros_n1" min="0" value="<?php echo h($embarcacao['numero_passageiros_n1'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="numero_passageiros_n2">Passageiros N2</label>
                            <input type="number" id="numero_passageiros_n2" name="numero_passageiros_n2" min="0" value="<?php echo h($embarcacao['numero_passageiros_n2'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="obs_passageiros">Obs Passageiros</label>
                            <input type="text" id="obs_passageiros" name="obs_passageiros" maxlength="100" value="<?php echo h($embarcacao['obs_passageiros'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="acessibilidade">Acessibilidade?</label>
                            <select id="acessibilidade" name="acessibilidade">
                                <option value="">-- Selecione --</option>
                                <option value="1" <?php echo (isset($embarcacao['acessibilidade']) && $embarcacao['acessibilidade'] == 1) ? 'selected' : ''; ?>>SIM</option>
                                <option value="0" <?php echo (isset($embarcacao['acessibilidade']) && $embarcacao['acessibilidade'] == '0') ? 'selected' : ''; ?>>NÃO</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- TAB: ARQUEACAO E DIMENSOES -->
                <div id="tab-dimensoes" class="tab-pane">
                    <div style="margin-bottom: 18px; padding: 12px 16px; background: rgba(9, 155, 112, 0.05); border: 1px solid rgba(9, 155, 112, 0.2); border-radius: 8px; font-size: 0.88rem; color: var(--cor-texto);">
                        <i class="fas fa-ruler-combined" style="color: var(--cor-destaque); margin-right: 6px;"></i>
                        <strong>Dimensionamento Técnico Obrigatório (ISO 8.2 & NORMAM):</strong>
                        Informe ao menos uma medida de Comprimento (Total ou Casco), uma medida de Boca (Moldada ou Máxima), o Pontal Moldado e a Arqueação Bruta (AB).
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="comprimento_total">Comprimento Total (m) <span class="required-star">*</span></label>
                            <input type="number" step="0.01" min="0" id="comprimento_total" name="comprimento_total" value="<?php echo h($embarcacao['comprimento_total'] ?? ''); ?>">
                            <small class="text-muted">Obrigatório C. Total ou do Casco.</small>
                        </div>
                        <div class="form-group">
                            <label for="comprimento_casco">Comprimento Casco (m)</label>
                            <input type="number" step="0.01" min="0" id="comprimento_casco" name="comprimento_casco" value="<?php echo h($embarcacao['comprimento_casco'] ?? ''); ?>">
                            <small class="text-muted">Pode suprir a ausência do C. Total.</small>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="boca_moldada">Boca Moldada (m) <span class="required-star">*</span></label>
                            <input type="number" step="0.01" min="0" id="boca_moldada" name="boca_moldada" value="<?php echo h($embarcacao['boca_moldada'] ?? ''); ?>">
                            <small class="text-muted">Obrigatório Boca Moldada ou Máxima.</small>
                        </div>
                        <div class="form-group">
                            <label for="boca_maxima">Boca Máxima (m)</label>
                            <input type="number" step="0.01" min="0" id="boca_maxima" name="boca_maxima" value="<?php echo h($embarcacao['boca_maxima'] ?? ''); ?>">
                            <small class="text-muted">Pode suprir a ausência da Boca Moldada.</small>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="pontal_moldado">Pontal Moldado (m) <span class="required-star">*</span></label>
                            <input type="number" step="0.01" min="0" id="pontal_moldado" name="pontal_moldado" required value="<?php echo h($embarcacao['pontal_moldado'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="comprimento_lpp">Comprimento LPP (m)</label>
                            <input type="number" step="0.01" min="0" id="comprimento_lpp" name="comprimento_lpp" value="<?php echo h($embarcacao['comprimento_lpp'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="arqueacao_bruta">Arqueação Bruta (AB) <span class="required-star">*</span></label>
                            <input type="text" id="arqueacao_bruta" name="arqueacao_bruta" required maxlength="50" placeholder="Ex.: 45.20" value="<?php echo h($embarcacao['arqueacao_bruta'] ?? ''); ?>">
                            <small class="text-muted">Obrigatório para emissão de propostas e certificados (NORMAM).</small>
                        </div>
                        <div class="form-group">
                            <label for="arqueacao_liquida">Arqueação Líquida (AL)</label>
                            <input type="number" step="0.01" id="arqueacao_liquida" name="arqueacao_liquida" value="<?php echo h($embarcacao['arqueacao_liquida'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="metodo_arqueacao">Método de Arqueação</label>
                            <select id="metodo_arqueacao" name="metodo_arqueacao">
                                <?php echo renderSelectOptions($metodos_arqueacao, $embarcacao['metodo_arqueacao'] ?? ''); ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="local_construcao">Local de Construção</label>
                            <input type="text" id="local_construcao" name="local_construcao" maxlength="200" value="<?php echo h($embarcacao['local_construcao'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="alert alert-info" style="margin: 16px 0;">
                        <strong>Dados de construção usados nas licenças LP e LC.</strong>
                        Eles serão reaproveitados automaticamente ao emitir esses documentos.
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="numero_casco">Número do Casco</label>
                            <input type="text" id="numero_casco" name="numero_casco" maxlength="100" value="<?php echo h($embarcacao['numero_casco'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="porte_bruto">Porte Bruto (t)</label>
                            <input type="number" step="0.01" id="porte_bruto" name="porte_bruto" value="<?php echo h($embarcacao['porte_bruto'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="estaleiro_nome">Estaleiro / Construtor</label>
                            <input type="text" id="estaleiro_nome" name="estaleiro_nome" maxlength="200" value="<?php echo h($embarcacao['estaleiro_nome'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="estaleiro_cpf_cnpj">CPF/CNPJ do Estaleiro</label>
                            <input type="text" id="estaleiro_cpf_cnpj" name="estaleiro_cpf_cnpj" maxlength="20" value="<?php echo h($embarcacao['estaleiro_cpf_cnpj'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="estaleiro_endereco">Endereço do Estaleiro / Construtor</label>
                        <textarea id="estaleiro_endereco" name="estaleiro_endereco" rows="2"><?php echo h($embarcacao['estaleiro_endereco'] ?? ''); ?></textarea>
                    </div>

                    <div class="alert alert-info" style="margin: 16px 0;">
                        <strong>Campos usados no PDF oficial do CNARQ.</strong>
                        Preencha estes dados para o Certificado Nacional de Arqueação sair completo.
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="cnarq_data_quilha">Data em que a quilha foi batida</label>
                            <input type="text" id="cnarq_data_quilha" name="cnarq_data_quilha" maxlength="50" placeholder="Ex: 2026 ou 09/02/2026" value="<?php echo h($embarcacao['cnarq_data_quilha'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="cnarq_calado_moldado_m">Calado Moldado (m)</label>
                            <input type="number" step="0.001" id="cnarq_calado_moldado_m" name="cnarq_calado_moldado_m" value="<?php echo h($embarcacao['cnarq_calado_moldado_m'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="cnarq_data_local_arqueacao_original">Data e local da arqueação original</label>
                            <input type="text" id="cnarq_data_local_arqueacao_original" name="cnarq_data_local_arqueacao_original" maxlength="200" placeholder="Ex: Belém - PA 08 de fevereiro de 2026" value="<?php echo h($embarcacao['cnarq_data_local_arqueacao_original'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="cnarq_data_local_ultima_rearqueacao">Data e local da última rearqueação</label>
                            <input type="text" id="cnarq_data_local_ultima_rearqueacao" name="cnarq_data_local_ultima_rearqueacao" maxlength="200" placeholder="Ex: x-x-x-x-x-x" value="<?php echo h($embarcacao['cnarq_data_local_ultima_rearqueacao'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="cnarq_espacos_incluidos_ab">Espaços incluídos na Arqueação Bruta</label>
                            <textarea id="cnarq_espacos_incluidos_ab" name="cnarq_espacos_incluidos_ab" rows="4" placeholder="Um por linha: Nome do espaço | Local | Comp."><?php echo h($embarcacao['cnarq_espacos_incluidos_ab'] ?? ''); ?></textarea>
                            <small class="text-muted">Exemplo: Porão de carga | Proa | 12,50</small>
                        </div>
                        <div class="form-group">
                            <label for="cnarq_espacos_incluidos_al">Espaços incluídos na Arqueação Líquida</label>
                            <textarea id="cnarq_espacos_incluidos_al" name="cnarq_espacos_incluidos_al" rows="4" placeholder="Um por linha: Nome do espaço | Local | Comp."><?php echo h($embarcacao['cnarq_espacos_incluidos_al'] ?? ''); ?></textarea>
                            <small class="text-muted">Pode ficar vazio se não houver discriminação.</small>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="cnarq_espacos_excluidos_m3">Espaços excluídos (m³)</label>
                            <input type="number" step="0.01" id="cnarq_espacos_excluidos_m3" name="cnarq_espacos_excluidos_m3" value="<?php echo h($embarcacao['cnarq_espacos_excluidos_m3'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- TAB: BORDA LIVRE -->
                <div id="tab-bordalivre" class="tab-pane">
                    <div class="alert alert-info" style="margin-bottom: 16px;">
                        <strong>Campos usados no PDF oficial do CNBL.</strong>
                        Estes campos alimentam diretamente o Certificado Nacional de Borda Livre.
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="cnbl_tipo_embarcacao">Tipo de Embarcação no CNBL (A/B/C/D/E)</label>
                            <select id="cnbl_tipo_embarcacao" name="cnbl_tipo_embarcacao">
                                <?php echo renderSelectOptions(['A', 'B', 'C', 'D', 'E'], $embarcacao['cnbl_tipo_embarcacao'] ?? ''); ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="cnbl_area_navegacao">Área de Navegação Interior no CNBL</label>
                            <select id="cnbl_area_navegacao" name="cnbl_area_navegacao">
                                <?php echo renderSelectOptions(['Área 1', 'Área 2'], $embarcacao['cnbl_area_navegacao'] ?? ''); ?>
                            </select>
                        </div>
                    </div>

                    <hr>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="borda_livre_mm">Borda Livre cadastrada (auxiliar)</label>
                            <input type="number" id="borda_livre_mm" name="borda_livre_mm" value="<?php echo h($embarcacao['borda_livre_mm'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="borda_livre_tipo">Tipo de Borda Livre (auxiliar)</label>
                            <select id="borda_livre_tipo" name="borda_livre_tipo">
                                <?php echo renderSelectOptions($tipos_borda_livre, $embarcacao['borda_livre_tipo'] ?? ''); ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="calado_maximo_m">Calado Máximo (auxiliar)</label>
                            <input type="number" step="0.01" id="calado_maximo_m" name="calado_maximo_m" value="<?php echo h($embarcacao['calado_maximo_m'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="aresta_superior_linha_conves">Aresta Superior da Linha do Convés (mm)</label>
                            <input type="text" inputmode="numeric" id="aresta_superior_linha_conves" name="aresta_superior_linha_conves" maxlength="50" value="<?php echo h($embarcacao['aresta_superior_linha_conves'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="centro_disco_situado">Distância até Centro do Disco (mm)</label>
                            <input type="text" inputmode="numeric" id="centro_disco_situado" name="centro_disco_situado" maxlength="50" value="<?php echo h($embarcacao['centro_disco_situado'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="acrescimo_agua_salgada">Acréscimo para Água Salgada (mm)</label>
                            <input type="text" inputmode="numeric" id="acrescimo_agua_salgada" name="acrescimo_agua_salgada" maxlength="50" value="<?php echo h($embarcacao['acrescimo_agua_salgada'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="dist_linha_conves_bico_proa">Centro do Disco até Bico de Proa (mm)</label>
                            <input type="text" inputmode="numeric" id="dist_linha_conves_bico_proa" name="dist_linha_conves_bico_proa" maxlength="50" value="<?php echo h($embarcacao['dist_linha_conves_bico_proa'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="dist_linha_conves_abaixo_disco">Linha do Convés abaixo do Disco (auxiliar)</label>
                            <input type="text" inputmode="numeric" id="dist_linha_conves_abaixo_disco" name="dist_linha_conves_abaixo_disco" maxlength="50" value="<?php echo h($embarcacao['dist_linha_conves_abaixo_disco'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="marca_linha_carga_area1">Dist. até Marca Linha Área 1 (mm)</label>
                            <input type="text" inputmode="numeric" id="marca_linha_carga_area1" name="marca_linha_carga_area1" value="<?php echo h($embarcacao['marca_linha_carga_area1'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="marca_linha_carga_area2">Dist. até Marca Linha Área 2 (mm)</label>
                            <input type="text" inputmode="numeric" id="marca_linha_carga_area2" name="marca_linha_carga_area2" value="<?php echo h($embarcacao['marca_linha_carga_area2'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <?php if ($isEdicao): ?>
                <!-- Info de data -->
                <div class="grid-2" style="margin-top: 20px; border-top: 1px solid var(--cor-borda); padding-top: 15px;">
                    <div class="form-group">
                        <label class="text-muted" style="font-size: 0.8rem;">
                            <i class="fas fa-calendar-plus"></i> Criado em: <?php echo formatarDataCompleta($embarcacao['criado_em'] ?? ''); ?>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="text-muted" style="font-size: 0.8rem;">
                            <i class="fas fa-calendar-check"></i> Atualizado: <?php echo formatarDataCompleta($embarcacao['atualizado_em'] ?? ''); ?>
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Botoes -->
                <div class="d-flex gap-2" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $isEdicao ? 'Atualizar' : 'Criar Embarcação'; ?>
                    </button>
                    <a href="<?php echo APP_URL; ?>embarcacoes" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openTab(tabId, element) {
    // Esconder todos os painéis
    document.querySelectorAll('.tab-pane').forEach(el => {
        el.classList.remove('active');
    });
    // Remover classe ativa das abas
    document.querySelectorAll('.tab-item').forEach(el => {
        el.classList.remove('active');
    });
    
    // Mostrar painel selecionado
    const targetPane = document.getElementById(tabId);
    if (targetPane) targetPane.classList.add('active');

    // Adicionar classe ativa na aba correspondente
    if (!element) {
        element = document.querySelector(`.tab-item[data-tab="${tabId}"]`) ||
                  document.querySelector(`.tab-item[onclick*="${tabId}"]`);
    }
    if (element) element.classList.add('active');

    const inp = document.getElementById('input_aba_ativa_emb');
    if (inp) inp.value = tabId;

    try {
        sessionStorage.setItem('erp_aba_embarcacao', tabId);
    } catch (e) {}

    const url = new URL(window.location);
    url.searchParams.set('aba', tabId);
    window.history.replaceState({}, '', url);
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    let targetAba = urlParams.get('aba');
    if (!targetAba) {
        try {
            targetAba = sessionStorage.getItem('erp_aba_embarcacao');
        } catch (e) {}
    }
    if (targetAba && document.getElementById(targetAba)) {
        openTab(targetAba);
    }
});

function atualizarCamposPropulsao() {
    const possuiPropulsao = document.getElementById('possui_propulsao');
    const campos = ['fabricante_motor', 'modelo_motor', 'numero_motor', 'potencia_kw']
        .map(id => document.getElementById(id))
        .filter(Boolean);
    if (!possuiPropulsao) return;

    const comPropulsao = possuiPropulsao.value === '1';
    const semPropulsao = possuiPropulsao.value === '0';

    campos.forEach(campo => {
        campo.required = comPropulsao;
        campo.disabled = semPropulsao;
        campo.toggleAttribute('disabled', semPropulsao);
        campo.readOnly = false;
        campo.setAttribute('aria-disabled', semPropulsao ? 'true' : 'false');

        const grupo = campo.closest('.form-group');
        if (grupo) {
            grupo.style.opacity = semPropulsao ? '0.55' : '1';
            grupo.style.pointerEvents = semPropulsao ? 'none' : 'auto';

            const label = grupo.querySelector('label');
            if (label) {
                let star = label.querySelector('.motor-star');
                if (comPropulsao) {
                    if (!star) {
                        star = document.createElement('span');
                        star.className = 'required-star motor-star';
                        star.textContent = ' *';
                        label.appendChild(star);
                    }
                } else if (star) {
                    star.remove();
                }
            }
        }

        if (semPropulsao) {
            campo.value = '';
            campo.classList.remove('field-invalid');
            campo.style.borderColor = '';
            if (campo.nextElementSibling && (campo.nextElementSibling.classList.contains('mensagem-erro') || campo.nextElementSibling.classList.contains('field-error'))) {
                campo.nextElementSibling.remove();
            }
        }
    });

    const ajuda = document.getElementById('ajuda_propulsao');
    if (ajuda) {
        ajuda.textContent = semPropulsao
            ? 'Embarcação sem propulsão: os dados do motor não se aplicam e serão apresentados assim no CSN.'
            : (comPropulsao
                ? 'Para embarcações com propulsão, fabricante, modelo, número do motor e potência são obrigatórios e alimentam o CSN.'
                : 'Selecione se a embarcação possui propulsão. Caso possua, os dados do motor serão obrigatórios.');
    }
}

function agendarAtualizacaoCamposPropulsao() {
    atualizarCamposPropulsao();
    setTimeout(atualizarCamposPropulsao, 0);
}

const campoPossuiPropulsao = document.getElementById('possui_propulsao');
if (campoPossuiPropulsao) {
    ['change', 'input', 'click'].forEach(evento => {
        campoPossuiPropulsao.addEventListener(evento, agendarAtualizacaoCamposPropulsao);
    });
}
atualizarCamposPropulsao();

function validarFormularioEmbarcacao(event) {
    const form = document.getElementById('formEmbarcacao');
    if (!form) return true;

    atualizarCamposPropulsao();

    // Limpar mensagens e estados anteriores
    form.querySelectorAll('.mensagem-erro, .field-error').forEach(el => el.remove());
    form.querySelectorAll('.field-invalid').forEach(el => {
        el.classList.remove('field-invalid');
        el.style.borderColor = '';
    });
    document.querySelectorAll('.tab-item').forEach(item => {
        item.classList.remove('has-error');
        const badge = item.querySelector('.tab-error-badge');
        if (badge) badge.style.display = 'none';
    });

    let valido = true;
    let primeiroInvalido = null;
    const errosPorAba = {};

    function registrarErro(campo, mensagem) {
        if (!campo) return;
        valido = false;
        campo.classList.add('field-invalid');
        campo.style.borderColor = '#e74c3c';

        if (!campo.nextElementSibling || (!campo.nextElementSibling.classList.contains('mensagem-erro') && !campo.nextElementSibling.classList.contains('field-error'))) {
            const span = document.createElement('span');
            span.className = 'mensagem-erro';
            span.textContent = mensagem;
            campo.parentElement.appendChild(span);
        }

        const tabPane = campo.closest('.tab-pane');
        if (tabPane) {
            errosPorAba[tabPane.id] = (errosPorAba[tabPane.id] || 0) + 1;
        }

        if (!primeiroInvalido) {
            primeiroInvalido = campo;
        }
    }

    // 1. Nome da embarcação (obrigatório, min 2 caracteres)
    const campoNome = document.getElementById('nome');
    if (campoNome) {
        const valNome = (campoNome.value || '').trim();
        if (!valNome) {
            registrarErro(campoNome, 'O nome da embarcação é obrigatório.');
        } else if (valNome.length < 2) {
            registrarErro(campoNome, 'O nome deve ter pelo menos 2 caracteres.');
        }
    }

    // 1.1 Tipo de Embarcação (obrigatório pela ISO 9001 / NORMAM)
    const campoTipo = document.getElementById('tipo_embarcacao_id');
    if (campoTipo) {
        if (!(campoTipo.value || '').trim()) {
            registrarErro(campoTipo, 'Selecione o tipo de embarcação (obrigatório pela ISO 9001 / NORMAM).');
        }
    }

    // 2. Possui propulsão (obrigatório: 0 ou 1)
    if (campoPossuiPropulsao) {
        const valProp = campoPossuiPropulsao.value;
        if (valProp === '' || (valProp !== '0' && valProp !== '1')) {
            registrarErro(campoPossuiPropulsao, 'A informação se possui propulsão é obrigatória.');
        } else if (valProp === '1') {
            // Motores obrigatórios quando possui propulsão
            const camposMotor = [
                { id: 'fabricante_motor', label: 'Informe o fabricante do motor.' },
                { id: 'modelo_motor', label: 'Informe o modelo do motor.' },
                { id: 'numero_motor', label: 'Informe o número do motor.' },
                { id: 'potencia_kw', label: 'Informe a potência propulsiva.' }
            ];
            camposMotor.forEach(item => {
                const c = document.getElementById(item.id);
                if (c && !(c.value || '').trim()) {
                    registrarErro(c, item.label);
                }
            });
        }
    }

    // 2.1 Requisitos de Dimensionamento Técnico (ISO 8.2 & NORMAM)
    const campoCompTotal = document.getElementById('comprimento_total');
    const campoCompCasco = document.getElementById('comprimento_casco');
    const compTotal = parseFloat(campoCompTotal ? campoCompTotal.value : 0) || 0;
    const compCasco = parseFloat(campoCompCasco ? campoCompCasco.value : 0) || 0;
    if (compTotal <= 0 && compCasco <= 0) {
        registrarErro(campoCompTotal || campoCompCasco, 'Informe o Comprimento Total ou do Casco (mínimo exigido pela ISO/NORMAM).');
    }

    const campoBocaMold = document.getElementById('boca_moldada');
    const campoBocaMax = document.getElementById('boca_maxima');
    const bocaMold = parseFloat(campoBocaMold ? campoBocaMold.value : 0) || 0;
    const bocaMax = parseFloat(campoBocaMax ? campoBocaMax.value : 0) || 0;
    if (bocaMold <= 0 && bocaMax <= 0) {
        registrarErro(campoBocaMold || campoBocaMax, 'Informe a Boca Moldada ou Máxima (mínimo exigido pela ISO/NORMAM).');
    }

    const campoPontal = document.getElementById('pontal_moldado');
    if (campoPontal) {
        const valPontal = parseFloat(campoPontal.value) || 0;
        if (valPontal <= 0) {
            registrarErro(campoPontal, 'Informe o Pontal Moldado (mínimo exigido pela ISO/NORMAM).');
        }
    }

    const campoAB = document.getElementById('arqueacao_bruta');
    if (campoAB) {
        const valAB = (campoAB.value || '').trim();
        if (!valAB || valAB === '0') {
            registrarErro(campoAB, 'A Arqueação Bruta (AB) é obrigatória pela NORMAM e ISO 9001.');
        }
    }

    // 3. Demais campos com atributo required ativo
    form.querySelectorAll('[required]').forEach(campo => {
        if (campo.disabled || campo.id === 'nome' || campo.id === 'possui_propulsao' ||
            campo.id === 'fabricante_motor' || campo.id === 'modelo_motor' ||
            campo.id === 'numero_motor' || campo.id === 'potencia_kw') {
            return;
        }
        if (!(campo.value || '').trim()) {
            registrarErro(campo, 'Este campo é obrigatório.');
        }
    });

    // 4. Validação de ano
    const campoAno = document.getElementById('ano');
    if (campoAno && campoAno.value) {
        const anoVal = parseInt(campoAno.value, 10);
        if (isNaN(anoVal) || anoVal < 1900 || anoVal > 2099) {
            registrarErro(campoAno, 'O ano deve estar entre 1900 e 2099.');
        }
    }

    // 5. Atualizar badges de erro nas abas
    Object.keys(errosPorAba).forEach(tabId => {
        const tabItem = document.querySelector(`.tab-item[data-tab="${tabId}"]`) ||
                        document.querySelector(`.tab-item[onclick*="${tabId}"]`);
        if (tabItem) {
            tabItem.classList.add('has-error');
            const badge = tabItem.querySelector('.tab-error-badge');
            if (badge) {
                badge.textContent = errosPorAba[tabId];
                badge.style.display = 'inline-block';
            }
        }
    });

    if (!valido && primeiroInvalido) {
        if (event && event.preventDefault) {
            event.preventDefault();
        }

        const tabPaneInvalido = primeiroInvalido.closest('.tab-pane');
        let nomeAba = 'na aba indicada';
        if (tabPaneInvalido) {
            openTab(tabPaneInvalido.id);
            const tabItem = document.querySelector(`.tab-item[data-tab="${tabPaneInvalido.id}"]`) ||
                            document.querySelector(`.tab-item[onclick*="${tabPaneInvalido.id}"]`);
            if (tabItem) {
                // Obter texto do primeiro nó de texto da aba
                nomeAba = '"' + tabItem.childNodes[0].textContent.trim() + '"';
            }
        }

        if (typeof mostrarMensagem === 'function') {
            mostrarMensagem('error', 'Por favor, preencha os campos obrigatórios destacados ' + (nomeAba ? 'na aba ' + nomeAba : '') + '.');
        }

        setTimeout(() => {
            primeiroInvalido.focus({ preventScroll: true });
            primeiroInvalido.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 80);

        return false;
    }

    return true;
}

// Limpeza de erros em tempo real
const formEl = document.getElementById('formEmbarcacao');
if (formEl) {
    formEl.addEventListener('input', function(e) {
        const target = e.target;
        if (target.classList.contains('field-invalid') && (target.value || '').trim()) {
            target.classList.remove('field-invalid');
            target.style.borderColor = '';
            if (target.nextElementSibling && (target.nextElementSibling.classList.contains('mensagem-erro') || target.nextElementSibling.classList.contains('field-error'))) {
                target.nextElementSibling.remove();
            }
            const tabPane = target.closest('.tab-pane');
            if (tabPane) {
                const restantes = tabPane.querySelectorAll('.field-invalid').length;
                const tabItem = document.querySelector(`.tab-item[data-tab="${tabPane.id}"]`) ||
                                document.querySelector(`.tab-item[onclick*="${tabPane.id}"]`);
                if (tabItem) {
                    const badge = tabItem.querySelector('.tab-error-badge');
                    if (restantes > 0) {
                        if (badge) badge.textContent = restantes;
                    } else {
                        tabItem.classList.remove('has-error');
                        if (badge) badge.style.display = 'none';
                    }
                }
            }
        }
    });

    formEl.addEventListener('change', function(e) {
        const target = e.target;
        if (target.classList.contains('field-invalid') && (target.value || '').trim()) {
            target.classList.remove('field-invalid');
            target.style.borderColor = '';
            if (target.nextElementSibling && (target.nextElementSibling.classList.contains('mensagem-erro') || target.nextElementSibling.classList.contains('field-error'))) {
                target.nextElementSibling.remove();
            }
            const tabPane = target.closest('.tab-pane');
            if (tabPane) {
                const restantes = tabPane.querySelectorAll('.field-invalid').length;
                const tabItem = document.querySelector(`.tab-item[data-tab="${tabPane.id}"]`) ||
                                document.querySelector(`.tab-item[onclick*="${tabPane.id}"]`);
                if (tabItem) {
                    const badge = tabItem.querySelector('.tab-error-badge');
                    if (restantes > 0) {
                        if (badge) badge.textContent = restantes;
                    } else {
                        tabItem.classList.remove('has-error');
                        if (badge) badge.style.display = 'none';
                    }
                }
            }
        }
    });
}

<?php if ($dadosTesteAtivos && !$isEdicao): ?>
const perfisDadosTeste = <?php echo json_encode($perfisTeste, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

function preencherFormularioComDadosTeste() {
    const seletor = document.getElementById('perfil_dados_teste');
    const perfil = perfisDadosTeste[seletor.value];
    const status = document.getElementById('status-dados-teste');

    if (!perfil) {
        status.textContent = 'Escolha um dos três modelos antes de preencher.';
        seletor.focus();
        return;
    }

    // Limpar mensagens e estados de erro anteriores
    const form = document.getElementById('formEmbarcacao');
    form.querySelectorAll('.field-invalid').forEach(el => {
        el.classList.remove('field-invalid');
        el.style.borderColor = '';
    });
    form.querySelectorAll('.mensagem-erro, .field-error').forEach(el => el.remove());
    document.querySelectorAll('.tab-item').forEach(el => {
        el.classList.remove('has-error');
        const badge = el.querySelector('.tab-error-badge');
        if (badge) badge.style.display = 'none';
    });

    const sufixo = new Date().toISOString().replace(/\D/g, '').slice(2, 14);
    const dados = {
        ...perfil.dados,
        nome: `${perfil.dados.nome} ${sufixo.slice(-6)}`,
        numero_inscricao: `TESTE${sufixo}`,
        indicativo_chamada: `T${sufixo.slice(-7)}`,
        numero_motor: perfil.dados.numero_motor ? `MOT-${sufixo}` : '',
        numero_casco: `CASCO-${sufixo}`,
    };

    Object.entries(dados).forEach(([nome, valor]) => {
        const campo = form.elements.namedItem(nome);
        if (!campo) return;
        campo.disabled = false;
        campo.value = valor;
        campo.dispatchEvent(new Event('input', { bubbles: true }));
        campo.dispatchEvent(new Event('change', { bubbles: true }));
    });

    atualizarCamposPropulsao();
    openTab('tab-gerais', document.querySelector('[onclick*="tab-gerais"]'));
    status.textContent = `${perfil.label} preenchida com dados fictícios. Revise ou clique em “Criar Embarcação”.`;
    document.getElementById('nome').focus();
}

document.getElementById('btn-preencher-dados-teste')
    .addEventListener('click', preencherFormularioComDadosTeste);
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
