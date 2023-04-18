<?php
namespace LFI\WPPlugins\EnvoiSenateurs;

use ElementorPro\Modules\Forms\Classes\Action_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class EnvoiSenateursAction extends Action_Base
{
    public function get_name()
    {
        return 'lfi-envoi-senateurs';
    }

    public function get_label()
    {
        return 'LFI : envoi aux sénateurs';
    }

    public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'lfi-envoi-senateurs',
			[
				'label' => esc_html__( 'Envoi aux sénateurs', 'textdomain' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

        $widget->add_control(
            'lfi-envoi-senateurs-email-template',
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
