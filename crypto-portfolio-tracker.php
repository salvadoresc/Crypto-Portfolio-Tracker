<?php
/**
 * Plugin Name: Crypto Portfolio Tracker
 * Plugin URI: https://github.com/tuusuario/crypto-portfolio-tracker
 * Description: Dashboard completo para análisis de inversiones crypto con soporte multi-usuario
 * Version: 1.0.0
 * Author: Tu Nombre
 * License: GPL v2 or later
 * Text Domain: crypto-portfolio-tracker
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// === Constantes del plugin ===
define('CPT_VERSION', '1.0.0');
define('CPT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CPT_PLUGIN_URL',  plugin_dir_url(__FILE__));

/**
 * Clase principal
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
        // Carga temprana de dependencias para el runtime normal
        $this->load_dependencies();

        // Enganches principales
        add_action('init', array($this, 'init')); // punto central de arranque
        add_action('plugins_loaded', array($this, 'plugins_loaded'));

        // Admin
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // REST
        add_action('rest_api_init', array($this, 'register_api_routes'));

        // Front
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Shortcode
        add_shortcode('crypto_dashboard', array($this, 'render_dashboard_shortcode'));
    }

    public function plugins_loaded() {
        // Aquí podrías cargar traducciones, etc.
        // load_plugin_textdomain('crypto-portfolio-tracker', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function init() {
        // Asegura tablas en runtime por si falló la activación
        $this->check_and_create_tables();
    }

    private function load_dependencies() {
        // Carga segura de dependencias
        $files = array(
            'includes/class-database.php',
            'includes/class-api-handler.php',
            'includes/class-user-portfolio.php',
            'includes/class-coingecko-api.php',
        );

        foreach ($files as $rel) {
            $path = CPT_PLUGIN_PATH . $rel;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    private function check_and_create_tables() {
        $tables_created = get_option('cpt_tables_created', false);
        $db_version     = get_option('cpt_db_version', '0');

        if (!$tables_created || $db_version !== CPT_VERSION) {
            global $wpdb;

            $portfolio_table    = $wpdb->prefix . 'cpt_portfolio';
            $transactions_table = $wpdb->prefix . 'cpt_transactions';
            $watchlist_table    = $wpdb->prefix . 'cpt_watchlist';

            $portfolio_exists    = ($wpdb->get_var("SHOW TABLES LIKE '{$portfolio_table}'") === $portfolio_table);
            $transactions_exists = ($wpdb->get_var("SHOW TABLES LIKE '{$transactions_table}'") === $transactions_table);
            $watchlist_exists    = ($wpdb->get_var("SHOW TABLES LIKE '{$watchlist_table}'") === $watchlist_table);

            if (!$portfolio_exists || !$transactions_exists || !$watchlist_exists) {
                // Carga explícita para asegurar la clase DB
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                require_once CPT_PLUGIN_PATH . 'includes/class-database.php';

                if (class_exists('CPT_Database')) {
                    $db = new CPT_Database();
                    $db->create_tables();

                    update_option('cpt_tables_created', true);
                    update_option('cpt_db_version', CPT_VERSION);
                }
            } else {
                // Si existen pero la versión cambió, permite futuras migraciones (no implementadas aquí)
                update_option('cpt_tables_created', true);
                update_option('cpt_db_version', CPT_VERSION);
            }
        }
    }

    public function enqueue_scripts() {
        // Solo cargar en páginas que contengan el shortcode o para usuarios logueados
        global $post;
        $load_scripts = false;

        // Verificar si estamos en una página con el shortcode
        if (is_singular() && $post && has_shortcode($post->post_content, 'crypto_dashboard')) {
            $load_scripts = true;
        }

        // O si el usuario está logueado (para cargar en cualquier página)
        if (is_user_logged_in()) {
            $load_scripts = true;
        }

        if ($load_scripts) {
            // Dependencias de WordPress
            wp_enqueue_script('wp-element');
            wp_enqueue_script('wp-api-fetch');
            wp_enqueue_script('wp-url');

                // 1) PropTypes UMD
                wp_enqueue_script(
                  'prop-types',
                  'https://cdnjs.cloudflare.com/ajax/libs/prop-types/15.8.1/prop-types.min.js',
                  array(), null, true
                );

                // 2) Recharts UMD (v2.x)
                wp_enqueue_script(
                  'recharts',
                  'https://cdnjs.cloudflare.com/ajax/libs/recharts/2.12.7/Recharts.min.js',
                  array('wp-element','prop-types'), // importante: depende de wp-element y prop-types
                  null, true
                );

                // 3) Shim: exponer React/ReactDOM globales para libs UMD
                wp_add_inline_script(
                  'recharts',
                  'window.React = window.React || (window.wp && wp.element);',
                  'before'
                );


                // 4) Tu dashboard
                wp_enqueue_script(
                  'cpt-dashboard',
                  plugins_url('assets/js/dashboard.js', __FILE__),
                  array('wp-element','wp-api-fetch','recharts'),
                  '1.0.0',
                  true
                );

                wp_localize_script('cpt-dashboard', 'cptAjax', array(
                  'nonce'       => wp_create_nonce('wp_rest'),
                  'isLoggedIn'  => is_user_logged_in(),
                  'loginUrl'    => wp_login_url(),
                  'registerUrl' => function_exists('wp_registration_url') ? wp_registration_url() : wp_login_url()
                ));



            // Estilos
            wp_enqueue_style(
                'crypto-dashboard-css',
                CPT_PLUGIN_URL . 'assets/css/dashboard.css',
                array(),
                CPT_VERSION
            );

            // Variables para JavaScript
            wp_localize_script('crypto-dashboard-react', 'cptAjax', array(
                'ajaxUrl'    => admin_url('admin-ajax.php'),
                'restUrl'    => rest_url('crypto-portfolio/v1/'),
                'nonce'      => wp_create_nonce('wp_rest'),
                'userId'     => get_current_user_id(),
                'isLoggedIn' => is_user_logged_in(),
                'loginUrl'   => wp_login_url(get_permalink()),
                'registerUrl'=> wp_registration_url(),
                'pluginUrl'  => CPT_PLUGIN_URL,
                'debug'      => defined('WP_DEBUG') && WP_DEBUG
            ));
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

    public function render_dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'public'  => 'false',
        ), $atts);

        // Si no está logueado y no es público, mostrar formulario de login
        if (!is_user_logged_in() && $atts['public'] !== 'true') {
            return $this->render_login_form();
        }

        // Asegurar que los scripts se cargas
        $this->enqueue_scripts();

        // Contenedor para React
        return '<div id="crypto-portfolio-dashboard" data-user-id="' . esc_attr($atts['user_id']) . '"></div>';
    }

    private function render_login_form() {
        ob_start(); ?>
        <div class="crypto-login-wrapper">
            <h3><?php _e('Accede a tu Portfolio Crypto', 'crypto-portfolio-tracker'); ?></h3>
            <p><?php _e('Inicia sesión o regístrate para gestionar tu portfolio de criptomonedas.', 'crypto-portfolio-tracker'); ?></p>

            <div class="crypto-auth-buttons">
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="button button-primary">
                    <?php _e('Iniciar Sesión', 'crypto-portfolio-tracker'); ?>
                </a>
                <?php if (get_option('users_can_register')): ?>
                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="button">
                    <?php _e('Registrarse', 'crypto-portfolio-tracker'); ?>
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
            echo '<div class="wrap"><h1>Crypto Portfolio</h1><p>Archivo admin/dashboard-admin.php no encontrado.</p></div>';
        }
    }

    public function settings_page() {
        $file = CPT_PLUGIN_PATH . 'admin/settings.php';
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="wrap"><h1>Configuración</h1><p>Archivo admin/settings.php no encontrado.</p></div>';
        }
    }
}

/**
 * Inicializa singleton
 */
