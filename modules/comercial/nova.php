<?php
/**
 * MÓDULO: COMERCIAL > PROPOSTAS
 * Arquivo: nova.php - Wizard de nova proposta
 * Passo 1: Selecionar cliente -> carregar embarcações automaticamente
 * Passo 2: Selecionar serviços por embarcação com preço automático, desconto e total geral
 * Passo 3: Revisão e confirmação
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/financeiro_escritorios.php';

verificar_sessao();
exigirAcesso('comercial');

$modoEdicao = !empty($_GET['id']);
$propostaEdicao = null;
$servicosEdicaoIniciais = [];

if ($modoEdicao) {
    $propostaIdEdicao = (string)$_GET['id'];
    $stmtEdicao = $pdo->prepare("
        SELECT p.*, c.nome AS cliente_nome, c.perfil AS cliente_perfil, c.cpf_cnpj AS cliente_cpfcnpj
        FROM propostas p
        INNER JOIN clientes c ON c.id = p.cliente_id
        WHERE p.id = :id
        LIMIT 1
    ");
    $stmtEdicao->execute([':id' => $propostaIdEdicao]);
    $propostaEdicao = $stmtEdicao->fetch(PDO::FETCH_ASSOC);

    $cargoEdicao = getCargo();
    $usuarioEdicao = (string)($_SESSION['usuario_id'] ?? '');
    $podeEditar = $propostaEdicao
        && in_array($cargoEdicao, ['ADMIN', 'VENDEDOR'], true)
        && ($cargoEdicao === 'ADMIN' || (string)$propostaEdicao['criado_por'] === $usuarioEdicao)
        && ($propostaEdicao['status'] ?? '') === 'rascunho'
        && empty($propostaEdicao['assinado'])
        && financeiroPodeAcessarEscritorio($pdo, (string)($propostaEdicao['escritorio_id'] ?? ''));

    if (!$podeEditar) {
        setMensagem('error', 'A proposta não foi encontrada ou não está disponível para edição.');
        redirecionar(APP_URL . 'comercial');
    }

    $stmtServicosEdicao = $pdo->prepare("
        SELECT ps.embarcacao_id, ps.servico_id, ps.quantidade, ps.preco_aplicado
        FROM propostas_servicos ps
        WHERE ps.proposta_id = :id
    ");
    $stmtServicosEdicao->execute([':id' => $propostaIdEdicao]);
    foreach ($stmtServicosEdicao->fetchAll(PDO::FETCH_ASSOC) as $servicoEdicao) {
        $embarcacaoId = (string)$servicoEdicao['embarcacao_id'];
        $servicoId = (string)$servicoEdicao['servico_id'];
        if (!isset($servicosEdicaoIniciais[$embarcacaoId])) {
            $servicosEdicaoIniciais[$embarcacaoId] = [];
        }
        $servicosEdicaoIniciais[$embarcacaoId][$servicoId] = [
            'qtd' => max(1, (int)$servicoEdicao['quantidade']),
            'preco' => round((float)$servicoEdicao['preco_aplicado'], 2),
        ];
    }
}

$escritoriosProposta = financeiroEscritoriosPermitidos($pdo);
$escritorioProposta = '';
try {
    $escritorioSolicitado = $modoEdicao ? ($propostaEdicao['escritorio_id'] ?? null) : ($_GET['escritorio_id'] ?? null);
    $escritorioProposta = financeiroResolverEscritorio($pdo, $escritorioSolicitado);
} catch (RuntimeException $e) {
    // A tela deve orientar o usuário em vez de responder HTTP 500 quando falta vínculo.
    error_log('Nova proposta sem escritorio disponivel: ' . $e->getMessage());
}
if ($escritorioProposta === 'todos') $escritorioProposta = financeiroEscritorioUsuario($pdo) ?: ($escritoriosProposta[0]['id'] ?? '');
$idsEscritoriosProposta = array_column($escritoriosProposta, 'id');
if (!in_array($escritorioProposta, $idsEscritoriosProposta, true)) $escritorioProposta = (string)($idsEscritoriosProposta[0] ?? '');
$selecionarEscritorioProposta = financeiroEhAdmin() || count($escritoriosProposta) > 1;
$escritorioPropostaDisponivel = $escritorioProposta !== '';

// Buscar proprietarios ativos
try {
    $stmtClientes = $pdo->query("SELECT id, nome, perfil, cpf_cnpj FROM clientes WHERE status = 'ATIVO' AND perfil = 'proprietario' ORDER BY nome ASC");
    $clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $clientes = [];
}

$clientePreSelecionadoId = $modoEdicao ? (string)$propostaEdicao['cliente_id'] : ($_GET['cliente_id'] ?? '');
$clientePreSelecionadoEncontrado = false;

if (!empty($clientes)) {
    foreach ($clientes as $cliente) {
        if (!empty($clientePreSelecionadoId) && $cliente['id'] === $clientePreSelecionadoId) {
            $clientePreSelecionadoEncontrado = true;
            break;
        }
    }

    if (!$clientePreSelecionadoEncontrado && count($clientes) === 1) {
        $clientePreSelecionadoId = $clientes[0]['id'];
        $clientePreSelecionadoEncontrado = true;
    }
}

// Buscar todos os serviços ativos
try {
    if ($modoEdicao) {
        $stmtServicos = $pdo->prepare("
            SELECT DISTINCT s.id, s.nome, s.descricao, s.preco_padrao
            FROM servicos s
            LEFT JOIN propostas_servicos ps ON ps.servico_id = s.id AND ps.proposta_id = :proposta
            WHERE s.ativo = 1 OR ps.id IS NOT NULL
            ORDER BY s.nome ASC
        ");
        $stmtServicos->execute([':proposta' => $propostaEdicao['id']]);
    } else {
        $stmtServicos = $pdo->query("SELECT id, nome, descricao, preco_padrao FROM servicos WHERE ativo = 1 ORDER BY nome ASC");
    }
    $servicos = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $servicos = [];
}

$titulo_page = ($modoEdicao ? 'Editar Proposta' : 'Nova Proposta') . ' - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal flow-shell">

    <!-- Cabeçalho do Wizard -->
    <div class="flow-hero">
        <div>
            <span class="flow-eyebrow"><i class="fas fa-route"></i> Etapa 1 do fluxo</span>
            <h1><i class="fas fa-file-invoice"></i> <?php echo $modoEdicao ? 'Editar Proposta ' . h($propostaEdicao['numero']) : 'Nova Proposta'; ?></h1>
            <p><?php echo $modoEdicao ? 'Atualize os dados do rascunho e revise os valores antes de salvar.' : 'Escolha o proprietário, adicione o contato do fechamento se desejar e revise os valores antes de enviar para assinatura.'; ?></p>
        </div>
        <div class="flow-actions">
            <a href="<?php echo APP_URL; ?>comercial/propostas" class="btn btn-secondary btn-sm">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </div>

    <div class="flow-track">
        <div class="flow-track-step is-active"><span>01</span>Proposta</div>
        <div class="flow-track-step"><span>02</span>Agendamento</div>
        <div class="flow-track-step"><span>03</span>Vistoria</div>
        <div class="flow-track-step"><span>04</span>Aprovação</div>
        <div class="flow-track-step"><span>05</span>Certificados</div>
    </div>

    <!-- Indicador de Passos (Stepper) -->
    <div class="wizard-steps" id="stepper" style="display: flex; gap: 0; margin-bottom: 25px; background: var(--cor-painel); border: 1px solid var(--cor-borda); border-radius: 12px; overflow: hidden;">
        <div class="wizard-step active" data-step="1" style="flex: 1; text-align: center; padding: 15px 10px; cursor: pointer; transition: all 0.3s; border-bottom: 3px solid transparent;">
            <span class="step-number" style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; background: var(--cor-destaque); color: #fff; font-weight: 700; font-size: 0.85rem; margin-bottom: 6px;">1</span>
            <span class="step-label" style="display: block; font-size: 0.8rem; color: var(--cor-destaque); font-weight: 600;">Proprietário</span>
        </div>
        <div class="wizard-step" data-step="2" style="flex: 1; text-align: center; padding: 15px 10px; cursor: pointer; transition: all 0.3s; border-bottom: 3px solid transparent; opacity: 0.5;">
            <span class="step-number" style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; background: var(--cor-borda); color: var(--cor-texto-secundario); font-weight: 700; font-size: 0.85rem; margin-bottom: 6px;">2</span>
            <span class="step-label" style="display: block; font-size: 0.8rem; color: var(--cor-texto-secundario); font-weight: 500;">Serviços</span>
        </div>
        <div class="wizard-step" data-step="3" style="flex: 1; text-align: center; padding: 15px 10px; cursor: pointer; transition: all 0.3s; border-bottom: 3px solid transparent; opacity: 0.5;">
            <span class="step-number" style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; background: var(--cor-borda); color: var(--cor-texto-secundario); font-weight: 700; font-size: 0.85rem; margin-bottom: 6px;">3</span>
            <span class="step-label" style="display: block; font-size: 0.8rem; color: var(--cor-texto-secundario); font-weight: 500;">Revisão</span>
        </div>
    </div>

    <!-- Formulário principal -->
    <form id="wizardForm" action="<?php echo APP_URL; ?>comercial/propostas/actions" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
        <input type="hidden" name="action" value="<?php echo $modoEdicao ? 'atualizar' : 'criar'; ?>">
        <?php if ($modoEdicao): ?>
        <input type="hidden" name="id" value="<?php echo h($propostaEdicao['id']); ?>">
        <?php endif; ?>
        <input type="hidden" id="dadosCliente" name="dados_cliente" value="">
        <input type="hidden" id="dadosServicosJson" name="dados_servicos_json" value="">

        <!-- ===== PASSO 1: SELECIONAR CLIENTE ===== -->
        <div class="wizard-panel active" id="passo1">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-tie"></i> Passo 1: Proprietário e responsável pelo fechamento</h3>
                </div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom:18px">
                        <label for="escritorio_id"><i class="fas fa-building"></i> Escritório da proposta *</label>
                        <?php if(!$escritorioPropostaDisponivel): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Nenhum escritório ativo está vinculado ao seu usuário. <?php if(financeiroEhAdmin()): ?><a href="<?= APP_URL ?>configuracoes/financeiro">Cadastrar ou ativar um escritório</a>.<?php else: ?>Solicite o vínculo a um administrador.<?php endif ?></div>
                        <?php elseif($selecionarEscritorioProposta): ?>
                        <select id="escritorio_id" name="escritorio_id" required><?php foreach($escritoriosProposta as $e): ?><option value="<?= h($e['id']) ?>" <?= $e['id']===$escritorioProposta?'selected':'' ?>><?= h($e['nome'].' · '.$e['cidade'].'/'.$e['uf']) ?></option><?php endforeach ?></select>
                        <small class="text-muted">A receita gerada pela proposta ficará vinculada a este escritório.</small>
                        <?php else: ?>
                        <input type="hidden" name="escritorio_id" value="<?= h($escritorioProposta) ?>">
                        <input value="<?= h(($escritoriosProposta[0]['nome']??'Escritório').' · '.($escritoriosProposta[0]['cidade']??'').'/'.($escritoriosProposta[0]['uf']??'')) ?>" disabled>
                        <?php endif ?>
                    </div>
                    <div class="wizard-helper">
                        <i class="fas fa-info-circle"></i>
                        <span>Primeiro escolha o proprietário da embarcação. Se desejar, informe quem foi o responsável pelo fechamento da proposta e seu telefone.</span>
                    </div>
                    <?php if (empty($clientes)): ?>
                        <div class="tabela-vazia">
                            <i class="fas fa-user-tie"></i>
                            <h3>Nenhum proprietário cadastrado</h3>
                            <p>Cadastre um proprietário antes de criar uma proposta.</p>
                            <a href="<?php echo APP_URL; ?>proprietarios/form" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Novo Proprietário
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="filtros" style="margin-bottom: 15px;">
                            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                                <label><i class="fas fa-search"></i> Buscar proprietário</label>
                                <input type="text" id="buscaClienteWizard" placeholder="Nome, CPF/CNPJ..." onkeyup="filtrarClientes()">
                            </div>
                        </div>
                        <div class="cliente-grid" id="clienteGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 12px; max-height: 400px; overflow-y: auto; padding: 5px;">
                            <?php foreach ($clientes as $c): ?>
                            <?php $clienteMarcado = $clientePreSelecionadoEncontrado && $clientePreSelecionadoId === $c['id']; ?>
                            <label class="cliente-card<?php echo $clienteMarcado ? ' is-selected' : ''; ?>" style="display: flex; align-items: center; gap: 12px; padding: 14px 16px; background: var(--cor-fundo); border: 2px solid var(--cor-borda); border-radius: 10px; cursor: pointer; transition: all 0.2s;">
                                <input type="radio" name="cliente_id" value="<?php echo h($c['id']); ?>"
                                       data-nome="<?php echo h($c['nome']); ?>"
                                       data-perfil="Proprietário"
                                       data-cpfcnpj="<?php echo h($c['cpf_cnpj'] ?? '-'); ?>"
                                       <?php echo $clienteMarcado ? 'checked' : ''; ?>
                                       onchange="clienteSelecionado(this)" style="display: none;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(46,204,113,0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-user-tie" style="color: var(--cor-destaque);"></i>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 600; color: var(--cor-texto); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo h($c['nome']); ?></div>
                                    <small style="color: var(--cor-texto-secundario);">Proprietário &middot; <?php echo h($c['cpf_cnpj'] ?? 'N/I'); ?></small>
                                </div>
                                <span class="cliente-check-indicator"><i class="fas fa-check"></i><em>Selecionado</em></span>
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="armador-box responsavel-box">
                            <div>
                                <label for="responsavel_fechamento_nome"><i class="fas fa-user-check"></i> Responsável pelo fechamento da proposta</label>
                                <small>Campo opcional. Quando informado, ficará visível para o vistoriador durante a vistoria.</small>
                            </div>
                            <input type="text" id="responsavel_fechamento_nome" name="responsavel_fechamento_nome" maxlength="255"
                                   value="<?php echo h($propostaEdicao['responsavel_fechamento_nome'] ?? ''); ?>"
                                   placeholder="Ex.: João da Silva" autocomplete="name" oninput="atualizarPasso1()">
                        </div>
                        <div class="armador-box responsavel-box">
                            <div>
                                <label for="responsavel_fechamento_telefone"><i class="fas fa-phone"></i> Telefone do responsável</label>
                                <small>Campo opcional para facilitar o contato do vistoriador.</small>
                            </div>
                            <input type="tel" id="responsavel_fechamento_telefone" name="responsavel_fechamento_telefone" maxlength="15"
                                   value="<?php echo h($propostaEdicao['responsavel_fechamento_telefone'] ?? ''); ?>"
                                   placeholder="Ex.: (91) 99999-9999" inputmode="numeric" autocomplete="tel"
                                   oninput="formatarTelefoneResponsavel(this); atualizarPasso1()">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-actions" style="margin-top: 20px; text-align: right;">
                <button type="button" class="btn btn-primary" onclick="irParaPasso(2)" id="btnPasso1" disabled>
                    Próximo <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- ===== PASSO 2: SERVIÇOS POR EMBARCAÇÃO ===== -->
        <div class="wizard-panel" id="passo2" style="display: none;">
            <!-- Info do cliente selecionado -->
            <div id="passo2ClienteInfo" style="margin-bottom: 20px; padding: 12px 16px; background: var(--cor-painel); border: 1px solid var(--cor-borda); border-radius: 10px; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-user-tie" style="color: var(--cor-destaque); font-size: 1.2rem;"></i>
                <span style="color: var(--cor-texto-secundario);">Proprietário: <strong id="passo2ClienteNome" style="color: var(--cor-texto);"></strong></span>
            </div>

            <div id="embarcacoesServicosContainer">
                <div id="paso2Loading" style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--cor-destaque);"></i>
                    <p style="margin-top: 10px; color: var(--cor-texto-secundario);">Carregando embarcações do proprietário...</p>
                </div>
                <div id="paso2Content" style="display: none;"></div>
                <div id="paso2Vazio" style="display: none;" class="tabela-vazia">
                    <i class="fas fa-ship"></i>
                    <h3>Nenhuma embarcação vinculada</h3>
                    <p>Este proprietário não possui embarcações vinculadas. Vincule embarcações ao proprietário primeiro.</p>
                </div>
            </div>

            <!-- Painel de Totais -->
            <div id="totaisPainel" class="smart-total-panel" style="display: none; margin-top: 25px; padding: 20px; background: var(--cor-painel); border: 1px solid var(--cor-borda); border-radius: 12px;">
                <h4 style="color: var(--cor-destaque); margin-bottom: 15px;"><i class="fas fa-calculator"></i> Resumo Financeiro</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                    <div style="text-align: center; padding: 12px; background: var(--cor-fundo); border-radius: 8px; border: 1px solid var(--cor-borda);">
                        <small class="text-muted" style="display: block; margin-bottom: 4px;">Subtotal</small>
                        <span id="subtotal" style="font-size: 1.2rem; font-weight: 700; color: var(--cor-texto);">R$ 0,00</span>
                    </div>
                    <div class="discount-card">
                        <small class="text-muted">Desconto</small>
                        <select id="tipoDesconto" name="tipo_desconto" onchange="setTipoDesconto(this.value)" class="discount-hidden-select" aria-label="Tipo de desconto">
                            <option value="perc">%</option>
                            <option value="valor">R$</option>
                        </select>
                        <div class="discount-control" role="group" aria-label="Tipo e valor do desconto">
                            <div class="discount-mode">
                                <button type="button" class="discount-mode-btn is-active" data-discount-type="perc" onclick="setTipoDesconto('perc')" title="Desconto em porcentagem" aria-pressed="true">%</button>
                                <button type="button" class="discount-mode-btn" data-discount-type="valor" onclick="setTipoDesconto('valor')" title="Desconto em reais" aria-pressed="false">R$</button>
                            </div>
                            <label class="discount-input-wrap" for="descontoGlobalDisplay">
                                <span id="descontoPrefixo">%</span>
                                <input type="text" id="descontoGlobalDisplay"
                                       value="<?php echo number_format((float)($propostaEdicao['desconto_percentual'] ?? 0), 2, ',', '.'); ?>"
                                       oninput="mascararDesconto(this)" onfocus="this.select()" title="Valor do desconto"
                                       inputmode="decimal" autocomplete="off" aria-describedby="descontoErro descontoValor">
                                <input type="hidden" id="descontoGlobal" name="desconto_global"
                                       value="<?php echo number_format((float)($propostaEdicao['desconto_percentual'] ?? 0), 2, '.', ''); ?>">
                            </label>
                        </div>
                        <small id="descontoErro" class="discount-error" role="alert" hidden>O desconto percentual deve ser menor que 100%.</small>
                        <small id="descontoValor" class="discount-feedback">- R$ 0,00</small>
                    </div>
                    <div class="entry-card">
                        <small class="text-muted">Entrada</small>
                        <label class="discount-input-wrap" for="valorEntradaDisplay" style="margin: 0 auto;">
                            <span>R$</span>
                            <input type="text" id="valorEntradaDisplay"
                                   value="<?php echo number_format((float)($propostaEdicao['valor_entrada'] ?? 0), 2, ',', '.'); ?>"
                                   oninput="mascararMoeda(this, 'valorEntrada')" onfocus="this.select()"
                                   title="Valor de entrada" inputmode="numeric" autocomplete="off">
                            <input type="hidden" id="valorEntrada" name="valor_entrada"
                                   value="<?php echo number_format((float)($propostaEdicao['valor_entrada'] ?? 0), 2, '.', ''); ?>">
                        </label>
                        <small id="entradaResumo" class="discount-feedback">Sem entrada informada</small>
                    </div>
                    <div style="text-align: center; padding: 12px; background: rgba(46,204,113,0.08); border-radius: 8px; border: 1px solid var(--cor-destaque);">
                        <small style="display: block; margin-bottom: 4px; color: var(--cor-destaque); font-weight: 500;">TOTAL GERAL</small>
                        <span id="totalGeral" style="font-size: 1.5rem; font-weight: 700; color: var(--cor-destaque);">R$ 0,00</span>
                    </div>
                </div>
                <!-- Parcelas -->
                <div style="margin-top: 15px;">
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label for="parcelas">Número de Parcelas</label>
                        <select id="parcelas" name="parcelas" style="width: auto; min-width: 150px;" onchange="atualizarTotais()">
                            <?php $parcelasSelecionadas = (int)($propostaEdicao['parcelas'] ?? 3); ?>
                            <option value="1" <?php echo $parcelasSelecionadas === 1 ? 'selected' : ''; ?>>1x (à vista)</option>
                            <option value="2" <?php echo $parcelasSelecionadas === 2 ? 'selected' : ''; ?>>2x</option>
                            <option value="3" <?php echo $parcelasSelecionadas === 3 ? 'selected' : ''; ?>>3x</option>
                            <option value="4" <?php echo $parcelasSelecionadas === 4 ? 'selected' : ''; ?>>4x</option>
                            <option value="5" <?php echo $parcelasSelecionadas === 5 ? 'selected' : ''; ?>>5x</option>
                            <option value="6" <?php echo $parcelasSelecionadas === 6 ? 'selected' : ''; ?>>6x</option>
                            <option value="12" <?php echo $parcelasSelecionadas === 12 ? 'selected' : ''; ?>>12x</option>
                        </select>
                    </div>
                    <div id="parcelasInfo" style="padding: 12px 16px; background: var(--cor-fundo); border-radius: 8px; border: 1px solid var(--cor-borda); color: var(--cor-texto-secundario); font-size: 0.9rem;">
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 20px; display: flex; justify-content: space-between;">
                <button type="button" class="btn btn-secondary" onclick="irParaPasso(1)">
                    <i class="fas fa-arrow-left"></i> Voltar
                </button>
                <button type="button" class="btn btn-primary" onclick="irParaPasso(3)" id="btnPasso2" disabled
                        title="Selecione pelo menos um serviço para continuar">
                    Próximo <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            <small id="avisoServicosObrigatorios" style="display: block; margin-top: 8px; text-align: right; color: var(--cor-erro);">
                Selecione pelo menos um serviço para avançar.
            </small>
        </div>

        <!-- ===== PASSO 3: REVISÃO E CONFIRMAÇÃO ===== -->
        <div class="wizard-panel" id="passo3" style="display: none;">
            <div id="reviewLoading" style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--cor-destaque);"></i>
                <p style="margin-top: 10px; color: var(--cor-texto-secundario);">Montando revisão...</p>
            </div>
            <div id="reviewContent" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-check-double"></i> Revisão da Proposta</h3>
                    </div>
                    <div class="card-body">
                        <!-- Resumo Cliente -->
                        <div class="review-section" style="margin-bottom: 20px;">
                            <h4 style="color: var(--cor-destaque); margin-bottom: 10px;"><i class="fas fa-user-tie"></i> Proprietário</h4>
                            <div id="reviewCliente" style="padding: 12px 16px; background: var(--cor-fundo); border-radius: 8px; border: 1px solid var(--cor-borda);"></div>
                        </div>
                        <div class="review-section" style="margin-bottom: 20px;">
                            <h4 style="color: var(--cor-destaque); margin-bottom: 10px;"><i class="fas fa-user-check"></i> Responsável pelo fechamento</h4>
                            <div id="reviewResponsavelFechamento" style="padding: 12px 16px; background: var(--cor-fundo); border-radius: 8px; border: 1px solid var(--cor-borda);"></div>
                        </div>
                        <!-- Serviços por Embarcação -->
                        <div class="review-section" style="margin-bottom: 20px;">
                            <h4 style="color: var(--cor-destaque); margin-bottom: 10px;"><i class="fas fa-ship"></i> Serviços por Embarcação</h4>
                            <div id="reviewPorEmbarcacao" style="padding: 12px 16px; background: var(--cor-fundo); border-radius: 8px; border: 1px solid var(--cor-borda);"></div>
                        </div>
                        <!-- Totais -->
                        <div id="reviewTotal" style="padding: 16px 20px; background: rgba(46,204,113,0.08); border: 1px solid var(--cor-destaque); border-radius: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <span class="text-muted">Subtotal:</span>
                                <span id="rSubtotal" style="font-weight: 600; color: var(--cor-texto);">R$ 0,00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <span class="text-muted">Desconto (<span id="rDescontoPerc">0</span>%):</span>
                                <span id="rDesconto" style="font-weight: 600; color: var(--cor-erro);">- R$ 0,00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <span class="text-muted">Entrada:</span>
                                <span id="rEntrada" style="font-weight: 600; color: var(--cor-texto);">R$ 0,00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <span class="text-muted">Saldo restante:</span>
                                <span id="rSaldo" style="font-weight: 600; color: var(--cor-texto);">R$ 0,00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 8px; border-top: 1px solid var(--cor-borda);">
                                <span style="font-weight: 600; color: var(--cor-destaque);">TOTAL GERAL:</span>
                                <span id="rTotalGeral" style="font-size: 1.5rem; font-weight: 700; color: var(--cor-destaque);">R$ 0,00</span>
                            </div>
                            <div id="rParcelas" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--cor-borda); color: var(--cor-texto-secundario); font-size: 0.9rem;"></div>
                        </div>

                        <!-- Forma de Pagamento -->
                        <div style="margin-top: 20px;">
                            <div class="form-group">
                                <label for="forma_pagamento"><i class="fas fa-credit-card"></i> Forma de Pagamento</label>
                                <select id="forma_pagamento" name="forma_pagamento" style="width: auto; min-width: 200px;">
                                    <?php $formaPagamentoSelecionada = (string)($propostaEdicao['forma_pagamento'] ?? 'parcelado'); ?>
                                    <option value="parcelado" <?php echo $formaPagamentoSelecionada === 'parcelado' ? 'selected' : ''; ?>>Parcelado (cartão / boleto parcelado)</option>
                                    <option value="a_vista" <?php echo $formaPagamentoSelecionada === 'a_vista' ? 'selected' : ''; ?>>À Vista</option>
                                    <option value="boleto" <?php echo $formaPagamentoSelecionada === 'boleto' ? 'selected' : ''; ?>>Boleto Bancário</option>
                                    <option value="pix" <?php echo $formaPagamentoSelecionada === 'pix' ? 'selected' : ''; ?>>PIX</option>
                                </select>
                            </div>
                        </div>

                        <!-- Observações -->
                        <div style="margin-top: 20px;">
                            <div class="form-group">
                                <label for="observacoes"><i class="fas fa-sticky-note"></i> Observações</label>
                                <textarea id="observacoes" name="observacoes" rows="3" style="width: 100%;"
                                          placeholder="Condições especiais, validade da proposta, informações adicionais..."><?php echo h($propostaEdicao['observacoes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-actions" style="margin-top: 20px; display: flex; justify-content: space-between;">
                    <button type="button" class="btn btn-secondary" onclick="irParaPasso(2)">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>
                    <button type="submit" class="btn btn-success btn-lg" id="btnSalvarProposta">
                        <i class="fas fa-check-circle"></i> <?php echo $modoEdicao ? 'Salvar Alterações' : 'Gerar Proposta'; ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Template dos serviços (será clonado via JS para cada embarcação) -->
<template id="templateServicosPorEmbarcacao">
    <div class="card embarcacao-bloco" style="margin-bottom: 20px;">
        <div class="card-header" style="display: flex; align-items: center; gap: 10px; cursor: pointer;" onclick="toggleEmbarcacaoBloco(this)">
            <i class="fas fa-ship" style="color: var(--cor-destaque);"></i>
            <h3 class="emb-nome" style="flex: 1; color: var(--cor-texto); font-size: 1rem; margin: 0;"></h3>
            <span class="emb-total" style="font-weight: 700; color: var(--cor-destaque); font-size: 1rem; margin-right: 10px;"></span>
            <i class="fas fa-chevron-down" style="color: var(--cor-texto-secundario); transition: transform 0.3s;"></i>
        </div>
        <div class="card-body emb-body" style="display: block;">
            <table style="width: 100%; border-collapse: collapse;" class="servicos-tabela">
                <thead>
                    <tr style="border-bottom: 1px solid var(--cor-borda);">
                        <th style="text-align: left; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 40px;"></th>
                        <th style="text-align: left; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem;">Serviço</th>
                        <th style="text-align: center; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 70px;">Qtd</th>
                        <th style="text-align: right; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 110px;">Preço Unit.</th>
                        <th style="text-align: right; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 110px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="servicos-tbody"></tbody>
            </table>
        </div>
    </div>
</template>

<template id="templateServicoLinha">
    <tr style="border-bottom: 1px solid var(--cor-borda); transition: background 0.2s;" class="servico-linha">
        <td style="padding: 8px 12px; text-align: center;">
            <input type="checkbox" class="check-servico" onchange="servicoToggled(this)" style="width: 16px; height: 16px; cursor: pointer; accent-color: var(--cor-destaque);">
        </td>
        <td style="padding: 8px 12px;">
            <span class="servico-nome" style="font-weight: 500;"></span>
            <br><small class="servico-desc text-muted"></small>
        </td>
        <td style="padding: 8px 12px; text-align: center;">
            <input type="number" class="qtd-servico" value="1" min="1" max="99"
                   style="width: 55px; padding: 4px 6px; background: var(--cor-fundo); border: 1px solid var(--cor-borda); border-radius: 6px; color: var(--cor-texto); text-align: center; font-size: 0.85rem;"
                   onchange="servicoQtdChanged(this)" onfocus="this.select()">
        </td>
        <td style="padding: 8px 12px; text-align: right;">
            <span class="preco-unitario" style="font-weight: 500;"></span>
        </td>
        <td style="padding: 8px 12px; text-align: right;">
            <span class="subtotal-servico" style="font-weight: 600; color: var(--cor-destaque);"></span>
        </td>
    </tr>
</template>

<script>
// ============ DADOS GLOBAIS ============
const ALL_SERVICOS = <?php echo json_encode($servicos, JSON_UNESCAPED_UNICODE); ?>;
const MODO_EDICAO = <?php echo $modoEdicao ? 'true' : 'false'; ?>;
const SERVICOS_EDICAO_INICIAIS = <?php echo json_encode($servicosEdicaoIniciais, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
let clienteSelecionadoData = null;
let responsavelFechamentoNomeData = '';
let responsavelFechamentoTelefoneData = '';
let embarcacoesCarregadas = []; // { id, nome, registro }
let embarcacaoSelecionadaId = null;
let servicosSelecionadosPorEmbarcacao = {};
let clientePasso2CarregadoId = null;

// ============ NAVEGAÇÃO DO WIZARD ============
function temServicoSelecionado() {
    return Object.values(servicosSelecionadosPorEmbarcacao).some(servicos => Object.keys(servicos).length > 0);
}

function atualizarEstadoAvancoServicos() {
    const possuiServico = temServicoSelecionado();
    const financeiroValido = validarDescontoPercentual();
    const botao = document.getElementById('btnPasso2');
    const aviso = document.getElementById('avisoServicosObrigatorios');
    if (botao) {
        botao.disabled = !possuiServico || !financeiroValido;
        botao.title = !possuiServico
            ? 'Selecione pelo menos um serviço para continuar'
            : (!financeiroValido ? 'Corrija o desconto percentual para continuar' : '');
    }
    if (aviso) aviso.style.display = possuiServico ? 'none' : 'block';
}

function irParaPasso(numero) {
    if (numero === 3 && !temServicoSelecionado()) {
        atualizarEstadoAvancoServicos();
        return;
    }
    if (numero === 3 && !validarDescontoPercentual(true)) {
        atualizarEstadoAvancoServicos();
        document.getElementById('descontoGlobalDisplay')?.focus();
        return;
    }

    document.querySelectorAll('.wizard-panel').forEach(p => p.style.display = 'none');
    document.getElementById('passo' + numero).style.display = 'block';

    // Atualiza stepper
    document.querySelectorAll('.wizard-step').forEach(step => {
        const s = parseInt(step.dataset.step);
        step.classList.remove('active');
        step.style.opacity = (s <= numero) ? '1' : '0.5';
        const numEl = step.querySelector('.step-number');
        const lblEl = step.querySelector('.step-label');
        if (s <= numero) {
            numEl.style.background = 'var(--cor-destaque)';
            numEl.style.color = '#fff';
            lblEl.style.color = 'var(--cor-destaque)';
            lblEl.style.fontWeight = '600';
        } else {
            numEl.style.background = 'var(--cor-borda)';
            numEl.style.color = 'var(--cor-texto-secundario)';
            lblEl.style.color = 'var(--cor-texto-secundario)';
            lblEl.style.fontWeight = '500';
        }
        if (s === numero) {
            step.classList.add('active');
            step.style.borderBottomColor = 'var(--cor-destaque)';
        } else {
            step.style.borderBottomColor = 'transparent';
        }
    });

    // Ações específicas
    if (numero === 2) carregarPasso2();
    if (numero === 3) montarRevisao();

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ============ PASSO 1: CLIENTE ============
function filtrarClientes() {
    const termo = document.getElementById('buscaClienteWizard').value.toLowerCase();
    document.querySelectorAll('.cliente-card').forEach(card => {
        card.style.display = card.textContent.toLowerCase().includes(termo) ? 'flex' : 'none';
    });
}

function clienteSelecionado(radio) {
    document.querySelectorAll('.cliente-card').forEach(c => {
        c.classList.remove('is-selected');
        c.style.borderColor = 'var(--cor-borda)';
        c.style.background = 'var(--cor-fundo)';
    });
    const card = radio.closest('.cliente-card');
    card.classList.add('is-selected');
    card.style.borderColor = 'var(--cor-destaque)';
    card.style.background = 'rgba(46,204,113,0.08)';

    clienteSelecionadoData = {
        id: radio.value,
        nome: radio.dataset.nome,
        perfil: radio.dataset.perfil,
        cpfcnpj: radio.dataset.cpfcnpj
    };
    document.getElementById('dadosCliente').value = JSON.stringify(clienteSelecionadoData);
    atualizarPasso1();
    embarcacoesCarregadas = [];
    embarcacaoSelecionadaId = null;
    servicosSelecionadosPorEmbarcacao = {};
    clientePasso2CarregadoId = null;
    atualizarEstadoAvancoServicos();
}

function atualizarPasso1() {
    responsavelFechamentoNomeData = document.getElementById('responsavel_fechamento_nome')?.value?.trim() || '';
    responsavelFechamentoTelefoneData = document.getElementById('responsavel_fechamento_telefone')?.value?.trim() || '';
    document.getElementById('btnPasso1').disabled = !clienteSelecionadoData || !<?= $escritorioPropostaDisponivel ? 'true' : 'false' ?>;
}

function formatarTelefoneResponsavel(input) {
    const numeros = input.value.replace(/\D/g, '').slice(0, 11);
    if (!numeros) {
        input.value = '';
        return;
    }

    if (numeros.length <= 2) {
        input.value = `(${numeros}`;
        return;
    }

    const ddd = numeros.slice(0, 2);
    const telefone = numeros.slice(2);
    const tamanhoPrefixo = numeros.length === 11 ? 5 : 4;
    const prefixo = telefone.slice(0, tamanhoPrefixo);
    const sufixo = telefone.slice(tamanhoPrefixo);
    input.value = `(${ddd}) ${prefixo}${sufixo ? '-' + sufixo : ''}`;
}

document.addEventListener('DOMContentLoaded', () => {
    const clienteMarcado = document.querySelector('input[name="cliente_id"]:checked');
    if (clienteMarcado) clienteSelecionado(clienteMarcado);
    if (MODO_EDICAO) {
        servicosSelecionadosPorEmbarcacao = JSON.parse(JSON.stringify(SERVICOS_EDICAO_INICIAIS));
        atualizarEstadoAvancoServicos();
    }
    setTipoDesconto(document.getElementById('tipoDesconto')?.value || 'perc');
    formatarCampoMoedaPorValor('valorEntradaDisplay', parseFloat(document.getElementById('valorEntrada')?.value) || 0);
    atualizarPasso1();
});

// ============ PASSO 2: SERVIÇOS POR EMBARCAÇÃO ============
function carregarPasso2() {
    if (!clienteSelecionadoData) return;

    if (clientePasso2CarregadoId === clienteSelecionadoData.id && embarcacoesCarregadas.length > 0) {
        document.getElementById('passo2ClienteNome').textContent = clienteSelecionadoData.nome;
        construirGradeServicos(embarcacoesCarregadas);
        return;
    }

    document.getElementById('paso2Loading').style.display = 'block';
    document.getElementById('paso2Content').style.display = 'none';
    document.getElementById('paso2Vazio').style.display = 'none';
    document.getElementById('totaisPainel').style.display = 'none';
    document.getElementById('passo2ClienteNome').textContent = clienteSelecionadoData.nome;

    fetch('<?php echo APP_URL; ?>comercial/propostas/actions?action=embarcacoes_cliente&cliente_id=' + encodeURIComponent(clienteSelecionadoData.id))
        .then(r => r.json())
        .then(data => {
            document.getElementById('paso2Loading').style.display = 'none';

            if (!data.embarcacoes || data.embarcacoes.length === 0) {
                document.getElementById('paso2Vazio').style.display = 'block';
                embarcacoesCarregadas = [];
                return;
            }

            embarcacoesCarregadas = data.embarcacoes;
            const primeiraComServico = data.embarcacoes.find(emb => {
                return Object.keys(servicosSelecionadosPorEmbarcacao[emb.id] || {}).length > 0;
            });
            embarcacaoSelecionadaId = primeiraComServico?.id || null;
            clientePasso2CarregadoId = clienteSelecionadoData.id;
            construirGradeServicos(data.embarcacoes);
        })
        .catch(err => {
            document.getElementById('paso2Loading').style.display = 'none';
            document.getElementById('paso2Vazio').style.display = 'block';
            document.getElementById('paso2Vazio').querySelector('p').textContent = 'Erro ao carregar embarcações.';
            console.error(err);
        });
}

function construirGradeServicos(embarcacoes) {
    const container = document.getElementById('paso2Content');
    const tplBloco = document.getElementById('templateServicosPorEmbarcacao');
    const tplLinha = document.getElementById('templateServicoLinha');

    let html = '';
    embarcacoes.forEach((emb, idx) => {
        const bloco = tplBloco.content.cloneNode(true);
        bloco.querySelector('.emb-nome').textContent = emb.nome + (emb.registro ? ' (' + emb.registro + ')' : '');
        bloco.querySelector('.emb-total').id = 'embTotal_' + emb.id;

        const tbody = bloco.querySelector('.servicos-tbody');
        ALL_SERVICOS.forEach(s => {
            const linha = tplLinha.content.cloneNode(true);
            linha.querySelector('.check-servico').dataset.embId = emb.id;
            linha.querySelector('.check-servico').dataset.servId = s.id;
            linha.querySelector('.servico-nome').textContent = s.nome;
            linha.querySelector('.servico-desc').textContent = (s.descricao && s.descricao.length > 60) ? s.descricao.substring(0, 60) + '...' : (s.descricao || '');
            linha.querySelector('.qtd-servico').dataset.embId = emb.id;
            linha.querySelector('.qtd-servico').dataset.servId = s.id;
            linha.querySelector('.preco-unitario').textContent = formatarMoeda(parseFloat(s.preco_padrao));
            linha.querySelector('.subtotal-servico').id = 'sub_' + emb.id + '_' + s.id;
            linha.querySelector('.subtotal-servico').textContent = formatarMoeda(0);
            linha.querySelector('.subtotal-servico').dataset.preco = s.preco_padrao;
            tbody.appendChild(linha);
        });

        // Append bloco ao container
        const wrapper = document.createElement('div');
        wrapper.appendChild(bloco);
        container.appendChild(wrapper);
        container.appendChild(bloco); // precisa ser assim com templates
    });

    // Reconstruir usando innerHTML pois template clonado é complexo
    // Vamos usar abordagem direta com strings
    container.innerHTML = '';
    embarcacoes.forEach(emb => {
        let blocoHtml = `
        <div class="card embarcacao-bloco" style="margin-bottom: 20px;">
            <div class="card-header" style="display: flex; align-items: center; gap: 10px; cursor: pointer;" onclick="toggleEmbarcacaoBloco(this)">
                <i class="fas fa-ship" style="color: var(--cor-destaque);"></i>
                <h3 style="flex: 1; color: var(--cor-texto); font-size: 1rem; margin: 0;">${esc(emb.nome)} ${emb.registro ? '<small class="text-muted">(' + esc(emb.registro) + ')</small>' : ''}</h3>
                <span id="embTotal_${emb.id}" style="font-weight: 700; color: var(--cor-destaque); font-size: 1rem; margin-right: 10px;">${formatarMoeda(0)}</span>
                <i class="fas fa-chevron-down" style="color: var(--cor-texto-secundario); transition: transform 0.3s;"></i>
            </div>
            <div class="card-body emb-body">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--cor-borda);">
                            <th style="text-align: left; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 40px;"></th>
                            <th style="text-align: left; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem;">Serviço</th>
                            <th style="text-align: center; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 70px;">Qtd</th>
                            <th style="text-align: right; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 110px;">Preço Unit.</th>
                            <th style="text-align: right; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 110px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>`;

        ALL_SERVICOS.forEach(s => {
            blocoHtml += `
                        <tr class="servico-linha" style="border-bottom: 1px solid var(--cor-borda);">
                            <td style="padding: 8px 12px; text-align: center;">
                                <input type="checkbox" class="check-servico" data-emb-id="${emb.id}" data-serv-id="${s.id}"
                                       onchange="servicoToggled(this)" style="width: 16px; height: 16px; cursor: pointer; accent-color: var(--cor-destaque);">
                            </td>
                            <td style="padding: 8px 12px;">
                                <span style="font-weight: 500;">${esc(s.nome)}</span>
                                ${s.descricao ? '<br><small class="text-muted">' + esc(s.descricao.length > 60 ? s.descricao.substring(0, 60) + '...' : s.descricao) + '</small>' : ''}
                            </td>
                            <td style="padding: 8px 12px; text-align: center;">
                                <input type="number" value="1" min="1" max="99" data-emb-id="${emb.id}" data-serv-id="${s.id}"
                                       class="qtd-servico" onchange="servicoQtdChanged(this)" onfocus="this.select()"
                                       style="width: 55px; padding: 4px 6px; background: var(--cor-fundo); border: 1px solid var(--cor-borda); border-radius: 6px; color: var(--cor-texto); text-align: center; font-size: 0.85rem;" disabled>
                            </td>
                            <td style="padding: 8px 12px; text-align: right;">
                                <span style="font-weight: 500;">${formatarMoeda(parseFloat(s.preco_padrao))}</span>
                            </td>
                            <td style="padding: 8px 12px; text-align: right;">
                                <span id="sub_${emb.id}_${s.id}" data-preco="${s.preco_padrao}" style="font-weight: 600; color: var(--cor-destaque);">${formatarMoeda(0)}</span>
                            </td>
                        </tr>`;
        });

        blocoHtml += `</tbody></table></div></div>`;
        container.innerHTML += blocoHtml;
    });

    document.getElementById('paso2Content').style.display = 'block';
    document.getElementById('totaisPainel').style.display = 'block';
    atualizarTotais();
}

function toggleEmbarcacaoBloco(headerEl) {
    const body = headerEl.nextElementSibling;
    const chevron = headerEl.querySelector('.fa-chevron-down');
    if (body.style.display === 'none') {
        body.style.display = 'block';
        chevron.style.transform = 'rotate(0deg)';
    } else {
        body.style.display = 'none';
        chevron.style.transform = 'rotate(-90deg)';
    }
}

// ============ INTERAÇÕES NOS SERVIÇOS ============
function servicoToggled(checkbox) {
    const embId = checkbox.dataset.embId;
    const servId = checkbox.dataset.servId;
    const linha = checkbox.closest('tr');
    const qtdInput = linha.querySelector('.qtd-servico');

    if (checkbox.checked) {
        linha.style.background = 'rgba(46,204,113,0.05)';
        qtdInput.disabled = false;
        qtdInput.value = 1;
    } else {
        linha.style.background = '';
        qtdInput.disabled = true;
        qtdInput.value = 0;
    }

    atualizarSubtotalServico(embId, servId);
    atualizarTotais();
}

function servicoQtdChanged(input) {
    const embId = input.dataset.embId;
    const servId = input.dataset.servId;
    atualizarSubtotalServico(embId, servId);
    atualizarTotais();
}

function atualizarSubtotalServico(embId, servId) {
    const linha = document.querySelector(`.check-servico[data-emb-id="${embId}"][data-serv-id="${servId}"]`).closest('tr');
    const checkbox = linha.querySelector('.check-servico');
    const qtdInput = linha.querySelector('.qtd-servico');
    const subEl = document.getElementById('sub_' + embId + '_' + servId);

    if (!checkbox.checked) {
        subEl.textContent = formatarMoeda(0);
        return;
    }

    const preco = parseFloat(subEl.dataset.preco) || 0;
    const qtd = Math.max(1, parseInt(qtdInput.value) || 1);
    qtdInput.value = qtd;
    const subtotal = preco * qtd;
    subEl.textContent = formatarMoeda(subtotal);
}

// ============ TOTAIS ============
function formatarNumeroPtBr(valor) {
    return Number(valor || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatarCampoMoedaPorValor(inputId, valor) {
    const input = document.getElementById(inputId);
    if (input) input.value = formatarNumeroPtBr(valor);
}

function mascararMoeda(input, hiddenId) {
    const digitos = input.value.replace(/\D/g, '').slice(0, 14);
    const valor = digitos ? Number(digitos) / 100 : 0;
    input.value = formatarNumeroPtBr(valor);
    const hidden = document.getElementById(hiddenId);
    if (hidden) hidden.value = valor.toFixed(2);
    atualizarTotais();
}

function normalizarPercentualVisivel(valor) {
    let texto = String(valor ?? '').replace(/\./g, ',').replace(/[^\d,]/g, '');
    const partes = texto.split(',');
    const inteiro = (partes.shift() || '0').replace(/^0+(?=\d)/, '').slice(0, 3) || '0';
    const decimal = partes.join('').slice(0, 2);
    return decimal.length ? inteiro + ',' + decimal : inteiro;
}

function mascararDesconto(input) {
    const tipo = document.getElementById('tipoDesconto')?.value || 'perc';
    if (tipo === 'valor') {
        mascararMoeda(input, 'descontoGlobal');
        return;
    }

    input.value = normalizarPercentualVisivel(input.value);
    const valor = parseFloat(input.value.replace(',', '.')) || 0;
    document.getElementById('descontoGlobal').value = valor.toFixed(2);
    atualizarTotais();
}

function validarDescontoPercentual(anunciar = false) {
    const tipo = document.getElementById('tipoDesconto')?.value || 'perc';
    const valor = parseFloat(document.getElementById('descontoGlobal')?.value) || 0;
    const invalido = tipo === 'perc' && valor >= 100;
    const visivel = document.getElementById('descontoGlobalDisplay');
    const wrapper = visivel?.closest('.discount-input-wrap');
    const erro = document.getElementById('descontoErro');

    visivel?.setAttribute('aria-invalid', invalido ? 'true' : 'false');
    wrapper?.classList.toggle('is-invalid', invalido);
    if (erro) erro.hidden = !invalido;

    if (anunciar && invalido && erro) {
        erro.textContent = 'O desconto percentual deve ser menor que 100%. O maior valor permitido é 99,99%.';
    }
    return !invalido;
}

function setTipoDesconto(tipo) {
    const tipoSeguro = tipo === 'valor' ? 'valor' : 'perc';
    const select = document.getElementById('tipoDesconto');
    const prefixo = document.getElementById('descontoPrefixo');
    const input = document.getElementById('descontoGlobalDisplay');
    const hidden = document.getElementById('descontoGlobal');

    if (select) select.value = tipoSeguro;
    if (prefixo) prefixo.textContent = tipoSeguro === 'valor' ? 'R$' : '%';
    if (input) {
        input.placeholder = '0,00';
        input.inputMode = tipoSeguro === 'valor' ? 'numeric' : 'decimal';
        input.value = formatarNumeroPtBr(parseFloat(hidden?.value) || 0);
    }

    document.querySelectorAll('.discount-mode-btn').forEach(btn => {
        const ativo = btn.dataset.discountType === tipoSeguro;
        btn.classList.toggle('is-active', ativo);
        btn.setAttribute('aria-pressed', ativo ? 'true' : 'false');
    });

    atualizarTotais();
}

function obterValorEntrada(totalGeral) {
    const entradaInput = document.getElementById('valorEntrada');
    if (!entradaInput) return 0;

    let valorEntrada = parseFloat(entradaInput.value) || 0;
    if (valorEntrada < 0) valorEntrada = 0;
    if (valorEntrada > totalGeral) {
        valorEntrada = totalGeral;
        entradaInput.value = totalGeral.toFixed(2);
        formatarCampoMoedaPorValor('valorEntradaDisplay', totalGeral);
    }

    return valorEntrada;
}

function atualizarTotais() {
    let subtotalGeral = 0;

    // Calcula subtotal por embarcação e geral
    document.querySelectorAll('.embarcacao-bloco').forEach(bloco => {
        let embTotal = 0;
        const checks = bloco.querySelectorAll('.check-servico');
        checks.forEach(cb => {
            if (cb.checked) {
                const embId = cb.dataset.embId;
                const servId = cb.dataset.servId;
                const subEl = document.getElementById('sub_' + embId + '_' + servId);
                const preco = parseFloat(subEl.dataset.preco) || 0;
                const qtdInput = bloco.querySelector(`.qtd-servico[data-emb-id="${embId}"][data-serv-id="${servId}"]`);
                const qtd = Math.max(1, parseInt(qtdInput?.value) || 1);
                embTotal += preco * qtd;
            }
        });
        subtotalGeral += embTotal;
        // Atualiza o total da embarcação no header
        const embTotalEl = bloco.querySelector('[id^="embTotal_"]');
        if (embTotalEl) embTotalEl.textContent = formatarMoeda(embTotal);
    });

    // Desconto
    const tipoDesconto = document.getElementById('tipoDesconto').value;
    const descInput = document.getElementById('descontoGlobal');
    let descontoValor = 0;
    let descontoPerc = 0;

    if (tipoDesconto === 'perc') {
        descontoPerc = parseFloat(descInput.value) || 0;
        descontoValor = descontoPerc < 100 ? subtotalGeral * (descontoPerc / 100) : 0;
    } else {
        descontoValor = parseFloat(descInput.value) || 0;
        if (descontoValor > subtotalGeral && subtotalGeral > 0) {
            descontoValor = subtotalGeral;
            descInput.value = subtotalGeral.toFixed(2);
            formatarCampoMoedaPorValor('descontoGlobalDisplay', subtotalGeral);
        }
        descontoPerc = subtotalGeral > 0 ? (descontoValor / subtotalGeral) * 100 : 0;
    }

    const totalGeral = Math.max(0, subtotalGeral - descontoValor);
    const valorEntrada = obterValorEntrada(totalGeral);
    const saldoRestante = Math.max(0, totalGeral - valorEntrada);

    // Atualiza display
    document.getElementById('subtotal').textContent = formatarMoeda(subtotalGeral);

    document.getElementById('descontoValor').textContent = tipoDesconto === 'perc'
        ? descontoPerc.toFixed(2).replace('.', ',') + '% = - ' + formatarMoeda(descontoValor)
        : '- ' + formatarMoeda(descontoValor) + ' (' + descontoPerc.toFixed(2).replace('.', ',') + '%)';

    document.getElementById('totalGeral').textContent = formatarMoeda(totalGeral);

    // Parcelas
    const parcelas = parseInt(document.getElementById('parcelas').value) || 1;
    const valorParcela = parcelas > 0 ? saldoRestante / parcelas : saldoRestante;
    const entradaResumo = document.getElementById('entradaResumo');
    if (entradaResumo) {
        entradaResumo.textContent = valorEntrada > 0
            ? 'Entrada de ' + formatarMoeda(valorEntrada) + ' e saldo restante de ' + formatarMoeda(saldoRestante)
            : 'Sem entrada informada';
    }

    let ph = '';
    if (valorEntrada > 0) {
        ph += `<div style="padding: 3px 0;">Entrada imediata: <strong>${formatarMoeda(valorEntrada)}</strong></div>`;
    }
    for (let i = 1; i <= parcelas; i++) {
        ph += `<div style="padding: 3px 0;">Parcela ${i}/<strong>${parcelas}: ${formatarMoeda(valorParcela)}</strong></div>`;
    }
    document.getElementById('parcelasInfo').innerHTML = ph;
    atualizarEstadoAvancoServicos();
}

// ============ PASSO 3: REVISÃO ============
function montarRevisao() {
    // Coleta todos os dados dos serviços selecionados
    const dadosServicos = [];
    let subtotalGeral = 0;

    document.querySelectorAll('.embarcacao-bloco').forEach(bloco => {
        const embHeader = bloco.querySelector('.card-header h3');
        const embNome = embHeader ? embHeader.textContent.replace(/\s*\(.*/, '').trim() : '';
        const embId = '';
        let embTotal = 0;
        const servicosDaEmb = [];

        const checks = bloco.querySelectorAll('.check-servico:checked');
        checks.forEach(cb => {
            const embId = cb.dataset.embId;
            const servId = cb.dataset.servId;
            const linha = cb.closest('tr');
            const nomeServ = linha.querySelector('td:nth-child(2) span').textContent.trim();
            const preco = parseFloat(document.getElementById('sub_' + embId + '_' + servId).dataset.preco) || 0;
            const qtd = Math.max(1, parseInt(linha.querySelector('.qtd-servico').value) || 1);
            const subtotal = preco * qtd;
            embTotal += subtotal;
            servicosDaEmb.push({ servico_id: servId, nome: nomeServ, preco, qtd, subtotal, quantidade: qtd });
        });

        if (checks.length > 0) {
            // Pegar nome real e registro da embarcação
            const embData = embarcacoesCarregadas.find(e => e.id === checks[0].dataset.embId);
            dadosServicos.push({
                embarcacao_id: checks[0].dataset.embId,
                embarcacao_nome: embData ? embData.nome : embNome,
                embarcacao_registro: embData ? (embData.registro || 'N/I') : '',
                total: embTotal,
                servicos: servicosDaEmb
            });
            subtotalGeral += embTotal;
        }
    });

    // Salva JSON para envio
    document.getElementById('dadosServicosJson').value = JSON.stringify(dadosServicos);

    // Desconto e total
    const tipoDesconto = document.getElementById('tipoDesconto').value;
    const descInput = parseFloat(document.getElementById('descontoGlobal').value) || 0;
    let descontoValor = 0;
    let descontoPerc = 0;

    if (tipoDesconto === 'perc') {
        descontoPerc = descInput;
        descontoValor = subtotalGeral * (descontoPerc / 100);
    } else {
        descontoValor = Math.min(subtotalGeral, descInput);
        descontoPerc = subtotalGeral > 0 ? (descontoValor / subtotalGeral) * 100 : 0;
    }

    const totalGeral = Math.max(0, subtotalGeral - descontoValor);
    const valorEntrada = obterValorEntrada(totalGeral);
    const saldoRestante = Math.max(0, totalGeral - valorEntrada);
    const parcelas = parseInt(document.getElementById('parcelas').value) || 1;

    // Monta HTML da revisão
    document.getElementById('reviewCliente').innerHTML = `
        <strong>${clienteSelecionadoData?.nome || ''}</strong><br>
        <small class="text-muted">Perfil: ${clienteSelecionadoData?.perfil || ''} &middot; CPF/CNPJ: ${clienteSelecionadoData?.cpfcnpj || ''}</small>`;
    document.getElementById('reviewResponsavelFechamento').innerHTML = `
        <strong>${esc(responsavelFechamentoNomeData) || 'Não informado'}</strong><br>
        <small class="text-muted">Telefone: ${esc(responsavelFechamentoTelefoneData) || 'Não informado'}</small>`;

    let revEmbHtml = '';
    dadosServicos.forEach(ds => {
        revEmbHtml += `
        <div style="margin-bottom: 15px; padding: 12px; background: var(--cor-sidebar); border-radius: 8px; border: 1px solid var(--cor-borda);">
            <h5 style="color: var(--cor-destaque); margin-bottom: 8px;">
                <i class="fas fa-ship"></i> ${esc(ds.embarcacao_nome)}
                ${ds.embarcacao_registro !== 'N/I' ? '<small class="text-muted">(' + esc(ds.embarcacao_registro) + ')</small>' : ''}
            </h5>
            <table style="width: 100%; border-collapse: collapse;">
                <thead><tr style="border-bottom: 1px solid var(--cor-borda);">
                    <th style="text-align: left; padding: 6px; color: var(--cor-texto-secundario); font-size: 0.75rem;">Serviço</th>
                    <th style="text-align: center; padding: 6px; color: var(--cor-texto-secundario); font-size: 0.75rem; width: 50px;">Qtd</th>
                    <th style="text-align: right; padding: 6px; color: var(--cor-texto-secundario); font-size: 0.75rem; width: 90px;">Unit.</th>
                    <th style="text-align: right; padding: 6px; color: var(--cor-texto-secundario); font-size: 0.75rem; width: 90px;">Subtotal</th>
                </tr></thead><tbody>`;
        ds.servicos.forEach(sv => {
            revEmbHtml += `<tr style="border-bottom: 1px solid var(--cor-borda);">
                <td style="padding: 6px;">${esc(sv.nome)}</td>
                <td style="text-align: center; padding: 6px;">${sv.qtd}</td>
                <td style="text-align: right; padding: 6px;">${formatarMoeda(sv.preco)}</td>
                <td style="text-align: right; padding: 6px; font-weight: 600;">${formatarMoeda(sv.subtotal)}</td>
            </tr>`;
        });
        revEmbHtml += `<tr><td colspan="3" style="text-align: right; padding: 6px; font-weight: 600;">Total da Embarcação:</td>
            <td style="text-align: right; padding: 6px; font-weight: 700; color: var(--cor-destaque);">${formatarMoeda(ds.total)}</td></tr>`;
        revEmbHtml += '</tbody></table></div>';
    });
    document.getElementById('reviewPorEmbarcacao').innerHTML = revEmbHtml || '<p class="text-muted">Nenhum serviço selecionado.</p>';

    // Totais
    document.getElementById('rSubtotal').textContent = formatarMoeda(subtotalGeral);
    document.getElementById('rDescontoPerc').textContent = descontoPerc.toFixed(2).replace('.', ',');
    document.getElementById('rDesconto').textContent = '- ' + formatarMoeda(descontoValor);
    document.getElementById('rEntrada').textContent = formatarMoeda(valorEntrada);
    document.getElementById('rSaldo').textContent = formatarMoeda(saldoRestante);
    document.getElementById('rTotalGeral').textContent = formatarMoeda(totalGeral);

    const valorParcela = parcelas > 0 ? saldoRestante / parcelas : saldoRestante;
    let rph = valorEntrada > 0 ? `Entrada de <strong>${formatarMoeda(valorEntrada)}</strong>` : '';
    for (let i = 1; i <= parcelas; i++) {
        if (rph) rph += ' &middot; ';
        rph += `${i}x de <strong>${formatarMoeda(valorParcela)}</strong>`;
    }
    document.getElementById('rParcelas').innerHTML = rph;

    // Mostra conteúdo, esconde loading
    document.getElementById('reviewLoading').style.display = 'none';
    document.getElementById('reviewContent').style.display = 'block';
}

