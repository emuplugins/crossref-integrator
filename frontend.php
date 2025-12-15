<?php

use Carbon_Fields\Frontend\Form;
use Carbon_Fields\Frontend\Container;
use Carbon_Fields\Frontend\Field;

Form::make('7', 'Criar capítulo');

Container::make('form', 'Sobre Você')
    ->where('id', '=', 7)
    ->add_fields([
        Field::make('text', 'lead_name', 'Nome e Sobrenome')
            ->set_attribute('placeholder', 'João Santos da Silva')
            ->set_width(100),

        Field::make('email', 'lead_email', 'Email')
            ->set_attribute('placeholder', 'contato@gmail.com')
            ->set_required(true)
            ->set_width(40),

        Field::make('text', 'lead_phone', 'Telefone / Celular')
            ->set_attribute('placeholder', '+55 11 9 0000-0000')
            ->set_width(40),

        Field::make('text', 'lead_lattes', 'Currículo Lattes')
            ->set_attribute('placeholder', 'https://lattes.cnpq.br/0000000000000000')
            ->set_width(40),

        Field::make('text', 'lead_cpf', 'CPF')
            ->set_attribute('placeholder', '000.000.000-00')
            ->set_width(40),
    ]);

Container::make('form', 'Detalhes do Livro')
    ->where('id', '=', 7)
    ->add_fields([
        Field::make('textarea', 'jats_abstract', 'Abstract (JATS)')
            ->set_help_text(
                'Structured abstract in JATS format, or plain text. May include paragraphs and semantic markup.'
            )
            ->set_required(true),

        Field::make('text', 'isbn_e', 'ISBN (Electronic Version)')
            ->set_width(33)
            ->set_help_text('International standard that uniquely identifies this work.'),

        Field::make('text', 'isbn_p', 'ISBN (Print Version)')
            ->set_width(33)
            ->set_help_text('International standard that uniquely identifies this work.'),

        Field::make('number', 'edition_number', 'Edition')
            ->set_width(33)
            ->set_required(true)
            ->set_attribute('min', 1)
            ->set_attribute('step', 1)
            ->set_help_text('Edition number of this work.'),

        Field::make('date', 'online_publication_date', 'Online Publication Date')
            ->set_width(33)
            ->set_required(true),

        Field::make('date', 'print_publication_date', 'Print Publication Date')
            ->set_width(33),

        Field::make('select', 'language', 'Language')
            ->set_width(33)
            ->set_options([
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
            ])
            ->set_default_value('pt')
            ->set_required(true),
    ]);

Container::make('form', 'Contribuintes')
    ->where('id', '=', 7)
    ->add_fields([
        Field::make('complex', '_contributor_groups', 'Grupos de Contribuintes')
            ->set_layout('tabbed-horizontal')
            ->set_min(1)
            ->set_header_template('<%- group_title %>')
            ->add_fields([
                Field::make('text', 'group_title', 'Nome do Grupo')
                    ->set_required(true)
                    ->set_width(33),

                Field::make('select', 'role', 'Função dos integrantes')
                    ->set_width(33)
                    ->set_options([
                        'author'            => 'Autores',
                        'editor'            => 'Editores',
                        'chair'             => 'Presidentes',
                        'reviewer'          => 'Revisores',
                        'review-assistant'  => 'Assistentes de Revisão',
                        'stats-reviewer'    => 'Revisores de Estatísticas',
                        'reviewer-external' => 'Revisores Externos',
                        'reader'            => 'Leitores',
                        'translator'        => 'Tradutores',
                    ]),

                Field::make('complex', '_contributors', 'Integrantes do Grupo')
                    ->set_layout('tabbed-horizontal')
                    ->set_min(1)
                    ->set_header_template('<%- given %>')
                    ->add_fields([
                        Field::make('text', 'given', 'Nome do Integrante')
                            ->set_required(true)
                            ->set_width(20),

                        Field::make('text', 'surname', 'Sobrenome (apenas pessoas)')
                            ->set_width(20),

                        Field::make('text', 'orcid', 'ORCID (apenas pessoas)')
                            ->set_width(20),

                        Field::make('text', 'lattes', 'Currículo Lattes')
                            ->set_width(20),

                        Field::make('textarea', 'bio', 'Biografia')
                            ->set_width(100),

                        Field::make('complex', '_affiliations', 'Afiliações')
                            ->set_layout('tabbed-vertical')
                            ->set_min(0)
                            ->set_header_template('<%- institution_name %>')
                            ->add_fields([
                                Field::make('text', 'institution_name', 'Nome da Instituição')
                                    ->set_required(true)
                                    ->set_width(40),

                                Field::make('text', 'institution_id', 'ID da instituição')
                                    ->set_width(40),

                                Field::make('select', 'role', 'Tipo do Identificador')
                                    ->set_width(20)
                                    ->set_options([
                                        'ror'      => 'RoR',
                                        'wikidata' => 'Wikidata',
                                        'isni'     => 'ISNI',
                                    ]),
                            ])
                    ])
            ])
            ->set_help_text('Grupos de contribuintes associados a este formulário.'),
    ]);
