<?php
/**
 * Plugin Name: Loteria Navidad 2025 - V5 (Event Delegation)
 * Description: Widgets de lotería robustos usando Event Delegation para evitar conflictos JS.
 * Version: 5.3 (Stealth Mode)
 * Author: Cascade AI
 */

if (!defined('ABSPATH')) exit;

// AUTO-FLUSH PERMALINKS ON UPDATE
add_action('init', function() {
    if (!get_option('loteria_navidad_v5_flushed')) {
        flush_rewrite_rules();
        update_option('loteria_navidad_v5_flushed', true);
    }
});

// 1. API PROXY SETUP (V5)
if (!defined('LOTERIA_ID_SORTEO_V5')) {
    define('LOTERIA_ID_SORTEO_V5', '1295909102');
}

// Enqueue Styles
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('loteria-navidad-styles', plugins_url('loteria.css', __FILE__), [], '5.3');
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
    $num = $request->get_param('num'); // Capturar número si viene
    
    // Construir URLs base
    $endpoints = [
        'premios' => 'https://www.loteriasyapuestas.es/servicios/premioDecimoProvisionalWeb?s=' . $id,
        'repartido' => 'https://www.loteriasyapuestas.es/servicios/repartidoEn1?s=' . $id,
        'busqueda' => 'https://www.loteriasyapuestas.es/servicios/busquedaNumeros?sorteo=' . $id . '&numero=' . $num
    ];

    if (!isset($endpoints[$type])) return new WP_Error('invalid', 'Tipo inválido', ['status' => 404]);
    
    // Cache key incluye el número si es búsqueda
    $cache_key = 'loteria_v5_' . $type . '_' . $id . ($num ? '_' . $num : '');
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

    // DEBUG SILENCIOSO: Si falla el JSON (ej. sorteo no iniciado), devolver vacío para pintar tabla "Pendiente"
    $json = json_decode($body);
    if ($json === null) {
        // Log interno por si acaso
        error_log("Loteria V5: Respuesta no JSON de SELAE (Code $code). Body length: " . strlen($body));
        // Devolver objeto vacío para que el frontend pinte los guiones
        return rest_ensure_response(new stdClass());
    }

    set_transient($cache_key, $body, 60);
    return rest_ensure_response($json);
}

function loteria_navidad_get_api_v5($type) {
    return get_rest_url(null, 'loteria-navidad/v5/datos/' . $type);
}

// 2. SHORTCODES (HTML ONLY - NO INLINE JS)

// Shortcode 1: Premios
add_shortcode('loteria_premios', function() {
    $uid = 'lot_' . md5(uniqid(rand(), true));
    $api = loteria_navidad_get_api_v5('premios');
    
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
});

// Shortcode 2: Comprobador
add_shortcode('loteria_comprobador', function() {
    $uid = 'lot_' . md5(uniqid(rand(), true));
    $api = loteria_navidad_get_api_v5('premios');

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
});

// Shortcode 3: Pedrea
add_shortcode('loteria_pedrea', function() {
    $uid = 'lot_' . md5(uniqid(rand(), true));
    $api = loteria_navidad_get_api_v5('premios');

    ob_start();
    ?>
    <div class="loteria-widget loteria-pedrea" data-api="<?php echo esc_attr($api); ?>" id="<?php echo $uid; ?>">
        <div class="loteria-header">
            <h2 class="loteria-title">La Pedrea</h2>
            <p class="loteria-subtitle">Números premiados con 1.000€ a la serie</p>
            <button class="loteria-btn-reload">Actualizar</button>
        </div>
        <div class="loteria-content">
            <div class="loteria-pedrea-list">
                <p class="loteria-loading">Cargando pedrea...</p>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
});

// Shortcode 4: Premios Horizontal (Frontpage)
add_shortcode('loteria_premios_horizontal', function() {
    $uid = 'lot_' . md5(uniqid(rand(), true));
    $api = loteria_navidad_get_api_v5('premios');
    
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
});

