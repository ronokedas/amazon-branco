<?php
/**
 * MÓDULO: COMERCIAL > SERVIÇOS
 * Arquivo: form.php - Formulário cadastro/edição de serviço
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/auth.php';

verificar_sessao();
if (!podeAcessar('servicos')) {
    setMensagem('error', 'Acesso negado. Você não tem permissão para gerenciar serviços.');
    redirecionar(APP_URL . 'dashboard');
}

$id = $_GET['id'] ?? null;
$editando = !empty($id);

$servico = [
    'id'           => '',
    'nome'         => '',
    'descricao'    => '',
    'certificado_modelo' => '',
    'preco_padrao' => '0,00',
    'ativo'        => 1,
];

// Se editando, carregar dados do serviço
if ($editando) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM servicos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dados) {
            $servico = array_merge($servico, $dados);
            // Converter preço para formato brasileiro no input
            $servico['preco_padrao'] = number_format((float)$dados['preco_padrao'], 2, ',', '.');
        } else {
            setMensagem('error', 'Serviço não encontrado.');
            redirecionar(APP_URL . 'comercial/servicos');
        }
    } catch (Exception $e) {
        error_log('Erro ao carregar serviço: ' . $e->getMessage());
        setMensagem('error', 'Erro ao carregar dados do serviço.');
        redirecionar(APP_URL . 'comercial/servicos');
    }
}

$titulo_page = ($editando ? 'Editar' : 'Novo') . ' Serviço - ERP Sistema';
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="form-container">
        <div class="form-header">
            <h3>
                <i class="fas fa-cogs"></i>
                <?php echo $editando ? 'Editar Serviço' : 'Novo Serviço'; ?>
            </h3>
            <a href="<?php echo APP_URL; ?>comercial/servicos" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <form action="<?php echo APP_URL; ?>comercial/servicos/actions" method="POST" class="form-padrao">
            <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
            <input type="hidden" name="action" value="<?php echo $editando ? 'editar' : 'inserir'; ?>">
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?php echo h($servico['id']); ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group col-12">
                    <label for="nome">Nome do Serviço *</label>
                    <input type="text" id="nome" name="nome" required
                           value="<?php echo h($servico['nome']); ?>"
                           placeholder="Ex: Vistoria Inicial Seco">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-12">
                    <label for="certificado_modelo">Certificado habilitado</label>
                    <select id="certificado_modelo" name="certificado_modelo">
                        <option value="">Não habilita certificado</option>
                        <?php foreach (['CSN' => 'CSN - Segurança da Navegação', 'CNBL' => 'CNBL - Borda Livre', 'CNARQ' => 'CNARQ - Arqueação'] as $valor => $rotulo): ?>
                            <option value="<?php echo h($valor); ?>" <?php echo ($servico['certificado_modelo'] ?? '') === $valor ? 'selected' : ''; ?>><?php echo h($rotulo); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Define qual certificado este serviço contratado permite emitir.</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-8">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="3"
                              placeholder="Descreva o que este serviço contempla..."><?php echo h($servico['descricao']); ?></textarea>
                </div>
                <div class="form-group col-4">
                    <label for="preco_padrao">Preço Padrão (R$) *</label>
                    <input type="text" id="preco_padrao" name="preco_padrao" required
                           value="<?php echo h($servico['preco_padrao']); ?>"
                           placeholder="0,00"
                           oninput="mascararMoeda(this)">
                    <small class="text-muted">Use vírgula para centavos. Ex: 1.500,00</small>
                </div>
            </div>

            <?php if ($editando): ?>
            <div class="form-row">
                <div class="form-group col-12">
                    <label>
                        <input type="checkbox" name="ativo" value="1" <?php echo $servico['ativo'] ? 'checked' : ''; ?>>
                        Serviço ativo (disponível para propostas)
                    </label>
                </div>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <?php echo $editando ? 'Atualizar' : 'Salvar'; ?>
                </button>
                <a href="<?php echo APP_URL; ?>comercial/servicos" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
/**
 * Máscara para valor monetário brasileiro (R$)
 * Permite digitar valores como 1500,00 e formata automaticamente
 */
function mascararMoeda(input) {
    let valor = input.value.replace(/\D/g, ''); // Remove tudo que não é dígito
    if (valor === '') {
        input.value = '';
        return;
    }
    // Converte para centavos (ex: "150000" => 150000 centavos = 1500.00)
    valor = (parseInt(valor, 10) / 100).toFixed(2);
    // Formata com separadores brasileiros
    valor = valor.replace('.', ',');
    valor = valor.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    input.value = valor;
}

// Inicializar a máscara ao carregar (se já tiver valor)
document.addEventListener('DOMContentLoaded', function() {
    const campo = document.getElementById('preco_padrao');
    if (campo && campo.value) {
        mascararMoeda(campo);
    }
});
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
