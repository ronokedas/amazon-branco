<?php
/**
 * MODULO: AGENDAMENTOS
 * Arquivo: form.php - Formulario cadastro/edicao de agendamento
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
$cargo = getCargo();
exigirAcesso('agendamentos');

$id = $_GET['id'] ?? null;
$editando = !empty($id);
$relatorioOrigemId = trim((string)($_GET['relatorio_origem_id'] ?? ''));
$fluxoPropostaSolicitado = (string)($_GET['fluxo_proposta'] ?? '') === '1';

$agendamento = [
    'id'               => '',
    'proposta_id'      => '',
    'embarcacao_id'    => '',
    'cliente_id'       => '',
    'armador_id'       => '',
    'operador_nome'    => '',
    'vistoriador_id'   => '',
    'tipo_vistoria'    => '',
    'data_vistoria'    => '',
    'hora_vistoria'    => '',
    'local'            => '',
    'contato_nome'     => '',
    'contato_telefone' => '',
    'status'           => 'pendente',
    'observacoes'      => '',
    'relatorio_origem_id' => '',
    'vistoriador_origem_id' => '',
    'motivo_reatribuicao' => '',
    'retorno_tipo' => '',
];

if (!$editando && $relatorioOrigemId !== '') {
    if ($cargo !== 'ADMIN') {
        setMensagem('error', 'Somente o administrador pode agendar retorno de cumprimento A/S.');
        redirecionar(APP_URL . 'vistorias');
    }
    $stmtRetorno = $pdo->prepare("SELECT v.id,v.numero,v.agendamento_id,v.embarcacao_id,v.pessoa_id,v.armador_id,
            v.operador_nome,a.proposta_id,a.cliente_id,a.local,a.contato_nome,a.contato_telefone,a.vistoriador_id,
            vr.tipo retorno_tipo
        FROM vistorias v
        JOIN vistoria_retornos vr ON vr.relatorio_origem_id=v.id
        LEFT JOIN agendamentos a ON a.id=v.agendamento_id
        WHERE v.id=:id
          AND ((vr.tipo='AS' AND v.status='RETORNO_AS')
            OR (vr.tipo='EXIGENCIAS' AND v.status='APROVADA_COM_EXIGENCIAS'))
        LIMIT 1");
    $stmtRetorno->execute([':id' => $relatorioOrigemId]);
    $origemRetorno = $stmtRetorno->fetch(PDO::FETCH_ASSOC);
    if (!$origemRetorno) {
        setMensagem('error', 'O relatório informado não possui A/S pendente para agendamento.');
        redirecionar(APP_URL . 'vistorias');
    }
    $stmtPendencia = $pdo->prepare("SELECT status FROM vistoria_retornos WHERE relatorio_origem_id=:id LIMIT 1");
    $stmtPendencia->execute([':id' => $relatorioOrigemId]);
    if ($stmtPendencia->fetchColumn() !== 'PENDENTE_AGENDAMENTO') {
        setMensagem('error', 'Este retorno A/S não está disponível para um novo agendamento.');
        redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode((string)($origemRetorno['agendamento_id'] ?? '')) . '&vistoria_id=' . urlencode($relatorioOrigemId));
    }
    $agendamento = array_merge($agendamento, [
        'proposta_id' => $origemRetorno['proposta_id'] ?? '',
        'embarcacao_id' => $origemRetorno['embarcacao_id'],
        'cliente_id' => $origemRetorno['cliente_id'] ?: $origemRetorno['pessoa_id'],
        'armador_id' => $origemRetorno['armador_id'] ?? '',
        'operador_nome' => $origemRetorno['operador_nome'] ?? '',
        'tipo_vistoria' => ($origemRetorno['retorno_tipo'] === 'AS'
            ? 'Cumprimento de A/S'
            : 'Retorno de exigências') . ' — relatório ' . ($origemRetorno['numero'] ?: $relatorioOrigemId),
        'local' => $origemRetorno['local'] ?? '',
        'contato_nome' => $origemRetorno['contato_nome'] ?? '',
        'contato_telefone' => $origemRetorno['contato_telefone'] ?? '',
        'observacoes' => $origemRetorno['retorno_tipo'] === 'AS'
            ? 'Retorno obrigatório para verificar o cumprimento das exigências A/S.'
            : 'Retorno para verificar o cumprimento das exigências pendentes.',
        'relatorio_origem_id' => $relatorioOrigemId,
        'vistoriador_id' => $origemRetorno['vistoriador_id'] ?? '',
        'vistoriador_origem_id' => $origemRetorno['vistoriador_id'] ?? '',
        'retorno_tipo' => $origemRetorno['retorno_tipo'],
    ]);
}

if ($editando) {
    if ($cargo === 'VISTORIADOR') {
        setMensagem('error', 'Acesso negado. Vistoriadores não podem editar agendamentos.');
        redirecionar(APP_URL . 'agendamentos');
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dados) {
            if (!empty($dados['relatorio_origem_id']) && $cargo !== 'ADMIN') {
                setMensagem('error', 'Somente o administrador pode editar um retorno A/S.');
                redirecionar(APP_URL . 'agendamentos');
            }
            $agendamento = array_merge($agendamento, $dados);
            if (!empty($dados['relatorio_origem_id'])) {
                $stmtAuditRetorno = $pdo->prepare("SELECT vr.vistoriador_origem_id,vr.motivo_reatribuicao,
                        a0.vistoriador_id vistoriador_original
                    FROM vistoria_retornos vr
                    JOIN vistorias v0 ON v0.id=vr.relatorio_origem_id
                    LEFT JOIN agendamentos a0 ON a0.id=v0.agendamento_id
                    WHERE vr.agendamento_id=:agendamento LIMIT 1");
                $stmtAuditRetorno->execute([':agendamento' => $id]);
                $auditRetorno = $stmtAuditRetorno->fetch(PDO::FETCH_ASSOC) ?: [];
                $agendamento['vistoriador_origem_id'] = $auditRetorno['vistoriador_origem_id']
                    ?? $auditRetorno['vistoriador_original']
                    ?? '';
                $agendamento['motivo_reatribuicao'] = $auditRetorno['motivo_reatribuicao'] ?? '';
            }
        } else {
            setMensagem('error', 'Agendamento não encontrado.');
            redirecionar(APP_URL . 'agendamentos');
        }
    } catch (Exception $e) {
        error_log('Erro ao carregar agendamento: ' . $e->getMessage());
        setMensagem('error', 'Erro ao carregar dados do agendamento.');
        redirecionar(APP_URL . 'agendamentos');
    }
}

try {
    $clientes = $pdo->query("SELECT id, nome, cpf_cnpj FROM clientes WHERE status = 'ATIVO' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $armadores = $pdo->query("SELECT id, nome, cpf_cnpj FROM clientes WHERE status = 'ATIVO' AND perfil = 'armador' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

    $propostas = $pdo->query("
        SELECT p.id, p.numero, p.responsavel_fechamento_nome, p.responsavel_fechamento_telefone,
               c.nome AS cliente_nome, p.valor_total
        FROM propostas p
        INNER JOIN clientes c ON p.cliente_id = c.id
        WHERE p.status IN ('enviada','aprovada','assinada')
        ORDER BY p.data_emissao DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $vistoriadores = [];
    if ($cargo === 'ADMIN' || $cargo === 'VENDEDOR') {
        $vistoriadores = $pdo->query("SELECT id, nome FROM usuarios WHERE ativo = 1 AND cargo = 'VISTORIADOR' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    $servicos = $pdo->query("SELECT id, nome FROM servicos WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $embarcacoes = $pdo->query("SELECT id, nome, registro FROM embarcacoes WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao carregar listas: ' . $e->getMessage());
    $clientes = [];
    $armadores = [];
    $propostas = [];
    $vistoriadores = [];
    $servicos = [];
    $embarcacoes = [];
}

$titulo_page = ($editando ? 'Editar' : 'Novo') . ' Agendamento - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

$servicosTravados = !empty($agendamento['proposta_id']);
$origemTravada = ($editando && !empty($agendamento['proposta_id'])) || !empty($agendamento['relatorio_origem_id']);
$fluxoProposta = $fluxoPropostaSolicitado && $editando && !empty($agendamento['proposta_id']);
$horaSelecionada = !empty($agendamento['hora_vistoria']) ? substr($agendamento['hora_vistoria'], 0, 5) : '';

?>

<style>
.readonly-field {
    background: var(--cor-sidebar, #f5f7f8);
    cursor: not-allowed;
}
</style>

<div class="conteudo-principal flow-shell">
    <div class="flow-hero">
        <div>
            <span class="flow-eyebrow"><i class="fas fa-route"></i> Etapa 2 do fluxo</span>
            <h1><?php echo $editando ? 'Editar Agendamento' : 'Novo Agendamento'; ?></h1>
            <p>Transforme a proposta assinada em uma vistoria clara para a equipe: cliente, embarcação, data, responsável e orientações do local.</p>
        </div>
        <div class="flow-actions">
            <a href="<?php echo APP_URL; ?>agendamentos" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="flow-track">
        <div class="flow-track-step"><span>01</span>Proposta</div>
        <div class="flow-track-step is-active"><span>02</span>Agendamento</div>
        <div class="flow-track-step"><span>03</span>Vistoria</div>
        <div class="flow-track-step"><span>04</span>Aprovação</div>
        <div class="flow-track-step"><span>05</span>Certificados</div>
    </div>

    <div class="form-container">
        <div class="form-header">
            <h3><i class="fas fa-calendar-check"></i> <?php echo $editando ? 'Editar Agendamento' : 'Novo Agendamento'; ?></h3>
            <span class="help-text">Campos com * são obrigatórios</span>
        </div>

        <form action="<?php echo APP_URL; ?>agendamentos/actions" method="POST" class="form-padrao">
            <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
            <input type="hidden" name="action" value="<?php echo $editando ? 'editar' : 'inserir'; ?>">
            <input type="hidden" name="relatorio_origem_id" value="<?php echo h($agendamento['relatorio_origem_id']); ?>">
            <input type="hidden" id="vistoriador_origem_id" name="vistoriador_origem_id" value="<?php echo h($agendamento['vistoriador_origem_id']); ?>">
            <input type="hidden" name="fluxo_proposta" value="<?php echo $fluxoProposta ? '1' : '0'; ?>">
            <?php if (!empty($agendamento['relatorio_origem_id'])): ?>
                <div class="alert alert-warning">
                    <strong><?php echo ($agendamento['retorno_tipo'] ?? '') === 'AS'
                        ? 'Retorno obrigatório A/S.'
                        : 'Retorno de exigências.'; ?></strong>
                    Este agendamento gerará uma nova OS e um novo relatório numerado, vinculado ao relatório de origem.
                </div>
            <?php endif; ?>
            <?php if ($cargo === 'VENDEDOR'): ?>
                <input type="hidden" name="vendedor_id" value="<?php echo h($_SESSION['usuario_id']); ?>">
            <?php endif; ?>
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?php echo h($agendamento['id']); ?>">
            <?php endif; ?>
            <?php if ($editando && !$origemTravada): ?>
                <input type="hidden" name="armador_id" value="<?php echo h($agendamento['armador_id']); ?>">
            <?php endif; ?>
            <?php if ($origemTravada): ?>
                <input type="hidden" name="proposta_id" value="<?php echo h($agendamento['proposta_id']); ?>">
                <input type="hidden" name="armador_id" value="<?php echo h($agendamento['armador_id']); ?>">
                <input type="hidden" name="operador_nome" value="<?php echo h($agendamento['operador_nome']); ?>">
                <input type="hidden" name="cliente_id" value="<?php echo h($agendamento['cliente_id']); ?>">
                <input type="hidden" name="embarcacao_id" value="<?php echo h($agendamento['embarcacao_id']); ?>">
            <?php endif; ?>

            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-file-signature"></i> Origem do serviço</h4>
                <p class="form-section-hint">Quando houver proposta vinculada, o sistema preenche cliente, embarcação e serviços automaticamente.</p>
                <div class="form-row">
                    <div class="form-group col-12">
                        <label for="proposta_id"><i class="fas fa-file-invoice"></i> Proposta vinculada</label>
                        <select id="proposta_id" name="proposta_id" onchange="carregarDadosProposta(this.value)" <?php echo $origemTravada ? 'disabled' : ''; ?>>
                            <option value="">-- Sem proposta vinculada --</option>
                            <?php foreach ($propostas as $prop): ?>
                                <option value="<?php echo h($prop['id']); ?>"
                                        data-cliente="<?php echo h($prop['cliente_nome']); ?>"
                                        data-contato-nome="<?php echo h($prop['responsavel_fechamento_nome'] ?? ''); ?>"
                                        data-contato-telefone="<?php echo h($prop['responsavel_fechamento_telefone'] ?? ''); ?>"
                                        <?php echo $agendamento['proposta_id'] === $prop['id'] ? 'selected' : ''; ?>>
                                    <?php echo h($prop['numero']); ?> — <?php echo h($prop['cliente_nome']); ?> (<?php echo formatarMoeda($prop['valor_total']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Use preferencialmente propostas já assinadas para manter a esteira comercial organizada.</small>
                    </div>
                    <?php if (!$editando): ?>
                        <div class="form-group col-6">
                            <label for="armador_id">Armador responsável</label>
                            <select id="armador_id" name="armador_id" <?php echo $origemTravada ? 'disabled' : ''; ?>>
                                <option value="">-- Selecione o armador, se houver --</option>
                                <?php foreach ($armadores as $arm): ?>
                                    <option value="<?php echo h($arm['id']); ?>"
                                            <?php echo ($agendamento['armador_id'] ?? '') === $arm['id'] ? 'selected' : ''; ?>>
                                        <?php echo h($arm['nome']); ?> <?php echo $arm['cpf_cnpj'] ? '(' . h($arm['cpf_cnpj']) . ')' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small>Pessoa responsável pela operação da embarcação no dia da vistoria.</small>
                        </div>
                        <div class="form-group col-6">
                            <label for="operador_nome">Respons&aacute;vel presente na vistoria</label>
                            <input type="text" id="operador_nome" name="operador_nome" maxlength="255"
                                   value="<?php echo h($agendamento['operador_nome']); ?>"
                                   placeholder="Nome do funcion&aacute;rio/operador, se houver">
                            <small>Esse nome ser&aacute; levado para o relat&oacute;rio de vistoria.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-user-anchor"></i> Cliente e embarcação</h4>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="cliente_id">Cliente *</label>
                        <select id="cliente_id" name="cliente_id" required onchange="carregarEmbarcacoesCliente(this.value)" <?php echo $origemTravada ? 'disabled' : ''; ?>>
                            <option value="">-- Selecione o cliente --</option>
                            <?php foreach ($clientes as $cli): ?>
                                <option value="<?php echo h($cli['id']); ?>"
                                        <?php echo $agendamento['cliente_id'] === $cli['id'] ? 'selected' : ''; ?>>
                                    <?php echo h($cli['nome']); ?> <?php echo $cli['cpf_cnpj'] ? '(' . h($cli['cpf_cnpj']) . ')' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-6">
                        <label for="embarcacao_id">Embarcação *</label>
                        <select id="embarcacao_id" name="embarcacao_id" required <?php echo $origemTravada ? 'disabled' : ''; ?>>
                            <option value="">-- Selecione a embarcação --</option>
                            <?php foreach ($embarcacoes as $emb): ?>
                                <option value="<?php echo h($emb['id']); ?>"
                                        <?php echo $agendamento['embarcacao_id'] === $emb['id'] ? 'selected' : ''; ?>>
                                    <?php echo h($emb['nome']); ?> <?php echo $emb['registro'] ? '(' . h($emb['registro']) . ')' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-clipboard-check"></i> Execução da vistoria</h4>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="tipo_vistoria">Tipo de vistoria / serviços *</label>
                        <textarea id="tipo_vistoria" name="tipo_vistoria" required rows="2" placeholder="Ex.: CSN inicial, CNBL, arqueação, convalidação..."><?php echo h($agendamento['tipo_vistoria']); ?></textarea>
                    </div>
                    <div class="form-group col-6">
                        <label for="vistoriador_id">Vistoriador responsável *</label>
                        <?php if ($cargo === 'ADMIN' || $cargo === 'VENDEDOR'): ?>
                            <select id="vistoriador_id" name="vistoriador_id" required>
                                <option value="">-- Selecione o vistoriador --</option>
                                <?php foreach ($vistoriadores as $v): ?>
                                    <option value="<?php echo h($v['id']); ?>"
                                            <?php echo $agendamento['vistoriador_id'] === $v['id'] ? 'selected' : ''; ?>>
                                        <?php echo h($v['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" class="form-control" readonly value="<?php echo h($_SESSION['usuario_nome'] ?? 'Você (VISTORIADOR)'); ?>">
                            <input type="hidden" name="vistoriador_id" value="<?php echo h($_SESSION['usuario_id']); ?>">
                            <small>Como vistoriador, você será automaticamente atribuído.</small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($agendamento['relatorio_origem_id'])): ?>
                    <div class="form-row">
                        <div class="form-group col-12">
                            <label for="motivo_reatribuicao">Motivo da troca de vistoriador</label>
                            <textarea id="motivo_reatribuicao" name="motivo_reatribuicao" rows="2"
                                      placeholder="Obrigatório somente se o responsável pelo retorno for diferente do vistoriador anterior."><?php echo h($agendamento['motivo_reatribuicao']); ?></textarea>
                            <small>O mesmo vistoriador é mantido por padrão. Toda substituição ficará registrada para auditoria.</small>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="data_vistoria">Data da vistoria *</label>
                        <input type="date" id="data_vistoria" name="data_vistoria" required
                               value="<?php echo h($agendamento['data_vistoria']); ?>"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group col-3">
                        <label for="hora_vistoria">Hora</label>
                        <input type="time" id="hora_vistoria" name="hora_vistoria" value="<?php echo h($agendamento['hora_vistoria']); ?>">
                    </div>
                    <div class="form-group col-5">
                        <label for="local">Local</label>
                        <input type="text" id="local" name="local" value="<?php echo h($agendamento['local']); ?>" placeholder="Endereço / estaleiro / porto">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h4 class="form-section-title"><i class="fas fa-phone-volume"></i> Contato e orientações</h4>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="contato_nome">Responsável pelo fechamento / contato</label>
                        <input type="text" id="contato_nome" name="contato_nome" value="<?php echo h($agendamento['contato_nome']); ?>" placeholder="Pessoa de contato">
                    </div>
                    <div class="form-group col-6">
                        <label for="contato_telefone">Telefone do responsável</label>
                        <input type="text" id="contato_telefone" name="contato_telefone"
                               value="<?php echo h($agendamento['contato_telefone']); ?>"
                               placeholder="(91) 99999-9999"
                               oninput="mascararTelefone(this)">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-12">
                        <label for="observacoes">Observações para a equipe</label>
                        <textarea id="observacoes" name="observacoes" rows="3" placeholder="Instruções, materiais necessários, pontos de atenção, acesso ao local..."><?php echo h($agendamento['observacoes']); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?php echo APP_URL; ?>agendamentos" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <?php echo $editando ? 'Atualizar agendamento' : 'Salvar agendamento'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
var embarcacoesOriginais = null;
var servicosIniciamTravados = <?php echo $servicosTravados ? 'true' : 'false'; ?>;

function atualizarEstadoServicosTravados(travado) {
    const campoVistoria = document.getElementById('tipo_vistoria');
    let hint = document.getElementById('servicosTravadosHint');
    if (!campoVistoria) return;

    campoVistoria.readOnly = travado;
    campoVistoria.classList.toggle('readonly-field', travado);

    if (!hint) {
        hint = document.createElement('small');
        hint.id = 'servicosTravadosHint';
        hint.textContent = 'Serviços vindos da proposta vinculada. Para alterar, ajuste a proposta antes do agendamento.';
        campoVistoria.insertAdjacentElement('afterend', hint);
    }
    hint.style.display = travado ? '' : 'none';
}

function transformarHoraEmSelecao() {
    const campoHora = document.getElementById('hora_vistoria');
    if (!campoHora || campoHora.tagName.toLowerCase() === 'select') return;

    const valorAtual = (campoHora.value || '').slice(0, 5);
    const selectHora = document.createElement('select');
    selectHora.id = campoHora.id;
    selectHora.name = campoHora.name;

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '-- Selecione --';
    selectHora.appendChild(placeholder);

    for (let minutos = 0; minutos < 24 * 60; minutos += 30) {
        const hora = String(Math.floor(minutos / 60)).padStart(2, '0');
        const minuto = String(minutos % 60).padStart(2, '0');
        const valor = `${hora}:${minuto}`;
        const option = document.createElement('option');
        option.value = valor;
        option.textContent = valor;
        option.selected = valorAtual === valor;
        selectHora.appendChild(option);
    }

    campoHora.replaceWith(selectHora);
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

function carregarDadosProposta(propostaId) {
    const selectEmbarcacao = document.getElementById('embarcacao_id');
    const selectCliente = document.getElementById('cliente_id');
    const selectArmador = document.getElementById('armador_id');
    const campoOperador = document.getElementById('operador_nome');
    const campoContatoNome = document.getElementById('contato_nome');
    const campoContatoTelefone = document.getElementById('contato_telefone');
    const campoVistoria = document.getElementById('tipo_vistoria');
    if (!propostaId) {
        if (selectCliente) selectCliente.value = '';
        if (selectArmador) selectArmador.value = '';
        if (campoOperador) campoOperador.value = '';
        if (campoContatoNome) campoContatoNome.value = '';
        if (campoContatoTelefone) campoContatoTelefone.value = '';
        if (campoVistoria) campoVistoria.value = '';
        atualizarEstadoServicosTravados(false);
        restaurarEmbarcacoes();
        return;
    }
    if (!embarcacoesOriginais && selectEmbarcacao) {
        embarcacoesOriginais = selectEmbarcacao.innerHTML;
    }
    fetch('<?php echo APP_URL; ?>agendamentos/actions?action=buscar_proposta&proposta_id=' + encodeURIComponent(propostaId))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.cliente_id && selectCliente) {
                    selectCliente.value = data.cliente_id;
                }
                if (campoContatoNome) campoContatoNome.value = data.contato_nome || '';
                if (campoContatoTelefone) campoContatoTelefone.value = data.contato_telefone || '';
                if (selectEmbarcacao && data.embarcacoes && data.embarcacoes.length > 0) {
                    let options = '<option value="">Selecione a embarcação</option>';
                    data.embarcacoes.forEach(function(emb) {
                        const selected = (String(emb.id) === String(data.embarcacao_id)) ? ' selected' : '';
                        options += '<option value="' + emb.id + '"' + selected + '>' + emb.nome + '</option>';
                    });
                    selectEmbarcacao.innerHTML = options;
                }
                if (campoVistoria && data.tipo_vistoria) {
                    campoVistoria.value = data.tipo_vistoria;
                    atualizarEstadoServicosTravados(true);
                }
            }
        })
        .catch(err => console.error('Erro ao carregar proposta:', err));
}

function carregarEmbarcacoesCliente(clienteId) {
    const selectEmbarcacao = document.getElementById('embarcacao_id');
    if (!selectEmbarcacao) return;
    if (!clienteId) {
        restaurarEmbarcacoes();
        return;
    }

    if (!embarcacoesOriginais) {
        embarcacoesOriginais = selectEmbarcacao.innerHTML;
    }

    selectEmbarcacao.innerHTML = '<option value="">Carregando embarcações...</option>';

    fetch('<?php echo APP_URL; ?>ajax/busca_embarcacoes.php?cliente_id=' + encodeURIComponent(clienteId))
        .then(response => response.json())
        .then(data => {
            const lista = Array.isArray(data) ? data : (data.embarcacoes || []);
            let options = '<option value="">-- Selecione a embarcação --</option>';
            lista.forEach(function(emb) {
                options += '<option value="' + emb.id + '">' + emb.nome + (emb.registro ? ' (' + emb.registro + ')' : '') + '</option>';
            });
            selectEmbarcacao.innerHTML = options;
        })
        .catch(() => {
            restaurarEmbarcacoes();
        });
}

function restaurarEmbarcacoes() {
    const selectEmbarcacao = document.getElementById('embarcacao_id');
    if (selectEmbarcacao && embarcacoesOriginais) {
        selectEmbarcacao.innerHTML = embarcacoesOriginais;
    }
}

transformarHoraEmSelecao();
atualizarEstadoServicosTravados(servicosIniciamTravados);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
