/**
 * MÓDULO: VISTORIAS
 * Arquivo: relatorio.js - Controlador de frontend para relatório técnico de vistoria
 */

// Configurações e tokens injetados pelo backend
const vistoriaConfig = window.ERP_VISTORIA_CONFIG || {};
const appUrl = vistoriaConfig.appUrl || '';

// ========================================================
// PRESERVAÇÃO DE RASCUNHO LOCAL
// ========================================================
(function preservarRascunhoRelatorio() {
    const form = document.getElementById('formRelatorio');
    if (!form || typeof localStorage === 'undefined') return;

    const draftKey = vistoriaConfig.draftKey || ('erp:relatorio:rascunho:' + (vistoriaConfig.agendamentoId || '') + ':' + (vistoriaConfig.vistoriaId || 'novo'));
    const ignorados = new Set(['csrf_token', 'formulario_completo', 'agendamento_id', 'vistoria_id', 'status_vistoria']);
    const status = document.getElementById('rascunhoRelatorioStatus');

    function controlesPersistiveis() {
        return Array.from(form.elements).filter(function(campo) {
            return campo.name && !ignorados.has(campo.name) && !['file', 'submit', 'button', 'password'].includes(campo.type);
        });
    }

    function salvar() {
        try {
            const ocorrencias = Object.create(null);
            const campos = controlesPersistiveis().map(function(campo) {
                const indice = ocorrencias[campo.name] || 0;
                ocorrencias[campo.name] = indice + 1;
                return {
                    nome: campo.name,
                    indice: indice,
                    valor: campo.value,
                    marcado: campo.type === 'checkbox' || campo.type === 'radio' ? campo.checked : null
                };
            });
            localStorage.setItem(draftKey, JSON.stringify({campos: campos, salvoEm: Date.now()}));
            if (status) status.innerHTML = '<i class="fas fa-cloud-arrow-down"></i> Preenchimento preservado automaticamente.';
        } catch (e) {
            // O formulário continua funcionando normalmente se o armazenamento local estiver indisponível.
        }
    }

    function restaurar() {
        try {
            const bruto = localStorage.getItem(draftKey);
            if (!bruto) return;
            const rascunho = JSON.parse(bruto);
            const totalAvulsasSalvas = (rascunho.campos || []).filter(function(campo) {
                return campo.nome === 'exigencia_descricao[]';
            }).length;
            let totalAvulsasAtuais = form.querySelectorAll('[name="exigencia_descricao[]"]').length;
            while (totalAvulsasAtuais < totalAvulsasSalvas && typeof adicionarLinhaAvulsa === 'function') {
                adicionarLinhaAvulsa();
                totalAvulsasAtuais++;
            }
            const porChave = new Map((rascunho.campos || []).map(function(campo) {
                return [campo.nome + '::' + campo.indice, campo];
            }));
            const ocorrencias = Object.create(null);
            controlesPersistiveis().forEach(function(campo) {
                const indice = ocorrencias[campo.name] || 0;
                ocorrencias[campo.name] = indice + 1;
                const salvo = porChave.get(campo.name + '::' + indice);
                if (!salvo) return;
                if (campo.type === 'checkbox' || campo.type === 'radio') campo.checked = !!salvo.marcado;
                else campo.value = salvo.valor;
            });
            form.querySelectorAll('.checklist-item').forEach(function(item) {
                const itemId = item.dataset.id;
                const valor = document.getElementById('status_' + itemId)?.value || '';
                item.querySelectorAll('.btn-toggle').forEach(function(botao) { botao.classList.remove('active'); });
                const seletor = valor === 'CONFORME' ? '.btn-toggle.conforme'
                    : (valor === 'NAO_CONFORME' ? '.btn-toggle.nao-conforme'
                    : (valor === 'NAO_SE_APLICA' ? '.btn-toggle.na' : ''));
                if (seletor) item.querySelector(seletor)?.classList.add('active');
                const detalhes = document.getElementById('details_' + itemId);
                if (detalhes) detalhes.style.display = valor === 'NAO_CONFORME' ? 'block' : 'none';
                const asOculto = document.getElementById('sem_prazo_' + itemId);
                const asCheck = item.querySelector('.checklist-sem-prazo');
                if (asOculto && asCheck) asCheck.checked = asOculto.value === '1';
            });
            if (typeof atualizarContadoresChecklist === 'function') atualizarContadoresChecklist();
            if (status) status.innerHTML = '<i class="fas fa-rotate-left"></i> Preenchimento anterior restaurado.';
        } catch (e) {
            localStorage.removeItem(draftKey);
        }
    }

    const salvoComSucesso = new URLSearchParams(window.location.search).get('salvo') === '1';
    if (salvoComSucesso) {
        localStorage.removeItem(draftKey);
        const urlLimpa = new URL(window.location.href);
        urlLimpa.searchParams.delete('salvo');
        window.history.replaceState({}, '', urlLimpa.toString());
    } else window.setTimeout(restaurar, 0);

    let temporizador = null;
    function agendarSalvamento() {
        window.clearTimeout(temporizador);
        temporizador = window.setTimeout(salvar, 120);
    }
    form.addEventListener('input', agendarSalvamento);
    form.addEventListener('change', salvar);
    form.addEventListener('click', function() { window.setTimeout(salvar, 0); });
    form.addEventListener('submit', salvar);
})();

