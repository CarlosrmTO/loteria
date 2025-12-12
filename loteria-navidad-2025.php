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
    define('LOTERIA_ID_SORTEO_V5', '1259409103');
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
    
    $endpoints = [
        'premios' => 'https://www.loteriasyapuestas.es/servicios/premioDecimoProvisionalWeb?s=' . $id,
        'repartido' => 'https://www.loteriasyapuestas.es/servicios/repartidoEn1?s=' . $id
    ];

    if (!isset($endpoints[$type])) return new WP_Error('invalid', 'Tipo inválido', ['status' => 404]);

    $cache_key = 'loteria_v5_' . $type . '_' . $id;
    $cached = get_transient($cache_key);
    if ($cached) return rest_ensure_response(json_decode($cached));

    $response = wp_remote_get($endpoints[$type], [
        'timeout' => 10,
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'es-ES,es;q=0.9',
            'Referer' => 'https://www.loteriasyapuestas.es/',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1'
        ]
    ]);
    if (is_wp_error($response)) return new WP_Error('api_error', $response->get_error_message(), ['status' => 500]);

    $body = wp_remote_retrieve_body($response);
    $code = wp_remote_retrieve_response_code($response);

    // DEBUG MODE: Si falla el JSON, devolver info de depuración
    $json = json_decode($body);
    if ($json === null) {
        // Intentar detectar por qué falló
        $json_error = json_last_error_msg();
        return rest_ensure_response([
            'error' => 'DEBUG_MODE',
            'message' => 'SELAE devolvió datos no válidos',
            'selae_code' => $code,
            'body_length' => strlen($body),
            'body_preview' => substr($body, 0, 500), // Ver qué llega realmente
            'json_error' => $json_error,
            'is_utf8' => mb_check_encoding($body, 'UTF-8') ? 'YES' : 'NO'
        ]);
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
            <button class="loteria-btn-reload" style="background:#fff;border:1px solid #c5a059;color:#c5a059;padding:8px 16px;border-radius:8px;cursor:pointer;margin-top:10px;">🔄 Actualizar</button>
        </div>
        <div class="loteria-content" style="background:#fff;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);padding:30px;border-top:4px solid #c5a059;">
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
            <button class="loteria-btn-reload" style="background:#fff;border:1px solid #c5a059;color:#c5a059;padding:8px 16px;border-radius:8px;cursor:pointer;margin-top:10px;">🔄 Actualizar</button>
        </div>
        <div style="background:#fff;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);padding:30px;border-top:4px solid #c5a059;">
            <form class="loteria-form-check" style="display:flex;gap:15px;justify-content:center;flex-wrap:wrap;margin-bottom:20px;">
                <div><label style="display:block;margin-bottom:5px;font-weight:600;">Número</label>
                    <input type="text" name="num" maxlength="5" placeholder="00000" style="padding:12px;border:1px solid #ddd;border-radius:8px;" required>
                </div>
                <div><label style="display:block;margin-bottom:5px;font-weight:600;">Importe (€)</label>
                    <input type="number" name="amt" value="20" min="1" style="padding:12px;border:1px solid #ddd;border-radius:8px;" required>
                </div>
                <div style="display:flex;align-items:flex-end;">
                    <button type="submit" style="background:#c5a059;color:white;border:none;padding:12px 24px;border-radius:8px;font-weight:600;cursor:pointer;">Comprobar</button>
                </div>
            </form>
            <div class="loteria-result"></div>
        </div>
    </div>
    <?php return ob_get_clean();
});

