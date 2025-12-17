<?php
/**
 * Plugin Name: Loteria Navidad 2025 - V6 (Native Blocks)
 * Description: Widgets de lotería nativos (Gutenberg + Shortcodes) con lógica avanzada de comprobación.
 * Version: 7.7
 * Author: CR
 */

if (!defined('ABSPATH')) exit;

// AUTO-FLUSH PERMALINKS ON UPDATE
add_action('init', function() {
    if (!get_option('loteria_navidad_v7_7_flushed')) {
        flush_rewrite_rules();
        update_option('loteria_navidad_v7_7_flushed', true);
    }
});

// 1. API PROXY SETUP
if (!defined('LOTERIA_ID_SORTEO_V5')) {
    define('LOTERIA_ID_SORTEO_V5', '1295909102');
}

// Enqueue Styles & Scripts
add_action('wp_enqueue_scripts', function() {
    // CSS
    wp_enqueue_style('loteria-navidad-styles', plugins_url('loteria.css', __FILE__), [], '7.7');
    
    // JS Logic (Consolidated)
    wp_enqueue_script('loteria-navidad-front', plugins_url('loteria-front.js', __FILE__), [], '7.7', true);
});

add_action('rest_api_init', function () {
    register_rest_route('loteria-navidad/v5', '/datos/(?P<type>[a-zA-Z0-9-]+)', array(
        'methods' => 'GET',
        'callback' => 'loteria_navidad_proxy_handler_v5',
        'permission_callback' => '__return_true'
    ));
});

function loteria_navidad_proxy_handler_v5($request) {
    $type = $request['type'];
    $id = LOTERIA_ID_SORTEO_V5;
    $num = $request->get_param('num'); 
    
    // Endpoints Oficiales (Sin parámetros de sorteo para 'premioDecimoProvisionalWeb')
    $endpoints = [
        'premios' => 'https://www.loteriasyapuestas.es/servicios/premioDecimoProvisionalWeb',
        'repartido' => 'https://www.loteriasyapuestas.es/servicios/repartidoEn1',
        'busqueda' => 'https://www.loteriasyapuestas.es/servicios/busquedaNumeros?sorteo=' . $id . '&numero=' . $num
    ];

    if (!isset($endpoints[$type])) return new WP_Error('invalid', 'Tipo inválido', ['status' => 404]);
    
    // Cache
    $cache_key = 'loteria_v6_' . $type . '_' . $id . ($num ? '_' . $num : '');
    $cached = get_transient($cache_key);
    if ($cached) return rest_ensure_response(json_decode($cached));

    $referer = 'https://theobjective.com/';
    $response = wp_remote_get($endpoints[$type], [
        'timeout' => 15,
        'sslverify' => false,
        'headers' => [
            'User-Agent' => 'selae_medio_TheObjective',
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Referer' => $referer,
            'Cache-Control' => 'no-cache'
        ]
    ]);
    if (is_wp_error($response)) return new WP_Error('api_error', $response->get_error_message(), ['status' => 500]);

    $body = wp_remote_retrieve_body($response);
    $code = wp_remote_retrieve_response_code($response);

    // Validación JSON
    $json = json_decode($body);
    if ($json === null) {
        // Fallback silencioso
        error_log("Loteria V6: Respuesta no JSON de SELAE (Code $code).");
        return rest_ensure_response(new stdClass());
    }

    set_transient($cache_key, $body, 60); // Cache 60s
    return rest_ensure_response($json);
}

function loteria_navidad_get_api_url($type) {
    return get_rest_url(null, 'loteria-navidad/v5/datos/' . $type);
}

// 2. HELPER FUNCTIONS FOR HTML GENERATION (Shared by Shortcodes & Blocks)

function loteria_render_premios_html() {
    $uid = 'lot_' . md5(uniqid(rand(), true));
    $api = loteria_navidad_get_api_url('premios');
    
    ob_start();
    ?>
    <div class="loteria-widget loteria-premios" data-api="<?php echo esc_attr($api); ?>" id="<?php echo $uid; ?>">
        <div class="loteria-header">
            <h2 class="loteria-title">Premios Principales</h2>
            <p class="loteria-subtitle">Resultados del Sorteo de Navidad 2025</p>
            <button class="loteria-btn-reload">Actualizar</button>
        </div>
        <div class="loteria-content">
            <div class="loteria-loading">Cargando premios...</div>
        </div>
    </div>
    <?php return ob_get_clean();
}

