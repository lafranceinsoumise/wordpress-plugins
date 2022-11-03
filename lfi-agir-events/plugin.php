<?php

namespace LFI\WPPlugins\AgirEvents;

define('LFI_AGIR_EVENTS_DIR__', plugin_dir_path(__FILE__));

/**
 * Class Plugin
 *
 * Main Plugin class
 * @since 1.0.0
 */
class Plugin
{
    /**
     * Instance
     *
     * @since 1.0.0
     * @access private
     * @static
     *
     * @var Plugin The single instance of the class.
     */
    private static $_instance = null;

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @since 1.0.0
     * @access public
     *
     * @return Plugin An instance of the class.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * Register API routes
     *
     * Register new Wordpress API routes.
     *
     * @since 1.0.0
     * @access public
     * 
     */
    public function register_rest_routes()
    {
        require_once(LFI_AGIR_EVENTS_DIR__ . 'api/lfi-agir-events.php');

        register_rest_route('lfi-agir-events', '/groups', [
            'methods' => 'GET',
            'callback' => __NAMESPACE__ . '\\API\search_groups',
        ]);
    }

    /**
     * Register Widgets
     *
     * Register new Elementor widgets.
     *
     * @since 1.0.0
     * @access public
     *
     * @param Widgets_Manager $widgets_manager Elementor widgets manager.
     */
    public function register_widgets($widgets_manager)
    {
        // Its is now safe to include Widgets files
        require_once(LFI_AGIR_EVENTS_DIR__ . 'widgets/lfi-agir-events.php');

        // Register Widgets
        $widgets_manager->register(new Widgets\LFIAgirEvents_Widget());
    }

    /**
     *  Plugin class constructor
     *
     * Register plugin action hooks and filters
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct()
    {
        // Register API routes
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        // Register widgets
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
    }
}

// Instantiate Plugin Class
Plugin::instance();
