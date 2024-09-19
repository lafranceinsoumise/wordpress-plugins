<?php

namespace LFI\WPPlugins\EnvoiParlementaires;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Liste_Parlementaires
{
    private static $instance = null;
    private $liste_parlementaires;

    private function __construct()
    {
        $this->liste_parlementaires = wp_json_file_decode(
            dirname(__FILE__) . '/comission_lois.json',
            ['associative' => true]
        );
    }

    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Liste_Parlementaires();
        }
        return self::$instance;
    }

    function expediteur($args)
    {
        if (
            !array_key_exists('email', $args)
            || !array_key_exists('nom', $args)
        ) {
            return null;
        }

        return [
            'email' => $args['email'],
            'nom' => $args['nom'],
        ];
    }

    public function random_parlementaire()
    {
        return $this->liste_parlementaires[array_rand($this->liste_parlementaires, 1)];
    }

    public function all_parlementaires()
    {
        return $this->liste_parlementaires;
    }
}