function loteria_render_comprobador_html() {
    $uid = 'lot_' . md5(uniqid(rand(), true));
    $api = loteria_navidad_get_api_url('premios'); // El comprobador usa la misma API de premios para cotejar

    ob_start();
    ?>
    <div class="loteria-widget loteria-comprobador" data-api="<?php echo esc_attr($api); ?>" id="<?php echo $uid; ?>">
        <div class="loteria-header">
            <h2 class="loteria-title">Comprobar Lotería</h2>
            <p class="loteria-subtitle">Introduce tu número y el importe jugado</p>
            <button class="loteria-btn-reload">Actualizar</button>
        </div>
        <div class="loteria-content">
            <form class="loteria-form-check">
                <div class="loteria-input-group"><label>Número</label>
                    <input type="text" name="num" maxlength="5" placeholder="00000" class="loteria-input" required>
                </div>
                <div class="loteria-input-group"><label>Importe (€)</label>
                    <input type="number" name="amt" value="20" min="1" class="loteria-input" required>
                </div>
                <div style="display:flex;align-items:flex-end;">
                    <button type="submit" class="loteria-btn-check">Comprobar</button>
                </div>
            </form>
            <div class="loteria-result"></div>
        </div>
    </div>
    <?php return ob_get_clean();
}

function loteria_render_pedrea_html() {
    $uid = 'lot_' . md5(uniqid(rand(), true));
    $api = loteria_navidad_get_api_url('premios');

    ob_start();
    ?>
    <div class="loteria-widget loteria-pedrea" data-api="<?php echo esc_attr($api); ?>" id="<?php echo $uid; ?>">
        <div class="loteria-header">
            <h2 class="loteria-title">Resultados Lotería Navidad 2025</h2>
            <button class="loteria-btn-reload">Actualizar</button>
        </div>
        <div class="loteria-content">
            <div class="loteria-pedrea-tabs"></div>
            <div class="loteria-pedrea-range-title"></div>
            <div class="loteria-pedrea-scroll">
                <div class="loteria-pedrea-table-container">
                    <p class="loteria-loading">Cargando datos...</p>
                </div>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

function loteria_render_horizontal_html() {
    $uid = 'lot_' . md5(uniqid(rand(), true));
    $api = loteria_navidad_get_api_url('premios');
    
    ob_start();
    ?>
    <div class="loteria-widget loteria-premios-horiz" data-api="<?php echo esc_attr($api); ?>" id="<?php echo $uid; ?>">
        <div class="loteria-box-horiz">
            <div class="loteria-scroll-container">
                <div class="loteria-content-horiz loteria-flex-row">
                    <div class="loteria-loading" style="width:100%;">Cargando premios...</div>
                </div>
            </div>
            <div style="text-align:center;">
                 <button class="loteria-btn-reload">Actualizar</button>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

// 3. SHORTCODES

add_shortcode('loteria_premios', 'loteria_render_premios_html');
add_shortcode('loteria_comprobador', 'loteria_render_comprobador_html');
add_shortcode('loteria_pedrea', 'loteria_render_pedrea_html');
add_shortcode('loteria_premios_horizontal', 'loteria_render_horizontal_html');

// 4. GUTENBERG BLOCKS - Usando block.json (método moderno)

// Registrar categoría personalizada para bloques de lotería
add_filter('block_categories_all', function($categories) {
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
}, 10, 1);

// Registrar bloques usando block.json
add_action('init', function() {
    // Registrar cada bloque desde su carpeta con block.json
    register_block_type(__DIR__ . '/blocks/horizontal');
    register_block_type(__DIR__ . '/blocks/comprobador');
    register_block_type(__DIR__ . '/blocks/pedrea');
    register_block_type(__DIR__ . '/blocks/premios');
});
