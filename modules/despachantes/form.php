<?php
/**
 * MODULO: CLIENTES
 * Arquivo: form.php - Formulario cadastro/edicao de despachante
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
$cargo = getCargo();
if (!in_array($cargo, ['ADMIN', 'VISTORIADOR'])) {
    setMensagem('error', 'Acesso negado.');
    redirecionar(APP_URL . 'dashboard');
}

$id = $_GET['id'] ?? null;
$editando = !empty($id);

$despachante = [
    'id' => '',
    'nome' => '',
    'tipo_pessoa' => 'PF',
    'cpf_cnpj' => '',
    'perfil' => 'despachante',
    'telefone' => '',
    'email' => '',
    'endereco' => '',
    'status' => 'ATIVO',
    'embarcacoes_ids' => [],
    'tipos_embarcacao_ids' => [],
];

// Se editando, carregar dados do despachante
if ($editando) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id AND perfil = 'despachante' AND status = 'ATIVO'");
        $stmt->execute([':id' => $id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dados) {
            $despachante = array_merge($despachante, $dados);
            // Carregar embarcacoes vinculadas
            $stmtEmb = $pdo->prepare("SELECT embarcacao_id FROM clientes_embarcacoes WHERE cliente_id = :cliente_id AND status = 'ATIVO'");
            $stmtEmb->execute([':cliente_id' => $id]);
            $despachante['embarcacoes_ids'] = array_column($stmtEmb->fetchAll(PDO::FETCH_ASSOC), 'embarcacao_id');

            $stmtTipos = $pdo->prepare("SELECT tipo_embarcacao_id FROM clientes_tipos_embarcacao WHERE cliente_id = :cliente_id");
            $stmtTipos->execute([':cliente_id' => $id]);
            $despachante['tipos_embarcacao_ids'] = array_column($stmtTipos->fetchAll(PDO::FETCH_ASSOC), 'tipo_embarcacao_id');
        } else {
            setMensagem('error', 'Despachante nao encontrado.');
            redirecionar(APP_URL . 'despachantes');
        }
    } catch (Exception $e) {
        error_log('Erro ao carregar despachante: ' . $e->getMessage());
        setMensagem('error', 'Erro ao carregar dados do despachante.');
        redirecionar(APP_URL . 'despachantes');
    }
}

// Buscar tipos de embarcacao ativos para indicar a atuacao do despachante
try {
    $stmtTipos = $pdo->query("SELECT id, nome FROM tipos_embarcacao WHERE ativo = 1 ORDER BY nome ASC");
    $tipos_embarcacao = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tipos_embarcacao = [];
}

try {
    $stmtEmbarcacoes = $pdo->query("SELECT id, nome, COALESCE(NULLIF(registro,''), numero_inscricao) AS registro FROM embarcacoes WHERE ativo = 1 ORDER BY nome ASC");
    $embarcacoes = $stmtEmbarcacoes->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $embarcacoes = [];
}

$titulo_page = ($editando ? 'Editar' : 'Novo') . ' Despachante - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="form-container">
        <div class="form-header">
            <h3>
                <i class="fas fa-user-tie"></i> 
                <?php echo $editando ? 'Editar Despachante' : 'Novo Despachante'; ?>
            </h3>
            <a href="<?php echo APP_URL; ?>despachantes" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <form action="<?php echo APP_URL; ?>despachantes/actions" method="POST" class="form-padrao">
            <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
            <input type="hidden" name="action" value="<?php echo $editando ? 'editar' : 'inserir'; ?>">
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?php echo h($despachante['id']); ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group col-12">
                    <label for="nome">Nome / Razão Social *</label>
                    <input type="text" id="nome" name="nome" required
                           value="<?php echo h($despachante['nome']); ?>"
                           placeholder="Nome completo ou razao social">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-4">
                    <label for="tipo_pessoa">Tipo *</label>
                    <select id="tipo_pessoa" name="tipo_pessoa" onchange="toggleCpfCnpj(true)">
                        <option value="PF" <?php echo $despachante['tipo_pessoa'] === 'PF' ? 'selected' : ''; ?>>Pessoa Física</option>
                        <option value="PJ" <?php echo $despachante['tipo_pessoa'] === 'PJ' ? 'selected' : ''; ?>>Pessoa Jurídica</option>
                    </select>
                </div>
                <div class="form-group col-4">
                    <label for="cpf_cnpj">CPF / CNPJ</label>
                    <input type="text" id="cpf_cnpj" name="cpf_cnpj"
                           value="<?php echo h($despachante['cpf_cnpj']); ?>"
                           placeholder="Apenas numeros"
                           oninput="mascararCpfCnpj(this)">
                </div>
                <div class="form-group col-4">
                    <label for="perfil">Perfil *</label>
                    <input type="hidden" name="perfil" value="despachante">
<select id="perfil_show" name="perfil_show" disabled required>
                        <option value="armador" <?php echo $despachante['perfil'] === 'armador' ? 'selected' : ''; ?>>Armador</option>
                        <option value="proprietario" <?php echo $despachante['perfil'] === 'proprietario' ? 'selected' : ''; ?>>Proprietário</option>
                        <option value="despachante" <?php echo $despachante['perfil'] === 'despachante' ? 'selected' : ''; ?>>Despachante</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-4">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone"
                           value="<?php echo h($despachante['telefone']); ?>"
                           placeholder="(91) 99999-9999"
                           oninput="mascararTelefone(this)">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-12">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" maxlength="150"
                           inputmode="email" autocomplete="email"
                           value="<?php echo h($despachante['email']); ?>"
                           placeholder="despachante@empresa.com.br">
                    <small class="text-muted">Opcional.</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-12">
                    <label for="endereco">Endereço</label>
                    <textarea id="endereco" name="endereco" rows="2"
                              placeholder="Logradouro, numero, bairro, cidade/UF"><?php echo h($despachante['endereco']); ?></textarea>
                </div>
            </div>

            <div class="form-row mt-4">
                <div class="form-group col-12">
                    <h4 style="border-bottom: 1px solid var(--cor-borda); padding-bottom: 10px;">
                        <i class="fas fa-link"></i> Embarcações vinculadas ao portal
                    </h4>
                    <p class="text-muted" style="margin: 8px 0 14px;">Somente vínculos ativos dão acesso aos documentos no Portal do Cliente. Desmarcar preserva o histórico e revoga o acesso.</p>
                    <div class="tipo-embarcacao-grid">
                        <?php foreach ($embarcacoes as $emb): ?>
                            <?php $marcado = in_array($emb['id'], $despachante['embarcacoes_ids'] ?? [], true); ?>
                            <label class="tipo-embarcacao-chip<?php echo $marcado ? ' is-selected' : ''; ?>">
                                <input type="checkbox" name="embarcacoes_ids[]" value="<?php echo h($emb['id']); ?>" <?php echo $marcado ? 'checked' : ''; ?> onchange="atualizarTipoChip(this)">
                                <i class="fas fa-check"></i>
                                <span><?php echo h($emb['nome']); ?><?php echo $emb['registro'] ? ' · ' . h($emb['registro']) : ''; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="form-row mt-4">
                <div class="form-group col-12">
                    <h4 style="border-bottom: 1px solid var(--cor-borda); padding-bottom: 10px;">
                        <i class="fas fa-ship"></i> Tipos de embarcação que atende
                    </h4>
                    <p class="text-muted" style="margin: 8px 0 14px;">
                        Marque os tipos de embarcação em que este despachante costuma trabalhar. Esse campo é opcional e ajuda a equipe a escolher o contato certo.
                    </p>

                    <?php if (empty($tipos_embarcacao)): ?>
                        <div class="tabela-vazia" style="padding: 20px;">
                            <i class="fas fa-ship"></i>
                            <p>Nenhum tipo de embarcação cadastrado.</p>
                        </div>
                    <?php else: ?>
                        <div class="tipo-embarcacao-grid">
                            <?php foreach ($tipos_embarcacao as $tipo): ?>
                                <?php $marcado = in_array($tipo['id'], $despachante['tipos_embarcacao_ids'] ?? [], true); ?>
                                <label class="tipo-embarcacao-chip<?php echo $marcado ? ' is-selected' : ''; ?>">
                                    <input type="checkbox"
                                           name="tipos_embarcacao[]"
                                           value="<?php echo h($tipo['id']); ?>"
                                           <?php echo $marcado ? 'checked' : ''; ?>
                                           onchange="atualizarTipoChip(this)">
                                    <i class="fas fa-check"></i>
                                    <span><?php echo h($tipo['nome']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row mt-4">
                <div class="form-group col-12">
                    <h4 style="border-bottom: 1px solid var(--cor-borda); padding-bottom: 10px;"><i class="fas fa-money-bill"></i> Dados Bancários / PIX</h4>
                </div>
                <div class="form-group col-4">
                    <label for="tipo_recebimento">Tipo de Recebimento</label>
                    <select id="tipo_recebimento" name="tipo_recebimento" onchange="toggleFinanceiro()">
                        <option value="">Nenhum / Não informado</option>
                        <option value="pix" <?php echo ($despachante['tipo_recebimento'] ?? '') === 'pix' ? 'selected' : ''; ?>>PIX</option>
                        <option value="cc" <?php echo ($despachante['tipo_recebimento'] ?? '') === 'cc' ? 'selected' : ''; ?>>Conta Corrente / Poupança</option>
                    </select>
                </div>
            </div>

            <div class="form-row" id="bloco_pix" style="display: none;">
                <div class="form-group col-12">
                    <label for="chave_pix">Chave PIX</label>
                    <input type="text" id="chave_pix" name="chave_pix"
                           value="<?php echo h($despachante['chave_pix'] ?? ''); ?>"
                           placeholder="CPF, CNPJ, Email, Celular ou Aleatória">
                </div>
            </div>

            <div class="form-row" id="bloco_cc" style="display: none;">
                <div class="form-group col-4">
                    <label for="banco">Banco</label>
                    <input type="text" id="banco" name="banco"
                           value="<?php echo h($despachante['banco'] ?? ''); ?>"
                           placeholder="Ex: Banco do Brasil">
                </div>
                <div class="form-group col-4">
                    <label for="agencia">Agência</label>
                    <input type="text" id="agencia" name="agencia"
                           value="<?php echo h($despachante['agencia'] ?? ''); ?>"
                           placeholder="Ex: 0000-0">
                </div>
                <div class="form-group col-4">
                    <label for="conta">Conta</label>
                    <input type="text" id="conta" name="conta"
                           value="<?php echo h($despachante['conta'] ?? ''); ?>"
                           placeholder="Ex: 00000-0">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 
                    <?php echo $editando ? 'Atualizar' : 'Salvar'; ?>
                </button>
                <a href="<?php echo APP_URL; ?>despachantes" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>


<style>
.tipo-embarcacao-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 10px;
}
.tipo-embarcacao-chip {
    min-height: 44px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border: 1px solid var(--cor-borda);
    border-radius: 8px;
    background: var(--cor-fundo);
    color: var(--cor-texto);
    cursor: pointer;
    transition: background 0.18s, border-color 0.18s, box-shadow 0.18s;
}
.tipo-embarcacao-chip input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.tipo-embarcacao-chip i {
    width: 20px;
    height: 20px;
    display: grid;
    place-items: center;
    border: 1px solid var(--cor-borda);
    border-radius: 50%;
    color: transparent;
    font-size: 0.75rem;
    flex-shrink: 0;
}
.tipo-embarcacao-chip.is-selected {
    border-color: #56e0ad;
    background: rgba(46,204,113,0.12);
    box-shadow: 0 0 0 2px rgba(86,224,173,0.16);
}
.tipo-embarcacao-chip.is-selected i {
    border-color: #56e0ad;
    background: #56e0ad;
    color: #021210;
}
</style>

<script>
function atualizarTipoChip(input) {
    const chip = input.closest('.tipo-embarcacao-chip');
    if (chip) chip.classList.toggle('is-selected', input.checked);
}

function toggleCpfCnpj(limparValor = false) {
    const tipo = document.getElementById('tipo_pessoa').value;
    const input = document.getElementById('cpf_cnpj');
    input.placeholder = tipo === 'PF' ? 'CPF (apenas numeros)' : 'CNPJ (apenas numeros)';
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

// Inicializar placeholder ao carregar
document.addEventListener('DOMContentLoaded', function() {
    toggleCpfCnpj(false);
});

function toggleFinanceiro() {
    const tipo = document.getElementById('tipo_recebimento').value;
    document.getElementById('bloco_pix').style.display = tipo === 'pix' ? 'flex' : 'none';
    document.getElementById('bloco_cc').style.display = tipo === 'cc' ? 'flex' : 'none';
}
document.addEventListener('DOMContentLoaded', function() {
    toggleFinanceiro();
});

</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
