<?php
/**
 * MODULO: CLIENTES
 * Arquivo: form.php - Formulário unificado de cadastro/edição de Clientes e Atores Navais
 * Suporta Armadores, Proprietários e Despachantes Marítimos
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
exigirAcesso('clientes');

$id = $_GET['id'] ?? null;
$editando = !empty($id);

// Perfil sugerido pela URL (ex: clientes/form?perfil=armador)
$perfilInicial = $_GET['perfil'] ?? 'proprietario';
if (!in_array($perfilInicial, ['armador', 'proprietario', 'despachante'], true)) {
    $perfilInicial = 'proprietario';
}

$cliente = [
    'id' => '',
    'nome' => '',
    'tipo_pessoa' => 'PF',
    'cpf_cnpj' => '',
    'perfil' => $perfilInicial,
    'telefone' => '',
    'email' => '',
    'endereco' => '',
    'status' => 'ATIVO',
    'tipo_recebimento' => '',
    'chave_pix' => '',
    'banco' => '',
    'agencia' => '',
    'conta' => '',
    'embarcacoes_ids' => [],
    'tipos_embarcacao_ids' => [],
];

// Se editando, carregar dados do banco
if ($editando) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id AND ativo = 1");
        $stmt->execute([':id' => $id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dados) {
            $cliente = array_merge($cliente, $dados);
            
            // Carregar embarcações ativas vinculadas
            $stmtEmb = $pdo->prepare("SELECT embarcacao_id FROM clientes_embarcacoes WHERE cliente_id = :cliente_id AND status = 'ATIVO'");
            $stmtEmb->execute([':cliente_id' => $id]);
            $cliente['embarcacoes_ids'] = array_column($stmtEmb->fetchAll(PDO::FETCH_ASSOC), 'embarcacao_id');

            // Carregar tipos de embarcação atendidos (específico despachante)
            $stmtTipos = $pdo->prepare("SELECT tipo_embarcacao_id FROM clientes_tipos_embarcacao WHERE cliente_id = :cliente_id");
            $stmtTipos->execute([':cliente_id' => $id]);
            $cliente['tipos_embarcacao_ids'] = array_column($stmtTipos->fetchAll(PDO::FETCH_ASSOC), 'tipo_embarcacao_id');
        } else {
            setMensagem('error', 'Registro não encontrado ou inativo.');
            redirecionar(APP_URL . 'clientes');
        }
    } catch (Exception $e) {
        error_log('Erro ao carregar cliente: ' . $e->getMessage());
        setMensagem('error', 'Erro ao carregar dados do cliente.');
        redirecionar(APP_URL . 'clientes');
    }
}

// Buscar embarcações ativas para seleção
try {
    $stmtEmb = $pdo->query("SELECT id, nome, registro FROM embarcacoes WHERE ativo = 1 ORDER BY criado_em DESC");
    $embarcacoes = $stmtEmb->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $embarcacoes = [];
}

// Buscar catálogo de tipos de embarcação (para despachantes)
try {
    $stmtTiposEmb = $pdo->query("SELECT id, nome FROM tipos_embarcacao WHERE ativo = 1 ORDER BY nome ASC");
    $tipos_embarcacao = $stmtTiposEmb->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tipos_embarcacao = [];
}

$nomePerfil = match($cliente['perfil']) {
    'armador' => 'Armador',
    'despachante' => 'Despachante',
    default => 'Proprietário'
};

$titulo_page = ($editando ? 'Editar ' : 'Novo ') . $nomePerfil . ' - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="form-container">
        <div class="form-header">
            <div>
                <h3>
                    <i class="fas fa-user-tie"></i> 
                    <?php echo $editando ? "Editar {$nomePerfil}" : "Novo Cadastro ({$nomePerfil})"; ?>
                </h3>
                <p class="text-muted" style="margin: 4px 0 0; font-size: 0.9rem;">
                    Cadastro unificado de clientes, operadores e agentes navais de acordo com as normas da Autoridade Marítima (DPC/NORMAM).
                </p>
            </div>
            <a href="<?php echo APP_URL; ?>clientes?perfil=<?php echo urlencode($cliente['perfil']); ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <form action="<?php echo APP_URL; ?>clientes/actions" method="POST" class="form-padrao">
            <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
            <input type="hidden" name="action" value="<?php echo $editando ? 'editar' : 'inserir'; ?>">
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?php echo h($cliente['id']); ?>">
            <?php endif; ?>

            <!-- 1. IDENTIFICAÇÃO E PERFIL NAVAL -->
            <div class="card mb-4" style="border: 1px solid var(--cor-borda); border-radius: 8px; padding: 20px; background: var(--cor-card-bg, #fff);">
                <h4 style="margin-top: 0; margin-bottom: 15px; color: var(--cor-primaria); font-size: 1.1rem; border-bottom: 1px solid var(--cor-borda); padding-bottom: 8px;">
                    <i class="fas fa-id-card"></i> 1. Identificação e Perfil Naval
                </h4>

                <div class="form-row">
                    <div class="form-group col-8">
                        <label for="nome">Nome Completo / Razão Social *</label>
                        <input type="text" id="nome" name="nome" required
                               value="<?php echo h($cliente['nome']); ?>"
                               placeholder="Ex: Navegação Rios da Amazônia Ltda ou João da Silva">
                        <small class="text-muted">Nome oficial constante na Receita Federal ou Capitania dos Portos.</small>
                    </div>

                    <div class="form-group col-4">
                        <label for="perfil">Perfil do Ator Naval *</label>
                        <select id="perfil" name="perfil" required onchange="aoMudarPerfil(this.value)">
                            <option value="proprietario" <?php echo $cliente['perfil'] === 'proprietario' ? 'selected' : ''; ?>>Proprietário Legal (TIE/TIEM)</option>
                            <option value="armador" <?php echo $cliente['perfil'] === 'armador' ? 'selected' : ''; ?>>Armador / Empresa de Navegação</option>
                            <option value="despachante" <?php echo $cliente['perfil'] === 'despachante' ? 'selected' : ''; ?>>Despachante Marítimo</option>
                        </select>
                        <small class="text-muted" id="ajuda_perfil">Define o papel operacional no ERP.</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="tipo_pessoa">Tipo de Pessoa *</label>
                        <select id="tipo_pessoa" name="tipo_pessoa" onchange="toggleCpfCnpj(true)">
                            <option value="PF" <?php echo $cliente['tipo_pessoa'] === 'PF' ? 'selected' : ''; ?>>Pessoa Física (PF)</option>
                            <option value="PJ" <?php echo $cliente['tipo_pessoa'] === 'PJ' ? 'selected' : ''; ?>>Pessoa Jurídica (PJ)</option>
                        </select>
                    </div>
                    <div class="form-group col-4">
                        <label for="cpf_cnpj">CPF / CNPJ</label>
                        <input type="text" id="cpf_cnpj" name="cpf_cnpj"
                               value="<?php echo h($cliente['cpf_cnpj']); ?>"
                               placeholder="Apenas números"
                               oninput="mascararCpfCnpj(this)">
                        <small class="text-muted">Validado com algoritmo oficial de dígitos verificadores.</small>
                    </div>
                    <div class="form-group col-4">
                        <label for="telefone">Telefone / WhatsApp</label>
                        <input type="text" id="telefone" name="telefone"
                               value="<?php echo h($cliente['telefone']); ?>"
                               placeholder="(91) 99999-9999"
                               oninput="mascararTelefone(this)">
                        <small class="text-muted">Utilizado para avisos e envio de relatórios/propostas.</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="email">E-mail Principal</label>
                        <input type="email" id="email" name="email" maxlength="150"
                               inputmode="email" autocomplete="email"
                               value="<?php echo h($cliente['email']); ?>"
                               placeholder="contato@empresa.com.br">
                        <small class="text-muted">Utilizado para envio de certificados e login do Portal do Cliente.</small>
                    </div>
                    <div class="form-group col-6">
                        <label for="endereco">Endereço Comercial / Residencial</label>
                        <input type="text" id="endereco" name="endereco"
                               value="<?php echo h($cliente['endereco']); ?>"
                               placeholder="Logradouro, número, bairro, cidade/UF">
                        <small class="text-muted">Endereço constante nos certificados navais.</small>
                    </div>
                </div>
            </div>

            <!-- 2. VÍNCULO DE EMBARCAÇÕES (FROTA / OPERAÇÃO) -->
            <div class="card mb-4" style="border: 1px solid var(--cor-borda); border-radius: 8px; padding: 20px; background: rgba(0, 123, 255, 0.03); border-left: 4px solid #007bff;">
                <h4 style="margin-top: 0; margin-bottom: 10px; color: #007bff; font-size: 1.1rem;">
                    <i class="fas fa-ship"></i> 2. Seleção e Vínculo de Embarcações
                </h4>
                <p class="text-muted" style="margin-bottom: 15px; font-size: 0.9rem;">
                    Selecione as embarcações pertencentes, operadas ou atendidas por este cadastro. Abaixo estão as 5 embarcações mais recentes e as já vinculadas. Use o campo de busca para encontrar qualquer barco da frota.
                </p>

                <div style="position: relative; max-width: 100%; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; background: var(--cor-input-bg, #fff); border: 2px solid var(--cor-borda); border-radius: 6px; padding: 0 10px;">
                        <i class="fas fa-search text-muted"></i>
                        <input type="text" id="buscaEmbarcacao" class="form-control" placeholder="Digite o nome ou número de inscrição da embarcação..." style="border: none; background: transparent; box-shadow: none; padding: 12px; font-size: 1rem; color: var(--cor-texto);">
                    </div>
                    <div id="resBuscaEmbarcacao" style="position: absolute; top: 100%; left: 0; right: 0; background: var(--cor-sidebar, #fff); border: 1px solid var(--cor-borda); border-radius: 0 0 6px 6px; box-shadow: 0 6px 16px rgba(0,0,0,0.25); z-index: 1000; display: none; max-height: 250px; overflow-y: auto;"></div>
                </div>

                <div id="listaEmbarcacoes" class="checkbox-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 10px;">
                    <?php
                    $recentes = [];
                    foreach ($embarcacoes as $emb) {
                        if (in_array($emb['id'], $cliente['embarcacoes_ids'], true)) {
                            $recentes[$emb['id']] = $emb;
                        }
                    }
                    $c = 0;
                    foreach ($embarcacoes as $emb) {
                        if (!isset($recentes[$emb['id']]) && $c < 5) {
                            $recentes[$emb['id']] = $emb;
                            $c++;
                        }
                    }
                    ?>
                    <?php if (empty($recentes)): ?>
                        <p class="text-muted">Nenhuma embarcação cadastrada no sistema. Cadastre uma embarcação primeiro no menu Embarcações.</p>
                    <?php else: ?>
                        <?php foreach ($recentes as $emb): ?>
                            <label class="checkbox-item emb-item" id="emb_<?php echo h($emb['id']); ?>" style="padding: 10px 14px; background: var(--cor-sidebar, #f8f9fa); border: 1px solid var(--cor-borda); border-radius: 6px; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: all 0.2s;">
                                <input type="checkbox" name="embarcacoes_ids[]" 
                                       value="<?php echo h($emb['id']); ?>"
                                       <?php echo in_array($emb['id'], $cliente['embarcacoes_ids'], true) ? 'checked' : ''; ?>
                                       style="width: 18px; height: 18px;">
                                <span style="font-weight: 500; font-size: 0.95rem;"><?php echo h($emb['nome']); ?> 
                                    <?php if ($emb['registro']): ?>
                                        <small class="text-muted" style="display: block; font-weight: normal; font-size: 0.8rem;">Inscrição: <?php echo h($emb['registro']); ?></small>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 3. TIPOS DE EMBARCAÇÃO QUE ATENDE (CONDICIONAL DESPACHANTE) -->
            <div id="secao_despachante_tipos" class="card mb-4" style="border: 1px solid var(--cor-borda); border-radius: 8px; padding: 20px; background: var(--cor-card-bg, #fff); <?php echo $cliente['perfil'] === 'despachante' ? '' : 'display: none;'; ?>">
                <h4 style="margin-top: 0; margin-bottom: 10px; color: var(--cor-primaria); font-size: 1.1rem; border-bottom: 1px solid var(--cor-borda); padding-bottom: 8px;">
                    <i class="fas fa-tags"></i> 3. Tipos de Embarcação que Atende
                </h4>
                <p class="text-muted" style="margin-bottom: 14px; font-size: 0.9rem;">
                    Marque os portes e tipos de embarcação em que este despachante atua com maior frequência (auxilia o setor comercial a direcionar ordens).
                </p>

                <?php if (empty($tipos_embarcacao)): ?>
                    <p class="text-muted">Nenhum tipo de embarcação cadastrado.</p>
                <?php else: ?>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php foreach ($tipos_embarcacao as $tipo): ?>
                            <?php $marcado = in_array($tipo['id'], $cliente['tipos_embarcacao_ids'] ?? [], true); ?>
                            <label class="tipo-embarcacao-chip<?php echo $marcado ? ' is-selected' : ''; ?>" style="padding: 8px 14px; border: 1px solid var(--cor-borda); border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; background: var(--cor-sidebar, #f8f9fa); user-select: none;">
                                <input type="checkbox"
                                       name="tipos_embarcacao[]"
                                       value="<?php echo h($tipo['id']); ?>"
                                       <?php echo $marcado ? 'checked' : ''; ?>
                                       onchange="atualizarTipoChip(this)"
                                       style="display: none;">
                                <i class="fas <?php echo $marcado ? 'fa-check-circle text-success' : 'fa-circle text-muted'; ?>"></i>
                                <span><?php echo h($tipo['nome']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 4. DADOS BANCÁRIOS E RECEBIMENTO FINANCEIRO -->
            <div id="secao_financeira" class="card mb-4" style="border: 1px solid var(--cor-borda); border-radius: 8px; padding: 20px; background: var(--cor-card-bg, #fff);">
                <h4 style="margin-top: 0; margin-bottom: 10px; color: var(--cor-primaria); font-size: 1.1rem; border-bottom: 1px solid var(--cor-borda); padding-bottom: 8px;">
                    <i class="fas fa-money-bill-wave"></i> 4. Dados Bancários / PIX
                </h4>
                <p class="text-muted" style="margin-bottom: 15px; font-size: 0.9rem;">
                    Opcional. Utilizado para reembolsos, repasses de comissão para despachantes ou devoluções de taxas portuárias.
                </p>

                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="tipo_recebimento">Forma de Recebimento</label>
                        <select id="tipo_recebimento" name="tipo_recebimento" onchange="toggleFinanceiro()">
                            <option value="">Não informado</option>
                            <option value="pix" <?php echo ($cliente['tipo_recebimento'] ?? '') === 'pix' ? 'selected' : ''; ?>>Chave PIX</option>
                            <option value="cc" <?php echo ($cliente['tipo_recebimento'] ?? '') === 'cc' ? 'selected' : ''; ?>>Conta Corrente / Bancária</option>
                        </select>
                    </div>

                    <div class="form-group col-8" id="bloco_pix" style="<?php echo ($cliente['tipo_recebimento'] ?? '') === 'pix' ? '' : 'display: none;'; ?>">
                        <label for="chave_pix">Chave PIX</label>
                        <input type="text" id="chave_pix" name="chave_pix"
                               value="<?php echo h($cliente['chave_pix'] ?? ''); ?>"
                               placeholder="CPF, CNPJ, E-mail, Celular ou Chave Aleatória">
                    </div>
                </div>

                <div class="form-row" id="bloco_cc" style="<?php echo ($cliente['tipo_recebimento'] ?? '') === 'cc' ? '' : 'display: none;'; ?>">
                    <div class="form-group col-4">
                        <label for="banco">Banco / Instituição</label>
                        <input type="text" id="banco" name="banco"
                               value="<?php echo h($cliente['banco'] ?? ''); ?>"
                               placeholder="Ex: Banco do Brasil, Bradesco, Nubank">
                    </div>
                    <div class="form-group col-4">
                        <label for="agencia">Agência</label>
                        <input type="text" id="agencia" name="agencia"
                               value="<?php echo h($cliente['agencia'] ?? ''); ?>"
                               placeholder="Ex: 1234-5">
                    </div>
                    <div class="form-group col-4">
                        <label for="conta">Número da Conta</label>
                        <input type="text" id="conta" name="conta"
                               value="<?php echo h($cliente['conta'] ?? ''); ?>"
                               placeholder="Ex: 98765-4">
                    </div>
                </div>
            </div>

            <!-- BOTÕES DE AÇÃO -->
            <div class="form-actions" style="display: flex; gap: 10px; align-items: center;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> 
                    <?php echo $editando ? 'Salvar Alterações' : 'Concluir Cadastro'; ?>
                </button>
                <a href="<?php echo APP_URL; ?>clientes?perfil=<?php echo urlencode($cliente['perfil']); ?>" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function aoMudarPerfil(perfil) {
    const secaoDespachante = document.getElementById('secao_despachante_tipos');
    const ajudaPerfil = document.getElementById('ajuda_perfil');

    if (perfil === 'despachante') {
        secaoDespachante.style.display = 'block';
        ajudaPerfil.textContent = 'Despachante Marítimo ou preposto credenciado perante a Capitania.';
    } else if (perfil === 'armador') {
        secaoDespachante.style.display = 'none';
        ajudaPerfil.textContent = 'Armador / Operador comercial e operacional da frota.';
    } else {
        secaoDespachante.style.display = 'none';
        ajudaPerfil.textContent = 'Proprietário legal registrado no Título de Inscrição da Embarcação (TIE).';
    }
}

function toggleCpfCnpj(limparValor = false) {
    const tipo = document.getElementById('tipo_pessoa').value;
    const input = document.getElementById('cpf_cnpj');
    input.placeholder = tipo === 'PF' ? 'CPF (apenas números)' : 'CNPJ (apenas números)';
    if (limparValor) {
        input.value = '';
    } else {
        mascararCpfCnpj(input);
    }
}

function mascararCpfCnpj(input) {
    let valor = input.value.replace(/\D/g, '');
    const tipo = document.getElementById('tipo_pessoa').value;
    
    if (tipo === 'PF') {
        if (valor.length > 11) valor = valor.slice(0, 11);
        if (valor.length > 9) {
            valor = valor.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
        } else if (valor.length > 6) {
            valor = valor.replace(/^(\d{3})(\d{3})(\d{1,3})$/, '$1.$2.$3');
        } else if (valor.length > 3) {
            valor = valor.replace(/^(\d{3})(\d{1,3})$/, '$1.$2');
        }
    } else {
        if (valor.length > 14) valor = valor.slice(0, 14);
        if (valor.length > 12) {
            valor = valor.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
        } else if (valor.length > 8) {
            valor = valor.replace(/^(\d{2})(\d{3})(\d{3})(\d{1,4})$/, '$1.$2.$3/$4');
        } else if (valor.length > 5) {
            valor = valor.replace(/^(\d{2})(\d{3})(\d{1,3})$/, '$1.$2.$3');
        } else if (valor.length > 2) {
            valor = valor.replace(/^(\d{2})(\d{1,3})$/, '$1.$2');
        }
    }
    input.value = valor;
}

function mascararTelefone(input) {
    let valor = input.value.replace(/\D/g, '');
    if (valor.length > 11) valor = valor.slice(0, 11);
    if (valor.length > 6) {
        valor = valor.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
    } else if (valor.length > 2) {
        valor = valor.replace(/^(\d{2})(\d{1,4})$/, '($1) $2');
    } else if (valor.length > 0) {
        valor = valor.replace(/^(\d{1,2})$/, '($1');
    }
    input.value = valor;
}

function toggleFinanceiro() {
    const tipo = document.getElementById('tipo_recebimento').value;
    const blocoPix = document.getElementById('bloco_pix');
    const blocoCc = document.getElementById('bloco_cc');
    
    blocoPix.style.display = (tipo === 'pix') ? 'block' : 'none';
    blocoCc.style.display = (tipo === 'cc') ? 'flex' : 'none';
}

function atualizarTipoChip(checkbox) {
    const label = checkbox.closest('label');
    const icone = label.querySelector('i');
    if (checkbox.checked) {
        label.classList.add('is-selected');
        label.style.borderColor = 'var(--cor-primaria)';
        icone.className = 'fas fa-check-circle text-success';
    } else {
        label.classList.remove('is-selected');
        label.style.borderColor = 'var(--cor-borda)';
        icone.className = 'fas fa-circle text-muted';
    }
}

function aoMudarPerfil(perfil) {
    const despachanteSecao = document.getElementById('secao_despachante_tipos');
    const ajudaPerfil = document.getElementById('ajuda_perfil');
    if (despachanteSecao) {
        despachanteSecao.style.display = (perfil === 'despachante') ? 'block' : 'none';
    }
    if (ajudaPerfil) {
        if (perfil === 'proprietario') {
            ajudaPerfil.innerHTML = '<i class="fas fa-circle-info text-primary"></i> <strong>Proprietário:</strong> Titular legal do registro TIE/TIEM na Capitania dos Portos (NORMAM-201/202).';
        } else if (perfil === 'armador') {
            ajudaPerfil.innerHTML = '<i class="fas fa-circle-info text-primary"></i> <strong>Armador:</strong> Operador náutico comercial responsável pelas vistorias estatutárias e tripulação.';
        } else if (perfil === 'despachante') {
            ajudaPerfil.innerHTML = '<i class="fas fa-circle-info text-primary"></i> <strong>Despachante:</strong> Representante legal com procuração na Capitania (NPCP) para protocolos e trâmites.';
        }
    }
}

// Busca assíncrona de embarcações via AJAX
document.getElementById('buscaEmbarcacao').addEventListener('input', function() {
    const query = this.value.trim();
    const resDiv = document.getElementById('resBuscaEmbarcacao');
    if (query.length < 2) {
        resDiv.style.display = 'none';
        return;
    }
    
    fetch('<?php echo APP_URL; ?>ajax/busca_embarcacoes.php?q=' + encodeURIComponent(query))
    .then(r => r.json())
    .then(data => {
        resDiv.innerHTML = '';
        if (!data || data.length === 0) {
            resDiv.innerHTML = '<div style="padding: 12px; color: var(--cor-texto-mutado);">Nenhuma embarcação encontrada com este termo.</div>';
        } else {
            data.forEach(item => {
                const div = document.createElement('div');
                div.style.padding = '10px 14px';
                div.style.borderBottom = '1px solid var(--cor-borda)';
                div.style.cursor = 'pointer';
                div.innerHTML = `<strong>${escapeHtml(item.nome)}</strong><br><small class="text-muted">Inscrição: ${escapeHtml(item.registro || 'Não informado')}</small>`;
                div.onmouseover = () => div.style.background = 'rgba(0,123,255,0.08)';
                div.onmouseout = () => div.style.background = 'transparent';
                div.onclick = function() {
                    const lista = document.getElementById('listaEmbarcacoes');
                    if (!document.getElementById('emb_' + item.id)) {
                        lista.insertAdjacentHTML('afterbegin', `
                            <label class="checkbox-item emb-item" id="emb_${item.id}" style="padding: 10px 14px; background: var(--cor-sidebar, #f8f9fa); border: 1px solid var(--cor-borda); border-radius: 6px; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: all 0.2s;">
                                <input type="checkbox" name="embarcacoes_ids[]" value="${item.id}" checked style="width: 18px; height: 18px;">
                                <span style="font-weight: 500; font-size: 0.95rem;">${escapeHtml(item.nome)} 
                                    <small class="text-muted" style="display: block; font-weight: normal; font-size: 0.8rem;">Inscrição: ${escapeHtml(item.registro || 'Não informado')}</small>
                                </span>
                            </label>
                        `);
                    } else {
                        document.querySelector('#emb_' + item.id + ' input[type="checkbox"]').checked = true;
                    }
                    document.getElementById('buscaEmbarcacao').value = '';
                    resDiv.style.display = 'none';
                };
                resDiv.appendChild(div);
            });
        }
        resDiv.style.display = 'block';
    })
    .catch(err => {
        console.error('Erro ao buscar embarcações:', err);
    });
});

document.addEventListener('click', function(e) {
    if (!document.getElementById('buscaEmbarcacao').contains(e.target) && !document.getElementById('resBuscaEmbarcacao').contains(e.target)) {
        document.getElementById('resBuscaEmbarcacao').style.display = 'none';
    }
});

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
    toggleCpfCnpj(false);
    aoMudarPerfil(document.getElementById('perfil').value);
    toggleFinanceiro();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
