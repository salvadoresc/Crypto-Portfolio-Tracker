<?php
/**
 * Crypto Portfolio Tracker Uninstall
 * 
 * This file is executed when the plugin is deleted from the admin area.
 * It removes all plugin data including database tables, options, and transients.
 * 
 * @package CryptoPortfolioTracker
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Include the main plugin file to access constants and functions
require_once plugin_dir_path(__FILE__) . 'crypto-portfolio-tracker.php';

/**
 * Clean up all plugin data
 * This will only run if the user specifically chooses to delete data
 */
function cpt_clean_plugin_data() {
    global $wpdb;
    
    // Check if user wants to keep data (default behavior)
    $delete_data = get_option('cpt_delete_data_on_uninstall', false);
    
    if (!$delete_data) {
        // By default, we preserve user data for safety
        return;
    }
    
    // =========================
    // DELETE DATABASE TABLES
    // =========================
    
    $tables = array(
        $wpdb->prefix . 'cpt_portfolio',
        $wpdb->prefix . 'cpt_transactions', 
        $wpdb->prefix . 'cpt_watchlist'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
    
    // =========================
    // DELETE OPTIONS
    // =========================
    
    $options = array(
        'cpt_tables_created',
        'cpt_db_version',
        'cpt_settings',
        'cpt_dashboard_page_id',
        'cpt_delete_data_on_uninstall'
    );
    
    foreach ($options as $option) {
        delete_option($option);
        // Also delete from multisite if applicable
        delete_site_option($option);
    }
    
    // =========================
    // DELETE TRANSIENTS (CACHE)
    // =========================
    
    // Delete all our API cache transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cpt_api_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cpt_api_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cpt_portfolio_prices_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cpt_portfolio_prices_%'");
    
    // For multisite
    if (is_multisite()) {
        $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_transient_cpt_api_%'");
        $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_transient_timeout_cpt_api_%'");
    }
    
    // =========================
    // DELETE USER META
    // =========================
    
    // If we stored any user preferences, clean them up
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'cpt_%'");
    
    // =========================
    // DELETE POSTS (DASHBOARD PAGE)
    // =========================
    
    $dashboard_page_id = get_option('cpt_dashboard_page_id');
    if ($dashboard_page_id) {
        wp_delete_post($dashboard_page_id, true); // Force delete, bypass trash
    }
    
    // Also search for any other pages that might contain our shortcode
    $pages_with_shortcode = get_posts(array(
        'post_type' => 'page',
        'post_status' => 'any',
        'numberposts' => -1,
        's' => '[crypto_dashboard]',
        'meta_query' => array()
    ));
    
    foreach ($pages_with_shortcode as $page) {
        if (has_shortcode($page->post_content, 'crypto_dashboard')) {
            // Only delete if the page ONLY contains our shortcode (to be safe)
            $content = trim(strip_tags($page->post_content));
            if (strpos($content, '[crypto_dashboard]') !== false && strlen($content) < 100) {
                wp_delete_post($page->ID, true);
            }
        }
    }
    
    // =========================
    // CLEAR SCHEDULED EVENTS
    // =========================
    
    // If we had any cron jobs, clear them
    wp_clear_scheduled_hook('cpt_refresh_prices');
    wp_clear_scheduled_hook('cpt_cleanup_cache');
    
    // =========================
    // FLUSH REWRITE RULES
    // =========================
    
    flush_rewrite_rules();
}

/**
 * Handle multisite uninstall
 */
if (is_multisite()) {
    // Get all sites
    $sites = get_sites(array('fields' => 'ids'));
    
    foreach ($sites as $blog_id) {
        switch_to_blog($blog_id);
        cpt_clean_plugin_data();
        restore_current_blog();
    }
} else {
    cpt_clean_plugin_data();
}

/**
 * Log uninstall for debugging (if WP_DEBUG is enabled)
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Crypto Portfolio Tracker: Plugin uninstalled and data cleaned up.');
}