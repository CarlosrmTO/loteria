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
     * Test mode - set to true to use sample JSON data
     */
    const TEST_MODE = false;

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
        
        // DEBUG endpoint to see raw SELAE response
        register_rest_route('loteria-navidad/v5', '/debug/raw', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'debug_raw_handler'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * Debug handler to see raw SELAE API response
     */
    public static function debug_raw_handler($request) {
        $endpoint = 'https://www.loteriasyapuestas.es/servicios/premioDecimoProvisionalWeb';
        $response = wp_remote_get($endpoint, array(
            'timeout' => 15,
            'sslverify' => false,
            'headers' => array(
                'User-Agent' => 'selae_medio_TheObjective',
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'Referer' => 'https://theobjective.com/',
                'Cache-Control' => 'no-cache'
            )
        ));
        
        if (is_wp_error($response)) {
            return rest_ensure_response(array('error' => $response->get_error_message()));
        }
        
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body);
        
        return rest_ensure_response(array(
            'raw_body' => $body,
            'parsed' => $json,
            'compruebe_count' => isset($json->compruebe) ? count($json->compruebe) : 0,
            'has_cuartos' => isset($json->cuartosPremios),
            'has_quintos' => isset($json->quintosPremios)
        ));
    }

    /**
     * Proxy handler for API requests
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public static function proxy_handler($request) {
        // Handle AMP CORS
        self::send_amp_cors_headers();

        $type = $request['type'];
        $id = self::SORTEO_ID;
        $num = $request->get_param('num');

        // TEST MODE: Return sample data from Navidad 2024
        if (self::TEST_MODE && $type === 'premios') {
            return rest_ensure_response(self::get_test_data());
        }

        // Endpoints Oficiales
        $endpoints = array(
            'premios' => 'https://www.loteriasyapuestas.es/servicios/premioDecimoProvisionalWeb',
            'repartido' => 'https://www.loteriasyapuestas.es/servicios/repartidoEn1',
            'resultados' => 'https://www.loteriasyapuestas.es/servicios/resultados1?s=' . $id,
            // 'busqueda' => 'https://www.loteriasyapuestas.es/servicios/busquedaNumeros?sorteo=' . $id . '&numero=' . $num // BLOCKED
        );

        // FIX: Búsqueda local usando los datos de 'premios'
        if ($type === 'busqueda') {
            // Obtener datos de premios (usando la misma lógica de caché)
            $premios_request = new WP_REST_Request('GET', '/loteria-navidad/v5/datos/premios');
            $premios_response = self::proxy_handler($premios_request);
            
            if (is_wp_error($premios_response)) {
                return $premios_response;
            }
            
            $data = $premios_response->get_data();
            $target_int = intval($num); // Comparar como enteros para evitar problemas de '0' iniciales
            $numero_formatted = sprintf('%05d', $target_int);
            $premio = 0;
            $error = 0;

            // Buscar en 'compruebe'
            if (isset($data->compruebe) && is_array($data->compruebe)) {
                foreach ($data->compruebe as $item) {
                    // Comparación robusta usando enteros
                    if (intval($item->decimo) === $target_int) {
                        $premio = $item->prize;
                        break;
                    }
                }
            }
            
            // Construir respuesta formato SELAE
            $response_obj = (object) array(
                'numero' => $numero_formatted,
                'premio' => $premio,
                'timestamp' => time(),
                'status' => 0,
                'error' => 0
            );

            // Formatear premio
            $response_obj->premio_formatted = number_format($premio / 100, 0, ',', '.') . ' €';

            return rest_ensure_response($response_obj);
        }

        if (!isset($endpoints[$type])) {
            return new WP_Error('invalid', 'Tipo inválido', array('status' => 404));
        }

        // Cache
        $cache_key = 'loteria_v6_' . $type . '_' . $id . ($num ? '_' . $num : '');
        
        // FORCE CLEAR CACHE if ?clear_cache=1 is present
        if (isset($_GET['clear_cache']) && $_GET['clear_cache'] == '1') {
            delete_transient($cache_key);
            error_log("Loteria API: Cache cleared for $cache_key");
        }
        
        $cached = get_transient($cache_key);
        $json = null;

        if ($cached) {
            $json = json_decode($cached);
            error_log("Loteria API: Using cached data for $type");
        } else {
            error_log("Loteria API: Fetching fresh data for $type");
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
            
            // CRITICAL: Build main prizes from compruebe if missing
            if ($type === 'premios' && isset($json->compruebe) && is_array($json->compruebe)) {
                // Extract main prizes from compruebe array
                if (!isset($json->primerPremio)) {
                    foreach ($json->compruebe as $item) {
                        if (isset($item->prizeType) && $item->prizeType === 'G') {
                            $json->primerPremio = (object) array('decimo' => $item->decimo, 'prize' => $item->prize);
                            break;
                        }
                    }
                }
                if (!isset($json->segundoPremio)) {
                    foreach ($json->compruebe as $item) {
                        if (isset($item->prizeType) && $item->prizeType === 'Z') {
                            $json->segundoPremio = (object) array('decimo' => $item->decimo, 'prize' => $item->prize);
                            break;
                        }
                    }
                }
                if (!isset($json->tercerosPremios)) {
                    $json->tercerosPremios = array();
                    foreach ($json->compruebe as $item) {
                        if (isset($item->prizeType) && $item->prizeType === 'Y') {
                            $json->tercerosPremios[] = (object) array('decimo' => $item->decimo, 'prize' => $item->prize);
                        }
                    }
                }
            }

            // CRITICAL: Si es 'premios', también obtener cuartosPremios y quintosPremios de resultados1
            // ONLY if they don't exist in the main response
            if ($type === 'premios' && isset($endpoints['resultados'])) {
                $need_cuartos = !isset($json->cuartosPremios) || empty($json->cuartosPremios);
                $need_quintos = !isset($json->quintosPremios) || empty($json->quintosPremios);
                
                if ($need_cuartos || $need_quintos) {
                    $resultados_response = wp_remote_get($endpoints['resultados'], array(
                        'timeout' => 15,
                        'sslverify' => false,
                        'headers' => array(
                            'User-Agent' => 'selae_medio_TheObjective',
                            'Accept' => 'application/json, text/javascript, */*; q=0.01',
                            'Referer' => $referer,
                            'Cache-Control' => 'no-cache'
                        )
                    ));

                    if (!is_wp_error($resultados_response)) {
                        $resultados_body = wp_remote_retrieve_body($resultados_response);
                        $resultados_json = json_decode($resultados_body);
                        
                        if ($resultados_json) {
                            if ($need_cuartos && isset($resultados_json->cuartosPremios)) {
                                $json->cuartosPremios = $resultados_json->cuartosPremios;
                            }
                            if ($need_quintos && isset($resultados_json->quintosPremios)) {
                                $json->quintosPremios = $resultados_json->quintosPremios;
                            }
                        }
                    }
                }
            }

            // Normalize decimo format in all prize objects and arrays
            if (isset($json->primerPremio) && isset($json->primerPremio->decimo)) {
                $json->primerPremio->decimo = sprintf('%05d', intval($json->primerPremio->decimo));
            } else if (!isset($json->primerPremio)) {
                $json->primerPremio = (object) array('decimo' => '-----');
            }
            if (isset($json->segundoPremio) && isset($json->segundoPremio->decimo)) {
                $json->segundoPremio->decimo = sprintf('%05d', intval($json->segundoPremio->decimo));
            } else if (!isset($json->segundoPremio)) {
                $json->segundoPremio = (object) array('decimo' => '-----');
            }
            if (isset($json->tercerosPremios) && is_array($json->tercerosPremios)) {
                foreach ($json->tercerosPremios as $item) {
                    if (isset($item->decimo)) {
                        $item->decimo = sprintf('%05d', intval($item->decimo));
                    }
                }
            } else if (!isset($json->tercerosPremios)) {
                $json->tercerosPremios = array((object) array('decimo' => '-----'));
            }
            if (isset($json->compruebe) && is_array($json->compruebe)) {
                foreach ($json->compruebe as $item) {
                    if (isset($item->decimo)) {
                        $item->decimo = sprintf('%05d', intval($item->decimo));
                    }
                }
            }
            if (isset($json->cuartosPremios) && is_array($json->cuartosPremios)) {
                $filtered_cuartos = array();
                foreach ($json->cuartosPremios as $item) {
                    if (isset($item->decimo) && $item->decimo !== null) {
                        $item->decimo = sprintf('%05d', intval($item->decimo));
                        $filtered_cuartos[] = $item;
                    }
                }
                // Ensure we always have 2 items, fill with placeholders
                while (count($filtered_cuartos) < 2) {
                    $filtered_cuartos[] = (object) array('decimo' => '-----');
                }
                $json->cuartosPremios = $filtered_cuartos;
            }
            if (isset($json->quintosPremios) && is_array($json->quintosPremios)) {
                $filtered_quintos = array();
                foreach ($json->quintosPremios as $item) {
                    if (isset($item->decimo) && $item->decimo !== null) {
                        $item->decimo = sprintf('%05d', intval($item->decimo));
                        $filtered_quintos[] = $item;
                    }
                }
                // Ensure we always have 8 items, fill with placeholders
                while (count($filtered_quintos) < 8) {
                    $filtered_quintos[] = (object) array('decimo' => '-----');
                }
                $json->quintosPremios = $filtered_quintos;
            }

            set_transient($cache_key, json_encode($json), 60); // Cache 60s
        }

        // Post-process for AMP (add formatted fields)
        if ($type === 'busqueda' && isset($json->premio)) {
            // SELAE returns prize in cents
            $json->premio_formatted = number_format($json->premio / 100, 0, ',', '.') . ' €';
        }

        // Handle empty/pending state for AMP
        // FIX: Consider draw started if we have 'compruebe' data, even if 'primerPremio' is missing
        $has_started = isset($json->primerPremio) || (isset($json->compruebe) && !empty($json->compruebe));
        
        if ($type === 'premios' && (empty($json) || !$has_started)) {
            if (!is_object($json)) {
                $json = new stdClass();
            }
            $json->pending = true;
            $json->message = 'Sorteo no iniciado';
        }

        return rest_ensure_response($json);
    }

    /**
     * Get test data (sample from Navidad 2024)
     * 
     * @return object
     */
    private static function get_test_data() {
        // Datos de ejemplo basados en Navidad 2024
        return (object) array(
            'tipoSorteo' => 'N',
            'fechaSorteo' => '2024-12-22T08:30:00.000Z',
            'drawIdSorteo' => '1259409102',
            'primerPremio' => (object) array('decimo' => '72480', 'prize' => 40000000),
            'segundoPremio' => (object) array('decimo' => '40014', 'prize' => 12500000),
            'tercerosPremios' => array(
                (object) array('decimo' => '11840', 'prize' => 5000000)
            ),
            'cuartosPremios' => array(
                (object) array('decimo' => '77768', 'prize' => 2000000),
                (object) array('decimo' => '19371', 'prize' => 2000000)
            ),
            'quintosPremios' => array(
                (object) array('decimo' => '25196', 'prize' => 600000),
                (object) array('decimo' => '31669', 'prize' => 600000),
                (object) array('decimo' => '52472', 'prize' => 600000),
                (object) array('decimo' => '55483', 'prize' => 600000),
                (object) array('decimo' => '57033', 'prize' => 600000),
                (object) array('decimo' => '67856', 'prize' => 600000),
                (object) array('decimo' => '71486', 'prize' => 600000),
                (object) array('decimo' => '78190', 'prize' => 600000)
            ),
            'listadoPremiosAsociados' => (object) array(
                'aproximacion1' => (object) array('prize' => 200000),
                'aproximacion2' => (object) array('prize' => 125000),
                'centena1' => (object) array('prize' => 10000),
                'centena2' => (object) array('prize' => 10000),
                'reintegro_gordo' => (object) array('prize' => 2000)
            ),
            'compruebe' => array(
                // Premios principales
                (object) array('decimo' => '72480', 'prize' => 40000000, 'prizeType' => 'G'),
                (object) array('decimo' => '40014', 'prize' => 12500000, 'prizeType' => 'Z'),
                (object) array('decimo' => '11840', 'prize' => 5000000, 'prizeType' => 'Y'),
                (object) array('decimo' => '77768', 'prize' => 2000000, 'prizeType' => 'X'),
                (object) array('decimo' => '19371', 'prize' => 2000000, 'prizeType' => 'X'),
                (object) array('decimo' => '25196', 'prize' => 600000, 'prizeType' => 'W'),
                (object) array('decimo' => '31669', 'prize' => 600000, 'prizeType' => 'W'),
                (object) array('decimo' => '52472', 'prize' => 600000, 'prizeType' => 'W'),
                (object) array('decimo' => '55483', 'prize' => 600000, 'prizeType' => 'W'),
                (object) array('decimo' => '57033', 'prize' => 600000, 'prizeType' => 'W'),
                (object) array('decimo' => '67856', 'prize' => 600000, 'prizeType' => 'W'),
                (object) array('decimo' => '71486', 'prize' => 600000, 'prizeType' => 'W'),
                (object) array('decimo' => '78190', 'prize' => 600000, 'prizeType' => 'W'),
                // Pedrea (muestra)
                (object) array('decimo' => '00015', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '00127', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '00234', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '00456', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '00789', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '01234', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '02345', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '03456', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '04567', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '05678', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '10001', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '15432', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '20123', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '25000', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '30456', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '35789', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '40123', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '45678', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '50001', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '55555', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '60234', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '65432', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '70001', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '75000', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '80123', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '85678', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '90001', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '95432', 'prize' => 100000, 'prizeType' => 'P'),
                (object) array('decimo' => '99999', 'prize' => 100000, 'prizeType' => 'P')
            )
        );
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

    /**
     * Send CORS headers for AMP
     */
    private static function send_amp_cors_headers() {
        // Allow requests from AMP caches
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        $amp_source_origin = isset($_GET['__amp_source_origin']) ? $_GET['__amp_source_origin'] : '';
        
        // If Origin is missing (same-origin), use the current host
        if (empty($origin)) {
            $protocol = is_ssl() ? 'https://' : 'http://';
            $origin = $protocol . $_SERVER['HTTP_HOST'];
        }
        
        if (!empty($amp_source_origin)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Expose-Headers: AMP-Access-Control-Allow-Source-Origin');
            header('AMP-Access-Control-Allow-Source-Origin: ' . $amp_source_origin);
        }
    }
}
