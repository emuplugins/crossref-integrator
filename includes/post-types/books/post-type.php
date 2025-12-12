<?php

// OK
if (!defined('ABSPATH')) exit;

function crossref_register_books_cpt()
{


    if (!crossref_verify_fields()) return;


    $labels = array(
        'name'                  => __('Books', 'crossref-integrator'),
        'singular_name'         => __('Book', 'crossref-integrator'),
        'menu_name'             => __('Books', 'crossref-integrator'),
        'name_admin_bar'        => __('Book', 'crossref-integrator'),
        'add_new'               => __('Add New', 'crossref-integrator'),
        'add_new_item'          => __('Add New Book', 'crossref-integrator'),
        'edit_item'             => __('Edit Book', 'crossref-integrator'),
        'new_item'              => __('New Book', 'crossref-integrator'),
        'view_item'             => __('View Book', 'crossref-integrator'),
        'view_items'            => __('View Books', 'crossref-integrator'),
        'search_items'          => __('Search Books', 'crossref-integrator'),
        'not_found'             => __('No books found', 'crossref-integrator'),
        'not_found_in_trash'    => __('No books found in Trash', 'crossref-integrator'),
        'all_items'             => __('All Books', 'crossref-integrator'),
        'archives'              => __('Book Archives', 'crossref-integrator'),
        'attributes'            => __('Book Attributes', 'crossref-integrator'),
        'insert_into_item'      => __('Insert into book', 'crossref-integrator'),
        'uploaded_to_this_item' => __('Uploaded to this book', 'crossref-integrator'),
        'items_list'            => __('Books list', 'crossref-integrator'),
        'items_list_navigation' => __('Books list navigation', 'crossref-integrator'),
        'filter_items_list'     => __('Filter books list', 'crossref-integrator'),
    );


    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-book',
        'supports'           => array('title', 'thumbnail', 'revisions'),
        'rewrite'            => array('slug' => get_option('_crossref_books_slug') ?? 'books'),
        'show_in_rest'       => false
    );

    register_post_type('crossref_books', $args);
}

add_action('init', 'crossref_register_books_cpt');
