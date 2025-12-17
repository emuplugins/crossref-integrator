<?php

use Carbon_Fields\Frontend\Form;
use Carbon_Fields\Frontend\Container;
use Carbon_Fields\Frontend\Field;

Form::make('create_book', 'Criar Livro')
    ->setAjaxCallback(function ($data) {

        $data = $data['carbon_fields_frontend'];

        $file_id = null;

        // Valida e faz upload do arquivo primeiro
        if (!empty($_FILES['lead_file']) && $_FILES['lead_file']['error'] === 0) {

            $file_ext = strtolower(pathinfo($_FILES['lead_file']['name'], PATHINFO_EXTENSION));

            if ($file_ext !== 'docx') {
                wp_send_json_error(['message' => 'Apenas arquivos .docx são permitidos.']);
                return;
            }

            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $uploaded = wp_handle_upload($_FILES['lead_file'], ['test_form' => false]);

            if (isset($uploaded['error'])) {
                wp_send_json_error(['message' => $uploaded['error']]);
                return;
            }

            $attachment = [
                'post_mime_type' => $uploaded['type'],
                'post_title'     => sanitize_file_name($_FILES['lead_file']['name']),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ];

            $attach_id = wp_insert_attachment($attachment, $uploaded['file']);
            wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $uploaded['file']));

            $file_id = $attach_id;
        } else {
            wp_send_json_error(['message' => 'Nenhum arquivo enviado.']);
            return;
        }

        // Cria o post após o arquivo estar válido
        $post_id = wp_insert_post([
            'post_title'  => sanitize_text_field(($data['lead_name'] ?? '') . ' - ' . ($data['post_title'] ?? '')),
            'post_status' => 'pending',
            'post_type'   => 'crossref_books',
            'post_author' => 0,
        ], true);

        if (is_wp_error($post_id)) {
            wp_send_json_error(['message' => $post_id->get_error_message()]);
            return;
        }

        // Salva os metadados
        foreach ($data as $meta_key => $value) {
            if ($meta_key !== 'lead_file') {
                carbon_set_post_meta($post_id, $meta_key, $value);
            }
        }

        // Salva o ID do arquivo
        carbon_set_post_meta($post_id, 'lead_file', $file_id);

        wp_send_json_success(['message' => 'Livro criado com sucesso.']);
    });

/* ------------------- Sobre Você ------------------- */

Container::make('form', 'Sobre Você')
    ->where('id', 'in', ['create_chapter', 'create_book', 'create_submission_call'])
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
    ->where('id', '=', 'create_book')
    ->add_fields([
        Field::make('text', 'post_title', 'Título do Livro')
            ->set_width(30)->set_required(true),



        Field::make('number', 'edition_number', 'Edição')
            ->set_width(30)
            ->set_required(true)
            ->set_attribute('min', 1)
            ->set_attribute('step', 1),

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

        Field::make('textarea', 'jats_abstract', 'Resumo (JATS)')
            ->set_help_text(
                'Resumo estruturado no formato JATS ou texto simples. Pode conter parágrafos e marcação semântica. Máximo de 1200 caracteres.'
            )
            ->set_attribute('maxlength', 1200)
            ->set_required(true),
    ]);

/* ------------------- Detalhes do Capitulo ------------------- */

Container::make('form', 'Detalhes do capítulo')
    ->where('id', '=', 'create_chapter')
    ->add_fields([
        Field::make('text', 'post_title', 'Título do Capítulo')
            ->set_width(30)->set_required(true),
        Field::make('select', 'parent_book', 'Livro Pai')
            ->set_options(function () {
                $books = get_posts([
                    'post_type'   => 'crossref_books',
                    'numberposts' => -1,
                    'orderby'     => 'title',
                    'order'       => 'ASC',
                    'post_status' => ['pending'],
                ]);

                $options = [
                    '' => 'Selecione um livro'
                ];

                foreach ($books as $book) {
                    $status = $book->post_status;

                    // traduz o status usando o text domain nativo do WP
                    switch ($status) {
                        case 'pending':
                            $status_label = __('Pending', 'default');
                            break;
                        case 'publish':
                            $status_label = __('Published', 'default');
                            break;
                        case 'draft':
                            $status_label = __('Draft', 'default');
                            break;
                        default:
                            $status_label = $status;
                    }

                    $postTitle = mb_substr($book->post_title, 0, 14, 'UTF-8');
                    if (mb_strlen($book->post_title, 'UTF-8') > 14) {
                        $postTitle .= '...';
                    }

                    $options[$book->ID] = $book->ID . ' - ' . substr($postTitle, 0, 14);
                }

                return $options;
            })->set_width(30),
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
            ->set_required(true)->set_width(30),


        Field::make('number', 'component_number', __('Chapter Number', 'crossref-integrator'))
            ->set_attribute('min', 1)
            ->set_attribute('step', 1)
            ->set_default_value(1)->set_width(30),

        Field::make('number', 'first_page', __('First Page', 'crossref-integrator'))
            ->set_attribute('min', 1)
            ->set_attribute('step', 1)
            ->set_default_value(1)->set_width(30),

        Field::make('number', 'last_page', __('Last Page', 'crossref-integrator'))
            ->set_attribute('min', 1)
            ->set_attribute('step', 1)
            ->set_default_value(1)->set_width(30),

        Field::make('textarea', 'jats_abstract', __('Abstract (JATS)', 'crossref-integrator'))
            ->set_help_text(__('Structured abstract in JATS format, or plain text.', 'crossref-integrator')),

    ]);