// ============ UTILITÁRIOS ============
// ============ PASSO 2: SELECAO POR EMBARCACAO ============
function construirGradeServicos(embarcacoes) {
    const container = document.getElementById('paso2Content');
    container.innerHTML = renderizarSeletorEmbarcacoes(embarcacoes) + '<div id="servicosEmbarcacaoAtual"></div>';
    document.getElementById('paso2Content').style.display = 'block';
    document.getElementById('totaisPainel').style.display = 'block';
    renderizarServicosEmbarcacaoAtual();
    atualizarTotais();
}

function renderizarSeletorEmbarcacoes(embarcacoes) {
    let html = '<div class="card" style="margin-bottom: 18px;"><div class="card-header"><h3><i class="fas fa-ship"></i> Escolha a embarcação</h3></div><div class="card-body"><div class="embarcacao-selector-grid">';
    embarcacoes.forEach(emb => {
        const resumo = obterResumoEmbarcacao(emb.id);
        const selecionada = embarcacaoSelecionadaId === emb.id;
        html += `
            <button type="button" class="embarcacao-select-card ${selecionada ? 'is-selected' : ''}" onclick="selecionarEmbarcacaoServicos('${escAttr(emb.id)}')">
                <span class="embarcacao-select-icon"><i class="fas fa-ship"></i></span>
                <span class="embarcacao-select-main">
                    <strong>${esc(emb.nome)}</strong>
                    <small>${emb.registro ? esc(emb.registro) : 'Sem registro informado'}</small>
                </span>
                <span class="embarcacao-select-summary">
                    <b id="embTotal_${escAttr(emb.id)}">${formatarMoeda(resumo.total)}</b>
                    <small>${resumo.qtd} serviço(s)</small>
                </span>
            </button>`;
    });
    html += '</div></div></div>';
    return html;
}

