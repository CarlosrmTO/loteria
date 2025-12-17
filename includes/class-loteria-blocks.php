<?php
/**
 * Gutenberg Blocks Class
 *
 * @package Loteria_Navidad
 * @since 7.9
 */

if (!defined('ABSPATH')) exit;

class Loteria_Blocks {

    /**
     * Initialize the class
     */
    public static function init() {
        add_filter('block_categories_all', array(__CLASS__, 'register_category'), 99, 1);
        add_filter('allowed_block_types_all', array(__CLASS__, 'allow_blocks'), 99, 2);
        add_action('init', array(__CLASS__, 'register_blocks'));
    }

    /**
     * Register custom block category
     */
    public static function register_category($categories) {
        return array_merge(
            array(
                array(
                    'slug'  => 'loteria',
                    'title' => 'Lotería Navidad',
                    'icon'  => 'tickets-alt',
                ),
            ),
            $categories
        );
    }

    /**
     * Ensure loteria blocks are always allowed
     */
    public static function allow_blocks($allowed_blocks, $editor_context) {
        // Bloques de lotería que siempre deben estar disponibles
        $loteria_blocks = array(
            'loteria/horizontal',
            'loteria/comprobador',
            'loteria/pedrea',
            'loteria/premios'
        );

        // Si no hay restricciones (todos los bloques permitidos), no hacer nada
        if ($allowed_blocks === true) {
            return $allowed_blocks;
        }

        // Si hay un array de bloques permitidos, añadir los nuestros
        if (is_array($allowed_blocks)) {
            return array_merge($allowed_blocks, $loteria_blocks);
        }

        // Si está vacío o false, solo permitir nuestros bloques
        return $loteria_blocks;
    }

    /**
     * Register all blocks
     */
    public static function register_blocks() {
        $blocks_dir = dirname(dirname(__FILE__)) . '/blocks/';

        // Registrar cada bloque con su render callback
        register_block_type($blocks_dir . 'horizontal', array(
            'render_callback' => array('Loteria_Render', 'horizontal')
        ));

        register_block_type($blocks_dir . 'comprobador', array(
            'render_callback' => array('Loteria_Render', 'comprobador')
        ));

        register_block_type($blocks_dir . 'pedrea', array(
            'render_callback' => array('Loteria_Render', 'pedrea')
        ));

        register_block_type($blocks_dir . 'premios', array(
            'render_callback' => array('Loteria_Render', 'premios')
        ));
    }
}
