<?php

class CrossrefFormBuilder
{
    protected array $containers;

    public function __construct(array $containers)
    {
        $this->containers = $containers;
    }

    public function render(): string
    {
        $html = '<form method="post" class="crossref-frontend-form">';

        foreach ($this->containers as $container) {

            $html .= '<div class="crossref-field-group"> <h3>' . ($container['title'] ?? '') . '</h3>';

            foreach ($container['fields'] as $field) {

                $html .= $this->renderField($field, '', null, 0); // depth inicial = 0
            }

            $html .= '</div>';
        }
        $html .= '<button type="submit">Enviar Formulário</button></form>';
        return $html;
    }

    protected function renderField(array $field, string $path = '', ?int $index = null, int $depth = 0): string
    {
        if ($field['type'] === 'repeater' && !isset($field['width'])) {
            $field['width'] = 100;
        }

        $html = '<div class="crossref-field" style="' . (isset($field['width']) ? 'width:' . $field['width'] . '%' : '') . '">';

        switch ($field['type']) {
            case 'repeater':
                $html .= $this->fieldRepeater($field, $path, $index, $depth);
                break;
            case 'text':
            case 'url':
            case 'email':
            case 'date':
            case 'tel':
            case 'number':
                $html .= $this->fieldInput($field, $path, $index);
                break;
            case 'select':
                $html .= $this->fieldSelect($field, $path, $index);
                break;
            case 'file':  // Novo tipo
                $html .= $this->fieldFile($field, $path, $index);
                break;
            case 'textarea':
                $html .= $this->fieldTextarea($field, $path, $index);
                break;
        }

        $html .= '</div>';
        return $html;
    }
    protected function fieldFile(array $field, string $path, ?int $index): string
    {
        if ($path !== '') {
            $pathForName = $index !== null ? str_replace('__INDEX__', (string)$index, $path) : $path;
            $name = $pathForName . '[' . $field['name'] . ']';
        } else {
            $name = $field['name'];
        }

        return '<label class="crossref-label">' . htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8') . '</label>' .
            '<input type="file" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '">';
    }

