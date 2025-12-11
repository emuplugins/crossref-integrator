<?php

/**
 * Plugin Name: Crossref Integrator
 * Description: Integra o WordPress com a API do Crossref, permitindo gerar e enviar metadados em XML conforme o padrão oficial, para a criação de DOIs.
 * Version: 1.0.0
 * Author: Tonny Santana
 * Author URI: https://angardagency.com
 * License: GPL2
 * Text Domain: crossref-integrator
 */

// Constantes de diretório do plugin
define('CROSSREF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CROSSREF_PLUGIN_URL', plugin_dir_url(__FILE__));

function crossref_verify_fields() {
    $doi_link     = get_option('_crossref_doi_deposit_link');
    $login_id     = get_option('_crossref_login_id');
    $login_passwd = get_option('_crossref_login_passwd');
    $doi_prefix   = get_option('_crossref_doi_prefix');
    $depositor    = get_option('_crossref_depositor');

    if (empty($doi_link) || empty($login_id) || empty($login_passwd) || empty($doi_prefix) || empty($depositor)) {
        return false;
    }

    return true;
}



add_action('after_setup_theme', 'crb_load');
function crb_load()
{
    require_once('vendor/autoload.php');
    \Carbon_Fields\Carbon_Fields::boot();

    require_once(CROSSREF_PLUGIN_DIR . '/includes/option-page.php');

    // Campos preenchidos, então carregamos os arquivos necessários
    require_once(CROSSREF_PLUGIN_DIR . '/includes/post-types/chapters/post-type.php');
    require_once(CROSSREF_PLUGIN_DIR . '/includes/post-types/books/post-type.php');
    require_once(CROSSREF_PLUGIN_DIR . '/includes/post-types/metaboxes/main.php');
}



// Iniciar os assets
function crossref_integrator_assets($hook)
{
    global $post_type;

    $screen = get_current_screen();
    if ($screen && $screen->base !== 'post') {
        return;
    }


    // CSS comum
    wp_enqueue_style(
        'crossref-integrator-style',
        CROSSREF_PLUGIN_URL . 'assets/style.css',
        [],
        filemtime(CROSSREF_PLUGIN_DIR . 'assets/style.css')
    );

    // JS específico por post type
    if ($post_type === 'books') {
        wp_enqueue_script(
            'crossref-integrator-books',
            CROSSREF_PLUGIN_URL . 'assets/books.js',
            ['jquery'],
            filemtime(CROSSREF_PLUGIN_DIR . 'assets/books.js'),
            true
        );
    }

    if ($post_type === 'chapters') {
        wp_enqueue_script(
            'crossref-integrator-chapters',
            CROSSREF_PLUGIN_URL . 'assets/chapters.js',
            ['jquery'],
            filemtime(CROSSREF_PLUGIN_DIR . 'assets/chapters.js'),
            true
        );
    }

    // CSS do Select2 (comum)
    wp_enqueue_style(
        'select2-css',
        CROSSREF_PLUGIN_URL . '/assets/select2.min.css',
        [],
        '4.1.0'
    );

    // JS do Select2 (comum)
    wp_enqueue_script(
        'select2-js',
        CROSSREF_PLUGIN_URL . '/assets/select2.min.js',
        ['jquery'],
        '4.1.0',
        true
    );

    // JS geral 
    wp_enqueue_script(
        'crossref-base',
        CROSSREF_PLUGIN_URL . '/assets/script.js',
        ['jquery'],
        '4.1.0',
        true
    );
}
add_action('admin_enqueue_scripts', 'crossref_integrator_assets');
