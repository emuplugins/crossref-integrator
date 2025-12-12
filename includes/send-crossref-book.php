<?php

// OK

if (!defined('ABSPATH')) exit;

add_action('wp_ajax_send_crossref_book', 'handler_send_crossref_book');

function handler_send_crossref_book()
{
    require(CROSSREF_PLUGIN_DIR . '/includes/classes/CreateBookXml.php');

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'send_crossref_nonce')) {
        wp_send_json_error(['message' => __('Access denied (invalid nonce).', 'crossref-integrator')], 403);
    }

    $bookId = $_POST['post_ID'];

    // Gerar XML do capítulo
    try {
        $xmlString = CreateBookXML::generate($bookId);
    } catch (Throwable $e) {
        wp_send_json_error([
            'message' => __('Error generating XML:', 'crossref-integrator') . ' ' . $e->getMessage(),
            'status'  => 'error'
        ]);
    }

    // Salvar XML no post meta
    update_post_meta(
        $bookId,
        '_crossref_xml',
        $xmlString
    );

    // Gera o “arquivo virtual” em memória
    $tmpFile = 'data://text/plain;base64,' . base64_encode($xmlString);

    $postFields = [
        'operation'     => 'doMDUpload',
        'login_id'      => carbon_get_theme_option('crossref_login_id'),
        'login_passwd'  => carbon_get_theme_option('crossref_login_passwd'),
        'fname'         => curl_file_create($tmpFile, 'application/xml', 'book.xml')
    ];

    $ch = curl_init(carbon_get_theme_option('crossref_doi_deposit_link'));
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        wp_send_json_error(['message' => __('Error sending file:', 'crossref-integrator') . ' ' . $error]);
    } else {
        wp_send_json_success([
            'message'  => __('File successfully sent.', 'crossref-integrator'),
            'response' => $response
        ]);
    }
}
