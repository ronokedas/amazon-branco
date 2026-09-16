<?php
/**
 * Componente: Cabeçalho, Trilha de Navegação e Passo 1 do Wizard de Proposta
 * Responsabilidade: Seleção de Escritório, Proprietário e Responsável pelo Fechamento
 */
?>
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
