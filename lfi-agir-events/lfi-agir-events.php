<?php

/**
 * Plugin Name: Événements Action populaire
 * Description: Widget Elementor pour récuperer et afficher une liste d'événements Action populaire
 * Plugin URI:  https://github.com/lafranceinsoumise/wordpress-plugins
 * Version:     1.0.0
 * Author:      Giuseppe De Ponte
 * Author URI:  https://github.com/giuseppedeponte/
 * Text Domain: lfi-agir-events
 *
 * Elementor tested up to: 3.8.0
 * Elementor Pro tested up to: 3.8.0
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

/**
 * Shortcut constant to the path of this file.
 */
define('LFI_AGIR_EVENTS_DIR__', plugin_dir_path(__FILE__));


function register_rest_routes()
{
  require_once(LFI_AGIR_EVENTS_DIR__ . 'api/lfi-agir-events.php');

  register_rest_route('lfi-agir-events', '/groups', [
    'methods' => 'GET',
    'callback' => 'search_groups',
  ]);
}

function register_widgets($widgets_manager)
{
  require_once(LFI_AGIR_EVENTS_DIR__ . 'widgets/lfi-agir-events.php');
  $widgets_manager->register(new \LFIAgirEvents_Elementor_List_Widget());
}

add_action('rest_api_init', 'register_rest_routes');
add_action('elementor/widgets/register', 'register_widgets');
