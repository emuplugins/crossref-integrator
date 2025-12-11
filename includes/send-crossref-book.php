<?php

if (!defined('ABSPATH')) exit;

add_action('wp_ajax_send_crossref_book', 'handler_send_crossref_book');

function handler_send_crossref_book()
{
    require(CROSSREF_PLUGIN_DIR . '/includes/classes/CreateBookXml.php');

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'send_crossref_nonce')) {
        wp_send_json_error(['message' => 'Acesso negado (nonce inválido).'], 403);
    }

    // ============================
    // Mapear e selecionar os campos enviados, para o formato correto da crossref
    // ============================

    $mapa = [
        'post_ID'                  => 'post_id',
        'post_title'               => 'book_title',
        'doi'                      => 'doi',
        'jats_abstract'            => 'jats_abstract',
        'isbn_e'                   => 'isbn_e',
        'isbn_p'                   => 'isbn_p',
        'edition_number'           => 'edition_number',
        'online_publication_date'  => 'online_date',
        'print_publication_date'   => 'print_date',
        'language'                 => 'language',
        'resource'                 => 'resource',
        'contributors'                 => 'contributors',
    ];

    $dados = [];

    foreach ($mapa as $postKey => $novoNome) {
        if (isset($_POST[$postKey])) {
            $dados[$novoNome] = $_POST[$postKey];
        }
    }

    // ============================
    // Decide se um contribuinte é uma pessoa física, ou uma organização
    // ============================

    $contribuintes = [];

    if (!empty($_POST['contributors'])) {
        foreach ($_POST['contributors'] as $c) {

            $given   = trim($c['given'] ?? '');
            $surname = trim($c['surname'] ?? '');
            $orcid   = trim($c['orcid'] ?? '');
            $role    = trim($c['role'] ?? 'author');

            if ($surname !== '') {
                $contribuintes[] = [
                    'type'       => 'person',
                    'given'      => $given,
                    'surname'    => $surname,
                    'orcid'      => $orcid,
                    'role'       => $role,
                    'afiliacoes' => [],
                ];
            } else {
                $contribuintes[] = [
                    'type'  => 'organization',
                    'given' => $given,
                    'role'  => $role,
                ];
            }
        }
    }

    $dados['contribuintes'] = $contribuintes;


    // ============================
    // Gerar XML e Salvar no campo _crossref_xml no banco de dados
    // ============================

    try {
        $xmlString = CreateBookXML::generate($dados);
    } catch (Throwable $e) {
        wp_send_json_error([
            'message' => $e->getMessage(),
            'status' => 'error'
        ]);
    }

    update_post_meta(
        $dados['post_id'],
        '_crossref_xml',
        $xmlString
    );

    // ============================
    // Enviar XML para a crossref
    // ============================
    
    wp_send_json_success([
        'message'  => 'enviado',
    ]);
    
    // Gera o “arquivo virtual” em memória


    // $tmpFile = 'data://text/plain;base64,' . base64_encode($xmlString);

    // $postFields = [
    //     'operation'     => 'doMDUpload',
    //     'login_id'      => 'arcoed',
    //     'login_passwd'  => 'NovaSenha2327.',
    //     'fname'         => curl_file_create($tmpFile, 'application/xml', 'book.xml')
    // ];

    // $ch = curl_init("https://test.crossref.org/servlet/deposit");
    // curl_setopt_array($ch, [
    //     CURLOPT_POST => true,
    //     CURLOPT_POSTFIELDS => $postFields,
    //     CURLOPT_RETURNTRANSFER => true,
    //     CURLOPT_SSL_VERIFYPEER => true
    // ]);

    // $response = curl_exec($ch);
    // $error    = curl_error($ch);
    // curl_close($ch);

    // if ($error) {
    //     wp_send_json_error(['message' => $error]);
    // } else {
    //     wp_send_json_success([
    //         'message'  => 'Arquivo enviado',
    //         'response' => $response
    //     ]);
    // }
}
