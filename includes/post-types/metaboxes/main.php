<?php


if (!defined('ABSPATH')) exit;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', function () {

    if (!crossref_verify_fields()) return;

    Container::make('post_meta', 'Digital Object Identifier')
        ->where('post_type', 'IN', ['books', 'chapters'])
        ->add_fields([
            Field::make('text', 'doi', 'Número DOI')
                ->set_attribute('maxLength', 26)
                ->set_attribute('placeholder', carbon_get_theme_option('crossref_doi_prefix') . '000-00-0000-000-0')
                ->set_attribute('type', 'text')
                ->set_attribute('readOnly', true)
                ->set_attribute('data-doi', 'crossref')
                ->set_width(30),

            Field::make('html', 'crossref_doi_buttons', 'Ações')
                ->set_html(function () {

                    global $post;
                    $post_id = $post->ID;

                    $doi = get_post_meta($post_id, '_doi', true);
                    $mostrar_gerador = empty($doi);

                    $html .= '<div style="display:flex; gap:10px">';
                    $html .= '<input type="hidden" name="nonce" value="' . wp_create_nonce('send_crossref_nonce') . '">';

                    if ($mostrar_gerador) {
                        $html .= '<button type="button" id="gerar_doi" class="button">Gerar Número</button>';
                    }

                    $html .= '<button type="button" id="crossref_submit_doi" class="button button-effects">'
                        . ($doi ? 'Enviar alterações para a Crossref' : 'Publicação Definitiva')
                        . '</button></div>';

                    return $html;
                })
                ->set_width(70),

            Field::make('html', 'crossref_doi_msg', '')
                ->set_html('<div id="crossref_doi_msg" class="crossref-doi-msg"></div>'),
        ]);


    // ------------------- Metabox de Livros -------------------
    Container::make('post_meta', 'Detalhes do Livro')
        ->where('post_type', '=', 'books')
        ->add_fields([
            Field::make('text', 'registrant', 'Registrante')->set_width(33)
                ->set_help_text('Organização responsável pela informação sendo registrada.')->set_required(true),
            Field::make('text', 'publisher', 'Publicador')->set_width(33)
                ->set_help_text('Nome da pessoa ou entidade que está enviando o conteúdo a Crossref.')->set_required(true),

            Field::make('textarea', 'jats_abstract', 'Resumo (Jats Abstract)')
                ->set_required(true)->set_help_text('Resumo estruturado da obra em formato JATS, ou texto simples. Pode conter texto, parágrafos e marcações semânticas básicas como itálico, negrito, listas e links. Este campo é usado por indexadores, repositórios e sistemas de publicação para processar e exibir corretamente o resumo da obra.'),

            Field::make('text', 'isbn_e', 'ISBN (Versão Eletrônica)')->set_width(33)->set_help_text('Padrão internacional que identifica unicamente esta obra.'),

            Field::make('text', 'isbn_p', 'ISBN (Versão Impressa)')->set_width(33)->set_help_text('Padrão internacional que identifica unicamente esta obra.'),

            Field::make('text', 'edition_number', 'Edição')
                ->set_attribute('type', 'number')
                ->set_attribute('min', 1)
                ->set_attribute('step', 1)->set_width(33)->set_help_text('Identificador da edição desta obra.')->set_required(true),

            Field::make('date', 'online_publication_date', 'Data de Publicação (Versão Eletrônica)')
                ->set_required(true)
                ->set_storage_format('Y-m-d')->set_width(33),

            Field::make('date', 'print_publication_date', 'Data de Publicação (Versão Impressa)')
                ->set_storage_format('Y-m-d')->set_width(33),

            Field::make('select', 'language', 'Idioma')
                ->set_options([
                    'pt' => 'Português',
                    'en' => 'Inglês',
                    'es' => 'Espanhol',
                    'fr' => 'Francês',
                    'de' => 'Alemão',
                    'it' => 'Italiano',
                    'zh' => 'Chinês',
                    'ja' => 'Japonês',
                    'ru' => 'Russo',
                    'ar' => 'Árabe',
                ])
                ->set_default_value('pt')
                ->set_required(true)->set_width(33),

        ]);


    Container::make('post_meta', 'Arquivo do Livro')
        ->where('post_type', '=', 'books')
        ->add_fields([
            Field::make('text', 'resource', 'Arquivo do Livro (Doi Resource)')
                ->set_required(true),
            Field::make('hidden', 'resource_id'),
            Field::make('html', 'crossref_doi_msg', '')
                ->set_html('<button type="button" class="button" id="crossref_resource_file_select">
                Selecionar arquivo
            </button>')->set_help_text('Link associado à obra ou recurso. Permite acesso direto e permanente ao conteúdo online, garantindo que o recurso possa ser localizado de forma confiável em repositórios e indexadores acadêmicos.'),
        ]);

    // ------------------- Metabox de Capítulos -------------------
    Container::make('post_meta', 'Detalhes do Capítulo')
        ->where('post_type', '=', 'chapters')
        ->add_fields([
            Field::make('select', 'parent_book', 'Livro Pai')
                ->set_options(function () {
                    $books = get_posts([
                        'post_type'   => 'books',   // CPT dos livros
                        'numberposts' => -1,        // Todos os livros
                        'orderby'     => 'title',
                        'order'       => 'ASC',
                    ]);

                    $options = [];
                    foreach ($books as $book) {
                        $options[$book->ID] = $book->ID . ' - ' . $book->post_title;
                    }

                    return $options;
                })->set_required(true)->set_width(25),
            Field::make('text', 'component_number', 'Número do Capítulo')
                ->set_attribute('type', 'number')
                ->set_attribute('min', 1)
                ->set_attribute('step', 1)
                ->set_default_value(1)->set_width(25),
            Field::make('text', 'first_page', 'Primeira Página')
                ->set_attribute('type', 'number')
                ->set_attribute('min', 1)
                ->set_attribute('step', 1)
                ->set_default_value(1)->set_width(25),

            Field::make('text', 'last_page', 'Última Página')
                ->set_attribute('type', 'number')
                ->set_attribute('min', 1)
                ->set_attribute('step', 1)
                ->set_default_value(1)->set_width(25),
            Field::make('textarea', 'jats_abstract', 'Resumo (Jats Abstract)')
                ->set_help_text('Resumo estruturado da obra em formato JATS, ou texto simples. Pode conter texto, parágrafos e marcações semânticas básicas como itálico, negrito, listas e links. Este campo é usado por indexadores, repositórios e sistemas de publicação para processar e exibir corretamente o resumo da obra.'),

            Field::make('date', 'online_publication_date', 'Data de Publicação (Versão Eletrônica)')
                ->set_storage_format('Y-m-d')
                ->set_required(true)->set_width(33),

            Field::make('date', 'print_publication_date', 'Data de Publicação (Versão Impressa)')
                ->set_storage_format('Y-m-d')->set_width(33),

            Field::make('select', 'language', 'Idioma')
                ->set_options([
                    'pt' => 'Português',
                    'en' => 'Inglês',
                    'es' => 'Espanhol',
                    'fr' => 'Francês',
                    'de' => 'Alemão',
                    'it' => 'Italiano',
                    'zh' => 'Chinês',
                    'ja' => 'Japonês',
                    'ru' => 'Russo',
                    'ar' => 'Árabe',
                ])
                ->set_default_value('pt')
                ->set_required(true)->set_width(33),
        ]);





    // ------------------- Contribuintes -------------------
    Container::make('post_meta', 'Contribuintes')
        ->where('post_type', 'IN', ['books', 'chapters'])
        ->add_fields([
            Field::make('complex', 'contributors', ' ')
                ->set_layout('tabbed-horizontal')
                ->add_fields([
                    Field::make('text', 'given', 'Nome')->set_required(true)->set_width(25),
                    Field::make('text', 'surname', 'Sobrenome (Apenas para Pessoas)')->set_width(25),
                    Field::make('text', 'orcid', 'ORCID (Apenas para pessoas)')->set_width(25),
                    Field::make('select', 'role', 'Função')
                        ->set_options([
                            'author'            => 'Autor',
                            'editor'            => 'Editor',
                            'chair'             => 'Presidente',
                            'reviewer'          => 'Revisor',
                            'review-assistant'  => 'Assistente de Revisão',
                            'stats-reviewer'    => 'Revisor de Estatísticas',
                            'reviewer-external' => 'Revisor Externo',
                            'reader'            => 'Leitor',
                            'translator'        => 'Tradutor',
                        ])
                        ->set_default_value('author')->set_width(15),
                ])
                ->set_min(1)
                ->set_header_template('<%- given %>')->set_help_text('Pessoas ou organizações que contribuiram para a autoria e edição da obra.'),
        ]);

    // ------------------- Citações -------------------
    Container::make('post_meta', 'Citações')
        ->where('post_type', 'IN', ['books', 'chapters'])
        ->add_fields([
            Field::make('complex', 'citation_list', ' ')
                ->set_layout('tabbed-horizontal')
                ->add_fields([
                    Field::make('text', 'unstructured_citation', 'Citação')->set_required(true)->set_width(25),
                    Field::make('text', 'doi', 'DOI da obra')->set_width(25),
                    
                ])
                ->set_min(1)
                ->set_header_template('<%- unstructured_citation %>')->set_help_text('Artigos, livros e outros conteúdos citados pelo item sendo registrado.'),
        ]);







    Container::make('post_meta', 'XML Gerado')
        ->where('post_type', 'IN', ['books', 'chapters'])
        ->add_fields([
            Field::make('html', 'crossref_xml_display', ' ')
                ->set_html(function () {

                    global $post;
                    $post_id = $post->ID;

                    $xml = get_post_meta($post_id, '_crossref_xml', true);

                    $xml_display = $xml
                        ? esc_html($xml)
                        : 'Clique em enviar DOI à Crossref para gerar o XML.';

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


// Importa o arquivo responsável por enviar o XML para a Crossref
require(CROSSREF_PLUGIN_DIR . '/includes/send-crossref-book.php');
require(CROSSREF_PLUGIN_DIR . '/includes/send-crossref-chapter.php');
