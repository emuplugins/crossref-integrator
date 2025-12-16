<?php

// OK
if (!defined('ABSPATH')) exit;

function crossref_register_submissions_cpt()
{


    if (!crossref_verify_fields()) return;


    $labels = array(
        'name'                  => 'Chamadas de Textos',
        'singular_name'         => 'Chamada',
        'menu_name'             => 'Chamadas',
        'name_admin_bar'        => 'Chamada',
        'add_new'               => __('Adicionar Novo', 'crossref-integrator'),
        'add_new_item'          => __('Adicionar Chamada', 'crossref-integrator'),
        'edit_item'             => __('Editar Chamada', 'crossref-integrator'),
        'new_item'              => __('Nova Chamada', 'crossref-integrator'),
        'view_item'             => __('Ver Chamada', 'crossref-integrator'),
        'view_items'            => __('Ver Chamadas', 'crossref-integrator'),
        'search_items'          => __('Procurar Chamadas de Texto', 'crossref-integrator'),
        'not_found'             => __('Nenhuma chamada encontrada', 'crossref-integrator'),
        'not_found_in_trash'    => __('Nenhuma chamada encontrada na lixeira', 'crossref-integrator'),
        'all_items'             => __('Todas as Chamadas', 'crossref-integrator'),
        'archives'              => __('Arquivos de Chamadas de Texto', 'crossref-integrator'),
        'attributes'            => __('Atributos da Chamada de Texto', 'crossref-integrator'),
        'insert_into_item'      => __('Inserir na chamada', 'crossref-integrator'),
        'uploaded_to_this_item' => __('Enviado para esta chamada', 'crossref-integrator'),
        'items_list'            => __('Lista de Chamadas de Texto', 'crossref-integrator'),
        'items_list_navigation' => __('Navegação da lista de Chamadas de Texto', 'crossref-integrator'),
        'filter_items_list'     => __('Filtrar lista de Chamadas de Texto', 'crossref-integrator'),
    );



    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-megaphone',
        'supports'           => array('title', 'thumbnail', 'revisions'),
        'show_in_rest'       => false
    );

    register_post_type('submission_calls', $args);
}

add_action('init', 'crossref_register_submissions_cpt');
