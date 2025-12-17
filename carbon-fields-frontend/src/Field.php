<?php

namespace Carbon_Fields\Frontend;

class Field
{
    protected string $type;
    protected string $name;
    protected mixed $label;

    // (adicionar junto às propriedades da classe)
    protected int $min = 0;


    protected array $attributes = [];
    protected mixed $default_value = null;
    protected array $initial_values = [];

    protected array $fields = [];

    // Caminho do campo para salvar
    // É somado a cada nivel de campos 
    // inicia como $this->name . '[$index]'
    protected string $path = '';



    // set functions
    protected string $layout = 'tabbed-horizontal';
    protected bool $required = false;
    protected int $width = 100;
    protected string $mask = '';
    protected string $mimeTypes = '';
    protected string $maskValidation = '';
    protected int $depth = 0;
    protected int $index = 0;
    protected mixed $header_template = false;
    protected string $help_text = '';
    protected array $options = [];

    protected function __construct(string $type, string $name, $label = false)
    {
        $this->type  = $type;
        $this->name  = $name;
        $this->label = $label;

        $this->set_path();
    }

    public static function make(string $type, string $name, string $label): self
    {
        return new self($type, $name, $label);
    }

    public function set_attribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    // alterar a função set_min para gravar o valor
    public function set_min(int $value): self
    {
        $this->min = max(0, (int) $value);
        return $this;
    }

    public function set_default_values($values): self
    {
        $this->initial_values = $values ?: [];
        return $this;
    }

    public function set_width($number)
    {
        $this->width = $number;
        return $this;
    }

    public function set_help_text(string $text): self
    {
        $this->help_text = $text;
        return $this;
    }

    public function set_required($value = false)
    {
        $this->required = $value;
        return $this;
    }

    public function set_mime_types($value = false)
    {
        $this->mimeTypes = $value;
        return $this;
    }

    public function set_mask($value = false)
    {
        if (is_string($value)) {
            $value = str_replace("'", '"', $value);
        }

        $this->mask = $value;
        return $this;
    }


    public function set_mask_validation($value = false)
    {
        $this->maskValidation = $value;
        return $this;
    }

    public function set_options(callable|array $options): self
    {
        if (is_callable($options)) {
            $options = $options(); // executa o callable e retorna o array
        }

        $this->options = $options;
        return $this;
    }

    public function set_layout($value)
    {
        $this->layout = $value;
        return $this;
    }

    // Apenas use se você sabe o que está fazendo
    public function set_depth($value)
    {
        $this->depth = $value;
        return $this;
    }

    // Usado para quando um complex field tem subcampos, essa função adiciona o path do complex field, em seus subcampos, e assim consecutivamente
    public function set_path(string $base = '', $isFirst = true, $isLast = false): self
    {
        $path = $base;

        // ['parent'][0]
        if (!empty($base)) {
            $path .= '[' . $this->index . ']';
        }

        // ['parent'][0]['name']
        $path .= '[' . $this->name . ']';

        // $this->path = $base . '[' . $this->name . '][' . $this->index . ']';
        $this->path = $path;
        return $this;
    }

    public function set_index($value)
    {
        $this->index = $value;
        return $this;
    }

    public function set_header_template($value)
    {
        $cleaned = trim(str_replace(['<', '>', '%', '-'], '', $value));

        $this->header_template = trim($cleaned);
        return $this;
    }

    public function add_fields($field)
    {
        $this->fields = [...$this->fields, ...((array)$field)];
        return $this;
    }

    public function set_default_value(mixed $value): self
    {
        $this->default_value = $value;
        return $this;
    }

    public function render(): string
    {

        $help_text = !empty($this->help_text) ? ('<small class="carbon-fields-frontend-help">' . htmlspecialchars($this->help_text, ENT_QUOTES, 'UTF-8')) . '</small>' : '';
        $html = '<div class="carbon-fields-frontend-field" style="width:' . $this->width . '%">';

        if ($this->type === 'complex') {

            // Estrutura inicial diferente
            $html = $this->fieldComplex($help_text);
        } elseif ($this->type === 'rich_text') {
            $html .= $this->fieldRichText();
            $html .= $help_text;
            $html .= '</div>';
        } else {
            // qualquer outro tipo usa fieldInput (inclui select, textarea, inputs, etc.)
            $html .= $this->fieldInput();
            $html .= $help_text;
            $html .= '</div>';
        }



        return $html;
    }

