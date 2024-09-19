<?php
namespace LFI\WPPlugins\EnvoiParlementaires;

use ElementorPro\Modules\Forms\Classes\Action_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class EnvoiParlementairesAction extends Action_Base
{
    public function get_name()
    {
        return 'lfi-envoi-parlementaires';
    }

    public function get_label()
    {
        return 'LFI : envoi aux parlementaires';
    }

    public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'lfi-envoi-parlementaires',
			[
				'label' => esc_html__( 'Envoi aux parlementaires', 'textdomain' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

        $widget->add_control(
            'lfi-envoi-parlementaires-email-template',
            [
                'label' => esc_html__('Test', 'textdomain'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );

        $widget->end_controls_section();

    }

    public function on_export( $element ) {
        // sert pour supprimer les paramètres configurés ci-dessus dans l'export enregistré
    }

    public function run($record, $ajax_handler) {
        // $record est une instance FormRecord
        // $ajax_handler est une instance du FormAjaxHandler

    }


}
