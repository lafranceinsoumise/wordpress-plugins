<?php
/*
Plugin Name: LFI Inscription plateforme
Description: Gère l'inscription sur la plateforme
Version: 1.0
Author: Jill Maud Royer
Author: Giuseppe De Ponte
Author: Salomé Cheysson
License: GPL3
*/

namespace LFI\WPPlugins\AgirRegistration;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Plugin
{
    const CACHE_GROUP = "lfi-agir-registration";
    const CAGNOTTE_DEFAULT_EXPIRATION = 10;

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'admin_init']);
        add_action('elementor_pro/init', [$this, 'register_elementor_addons']);
        add_action('wp_enqueue_scripts', [$this, 'cookie_script']);
        add_action('lfi_agir_registration_cagnotte_rafraichir', [$this, 'cagnotte_rafraichir']);
        add_shortcode('agir_signatures', [$this, 'signature_shortcode_handler']);
        add_shortcode('agir_cagnotte', [$this, 'cagnotte_shortcode_handler']);
    }

    public function admin_init()
    {
        require_once dirname(__FILE__).'/includes/admin.php';

        new Admin();
    }

    public function register_elementor_addons()
    {
        require_once dirname(__FILE__).'/includes/registration-handler.php';
        require_once dirname(__FILE__).'/includes/update-newsletters-handler.php';

        $elementor_registration_action = new RegistrationAction();
        \ElementorPro\Plugin::instance()
            ->modules_manager->get_modules('forms')
            ->add_form_action($elementor_registration_action->get_name(), $elementor_registration_action)
        ;

        $elementor_newsletter_action = new UpdateNewslettersAction();
        \ElementorPro\Plugin::instance()
            ->modules_manager->get_modules('forms')
            ->add_form_action($elementor_newsletter_action->get_name(), $elementor_newsletter_action)
        ;
    }

    function cookie_script()
    {
        wp_enqueue_script('js-cookie', plugin_dir_url( __FILE__ ).'/scripts/js-cookie.js', [], 2);
        wp_enqueue_script('lfi-polyfills', plugin_dir_url( __FILE__ ).'/scripts/polyfills.js', [], 2);
        wp_enqueue_script('lfi-agir-cookie', plugin_dir_url( __FILE__ ).'/scripts/cookie.js', ['lfi-polyfills', 'js-cookie'], 3);
    }

    function signature_shortcode_handler($atts, $content, $tag)
    {
        if (!is_array($atts) || !isset($atts["type"]) || !in_array($atts["type"], ["nsp", "lfi"])) {
            return "";
        }

        $transient_key = 'agir_signature_'.$atts["type"];

        $count = get_transient($transient_key);

        if ($count !== false) {
            return $count;
        }

        $options = get_option('lfi_settings');

        $url = $options['api_server'].'/api/people/counter/';
        $query = ['type' => $atts['type']];

        $response = wp_remote_get($url.'?'.http_build_query($query), [
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Basic '.base64_encode($options['api_id'].':'.$options['api_key']),
                'X-Wordpress-Client' => $_SERVER['REMOTE_ADDR']
            ]
        ]);

        if (is_wp_error($response) || $response['response']['code'] !== 200) {
            return get_option("lfi_counter_stale");
        }

        $count  = json_decode($response["body"])->value;
        set_transient($transient_key, $count, 30);
        update_option("lfi_counter_stale", $count, false);

        return $count;
    }

    function cagnotte_shortcode_handler($atts, $content, $tag)
    {
        $atts = shortcode_atts(
            array(
                'slug' => NULL,
                'minutes' => NULL
            ), $atts
        );

        $slug = $atts['slug'];

        if(is_null($slug) ) {
            return "";
        }

        $minutes = floatval($atts['minutes']);
        if ($minutes === 0.) {
            $minutes = self::CAGNOTTE_DEFAULT_EXPIRATION;
        }

        $expiration = intval($minutes * 60);
        list($valeur, $valide) = $this->cagnotte_recuperer_valeur_cache($slug, $expiration);

        if (!$valide && !wp_next_scheduled(
            'lfi_agir_registration_cagnotte_rafraichir', [$slug]
        )) {
            wp_schedule_single_event(
                time(),
                'lfi_agir_registration_cagnotte_rafraichir',
                [$slug],
                true
            );
        }

        return strval($valeur);
    }

    function cagnotte_recuperer_valeur_cache($slug, $expiration) {
        $cached_value = wp_cache_get("cagnotte-$slug", self::CACHE_GROUP);
        $valeur = 0;
        $timestamp = 0;
        $now = time();

        if ($cached_value) {
            $elements = explode(':', $cached_value);
            $valeur = intval($elements[0]);
            if (count($elements) > 1) {
                $timestamp = intval($elements[1]);
            }
        }

        if (!$valeur || $now > $timestamp + $expiration) {
            return array($valeur, false);
        }

        return array($valeur, true);
    }

    function cagnotte_rafraichir($slug) {
        $valeur = $this->cagnotte_recuperer_valeur_actuelle($slug);

        if (is_null($valeur)) {
            return NULL;
        }

        $timestamp = time();
        $cached_value = strval($valeur) . ':' . strval($timestamp);

        wp_cache_set(
            "cagnotte-$slug",
            $cached_value,
            self::CACHE_GROUP,
            0  // cacher sans limite pour pouvoir afficher une valeur même périmée
        );

        return $valeur;
    }

    function cagnotte_recuperer_valeur_actuelle($slug) {
        $options = get_option('lfi_settings');
        $url = $options['api_server'] . "/cagnottes/$slug/compteur/";

        $response = wp_remote_get($url, [
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($options['api_id'] . ':' . $options['api_key']),
                'X-Wordpress-Client' => $_SERVER['REMOTE_ADDR']
            ]
        ]);

        if (is_wp_error($response) || $response['response']['code'] !== 200) {
            return NULL;
        }

        $body = wp_remote_retrieve_body($response);
        $json_body = json_decode($body, true);

        if (is_null($json_body) || !array_key_exists('totalAmount', $json_body)) {
            return NULL;
        }

        return $json_body['totalAmount'];
    }
}

new Plugin();