    protected function fieldRichText()
    {
        $name = $this->path;
        $value = $this->default_value ?? '';
        $required = $this->required ? 'required' : '';

        $html = '';

        if ($this->label) {

            // Label
            $html  .= '<label class="carbon-fields-frontend-label">'
                . htmlspecialchars($this->label)
                . ($required ? '<span style="color:red"> *</span>' : '')
                . '</label>';
        }

        // Textarea simples
        $html .= '<textarea name="carbon_fields_frontend' . esc_attr($name) . '" class="frontend-richtext" rows="8" ' . $required . '>'
            . esc_textarea($value)
            . '</textarea>';

        // Enfileira scripts do editor
        if (!is_admin()) {
            wp_enqueue_editor(); // apenas carrega TinyMCE scripts
        }

        return $html;
    }



    protected function fieldComplex($help_text)
    {
        // Identificador único para o complex
        $dataComplexID = 'complex-' . md5($this->name . uniqid('', true));

        // Define os itens a partir de initial_values
        $items = !empty($this->initial_values) ? $this->initial_values : [];

        // Se não houver items mas houver min definido (ou required), crie items vazios
        if (empty($items) && ($this->required || $this->min > 0)) {
            $count = max(1, $this->min);
            for ($m = 0; $m < $count; $m++) {
                $emptyItem = [];
                foreach ($this->fields as $subField) {
                    $emptyItem[$subField->name] = $subField->default_value ?? null;
                }
                $items[] = $emptyItem;
            }
        }


        // Início do HTML
        $html  = '<div class="carbon-fields-frontend-field carbon-fields-frontend-complex-field ' . $this->layout . '"';
        $html .= ' data-name="carbon_fields_frontend' . htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8') . '"';
        $html .= $this->header_template ? ' header-template="' . htmlspecialchars($this->header_template, ENT_QUOTES, 'UTF-8') . '"' : '';
        $html .= ' data-complex-id="' . htmlspecialchars($dataComplexID, ENT_QUOTES, 'UTF-8') . '"';
        $html .= ' data-depth="' . $this->depth . '"';
        $html .= ' data-complex-min="' . $this->min . '"';
        $html .= $this->required ? ' data-complex-required="true"' : '';
        $html .= '>';

        if($this->label) {
            // Label do campo
            $html .= '<label class="carbon-fields-frontend-label">' . htmlspecialchars($this->label, ENT_QUOTES, 'UTF-8') . '</label>';
        }

        /* TABS */
        $html .= '<div class="carbon-fields-frontend-complex-tabs">';
        $html .= '<ul class="carbon-fields-frontend-complex-tab-list">';
        $tabIndex = 0; // índice local do loop
        foreach ($items as $item) {
            $tabTitle = $this->header_template && isset($item[$this->header_template]) ? $item[$this->header_template] : $tabIndex + 1;

            $active = $tabIndex === 0 ? ' active' : '';
            $html .= '<li class="carbon-fields-frontend-complex-tab' . $active . '" data-index="' . $tabIndex . '">'
                . htmlspecialchars($tabTitle, ENT_QUOTES, 'UTF-8') . '</li>';
            $tabIndex++;
        }
        $html .= '</ul>';
        $html .= '<div class="carbon-fields-frontend-complex-tab-add" data-action="add" data-depth="' . ($this->depth) . '">+</div>';
        $html .= '</div>';

        /* ITENS */
        $html .= '<div class="carbon-fields-frontend-complex-items">';
        $tabIndex = 0;
        foreach ($items as $item) {
            $activeClass = $tabIndex === 0 ? ' active' : '';
            $html .= '<div class="carbon-fields-frontend-complex-item' . $activeClass . '" data-index="' . $tabIndex . '" data-depth="' . $this->depth . '">';

            // aplica valores iniciais recursivamente
            $this->applyInitialValues($item);

            foreach ($this->fields as $subField) {
                $subField->set_depth($this->depth + 1);
                $isLast = empty($subField->fields);
                $subField->set_index($tabIndex);
                $subField->set_path($this->path, $isLast);
                $html .= $subField->render();
            }

            $html .= '<div class="carbon-fields-frontend-complex-remove-wrapper"><svg class="carbon-fields-frontend-complex-remove" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><title>Remover</title><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></div>';
            $html .= '</div>';
            $tabIndex++;
        }


        // TEMPLATE INVISÍVEL
        $html .= '<template class="carbon-fields-frontend-complex-item carbon-fields-frontend-template" data-index="__INDEX__" data-template-for="' . htmlspecialchars($dataComplexID, ENT_QUOTES, 'UTF-8') . '" data-depth="' . ($this->depth) . '">';

        foreach ($this->fields as $subField) {

            $isLast = empty($subField->fields);

            $subField->set_path($this->path, $isLast);
            $subField->set_default_values([]); // valor vazio no template
            $subField->set_default_value(''); // valor vazio no template

            $html .= $subField->render();
        }

        $html .= '<div class="carbon-fields-frontend-complex-remove-wrapper"><svg class="carbon-fields-frontend-complex-remove" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><title>Remover</title><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></div>';

        $html .= '</template>'; // fim do template






        $html .= '</div>'; // .carbon-fields-frontend-complex-items

        // Apenas exibe a mensagem de "nenhum item" 
        $html .= '<div class="carbon-fields-frontend-complex-add-first">
            <p>Este item ainda não tem nenhum registro.</p>
            <button type="button" class="carbon-fields-frontend-btn" data-action="add" data-depth="' . ($this->depth) . '">Adicionar ' . (isset($this->singular_name) ? $this->singular_name : '') . '</button>
        </div>';

        $html .= $help_text;
        $html .= '</div>'; // .carbon-fields-frontend-complex-field

        return $html;
    }



