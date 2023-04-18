<?php
namespace LFI\WPPlugins\EnvoiDeputes;

use ElementorPro\Modules\Forms\Classes\Action_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class EnvoiDeputesAction extends Action_Base
{
    public function get_name()
    {
        return 'lfi-envoi-deputes';
    }

    public function get_label()
    {
        return 'LFI : envoi aux députés';
    }

    public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'lfi-envoi-deputes',
			[
				'label' => esc_html__( 'Envoi aux députés', 'textdomain' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

        $widget->add_control(
            'lfi-envoi-deputes-email-template',
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
