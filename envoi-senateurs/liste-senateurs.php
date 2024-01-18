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
        "LREM", // Groupe Rassemblement des démocrates, progressistes et indépendants
        "RDSE", // Groupe du Rassemblement Démocratique et Social Européen
        "UC", // Groupe Union Centriste
        "UMP", // Groupe Les Républicains
        "CRC", // Groupe Communiste Républicain Citoyen et Écologiste - Kanaky
        "SOC", // Groupe Socialiste, Écologiste et Républicain
        "RTLI", // Groupe Les Indépendants - République et Territoires
        "GEST", // Groupe Écologiste - Solidarité et Territoires
        "NI", // Réunion administrative des Sénateurs ne figurant sur la liste d'aucun groupe
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
        return is_email($senateur['e']) && in_array(
            $senateur['g'],
            $this->target_groups,
            true
        );
    }


    public function departement_senateurs($departement)
    {
        $dep_info = $this->departements[$departement] ?? null;

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


    public function senateur($departement, $senateur, $index = null)
    {
        $dep_info = $this->departements[$departement] ?? null;
        if (is_null($dep_info)) {
            return null;
        }

        if (is_null($senateur) && false === is_null($index)) {
            $senateur = $dep_info['sen'][$index] ?? null;
        }

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
            'groupe' => $senateur["gl"],
            'recipient' => "$cha $dep_info[cha]$dep_info[nom]",
            'adresse' => $adresse,
            'civilite' => $civilite,
            'email' => $senateur['e']
        ];
    }

    function expediteur($args)
    {
        if (
            !array_key_exists('email', $args)
            || !array_key_exists('nom', $args)
            || !array_key_exists('prenom', $args)
            || !array_key_exists('civilite', $args)
        ) {
            return null;
        }

        $nom_complet = mb_convert_case($args['prenom'], MB_CASE_TITLE)
            . ' ' . mb_convert_case($args['nom'], MB_CASE_UPPER);

        return [
            'email' => $args['email'],
            'nom' => $args['nom'],
            'prenom' => $args['prenom'],
            'profession' => $args['profession'] ?? "",
            'civilite' => $args['civilite'],
            'nom_complet' => $nom_complet,
        ];
    }

    public function random_senateur($departement)
    {
        $senateurs = $this->departement_senateurs($departement);
        if (empty($senateurs)) {
            return null;
        }
        $index = array_rand($senateurs, 1);

        return $this->senateur($departement, null, $index);
    }

    public function departement_twitters($departement)
    {
        $senateurs = $this->departement_senateurs($departement);

        $twitters = [];
        foreach ($senateurs as $senateur) {
            if ($senateur["t"]) {
                array_push($twitters, "@" . $senateur["t"]);
            }
        }
        return $twitters;
    }
}
