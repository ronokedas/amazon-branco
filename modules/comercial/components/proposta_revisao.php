<?php
/**
 * Componente: Passo 3 do Wizard de Proposta
 * Responsabilidade: Revisão da Proposta, Totais Consolidados, Forma de Pagamento e Submissão
 */
?>
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
