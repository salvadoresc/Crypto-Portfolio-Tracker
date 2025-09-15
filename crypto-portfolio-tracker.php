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

        // Frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Shortcode
        add_shortcode('crypto_dashboard', array($this, 'render_dashboard_shortcode'));

        // Internationalization
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            CPT_TEXT_DOMAIN,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    public function plugins_loaded() {
        // Load translations - this is where we can add more functionality
    }

    public function init() {
        // Ensure tables in runtime in case activation failed
        $this->check_and_create_tables();
    }

    private function load_dependencies() {
        // Safe loading of dependencies
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
                // Explicit loading to ensure DB class
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

    public function enqueue_scripts() {
        // Only load on pages containing the shortcode or for logged in users
        global $post;
        $load_scripts = false;

        // Check if we're on a page with the shortcode
        if (is_singular() && $post && has_shortcode($post->post_content, 'crypto_dashboard')) {
            $load_scripts = true;
        }

        // Or if user is logged in (to load on any page)
        if (is_user_logged_in()) {
            $load_scripts = true;
        }

        if ($load_scripts) {
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

            // 2) Recharts UMD (v2.x)
            wp_enqueue_script(
                'recharts',
                'https://cdnjs.cloudflare.com/ajax/libs/recharts/2.12.7/Recharts.min.js',
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

            wp_localize_script('cpt-dashboard', 'cptAjax', array(
                'nonce'       => wp_create_nonce('wp_rest'),
                'isLoggedIn'  => is_user_logged_in(),
                'loginUrl'    => wp_login_url(),
                'registerUrl' => function_exists('wp_registration_url') ? wp_registration_url() : wp_login_url(),
                'strings'     => array(
                    'login_required' => __('Access Required', CPT_TEXT_DOMAIN),
                    'login_message' => __('You need to log in to view your cryptocurrency portfolio.', CPT_TEXT_DOMAIN),
                    'login_button' => __('Log In', CPT_TEXT_DOMAIN),
                    'register_button' => __('Register', CPT_TEXT_DOMAIN),
                    'loading' => __('Loading portfolio...', CPT_TEXT_DOMAIN),
                    'add_transaction' => __('Add Transaction', CPT_TEXT_DOMAIN),
                    'cancel' => __('Cancel', CPT_TEXT_DOMAIN),
                    'refresh_prices' => __('Refresh Prices', CPT_TEXT_DOMAIN),
                    'dashboard_title' => __('Crypto Investment Dashboard', CPT_TEXT_DOMAIN),
                    'dashboard_subtitle' => __('Complete analysis of your crypto portfolio', CPT_TEXT_DOMAIN),
                    'total_investment' => __('Total Investment', CPT_TEXT_DOMAIN),
                    'current_value' => __('Current Value', CPT_TEXT_DOMAIN),
                    'total_pnl' => __('Total P&L', CPT_TEXT_DOMAIN),
                    'roi_percent' => __('ROI %', CPT_TEXT_DOMAIN),
                    'investment_evolution' => __('Investment Evolution', CPT_TEXT_DOMAIN),
                    'portfolio_distribution' => __('Portfolio Distribution', CPT_TEXT_DOMAIN),
                    'performance_per_crypto' => __('Performance per Crypto', CPT_TEXT_DOMAIN),
                    'crypto_detail' => __('Crypto Detail', CPT_TEXT_DOMAIN),
                    'transaction_history' => __('Transaction History', CPT_TEXT_DOMAIN),
                    'empty_portfolio_title' => __('Empty Portfolio', CPT_TEXT_DOMAIN),
                    'empty_portfolio_message' => __('Start by adding your first transaction to see your portfolio in action!', CPT_TEXT_DOMAIN),
                    'add_first_transaction' => __('Add First Transaction', CPT_TEXT_DOMAIN),
                )
            ));

            // Styles
            wp_enqueue_style(
                'crypto-dashboard-css',
                CPT_PLUGIN_URL . 'assets/css/dashboard.css',
                array(),
                CPT_VERSION
            );
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

        // If not logged in and not public, show login form
        if (!is_user_logged_in() && $atts['public'] !== 'true') {
            return $this->render_login_form();
        }

        // Ensure scripts load
        $this->enqueue_scripts();

        // Container for React
        return '<div id="crypto-portfolio-dashboard" data-user-id="' . esc_attr($atts['user_id']) . '"></div>';
    }

    private function render_login_form() {
        ob_start(); ?>
        <div class="crypto-login-wrapper">
            <h3><?php esc_html_e('Access to your Crypto Portfolio', CPT_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('Log in or register to manage your cryptocurrency portfolio.', CPT_TEXT_DOMAIN); ?></p>

            <div class="crypto-auth-buttons">
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="button button-primary">
                    <?php esc_html_e('Log In', CPT_TEXT_DOMAIN); ?>
                </a>
                <?php if (get_option('users_can_register')): ?>
                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="button">
                    <?php esc_html_e('Register', CPT_TEXT_DOMAIN); ?>
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
            __('Crypto Portfolio', CPT_TEXT_DOMAIN),
            __('Crypto Portfolio', CPT_TEXT_DOMAIN),
            'manage_options',
            'crypto-portfolio-tracker',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );

        add_submenu_page(
            'crypto-portfolio-tracker',
            __('Settings', CPT_TEXT_DOMAIN),
            __('Settings', CPT_TEXT_DOMAIN),
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
            echo '<div class="wrap"><h1>' . esc_html__('Crypto Portfolio', CPT_TEXT_DOMAIN) . '</h1><p>' . esc_html__('Admin dashboard file not found.', CPT_TEXT_DOMAIN) . '</p></div>';
        }
    }

    public function settings_page() {
        $file = CPT_PLUGIN_PATH . 'admin/settings.php';
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Settings', CPT_TEXT_DOMAIN) . '</h1><p>' . esc_html__('Settings file not found.', CPT_TEXT_DOMAIN) . '</p></div>';
        }
    }
}

/**
 * Initialize singleton
 */
function init_crypto_portfolio_tracker_singleton() {
    return CryptoPortfolioTracker::get_instance();
}
add_action('plugins_loaded', 'init_crypto_portfolio_tracker_singleton');

// ======================================================================
// ============== ACTIVATION / DEACTIVATION AT FILE LEVEL =============
// ======================================================================

/**
 * Plugin activation
 * - Creates tables with dbDelta
 * - Creates page with shortcode
 * - Handles network activation (multisite)
 */
function cpt_plugin_activate($network_wide) {
    // Ensure DB dependencies
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
 * Activation logic per site (blog)
 */
function cpt_run_activation_for_blog() {
    if (class_exists('CPT_Database')) {
        $db = new CPT_Database();
        $db->create_tables();
    }

    // Create dashboard page if it doesn't exist
    $page_title   = __('My Crypto Portfolio', CPT_TEXT_DOMAIN);
    $page_content = '[crypto_dashboard]';
    $page_check   = get_page_by_title($page_title);

    if (!isset($page_check->ID)) {
        $page_id = wp_insert_post(array(
            'post_type'   => 'page',
            'post_title'  => $page_title,
            'post_content'=> $page_content,
            'post_status' => 'publish',
            'post_author' => get_current_user_id() ?: 1,
            'post_name'   => 'crypto-portfolio',
        ));

        if ($page_id) {
            update_option('cpt_dashboard_page_id', $page_id);
        }
    }

    update_option('cpt_tables_created', true);
    update_option('cpt_db_version', CPT_VERSION);
}

/**
 * Plugin deactivation
 */
function cpt_plugin_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'cpt_plugin_deactivate');

/**
 * Plugin uninstall - clean up data
 * This function will be called from uninstall.php
 */
function cpt_plugin_uninstall() {
    global $wpdb;
    
    // Only clean up if user specifically wants to delete data
    $delete_data = get_option('cpt_delete_data_on_uninstall', false);
    
    if ($delete_data) {
        // Delete tables
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cpt_portfolio");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cpt_transactions");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cpt_watchlist");
        
        // Delete options
        delete_option('cpt_tables_created');
        delete_option('cpt_db_version');
        delete_option('cpt_settings');
        delete_option('cpt_dashboard_page_id');
        delete_option('cpt_delete_data_on_uninstall');
        
        // Clear transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cpt_api_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cpt_api_%'");
    }
}