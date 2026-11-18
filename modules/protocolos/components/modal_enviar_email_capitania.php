<?php
/**
 * Modal de Envio Oficial de Ofício e Documentos por E-mail para a Capitania dos Portos
 * Local: modules/protocolos/components/modal_enviar_email_capitania.php
 */

$emailCapitaniaPadrao = $d['unidade_email'] ?? '';
$emailsAdicionaisCapitania = $d['unidade_emails_adicionais'] ?? '';
$emailCliente = $d['cliente_email'] ?? '';
$nomeCapitania = $d['unidade_nome'] ?: 'Capitania dos Portos';
$numeroOficioDoc = !empty($d['numero_oficio']) ? $d['numero_oficio'] : ('Ofício ' . ($d['numero'] ?? ''));
$assuntoPadrao = "Ofício {$numeroOficioDoc} - Encaminhamento de Documentos Navais - Embarcação " . ($d['embarcacao_nome'] ?? '');

$corpoPadrao = "À " . $nomeCapitania . "\n"
    . (!empty($d['destinatario_autoridade']) ? "A/C: " . $d['destinatario_autoridade'] . "\n\n" : "\n")
    . "Prezados Senhores,\n\n"
    . "Cumprimentando-os cordialmente, a Entidade Certificadora Amazon Naval vem por meio deste encaminhar os documentos técnicos oficiais da embarcação "
    . ($d['embarcacao_nome'] ?? '') . (!empty($d['registro']) ? " (Registro: " . $d['registro'] . ")" : "") . ", "
    . "em estrita conformidade com as diretrizes da DPC / Marinha do Brasil (NORMAM-202).\n\n"
    . "Em anexo, transmitimos o Ofício de Encaminhamento e o dossiê técnico digitalizado para autuação e arquivamento nesta Organização Militar.\n\n"
    . "Permanecemos à inteira disposição para quaisquer esclarecimentos técnicos que se façam necessários.\n\n"
    . "Atenciosamente,\n"
    . (!empty($d['assinante_nome']) ? $d['assinante_nome'] . "\n" . ($d['assinante_cargo'] ?: 'Secretária') . "\n" : "Secretaria Operacional Naval\n")
    . "Amazon Certificadora Naval\n"
    . "Telefone/WhatsApp: (91) 99111-2065 | E-mail: amazoncertificados@gmail.com";
?>