function selecionarEmbarcacaoServicos(embId) {
    embarcacaoSelecionadaId = embId;
    construirGradeServicos(embarcacoesCarregadas);
}

function renderizarServicosEmbarcacaoAtual() {
    const area = document.getElementById('servicosEmbarcacaoAtual');
    if (!area) return;

    if (!embarcacaoSelecionadaId) {
        area.innerHTML = `
            <div class="tabela-vazia" style="margin-bottom: 20px;">
                <i class="fas fa-mouse-pointer"></i>
                <h3>Selecione uma embarcação</h3>
                <p>Depois de escolher a embarcação, a lista de serviços aparece aqui. Você pode voltar e escolher outra embarcação depois.</p>
            </div>`;
        return;
    }

    const emb = embarcacoesCarregadas.find(e => e.id === embarcacaoSelecionadaId);
    if (!emb) {
        area.innerHTML = '';
        return;
    }

    let html = `
        <div class="card embarcacao-bloco" data-emb-id="${escAttr(emb.id)}" style="margin-bottom: 20px;">
            <div class="card-header" style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-list-check" style="color: var(--cor-destaque);"></i>
                <h3 style="flex: 1; color: var(--cor-texto); font-size: 1rem; margin: 0;">Serviços para ${esc(emb.nome)} ${emb.registro ? '<small class="text-muted">(' + esc(emb.registro) + ')</small>' : ''}</h3>
            </div>
            <div class="card-body emb-body">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--cor-borda);">
                            <th style="text-align: left; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 40px;"></th>
                            <th style="text-align: left; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem;">Serviço</th>
                            <th style="text-align: center; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 70px;">Qtd</th>
                            <th style="text-align: right; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 110px;">Preço Unit.</th>
                            <th style="text-align: right; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 110px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>`;

    ALL_SERVICOS.forEach(s => {
        const estado = servicosSelecionadosPorEmbarcacao[emb.id]?.[s.id] || null;
        const checked = !!estado;
        const qtd = estado?.qtd || 1;
        const preco = checked && Number.isFinite(Number(estado?.preco))
            ? Number(estado.preco)
            : (parseFloat(s.preco_padrao) || 0);
        const subtotal = checked ? preco * qtd : 0;
        html += `
            <tr class="servico-linha" style="border-bottom: 1px solid var(--cor-borda); ${checked ? 'background: rgba(46,204,113,0.05);' : ''}">
                <td style="padding: 8px 12px; text-align: center;">
                    <input type="checkbox" class="check-servico" data-emb-id="${escAttr(emb.id)}" data-serv-id="${escAttr(s.id)}"
                           onchange="servicoToggled(this)" style="width: 16px; height: 16px; cursor: pointer; accent-color: var(--cor-destaque);" ${checked ? 'checked' : ''}>
                </td>
                <td style="padding: 8px 12px;">
                    <span style="font-weight: 500;">${esc(s.nome)}</span>
                    ${s.descricao ? '<br><small class="text-muted">' + esc(s.descricao.length > 60 ? s.descricao.substring(0, 60) + '...' : s.descricao) + '</small>' : ''}
                </td>
                <td style="padding: 8px 12px; text-align: center;">
                    <input type="number" value="${qtd}" min="1" max="99" data-emb-id="${escAttr(emb.id)}" data-serv-id="${escAttr(s.id)}"
                           class="qtd-servico" onchange="servicoQtdChanged(this)" onfocus="this.select()"
                           style="width: 55px; padding: 4px 6px; background: var(--cor-fundo); border: 1px solid var(--cor-borda); border-radius: 6px; color: var(--cor-texto); text-align: center; font-size: 0.85rem;" ${checked ? '' : 'disabled'}>
                </td>
                <td style="padding: 8px 12px; text-align: right;">
                    <span style="font-weight: 500;">${formatarMoeda(preco)}</span>
                </td>
                <td style="padding: 8px 12px; text-align: right;">
                    <span id="sub_${escAttr(emb.id)}_${escAttr(s.id)}" data-preco="${preco}" style="font-weight: 600; color: var(--cor-destaque);">${formatarMoeda(subtotal)}</span>
                </td>
            </tr>`;
    });

    html += '</tbody></table></div></div>';
    area.innerHTML = html;
}