// ========================================================
// CAMPOS DE REESCRITA (RELATÓRIO DE CUMPRIMENTO)
// ========================================================
function atualizarCamposReescrita() {
    const envioFinal = document.getElementById('status_vistoria')?.value === 'AGUARDANDO_APROVACAO';
    document.querySelectorAll('.cumprimento-status').forEach(function(select) {
        const bloco = document.querySelector(`[data-reescrita-id="${select.dataset.exigenciaId}"]`);
        if (!bloco) return;
        const parcial = select.value === 'cumprida_parcial_reescrita';
        bloco.hidden = !parcial;
        const campo = bloco.querySelector('textarea');
        if (campo) campo.required = parcial && envioFinal;
    });
}
document.querySelectorAll('.cumprimento-status').forEach(function(select) {
    select.addEventListener('change', atualizarCamposReescrita);
});
document.getElementById('status_vistoria')?.addEventListener('change', atualizarCamposReescrita);
document.getElementById('formRelatorio')?.addEventListener('submit', atualizarCamposReescrita);
atualizarCamposReescrita();

// ========================================================
// ATUALIZADOR DE CONTADORES DO CHECKLIST NORMAM
// ========================================================
function atualizarContadoresChecklist() {
    let total = 0, respondidos = 0, conformes = 0, naoConformes = 0, totalAS = 0, naoSeAplica = 0;

    document.querySelectorAll('.checklist-section').forEach(function(section) {
        let catRespondidos = 0, catExigencias = 0, catAS = 0;

        const itens = section.querySelectorAll('.checklist-item');
        total += itens.length;
        itens.forEach(function(item) {
            const status = document.getElementById('status_' + item.dataset.id)?.value || '';

            if (status !== '') {
                respondidos++;
                catRespondidos++;
                if (status === 'CONFORME') {
                    conformes++;
                } else if (status === 'NAO_SE_APLICA') {
                    naoSeAplica++;
                }
            }
            if (status === 'NAO_CONFORME') {
                naoConformes++;
                catExigencias++;
                const semPrazo = document.getElementById('sem_prazo_' + item.dataset.id);
                if (semPrazo?.value === '1') {
                    totalAS++;
                    catAS++;
                }
            }
        });

        const contadorRespondidos = section.querySelector('[data-counter="respondidos"]');
        const contadorExigencias = section.querySelector('[data-counter="exigencias"]');
        const contadorAS = section.querySelector('[data-counter="as"]');

        if (contadorRespondidos) contadorRespondidos.textContent = String(catRespondidos);
        if (contadorExigencias) contadorExigencias.textContent = String(catExigencias);
        if (contadorAS) contadorAS.textContent = String(catAS);

        section.querySelector('[data-badge="exigencias"]')?.classList.toggle('is-zero', catExigencias === 0);
        section.querySelector('[data-badge="as"]')?.classList.toggle('is-hidden', catAS === 0);
    });

    const resumoRespondidos = document.getElementById('checklistRespondidos');
    const resumoConformes = document.getElementById('checklistConformes');
    const resumoPendentes = document.getElementById('checklistPendentes');
    const resumoNaoConformes = document.getElementById('checklistNaoConformes');
    const resumoAS = document.getElementById('checklistAS');

    const pendentes = Math.max(0, total - respondidos);

    if (resumoRespondidos) resumoRespondidos.textContent = respondidos + ' / ' + total;
    if (resumoConformes) resumoConformes.textContent = String(conformes);
    if (resumoPendentes) resumoPendentes.textContent = String(pendentes);
    if (resumoNaoConformes) resumoNaoConformes.textContent = String(naoConformes);
    if (resumoAS) resumoAS.textContent = String(totalAS);

    // Atualiza contadores nas abas de filtros rápidos (Pills)
    const pillPendentes = document.getElementById('pillPendentes');
    const pillConformes = document.getElementById('pillConformes');
    const pillNaoConformes = document.getElementById('pillNaoConformes');
    const pillNA = document.getElementById('pillNA');
    if (pillPendentes) pillPendentes.textContent = String(pendentes);
    if (pillConformes) pillConformes.textContent = String(conformes);
    if (pillNaoConformes) pillNaoConformes.textContent = String(naoConformes);
    if (pillNA) pillNA.textContent = String(naoSeAplica);
}

// Toggle Accordions
function toggleSection(id, headerButton) {
    const body = document.getElementById(id);
    const header = headerButton || body.previousElementSibling;
    const icon = header.querySelector('.icone-toggle');
    if (body.style.display === 'block') {
        body.style.display = 'none';
        header.setAttribute('aria-expanded', 'false');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    } else {
        body.style.display = 'block';
        header.setAttribute('aria-expanded', 'true');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    }
}

