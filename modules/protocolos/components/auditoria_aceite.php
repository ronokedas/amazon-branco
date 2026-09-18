<?php
/**
 * Componente: Auditoria Criptográfica, Anexos Digitais e Aceite Digital
 * Local: modules/protocolos/components/auditoria_aceite.php
 *
 * Responsável pelo repositório de arquivos digitais com hash SHA-256,
 * pela trilha cronológica imutável de auditoria, pelo encerramento/cancelamento
 * e pelos fluxos de aceite digital externo via smartphone.
 */
?>

<!-- ============================================== -->
<!-- ABA 5: ANEXOS & DOCUMENTOS DIGITAIS            -->
<!-- ============================================== -->
<div id="pane-anexos" class="prot-tab-pane <?= $abaAtiva === 'anexos' ? 'active' : '' ?>">
    <section class="card mb-4">
        <div class="card-body">
            <h3 style="font-size: 1.15rem; color: var(--accent, #56e0ad);" class="mb-3">
                <i class="fa-solid fa-paperclip"></i> Repositório de Documentos & Anexos Digitais
            </h3>
            <p class="text-secondary small">Arquivos digitais (pranchas navais, memoriais de cálculo, comprovantes de protocolo SISAP, recibos ou fotos) protegidos com hash criptográfico SHA-256 para integridade e fé pública.</p>

            <?php if (!$somenteLeitura): ?>
                <form method="post" enctype="multipart/form-data" action="<?= APP_URL ?>protocolos/actions" class="mb-4 p-3 rounded" style="background: rgba(255,255,255,0.02); border: 1px solid var(--border);">
                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                    <input type="hidden" name="action" value="anexar_documentos">
                    <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                    <input type="hidden" name="aba" class="input-aba-ativa" value="anexos">

                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Vincular a um Evento Específico (Opcional)</label>
                            <select class="form-control" name="movimentacao_id">
                                <option value="">Vincular ao dossiê geral</option>
                                <?php foreach ($movs as $m): ?>
                                    <?php if ($m['status'] !== 'RASCUNHO'): ?>
                                        <option value="<?= h($m['id']) ?>">
                                            Evento #<?= str_pad((string)$m['sequencia'], 2, '0', STR_PAD_LEFT) ?> — <?= h(str_replace('_', ' ', $m['natureza'])) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Selecionar Arquivos (PDF, JPG, PNG)</label>
                            <input class="form-control" type="file" required multiple name="documentos[]" accept=".pdf,.jpg,.jpeg,.png">
                        </div>

                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="fa-solid fa-upload"></i> Fazer Upload
                            </button>
                        </div>
                    </div>
                    <small class="text-secondary d-block mt-2">Envie até 10 arquivos simultâneos, com no máximo 15 MB cada.</small>
                </form>
            <?php endif; ?>

            <?php if (!$documentosAnexados): ?>
                <div class="text-center py-4 text-secondary">
                    <i class="fa-regular fa-file fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0">Nenhum arquivo digital anexado a este dossiê.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0" style="font-size: 0.88rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border);">
                                <th>Arquivo</th>
                                <th>Vínculo</th>
                                <th>Tamanho</th>
                                <th>Enviado por / Data</th>
                                <th>Integridade (SHA-256)</th>
                                <th style="text-align: right;">Download</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documentosAnexados as $a): ?>
                                <tr style="border-bottom: 1px solid var(--border); vertical-align: middle;">
                                    <td>
                                        <a target="_blank" href="<?= APP_URL ?>protocolos/arquivo?id=<?= urlencode($a['id']) ?>" class="fw-semibold text-accent">
                                            <i class="fa-regular fa-file-pdf"></i> <?= h($a['nome_original']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?= $a['movimentacao_sequencia'] ? '<span class="badge bg-secondary">Evento #' . str_pad((string)$a['movimentacao_sequencia'], 2, '0', STR_PAD_LEFT) . '</span>' : '<span class="badge bg-dark">Dossiê Geral</span>' ?>
                                    </td>
                                    <td><?= number_format((int)$a['tamanho_bytes'] / 1024, 1, ',', '.') ?> KB</td>
                                    <td>
                                        <?= h($a['criador_nome'] ?: 'Sistema') ?><br>
                                        <small class="text-secondary"><?= formatarDataCompleta($a['criado_em']) ?></small>
                                    </td>
                                    <td>
                                        <code style="font-size: 0.72rem;"><?= substr($a['sha256'], 0, 16) ?>...</code>
                                    </td>
                                    <td style="text-align: right;">
                                        <a class="btn btn-sm btn-secondary" target="_blank" href="<?= APP_URL ?>protocolos/arquivo?id=<?= urlencode($a['id']) ?>" download>
                                            <i class="fa-solid fa-download"></i> Baixar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- ============================================== -->
<!-- ABA 6: AUDITORIA & ENCERRAMENTO               -->
<!-- ============================================== -->
<div id="pane-auditoria" class="prot-tab-pane <?= $abaAtiva === 'auditoria' ? 'active' : '' ?>">
    <div class="row g-4">
        <div class="col-md-8">
            <section class="card">
                <div class="card-body">
                    <h3 style="font-size: 1.15rem; color: var(--accent, #56e0ad);" class="mb-3">
                        <i class="fa-solid fa-shield-halved"></i> Trilha de Auditoria Criptográfica
                    </h3>
                    <p class="text-secondary small">Registro cronológico imutável de todas as operações e transições de status realizadas neste processo naval.</p>

                    <div style="max-height: 480px; overflow-y: auto; padding-right: 8px;">
                        <?php foreach ($auditoria as $a): ?>
                            <div class="p-2 mb-2 rounded" style="background: rgba(255,255,255,0.02); border-left: 3px solid var(--accent); font-size: 0.84rem;">
                                <div class="d-flex justify-content-between">
                                    <strong><?= h($auditoriaLabels[$a['evento']] ?? str_replace('_', ' ', $a['evento'])) ?></strong>
                                    <span class="text-secondary small"><?= formatarDataCompleta($a['criado_em']) ?></span>
                                </div>
                                <div class="text-secondary small">
                                    Operador: <?= h($a['usuario_nome'] ?: 'Acesso Externo / Público') ?> · IP: <?= h($a['ip'] ?: '—') ?>
                                </div>
                                <?php if ($a['detalhe']): ?>
                                    <div class="mt-1 small" style="color: var(--text-primary);"><?= h($a['detalhe']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-md-4">
            <section class="card">
                <div class="card-body">
                    <h3 style="font-size: 1.15rem; color: var(--accent, #56e0ad);" class="mb-3">
                        <i class="fa-solid fa-flag-checkered"></i> Conclusão do Processo
                    </h3>

                    <?php if (!$somenteLeitura): ?>
                        <p class="text-secondary small">Encerre este dossiê quando todo o trâmite tiver sido finalizado com sucesso e todos os originais devolvidos ao armador.</p>

                        <form method="post" action="<?= APP_URL ?>protocolos/actions" class="mb-3">
                            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                            <input type="hidden" name="action" value="encerrar">
                            <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                            <input type="hidden" name="aba" class="input-aba-ativa" value="auditoria">
                            <button type="submit" class="btn btn-success w-100 mb-2" onclick="return confirm('Confirma o encerramento do dossiê? Os dados serão congelados como finalizados.')">
                                <i class="fa-solid fa-check-circle"></i> Encerrar Dossiê (Concluído)
                            </button>
                        </form>

                        <hr style="border-color: var(--border);">

                        <p class="text-secondary small">Em caso de desistência formal ou cancelamento do processo documental:</p>
                        <form method="post" action="<?= APP_URL ?>protocolos/actions" onsubmit="return confirm('Atenção: Deseja realmente cancelar este dossiê? Esta ação é irreversível.')">
                            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                            <input type="hidden" name="action" value="cancelar">
                            <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                            <input type="hidden" name="aba" class="input-aba-ativa" value="auditoria">
                            <div class="mb-2">
                                <input class="form-control form-control-sm" name="motivo" required placeholder="Motivo obrigatório do cancelamento...">
                            </div>
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                <i class="fa-solid fa-ban"></i> Cancelar Dossiê
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="badge bg-secondary p-2 w-100 text-center">
                            Dossiê <?= h($labels[$d['status']] ?? $d['status']) ?> em <?= formatarDataCompleta($d['atualizado_em']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>
