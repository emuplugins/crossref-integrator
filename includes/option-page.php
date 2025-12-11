<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', 'crossref_options_page');
function crossref_options_page()
{
    Container::make('theme_options', __('Crossref Integrator', 'text-domain'))
        ->set_icon('dashicons-admin-generic')
        ->add_fields(array(
            Field::make('text', 'crossref_doi_deposit_link', 'Link para depósito de DOIs na Crossref'),
            Field::make('text', 'crossref_login_id', 'Login ID'),
            Field::make('text', 'crossref_login_passwd', 'Password'),
            Field::make('text', 'crossref_doi_prefix', 'Prefixo Doi'),
            Field::make('text', 'crossref_depositor', 'Nome do Depositante'),
            Field::make('text', 'crossref_contact_email', 'Email de Contato'),
            Field::make('html', 'aviso')
                ->set_html('<p>Atenção, cadastre todos os campos para iniciar o plugin.</p>'),
        ));
}