window.scrollToTopChecklist = function() {
    const target = document.querySelector('.checklist-toolbar') || document.querySelector('#checklist-container') || document.querySelector('.checklist-editor-section');
    if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

// Checklist Item Status
function setStatus(itemId, status, btnElement) {
    document.getElementById('status_' + itemId).value = status;

    const parent = btnElement.closest('.item-actions');
    parent.querySelectorAll('.btn-toggle').forEach(b => b.classList.remove('active'));
    btnElement.classList.add('active');

    const detailsDiv = document.getElementById('details_' + itemId);
    if (status === 'NAO_CONFORME') {
        detailsDiv.style.display = 'block';
        const obsInput = detailsDiv.querySelector('input[name="checklist_observacao[]"]');
        if(obsInput) obsInput.focus();
    } else {
        detailsDiv.style.display = 'none';
        document.getElementById('obs_' + itemId).value = '';
        const semPrazoInput = document.getElementById('sem_prazo_' + itemId);
        if (semPrazoInput) semPrazoInput.value = '0';
        const semPrazoCheck = detailsDiv.querySelector('.checklist-sem-prazo');
        if (semPrazoCheck) semPrazoCheck.checked = false;
    }

    const fotoBox = document.getElementById('foto_box_' + itemId);
    if (fotoBox) {
        if (status === 'CONFORME' || status === 'NAO_CONFORME') {
            fotoBox.style.display = 'block';
        } else {
            fotoBox.style.display = 'none';
        }
    }

    atualizarContadoresChecklist();
}

// ========================================================
// GESTÃO DE MÚLTIPLAS FOTOS ACUMULADAS POR EXIGÊNCIA
// ========================================================
window.checklistArquivos = window.checklistArquivos || {};

function abrirCameraItem(itemId) {
    const input = document.getElementById('foto_camera_' + itemId);
    if (input) {
        input.value = '';
        input.click();
    }
}

function abrirGaleriaItem(itemId) {
    const input = document.getElementById('foto_galeria_' + itemId);
    if (input) {
        input.value = '';
        input.click();
    }
}

function abrirSeletorFotos(itemId) {
    abrirCameraItem(itemId);
}

function adicionarFotosItem(itemId, input) {
    if (!input.files || input.files.length === 0) return;

    if (!window.checklistArquivos[itemId]) {
        window.checklistArquivos[itemId] = [];
    }

    for (let i = 0; i < input.files.length; i++) {
        const file = input.files[i];
        window.checklistArquivos[itemId].push(file);
    }

    sincronizarInputFiles(itemId);
    renderizarPreviewsNovasFotos(itemId);
    atualizarContadorFotosItem(itemId);
    atualizarContadoresChecklist();
}

function sincronizarInputFiles(itemId) {
    const input = document.getElementById('foto_input_' + itemId);
    if (!input) return;

    try {
        const dt = new DataTransfer();
        const files = window.checklistArquivos[itemId] || [];
        files.forEach(f => dt.items.add(f));
        input.files = dt.files;
    } catch (e) {
        console.warn('DataTransfer não suportado diretamente:', e);
    }
}

function removerNovaFotoItem(itemId, fileIndex) {
    if (!window.checklistArquivos[itemId]) return;
    window.checklistArquivos[itemId].splice(fileIndex, 1);
    sincronizarInputFiles(itemId);
    renderizarPreviewsNovasFotos(itemId);
    atualizarContadorFotosItem(itemId);
    atualizarContadoresChecklist();
}

function renderizarPreviewsNovasFotos(itemId) {
    const previewContainer = document.getElementById('foto_preview_' + itemId);
    if (!previewContainer) return;

    previewContainer.innerHTML = '';
    const files = window.checklistArquivos[itemId] || [];

    files.forEach((file, idx) => {
        const thumbDiv = document.createElement('div');
        thumbDiv.className = 'foto-thumb-card nova-foto';
        thumbDiv.style.cssText = 'position: relative; border: 2px solid #0284c7; border-radius: 6px; overflow: hidden; width: 68px; height: 68px; background: #0f172a; flex-shrink: 0;';
        thumbDiv.title = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';

        const img = document.createElement('img');
        img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
        img.alt = file.name;

        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
        thumbDiv.appendChild(img);

        const btnRemove = document.createElement('button');
        btnRemove.type = 'button';
        btnRemove.innerHTML = '<i class="fa-solid fa-times"></i>';
        btnRemove.title = 'Remover esta foto';
        btnRemove.style.cssText = 'position: absolute; top: 2px; right: 2px; width: 20px; height: 20px; border-radius: 50%; background: rgba(220, 38, 38, 0.95); color: #fff; border: none; font-size: 11px; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.5);';
        btnRemove.onclick = function(e) {
            e.stopPropagation();
            removerNovaFotoItem(itemId, idx);
        };
        thumbDiv.appendChild(btnRemove);

        const tagNova = document.createElement('span');
        tagNova.textContent = 'Nova';
        tagNova.style.cssText = 'position: absolute; bottom: 0; left: 0; right: 0; background: rgba(2, 132, 199, 0.9); color: #fff; font-size: 9px; text-align: center; font-weight: 700; line-height: 14px;';
        thumbDiv.appendChild(tagNova);

        previewContainer.appendChild(thumbDiv);
    });
}

function atualizarContadorFotosItem(itemId) {
    const input = document.getElementById('foto_input_' + itemId);
    const countSpan = document.getElementById('foto_count_' + itemId);
    const badgeSpan = document.getElementById('foto_badge_' + itemId);
    const existDiv = document.getElementById('fotos_existentes_' + itemId);
    
    const countExistentes = existDiv ? existDiv.querySelectorAll('.foto-thumb-card').length : 0;
    const countNovas = (window.checklistArquivos[itemId] || []).length;
    const total = countExistentes + countNovas;

    if (countSpan) countSpan.textContent = total;
    if (input) {
        input.setAttribute('data-ja-tem-foto', total > 0 ? '1' : '0');
    }
    if (badgeSpan) {
        badgeSpan.style.color = total > 0 ? '#15803d' : '#64748b';
        const icon = badgeSpan.querySelector('i');
        if (icon) {
            icon.className = total > 0 ? 'fa-solid fa-circle-check' : 'fa-solid fa-images';
        }
    }
}

function excluirFotoExistente(fotoId, itemId, btn) {
    if (!confirm('Deseja realmente excluir esta foto da evidência?')) {
        return;
    }
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

    const formData = new FormData();
    formData.append('foto_id', fotoId);
    formData.append('csrf_token', vistoriaConfig.csrfToken || '');

    const appUrl = vistoriaConfig.appUrl || '';
    fetch(appUrl + 'vistorias/actions?action=excluir_foto_checklist', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            const card = document.getElementById('foto_card_' + fotoId);
            if (card) {
                card.remove();
            }
            atualizarContadorFotosItem(itemId);
            atualizarContadoresChecklist();
        } else {
            alert(data.mensagem || 'Erro ao excluir foto.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-times"></i>';
        }
    })
    .catch(err => {
        console.error(err);
        alert('Falha na comunicação com o servidor.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-times"></i>';
    });
}

document.querySelectorAll('.checklist-sem-prazo').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const target = document.getElementById(this.dataset.target);
        if (target) target.value = this.checked ? '1' : '0';
        atualizarContadoresChecklist();
    });
});

