            <?php
            $responsavelVistoria = trim((string)($vistoria['operador_nome'] ?? ''));
            if ($responsavelVistoria === '') {
                $responsavelVistoria = $armador_relatorio_nome ?: ($ag['armador_nome'] ?? '');
            }
            if ($responsavelVistoria === '') {
                $responsavelVistoria = $ag['cliente_nome'] ?? 'Nao informado';
            }
            $statusLabelsAdmin = [
                'PENDENTE' => 'Pendente',
                'AGUARDANDO_APROVACAO' => 'Aguardando aprovacao',
                'APROVADA' => 'Aprovada',
                'APROVADA_COM_EXIGENCIAS' => 'Aprovada com exigencias',
                'RETORNO_AS' => 'Retorno A/S necessario',
                'REPROVADA' => 'Reprovada',
                'CANCELADA' => 'Cancelada',
            ];
            ?>
            <div class="admin-review-grid">
                <div class="admin-review-panel admin-review-main">
                    <h4><i class="fas fa-file-signature"></i> Revisão do relatório enviado</h4>
                    <div class="admin-review-body">
                        <div class="admin-review-kpis">
                            <div class="admin-review-kpi admin-review-kpi--status">
                                <small>Numero do relatorio</small>
                                <strong><?= h($vistoria['numero'] ?? 'S/N') ?></strong>
                            </div>
                            <div class="admin-review-kpi">
                                <small>Data da vistoria</small>
                                <strong><?= !empty($vistoria['data_vistoria']) ? formatarData($vistoria['data_vistoria']) : '-' ?></strong>
                            </div>
                            <div class="admin-review-kpi">
                                <small>Responsavel informado</small>
                                <strong><?= h($responsavelVistoria) ?></strong>
                            </div>
                            <div class="admin-review-kpi">
                                <small>Status atual</small>
                                <strong><?= h($statusLabelsAdmin[$vistoria['status'] ?? ''] ?? ($vistoria['status'] ?? '-')) ?></strong>
                            </div>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <h5 class="admin-review-section-heading">Observações técnicas do vistoriador</h5>
                            <div class="admin-review-text"><?= h($vistoria['observacoes_tecnicas'] ?: 'Nenhuma observacao tecnica informada.') ?></div>
                        </div>

                        <div class="admin-review-document">
                            <div class="admin-review-document-bar">
                                <div class="admin-review-document-title">
                                    <span><i class="fas fa-file-pdf"></i></span>
                                    <div><strong>Relatório original do vistoriador</strong><small>Confira todas as respostas, exigências e dados oficiais.</small></div>
                                </div>
                                <a href="<?= APP_URL ?>vistorias/relatorio_pdf.php?id=<?= urlencode($vistoria['id']); ?>"
                                   target="_blank" rel="noopener" class="admin-review-document-link">
                                    <i class="fas fa-up-right-from-square"></i> Abrir PDF completo
                                </a>
                            </div>
                        </div>

                        <section class="admin-requirements" aria-labelledby="adminRequirementsTitle">
                            <div class="admin-requirements-heading">
                                <h5 id="adminRequirementsTitle"><i class="fas fa-list-check"></i> Exigências registradas</h5>
                            </div>

                            <div class="admin-requirements-summary" aria-label="Resumo das exigências abertas">
                                <div class="admin-requirements-stat">
                                    <strong><?= (int)$resumo_aprovacao_relatorio['pendentes'] ?></strong>
                                    <span>abertas</span>
                                </div>
                                <div class="admin-requirements-stat is-as">
                                    <strong><?= (int)$resumo_aprovacao_relatorio['pendentes_as'] ?></strong>
                                    <span>A/S</span>
                                </div>
                                <div class="admin-requirements-stat is-common">
                                    <strong><?= (int)$resumo_aprovacao_relatorio['pendentes_comuns'] ?></strong>
                                    <span>comuns</span>
                                </div>
                                <div class="admin-requirements-stat is-common">
                                    <strong><?= count($exigencias_cumpridas_relatorio) ?></strong>
                                    <span>cumpridas</span>
                                </div>
                            </div>

                            <?php if (empty($exigencias_relatorio)): ?>
                                <div class="admin-requirements-empty">
                                    <i class="fas fa-circle-check"></i>
                                    Nenhuma exigência registrada neste relatório.
                                </div>
                            <?php else: ?>
                                <label class="admin-requirements-search" for="adminRequirementsSearch">
                                    <i class="fas fa-search"></i>
                                    <input type="search" id="adminRequirementsSearch" placeholder="Buscar exigência" autocomplete="off">
                                </label>

                                <div class="admin-requirements-groups" id="adminRequirementsGroups">
                                    <?php
                                    $gruposExigenciasReview = [
                                        [
                                            'id' => 'as',
                                            'titulo' => 'Exigências A/S',
                                            'icone' => 'fa-triangle-exclamation',
                                            'classe' => 'is-as',
                                            'itens' => $exigencias_as_relatorio,
                                            'aberto' => !empty($exigencias_as_relatorio),
                                        ],
                                        [
                                            'id' => 'comuns',
                                            'titulo' => 'Exigências comuns',
                                            'icone' => 'fa-circle-exclamation',
                                            'classe' => 'is-common',
                                            'itens' => $exigencias_comuns_relatorio,
                                            'aberto' => empty($exigencias_as_relatorio) && !empty($exigencias_comuns_relatorio),
                                        ],
                                        [
                                            'id' => 'cumpridas',
                                            'titulo' => 'Resultado da verificação — exigências cumpridas',
                                            'icone' => 'fa-circle-check',
                                            'classe' => 'is-common',
                                            'itens' => $exigencias_cumpridas_relatorio,
                                            'aberto' => empty($exigencias_as_relatorio) && empty($exigencias_comuns_relatorio),
                                        ],
                                    ];
                                    ?>
                                    <?php foreach ($gruposExigenciasReview as $grupoReview): ?>
                                        <?php if (empty($grupoReview['itens'])) continue; ?>
                                        <?php $grupoBodyId = 'admin-requirement-group-' . $grupoReview['id']; ?>
                                        <section class="admin-requirement-group <?= h($grupoReview['classe']) ?>" data-requirement-group>
                                            <button type="button"
                                                    class="admin-requirement-group-toggle"
                                                    aria-expanded="<?= $grupoReview['aberto'] ? 'true' : 'false' ?>"
                                                    aria-controls="<?= h($grupoBodyId) ?>">
                                                <i class="fas <?= h($grupoReview['icone']) ?>"></i>
                                                <span><?= h($grupoReview['titulo']) ?> (<span data-group-visible-count><?= count($grupoReview['itens']) ?></span>)</span>
                                                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                                            </button>
                                            <div class="admin-requirement-group-body"
                                                 id="<?= h($grupoBodyId) ?>"
                                                 <?= $grupoReview['aberto'] ? '' : 'hidden' ?>>
                                                <ul class="admin-requirement-list">
                                                    <?php foreach ($grupoReview['itens'] as $indiceReview => $exReview): ?>
                                                        <?php
                                                        $descricaoReview = (string)($exReview['descricao'] ?? $exReview['catalogo_descricao'] ?? 'Exigência sem descrição');
                                                        $referenciaReview = (string)($exReview['item_normam'] ?? $exReview['catalogo_item_normam'] ?? 'Sem referência normativa');
                                                        $detailId = 'admin-requirement-detail-' . $grupoReview['id'] . '-' . $indiceReview;
                                                        ?>
                                                        <li class="admin-requirement-item" data-requirement-item>
                                                            <button type="button"
                                                                    class="admin-requirement-row-toggle"
                                                                    aria-expanded="false"
                                                                    aria-controls="<?= h($detailId) ?>">
                                                                <span class="admin-requirement-description" title="<?= h($descricaoReview) ?>"><?= h($descricaoReview) ?></span>
                                                                <span class="admin-requirement-reference"><?= h($referenciaReview) ?></span>
                                                                <span class="admin-requirement-badge <?= $grupoReview['id'] === 'as' ? 'is-as' : '' ?>">
                                                                    <?= $grupoReview['id'] === 'as'
                                                                        ? 'A/S'
                                                                        : ($grupoReview['id'] === 'cumpridas' ? 'Cumprida' : 'Comum') ?>
                                                                </span>
                                                                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                                                            </button>
                                                            <div class="admin-requirement-detail" id="<?= h($detailId) ?>" hidden>
                                                                <strong><?= h($descricaoReview) ?></strong>
                                                                <div>Referência: <?= h($referenciaReview) ?></div>
                                                                <?php if (!empty($exReview['observacao'])): ?>
                                                                    <div>Observação: <?= h($exReview['observacao']) ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </section>
                                    <?php endforeach; ?>
                                </div>

                                <div class="admin-requirements-empty" id="adminRequirementsNoResults" hidden>
                                    <i class="fas fa-search"></i>
                                    Nenhuma exigência encontrada para esta busca.
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>
                </div>

                <div class="admin-review-sidebar">
                    <div class="admin-review-panel admin-decision-card">
                        <h4><i class="fas fa-gavel"></i> Decisão da vistoria</h4>
                        <div class="admin-review-body">
                            <div class="admin-decision-steps" aria-label="Etapas da decisão">
                                <span><b>1</b> Revise as exigências</span>
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                <span><b>2</b> Registre a decisão</span>
                            </div>
                            <?php if (($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO' && $cargo === 'ADMIN' && $eh_relatorio_vigente): ?>
                                <form method="POST" action="<?= APP_URL ?>vistorias/actions?action=aprovar_ou_reprovar" id="formDecisaoAdmin">
                                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()); ?>">
                                    <input type="hidden" name="id" value="<?= h($vistoria['id']); ?>">
                                    <input type="hidden" name="versao_relatorio" value="<?= h($resumo_aprovacao_relatorio['versao']); ?>">
                                    <div class="form-group mb-3">
                                        <label>Resultado da aprova&ccedil;&atilde;o *</label>
                                        <div style="display:grid;gap:9px;margin-top:8px">
                                            <label style="display:flex;gap:10px;align-items:flex-start;padding:12px;border:1px solid #b9ded2;border-radius:9px;background:#f2fbf8;<?= $resumo_aprovacao_relatorio['pendentes'] > 0 ? 'opacity:.55' : '' ?>">
                                                <input type="radio" name="resultado_relatorio" value="APROVADA" <?= $resumo_aprovacao_relatorio['pendentes'] === 0 ? 'checked' : 'disabled' ?>>
                                                <span><strong>Aprovada</strong><br><small>Libera certificados Provis&oacute;rio, Condicional e Definitivo.</small></span>
                                            </label>
                                            <label style="display:flex;gap:10px;align-items:flex-start;padding:12px;border:1px solid #efd39e;border-radius:9px;background:#fff9ed;<?= $resumo_aprovacao_relatorio['pendentes'] === 0 ? 'opacity:.55' : '' ?>">
                                                <input type="radio" name="resultado_relatorio"
                                                       value="<?= $resumo_aprovacao_relatorio['pendentes_as'] > 0 ? 'RETORNO_AS' : 'APROVADA_COM_EXIGENCIAS' ?>"
                                                       <?= $resumo_aprovacao_relatorio['pendentes'] > 0 ? 'checked' : 'disabled' ?>>
                                                <?php if ($resumo_aprovacao_relatorio['pendentes_as'] > 0): ?>
                                                    <span><strong>Encaminhar para Retorno A/S</strong><br><small>O relatório não será aprovado. O processo ficará aguardando o agendamento de uma nova visita.</small></span>
                                                <?php else: ?>
                                                    <span><strong>Aprovada com exig&ecirc;ncias</strong><br><small>Permite apenas certificados Provis&oacute;rio e Condicional.</small></span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="admin-decision-summary">
                                        <strong><?= (int)$resumo_aprovacao_relatorio['pendentes'] ?> exig&ecirc;ncia(s) aberta(s)</strong><br>
                                        <?= (int)$resumo_aprovacao_relatorio['pendentes_as'] ?> A/S e
                                        <?= (int)$resumo_aprovacao_relatorio['pendentes_comuns'] ?> comum(ns).
                                        <?php if ($resumo_aprovacao_relatorio['pendentes_as'] > 0): ?>
                                            <br><span style="color:#a52b22">Exig&ecirc;ncia A/S bloqueia toda certifica&ccedil;&atilde;o at&eacute; o cumprimento.</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="margin-bottom:16px">
                                        <?php if ($resumo_aprovacao_relatorio['pendentes_as'] > 0): ?>
                                            <div style="padding:14px;border:2px solid #d95047;border-radius:10px;background:#fff4f2">
                                                <strong style="display:block;color:#9f261f;margin-bottom:6px">
                                                    <i class="fas fa-ban"></i> Decis&atilde;o obrigat&oacute;ria: n&atilde;o aprovar
                                                </strong>
                                                <div style="margin-bottom:12px;color:#65322e">
                                                    Encaminhe este relat&oacute;rio para <strong>Retornos A/S</strong>. Depois, a pr&oacute;xima a&ccedil;&atilde;o no dashboard ser&aacute; agendar uma nova visita.
                                                </div>
                                                <button type="submit" name="decisao" value="retorno_as"
                                                        class="btn btn-danger"
                                                        onclick="return confirm('Este relatorio nao sera aprovado. Encaminhar agora para Retornos A/S e abrir a etapa de novo agendamento?')">
                                                    <i class="fas fa-calendar-plus"></i>
                                                    N&atilde;o aprovar e enviar para Retornos A/S
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <button type="submit" name="decisao" value="aprovar"
                                                    class="btn btn-warning"
                                                    onclick="return confirm('<?= $resumo_aprovacao_relatorio['pendentes'] > 0 ? 'Aprovar este relatorio com exigencias comuns?' : 'Aprovar este relatorio?' ?>')">
                                                <i class="fas fa-check-circle"></i>
                                                <?= $resumo_aprovacao_relatorio['pendentes'] > 0 ? 'Aprovar com exig&ecirc;ncias' : 'Aprovar relat&oacute;rio' ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <hr style="border:0;border-top:1px solid #dfe9e5;margin:16px 0">
                                    <div class="form-group mb-3">
                                        <label for="status_vistoria_admin">Outras decis&otilde;es administrativas</label>
                                        <select id="status_vistoria_admin" name="status_vistoria" class="form-control" required>
                                            <option value="AGUARDANDO_APROVACAO" selected>Manter aguardando aprova&ccedil;&atilde;o</option>
                                            <option value="PENDENTE">Devolver para corre&ccedil;&atilde;o</option>
                                            <option value="REPROVADA" <?= ($vistoria['status'] ?? '') === 'REPROVADA' ? 'selected' : '' ?>>Reprovada</option>
                                            <option value="CANCELADA" <?= ($vistoria['status'] ?? '') === 'CANCELADA' ? 'selected' : '' ?>>Cancelada</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="observacao_admin">Observa&ccedil;&atilde;o do administrador</label>
                                        <textarea id="observacao_admin" name="observacao_admin" class="form-control" rows="4" placeholder="Obrigat&oacute;ria para reprovar."></textarea>
                                    </div>
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> Salvar decis&atilde;o sem aprovar
                                        </button>
                                        <a href="<?= APP_URL ?>documentacao/aprovacao_relatorios" class="btn btn-secondary">
                                            Voltar para aprovacoes
                                        </a>
                                    </div>
                                </form>
                            <?php elseif (($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO' && !$eh_relatorio_vigente): ?>
                                <div class="admin-review-text">
                                    Este relatório foi substituído e permanece disponível somente para consulta.
                                    <?php if ($relatorio_vigente_cadeia): ?>
                                        <a href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode((string)$relatorio_vigente_cadeia['agendamento_id']) ?>&vistoria_id=<?= urlencode((string)$relatorio_vigente_cadeia['id']) ?>">
                                            Abrir <?= h($relatorio_vigente_cadeia['numero'] ?: $relatorio_vigente_cadeia['id']) ?>
                                        </a>.
                                    <?php endif; ?>
                                </div>
                            <?php elseif (($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO'): ?>
                                <div class="admin-review-text">
                                    O relat&oacute;rio est&aacute; dispon&iacute;vel para revis&atilde;o. Somente o vistoriador atribu&iacute;do pode alterar seu conte&uacute;do e suas exig&ecirc;ncias.
                                </div>
                            <?php else: ?>
                                <div class="admin-review-text">
                                    Este relatorio ja foi finalizado como <strong><?= h($statusLabelsAdmin[$vistoria['status'] ?? ''] ?? ($vistoria['status'] ?? '-')) ?></strong>.
                                    <?php if (!empty($vistoria['observacao_admin'])): ?>
                                        <br><br>Observacao do admin: <?= h($vistoria['observacao_admin']) ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($cargo === 'ADMIN'
                                    && $eh_relatorio_vigente
                                    && in_array(($vistoria['status'] ?? ''), ['APROVADA','APROVADA_COM_EXIGENCIAS'], true)
                                    && ($vistoria['assinatura_status'] ?? 'PENDENTE') !== 'ASSINADO'): ?>
                                    <div style="margin-top:12px;padding:16px;border:2px solid #e9b64b;border-radius:12px;background:#fff9ed;box-shadow:0 6px 18px rgba(132,87,0,.09)">
                                        <strong style="display:block;margin-bottom:7px"><i class="fas fa-clock"></i> Aguardando assinatura do vistoriador</strong>
                                        <p style="margin:0 0 10px;color:#66573b">Certificados, OS e agendamento permanecem bloqueados até a assinatura. O administrador pode aplicar somente a assinatura cadastrada do vistoriador atribuído.</p>
                                        <div style="display:flex;gap:10px;align-items:stretch;flex-wrap:wrap">
                                            <button type="button" class="btn btn-warning js-assinar-substituto"
                                                    style="flex:1 1 230px;justify-content:center"
                                                    data-documento-id="<?= h($vistoria['id']) ?>">
                                                <i class="fas fa-file-signature"></i> Assinar pelo vistoriador
                                            </button>
                                            <?php if (($vistoria['status'] ?? '') === 'APROVADA_COM_EXIGENCIAS'): ?>
                                                <a href="<?= APP_URL ?>dashboard#retornos-as"
                                                   class="btn btn-secondary"
                                                   style="flex:1 1 230px;justify-content:center">
                                                    <i class="fas fa-calendar-plus"></i> Reagendar exig&ecirc;ncias no painel
                                                </a>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-success"
                                                        style="flex:1 1 230px;justify-content:center;opacity:.58;cursor:not-allowed"
                                                        disabled title="Assine o relat&oacute;rio antes de gerar certificados">
                                                    <i class="fas fa-certificate"></i> Gerar certificados ap&oacute;s assinar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div style="margin-top: 12px;">
                                    <a href="<?= APP_URL ?>documentacao/aprovacao_relatorios" class="btn btn-secondary">
                                        Voltar para aprovacoes
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (false): // Inclusao em revisao desativada: somente o vistoriador altera exigencias. ?>
                    <div class="admin-review-panel">
                        <h4><i class="fas fa-plus-circle"></i> Exigencia manual do analista</h4>
                        <div class="admin-review-body">
                            <?php if (($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO'): ?>
                                <form method="POST" action="<?= APP_URL ?>vistorias/actions?action=adicionar_exigencia_analista">
                                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()); ?>">
                                    <input type="hidden" name="vistoria_id" value="<?= h($vistoria['id']); ?>">
                                    <div class="form-group mb-3">
                                        <label for="analista_bloco">Tipo</label>
                                        <select id="analista_bloco" name="bloco_vistoria" class="form-control">
                                            <?php foreach ($blocos_vistoria_todos as $valorBloco => $rotuloBloco): ?>
                                                <option value="<?= h($valorBloco); ?>"><?= h($rotuloBloco); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="analista_descricao">Descricao da exigencia *</label>
                                        <textarea id="analista_descricao" name="descricao" class="form-control" rows="3" required placeholder="Descreva a pendencia encontrada na revisao."></textarea>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="analista_normam">Referencia normativa</label>
                                        <input type="text" id="analista_normam" name="item_normam" class="form-control" placeholder="Ex.: NORMAM-202/DPC, item 4.14">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="analista_observacao">Observacao interna</label>
                                        <textarea id="analista_observacao" name="observacao" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label style="display:flex; gap:8px; align-items:center;">
                                            <input type="checkbox" name="sem_prazo" value="1" checked>
                                            A/S — Antes de suspender
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Adicionar exigencia ao relatorio
                                    </button>
                                </form>
                            <?php else: ?>
                                <p class="text-muted">Relatorio finalizado. Novas exigencias manuais ficam bloqueadas para preservar o historico.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php endif; ?>
                </div>
            </div>
