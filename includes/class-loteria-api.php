<?php
/**
 * API Proxy Class
 *
 * @package Loteria_Navidad
 * @since 7.9
 */

if (!defined('ABSPATH')) exit;

class Loteria_API {

    /**
     * Sorteo ID
     */
    const SORTEO_ID = '1295909102';

    /**
     * Initialize the class
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public static function register_routes() {
        register_rest_route('loteria-navidad/v5', '/datos/(?P<type>[a-zA-Z0-9-]+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'proxy_handler'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * Proxy handler for API requests
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function proxy_handler($request) {
        $type = $request['type'];
        $id = self::SORTEO_ID;
        $num = $request->get_param('num');

        // Endpoints Oficiales
        $endpoints = array(
            'premios' => 'https://www.loteriasyapuestas.es/servicios/premioDecimoProvisionalWeb',
            'repartido' => 'https://www.loteriasyapuestas.es/servicios/repartidoEn1',
            'busqueda' => 'https://www.loteriasyapuestas.es/servicios/busquedaNumeros?sorteo=' . $id . '&numero=' . $num
        );

        if (!isset($endpoints[$type])) {
            return new WP_Error('invalid', 'Tipo inválido', array('status' => 404));
        }

        // Cache
        $cache_key = 'loteria_v6_' . $type . '_' . $id . ($num ? '_' . $num : '');
        $cached = get_transient($cache_key);
        if ($cached) {
            return rest_ensure_response(json_decode($cached));
        }

        $referer = 'https://theobjective.com/';
        $response = wp_remote_get($endpoints[$type], array(
            'timeout' => 15,
            'sslverify' => false,
            'headers' => array(
                'User-Agent' => 'selae_medio_TheObjective',
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'Referer' => $referer,
                'Cache-Control' => 'no-cache'
            )
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message(), array('status' => 500));
        }

        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);

        // Validación JSON
        $json = json_decode($body);
        if ($json === null) {
            error_log("Loteria V6: Respuesta no JSON de SELAE (Code $code).");
            return rest_ensure_response(new stdClass());
        }

        set_transient($cache_key, $body, 60); // Cache 60s
        return rest_ensure_response($json);
    }

    /**
     * Get API URL for a specific type
     *
     * @param string $type
     * @return string
     */
    public static function get_api_url($type) {
        return get_rest_url(null, 'loteria-navidad/v5/datos/' . $type);
    }
}
