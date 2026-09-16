<?php
/**
 * Componente: Templates HTML para Clonagem Dinâmica no DOM do Wizard de Propostas
 * Responsabilidade: Estrutura base de blocos de embarcação e linhas de serviços
 */
?>
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
