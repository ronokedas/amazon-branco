            <!-- ===== OBSERVACOES TECNICAS ===== -->
            <div style="padding: 0 20px 20px;" class="observacoes-section">
                <h4 style="margin: 0 0 4px; font-size: 1rem; color: var(--cor-destaque, #2ECC71);"><i class="fas fa-flag-checkered"></i> Conclusão da Vistoria</h4>
                <small class="text-muted" style="display:block; margin-bottom:15px;">Registre observações gerais e defina o encaminhamento do relatório.</small>
                <div class="form-group">
                    <label for="observacoes_tecnicas">
                        <i class="fas fa-sticky-note"></i> Observações Técnicas
                    </label>
                    <textarea id="observacoes_tecnicas" name="observacoes_tecnicas" rows="4"
                              placeholder="Observações técnicas gerais, recomendações, restrições encontradas..."
                              style="width: 100%; padding: 10px 14px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 6px; color: var(--cor-texto, #ddd); resize: vertical;"><?php echo h($vistoria['observacoes_tecnicas'] ?? ''); ?></textarea>
                </div>

                <!-- Status da vistoria (resultado final) -->
                <div class="form-group" style="margin-top: 15px;">
                    <label for="status_vistoria">
                        <i class="fas fa-gavel"></i> Resultado Final da Vistoria *
                    </label>
                    <select id="status_vistoria" name="status_vistoria" required
                            style="width: 100%; padding: 10px 14px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 6px; color: var(--cor-texto, #ddd); font-size: 1rem;">
                        <option value="" selected disabled>Selecione o resultado...</option>
                        <option value="PENDENTE">Pendente (relatório em andamento)</option>
                        <option value="AGUARDANDO_APROVACAO">Aguardando Aprovação</option>
                        <?php if (getCargo() === 'ADMIN'): ?>
                        <option value="REPROVADA">Reprovada</option>
                        <option value="CANCELADA">Cancelada</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