function servicoToggled(checkbox) {
    const embId = checkbox.dataset.embId;
    const servId = checkbox.dataset.servId;
    const linha = checkbox.closest('tr');
    const qtdInput = linha.querySelector('.qtd-servico');

    if (checkbox.checked) {
        linha.style.background = 'rgba(46,204,113,0.05)';
        qtdInput.disabled = false;
        qtdInput.value = 1;
        salvarServicoSelecionado(embId, servId, qtdInput.value);
    } else {
        linha.style.background = '';
        qtdInput.disabled = true;
        qtdInput.value = 0;
        removerServicoSelecionado(embId, servId);
    }

    atualizarSubtotalServico(embId, servId);
    atualizarTotais();
}

function servicoQtdChanged(input) {
    const embId = input.dataset.embId;
    const servId = input.dataset.servId;
    const linha = input.closest('tr');
    const checkbox = linha.querySelector('.check-servico');
    if (checkbox.checked) {
        salvarServicoSelecionado(embId, servId, input.value);
    }
    atualizarSubtotalServico(embId, servId);
    atualizarTotais();
}

function salvarServicoSelecionado(embId, servId, qtdValor) {
    if (!servicosSelecionadosPorEmbarcacao[embId]) {
        servicosSelecionadosPorEmbarcacao[embId] = {};
    }
    const estadoAtual = servicosSelecionadosPorEmbarcacao[embId][servId] || {};
    const servicoCatalogo = ALL_SERVICOS.find(s => String(s.id) === String(servId));
    servicosSelecionadosPorEmbarcacao[embId][servId] = {
        qtd: Math.max(1, parseInt(qtdValor) || 1),
        preco: Number.isFinite(Number(estadoAtual.preco))
            ? Number(estadoAtual.preco)
            : (parseFloat(servicoCatalogo?.preco_padrao) || 0)
    };
    atualizarEstadoAvancoServicos();
}

