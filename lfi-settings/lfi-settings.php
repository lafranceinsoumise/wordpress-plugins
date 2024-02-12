<?php

/**
 * Plugin Name: LFI Réglages par défaut
 * Description: Ajustements divers à mettre en place sur tous nos wordpress
 * Plugin URI:  https://github.com/lafranceinsoumise/wordpress-plugins
 * Version:     1.0.0
 * Author:      Salomé Cheysson
 * Author URI:  https://github.com/aktiur/
 * Text Domain: lfi-settings
 *
 */

namespace LFI\Settings;


if (!defined('ABSPATH')) exit; // Exit if accessed directly


final class Plugin
{
    /**
     * Plugin Version
     *
     * @since 1.0.0
     * @var string The plugin version.
     */
    const VERSION = '1.0.0';

    /**
     * Minimum PHP Version
     *
     * @since 1.0.0
     * @var string Minimum PHP version required to run the plugin.
     */
    const MINIMUM_PHP_VERSION = '7.0';

    public function __construct()
    {
        // Load translation
        add_action( 'init', array($this, 'i18n') );
        add_action( 'init', array($this, 'admin_init') );
        add_filter( 'the_author', array($this, 'replace_author') );
        add_filter( 'oembed_response_data', array($this, 'replace_author_in_oembed') );
        add_action( 'template_redirect', array($this, 'disable_author_pages') );
    }

    /**
     * Load Textdomain
     *
     * Load plugin localization files.
     * Fired by `init` action hook.
     *
     * @since 1.0.0
     * @access public
     */
    public function i18n()
    {
        load_plugin_textdomain( 'lfi-programme' );
    }

    public function admin_init() {
        require_once dirname(__FILE__) . '/admin.php';
        new GeneralAdmin();
    }

    public function replace_author( $display_author )
    {
        $options = get_option( 'lfi_general' );
        $enabled = isset($options['hide_authors_enabled']) ?
            $options['hide_authors_enabled'] : false;
        if ( $enabled && !is_admin() ) {
            return $options['hide_authors_replacement'];
        }
        return $display_author;
    }

    public function replace_author_in_oembed( $data )
    {
        $options = get_option( 'lfi_general' );
        $enabled = isset($options['hide_authors_oembed']) ?
            $options['hide_authors_oembed'] : false;
        if ( $enabled ) {
            $data['author_name'] = $options[ 'hide_authors_replacement' ];
            $data['author_url'] = get_home_url();
        }
        return $data;
    }

    public function disable_author_pages() {
        $options = get_option( 'lfi_general' );
        $enabled = isset($options['hide_authors_pages']) ?
            $options['hide_authors_pages'] : false;
        if ( $enabled && is_author() ) {
            wp_redirect( home_url() );
        }
    }

}

new Plugin();
