<?php

namespace LFI\Compteur;


use ElementorPro\Modules\Forms\Classes\Action_Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class CompteurAction extends Action_Base
{
    public function get_name()
    {
        return 'lfi-compteur';
    }

    public function get_label()
    {
        return "LFI : incrémenter compteur";
    }

    public function run($record, $ajax_handler)
    {
        $settings = $record->get('form_settings');
        $raw_fields = $record->get('fields');

        $redis_client = get_redis_client();

        if (
            empty($settings['lfi_compteur_nom'])
        ) {
            return;
        }

        if ( empty( $settings['lfi_compteur_uniques'] ) ) {
            $uniques = [];
        } else {
            $uniques = explode( ',', $settings['lfi_compteur_unique'] );
        }

        $key_prefix = compteur_redis_key($settings["lfi_compteur_nom"]);

        $results = [];

        foreach($uniques as $field) {
            $value = $raw_fields[$field];
            if ( isset($value) && $value !== "" ) {
                array_push(
                    $results,
                    $redis_client->sAdd("{$key_prefix}:{$field}", $value )
                );
            }
        }

        if ( count( $results ) === 0 ) {
            $ajax_handler->add_error_message(
                $settings['lfi_compteur_message_erreur']
            );
            return;
        }

        if ( array_sum( $results ) == count( $results ) ) {
            $redis_client->incr( $key_prefix );
        }

    }

    public function register_settings_section($widget)
    {
        $widget->start_controls_section('section_lfi_compteur', [
            'label' => 'Incrémenter un compteur',
            'condition' => [
                'submit_actions' => $this->get_name(),
            ],
        ]);

        $widget->add_control(
            'lfi_compteur_nom',
            [
                'label' => "Identifiant du compteur",
                'type' => \Elementor\Controls_Manager::TEXT,
                'description' => 'Un identifiant de compteur, qui devra aussi être utilisé dans le short code pour afficher la valeur du compteur.',
            ]
        );

        $widget->add_control(
            'lfi_compteur_uniques',
            [
                'label' => 'Champs de déduplication',
                'type' => \Elementor\Controls_Manager::TEXT,
                'description' => 'La liste des champs à utiliser pour dédupliquer les inscriptions, séparés par des virgules (par exemple : email,telephone).'
            ]
        );

        $widget->add_control(
            'lfi_compteur_message_erreur',
            [
                'label' => 'Message d\'erreur',
                'type' => \Elementor\Controls_Manager::TEXT,
                'description' => 'Le message d\'erreur à afficher si l\'utilisateur n\'a renseigné aucun des champs de déduplication.'
            ]
        );

        $widget->end_controls_section();
    }

    public function on_export($element)
    {
        unset($element['lfi_compteur_nom']);
        unset($element['lfi_compteur_uniques']);
        unset($element['lfi_compteur_message_erreur']);
    }
}
