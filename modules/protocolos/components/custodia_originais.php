<?php
/**
 * Componente: Termo e Controle de Custódia de Documentos Físicos Originais
 * Local: modules/protocolos/components/custodia_originais.php
 *
 * Responsável pelo controle rigoroso da guarda e devolução de documentos originais
 * (escrituras públicas, notas fiscais, memoriais assinados, TIE/TIEM) confiados
 * à Amazon Certificadora Naval, com registro formal de baixa e emissão de recibo.
 */
?>

<div id="pane-custodia" class="prot-tab-pane <?= $abaAtiva === 'custodia' ? 'active' : '' ?>">
    <section class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--accent, #56e0ad);" class="m-0">
                        <i class="fa-solid fa-box-archive"></i> Controle de Custódia de Documentos Originais
                    </h3>
                    <small class="text-secondary">Termo formal de guarda provisória de originais do cliente com comprovação de devolução.</small>
                </div>
                <?php if ($originais): ?>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="window.print()">
                        <i class="fa-solid fa-print"></i> Imprimir Relação para Arquivo Físico
                    </button>
                <?php endif; ?>
            </div>

            <p class="text-secondary small">
                Documentos físicos originais confiados pelo armador/cliente (ex.: escrituras públicas, notas fiscais originais dos motores, memoriais com firma reconhecida, vias de plantas navais) que permanecem sob guarda da Amazon Certificadora Naval e exigem devolução formal com registro de recebimento.
            </p>

            <?php if (!$originais): ?>
                <div class="text-center py-4 text-secondary">
                    <i class="fa-solid fa-box-open fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0">Nenhum documento original sob custódia registrado neste dossiê.</p>
                    <small>Para registrar custódia, marque o campo <strong>"Exige devolução"</strong> ao adicionar itens em uma movimentação de entrada.</small>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border);">
                                <th>Documento Original</th>
                                <th>Evento de Entrada</th>
                                <th>Qtd</th>
                                <th>Condição / Revisão</th>
                                <th>Status da Custódia</th>
                                <th style="text-align: right;">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($originais as $o): ?>
                                <tr style="border-bottom: 1px solid var(--border); vertical-align: middle;">
                                    <td>
                                        <strong><?= h($o['descricao']) ?></strong>
                                        <?php if ($o['categoria'] ?? ''): ?>
                                            <div class="text-secondary small"><?= h($o['categoria']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">Evento #<?= str_pad((string)$o['sequencia'], 2, '0', STR_PAD_LEFT) ?></span>
                                    </td>
                                    <td><?= (int)$o['quantidade'] ?></td>
                                    <td><?= h($o['condicao_documento'] ?: ($o['numero_revisao'] ?: 'Conferido')) ?></td>
                                    <td>
                                        <?php if ($o['devolvido_em']): ?>
                                            <span class="badge bg-success">
                                                <i class="fa-solid fa-check"></i> Devolvido em <?= formatarDataCompleta($o['devolvido_em']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fa-solid fa-lock"></i> Sob Custódia da Amazon Naval
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if (!$o['devolvido_em'] && !$somenteLeitura): ?>
                                            <form method="post" action="<?= APP_URL ?>protocolos/actions" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                                                <input type="hidden" name="action" value="registrar_devolucao">
                                                <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                                                <input type="hidden" name="item_id" value="<?= h($o['id']) ?>">
                                                <input type="hidden" name="aba" class="input-aba-ativa" value="custodia">
                                                <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Confirma a devolução deste documento original ao cliente/representante?')">
                                                    <i class="fa-solid fa-hand-holding-hand"></i> Dar Baixa na Devolução
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">Baixado</span>
                                        <?php endif; ?>
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