function removerServicoSelecionado(embId, servId) {
    if (!servicosSelecionadosPorEmbarcacao[embId]) return;
    delete servicosSelecionadosPorEmbarcacao[embId][servId];
    if (Object.keys(servicosSelecionadosPorEmbarcacao[embId]).length === 0) {
        delete servicosSelecionadosPorEmbarcacao[embId];
    }
    atualizarEstadoAvancoServicos();
}

function obterResumoEmbarcacao(embId) {
    const selecionados = servicosSelecionadosPorEmbarcacao[embId] || {};
    let total = 0;
    let qtd = 0;

    Object.entries(selecionados).forEach(([servId, estado]) => {
        const servico = ALL_SERVICOS.find(s => String(s.id) === String(servId));
        if (!servico) return;
        const quantidade = Math.max(1, parseInt(estado.qtd) || 1);
        const preco = Number.isFinite(Number(estado.preco))
            ? Number(estado.preco)
            : (parseFloat(servico.preco_padrao) || 0);
        total += preco * quantidade;
        qtd++;
    });

    return { total, qtd };
}

function atualizarTotais() {
    let subtotalGeral = 0;
    embarcacoesCarregadas.forEach(emb => {
        const resumo = obterResumoEmbarcacao(emb.id);
        subtotalGeral += resumo.total;
        const embTotalEl = document.getElementById('embTotal_' + emb.id);
        if (embTotalEl) {
            embTotalEl.textContent = formatarMoeda(resumo.total);
            const summarySmall = embTotalEl.closest('.embarcacao-select-summary')?.querySelector('small');
            if (summarySmall) summarySmall.textContent = resumo.qtd + ' serviço(s)';
        }
    });

    const tipoDesconto = document.getElementById('tipoDesconto').value;
    const descInput = document.getElementById('descontoGlobal');
    let descontoValor = 0;
    let descontoPerc = 0;

    if (tipoDesconto === 'perc') {
        descontoPerc = parseFloat(descInput.value) || 0;
        descontoValor = descontoPerc < 100 ? subtotalGeral * (descontoPerc / 100) : 0;
    } else {
        descontoValor = parseFloat(descInput.value) || 0;
        if (descontoValor > subtotalGeral && subtotalGeral > 0) {
            descontoValor = subtotalGeral;
            descInput.value = subtotalGeral.toFixed(2);
            formatarCampoMoedaPorValor('descontoGlobalDisplay', subtotalGeral);
        }
        descontoPerc = subtotalGeral > 0 ? (descontoValor / subtotalGeral) * 100 : 0;
    }

    const totalGeral = Math.max(0, subtotalGeral - descontoValor);
    const valorEntrada = obterValorEntrada(totalGeral);
    const saldoRestante = Math.max(0, totalGeral - valorEntrada);
    document.getElementById('subtotal').textContent = formatarMoeda(subtotalGeral);
    document.getElementById('descontoValor').textContent = tipoDesconto === 'perc'
        ? descontoPerc.toFixed(2).replace('.', ',') + '% = - ' + formatarMoeda(descontoValor)
        : '- ' + formatarMoeda(descontoValor) + ' (' + descontoPerc.toFixed(2).replace('.', ',') + '%)';
    document.getElementById('totalGeral').textContent = formatarMoeda(totalGeral);

    const parcelas = parseInt(document.getElementById('parcelas').value) || 1;
    const valorParcela = parcelas > 0 ? saldoRestante / parcelas : saldoRestante;
    const entradaResumo = document.getElementById('entradaResumo');
    if (entradaResumo) {
        entradaResumo.textContent = valorEntrada > 0
            ? 'Entrada de ' + formatarMoeda(valorEntrada) + ' e saldo restante de ' + formatarMoeda(saldoRestante)
            : 'Sem entrada informada';
    }

    let ph = '';
    if (valorEntrada > 0) {
        ph += `<div style="padding: 3px 0;">Entrada imediata: <strong>${formatarMoeda(valorEntrada)}</strong></div>`;
    }
    for (let i = 1; i <= parcelas; i++) {
        ph += `<div style="padding: 3px 0;">Parcela ${i}/<strong>${parcelas}: ${formatarMoeda(valorParcela)}</strong></div>`;
    }
    document.getElementById('parcelasInfo').innerHTML = ph;
    atualizarEstadoAvancoServicos();
}

