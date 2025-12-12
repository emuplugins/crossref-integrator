<?php

// OK
if (!defined('ABSPATH')) exit;

function crossref_register_chapters_cpt()
{

    if (!crossref_verify_fields()) return;

    $labels = array(
        'name'                  => __('Chapters', 'crossref-integrator'),
        'singular_name'         => __('Book', 'crossref-integrator'),
        'menu_name'             => __('Chapters', 'crossref-integrator'),
        'name_admin_bar'        => __('Chapter', 'crossref-integrator'),
        'add_new'               => __('Add New', 'crossref-integrator'),
        'add_new_item'          => __('Add New chapter', 'crossref-integrator'),
        'edit_item'             => __('Edit chapter', 'crossref-integrator'),
        'new_item'              => __('New chapter', 'crossref-integrator'),
        'view_item'             => __('View chapter', 'crossref-integrator'),
        'view_items'            => __('View chapters', 'crossref-integrator'),
        'search_items'          => __('Search chapters', 'crossref-integrator'),
        'not_found'             => __('No chapters found', 'crossref-integrator'),
        'not_found_in_trash'    => __('No chapters found in Trash', 'crossref-integrator'),
        'all_items'             => __('All chapters', 'crossref-integrator'),
        'archives'              => __('Chapter Archives', 'crossref-integrator'),
        'attributes'            => __('Chapter Attributes', 'crossref-integrator'),
        'insert_into_item'      => __('Insert into chapter', 'crossref-integrator'),
        'uploaded_to_this_item' => __('Uploaded to this chapter', 'crossref-integrator'),
        'items_list'            => __('Chapters list', 'crossref-integrator'),
        'items_list_navigation' => __('Chapters list navigation', 'crossref-integrator'),
        'filter_items_list'     => __('Filter chapters list', 'crossref-integrator'),
    );



    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-media-text',
        'supports'           => array('title', 'thumbnail', 'revisions'),
        'rewrite'            => array('slug' => get_option('_crossref_chapters_slug') ?? 'chapters'),
        'show_in_rest'       => false
    );

    register_post_type('crossref_chapters', $args);
}

add_action('init', 'crossref_register_chapters_cpt');
