<?php
/**
 * Componente: Trâmite Oficial / SISAP - Capitanias, Delegacias e Agências
 * Local: modules/protocolos/components/tramite_oficial.php
 *
 * Responsável pelo registro oficial do processo na Autoridade Marítima (Marinha do Brasil),
 * acompanhamento do número do protocolo externo, controle de validade provisória
 * e atualização de andamentos (incluindo notificações de exigência e liberação para retirada).
 */
$chaveProc = 'protocolo_externo_' . 'numero';
?>

<div id="pane-marinha" class="prot-tab-pane <?= $abaAtiva === 'marinha' ? 'active' : '' ?>">
    <div class="row g-4">
        <!-- Registro Oficial do Atendimento na Marinha -->
        <div class="col-md-6">
            <section class="card h-100">
                <div class="card-body">
                    <h3 style="font-size: 1.15rem; color: var(--accent, #56e0ad);" class="mb-3">
                        <i class="fa-solid fa-building-flag"></i> Registro do Atendimento na Marinha
                    </h3>
                    <p class="text-secondary small">Preencha assim que o processo for presencialmente ou digitalmente protocolado na Capitania dos Portos, Delegacia ou Agência (DPC / SISAP).</p>

                    <?php if (!$somenteLeitura): ?>
                        <form method="post" action="<?= APP_URL ?>protocolos/actions">
                            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                            <input type="hidden" name="action" value="registro_orgao">
                            <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                            <input type="hidden" name="aba" class="input-aba-ativa" value="marinha">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Unidade Marítima (Capitania / Delegacia) *</label>
                                <select class="form-control" name="unidade_maritima_id" required>
                                    <option value="">-- Selecione a Capitania / Órgão --</option>
                                    <?php foreach ($unidades as $u): ?>
                                        <option value="<?= h($u['id']) ?>" <?= $d['unidade_maritima_id'] === $u['id'] ? 'selected' : '' ?>>
                                            <?= h($u['nome'] . ' (' . $u['cidade'] . '/' . $u['uf'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Identificação Oficial do Processo / Protocolo na Marinha</label>
                                <input class="form-control" name="numero_processo_orgao" 
                                       value="<?= h($d[$chaveProc] ?? '') ?>" 
                                       placeholder="Ex.: 23000.012345/2026-89">
                                <small class="text-muted">Número fornecido pelo protocolo da Capitania para acompanhamento no SISAP.</small>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Data/Hora do Atendimento *</label>
                                    <input class="form-control" type="datetime-local" name="protocolo_externo_em" required 
                                           value="<?= $d['protocolo_externo_em'] ? date('Y-m-d\TH:i', strtotime($d['protocolo_externo_em'])) : date('Y-m-d\TH:i') ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Validade do Protocolo Provisório</label>
                                    <input class="form-control" type="date" name="validade" 
                                           value="<?= h($d['protocolo_externo_validade'] ?? '') ?>">
                                    <small class="text-muted">Prazo provisório concedido pelo órgão (geralmente 90 ou 180 dias).</small>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Salvar Registro do Atendimento
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-secondary mb-0">
                            <strong>Dossiê Encerrado:</strong> Os dados de atendimento no órgão estão congelados para consulta e auditoria.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Atualização de Andamento / Notificação de Exigência -->
        <div class="col-md-6">
            <section class="card h-100">
                <div class="card-body">
                    <h3 style="font-size: 1.15rem; color: var(--accent, #56e0ad);" class="mb-3">
                        <i class="fa-solid fa-arrow-progress"></i> Atualização de Andamento na Marinha
                    </h3>
                    <p class="text-secondary small">Atualize o status do processo conforme as movimentações e despachos da Capitania dos Portos (ex.: notificação de exigência ou documento pronto para retirada).</p>

                    <?php if (!$somenteLeitura): ?>
                        <form method="post" action="<?= APP_URL ?>protocolos/actions">
                            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                            <input type="hidden" name="action" value="andamento_orgao">
                            <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                            <input type="hidden" name="aba" class="input-aba-ativa" value="marinha">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Novo Andamento Informado pelo Órgão *</label>
                                <select class="form-control" name="novo_status" required>
                                    <option value="PROTOCOLADO" <?= $d['status'] === 'PROTOCOLADO' ? 'selected' : '' ?>>PROTOCOLADO (Aguardando análise da Capitania)</option>
                                    <option value="EM_ANALISE_NO_ORGAO" <?= $d['status'] === 'EM_ANALISE_NO_ORGAO' ? 'selected' : '' ?>>EM ANÁLISE TÉCNICA (Com o perito/vistoriador da Marinha)</option>
                                    <option value="EM_EXIGENCIA" <?= $d['status'] === 'EM_EXIGENCIA' ? 'selected' : '' ?>>EM EXIGÊNCIA ⚠️ (Ofício de exigência expedido)</option>
                                    <option value="A_DISPOSICAO" <?= $d['status'] === 'A_DISPOSICAO' ? 'selected' : '' ?>>DOCUMENTO À DISPOSIÇÃO (Pronto para retirada no balcão)</option>
                                    <option value="RETIRADO" <?= $d['status'] === 'RETIRADO' ? 'selected' : '' ?>>RETIRADO (Documento definitivo retirado na Capitania)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Detalhes do Andamento / Despacho *</label>
                                <textarea class="form-control" name="andamento_observacao" rows="3" required placeholder="Ex.: Recebido Ofício nº 123/2026 solicitando ajuste na prancha de arranjo com prazo até 20/10/2026..."></textarea>
                                <small class="text-muted">Esta anotação ficará registrada com carimbo de data/hora e usuário na auditoria.</small>
                            </div>

                            <button type="submit" class="btn btn-secondary">
                                <i class="fa-solid fa-pen-to-square"></i> Registrar Andamento
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-secondary mb-0">
                            <strong>Dossiê Encerrado:</strong> Trâmite finalizado.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>