function montarRevisao() {
    const dadosServicos = [];
    let subtotalGeral = 0;

    embarcacoesCarregadas.forEach(embData => {
        const selecionados = servicosSelecionadosPorEmbarcacao[embData.id] || {};
        const servicosDaEmb = [];
        let embTotal = 0;

        Object.entries(selecionados).forEach(([servId, estado]) => {
            const servico = ALL_SERVICOS.find(s => String(s.id) === String(servId));
            if (!servico) return;
            const preco = Number.isFinite(Number(estado.preco))
                ? Number(estado.preco)
                : (parseFloat(servico.preco_padrao) || 0);
            const qtd = Math.max(1, parseInt(estado.qtd) || 1);
            const subtotal = preco * qtd;
            embTotal += subtotal;
            servicosDaEmb.push({ servico_id: servId, nome: servico.nome, preco, qtd, subtotal, quantidade: qtd });
        });

        if (servicosDaEmb.length > 0) {
            dadosServicos.push({
                embarcacao_id: embData.id,
                embarcacao_nome: embData.nome,
                embarcacao_registro: embData.registro || 'N/I',
                total: embTotal,
                servicos: servicosDaEmb
            });
            subtotalGeral += embTotal;
        }
    });

    document.getElementById('dadosServicosJson').value = JSON.stringify(dadosServicos);

    const tipoDesconto = document.getElementById('tipoDesconto').value;
    const descInput = parseFloat(document.getElementById('descontoGlobal').value) || 0;
    let descontoValor = 0;
    let descontoPerc = 0;

    if (tipoDesconto === 'perc') {
        descontoPerc = descInput;
        descontoValor = subtotalGeral * (descontoPerc / 100);
    } else {
        descontoValor = Math.min(subtotalGeral, descInput);
        descontoPerc = subtotalGeral > 0 ? (descontoValor / subtotalGeral) * 100 : 0;
    }

    const totalGeral = Math.max(0, subtotalGeral - descontoValor);
    const valorEntrada = obterValorEntrada(totalGeral);
    const saldoRestante = Math.max(0, totalGeral - valorEntrada);
    const parcelas = parseInt(document.getElementById('parcelas').value) || 1;

    document.getElementById('reviewCliente').innerHTML = `
        <strong>${clienteSelecionadoData?.nome || ''}</strong><br>
        <small class="text-muted">Perfil: ${clienteSelecionadoData?.perfil || ''} &middot; CPF/CNPJ: ${clienteSelecionadoData?.cpfcnpj || ''}</small>`;
    document.getElementById('reviewResponsavelFechamento').innerHTML = `
        <strong>${esc(responsavelFechamentoNomeData) || 'Não informado'}</strong><br>
        <small class="text-muted">Telefone: ${esc(responsavelFechamentoTelefoneData) || 'Não informado'}</small>`;

    let revEmbHtml = '';
    dadosServicos.forEach(ds => {
        revEmbHtml += `
        <div style="margin-bottom: 15px; padding: 12px; background: var(--cor-sidebar); border-radius: 8px; border: 1px solid var(--cor-borda);">
            <h5 style="color: var(--cor-destaque); margin-bottom: 8px;">
                <i class="fas fa-ship"></i> ${esc(ds.embarcacao_nome)}
                ${ds.embarcacao_registro !== 'N/I' ? '<small class="text-muted">(' + esc(ds.embarcacao_registro) + ')</small>' : ''}
            </h5>
            <table style="width: 100%; border-collapse: collapse;">
                <thead><tr style="border-bottom: 1px solid var(--cor-borda);">
                    <th style="text-align: left; padding: 6px; color: var(--cor-texto-secundario); font-size: 0.75rem;">Serviço</th>
                    <th style="text-align: center; padding: 6px; color: var(--cor-texto-secundario); font-size: 0.75rem; width: 50px;">Qtd</th>
                    <th style="text-align: right; padding: 6px; color: var(--cor-texto-secundario); font-size: 0.75rem; width: 90px;">Unit.</th>
                    <th style="text-align: right; padding: 6px; color: var(--cor-texto-secundario); font-size: 0.75rem; width: 90px;">Subtotal</th>
                </tr></thead><tbody>`;
        ds.servicos.forEach(sv => {
            revEmbHtml += `<tr style="border-bottom: 1px solid var(--cor-borda);">
                <td style="padding: 6px;">${esc(sv.nome)}</td>
                <td style="text-align: center; padding: 6px;">${sv.qtd}</td>
                <td style="text-align: right; padding: 6px;">${formatarMoeda(sv.preco)}</td>
                <td style="text-align: right; padding: 6px; font-weight: 600;">${formatarMoeda(sv.subtotal)}</td>
            </tr>`;
        });
        revEmbHtml += `<tr><td colspan="3" style="text-align: right; padding: 6px; font-weight: 600;">Total da Embarcação:</td>
            <td style="text-align: right; padding: 6px; font-weight: 700; color: var(--cor-destaque);">${formatarMoeda(ds.total)}</td></tr>`;
        revEmbHtml += '</tbody></table></div>';
    });
    document.getElementById('reviewPorEmbarcacao').innerHTML = revEmbHtml || '<p class="text-muted">Nenhum serviço selecionado.</p>';

    document.getElementById('rSubtotal').textContent = formatarMoeda(subtotalGeral);
    document.getElementById('rDescontoPerc').textContent = descontoPerc.toFixed(2).replace('.', ',');
    document.getElementById('rDesconto').textContent = '- ' + formatarMoeda(descontoValor);
    document.getElementById('rEntrada').textContent = formatarMoeda(valorEntrada);
    document.getElementById('rSaldo').textContent = formatarMoeda(saldoRestante);
    document.getElementById('rTotalGeral').textContent = formatarMoeda(totalGeral);

    const valorParcela = parcelas > 0 ? saldoRestante / parcelas : saldoRestante;
    let rph = valorEntrada > 0 ? `Entrada de <strong>${formatarMoeda(valorEntrada)}</strong>` : '';
    for (let i = 1; i <= parcelas; i++) {
        if (rph) rph += ' &middot; ';
        rph += `${i}x de <strong>${formatarMoeda(valorParcela)}</strong>`;
    }
    document.getElementById('rParcelas').innerHTML = rph;

    document.getElementById('reviewLoading').style.display = 'none';
    document.getElementById('reviewContent').style.display = 'block';
}

