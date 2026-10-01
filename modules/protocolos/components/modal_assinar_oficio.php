<?php
$usuarioLogadoId = $_SESSION['usuario_id'] ?? '';
$responsaveisAssinatura = $pdo->query("SELECT id, usuario_id, nome_completo, cargo_titulo, registro_profissional, assinatura_arquivo FROM responsaveis_assinatura WHERE ativo = 1 ORDER BY nome_completo")->fetchAll(PDO::FETCH_ASSOC);
$temLogadoVinculado = false;
foreach ($responsaveisAssinatura as $rCheck) {
    if (!empty($rCheck['usuario_id']) && $rCheck['usuario_id'] === $usuarioLogadoId) {
        $temLogadoVinculado = true;
        break;
    }
}
?>

<div class="modal fade" id="modal-assinar-oficio" tabindex="-1" aria-labelledby="modalAssinarOficioLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background: var(--bg-surface, #071f1b); border: 1px solid var(--accent, #56e0ad); box-shadow: 0 10px 30px rgba(0,0,0,0.6);">
            <div class="modal-header" style="border-bottom: 1px solid rgba(86, 224, 173, 0.2);">
                <h5 class="modal-title d-flex align-items-center gap-2 text-accent" id="modalAssinarOficioLabel">
                    <i class="fa-solid fa-file-signature"></i> Assinatura Digital do Ofício Oficial
                </h5>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" aria-label="Fechar">✕</button>
            </div>
            
            <form method="post" action="<?= APP_URL ?>protocolos/actions" id="form-assinar-oficio" onsubmit="return antesEnviarAssinaturaOficio()">
                <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                <input type="hidden" name="action" value="assinar_oficio">
                <input type="hidden" name="dossie_id" value="<?= h($id) ?>">
                <input type="hidden" name="alvo_tipo" id="modal_alvo_tipo" value="dossie">
                <input type="hidden" name="alvo_id" id="modal_alvo_id" value="<?= h($id) ?>">
                <input type="hidden" name="aba" class="input-aba-ativa" value="<?= h($abaAtiva ?? 'timeline') ?>">
                <input type="hidden" name="assinatura_imagem" id="modal_assinatura_imagem_input" value="">

                <div class="modal-body p-4">
                    <div class="prot-helper-box mb-3" style="background: rgba(86, 224, 173, 0.08); border: 1px solid var(--accent, #56e0ad);">
                        <i class="fa-solid fa-certificate text-accent fs-4 me-2"></i>
                        <div class="small">
                            <strong>Validade Jurídica e Normativa (NORMAM-202/DPC):</strong><br>
                            A assinatura digital aplicada a este ofício registra nome, cargo, rubrica oficial, data/hora e hash criptográfico SHA-256 no sistema, autenticando formalmente o encaminhamento perante a Capitania dos Portos.
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="modal_numero_oficio">
                                <i class="fa-solid fa-hashtag text-accent"></i> Número do Ofício
                            </label>
                            <input type="text" 
                                   name="numero_oficio" 
                                   id="modal_numero_oficio" 
                                   class="form-control" 
                                   placeholder="Ex: AM-OF020/2026" 
                                   required>
                            <small class="text-muted">Numeração oficial atribuída ao documento de encaminhamento.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="modal_destinatario_autoridade">
                                <i class="fa-solid fa-user-shield text-accent"></i> Destinatário A/C (Autoridade na Capitania)
                            </label>
                            <input type="text" 
                                   name="destinatario_autoridade" 
                                   id="modal_destinatario_autoridade" 
                                   class="form-control" 
                                   placeholder="Ex: CAPITÃO DE MAR E GUERRA – ALEXANDRE BATISTA PIMENTEL" 
                                   required>
                            <small class="text-muted">Posto e nome do Capitão dos Portos ou autoridade competente.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="modal_responsavel_select">
                            <i class="fa-solid fa-user-check text-accent"></i> Responsável que Assina o Ofício
                        </label>
                        <select class="form-control" id="modal_responsavel_select" name="responsavel_id" onchange="aoMudarResponsavelAssinatura(this)">
                            <option value="" data-nome="THAINARA BARROS" data-cargo="Secretária" <?= !$temLogadoVinculado ? 'selected' : '' ?>>
                                Thainara Barros — Secretária (Padrão Operacional)
                            </option>
                            <?php foreach ($responsaveisAssinatura as $resp): ?>
                                <?php $isLogado = (!empty($resp['usuario_id']) && $resp['usuario_id'] === $usuarioLogadoId); ?>
                                <option value="<?= (int)$resp['id'] ?>" 
                                        data-nome="<?= h($resp['nome_completo']) ?>" 
                                        data-cargo="<?= h($resp['cargo_titulo'] . ($resp['registro_profissional'] ? ' - ' . $resp['registro_profissional'] : '')) ?>"
                                        data-tem-imagem="<?= !empty($resp['assinatura_arquivo']) ? '1' : '0' ?>"
                                        <?= $isLogado ? 'selected' : '' ?>>
                                    <?= h($resp['nome_completo']) ?> — <?= h($resp['cargo_titulo']) ?> <?= $resp['registro_profissional'] ? '(' . h($resp['registro_profissional']) . ')' : '' ?> <?= $isLogado ? '★ (Você)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="outro" data-nome="" data-cargo="">
                                Outro Responsável / Digitar Manualmente
                            </option>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="modal_assinante_nome">Nome Completo do Assinante *</label>
                            <input type="text" name="assinante_nome" id="modal_assinante_nome" class="form-control" value="THAINARA BARROS" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="modal_assinante_cargo">Cargo / Função *</label>
                            <input type="text" name="assinante_cargo" id="modal_assinante_cargo" class="form-control" value="Secretária" required>
                        </div>
                    </div>

                    <!-- Opções de Rubrica e Assinatura -->
                    <div class="mb-3 p-3 rounded" style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border);">
                        <label class="form-label fw-bold d-block mb-2">
                            <i class="fa-solid fa-signature text-accent"></i> Rubrica / Assinatura Gráfica
                        </label>
                        
                        <div class="d-flex gap-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modo_rubrica" id="modo_rubrica_padrao" value="padrao" checked onchange="aoAlternarModoRubrica()">
                                <label class="form-check-label small" for="modo_rubrica_padrao">
                                    Usar Assinatura Cadastrada / Carimbo Digital Criptográfico
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modo_rubrica" id="modo_rubrica_desenho" value="desenhar" onchange="aoAlternarModoRubrica()">
                                <label class="form-check-label small" for="modo_rubrica_desenho">
                                    Desenhar Rubrica na Tela
                                </label>
                            </div>
                        </div>

                        <!-- Canvas de Desenho Manual (Opcional) -->
                        <div id="box-canvas-assinatura" style="display: none;" class="mt-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-secondary">Desenhe a rubrica com o mouse ou na tela sensível ao toque:</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="limparCanvasAssinatura()">Limpar</button>
                            </div>
                            <div style="border: 2px dashed var(--accent, #56e0ad); border-radius: 8px; background: #ffffff; width: 100%; height: 110px;">
                                <canvas id="canvas-assinatura-oficio" width="600" height="110" style="width: 100%; height: 100%; touch-action: none; cursor: crosshair;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="border-top: 1px solid rgba(86, 224, 173, 0.2);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-lg" style="background: #087653; border-color: #087653;">
                        <i class="fa-solid fa-certificate"></i> Concluir Assinatura Digital do Ofício
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let canvasOficio = null;
let ctxOficio = null;
let desenhandoOficio = false;
let teveDesenhoOficio = false;