// ========================================================
// CONTROLE DE PRODUTIVIDADE DO CHECKLIST (EXPANDIR / FILTROS)
// ========================================================
let filtroChecklistAtivo = 'todos';

function expandirTodasCategorias() {
    document.querySelectorAll('.checklist-section').forEach(section => {
        const body = section.querySelector('.checklist-body');
        const header = section.querySelector('.checklist-header');
        const icon = header?.querySelector('.icone-toggle');
        if (body) body.style.display = 'block';
        if (header) header.setAttribute('aria-expanded', 'true');
        if (icon) {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        }
    });
}

function recolherTodasCategorias() {
    document.querySelectorAll('.checklist-section').forEach(section => {
        const body = section.querySelector('.checklist-body');
        const header = section.querySelector('.checklist-header');
        const icon = header?.querySelector('.icone-toggle');
        if (body) body.style.display = 'none';
        if (header) header.setAttribute('aria-expanded', 'false');
        if (icon) {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    });
}

function filtrarChecklistStatus(filtro, btnPill) {
    filtroChecklistAtivo = filtro;
    document.querySelectorAll('.btn-filter-pill').forEach(btn => btn.classList.remove('active'));
    if (btnPill) btnPill.classList.add('active');
    aplicarFiltrosChecklist();
}

function aplicarFiltrosChecklist() {
    const term = (document.getElementById('buscaChecklist')?.value || '').trim().toLowerCase();
    const sections = document.querySelectorAll('.checklist-section');
    const filtro = filtroChecklistAtivo;
    let totalVisiveis = 0;

    sections.forEach(section => {
        let hasVisible = false;
        const items = section.querySelectorAll('.checklist-item');
        const body = section.querySelector('.checklist-body');
        const header = section.querySelector('.checklist-header');
        const icon = section.querySelector('.icone-toggle');

        items.forEach(item => {
            const itemId = item.dataset.id;
            const status = document.getElementById('status_' + itemId)?.value || '';
            const text = item.getAttribute('data-text') || '';

            let matchesFilter = true;
            if (filtro === 'pendentes') {
                matchesFilter = (status === '');
            } else if (filtro === 'conforme') {
                matchesFilter = (status === 'CONFORME');
            } else if (filtro === 'nao_conforme') {
                matchesFilter = (status === 'NAO_CONFORME');
            } else if (filtro === 'na') {
                matchesFilter = (status === 'NAO_SE_APLICA');
            }

            const matchesSearch = (term === '' || text.indexOf(term) > -1);

            if (matchesFilter && matchesSearch) {
                item.style.display = 'block';
                hasVisible = true;
                totalVisiveis++;
            } else {
                item.style.display = 'none';
            }
        });

        if (hasVisible) {
            section.style.display = 'block';
            if (filtro !== 'todos' || term !== '') {
                if (body) body.style.display = 'block';
                if (header) header.setAttribute('aria-expanded', 'true');
                if (icon) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                }
            }
        } else {
            section.style.display = (filtro === 'todos' && term === '') ? 'block' : 'none';
        }
    });

    document.getElementById('checklistSemResultados')?.classList.toggle('is-hidden', totalVisiveis !== 0);
}

document.getElementById('buscaChecklist')?.addEventListener('input', aplicarFiltrosChecklist);