// Shortcode 3: Buscar Número
add_shortcode('loteria_buscar', function() {
    $uid = 'lot_' . md5(uniqid(rand(), true));
    $api = loteria_navidad_get_api_v5('repartido');

    ob_start();
    ?>
    <div class="loteria-widget loteria-buscar" data-api="<?php echo esc_attr($api); ?>" id="<?php echo $uid; ?>" style="margin:40px 0;font-family:Arial,sans-serif;clear:both;min-height:100px;">
        <div style="text-align:center;margin-bottom:30px;">
            <h2 style="font-size:2rem;color:#1a1a1a;">Buscar Número</h2>
            <p style="color:#666;">Descubre dónde se vende tu número favorito</p>
            <button class="loteria-btn-reload" style="background:#fff;border:1px solid #c5a059;color:#c5a059;padding:8px 16px;border-radius:8px;cursor:pointer;margin-top:10px;">🔄 Actualizar</button>
        </div>
        <div style="background:#fff;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);padding:30px;border-top:4px solid #c5a059;">
            <form class="loteria-form-search" style="display:flex;gap:15px;justify-content:center;margin-bottom:20px;">
                <input type="text" name="num" maxlength="5" placeholder="00000" style="flex:1;max-width:200px;padding:12px;border:1px solid #ddd;border-radius:8px;" required>
                <button type="submit" style="background:#c5a059;color:white;border:none;padding:12px 24px;border-radius:8px;font-weight:600;cursor:pointer;">Buscar</button>
            </form>
            <div class="loteria-result"></div>
        </div>
    </div>
    <?php return ob_get_clean();
});

// Shortcode 4: Administraciones Premiadas
add_shortcode('loteria_admin_premiadas', function() {
    $uid = 'lot_' . md5(uniqid(rand(), true));
    $api = loteria_navidad_get_api_v5('repartido');

    ob_start();
    ?>
    <div class="loteria-widget loteria-admin-premiadas" data-api="<?php echo esc_attr($api); ?>" id="<?php echo $uid; ?>" style="margin:40px 0;font-family:Arial,sans-serif;clear:both;min-height:100px;">
        <div style="text-align:center;margin-bottom:30px;">
            <h2 style="font-size:2rem;color:#1a1a1a;">Administraciones Premiadas</h2>
            <p style="color:#666;">Administraciones que han vendido números premiados</p>
        </div>
        <div style="background:#fff;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);padding:30px;border-top:4px solid #c5a059;">
            <select class="loteria-prov-select" style="width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;margin-bottom:20px;">
                <option value="">Selecciona una provincia...</option>
            </select>
            <div class="loteria-list" style="max-height:500px;overflow-y:auto;">
                <p style="text-align:center;color:#666;padding:40px;">Cargando datos...</p>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
});

