<?php
/**
 * Plugin Name: Crypto Portfolio Tracker
 * Plugin URI: https://wordpress.org/plugins/crypto-portfolio-tracker/
 * Description: Complete cryptocurrency portfolio tracking with real-time prices, P&L analysis, and interactive dashboard. Multi-user support for investment management.
 * Version: 1.0.0
 * Author: Emigdio Salvador Corado
 * Author URI: https://salvadoresc.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: crypto-portfolio-tracker
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * Update URI: false
 */

if (!defined('ABSPATH')) {
    exit;
}

// === Plugin Constants ===
define('CPT_VERSION', '1.0.0');
define('CPT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CPT_PLUGIN_URL',  plugin_dir_url(__FILE__));
define('CPT_TEXT_DOMAIN', 'crypto-portfolio-tracker');

/**
 * Main Plugin Class
 */
class CryptoPortfolioTracker {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Load dependencies early for normal runtime
        $this->load_dependencies();

        // Main hooks
        add_action('init', array($this, 'init')); // central startup point
        add_action('plugins_loaded', array($this, 'plugins_loaded'));

        // Admin
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // REST
        add_action('rest_api_init', array($this, 'register_api_routes'));

        // Frontend - MODIFICADO: usar wp_loaded para asegurar que el usuario esté disponible
        add_action('wp_loaded', array($this, 'init_frontend_hooks'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Shortcode
        add_shortcode('crypto_dashboard', array($this, 'render_dashboard_shortcode'));

        // Internationalization
        add_action('plugins_loaded', array($this, 'load_textdomain'), 1);
    }

    /**
     * Inicializar hooks del frontend cuando WordPress esté completamente cargado
     */
    public function init_frontend_hooks() {
        // Verificar si estamos en una página con el shortcode
        if (!is_admin()) {
            add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_scripts'));
        }
    }

    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain() {
        $loaded = load_plugin_textdomain(
            'crypto-portfolio-tracker',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
        
        // Debug temporal para verificar carga de traducciones
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CPT Textdomain loaded: ' . ($loaded ? 'YES' : 'NO'));
            error_log('CPT Current locale: ' . get_locale());
        }
    }

    public function load_dependencies() {
        $files = array(
            'includes/class-database.php',
            'includes/class-validation.php',
            'includes/class-coingecko-api.php',
            'includes/class-user-portfolio.php',
            'includes/class-api-handler.php',
        );

        foreach ($files as $file) {
            $path = CPT_PLUGIN_PATH . $file;
            if (file_exists($path)) {
                require_once $path;
            } else {
                error_log("CPT: Missing file - $file");
            }
        }
    }

    public function init() {
        // Debug info temporal
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('=== CPT DEBUG i18n ===');
            error_log('Locale WP: ' . get_locale());
            error_log('Strings en cptAjax: ' . (isset($GLOBALS['cptAjax']) ? 'defined' : 'undefined'));
            error_log('Dashboard title ES: ' . __('Dashboard de Inversiones Crypto', 'crypto-portfolio-tracker'));
            error_log('Dashboard title EN: ' . __('Crypto Investment Dashboard', 'crypto-portfolio-tracker'));
            error_log('Usuario logueado: ' . (is_user_logged_in() ? 'true' : 'false'));
            
            // Debug de scripts cargados
            global $wp_scripts;
            $recharts_loaded = wp_script_is('recharts', 'enqueued') || wp_script_is('recharts', 'done');
            $dashboard_loaded = wp_script_is('cpt-dashboard', 'enqueued') || wp_script_is('cpt-dashboard', 'done');
            error_log('Scripts cargados: ' . ($recharts_loaded ? 'true' : 'false') . ' ' . ($dashboard_loaded ? 'true' : 'false'));
        }
    }

    public function plugins_loaded() {
        // Create tables if needed
        if (!get_option('cpt_tables_created', false)) {
            if (file_exists(CPT_PLUGIN_PATH . 'includes/class-database.php')) {
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                require_once CPT_PLUGIN_PATH . 'includes/class-database.php';

                if (class_exists('CPT_Database')) {
                    $db = new CPT_Database();
                    $db->create_tables();

                    update_option('cpt_tables_created', true);
                    update_option('cpt_db_version', CPT_VERSION);
                }
            } else {
                // If they exist but version changed, allow future migrations (not implemented here)
                update_option('cpt_tables_created', true);
                update_option('cpt_db_version', CPT_VERSION);
            }
        }
    }

