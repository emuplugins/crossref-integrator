<?php

// OK
if (!defined('ABSPATH')) exit;

function crossref_register_submissions_cpt()
{


    if (!crossref_verify_fields()) return;


    $labels = array(
        'name'                  => __('Calls for Submissions', 'crossref-integrator'),
        'singular_name'         => __('Submission Call', 'crossref-integrator'),
        'menu_name'             => __('Submission Calls', 'crossref-integrator'),
        'name_admin_bar'        => __('Submission Calls', 'crossref-integrator'),
        'add_new'               => __('Add New', 'crossref-integrator'),
        'add_new_item'          => __('Add New Submission', 'crossref-integrator'),
        'edit_item'             => __('Edit Call', 'crossref-integrator'),
        'new_item'              => __('New Call', 'crossref-integrator'),
        'view_item'             => __('View Call', 'crossref-integrator'),
        'view_items'            => __('View Calls', 'crossref-integrator'),
        'search_items'          => __('Search Submission Calls', 'crossref-integrator'),
        'not_found'             => __('No submissions found', 'crossref-integrator'),
        'not_found_in_trash'    => __('No submissions found in Trash', 'crossref-integrator'),
        'all_items'             => __('All Submission Calls', 'crossref-integrator'),
        'archives'              => __('Submission Call Archives', 'crossref-integrator'),
        'attributes'            => __('Submission Call Attributes', 'crossref-integrator'),
        'insert_into_item'      => __('Insert into submission', 'crossref-integrator'),
        'uploaded_to_this_item' => __('Uploaded to this submission', 'crossref-integrator'),
        'items_list'            => __('Submission Calls list', 'crossref-integrator'),
        'items_list_navigation' => __('Submission Calls list navigation', 'crossref-integrator'),
        'filter_items_list'     => __('Filter submissions list', 'crossref-integrator'),
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
