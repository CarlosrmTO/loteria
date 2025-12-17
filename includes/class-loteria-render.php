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
     * Render Premios widget
     *
     * @return string
     */
    public static function premios() {
        $uid = 'lot_' . md5(uniqid(rand(), true));
        $api = Loteria_API::get_api_url('premios');

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
