<?php

if (!defined('ABSPATH')) exit;

function crossref_register_chapters_cpt() {

    if(!crossref_verify_fields()) return;

    $labels = array(
        'name'               => 'Chapters',
        'singular_name'      => 'Chapter',
        
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-media-text',
        'supports'           => array('title', 'thumbnail', 'revisions'),
        'rewrite'            => array('slug' => 'chapters'),
        'show_in_rest'       => false
    );

    register_post_type('chapters', $args);
}

add_action('init', 'crossref_register_chapters_cpt');