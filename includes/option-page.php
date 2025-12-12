<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', 'crossref_options_page');
function crossref_options_page()
{
    Container::make('theme_options', __('Crossref', 'crossref-integrator'))
        ->set_icon('dashicons-admin-generic')
        ->add_fields(array(
            Field::make('text', 'crossref_publisher_name', __('Publisher Name', 'crossref-integrator'))
                ->set_width(100)
                ->set_required(true)
                ->set_help_text(__('The company or organization that publishes the book or content.', 'crossref-integrator')),
            Field::make('text', 'crossref_login_id', __('Login ID', 'crossref-integrator'))
                ->set_width(33)
                ->set_required(true)
                ->set_help_text(__('Enter your Crossref login ID provided by Crossref.', 'crossref-integrator')),

            Field::make('text', 'crossref_doi_prefix', __('DOI Prefix', 'crossref-integrator'))
                ->set_width(33)
                ->set_required(true)
                ->set_attribute('placeholder', '00.00000/')
                ->set_help_text(__('The DOI prefix assigned to your organization, e.g., 10.12345/.', 'crossref-integrator')),

            Field::make('text', 'crossref_depositor', __('Depositor Name', 'crossref-integrator'))
                ->set_width(33)
                ->set_required(true)
                ->set_help_text(__('Entity (person or organization) responsible for the DOI prefix.', 'crossref-integrator')),

            Field::make('text', 'crossref_login_passwd', __('Password', 'crossref-integrator'))
                ->set_width(33)
                ->set_required(true)
                ->set_help_text(__('Password used for depositing DOIs in Crossref.', 'crossref-integrator')),

            Field::make('text', 'crossref_doi_deposit_link', __('Link for depositing DOIs in Crossref', 'crossref-integrator'))
                ->set_width(33)
                ->set_required(true)
                ->set_help_text(__('The URL endpoint to deposit DOIs in Crossref.', 'crossref-integrator')),

            Field::make('text', 'crossref_contact_email', __('Contact Email', 'crossref-integrator'))
                ->set_width(33)
                ->set_attribute('type', 'email')
                ->set_required(true)
                ->set_help_text(__('The email that will receive notifications about DOI registration status.', 'crossref-integrator'))
                ->set_attribute('autocomplete', 'off'),

            Field::make('text', 'crossref_books_slug', __('Books Slug', 'crossref-integrator'))
                ->set_width(33)
                ->set_attribute('placeholder', 'books')
                ->set_help_text(__('Slug to be used for book archive URLs, e.g., "books".', 'crossref-integrator')),

            Field::make('text', 'crossref_chapters_slug', __('Chapters Slug', 'crossref-integrator'))
                ->set_width(33)
                ->set_attribute('placeholder', 'chapters')
                ->set_help_text(__('Slug to be used for chapter URLs, e.g., "chapters".', 'crossref-integrator')),
            Field::make('text', 'crossref_registrant', __('Registrant', 'crossref-integrator'))
                ->set_width(33)
                ->set_required(true)
                ->set_help_text(__('Organization, entity, or means responsible for registering the DOI.', 'crossref-integrator')),

            Field::make('html', 'aviso', ' ')
                ->set_html('<span style="color:red;">' . __('Attention, fill in all fields to activate the plugin.', 'crossref-integrator') . '</span>'),
        ));
}
