<?php
/**
 * Componente: Histórico de Movimentações e Nova Entrada/Saída Guiada
 * Local: modules/protocolos/components/movimentacoes_historico.php
 *
 * Responsável pela visualização em linha do tempo das movimentações congeladas
 * com comprovantes oficiais (PDF com hash SHA-256) e pelo formulário guiado
 * de novas movimentações (ou retificações) com catálogo NORMAM e controle de custódia.
 */
?>

<!-- ============================================== -->
<!-- ABA 1: LINHA DO TEMPO & TRÂMITE               -->
<!-- ============================================== -->
<div id="pane-timeline" class="prot-tab-pane <?= $abaAtiva === 'timeline' ? 'active' : '' ?>">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="m-0" style="font-size: 1.15rem; color: var(--accent, #56e0ad);">
            <i class="fa-solid fa-clock-rotate-left"></i> Histórico Cronológico de Movimentações
        </h3>
        <?php if (!$somenteLeitura): ?>
            <button type="button" class="btn btn-sm btn-primary" onclick="trocarAbaDossie('movimentacao')">
                <i class="fa-solid fa-plus"></i> Registrar Nova Entrada/Saída
            </button>
        <?php endif; ?>
    </div>

    <?php if (!$movs): ?>
        <div class="prot-helper-box info">
            <i class="fa-solid fa-info-circle text-accent"></i> 
            <strong>Nenhuma movimentação registrada ainda.</strong><br>
            O primeiro passo operacional é registrar a <strong>Entrada</strong> dos documentos físicos entregues pelo cliente, armador ou despachante à Amazon Certificadora.
            <?php if (!$somenteLeitura): ?>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-primary" onclick="trocarAbaDossie('movimentacao')">
                        <i class="fa-solid fa-file-import"></i> Registrar Primeira Entrada
                    </button>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="prot-timeline-list">
            <?php foreach ($movs as $m): ?>
                <div class="prot-timeline-item" id="mov-<?= h($m['id']) ?>">
                    <div class="prot-timeline-dot">
                        <?= (int)$m['sequencia'] ?>
                    </div>
                    <div class="prot-timeline-card">
                        <div class="prot-timeline-header">
                            <div>
                                <span class="badge <?= $m['tipo'] === 'ENTRADA' ? 'bg-info' : 'bg-primary' ?> me-2">
                                    <?= $m['tipo'] === 'ENTRADA' ? '📥 ENTRADA' : '📤 SAÍDA' ?>
                                </span>
                                <strong class="fs-6"><?= h(str_replace('_', ' ', $m['natureza'])) ?></strong>
                                <span class="badge bg-secondary ms-2 small">Evento #<?= str_pad((string)$m['sequencia'], 2, '0', STR_PAD_LEFT) ?></span>
                                <?php if ($m['status'] === 'RASCUNHO'): ?>
                                    <span class="badge bg-warning text-dark ms-1">RASCUNHO (Não congelado)</span>
                                <?php elseif ($m['status'] === 'RETIFICADA'): ?>
                                    <span class="badge bg-danger ms-1">RETIFICADA</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-secondary small">
                                <i class="fa-regular fa-calendar"></i> <?= formatarDataCompleta($m['movimentado_em']) ?>
                            </div>
                        </div>

                        <div class="row g-2 mb-2 small">
                            <div class="col-md-6">
                                <span class="text-secondary">De (Origem):</span> 
                                <strong><?= h($m['origem_nome']) ?></strong> 
                                <span class="text-secondary">(<?= h($m['origem_tipo']) ?>)</span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-secondary">Para (Destino):</span> 
                                <strong><?= h($m['destino_nome']) ?></strong> 
                                <span class="text-secondary">(<?= h($m['destino_tipo']) ?>)</span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-secondary">Local & Envio:</span> 
                                <?= h($m['cidade'] . '/' . $m['uf']) ?> · <?= h($m['meio_envio']) ?>
                                <?php if ($m['codigo_rastreio']): ?>
                                    · Rastreio: <code><?= h($m['codigo_rastreio']) ?></code>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <span class="text-secondary">Registrado por:</span> 
                                <?= h($m['criador_nome'] ?: 'Sistema') ?>
                            </div>
                        </div>

                        <?php if ($m['observacoes']): ?>
                            <div class="small p-2 rounded mb-3" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border);">
                                <strong>Observações:</strong> <?= nl2br(h($m['observacoes'])) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Relação de Itens da Movimentação -->
                        <?php 
                        $qItens = $pdo->prepare("SELECT * FROM protocolo_movimentacao_itens WHERE movimentacao_id = :mid ORDER BY id ASC");
                        $qItens->execute([':mid' => $m['id']]);
                        $mItens = $qItens->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php if ($mItens): ?>
                            <div class="mb-3">
                                <span class="small fw-bold text-secondary d-block mb-1">
                                    <i class="fa-solid fa-files"></i> Documentos desta Movimentação (<?= count($mItens) ?>):
                                </span>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0" style="font-size: 0.82rem;">
                                        <thead>
                                            <tr class="text-secondary">
                                                <th>Item</th>
                                                <th>Suporte / Forma</th>
                                                <th>Qtd</th>
                                                <th>Revisão</th>
                                                <th>Custódia</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($mItens as $it): ?>
                                                <tr>
                                                    <td><strong><?= h($it['descricao']) ?></strong></td>
                                                    <td><?= h($it['suporte']) ?> · <?= h(str_replace('_', ' ', $it['forma'])) ?></td>
                                                    <td><?= (int)$it['quantidade'] ?></td>
                                                    <td><?= h($it['numero_revisao'] ?: '—') ?></td>
                                                    <td>
                                                        <?php if ($it['requer_devolucao']): ?>
                                                            <?php if ($it['devolvido_em']): ?>
                                                                <span class="badge bg-success">Devolvido</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning text-dark">Exige Devolução</span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Ações da Movimentação -->
                        <div class="d-flex flex-wrap gap-2 pt-2 border-top" style="border-color: var(--border) !important;">
                            <?php if (in_array($m['status'], ['CONFIRMADA', 'RETIFICADA'], true)): ?>
                                <a class="btn btn-sm btn-secondary" target="_blank" href="<?= APP_URL ?>protocolos/pdf?id=<?= urlencode($m['id']) ?>">
                                    <i class="fa-solid fa-file-pdf text-danger"></i> Comprovante Oficial (PDF)
                                </a>

                                <?php if ($m['aceite_existente']): ?>
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="abrirModalAceite('<?= h($m['aceite_existente']) ?>')">
                                        <i class="fa-solid fa-file-signature"></i> 
                                        <?= $m['aceite_data'] ? 'Aceite Assinado' : 'Ver Link de Aceite' ?>
                                    </button>
                                <?php elseif (!$somenteLeitura): ?>
                                    <form method="post" action="<?= APP_URL ?>protocolos/actions" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                                        <input type="hidden" name="action" value="criar_aceite">
                                        <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                                        <input type="hidden" name="movimentacao_id" value="<?= h($m['id']) ?>">
                                        <input type="hidden" name="aba" class="input-aba-ativa" value="timeline">
                                        <button type="submit" class="btn btn-sm btn-secondary">
                                            <i class="fa-solid fa-signature"></i> Gerar link de aceite
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($m['status'] === 'RASCUNHO' && !$somenteLeitura): ?>
                                <form method="post" action="<?= APP_URL ?>protocolos/actions" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                                    <input type="hidden" name="action" value="confirmar">
                                    <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                                    <input type="hidden" name="movimentacao_id" value="<?= h($m['id']) ?>">
                                    <input type="hidden" name="aba" class="input-aba-ativa" value="timeline">
                                    <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Atenção: Ao confirmar, este evento terá seu conteúdo congelado e será gerado o comprovante com código de autenticidade. Deseja prosseguir?')">
                                        <i class="fa-solid fa-lock"></i> Confirmar e Congelar Evento
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($m['status'] === 'CONFIRMADA' && !$somenteLeitura): ?>
                                <a class="btn btn-sm btn-secondary" href="<?= APP_URL ?>protocolos/form?id=<?= urlencode($id) ?>&retificar=<?= urlencode($m['id']) ?>&aba=movimentacao">
                                    <i class="fa-solid fa-rotate-left"></i> Retificar este Evento
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================== -->
<!-- ABA 2: NOVA MOVIMENTAÇÃO (ENTRADA / SAÍDA)     -->
<!-- ============================================== -->
<?php if (!$somenteLeitura): ?>
    <div id="pane-movimentacao" class="prot-tab-pane <?= $abaAtiva === 'movimentacao' ? 'active' : '' ?>">
        <section class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="m-0" style="font-size: 1.15rem; color: var(--accent, #56e0ad);">
                        <i class="fa-solid fa-plus-circle"></i> 
                        <?= $retificar ? 'Retificar Evento Confirmado' : 'Registrar Nova Movimentação' ?>
                    </h3>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="trocarAbaDossie('timeline')">Cancelar</button>
                </div>

                <?php if ($retificar): ?>
                    <div class="prot-helper-box warning">
                        <i class="fa-solid fa-triangle-exclamation text-warning"></i>
                        <strong>Modo de Retificação:</strong> A retificação criará um novo evento oficial com a devida correção. O evento anterior permanecerá gravado na trilha de auditoria e será marcado como retificado para garantia jurídica.
                    </div>
                <?php else: ?>
                    <div class="prot-helper-box info">
                        <i class="fa-solid fa-lightbulb text-accent"></i>
                        <strong>Como preencher o sentido da movimentação:</strong>
                        <ul class="mb-0">
                            <li><strong>Entrada:</strong> Selecione quando a Amazon Certificadora estiver <em>recebendo</em> documentos de clientes, estaleiros ou despachantes.</li>
                            <li><strong>Saída:</strong> Selecione quando a Amazon Certificadora estiver <em>enviando ou protocolando</em> na Capitania dos Portos ou <em>entregando</em> documentos de volta ao cliente.</li>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= APP_URL ?>protocolos/actions" id="form-movimentacao">
                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                    <input type="hidden" name="action" value="adicionar_movimentacao">
                    <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                    <input type="hidden" name="idempotency_key" value="<?= h(bin2hex(random_bytes(16))) ?>">
                    <input type="hidden" name="retifica_movimentacao_id" value="<?= h($retificar) ?>">
                    <input type="hidden" name="aba" class="input-aba-ativa" value="timeline">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="mov_tipo">Sentido da Movimentação *</label>
                            <select class="form-control" name="tipo" id="mov_tipo" required onchange="ajustarSentidoMovimentacao(this.value)">
                                <option value="ENTRADA">📥 ENTRADA — Amazon Certificadora recebe documentos</option>
                                <option value="SAIDA">📤 SAÍDA — Amazon Certificadora entrega / protocola</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="mov_natureza">Natureza Operacional *</label>
                            <select class="form-control" name="natureza" id="mov_natureza" required>
                                <option value="RECEBIMENTO_CLIENTE">Recebimento do Cliente / Representante</option>
                                <option value="ENVIO_ORGAO">Envio / Protocolo na Capitania dos Portos (Marinha)</option>
                                <option value="RETORNO_ORGAO">Retorno / Exigência Recebida da Marinha</option>
                                <option value="CUMPRIMENTO_EXIGENCIA">Cumprimento de exigência</option>
                                <option value="RETIRADA_ORGAO">Retirada de Documento Aprovado no Órgão</option>
                                <option value="ENTREGA_CLIENTE">Entrega Definitiva de Documentos ao Cliente</option>
                                <option value="TRANSFERENCIA_INTERNA">Transferência Interna entre Departamentos</option>
                                <option value="OUTRA">Outra Movimentação</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Origem (Quem entrega os documentos) *</label>
                            <div class="row g-2">
                                <div class="col-4">
                                    <select class="form-control" name="origem_tipo" id="origem_tipo">
                                        <option value="CLIENTE">Cliente</option>
                                        <option value="REPRESENTANTE">Representante</option>
                                        <option value="AMAZON_NAVAL">Amazon Naval</option>
                                        <option value="CAPITANIA">Capitania</option>
                                        <option value="DELEGACIA">Delegacia</option>
                                        <option value="AGENCIA">Agência</option>
                                        <option value="CORREIOS">Correios</option>
                                        <option value="TRANSPORTADORA">Transportadora</option>
                                        <option value="OUTRO">Outro</option>
                                    </select>
                                </div>
                                <div class="col-8">
                                    <input class="form-control" name="origem_nome" id="origem_nome" required 
                                           value="<?= h($d['cliente_nome'] ?: '') ?>" placeholder="Nome completo do emissor">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Destino (Quem recebe os documentos) *</label>
                            <div class="row g-2">
                                <div class="col-4">
                                    <select class="form-control" name="destino_tipo" id="destino_tipo">
                                        <option value="AMAZON_NAVAL" selected>Amazon Naval</option>
                                        <option value="CAPITANIA">Capitania</option>
                                        <option value="DELEGACIA">Delegacia</option>
                                        <option value="AGENCIA">Agência</option>
                                        <option value="CLIENTE">Cliente</option>
                                        <option value="REPRESENTANTE">Representante</option>
                                        <option value="CORREIOS">Correios</option>
                                        <option value="TRANSPORTADORA">Transportadora</option>
                                        <option value="OUTRO">Outro</option>
                                    </select>
                                </div>
                                <div class="col-8">
                                    <input class="form-control" name="destino_nome" id="destino_nome" required 
                                           value="Amazon Certificadora Naval" placeholder="Nome de quem recebe">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold" for="mov_unidade">Unidade Marítima (se houver envio ao órgão)</label>
                            <select class="form-control" name="unidade_maritima_id" id="mov_unidade" onchange="aoSelecionarUnidadeMaritima(this)">
                                <option value="">Selecione quando houver trâmite com a Marinha</option>
                                <?php foreach ($unidades as $u): ?>
                                    <option value="<?= h($u['id']) ?>" data-cidade="<?= h($u['cidade']) ?>" data-uf="<?= h($u['uf']) ?>" <?= $d['unidade_maritima_id'] === $u['id'] ? 'selected' : '' ?>>
                                        <?= h($u['nome'] . ' — ' . $u['cidade'] . '/' . $u['uf']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold" for="movimentado_em">Data e Hora do Evento *</label>
                            <input class="form-control" type="datetime-local" name="movimentado_em" id="movimentado_em" required value="<?= date('Y-m-d\TH:i') ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold" for="mov_cidade">Cidade *</label>
                            <input class="form-control" name="cidade" id="mov_cidade" required value="Belém">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold" for="mov_uf">UF *</label>
                            <input class="form-control" name="uf" id="mov_uf" maxlength="2" required value="PA">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label fw-bold" for="meio_envio">Meio de Envio / Transporte *</label>
                            <select class="form-control" name="meio_envio" id="meio_envio">
                                <option value="PRESENCIAL">Presencial (Balcão / Em mãos)</option>
                                <option value="CORREIOS">Correios (SEDEX / PAC / AR)</option>
                                <option value="PORTAL">Portal Digital SISAP / DPC</option>
                                <option value="EMAIL">E-mail Oficial</option>
                                <option value="TRANSPORTADORA">Transportadora Fluvial / Rodoviária</option>
                                <option value="MENSAGEIRO">Mensageiro / Despachante</option>
                                <option value="OUTRO">Outro</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold" for="portador_nome">Portador / Entregador</label>
                            <input class="form-control" name="portador_nome" id="portador_nome" placeholder="Ex.: João da Silva (Despachante)">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold" for="codigo_rastreio">Código de Rastreio (se houver)</label>
                            <input class="form-control" name="codigo_rastreio" id="codigo_rastreio" placeholder="Ex.: AA123456789BR">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-bold" for="protocolo_anterior_id">Vínculo com Evento</label>
                            <select class="form-control" name="protocolo_anterior_id" id="protocolo_anterior_id">
                                <option value="">Sem vínculo</option>
                                <?php foreach ($movs as $m): ?>
                                    <?php if ($m['status'] !== 'RASCUNHO'): ?>
                                        <option value="<?= h($m['id']) ?>">
                                            Evento #<?= str_pad((string)$m['sequencia'], 2, '0', STR_PAD_LEFT) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold" for="observacoes">Observações Técnicas / Operacionais</label>
                            <textarea class="form-control" name="observacoes" id="observacoes" rows="2" placeholder="Informações adicionais sobre o estado dos documentos, exigências verbais, prazos acordados..."></textarea>
                        </div>
                    </div>

                    <!-- Documentos da Movimentação -->
                    <div class="p-3 rounded mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid var(--border);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="m-0 text-accent" style="font-size: 1rem;">
                                <i class="fa-solid fa-list-check"></i> Relação de Documentos Apresentados
                            </h4>
                            <button class="btn btn-sm btn-secondary" type="button" id="btn-add-doc">
                                <i class="fa-solid fa-plus"></i> Adicionar Item
                            </button>
                        </div>
                        <p class="text-secondary small mb-3">Selecione documentos do catálogo naval ou descreva livremente. Marque o checkbox <strong>"Exige devolução"</strong> para qualquer via original que precise retornar ao cliente sob custódia.</p>

                        <!-- Atalhos rápidos para adicionar documentos frequentes -->
                        <div class="mb-3">
                            <span class="text-secondary small me-2"><i class="fa-solid fa-bolt text-accent"></i> Adicionar Rápido:</span>
                            <div class="d-inline-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarDocCatalogo('REQ_INTERESSADO')">+ Requerimento</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarDocCatalogo('ART')">+ ART / RRT</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarDocCatalogo('MEMORIAL_DESCRITIVO')">+ Memorial Descritivo</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarDocCatalogo('PLANO_ARRANJO_GERAL')">+ Arranjo Geral</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarDocCatalogo('PLANO_LINHAS')">+ Plano de Linhas</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarDocCatalogo('CALCULOS_ESTABILIDADE')">+ Estabilidade</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarDocCatalogo('PROCURACAO')">+ Procuração</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarDocCatalogo('DOCUMENTO_PROPRIEDADE')">+ Doc. Propriedade</button>
                            </div>
                        </div>

                        <div id="lista-docs-container"></div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa-solid fa-floppy-disk"></i> Salvar Movimentação para Conferência
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg" onclick="trocarAbaDossie('timeline')">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
<?php endif; ?>
