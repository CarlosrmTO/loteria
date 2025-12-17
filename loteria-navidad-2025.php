<?php
/**
 * Plugin Name: Loteria Navidad 2025
 * Plugin URI: https://theobjective.com
 * Description: Widgets de lotería nativos (Gutenberg + Shortcodes) con lógica avanzada de comprobación.
 * Version: 8.0
 * Author: CR
 * Author URI: https://theobjective.com
 * Text Domain: loteria-navidad
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package Loteria_Navidad
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('LOTERIA_NAVIDAD_VERSION', '8.0');
define('LOTERIA_NAVIDAD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LOTERIA_NAVIDAD_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Plugin Class
 */
class Loteria_Navidad_Plugin {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
        $this->init_components();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'flush_rewrite_rules_once'));
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once LOTERIA_NAVIDAD_PLUGIN_DIR . 'includes/class-loteria-api.php';
        require_once LOTERIA_NAVIDAD_PLUGIN_DIR . 'includes/class-loteria-assets.php';
        require_once LOTERIA_NAVIDAD_PLUGIN_DIR . 'includes/class-loteria-render.php';
        require_once LOTERIA_NAVIDAD_PLUGIN_DIR . 'includes/class-loteria-shortcodes.php';
        require_once LOTERIA_NAVIDAD_PLUGIN_DIR . 'includes/class-loteria-blocks.php';
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        Loteria_API::init();
        Loteria_Assets::init();
        Loteria_Shortcodes::init();
        Loteria_Blocks::init();
    }

    /**
     * Flush rewrite rules once on version update
     */
    public function flush_rewrite_rules_once() {
        $option_name = 'loteria_navidad_v' . str_replace('.', '_', LOTERIA_NAVIDAD_VERSION) . '_flushed';
        if (!get_option($option_name)) {
            flush_rewrite_rules();
            update_option($option_name, true);
        }
    }
}

/**
 * Initialize the plugin
 */
function loteria_navidad_init() {
    return Loteria_Navidad_Plugin::get_instance();
}

// Start the plugin
loteria_navidad_init();
