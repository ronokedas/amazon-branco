<?php
/**
 * MÓDULO: Documentação > LC (Licença de Construção / LCEC)
 * Formulário de Criação/Edição
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

verificar_sessao();
if (!podeAcessar('documentacao')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

$editando = false;
$licenca = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $editando = true;
    $stmt = $pdo->prepare("SELECT * FROM certificados_lc WHERE id = :id AND ativo = 1");
    $stmt->execute([':id' => $id]);
    $licenca = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$licenca) {
        setMensagem('error', 'Licença não encontrada.');
        redirecionar(APP_URL . 'documentacao/lc');
    }
}

// Gerar próximo número
$proximo_numero = '';
$proximo_numero_ec = '';
if (!$editando) {
    $ano = date('y');
    $ano4 = date('Y');
    $stmt_num = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_lc WHERE YEAR(criado_em) = :ano");
    $stmt_num->execute([':ano' => $ano4]);
    $total = $stmt_num->fetch()['total'];
    $seq = $total + 1;
    $proximo_numero = "AM-LC-{$seq}/{$ano}";
    $proximo_numero_ec = "AM-EC-{$seq}/{$ano}";
}

// Embarcações
$stmt_emb = $pdo->prepare("SELECT * FROM embarcacoes WHERE ativo = 1 ORDER BY nome");
$stmt_emb->execute();
$embarcacoes = $stmt_emb->fetchAll(PDO::FETCH_ASSOC);

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
}

// Buscar lista de despachantes ativos
$stmt_desp = $pdo->prepare("SELECT id, nome FROM clientes WHERE perfil = 'despachante' AND status = 'ATIVO' ORDER BY nome");
$stmt_desp->execute();
$despachantes_list = $stmt_desp->fetchAll(PDO::FETCH_ASSOC);

$titulo_page = ($editando ? 'Editar' : 'Nova') . ' Licença de Construção/LCEC - ' . APP_NAME;
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-header">
        <h2><i class="fas fa-file-certificate"></i> <?php echo $editando ? 'Editar Licença' : 'Nova Licença'; ?> de Construção / LCEC</h2>
        <a href="<?php echo APP_URL; ?>documentacao/lc" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>

    <?php if ($editando && $licenca['assinado']): ?>
        <div class="card mb-3" style="border-left: 4px solid var(--cor-destaque);">
            <div class="card-body">
                <p style="margin:0;"><i class="fas fa-lock" style="color: var(--cor-destaque);"></i>
                <strong>Esta licença já foi assinada digitalmente.</strong><br>
                Assinado por: <?php echo h($licenca['assinante_nome']); ?> em <?php echo formatarDataCompleta($licenca['assinatura_em']); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo APP_URL; ?>documentacao/lc/actions">
        <input type="hidden" name="action" value="salvar">
        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?php echo h($licenca['id']); ?>">
            <input type="hidden" name="vistoria_id" value="<?php echo h($licenca['vistoria_id'] ?? ''); ?>">
        <?php endif; ?>

        <!-- Seção 1: Identificação -->
        <div class="card mb-3">
            <div class="card-header"><h3><i class="fas fa-id-card"></i> Identificação</h3></div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Número da Licença</label>
                        <input type="text" class="form-control" id="numero_lc_display" 
                               value="<?php echo $editando ? h($licenca['numero_lc']) : h($proximo_numero); ?>" readonly 
                               style="background: var(--cor-sidebar); font-weight: bold;">
                        <small class="text-muted">Para LCEC o número será AM-EC:{n}/{ano}</small>
                    </div>
                    <div class="form-group">
                        <label for="tipo_licenca">Tipo de Licença *</label>
                        <select name="tipo_licenca" id="tipo_licenca" class="form-control" required onchange="atualizarNumero()">
                            <?php
                            $tipos = ['LC'=>'LC - Licença de Construção','LA'=>'LA - Licença de Alteração','LR'=>'LR - Licença de Reclassificação','LCEC'=>'LCEC - Exploração Comercial'];
                            $current = $editando ? $licenca['tipo_licenca'] : 'LC';
                            foreach ($tipos as $val => $label): ?>
                                <option value="<?php echo $val; ?>" <?php echo $current === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <?php
                            $sts = ['rascunho'=>'Rascunho','emitido'=>'Emitido','cancelado'=>'Cancelado'];
                            $cur = $editando ? $licenca['status'] : 'rascunho';
                            foreach ($sts as $v => $l): ?>
                                <option value="<?php echo $v; ?>" <?php echo $cur === $v ? 'selected' : ''; ?>><?php echo $l; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="data_emissao">Data de Emissão</label>
                        <input type="date" name="data_emissao" id="data_emissao" class="form-control" required
                               value="<?php echo $editando ? h($licenca['data_emissao']) : date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="data_validade">Data de Validade</label>
                        <input type="date" name="data_validade" id="data_validade" class="form-control"
                               value="<?php echo $editando ? h($licenca['data_validade']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group" id="lc_term_group" style="<?php echo $current === 'LCEC' ? '' : 'display:none;'; ?>">
                    <label for="data_termino_construcao">Data Término da Construção (apenas LCEC)</label>
                    <input type="date" name="data_termino_construcao" id="data_termino_construcao" class="form-control"
                           value="<?php echo $editando ? h($licenca['data_termino_construcao'] ?? '') : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="local_emissao">Local de Emissão</label>
                    <input type="text" name="local_emissao" id="local_emissao" class="form-control"
                           value="<?php echo $editando ? h($licenca['local_emissao']) : 'Belém-PA'; ?>">
                </div>
            </div>
        </div>

        <!-- Seção 2: Dados da Embarcação -->
        <div class="card mb-3">
            <div class="card-header"><h3><i class="fas fa-ship"></i> Dados da Embarcação</h3></div>
            <div class="card-body">
                <?php if (!$editando): ?>
                <div class="form-group">
                    <label for="embarcacao_id"><i class="fas fa-search"></i> Selecionar Embarcação do Cadastro</label>
                    <select id="embarcacao_id" class="form-control" onchange="carregarDadosEmbarcacao(this.value)">
                        <option value="">-- Selecione --</option>
                        <?php foreach ($embarcacoes as $emb): ?>
                            <option value="<?php echo h($emb['id']); ?>"
                                data-nome="<?php echo h($emb['nome']); ?>"
                                data-registro="<?php echo h($emb['registro']); ?>"
                                data-tipo="<?php echo h($emb['tipo']); ?>"
                                data-prop-nome="<?php echo h($emb['proprietario'] ?? ''); ?>"
                                data-comprimento_total="<?php echo h($emb['comprimento_total'] ?? ''); ?>"
                                data-comprimento_casco="<?php echo h($emb['comprimento_casco'] ?? ''); ?>"
                                data-boca_moldada="<?php echo h($emb['boca_moldada'] ?? ''); ?>"
                                data-pontal_moldado="<?php echo h($emb['pontal_moldado'] ?? ''); ?>"
                                data-arqueacao_bruta="<?php echo h($emb['arqueacao_bruta'] ?? ''); ?>"
                                data-material_casco="<?php echo h($emb['material_casco'] ?? ''); ?>"
                                <?php echo (!empty($_GET['agendamento_id']) && isset($dadosPre['embarcacao_id']) && $dadosPre['embarcacao_id'] == $emb['id']) ? 'selected' : ''; ?>>
                                <?php echo h($emb['nome']) . ' (' . h($emb['tipo']) . ' - ' . h($emb['registro']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <hr>
                <?php endif; ?>

                <div class="grid-3">
                    <div class="form-group">
                        <label for="nome_embarcacao">Nome da Embarcação *</label>
                        <input type="text" name="nome_embarcacao" id="nome_embarcacao" class="form-control" required
                               value="<?php echo $editando ? h($licenca['nome_embarcacao']) : h($preenchimento['nome_embarcacao']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="tipo_embarcacao">Tipo de Embarcação</label>
                        <input type="text" name="tipo_embarcacao" id="tipo_embarcacao" class="form-control"
                               value="<?php echo $editando ? h($licenca['tipo_embarcacao']) : h($preenchimento['tipo_embarcacao']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="numero_casco">Número do Casco</label>
                        <input type="text" name="numero_casco" id="numero_casco" class="form-control"
                               value="<?php echo $editando ? h($licenca['numero_casco'] ?? '') : ''; ?>">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="material_casco">Material do Casco</label>
                        <input type="text" name="material_casco" id="material_casco" class="form-control"
                               value="<?php echo $editando ? h($licenca['material_casco']) : h($preenchimento['material_casco']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="sociedade_classificadora">Sociedade Classificadora</label>
                        <input type="text" name="sociedade_classificadora" id="sociedade_classificadora" class="form-control"
                               value="<?php echo $editando ? h($licenca['sociedade_classificadora']) : ''; ?>">
                    </div>
                </div>

                <!-- Dimensões -->
                <h4 style="margin:15px 0 10px;font-size:14px;color:#555;">Dimensões</h4>
                <div class="grid-5">
                    <div class="form-group">
                        <label for="comprimento_total">Comp. Total (m)</label>
                        <input type="number" name="comprimento_total" id="comprimento_total" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($licenca['comprimento_total']) : h($preenchimento['comprimento_total']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="comprimento_pp">Comp. PP (m)</label>
                        <input type="number" name="comprimento_pp" id="comprimento_pp" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($licenca['comprimento_pp'] ?? '') : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="boca_moldada">Boca Mold. (m)</label>
                        <input type="number" name="boca_moldada" id="boca_moldada" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($licenca['boca_moldada']) : h($preenchimento['boca_moldada']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="pontal_moldado">Pontal Mold. (m)</label>
                        <input type="number" name="pontal_moldado" id="pontal_moldado" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($licenca['pontal_moldado']) : h($preenchimento['pontal_moldado']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="calado_maximo">Calado Máx. (m)</label>
                        <input type="number" name="calado_maximo" id="calado_maximo" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($licenca['calado_maximo'] ?? '') : ''; ?>">
                    </div>
                </div>

                <!-- Capacidades -->
                <h4 style="margin:15px 0 10px;font-size:14px;color:#555;">Capacidades</h4>
                <div class="grid-3">
                    <div class="form-group">
                        <label for="porte_bruto">Porte Bruto (PB)</label>
                        <input type="number" name="porte_bruto" id="porte_bruto" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($licenca['porte_bruto'] ?? '') : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="numero_tripulantes">Nº Tripulantes</label>
                        <input type="number" name="numero_tripulantes" id="numero_tripulantes" class="form-control" min="0"
                               value="<?php echo $editando ? h($licenca['numero_tripulantes'] ?? '') : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="numero_passageiros">Nº Passageiros</label>
                        <input type="number" name="numero_passageiros" id="numero_passageiros" class="form-control" min="0"
                               value="<?php echo $editando ? h($licenca['numero_passageiros'] ?? '') : ''; ?>">
                    </div>
                </div>

                <!-- Navegação e Atividade -->
                <h4 style="margin:15px 0 10px;font-size:14px;color:#555;">Navegação e Atividade</h4>
                <div class="grid-4">
                    <div class="form-group">
                        <label for="tipo_navegacao">Tipo de Navegação</label>
                        <input type="text" name="tipo_navegacao" id="tipo_navegacao" class="form-control" placeholder="Ex: Interior, Mar Aberto"
                               value="<?php echo $editando ? h($licenca['tipo_navegacao'] ?? '') : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="area_navegacao">Área de Navegação</label>
                        <input type="text" name="area_navegacao" id="area_navegacao" class="form-control" placeholder="Ex: Área 1, Cabotagem"
                               value="<?php echo $editando ? h($licenca['area_navegacao'] ?? '') : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="atividade_servico">Atividade/Serviço</label>
                        <input type="text" name="atividade_servico" id="atividade_servico" class="form-control" placeholder="Ex: Transporte de Passageiros"
                               value="<?php echo $editando ? h($licenca['atividade_servico'] ?? '') : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="propulsao">Propulsão</label>
                        <input type="text" name="propulsao" id="propulsao" class="form-control" placeholder="Ex: Motor Diesel"
                               value="<?php echo $editando ? h($licenca['propulsao'] ?? '') : ''; ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção 3: Proprietário -->
        <div class="card mb-3">
            <div class="card-header"><h3><i class="fas fa-user-tie"></i> Proprietário / Armador</h3></div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label for="proprietario_nome">Nome / Razão Social</label>
                        <input type="text" name="proprietario_nome" id="proprietario_nome" class="form-control"
                               value="<?php echo $editando ? h($licenca['proprietario_nome']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="proprietario_cpf_cnpj">CPF / CNPJ</label>
                        <input type="text" name="proprietario_cpf_cnpj" id="proprietario_cpf_cnpj" class="form-control"
                               value="<?php echo $editando ? h($licenca['proprietario_cpf_cnpj']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="proprietario_endereco">Endereço</label>
                    <textarea name="proprietario_endereco" id="proprietario_endereco" class="form-control" rows="2"><?php echo $editando ? h($licenca['proprietario_endereco']) : ''; ?></textarea>
                </div>
            </div>
        </div>

        <!-- Seção 4: Estaleiro -->
        <div class="card mb-3">
            <div class="card-header"><h3><i class="fas fa-hard-hat"></i> Estaleiro / Construtor</h3></div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label for="estaleiro_nome">Nome / Razão Social</label>
                        <input type="text" name="estaleiro_nome" id="estaleiro_nome" class="form-control"
                               value="<?php echo $editando ? h($licenca['estaleiro_nome']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="estaleiro_cpf_cnpj">CPF / CNPJ</label>
                        <input type="text" name="estaleiro_cpf_cnpj" id="estaleiro_cpf_cnpj" class="form-control"
                               value="<?php echo $editando ? h($licenca['estaleiro_cpf_cnpj']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="estaleiro_endereco">Endereço</label>
                    <textarea name="estaleiro_endereco" id="estaleiro_endereco" class="form-control" rows="2"><?php echo $editando ? h($licenca['estaleiro_endereco']) : ''; ?></textarea>
                </div>
            </div>
        </div>

        <!-- Seção 5: Assinatura -->
        <div class="card mb-3">
            <div class="card-header"><h3><i class="fas fa-user-tie"></i> Responsável pela Assinatura</h3></div>
            <div class="card-body">
                <div class="grid-3">
                    <div class="form-group">
                        <label for="assinante_nome">Nome Completo</label>
                        <input type="text" name="assinante_nome" id="assinante_nome" class="form-control"
                               value="<?php echo $editando ? h($licenca['assinante_nome']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="assinante_titulo">Título/Cargo</label>
                        <input type="text" name="assinante_titulo" id="assinante_titulo" class="form-control" placeholder="Ex: Engenheira Naval"
                               value="<?php echo $editando ? h($licenca['assinante_titulo']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="assinante_registro">Registro Profissional</label>
                        <input type="text" name="assinante_registro" id="assinante_registro" class="form-control" placeholder="Ex: CREA: 22.482"
                               value="<?php echo $editando ? h($licenca['assinante_registro']) : ''; ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões -->
        <div class="card mb-3">
            <div class="card-footer" style="display: flex; gap: 10px; justify-content: flex-end;">
                <a href="<?php echo APP_URL; ?>documentacao/lc" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo $editando ? 'Atualizar' : 'Salvar'; ?> Licença</button>
            </div>
        </div>
    </form>
</div>

<script>
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
