/**
 * ERP SISTEMA DE GESTÃO - JAVASCRIPT
 * Modulo: Funcionalidades gerais do sistema
 */

(function() {
    'use strict';

    // ============================================
    // SIDEBAR
    // ============================================
    
    window.toggleSidebar = function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar) sidebar.classList.toggle('active');
        if (overlay) overlay.classList.toggle('active');
    };

    // Toggle submenu no sidebar
    window.toggleSubmenu = function(element) {
        const navGroup = element.closest('.nav-group');
        if (navGroup) {
            const submenu = navGroup.querySelector('.nav-submenu');
            if (submenu) {
                submenu.classList.toggle('open');
            }
        }
    };

    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar && overlay && window.innerWidth <= 1024) {
            if (e.target.closest('.nav-item') && !e.target.closest('.nav-item').hasAttribute('data-toggle')) {
                sidebar.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
            }
        }
    });

    // ============================================
    // MENSAGENS
    // ============================================
    
    document.addEventListener('DOMContentLoaded', function() {
        const mensagens = document.querySelectorAll('.message');
        mensagens.forEach(function(msg) {
            setTimeout(function() { if (msg.parentElement) msg.remove(); }, 5000);
        });

        const feedback = window.__formFeedback;
        if (!feedback) return;

        const forms = Array.from(document.querySelectorAll('form'));
        const valores = feedback.valores || {};
        const campos = feedback.campos || {};
        const nomesComErro = Array.isArray(campos) ? campos : Object.keys(campos);

        let formAlvo = forms.find(function(form) {
            return Object.keys(valores).some(function(nome) {
                return form.querySelector('[name="' + CSS.escape(nome) + '"], [name="' + CSS.escape(nome) + '[]"]');
            });
        });
        if (!formAlvo) formAlvo = forms[0];
        if (!formAlvo) return;

        Object.keys(valores).forEach(function(nome) {
            const valor = valores[nome];
            const controles = formAlvo.querySelectorAll(
                '[name="' + CSS.escape(nome) + '"], [name="' + CSS.escape(nome) + '[]"]'
            );
            controles.forEach(function(controle) {
                if (controle.type === 'file' || controle.type === 'password') return;
                if (controle.type === 'checkbox' || controle.type === 'radio') {
                    const selecionados = Array.isArray(valor) ? valor.map(String) : [String(valor)];
                    controle.checked = selecionados.includes(String(controle.value));
                } else {
                    controle.value = Array.isArray(valor) ? valor[0] || '' : valor;
                }
            });
        });

        let primeiroInvalido = null;
        nomesComErro.forEach(function(nome) {
            const controle = formAlvo.querySelector(
                '[name="' + CSS.escape(nome) + '"], [name="' + CSS.escape(nome) + '[]"]'
            );
            if (!controle) return;

            controle.classList.add('field-invalid');
            controle.setAttribute('aria-invalid', 'true');
            if (!primeiroInvalido) primeiroInvalido = controle;

            const grupo = controle.closest('.form-group') || controle.parentElement;
            if (!grupo || grupo.querySelector('.field-error')) return;

            const mensagem = Array.isArray(campos)
                ? 'Verifique este campo.'
                : (campos[nome] || 'Verifique este campo.');
            const erro = document.createElement('span');
            erro.className = 'field-error';
            erro.textContent = mensagem;
            grupo.appendChild(erro);
        });

        if (primeiroInvalido) {
            primeiroInvalido.focus({ preventScroll: true });
            primeiroInvalido.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    window.mostrarMensagem = function(tipo, texto) {
        var icones = { success: 'fa-check-circle', error: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
        var msg = document.createElement('div');
        msg.className = 'message ' + tipo;
        msg.innerHTML = '<i class="fas ' + (icones[tipo] || icones.info) + '"></i><span>' + texto + '</span><button class="close-msg" onclick="this.remove()">&times;</button>';
        document.body.appendChild(msg);
        setTimeout(function() { if (msg.parentElement) msg.remove(); }, 5000);
    };

    // ============================================
    // FORMULÁRIOS
    // ============================================
    
    window.validarFormulario = function(formId) {
        var form = document.getElementById(formId);
        if (!form) return true;
        var campos = form.querySelectorAll('[required]');
        var valido = true;
        campos.forEach(function(campo) {
            if (!campo.value.trim()) {
                campo.style.borderColor = '#E74C3C';
                valido = false;
                if (!campo.nextElementSibling || !campo.nextElementSibling.classList.contains('mensagem-erro')) {
                    var msg = document.createElement('span');
                    msg.className = 'mensagem-erro';
                    msg.style.cssText = 'color: #E74C3C; font-size: 0.8rem; margin-top: 4px; display: block;';
                    msg.textContent = 'Este campo é obrigatório';
                    campo.parentElement.appendChild(msg);
                }
            } else {
                campo.style.borderColor = '';
                if (campo.nextElementSibling && campo.nextElementSibling.classList.contains('mensagem-erro')) {
                    campo.nextElementSibling.remove();
                }
            }
        });
        return valido;
    };

    window.aplicarMascaraCPF = function(input) {
        var v = input.value.replace(/\D/g, '').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        input.value = v;
    };

    window.aplicarMascaraCNPJ = function(input) {
        var v = input.value.replace(/\D/g, '').replace(/^(\d{2})(\d)/, '$1.$2').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
        input.value = v;
    };

    window.aplicarMascaraTelefone = function(input) {
        var v = input.value.replace(/\D/g, '');
        v = v.replace(/^(\d{2})(\d)/, '($1) $2').replace(/(\d)(\d{4})$/, '$1-$2');
        input.value = v;
    };

    window.aplicarMascaraCEP = function(input) {
        var v = input.value.replace(/\D/g, '').replace(/(\d{5})(\d)/, '$1-$2');
        input.value = v;
    };

    window.aplicarMascaraMoeda = function(input) {
        var v = input.value.replace(/\D/g, '');
        v = (parseInt(v || '0') / 100).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        input.value = 'R$ ' + v;
    };

    window.aplicarMascaraData = function(input) {
        var v = input.value.replace(/\D/g, '').replace(/(\d{2})(\d)/, '$1/$2').replace(/(\d{2})(\d)/, '$1/$2');
        input.value = v;
    };

    window.buscarCep = function(cepInputId, campos) {
        var cep = document.getElementById(cepInputId).value.replace(/\D/g, '');
        if (cep.length !== 8) return;
        fetch('https://viacep.com.br/ws/' + cep + '/json/')
            .then(r => r.json())
            .then(data => {
                if (!data.erro) {
                    if (campos.logradouro) document.getElementById(campos.logradouro).value = data.logradouro || '';
                    if (campos.bairro) document.getElementById(campos.bairro).value = data.bairro || '';
                    if (campos.cidade) document.getElementById(campos.cidade).value = data.localidade || '';
                    if (campos.estado) document.getElementById(campos.estado).value = data.uf || '';
                } else {
                    mostrarMensagem('error', 'CEP não encontrado');
                }
            });
    };

    // ============================================
    // MODAIS
    // ============================================
    
    window.abrirModal = function(id) { var m = document.getElementById(id); if (m) m.classList.add('active'); };
    window.fecharModal = function(id) { var m = document.getElementById(id); if (m) m.classList.remove('active'); };
    window.alternarModal = function(id) { var m = document.getElementById(id); if (m) m.classList.toggle('active'); };

    // ============================================
    // CONFIRMAÇÃO
    // ============================================
    
    window.confirmarAcao = function(mensagem, url) {
        if (confirm(mensagem || 'Deseja realmente realizar esta ação?')) {
            if (url) window.location.href = url;
            else window.history.back();
        }
    };

    // ============================================
    // TABELAS / FILTROS
    // ============================================
    
    window.filtrarTabela = function(inputId, tabelaId) {
        var termo = document.getElementById(inputId).value.toLowerCase();
        var linhas = document.getElementById(tabelaId).querySelectorAll('tbody tr');
        linhas.forEach(function(linha) {
            linha.style.display = linha.textContent.toLowerCase().indexOf(termo) > -1 ? '' : 'none';
        });
    };

    // ============================================
    // SENHA TOGGLE
    // ============================================
    
    window.toggleSenha = function(inputId, iconeId) {
        var input = document.getElementById(inputId);
        var icone = document.getElementById(iconeId);
        if (input && icone) {
            if (input.type === 'password') { input.type = 'text'; icone.className = 'fas fa-eye-slash'; }
            else { input.type = 'password'; icone.className = 'fas fa-eye'; }
        }
    };

})();
document.addEventListener('change', function (event) {
    const input = event.target.closest('[data-feedback-upload] input[type="file"]');
    if (!input) return;
    const files = Array.from(input.files || []);
    const allowed = ['jpg','jpeg','png','webp','pdf','docx','xlsx','pptx','csv','txt'];
    const invalid = files.length > 5 || files.some(file => file.size > 10 * 1024 * 1024 || !allowed.includes((file.name.split('.').pop() || '').toLowerCase()));
    if (invalid) {
        input.value = '';
        if (typeof showToast === 'function') showToast('Use até 5 arquivos permitidos, com no máximo 10 MB cada.', 'error');
    }
    const preview = input.closest('[data-feedback-upload]').querySelector('.feedback-preview');
    if (preview) preview.innerHTML = invalid ? '' : files.map(file => '<span>' + String(file.name).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c])) + '</span>').join('');
});

document.addEventListener('input', function (event) {
    const search = event.target.closest('[data-feedback-user-search]');
    if (!search) return;
    const select = search.closest('form').querySelector('[data-feedback-user-select]');
    if (!select) return;
    const term = search.value.trim().toLocaleLowerCase('pt-BR');
    Array.from(select.options).forEach((option, index) => {
        if (index === 0) return;
        option.hidden = term !== '' && !option.textContent.toLocaleLowerCase('pt-BR').includes(term);
    });
    if (select.selectedOptions[0]?.hidden) select.value = '';
});
