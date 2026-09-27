<?php
/**
 * MÓDULO: Documentação > LC (Licença de Construção / LCEC)
 * Formulário de Criação/Edição
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../includes/aprovacao_ui.php';

verificar_sessao();
if (!podeAcessar('doc_lc')) {
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
$proximo_numero_la = '';
$proximo_numero_lr = '';
if (!$editando) {
    $ano = date('y');
    $ano4 = date('Y');
    $stmt_num = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_lc WHERE YEAR(criado_em) = :ano");
    $stmt_num->execute([':ano' => $ano4]);
    $total = $stmt_num->fetch()['total'];
    $seq = $total + 1;
    $proximo_numero = "AM-LC-{$seq}/{$ano}";
    $proximo_numero_ec = "AM-EC-{$seq}/{$ano}";
    $proximo_numero_la = "AM-LA-{$seq}/{$ano}";
    $proximo_numero_lr = "AM-LR-{$seq}/{$ano}";
}

// Embarcações
$stmt_emb = $pdo->prepare("SELECT * FROM embarcacoes WHERE ativo = 1 ORDER BY nome");
$stmt_emb->execute();
$embarcacoes = $stmt_emb->fetchAll(PDO::FETCH_ASSOC);

// --- PRE-PREENCHIMENTO ---
$preenchimento = [
    'embarcacao_id'        => '',
    'nome_embarcacao'      => '',
    'numero_inscricao'     => '',
    'indicativo_chamada'   => '',
    'atividades_servicos'  => '',
    'tipo_embarcacao'      => '',
    'ano_construcao'       => '',
    'comprimento_total'    => '',
    'comprimento_casco'    => '',
    'comprimento_pp'       => '',
    'boca_moldada'         => '',
    'pontal_moldado'       => '',
    'calado_maximo'        => '',
    'porte_bruto'          => '',
    'arqueacao_bruta'      => '',
    'material_casco'       => '',
    'numero_casco'         => '',
    'numero_tripulantes'   => '',
    'numero_passageiros'   => '',
    'tipo_navegacao'       => '',
    'area_navegacao'       => '',
    'atividade_servico'    => '',
    'propulsao'            => '',
    'relatorio_numero'     => '',
    'proprietario'         => '',
    'proprietario_nome'    => '',
    'proprietario_cpf_cnpj'=> '',
    'proprietario_endereco'=> '',
    'estaleiro_nome'       => '',
    'estaleiro_cpf_cnpj'   => '',
    'estaleiro_endereco'   => '',
    'tipo_licenca'         => 'LC',
    'assinante_nome'       => '',
    'assinante_titulo'     => '',
    'assinante_registro'   => '',
];
$dadosPre = null;
$analise_id = $_GET['analise_id'] ?? ($licenca['analise_id'] ?? '');
$analise_dados = null;
$bloqueio_exigencias = false;
$total_pendencias = 0;
$relatorio_conclusivo_aprovado = false;

// 1. Pré-preenchimento via Análise de Planos (RAP)
if (!$editando && !empty($analise_id)) {
    $stmtAn = $pdo->prepare("SELECT ap.*, e.nome emb_nome, e.registro emb_registro, e.tipo_embarcacao emb_tipo,
        e.comprimento_total emb_comprimento_total, e.comprimento_casco emb_comprimento_casco,
        e.boca_moldada emb_boca_moldada, e.pontal_moldado emb_pontal_moldado, e.calado_maximo emb_calado_maximo,
        e.porte_bruto emb_porte_bruto, e.material_casco emb_material_casco, e.ano emb_ano,
        e.proprietario emb_proprietario, e.indicativo_chamada emb_indicativo, e.arqueacao_bruta emb_ab,
        c.nome cli_nome, c.cpf_cnpj cli_cpf_cnpj, c.endereco cli_endereco
        FROM analises_planos ap
        LEFT JOIN embarcacoes e ON e.id = ap.embarcacao_id
        LEFT JOIN clientes c ON c.id = ap.solicitante_id
        WHERE ap.id = :id LIMIT 1");
    $stmtAn->execute([':id' => $analise_id]);
    $analise_dados = $stmtAn->fetch(PDO::FETCH_ASSOC);

    if ($analise_dados) {
        $tipo_processo_an = $analise_dados['tipo_processo'] ?? 'LC';
        $preenchimento['embarcacao_id']      = (string)($analise_dados['embarcacao_id'] ?? '');
        $preenchimento['nome_embarcacao']    = (string)($analise_dados['emb_nome'] ?: ($analise_dados['embarcacao_nome'] ?? ''));
        $preenchimento['numero_inscricao']   = (string)($analise_dados['emb_registro'] ?? '');
        $preenchimento['indicativo_chamada'] = (string)($analise_dados['emb_indicativo'] ?? '');
        $preenchimento['tipo_navegacao']     = (string)($analise_dados['tipo_navegacao'] ?? '');
        $preenchimento['atividades_servicos']= (string)($analise_dados['tipo_navegacao'] ?? '');
        $preenchimento['tipo_embarcacao']    = (string)($analise_dados['emb_tipo'] ?? '');
        $preenchimento['ano_construcao']     = (string)($analise_dados['emb_ano'] ?? '');
        $preenchimento['comprimento_total']  = (string)($analise_dados['emb_comprimento_total'] ?? '');
        $preenchimento['comprimento_casco']  = (string)($analise_dados['emb_comprimento_casco'] ?? '');
        $preenchimento['boca_moldada']       = (string)($analise_dados['emb_boca_moldada'] ?? '');
        $preenchimento['pontal_moldado']     = (string)($analise_dados['emb_pontal_moldado'] ?? '');
        $preenchimento['calado_maximo']      = (string)($analise_dados['emb_calado_maximo'] ?? '');
        $preenchimento['porte_bruto']        = (string)($analise_dados['emb_porte_bruto'] ?? '');
        $preenchimento['material_casco']     = (string)($analise_dados['emb_material_casco'] ?? '');
        $preenchimento['numero_casco']       = (string)($analise_dados['numero_casco'] ?? '');
        $preenchimento['arqueacao_bruta']    = (string)($analise_dados['emb_ab'] ?? '');
        $preenchimento['numero_passageiros'] = (string)($analise_dados['numero_passageiros'] ?? '');
        $preenchimento['propulsao']          = $analise_dados['possui_propulsao'] === null ? '' : ((int)$analise_dados['possui_propulsao'] ? 'Com Propulsão' : 'Sem Propulsão');
        $preenchimento['relatorio_numero']   = (string)($analise_dados['numero'] ?? '');
        $preenchimento['proprietario']       = (string)($analise_dados['cli_nome'] ?: ($analise_dados['emb_proprietario'] ?? ''));
        $preenchimento['proprietario_nome']  = (string)($analise_dados['cli_nome'] ?: ($analise_dados['emb_proprietario'] ?? ''));
        $preenchimento['proprietario_cpf_cnpj'] = (string)($analise_dados['cli_cpf_cnpj'] ?? '');
        $preenchimento['proprietario_endereco'] = (string)($analise_dados['cli_endereco'] ?? '');
        $preenchimento['estaleiro_nome']     = (string)($analise_dados['estaleiro'] ?? '');
        $preenchimento['tipo_licenca']       = in_array($tipo_processo_an, ['LC','LA','LR','LCEC'], true) ? $tipo_processo_an : 'LC';

        // Checar se há parecer conclusivo aprovado
        $stmtPar = $pdo->prepare("SELECT numero, resultado, finalidade FROM analise_planos_pareceres WHERE analise_id = :id AND status = 'PUBLICADO' ORDER BY versao DESC LIMIT 1");
        $stmtPar->execute([':id' => $analise_id]);
        $ultimoRap = $stmtPar->fetch(PDO::FETCH_ASSOC);
        if ($ultimoRap || $analise_dados['status'] === 'CONCLUIDA') {
            $relatorio_conclusivo_aprovado = true;
            if ($ultimoRap) {
                $preenchimento['relatorio_numero'] .= ' / ' . $ultimoRap['numero'];
            }
        }

        // Checar exigências graves A/S pendentes
        $stmtExAS = $pdo->prepare("SELECT COUNT(*) FROM analise_planos_exigencias WHERE analise_id = :id AND as_impeditivo = 1 AND (status <> 'CUMPRIDA' OR saneamento_pendente = 1)");
        $stmtExAS->execute([':id' => $analise_id]);
        $total_pendencias_as = (int)$stmtExAS->fetchColumn();
        if ($total_pendencias_as > 0) {
            $bloqueio_exigencias = true;
        }

        $stmtEx = $pdo->prepare("SELECT COUNT(*) FROM analise_planos_exigencias WHERE analise_id = :id AND (status <> 'CUMPRIDA' OR saneamento_pendente = 1)");
        $stmtEx->execute([':id' => $analise_id]);
        $total_pendencias = (int)$stmtEx->fetchColumn();
    }
} elseif (!$editando && !empty($_GET['agendamento_id'])) {
    // 2. Pré-preenchimento via Agendamento / Vistoria
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
        $preenchimento['proprietario_nome']  = h($dadosPre['proprietario'] ?? '');
    }
}

// Preenchimento do Responsável Técnico / Assinante padrão caso seja analista logado
$usuario_logado_id = (string)($_SESSION['usuario_id'] ?? '');
if (!$editando && !empty($usuario_logado_id)) {
    $stmtR = $pdo->prepare("SELECT * FROM responsaveis_assinatura WHERE usuario_id = :u AND ativo = 1 LIMIT 1");
    $stmtR->execute([':u' => $usuario_logado_id]);
    $resp_analista = $stmtR->fetch(PDO::FETCH_ASSOC);
    if ($resp_analista) {
        $preenchimento['assinante_nome'] = $resp_analista['nome_completo'];
        $preenchimento['assinante_titulo'] = $resp_analista['cargo_titulo'];
        $preenchimento['assinante_registro'] = $resp_analista['registro_profissional'];
    }
}

// Buscar lista de despachantes ativos
$stmt_desp = $pdo->prepare("SELECT id, nome FROM clientes WHERE perfil = 'despachante' AND status = 'ATIVO' ORDER BY nome");
$stmt_desp->execute();
$despachantes_list = $stmt_desp->fetchAll(PDO::FETCH_ASSOC);

// Carregar Observações Técnicas da Licença (NORMAM-202) e Modelos do Analista
$usuarioLogadoId = $_SESSION['usuario_id'] ?? null;
$modelosAnalista = obterTodosTemplatesObservacoesLicenca($usuarioLogadoId);
$modelosOriginaisNormam = modelosObservacoesLicencaPadrao();

$tipo_licenca_atual = $editando ? ($licenca['tipo_licenca'] ?? 'LC') : ($preenchimento['tipo_licenca'] ?? 'LC');
$origem_modelo_atual = $modelosAnalista[$tipo_licenca_atual]['origem'] ?? 'normam_padrao';

$observacoes_valor = '';
if ($editando) {
    $observacoes_valor = (string)($licenca['observacoes'] ?? '');
    if (trim($observacoes_valor) === '' && !empty($licenca['dados_json'])) {
        $dj = json_decode($licenca['dados_json'], true);
        if (!empty($dj['observacoes'])) {
            $observacoes_valor = (string)$dj['observacoes'];
        }
    }
    if (trim($observacoes_valor) === '') {
        $observacoes_valor = gerarObservacoesPadraoLicenca($licenca, $tipo_licenca_atual, $usuarioLogadoId);
    }
} else {
    $observacoes_valor = gerarObservacoesPadraoLicenca($preenchimento, $tipo_licenca_atual, $usuarioLogadoId);
}

$titulo_page = ($editando ? 'Editar' : 'Nova') . ' Licença de Construção/LCEC - ' . APP_NAME;
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-header">
        <h2><i class="fas fa-file-certificate"></i> <?php echo $editando ? 'Editar Licença' : 'Nova Licença'; ?> de Construção / LCEC</h2>
        <a href="<?php echo APP_URL; ?>documentacao/lc" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>

    <?php if ($bloqueio_exigencias): ?>
        <div class="alert alert-danger mb-3" style="border-radius: 8px; font-weight: 500;">
            <i class="fas fa-ban"></i> <strong>Emissão Bloqueada por Condição A/S:</strong>
            O Relatório de Análise de Planos vinculado (<?= h($preenchimento['relatorio_numero']) ?>) possui <strong><?= (int)$total_pendencias_as ?> exigência(s) com condição grave A/S pendente(s)</strong>.
            Conforme a regra naval, exigências marcadas como <strong>A/S (Ação/Assunto Suspensivo)</strong> suspendem e impedem a emissão de licenças e certificados da embarcação até seu cumprimento integral.
        </div>
    <?php elseif (!empty($analise_id) && $analise_dados): ?>
        <div class="alert alert-success mb-3" style="border-radius: 8px;">
            <i class="fas fa-check-circle"></i> <strong>Vinculado à Análise de Planos:</strong>
            Processo <strong><?= h($analise_dados['numero']) ?></strong> · Embarcação: <strong><?= h($preenchimento['nome_embarcacao']) ?></strong>
            · Status: <span class="badge badge-success"><?= h($analise_dados['status']) ?></span>
            <?= $relatorio_conclusivo_aprovado ? '· Relatório RAP Vinculado' : '' ?>
            <?= $total_pendencias > 0 ? " · ({$total_pendencias} exigência(s) regular(es) em acompanhamento)" : '' ?>
        </div>
    <?php endif; ?>

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
            <input type="hidden" name="embarcacao_id" value="<?php echo h($licenca['embarcacao_id'] ?? ''); ?>">
            <input type="hidden" name="cliente_id" value="<?php echo h($licenca['cliente_id'] ?? ''); ?>">
            <input type="hidden" name="vistoria_id" value="<?php echo h($licenca['vistoria_id'] ?? ''); ?>">
            <input type="hidden" name="analise_id" value="<?php echo h($licenca['analise_id'] ?? ''); ?>">
        <?php else: ?>
            <?php if (!empty($analise_id)): ?>
                <input type="hidden" name="analise_id" value="<?php echo h($analise_id); ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['vistoria_id'])): ?>
                <input type="hidden" name="vistoria_id" value="<?php echo h($_GET['vistoria_id']); ?>">
            <?php endif; ?>
        <?php endif; ?>

        <!-- Seção 1: Identificação -->
        <div class="card mb-3">
            <div class="card-header"><h3><i class="fas fa-id-card"></i> Identificação</h3></div>
            <div class="card-body">
                <div class="grid-2">
                    <?php
                    $current = $editando ? $licenca['tipo_licenca'] : ($preenchimento['tipo_licenca'] ?? 'LC');
                    $numero_inicial = $proximo_numero;
                    if ($current === 'LCEC') $numero_inicial = $proximo_numero_ec;
                    elseif ($current === 'LA') $numero_inicial = $proximo_numero_la;
                    elseif ($current === 'LR') $numero_inicial = $proximo_numero_lr;
                    ?>
                    <div class="form-group">
                        <label>Número da Licença</label>
                        <input type="text" class="form-control" id="numero_lc_display" 
                               value="<?php echo $editando ? h($licenca['numero_lc']) : h($numero_inicial); ?>" readonly 
                               style="background: var(--cor-sidebar); font-weight: bold;">
                        <small class="text-muted">LC (AM-LC), LA (AM-LA), LR (AM-LR) ou LCEC (AM-EC)</small>
                    </div>
                    <div class="form-group">
                        <label for="tipo_licenca">Tipo de Licença *</label>
                        <select name="tipo_licenca" id="tipo_licenca" class="form-control" required onchange="atualizarNumero()">
                            <?php
                            $tipos = [
                                'LC'   => 'LC - Licença de Construção',
                                'LA'   => 'LA - Licença de Alteração',
                                'LR'   => 'LR - Licença de Reclassificação',
                                'LCEC' => 'LCEC - Construção Embarcação Classificada'
                            ];
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
                    <select id="embarcacao_id" name="embarcacao_id" class="form-control" onchange="carregarDadosEmbarcacao(this.value)" required>
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
                                data-ano="<?php echo h($emb['ano'] ?? ''); ?>"
                                data-tripulantes="<?php echo h($emb['numero_tripulantes'] ?? 6); ?>"
                                data-passageiros="<?php echo h($emb['numero_passageiros'] ?? 0); ?>"
                                data-porte_bruto="<?php echo h($emb['porte_bruto'] ?? ''); ?>"
                                <?php echo (!empty($_GET['agendamento_id']) && isset($dadosPre['embarcacao_id']) && $dadosPre['embarcacao_id'] == $emb['id']) ? 'selected' : ''; ?>>
                                <?php echo h($emb['nome']) . ' (' . h($emb['tipo']) . ' - ' . h($emb['registro']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <hr>
                <?php endif; ?>

                <div class="grid-4">
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
                        <label for="ano_construcao">Ano Construção / Quilha</label>
                        <input type="text" name="ano_construcao" id="ano_construcao" class="form-control" placeholder="Ex: 2022"
                               value="<?php echo $editando ? h($licenca['ano_construcao'] ?? ($licenca['dados_json']['ano_construcao'] ?? '')) : h($preenchimento['ano_construcao']); ?>">
                        <small class="text-muted">Item 1 das Observações NORMAM</small>
                    </div>
                    <div class="form-group">
                        <label for="numero_casco">Número do Casco</label>
                        <input type="text" name="numero_casco" id="numero_casco" class="form-control"
                               value="<?php echo $editando ? h($licenca['numero_casco'] ?? '') : h($preenchimento['numero_casco'] ?? ''); ?>">
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
                               value="<?php echo $editando ? h($licenca['sociedade_classificadora'] ?? 'Amazon Naval Ltda') : 'Amazon Naval Ltda'; ?>">
                    </div>
                </div>

                <!-- Dimensões -->
                <h4 style="margin:15px 0 10px;font-size:14px;color:#555;">Dimensões</h4>
                <div class="grid-6" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 15px;">
                    <div class="form-group">
                        <label for="comprimento_total">Comp. Total (m)</label>
                        <input type="number" name="comprimento_total" id="comprimento_total" class="form-control" step="0.001"
                               value="<?php echo $editando ? h($licenca['comprimento_total']) : h($preenchimento['comprimento_total']); ?>">
                        <small class="text-muted">Com rampas (Item 5)</small>
                    </div>
                    <div class="form-group">
                        <label for="comprimento_casco">Comp. Casco (m)</label>
                        <input type="number" name="comprimento_casco" id="comprimento_casco" class="form-control" step="0.001"
                               value="<?php echo $editando ? h($licenca['comprimento_casco'] ?? ($licenca['dados_json']['comprimento_casco'] ?? '')) : h($preenchimento['comprimento_casco'] ?? ''); ?>">
                        <small class="text-muted">Casco (Item 5)</small>
                    </div>
                    <div class="form-group">
                        <label for="comprimento_pp">Comp. PP (m)</label>
                        <input type="number" name="comprimento_pp" id="comprimento_pp" class="form-control" step="0.001"
                               value="<?php echo $editando ? h($licenca['comprimento_pp'] ?? '') : h($preenchimento['comprimento_pp'] ?? ''); ?>">
                        <small class="text-muted">Perpendiculares</small>
                    </div>
                    <div class="form-group">
                        <label for="boca_moldada">Boca Mold. (m)</label>
                        <input type="number" name="boca_moldada" id="boca_moldada" class="form-control" step="0.001"
                               value="<?php echo $editando ? h($licenca['boca_moldada']) : h($preenchimento['boca_moldada']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="pontal_moldado">Pontal Mold. (m)</label>
                        <input type="number" name="pontal_moldado" id="pontal_moldado" class="form-control" step="0.001"
                               value="<?php echo $editando ? h($licenca['pontal_moldado']) : h($preenchimento['pontal_moldado']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="calado_maximo">Calado Máx. (m)</label>
                        <input type="number" name="calado_maximo" id="calado_maximo" class="form-control" step="0.001"
                               value="<?php echo $editando ? h($licenca['calado_maximo'] ?? '') : h($preenchimento['calado_maximo'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Capacidades -->
                <h4 style="margin:15px 0 10px;font-size:14px;color:#555;">Capacidades</h4>
                <div class="grid-3">
                    <div class="form-group">
                        <label for="porte_bruto">Porte Bruto (PB)</label>
                        <input type="number" name="porte_bruto" id="porte_bruto" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($licenca['porte_bruto'] ?? '') : h($preenchimento['porte_bruto'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="numero_tripulantes">Nº Tripulantes</label>
                        <input type="number" name="numero_tripulantes" id="numero_tripulantes" class="form-control" min="0"
                               value="<?php echo $editando ? h($licenca['numero_tripulantes'] ?? '') : h($preenchimento['numero_tripulantes'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="numero_passageiros">Nº Passageiros</label>
                        <input type="number" name="numero_passageiros" id="numero_passageiros" class="form-control" min="0"
                               value="<?php echo $editando ? h($licenca['numero_passageiros'] ?? '') : h($preenchimento['numero_passageiros'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Navegação e Atividade -->
                <h4 style="margin:15px 0 10px;font-size:14px;color:#555;">Navegação e Atividade</h4>
                <div class="grid-4">
                    <div class="form-group">
                        <label for="tipo_navegacao">Tipo de Navegação</label>
                        <input type="text" name="tipo_navegacao" id="tipo_navegacao" class="form-control" placeholder="Ex: Interior, Mar Aberto"
                               value="<?php echo $editando ? h($licenca['tipo_navegacao'] ?? '') : h($preenchimento['tipo_navegacao'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="area_navegacao">Área de Navegação</label>
                        <input type="text" name="area_navegacao" id="area_navegacao" class="form-control" placeholder="Ex: Área 1, Cabotagem"
                               value="<?php echo $editando ? h($licenca['area_navegacao'] ?? '') : h($preenchimento['area_navegacao'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="atividade_servico">Atividade/Serviço</label>
                        <input type="text" name="atividade_servico" id="atividade_servico" class="form-control" placeholder="Ex: Transporte de Passageiros"
                               value="<?php echo $editando ? h($licenca['atividade_servico'] ?? '') : h($preenchimento['atividade_servico'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="propulsao">Propulsão</label>
                        <input type="text" name="propulsao" id="propulsao" class="form-control" placeholder="Ex: Motor Diesel"
                               value="<?php echo $editando ? h($licenca['propulsao'] ?? '') : h($preenchimento['propulsao'] ?? ''); ?>">
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
                               value="<?php echo $editando ? h($licenca['proprietario_nome']) : h($preenchimento['proprietario_nome'] ?? $preenchimento['proprietario'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="proprietario_cpf_cnpj">CPF / CNPJ</label>
                        <input type="text" name="proprietario_cpf_cnpj" id="proprietario_cpf_cnpj" class="form-control"
                               value="<?php echo $editando ? h($licenca['proprietario_cpf_cnpj']) : h($preenchimento['proprietario_cpf_cnpj'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="proprietario_endereco">Endereço</label>
                    <textarea name="proprietario_endereco" id="proprietario_endereco" class="form-control" rows="2"><?php echo $editando ? h($licenca['proprietario_endereco']) : h($preenchimento['proprietario_endereco'] ?? ''); ?></textarea>
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
                               value="<?php echo $editando ? h($licenca['estaleiro_nome']) : h($preenchimento['estaleiro_nome'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="estaleiro_cpf_cnpj">CPF / CNPJ</label>
                        <input type="text" name="estaleiro_cpf_cnpj" id="estaleiro_cpf_cnpj" class="form-control"
                               value="<?php echo $editando ? h($licenca['estaleiro_cpf_cnpj']) : h($preenchimento['estaleiro_cpf_cnpj'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="estaleiro_endereco">Endereço</label>
                    <textarea name="estaleiro_endereco" id="estaleiro_endereco" class="form-control" rows="2"><?php echo $editando ? h($licenca['estaleiro_endereco']) : h($preenchimento['estaleiro_endereco'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Seção 5: Observações Técnicas Oficiais da Licença (NORMAM-202) -->
        <div class="card mb-3" style="border-left: 4px solid #0891b2;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <h3 style="margin: 0;"><i class="fas fa-clipboard-list" style="color: #0891b2;"></i> Observações Técnicas da Licença (NORMAM-202)</h3>
                    <span id="badge_status_modelo" class="badge" style="font-size: 11px; font-weight: 600; padding: 4px 8px; border-radius: 4px; <?= $origem_modelo_atual === 'analista' ? 'background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;' : 'background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;' ?>">
                        <?= $origem_modelo_atual === 'analista' ? '★ Modelo Customizado pelo Analista' : 'Padrão Oficial NORMAM-202' ?>
                    </span>
                </div>
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <button type="button" class="btn btn-sm btn-success" onclick="salvarObservacoesComoModeloAtual()" title="Salvar o texto atual deste formulário como o meu modelo padrão permanente para este tipo de licença">
                        <i class="fas fa-save"></i> Salvar como Meu Modelo Padrão (<span id="lbl_tipo_salvar"><?= h($tipo_licenca_atual) ?></span>)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="abrirModalGerenciarModelos()" title="Abrir painel para editar e personalizar os textos de todos os 4 modelos (LC, LA, LR, LCEC)">
                        <i class="fas fa-sliders-h"></i> Personalizar Modelos
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="restaurarObservacoesPadrao(null, false)" title="Recalcular e restaurar com o modelo do analista naval e dados da embarcação">
                        <i class="fas fa-magic"></i> Restaurar Modelo do Analista
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="restaurarObservacoesPadrao(null, true)" title="Reverter para o texto original de fábrica da NORMAM-202">
                        <i class="fas fa-undo"></i> Padrão NORMAM Original
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="alerta_modelo_msg" style="display:none; margin-bottom: 12px; padding: 10px 14px; border-radius: 6px; font-size: 13px; font-weight: 500;"></div>

                <p class="text-muted" style="margin-bottom: 10px; font-size: 13px;">
                    <i class="fas fa-info-circle"></i> Estas observações serão impressas diretamente no PDF oficial da Licença (Anexo 3-A NORMAM-202). O sistema pré-carregou automaticamente os dados técnicos com base no cadastro da embarcação e da análise (batimento de quilha, RAP, dimensões, lotação máxima e singradura). Você pode personalizar qualquer linha livremente antes de salvar ou emitir.
                </p>
                
                <!-- Chips/Pills de atalho rápido -->
                <div style="margin-bottom: 12px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    <span style="font-size: 12px; font-weight: 600; color: #555;"><i class="fas fa-bolt"></i> Preencher modelo em 1 clique:</span>
                    <button type="button" class="btn btn-sm" id="btn_chip_LC" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-weight: 500;" onclick="aplicarModeloObservacoes('LC')">
                        <i class="fas fa-hammer"></i> Padrão LC (Construção) <span id="chip_indicador_LC"><?= !empty($modelosAnalista['LC']['origem']) && $modelosAnalista['LC']['origem'] === 'analista' ? '★' : '' ?></span>
                    </button>
                    <button type="button" class="btn btn-sm" id="btn_chip_LA" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-weight: 500;" onclick="aplicarModeloObservacoes('LA')">
                        <i class="fas fa-wrench"></i> Padrão LA (Alteração) <span id="chip_indicador_LA"><?= !empty($modelosAnalista['LA']['origem']) && $modelosAnalista['LA']['origem'] === 'analista' ? '★' : '' ?></span>
                    </button>
                    <button type="button" class="btn btn-sm" id="btn_chip_LR" style="background: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe; font-weight: 500;" onclick="aplicarModeloObservacoes('LR')">
                        <i class="fas fa-compass"></i> Padrão LR (Reclassificação) <span id="chip_indicador_LR"><?= !empty($modelosAnalista['LR']['origem']) && $modelosAnalista['LR']['origem'] === 'analista' ? '★' : '' ?></span>
                    </button>
                    <button type="button" class="btn btn-sm" id="btn_chip_LCEC" style="background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; font-weight: 500;" onclick="aplicarModeloObservacoes('LCEC')">
                        <i class="fas fa-ship"></i> Padrão LCEC (Já Construída) <span id="chip_indicador_LCEC"><?= !empty($modelosAnalista['LCEC']['origem']) && $modelosAnalista['LCEC']['origem'] === 'analista' ? '★' : '' ?></span>
                    </button>
                </div>

                <div class="form-group mb-0">
                    <textarea name="observacoes" id="observacoes" rows="13" class="form-control" style="font-family: Consolas, Monaco, 'Courier New', monospace; font-size: 13px; line-height: 1.55;"><?php echo h($observacoes_valor); ?></textarea>
                    <small class="text-muted"><i class="fas fa-shield-alt"></i> Modelos suportados pela NORMAM-202: <strong>LC (Construção)</strong>, <strong>LA (Alteração)</strong>, <strong>LR (Reclassificação)</strong> e <strong>LCEC (Embarcação já Construída)</strong>. As variáveis dinâmicas (quilha, RAP, comprimento, lotação e porte) são preenchidas automaticamente a partir dos campos do formulário.</small>
                </div>
            </div>
        </div>

        <!-- Seção 6: Assinatura -->
        <div class="card mb-3">
            <div class="card-header"><h3><i class="fas fa-user-tie"></i> Responsável pela Assinatura</h3></div>
            <div class="card-body">
                <div class="grid-3">
                    <div class="form-group">
                        <label for="assinante_nome">Nome Completo</label>
                        <input type="text" name="assinante_nome" id="assinante_nome" class="form-control"
                               value="<?php echo $editando ? h($licenca['assinante_nome']) : h($preenchimento['assinante_nome'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="assinante_titulo">Título/Cargo</label>
                        <input type="text" name="assinante_titulo" id="assinante_titulo" class="form-control" placeholder="Ex: Engenheiro Naval / Analista Técnico"
                               value="<?php echo $editando ? h($licenca['assinante_titulo']) : h($preenchimento['assinante_titulo'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="assinante_registro">Registro Profissional</label>
                        <input type="text" name="assinante_registro" id="assinante_registro" class="form-control" placeholder="Ex: CREA: 22.482"
                               value="<?php echo $editando ? h($licenca['assinante_registro']) : h($preenchimento['assinante_registro'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões -->
        <div class="card mb-3">
            <div class="card-footer" style="display: flex; gap: 10px; justify-content: flex-end; align-items: center;">
                <a href="<?php echo APP_URL; ?>documentacao/lc" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
                <?php if ($editando && $licenca['status'] === 'emitido' && empty($licenca['assinado'])): ?>
                    <?php renderBotaoAprovacaoDocumento($pdo, $licenca['tipo_licenca'] ?: 'LC', $licenca['id'], $licenca['status'], (bool)$licenca['assinado'], (int)($licenca['responsavel_assinatura_id'] ?: 0)); ?>
                <?php endif; ?>
                <?php if ($bloqueio_exigencias): ?>
                    <button type="button" class="btn btn-danger" disabled title="Bloqueado por exigências pendentes no RAP">
                        <i class="fas fa-ban"></i> Emissão Bloqueada (Exigências Pendentes)
                    </button>
                <?php else: ?>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> <?php echo $editando ? 'Atualizar' : 'Salvar e Emitir'; ?> Licença
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- MODAL DE PERSONALIZAÇÃO DE MODELOS DE OBSERVAÇÃO DO ANALISTA -->
    <div id="modalModelosObservacoes" style="display:none; position:fixed; z-index:99999; left:0; top:0; width:100%; height:100%; background:rgba(15,23,42,0.65); overflow-y:auto; backdrop-filter:blur(3px);">
        <div style="max-width:880px; margin:35px auto; background:#fff; border-radius:12px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.3); overflow:hidden; border:1px solid #cbd5e1;">
            <div style="background:#0f172a; color:#fff; padding:18px 24px; display:flex; justify-content:space-between; align-items:center;">
                <h4 style="margin:0; font-size:1.18rem; font-weight:600; display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-sliders-h text-primary" style="color:#38bdf8;"></i> Personalizar Modelos de Observação do Analista (NORMAM-202)
                </h4>
                <button type="button" onclick="fecharModalGerenciarModelos()" style="background:transparent; border:none; color:#94a3b8; font-size:1.6rem; cursor:pointer; line-height:1;">&times;</button>
            </div>
            
            <div style="padding:22px 26px;">
                <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:12px 18px; margin-bottom:18px;">
                    <p style="margin:0; font-size:0.875rem; color:#166534; line-height:1.5;">
                        <i class="fas fa-info-circle"></i> <strong>Instruções do Analista Naval:</strong> Você pode ajustar o texto de qualquer um dos 4 modelos oficiais. As tags dinâmicas como <code>{comprimento_total}</code>, <code>{comprimento_casco}</code>, <code>{numero_rap}</code>, <code>{ano_quilha}</code> e <code>{lotacao_regular}</code> são preservadas e continuarão sendo substituídas automaticamente com os dados da embarcação que você abrir!
                    </p>
                </div>

                <!-- Abas dos 4 modelos -->
                <div style="display:flex; gap:6px; border-bottom:2px solid #e2e8f0; margin-bottom:16px;">
                    <button type="button" class="btn-aba-modelo active" id="tab_btn_LC" onclick="trocarAbaModelo('LC')" style="padding:10px 18px; border:none; background:none; font-weight:600; font-size:0.92rem; cursor:pointer; border-bottom:3px solid #0284c7; color:#0284c7;">
                        <i class="fas fa-hammer"></i> LC (Construção) <span id="modal_tag_origem_LC" style="font-size:10px; font-weight:normal; margin-left:4px;"></span>
                    </button>
                    <button type="button" class="btn-aba-modelo" id="tab_btn_LA" onclick="trocarAbaModelo('LA')" style="padding:10px 18px; border:none; background:none; font-weight:500; font-size:0.92rem; cursor:pointer; color:#64748b;">
                        <i class="fas fa-wrench"></i> LA (Alteração) <span id="modal_tag_origem_LA" style="font-size:10px; font-weight:normal; margin-left:4px;"></span>
                    </button>
                    <button type="button" class="btn-aba-modelo" id="tab_btn_LR" onclick="trocarAbaModelo('LR')" style="padding:10px 18px; border:none; background:none; font-weight:500; font-size:0.92rem; cursor:pointer; color:#64748b;">
                        <i class="fas fa-compass"></i> LR (Reclassificação) <span id="modal_tag_origem_LR" style="font-size:10px; font-weight:normal; margin-left:4px;"></span>
                    </button>
                    <button type="button" class="btn-aba-modelo" id="tab_btn_LCEC" onclick="trocarAbaModelo('LCEC')" style="padding:10px 18px; border:none; background:none; font-weight:500; font-size:0.92rem; cursor:pointer; color:#64748b;">
                        <i class="fas fa-ship"></i> LCEC (Já Construída) <span id="modal_tag_origem_LCEC" style="font-size:10px; font-weight:normal; margin-left:4px;"></span>
                    </button>
                </div>

                <!-- Painel do Modelo Ativo -->
                <div>
                    <!-- Tags Rápidas -->
                    <div style="margin-bottom:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px;">
                        <span style="font-size:0.8rem; font-weight:600; color:#334155; margin-bottom:6px; display:block;">
                            <i class="fas fa-tags text-primary"></i> Clique para inserir uma tag dinâmica no texto do modelo:
                        </span>
                        <div style="display:flex; flex-wrap:wrap; gap:6px;">
                            <button type="button" class="btn btn-xs" style="background:#fff; border:1px solid #cbd5e1; font-size:0.78rem; padding:3px 8px; border-radius:4px; cursor:pointer;" onclick="inserirTagNoModelo('{ano_quilha}')" title="Ano de batimento de quilha">+ {ano_quilha}</button>
                            <button type="button" class="btn btn-xs" style="background:#fff; border:1px solid #cbd5e1; font-size:0.78rem; padding:3px 8px; border-radius:4px; cursor:pointer;" onclick="inserirTagNoModelo('{ano_evento}')" title="Ano do evento (previsão/alteração/reclassificação)">+ {ano_evento}</button>
                            <button type="button" class="btn btn-xs" style="background:#fff; border:1px solid #cbd5e1; font-size:0.78rem; padding:3px 8px; border-radius:4px; cursor:pointer;" onclick="inserirTagNoModelo('{numero_rap}')" title="Número do Relatório de Análise de Planos">+ {numero_rap}</button>
                            <button type="button" class="btn btn-xs" style="background:#fff; border:1px solid #cbd5e1; font-size:0.78rem; padding:3px 8px; border-radius:4px; cursor:pointer;" onclick="inserirTagNoModelo('{comprimento_total}')" title="Comprimento total com rampas (m)">+ {comprimento_total}</button>
                            <button type="button" class="btn btn-xs" style="background:#fff; border:1px solid #cbd5e1; font-size:0.78rem; padding:3px 8px; border-radius:4px; cursor:pointer;" onclick="inserirTagNoModelo('{comprimento_casco}')" title="Comprimento do casco (m)">+ {comprimento_casco}</button>
                            <button type="button" class="btn btn-xs" style="background:#fff; border:1px solid #cbd5e1; font-size:0.78rem; padding:3px 8px; border-radius:4px; cursor:pointer;" onclick="inserirTagNoModelo('{tripulantes}')" title="Número de tripulantes formatado (ex: 06)">+ {tripulantes}</button>
                            <button type="button" class="btn btn-xs" style="background:#fff; border:1px solid #cbd5e1; font-size:0.78rem; padding:3px 8px; border-radius:4px; cursor:pointer;" onclick="inserirTagNoModelo('{passageiros_turismo}')" title="Lotação máxima turismo">+ {passageiros_turismo}</button>
                            <button type="button" class="btn btn-xs" style="background:#fff; border:1px solid #cbd5e1; font-size:0.78rem; padding:3px 8px; border-radius:4px; cursor:pointer;" onclick="inserirTagNoModelo('{passageiros_regular}')" title="Lotação máxima regular">+ {passageiros_regular}</button>
                            <button type="button" class="btn btn-xs" style="background:#fff; border:1px solid #cbd5e1; font-size:0.78rem; padding:3px 8px; border-radius:4px; cursor:pointer;" onclick="inserirTagNoModelo('{passageiros_misto}')" title="Lotação máxima mista">+ {passageiros_misto}</button>
                            <button type="button" class="btn btn-xs" style="background:#fff; border:1px solid #cbd5e1; font-size:0.78rem; padding:3px 8px; border-radius:4px; cursor:pointer;" onclick="inserirTagNoModelo('{porte_bruto}')" title="Carga sobre o convés / porte bruto (t)">+ {porte_bruto}</button>
                            <button type="button" class="btn btn-xs" style="background:#fff; border:1px solid #cbd5e1; font-size:0.78rem; padding:3px 8px; border-radius:4px; cursor:pointer;" onclick="inserirTagNoModelo('{tempo_singradura}')" title="Tempo de singradura">+ {tempo_singradura}</button>
                            <button type="button" class="btn btn-xs" style="background:#fff; border:1px solid #cbd5e1; font-size:0.78rem; padding:3px 8px; border-radius:4px; cursor:pointer;" onclick="inserirTagNoModelo('{restricao_carga}')" title="Restrição de carga nos porões">+ {restricao_carga}</button>
                        </div>
                    </div>

                    <!-- Textareas por tipo -->
                    <div id="painel_editor_LC" class="painel-modelo-aba">
                        <textarea id="modal_textarea_LC" rows="12" class="form-control" style="font-family:Consolas, Monaco, monospace; font-size:13px; line-height:1.5;"></textarea>
                    </div>
                    <div id="painel_editor_LA" class="painel-modelo-aba" style="display:none;">
                        <textarea id="modal_textarea_LA" rows="12" class="form-control" style="font-family:Consolas, Monaco, monospace; font-size:13px; line-height:1.5;"></textarea>
                    </div>
                    <div id="painel_editor_LR" class="painel-modelo-aba" style="display:none;">
                        <textarea id="modal_textarea_LR" rows="12" class="form-control" style="font-family:Consolas, Monaco, monospace; font-size:13px; line-height:1.5;"></textarea>
                    </div>
                    <div id="painel_editor_LCEC" class="painel-modelo-aba" style="display:none;">
                        <textarea id="modal_textarea_LCEC" rows="12" class="form-control" style="font-family:Consolas, Monaco, monospace; font-size:13px; line-height:1.5;"></textarea>
                    </div>
                </div>
            </div>

            <div style="background:#f8fafc; padding:16px 26px; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="restaurarModeloModalAtualAoPadraoNormam()">
                    <i class="fas fa-undo"></i> Restaurar Esta Aba para NORMAM Original
                </button>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="fecharModalGerenciarModelos()">Cancelar</button>
                    <button type="button" class="btn btn-success btn-sm" onclick="salvarModeloModalAtual()">
                        <i class="fas fa-check"></i> Salvar Este Modelo Padrão
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Modelos de observações do analista e NORMAM original
const modelosObservacoesAnalista = <?php echo json_encode($modelosAnalista, JSON_UNESCAPED_UNICODE); ?>;
const modelosOriginaisNormam = <?php echo json_encode($modelosOriginaisNormam, JSON_UNESCAPED_UNICODE); ?>;
const csrfTokenApp = <?php echo json_encode(gerarCSRF()); ?>;
let abaModeloAtual = 'LC';

function atualizarNumero() {
    const select = document.getElementById('tipo_licenca');
    if (!select) return;
    const tipo = select.value;
    const input = document.getElementById('numero_lc_display');
    const termGroup = document.getElementById('lc_term_group');
    if (termGroup) {
        termGroup.style.display = tipo === 'LCEC' ? '' : 'none';
    }
    const lblTipo = document.getElementById('lbl_tipo_salvar');
    if (lblTipo) lblTipo.textContent = tipo;

    atualizarBadgeOrigem(tipo);

    <?php if (!$editando): ?>
    if (input) {
        if (tipo === 'LCEC') input.value = <?php echo json_encode($proximo_numero_ec); ?>;
        else if (tipo === 'LA') input.value = <?php echo json_encode($proximo_numero_la); ?>;
        else if (tipo === 'LR') input.value = <?php echo json_encode($proximo_numero_lr); ?>;
        else input.value = <?php echo json_encode($proximo_numero); ?>;
    }
    <?php endif; ?>
}

function formatarMetros(val) {
    if (val === null || val === undefined || val === '' || isNaN(val)) return '';
    return parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
}

function obterTemplateAtualParaTipo(tipo, forcarOriginal = false) {
    tipo = (tipo || 'LC').toUpperCase();
    if (forcarOriginal) {
        return modelosOriginaisNormam[tipo] || modelosOriginaisNormam['LC'];
    }
    if (modelosObservacoesAnalista[tipo] && modelosObservacoesAnalista[tipo].conteudo) {
        return modelosObservacoesAnalista[tipo].conteudo;
    }
    return modelosOriginaisNormam[tipo] || modelosOriginaisNormam['LC'];
}

function compilarTemplateComDadosFormulario(template, tipo) {
    tipo = (tipo || 'LC').toUpperCase();
    const compTotalVal = document.getElementById('comprimento_total') ? document.getElementById('comprimento_total').value : '';
    const compCascoVal = (document.getElementById('comprimento_casco') ? document.getElementById('comprimento_casco').value : '') 
                      || (document.getElementById('comprimento_pp') ? document.getElementById('comprimento_pp').value : '');
    const anoQuilhaVal = (document.getElementById('ano_construcao') ? document.getElementById('ano_construcao').value : '') || '2022';
    const numTripVal = parseInt(document.getElementById('numero_tripulantes') ? document.getElementById('numero_tripulantes').value : 6) || 6;
    const numPassVal = parseInt(document.getElementById('numero_passageiros') ? document.getElementById('numero_passageiros').value : 0) || 0;
    const porteBrutoVal = parseFloat(document.getElementById('porte_bruto') ? document.getElementById('porte_bruto').value : 30) || 30;

    const tripFmt = String(numTripVal).padStart(2, '0');
    const compTotalFmt = compTotalVal ? formatarMetros(compTotalVal) : '32,560';
    const compCascoFmt = compCascoVal ? formatarMetros(compCascoVal) : '26,950';
    const porteBrutoFmt = Math.round(porteBrutoVal);

    let passRegular = numPassVal > 0 ? numPassVal : 173;
    let passTurismo = numPassVal > 0 ? Math.round(numPassVal * 1.514) : 262;
    let passMisto   = numPassVal > 0 ? Math.round(numPassVal * 0.624) : 108;
    if (numPassVal === 173) {
        passTurismo = 262;
        passMisto = 108;
    }

    const anoAtual = new Date().getFullYear();
    let anoEvento = String(anoAtual + 1);
    if (tipo === 'LA' || tipo === 'LR') {
        anoEvento = String(anoAtual);
    } else if (tipo === 'LCEC') {
        const dtTerm = document.getElementById('data_termino_construcao') ? document.getElementById('data_termino_construcao').value : '';
        anoEvento = String(dtTerm ? new Date(dtTerm).getFullYear() : anoAtual);
    } else if (tipo === 'LC') {
        const dtVal = document.getElementById('data_validade') ? document.getElementById('data_validade').value : '';
        anoEvento = String(dtVal ? new Date(dtVal).getFullYear() : (anoAtual + 1));
    }

    const relatorioNumero = <?php echo json_encode(!empty($preenchimento['relatorio_numero']) ? $preenchimento['relatorio_numero'] : (!empty($licenca['relatorio_numero']) ? $licenca['relatorio_numero'] : '')); ?> || ('RC-RAP' + anoAtual);

    let texto = template;

    // Mapa completo de interpolação de tags
    const mapa = {
        '{ano_quilha}': anoQuilhaVal,
        '{ano_construcao}': anoQuilhaVal,
        '{ano_evento}': anoEvento,
        '{ano_conclusao}': anoEvento,
        '{ano_alteracao}': anoEvento,
        '{ano_reclassificacao}': anoEvento,
        '{ano_conclusao_lcec}': anoEvento,
        '{numero_rap}': relatorioNumero,
        '{relatorio_numero}': relatorioNumero,
        '{rap}': relatorioNumero,
        '{comprimento_total}': compTotalFmt,
        '{ct}': compTotalFmt,
        '{comprimento_casco}': compCascoFmt,
        '{comprimento_regra}': compCascoFmt,
        '{l}': compCascoFmt,
        '{tripulantes}': tripFmt,
        '{numero_tripulantes}': tripFmt,
        '{passageiros_turismo}': passTurismo,
        '{passageiros_regular}': passRegular,
        '{passageiros_misto}': passMisto,
        '{lotacao_turismo}': passTurismo,
        '{lotacao_regular}': passRegular,
        '{lotacao_misto}': passMisto,
        '{porte_bruto}': porteBrutoFmt,
        '{carga}': porteBrutoFmt,
        '{tempo_singradura}': 'inferior a 12 h',
        '{restricao_carga}': 'A embarcação não poderá transportar carga nos porões.'
    };

    for (const [tag, val] of Object.entries(mapa)) {
        const re = new RegExp(tag.replace(/([{}])/g, '\\$1'), 'gi');
        texto = texto.replace(re, val);
    }

    return texto;
}

function aplicarModeloObservacoes(tipo) {
    const selTipo = document.getElementById('tipo_licenca');
    if (selTipo && selTipo.value !== tipo) {
        selTipo.value = tipo;
        atualizarNumero();
    }
    const lblTipo = document.getElementById('lbl_tipo_salvar');
    if (lblTipo) lblTipo.textContent = tipo;
    restaurarObservacoesPadrao(tipo, false);
}

function restaurarObservacoesPadrao(tipoForcado = null, forcarOriginal = false) {
    const tipo = tipoForcado || (document.getElementById('tipo_licenca') ? document.getElementById('tipo_licenca').value : 'LC') || 'LC';
    const template = obterTemplateAtualParaTipo(tipo, forcarOriginal);
    const textoCompilado = compilarTemplateComDadosFormulario(template, tipo);
    const textarea = document.getElementById('observacoes');
    if (textarea) {
        textarea.value = textoCompilado;
    }
    atualizarBadgeOrigem(tipo, forcarOriginal);
    
    if (forcarOriginal) {
        mostrarNotificacaoFlutuante('✓ Texto restaurado para o Padrão Oficial NORMAM-202.', 'info');
    } else {
        const eCustom = modelosObservacoesAnalista[tipo] && modelosObservacoesAnalista[tipo].origem === 'analista';
        mostrarNotificacaoFlutuante(eCustom ? '✓ Modelo personalizado do Analista aplicado com dados da embarcação.' : '✓ Modelo oficial NORMAM-202 aplicado com dados da embarcação.', 'success');
    }
}

function atualizarBadgeOrigem(tipo, forcarOriginal = false) {
    const badge = document.getElementById('badge_status_modelo');
    if (!badge) return;
    const isCustom = !forcarOriginal && modelosObservacoesAnalista[tipo] && modelosObservacoesAnalista[tipo].origem === 'analista';
    if (isCustom) {
        badge.textContent = '★ Modelo Customizado pelo Analista';
        badge.style.background = '#dcfce7';
        badge.style.color = '#166534';
        badge.style.border = '1px solid #bbf7d0';
    } else {
        badge.textContent = 'Padrão Oficial NORMAM-202';
        badge.style.background = '#e0f2fe';
        badge.style.color = '#0369a1';
        badge.style.border = '1px solid #bae6fd';
    }
}

// Converte valores estáticos preenchidos no texto em tags automáticas
function converterTextoParaTemplateComTags(texto, tipo) {
    let t = texto;

    // Relatório RAP específico do formulário
    const rapVal = <?php echo json_encode(!empty($preenchimento['relatorio_numero']) ? $preenchimento['relatorio_numero'] : (!empty($licenca['relatorio_numero']) ? $licenca['relatorio_numero'] : '')); ?>;
    if (rapVal && rapVal.trim()) {
        t = t.split(rapVal.trim()).join('{numero_rap}');
    }

    // Comprimentos do formulário
    const cTotalVal = document.getElementById('comprimento_total') ? document.getElementById('comprimento_total').value : '';
    if (cTotalVal) {
        const fmtTotal = formatarMetros(cTotalVal);
        if (fmtTotal) t = t.split(fmtTotal + ' m').join('{comprimento_total} m');
    }
    const cCascoVal = (document.getElementById('comprimento_casco') ? document.getElementById('comprimento_casco').value : '')
                   || (document.getElementById('comprimento_pp') ? document.getElementById('comprimento_pp').value : '');
    if (cCascoVal) {
        const fmtCasco = formatarMetros(cCascoVal);
        if (fmtCasco) t = t.split(fmtCasco + ' m').join('{comprimento_casco} m');
    }

    // Relatório RAP genérico via Regex
    t = t.replace(/(base no Relatório de Análise de Planos n\.º\s+)(RC-RAP[^\s.]+|AM-RAP[^\s.]+|[A-Z0-9\/-]+)/gi, '$1{numero_rap}');

    // Batimento de Quilha
    t = t.replace(/(1\s*-\s*Data de Batimento de Quilha:\s*)\d{4}/gi, '$1{ano_quilha}');

    // Evento da Linha 2
    t = t.replace(/(2\s*-\s*(?:Previsão de Conclusão da Construção|Data de Alteração|Data de Reclassificação|Data de Conclusão da Construção \(LCEC\)):\s*)\d{4}/gi, '$1{ano_evento}');

    // Comprimentos total e casco genérico via Regex
    t = t.replace(/(comprimento total com rampas de\s+)[\d,.]+(\s*m\s+e\s+comprimento do casco de\s+)[\d,.]+(\s*m)/gi, '$1{comprimento_total}$2{comprimento_casco}$3');

    // Lotação Turismo
    t = t.replace(/(turismo:\s*está destinada a operar com\s+)\d+(\s+passageiros\s+e\s+)\d+(\s+tripulantes)/gi, '$1{passageiros_turismo}$2{tripulantes}$3');

    // Lotação Passageiros Regular
    t = t.replace(/(passageiros:\s*está destinada a operar com\s+)\d+(\s+passageiros\s+e\s+)\d+(\s+tripulantes)/gi, '$1{passageiros_regular}$2{tripulantes}$3');

    // Lotação Passageiros Misto + Carga
    t = t.replace(/(passageiros e carga no convés:\s*está destinada a operar com\s+)\d+(\s+passageiros,\s+)\d+(\s+tripulantes,\s+e com\s+)[\d,.]+(\s*t\.\s+de carga)/gi, '$1{passageiros_misto}$2{tripulantes}$3{porte_bruto}$4');

    return t;
}

function salvarObservacoesComoModeloAtual() {
    const tipo = (document.getElementById('tipo_licenca') ? document.getElementById('tipo_licenca').value : 'LC') || 'LC';
    const textarea = document.getElementById('observacoes');
    if (!textarea || !textarea.value.trim()) {
        alert('O campo de observações está vazio.');
        return;
    }

    let texto = textarea.value.trim();
    texto = converterTextoParaTemplateComTags(texto, tipo);

    salvarModeloAjax(tipo, texto, function(res) {
        if (res.sucesso) {
            modelosObservacoesAnalista[tipo] = {
                tipo: tipo,
                origem: 'analista',
                conteudo: texto,
                atualizado_em: new Date().toISOString()
            };
            atualizarBadgeOrigem(tipo, false);
            const chipInd = document.getElementById('chip_indicador_' + tipo);
            if (chipInd) chipInd.textContent = '★';
            mostrarNotificacaoFlutuante('✓ Sucesso: O seu texto foi salvo como modelo padrão ' + tipo + '! Ele será carregado sempre que você clicar no botão ou criar uma nova licença.');
        } else {
            alert(res.mensagem || 'Erro ao salvar modelo.');
        }
    });
}

function salvarModeloAjax(tipo, conteudo, callback) {
    const formData = new FormData();
    formData.append('action', 'salvar_modelo_observacao');
    formData.append('ajax', '1');
    formData.append('csrf_token', csrfTokenApp);
    formData.append('tipo_licenca', tipo);
    formData.append('conteudo_template', conteudo);

    fetch('<?php echo APP_URL; ?>documentacao/lc/actions', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => callback(data))
    .catch(err => {
        console.error(err);
        alert('Falha na comunicação com o servidor ao salvar modelo.');
    });
}

function abrirModalGerenciarModelos() {
    const tipos = ['LC', 'LA', 'LR', 'LCEC'];
    tipos.forEach(t => {
        const tpl = obterTemplateAtualParaTipo(t, false);
        const ta = document.getElementById('modal_textarea_' + t);
        if (ta) ta.value = tpl;
        const tagOrigem = document.getElementById('modal_tag_origem_' + t);
        if (tagOrigem) {
            const isCustom = modelosObservacoesAnalista[t] && modelosObservacoesAnalista[t].origem === 'analista';
            tagOrigem.textContent = isCustom ? '(Personalizado)' : '(NORMAM)';
            tagOrigem.style.color = isCustom ? '#166534' : '#64748b';
        }
    });

    const tipoAtual = (document.getElementById('tipo_licenca') ? document.getElementById('tipo_licenca').value : 'LC') || 'LC';
    trocarAbaModelo(tipoAtual);

    const modal = document.getElementById('modalModelosObservacoes');
    if (modal) modal.style.display = 'block';
}

function fecharModalGerenciarModelos() {
    const modal = document.getElementById('modalModelosObservacoes');
    if (modal) modal.style.display = 'none';
}

function trocarAbaModelo(tipo) {
    abaModeloAtual = tipo;
    const tipos = ['LC', 'LA', 'LR', 'LCEC'];
    tipos.forEach(t => {
        const btn = document.getElementById('tab_btn_' + t);
        const painel = document.getElementById('painel_editor_' + t);
        if (t === tipo) {
            if (btn) {
                btn.classList.add('active');
                btn.style.borderBottom = '3px solid #0284c7';
                btn.style.color = '#0284c7';
                btn.style.fontWeight = '600';
            }
            if (painel) painel.style.display = 'block';
        } else {
            if (btn) {
                btn.classList.remove('active');
                btn.style.borderBottom = 'none';
                btn.style.color = '#64748b';
                btn.style.fontWeight = '500';
            }
            if (painel) painel.style.display = 'none';
        }
    });
}

function inserirTagNoModelo(tag) {
    const ta = document.getElementById('modal_textarea_' + abaModeloAtual);
    if (!ta) return;
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const val = ta.value;
    ta.value = val.substring(0, start) + tag + val.substring(end);
    ta.selectionStart = ta.selectionEnd = start + tag.length;
    ta.focus();
}

function salvarModeloModalAtual() {
    const tipo = abaModeloAtual;
    const ta = document.getElementById('modal_textarea_' + tipo);
    if (!ta || !ta.value.trim()) {
        alert('O texto do modelo não pode estar vazio.');
        return;
    }

    const conteudo = ta.value.trim();
    salvarModeloAjax(tipo, conteudo, function(res) {
        if (res.sucesso) {
            modelosObservacoesAnalista[tipo] = {
                tipo: tipo,
                origem: 'analista',
                conteudo: conteudo,
                atualizado_em: new Date().toISOString()
            };
            const chipInd = document.getElementById('chip_indicador_' + tipo);
            if (chipInd) chipInd.textContent = '★';

            const selTipo = document.getElementById('tipo_licenca');
            if (selTipo && selTipo.value === tipo) {
                restaurarObservacoesPadrao(tipo, false);
            }
            fecharModalGerenciarModelos();
            mostrarNotificacaoFlutuante('✓ Modelo ' + tipo + ' salvo e atualizado com sucesso no formulário!');
        } else {
            alert(res.mensagem || 'Erro ao salvar modelo.');
        }
    });
}

function restaurarModeloModalAtualAoPadraoNormam() {
    const tipo = abaModeloAtual;
    if (!confirm('Deseja realmente reverter o modelo ' + tipo + ' para o padrão de fábrica da NORMAM-202?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'restaurar_modelo_observacao');
    formData.append('ajax', '1');
    formData.append('csrf_token', csrfTokenApp);
    formData.append('tipo_licenca', tipo);

    fetch('<?php echo APP_URL; ?>documentacao/lc/actions', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.sucesso) {
            delete modelosObservacoesAnalista[tipo];
            const ta = document.getElementById('modal_textarea_' + tipo);
            if (ta) ta.value = res.conteudo;
            const chipInd = document.getElementById('chip_indicador_' + tipo);
            if (chipInd) chipInd.textContent = '';

            const selTipo = document.getElementById('tipo_licenca');
            if (selTipo && selTipo.value === tipo) {
                restaurarObservacoesPadrao(tipo, true);
            }
            fecharModalGerenciarModelos();
            mostrarNotificacaoFlutuante('✓ Modelo ' + tipo + ' restaurado para o original da NORMAM-202.', 'info');
        } else {
            alert(res.mensagem || 'Erro ao restaurar modelo.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Erro ao comunicar com o servidor.');
    });
}

function mostrarNotificacaoFlutuante(msg, tipo = 'success') {
    const el = document.getElementById('alerta_modelo_msg');
    if (!el) return;
    el.innerHTML = '<i class="fas fa-check-circle"></i> ' + msg;
    el.style.display = 'block';
    if (tipo === 'info') {
        el.style.background = '#e0f2fe';
        el.style.color = '#0369a1';
        el.style.border = '1px solid #bae6fd';
    } else {
        el.style.background = '#f0fdf4';
        el.style.color = '#166534';
        el.style.border = '1px solid #bbf7d0';
    }
    setTimeout(() => {
        el.style.display = 'none';
    }, 6000);
}

function carregarDadosEmbarcacao(embarcacaoId) {
    if (!embarcacaoId) return;
    
    const select = document.getElementById('embarcacao_id');
    const option = select.options[select.selectedIndex];
    
    if (!option) return;
    
    const campos = {
        'nome_embarcacao': 'nome',
        'numero_inscricao': 'registro',
        'indicativo_chamada': 'indicativo_chamada',
        'tipo_embarcacao': 'tipo',
        'ano_construcao': 'ano',
        'comprimento_total': 'comprimento_total',
        'comprimento_casco': 'comprimento_casco',
        'boca_moldada': 'boca_moldada',
        'pontal_moldado': 'pontal_moldado',
        'arqueacao_bruta': 'arqueacao_bruta',
        'material_casco': 'material_casco',
        'numero_tripulantes': 'tripulantes',
        'numero_passageiros': 'passageiros',
        'porte_bruto': 'porte_bruto'
    };
    
    for (const [fieldId, dataAttr] of Object.entries(campos)) {
        const input = document.getElementById(fieldId);
        if (input && (!input.value || input.value === '' || input.value === '0')) {
            const value = option.dataset[dataAttr] || '';
            if (value !== '') {
                input.value = value;
            }
        }
    }

    // Se o campo observações estiver vazio, atualiza automaticamente com os dados da nova embarcação
    const textareaObs = document.getElementById('observacoes');
    if (textareaObs && (!textareaObs.value || textareaObs.value.trim() === '')) {
        restaurarObservacoesPadrao();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    atualizarNumero();
    <?php if (!empty($_GET['agendamento_id'])): ?>
    const select = document.getElementById('embarcacao_id');
    if (select && select.value) {
        carregarDadosEmbarcacao(select.value);
    }
    <?php endif; ?>
});
</script>

<?php renderAprovacaoUi($pdo); require_once __DIR__ . '/../../../includes/footer.php'; ?>
