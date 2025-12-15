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
    <div class="loteria-widget loteria-premios" data-api="<?php echo esc_attr($api); ?>" id="<?php echo $uid; ?>" style="margin:40px 0;font-family:Arial,sans-serif;clear:both;min-height:100px;">
        <div style="text-align:center;margin-bottom:30px;">
            <h2 style="font-size:2rem;color:#1a1a1a;">Premios Principales</h2>
            <p style="color:#666;">Resultados del Sorteo de Navidad 2025</p>
            <button class="loteria-btn-reload" style="background:#FFE032;border:none;color:black;padding:8px 16px;border-radius:8px;cursor:pointer;margin-top:10px;font-weight:600;">Actualizar</button>
        </div>
        <div class="loteria-content" style="background:#fff;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);padding:30px;border-top:4px solid #FFE032;">
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
    <div class="loteria-widget loteria-comprobador" data-api="<?php echo esc_attr($api); ?>" id="<?php echo $uid; ?>" style="margin:40px 0;font-family:Arial,sans-serif;clear:both;min-height:100px;">
        <div style="text-align:center;margin-bottom:30px;">
            <h2 style="font-size:2rem;color:#1a1a1a;">Comprobar Lotería</h2>
            <p style="color:#666;">Introduce tu número y el importe jugado</p>
            <button class="loteria-btn-reload" style="background:#FFE032;border:none;color:black;padding:8px 16px;border-radius:8px;cursor:pointer;margin-top:10px;font-weight:600;">Actualizar</button>
        </div>
        <div style="background:#fff;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);padding:30px;border-top:4px solid #FFE032;">
            <form class="loteria-form-check" style="display:flex;gap:15px;justify-content:center;flex-wrap:wrap;margin-bottom:20px;">
                <div><label style="display:block;margin-bottom:5px;font-weight:600;">Número</label>
                    <input type="text" name="num" maxlength="5" placeholder="00000" style="padding:12px;border:1px solid #ddd;border-radius:8px;" required>
                </div>
                <div><label style="display:block;margin-bottom:5px;font-weight:600;">Importe (€)</label>
                    <input type="number" name="amt" value="20" min="1" style="padding:12px;border:1px solid #ddd;border-radius:8px;" required>
                </div>
                <div style="display:flex;align-items:flex-end;">
                    <button type="submit" style="background:#FFE032;color:black;border:none;padding:12px 24px;border-radius:8px;font-weight:600;cursor:pointer;">Comprobar</button>
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
    <div class="loteria-widget loteria-pedrea" data-api="<?php echo esc_attr($api); ?>" id="<?php echo $uid; ?>" style="margin:40px 0;font-family:Arial,sans-serif;clear:both;min-height:100px;">
        <div style="text-align:center;margin-bottom:20px;">
            <h2 style="font-size:2rem;color:#1a1a1a;">La Pedrea</h2>
            <p style="color:#666;">Números premiados con 1.000€ a la serie</p>
            <button class="loteria-btn-reload" style="background:#FFE032;border:none;color:black;padding:8px 16px;border-radius:8px;cursor:pointer;margin-top:10px;font-weight:600;">Actualizar</button>
        </div>
        <div style="background:#fff;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);padding:20px;border-top:4px solid #FFE032;">
            <div class="loteria-pedrea-list" style="max-height:400px;overflow-y:auto;display:flex;flex-wrap:wrap;gap:8px;justify-content:center;padding-right:5px;">
                <p style="text-align:center;color:#666;width:100%;">Cargando pedrea...</p>
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
    <div class="loteria-widget loteria-premios-horiz" data-api="<?php echo esc_attr($api); ?>" id="<?php echo $uid; ?>" style="margin:20px 0;font-family:Arial,sans-serif;clear:both;">
        <div style="background:#fff;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);padding:15px;border-top:3px solid #FFE032;">
            <div style="overflow-x:auto;padding-bottom:10px;margin-bottom:10px;">
                <div class="loteria-content-horiz" style="display:flex;gap:15px;min-width:max-content;padding:0 5px;">
                    <div class="loteria-loading" style="text-align:center;width:100%;color:#666;">Cargando premios...</div>
                </div>
            </div>
            <div style="text-align:center;">
                 <button class="loteria-btn-reload" style="background:#FFE032;border:none;color:black;padding:8px 16px;border-radius:8px;cursor:pointer;font-weight:600;">Actualizar</button>
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

                    h += `<div style="padding:15px;margin:10px 0;background:#fffaf0;border:1px solid #e0e0e0;border-left:5px solid #FFE032;border-radius:8px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                        <div style="flex:1;min-width:140px;">
                            <strong style="font-size:1.1em;display:block;margin-bottom:4px;">${i.n}</strong>
                            <small style="color:#666;font-size:0.9em;">${i.v}</small>
                        </div>
                        <div style="flex:1;text-align:center;font-size:1.8rem;font-weight:700;font-family:monospace;color:#333;min-width:120px;">
                            ${num}
                        </div>
                        <div style="flex:1;text-align:right;color:#999;font-size:0.85em;min-width:100px;">
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
                        res.innerHTML = `<div style="text-align:center;padding:20px;background:#fdfbf7;border:1px solid #e8dfc8;border-radius:8px;"><h3>¡Enhorabuena!</h3><p>Premio: <strong>${fmt(winAmt)}</strong></p></div>`;
                    } else {
                        res.innerHTML = `<div style="text-align:center;padding:20px;background:#f8f9fa;border:1px solid #ddd;border-radius:8px;"><h3>Su nº no ha sido premiado</h3></div>`;
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
                    h += `<span style="display:inline-block;padding:6px 10px;background:#f8f9fa;border:1px solid #eee;border-radius:4px;font-family:monospace;font-size:1.1rem;color:#333;font-weight:600;">${n}</span>`;
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
                    h += `<div style="text-align:center;padding:0 15px;border-right:1px solid #eee;min-width:110px;">
                        <div style="font-size:0.8rem;color:#666;text-transform:uppercase;font-weight:bold;">${it.l}</div>
                        <div style="font-family:monospace;font-size:1.6rem;font-weight:700;color:#333;margin:4px 0;">${num}</div>
                        <div style="font-size:0.75rem;color:#999;">${it.v}</div>
                    </div>`;
                });
                // Remove last border
                content.innerHTML = h.replace(/border-right:1px solid #eee;min-width:110px;">(?=[^>]*$)/, 'min-width:110px;">');
            }).catch(console.error);
        });

    });
    </script>
    <?php
}, 100); // Priority 100 to ensure it's late in footer
