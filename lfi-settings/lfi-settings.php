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
        add_action( 'admin_init', array($this, 'settings_init') );
        add_action( 'admin_menu', array($this, 'settings_page') );
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

    public function replace_author( $display_author )
    {
        $options = get_option( 'lfi_general' );
        if ( $options['hide_authors_enabled'] ) {
            return $options['hide_authors_replacement'];
        }
        return $display_author;
    }

    public function replace_author_in_oembed( $data )
    {
        $options = get_option( 'lfi_general' );
        if ( $options['hide_authors_enabled'] ) {
            $data['author_name'] = $options[ 'hide_authors_replacement' ];
            $data['author_url'] = get_home_url();
        }
        return $data;
    }

    public function disable_author_pages() {
        $options = get_option( 'lfi_general' );
        if ( $options['hide_authors_enabled'] && is_author() ) {
            wp_redirect( home_url() );
        }
    }

    public function settings_init()
    {
        register_setting(
            'lfi_general',
            'lfi_general',
            array(
                'type' => 'array',
                'default' => array(
                    'hide_authors_enabled' => false,
                    'hide_authors_replacement' => 'La France insoumise',
                ),
            ),
        );

        add_settings_section(
            'lfi_hide_authors_section',
            __('Masquer les noms d\'auteurs', 'lfi-settings'),
            [$this, 'hide_authors_section_render'],
            'lfi_general',
            array(
                'id' => 'lfi_hide_authors_section',
            ),
        );

        add_settings_field(
            'lfi_hide_authors_enabled',
            __('Activer le masquage des noms d\'auteurs', 'lfi-settings'),
            [$this, 'hide_authors_enabled_render'],
            'lfi_general',
            'lfi_hide_authors_section',
            array(
                'label_for' => 'lfi_hide_authors_enabled',
                'class' => 'lfi_settings_row'
            ),
        );

        add_settings_field(
            'lfi_hide_authors_replacement',
            __('Nom affiché à la place', 'lfi-settings'),
            [$this, 'hide_authors_replacement_render'],
            'lfi_general',
            'lfi_hide_authors_section',
            array(
                'label_for' => 'lfi_hide_authors_replacement',
                'class' => 'lfi_settings_row',
            ),
        );
    }


    function settings_page() {
        add_menu_page(
            'Paramètres généraux',
            'La France insoumise',
            'manage_options',
            'lfi_general',
            [$this, 'settings_page_render'],
        );
    }

    function hide_authors_section_render ( $args )
    {
        ?>
        <p id="<?php echo esc_attr( $args['id'] ); ?>">
            Cette fonctionnalité permet de remplacer les noms
            d'auteurs partout par une valeur unique, définie ici, lorsqu'elle est active.
        </p>
        <?php
    }

    function hide_authors_enabled_render( $args )
    {
        $value = get_option( 'lfi_general' )['hide_authors_enabled'];
        ?>
            <input
               type="checkbox"
               id="<?php echo esc_attr( $args['label_for'] ); ?>"
               <?php if ($value) { echo "checked"; } ?>
               name="lfi_general[hide_authors_enabled]"
            >
        <?php
    }

    function hide_authors_replacement_render( $args )
    {
        $value = get_option( 'lfi_general' )['hide_authors_replacement'];
        ?>
            <input type="text"
                   id="<?php echo esc_attr( $args['label_for'] ); ?>"
                   name="lfi_general[hide_authors_replacement]"
                   value="<?php echo $value; ?>"
            >
        <?php
    }

    function settings_page_render()
    {

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( isset( $_GET['settings-updated'] ) ) {
            // add settings saved message with the class of "updated"
            add_settings_error(
                'lfi_messages',
                'lfi_message',
                __( 'Paramètres enregistrés', 'lfi-settings' ),
                'updated'
            );
        }

        settings_errors( 'lfi_messages' );

        ?>
        <div class="wrap">
          <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
          <form action="options.php" method="post">
            <?php
            settings_fields('lfi_general');
            do_settings_sections('lfi_general');
            submit_button('Valider');
            ?>
          </form>
        </div>
        <?php
    }

}

new Plugin();
