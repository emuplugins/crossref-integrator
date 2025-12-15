<?php

namespace Carbon_Fields\Frontend;

use Carbon_Fields\Frontend\Container;

class Form
{
    protected string $id;
    protected string $name;

    protected function __construct(string $id, string $form_name)
    {
        $this->id   = $id;
        $this->name = $form_name;

        // Adiciona o shortcode do formulario
        add_shortcode(
            'carbon_fields_frontend_form_' . $id,
            [$this, 'render']
        );

        add_action('wp_ajax_nopriv_carbon_fields_frontend_form' . $id, [$this, 'ajaxRequest']);
        add_action('wp_ajax_carbon_fields_frontend_form' . $id, [$this, 'ajaxRequest']);

        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function ajaxRequest()
    {
        // Logica de envio aqui
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
        $html .= '<h3>' . esc_html($this->name) . '</h3>';

        foreach ($containers as $container) {

            $html .= '<fieldset class="carbon-fields-frontend-container">';
            $html .= '<legend>' . esc_html($container->getName()) . '</legend>';

            foreach ($container->getFields() as $field) {
                $html .= $field->render();
            }

            $html .= '</fieldset>';
        }

        $html .= '<button type="submit">Enviar Formulário</button>';
        $html .= '</form>';

        return $html;
    }
}
