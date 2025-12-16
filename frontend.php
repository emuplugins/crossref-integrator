<?php

use Carbon_Fields\Frontend\Form;
use Carbon_Fields\Frontend\Container;
use Carbon_Fields\Frontend\Field;

Form::make('7', 'Criar capítulo')
    ->setAjaxCallback(function ($data) {

        $data = $data['carbon_fields_frontend'];

        $post_id = wp_insert_post([
            'post_title'  => sanitize_text_field($data['lead_name'] ?? ''),
            'post_status' => 'pending',
            'post_type'   => 'crossref_books',
            'post_author' => 0,
        ], true);

        if (is_wp_error($post_id)) {
            wp_send_json_error([
                'message' => $post_id->get_error_message(),
            ]);
        }

        foreach ($data as $meta_key => $value) {
            carbon_set_post_meta($post_id, $meta_key, $value);
        }

        wp_send_json_success([
            'message' => 'Capítulo criado com sucesso.',
        ]);
    });

/* ------------------- Sobre Você ------------------- */

Container::make('form', 'Sobre Você')
    ->where('id', '=', 7)
    ->add_fields([
        Field::make('text', 'lead_name', 'Nome completo')
            ->set_attribute('placeholder', 'João Santos da Silva')
            ->set_required(true)
            ->set_width(100),

        Field::make('email', 'lead_email', 'E-mail')
            ->set_attribute('placeholder', 'contato@gmail.com')
            ->set_required(true)
            ->set_width(40),

        Field::make('text', 'lead_phone', 'Telefone ou celular')
            ->set_attribute('placeholder', '+55 11 9 0000-0000')
            ->set_required(true)
            ->set_width(40),

        Field::make('url', 'lead_lattes', 'Currículo Lattes')
            ->set_attribute('placeholder', 'https://lattes.cnpq.br/0000000000000000')
            ->set_width(40),

        Field::make('text', 'lead_cpf', 'CPF')
            ->set_attribute('placeholder', '000.000.000-00')
            ->set_required(true)
            ->set_width(40),
    ]);

/* ------------------- Detalhes do Livro ------------------- */

Container::make('form', 'Detalhes do Livro')
    ->where('id', '=', 7)
    ->add_fields([
        Field::make('textarea', 'jats_abstract', 'Resumo (JATS)')
            ->set_help_text(
                'Resumo estruturado no formato JATS ou texto simples. Pode conter parágrafos e marcação semântica. Máximo de 1200 caracteres.'
            )
            ->set_attribute('maxlength', 1200)
            ->set_required(true),

        Field::make('text', 'isbn_e', 'ISBN (versão eletrônica)')
            ->set_width(33)
            ->set_help_text('Identificador internacional da obra na versão digital.'),

        Field::make('text', 'isbn_p', 'ISBN (versão impressa)')
            ->set_width(33)
            ->set_help_text('Identificador internacional da obra na versão impressa.'),

        Field::make('number', 'edition_number', 'Número da edição')
            ->set_width(30)
            ->set_required(true)
            ->set_attribute('min', 1)
            ->set_attribute('step', 1)
            ->set_help_text('Número da edição da obra.'),

        Field::make('date', 'print_publication_date', 'Data de publicação impressa')
            ->set_width(30),

        Field::make('select', 'language', 'Idioma')
            ->set_width(30)
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
            ->set_required(true),
    ]);

/* ------------------- Contribuintes ------------------- */

Container::make('form', 'Contribuintes')
    ->where('id', '=', 7)
    ->add_fields([
        Field::make('complex', 'contributor_groups', 'Grupos de Contribuintes')
            ->set_layout('tabbed-horizontal')
            ->set_min(1)
            ->set_header_template('<%- group_title %>')
            ->add_fields([
                Field::make('text', 'group_title', 'Nome do grupo')
                    ->set_required(true)
                    ->set_width(33),

                Field::make('select', 'role', 'Função do grupo')
                    ->set_width(33)
                    ->set_options([
                        'author'            => 'Autores',
                        'editor'            => 'Editores',
                        'chair'             => 'Presidentes',
                        'reviewer'          => 'Revisores',
                        'review-assistant'  => 'Assistentes de revisão',
                        'stats-reviewer'    => 'Revisores de estatística',
                        'reviewer-external' => 'Revisores externos',
                        'reader'            => 'Leitores',
                        'translator'        => 'Tradutores',
                    ]),

                Field::make('complex', 'contributors', 'Integrantes')
                    ->set_layout('tabbed-horizontal')
                    ->set_min(1)
                    ->set_header_template('<%- given %>')
                    ->add_fields([
                        Field::make('text', 'given', 'Nome')
                            ->set_required(true)
                            ->set_width(40),

                        Field::make('text', 'surname', 'Sobrenome (pessoas físicas)')
                            ->set_width(20),

                        Field::make('url', 'orcid', 'ORCID')
                            ->set_width(20),

                        Field::make('url', 'lattes', 'Currículo Lattes')
                            ->set_width(20),

                        Field::make('textarea', 'bio', 'Biografia')
                            ->set_width(100),

                        Field::make('complex', 'affiliations', 'Afiliações')
                            ->set_layout('tabbed-vertical')
                            ->set_header_template('<%- institution_name %>')
                            ->add_fields([
                                Field::make('text', 'institution_name', 'Nome da instituição')
                                    ->set_required(true)
                                    ->set_width(40),

                                Field::make('text', 'institution_id', 'Identificador da instituição')
                                    ->set_width(40),

                                Field::make('select', 'role', 'Tipo de identificador')
                                    ->set_width(20)
                                    ->set_options([
                                        'ror'      => 'ROR',
                                        'wikidata' => 'Wikidata',
                                        'isni'     => 'ISNI',
                                    ]),
                            ]),
                    ]),
            ])
            ->set_help_text('Grupos de contribuintes associados a este capítulo.'),
    ]);

/* ------------------- Referências ------------------- */

Container::make('form', 'Referências')
    ->where('id', '=', 7)
    ->add_fields([
        Field::make('complex', 'citations', 'Referências citadas')
            ->set_layout('tabbed-horizontal')
            ->set_min(4)
            ->set_header_template('<%- unstructured_citation %>')
            ->add_fields([
                Field::make('text', 'unstructured_citation', 'Referência')
                    ->set_required(true)
                    ->set_width(50),

                Field::make('text', 'doi', 'DOI da obra')
                    ->set_width(50),
            ])
            ->set_help_text('Artigos, livros e outros conteúdos citados neste capítulo.'),
    ]);
