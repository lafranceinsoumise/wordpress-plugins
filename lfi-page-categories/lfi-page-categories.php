<?php
/*
Plugin Name: LFI Page taxonomies
Description: Ajoute catégories et tags aux pages
Version: 1.0
Author: Jill Maud Royer
License: GPL3
*/

namespace LFI\WPPlugins\PageCategories;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Plugin
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'add_taxonomies_to_pages']);
    }

    public function add_taxonomies_to_pages() {
        register_taxonomy_for_object_type( 'post_tag', 'page' );
        register_taxonomy_for_object_type( 'category', 'page' );
    }
}

new Plugin();