                <div style="margin:20px;padding:18px;border:1px solid #f59e0b;border-radius:10px;background:rgba(245,158,11,.08);">
                    <h4 style="margin-top:0;"><i class="fas fa-clipboard-check"></i> Relatório de Verificação de Cumprimento de Exigências
                        <span class="badge <?= $tipo_retorno_atual === 'AS' ? 'bg-danger' : 'bg-warning text-dark' ?>"><?= $tipo_retorno_atual === 'AS' ? 'RETORNO A/S' : 'RETORNO - EXIGÊNCIAS' ?></span>
                    </h4>
                    <p>Continuação do relatório <strong><?= h($relatorio_anterior_numero_ui ?: $vistoria['relatorio_anterior_id']) ?></strong>. Classifique todas as exigências pendentes herdadas, inclusive as comuns, e registre qualquer nova deficiência encontrada.</p>
                </div>
                <div style="padding:0 20px 20px;display:grid;gap:14px;">
                    <div class="form-group">
                        <label for="data_vistoria">Data da verificação *</label>
                        <input type="date" id="data_vistoria" name="data_vistoria" required readonly value="<?= h($vistoria['data_vistoria'] ?? $ag['data_vistoria'] ?? date('Y-m-d')) ?>">
                    </div>
                    <?php foreach ($exigencias_avulsas as $ex): ?>
                        <article style="padding:16px;border:1px solid var(--cor-borda,#444);border-radius:9px;">
                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                                <div>
                                    <strong><?= h($ex['descricao'] ?: $ex['item']) ?></strong>
                                    <?php if (!empty($ex['item_normam'])): ?><small style="display:block;">NORMAM: <?= h($ex['item_normam']) ?></small><?php endif; ?>
                                </div>
                                <?php if (!empty($ex['antes_de_suspender'])): ?><span class="badge bg-danger">A/S — Antes de suspender</span><?php endif; ?>
                            </div>
                            <?php if (!empty($ex['exigencia_origem_id'])): ?>
                                <div class="form-group" style="margin-top:12px;">
                                    <label for="cumprimento_<?= h($ex['id']) ?>">Resultado da verificação *</label>
                                    <select class="cumprimento-status" data-exigencia-id="<?= h($ex['id']) ?>" id="cumprimento_<?= h($ex['id']) ?>" name="cumprimento_status[<?= h($ex['id']) ?>]" required>
                                        <option value="pendente" <?= ($ex['status_item'] ?? '') === 'pendente' ? 'selected' : '' ?>>Não cumprida / transcrita</option>
                                        <option value="cumprida" <?= ($ex['status_item'] ?? '') === 'cumprida' ? 'selected' : '' ?>>Cumprida</option>
                                        <option value="cumprida_parcial_reescrita" <?= ($ex['status_item'] ?? '') === 'cumprida_parcial_reescrita' ? 'selected' : '' ?>>Parcialmente cumprida / reescrita</option>
                                        <option value="nao_cumprida_transcrita" <?= ($ex['status_item'] ?? '') === 'nao_cumprida_transcrita' ? 'selected' : '' ?>>Não cumprida / transcrita</option>
                                    </select>
                                </div>
                                <div class="form-group cumprimento-reescrita" data-reescrita-id="<?= h($ex['id']) ?>" hidden>
                                    <label for="reescrita_<?= h($ex['id']) ?>">Descrição reescrita *</label>
                                    <textarea id="reescrita_<?= h($ex['id']) ?>" name="cumprimento_descricao_reescrita[<?= h($ex['id']) ?>]" rows="3" placeholder="Reescreva a exigência conforme o cumprimento parcial."><?= h($ex['descricao_reescrita'] ?? '') ?></textarea>
                                    <small>A referência normativa permanece: <?= h($ex['item_normam'] ?: 'não informada') ?></small>
                                </div>
                            <?php else: ?>
                                <p style="margin:12px 0 0;"><span class="badge bg-info">INSERIDA NESTA VISITA</span></p>
                            <?php endif; ?>
                            <?php if (!empty($ex['observacao_origem'])): ?>
                                <div class="form-group" style="margin-top:12px;">
                                    <label>Observação registrada no relatório anterior</label>
                                    <div style="padding:11px 12px;border:1px solid var(--cor-borda,#444);border-radius:7px;background:rgba(120,120,120,.08);white-space:pre-wrap;"><?= h($ex['observacao_origem']) ?></div>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                    <div style="padding:16px;border:1px dashed var(--cor-borda,#777);border-radius:9px;">
                        <h4 style="margin-top:0;">Novas exigências encontradas nesta visita</h4>
                        <p>Use esta área somente para uma deficiência nova, que não veio do relatório anterior.</p>
                        <div id="novasExigenciasRetorno"></div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="adicionarNovaExigenciaRetorno()">
                            <i class="fas fa-plus"></i> Adicionar nova exigência
                        </button>
                    </div>
                    <div class="form-group">
                        <label for="observacoes_tecnicas">Observações técnicas gerais</label>
                        <textarea id="observacoes_tecnicas" name="observacoes_tecnicas" rows="4"><?= h($vistoria['observacoes_tecnicas'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group" style="padding:14px;border:1px solid #f59e0b;border-radius:8px;background:rgba(245,158,11,.06);">
                        <label for="prazo_exigencias_dias">Validade do relatório para certificação *</label>
                        <select id="prazo_exigencias_dias" name="prazo_exigencias_dias" required style="max-width:280px;">
                            <option value="" <?= $prazo_exigencias_dias === '' ? 'selected' : '' ?> disabled>Selecione...</option>
                            <option value="60" <?= $prazo_exigencias_dias === '60' ? 'selected' : '' ?>>60 dias</option>
                            <option value="90" <?= $prazo_exigencias_dias === '90' ? 'selected' : '' ?>>90 dias</option>
                        </select>
                        <small class="text-muted" style="display:block;margin-top:6px;">
                            A validade será contada a partir da data desta verificação e usada na emissão do certificado. Quando definida no relatório anterior, a opção é herdada automaticamente.
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="status_vistoria">Situação do relatório *</label>
                        <select id="status_vistoria" name="status_vistoria" required>
                            <option value="" selected disabled>Selecione o resultado...</option>
                            <option value="PENDENTE">Salvar como pendente</option>
                            <option value="AGUARDANDO_APROVACAO">Enviar para análise</option>
                        </select>
                    </div>
                </div>