function formatarMoeda(valor) {
    return 'R$ ' + valor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function esc(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function escAttr(str) {
    return esc(String(str)).replace(/'/g, '&#39;');
}

function deveIgnorarEnterWizard(event) {
    const alvo = event.target;
    const tag = (alvo?.tagName || '').toLowerCase();
    const tipo = (alvo?.type || '').toLowerCase();

    if (event.key !== 'Enter') return true;
    if (event.ctrlKey || event.altKey || event.shiftKey || event.metaKey) return true;
    if (alvo?.isContentEditable) return true;
    if (['textarea', 'select', 'button', 'a'].includes(tag)) return true;
    if (tag === 'input' && !['checkbox', 'radio'].includes(tipo)) {
        event.preventDefault();
        return true;
    }

    return false;
}

function obterPassoAtualWizard() {
    const painelVisivel = Array.from(document.querySelectorAll('.wizard-panel')).find(painel => {
        const style = window.getComputedStyle(painel);
        return style.display !== 'none' && style.visibility !== 'hidden';
    });
    const match = painelVisivel?.id?.match(/^passo(\d+)$/);
    return match ? parseInt(match[1], 10) : 1;
}

function avancarWizardComEnter(event) {
    if (deveIgnorarEnterWizard(event)) return;

    const passoAtual = obterPassoAtualWizard();
    let botao = null;

    if (passoAtual === 1) botao = document.getElementById('btnPasso1');
    if (passoAtual === 2) botao = document.getElementById('btnPasso2');
    if (passoAtual === 3) botao = document.querySelector('#passo3 button[type="submit"]');

    if (!botao || botao.disabled) return;

    event.preventDefault();
    botao.click();
}

document.addEventListener('keydown', avancarWizardComEnter);
document.getElementById('wizardForm')?.addEventListener('submit', event => {
    if (validarDescontoPercentual(true)) return;
    event.preventDefault();
    irParaPasso(2);
    document.getElementById('descontoGlobalDisplay')?.focus();
});
</script>

<style>
.wizard-step.active .step-label { color: var(--cor-destaque) !important; font-weight: 600 !important; }
.cliente-card:hover { border-color: var(--cor-destaque) !important; }
.cliente-card.is-selected {
    border-color: #56e0ad !important;
    background: rgba(46,204,113,0.15) !important;
    box-shadow: inset 4px 0 0 #56e0ad, 0 0 0 3px rgba(86,224,173,0.16);
}
.cliente-check-indicator {
    min-width: 28px;
    height: 28px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 0 8px;
    color: transparent;
    background: transparent;
    border: 2px solid rgba(199,244,231,0.22);
    flex-shrink: 0;
    transition: all 0.2s;
}
.cliente-card.is-selected .cliente-check-indicator {
    color: #021210;
    background: #56e0ad;
    border-color: #56e0ad;
}
.cliente-check-indicator em {
    display: none;
    font-size: 11px;
    font-style: normal;
    font-weight: 800;
}
.cliente-card.is-selected .cliente-check-indicator em {
    display: inline;
}
.wizard-helper,
.wizard-warning,
.armador-box {
    border: 1px solid var(--cor-borda);
    border-radius: 10px;
    background: rgba(46,204,113,0.06);
    padding: 12px 14px;
}
.wizard-helper,
.wizard-warning {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    margin-bottom: 15px;
    color: var(--cor-texto-secundario);
}
.wizard-helper i {
    color: var(--cor-destaque);
    margin-top: 2px;
}
.wizard-warning {
    background: rgba(255,193,7,0.08);
}
.wizard-warning i {
    color: #ffc107;
    margin-top: 2px;
}
.armador-box {
    margin-top: 18px;
    display: grid;
    grid-template-columns: minmax(220px, 0.8fr) minmax(260px, 1.2fr);
    gap: 14px;
    align-items: center;
}
.armador-box label,
.armador-box small {
    display: block;
}
.armador-box small {
    color: var(--cor-texto-secundario);
    margin-top: 4px;
}
.armador-box select {
    width: 100%;
}
.discount-card {
    text-align: center;
    padding: 12px;
    background: var(--cor-fundo);
    border-radius: 8px;
    border: 1px solid var(--cor-borda);
}
.discount-card > small:first-child {
    display: block;
    margin-bottom: 8px;
}
.discount-hidden-select {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    opacity: 0;
    pointer-events: none;
}
.discount-control {
    display: grid;
    grid-template-columns: 1fr;
    align-items: stretch;
    justify-content: center;
    gap: 8px;
    width: 100%;
    min-width: 0;
}
.discount-mode {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    width: 100%;
    min-width: 0;
    border: 1px solid var(--cor-borda);
    border-radius: 8px;
    overflow: hidden;
    background: var(--cor-sidebar);
    padding: 3px;
    gap: 3px;
}
.discount-mode-btn {
    min-width: 0;
    height: 40px;
    border: 1px solid transparent;
    border-radius: 6px;
    background: transparent;
    color: var(--cor-texto-secundario);
    font-weight: 800;
    cursor: pointer;
    transition: background 0.18s, color 0.18s, border-color 0.18s, box-shadow 0.18s;
}
.discount-mode-btn:hover {
    color: var(--cor-texto);
    border-color: rgba(86,224,173,0.35);
}
.discount-mode-btn.is-active {
    background: #56e0ad;
    border-color: #56e0ad;
    box-shadow: 0 0 0 2px rgba(86,224,173,0.22);
    color: #021210;
}
.discount-input-wrap {
    min-width: 0;
    height: 42px;
    display: flex;
    align-items: center;
    border: 1px solid var(--cor-borda);
    border-radius: 8px;
    background: var(--cor-sidebar);
    overflow: hidden;
}
.discount-card,
.entry-card {
    min-width: 0;
}
.discount-mode {
    width: 100%;
    grid-template-columns: 1fr 1fr;
}
.discount-input-wrap span {
    min-width: 44px;
    height: 100%;
    display: grid;
    place-items: center;
    color: var(--cor-destaque);
    font-weight: 800;
    border-right: 1px solid var(--cor-borda);
}
.discount-input-wrap input {
    width: 100%;
    min-width: 0;
    height: 100%;
    border: 0;
    outline: none;
    background: transparent;
    color: var(--cor-texto);
    text-align: center;
    font-size: 1rem;
    font-weight: 800;
}
.discount-feedback {
    display: block;
    margin-top: 8px;
    color: var(--cor-texto-secundario);
}
.discount-input-wrap.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.12);
}
.discount-input-wrap input[aria-invalid="true"] {
    color: #b4232d !important;
}
.discount-error {
    display: block;
    margin-top: 7px;
    color: #b4232d;
    font-size: 0.78rem;
    font-weight: 700;
}
.discount-error[hidden] {
    display: none;
}
.servico-linha:hover { background: rgba(46,204,113,0.03) !important; }
.emb-body table { font-size: 0.9rem; }
.embarcacao-selector-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 12px;
}
.embarcacao-select-card {
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr) auto;
    gap: 12px;
    align-items: center;
    width: 100%;
    min-height: 74px;
    padding: 12px 14px;
    border: 1px solid var(--cor-borda);
    border-radius: 10px;
    background: var(--cor-fundo);
    color: var(--cor-texto);
    text-align: left;
    cursor: pointer;
}
.embarcacao-select-card:hover,
.embarcacao-select-card.is-selected {
    border-color: var(--cor-destaque);
    background: rgba(46,204,113,0.08);
}
.embarcacao-select-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    color: var(--cor-destaque);
    background: rgba(46,204,113,0.12);
}
.embarcacao-select-main strong,
.embarcacao-select-main small,
.embarcacao-select-summary b,
.embarcacao-select-summary small {
    display: block;
}
.embarcacao-select-main strong {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.embarcacao-select-main small,
.embarcacao-select-summary small {
    color: var(--cor-texto-secundario);
}
.embarcacao-select-summary {
    text-align: right;
}
.embarcacao-select-summary b {
    color: var(--cor-destaque);
}
@media (max-width: 720px) {
    .armador-box {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