function inicializarCanvasOficio() {
    canvasOficio = document.getElementById('canvas-assinatura-oficio');
    if (!canvasOficio) return;
    ctxOficio = canvasOficio.getContext('2d');
    ctxOficio.strokeStyle = '#002244';
    ctxOficio.lineWidth = 2.5;
    ctxOficio.lineCap = 'round';
    ctxOficio.lineJoin = 'round';

    function pos(e) {
        const rect = canvasOficio.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        const scaleX = canvasOficio.width / rect.width;
        const scaleY = canvasOficio.height / rect.height;
        return {
            x: (clientX - rect.left) * scaleX,
            y: (clientY - rect.top) * scaleY
        };
    }

    function comecar(e) {
        e.preventDefault();
        desenhandoOficio = true;
        teveDesenhoOficio = true;
        const p = pos(e);
        ctxOficio.beginPath();
        ctxOficio.moveTo(p.x, p.y);
    }
    function desenhar(e) {
        if (!desenhandoOficio) return;
        e.preventDefault();
        const p = pos(e);
        ctxOficio.lineTo(p.x, p.y);
        ctxOficio.stroke();
    }
    function parar(e) {
        if (desenhandoOficio) {
            e.preventDefault();
            desenhandoOficio = false;
        }
    }

    canvasOficio.addEventListener('mousedown', comecar);
    canvasOficio.addEventListener('mousemove', desenhar);
    canvasOficio.addEventListener('mouseup', parar);
    canvasOficio.addEventListener('mouseleave', parar);

    canvasOficio.addEventListener('touchstart', comecar, { passive: false });
    canvasOficio.addEventListener('touchmove', desenhar, { passive: false });
    canvasOficio.addEventListener('touchend', parar);
}

