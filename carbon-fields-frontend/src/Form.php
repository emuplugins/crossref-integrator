<?php

namespace Carbon_Fields\Frontend;

use Carbon_Fields\Frontend\Container;

class Form
{
    protected string $id;
    protected string $name;

    // Aqui armazenamos a função externa
    protected $ajaxCallback = null;

    protected function __construct(string $id, string $form_name)
    {
        $this->id   = $id;
        $this->name = $form_name;

        // Adiciona o shortcode do formulario
        add_shortcode(
            'carbon_fields_frontend_form_' . $id,
            [$this, 'render']
        );

        add_action('wp_ajax_nopriv_carbon_fields_frontend_form_' . $id, [$this, 'ajaxRequest']);
        add_action('wp_ajax_carbon_fields_frontend_form_' . $id, [$this, 'ajaxRequest']);

        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    // Função publica para construir a classe
    public static function make(string $id, string $form_name): self
    {
        return new self($id, $form_name);
    }


    function enqueueScripts()
    {
        // CSS
        wp_enqueue_style(
            'carbon-fields-frontend',
            CARBON_FIELDS_FRONTEND_URL . 'assets/style.css',
            [],
            filemtime(CARBON_FIELDS_FRONTEND_PATH . 'assets/style.css'),
            'all'
        );

        // JS
        wp_enqueue_script(
            'carbon-fields-frontend',
           CARBON_FIELDS_FRONTEND_URL . 'assets/script.js',
            ['jquery'], // ou [] se não depender de jQuery
            filemtime(CARBON_FIELDS_FRONTEND_PATH . 'assets/script.js'),
            true // carregar no footer
        );
    }

    public function render(): string
    {

        $containers = Container::forForm($this->id);

        $html  = '<form method="post" id="form_'.$this->id.'" class="carbon-fields-frontend-form">';

        $html .= '<div class="carbon-fields-frontend-containers">';

        foreach ($containers as $container) {
            
            $html .= '<fieldset class="carbon-fields-frontend-container">';
            $html .= '<legend>' . esc_html($container->getName()) . '</legend>';
            
            foreach ($container->getFields() as $field) {
                $html .= $field->render();
            }
            
            $html .= '</fieldset>';
        }

        $html .= '</div>';
        
        $html .= '<button class="carbon-fields-frontend-btn" type="submit">Enviar Formulário</button>';
        $html .= '</form><div class="carbon-fields-frontend-notice-wrapper"></div>';

        return $html;
    }

    // Método para registrar um callback externo
    public function setAjaxCallback(callable $callback): self
    {
        $this->ajaxCallback = $callback;
        return $this;
    }

     public function ajaxRequest()
    {
        if ($this->ajaxCallback) {
            // Executa a função externa
            call_user_func($this->ajaxCallback, $_POST);
        } else {
            // Lógica padrão caso não tenha callback
            wp_send_json_error(['message' => 'Nenhuma função definida para o Ajax']);
        }
    }
}