    protected function fieldSelect(array $field, string $path, ?int $index): string
    {
        if ($path !== '') {
            $pathForName = $index !== null ? str_replace('__INDEX__', (string)$index, $path) : $path;
            $name = $pathForName . '[' . $field['name'] . ']';
        } else {
            $name = $field['name'];
        }

        $value = $field['value'] ?? '';
        $options = $field['options'] ?? []; // Array de opções ['valor' => 'label']

        $html = '<label class="crossref-label">' . htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8') . '</label>';
        $html .= '<select name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '">';
        foreach ($options as $optValue => $optLabel) {
            $selected = ($value == $optValue) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($optValue, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>' .
                htmlspecialchars($optLabel, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        $html .= '</select>';

        return $html;
    }


    protected function fieldRepeater(array $field, string $path, ?int $index, int $depth): string
    {
        $titleBind = $field['title_bind'] ?? null;
        $hasFields = !empty($field['fields']) && is_array($field['fields']);

        // atributo que será renderizado no HTML (data-repeater-required)
        $requiredAttr = isset($field['required']) ? 'data-repeater-required="true"' : '';

        $repeaterId = 'repeater-' . md5($field['name'] . uniqid('', true));
        $createInitialItem = false;
        $tabTitle = ++$index;

        // se há um campo que corresponde ao title_bind e tem valor, usamos esse valor como gatilho
        if ($hasFields && $titleBind) {
            foreach ($field['fields'] as $subField) {
                if (($subField['type'] ?? null) === 'text' && ($subField['name'] ?? null) === $titleBind && !empty($subField['value'])) {
                    $createInitialItem = true;
                    $tabTitle = $subField['value'];
                    break;
                }
            }
        }

        // se não foi criado por title_bind, criar item inicial se o repeater for required
        $isRequiredFlag = isset($field['required']) && $field['required'];
        if (!$createInitialItem && $isRequiredFlag) {
            $createInitialItem = true;
        }


        $html  = '<div class="crossref-repeater"';
        $html .= ' data-name="' . htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') . '"';
        $html .= $titleBind ? ' data-title-bind="' . htmlspecialchars($titleBind, ENT_QUOTES, 'UTF-8') . '"' : '';
        $html .= ' data-repeater-id="' . htmlspecialchars($repeaterId, ENT_QUOTES, 'UTF-8') . '"';
        $html .= ' data-depth="' . $depth . '"';
        $html .= $requiredAttr;
        $html .= '>';

        $html .= '<label class="crossref-label">' . htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8') . '</label>';

        /* TABS */
        $html .= '<ul class="crossref-repeater-tabs">';
        if ($createInitialItem) {
            $html .= '<li class="crossref-tab active" data-index="0">' . htmlspecialchars($tabTitle, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $html .= '<li class="crossref-tab-add" data-action="add">+</li>';
        $html .= '</ul>';

        /* ITEMS */
        $html .= '<div class="crossref-repeater-items">';

        // ITEM INICIAL
        if ($createInitialItem) {
            $base = $path ? $path . '[' . $field['name'] . '][0]' : $field['name'] . '[0]';
            $html .= '<div class="crossref-repeater-item active" data-index="0" data-depth="' . ($depth + 1) . '">';
            foreach ($field['fields'] as $subField) {
                $html .= $this->renderField($subField, $base, 0, $depth + 1);
            }
            $html .= '<div class="crossref-remove-wrapper"><button type="button" class="crossref-remove">Remover ' . (isset($field['singular_name']) ? $field['singular_name'] : '') . '</button></div>';
            $html .= '</div>';
        }

        // TEMPLATE INVISÍVEL
        $templateBase = $path ? $path . '[' . $field['name'] . '][__INDEX__]' : $field['name'] . '[__INDEX__]';
        $html .= '<div class="crossref-repeater-item crossref-template" data-index="__INDEX__" data-template-for="' . htmlspecialchars($repeaterId, ENT_QUOTES, 'UTF-8') . '" data-depth="' . ($depth + 1) . '">';
        foreach ($field['fields'] as $subField) {
            unset($subField['required']);
            $html .= $this->renderField($subField, $templateBase, null, $depth + 1);
        }
        $html .= '<div class="crossref-remove-wrapper"><button type="button" class="crossref-remove">Remover ' . (isset($field['singular_name']) ? $field['singular_name'] : '') . '</button></div>';
        $html .= '</div>'; // template

        $html .= '</div>'; // .crossref-repeater-items

        // só mostra a mensagem "ainda não tem nenhum registro" se não houver item inicial
        if (!$createInitialItem) {
            $html .= '<div class="crossref-repeater-add-first"><p>Este item ainda não tem nenhum registro.</p> <button type="button" class="crossref-tab-add" data-action="add">Adicionar ' . (isset($field['singular_name']) ? $field['singular_name'] : '') . '</button></div>';
        }

        $html .= '</div>'; // repeater

        return $html;
    }



    protected function fieldInput(array $field, string $path, ?int $index): string
    {
        if ($path !== '') {
            $pathForName = $index !== null ? str_replace('__INDEX__', (string)$index, $path) : $path;
            $name = $pathForName . '[' . $field['name'] . ']';
        } else {
            $name = $field['name'];
        }

        $value = $field['value'] ?? '';
        $type = $field['type']; // Pode ser text, url, email, date, tel, number
        $required = isset($field['required']) ? 'required' : '';

        return '<label class="crossref-label">' . htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8') . ($required ? '<span style="color:red"> *</span>' : '') . '</label>' .
            '<input type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '" name="' .
            htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' .
            htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"
             ' . htmlspecialchars($required, ENT_QUOTES, 'UTF-8') . '>';
    }

    protected function fieldTextarea(array $field, string $path, ?int $index): string
    {
        if ($path !== '') {
            $pathForName = $index !== null ? str_replace('__INDEX__', (string)$index, $path) : $path;
            $name = $pathForName . '[' . $field['name'] . ']';
        } else {
            $name = $field['name'];
        }

        $value = $field['value'] ?? '';
        $rows  = $field['rows'] ?? 6;
        $cols  = $field['cols'] ?? 40;

        return '<label class="crossref-label">' . htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8') . '</label>' .
            '<textarea name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" ' .
            'rows="' . intval($rows) . '" cols="' . intval($cols) . '">' .
            htmlspecialchars($value, ENT_QUOTES, 'UTF-8') .
            '</textarea>';
    }
}










add_shortcode(
    'crossref_frontend_chapter_form',
    'crossref_frontend_chapter_form_shortcode'
);

function crossref_frontend_chapter_form_shortcode($atts = [], $content = null)
{
    ob_start();


    $fields = [];

    $leadInfo = [
        [
            'title' => 'Sobre Você',
            'fields' => [
                [
                    'type'  => 'text',
                    'label' => 'Nome e Sobrenome',
                    'name'  => '_lead_name',
                    'placeholder' => 'João Santos da Silva',
                    'width' => 100,
                ],
                [
                    'type'  => 'email',
                    'label' => 'Email',
                    'name'  => '_lead_email',
                    'placeholder' => 'contato@gmail.com *',
                    'required' => true,
                    'width' => 40,
                ],
                [
                    'type'  => 'text',
                    'label' => 'Telefone / Celular',
                    'name'  => '_lead_phone',
                    'placeholder' => '+55 11 9 0000-0000',
                    'width' => 40,
                ],
                [
                    'type'  => 'text',
                    'label' => 'Currículo Lattes',
                    'name'  => '_lead_lattes',
                    'placeholder' => 'https://lattes.cnpq.br/0000000000000000',
                    'width' => 40,
                ],
                [
                    'type'  => 'text',
                    'label' => 'CPF',
                    'name'  => '_lead_cpf',
                    'placeholder' => '000.000.000-00',
                    'width' => 40,
                ],
            ]
        ]
    ];


    $bookDetails = [
        'title' => 'Detalhes do Livro',
        'fields' => [

            [
                'type'  => 'textarea',
                'label' => 'Abstract (JATS)',
                'name'  => 'jats_abstract',
                'help'  => 'Structured abstract in JATS format, or plain text. May include paragraphs and semantic markup.',
                'required' => true,
            ],
            [
                'type'  => 'text',
                'label' => 'ISBN (Electronic Version)',
                'name'  => 'isbn_e',
                'width' => 33,
                'help'  => 'International standard that uniquely identifies this work.',
            ],
            [
                'type'  => 'text',
                'label' => 'ISBN (Print Version)',
                'name'  => 'isbn_p',
                'width' => 33,
                'help'  => 'International standard that uniquely identifies this work.',
            ],
            [
                'type'  => 'number',
                'label' => 'Edition',
                'name'  => 'edition_number',
                'width' => 33,
                'help'  => 'Edition number of this work.',
                'required' => true,
                'attributes' => [
                    'min' => 1,
                    'step' => 1,
                ],
            ],
            [
                'type'  => 'date',
                'label' => 'Online Publication Date',
                'name'  => 'online_publication_date',
                'width' => 33,
                'required' => true,
                'storage_format' => 'Y-m-d',
            ],
            [
                'type'  => 'date',
                'label' => 'Print Publication Date',
                'name'  => 'print_publication_date',
                'width' => 33,
                'storage_format' => 'Y-m-d',
            ],
            [
                'type'  => 'select',
                'label' => 'Language',
                'name'  => 'language',
                'width' => 33,
                'options' => [
                    'pt' => 'Portuguese',
                    'en' => 'English',
                    'es' => 'Spanish',
                    'fr' => 'French',
                    'de' => 'German',
                    'it' => 'Italian',
                    'zh' => 'Chinese',
                    'ja' => 'Japanese',
                    'ru' => 'Russian',
                    'ar' => 'Arabic',
                ],
                'default'  => 'pt',
                'required' => true,
            ],
        ]
    ];

    $contributors = [
        'title' => 'Contribuintes',
        'fields' => [
            [
                'type'       => 'repeater',
                'label'      => 'Grupos de Contribuintes',
                'name'       => '_contributor_groups',
                'title_bind' => 'group_title',
                'required' => 'true',
                'singular_name' => 'grupo',
                'fields'     => [
                    [
                        'type'  => 'text',
                        'label' => 'Nome do Grupo',
                        'name' => 'group_title',
                        'width' => 33,
                        'value' => 'Autores',
                        'required' => 'true'
                    ],
                    [
                        'type'  => 'select',
                        'label' => 'Função dos integrantes',
                        'name' => 'role',
                        'width' => 33,
                        'value' => 'author',
                        'options' => [
                            'author' => 'Autores',
                            'editor' => 'Editores',
                            'chair' => 'Presidentes',
                            'reviewer' => 'Revisores',
                            'review-assistant' => 'Assistentes de Revisão',
                            'stats-reviewer' => 'Revisores de Estatísticas',
                            'reviewer-external' => 'Revisores Externos',
                            'reader' => 'Leitores',
                            'translator' => 'Tradutores',
                        ]
                    ],
                    [
                        'type'       => 'repeater',
                        'label'      => 'Integrantes do Grupo',
                        'name'       => '_contributors',
                        'title_bind' => '_given',
                        'required' => 'true',
                        'singular_name' => 'integrante',
                        'fields' => [
                            [
                                'type'  => 'text',
                                'label' => 'Nome do Integrante',
                                'name' => '_given',
                                'width' => 20,
                                'required' => true,
                            ],
                            [
                                'type'  => 'text',
                                'label' => 'Sobrenome (apenas pessoas)',
                                'name' => '_surname',
                                'width' => 20,
                            ],
                            [
                                'type'  => 'text',
                                'label' => 'ORCID (apenas pessoas)',
                                'name' => '_orcid',
                                'width' => 20,
                            ],
                            [
                                'type'  => 'text',
                                'label' => 'Currículo Lattes',
                                'name' => '_lattes',
                                'width' => 20,
                            ],
                            [
                                'type'  => 'textarea',
                                'label' => 'Biografia',
                                'name' => '_bio',
                                'width' => 100,
                            ],
                            [
                                'type'       => 'repeater',
                                'label'      => 'Afiliações',
                                'name'       => '_affiliations',
                                'title_bind' => '_institution_name',
                                'singular_name' => 'afiliação',
                                'fields' => [
                                    [
                                        'type'  => 'text',
                                        'label' => 'Nome da Instituição',
                                        'name' => '_institution_name',
                                    ],
                                    [
                                        'type'  => 'text',
                                        'label' => 'ID da instituição',
                                        'name' => '_institution_id',
                                    ],
                                    [
                                        'type'  => 'select',
                                        'label' => 'Tipo do Identificador',
                                        'name' => 'role',
                                        'width' => 33,
                                        'value' => 'RoR',
                                        'options' => [
                                            'ror' => 'RoR',
                                            'wikidata' => 'Wikidata',
                                            'isni' => 'ISNI',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]
    ];

    $citations = [
        'title' => 'Citações',
        'fields' => [

            [
                'type'   => 'repeater',
                'label'  => 'Citations',
                'name'   => 'citation_list',
                'layout' => 'tabbed-horizontal',
                'min'    => 1,
                'help'   => 'Articles, books, and other content cited by the registered item.',
                'fields' => [
                    [
                        'type'     => 'text',
                        'label'    => 'Citation',
                        'name'     => 'unstructured_citation',
                        'width'    => 25,
                        'required' => true,
                    ],
                    [
                        'type'  => 'text',
                        'label' => 'Work DOI',
                        'name'  => 'doi',
                        'width' => 25,
                    ],
                ],
                'header_template' => '<%- unstructured_citation %>',
            ],
        ]
    ];



    // === montagem correta dos containers ===
    $fields = array_merge(
        $leadInfo,
        [$bookDetails],    // envolver em array numerado
        [$contributors],
        [$citations]
    );

    $builder = new CrossrefFormBuilder($fields);

    echo $builder->render();

?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const form = document.querySelector('.crossref-frontend-form');

            form.addEventListener('invalid', (event) => {
                event.target.classList.add('required');
                event.target.focus(); // foca no campo inválido

            }, true);

            form.querySelectorAll('input, select, textarea').forEach((field) => {
                field.addEventListener('input', (e) => {
                    e.target.classList.remove('required');
                });
            });

            form.addEventListener('submit', () => {
                firstInvalidFired = false; // reseta para próxima submissão
            });

            /* ===== ATIVAÇÃO DE ABAS / CLIQUES ===== */
            document.addEventListener('click', function(e) {

                // clicar em uma aba (aceita clicks em elementos filhos)
                const clickedTab = e.target.closest('.crossref-tab');
                if (clickedTab && clickedTab.closest('.crossref-repeater')) {
                    const repeater = clickedTab.closest('.crossref-repeater');
                    const index = String(clickedTab.dataset.index); // normaliza para string
                    activateTab(repeater, index);

                    // garante que o item correspondente também tenha a classe active
                    repeater.querySelectorAll(':scope > .crossref-repeater-items > .crossref-repeater-item:not(.crossref-template)')
                        .forEach(item => item.classList.toggle('active', String(item.dataset.index) === index));

                    return; // evita cair em outros handlers
                }

                // adicionar item (aceita click em elementos filhos do botão)
                const addBtn = e.target.closest('.crossref-tab-add');
                if (addBtn) {
                    const repeater = addBtn.closest('.crossref-repeater');
                    if (repeater) addRepeaterItem(repeater);
                    return;
                }

                // remover item (aceita click em elementos filhos do botão)
                const removeBtn = e.target.closest('.crossref-remove');
                if (removeBtn) {
                    removeRepeaterItem(removeBtn);
                    return;
                }
            });

            /* ===== ATUALIZA TÍTULO DINAMICAMENTE ===== */
            document.addEventListener('input', function(e) {
                const input = e.target;
                if (!input.name) return;

                const item = input.closest('.crossref-repeater-item');
                const repeater = input.closest('.crossref-repeater');
                if (!item || !repeater) return;

                const titleBind = repeater.dataset.titleBind;
                if (!titleBind) return;

                if (!input.name.endsWith('[' + titleBind + ']')) return;

                const index = String(item.dataset.index);
                const tab = repeater.querySelector('.crossref-tab[data-index="' + index + '"]');
                if (!tab) return;

                const value = input.value.trim();
                tab.textContent = value !== '' ? value : (parseInt(index, 10) + 1);
            });

            /* ===== FUNÇÕES ===== */

            function addRepeaterItem(repeater) {
                const repeaterId = repeater.dataset.repeaterId;
                const template = repeater.querySelector(`.crossref-template[data-template-for="${repeaterId}"]`);
                if (!template) return;

                const itemsWrap = repeater.querySelector(':scope > .crossref-repeater-items');
                if (!itemsWrap) return;

                const clone = template.cloneNode(true);
                clone.classList.remove('crossref-template');

                resetRepeaterItem(clone);

                // adiciona o clone ao DOM
                itemsWrap.appendChild(clone);

                // reindexa todos os itens (fonte da verdade)
                reindexRepeater(repeater);

                // ativa o último item real recém-criado (usa dataset real após reindex)
                const lastItem = itemsWrap.querySelector(':scope > .crossref-repeater-item:not(.crossref-template):last-of-type');
                if (lastItem) {
                    activateTab(repeater, String(lastItem.dataset.index));
                }
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
                const itemsWrap = repeater.querySelector(':scope > .crossref-repeater-items');
                if (!itemsWrap) return;

                const items = Array.from(itemsWrap.querySelectorAll(':scope > .crossref-repeater-item:not(.crossref-template)'));
                const tabsWrap = repeater.querySelector(':scope > .crossref-repeater-tabs');
                if (!tabsWrap) return;
                const tabAdd = tabsWrap.querySelector('.crossref-tab-add');

                // atualiza nomes e data-index dos itens
                items.forEach((item, index) => {
                    item.dataset.index = String(index);

                    item.querySelectorAll('input, textarea, select').forEach(input => {
                        // substitui placeholders __INDEX__
                        if (input.name && input.name.indexOf('__INDEX__') !== -1) {
                            input.name = input.name.replace(/__INDEX__/g, index);
                        }
                        // atualiza o índice do nível atual (substitui a primeira ocorrência [n])
                        if (input.name) {
                            input.name = input.name.replace(/\[\d+\]/, '[' + index + ']');
                        }
                    });
                });

                // garante que exista uma aba por item na ordem correta
                let existingTabs = Array.from(tabsWrap.querySelectorAll(':scope > .crossref-tab:not(.crossref-tab-add)'));

                // criar/atualizar abas para cada item
                for (let i = 0; i < items.length; i++) {
                    let tab = existingTabs[i];
                    if (!tab) {
                        tab = document.createElement('li');
                        tab.className = 'crossref-tab';
                        // insere antes do botão add; se tabAdd for null, insertBefore com null faz append
                        tabsWrap.insertBefore(tab, tabAdd || null);
                        existingTabs = Array.from(tabsWrap.querySelectorAll(':scope > .crossref-tab:not(.crossref-tab-add)'));
                    }

                    tab.dataset.index = String(i);

                    // obtém título a partir do item correspondente
                    const item = items[i];
                    let title = '';

                    const titleBind = repeater.dataset.titleBind;
                    if (titleBind) {
                        const bindInput = item.querySelector('input[name$="[' + titleBind + ']"]');
                        if (bindInput && bindInput.value.trim() !== '') {
                            title = bindInput.value.trim();
                        }
                    }

                    tab.textContent = title !== '' ? title : (i + 1);
                }

                // remove abas extras (se houver)
                existingTabs = Array.from(tabsWrap.querySelectorAll(':scope > .crossref-tab:not(.crossref-tab-add)'));
                if (existingTabs.length > items.length) {
                    for (let j = existingTabs.length - 1; j >= items.length; j--) {
                        existingTabs[j].remove();
                    }
                }

                // reindexa repeaters internos por item (aplica recursivamente a todos os repeaters dentro do item)
                items.forEach(item => {
                    const innerRepeaters = Array.from(item.querySelectorAll('.crossref-repeater'));
                    innerRepeaters.forEach(inner => reindexRepeater(inner));
                });
            }

            function resetRepeaterItem(item) {
                /* limpa inputs/values no item clonado */
                item.querySelectorAll('input').forEach(input => {
                    input.value = '';
                    input.removeAttribute('value');

                    if (input.type === 'checkbox' || input.type === 'radio') {
                        input.checked = false;
                    }
                });

                item.querySelectorAll('select').forEach(select => {
                    select.selectedIndex = 0;
                });

                item.querySelectorAll('textarea').forEach(textarea => {
                    textarea.value = '';
                });

                /* para repeaters internos, preserve o template e remova apenas itens reais e tabs (não o botão add) */
                item.querySelectorAll('.crossref-repeater').forEach(inner => {
                    const tabs = inner.querySelector('.crossref-repeater-tabs');
                    const items = inner.querySelector('.crossref-repeater-items');

                    if (tabs) {
                        // remove todas as abas exceto o botão de adicionar
                        tabs.querySelectorAll('.crossref-tab:not(.crossref-tab-add)').forEach(tab => tab.remove());
                    }

                    if (items) {
                        // remove apenas itens reais; preserva .crossref-template
                        items.querySelectorAll('.crossref-repeater-item:not(.crossref-template)').forEach(it => it.remove());
                    }
                });
            }

            function activateTab(repeater, index) {
                if (!repeater) return;
                const idx = String(index); // garante comparação string-string

                // ativa apenas a aba selecionada (apenas filhos diretos das tabs)
                const tabs = repeater.querySelectorAll(':scope > .crossref-repeater-tabs > .crossref-tab');
                tabs.forEach(tab => tab.classList.toggle('active', String(tab.dataset.index) === idx));

                // ativa apenas o item correspondente
                const items = repeater.querySelectorAll(':scope > .crossref-repeater-items > .crossref-repeater-item:not(.crossref-template)');
                items.forEach(item => item.classList.toggle('active', String(item.dataset.index) === idx));

                // garante que os repeaters internos do item ativado também abram o primeiro item
                const activeItem = repeater.querySelector(`:scope > .crossref-repeater-items > .crossref-repeater-item[data-index="${idx}"]`);
                if (activeItem) {
                    const innerRepeaters = activeItem.querySelectorAll('.crossref-repeater');
                    innerRepeaters.forEach(inner => {
                        const firstInnerItem = inner.querySelector(':scope > .crossref-repeater-items > .crossref-repeater-item:not(.crossref-template):first-of-type');
                        if (!firstInnerItem) return;

                        const firstInnerIndex = String(firstInnerItem.dataset.index);

                        inner.querySelectorAll(':scope > .crossref-repeater-items > .crossref-repeater-item').forEach(it => it.classList.remove('active'));
                        inner.querySelectorAll(':scope > .crossref-repeater-tabs > .crossref-tab').forEach(tab => tab.classList.remove('active'));

                        firstInnerItem.classList.add('active');
                        const firstInnerTab = inner.querySelector(`:scope > .crossref-repeater-tabs > .crossref-tab[data-index="${firstInnerIndex}"]`);
                        if (firstInnerTab) firstInnerTab.classList.add('active');
                    });
                }
            }

        });
    </script>



    <style>
        .crossref-frontend-form {
            --border-color: #d1d1d1ff;
            --containers-background: #fff;
            display: flex;
            flex-direction: column;
            flex-wrap: wrap;
            /* horizontal */
            gap: 16px;


            & input {
                width: 100%;
            }

            & .crossref-field {
                width: 100%;
                flex-grow: 1
            }

            & .crossref-field-group {
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                /* horizontal */
                gap: 16px;

                border: 1px solid var(--border-color);
                padding: 16px;
            }

            & * {
                transition: border-color 0.5s ease;
            }
        }

        /* ===== REPEATER ===== */
        .crossref-repeater {
            flex-wrap: wrap;
            background: var(--containers-background);

        }

        .crossref-repeater-items {

            background: var(--containers-background);
        }



        /* ===== TABS ===== */
        .crossref-repeater-tabs {
            display: flex;
            gap: 12px;
            padding: 0;
            list-style: none;
        }

        .crossref-tab,
        .crossref-tab-add {
            padding: 6px 10px;
            cursor: pointer;
            border: 1px solid var(--border-color);
            border-bottom: none;
            background: #f5f5f5;
        }

        .crossref-tab.active {
            background: var(--containers-background);
            font-weight: bold;
            position: relative;

            &:after {
                position: absolute;
                bottom: -2px;
                left: 0;
                width: 100%;
                height: 5px;
                background: var(--containers-background);
                content: ""
            }
        }

        /* ===== CONTEÚDOS ===== */
        .crossref-repeater-item {
            display: none;
            border: 1px solid var(--border-color);
            padding: 18px;
            background: var(--containers-background);
            gap: 12px;
        }

        .crossref-repeater-item.active {
            display: flex;
            flex-wrap: wrap;
        }

        .crossref-label {
            width: 100%;
            margin-bottom: 12px;
        }

        /* ===== LAYOUT POR PROFUNDIDADE ===== */
        .crossref-repeater[data-depth="0"] {
            display: flex;
            flex-direction: column;

        }

        .crossref-repeater[data-depth="1"] {
            display: flex;
            flex-direction: column;

        }


        .crossref-repeater[data-depth="2"] {
            display: flex;
            flex-direction: row;
            /* horizontal */
            gap: 0 16px;

            & .crossref-repeater-tabs {
                display: flex;
                flex-direction: column;
                width: 25%;
            }

            & .crossref-tab,
            .crossref-tab-add {

                border-bottom: 1px solid var(--border-color);
                word-break: break-word;
                /* força quebra de palavras longas */
                overflow-wrap: break-word;
                /* garante compatibilidade */
                hyphens: auto;
                /* opcional, adiciona hífens se suportado */
                position: relative;


            }

            & .crossref-repeater-tabs {

                border-bottom: none;
            }

            .crossref-repeater-items {
                flex-grow: 1;
                width: 50%
            }

        }

        .crossref-repeater[data-depth="3"] {
            display: flex;
            flex-direction: column;

        }

        .crossref-remove-wrapper {
            width: 100%;
        }

        .crossref-repeater-add-first {
            display: none;
        }

        .crossref-repeater:has(> .crossref-repeater-items > .crossref-repeater-item:only-child) {

            &>.crossref-repeater-tabs>.crossref-tab-add {
                display: none;
            }

            & .crossref-repeater-add-first {
                display: block;
            }
        }

        input,
        select,
        textarea {
            scroll-margin-top: 50vh;
            border: none !important;
            outline: 1px solid var(--border-color);
            /* muda a cor do outline */

            &.required {
                outline: 1px solid #ff4343ff;

                background-color: #fcf2f2ff;
            }
        }

        .required {
            /* amarelo claro */
            transition: background-color 0.5s ease;

            &>.crossref-repeater-tabs>.crossref-tab.active,
            >.crossref-repeater-items>.crossref-repeater-item {

                border-color: #ff4343ff !important;
                --containers-background: #fcf2f2ff;

            }

            /* muda a cor do outline */
        }

        /* Para profundidades maiores, continue alternando row/column conforme quiser */
    </style>

<?php


    return ob_get_clean();
}
