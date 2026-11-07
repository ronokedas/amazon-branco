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

$relatorioOrigemId = trim((string)($_GET['relatorio_origem_id'] ?? ''));

$fluxoPropostaSolicitado = (string)($_GET['fluxo_proposta'] ?? '') === '1';



// Se não veio id mas veio proposta_id, buscar se já existe agendamento pendente para esta proposta

if (empty($id) && !empty($_GET['proposta_id'])) {

    $propostaIdGet = trim((string)$_GET['proposta_id']);

    if (function_exists('proximoAgendamentoPendenteProposta')) {

        $agendExistente = proximoAgendamentoPendenteProposta($pdo, $propostaIdGet);

        if ($agendExistente) {

            $id = $agendExistente;

        }

    } else {

        $stmtCheck = $pdo->prepare("SELECT id FROM agendamentos WHERE proposta_id = :prop AND status = 'pendente' LIMIT 1");

        $stmtCheck->execute([':prop' => $propostaIdGet]);

        $id = $stmtCheck->fetchColumn() ?: null;

    }

}

$editando = !empty($id);



$agendamento = [

    'id'               => '',

    'proposta_id'      => trim((string)($_GET['proposta_id'] ?? '')),

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

    'analista_id' => '',

    'prazo_analise' => '',

];



if (!$editando && $relatorioOrigemId !== '') {

    if (!in_array($cargo, ['ADMIN', 'SECRETARIA'], true)) {

        setMensagem('error', 'Somente o administrador ou secretaria pode agendar retorno de cumprimento A/S.');

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



if (!$editando && empty($relatorioOrigemId)) {

    if (!empty($_GET['cliente_id'])) {

        $agendamento['cliente_id'] = trim((string)$_GET['cliente_id']);

    }

    if (!empty($_GET['embarcacao_id'])) {

        $agendamento['embarcacao_id'] = trim((string)$_GET['embarcacao_id']);

    }

    if (!empty($_GET['tipo_vistoria'])) {

        $agendamento['tipo_vistoria'] = trim((string)$_GET['tipo_vistoria']);

    }

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

            if (!empty($dados['relatorio_origem_id']) && !in_array($cargo, ['ADMIN', 'SECRETARIA'], true)) {

                setMensagem('error', 'Somente o administrador ou secretaria pode editar um retorno A/S.');

                redirecionar(APP_URL . 'agendamentos');

            }

            $agendamento = array_merge($agendamento, $dados);

            if (empty($agendamento['analista_id']) && !empty($agendamento['proposta_id'])) {

                $stmtApCheck = $pdo->prepare("SELECT analista_id, prazo_agendado_em FROM analises_planos WHERE proposta_id = :prop ORDER BY criado_em DESC LIMIT 1");

                $stmtApCheck->execute([':prop' => $agendamento['proposta_id']]);

                $apDados = $stmtApCheck->fetch(PDO::FETCH_ASSOC);

                if ($apDados) {

                    $agendamento['analista_id'] = $apDados['analista_id'] ?? '';

                    $agendamento['prazo_analise'] = $apDados['prazo_agendado_em'] ?? '';

                }

            }

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

    if (in_array($cargo, ['ADMIN', 'SECRETARIA', 'VENDEDOR'], true)) {

        $vistoriadores = $pdo->query("SELECT DISTINCT u.id, u.nome, u.email, u.status_sgq, u.credencial_marinha_numero, u.credencial_marinha_validade, u.registro_conselho_tipo, u.registro_conselho_validade FROM usuarios u LEFT JOIN usuario_perfis up ON up.usuario_id = u.id WHERE u.ativo = 1 AND (u.cargo = 'VISTORIADOR' OR up.perfil = 'VISTORIADOR') ORDER BY u.nome ASC")->fetchAll(PDO::FETCH_ASSOC);

    } else {

        $vistoriadores = [['id' => $_SESSION['usuario_id'], 'nome' => $_SESSION['usuario_nome'] ?? 'Você', 'email' => $_SESSION['usuario_email'] ?? '']];

    }



    $servicos = $pdo->query("SELECT id, nome FROM servicos WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

    $embarcacoes = $pdo->query("SELECT id, nome, registro FROM embarcacoes WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

    $analistas = [];

    if (in_array($cargo, ['ADMIN', 'SECRETARIA', 'VENDEDOR'], true)) {

        $analistas = $pdo->query("SELECT DISTINCT u.id, u.nome, u.email FROM usuarios u LEFT JOIN usuario_perfis up ON up.usuario_id = u.id WHERE u.ativo = 1 AND u.excluido_em IS NULL AND (u.cargo = 'ANALISTA' OR up.perfil = 'ANALISTA') ORDER BY u.nome ASC")->fetchAll(PDO::FETCH_ASSOC);

    }

} catch (Exception $e) {

    error_log('Erro ao carregar listas: ' . $e->getMessage());

    $clientes = [];

    $armadores = [];

    $propostas = [];

    $vistoriadores = [];

    $servicos = [];

    $embarcacoes = [];

    $analistas = [];

}



$titulo_page = ($editando ? 'Editar' : 'Novo') . ' Agendamento - ERP Sistema';

require_once __DIR__ . '/../../includes/header.php';

require_once __DIR__ . '/../../includes/sidebar.php';



$servicosTravados = !empty($agendamento['proposta_id']);

$origemTravada = ($editando && !empty($agendamento['proposta_id'])) || !empty($agendamento['relatorio_origem_id']);

$fluxoProposta = $fluxoPropostaSolicitado && $editando && !empty($agendamento['proposta_id']);

$horaSelecionada = !empty($agendamento['hora_vistoria']) ? substr($agendamento['hora_vistoria'], 0, 5) : '';

$inicialTemAnalise = !empty($agendamento['analista_id']) || stripos((string)$agendamento['tipo_vistoria'], 'Análise de Planos') !== false || stripos((string)$agendamento['tipo_vistoria'], 'Analise de Planos') !== false;

$inicialTemVistoria = empty($agendamento['proposta_id']) || !empty($agendamento['vistoriador_id']) || stripos((string)$agendamento['tipo_vistoria'], 'Vistoria') !== false || !$inicialTemAnalise;





?>



<style>

.readonly-field {

    background: var(--cor-sidebar, #f5f7f8);

    cursor: not-allowed;

}

.multi-vessel-wrapper {

    margin-top: 15px;

    margin-bottom: 25px;

}

.multi-vessel-banner {

    background: #f0fdf4;

    border: 1px solid #86efac;

    border-radius: 12px;

    padding: 16px 20px;

    margin-bottom: 20px;

    display: flex;

    align-items: center;

    gap: 16px;

    box-shadow: 0 1px 3px rgba(0,0,0,0.03);

}

.multi-vessel-banner i {

    font-size: 26px;

    color: #16a34a;

    background: #dcfce7;

    width: 48px;

    height: 48px;

    border-radius: 12px;

    display: grid;

    place-items: center;

    flex-shrink: 0;

}

.multi-vessel-banner h5 {

    margin: 0 0 4px 0;

    font-size: 15px;

    font-weight: 800;

    color: #14532d;

}

.multi-vessel-banner p {

    margin: 0;

    font-size: 13px;

    color: #15803d;

}

.multi-global-toolbar {

    background: #ffffff;

    border: 1px solid #cbd5e1;

    border-radius: 12px;

    padding: 18px 22px;

    margin-bottom: 24px;

    box-shadow: 0 2px 6px rgba(0,0,0,0.03);

}

.multi-global-toolbar__title {

    font-size: 13px;

    font-weight: 800;

    color: #0f172a;

    text-transform: uppercase;

    letter-spacing: 0.04em;

    margin-bottom: 14px;

    display: flex;

    align-items: center;

    gap: 8px;

}

.multi-global-toolbar__title i {

    color: #0284c7;

}

.vessel-schedule-card {

    background: #ffffff;

    border: 1px solid #cbd5e1;

    border-radius: 12px;

    margin-bottom: 20px;

    overflow: hidden;

    box-shadow: 0 2px 5px rgba(0,0,0,0.03);

    transition: border-color 0.15s ease, box-shadow 0.15s ease;

}

.vessel-schedule-card:hover {

    border-color: #94a3b8;

    box-shadow: 0 4px 12px rgba(0,0,0,0.06);

}

.vessel-schedule-card__header {

    background: #f8fafc;

    border-bottom: 1px solid #e2e8f0;

    padding: 14px 20px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    flex-wrap: wrap;

    gap: 12px;

}

.vessel-schedule-card__title {

    font-size: 16px;

    font-weight: 800;

    color: #0f172a;

    display: flex;

    align-items: center;

    gap: 10px;

}

.vessel-schedule-card__meta {

    display: flex;

    align-items: center;

    gap: 8px;

    font-size: 12px;

}

.vessel-schedule-card__chips {

    padding: 12px 20px;

    background: #ffffff;

    border-bottom: 1px dashed #e2e8f0;

    display: flex;

    flex-wrap: wrap;

    gap: 7px;

    align-items: center;

}

.vessel-schedule-card__body {

    padding: 18px 20px;

}

.badge-vessel-reg {

    background: #f1f5f9;

    color: #475569;

    border: 1px solid #cbd5e1;

    padding: 3px 8px;

    border-radius: 6px;

    font-weight: 600;

}

.badge-vessel-prop {

    background: #e0f2fe;

    color: #0369a1;

    border: 1px solid #bae6fd;

    padding: 3px 8px;

    border-radius: 6px;

    font-weight: 700;

}

.badge-srv-vist {

    background: #ecfdf5;

    color: #047857;

    border: 1px solid #a7f3d0;

    padding: 4px 10px;

    border-radius: 6px;

    font-size: 11.5px;

    font-weight: 600;

}

.badge-srv-ana {

    background: #f5f3ff;

    color: #6d28d9;

    border: 1px solid #ddd6fe;

    padding: 4px 10px;

    border-radius: 6px;

    font-size: 11.5px;

    font-weight: 600;

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

            <input type="hidden" name="agendamentos_multi_json" id="agendamentos_multi_json" value="">

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

                <h4 class="form-section-title"><i class="fas fa-user-tie"></i> Cliente Contratante</h4>

                <div class="form-row">

                    <div class="form-group col-12">

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

                </div>

            </div>



            <!-- PAINEL MULTI-EMBARCAÇÕES -->

            <div id="blocoMultiVessel" class="multi-vessel-wrapper" style="display: none;">

                <div class="multi-vessel-banner">

                    <i class="fas fa-layer-group"></i>

                    <div>

                        <h5>Proposta com Múltiplas Embarcações</h5>

                        <p>Esta proposta possui <strong><span id="multiVesselCount">2</span> embarcações</strong>. Defina as datas, horários e responsáveis técnicos individualmente para cada uma, ou utilize os botões rápidos abaixo para preenchimento em 1 clique.</p>

                    </div>

                </div>



                <!-- Barra de Ações Rápidas Globais -->

                <div class="multi-global-toolbar">

                    <div class="multi-global-toolbar__title">

                        <i class="fas fa-bolt"></i> Atalhos Rápidos Globais (Aplicar a todas as embarcações em 1 clique)

                    </div>

                    <div class="form-row align-items-end" style="row-gap: 12px;">

                        <!-- Vistoriador Global -->

                        <div class="form-group col-md-4 mb-0">

                            <label for="global_vistoriador_id" style="font-size: 12px; font-weight: 700; color: #475569;">

                                <i class="fas fa-compass" style="color: #0284c7;"></i> Vistoriador para Todas

                            </label>

                            <div style="display: flex; gap: 4px;">

                                <select id="global_vistoriador_id" class="form-control form-control-sm">

                                    <option value="">-- Selecione o Vistoriador --</option>

                                    <?php foreach ($vistoriadores as $v): ?>

                                        <option value="<?php echo h($v['id']); ?>"><?php echo h($v['nome']); ?></option>

                                    <?php endforeach; ?>

                                </select>

                                <button type="button" class="btn btn-sm btn-outline-primary text-nowrap" onclick="aplicarVistoriadorATodas()" title="Copiar este vistoriador para todas as embarcações">

                                    <i class="fas fa-check-double"></i> Aplicar

                                </button>

                            </div>

                        </div>



                        <!-- Analista Global -->

                        <div class="form-group col-md-4 mb-0">

                            <label for="global_analista_id" style="font-size: 12px; font-weight: 700; color: #475569;">

                                <i class="fas fa-drafting-compass" style="color: #6366f1;"></i> Analista de Planos para Todas

                            </label>

                            <div style="display: flex; gap: 4px;">

                                <select id="global_analista_id" class="form-control form-control-sm">

                                    <option value="">-- Selecione o Analista --</option>

                                    <?php foreach ($analistas as $a): ?>

                                        <option value="<?php echo h($a['id']); ?>"><?php echo h($a['nome']); ?></option>

                                    <?php endforeach; ?>

                                </select>

                                <button type="button" class="btn btn-sm btn-outline-primary text-nowrap" onclick="aplicarAnalistaATodas()" title="Copiar este analista para todas as embarcações">

                                    <i class="fas fa-check-double"></i> Aplicar

                                </button>

                            </div>

                        </div>



                        <!-- Data / Hora / Local Global -->

                        <div class="form-group col-md-4 mb-0">

                            <label style="font-size: 12px; font-weight: 700; color: #475569;">

                                <i class="fas fa-calendar-alt" style="color: #0f766e;"></i> Mesma Data, Hora e Local

                            </label>

                            <div style="display: flex; gap: 4px;">

                                <input type="date" id="global_data_vistoria" class="form-control form-control-sm" min="<?php echo date('Y-m-d'); ?>" title="Data para todas">

                                <select id="global_hora_vistoria" class="form-control form-control-sm" style="max-width: 90px;" title="Hora para todas">

                                    <option value="">Hora</option>

                                    <?php for ($min = 0; $min < 24 * 60; $min += 30): ?>

                                        <?php $hf = sprintf('%02d:%02d', floor($min / 60), $min % 60); ?>

                                        <option value="<?php echo $hf; ?>"><?php echo $hf; ?></option>

                                    <?php endfor; ?>

                                </select>

                                <input type="text" id="global_local" class="form-control form-control-sm" placeholder="Local" title="Local para todas">

                                <button type="button" class="btn btn-sm btn-outline-success text-nowrap" onclick="aplicarDataLocalATodas()" title="Copiar Data, Hora e Local para todas">

                                    <i class="fas fa-check"></i>

                                </button>

                            </div>

                        </div>

                    </div>

                </div>



                <!-- Container onde os cards de cada embarcação são inseridos -->

                <div id="cardsEmbarcacoesContainer"></div>

            </div>



            <!-- PAINEL SINGLE EMBARCAÇÃO -->

            <div id="blocoSingleVessel">

                <div class="form-section">

                    <h4 class="form-section-title"><i class="fas fa-ship"></i> Embarcação</h4>

                    <div class="form-row">

                        <div class="form-group col-12">

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

                    <h4 class="form-section-title"><i class="fas fa-tasks"></i> Serviços e Responsáveis Técnicos</h4>

                    <div class="form-row">

                        <div class="form-group col-12">

                            <label for="tipo_vistoria">Serviços / Tipo de vistoria *</label>

                            <textarea id="tipo_vistoria" name="tipo_vistoria" required rows="2" placeholder="Ex.: CSN inicial, CNBL, arqueação, Análise de Planos EC1..."><?php echo h($agendamento['tipo_vistoria']); ?></textarea>

                            <small class="text-muted">Serviços contratados que demandam vistoria naval em campo ou análise técnica de engenharia.</small>

                        </div>

                    </div>



                    <div id="alertaServicosMistos" style="display:none;" class="alert alert-info col-12 mb-3">

                        <i class="fas fa-info-circle"></i> <strong>Proposta Integrada (Vistoria de Campo + Análise de Planos):</strong> Selecione o Vistoriador Naval para a vistoria na embarcação e o Analista Naval para aprovação dos projetos.

                    </div>



                    <!-- BLOCO 1: VISTORIA DE CAMPO -->

                    <div id="blocoVistoriaCampo" style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 20px; background: #fafafa;">

                        <div style="font-weight: 600; color: #1e293b; margin-bottom: 12px; font-size: 0.95rem;">

                            <i class="fas fa-compass" style="color: #0284c7;"></i> Vistoria Técnica em Campo

                        </div>

                        <div class="form-row">

                            <div class="form-group col-12">

                                <label for="vistoriador_id">Vistoriador naval responsável <span id="labelVistObrigatorio">*</span></label>

                                <?php if (in_array($cargo, ['ADMIN', 'SECRETARIA', 'VENDEDOR'], true)): ?>

                                    <select id="vistoriador_id" name="vistoriador_id">

                                        <option value="">-- Selecione o vistoriador --</option>

                                        <?php foreach ($vistoriadores as $v): ?>

                                            <?php

                                            $statusSgq = $v['status_sgq'] ?? 'QUALIFICADO';

                                            $marinhaVal = !empty($v['credencial_marinha_validade']) ? date('d/m/Y', strtotime($v['credencial_marinha_validade'])) : '';

                                            $vencido = !empty($v['credencial_marinha_validade']) && $v['credencial_marinha_validade'] < date('Y-m-d');

                                            $tagSgq = $vencido

                                                ? ' [INAPTO - Credencial Marinha Vencida]'

                                                : ($statusSgq !== 'QUALIFICADO' ? " [SGQ: {$statusSgq}]" : ($marinhaVal ? " (Marinha: {$marinhaVal})" : ' (Qualificado)'));

                                            ?>

                                            <option value="<?php echo h($v['id']); ?>"

                                                    <?php echo $vencido ? 'style="color:#b42318;font-weight:600;"' : ''; ?>

                                                    <?php echo ($agendamento['vistoriador_id'] ?? '') === $v['id'] ? 'selected' : ''; ?>>

                                                <?php echo h($v['nome']) . ' (' . h($v['email']) . ')' . $tagSgq; ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                    <small class="text-muted">Selecione o engenheiro/vistoriador naval credenciado que realizará a vistoria técnica em campo.</small>

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

                                <label for="data_vistoria">Data da vistoria <span id="labelDataObrigatorio">*</span></label>

                                <input type="date" id="data_vistoria" name="data_vistoria"

                                       value="<?php echo h($agendamento['data_vistoria']); ?>"

                                       min="<?php echo date('Y-m-d'); ?>">

                            </div>

                            <div class="form-group col-3">

                                <label for="hora_vistoria">Hora</label>

                                <input type="time" id="hora_vistoria" name="hora_vistoria" value="<?php echo h($agendamento['hora_vistoria']); ?>">

                            </div>

                            <div class="form-group col-5">

                                <label for="local">Local da vistoria</label>

                                <input type="text" id="local" name="local" value="<?php echo h($agendamento['local']); ?>" placeholder="Endereço / estaleiro / porto">

                            </div>

                        </div>

                    </div>



                    <!-- BLOCO 2: ANÁLISE TÉCNICA DE PLANOS -->

                    <div id="blocoAnalisePlanos" style="border: 1px solid #e0e7ff; border-radius: 8px; padding: 15px; margin-bottom: 20px; background: #f8faff;">

                        <div style="font-weight: 600; color: #3730a3; margin-bottom: 12px; font-size: 0.95rem;">

                            <i class="fas fa-drafting-compass" style="color: #6366f1;"></i> Análise Técnica de Planos (Gabinete / Engenharia)

                        </div>

                        <div class="form-row">

                            <div class="form-group col-7">

                                <label for="analista_id">Analista naval responsável <span id="labelAnalistaObrigatorio">*</span></label>

                                <select id="analista_id" name="analista_id">

                                    <option value="">-- Selecione o analista técnico --</option>

                                    <?php foreach ($analistas as $a): ?>

                                        <option value="<?php echo h($a['id']); ?>"

                                                <?php echo ($agendamento['analista_id'] ?? '') === $a['id'] ? 'selected' : ''; ?>>

                                            <?php echo h($a['nome']); ?> (<?php echo h($a['email']); ?>)

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <small class="text-muted">Engenheiro ou analista naval responsável pela análise de conformidade (NORMAM-201/NORMAM-202).</small>

                            </div>

                            <div class="form-group col-5">

                                <label for="prazo_analise">Prazo para conclusão da análise <span id="labelPrazoObrigatorio">*</span></label>

                                <?php

                                $prazoVal = '';

                                if (!empty($agendamento['prazo_analise'])) {

                                    $prazoVal = date('Y-m-d\TH:i', strtotime($agendamento['prazo_analise']));

                                }

                                ?>

                                <input type="datetime-local" id="prazo_analise" name="prazo_analise" value="<?php echo h($prazoVal); ?>">

                                <small class="text-muted">Prazo limite para emissão de exigências ou parecer de aprovação.</small>

                            </div>

                        </div>

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

var isMultiVesselMode = false;



var listaVistoriadores = <?php echo json_encode($vistoriadores, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

var listaAnalistas = <?php echo json_encode($analistas, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

var cargoUsuario = <?php echo json_encode($cargo); ?>;

var usuarioLogadoId = <?php echo json_encode($_SESSION['usuario_id']); ?>;

var usuarioLogadoNome = <?php echo json_encode($_SESSION['usuario_nome'] ?? 'Você'); ?>;



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



function ajustarVisibilidadeBlocos(temVistoria, temAnalise) {

    const blocoVist = document.getElementById('blocoVistoriaCampo');

    const blocoAna = document.getElementById('blocoAnalisePlanos');

    const alertaMistos = document.getElementById('alertaServicosMistos');

    const vistoriadorInput = document.getElementById('vistoriador_id');

    const dataVistoriaInput = document.getElementById('data_vistoria');

    const analistaInput = document.getElementById('analista_id');

    const prazoAnaliseInput = document.getElementById('prazo_analise');

    const labelVistObrigatorio = document.getElementById('labelVistObrigatorio');

    const labelDataObrigatorio = document.getElementById('labelDataObrigatorio');

    const labelAnalistaObrigatorio = document.getElementById('labelAnalistaObrigatorio');

    const labelPrazoObrigatorio = document.getElementById('labelPrazoObrigatorio');



    if (alertaMistos) alertaMistos.style.display = (temVistoria && temAnalise) ? 'block' : 'none';



    if (temAnalise) {

        if (blocoAna) blocoAna.style.display = 'block';

        if (analistaInput) analistaInput.required = true;

        if (prazoAnaliseInput) prazoAnaliseInput.required = true;

        if (labelAnalistaObrigatorio) labelAnalistaObrigatorio.style.display = 'inline';

        if (labelPrazoObrigatorio) labelPrazoObrigatorio.style.display = 'inline';

    } else {

        if (blocoAna) blocoAna.style.display = 'none';

        if (analistaInput) analistaInput.required = false;

        if (prazoAnaliseInput) prazoAnaliseInput.required = false;

        if (labelAnalistaObrigatorio) labelAnalistaObrigatorio.style.display = 'none';

        if (labelPrazoObrigatorio) labelPrazoObrigatorio.style.display = 'none';

    }



    if (temVistoria) {

        if (blocoVist) blocoVist.style.display = 'block';

        if (vistoriadorInput && vistoriadorInput.tagName.toLowerCase() === 'select') vistoriadorInput.required = true;

        if (dataVistoriaInput) dataVistoriaInput.required = true;

        if (labelVistObrigatorio) labelVistObrigatorio.style.display = 'inline';

        if (labelDataObrigatorio) labelDataObrigatorio.style.display = 'inline';

    } else if (temAnalise) {

        if (blocoVist) blocoVist.style.display = 'none';

        if (vistoriadorInput && vistoriadorInput.tagName.toLowerCase() === 'select') vistoriadorInput.required = false;

        if (dataVistoriaInput) dataVistoriaInput.required = false;

        if (labelVistObrigatorio) labelVistObrigatorio.style.display = 'none';

        if (labelDataObrigatorio) labelDataObrigatorio.style.display = 'none';

    } else {

        if (blocoVist) blocoVist.style.display = 'block';

        if (vistoriadorInput && vistoriadorInput.tagName.toLowerCase() === 'select') vistoriadorInput.required = true;

        if (dataVistoriaInput) dataVistoriaInput.required = true;

    }

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



function gerarOpcoesHoras(valorSelecionado) {

    let html = '<option value="">-- Hora --</option>';

    for (let minutos = 0; minutos < 24 * 60; minutos += 30) {

        const h = String(Math.floor(minutos / 60)).padStart(2, '0');

        const m = String(minutos % 60).padStart(2, '0');

        const val = `${h}:${m}`;

        const sel = (valorSelecionado === val) ? ' selected' : '';

        html += `<option value="${val}"${sel}>${val}</option>`;

    }

    return html;

}



function gerarOpcoesVistoriadores(selecionadoId) {

    let html = '<option value="">-- Selecione o Vistoriador --</option>';

    listaVistoriadores.forEach(function(v) {

        const sel = (String(v.id) === String(selecionadoId)) ? ' selected' : '';

        html += `<option value="${v.id}"${sel}>${v.nome}</option>`;

    });

    return html;

}



function gerarOpcoesAnalistas(selecionadoId) {

    let html = '<option value="">-- Selecione o Analista --</option>';

    listaAnalistas.forEach(function(a) {

        const sel = (String(a.id) === String(selecionadoId)) ? ' selected' : '';

        html += `<option value="${a.id}"${sel}>${a.nome}</option>`;

    });

    return html;

}



function renderizarMultiEmbarcacoes(embarcacoes) {

    const container = document.getElementById('cardsEmbarcacoesContainer');

    const multiCount = document.getElementById('multiVesselCount');

    if (!container) return;



    if (multiCount) multiCount.textContent = embarcacoes.length;

    container.innerHTML = '';



    const hoje = new Date().toISOString().split('T')[0];



    embarcacoes.forEach(function(emb, idx) {

        const card = document.createElement('div');

        card.className = 'vessel-schedule-card';

        card.dataset.embId = emb.id;

        card.dataset.agId = emb.agendamento_id || '';

        card.dataset.embNome = emb.nome;

        card.dataset.temVistoria = emb.tem_vistoria ? '1' : '0';

        card.dataset.temAnalise = emb.tem_analise ? '1' : '0';



        // Badges do Header

        let regTag = emb.registro ? `<span class="badge-vessel-reg"><i class="fas fa-id-card"></i> ${emb.registro}</span>` : '';

        let propTag = '';

        if (emb.propulsao === 'COM_PROPULSAO') {

            propTag = '<span class="badge-vessel-prop"><i class="fas fa-bolt"></i> COM PROPULSÃO</span>';

        } else if (emb.propulsao === 'SEM_PROPULSAO') {

            propTag = '<span class="badge-vessel-prop" style="background:#fef3c7;color:#92400e;border-color:#fde68a;"><i class="fas fa-anchor"></i> SEM PROPULSÃO (BALSA)</span>';

        }



        // Chips dos serviços exclusivos da embarcação

        let chipsHtml = '';

        if (emb.servicos_vistoria && emb.servicos_vistoria.length > 0) {

            emb.servicos_vistoria.forEach(function(s) {

                chipsHtml += `<span class="badge-srv-vist"><i class="fas fa-compass"></i> ${s}</span>`;

            });

        }

        if (emb.servicos_analise && emb.servicos_analise.length > 0) {

            emb.servicos_analise.forEach(function(s) {

                chipsHtml += `<span class="badge-srv-ana"><i class="fas fa-drafting-compass"></i> ${s}</span>`;

            });

        }

        if (!chipsHtml) {

            chipsHtml = '<span class="badge-srv-vist"><i class="fas fa-check"></i> Vistoria Técnica Geral</span>';

        }



        // Vistoriador Select / Readonly

        let vistoriadorFieldHtml = '';

        if (cargoUsuario === 'VISTORIADOR') {

            vistoriadorFieldHtml = `

                <input type="text" class="form-control form-control-sm" readonly value="${usuarioLogadoNome} (Você)">

                <input type="hidden" class="multi-vistoriador-id" value="${usuarioLogadoId}">

            `;

        } else {

            vistoriadorFieldHtml = `

                <select class="form-control form-control-sm multi-vistoriador-id">

                    ${gerarOpcoesVistoriadores(emb.vistoriador_id)}

                </select>

            `;

        }



        // Bloco de Análise de Planos (se contratada)

        let analiseRowHtml = '';

        if (emb.tem_analise) {

            analiseRowHtml = `

                <div class="form-row mt-3 pt-3" style="border-top: 1px dashed #e2e8f0;">

                    <div class="form-group col-md-7 mb-2">

                        <label style="font-size: 12px; font-weight: 700; color: #4338ca;">

                            <i class="fas fa-drafting-compass" style="color: #6366f1;"></i> Analista Naval Responsável *

                        </label>

                        <select class="form-control form-control-sm multi-analista-id">

                            ${gerarOpcoesAnalistas(emb.analista_id)}

                        </select>

                    </div>

                    <div class="form-group col-md-5 mb-2">

                        <label style="font-size: 12px; font-weight: 700; color: #4338ca;">

                            <i class="fas fa-clock" style="color: #6366f1;"></i> Prazo Conclusão da Análise *

                        </label>

                        <input type="datetime-local" class="form-control form-control-sm multi-prazo-analise" value="${emb.prazo_analise || ''}">

                    </div>

                </div>

            `;

        }



        card.innerHTML = `

            <div class="vessel-schedule-card__header">

                <div class="vessel-schedule-card__title">

                    <i class="fas fa-ship" style="color: #0f766e;"></i>

                    <span>${emb.nome}</span>

                </div>

                <div class="vessel-schedule-card__meta">

                    ${regTag}

                    ${propTag}

                </div>

            </div>

            <div class="vessel-schedule-card__chips">

                <strong style="font-size: 11.5px; color: #64748b; margin-right: 4px;">Serviços:</strong>

                ${chipsHtml}

            </div>

            <div class="vessel-schedule-card__body">

                <div class="form-row">

                    <div class="form-group col-md-3 mb-2">

                        <label style="font-size: 12px; font-weight: 700; color: #334155;">

                            <i class="fas fa-calendar" style="color: #0f766e;"></i> Data da Vistoria *

                        </label>

                        <input type="date" class="form-control form-control-sm multi-data-vistoria" value="${emb.data_vistoria || ''}" min="${hoje}">

                    </div>

                    <div class="form-group col-md-2 mb-2">

                        <label style="font-size: 12px; font-weight: 700; color: #334155;">

                            <i class="fas fa-clock"></i> Hora

                        </label>

                        <select class="form-control form-control-sm multi-hora-vistoria">

                            ${gerarOpcoesHoras(emb.hora_vistoria || '')}

                        </select>

                    </div>

                    <div class="form-group col-md-3 mb-2">

                        <label style="font-size: 12px; font-weight: 700; color: #334155;">

                            <i class="fas fa-location-dot" style="color: #0284c7;"></i> Local da Vistoria

                        </label>

                        <input type="text" class="form-control form-control-sm multi-local-vistoria" value="${emb.local || ''}" placeholder="Estaleiro / porto / marina">

                    </div>

                    <div class="form-group col-md-4 mb-2">

                        <label style="font-size: 12px; font-weight: 700; color: #334155;">

                            <i class="fas fa-user-check" style="color: #0f766e;"></i> Vistoriador Naval *

                        </label>

                        ${vistoriadorFieldHtml}

                    </div>

                </div>



                ${analiseRowHtml}



                <div class="form-row mt-2">

                    <div class="form-group col-12 mb-0">

                        <label style="font-size: 11.5px; font-weight: 600; color: #64748b;">Instruções ou observações para esta embarcação:</label>

                        <input type="text" class="form-control form-control-sm multi-observacoes" value="${emb.observacoes || ''}" placeholder="Ex.: acesso restrito, pranchas enviadas em anexo...">

                    </div>

                </div>

            </div>

        `;



        container.appendChild(card);

    });

}



function aplicarVistoriadorATodas() {

    const globalSelect = document.getElementById('global_vistoriador_id');

    if (!globalSelect || !globalSelect.value) {

        alert('Selecione primeiro um vistoriador na caixa de atalho global.');

        if (globalSelect) globalSelect.focus();

        return;

    }

    const val = globalSelect.value;

    const targets = document.querySelectorAll('.multi-vistoriador-id');

    let count = 0;

    targets.forEach(function(sel) {

        if (sel.tagName.toLowerCase() === 'select') {

            sel.value = val;

            count++;

        }

    });

    alert(`Vistoriador aplicado com sucesso a ${count} embarcação(ões)!`);

}



function aplicarAnalistaATodas() {

    const globalSelect = document.getElementById('global_analista_id');

    if (!globalSelect || !globalSelect.value) {

        alert('Selecione primeiro um analista na caixa de atalho global.');

        if (globalSelect) globalSelect.focus();

        return;

    }

    const val = globalSelect.value;

    const targets = document.querySelectorAll('.multi-analista-id');

    let count = 0;

    targets.forEach(function(sel) {

        sel.value = val;

        count++;

    });

    alert(`Analista aplicado com sucesso a ${count} embarcação(ões)!`);

}



function aplicarDataLocalATodas() {

    const dataVal = document.getElementById('global_data_vistoria').value;

    const horaVal = document.getElementById('global_hora_vistoria').value;

    const localVal = document.getElementById('global_local').value;



    if (!dataVal && !horaVal && !localVal) {

        alert('Preencha ao menos a Data, Hora ou Local no atalho global para aplicar.');

        return;

    }



    if (dataVal) {

        document.querySelectorAll('.multi-data-vistoria').forEach(input => input.value = dataVal);

    }

    if (horaVal) {

        document.querySelectorAll('.multi-hora-vistoria').forEach(select => select.value = horaVal);

    }

    if (localVal) {

        document.querySelectorAll('.multi-local-vistoria').forEach(input => input.value = localVal);

    }



    alert('Data, Horário e Local replicados para todas as embarcações com sucesso!');

}



function alternarModoMultiVessel(isMulti) {

    isMultiVesselMode = isMulti;

    const blocoMulti = document.getElementById('blocoMultiVessel');

    const blocoSingle = document.getElementById('blocoSingleVessel');

    const embSelect = document.getElementById('embarcacao_id');

    const tipoVistoriaArea = document.getElementById('tipo_vistoria');

    const vistoriadorSelect = document.getElementById('vistoriador_id');

    const dataVistoriaInput = document.getElementById('data_vistoria');

    const analistaSelect = document.getElementById('analista_id');

    const prazoAnaliseInput = document.getElementById('prazo_analise');



    if (isMulti) {

        if (blocoMulti) blocoMulti.style.display = 'block';

        if (blocoSingle) blocoSingle.style.display = 'none';



        // Desabilitar required de inputs single para não bloquear submit

        if (embSelect) embSelect.required = false;

        if (tipoVistoriaArea) tipoVistoriaArea.required = false;

        if (vistoriadorSelect) vistoriadorSelect.required = false;

        if (dataVistoriaInput) dataVistoriaInput.required = false;

        if (analistaSelect) analistaSelect.required = false;

        if (prazoAnaliseInput) prazoAnaliseInput.required = false;

    } else {

        if (blocoMulti) blocoMulti.style.display = 'none';

        if (blocoSingle) blocoSingle.style.display = 'block';



        if (embSelect) embSelect.required = true;

        if (tipoVistoriaArea) tipoVistoriaArea.required = true;

    }

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

        alternarModoMultiVessel(false);

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



                if (data.is_multi && data.embarcacoes && data.embarcacoes.length > 1) {

                    alternarModoMultiVessel(true);

                    renderizarMultiEmbarcacoes(data.embarcacoes);

                } else {

                    alternarModoMultiVessel(false);

                    if (selectEmbarcacao && data.embarcacoes && data.embarcacoes.length > 0) {

                        let options = '<option value="">Selecione a embarcação</option>';

                        data.embarcacoes.forEach(function(emb) {

                            const selected = (String(emb.id) === String(data.embarcacao_id)) ? ' selected' : '';

                            options += '<option value="' + emb.id + '"' + selected + '>' + emb.nome + '</option>';

                        });

                        selectEmbarcacao.innerHTML = options;

                    }

                    if (data.analista_id && document.getElementById('analista_id')) {

                        document.getElementById('analista_id').value = data.analista_id;

                    }

                    if (data.prazo_analise && document.getElementById('prazo_analise')) {

                        document.getElementById('prazo_analise').value = data.prazo_analise;

                    }

                    ajustarVisibilidadeBlocos(data.tem_vistoria, data.tem_analise);

                    if (campoVistoria && data.tipo_vistoria) {

                        campoVistoria.value = data.tipo_vistoria;

                        atualizarEstadoServicosTravados(true);

                    }

                }



                if (data.agendamento_id) {

                    let inputId = document.querySelector('input[name="id"]');

                    if (!inputId) {

                        inputId = document.createElement('input');

                        inputId.type = 'hidden';

                        inputId.name = 'id';

                        const formElem = document.querySelector('form.form-padrao');

                        if (formElem) formElem.appendChild(inputId);

                    }

                    if (inputId) inputId.value = data.agendamento_id;

                    const inputAction = document.querySelector('input[name="action"]');

                    if (inputAction) inputAction.value = 'editar';

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



// Submissão do Formulário - Validação de Multi-Embarcações

const formPadrao = document.querySelector('form.form-padrao');

if (formPadrao) {

    formPadrao.addEventListener('submit', function(e) {

        if (isMultiVesselMode) {

            const cards = document.querySelectorAll('.vessel-schedule-card');

            let multiPayload = [];

            let errorMsg = null;

            let focusElem = null;



            cards.forEach(function(card) {

                if (errorMsg) return;

                const embId = card.dataset.embId;

                const agId = card.dataset.agId || '';

                const embNome = card.dataset.embNome;

                const temVist = card.dataset.temVistoria === '1';

                const temAna = card.dataset.temAnalise === '1';



                const dataInput = card.querySelector('.multi-data-vistoria');

                const horaInput = card.querySelector('.multi-hora-vistoria');

                const localInput = card.querySelector('.multi-local-vistoria');

                const vistSelect = card.querySelector('.multi-vistoriador-id');

                const anaSelect = card.querySelector('.multi-analista-id');

                const prazoInput = card.querySelector('.multi-prazo-analise');

                const obsInput = card.querySelector('.multi-observacoes');



                const dataVal = dataInput ? dataInput.value : '';

                const horaVal = horaInput ? horaInput.value : '';

                const localVal = localInput ? localInput.value : '';

                const vistVal = vistSelect ? vistSelect.value : '';

                const anaVal = anaSelect ? anaSelect.value : '';

                const prazoVal = prazoInput ? prazoInput.value : '';

                const obsVal = obsInput ? obsInput.value : '';



                if (temVist) {

                    if (!dataVal) {

                        errorMsg = `Por favor, informe a Data da Vistoria para a embarcação "${embNome}".`;

                        focusElem = dataInput;

                        return;

                    }

                    if (!vistVal) {

                        errorMsg = `Por favor, selecione o Vistoriador Naval responsável para a embarcação "${embNome}".`;

                        focusElem = vistSelect;

                        return;

                    }

                }



                if (temAna) {

                    if (!anaVal) {

                        errorMsg = `Por favor, selecione o Analista Naval responsável para a embarcação "${embNome}".`;

                        focusElem = anaSelect;

                        return;

                    }

                    if (!prazoVal) {

                        errorMsg = `Por favor, defina o Prazo da Análise Técnica para a embarcação "${embNome}".`;

                        focusElem = prazoInput;

                        return;

                    }

                }



                multiPayload.push({

                    embarcacao_id: embId,

                    agendamento_id: agId,

                    data_vistoria: dataVal,

                    hora_vistoria: horaVal,

                    local: localVal,

                    vistoriador_id: vistVal,

                    analista_id: anaVal,

                    prazo_analise: prazoVal,

                    observacoes: obsVal

                });

            });



            if (errorMsg) {

                e.preventDefault();

                alert(errorMsg);

                if (focusElem) focusElem.focus();

                return false;

            }



            document.getElementById('agendamentos_multi_json').value = JSON.stringify(multiPayload);

        }

    });

}



transformarHoraEmSelecao();

atualizarEstadoServicosTravados(servicosIniciamTravados);

ajustarVisibilidadeBlocos(<?php echo $inicialTemVistoria ? 'true' : 'false'; ?>, <?php echo $inicialTemAnalise ? 'true' : 'false'; ?>);



const propSelectInicial = document.getElementById('proposta_id');

if (propSelectInicial && propSelectInicial.value) {

    carregarDadosProposta(propSelectInicial.value);

}

</script>



<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

