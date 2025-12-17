<?php
/**
 * Shortcodes Class
 *
 * @package Loteria_Navidad
 * @since 7.9
 */

if (!defined('ABSPATH')) exit;

class Loteria_Shortcodes {

    /**
     * Initialize the class
     */
    public static function init() {
        add_shortcode('loteria_premios', array(__CLASS__, 'premios'));
        add_shortcode('loteria_comprobador', array(__CLASS__, 'comprobador'));
        add_shortcode('loteria_pedrea', array(__CLASS__, 'pedrea'));
        add_shortcode('loteria_premios_horizontal', array(__CLASS__, 'horizontal'));
    }

    /**
     * Premios shortcode
     */
    public static function premios($atts) {
        return Loteria_Render::premios();
    }

    /**
     * Comprobador shortcode
     */
    public static function comprobador($atts) {
        return Loteria_Render::comprobador();
    }

    /**
     * Pedrea shortcode
     */
    public static function pedrea($atts) {
        return Loteria_Render::pedrea();
    }

    /**
     * Horizontal shortcode
     */
    public static function horizontal($atts) {
        return Loteria_Render::horizontal();
    }
}
