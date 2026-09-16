<?php
/**
 * Componente: Passo 2 do Wizard de Proposta
 * Responsabilidade: Seleção de Serviços por Embarcação e Painel de Resumo Financeiro (Totais, Descontos, Parcelas)
 */
?>
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
