<?php

if (!defined('ABSPATH')) exit;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {

    if (!crossref_verify_fields()) return;

    // ------------------- DOI -------------------
    Container::make('post_meta', __('Digital Object Identifier', 'crossref-integrator'))
        ->where('post_type', 'IN', ['crossref_books', 'crossref_chapters'])
        ->add_fields([
            Field::make('text', 'doi', __('DOI Number', 'crossref-integrator'))
                ->set_attribute('maxLength', 26)
                ->set_attribute('placeholder', carbon_get_theme_option('crossref_doi_prefix') . '000-00-0000-000-0')
                ->set_attribute('type', 'text')
                ->set_attribute('readOnly', true)
                ->set_attribute('data-doi', 'crossref')
                ->set_width(30),

            Field::make('html', 'crossref_doi_buttons', __('Actions', 'crossref-integrator'))
                ->set_html(function () {

                    global $post;
                    $post_id = $post->ID;

                    $doi = get_post_meta($post_id, '_doi', true);
                    $mostrar_gerador = empty($doi);

                    $html = '<div style="display:flex; gap:10px">';
                    $html .= '<input type="hidden" name="nonce" value="' . wp_create_nonce('send_crossref_nonce') . '">';

                    if ($mostrar_gerador) {
                        $html .= '<button type="button" id="gerar_doi" class="button">'
                            . __('Generate New', 'crossref-integrator') .
                        '</button>';
                    }

                    $html .= '<button type="button" id="crossref_submit_doi" class="button button-effects">'
                        . ($doi 
                            ? __('Save changes locally', 'crossref-integrator') 
                            : __('Send updates to Crossref', 'crossref-integrator'))
                        . '</button></div>';

                    return $html;
                })
                ->set_width(70),

            Field::make('html', 'crossref_doi_msg', '')
                ->set_html('<div id="crossref_doi_msg" class="crossref-doi-msg"></div>'),
        ]);


    // ------------------- Book Metabox -------------------
    Container::make('post_meta', __('Book Details', 'crossref-integrator'))
        ->where('post_type', '=', 'crossref_books')
        ->add_fields([
            Field::make('text', 'registrant', __('Registrant', 'crossref-integrator'))->set_width(33)
                ->set_help_text(__('Organization responsible for the information being registered.', 'crossref-integrator'))
                ->set_required(true),

            Field::make('text', 'publisher', __('Publisher', 'crossref-integrator'))->set_width(33)
                ->set_help_text(__('Name of the person or entity submitting the content to Crossref.', 'crossref-integrator'))
                ->set_required(true),

            Field::make('textarea', 'jats_abstract', __('Abstract (JATS)', 'crossref-integrator'))
                ->set_required(true)
                ->set_help_text(__('Structured abstract in JATS format, or plain text. May include paragraphs and semantic markup.', 'crossref-integrator')),

            Field::make('text', 'isbn_e', __('ISBN (Electronic Version)', 'crossref-integrator'))->set_width(33)
                ->set_help_text(__('International standard that uniquely identifies this work.', 'crossref-integrator')),

            Field::make('text', 'isbn_p', __('ISBN (Print Version)', 'crossref-integrator'))->set_width(33)
                ->set_help_text(__('International standard that uniquely identifies this work.', 'crossref-integrator')),

            Field::make('text', 'edition_number', __('Edition', 'crossref-integrator'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', 1)
                ->set_attribute('step', 1)->set_width(33)
                ->set_help_text(__('Edition number of this work.', 'crossref-integrator'))
                ->set_required(true),

            Field::make('date', 'online_publication_date', __('Online Publication Date', 'crossref-integrator'))
                ->set_required(true)
                ->set_storage_format('Y-m-d')->set_width(33),

            Field::make('date', 'print_publication_date', __('Print Publication Date', 'crossref-integrator'))
                ->set_storage_format('Y-m-d')->set_width(33),

            Field::make('select', 'language', __('Language', 'crossref-integrator'))
                ->set_options([
                    'pt' => __('Portuguese', 'crossref-integrator'),
                    'en' => __('English', 'crossref-integrator'),
                    'es' => __('Spanish', 'crossref-integrator'),
                    'fr' => __('French', 'crossref-integrator'),
                    'de' => __('German', 'crossref-integrator'),
                    'it' => __('Italian', 'crossref-integrator'),
                    'zh' => __('Chinese', 'crossref-integrator'),
                    'ja' => __('Japanese', 'crossref-integrator'),
                    'ru' => __('Russian', 'crossref-integrator'),
                    'ar' => __('Arabic', 'crossref-integrator'),
                ])
                ->set_default_value('pt')
                ->set_required(true)->set_width(33),
        ]);


    // ------------------- Book File -------------------
    Container::make('post_meta', __('Book File', 'crossref-integrator'))
        ->where('post_type', '=', 'crossref_books')
        ->add_fields([
            Field::make('text', 'resource', __('Book File (DOI Resource)', 'crossref-integrator'))
                ->set_required(true),

            Field::make('hidden', 'resource_id'),

            Field::make('html', 'crossref_doi_msg', '')
                ->set_html('<button type="button" class="button" id="crossref_resource_file_select">'
                    . __('Select File', 'crossref-integrator') .
                '</button>')
                ->set_help_text(__('Link associated with the work. Provides direct and permanent access.', 'crossref-integrator')),
        ]);


    // ------------------- Chapter Metabox -------------------
    Container::make('post_meta', __('Chapter Details', 'crossref-integrator'))
        ->where('post_type', '=', 'crossref_chapters')
        ->add_fields([
            Field::make('select', 'parent_book', __('Parent Book', 'crossref-integrator'))
                ->set_options(function () {
                    $books = get_posts([
                        'post_type'   => 'crossref_books',
                        'numberposts' => -1,
                        'orderby'     => 'title',
                        'order'       => 'ASC',
                    ]);

                    $options = [];
                    foreach ($books as $book) {
                        $options[$book->ID] = $book->ID . ' - ' . $book->post_title;
                    }

                    return $options;
                })
                ->set_required(true)->set_width(25),

            Field::make('text', 'component_number', __('Chapter Number', 'crossref-integrator'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', 1)
                ->set_attribute('step', 1)
                ->set_default_value(1)->set_width(25),

            Field::make('text', 'first_page', __('First Page', 'crossref-integrator'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', 1)
                ->set_attribute('step', 1)
                ->set_default_value(1)->set_width(25),

            Field::make('text', 'last_page', __('Last Page', 'crossref-integrator'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', 1)
                ->set_attribute('step', 1)
                ->set_default_value(1)->set_width(25),

            Field::make('textarea', 'jats_abstract', __('Abstract (JATS)', 'crossref-integrator'))
                ->set_help_text(__('Structured abstract in JATS format, or plain text.', 'crossref-integrator')),

            Field::make('date', 'online_publication_date', __('Online Publication Date', 'crossref-integrator'))
                ->set_storage_format('Y-m-d')
                ->set_required(true)->set_width(33),

            Field::make('date', 'print_publication_date', __('Print Publication Date', 'crossref-integrator'))
                ->set_storage_format('Y-m-d')->set_width(33),

            Field::make('select', 'language', __('Language', 'crossref-integrator'))
                ->set_options([
                    'pt' => __('Portuguese', 'crossref-integrator'),
                    'en' => __('English', 'crossref-integrator'),
                    'es' => __('Spanish', 'crossref-integrator'),
                    'fr' => __('French', 'crossref-integrator'),
                    'de' => __('German', 'crossref-integrator'),
                    'it' => __('Italian', 'crossref-integrator'),
                    'zh' => __('Chinese', 'crossref-integrator'),
                    'ja' => __('Japanese', 'crossref-integrator'),
                    'ru' => __('Russian', 'crossref-integrator'),
                    'ar' => __('Arabic', 'crossref-integrator'),
                ])
                ->set_default_value('pt')
                ->set_required(true)->set_width(33),
        ]);


    // ------------------- Contributors -------------------
    Container::make('post_meta', __('Contributors', 'crossref-integrator'))
        ->where('post_type', 'IN', ['crossref_books', 'crossref_chapters'])
        ->add_fields([
            Field::make('complex', 'contributors', ' ')
                ->set_layout('tabbed-horizontal')
                ->add_fields([
                    Field::make('text', 'given', __('Given Name', 'crossref-integrator'))
                        ->set_required(true)->set_width(25),

                    Field::make('text', 'surname', __('Surname (People Only)', 'crossref-integrator'))->set_width(25),

                    Field::make('text', 'orcid', __('ORCID (People Only)', 'crossref-integrator'))->set_width(25),

                    Field::make('select', 'role', __('Role', 'crossref-integrator'))
                        ->set_options([
                            'author'            => __('Author', 'crossref-integrator'),
                            'editor'            => __('Editor', 'crossref-integrator'),
                            'chair'             => __('Chair', 'crossref-integrator'),
                            'reviewer'          => __('Reviewer', 'crossref-integrator'),
                            'review-assistant'  => __('Review Assistant', 'crossref-integrator'),
                            'stats-reviewer'    => __('Statistics Reviewer', 'crossref-integrator'),
                            'reviewer-external' => __('External Reviewer', 'crossref-integrator'),
                            'reader'            => __('Reader', 'crossref-integrator'),
                            'translator'        => __('Translator', 'crossref-integrator'),
                        ])
                        ->set_default_value('author')->set_width(15),
                ])
                ->set_min(1)
                ->set_header_template('<%- given %>')
                ->set_help_text(__('People or organizations who contributed to the work.', 'crossref-integrator')),
        ]);


    // ------------------- Citations -------------------
    Container::make('post_meta', __('Citations', 'crossref-integrator'))
        ->where('post_type', 'IN', ['crossref_books', 'crossref_chapters'])
        ->add_fields([
            Field::make('complex', 'citation_list', ' ')
                ->set_layout('tabbed-horizontal')
                ->add_fields([
                    Field::make('text', 'unstructured_citation', __('Citation', 'crossref-integrator'))
                        ->set_required(true)->set_width(25),

                    Field::make('text', 'doi', __('Work DOI', 'crossref-integrator'))->set_width(25),
                ])
                ->set_min(1)
                ->set_header_template('<%- unstructured_citation %>')
                ->set_help_text(__('Articles, books, and other content cited by the registered item.', 'crossref-integrator')),
        ]);


    // ------------------- XML Display -------------------
    Container::make('post_meta', __('Generated XML', 'crossref-integrator'))
        ->where('post_type', 'IN', ['crossref_books', 'crossref_chapters'])
        ->add_fields([
            Field::make('html', 'crossref_xml_display', ' ')
                ->set_html(function () {

                    global $post;
                    $post_id = $post->ID;

                    $xml = get_post_meta($post_id, '_crossref_xml', true);

                    $xml_display = $xml
                        ? esc_html($xml)
                        : __('Click “Send DOI to Crossref” to generate the XML.', 'crossref-integrator');

                    $html = '<pre style="
                        background: #f9f9f9;
                        border: 1px solid #ccc;
                        padding: 10px;
                        overflow: auto;
                        white-space: pre-wrap;
                        font-family: monospace;
                    ">' . $xml_display . '</pre>';

                    return $html;
                }),
        ]);
});

require(CROSSREF_PLUGIN_DIR . '/includes/send-crossref-book.php');
require(CROSSREF_PLUGIN_DIR . '/includes/send-crossref-chapter.php');
