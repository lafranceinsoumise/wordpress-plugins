<?php
/**
 * @package compteur_elementor
 * @version 0.0.3
 */
/*
  Plugin Name: Compteur LFI
  Plugin URI: https://github.com/lafranceinsoumise/wordpress-plugins/
  Description: Un shortcode pour compter le nombre de soumissions d'un formulaire elementor.
  Author: Salomé Cheysson, Alexandra Puret
  Version: 0.0.2
  Author URI: https://github.com/aktiur/
*/


namespace LFI\Compteur;


define('COMPTEUR_KEY_PREFIX', 'lfi-compteur');


function compteur_redis_key($name) {
    return WP_REDIS_PREFIX . ':' . COMPTEUR_KEY_PREFIX . ':' . $name;
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
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::add_command(COMPTEUR_KEY_PREFIX, [$this, 'setup_compteur_lfi'] );
        }
    }

    function setup_compteur_lfi($args, $assoc_args) {
        global $wpdb;

        $formulaire_id = $args[1];
        $compteur_nom = $args[2];
        $uniques = explode(",", $args[3]);

        $fields_to_select = [];
        $join = [];
        $submission_alias = "s";
        foreach ($uniques as &$unique) {
            array_push($fields_to_select, "{$unique}.value as {$unique}");
            array_push($join, "left join wp_e_submissions_values as {$unique} on {$submission_alias}.id = {$unique}.submission_id and {$unique}.key = '{$unique}'");
        }

        $fields_to_select_str = implode(",", $fields_to_select);
        $join_str = implode(" ", $join);
        $query = "select {$fields_to_select_str} from wp_e_submissions as {$submission_alias} {$join_str} where {$submission_alias}.element_id = '{$formulaire_id}'";
        $rows = $wpdb->get_results($query, "OBJECT_K");

        if (empty($rows)) {
            $this->log("Aucune submissions à traiter");
            return;
        }

        $redis_client = get_redis_client();

        $compteur_init = 0;
        $key = compteur_redis_key($compteur_nom);
        echo "Setup insertion avec la clef : {$key}";
        foreach ($rows as $row) {
            $entries = [];
            foreach ($uniques as &$unique) {
                if ( isset($row->$unique) && $row->$unique !== "") {
                    array_push($entries, $redis_client->sAdd("{$key}:{$unique}", $row->$unique));
                }
            }
            if (count( $entries ) === array_sum( $entries ) ) {
                $compteur_init++;
            }
        }
        $redis_client->incr(compteur_redis_key($compteur_nom), $compteur_init);
        $this->log("Compteur initialisé à : {$compteur_init}");
    }

    private function log($message) {
        if (class_exists("WP_CLI")) {
            \WP_CLI::log("\n{$message}\n");
        }
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
                'nom' => NULL,
                'separateur' => '&nbsp;'
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

        return number_format($valeur, 0, ',', $atts['separateur']);
    }
}


new Plugin();
