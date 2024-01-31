<?php

namespace LFI\WPPlugins\DjanStats;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Admin
{
    private $dummykey = "somethingveryveryverylonglongenoughtofillfield";

    protected function write_log($log)
    {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

    public function __construct()
    {
        // When initialized
        add_action('admin_init', [$this, 'settings_init']);
        // When menu load
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function add_admin_menu()
    {
        add_options_page(
            'LFI | Paramètres API Djan',
            'LFI | Paramètres API Djan',
            'manage_options',
            'lfi-djan-stats',
            [$this, 'options_page']
        );
    }

    public function options_page()
    {
?>
        <h1>Paramètres de connexion à l'API Djan</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('djan_settings_page');
            do_settings_sections('djan_settings_page');
            submit_button("Vérifier les identifiants"); ?>
        </form>
    <?php

    }

    public function settings_init()
    {
        register_setting('djan_settings_page', 'djan_settings', [$this, 'sanitize']);

        add_settings_section(
            'djan_credentials_section',
            '',
            [$this, 'credentials_section_callback'],
            'djan_settings_page'
        );
    }

    public function sanitize($data)
    {
        $old = get_option('djan_settings');

        if ($data['api_token'] == $this->dummykey) {
            $data['api_token'] = $old['api_token'];
        }

        $data['api_success'] = $this->check_credentials($data['api_server'], $data['api_token']);

        return $data;
    }

    public function credentials_section_callback()
    {
        add_settings_field(
            'djan_api_server',
            'Serveur Djan',
            [$this, 'api_server_render'],
            'djan_settings_page',
            'djan_credentials_section'
        );

        add_settings_field(
            'djan_api_stats_endpoint',
            'Endpoint pour retrouver les statistiques',
            [$this, 'api_stats_endpoint_render'],
            'djan_settings_page',
            'djan_credentials_section'
        );

        add_settings_field(
            'djan_api_token',
            'Token de connexion',
            [$this, 'api_token_render'],
            'djan_settings_page',
            'djan_credentials_section'
        );
    }


    public function api_server_render()
    {
        $options = get_option('djan_settings');
        ?><input type="text" name="djan_settings[api_server]" value="<?= isset($options['api_server']) ? esc_attr($options['api_server']) : ''; ?>"><?php
    }

    public function api_stats_endpoint_render()
    {
        $options = get_option('djan_settings');
        ?><input type="text" name="djan_settings[api_stats_endpoint]" value="<?= isset($options['api_stats_endpoint']) ? esc_attr($options['api_stats_endpoint']) : ''; ?>"><?php
    }

    public function api_token_render()
    {
        $options = get_option('djan_settings');
        ?><input type="password" name="djan_settings[api_token]" value="<?= empty($options["api_token"]) ? "" : $this->dummykey ?>"><?php

        if (isset($options["api_success"]) && $options["api_success"] === true) {
            ?><p style="color: green;">API connectée</p><?php
        }

        if (isset($options["api_success"]) && $options["api_success"] === false) {
            ?><p style="color: red;">L'authentification a échoué</p><?php
        }
    }

    private function check_credentials($domain, $token)
    {
        try {
            $url = $domain . "/api/status";

            $response = wp_remote_get($url, [
                'timeout' => 300,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]);

            if (isset($error) || is_wp_error($response)) {
                return false;
            }

            if (in_array($response['response']['code'], [401, 403])) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
