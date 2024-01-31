<?php
/*
Plugin Name: LFI Djan Stats
Description: Ajoute un shortcode pour récuperer les statistiques d'un URL court djan
Version: 0.1
Author: Giuseppe de Ponte, Salomé Cheysson
License: GPL3
*/

namespace LFI\WPPlugins\DjanStats;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Plugin
{
    const CACHE_GROUP = "lfi-djan-stats";
    const CACHE_DEFAULT_EXPIRATION = 10; // 10 minutes

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

    protected function log($message, $shouldNotDie = true)
    {
        error_log(print_r($message, true));
        if ($shouldNotDie) {
            exit;
        }
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'admin_init']);
        add_action('lfi_djan_stats_refresh', [$this, 'djan_stats_refresh'], 10, 2);
        add_shortcode('lfi_djan_stats', [$this, 'lfi_djan_stats_shortcode_handler']);
    }

    public function admin_init()
    {
        require_once dirname(__FILE__) . '/includes/admin.php';

        new Admin();
    }

    function lfi_djan_stats_shortcode_handler($atts, $content, $tag)
    {
        $atts = shortcode_atts(
            array(
                'path' => NULL,
                'exp_min' => self::CACHE_DEFAULT_EXPIRATION,
                'unique' => false
            ),
            $atts
        );

        $path = $atts['path'];
        if (is_null($path)) {
            return NULL;
        }

        $unique = filter_var($atts['unique'], FILTER_VALIDATE_BOOLEAN);
        $exp_min = intval($atts['exp_min']);

        return $this->get_cached_value($path, $unique, $exp_min);
    }

    function get_cache_key($path, $unique)
    {
        $url = sanitize_title($path);

        return $unique ? "djan-stats-$url-unique" : "djan-stats-$url";
    }

    function get_cached_value($path, $unique, $exp_min)
    {
        $cache_key = $this->get_cache_key($path, $unique);
        $cached_value = wp_cache_get($cache_key, self::CACHE_GROUP,);
        $elements = $cached_value ? explode(':', $cached_value) : [0];
        $now = time();
        $timestamp = count($elements) > 1 ? intval($elements[1]) : 0;
        $valeur = intval($elements[0]);

        $expired = !$valeur || $now > $timestamp + ($exp_min * 60);

        if ($expired && !wp_next_scheduled('lfi_djan_stats_refresh', [$path, $unique])) {
            wp_schedule_single_event(
                time(),
                'lfi_djan_stats_refresh',
                [$path, $unique],
                true
            );
        }

        return $valeur;
    }

    function djan_stats_refresh($path, $unique)
    {
        $valeur = $this->get_real_value($path, $unique);

        if (is_null($valeur)) {
            return NULL;
        }

        $timestamp = time();
        $cache_key = $this->get_cache_key($path, $unique);
        $cached_value = strval($valeur) . ':' . strval($timestamp);

        wp_cache_set(
            $cache_key,
            $cached_value,
            self::CACHE_GROUP,
            0  // cacher sans limite pour pouvoir afficher une valeur même périmée
        );

        return $valeur;
    }

    function get_real_value($path, $unique)
    {
        $options = get_option('djan_settings');
        $url = $options['api_server'] . "/" . $options['api_stats_endpoint'] . "/$path";
        $url = preg_replace('#(?<!:)/+#im', '/', $url);

        $response = wp_remote_get($url, [
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer ' . $options['api_token'],
                'X-Wordpress-Client' => $_SERVER['REMOTE_ADDR']
            ]
        ]);

        if (is_wp_error($response) || $response['response']['code'] !== 200) {
            return NULL;
        }

        $body = wp_remote_retrieve_body($response);
        $json_body = json_decode($body, true);

        if (!is_null($json_body) && $unique && array_key_exists('unique_counter', $json_body)) {
            return $json_body["unique_counter"];
        }

        if (!is_null($json_body) && !$unique && array_key_exists('counter', $json_body)) {
            return $json_body["counter"];
        }

        return NULL;
    }
}

new Plugin();
