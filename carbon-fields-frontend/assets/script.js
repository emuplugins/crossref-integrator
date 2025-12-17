function startTinyMce() {
    if (typeof tinymce === 'undefined') return;

    document.querySelectorAll('textarea.frontend-richtext').forEach(function (textarea) {

        if (textarea.dataset.tinymceInitialized) return;
        textarea.dataset.tinymceInitialized = '1';

        tinymce.init({
            target: textarea,
            toolbar: 'bold italic underline | bullist numlist | link unlink',
            menubar: false,
            plugins: 'lists link',
            setup: function (editor) {

                const sync = () => {
                    editor.save();
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                };

                editor.on('change keyup paste undo redo', sync);
            }
        });
    });
}


// helper: retorna array de índices dos itens ancestrais (do mais externo para o mais interno)
function getParentIndexes(repeater) {
    const indexes = [];
    // começa no item que contém esse repeater (se houver)
    let item = repeater.closest('.carbon-fields-frontend-complex-item');
    while (item) {
        if (item.dataset && typeof item.dataset.index !== 'undefined') {
            // push no array (vai acumular do mais interno para o mais externo)
            indexes.push(Number(item.dataset.index));
        }
        // sobe: encontra o próximo item ancestral acima do item atual
        const parentCandidate = item.parentElement ? item.parentElement.closest('.carbon-fields-frontend-complex-item') : null;
        item = parentCandidate;
    }
    // queremos do mais externo para o mais interno
    return indexes.reverse();
}



function stripHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent || div.innerText || '';
}


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

    startTinyMce()

    const form = document.querySelector('form.carbon-fields-frontend-form');
    if (!form) return;

    /* ===== envio via AJAX (com validação de arquivo) ===== */

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const formEl = e.target;
        const data = new FormData(form);
        const newId = formEl.id.startsWith('form_') ? formEl.id.slice(5) : formEl.id;


        formEl.classList.add('submitting');

        /* ---------- action do WordPress ---------- */
        data.append('action', 'carbon_fields_frontend_form_' + newId);

        /* ---------- envio ---------- */
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: data
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    formEl.reset();
                    addNotice('success', 'Enviado', 'Formulário enviado com sucesso.');
                } else {
                    addNotice(
                        'error',
                        'Erro no envio',
                        res.data?.message || 'Erro ao enviar o formulário.'
                    );

                    console.log(res.data?.message)
                }
                formEl.classList.remove('submitting');
            })
            .catch((err) => {
                addNotice('error', 'Erro', 'Erro de rede. Tente novamente.' + err);
                formEl.classList.remove('submitting');
            });
    });

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
                addRepeaterItem(repeater, addBtn.dataset.depth);
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

        const raw = input.value || '';
        const value = stripHtml(raw).trim();

        tab.textContent = value !== '' ? value : (parseInt(index, 10) + 1);

    });

    /* ===== FUNÇÕES ===== */

    function addRepeaterItem(repeater, depth = 0) {
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

        // calcula hierarquia de índices dos pais e passa para reindex
        const parentIndexes = getParentIndexes(repeater);
        reindexRepeater(repeater, depth, parentIndexes);

        const realItems = Array.from(itemsWrap.children).filter(child =>
            child.classList && child.classList.contains('carbon-fields-frontend-complex-item') &&
            child.tagName !== 'TEMPLATE' && !child.classList.contains('carbon-fields-frontend-template')
        );
        const last = realItems[realItems.length - 1];
        if (last) activateTab(repeater, String(last.dataset.index));

        startTinyMce();
    }

    function removeRepeaterItem(button) {
        const item = button.closest('.carbon-fields-frontend-complex-item');
        const repeater = button.closest('.carbon-fields-frontend-complex-field');

        const depth = item?.dataset?.depth;

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

        // pega índice e tab (se existir) antes de remover o item do DOM
        const itemIndex = item.dataset.index;
        const tabSelector = '.carbon-fields-frontend-complex-tab[data-index="' + itemIndex + '"]';
        const tabItem = repeater.querySelector(':scope > .carbon-fields-frontend-complex-tabs > .carbon-fields-frontend-complex-tab-list ' + tabSelector);

        // remove elemento e sua aba (se encontrada)
        if (tabItem) tabItem.remove();
        item.remove();

        // recalcula parentIndexes e reindex completo (reconstrói abas)
        const parentIndexes = getParentIndexes(repeater);
        reindexRepeater(repeater, depth, parentIndexes);

        const firstItem = Array.from(itemsWrap.children).find(child =>
            child.classList && child.classList.contains('carbon-fields-frontend-complex-item') &&
            child.tagName !== 'TEMPLATE' && !child.classList.contains('carbon-fields-frontend-template')
        );
        if (firstItem) activateTab(repeater, String(firstItem.dataset.index));

        addNotice('success', 'Item removido', 'Item removido com sucesso.');
    }


    function reindexRepeater(repeater, depth = 0, parentIndexes = []) {
        const itemsWrap = repeater.querySelector(':scope > .carbon-fields-frontend-complex-items');
        if (!itemsWrap) return;

        const items = Array.from(itemsWrap.children).filter(child =>
            child.classList && child.classList.contains('carbon-fields-frontend-complex-item') &&
            child.tagName !== 'TEMPLATE' && !child.classList.contains('carbon-fields-frontend-template')
        );

        const tabsWrap = repeater.querySelector(':scope > .carbon-fields-frontend-complex-tabs > .carbon-fields-frontend-complex-tab-list');
        const tabAdd = tabsWrap?.querySelector('[data-action="add"]');

        // --- importante: limpar abas existentes (exceto o botão de add) e reconstruir ---
        if (tabsWrap) {
            Array.from(tabsWrap.querySelectorAll(':scope > .carbon-fields-frontend-complex-tab:not([data-action="add"])'))
                .forEach(t => t.remove());
        }

        items.forEach((item, i) => {
            // Atualiza índice local
            item.dataset.index = i;
            item.dataset.depth = depth;

            // hierarquia completa até aqui (parentIndexes já contém índices dos níveis acima deste repeater)
            const currentIndexes = [...parentIndexes, i];

            // Atualiza nomes dos inputs (mesma lógica sua)
            item.querySelectorAll('input, textarea, select').forEach(input => {
                if (!input.name) return;

                let name = input.name;

                const matches = [...name.matchAll(/\[(\d+)\]/g)];
                if (matches.length) {
                    let newName = name;
                    for (let m = matches.length - 1, k = currentIndexes.length - 1; m >= 0 && k >= 0; m--, k--) {
                        const match = matches[m];
                        const start = match.index;
                        const end = start + match[0].length;
                        newName = newName.slice(0, start) + `[${currentIndexes[k]}]` + newName.slice(end);
                    }
                    name = newName;
                }

                input.name = name;
            });

            // cria a aba correspondente (agora partimos de uma lista limpa)
            let tab;
            if (tabsWrap) {
                tab = document.createElement('li');
                tab.className = 'carbon-fields-frontend-complex-tab';
                tabsWrap.insertBefore(tab, tabAdd || null);
                tab.dataset.index = i;
            }

            // Título do tab
            const headerTemplate = repeater.getAttribute('header-template');
            let title = String(i + 1);
            if (headerTemplate) {
                const bindInput = item.querySelector(`[name*="[${headerTemplate}]"]`);
                const raw = bindInput ? String(bindInput.value || '') : '';
                const val = stripHtml(raw).trim();
                title = val !== '' ? val : String(i + 1);
            }
            if (tab) tab.textContent = title;

            // Chamada recursiva para filhos, passando a hierarquia de índices
            const innerRepeaters = Array.from(item.querySelectorAll(':scope > .carbon-fields-frontend-complex-field'));
            innerRepeaters.forEach(inner => reindexRepeater(inner, depth + 1, currentIndexes));
        });
    }


    function activateTab(repeater, index) {
        if (!repeater) return;
        const idx = String(index);

        const tabs = Array.from(repeater.querySelectorAll(':scope > .carbon-fields-frontend-complex-tabs > .carbon-fields-frontend-complex-tab-list > .carbon-fields-frontend-complex-tab:not([data-action="add"])'));
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




});

