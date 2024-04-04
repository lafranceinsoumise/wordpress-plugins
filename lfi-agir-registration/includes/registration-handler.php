<?php

namespace LFI\WPPlugins\AgirRegistration;


use ElementorPro\Modules\Forms\Classes\Action_Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class RegistrationAction extends Action_Base
{
    public function get_name()
    {
        return 'lfi-registration';
    }

    public function get_label()
    {
        return "LFI : Inscription à la plateforme";
    }

    protected function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTimeImmutable::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    public function run($record, $ajax_handler)
    {
        $settings = $record->get('form_settings');
        $raw_fields = $record->get('fields');

        if (
            empty($settings['agir_registration_type']) ||
            !in_array($settings['agir_registration_type'], ['LFI', 'NSP', 'LJI', 'ISE', 'EU24'])
        ) {
            return;
        }

        // Normalize the Form Data
        $fields = [];
        foreach ($raw_fields as $id => $field) {
            $fields[str_replace("agir_", "", $id)]  = $field['value'];
        }

        // Allow desactivating the whole subscription action process on-demand
        if (array_key_exists('lfi_registration', $fields) && $fields['lfi_registration'] === "N") {
            return;
        }

        if (empty($fields['email'])) {
            $ajax_handler->add_error("email", "L'email est obligatoire.");
        }

        if (!empty($fields['email']) && !is_email($fields['email'])) {
            $ajax_handler->add_error("email", "L'e-mail est invalide.");
        }

        // le code postal n'est obligatoire que sans précision du pays ou si le pays est la France
        if (empty($fields['location_country']) || $fields['location_country'] == 'FR') {
            if (empty($fields['location_zip'])) {
                $ajax_handler->add_error("location_zip", 'Le code postal est obligatoire.');
            }

            if (!empty($fields['location_zip']) && !preg_match('/^[0-9]{5}$/', $fields['location_zip'])) {
                $ajax_handler->add_error("location_zip", 'Le code postal est invalide.');
            }
        }

        // Validate date_of_birth format
        if (array_key_exists('date_of_birth', $fields)) {
            if (!empty($fields['date_of_birth']) && false === $this->validateDate($fields["date_of_birth"])) {
                $ajax_handler->add_error("date_of_birth", 'La date de naissance est invalide. Veuillez renseigner une date au format AAAA-MM-JJ.');
            }
            // Allow specifying the date of birth in the format 'd/m/Y' through the 'dob' field
        } elseif (array_key_exists('dob', $fields) && !empty($fields['dob'])) {
            if ($this->validateDate($fields["dob"], "d/m/Y")) {
                $fields["date_of_birth"] = \DateTimeImmutable::createFromFormat('d/m/Y', $fields["dob"])->format('Y-m-d');
            } else {
                $ajax_handler->add_error("dob", 'La date de naissance est invalide. Veuillez renseigner une date au format JJ/MM/AAAA.');
            }
        }

        if (array_key_exists('media_preferences', $fields)) {
            $fields["media_preferences"] = empty($fields["media_preferences"]) ? [] : explode(",", $fields["media_preferences"]);
        }

        if (count($ajax_handler->errors) > 0) {
            return;
        }


        $data = [];
        $data["email"] = sanitize_email($fields['email']);
        unset($fields['email']);
        $data["type"] = $settings["agir_registration_type"];

        $api_fields = [
            "first_name",
            "last_name",
            "contact_phone",
            "referrer",
            "mandat",
            "location_zip",
            "location_city",
            "location_country",
            "contact_phone",
            "gender",
            "date_of_birth",
            "media_preferences"
        ];

        $metadata = array();

        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $value = array_map("sanitize_text_field", $value);
            } else {
                $value = sanitize_text_field($value);
            }

            if (in_array($key, $api_fields)) {
                $data[$key] = $value;
            } else {
                $metadata[$key] = $value;
            }
        }

        if (count($metadata) > 0) {
            $data["metadata"] = $metadata;
        }

        $options = get_option('lfi_settings');

        $url = $options['api_server'] . '/api/people/subscription/';

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($options['api_id'] . ':' . $options['api_key']),
                'X-Wordpress-Client' => $_SERVER['REMOTE_ADDR']
            ],
            'body' => json_encode($data)
        ]);

        if (!is_wp_error($response) && ($response['response']['code'] === 400 || $response['response']['code'] === 422)) {
            $errors = json_decode($response["body"]);
            foreach ($errors as $field => $msg) {
                $ajax_handler->add_error($field, $msg);
            }
        }

        if (is_wp_error($response) || $response['response']['code'] !== 201) {
            $ajax_handler->add_error_message('Une erreur est survenue, veuillez réessayer plus tard.');
            return;
        }

        $redirect_to = json_decode($response['body'])->url;

        if ($settings["agir_registration_redirect"] && !empty($redirect_to) && filter_var($redirect_to, FILTER_VALIDATE_URL)) {
            $ajax_handler->add_response_data('redirect_url', $redirect_to);
        }
    }

    public function register_settings_section($widget)
    {
        $widget->start_controls_section('section_agir_registration', [
            'label' => 'Inscription à la plateforme',
            'condition' => [
                'submit_actions' => $this->get_name(),
            ],
        ]);

        $widget->add_control(
            'agir_registration_type',
            [
                'label' => "Type d'inscription",
                'type' => \Elementor\Controls_Manager::SELECT,
                'description' => 'Les champs pris en compte sont first_name, last_name, email, contact_phone, location_zip. L\'URL
            de redirection dépend du type d\'inscription.',
                'options' => [
                    'LFI' => "LFI",
                    'NSP' => "NSP",
                    'LJI' => "LJI",
                    'ISE' => 'ISE',
                    'EU24' => 'EU24',
                ],
                'default' => 'NSP'
            ]
        );

        $widget->add_control(
            'agir_registration_redirect',
            [
                'label' => 'Rediriger la personne vers une page externe après validation',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => "Oui",
                'label_off' => "Non",
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $widget->end_controls_section();
    }

    public function on_export($element)
    {
        unset($element['agir_registration_type']);
        unset($element['agir_registration_redirect']);
    }
}