// Shortcode 5: [ELIMINADO] Buscador Administraciones

// 3. SINGLE UNIFIED JAVASCRIPT (WP_FOOTER)
add_action('wp_footer', function() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Helper: Format Currency
        const fmt = (n) => new Intl.NumberFormat('es-ES', {style:'currency', currency:'EUR'}).format(n);

        // 1. PREMIOS
        document.querySelectorAll('.loteria-premios').forEach(w => {
            const api = w.dataset.api;
            const content = w.querySelector('.loteria-content');
            w.querySelector('.loteria-btn-reload').onclick = () => location.reload();
            
            fetch(api).then(r=>{
                if(!r.ok) throw new Error(`API Error ${r.status}`);
                return r.json();
            }).then(d => {
                // DEBUG HANDLER
                if(d.error === 'DEBUG_MODE') {
                    console.warn('DEBUG:', d);
                    const debugInfo = `<div style="background:#333;color:#0f0;padding:15px;font-family:monospace;font-size:12px;text-align:left;overflow:auto;">
                        <strong>DEBUG MODE:</strong><br>
                        Status: ${d.selae_code}<br>
                        Preview:<br>${d.body_preview.replace(/</g,'&lt;')}
                    </div>`;
                    if(content) content.innerHTML = debugInfo;
                    return;
                }

                // Definir estructura base de premios
                const basePremios = [
                    {n:'EL GORDO', v:'4.000.000 €', k:'primerPremio'},
                    {n:'2º PREMIO', v:'1.250.000 €', k:'segundoPremio'},
                    {n:'3º PREMIO', v:'500.000 €', k:'tercerosPremios', idx:0},
                    {n:'4º PREMIO', v:'200.000 €', k:'cuartosPremios', idx:0},
                    {n:'5º PREMIO', v:'60.000 €', k:'quintosPremios', idx:0}
                ];

                let h = '';
                basePremios.forEach(i => {
                    // Obtener dato real o usar default
                    let num = '-----';
                    let status = 'Pendiente de extraer';
                    
                    if (d[i.k]) {
                        const obj = (i.idx !== undefined && Array.isArray(d[i.k])) ? d[i.k][i.idx] : d[i.k];
                        if (obj && obj.decimo) {
                            num = obj.decimo;
                            status = ''; // Si hay número, borramos "Pendiente" o ponemos hora si viniera
                        }
                    }

                    h += `<div class="loteria-premio-row">
                        <div class="loteria-premio-info">
                            <strong class="loteria-premio-name">${i.n}</strong>
                            <small class="loteria-premio-val">${i.v}</small>
                        </div>
                        <div class="loteria-premio-num">
                            ${num}
                        </div>
                        <div class="loteria-premio-status">
                            ${status}
                        </div>
                    </div>`;
                });
                content.innerHTML = h;

            }).catch(e => {
                console.error(e);
                content.innerHTML = `<p style="color:red;">Error: ${e.message}</p>`;
            });
        });

        // 2. COMPROBADOR
        document.querySelectorAll('.loteria-comprobador').forEach(w => {
            const api = w.dataset.api;
            const res = w.querySelector('.loteria-result');
            w.querySelector('.loteria-btn-reload').onclick = () => location.reload();

            w.querySelector('form').addEventListener('submit', function(e) {
                e.preventDefault();
                const num = this.querySelector('[name=num]').value.padStart(5,'0');
                const amt = parseFloat(this.querySelector('[name=amt]').value) || 20;
                res.innerHTML = '<p style="text-align:center;">Comprobando...</p>';

                fetch(api).then(r=>{
                if(!r.ok) throw new Error(`API Error ${r.status}`);
                return r.json();
            }).then(d => {
                // DEBUG HANDLER
                if(d.error === 'DEBUG_MODE') {
                    const debugInfo = `<div style="background:#333;color:#0f0;padding:15px;font-family:monospace;font-size:12px;text-align:left;">DEBUG: ${d.json_error} (${d.body_length} bytes)<br>Preview: ${d.body_preview.replace(/</g,'&lt;')}</div>`;
                    res.innerHTML = debugInfo;
                    return;
                }

                    if(!d.compruebe) { res.innerHTML = '<p style="text-align:center;">Su nº no ha sido premiado.</p>'; return; }
                    const win = d.compruebe.find(i => i.decimo == num);
                    if(win) {
                        const winAmt = (win.prize/100/20) * amt;
                        res.innerHTML = `<div class="loteria-result-box loteria-result-win"><h3>¡Enhorabuena!</h3><p>Premio: <strong>${fmt(winAmt)}</strong></p></div>`;
                    } else {
                        res.innerHTML = `<div class="loteria-result-box loteria-result-lose"><h3>Su nº no ha sido premiado</h3></div>`;
                    }
                }).catch(e => {
                    console.error(e);
                    res.innerHTML = `<p style="color:red;">Error: ${e.message}</p>`;
                });
            });
        });

        // 3. PEDREA
        document.querySelectorAll('.loteria-pedrea').forEach(w => {
            const api = w.dataset.api;
            const list = w.querySelector('.loteria-pedrea-list');
            w.querySelector('.loteria-btn-reload').onclick = () => location.reload();
            
            fetch(api).then(r=>r.json()).then(d => {
                if(d.error === 'DEBUG_MODE') return;
                if(!d.compruebe || !Array.isArray(d.compruebe)) {
                    list.innerHTML = '<p style="text-align:center;width:100%;">Información aún no disponible.</p>';
                    return;
                }
                
                // Filtrar Pedrea: Premios de 1.000€ a la serie (100.000 céntimos)
                // Se ordenan numéricamente
                const pedrea = d.compruebe
                    .filter(i => parseInt(i.prize) === 100000)
                    .map(i => i.decimo)
                    .sort((a,b) => a - b);
                
                if(!pedrea.length) {
                    list.innerHTML = '<p style="text-align:center;width:100%;">Aún no hay datos de pedrea.</p>';
                    return;
                }

                let h = '';
                pedrea.forEach(n => {
                    h += `<span class="loteria-pedrea-item">${n}</span>`;
                });
                list.innerHTML = h;

            }).catch(e => {
                console.error(e);
                list.innerHTML = `<p style="color:red;text-align:center;">Error al cargar pedrea.</p>`;
            });
        });

        // 4. PREMIOS HORIZONTAL
        document.querySelectorAll('.loteria-premios-horiz').forEach(w => {
            const api = w.dataset.api;
            const content = w.querySelector('.loteria-content-horiz');
            w.querySelector('.loteria-btn-reload').onclick = () => location.reload();
            
            fetch(api).then(r=>r.json()).then(d => {
                if(d.error === 'DEBUG_MODE') return;

                const items = [
                    {l:'Gordo', v:'4M€', k:'primerPremio'},
                    {l:'2º', v:'1.25M€', k:'segundoPremio'},
                    {l:'3º', v:'500k€', k:'tercerosPremios', i:0},
                    {l:'4º', v:'200k€', k:'cuartosPremios', i:0},
                    {l:'5º', v:'60k€', k:'quintosPremios', i:0}
                ];

                let h = '';
                items.forEach(it => {
                    let num = '-----';
                    if (d[it.k]) {
                        const obj = (it.i !== undefined && Array.isArray(d[it.k])) ? d[it.k][it.i] : d[it.k];
                        if (obj && obj.decimo) num = obj.decimo;
                    }
                    h += `<div class="loteria-item-horiz">
                        <div class="loteria-label-horiz">${it.l}</div>
                        <div class="loteria-num-horiz">${num}</div>
                        <div class="loteria-prize-horiz">${it.v}</div>
                    </div>`;
                });
                // Remove last border (CSS does it via last-child, but let's keep JS clean)
                content.innerHTML = h;
            }).catch(console.error);
        });

    });
    </script>
    <?php
}, 100); // Priority 100 to ensure it's late in footer
