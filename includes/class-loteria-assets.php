<?php
/**
 * Asset Management Class
 *
 * @package Loteria_Navidad
 * @since 7.9
 */

if (!defined('ABSPATH')) exit;

class Loteria_Assets {

    /**
     * Plugin version
     */
    const VERSION = '7.9';

    /**
     * Initialize the class
     */
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_frontend_assets'));
        add_action('enqueue_block_editor_assets', array(__CLASS__, 'enqueue_editor_assets'));
    }

    /**
     * Enqueue frontend assets
     */
    public static function enqueue_frontend_assets() {
        // CSS
        wp_enqueue_style(
            'loteria-navidad-styles',
            plugins_url('assets/css/loteria.css', dirname(__FILE__)),
            array(),
            self::VERSION
        );

        // JS Logic
        wp_enqueue_script(
            'loteria-navidad-front',
            plugins_url('assets/js/loteria-front.js', dirname(__FILE__)),
            array(),
            self::VERSION,
            true
        );
    }

    /**
     * Enqueue block editor assets
     */
    public static function enqueue_editor_assets() {
        // CSS - Same styles for editor preview
        wp_enqueue_style(
            'loteria-navidad-editor-styles',
            plugins_url('assets/css/loteria.css', dirname(__FILE__)),
            array(),
            self::VERSION
        );

        // JS - Frontend logic for live preview in editor
        wp_enqueue_script(
            'loteria-navidad-editor-front',
            plugins_url('assets/js/loteria-front.js', dirname(__FILE__)),
            array('wp-blocks', 'wp-element', 'wp-data', 'wp-block-editor'),
            self::VERSION,
            true
        );
    }
}
