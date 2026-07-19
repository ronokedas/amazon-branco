<?php
/**
 * MODULO: CLIENTES
 * Arquivo: form.php - Formulario cadastro/edicao de cliente
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

$cliente = [
    'id' => '',
    'nome' => '',
    'tipo_pessoa' => 'PF',
    'cpf_cnpj' => '',
    'perfil' => 'proprietario',
    'telefone' => '',
    'email' => '',
    'endereco' => '',
    'status' => 'ATIVO',
    'embarcacoes_ids' => [],
];

// Se editando, carregar dados do cliente
if ($editando) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id AND status = 'ATIVO'");
        $stmt->execute([':id' => $id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dados) {
            $cliente = array_merge($cliente, $dados);
            // Carregar embarcacoes vinculadas
            $stmtEmb = $pdo->prepare("SELECT embarcacao_id FROM clientes_embarcacoes WHERE cliente_id = :cliente_id AND status = 'ATIVO'");
            $stmtEmb->execute([':cliente_id' => $id]);
            $cliente['embarcacoes_ids'] = array_column($stmtEmb->fetchAll(PDO::FETCH_ASSOC), 'embarcacao_id');
        } else {
            setMensagem('error', 'Cliente nao encontrado.');
            redirecionar(APP_URL . 'clientes');
        }
    } catch (Exception $e) {
        error_log('Erro ao carregar cliente: ' . $e->getMessage());
        setMensagem('error', 'Erro ao carregar dados do cliente.');
        redirecionar(APP_URL . 'clientes');
    }
}

// Buscar embarcacoes ativas para vincular
try {
    $stmtEmb = $pdo->query("SELECT id, nome, registro FROM embarcacoes WHERE ativo = 1 ORDER BY nome ASC");
    $embarcacoes = $stmtEmb->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $embarcacoes = [];
}

$titulo_page = ($editando ? 'Editar' : 'Novo') . ' Cliente - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="form-container">
        <div class="form-header">
            <h3>
                <i class="fas fa-user-tie"></i> 
                <?php echo $editando ? 'Editar Cliente' : 'Novo Cliente'; ?>
            </h3>
            <a href="<?php echo APP_URL; ?>clientes" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <form action="<?php echo APP_URL; ?>clientes/actions" method="POST" class="form-padrao">
            <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
            <input type="hidden" name="action" value="<?php echo $editando ? 'editar' : 'inserir'; ?>">
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?php echo h($cliente['id']); ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group col-12">
                    <label for="nome">Nome / Razão Social *</label>
                    <input type="text" id="nome" name="nome" required
                           value="<?php echo h($cliente['nome']); ?>"
                           placeholder="Nome completo ou razao social">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-4">
                    <label for="tipo_pessoa">Tipo *</label>
                    <select id="tipo_pessoa" name="tipo_pessoa" onchange="toggleCpfCnpj(true)">
                        <option value="PF" <?php echo $cliente['tipo_pessoa'] === 'PF' ? 'selected' : ''; ?>>Pessoa Física</option>
                        <option value="PJ" <?php echo $cliente['tipo_pessoa'] === 'PJ' ? 'selected' : ''; ?>>Pessoa Jurídica</option>
                    </select>
                </div>
                <div class="form-group col-4">
                    <label for="cpf_cnpj">CPF / CNPJ</label>
                    <input type="text" id="cpf_cnpj" name="cpf_cnpj"
                           value="<?php echo h($cliente['cpf_cnpj']); ?>"
                           placeholder="Apenas numeros"
                           oninput="mascararCpfCnpj(this)">
                </div>
                <div class="form-group col-4">
                    <label for="perfil">Perfil *</label>
                    <select id="perfil" name="perfil" required>
                        <option value="armador" <?php echo $cliente['perfil'] === 'armador' ? 'selected' : ''; ?>>Armador</option>
                        <option value="proprietario" <?php echo $cliente['perfil'] === 'proprietario' ? 'selected' : ''; ?>>Proprietário</option>
                        <option value="despachante" <?php echo $cliente['perfil'] === 'despachante' ? 'selected' : ''; ?>>Despachante</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-4">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone"
                           value="<?php echo h($cliente['telefone']); ?>"
                           placeholder="(91) 99999-9999"
                           oninput="mascararTelefone(this)">
                </div>
                <div class="form-group col-8">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email"
                           value="<?php echo h($cliente['email']); ?>"
                           placeholder="cliente@exemplo.com">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-12">
                    <label for="endereco">Endereço</label>
                    <textarea id="endereco" name="endereco" rows="2"
                              placeholder="Logradouro, numero, bairro, cidade/UF"><?php echo h($cliente['endereco']); ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-12">
                    <label><i class="fas fa-ship"></i> Embarcações Vinculadas</label>
                    <div class="checkbox-grid">
                        <?php if (empty($embarcacoes)): ?>
                            <p class="text-muted">Nenhuma embarcação cadastrada. 
                                <a href="<?php echo APP_URL; ?>embarcacoes/form">Cadastre uma embarcação</a> primeiro.
                            </p>
                        <?php else: ?>
                            <?php foreach ($embarcacoes as $emb): ?>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="embarcacoes_ids[]" 
                                           value="<?php echo h($emb['id']); ?>"
                                           <?php echo in_array($emb['id'], $cliente['embarcacoes_ids']) ? 'checked' : ''; ?>>
                                    <span><?php echo h($emb['nome']); ?> 
                                        <?php if ($emb['registro']): ?>
                                            <small>(<?php echo h($emb['registro']); ?>)</small>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 
                    <?php echo $editando ? 'Atualizar' : 'Salvar'; ?>
                </button>
                <a href="<?php echo APP_URL; ?>clientes" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
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
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