/* ------------------- Contribuintes ------------------- */

Container::make('form', 'Contribuintes')
    ->where('id', 'in', ['create_book', 'create_chapter'])
    ->add_fields([
        Field::make('complex', 'contributor_groups', 'Grupos de Contribuintes')
            ->set_layout('tabbed-horizontal')
            ->set_min(1)
            ->set_header_template('<%- group_title %>')
            ->add_fields([
                Field::make('text', 'group_title', 'Nome do grupo')
                    ->set_required(true)
                    ->set_width(33)->set_default_value('Autores'),

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
                            ->set_width(20),

                        Field::make('text', 'surname', 'Sobrenome')
                            ->set_width(20),

                        Field::make('url', 'orcid', 'ORCID')
                            ->set_width(20),

                        Field::make('url', 'lattes', 'Currículo Lattes')
                            ->set_width(20),

                        Field::make('rich_text', 'bio', 'Biografia')
                            ->set_width(100),

                        Field::make('complex', 'affiliations', 'Afiliações')
                            ->set_layout('tabbed-vertical')
                            ->set_header_template('<%- institution_name %>')
                            ->add_fields([
                                Field::make('text', 'institution_name', 'Instituição')
                                    ->set_required(true)
                                    ->set_width(40),

                                Field::make('text', 'institution_id', 'ID da instituição')
                                    ->set_width(40),

                                Field::make('select', 'role', 'Tipo do ID')
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

/* ------------------- Citações ------------------- */

Container::make('form', 'Citações')
    ->where('id', 'in', ['create_book', 'create_chapter'])
    ->add_fields([
        Field::make('complex', 'citations', 'Obras citadas')
            ->set_layout('tabbed-horizontal')

            ->set_header_template('<%- unstructured_citation %>')
            ->add_fields([
                Field::make('rich_text', 'unstructured_citation', 'Referência')
                    ->set_required(true)
                    ->set_width(50),

                Field::make('text', 'doi', 'DOI da obra')
                    ->set_width(50),
            ])
            ->set_help_text('Artigos, livros e outros conteúdos citados neste capítulo.'),
    ]);





Form::make('create_chapter', 'Criar capítulo')
    ->setAjaxCallback(function ($data) {

        $data = $data['carbon_fields_frontend'];

        // Verifica se o arquivo foi enviado
        if (!empty($_FILES['chapter_file']) && $_FILES['chapter_file']['error'] === 0) {

            $file_ext = strtolower(pathinfo($_FILES['chapter_file']['name'], PATHINFO_EXTENSION));

            // Valida a extensão do arquivo
            if ($file_ext !== 'docx') {
                wp_send_json_error(['message' => 'Apenas arquivos .docx são permitidos.']);
                return; // Interrompe o processo se o arquivo for inválido
            }

            // Realiza o upload do arquivo
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $uploaded = wp_handle_upload($_FILES['chapter_file'], ['test_form' => false]);

            // Verifica se houve erro no upload
            if (isset($uploaded['error'])) {
                wp_send_json_error(['message' => $uploaded['error']]);
                return; // Interrompe o processo caso o upload falhe
            }

            // Cria o anexo para salvar no banco de dados
            $attachment = [
                'post_mime_type' => $uploaded['type'],
                'post_title'     => sanitize_file_name($_FILES['chapter_file']['name']),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ];

            $attach_id = wp_insert_attachment($attachment, $uploaded['file']);

            // Gera os metadados do arquivo
            wp_generate_attachment_metadata($attach_id, $uploaded['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);

            // Salva o ID do anexo no campo meta
            $file_id = $attach_id; // Salva o ID do anexo para posterior uso
        } else {
            $file_id = null; // Nenhum arquivo enviado
        }

        // Cria o post após a validação do arquivo
        $post_id = wp_insert_post([
            'post_title'  => sanitize_text_field(($data['lead_name'] ?? '') . ' - ' . ($data['post_title'] ?? '')),
            'post_status' => 'pending',
            'post_type'   => 'crossref_chapters',
            'post_author' => 0,
        ], true);

        if (is_wp_error($post_id)) {
            wp_send_json_error([
                'message' => $post_id->get_error_message(),
            ]);
            return; // Interrompe o processo caso o post não seja criado
        }

        // Salva os metadados do post, incluindo o ID do arquivo
        foreach ($data as $meta_key => $value) {
            carbon_set_post_meta($post_id, $meta_key, $value);
        }

        // Salva o ID do arquivo, se houver
        if ($file_id) {
            carbon_set_post_meta($post_id, 'chapter_file', $file_id);
        }

        wp_send_json_success([
            'message' => 'Capítulo criado com sucesso.',
        ]);
    });



Form::make('create_submission_call', 'Criar chamada')
    ->setAjaxCallback(function ($data) {

        $data = $data['carbon_fields_frontend'];

        // Verifica se o arquivo foi enviado
        if (!empty($_FILES['submission_file']) && $_FILES['submission_file']['error'] === 0) {

            $file_ext = strtolower(pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION));

            // Valida a extensão do arquivo
            if ($file_ext !== 'docx') {
                wp_send_json_error(['message' => 'Apenas arquivos .docx são permitidos.']);
                return; // Interrompe o processo se o arquivo for inválido
            }

            // Realiza o upload do arquivo
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $uploaded = wp_handle_upload($_FILES['submission_file'], ['test_form' => false]);

            // Verifica se houve erro no upload
            if (isset($uploaded['error'])) {
                wp_send_json_error(['message' => $uploaded['error']]);
                return; // Interrompe o processo caso o upload falhe
            }

            // Cria o anexo para salvar no banco de dados
            $attachment = [
                'post_mime_type' => $uploaded['type'],
                'post_title'     => sanitize_file_name($_FILES['submission_file']['name']),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ];

            $attach_id = wp_insert_attachment($attachment, $uploaded['file']);

            // Gera os metadados do arquivo
            wp_generate_attachment_metadata($attach_id, $uploaded['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);

            // Salva o ID do anexo no campo meta
            $file_id = $attach_id; // Salva o ID do anexo para posterior uso
        } else {
            $file_id = null; // Nenhum arquivo enviado
        }

        // Cria o post após a validação do arquivo
        $post_id = wp_insert_post([
            'post_title'  => sanitize_text_field(($data['lead_name'] ?? '') . ' - ' . ($data['post_title'] ?? '')),
            'post_status' => 'pending',
            'post_type'   => 'submission_calls',
            'post_author' => 0,
        ], true);

        if (is_wp_error($post_id)) {
            wp_send_json_error([
                'message' => $post_id->get_error_message(),
            ]);
            return; // Interrompe o processo caso o post não seja criado
        }

        // Salva os metadados do post, incluindo o ID do arquivo
        foreach ($data as $meta_key => $value) {
            carbon_set_post_meta($post_id, $meta_key, $value);
        }

        // Salva o ID do arquivo, se houver
        if ($file_id) {
            carbon_set_post_meta($post_id, 'submission_file', $file_id);
        }

        wp_send_json_success([
            'message' => 'Chamada criada com sucesso.',
        ]);
    });



/* ------------------- Detalhes da chamada ------------------- */

Container::make('form', 'Detalhes da Chamada')
    ->where('id', '=', 'create_submission_call')
    ->add_fields([
        Field::make('text', 'post_title', 'Tema da chamada')
            ->set_width(100)->set_required(true),
        Field::make('text', 'keywords', '3 a 5 palavras chave')
            ->set_width(40)->set_required(true)
            ->set_attribute('placeholder', 'Saúde, Educação, Segurança Pública...'),

        Field::make('select', 'type', 'Tipo da chamada')
            ->set_width(40)
            ->set_options([
                'rolling_submissions'      => 'Fluxo Contínuo',
                'ebook' => 'Ebook Completo',
            ]),

        Field::make('rich_text', 'submission_call_abstract', 'Resumo')
            ->set_help_text(
                'Resumo estruturado em texto simples. Pode conter parágrafos e marcação semântica. Máximo de 1200 caracteres.'
            )
            ->set_attribute('maxlength', 1200)
            ->set_required(true),


    ]);


Container::make('form', 'Organizadores')
    ->where('id', 'in', ['create_submission_call'])
    ->add_fields([
        Field::make('complex', 'organizing_committee', 'Integrantes da comissão')
            ->set_layout('tabbed-horizontal')

            ->set_header_template('<%- organizer_name %>')
            ->add_fields([
                Field::make('text', 'organizer_name', 'Nome do organizador')
                    ->set_required(true)
                    ->set_width(30),
                Field::make('email', 'organizer_email', 'Email')
                    ->set_required(true)
                    ->set_width(30),
                Field::make('url', 'organizer_lattes', 'Lattes')
                    ->set_width(30),
            ])
            ->set_help_text('Artigos, livros e outros conteúdos citados neste capítulo.')
            ->set_min(1),
    ]);