    /**
     * NUEVA FUNCIÓN: Verificar si debe cargar scripts y cargarlos condicionalmente
     */
    public function maybe_enqueue_scripts() {
        global $post;
        
        // Solo cargar si:
        // 1. Estamos en una página singular
        // 2. El post tiene el shortcode crypto_dashboard
        // 3. O si estamos en una página donde sabemos que se necesita (para casos especiales)
        $should_load = false;
        
        if (is_singular() && $post && has_shortcode($post->post_content, 'crypto_dashboard')) {
            $should_load = true;
        }
        
        // Permitir que otros plugins/temas fuercen la carga
        $should_load = apply_filters('cpt_should_load_scripts', $should_load);
        
        if ($should_load) {
            $this->enqueue_scripts();
        }
    }

    /**
     * FUNCIÓN CORREGIDA: Cargar scripts sin restricciones de usuario
     */
    public function enqueue_scripts() {
        // NO verificar permisos de usuario aquí - dejar que el shortcode maneje eso
        
        // WordPress dependencies
        wp_enqueue_script('wp-element');
        wp_enqueue_script('wp-api-fetch');
        wp_enqueue_script('wp-url');

        // 1) PropTypes UMD
        wp_enqueue_script(
            'prop-types',
            'https://cdnjs.cloudflare.com/ajax/libs/prop-types/15.8.1/prop-types.min.js',
            array(), null, true
        );

        // 2) Recharts UMD (v2.x) - CDN alternativo más confiable
        wp_enqueue_script(
            'recharts',
            'https://unpkg.com/recharts@2.12.7/umd/Recharts.js',
            array('wp-element','prop-types'), // important: depends on wp-element and prop-types
            null, true
        );

        // 3) Shim: expose React/ReactDOM globals for UMD libs
        wp_add_inline_script(
            'recharts',
            'window.React = window.React || (window.wp && wp.element);',
            'before'
        );

        // 4) Your dashboard
        wp_enqueue_script(
            'cpt-dashboard',
            plugins_url('assets/js/dashboard.js', __FILE__),
            array('wp-element','wp-api-fetch','recharts'),
            CPT_VERSION,
            true
        );

        // 5) Localizar strings DESPUÉS de cargar traducciones - CORREGIDO CON TRADUCCIONES EN ESPAÑOL
        wp_localize_script('cpt-dashboard', 'cptAjax', array(
            'nonce'       => wp_create_nonce('wp_rest'),
            'isLoggedIn'  => is_user_logged_in(),
            'loginUrl'    => wp_login_url(),
            'registerUrl' => function_exists('wp_registration_url') ? wp_registration_url() : wp_login_url(),
            'currentUserId' => get_current_user_id(), // AÑADIDO: ID del usuario actual
            'canRead'     => current_user_can('read'), // AÑADIDO: Verificar capacidades
            'restUrl'     => rest_url('crypto-portfolio/v1/'), // AÑADIDO: URL base de la API
            'strings'     => array(
                'login_required' => __('Acceso Requerido', 'crypto-portfolio-tracker'),
                'login_message' => __('Necesitas iniciar sesión para ver tu portafolio de criptomonedas.', 'crypto-portfolio-tracker'),
                'dashboard_title' => __('Dashboard de Inversiones Crypto', 'crypto-portfolio-tracker'),
                'dashboard_subtitle' => __('Análisis completo de tu portafolio crypto', 'crypto-portfolio-tracker'),
                'total_portfolio_value' => __('Valor Total del Portafolio', 'crypto-portfolio-tracker'),
                'total_invested' => __('Total Invertido', 'crypto-portfolio-tracker'),
                'total_profit_loss' => __('Ganancias y Pérdidas', 'crypto-portfolio-tracker'),
                'portfolio_change_24h' => __('Cambio 24h', 'crypto-portfolio-tracker'),
                'your_portfolio' => __('Tu Portafolio', 'crypto-portfolio-tracker'),
                'recent_transactions' => __('Transacciones Recientes', 'crypto-portfolio-tracker'),
                'add_transaction' => __('Añadir Transacción', 'crypto-portfolio-tracker'),
                'update_prices' => __('Actualizar Precios', 'crypto-portfolio-tracker'),
                'add_first_transaction' => __('Añadir Primera Transacción', 'crypto-portfolio-tracker'),
                // Nuevos strings para el formulario
                'edit_transaction' => __('Editar Transacción', 'crypto-portfolio-tracker'),
                'cryptocurrency' => __('Criptomoneda', 'crypto-portfolio-tracker'),
                'type' => __('Tipo', 'crypto-portfolio-tracker'),
                'date' => __('Fecha', 'crypto-portfolio-tracker'),
                'buy' => __('Compra', 'crypto-portfolio-tracker'),
                'sell' => __('Venta', 'crypto-portfolio-tracker'),
                'price_per_unit' => __('Precio por Unidad ($)', 'crypto-portfolio-tracker'),
                'price_help' => __('Precio de la crypto en ese momento', 'crypto-portfolio-tracker'),
                'exact_quantity' => __('Cantidad Exacta Recibida', 'crypto-portfolio-tracker'),
                'quantity_help' => __('Cantidad exacta que recibiste (según tu exchange)', 'crypto-portfolio-tracker'),
                'total_invested' => __('Monto Total Invertido ($)', 'crypto-portfolio-tracker'),
                'amount_help' => __('Monto total que gastaste (incluyendo fees)', 'crypto-portfolio-tracker'),
                'verification' => __('Verificación: ', 'crypto-portfolio-tracker'),
                'fee_note' => __('💡 El monto total puede ser diferente debido a fees del exchange', 'crypto-portfolio-tracker'),
                'exchange_optional' => __('Exchange (opcional)', 'crypto-portfolio-tracker'),
                'notes_optional' => __('Notas (opcional)', 'crypto-portfolio-tracker'),
                'notes_placeholder' => __('Notas adicionales...', 'crypto-portfolio-tracker'),
                'update_transaction' => __('Actualizar Transacción', 'crypto-portfolio-tracker'),
                'amount' => __('Monto', 'crypto-portfolio-tracker'),
                'invested' => __('Invertido', 'crypto-portfolio-tracker'),
                'avg_price' => __('Precio Promedio', 'crypto-portfolio-tracker'),
                'current_price' => __('Precio Actual', 'crypto-portfolio-tracker'),
                'current_value' => __('Valor Actual', 'crypto-portfolio-tracker'),
                'quantity' => __('Cantidad', 'crypto-portfolio-tracker'),
                'price' => __('Precio', 'crypto-portfolio-tracker'),
                'total' => __('Total', 'crypto-portfolio-tracker'),
                'actions' => __('Acciones', 'crypto-portfolio-tracker'),
                'value' => __('Valor', 'crypto-portfolio-tracker'),
                'crypto' => __('Crypto', 'crypto-portfolio-tracker'),
                'no_data' => __('No hay datos para mostrar', 'crypto-portfolio-tracker'),
                'loading' => __('Cargando portfolio...', 'crypto-portfolio-tracker'),
                'charts_loading' => __('Los gráficos se están cargando... Si no aparecen, recarga la página.', 'crypto-portfolio-tracker'),
                
                // AÑADIDOS: Mensajes de error específicos para debugging
                'error_loading_portfolio' => __('Error al cargar datos del portafolio', 'crypto-portfolio-tracker'),
                'error_api_connection' => __('No se pudo conectar a la API', 'crypto-portfolio-tracker'),
                'error_insufficient_permissions' => __('No tienes permisos para ver este contenido', 'crypto-portfolio-tracker'),
                'error_not_logged_in' => __('Debes estar conectado para acceder a esta función', 'crypto-portfolio-tracker'),
            )
        ));

        // Styles
        wp_enqueue_style(
            'crypto-dashboard-css',
            CPT_PLUGIN_URL . 'assets/css/dashboard.css',
            array(),
            CPT_VERSION
        );
        
        // Debug: Log que los scripts se están cargando
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CPT: Scripts enqueued for user ID: ' . get_current_user_id() . ', can read: ' . (current_user_can('read') ? 'yes' : 'no'));
        }
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'crypto-portfolio') !== false) {
            wp_enqueue_script('crypto-admin-js', CPT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CPT_VERSION, true);
            wp_enqueue_style('crypto-admin-css', CPT_PLUGIN_URL . 'assets/css/admin.css', array(), CPT_VERSION);
        }
    }

    public function register_api_routes() {
        if (class_exists('CPT_API_Handler')) {
            $api_handler = new CPT_API_Handler();
            $api_handler->register_routes();
        }
    }

    /**
     * FUNCIÓN CORREGIDA: Shortcode manteniendo ID correcto y asegurando carga de scripts
     */
    public function render_dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'public'  => 'false',
        ), $atts);

        // Si no está logueado y no es público, mostrar formulario de login
        if (!is_user_logged_in() && $atts['public'] !== 'true') {
            return $this->render_login_form();
        }

        // CAMBIO IMPORTANTE: Asegurar que los scripts se cargan cuando se renderiza el shortcode
        $this->enqueue_scripts();

        // Contenedor para React con ID CORRECTO que busca el JavaScript
        $container_atts = array(
            'id' => 'crypto-portfolio-dashboard', // ID CORRECTO que busca dashboard.js
            'data-user-id' => esc_attr($atts['user_id']),
            'data-logged-in' => is_user_logged_in() ? '1' : '0',
            'data-can-read' => current_user_can('read') ? '1' : '0',
            'data-plugin-version' => CPT_VERSION,
        );

        $container_html = '<div';
        foreach ($container_atts as $key => $value) {
            $container_html .= ' ' . $key . '="' . esc_attr($value) . '"';
        }
        $container_html .= '></div>';

        return $container_html;
    }

    private function render_login_form() {
        ob_start(); ?>
        <div class="crypto-login-wrapper">
            <h3><?php esc_html_e('Acceso a tu Portafolio Crypto', 'crypto-portfolio-tracker'); ?></h3>
            <p><?php esc_html_e('Inicia sesión o regístrate para gestionar tu portafolio de criptomonedas.', 'crypto-portfolio-tracker'); ?></p>

            <div class="crypto-auth-buttons">
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="button button-primary">
                    <?php esc_html_e('Iniciar Sesión', 'crypto-portfolio-tracker'); ?>
                </a>
                <?php if (get_option('users_can_register')): ?>
                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="button">
                    <?php esc_html_e('Registrarse', 'crypto-portfolio-tracker'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <style>
        .crypto-login-wrapper { 
            text-align: center; 
            padding: 2rem; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            background: #f9f9f9; 
            margin: 2rem 0;
        }
        .crypto-auth-buttons { 
            margin-top: 1rem; 
        }
        .crypto-auth-buttons .button { 
            margin: 0 0.5rem; 
        }
        </style>
        <?php
        return ob_get_clean();
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Crypto Portfolio', 'crypto-portfolio-tracker'),
            __('Crypto Portfolio', 'crypto-portfolio-tracker'),
            'manage_options',
            'crypto-portfolio-tracker',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );

        add_submenu_page(
            'crypto-portfolio-tracker',
            __('Configuración', 'crypto-portfolio-tracker'),
            __('Configuración', 'crypto-portfolio-tracker'),
            'manage_options',
            'crypto-portfolio-settings',
            array($this, 'settings_page')
        );
    }

    public function admin_page() {
        $file = CPT_PLUGIN_PATH . 'admin/dashboard-admin.php';
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Crypto Portfolio', 'crypto-portfolio-tracker') . '</h1><p>' . esc_html__('Archivo del dashboard de administración no encontrado.', 'crypto-portfolio-tracker') . '</p></div>';
        }
    }

    public function settings_page() {
        $file = CPT_PLUGIN_PATH . 'admin/settings.php';
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Configuración', 'crypto-portfolio-tracker') . '</h1><p>' . esc_html__('Archivo de configuración no encontrado.', 'crypto-portfolio-tracker') . '</p></div>';
        }
    }
}

// === Plugin Activation/Deactivation ===

register_activation_hook(__FILE__, function() {
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Set activation flag
    update_option('cpt_plugin_activated', true);
    update_option('cpt_activation_time', current_time('timestamp'));
});

register_deactivation_hook(__FILE__, function() {
    // Clear any scheduled events
    wp_clear_scheduled_hook('cpt_daily_cache_cleanup');
    
    // Flush rewrite rules
    flush_rewrite_rules();
});

// === Initialize Plugin ===
function cpt_init() {
    return CryptoPortfolioTracker::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'cpt_init', 5); // Priority 5 to load early but after basic WordPress