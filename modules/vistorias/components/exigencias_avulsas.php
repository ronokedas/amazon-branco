            <!-- ===== EXIGÊNCIAS AVULSAS ===== -->
            <div style="padding: 20px;" class="avulsa-section">
                <h4 style="margin: 0 0 15px 0; font-size: 1rem; color: var(--cor-destaque, #2ECC71);">
                    <i class="fas fa-plus-circle"></i> Exigências Avulsas (Fora do Catálogo)
                </h4>
                <small class="text-muted">Adicione itens pendentes que não constam no checklist acima.</small>

                <div style="margin: 15px 0;" class="no-print">
                    <button type="button" class="btn btn-sm btn-primary avulsa-add-button" onclick="adicionarLinhaAvulsa()">
                        <i class="fas fa-plus"></i> Adicionar Item Avulso
                    </button>
                </div>

                <div id="avulsaEmpty" class="avulsa-empty <?= empty($exigencias_avulsas) ? '' : 'is-hidden' ?>">
                    <i class="fas fa-clipboard-check"></i>
                    Nenhuma exigência avulsa adicionada.
                </div>
                <div id="avulsaTableWrap" class="avulsa-table-wrap <?= empty($exigencias_avulsas) ? 'is-hidden' : '' ?>">
                <table id="tabelaExigenciasAvulsas" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--cor-sidebar, #1a1a2e); border-bottom: 2px solid var(--cor-borda);">
                            <th style="width: 40px; text-align: center; padding: 8px 6px;">#</th>
                            <th style="width: 170px; text-align: left; padding: 8px 6px;">Tipo de Vistoria</th>
                            <th style="text-align: left; padding: 8px 6px;">Descrição da Exigência *</th>
                            <th style="text-align: left; padding: 8px 6px;">Item da NORMAM</th>
                            <th style="width: 120px; text-align: center; padding: 8px 6px;">Situação</th>
                            <th style="text-align: left; padding: 8px 6px;">Observacao / Justificativa</th>
                            <th style="width: 130px; text-align: center; padding: 8px 6px;">A/S — Antes de suspender</th>
                            <th style="width: 40px; text-align: center; padding: 8px 6px;" class="no-print"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exigencias_avulsas as $idx => $ex): ?>
                        <tr class="linha-exigencia-avulsa">
                            <td data-label="Ordem" style="text-align: center; padding: 6px;">
                                <span class="ordem-num-avulsa"><?php echo (int)$ex['ordem']; ?></span>
                                <input type="hidden" name="exigencia_id[]" value="<?php echo h($ex['id'] ?? ''); ?>">
                                <input type="hidden" name="exigencia_ordem[]" value="<?php echo (int)$ex['ordem']; ?>" class="ordem-input-avulsa">
                            </td>
                            <td data-label="Tipo" style="padding: 6px;">
                                <?php $blocoAtual = $ex['bloco_vistoria'] ?? $bloco_vistoria_padrao; ?>
                                <select name="exigencia_bloco[]"
                                        style="width: 100%; padding: 6px 4px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                                    <?php foreach ($blocos_vistoria_disponiveis as $valorBloco => $rotuloBloco): ?>
                                        <option value="<?php echo h($valorBloco); ?>" <?php echo $blocoAtual === $valorBloco ? 'selected' : ''; ?>>
                                            <?php echo h($rotuloBloco); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td data-label="Descrição" style="padding: 6px;">
                                <input type="text" name="exigencia_descricao[]" value="<?php echo h($ex['descricao'] ?? ''); ?>"
                                       placeholder="Ex.: nao tem seguranca" required
                                       style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                            </td>
                            <td data-label="NORMAM" style="padding: 6px;">
                                <input type="text" name="exigencia_item[]" value="<?php echo h($ex['item']); ?>"
                                       placeholder="Ex.: NORMAM-202/DPC, Cap. 03"
                                       style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                            </td>
                            <td data-label="Situação" style="padding: 6px; text-align: center;">
                                <select name="status_item[]"
                                        style="width: 100%; padding: 6px 4px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                                    <option value="inserida" <?php echo ($ex['status_item'] ?? 'inserida') === 'inserida' ? 'selected' : ''; ?>>Inserida / N/A</option>
                                    <option value="pendente" <?php echo ($ex['status_item'] ?? '') === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                    <option value="cumprida" <?php echo ($ex['status_item'] ?? '') === 'cumprida' ? 'selected' : ''; ?>>Cumprida</option>
                                </select>
                            </td>
                            <td data-label="Observação" style="padding: 6px;">
                                <input type="text" name="exigencia_observacao[]" value="<?php echo h($ex['observacao'] ?? ''); ?>"
                                       placeholder="Observacao"
                                       style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                            </td>
                            <td data-label="Sem prazo" style="padding: 6px; text-align: center;">
                                <?php $avulsaSemPrazo = !empty($ex['antes_de_suspender']); ?>
                                <input type="hidden" name="exigencia_sem_prazo[]" class="avulsa-sem-prazo-input" value="<?php echo $avulsaSemPrazo ? '1' : '0'; ?>">
                                <label style="display:inline-flex; align-items:center; gap:6px;">
                                    <input type="checkbox" class="avulsa-sem-prazo-check" <?php echo $avulsaSemPrazo ? 'checked' : ''; ?>>
                                    A/S
                                </label>
                            </td>
                            <td data-label="Ações" style="text-align: center; padding: 6px;" class="no-print">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removerLinhaAvulsa(this)" title="Remover" aria-label="Remover exigência avulsa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
