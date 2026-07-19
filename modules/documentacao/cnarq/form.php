<?php
/**
 * MÓDULO: Documentação > Certificados CNARQ
 * Formulário de Criação/Edição do Certificado Nacional de Arqueação
 * Dados da embarcação puxados automaticamente do cadastro
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

// Se tem ID, é edição
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $editando = true;

    $stmt = $pdo->prepare("SELECT * FROM certificados_cnarq WHERE id = :id AND ativo = 1");
    $stmt->execute([':id' => $id]);
    $certificado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$certificado) {
        setMensagem('error', 'Certificado não encontrado.');
        redirecionar(APP_URL . 'documentacao/cnarq');
    }

}

if (!$editando) {
    header('Location: ' . APP_URL . 'certificados');
    exit;
}

// Gerar próximo número (se não estiver editando)
$proximo_numero = '';
if (!$editando) {
    $ano = date('y');
    $ano4 = date('Y');
    $stmt_num = $pdo->prepare('SELECT COUNT(*) as total FROM certificados_cnarq WHERE YEAR(criado_em) = :ano');
    $stmt_num->execute([':ano' => $ano4]);
    $total = $stmt_num->fetch()['total'];
    $seq = $total + 1;
    $proximo_numero = "AM-CNARQ-{$seq}/{$ano}";
}

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
}// Buscar lista de embarcações ativas para o select
$stmt_emb = $pdo->prepare("SELECT id, nome, tipo, registro
                           FROM embarcacoes WHERE ativo = 1 ORDER BY nome");
$stmt_emb->execute();
$embarcacoes = $stmt_emb->fetchAll(PDO::FETCH_ASSOC);


// Buscar lista de despachantes ativos
$stmt_desp = $pdo->prepare("SELECT id, nome FROM clientes WHERE perfil = 'despachante' AND status = 'ATIVO' ORDER BY nome");
$stmt_desp->execute();
$despachantes_list = $stmt_desp->fetchAll(PDO::FETCH_ASSOC);

$titulo_page = ($editando ? 'Editar' : 'Novo') . ' Certificado CNARQ - ' . APP_NAME;
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-header">
        <h2>
            <i class="fas fa-file-certificate"></i> 
            <?php echo $editando ? 'Editar Certificado CNARQ' : 'Novo Certificado CNARQ'; ?>
        </h2>
        <a href="<?php echo APP_URL; ?>documentacao/cnarq" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Se já estiver assinado, mostrar aviso -->
    <?php if ($editando && $certificado['assinado']): ?>
        <div class="card mb-3" style="border-left: 4px solid var(--cor-destaque);">
            <div class="card-body">
                <p style="margin:0;">
                    <i class="fas fa-lock" style="color: var(--cor-destaque);"></i>
                    <strong>Este certificado já foi assinado digitalmente.</strong><br>
                    Assinado por: <?php echo h($certificado['assinante_nome']); ?> em 
                    <?php echo formatarDataCompleta($certificado['assinatura_em']); ?> 
                    (IP: <?php echo h($certificado['assinatura_ip']); ?>)
                </p>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo APP_URL; ?>documentacao/cnarq/actions" id="formCertificado">
        <input type="hidden" name="action" value="salvar">
        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?php echo h($certificado['id']); ?>">
        <?php endif; ?>

        <!-- SEÇÃO 1: Identificação do Certificado -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-id-card"></i> Identificação do Certificado</h3>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Número do Certificado</label>
                        <input type="text" class="form-control" value="<?php echo $editando ? h($certificado['numero']) : h($proximo_numero); ?>" readonly 
                               style="background: var(--cor-sidebar); font-weight: bold;">
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
            </div>
        </div>

        <!-- SEÇÃO 2: Dados da Embarcação (puxados automaticamente) -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-ship"></i> Dados da Embarcação</h3>
            </div>
            <div class="card-body">
                <?php if (!$editando): ?>
                <!-- Select de embarcação para puxar dados automaticamente -->
                <div class="form-group">
                    <label for="embarcacao_id"><i class="fas fa-search"></i> Selecionar Embarcação do Cadastro</label>
                     <select id="embarcacao_id" class="form-control" onchange="carregarDadosEmbarcacao(this.value)">
                        <option value="">-- Selecione uma embarcação --</option>
                        <?php foreach ($embarcacoes as $emb): ?>
                            <option value="<?php echo h($emb['id']); ?>"
                                data-nome="<?php echo h($emb['nome']); ?>"
                                data-tipo="<?php echo h($emb['tipo']); ?>"
                                data-numero_inscricao="<?php echo h($emb['numero_inscricao'] ?? ''); ?>"
                                data-indicativo_chamada="<?php echo h($emb['indicativo_chamada'] ?? ''); ?>"
                                data-comprimento_total="<?php echo h($emb['comprimento_total'] ?? ''); ?>"
                                data-comprimento_casco="<?php echo h($emb['comprimento_casco'] ?? ''); ?>"
                                data-boca_moldada="<?php echo h($emb['boca_moldada'] ?? ''); ?>"
                                data-pontal_moldado="<?php echo h($emb['pontal_moldado'] ?? ''); ?>"
                                data-arqueacao_bruta="<?php echo h($emb['arqueacao_bruta'] ?? ''); ?>"
                                <?php echo (!empty($_GET['agendamento_id']) && isset($dadosPre['embarcacao_id']) && $dadosPre['embarcacao_id'] == $emb['id']) ? 'selected' : ''; ?>>
                                <?php echo h($emb['nome']) . ' (' . h($emb['tipo']) . ' - ' . h($emb['registro']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Ao selecionar, os dados básicos da embarcação serão preenchidos automaticamente.</small>
                </div>
                <hr>
                <?php endif; ?>

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
                    <div class="form-group">
                        <label for="material_casco">Material do Casco</label>
                        <input type="text" name="material_casco" id="material_casco" class="form-control"
                               value="<?php echo $editando ? h($certificado['material_casco']) : h($preenchimento['material_casco']); ?>">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="porto_inscricao">Porto de Inscrição</label>
                        <input type="text" name="porto_inscricao" id="porto_inscricao" class="form-control"
                               value="<?php echo $editando ? h($certificado['porto_inscricao']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="local_construcao">Local de Construção</label>
                        <input type="text" name="local_construcao" id="local_construcao" class="form-control"
                               value="<?php echo $editando ? h($certificado['local_construcao']) : ''; ?>">
                    </div>
                </div>

                <!-- Dimensões para Arqueação -->
                <div class="grid-3">
                    <div class="form-group">
                        <label for="comprimento_total">Comprimento Total (m)</label>
                        <input type="number" name="comprimento_total" id="comprimento_total" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($certificado['comprimento_total']) : h($preenchimento['comprimento_total']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="comprimento_casco">Comprimento do Casco (m)</label>
                        <input type="number" name="comprimento_casco" id="comprimento_casco" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($certificado['comprimento_casco']) : h($preenchimento['comprimento_casco']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="comprimento_lpp">Comprimento LPP (m)</label>
                        <input type="number" name="comprimento_lpp" id="comprimento_lpp" class="form-control" step="0.01"
                               placeholder="Entre perpendiculares"
                               value="<?php echo $editando ? h($certificado['comprimento_lpp'] ?? '') : ''; ?>">
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label for="boca_moldada">Boca Moldada (m)</label>
                        <input type="number" name="boca_moldada" id="boca_moldada" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($certificado['boca_moldada']) : h($preenchimento['boca_moldada']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="boca_maxima">Boca Máxima (m)</label>
                        <input type="number" name="boca_maxima" id="boca_maxima" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($certificado['boca_maxima'] ?? '') : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="pontal_moldado">Pontal Moldado (m)</label>
                        <input type="number" name="pontal_moldado" id="pontal_moldado" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($certificado['pontal_moldado']) : h($preenchimento['pontal_moldado']); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 3: Arqueação -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-balance-scale"></i> Arqueação</h3>
            </div>
            <div class="card-body">
                <div class="grid-3">
                    <div class="form-group">
                        <label for="arqueacao_bruta">Arqueação Bruta (AB)</label>
                        <input type="number" name="arqueacao_bruta" id="arqueacao_bruta" class="form-control" step="0.01" min="0"
                               value="<?php echo $editando ? h($certificado['arqueacao_bruta']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="arqueacao_liquida">Arqueação Líquida (AL)</label>
                        <input type="number" name="arqueacao_liquida" id="arqueacao_liquida" class="form-control" step="0.01" min="0"
                               value="<?php echo $editando ? h($certificado['arqueacao_liquida']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="metodo_arqueacao">Método de Arqueação</label>
                        <select name="metodo_arqueacao" id="metodo_arqueacao" class="form-control">
                            <option value="">-- Selecione --</option>
                            <?php
                            $metodos = ['NORMAM-202', 'Convenção Internacional', 'Lei nº 9.537/1997', 'Outro'];
                            $selected_met = $editando ? $certificado['metodo_arqueacao'] : '';
                            foreach ($metodos as $met):
                            ?>
                                <option value="<?php echo $met; ?>" <?php echo $selected_met === $met ? 'selected' : ''; ?>>
                                    <?php echo $met; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 4: Vistoria e Certificação -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-clipboard-check"></i> Vistoria e Certificação</h3>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label for="relatorio_numero">N° Relatório de Vistorias</label>
                        <input type="text" name="relatorio_numero" id="relatorio_numero" class="form-control"
                               placeholder="Ex: AM-REL-AP:100/26"
                               value="<?php echo $editando ? h($certificado['relatorio_numero'] ?? '') : h($preenchimento['relatorio_numero']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="local_vistoria">Local da Vistoria</label>
                        <input type="text" name="local_vistoria" id="local_vistoria" class="form-control"
                               value="<?php echo $editando ? h($certificado['local_vistoria'] ?? '') : ''; ?>">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="data_vistoria">Data da Vistoria</label>
                        <input type="date" name="data_vistoria" id="data_vistoria" class="form-control"
                               value="<?php echo $editando ? h($certificado['data_vistoria'] ?? '') : ''; ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 5: Responsável pela Assinatura -->
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
                <a href="<?php echo APP_URL; ?>documentacao/cnarq" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> <?php echo $editando ? 'Atualizar Certificado' : 'Salvar Certificado'; ?>
                </button>
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
