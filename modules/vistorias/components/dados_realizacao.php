            <!-- ===== DATA DA VISTORIA E ARMADOR ===== -->
            <section class="report-inspection-card">
                <div class="report-section-heading">
                    <div><i class="fas fa-calendar-check"></i><span><strong>Dados da realização</strong><small>Confirme a data, validade e o responsável presente.</small></span></div>
                </div>
                <div class="report-inspection-grid">
                    <div class="report-inspection-column">
                        <div class="form-group">
                            <label for="data_vistoria"><i class="fas fa-calendar-check"></i> Data da Realização da Vistoria *</label>
                            <input type="date" id="data_vistoria" name="data_vistoria" class="form-control"
                                   value="<?php echo h($vistoria['data_vistoria'] ?? $ag['data_vistoria']); ?>" required>
                        </div>
                        <div class="form-group report-validity-field">
                            <label for="prazo_exigencias_dias">Prazo de validade do relatório <span class="required-mark">*</span></label>
                            <select id="prazo_exigencias_dias" name="prazo_exigencias_dias" class="form-control" required>
                                <option value="" <?= $prazo_exigencias_dias === '' ? 'selected' : '' ?> disabled>Selecione...</option>
                                <option value="60" <?= $prazo_exigencias_dias === '60' ? 'selected' : '' ?>>60 dias</option>
                                <option value="90" <?= $prazo_exigencias_dias === '90' ? 'selected' : '' ?>>90 dias</option>
                            </select>
                            <small class="report-field-help">Define o vencimento das exigências e a validade do CSN. A/S significa “Antes de suspender” e bloqueia a embarcação e todos os certificados.</small>
                        </div>
                    </div>
                    <div class="report-inspection-column">
                      <div class="form-group">
                    <label for="armador_id">
                        <i class="fas fa-user-tie"></i> Armador na data da Vistoria (Operador)
                    </label>
                    <select id="armador_id" name="armador_id" class="form-control">
                        <option value="" style="background: #2a2a3e; color: #ddd;">-- Nenhum Armador Específico --</option>
                        <?php
                        try {
                            $stmtArm = $pdo->query("SELECT id, nome, cpf_cnpj FROM clientes WHERE perfil = 'armador' AND status = 'ATIVO' ORDER BY nome ASC");
                            while ($a = $stmtArm->fetch(PDO::FETCH_ASSOC)) {
                                $armadorAtualId = $vistoria['armador_id'] ?? $ag['armador_id'] ?? '';
                                $selected = ($armadorAtualId === $a['id']) ? 'selected' : '';
                                echo "<option value='".h($a['id'])."' $selected style='background: #2a2a3e; color: #ddd;'>".h($a['nome'])." (".h($a['cpf_cnpj']).")</option>";
                            }
                        } catch (Exception $e) {
                            error_log('Erro ao carregar armadores: ' . $e->getMessage());
                        }
                        ?>
                    </select>
                    <small class="text-muted" style="display:block; margin-top: 6px;">
                        Se o responsável presente for funcionário ou outra pessoa, digite o nome abaixo.
                    </small>
                    <input type="text"
                           id="operador_nome"
                           name="operador_nome"
                           class="form-control"
                           value="<?php echo h($vistoria['operador_nome'] ?? $ag['agendamento_operador_nome'] ?? ''); ?>"
                           placeholder="Nome do operador/responsável presente na vistoria">
                      </div>
                    </div>
                </div>
            </section>
