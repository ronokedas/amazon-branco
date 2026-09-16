<?php
/**
 * MÓDULO: SERVIÇOS
 * Arquivo: form.php - Formulário cadastro/edição de serviço naval
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

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
            redirecionar(APP_URL . 'servicos');
        }
    } catch (Exception $e) {
        error_log('Erro ao carregar serviço: ' . $e->getMessage());
        setMensagem('error', 'Erro ao carregar dados do serviço.');
        redirecionar(APP_URL . 'servicos');
    }
}

$titulo_page = ($editando ? 'Editar' : 'Novo') . ' Serviço - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="form-container">
        <div class="form-header">
            <h3>
                <i class="fas fa-cogs"></i>
                <?php echo $editando ? 'Editar Serviço' : 'Novo Serviço'; ?>
            </h3>
            <a href="<?php echo APP_URL; ?>servicos" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <form action="<?php echo APP_URL; ?>servicos/actions" method="POST" class="form-padrao">
            <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
            <input type="hidden" name="action" value="<?php echo $editando ? 'editar' : 'inserir'; ?>">
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?php echo h($servico['id']); ?>">
            <?php endif; ?>

            <!-- Atalhos Rápidos (Pills / Chips) conforme AGENTS.md -->
            <div class="quick-pills" style="margin-bottom: 16px; padding: 12px; background: rgba(37,150,190,0.06); border: 1px dashed #2596be; border-radius: 8px;">
                <div style="font-size: 0.82rem; font-weight: 600; color: #1e293b; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-bolt" style="color: #2596be;"></i> Preenchimento em 1 Clique (Padrão DPC/Marinha do Brasil):
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                    <button type="button" class="btn btn-sm btn-outline-secondary pill-btn" style="font-size: 0.78rem; padding: 3px 10px; border-radius: 14px;" onclick="aplicarModeloRapido('Vistoria Periódica CSN', 'CSN', 'Vistoria periódica de segurança da navegação conforme NORMAM-202/DPC, com verificação de itens de salvatagem, combate a incêndio, luzes e navegação.')">
                        CSN Periódica (NORMAM-202)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary pill-btn" style="font-size: 0.78rem; padding: 3px 10px; border-radius: 14px;" onclick="aplicarModeloRapido('Vistoria de Borda Livre CNBL', 'CNBL', 'Cálculo e atribuição de borda livre com fixação da marca de linha de carga de acordo com a NORMAM-201/DPC.')">
                        Borda Livre CNBL (NORMAM-201)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary pill-btn" style="font-size: 0.78rem; padding: 3px 10px; border-radius: 14px;" onclick="aplicarModeloRapido('Cálculo de Arqueação CNARQ', 'CNARQ', 'Determinação das arqueações bruta e líquida de embarcações conforme NORMAM-201/DPC e memorial descritivo.')">
                        Arqueação CNARQ (NORMAM-201)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary pill-btn" style="font-size: 0.78rem; padding: 3px 10px; border-radius: 14px;" onclick="aplicarModeloRapido('Licença Provisória de Entrada em Tráfego (LP)', 'LP', 'Emissão de Licença Provisória para tráfego conforme preconizado na NORMAM-202/DPC e diretrizes da Capitania.')">
                        Licença Provisória LP (NORMAM-202)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary pill-btn" style="font-size: 0.78rem; padding: 3px 10px; border-radius: 14px;" onclick="aplicarModeloRapido('Licença de Construção e Alteração (LC)', 'LC', 'Licenciamento para obras de construção ou alteração estrutural naval em estaleiro conforme NORMAM-202.')">
                        Licença Construção LC (NORMAM-202)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary pill-btn" style="font-size: 0.78rem; padding: 3px 10px; border-radius: 14px;" onclick="aplicarModeloRapido('Laudo Pericial de Habitabilidade (CHT)', 'CHT', 'Vistoria e emissão de Certificado de Habitabilidade para tripulantes e passageiros conforme NORMAM-202.')">
                        Habitabilidade CHT (NORMAM-202)
                    </button>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-12">
                    <label for="nome">Nome do Serviço *</label>
                    <input type="text" id="nome" name="nome" required
                           value="<?php echo h($servico['nome']); ?>"
                           placeholder="Ex: Vistoria Inicial Seco">
                    <small class="text-muted">Denominação oficial do serviço naval conforme escopo ou normas da Autoridade Marítima (DPC/NORMAM).</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-12">
                    <label for="certificado_modelo">Certificado Estatutário Habilitado</label>
                    <select id="certificado_modelo" name="certificado_modelo">
                        <option value="">Não habilita emissão de certificado</option>
                        <?php 
                        $modelosCertificados = [
                            'CSN'   => 'CSN - Certificado de Segurança da Navegação (NORMAM-202)',
                            'CNBL'  => 'CNBL - Certificado Nacional de Borda Livre (NORMAM-201)',
                            'CNARQ' => 'CNARQ - Certificado Nacional de Arqueação (NORMAM-201)',
                            'LP'    => 'LP - Licença Provisória (NORMAM-202)',
                            'LC'    => 'LC - Licença de Construção / Alteração (NORMAM-202)',
                            'CHT'   => 'CHT - Certificado de Habitabilidade (NORMAM-202)',
                        ];
                        foreach ($modelosCertificados as $valor => $rotulo): 
                        ?>
                            <option value="<?php echo h($valor); ?>" <?php echo ($servico['certificado_modelo'] ?? '') === $valor ? 'selected' : ''; ?>>
                                <?php echo h($rotulo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Define qual documento naval estatutário será emitido pelo motor central após a conclusão e aprovação da vistoria técnica.</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-8">
                    <label for="descricao">Descrição Técnica / Escopo NORMAM</label>
                    <textarea id="descricao" name="descricao" rows="3"
                              placeholder="Descreva detalhadamente o escopo pericial ou referência regulamentar da Marinha..."><?php echo h($servico['descricao']); ?></textarea>
                    <small class="text-muted">Instruções técnicas para a proposta comercial e laudo pericial do vistoriador.</small>
                </div>
                <div class="form-group col-4">
                    <label for="preco_padrao">Preço Padrão Sugerido (R$) *</label>
                    <input type="text" id="preco_padrao" name="preco_padrao" required
                           value="<?php echo h($servico['preco_padrao']); ?>"
                           placeholder="0,00"
                           oninput="mascararMoeda(this)">
                    <small class="text-muted">Tarifa de referência para propostas comerciais. Centavos com vírgula (ex: 2.500,00).</small>
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
                <a href="<?php echo APP_URL; ?>servicos" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
/**
 * Aplica modelo padrão NORMAM em 1 clique
 */
function aplicarModeloRapido(nome, modelo, desc) {
    const elNome = document.getElementById('nome');
    const elModelo = document.getElementById('certificado_modelo');
    const elDesc = document.getElementById('descricao');
    if (elNome) elNome.value = nome;
    if (elModelo) elModelo.value = modelo;
    if (elDesc) elDesc.value = desc;
}

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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
