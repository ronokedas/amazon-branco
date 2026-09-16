            <div style="margin: 20px 20px 0; padding: 14px 16px; border: 1px solid var(--cor-destaque); border-radius: 8px; background: rgba(46, 204, 113, 0.08);">
                <strong style="display:block; margin-bottom: 8px; color: var(--cor-destaque);">
                    <i class="fas fa-user-check"></i> Responsável pelo fechamento da proposta
                </strong>
                <div><?php echo h($ag['contato_nome'] ?: 'Não informado'); ?></div>
                <small class="text-muted">
                    Telefone:
                    <?php if (!empty($ag['contato_telefone'])): ?>
                        <a href="tel:<?php echo h(preg_replace('/\D/', '', $ag['contato_telefone'])); ?>"><?php echo h($ag['contato_telefone']); ?></a>
                    <?php else: ?>
                        Não informado
                    <?php endif; ?>
                </small>
            </div>

            <!-- ===== FOTO OFICIAL & IDENTIFICAÇÃO DA EMBARCAÇÃO ===== -->
            <section class="report-vessel-card" id="secaoFotoOficialEmbarcacao">
                <div class="report-section-heading">
                    <div>
                        <i class="fas fa-camera"></i>
                        <span>
                            <strong>Foto Oficial da Embarcação & Identificação Técnica</strong>
                            <small>Capture em campo ou envie a foto canônica da embarcação para sincronização com Gerenciar Embarcações, dossiê e certificados.</small>
                        </span>
                    </div>
                    <div>
                        <a href="<?= APP_URL ?>embarcacoes" target="_blank" class="vessel-specs-link" title="Abrir Gerenciar Embarcações em nova aba">
                            <i class="fas fa-arrow-up-right-from-square"></i> Ver em Embarcações
                        </a>
                    </div>
                </div>
                <div class="vessel-card-body">
                    <!-- Coluna da Foto Oficial e Captura -->
                    <div class="vessel-photo-box">
                        <div class="vessel-photo-frame" id="vesselPhotoFrame">
                            <div id="vesselPhotoOverlay" class="vessel-photo-overlay">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                                <span id="vesselPhotoOverlayText">Atualizando foto oficial...</span>
                            </div>
                            <span id="vesselPhotoBadge" class="vessel-photo-badge <?= !empty($ag['foto_url']) ? 'is-cadastrada' : 'is-sem-foto' ?>">
                                <i class="fas <?= !empty($ag['foto_url']) ? 'fa-circle-check' : 'fa-camera' ?>"></i>
                                <?= !empty($ag['foto_url']) ? 'Foto Cadastrada' : 'Sem Foto Oficial' ?>
                            </span>
                            <img id="imgFotoOficialEmbarcacao" src="<?= !empty($ag['foto_url']) ? h($ag['foto_url']) : '' ?>" alt="Foto Oficial da Embarcação" style="<?= empty($ag['foto_url']) ? 'display:none;' : '' ?>">
                            <div id="vesselPhotoPlaceholder" class="photo-placeholder" style="<?= !empty($ag['foto_url']) ? 'display:none;' : '' ?>">
                                <i class="fas fa-ship"></i>
                                <strong>Nenhuma foto oficial registrada</strong>
                                <small>Tire uma foto direta em campo com a câmera ou escolha um arquivo da galeria.</small>
                            </div>
                        </div>

                        <div class="vessel-photo-actions">
                            <!-- Botao da Camera (com capture=environment para abrir camera traseira no celular/tablet) -->
                            <button type="button" class="btn-photo-capture" onclick="document.getElementById('inputCameraEmbarcacao').click()">
                                <i class="fas fa-camera"></i> Tirar Foto com a Câmera
                            </button>
                            <input type="file" id="inputCameraEmbarcacao" accept="image/*" capture="environment" style="display:none" onchange="uploadFotoEmbarcacaoAjax(this)">

                            <!-- Botao da Galeria / Arquivo -->
                            <button type="button" class="btn-photo-upload" onclick="document.getElementById('inputArquivoEmbarcacao').click()">
                                <i class="fas fa-images"></i> Escolher da Galeria / Arquivo
                            </button>
                            <input type="file" id="inputArquivoEmbarcacao" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="uploadFotoEmbarcacaoAjax(this)">

                            <!-- Input backup para envio tradicional caso salve o form -->
                            <input type="file" name="foto_oficial_embarcacao" id="inputFotoFormulario" accept="image/jpeg,image/png,image/webp" style="display:none">

                            <!-- Botao para remover foto -->
                            <button type="button" id="btnRemoverFotoOficial" class="btn-photo-remove" onclick="removerFotoEmbarcacaoAjax()" style="<?= empty($ag['foto_url']) ? 'display:none;' : '' ?>">
                                <i class="fas fa-trash-can"></i> Remover Foto Oficial
                            </button>
                        </div>

                        <div id="msgFotoOficialEmbarcacao" style="display:none; font-size: 12px; padding: 8px 12px; border-radius: 6px;"></div>

                        <p class="vessel-photo-hint">
                            <i class="fas fa-circle-check text-success"></i> A foto é salva instantaneamente no banco de dados e refletida no Gerenciar Embarcações, sem recarregar a página e sem perder as respostas do checklist.
                        </p>
                    </div>

                    <!-- Coluna de Especificações Técnicas da Embarcação -->
                    <div class="vessel-specs-container">
                        <div class="vessel-specs-header">
                            <span class="vessel-specs-title">
                                <i class="fas fa-id-card-clip text-primary"></i>
                                <?= h($ag['embarcacao_nome']) ?>
                            </span>
                            <span class="badge bg-secondary" style="font-size: 12px;">
                                <?= h($ag['tipo_embarcacao'] ?: ($ag['tipo'] ?: 'Embarcação')) ?>
                            </span>
                        </div>

                        <div class="vessel-specs-grid">
                            <div class="vessel-spec-item">
                                <span class="vessel-spec-label">Nº Inscrição / TIE</span>
                                <span class="vessel-spec-val"><?= h($ag['embarcacao_numero_inscricao'] ?: ($ag['embarcacao_registro'] ?: 'Não informado')) ?></span>
                            </div>
                            <div class="vessel-spec-item">
                                <span class="vessel-spec-label">Ano de Construção</span>
                                <span class="vessel-spec-val"><?= h($ag['embarcacao_ano'] ?: 'Não informado') ?></span>
                            </div>
                            <div class="vessel-spec-item">
                                <span class="vessel-spec-label">Material do Casco</span>
                                <span class="vessel-spec-val"><?= h($ag['material_casco'] ?: 'Não informado') ?></span>
                            </div>
                            <div class="vessel-spec-item">
                                <span class="vessel-spec-label">Arqueação Bruta (AB)</span>
                                <span class="vessel-spec-val"><?= !empty($ag['arqueacao_bruta']) ? h($ag['arqueacao_bruta']) . ' AB' : 'Não informada' ?></span>
                            </div>
                            <div class="vessel-spec-item">
                                <span class="vessel-spec-label">Comprimento (LOA)</span>
                                <span class="vessel-spec-val"><?= !empty($ag['comprimento_total']) ? h($ag['comprimento_total']) . ' m' : 'Não informado' ?></span>
                            </div>
                            <div class="vessel-spec-item">
                                <span class="vessel-spec-label">Boca Moldada</span>
                                <span class="vessel-spec-val"><?= !empty($ag['boca_moldada']) ? h($ag['boca_moldada']) . ' m' : 'Não informada' ?></span>
                            </div>
                            <div class="vessel-spec-item">
                                <span class="vessel-spec-label">Pontal Moldado</span>
                                <span class="vessel-spec-val"><?= !empty($ag['pontal_moldado']) ? h($ag['pontal_moldado']) . ' m' : 'Não informado' ?></span>
                            </div>
                            <div class="vessel-spec-item">
                                <span class="vessel-spec-label">Propulsão Naval</span>
                                <span class="vessel-spec-val"><?= !empty($ag['possui_propulsao']) ? 'Com Propulsão' : 'Sem Propulsão' ?></span>
                            </div>
                            <div class="vessel-spec-item">
                                <span class="vessel-spec-label">Lotação Passageiros</span>
                                <span class="vessel-spec-val">
                                    N1: <?= h($ag['numero_passageiros_n1'] ?? 0) ?> | N2: <?= h($ag['numero_passageiros_n2'] ?? 0) ?>
                                </span>
                            </div>
                            <div class="vessel-spec-item">
                                <span class="vessel-spec-label">Cliente Proprietário</span>
                                <span class="vessel-spec-val"><?= h($ag['cliente_nome']) ?></span>
                            </div>
                        </div>

                        <div class="vessel-specs-normam-box">
                            <i class="fas fa-shield-halved"></i>
                            <div>
                                <strong>Padronização de Campo (NORMAM-DPC):</strong>
                                A comprovação visual da embarcação em conjunto com os dados de arqueação, boca e casco é requisito essencial para relatórios periciais navais e auditorias da Capitania dos Portos.
                            </div>
                        </div>
                    </div>
                </div>
            </section>
