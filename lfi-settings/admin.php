<?php

namespace LFI\Settings;

final class GeneralAdmin {

    public function __construct()
    {
        // Load translation
        add_action( 'admin_init', array($this, 'init') );
        add_action( 'admin_menu', array($this, 'general_page'), 9 );
    }


    public function init()
    {
        // éviter d'ajouter les infos si on est pas sur la page
        if (! isset($_GET['page']) || $_GET['page'] !== 'lfi') {
            return;
        }

        register_setting(
            'lfi_general',
            'lfi_general',
            array(
                'type' => 'array',
                'default' => array(
                    'hide_authors_enabled' => false,
                    'hide_authors_oembed' => false,
                    'hide_authors_pages' => false,
                    'hide_authors_replacement' => 'La France insoumise',
                ),
            ),
        );

        add_settings_section(
            'hide_authors_section',
            __('Cacher les auteurs', 'lfi-settings'),
            [$this, 'hide_authors_section_render'],
            'lfi',
            array(
                'id' => 'hide_authors_section',
            ),
        );

        add_settings_field(
            'hide_authors_enabled',
            __('Masquage des noms d\'auteurs', 'lfi-settings'),
            [$this, 'hide_authors_enabled_render'],
            'lfi',
            'hide_authors_section',
            array(
                'label_for' => 'hide_authors_enabled',
                'class' => 'lfi_settings_row'
            ),
        );

        add_settings_field(
            'hide_authors_oembed',
            __('Masquage de l\'auteur dans les données oEmbed', 'lfi-settings'),
            [$this, 'hide_authors_oembed_render'],
            'lfi',
            'hide_authors_section',
            array(
                'label_for' => 'hide_authors_oembed',
                'class' => 'lfi_settings_row'
            ),
        );

        add_settings_field(
            'hide_authors_pages',
            __('Désactivation des pages d\'auteur', 'lfi-settings'),
            [$this, 'hide_authors_pages_render'],
            'lfi',
            'hide_authors_section',
            array(
                'label_for' => 'hide_authors_pages',
                'class' => 'lfi_settings_row'
            ),
        );

        add_settings_field(
            'hide_authors_replacement',
            __('Nom affiché à la place de l\'auteur', 'lfi-settings'),
            [$this, 'hide_authors_replacement_render'],
            'lfi',
            'hide_authors_section',
            array(
                'label_for' => 'hide_authors_replacement',
                'class' => 'lfi_settings_row',
            ),
        );
    }

    function general_page() {
        add_menu_page(
            'LFI | Fonctionnalités diverses',
            'La France insoumise',
            'manage_options',
            'lfi',
            [$this, 'general_page_render'],
        );

        add_submenu_page(
            'lfi',
            'LFI | Fonctionnalités diverses',
            'Fonctionnalités diverses',
            'manage_options',
            'lfi',
            [$this, 'general_page_render'],
        );
    }

    function hide_authors_section_render ( $args )
    {
        ?>
        <p id="<?php echo esc_attr( $args['id'] ); ?>">
            Cette fonctionnalité permet de remplacer les noms
            d'auteurs dans divers contextes par une valeur unique,
            définie ici, pour éviter de laisser fuire de l'information
            sur les auteurs réels des articles.
        </p>
        <?php
    }

    function hide_authors_enabled_render( $args )
    {
        $conf = get_option( 'lfi_general' );
        $value = isset( $conf['hide_authors_enabled'] ) ? $conf['hide_authors_enabled'] : false
        ?>
            <input
               type="checkbox"
               id="<?php echo esc_attr( $args['label_for'] ); ?>"
               <?php if ($value) { echo "checked"; } ?>
               name="lfi_general[hide_authors_enabled]"
            >
            <p class="description">
               Cache l'auteur à tous les endroits où get_the_author est
               utilisé pour le récupérer.
            </p>
        <?php
    }

    function hide_authors_oembed_render( $args )
    {
        $conf = get_option( 'lfi_general' );
        $value = isset( $conf['hide_authors_oembed'] ) ? $conf['hide_authors_oembed'] : false;
        ?>
            <input
                 type="checkbox"
                 id="<?php echo esc_attr( $args['label_for'] ); ?>"
                 <?php if ($value) { echo "checked"; } ?>
                 name="lfi_general[hide_authors_oembed]"
            >
            <p class="description">
                Masque les noms d'auteurs dans les données oEmbed utilisées par un
                certain nombre d'applications pour récupérer les métadonnées associées
                à une page (notamment Discord)
            </p>
        <?php
    }

    function hide_authors_pages_render( $args )
    {
        $conf = get_option( 'lfi_general' );
        $value = isset( $conf['hide_authors_pages'] ) ? $conf['hide_authors_pages'] : false;
        ?>
            <input
                 type="checkbox"
                 id="<?php echo esc_attr( $args['label_for'] ); ?>"
                 <?php if ($value) { echo "checked"; } ?>
                 name="lfi_general[hide_authors_pages]"
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

    function general_page_render()
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
            do_settings_sections('lfi');
            submit_button('Valider');
            ?>
          </form>
        </div>
        <?php
    }


}
