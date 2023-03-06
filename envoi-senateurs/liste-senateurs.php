<?php

namespace LFI\WPPlugins\EnvoiSenateurs;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Liste_Senateurs
{
    private static $instance = null;
    private $departements;

    private function __construct()
    {
        $senateurs_config = wp_json_file_decode(
            dirname(__FILE__) . '/senateurs.json',
            ['associative' => true]
        );

        $this->departements = array_combine(
            array_column($senateurs_config, 'id'),
            $senateurs_config
        );
    }

    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Liste_Senateurs();
        }
        return self::$instance;
    }

    public function departement_existe($departement)
    {
        return array_key_exists($departement, $this->departements);
    }

    public function departement($departement)
    {
        $dep_info = $this->departements[$departement] ?? null;

        if (is_null($dep_info)) {
            return null;
        }

        return [
            'code' => $dep_info['id'],
            'nom' => $dep_info['nom'],
            'charniere' => $dep_info['cha'],
            'article' => $dep_info['art']
        ];
    }


    public function senateur($departement, $index)
    {
        $dep_info = $this->departements[$departement] ?? null;
        if (is_null($dep_info)) {
            return null;
        }

        $senateur = $dep_info['sen'][$index] ?? null;

        if (is_null($senateur)) {
            return null;
        }

        if ($senateur['s'] === 'F') {
            $fonction = 'Sénatrice';
            $adresse = 'Madame la Sénatrice';
            $civilite = 'Madame';
        } else {
            $fonction = 'Sénateur';
            $adresse = 'Monsieur le Sénateur';
            $civilite = 'Monsieur';
        }

        return [
            'departement' => $departement,
            'id' => $index,
            'nom' => $senateur['n'],
            'prenom' => $senateur['p'],
            'nom_complet' => "$senateur[p] $senateur[n]",
            'fonction' => "$fonction $dep_info[cha]$dep_info[nom]",
            'adresse' => $adresse,
            'civilite' => $civilite,
            'email' => $senateur['e']
        ];
    }

    public function random_senateur($departement)
    {
        $dep_info = $this->departements[$departement] ?? null;
        if (is_null($dep_info)) {
            return null;
        }

        $index = array_rand($dep_info['sen'], 1);
        return $this->senateur($departement, $index);
    }
}
