<?php
/**
 * Widget Render Class
 *
 * @package Loteria_Navidad
 * @since 7.9
 */

if (!defined('ABSPATH')) exit;

class Loteria_Render {

    /**
     * Check if current request is AMP
     * 
     * @return boolean
     */
    private static function is_amp() {
        return function_exists('is_amp_endpoint') && is_amp_endpoint();
    }

    /**
     * Get non-AMP URL for the current page
     * 
     * @return string
     */
    private static function get_web_url() {
        $url = '';

        // Try to get canonical URL first
        if (is_singular()) {
            $url = get_permalink();
        } else {
            global $wp;
            $url = home_url(add_query_arg(array(), $wp->request));
        }

        // 1. Remove 'amp' query parameter (standard WP way)
        $url = remove_query_arg(array('amp', 'usqp'), $url);
        
        // 2. Remove '/amp/' or '/amp' endpoint from path, preserving query args
        // Matches /amp/ at the end, or /amp?..., or /amp/?...
        $url = preg_replace('#/amp/?(\?|$)#i', '$1', $url);
        
        return $url;
    }

    /**
     * Render Premios widget
     *
     * @return string
     */
    public static function premios() {
        $uid = 'lot_' . md5(uniqid(rand(), true));
        $api = Loteria_API::get_api_url('premios');

        ob_start();
        
        if (self::is_amp()) {
            // AMP Version using amp-list
            ?>
            <div class="loteria-widget loteria-premios">
                <div class="loteria-header">
                    <h2 class="loteria-title">Premios Principales</h2>
                    <p class="loteria-subtitle">Resultados del Sorteo de Navidad 2025</p>
                </div>
                <div class="loteria-content">
                    <amp-list width="auto" height="400" layout="fixed-height" src="<?php echo esc_attr($api); ?>" items=".">
                        <template type="amp-mustache">
                            {{#error}}
                                <div class="loteria-loading">Error cargando datos</div>
                            {{/error}}
                            {{#pending}}
                                <div class="loteria-loading">{{message}}</div>
                            {{/pending}}
                            {{^error}}
                            {{^pending}}
                                <div class="loteria-premio-row">
                                    <div class="loteria-premio-info">
                                        <strong class="loteria-premio-name">EL GORDO</strong>
                                        <small class="loteria-premio-val">4.000.000 €</small>
                                    </div>
                                    <div class="loteria-premio-num">{{primerPremio.decimo}}</div>
                                </div>
                                <div class="loteria-premio-row">
                                    <div class="loteria-premio-info">
                                        <strong class="loteria-premio-name">2º PREMIO</strong>
                                        <small class="loteria-premio-val">1.250.000 €</small>
                                    </div>
                                    <div class="loteria-premio-num">{{segundoPremio.decimo}}</div>
                                </div>
                                <div class="loteria-premio-row">
                                    <div class="loteria-premio-info">
                                        <strong class="loteria-premio-name">3º PREMIO</strong>
                                        <small class="loteria-premio-val">500.000 €</small>
                                    </div>
                                    <div class="loteria-premio-num">{{tercerosPremios.0.decimo}}</div>
                                </div>
                                
                                <div class="loteria-compact-section">
                                    <span class="loteria-compact-title">4º PREMIOS</span>
                                    <span class="loteria-compact-val">200.000 €</span>
                                    <div class="loteria-compact-grid">
                                        {{#cuartosPremios}}
                                            <div class="loteria-compact-num">{{decimo}}</div>
                                        {{/cuartosPremios}}
                                    </div>
                                </div>

                                <div class="loteria-compact-section">
                                    <span class="loteria-compact-title">5º PREMIOS</span>
                                    <span class="loteria-compact-val">60.000 €</span>
                                    <div class="loteria-compact-grid">
                                        {{#quintosPremios}}
                                            <div class="loteria-compact-num">{{decimo}}</div>
                                        {{/quintosPremios}}
                                    </div>
                                </div>
                            {{/pending}}
                            {{/error}}
                        </template>
                        <div placeholder>
                            <div class="loteria-loading">Cargando premios...</div>
                        </div>
                        <div fallback>
                            <div class="loteria-loading">Error cargando datos</div>
                        </div>
                    </amp-list>
                    <div style="text-align:center; margin-top: 15px;">
                        <a href="<?php echo esc_url(self::get_web_url()); ?>" class="loteria-btn-reload" style="text-decoration:none;">
                            Ver versión web completa
                        </a>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        // Standard Version
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
        <?php
        return ob_get_clean();
    }

    /**
     * Render Comprobador widget
     *
     * @return string
     */
    public static function comprobador() {
        $uid = 'lot_' . md5(uniqid(rand(), true));
        $api = Loteria_API::get_api_url('premios');

        ob_start();
        
        if (self::is_amp()) {
            // AMP Version using amp-form
            $action_xhr = Loteria_API::get_api_url('busqueda');
            ?>
            <div class="loteria-widget loteria-comprobador">
                <div class="loteria-header">
                    <h2 class="loteria-title">Comprobar Lotería</h2>
                    <p class="loteria-subtitle">Introduce tu número</p>
                </div>
                <div class="loteria-content">
                    <form method="GET" action-xhr="<?php echo esc_attr($action_xhr); ?>" target="_top" class="loteria-form-check">
                        <div class="loteria-input-group"><label>Número</label>
                            <input type="text" name="num" maxlength="5" placeholder="00000" class="loteria-input" required pattern="[0-9]{1,5}">
                        </div>
                        <div class="loteria-input-group" style="display:none;"><label>Importe (€)</label>
                            <input type="number" name="amt" value="20" class="loteria-input" hidden>
                        </div>
                        <div style="display:flex;align-items:flex-end;">
                            <button type="submit" class="loteria-btn-check">Comprobar</button>
                        </div>
                        
                        <div submitting>
                            <div class="loteria-loading">Comprobando...</div>
                        </div>
                        
                        <div submit-success>
                            <template type="amp-mustache">
                                {{#error}}
                                    <div class="loteria-result-box loteria-result-lose">
                                        <p class="loteria-result-msg">Error: {{error}}</p>
                                    </div>
                                {{/error}}
                                {{^error}}
                                    {{#premio}}
                                        <div class="loteria-result-box loteria-result-win">
                                            <p class="loteria-result-msg">¡Enhorabuena!</p>
                                            <p>Premio por décimo: <strong>{{premio_formatted}}</strong></p>
                                        </div>
                                    {{/premio}}
                                    {{^premio}}
                                        <div class="loteria-result-box loteria-result-lose">
                                            <p class="loteria-result-msg">El número no ha sido premiado</p>
                                        </div>
                                    {{/premio}}
                                {{/error}}
                            </template>
                        </div>
                        
                        <div submit-error>
                            <div class="loteria-result-box loteria-result-lose">
                                <p class="loteria-result-msg">Error de conexión. Inténtalo de nuevo.</p>
                            </div>
                        </div>
                    </form>
                    <div style="text-align:center; margin-top: 15px;">
                        <a href="<?php echo esc_url(self::get_web_url()); ?>" class="loteria-btn-reload" style="text-decoration:none;">
                            Ver versión web completa
                        </a>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        // Standard Version
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
        <?php
        return ob_get_clean();
    }

    /**
     * Render Pedrea widget
     *
     * @return string
     */
    public static function pedrea() {
        $uid = 'lot_' . md5(uniqid(rand(), true));
        $api = Loteria_API::get_api_url('premios');

        ob_start();
        
        if (self::is_amp()) {
            // AMP Version: Pedrea is too complex for basic AMP without iframes or huge JS
            // Fallback to a simple message pointing to non-AMP version
            ?>
            <div class="loteria-widget loteria-pedrea">
                <div class="loteria-header">
                    <h2 class="loteria-title">Resultados Lotería Navidad 2025</h2>
                </div>
                <div class="loteria-content" style="text-align:center;">
                    <p>La tabla completa de la pedrea no está disponible en la versión AMP.</p>
                    <a href="<?php echo esc_url(self::get_web_url()); ?>" class="loteria-btn-reload" style="text-decoration:none;">
                        Ver versión web completa
                    </a>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        // Standard Version
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
        <?php
        return ob_get_clean();
    }

    /**
     * Render Horizontal widget
     *
     * @return string
     */
    public static function horizontal() {
        $uid = 'lot_' . md5(uniqid(rand(), true));
        $api = Loteria_API::get_api_url('premios');

        ob_start();
        
        if (self::is_amp()) {
            // AMP Version using amp-list
            ?>
            <div class="loteria-widget loteria-premios-horiz">
                <div class="loteria-box-horiz">
                    <div class="loteria-scroll-container">
                        <amp-list width="auto" height="120" layout="fixed-height" src="<?php echo esc_attr($api); ?>" items=".">
                            <template type="amp-mustache">
                                <div class="loteria-content-horiz loteria-flex-row">
                                    {{#error}}
                                        <div class="loteria-loading">Error cargando datos</div>
                                    {{/error}}
                                    {{#pending}}
                                        <div class="loteria-loading">{{message}}</div>
                                    {{/pending}}
                                    {{^error}}
                                    {{^pending}}
                                        <div class="loteria-item-horiz single">
                                            <div class="loteria-label-horiz">El Gordo</div>
                                            <div class="loteria-num-horiz main-num">{{primerPremio.decimo}}</div>
                                            <div class="loteria-prize-horiz">4.000.000€</div>
                                        </div>
                                        <div class="loteria-item-horiz single">
                                            <div class="loteria-label-horiz">2º Premio</div>
                                            <div class="loteria-num-horiz main-num">{{segundoPremio.decimo}}</div>
                                            <div class="loteria-prize-horiz">1.250.000€</div>
                                        </div>
                                        <div class="loteria-item-horiz single">
                                            <div class="loteria-label-horiz">3º Premio</div>
                                            <div class="loteria-num-horiz main-num">{{tercerosPremios.0.decimo}}</div>
                                            <div class="loteria-prize-horiz">500.000€</div>
                                        </div>
                                        
                                        <div class="loteria-item-horiz group-4">
                                            <div class="loteria-label-horiz">4º Premio</div>
                                            <div class="loteria-grid-4">
                                                {{#cuartosPremios}}
                                                <span class="mini-num">{{decimo}}</span>
                                                {{/cuartosPremios}}
                                            </div>
                                            <div class="loteria-prize-horiz">200.000€</div>
                                        </div>

                                        <div class="loteria-item-horiz group-5">
                                            <div class="loteria-label-horiz">5º Premio</div>
                                            <div class="loteria-grid-5">
                                                {{#quintosPremios}}
                                                <span class="mini-num">{{decimo}}</span>
                                                {{/quintosPremios}}
                                            </div>
                                            <div class="loteria-prize-horiz">60.000€</div>
                                        </div>
                                    {{/pending}}
                                    {{/error}}
                                </div>
                            </template>
                            <div placeholder>
                                <div class="loteria-loading">Cargando premios...</div>
                            </div>
                            <div fallback>
                                <div class="loteria-loading">Error cargando datos</div>
                            </div>
                        </amp-list>
                    </div>
                    <div style="text-align:center; margin-top: 15px;">
                        <a href="<?php echo esc_url(self::get_web_url()); ?>" class="loteria-btn-reload" style="text-decoration:none;">
                            Ver versión web completa
                        </a>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        // Standard Version
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
                     <a class="loteria-btn-reload" href="https://theobjective.com/loterias/loteria-navidad/2025-12-10/comprobar-loteria-navidad-2025-premios/">Comprobar décimo</a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
