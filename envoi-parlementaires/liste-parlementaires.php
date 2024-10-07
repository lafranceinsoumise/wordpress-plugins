<?php

namespace LFI\WPPlugins\EnvoiParlementaires;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Liste_Parlementaires
{
    private static $instance = null;
    private $liste_parlementaires;

    public function exclude_parlementaire($parlementaire)
    {
        $parlementaire_to_exclude = array_map('strtolower', [
            "M. Pouria Amirshahi", "Mme Léa Balage El Mariky", "M. Emmanuel Duplessy", "Mme Émeline K/Bidi", "M. Stéphane Peu", "M. Frédéric Maillot", "M. Benjamin Lucas-Lundy", "M. Alexis CORBIÈRE", "Mme Clémentine Autain", "M. Hendrik DAVI", "Mme Karine Lebon", "Mme Sandrine Rousseau", "Mme Danielle Simonnet"
        ]);
        if (
            $parlementaire["groupe"] === "La France insoumise - Nouveau Front Populaire" ||
            in_array(strtolower($parlementaire["nom"]), $parlementaire_to_exclude)
        ) {
            return false;
        }

        return true;
    }

    private function __construct()
    {
        $this->liste_parlementaires = array_filter(wp_json_file_decode(
            dirname(__FILE__) . '/deputes.json',
            ['associative' => true]
        ), array($this, "exclude_parlementaire"));
    }

    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Liste_Parlementaires();
        }
        return self::$instance;
    }

    public function get_departements() {
        $departements = [];
        foreach ($this->liste_parlementaires as $parlementaire) {
            $departement = $parlementaire["departement"];
            if (array_key_exists($departement, $departements)) {
                array_push($departements, $departement);
            }
        }
        return $departements;
    }

    public function get_parlementaire_par_departement($departement) {
        return array_filter($this->liste_parlementaires, function($parlementaire) use ($departement) {
            return $parlementaire["departement"] == $departement;
        });
    }

    function expediteur($args)
    {
        if (
            !array_key_exists('email', $args)
            || !array_key_exists('nom', $args)
            || !array_key_exists('prenom', $args)
        ) {
            return null;
        }

        return [
            'email' => $args['email'],
            'nom' => $args['nom'],
            'prenom' => $args['prenom'],
            'departement' => $args['departement']
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
