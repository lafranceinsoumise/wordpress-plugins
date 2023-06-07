<?php

namespace LFI\WPPlugins\EnvoiSenateurs;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Liste_Senateurs
{
    private static $instance = null;
    private $departements;
    private $target_groups = array(
        "SER", // Socialistes, Écologistes & Républicains
        "Les Républicains",
        "Les Indépendants",
        "RDSE", // Rassemblement Démocratisque et Social Éuropéen
        "UC", // Union Centriste
        "RDPI", // Rassemblement des Démocrates Progressistes et Indépendants
        "NI", // Non inscrits
        // "GEST", // Groupe Écologiste du Sénat Solidarité et Territoires
        // "CRCE", // Groupe Communiste Républicain et Citoyen
    );

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

    public function filter_senateurs($senateur)
    {
        return in_array(
            $senateur['g'],
            $this->target_groups,
            true
        );
    }


    public function departement_senateurs($dep_info)
    {
        if (is_null($dep_info) or empty($dep_info['sen'])) {
            return [];
        }

        return array_filter(
            $dep_info['sen'],
            array($this, 'filter_senateurs')
        );
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
            'article' => $dep_info['art'],
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
            $cha = 'à la sénatrice';
        } else {
            $fonction = 'Sénateur';
            $adresse = 'Monsieur le Sénateur';
            $civilite = 'Monsieur';
            $cha = 'au sénateur';
        }

        return [
            'departement' => $departement,
            'id' => $index,
            'nom' => $senateur['n'],
            'prenom' => $senateur['p'],
            'nom_complet' => "$senateur[p] $senateur[n]",
            'fonction' => "$fonction $dep_info[cha]$dep_info[nom]",
            'recipient' => "$cha $senateur[g] $dep_info[cha]$dep_info[nom]",
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
        $senateurs = $this->departement_senateurs($dep_info);
        if (empty($senateurs)) {

            return null;
        }
        $index = array_rand($senateurs, 1);

        return $this->senateur($departement, $index);
    }
}
