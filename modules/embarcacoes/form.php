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
if (!in_array($cargo, ['ADMIN', 'VISTORIADOR'])) {
    setMensagem('error', 'Acesso negado. Voce nao tem permissao para acessar este modulo.');
    redirecionar(APP_URL . 'dashboard');
}

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
}
.tab-item.active {
    color: #fff;
    background: var(--cor-destaque);
    border-radius: 6px 6px 0 0;
    border-bottom-color: var(--cor-destaque);
}
.tab-item:hover:not(.active) {
    color: var(--cor-texto);
    background: rgba(0,0,0,0.02);
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
            <form method="POST"
                  action="<?php echo APP_URL; ?>embarcacoes/actions?action=salvar" 
                  id="formEmbarcacao"
                  autocomplete="off"
                  onsubmit="return validarFormulario('formEmbarcacao')">
                
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="_submission_token" value="<?php echo h(bin2hex(random_bytes(24))); ?>">
                <input type="hidden" name="id" value="<?php echo h($embarcacao['id'] ?? ''); ?>">

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
                    <li class="tab-item active" onclick="openTab('tab-gerais', this)">Dados Gerais</li>
                    <li class="tab-item" onclick="openTab('tab-tecnicos', this)">Dados Técnicos e Propulsão</li>
                    <li class="tab-item" onclick="openTab('tab-dimensoes', this)">Arqueação e Dimensões</li>
                    <li class="tab-item" onclick="openTab('tab-bordalivre', this)">Linha de Carga (CNBL)</li>
                </ul>

                <!-- TAB: DADOS GERAIS -->
                <div id="tab-gerais" class="tab-pane active">
                    <?php if ($isEdicao): ?>
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:18px;padding:12px;border:1px solid var(--cor-borda);border-radius:10px;background:rgba(9,155,112,.05)">
                        <img src="<?= h($embarcacao['foto_url'] ?: APP_URL . 'assets/img/portal-hero-ship.png') ?>" alt="Foto oficial da embarcação" style="width:104px;height:78px;object-fit:cover;border-radius:9px;border:1px solid var(--cor-borda)">
                        <span><strong style="display:block">Foto oficial da embarcação</strong><small class="text-muted"><?= $embarcacao['foto_url'] ? 'Capturada pelo vistoriador no Amazon Campo.' : 'Ainda não capturada. O vistoriador poderá adicionar pelo Amazon Campo.' ?></small></span>
                    </div>
                    <?php endif; ?>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="nome"><i class="fas fa-ship"></i> Nome da embarcacao *</label>
                            <input type="text" id="nome" name="nome" required maxlength="150" value="<?php echo h($embarcacao['nome'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="tipo_embarcacao_id"><i class="fas fa-tags"></i> Tipo de Embarcação</label>
                            <select id="tipo_embarcacao_id" name="tipo_embarcacao_id">
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
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="comprimento_total">Comprimento Total (m)</label>
                            <input type="number" step="0.01" min="0" id="comprimento_total" name="comprimento_total" value="<?php echo h($embarcacao['comprimento_total'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="comprimento_casco">Comprimento Casco (m)</label>
                            <input type="number" step="0.01" min="0" id="comprimento_casco" name="comprimento_casco" value="<?php echo h($embarcacao['comprimento_casco'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="comprimento_lpp">Comprimento LPP (m)</label>
                            <input type="number" step="0.01" min="0" id="comprimento_lpp" name="comprimento_lpp" value="<?php echo h($embarcacao['comprimento_lpp'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="pontal_moldado">Pontal Moldado (m)</label>
                            <input type="number" step="0.01" min="0" id="pontal_moldado" name="pontal_moldado" value="<?php echo h($embarcacao['pontal_moldado'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="boca_moldada">Boca Moldada (m)</label>
                            <input type="number" step="0.01" min="0" id="boca_moldada" name="boca_moldada" value="<?php echo h($embarcacao['boca_moldada'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="boca_maxima">Boca Máxima (m)</label>
                            <input type="number" step="0.01" min="0" id="boca_maxima" name="boca_maxima" value="<?php echo h($embarcacao['boca_maxima'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="arqueacao_bruta">Arqueação Bruta (AB)</label>
                            <input type="text" id="arqueacao_bruta" name="arqueacao_bruta" maxlength="50" value="<?php echo h($embarcacao['arqueacao_bruta'] ?? ''); ?>">
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
    document.getElementById(tabId).classList.add('active');
    // Adicionar classe ativa na aba
    element.classList.add('active');
}

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
        }

        if (semPropulsao) campo.value = '';
    });

    const ajuda = document.getElementById('ajuda_propulsao');
    ajuda.textContent = semPropulsao
        ? 'Embarcação sem propulsão: os dados do motor não se aplicam e serão apresentados assim no CSN.'
        : 'Para embarcações com propulsão, fabricante, modelo, número do motor e potência são obrigatórios e alimentam o CSN.';
}

function agendarAtualizacaoCamposPropulsao() {
    atualizarCamposPropulsao();
    // Alguns navegadores atualizam o valor visual do select no fim do clique.
    setTimeout(atualizarCamposPropulsao, 0);
}

const campoPossuiPropulsao = document.getElementById('possui_propulsao');
['change', 'input', 'click'].forEach(evento => {
    campoPossuiPropulsao.addEventListener(evento, agendarAtualizacaoCamposPropulsao);
});
atualizarCamposPropulsao();

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

    const sufixo = new Date().toISOString().replace(/\D/g, '').slice(2, 14);
    const dados = {
        ...perfil.dados,
        nome: `${perfil.dados.nome} ${sufixo.slice(-6)}`,
        numero_inscricao: `TESTE${sufixo}`,
        indicativo_chamada: `T${sufixo.slice(-7)}`,
        numero_motor: perfil.dados.numero_motor ? `MOT-${sufixo}` : '',
        numero_casco: `CASCO-${sufixo}`,
    };
    const form = document.getElementById('formEmbarcacao');

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
