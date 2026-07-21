<?php
/**
 * MODULO: CLIENTES
 * Arquivo: form.php - Formulario cadastro/edicao de proprietario
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
$cargo = getCargo();
exigirAcesso('proprietarios');

$id = $_GET['id'] ?? null;
$editando = !empty($id);

$proprietario = [
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

// Se editando, carregar dados do proprietario
if ($editando) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id AND perfil = 'proprietario' AND status = 'ATIVO'");
        $stmt->execute([':id' => $id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dados) {
            $proprietario = array_merge($proprietario, $dados);
            // Carregar embarcacoes vinculadas
            $stmtEmb = $pdo->prepare("SELECT embarcacao_id FROM clientes_embarcacoes WHERE cliente_id = :cliente_id AND status='ATIVO'");
            $stmtEmb->execute([':cliente_id' => $id]);
            $proprietario['embarcacoes_ids'] = array_column($stmtEmb->fetchAll(PDO::FETCH_ASSOC), 'embarcacao_id');
        } else {
            setMensagem('error', 'Proprietário nao encontrado.');
            redirecionar(APP_URL . 'proprietarios');
        }
    } catch (Exception $e) {
        error_log('Erro ao carregar proprietario: ' . $e->getMessage());
        setMensagem('error', 'Erro ao carregar dados do proprietario.');
        redirecionar(APP_URL . 'proprietarios');
    }
}

// Buscar embarcacoes ativas para vincular
try {
    // Busca TODAS as embarcacoes ativas ordenadas pela data de criacao (mais recentes primeiro)
    $stmtEmb = $pdo->query("SELECT id, nome, registro FROM embarcacoes WHERE ativo = 1 ORDER BY criado_em DESC");
    $embarcacoes = $stmtEmb->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $embarcacoes = [];
}

$titulo_page = ($editando ? 'Editar' : 'Novo') . ' Proprietário - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="form-container">
        <div class="form-header">
            <h3>
                <i class="fas fa-user-tie"></i> 
                <?php echo $editando ? 'Editar Proprietário' : 'Novo Proprietário'; ?>
            </h3>
            <a href="<?php echo APP_URL; ?>proprietarios" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <form action="<?php echo APP_URL; ?>proprietarios/actions" method="POST" class="form-padrao">
            <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
            <input type="hidden" name="action" value="<?php echo $editando ? 'editar' : 'inserir'; ?>">
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?php echo h($proprietario['id']); ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group col-12">
                    <label for="nome">Nome / Razão Social *</label>
                    <input type="text" id="nome" name="nome" required
                           value="<?php echo h($proprietario['nome']); ?>"
                           placeholder="Nome completo ou razao social">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-4">
                    <label for="tipo_pessoa">Tipo *</label>
                    <select id="tipo_pessoa" name="tipo_pessoa" onchange="toggleCpfCnpj(true)">
                        <option value="PF" <?php echo $proprietario['tipo_pessoa'] === 'PF' ? 'selected' : ''; ?>>Pessoa Física</option>
                        <option value="PJ" <?php echo $proprietario['tipo_pessoa'] === 'PJ' ? 'selected' : ''; ?>>Pessoa Jurídica</option>
                    </select>
                </div>
                <div class="form-group col-4">
                    <label for="cpf_cnpj">CPF / CNPJ</label>
                    <input type="text" id="cpf_cnpj" name="cpf_cnpj"
                           value="<?php echo h($proprietario['cpf_cnpj']); ?>"
                           placeholder="Apenas numeros"
                           oninput="mascararCpfCnpj(this)">
                </div>
                <div class="form-group col-4">
                    <label for="perfil">Perfil *</label>
                    <input type="hidden" name="perfil" value="proprietario">
<select id="perfil_show" name="perfil_show" disabled required>
                        <option value="armador" <?php echo $proprietario['perfil'] === 'armador' ? 'selected' : ''; ?>>Armador</option>
                        <option value="proprietario" <?php echo $proprietario['perfil'] === 'proprietario' ? 'selected' : ''; ?>>Proprietário</option>
                        <option value="despachante" <?php echo $proprietario['perfil'] === 'despachante' ? 'selected' : ''; ?>>Despachante</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-4">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telefone"
                           value="<?php echo h($proprietario['telefone']); ?>"
                           placeholder="(91) 99999-9999"
                           oninput="mascararTelefone(this)">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-12">
                    <label for="email">E-mail *</label>
                    <input type="email" id="email" name="email" required maxlength="150"
                           inputmode="email" autocomplete="email"
                           value="<?php echo h($proprietario['email']); ?>"
                           placeholder="nome@empresa.com.br">
                    <small class="text-muted">Obrigatório. Este endereço será usado para contato e futuro acesso ao portal.</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-12">
                    <label for="endereco">Endereço</label>
                    <textarea id="endereco" name="endereco" rows="2"
                              placeholder="Logradouro, numero, bairro, cidade/UF"><?php echo h($proprietario['endereco']); ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-12" style="background: rgba(0, 123, 255, 0.05); padding: 20px; border: 1px solid rgba(0, 123, 255, 0.2); border-radius: 8px; border-left: 4px solid #007bff;">
                    <label style="font-size: 1.1rem; color: #007bff; margin-bottom: 15px; display: block;">
                        <i class="fas fa-ship"></i> Seleção de Embarcações (Vinculadas)
                    </label>
                    <p class="text-muted" style="margin-top: -10px; margin-bottom: 15px; font-size: 0.9rem;">
                        Selecione as embarcações relacionadas a este cadastro. Abaixo, estão listadas as 5 embarcações mais recentes do sistema, além das já selecionadas. Para encontrar outras, utilize o campo de busca.
                    </p>
                    
                    <div style="position: relative; max-width: 100%; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; background: var(--cor-input-bg); border: 2px solid var(--cor-borda); border-radius: 6px; padding: 0 10px;">
                            <i class="fas fa-search text-muted"></i>
                            <input type="text" id="buscaEmbarcacao" class="form-control" placeholder="Digite o nome da embarcação para buscar..." style="border: none; background: transparent; box-shadow: none; padding: 12px; font-size: 1rem; color: var(--cor-texto);">
                        </div>
                        <div id="resBuscaEmbarcacao" style="position: absolute; top: 100%; left: 0; right: 0; background: var(--cor-sidebar); border: 1px solid var(--cor-borda); border-radius: 0 0 6px 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 1000; display: none; max-height: 250px; overflow-y: auto;"></div>
                    </div>
                    
                    <div id="listaEmbarcacoes" class="checkbox-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 10px;">
                        <?php
                        $recentes = [];
                        // 1. Sempre incluir os que já estao vinculados
                        foreach ($embarcacoes as $emb) {
                            if (in_array($emb['id'], $proprietario['embarcacoes_ids'])) {
                                $recentes[$emb['id']] = $emb;
                            }
                        }
                        // 2. Incluir os 5 mais recentes cadastrados no sistema (ordem desc de criado_em da query acima)
                        $c = 0;
                        foreach ($embarcacoes as $emb) {
                            if (!isset($recentes[$emb['id']]) && $c < 5) {
                                $recentes[$emb['id']] = $emb;
                                $c++;
                            }
                        }
                        ?>
                        <?php foreach ($recentes as $emb): ?>
                            <label class="checkbox-item emb-item" id="emb_<?php echo h($emb['id']); ?>" style="padding: 10px; background: var(--cor-sidebar); border: 1px solid var(--cor-borda); border-radius: 6px; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: all 0.2s;">
                                <input type="checkbox" name="embarcacoes_ids[]" 
                                       value="<?php echo h($emb['id']); ?>"
                                       <?php echo in_array($emb['id'], $proprietario['embarcacoes_ids']) ? 'checked' : ''; ?>
                                       style="width: 18px; height: 18px;">
                                <span style="font-weight: 500; font-size: 0.95rem;"><?php echo h($emb['nome']); ?> 
                                    <?php if ($emb['registro']): ?>
                                        <small class="text-muted" style="display: block; font-weight: normal; font-size: 0.8rem;">Registro: <?php echo h($emb['registro']); ?></small>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 
                    <?php echo $editando ? 'Atualizar' : 'Salvar'; ?>
                </button>
                <a href="<?php echo APP_URL; ?>proprietarios" class="btn btn-secondary">
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
<script>
document.getElementById('buscaEmbarcacao').addEventListener('input', function() {
    const query = this.value;
    const resDiv = document.getElementById('resBuscaEmbarcacao');
    if(query.length < 2) {
        resDiv.style.display = 'none';
        return;
    }
    
    fetch('<?php echo APP_URL; ?>ajax/busca_embarcacoes.php?q=' + encodeURIComponent(query))
    .then(r => r.json())
    .then(data => {
        resDiv.innerHTML = '';
        if(data.length === 0) {
            resDiv.innerHTML = '<div style="padding: 8px;">Nenhuma encontrada.</div>';
        } else {
            data.forEach(item => {
                const div = document.createElement('div');
                div.style.padding = '8px';
                div.style.borderBottom = '1px solid var(--cor-borda)';
                div.style.cursor = 'pointer';
                div.innerHTML = `<strong>${escapeHtml(item.nome)}</strong><br><small>${escapeHtml(item.registro)}</small>`;
                div.onclick = function() {
                    const lista = document.getElementById('listaEmbarcacoes');
                    if (!document.getElementById('emb_' + item.id)) {
                        lista.insertAdjacentHTML('afterbegin', `
                            <label class="checkbox-item emb-item" id="emb_${item.id}">
                                <input type="checkbox" name="embarcacoes_ids[]" value="${item.id}" checked>
                                <span>${escapeHtml(item.nome)} <small>(${escapeHtml(item.registro)})</small></span>
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
    });
});

document.addEventListener('click', function(e) {
    if(!document.getElementById('buscaEmbarcacao').contains(e.target) && !document.getElementById('resBuscaEmbarcacao').contains(e.target)) {
        document.getElementById('resBuscaEmbarcacao').style.display = 'none';
    }
});

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
