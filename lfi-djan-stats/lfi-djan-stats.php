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
    const CAGNOTTE_DEFAULT_EXPIRATION = 10;

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', [$this, 'admin_init']);
        add_action('lfi_djan_stats_refresh', [$this, 'djan_stats_refresh']);
        add_shortcode('lfi_djan_stats', [$this, 'lfi_djan_stats_shortcode_handler']);
    }

    public function admin_init()
    {
        require_once dirname(__FILE__) . '/includes/admin.php';

        new Admin();
    }

    function lfi_djan_stats_shortcode_handler($attrs, $content, $tag)
    {
        $attrs = shortcode_attrs(
            array(
                'short_url' => NULL,
                'minutes' => NULL,
                'unique' => true
            ),
            $attrs
        );

        $short_url = $attrs['short_url'];
        $unique = (bool) $attrs['unique'];

        if (is_null($short_url)) {
            return 0;
        }

        $minutes = floatval($attrs['minutes']);
        if ($minutes === 0.) {
            $minutes = self::CAGNOTTE_DEFAULT_EXPIRATION;
        }

        $expiration = intval($minutes * 60);
        list($valeur, $valide) = $this->get_cached_value($short_url, $expiration, $unique);

        if (!$valide && !wp_next_scheduled(
            'lfi_djan_stats_refresh',
            [$short_url, $unique]
        )) {
            wp_schedule_single_event(
                time(),
                'lfi_djan_stats_refresh',
                [$short_url, $unique],
                true
            );
        }

        return strval($valeur);
    }

    function get_cache_key($short_url, $unique)
    {
        return $unique ? "djan-stats-$short_url-unique" : "djan-stats-$short_url";
    }

    function get_cached_value($short_url, $expiration, $unique)
    {
        $cache_key = $this->get_cache_key($short_url, $unique);
        $cached_value = wp_cache_get($cache_key);
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

    function djan_stats_refresh($short_url, $unique)
    {
        $valeur = $this->get_real_value($short_url, $unique);

        if (is_null($valeur)) {
            return NULL;
        }

        $timestamp = time();
        $cache_key = $this->get_cache_key($short_url, $unique);
        $cached_value = strval($valeur) . ':' . strval($timestamp);

        wp_cache_set(
            $cache_key,
            $cached_value,
            self::CACHE_GROUP,
            0  // cacher sans limite pour pouvoir afficher une valeur même périmée
        );

        return $valeur;
    }

    function get_real_value($short_url, $unique)
    {
        $options = get_option('djan_settings');
        $url = $options['api_server'] . $options['api_stats_endpoint'] . "/$short_url";

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

        if (!is_null($json_body)) {
            return NULL;
        }

        if ($unique && !array_key_exists('unique_counter', $json_body)) {
            return $json_body["unique_counter"];
        }

        if (!$unique && !array_key_exists('counter', $json_body)) {
            return $json_body["counter"];
        }

        return NULL;
    }
}

new Plugin();