// ========================================================
// GESTÃO DA FOTO OFICIAL DA EMBARCAÇÃO (AJAX INSTANTÂNEO)
// ========================================================
function uploadFotoEmbarcacaoAjax(inputElement) {
    if (!inputElement || !inputElement.files || !inputElement.files[0]) return;

    const file = inputElement.files[0];
    const embarcacaoId = vistoriaConfig.embarcacaoId || '';
    const vistoriaId = vistoriaConfig.vistoriaId || '';
    const csrfToken = vistoriaConfig.csrfToken || '';
    const appUrl = vistoriaConfig.appUrl || '';

    if (!embarcacaoId) {
        alert('Identificador da embarcação não encontrado.');
        return;
    }

    const overlay = document.getElementById('vesselPhotoOverlay');
    const overlayText = document.getElementById('vesselPhotoOverlayText');
    const msgBox = document.getElementById('msgFotoOficialEmbarcacao');
    const imgPreview = document.getElementById('imgFotoOficialEmbarcacao');
    const placeholder = document.getElementById('vesselPhotoPlaceholder');
    const badge = document.getElementById('vesselPhotoBadge');
    const btnRemover = document.getElementById('btnRemoverFotoOficial');
    const thumbCabecalho = document.getElementById('thumbFotoCabecalhoEmbarcacao');

    if (overlay) overlay.classList.add('is-loading');
    if (overlayText) overlayText.textContent = 'Enviando foto oficial (' + (file.size / 1024).toFixed(0) + ' KB)...';
    if (msgBox) msgBox.style.display = 'none';

    const inputForm = document.getElementById('inputFotoFormulario');
    if (inputForm && inputElement !== inputForm) {
        try {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            inputForm.files = dataTransfer.files;
        } catch (e) {}
    }

    const formData = new FormData();
    formData.append('foto_oficial_embarcacao', file);
    formData.append('embarcacao_id', embarcacaoId);
    if (vistoriaId) formData.append('vistoria_id', vistoriaId);
    formData.append('csrf_token', csrfToken);

    fetch(appUrl + 'vistorias/actions?action=salvar_foto_oficial_embarcacao', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (overlay) overlay.classList.remove('is-loading');
        if (data.ok) {
            if (imgPreview) {
                imgPreview.src = data.foto_url;
                imgPreview.style.display = 'block';
            }
            if (placeholder) placeholder.style.display = 'none';
            if (badge) {
                badge.className = 'vessel-photo-badge is-cadastrada';
                badge.innerHTML = '<i class="fas fa-circle-check"></i> Foto Cadastrada';
            }
            if (btnRemover) btnRemover.style.display = 'flex';
            if (thumbCabecalho) {
                thumbCabecalho.src = data.foto_url;
                thumbCabecalho.style.display = 'block';
            }
            if (msgBox) {
                msgBox.style.display = 'block';
                msgBox.style.background = '#ecfdf5';
                msgBox.style.color = '#065f46';
                msgBox.style.border = '1px solid #a7f3d0';
                msgBox.innerHTML = '<i class="fas fa-circle-check"></i> ' + (data.mensagem || 'Foto oficial da embarcação atualizada e salva com sucesso!');
            }
        } else {
            if (msgBox) {
                msgBox.style.display = 'block';
                msgBox.style.background = '#fef2f2';
                msgBox.style.color = '#991b1b';
                msgBox.style.border = '1px solid #fecaca';
                msgBox.innerHTML = '<i class="fas fa-triangle-exclamation"></i> ' + (data.mensagem || 'Erro ao salvar foto oficial.');
            }
            alert(data.mensagem || 'Erro ao salvar foto oficial.');
        }
    })
    .catch(err => {
        console.error(err);
        if (overlay) overlay.classList.remove('is-loading');
        if (msgBox) {
            msgBox.style.display = 'block';
            msgBox.style.background = '#fef2f2';
            msgBox.style.color = '#991b1b';
            msgBox.style.border = '1px solid #fecaca';
            msgBox.innerHTML = '<i class="fas fa-triangle-exclamation"></i> Falha na comunicação com o servidor ao enviar a foto.';
        }
        alert('Falha na comunicação com o servidor ao enviar a foto.');
    })
    .finally(() => {
        inputElement.value = '';
    });
}

function removerFotoEmbarcacaoAjax() {
    if (!confirm('Deseja realmente remover a foto oficial desta embarcação? Ela também será desvinculada do Gerenciar Embarcações.')) {
        return;
    }

    const embarcacaoId = vistoriaConfig.embarcacaoId || '';
    const csrfToken = vistoriaConfig.csrfToken || '';
    const appUrl = vistoriaConfig.appUrl || '';
    const overlay = document.getElementById('vesselPhotoOverlay');
    const overlayText = document.getElementById('vesselPhotoOverlayText');
    const msgBox = document.getElementById('msgFotoOficialEmbarcacao');
    const imgPreview = document.getElementById('imgFotoOficialEmbarcacao');
    const placeholder = document.getElementById('vesselPhotoPlaceholder');
    const badge = document.getElementById('vesselPhotoBadge');
    const btnRemover = document.getElementById('btnRemoverFotoOficial');
    const thumbCabecalho = document.getElementById('thumbFotoCabecalhoEmbarcacao');

    if (overlay) overlay.classList.add('is-loading');
    if (overlayText) overlayText.textContent = 'Removendo foto oficial...';

    const formData = new FormData();
    formData.append('embarcacao_id', embarcacaoId);
    formData.append('csrf_token', csrfToken);

    fetch(appUrl + 'vistorias/actions?action=remover_foto_oficial_embarcacao', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (overlay) overlay.classList.remove('is-loading');
        if (data.ok) {
            if (imgPreview) {
                imgPreview.src = '';
                imgPreview.style.display = 'none';
            }
            if (placeholder) placeholder.style.display = 'flex';
            if (badge) {
                badge.className = 'vessel-photo-badge is-sem-foto';
                badge.innerHTML = '<i class="fas fa-camera"></i> Sem Foto Oficial';
            }
            if (btnRemover) btnRemover.style.display = 'none';
            if (thumbCabecalho) {
                thumbCabecalho.src = '';
                thumbCabecalho.style.display = 'none';
            }
            if (msgBox) {
                msgBox.style.display = 'block';
                msgBox.style.background = '#f8fafc';
                msgBox.style.color = '#334155';
                msgBox.style.border = '1px solid #cbd5e1';
                msgBox.innerHTML = '<i class="fas fa-info-circle"></i> Foto oficial removida com sucesso.';
            }
        } else {
            alert(data.mensagem || 'Erro ao remover foto oficial.');
        }
    })
    .catch(err => {
        console.error(err);
        if (overlay) overlay.classList.remove('is-loading');
        alert('Erro ao conectar ao servidor para remover foto.');
    });
}