<div class="modal fade" id="modal-enviar-email-capitania" tabindex="-1" aria-labelledby="modalEnviarEmailCapitaniaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background: var(--bg-surface, #071f1b); border: 1px solid var(--accent, #56e0ad); box-shadow: 0 10px 30px rgba(0,0,0,0.6);">
            <div class="modal-header" style="border-bottom: 1px solid rgba(86, 224, 173, 0.2);">
                <h5 class="modal-title d-flex align-items-center gap-2 text-accent" id="modalEnviarEmailCapitaniaLabel">
                    <i class="fa-solid fa-paper-plane"></i> Enviar Documentos e Ofício por E-mail à Capitania
                </h5>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" aria-label="Fechar">✕</button>
            </div>

            <form method="post" action="<?= APP_URL ?>protocolos/actions" id="form-enviar-email-capitania" onsubmit="return confirmarEnvioEmailCapitania(this)">
                <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                <input type="hidden" name="action" value="enviar_email_capitania">
                <input type="hidden" name="dossie_id" value="<?= h($d['id']) ?>">
                <input type="hidden" name="aba" class="input-aba-ativa" value="<?= h($abaAtiva ?? 'timeline') ?>">

                <div class="modal-body p-4">
                    <div class="prot-helper-box mb-3" style="background: rgba(86, 224, 173, 0.08); border: 1px solid var(--accent, #56e0ad);">
                        <i class="fa-solid fa-envelope-circle-check text-accent fs-4 me-2"></i>
                        <div class="small">
                            <strong>Trâmite Oficial de Protocolo Eletrônico (NORMAM-202/DPC):</strong><br>
                            O sistema gerará o arquivo PDF em alta resolução, anexará à mensagem criptografada e registrará o comprovante de envio na linha do tempo do dossiê com data, hora, usuário e hash do documento.
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-bold" for="email_destinatario">
                                <i class="fa-solid fa-at text-accent"></i> E-mail Oficial da Capitania / Órgão *
                            </label>
                            <input type="email" 
                                   name="destinatario_email" 
                                   id="email_destinatario" 
                                   class="form-control" 
                                   value="<?= h($emailCapitaniaPadrao) ?>" 
                                   placeholder="Ex: cpaor.protocolo@marinha.mil.br" 
                                   required>
                            <small class="text-muted">Endereço eletrônico do setor de protocolo ou gabinete da Capitania.</small>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label fw-bold" for="email_destinatario_nome">
                                <i class="fa-solid fa-landmark text-accent"></i> Nome da Unidade / Destinatário
                            </label>
                            <input type="text" 
                                   name="destinatario_nome" 
                                   id="email_destinatario_nome" 
                                   class="form-control" 
                                   value="<?= h($nomeCapitania) ?>" 
                                   required>
                            <small class="text-muted">Órgão que receberá a documentação.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="email_cc">
                            <i class="fa-solid fa-copy text-accent"></i> E-mails em Cópia (CC)
                        </label>
                        <input type="text" 
                               name="emails_cc" 
                               id="email_cc" 
                               class="form-control" 
                               placeholder="Ex: despachante@email.com, secretaria@amazonnaval.com.br"
                               value="">
                        <div class="d-flex flex-wrap gap-2 mt-2 align-items-center">
                            <span class="small text-muted me-1">Atalhos rápidos:</span>
                            <?php if ($emailCliente): ?>
                                <button type="button" class="btn btn-xs btn-outline-info py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarEmailCC('<?= h(addslashes($emailCliente)) ?>')">
                                    <i class="fa-solid fa-plus"></i> Cliente (<?= h($emailCliente) ?>)
                                </button>
                            <?php endif; ?>
                            <?php if ($emailsAdicionaisCapitania): ?>
                                <button type="button" class="btn btn-xs btn-outline-info py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarEmailCC('<?= h(addslashes($emailsAdicionaisCapitania)) ?>')">
                                    <i class="fa-solid fa-plus"></i> Setores Adicionais Capitania
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-xs btn-outline-info py-0 px-2" style="font-size: 0.75rem;" onclick="adicionarEmailCC('amazoncertificados@gmail.com')">
                                <i class="fa-solid fa-plus"></i> Cópia Secretaria Amazon
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1">Separe múltiplos e-mails por vírgula.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="email_assunto">
                            <i class="fa-solid fa-tag text-accent"></i> Assunto do E-mail *
                        </label>
                        <input type="text" 
                               name="assunto" 
                               id="email_assunto" 
                               class="form-control" 
                               value="<?= h($assuntoPadrao) ?>" 
                               required>
                        <small class="text-muted">Assunto padronizado para localização rápida no protocolo militar.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="fa-solid fa-file-pdf text-accent"></i> Documento(s) Anexo(s) a Enviar *
                        </label>
                        <div class="p-3 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(86, 224, 173, 0.2);">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="tipo_anexo" id="anexo_unificado" value="unificado" checked>
                                <label class="form-check-label fw-bold text-accent" for="anexo_unificado">
                                    <i class="fa-solid fa-file-circle-check"></i> Dossiê Completo Unificado (Recomendado)
                                </label>
                                <div class="small text-muted ms-4">
                                    Gera um único arquivo PDF oficial contendo o <strong>Ofício de Encaminhamento</strong> na folha 1, seguido de todos os relatórios de vistoria, pareceres e certificados emitidos.
                                </div>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_anexo" id="anexo_oficio_apenas" value="oficio">
                                <label class="form-check-label" for="anexo_oficio_apenas">
                                    <i class="fa-solid fa-file-lines"></i> Somente o Ofício de Encaminhamento (PDF Página Única)
                                </label>
                                <div class="small text-muted ms-4">
                                    Envia estritamente a folha do ofício formal para protocolo simples.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="email_mensagem">
                            <i class="fa-solid fa-align-left text-accent"></i> Mensagem do E-mail
                        </label>
                        <textarea name="mensagem_corpo" 
                                  id="email_mensagem" 
                                  class="form-control" 
                                  rows="7" 
                                  style="font-family: monospace; font-size: 0.85rem;"><?= h($corpoPadrao) ?></textarea>
                        <small class="text-muted">Texto formal transmitido no corpo do e-mail.</small>
                    </div>
                </div>

                <div class="modal-footer" style="border-top: 1px solid rgba(86, 224, 173, 0.2);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btn-submit-envio-email" style="background: #087653; border-color: #087653;">
                        <i class="fa-solid fa-paper-plane"></i> Disparar E-mail com Documentos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalEnviarEmailCapitania() {
    const modalEl = document.getElementById('modal-enviar-email-capitania');
    if (!modalEl) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function adicionarEmailCC(email) {
    const input = document.getElementById('email_cc');
    if (!input) return;
    let atual = input.value.trim();
    if (!atual) {
        input.value = email;
    } else {
        const lista = atual.split(',').map(s => s.trim());
        if (!lista.includes(email)) {
            lista.push(email);
            input.value = lista.join(', ');
        }
    }
}

function confirmarEnvioEmailCapitania(form) {
    const dest = document.getElementById('email_destinatario').value.trim();
    if (!dest) {
        alert('Por favor, informe o e-mail oficial da Capitania.');
        return false;
    }
    const btn = document.getElementById('btn-submit-envio-email');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Gerando PDF e Enviando...';
    }
    return true;
}
</script>
