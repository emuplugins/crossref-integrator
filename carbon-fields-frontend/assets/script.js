document.addEventListener('DOMContentLoaded', function () {

    const form = document.querySelector('.carbon-fields-frontend-form');

    if (!form) return;

    form.addEventListener('invalid', (event) => {
        event.target.classList.add('required');
        event.target.focus();
    }, true);

    form.querySelectorAll('input, select, textarea').forEach((field) => {
        field.addEventListener('input', (e) => {
            e.target.classList.remove('required');
        });
    });

    form.addEventListener('submit', () => {
        // se usa alguma flag global de primeira validação, resete aqui
        if (typeof firstInvalidFired !== 'undefined') firstInvalidFired = false;
    });

    /* ===== ATIVAÇÃO DE ABAS / CLIQUES ===== */
    document.addEventListener('click', function (e) {

        const clickedTab = e.target.closest('.carbon-fields-frontend-complex-tab');
        if (clickedTab && clickedTab.closest('.carbon-fields-frontend-complex-field')) {
            const repeater = clickedTab.closest('.carbon-fields-frontend-complex-field');
            const index = String(clickedTab.dataset.index);
            activateTab(repeater, index);
            return;
        }

        const addBtn = e.target.closest('.carbon-fields-frontend-complex-tab-add');
        if (addBtn) {
            const repeater = addBtn.closest('.carbon-fields-frontend-complex-field');
            if (repeater) addRepeaterItem(repeater);
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

        // aceita tanto nomes com ...[field] quanto com placeholder __INDEX__ etc.
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
        const template = repeater.querySelector(':scope > .carbon-fields-frontend-complex-items > .carbon-fields-frontend-complex-item.carbon-fields-frontend-template');
        if (!itemsWrap || !template) return;

        // clone template (deep)
        const clone = template.cloneNode(true);

        // remover classe de template e tornar um item real
        clone.classList.remove('carbon-fields-frontend-template');
        clone.classList.add('active');

        // append to DOM (antes de reindexar)
        itemsWrap.appendChild(clone);

        // limpar valores do clone (caso não estejam vazios)
        clone.querySelectorAll('input, select, textarea').forEach(input => {
            // substitui placeholder __INDEX__ se existir (será ajustado de novo no reindex)
            if (input.name && input.name.indexOf('__INDEX__') !== -1) {
                input.name = input.name.replace(/__INDEX__/g, '');
            }
            // limpa valor
            if (input.type === 'checkbox' || input.type === 'radio') {
                input.checked = false;
            } else {
                input.value = '';
                input.removeAttribute('value');
            }
            // limpa atributos que não interessam no clone (opcional)
        });

        // reindexa todo o repeater (vai ajustar data-index, abas e nomes)
        reindexRepeater(repeater);

        // ativa o último item real recém-criado
        const realItems = itemsWrap.querySelectorAll(':scope > .carbon-fields-frontend-complex-item:not(.carbon-fields-frontend-template)');
        const last = realItems[realItems.length - 1];
        if (last) activateTab(repeater, String(last.dataset.index));
    }

    function removeRepeaterItem(button) {
        const item = button.closest('.carbon-fields-frontend-complex-item');
        const repeater = button.closest('.carbon-fields-frontend-complex-field');
        if (!item || !repeater) return;

        const itemsWrap = repeater.querySelector(':scope > .carbon-fields-frontend-complex-items');
        if (!itemsWrap) return;

        // apenas itens reais (exclui o template)
        const realItems = itemsWrap.querySelectorAll(':scope > .carbon-fields-frontend-complex-item:not(.carbon-fields-frontend-template)');
        const minAttr = repeater.getAttribute('data-complex-min');
        const min = minAttr ? parseInt(minAttr, 10) : 0;

        // se houver menos ou igual ao min, bloqueia remoção e pisca (indica obrigatoriedade)
        if (realItems.length <= min) {
            const blinkTimes = 2; // número de piscadas
            const interval = 150; // duração de cada estado em ms
            let count = 0;

            const blink = () => {
                repeater.classList.toggle('required'); // alterna a classe
                count++;
                if (count < blinkTimes * 2) setTimeout(blink, interval);
            };

            blink();
            return; // não remove
        }

        // remove o item normalmente
        item.remove();

        // reindexa itens e abas
        reindexRepeater(repeater);

        // ativa primeiro item real restante
        const firstItem = itemsWrap.querySelector(':scope > .carbon-fields-frontend-complex-item:not(.carbon-fields-frontend-template)');
        if (firstItem) activateTab(repeater, String(firstItem.dataset.index));
    }


    function reindexRepeater(repeater) {
        const itemsWrap = repeater.querySelector(':scope > .carbon-fields-frontend-complex-items');
        if (!itemsWrap) return;

        // apenas itens reais (exclui o template)
        const items = Array.from(itemsWrap.querySelectorAll(':scope > .carbon-fields-frontend-complex-item:not(.carbon-fields-frontend-template)'));
        const tabsWrap = repeater.querySelector(':scope > .carbon-fields-frontend-complex-tabs');
        if (!tabsWrap) return;
        const tabAdd = tabsWrap.querySelector('.carbon-fields-frontend-complex-tab-add');

        // atualiza data-index e abas para cada item real
        items.forEach((item, i) => {
            item.dataset.index = i;

            // atualiza nomes dos inputs do nível atual substituindo o primeiro índice numérico ou placeholder
            item.querySelectorAll('input, textarea, select').forEach(input => {
                if (!input.name) return;

                if (input.name.indexOf('__INDEX__') !== -1) {
                    input.name = input.name.replace(/__INDEX__/g, i);
                } else {
                    input.name = input.name.replace(/\[\d+\]/, '[' + i + ']');
                }
            });

            // cria/atualiza aba correspondente
            let tab = tabsWrap.querySelector(`:scope > .carbon-fields-frontend-complex-tab[data-index="${i}"]`);
            if (!tab) {
                tab = document.createElement('li');
                tab.className = 'carbon-fields-frontend-complex-tab';
                tabsWrap.insertBefore(tab, tabAdd || null);
            }
            tab.dataset.index = i;

            const headerTemplate = repeater.getAttribute('header-template');
            let title = (i + 1);

            if (headerTemplate) {
                // procura qualquer input/textarea/select dentro do item cujo name contenha "[headerTemplate]"
                const allFields = Array.from(item.querySelectorAll('input, textarea, select'));
                const bindInput = allFields.find(el => el.name && el.name.includes('[' + headerTemplate + ']'));

                if (bindInput && bindInput.value.trim() !== '') {
                    title = bindInput.value.trim();
                } else {
                    // se existia um título de aba anterior e ele não era apenas número, mantenha-o
                    const existingTab = tabsWrap.querySelector(`:scope > .carbon-fields-frontend-complex-tab[data-index="${i}"]`);
                    if (existingTab && existingTab.textContent && existingTab.textContent.trim() !== String(i + 1)) {
                        title = existingTab.textContent.trim();
                    }
                }
            }

            tab.textContent = title;
        });

        // remove abas extras (que tenham índices >= items.length)
        Array.from(tabsWrap.querySelectorAll(':scope > .carbon-fields-frontend-complex-tab:not(.carbon-fields-frontend-complex-tab-add)'))
            .filter(tab => parseInt(tab.dataset.index, 10) >= items.length)
            .forEach(tab => tab.remove());

        // reindexa repeaters internos dentro de cada item (recursivo)
        items.forEach(item => {
            const innerRepeaters = Array.from(item.querySelectorAll('.carbon-fields-frontend-complex-field'));
            innerRepeaters.forEach(inner => reindexRepeater(inner));
        });
    }


    function activateTab(repeater, index) {
        if (!repeater) return;
        const idx = String(index);

        const tabs = Array.from(repeater.querySelectorAll(':scope > .carbon-fields-frontend-complex-tabs > .carbon-fields-frontend-complex-tab'));
        const items = Array.from(repeater.querySelectorAll(':scope > .carbon-fields-frontend-complex-items > .carbon-fields-frontend-complex-item:not(.carbon-fields-frontend-template)'));

        tabs.forEach(tab => tab.classList.toggle('active', String(tab.dataset.index) === idx));
        items.forEach(item => item.classList.toggle('active', String(item.dataset.index) === idx));

        // ao ativar, garanta que inner repeaters abram seu primeiro item
        const activeItem = repeater.querySelector(`:scope > .carbon-fields-frontend-complex-items > .carbon-fields-frontend-complex-item[data-index="${idx}"]`);
        if (activeItem) {
            const innerRepeaters = Array.from(activeItem.querySelectorAll(':scope > .carbon-fields-frontend-complex-field, .carbon-fields-frontend-complex-field'));
            innerRepeaters.forEach(inner => {
                const firstInner = inner.querySelector(':scope > .carbon-fields-frontend-complex-items > .carbon-fields-frontend-complex-item:not(.carbon-fields-frontend-template):first-of-type');
                if (!firstInner) return;
                // ativa primeiro inner tab
                const firstIndex = String(firstInner.dataset.index);
                activateTab(inner, firstIndex);
            });
        }
    }

});




document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form.carbon-fields-frontend-form');
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const data = new FormData(form);
        data.append('action', 'carbon_fields_frontend_form' + form.id);
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: data
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    alert('Formulário enviado com sucesso!');
                } else {
                    alert('Erro no envio.');
                }
            });
    });


    document.addEventListener('focusin', (e) => {
        if (['INPUT', 'SELECT', 'TEXTAREA'].includes(e.target.tagName)) {
            e.target.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
        }
    });

});