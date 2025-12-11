<?php

if (!defined('ABSPATH')) exit;

function crossref_register_books_cpt() {

    $labels = array(
        'name'               => 'Books',
        'singular_name'      => 'Book',
        
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-book',
        'supports'           => array('title', 'thumbnail', 'revisions'),
        'rewrite'            => array('slug' => 'books'),
        'show_in_rest'       => false
    );

    register_post_type('books', $args);
}

add_action('init', 'crossref_register_books_cpt');