function limparCanvasAssinatura() {
    if (ctxOficio && canvasOficio) {
        ctxOficio.clearRect(0, 0, canvasOficio.width, canvasOficio.height);
        teveDesenhoOficio = false;
    }
    document.getElementById('modal_assinatura_imagem_input').value = '';
}

function aoAlternarModoRubrica() {
    const modoDesenho = document.getElementById('modo_rubrica_desenho')?.checked;
    const box = document.getElementById('box-canvas-assinatura');
    if (box) {
        box.style.display = modoDesenho ? 'block' : 'none';
        if (modoDesenho && !canvasOficio) {
            setTimeout(inicializarCanvasOficio, 100);
        }
    }
}

function aoMudarResponsavelAssinatura(sel) {
    const opt = sel.selectedOptions[0];
    if (!opt) return;
    const nome = opt.dataset.nome || '';
    const cargo = opt.dataset.cargo || '';
    if (nome) document.getElementById('modal_assinante_nome').value = nome;
    if (cargo) document.getElementById('modal_assinante_cargo').value = cargo;
}

function abrirModalAssinarOficio(tipo, idAlvo, destACAtual, numOficioAtual, assinanteNomeAtual, assinanteCargoAtual, respIdAtual) {
    document.getElementById('modal_alvo_tipo').value = tipo;
    document.getElementById('modal_alvo_id').value = idAlvo;

    const inputNum = document.getElementById('modal_numero_oficio');
    const inputAC = document.getElementById('modal_destinatario_autoridade');
    const inputNome = document.getElementById('modal_assinante_nome');
    const inputCargo = document.getElementById('modal_assinante_cargo');
    const selResp = document.getElementById('modal_responsavel_select');

    if (destACAtual && destACAtual.trim()) {
        inputAC.value = destACAtual;
    } else {
        inputAC.value = 'CAPITÃO DE MAR E GUERRA – ALEXANDRE BATISTA PIMENTEL';
    }

    if (numOficioAtual && numOficioAtual.trim()) {
        inputNum.value = numOficioAtual;
    } else {
        const anoAtual = new Date().getFullYear();
        inputNum.value = 'AM-OF020/' + anoAtual;
    }

    if (assinanteNomeAtual && assinanteNomeAtual.trim()) {
        inputNome.value = assinanteNomeAtual;
    } else {
        inputNome.value = 'THAINARA BARROS';
    }

    if (assinanteCargoAtual && assinanteCargoAtual.trim()) {
        inputCargo.value = assinanteCargoAtual;
    } else {
        inputCargo.value = 'Secretária';
    }

    if (respIdAtual && parseInt(respIdAtual) > 0) {
        selResp.value = respIdAtual;
    } else {
        selResp.value = '';
    }

    // Resetar rádio de rubrica para padrão
    document.getElementById('modo_rubrica_padrao').checked = true;
    aoAlternarModoRubrica();

    const modalEl = document.getElementById('modal-assinar-oficio');
    if (window.bootstrap && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    } else {
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
    }
}

function antesEnviarAssinaturaOficio() {
    const modoDesenho = document.getElementById('modo_rubrica_desenho')?.checked;
    if (modoDesenho && teveDesenhoOficio && canvasOficio) {
        document.getElementById('modal_assinatura_imagem_input').value = canvasOficio.toDataURL('image/png');
    }
    return true;
}
</script>