    protected function fieldInput()
    {
        $name = $this->path;
        $value = $this->default_value ?? '';
        $required = $this->required ? 'required' : '';

        // Monta atributos adicionais
        $attrStr = '';
        foreach ($this->attributes as $key => $val) {
            $attrStr .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
        }

        $html = '';
        if ($this->type === 'select') {
            if ($this->label) {
                $html .= '<label class="carbon-fields-frontend-label">' . htmlspecialchars($this->label) .  ($required ? '<span style="color:red"> *</span>' : '') . '</label>';
            }

            $html .= '<select name="carbon_fields_frontend' . htmlspecialchars($name) . '" ' . $required . ' ' . $attrStr . '>';
            foreach ($this->options as $key => $label) {
                $selected = $key == $value ? 'selected' : '';
                $html .= '<option value="' . htmlspecialchars($key) . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
            }
            $html .= '</select>';
            return $html;
        }

        if ($this->type === 'textarea') {
            if ($this->label) {
                $html .= '<label class="carbon-fields-frontend-label">' .
                    htmlspecialchars($this->label) .
                    ($required ? '<span style="color:red"> *</span>' : '') .
                    '</label>';
            }
            $html .= '<textarea name="carbon_fields_frontend' . htmlspecialchars($name) . '" ' . $attrStr . ($required ? ' required' : '') .
                '>' . htmlspecialchars($value) . '</textarea>';

            return $html;
        }

        $mask = $this->mask ? (" data-mask='" . $this->mask . "' ") : "";
        $validation = $this->maskValidation ? (" data-mask-validation='" . $this->maskValidation . "'") : "";
        $mimeTypes = $this->mimeTypes ? (' accept="' . $this->mimeTypes . '" ') : '';

        if ($this->label) {

            $html .= '<label class="carbon-fields-frontend-label">' . htmlspecialchars($this->label) .  ($required ? '<span style="color:red"> *</span>' : '')  . '</label>';
        }

        $html .=
            '<input type="' . htmlspecialchars($this->type) . '" name="carbon_fields_frontend' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" ' . $required . ' ' . $attrStr . '" ' . $mask . $validation . $mimeTypes . ' >';

        return $html;
    }


    public function get_fields()
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'default_value' => $this->default_value,
            'fields' => $this->fields,
            'options' => $this->options ?? [],
            'help_text' => $this->help_text ?? '',
            'min' => $this->min,
            'layout' => $this->layout ?? '',
        ];
    }

    protected function applyInitialValues(array $itemValues): void
    {
        foreach ($this->fields as $subField) {
            if (isset($itemValues[$subField->name])) {
                if ($subField->type === 'complex') {
                    // Complex: set default_values recursivamente
                    $subField->set_default_values($itemValues[$subField->name]);
                } else {
                    $subField->set_default_value($itemValues[$subField->name]);
                }
            }
        }
    }
}
