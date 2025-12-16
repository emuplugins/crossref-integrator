/* ===== notifications ===== */
function addNotice(type = '', title = '', message = '') {
    const wrapper = document.querySelector('.carbon-fields-frontend-notice-wrapper');
    if (!wrapper) return;

    const notice = document.createElement('div');
    notice.className = 'carbon-fields-frontend-notice' + (type ? ' ' + type : '');

    const header = document.createElement('div');
    header.className = 'carbon-fields-frontend-notice-header';

    // Ícones por tipo
    const icons = {
        success: `
            <svg class="info-icon" xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12.75 11.25 15 15 9.75M21 12
                         a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        `,
        error: `
            <svg class="info-icon" xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 9v4m0 4h.01M10.29 3.86
                         1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.2
                         a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86
                         a1.5 1.5 0 0 0-2.42 0Z" />
            </svg>
        `
    };

    // Ícone (default: success)
    header.innerHTML = icons[type] || icons.success;

    if (title) {
        const spanTitle = document.createElement('span');
        spanTitle.className = 'carbon-fields-frontend-notice-title';
        spanTitle.textContent = title;
        header.appendChild(spanTitle);
    }

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'carbon-fields-frontend-notice-close';
    closeBtn.setAttribute('aria-label', 'Fechar');

    closeBtn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
             viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M6 18 18 6M6 6l12 12" />
        </svg>
    `;

    closeBtn.addEventListener('click', function () {
        const noticeEl = this.closest('.carbon-fields-frontend-notice');
        if (!noticeEl) return;
        removeNotice(noticeEl);
    });

    header.appendChild(closeBtn);

    const p = document.createElement('p');
    p.textContent = message;

    const loader = document.createElement('div');
    loader.className = 'carbon-fields-frontend-notice-loader';

    notice.appendChild(header);
    notice.appendChild(p);
    notice.appendChild(loader);
    wrapper.appendChild(notice);

    setTimeout(() => {
        removeNotice(notice);
    }, 5000);
}

function removeNotice(noticeEl) {
    if (!noticeEl || noticeEl.classList.contains('is-removing')) return;

    noticeEl.classList.add('is-removing');

    noticeEl.addEventListener('animationend', function () {
        noticeEl.remove();
    }, { once: true });
}

/* ===== form logic (único DOMContentLoaded unificado) ===== */
document.addEventListener('DOMContentLoaded', function () {

    const form = document.querySelector('form.carbon-fields-frontend-form');
    if (!form) return;

    /* ---------- controle de abertura de aba por tentativa de envio --------- */
    form._openedInvalidTabHandled = false;

    Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"], [data-action="submit"]'))
        .forEach(btn => {
            btn.addEventListener('click', () => {
                form._openedInvalidTabHandled = false;
            }, true);
        });

    form.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && e.target && e.target.tagName !== 'TEXTAREA') {
            form._openedInvalidTabHandled = false;
        }
    }, true);

    /* ---------- handler invalid (mostra notice ao primeiro invalid por envio) ---------- */
    form.addEventListener('invalid', (event) => {
        const target = event.target;

        if (!['INPUT', 'SELECT', 'TEXTAREA'].includes(target.tagName)) return;

        target.classList.add('required');

        if (form._openedInvalidTabHandled) {
            return;
        }

        form._openedInvalidTabHandled = true;

        // monta caminho e abre tabs necessárias
        const path = [];
        let el = target;
        while (el) {
            const item = el.closest('.carbon-fields-frontend-complex-item');
            const repeater = item ? item.closest('.carbon-fields-frontend-complex-field') : null;
            if (!item || !repeater) break;
            path.unshift({ repeater: repeater, index: String(item.dataset.index) });
            el = repeater.parentElement;
        }

        path.forEach(p => {
            activateTab(p.repeater, p.index);
        });

        // scroll + focus
        setTimeout(() => {
            try { target.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' }); } catch (e) { }
            try { target.focus({ preventScroll: true }); } catch (e) {
                try { target.focus(); } catch (e2) { }
            }
        }, 50);

        // mostra notice explicativa uma vez por tentativa de envio
        addNotice('error', 'Campos obrigatórios', 'Existem campos obrigatórios não preenchidos.');
    }, true);

    /* ---------- input remove marcação visual ---------- */
    form.querySelectorAll('input, select, textarea').forEach((field) => {
        field.addEventListener('input', (e) => {
            e.target.classList.remove('required');
        });
    });

    /* ===== CLIQUES GLOBAIS (abas / add / remove) ===== */
    document.addEventListener('click', function (e) {

        const clickedTab = e.target.closest('.carbon-fields-frontend-complex-tab');
        if (clickedTab && clickedTab.closest('.carbon-fields-frontend-complex-field')) {
            const repeater = clickedTab.closest('.carbon-fields-frontend-complex-field');
            const index = String(clickedTab.dataset.index);
            activateTab(repeater, index);
            return;
        }

        const addBtn = e.target.closest('[data-action="add"]');
        if (addBtn) {
            const repeater = addBtn.closest('.carbon-fields-frontend-complex-field');
            if (repeater) {
                addRepeaterItem(repeater);
                // aviso de confirmação
                addNotice('success', 'Item adicionado', 'Novo item adicionado.');
            }
            return;
        }

        const removeBtn = e.target.closest('.carbon-fields-frontend-complex-remove');
        if (removeBtn) {
            removeRepeaterItem(removeBtn);
            return;
        }
    });

    /* ===== ATUALIZA TÍTULO DINAMICAMENTE ===== */
    document.addEventListener('input', function (e) {
        const input = e.target;
        if (!input.name) return;

        const item = input.closest('.carbon-fields-frontend-complex-item');
        const repeater = input.closest('.carbon-fields-frontend-complex-field');
        if (!item || !repeater) return;

        const headerTemplate = repeater.getAttribute('header-template');
        if (!headerTemplate) return;

        if (!input.name.includes('[' + headerTemplate + ']')) return;

        const index = String(item.dataset.index);
        const tab = repeater.querySelector('.carbon-fields-frontend-complex-tab[data-index="' + index + '"]');
        if (!tab) return;

        const value = input.value.trim();
        tab.textContent = value !== '' ? value : (parseInt(index, 10) + 1);
    });

    /* ===== FUNÇÕES ===== */

    function addRepeaterItem(repeater) {
        const itemsWrap = repeater.querySelector(':scope > .carbon-fields-frontend-complex-items');
        if (!itemsWrap) return;

        const templateElem =
            itemsWrap.querySelector(':scope > template.carbon-fields-frontend-complex-template') ||
            itemsWrap.querySelector(':scope > template.carbon-fields-frontend-complex-item.carbon-fields-frontend-template') ||
            itemsWrap.querySelector(':scope > .carbon-fields-frontend-complex-item.carbon-fields-frontend-template');

        if (!templateElem) return;

        let fragment;
        if (templateElem.tagName === 'TEMPLATE') {
            fragment = templateElem.content.cloneNode(true);
        } else {
            fragment = templateElem.cloneNode(true);
        }

        let newItem;
        if (fragment instanceof DocumentFragment) {
            newItem = document.createElement('div');
            newItem.className = 'carbon-fields-frontend-complex-item';
            if (templateElem.dataset && templateElem.dataset.depth) {
                newItem.setAttribute('data-depth', templateElem.dataset.depth);
            }
            newItem.appendChild(fragment);
        } else if (fragment.nodeType === Node.ELEMENT_NODE) {
            newItem = fragment;
            if (newItem.tagName === 'TEMPLATE') {
                const tempDiv = document.createElement('div');
                tempDiv.className = 'carbon-fields-frontend-complex-item';
                tempDiv.setAttribute('data-depth', newItem.getAttribute('data-depth') || '0');
                tempDiv.innerHTML = newItem.innerHTML;
                newItem = tempDiv;
            } else {
                if (!newItem.classList.contains('carbon-fields-frontend-complex-item')) {
                    newItem.classList.add('carbon-fields-frontend-complex-item');
                }
            }
        } else {
            return;
        }

        newItem.classList.remove('carbon-fields-frontend-template');
        newItem.classList.add('active');

        itemsWrap.appendChild(newItem);

        newItem.querySelectorAll('input, select, textarea').forEach(input => {
            if (input.name && input.name.indexOf('__INDEX__') !== -1) {
                input.name = input.name.replace(/__INDEX__/g, '');
            }
            input.classList.remove('required');
        });

        reindexRepeater(repeater);

        const realItems = Array.from(itemsWrap.children).filter(child =>
            child.classList && child.classList.contains('carbon-fields-frontend-complex-item') &&
            child.tagName !== 'TEMPLATE' && !child.classList.contains('carbon-fields-frontend-template')
        );
        const last = realItems[realItems.length - 1];
        if (last) activateTab(repeater, String(last.dataset.index));
    }

    function removeRepeaterItem(button) {
        const item = button.closest('.carbon-fields-frontend-complex-item');
        const repeater = button.closest('.carbon-fields-frontend-complex-field');
        if (!item || !repeater) return;

        const itemsWrap = repeater.querySelector(':scope > .carbon-fields-frontend-complex-items');
        if (!itemsWrap) return;

        const realItems = Array.from(itemsWrap.children).filter(child =>
            child.classList && child.classList.contains('carbon-fields-frontend-complex-item') &&
            child.tagName !== 'TEMPLATE' && !child.classList.contains('carbon-fields-frontend-template')
        );

        const minAttr = repeater.getAttribute('data-complex-min');
        const min = minAttr ? parseInt(minAttr, 10) : 0;
        if (realItems.length <= min) {

            // aviso visual + notice explicativa
            const blinkTimes = 2;
            const interval = 150;
            let count = 0;
            const blink = () => {
                repeater.classList.toggle('required');
                count++;
                if (count < blinkTimes * 2) setTimeout(blink, interval);
            };
            blink();

            addNotice(
                'error',
                'Número Mínimo atingido',
                'Preencha pelo menos ' + min + ' item' + (min > 1 ? 's' : '') + '.'
            );

            return;
        }

        item.remove();

        reindexRepeater(repeater);

        const firstItem = Array.from(itemsWrap.children).find(child =>
            child.classList && child.classList.contains('carbon-fields-frontend-complex-item') &&
            child.tagName !== 'TEMPLATE' && !child.classList.contains('carbon-fields-frontend-template')
        );
        if (firstItem) activateTab(repeater, String(firstItem.dataset.index));

        // aviso de remoção
        addNotice('success', 'Item removido', 'Item removido com sucesso.');
    }

    function reindexRepeater(repeater) {
        const itemsWrap = repeater.querySelector(':scope > .carbon-fields-frontend-complex-items');
        if (!itemsWrap) return;

        const items = Array.from(itemsWrap.children).filter(child =>
            child.classList && child.classList.contains('carbon-fields-frontend-complex-item') &&
            child.tagName !== 'TEMPLATE' && !child.classList.contains('carbon-fields-frontend-template')
        );

        const tabsWrap = repeater.querySelector(':scope > .carbon-fields-frontend-complex-tabs');
        if (!tabsWrap) return;
        const tabAdd = tabsWrap.querySelector('[data-action="add"]');

        items.forEach((item, i) => {
            item.dataset.index = i;
            item.setAttribute('data-index', i);

            item.querySelectorAll('input, textarea, select').forEach(input => {
                if (!input.name) return;

                if (input.name.indexOf('__INDEX__') !== -1) {
                    input.name = input.name.replace(/__INDEX__/g, i);
                } else {
                    input.name = input.name.replace(/\[\d+\]/, '[' + i + ']');
                }
            });

            let tab = tabsWrap.querySelector(`:scope > .carbon-fields-frontend-complex-tab[data-index="${i}"]`);
            if (!tab) {
                tab = document.createElement('li');
                tab.className = 'carbon-fields-frontend-complex-tab';
                tabsWrap.insertBefore(tab, tabAdd || null);
            }
            tab.dataset.index = i;

            const headerTemplate = repeater.getAttribute('header-template');
            let title = String(i + 1);

            if (headerTemplate) {
                const bindInput = item.querySelector(`[name*="[${headerTemplate}]"]`);
                const val = bindInput ? String(bindInput.value || '').trim() : '';
                title = val !== '' ? val : String(i + 1);
            }

            tab.textContent = title;
        });

        Array.from(tabsWrap.querySelectorAll(':scope > .carbon-fields-frontend-complex-tab:not([data-action="add"])'))
            .filter(tab => parseInt(tab.dataset.index || -1, 10) >= items.length)
            .forEach(tab => tab.remove());

        items.forEach(item => {
            const innerRepeaters = Array.from(item.querySelectorAll('.carbon-fields-frontend-complex-field'));
            innerRepeaters.forEach(inner => reindexRepeater(inner));
        });
    }

    function activateTab(repeater, index) {
        if (!repeater) return;
        const idx = String(index);

        const tabs = Array.from(repeater.querySelectorAll(':scope > .carbon-fields-frontend-complex-tabs > .carbon-fields-frontend-complex-tab:not([data-action="add"])'));
        const items = Array.from(repeater.querySelectorAll(':scope > .carbon-fields-frontend-complex-items > .carbon-fields-frontend-complex-item')).filter(el =>
            el.tagName !== 'TEMPLATE' && !el.classList.contains('carbon-fields-frontend-complex-template')
        );

        tabs.forEach(tab => tab.classList.toggle('active', String(tab.dataset.index) === idx));
        items.forEach(item => item.classList.toggle('active', String(item.dataset.index) === idx));

        const activeItem = repeater.querySelector(`:scope > .carbon-fields-frontend-complex-items > .carbon-fields-frontend-complex-item[data-index="${idx}"]`);
        if (activeItem) {
            const innerRepeaters = Array.from(activeItem.querySelectorAll('.carbon-fields-frontend-complex-field'));
            innerRepeaters.forEach(inner => {
                const firstInner = Array.from(inner.querySelectorAll(':scope > .carbon-fields-frontend-complex-items > .carbon-fields-frontend-complex-item'))
                    .filter(el => el.tagName !== 'TEMPLATE' && !el.classList.contains('carbon-fields-frontend-template'))[0];
                if (!firstInner) return;
                const firstIndex = String(firstInner.dataset.index);
                activateTab(inner, firstIndex);
            });
        }
    }

    /* ===== envio via AJAX (notices em success / error) ===== */
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const data = new FormData(form);
        const newId = form.id.startsWith('form_') ? form.id.slice(5) : form.id;

        e.target.classList.add('submitting');

        data.append('action', 'carbon_fields_frontend_form_' + newId);

        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: data
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    form.reset();
                    addNotice('success', 'Enviado', 'Formulário enviado com sucesso.');
                } else {
                    addNotice('error', 'Erro no envio', res.data?.message || 'Erro ao enviar o formulário.');
                }
                e.target.classList.remove('submitting');
            })
            .catch(() => {
                addNotice('error', 'Erro', 'Erro de rede. Tente novamente.');
                e.target.classList.remove('submitting');
            });
    });

});
