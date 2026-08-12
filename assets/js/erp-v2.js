(function () {
    'use strict';

    const normalize = value => String(value || '').replace(/\s+/g, ' ').trim();
    const submittingForms = new WeakSet();

    function createSubmissionToken() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
            const bytes = new Uint8Array(24);
            window.crypto.getRandomValues(bytes);
            return Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
        }
        return `${Date.now().toString(36)}_${Math.random().toString(36).slice(2)}_${Math.random().toString(36).slice(2)}`;
    }

    function unlockSubmission(form, proxy, buttons, submitter) {
        submittingForms.delete(form);
        form.removeAttribute('aria-busy');
        delete form.dataset.submitting;
        if (proxy && proxy.parentNode) proxy.remove();
        buttons.forEach(button => { button.disabled = false; });
        if (submitter && submitter.dataset.processingOriginalHtml !== undefined) {
            submitter.innerHTML = submitter.dataset.processingOriginalHtml;
            delete submitter.dataset.processingOriginalHtml;
        }
    }

    // Executado na fase de captura para proteger inclusive formularios
    // adicionados dinamicamente e antes dos handlers especificos dos modulos.
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.method.toLowerCase() !== 'post' || form.dataset.allowRepeatSubmit !== undefined) return;

        if (submittingForms.has(form)) {
            event.preventDefault();
            event.stopImmediatePropagation();
            if (typeof window.showToast === 'function') {
                window.showToast('Este formulario ja esta sendo processado. Aguarde.', 'info');
            }
            return;
        }

        let token = form.querySelector('input[name="_submission_token"]');
        if (!token) {
            token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_submission_token';
            form.appendChild(token);
        }
        if (!token.value) token.value = createSubmissionToken();

        submittingForms.add(form);
        form.dataset.submitting = 'true';
        form.setAttribute('aria-busy', 'true');

        const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;
        let submitterProxy = null;
        if (submitter && submitter.getAttribute('name')) {
            submitterProxy = document.createElement('input');
            submitterProxy.type = 'hidden';
            submitterProxy.name = submitter.getAttribute('name');
            submitterProxy.value = submitter.getAttribute('value') || '';
            form.appendChild(submitterProxy);
        }

        const buttons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
        buttons.forEach(button => { button.disabled = true; });
        if (submitter && submitter.tagName === 'BUTTON') {
            submitter.dataset.processingOriginalHtml = submitter.innerHTML;
            submitter.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Processando...';
        }

        // Um handler do proprio modulo pode cancelar o envio para fazer
        // validacao ou AJAX. Nesse caso ele continua responsavel pelo fluxo.
        setTimeout(() => {
            if (event.defaultPrevented) {
                unlockSubmission(form, submitterProxy, buttons, submitter);
            }
        }, 0);
    }, true);

    // Ao voltar pelo cache do navegador, o formulario deve estar utilizavel
    // e receber um novo identificador no proximo envio.
    window.addEventListener('pageshow', event => {
        if (!event.persisted) return;
        document.querySelectorAll('form[data-submitting="true"]').forEach(form => {
            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(button => {
                button.disabled = false;
            });
            form.querySelector('input[name="_submission_token"]')?.remove();
            form.removeAttribute('aria-busy');
            delete form.dataset.submitting;
            submittingForms.delete(form);
        });
    });

    function enhanceTables(root) {
        root.querySelectorAll('table').forEach(table => {
            if (table.closest('.documento-papel, .pdf-preview, [data-static-document]')) return;
            if (table.dataset.responsive === 'off') return;

            const headers = Array.from(table.querySelectorAll('thead th')).map(th => normalize(th.textContent));
            if (!headers.length) return;

            table.classList.add('erp-responsive-table');
            table.querySelectorAll('tbody tr').forEach(row => {
                Array.from(row.children).forEach((cell, index) => {
                    if (cell.tagName !== 'TD' || cell.hasAttribute('data-label')) return;
                    const label = headers[index] || 'Informacao';
                    cell.setAttribute('data-label', label);
                    const normalizedLabel = label.toLocaleLowerCase('pt-BR');
                    if (/nome|embarca|cliente|pessoa|servi|proposta|documento/.test(normalizedLabel)) cell.classList.add('erp-cell-primary');
                    if (/status|situa|estado/.test(normalizedLabel)) cell.classList.add('erp-cell-status');
                    if (/aç|acoe|acoes/.test(normalizedLabel)) cell.classList.add('erp-cell-actions');
                });
            });
        });
    }

    function enhanceTableScrolling(root) {
        root.querySelectorAll('table').forEach(table => {
            if (table.closest('.documento-papel, .pdf-preview, [data-static-document]')) return;
            if (!table.querySelector('thead th')) return;
            if (table.closest('.finance-table-scroll, .erp-table-scroll, .portal-admin-table-container')) return;

            const existingContainer = table.closest('.tabela-container, .table-responsive, .table-container, .data-table-wrapper, .portal-table-wrap, .report-table-wrap');
            const scrollContainer = existingContainer || (() => {
                const wrapper = document.createElement('div');
                wrapper.className = 'erp-table-scroll';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
                return wrapper;
            })();

            scrollContainer.classList.add('erp-table-scroll');
            if (scrollContainer.dataset.erpScrollEnhanced) return;
            scrollContainer.dataset.erpScrollEnhanced = 'true';
            scrollContainer.tabIndex ||= 0;
            scrollContainer.setAttribute('aria-label', 'Tabela. Deslize horizontalmente para ver todas as colunas.');

            const hint = document.createElement('p');
            hint.className = 'erp-table-scroll-hint';
            hint.innerHTML = '<i class="fa-solid fa-arrows-left-right" aria-hidden="true"></i> Arraste a tabela para o lado para ver todas as colunas.';
            scrollContainer.insertAdjacentElement('afterend', hint);
        });
    }

    function enhanceActionLabels(root) {
        root.querySelectorAll('.erp-cell-actions .btn, table.erp-responsive-table td:last-child .btn').forEach(action => {
            if (action.querySelector('.erp-action-label')) return;
            const label = normalize(action.getAttribute('aria-label') || action.getAttribute('title'));
            if (!label) return;
            const text = document.createElement('span');
            text.className = 'erp-action-label';
            text.textContent = label;
            action.appendChild(text);
        });
    }

    function countActiveFilters(container) {
        return Array.from(container.querySelectorAll('input, select')).filter(control => {
            if (!control.name || control.type === 'hidden' || control.type === 'submit') return false;
            if (control.type === 'checkbox' || control.type === 'radio') return control.checked;
            const value = normalize(control.value);
            return value !== '' && value !== 'todos' && value !== 'todas';
        }).length;
    }

    function enhanceFilters(root) {
        root.querySelectorAll('.filtros, .filters-bar, .filter-bar, .tabela-filtros').forEach((panel, index) => {
            if (panel.dataset.erpEnhanced) return;
            panel.dataset.erpEnhanced = 'true';
            panel.classList.add('erp-filter-panel');
            panel.id ||= `erp-filter-panel-${index + 1}`;

            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'erp-filter-toggle';
            toggle.setAttribute('aria-controls', panel.id);
            toggle.setAttribute('aria-expanded', 'false');

            const update = () => {
                const count = countActiveFilters(panel);
                toggle.innerHTML = `<span><i class="fa-solid fa-filter" aria-hidden="true"></i> Filtros</span>${count ? `<span class="erp-filter-count" aria-label="${count} filtros ativos">${count}</span>` : '<i class="fa-solid fa-chevron-down" aria-hidden="true"></i>'}`;
            };

            toggle.addEventListener('click', () => {
                const open = panel.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', String(open));
                const chevron = toggle.querySelector('.fa-chevron-down, .fa-chevron-up');
                if (chevron) chevron.className = `fa-solid fa-chevron-${open ? 'up' : 'down'}`;
            });
            panel.addEventListener('change', update);
            panel.addEventListener('input', update);
            panel.parentNode.insertBefore(toggle, panel);
            update();
        });
    }

    function enhanceForms(root) {
        root.querySelectorAll('form').forEach(form => {
            if (form.dataset.noDirtyWarning !== undefined || form.method.toLowerCase() === 'get') return;
            let dirty = false;
            let submitting = false;
            form.addEventListener('input', () => { dirty = true; }, { passive: true });
            form.addEventListener('change', () => { dirty = true; }, { passive: true });
            form.addEventListener('submit', () => { submitting = true; dirty = false; });
            window.addEventListener('beforeunload', event => {
                if (!dirty || submitting) return;
                event.preventDefault();
                event.returnValue = '';
            });
        });
    }

    function enhanceDialogs(root) {
        root.querySelectorAll('.modal').forEach(modal => {
            modal.setAttribute('role', modal.getAttribute('role') || 'dialog');
            modal.setAttribute('aria-modal', 'true');
        });
    }

    function enhance(root = document) {
        enhanceTables(root);
        enhanceTableScrolling(root);
        enhanceActionLabels(root);
        enhanceFilters(root);
        enhanceForms(root);
        enhanceDialogs(root);
        document.documentElement.classList.add('erp-v2-ready');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => enhance());
    } else {
        enhance();
    }

    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => mutation.addedNodes.forEach(node => {
            if (node.nodeType === Node.ELEMENT_NODE) enhance(node);
        }));
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });
})();
