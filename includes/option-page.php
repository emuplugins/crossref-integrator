<?php

// OK
use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', 'crossref_options_page');
function crossref_options_page()
{
    Container::make('theme_options', __('Crossref', 'crossref-integrator'))
        ->set_icon('dashicons-admin-generic')
        ->add_fields(array(
            Field::make('text', 'crossref_login_id', __('Login ID', 'crossref-integrator'))->set_width(33)->set_required(true),
            Field::make('text', 'crossref_doi_prefix', __('DOI Prefix', 'crossref-integrator'))->set_width(33)->set_required(true),
            Field::make('text', 'crossref_depositor', __('Depositor Name', 'crossref-integrator'))->set_width(33)->set_required(true),
            Field::make('text', 'crossref_login_passwd', __('Password', 'crossref-integrator'))->set_width(33)->set_required(true),
            Field::make('text', 'crossref_doi_deposit_link', __('Link for depositing DOIs in Crossref', 'crossref-integrator'))->set_width(33)->set_required(true),
            Field::make('text', 'crossref_contact_email', __('Contact Email', 'crossref-integrator'))->set_width(33)->set_required(true),
            Field::make('text', 'crossref_books_slug', __('Books Slug', 'crossref-integrator'))->set_width(25)->set_attribute('placeholder', 'books'),
            Field::make('text', 'crossref_chapters_slug', __('Chapters Slug', 'crossref-integrator'))->set_width(25)->set_attribute('placeholder', 'chapters'),

            Field::make('html', 'aviso')
                ->set_html('<p>' . __('Attention, fill in all fields to activate the plugin.', 'crossref-integrator') . '</p>'),
        ));
}
