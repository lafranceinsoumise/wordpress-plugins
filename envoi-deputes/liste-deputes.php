<?php

namespace LFI\WPPlugins\EnvoiDeputes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Liste_Deputes
{
    private static $instance = null;
    private $departements;

    private function __construct()
    {
        $deputes_config = wp_json_file_decode(
            dirname(__FILE__) . '/deputes.json',
            ['associative' => true]
        );

        $this->departements = array_combine(
            array_column($deputes_config, 'code'),
            $deputes_config
        );
    }

    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Liste_Deputes();
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
            'code' => $dep_info['code'],
            'nom' => $dep_info['nom'],
            'charniere' => $dep_info['cha'],
            'article' => $dep_info['art']
        ];
    }


    public function depute($departement, $index)
    {
        $dep_info = $this->departements[$departement] ?? null;
        if (is_null($dep_info)) {
            return null;
        }

        $depute = $dep_info['ds'][$index] ?? null;

        if (is_null($depute)) {
            return null;
        }

        if ($depute['s'] === 'F') {
            $fonction = 'Députée';
            $adresse = 'Madame la Députée';
            $civilite = 'Madame';
            $cha = 'à la députée';
        } else {
            $fonction = 'Député';
            $adresse = 'Monsieur le Député';
            $civilite = 'Monsieur';
            $cha = 'au député';
        }

        return [
            'departement' => $departement,
            'id' => $index,
            'nom' => $depute['n'],
            'prenom' => $depute['p'],
            'nom_complet' => "$depute[p] $depute[n]",
            'fonction' => "$fonction $dep_info[cha]$dep_info[nom]",
            'recipient' => "$cha $depute[g] $dep_info[cha]$dep_info[nom]",
            'adresse' => $adresse,
            'civilite' => $civilite,
            'email' => $depute['e']
        ];
    }

    public function random_depute($departement)
    {
        $dep_info = $this->departements[$departement] ?? null;
        if (is_null($dep_info) or empty($dep_info['ds'])) {
            return null;
        }

        $index = array_rand($dep_info['ds'], 1);
        return $this->depute($departement, $index);
    }
}
