<?php
/**
 * @package compteur_elementor
 * @version 0.0.3
 */
/*
  Plugin Name: Compteur Elementor
  Plugin URI: https://github.com/lafranceinsoumise/wordpress-plugins/
  Description: Un shortcode pour compter le nombre de soumissions d'un formulaire elementor.
  Author: Salomé Cheysson
  Version: 0.0.2
  Author URI: https://github.com/aktiur/
*/


namespace LFI\Compteur;


define('COMPTEUR_KEY_PREFIX', 'lfi-compteur');


function compteur_redis_key($name) {
    return COMPTEUR_KEY_PREFIX . ':' . $name;
}


function get_redis_client() {
    return $GLOBALS['wp_object_cache']->redis;
}




class Plugin
{

    public function __construct()
    {
        add_action('init', [$this, 'compteur_init']);
        add_action('elementor_pro/init', [$this, 'register_elementor_plugins']);
    }

    public function register_elementor_plugins()
    {
        require_once dirname(__FILE__) . '/compteur-action.php';

        $elementor_compteur_action = new CompteurAction();
        \ElementorPro\Plugin::instance()
            ->modules_manager->get_modules('forms')
            ->add_form_action(
                $elementor_compteur_action->get_name(),
                $elementor_compteur_action
            );
    }

    public function compteur_init()
    {
        add_shortcode('lfi-compteur', [$this, 'compteur_shortcode']);
    }

    public function compteur_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'nom' => NULL
            ),
            $atts
        );

        if ( ! isset($atts['nom']) ) {
            return "0<!-- nom du compteur manquant -->";
        }

        $redis_client = get_redis_client();

        $valeur = $redis_client->get( compteur_redis_key( $atts['nom'] ) );

        if (! $valeur ) {
            return "0<!-- le compteur n'existe pas -->";
        }

        return $valeur;
    }
}


new Plugin();