function init_crypto_portfolio_tracker_singleton() {
    return CryptoPortfolioTracker::get_instance();
}
add_action('plugins_loaded', 'init_crypto_portfolio_tracker_singleton');


// ======================================================================
// ============== ACTIVACIÓN / DESACTIVACIÓN A NIVEL DE ARCHIVO =========
// ======================================================================

/**
 * Activación del plugin
 * - Crea tablas con dbDelta
 * - Crea la página con el shortcode
 * - Maneja activación en red (multisitio)
 */
function cpt_plugin_activate($network_wide) {
    // Asegurar dependencias de DB
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once CPT_PLUGIN_PATH . 'includes/class-database.php';

    if (is_multisite() && $network_wide) {
        $sites = get_sites(array('fields' => 'ids'));
        foreach ($sites as $blog_id) {
            switch_to_blog($blog_id);
            cpt_run_activation_for_blog();
            restore_current_blog();
        }
    } else {
        cpt_run_activation_for_blog();
    }

    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'cpt_plugin_activate');

/**
 * Lógica de activación por sitio (blog)
 */
function cpt_run_activation_for_blog() {
    if (class_exists('CPT_Database')) {
        $db = new CPT_Database();
        $db->create_tables();
    }

    // Crear página del dashboard si no existe
    $page_title   = 'Mi Portfolio Crypto';
    $page_content = '[crypto_dashboard]';
    $page_check   = get_page_by_title($page_title);

    if (!isset($page_check->ID)) {
        $page_id = wp_insert_post(array(
            'post_type'   => 'page',
            'post_title'  => $page_title,
            'post_content'=> $page_content,
            'post_status' => 'publish',
            'post_author' => get_current_user_id() ?: 1,
            'post_name'   => 'crypto-portfolio', // correcto: post_name
        ));

        if ($page_id) {
            update_option('cpt_dashboard_page_id', $page_id);
        }
    }

    update_option('cpt_tables_created', true);
    update_option('cpt_db_version', CPT_VERSION);
}

/**
 * Desactivación del plugin
 */
function cpt_plugin_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'cpt_plugin_deactivate');