// Shortcode 5: Buscador Administraciones
add_shortcode('loteria_buscador_admin', function() {
    $uid = 'lot_' . md5(uniqid(rand(), true));
    $api = loteria_navidad_get_api_v5('repartido');

    ob_start();
    ?>
    <div class="loteria-widget loteria-buscador-admin" data-api="<?php echo esc_attr($api); ?>" id="<?php echo $uid; ?>" style="margin:40px 0;font-family:Arial,sans-serif;clear:both;min-height:100px;">
        <div style="text-align:center;margin-bottom:30px;">
            <h2 style="font-size:2rem;color:#1a1a1a;">Buscador de Administraciones</h2>
            <p style="color:#666;">Encuentra dónde comprar lotería en toda España</p>
        </div>
        <div style="background:#fff;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);padding:30px;border-top:4px solid #c5a059;">
            <div style="display:flex;gap:10px;margin-bottom:15px;">
                <input type="text" class="loteria-input-search" placeholder="Buscar por nombre, localidad..." style="flex:1;padding:12px;border:1px solid #ddd;border-radius:8px;">
                <button class="loteria-btn-search" style="background:#c5a059;color:white;border:none;padding:12px 24px;border-radius:8px;font-weight:600;cursor:pointer;">Buscar</button>
            </div>
            <select class="loteria-prov-select" style="width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;margin-bottom:20px;">
                <option value="">Todas las provincias</option>
            </select>
            <div class="loteria-list" style="max-height:500px;overflow-y:auto;">
                <p style="text-align:center;color:#666;padding:40px;">Cargando datos...</p>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
});

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
                        Length: ${d.body_length}<br>
                        JSON Error: ${d.json_error}<br>
                        UTF-8: ${d.is_utf8}<br>
                        <hr style="border-color:#555;">
                        Preview:<br>${d.body_preview.replace(/</g,'&lt;')}
                    </div>`;
                    if(content) content.innerHTML = debugInfo;
                    if(res) res.innerHTML = debugInfo;
                    if(list) list.innerHTML = debugInfo;
                    return;
                }

                if(!d.primerPremio) {
                    content.innerHTML = '<p style="text-align:center;color:#666;">⚠️ Información no disponible.</p>';
                    return;
                }
                const p = [
                    {n:'EL GORDO', v:'4.000.000 €', d:d.primerPremio.decimo},
                    {n:'2º PREMIO', v:'1.250.000 €', d:d.segundoPremio.decimo},
                    {n:'3º PREMIO', v:'500.000 €', d:d.tercerosPremios[0].decimo},
                    {n:'4º PREMIO', v:'200.000 €', d:d.cuartosPremios[0].decimo},
                    {n:'5º PREMIO', v:'60.000 €', d:d.quintosPremios[0].decimo}
                ];
                let h = '';
                p.forEach(i => {
                    h += `<div style="padding:15px;margin:10px 0;border:1px solid #e0e0e0;border-left:4px solid #c5a059;border-radius:8px;display:grid;grid-template-columns:120px 1fr;gap:15px;align-items:center;">
                        <div><strong>${i.n}</strong><br><small style="color:#666;">${i.v}</small></div>
                        <div style="text-align:center;font-size:1.5rem;font-weight:700;font-family:monospace;">${i.d||'-----'}</div>
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

                    if(!d.compruebe) { res.innerHTML = '<p style="text-align:center;">Sistema no activo.</p>'; return; }
                    const win = d.compruebe.find(i => i.decimo == num);
                    if(win) {
                        const winAmt = (win.prize/100/20) * amt;
                        res.innerHTML = `<div style="text-align:center;padding:20px;background:#fdfbf7;border:1px solid #e8dfc8;border-radius:8px;"><h3>¡Enhorabuena!</h3><p>Premio: <strong>${fmt(winAmt)}</strong></p></div>`;
                    } else {
                        res.innerHTML = `<div style="text-align:center;padding:20px;background:#f8f9fa;border:1px solid #ddd;border-radius:8px;"><h3>Sin premio</h3></div>`;
                    }
                }).catch(e => {
                    console.error(e);
                    res.innerHTML = `<p style="color:red;">Error: ${e.message}</p>`;
                });
            });
        });

        // 3. BUSCAR NUMERO
        document.querySelectorAll('.loteria-buscar').forEach(w => {
            const api = w.dataset.api;
            const res = w.querySelector('.loteria-result');
            w.querySelector('.loteria-btn-reload').onclick = () => location.reload();

            w.querySelector('form').addEventListener('submit', function(e) {
                e.preventDefault();
                const num = this.querySelector('[name=num]').value.padStart(5,'0');
                res.innerHTML = '<p style="text-align:center;">Buscando...</p>';

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
                    if(!Array.isArray(d)) { res.innerHTML = '<p style="text-align:center;">Buscador no disponible.</p>'; return; }
                    const entry = d.find(i => i.decimo == num);
                    if(entry && entry.repartidoEn) {
                        let h = '';
                        entry.repartidoEn.forEach(a => {
                            h += `<div style="padding:15px;border-bottom:1px solid #e0e0e0;"><strong>${a.nombre_comercial}</strong><br><small>${a.direccion}, ${a.poblacion}</small></div>`;
                        });
                        res.innerHTML = h;
                    } else {
                        res.innerHTML = `<p style="text-align:center;">No encontrado.</p>`;
                    }
                }).catch(e => {
                    console.error(e);
                    res.innerHTML = `<p style="color:red;">Error: ${e.message}</p>`;
                });
            });
        });

        // 4 & 5. ADMINS (Shared Data Logic)
        const loadAdmins = (w, type) => {
            const api = w.dataset.api;
            const sel = w.querySelector('select');
            const list = w.querySelector('.loteria-list');
            
            fetch(api).then(r=>{
                if(!r.ok) throw new Error(`API Error ${r.status}`);
                return r.json();
            }).then(d => {
                // DEBUG HANDLER
                if(d.error === 'DEBUG_MODE') {
                    const debugInfo = `<div style="background:#333;color:#0f0;padding:15px;font-family:monospace;font-size:12px;text-align:left;">DEBUG: ${d.json_error} (${d.body_length} bytes)<br>Preview: ${d.body_preview.replace(/</g,'&lt;')}</div>`;
                    list.innerHTML = debugInfo;
                    return;
                }
                if(!Array.isArray(d)) { list.innerHTML = '<p style="text-align:center;">Datos no disponibles.</p>'; return; }
                
                // Process Admins
                const map = new Map();
                d.forEach(i => {
                    if(i.repartidoEn) i.repartidoEn.forEach(a => map.set(a.poblacion+a.direccion, a));
                });
                const admins = Array.from(map.values());
                
                // Fill Select
                const provs = new Set(admins.map(a => a.provincia).filter(Boolean));
                Array.from(provs).sort().forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p; opt.textContent = p;
                    sel.appendChild(opt);
                });

                // Logic for "Premiadas" (List by Province)
                if(type === 'premiadas') {
                    list.innerHTML = '<p style="text-align:center;padding:40px;color:#666;">Selecciona una provincia.</p>';
                    sel.onchange = function() {
                        if(!this.value) return;
                        const f = admins.filter(a => a.provincia === this.value);
                        let h = `<h3>${this.value}</h3>`;
                        f.forEach(a => h += `<div style="padding:10px;border-bottom:1px solid #eee;"><strong>${a.nombre_comercial}</strong><br><small>${a.direccion}, ${a.poblacion}</small></div>`);
                        list.innerHTML = h;
                    };
                }

                // Logic for "Buscador" (Filter input)
                if(type === 'buscador') {
                    const input = w.querySelector('input');
                    const btn = w.querySelector('.loteria-btn-search');
                    const doSearch = () => {
                        const q = input.value.toLowerCase();
                        const p = sel.value;
                        const f = admins.filter(a => (p ? a.provincia===p : true) && (a.nombre_comercial.toLowerCase().includes(q) || a.poblacion.toLowerCase().includes(q)));
                        if(!f.length) { list.innerHTML = '<p style="text-align:center;">Sin resultados.</p>'; return; }
                        let h = `<div style="padding:10px;background:#f0f7ff;">Encontradas: ${f.length}</div>`;
                        f.slice(0,50).forEach(a => h += `<div style="padding:10px;border-bottom:1px solid #eee;"><strong>${a.nombre_comercial}</strong><br><small>${a.direccion}, ${a.poblacion} (${a.provincia})</small></div>`);
                        list.innerHTML = h;
                    };
                    btn.onclick = doSearch;
                    input.onkeyup = (e) => { if(e.key==='Enter') doSearch(); };
                    list.innerHTML = '<p style="text-align:center;padding:40px;">Usa el buscador.</p>';
                }

            }).catch(e => {
                console.error(e);
                list.innerHTML = `<p style="color:red;">Error: ${e.message}</p>`;
            });
        };

        document.querySelectorAll('.loteria-admin-premiadas').forEach(w => loadAdmins(w, 'premiadas'));
        document.querySelectorAll('.loteria-buscador-admin').forEach(w => loadAdmins(w, 'buscador'));

    });
    </script>
    <?php
}, 100); // Priority 100 to ensure it's late in footer