atualizarContadoresChecklist();

// ========================================================
// NOVAS EXIGÊNCIAS NO RELATÓRIO DE RETORNO
// ========================================================
let contadorNovasExigenciasRetorno = 0;
function adicionarNovaExigenciaRetorno() {
    const container = document.getElementById('novasExigenciasRetorno');
    if (!container) return;
    const indice = contadorNovasExigenciasRetorno++;
    const bloco = document.createElement('article');
    bloco.style.cssText = 'margin:12px 0;padding:14px;border:1px solid var(--cor-borda,#555);border-radius:8px;';
    bloco.innerHTML = `
        <div class="form-row">
            <div class="form-group col-6">
                <label>Descrição da nova exigência *</label>
                <input type="text" name="nova_exigencia_descricao[${indice}]" required>
            </div>
            <div class="form-group col-3">
                <label>Item da NORMAM</label>
                <input type="text" name="nova_exigencia_item_normam[${indice}]">
            </div>
            <div class="form-group col-3">
                <label>Seção *</label>
                <select name="nova_exigencia_bloco[${indice}]" required>
                    <option value="seco">Vistoria em Seco</option>
                    <option value="flutuando" selected>Vistoria Flutuando</option>
                    <option value="borda_livre">Vistoria de Borda Livre</option>
                    <option value="arqueacao">Vistoria de Arqueação</option>
                </select>
            </div>
        </div>
        <label style="display:flex;gap:8px;align-items:center">
            <input type="checkbox" name="nova_exigencia_as[${indice}]" value="1"> A/S — Antes de suspender
        </label>
        <button type="button" class="btn btn-danger btn-sm" style="margin-top:10px" onclick="this.closest('article').remove()">
            <i class="fas fa-trash"></i> Remover
        </button>`;
    container.appendChild(bloco);
}

// ========================================================
// TABELA DE EXIGÊNCIAS AVULSAS
// ========================================================
let contadorLinhasAvulsa = vistoriaConfig.contadorLinhasAvulsa || 0;
const blocosVistoriaAvulsa = vistoriaConfig.blocosVistoriaAvulsa || {
    'seco': 'Vistoria em Seco',
    'flutuando': 'Vistoria Flutuando',
    'borda_livre': 'Vistoria de Borda Livre',
    'arqueacao': 'Vistoria de Arqueação'
};
const blocoVistoriaPadrao = vistoriaConfig.blocoVistoriaPadrao || 'flutuando';

function opcoesBlocoVistoriaAvulsa(valorSelecionado) {
    return Object.entries(blocosVistoriaAvulsa).map(function([valor, rotulo]) {
        const selected = valor === valorSelecionado ? ' selected' : '';
        return `<option value="${valor}"${selected}>${rotulo}</option>`;
    }).join('');
}

function adicionarLinhaAvulsa() {
    contadorLinhasAvulsa++;
    const tbody = document.querySelector('#tabelaExigenciasAvulsas tbody');
    if (!tbody) return;
    const tr = document.createElement('tr');
    tr.className = 'linha-exigencia-avulsa';

    tr.innerHTML = `
        <td data-label="Ordem" style="text-align: center; padding: 6px;">
            <span class="ordem-num-avulsa">${contadorLinhasAvulsa}</span>
            <input type="hidden" name="exigencia_id[]" value="">
            <input type="hidden" name="exigencia_ordem[]" value="${contadorLinhasAvulsa}" class="ordem-input-avulsa">
        </td>
        <td data-label="Tipo" style="padding: 6px;">
            <select name="exigencia_bloco[]"
                    style="width: 100%; padding: 6px 4px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                ${opcoesBlocoVistoriaAvulsa(blocoVistoriaPadrao)}
            </select>
        </td>
        <td data-label="Descrição" style="padding: 6px;">
            <input type="text" name="exigencia_descricao[]" value=""
                   placeholder="Ex.: nao tem seguranca" required
                   style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
        </td>
        <td data-label="NORMAM" style="padding: 6px;">
            <input type="text" name="exigencia_item[]" value=""
                   placeholder="Ex.: NORMAM-202/DPC, Cap. 03"
                   style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
        </td>
        <td data-label="Situação" style="padding: 6px; text-align: center;">
            <select name="status_item[]"
                    style="width: 100%; padding: 6px 4px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                <option value="pendente">Pendente</option>
                <option value="cumprida">Cumprida</option>
            </select>
        </td>
        <td data-label="Observação" style="padding: 6px;">
            <input type="text" name="exigencia_observacao[]" value=""
                   placeholder="Observacao"
                   style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
        </td>
        <td data-label="Sem prazo" style="padding: 6px; text-align: center;">
            <input type="hidden" name="exigencia_sem_prazo[]" class="avulsa-sem-prazo-input" value="1">
            <label style="display:inline-flex; align-items:center; gap:6px;">
                <input type="checkbox" class="avulsa-sem-prazo-check" checked>
                AS
            </label>
        </td>
        <td data-label="Ações" style="text-align: center; padding: 6px;" class="no-print">
            <button type="button" class="btn btn-danger btn-sm" onclick="removerLinhaAvulsa(this)" title="Remover" aria-label="Remover exigência avulsa">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    vincularSemPrazoAvulsa(tr);
    renumerarLinhasAvulsas();
    atualizarEstadoTabelaAvulsa();
}

function vincularSemPrazoAvulsa(contexto) {
    contexto.querySelectorAll('.avulsa-sem-prazo-check').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const input = this.closest('td').querySelector('.avulsa-sem-prazo-input');
            if (input) input.value = this.checked ? '1' : '0';
        });
    });
}

document.querySelectorAll('#tabelaExigenciasAvulsas tbody tr').forEach(vincularSemPrazoAvulsa);

function removerLinhaAvulsa(btn) {
    btn.closest('tr').remove();
    renumerarLinhasAvulsas();
    atualizarEstadoTabelaAvulsa();
}

function atualizarEstadoTabelaAvulsa() {
    const temLinhas = document.querySelectorAll('#tabelaExigenciasAvulsas tbody tr.linha-exigencia-avulsa').length > 0;
    document.getElementById('avulsaEmpty')?.classList.toggle('is-hidden', temLinhas);
    document.getElementById('avulsaTableWrap')?.classList.toggle('is-hidden', !temLinhas);
}

function renumerarLinhasAvulsas() {
    const rows = document.querySelectorAll('#tabelaExigenciasAvulsas tbody tr.linha-exigencia-avulsa');
    rows.forEach((row, i) => {
        const num = i + 1;
        row.querySelector('.ordem-num-avulsa').textContent = num;
        row.querySelector('.ordem-input-avulsa').value = num;
    });
    contadorLinhasAvulsa = rows.length;
}

atualizarEstadoTabelaAvulsa();

// Confirmação ao salvar formulário
document.getElementById('formRelatorio')?.addEventListener('submit', function(e) {
    const status = document.getElementById('status_vistoria')?.value;

    if (status === 'APROVADA' || status === 'REPROVADA') {
        const msg = status === 'APROVADA'
            ? 'Ao salvar como APROVADA, a Ordem de Servico sera marcada como EXECUTADA e os certificados serao liberados. Deseja continuar?'
            : 'Ao salvar como REPROVADA, a Ordem de Servico sera marcada como EXECUTADA. Deseja continuar?';
        if (!confirm(msg)) {
            e.preventDefault();
        }
    }
});

// ========================================================
// CONTROLES DE REVISÃO DO ADMINISTRADOR
// ========================================================
function definirExpansaoReview(botao, expandido) {
    const alvoId = botao.getAttribute('aria-controls');
    const alvo = alvoId ? document.getElementById(alvoId) : null;
    if (!alvo) return;
    botao.setAttribute('aria-expanded', expandido ? 'true' : 'false');
    alvo.hidden = !expandido;
}

document.querySelectorAll('.admin-requirement-group-toggle, .admin-requirement-row-toggle').forEach(function(botao) {
    botao.dataset.defaultExpanded = botao.getAttribute('aria-expanded') || 'false';
    botao.addEventListener('click', function() {
        definirExpansaoReview(botao, botao.getAttribute('aria-expanded') !== 'true');
    });
});

const adminRequirementsSearch = document.getElementById('adminRequirementsSearch');
const adminRequirementsNoResults = document.getElementById('adminRequirementsNoResults');
if (adminRequirementsSearch) {
    const normalizarBuscaReview = function(texto) {
        return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR').trim();
    };

    adminRequirementsSearch.addEventListener('input', function() {
        const termo = normalizarBuscaReview(adminRequirementsSearch.value);
        let totalVisivel = 0;

        document.querySelectorAll('[data-requirement-group]').forEach(function(grupo) {
            let visiveisNoGrupo = 0;
            grupo.querySelectorAll('[data-requirement-item]').forEach(function(item) {
                const corresponde = !termo || normalizarBuscaReview(item.textContent || '').includes(termo);
                item.hidden = !corresponde;
                if (corresponde) visiveisNoGrupo++;
            });

            grupo.hidden = visiveisNoGrupo === 0;
            totalVisivel += visiveisNoGrupo;
            const contador = grupo.querySelector('[data-group-visible-count]');
            if (contador) contador.textContent = String(visiveisNoGrupo);

            const botaoGrupo = grupo.querySelector('.admin-requirement-group-toggle');
            if (botaoGrupo) {
                definirExpansaoReview(
                    botaoGrupo,
                    termo ? visiveisNoGrupo > 0 : botaoGrupo.dataset.defaultExpanded === 'true'
                );
            }
        });

        if (adminRequirementsNoResults) {
            adminRequirementsNoResults.hidden = totalVisivel > 0;
        }
    });
}

// Modal de Assinatura Substituta
const modalAssinaturaSubstituta = document.getElementById('modalAssinaturaSubstituta');
const mensagemAssinaturaSubstituta = document.getElementById('mensagemAssinaturaSubstituta');
const confirmarAssinaturaSubstituta = document.getElementById('confirmarAssinaturaSubstituta');
let relatorioAssinaturaSubstituta = '';

function fecharModalAssinaturaSubstituta() {
    if (!modalAssinaturaSubstituta || confirmarAssinaturaSubstituta?.disabled) return;
    modalAssinaturaSubstituta.style.display = 'none';
    document.body.style.overflow = '';
    relatorioAssinaturaSubstituta = '';
}

function exibirMensagemAssinaturaSubstituta(texto, erro = false) {
    if (!mensagemAssinaturaSubstituta) return;
    mensagemAssinaturaSubstituta.style.display = 'block';
    mensagemAssinaturaSubstituta.className = 'alert ' + (erro ? 'alert-danger' : 'alert-info');
    mensagemAssinaturaSubstituta.textContent = texto;
}

document.querySelectorAll('.js-assinar-substituto').forEach(function(botao) {
    botao.addEventListener('click', function() {
        relatorioAssinaturaSubstituta = this.dataset.documentoId || '';
        if (!modalAssinaturaSubstituta || !relatorioAssinaturaSubstituta) return;
        mensagemAssinaturaSubstituta.style.display = 'none';
        confirmarAssinaturaSubstituta.disabled = false;
        modalAssinaturaSubstituta.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        confirmarAssinaturaSubstituta.focus();
    });
});

document.getElementById('cancelarAssinaturaSubstituta')?.addEventListener('click', fecharModalAssinaturaSubstituta);
document.getElementById('fecharAssinaturaSubstituta')?.addEventListener('click', fecharModalAssinaturaSubstituta);
modalAssinaturaSubstituta?.addEventListener('click', function(evento) {
    if (evento.target === modalAssinaturaSubstituta) fecharModalAssinaturaSubstituta();
});
document.addEventListener('keydown', function(evento) {
    if (evento.key === 'Escape' && modalAssinaturaSubstituta?.style.display === 'flex') {
        fecharModalAssinaturaSubstituta();
    }
});

confirmarAssinaturaSubstituta?.addEventListener('click', function() {
    if (!relatorioAssinaturaSubstituta) return;
    if (!navigator.geolocation) {
        exibirMensagemAssinaturaSubstituta('Este navegador não oferece geolocalização.', true);
        return;
    }

    const appUrl = vistoriaConfig.appUrl || '';
    const csrfToken = vistoriaConfig.csrfToken || '';
    confirmarAssinaturaSubstituta.disabled = true;
    exibirMensagemAssinaturaSubstituta('Obtendo localização e preparando a assinatura...');
    navigator.geolocation.getCurrentPosition(function(posicao) {
        const dados = new FormData();
        dados.append('csrf_token', csrfToken);
        dados.append('action', 'assinar');
        dados.append('documento_tipo', 'RELATORIO');
        dados.append('documento_id', relatorioAssinaturaSubstituta);
        dados.append('latitude', posicao.coords.latitude);
        dados.append('longitude', posicao.coords.longitude);
        dados.append('geo_precisao_m', posicao.coords.accuracy || '');

        fetch(appUrl + 'minhas-assinaturas/actions', {
            method: 'POST',
            body: dados,
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(async function(resposta) {
            const retorno = await resposta.json().catch(function() {
                return {success:false,message:'O servidor retornou uma resposta inválida.'};
            });
            if (!resposta.ok || !retorno.success) {
                throw new Error(retorno.message || 'Não foi possível assinar o relatório.');
            }
            exibirMensagemAssinaturaSubstituta(retorno.message || 'Relatório assinado com sucesso.');
            const proximaUrl = retorno.data?.proxima_url;
            modalAssinaturaSubstituta.style.display = 'none';
            document.body.style.overflow = '';
            window.setTimeout(function() {
                window.location.assign(proximaUrl || (appUrl + 'documentacao/novo_certificado?agendamento_id=' + encodeURIComponent(vistoriaConfig.agendamentoId || '') + '&vistoria_id=' + encodeURIComponent(vistoriaConfig.vistoriaId || '')));
            }, 650);
        }).catch(function(erro) {
            exibirMensagemAssinaturaSubstituta(erro.message, true);
            confirmarAssinaturaSubstituta.disabled = false;
        });
    }, function(erro) {
        const mensagem = erro.code === 1
            ? 'A localização é obrigatória para autorizar a assinatura.'
            : 'Não foi possível obter a localização. Verifique a permissão do navegador e tente novamente.';
        exibirMensagemAssinaturaSubstituta(mensagem, true);
        confirmarAssinaturaSubstituta.disabled = false;
    }, {enableHighAccuracy:true,timeout:15000,maximumAge:0});
});

document.getElementById('formDecisaoAdmin')?.addEventListener('submit', function(e) {
    if (e.submitter?.name === 'decisao' && e.submitter.value === 'aprovar') {
        return;
    }
    const status = document.getElementById('status_vistoria_admin')?.value;
    const observacao = document.getElementById('observacao_admin')?.value.trim();

    if (!status) {
        e.preventDefault();
        alert('Selecione o resultado final da vistoria.');
        return;
    }

    if (status === 'REPROVADA' && !observacao) {
        e.preventDefault();
        alert('Informe uma observacao para reprovar o relatorio.');
        return;
    }

    const labels = {
        PENDENTE: 'Pendente',
        AGUARDANDO_APROVACAO: 'Aguardando Aprovacao',
        APROVADA: 'Aprovada',
        APROVADA_COM_EXIGENCIAS: 'Aprovada com Exigencias',
        RETORNO_AS: 'Retorno A/S Necessario',
        REPROVADA: 'Reprovada',
        CANCELADA: 'Cancelada'
    };
    const texto = 'Confirmar resultado final como ' + (labels[status] || status) + '?';
    if (!confirm(texto)) {
        e.preventDefault();
    }
});
