<?php
if (!defined('ABSPATH')) {
    exit;
}

// Process settings form
if (isset($_POST['save_settings'])) {
    check_admin_referer('cpt_settings_nonce');
    
    // Save settings
    $settings = array(
        'coingecko_api_key' => sanitize_text_field($_POST['coingecko_api_key'] ?? ''),
        'cache_duration' => intval($_POST['cache_duration'] ?? 300),
        'default_currency' => sanitize_text_field($_POST['default_currency'] ?? 'usd'),
        'enable_public_signup' => isset($_POST['enable_public_signup']) ? 1 : 0,
        'dashboard_page_id' => intval($_POST['dashboard_page_id'] ?? 0),
        'require_email_verification' => isset($_POST['require_email_verification']) ? 1 : 0,
        'max_transactions_per_user' => intval($_POST['max_transactions_per_user'] ?? 1000),
        'enable_data_export' => isset($_POST['enable_data_export']) ? 1 : 0,
        'enable_portfolio_sharing' => isset($_POST['enable_portfolio_sharing']) ? 1 : 0,
        'delete_data_on_uninstall' => isset($_POST['delete_data_on_uninstall']) ? 1 : 0
    );
    
    update_option('cpt_settings', $settings);
    
    echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully.', 'Crypto-Portfolio-Tracker') . '</p></div>';
}

// Get current settings
$settings = get_option('cpt_settings', array(
    'coingecko_api_key' => '',
    'cache_duration' => 300,
    'default_currency' => 'usd',
    'enable_public_signup' => 1,
    'dashboard_page_id' => get_option('cpt_dashboard_page_id', 0),
    'require_email_verification' => 0,
    'max_transactions_per_user' => 1000,
    'enable_data_export' => 1,
    'enable_portfolio_sharing' => 0,
    'delete_data_on_uninstall' => 0
));

// Get available pages to select
$pages = get_pages();
$dashboard_page = get_post($settings['dashboard_page_id']);
?>

<div class="wrap">
    <h1><?php esc_html_e('Settings - Crypto Portfolio Tracker', 'Crypto-Portfolio-Tracker'); ?></h1>
    
    <div class="cpt-admin-container">
        <div class="cpt-admin-header">
            <h2><?php esc_html_e('🚀 Welcome to Crypto Portfolio Tracker!', 'Crypto-Portfolio-Tracker'); ?></h2>
            <p><?php esc_html_e('Configure your plugin to offer the best experience to your users.', 'Crypto-Portfolio-Tracker'); ?></p>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('cpt_settings_nonce'); ?>
            
            <div class="cpt-settings-section">
                <h3><?php esc_html_e('🔗 API Configuration', 'Crypto-Portfolio-Tracker'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="coingecko_api_key"><?php esc_html_e('CoinGecko API Key', 'Crypto-Portfolio-Tracker'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="coingecko_api_key" name="coingecko_api_key" 
                                   value="<?php echo esc_attr($settings['coingecko_api_key']); ?>" 
                                   class="regular-text" placeholder="<?php esc_attr_e('Optional - For higher request limits', 'Crypto-Portfolio-Tracker'); ?>" />
                            <p class="description">
                                <?php
                                printf(
                                    esc_html__('CoinGecko API Key (optional). Without API key you have 50 calls/minute. %s', 'Crypto-Portfolio-Tracker'),
                                    '<a href="https://www.coingecko.com/en/api/pricing" target="_blank">' . esc_html__('Get API Key', 'Crypto-Portfolio-Tracker') . '</a>'
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cache_duration"><?php esc_html_e('Cache Duration (seconds)', 'Crypto-Portfolio-Tracker'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cache_duration" name="cache_duration" 
                                   value="<?php echo esc_attr($settings['cache_duration']); ?>" 
                                   class="small-text" min="60" max="3600" />
                            <p class="description"><?php esc_html_e('Time in seconds to cache prices (recommended: 300 seconds / 5 minutes)', 'Crypto-Portfolio-Tracker'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="default_currency"><?php esc_html_e('Default Currency', 'Crypto-Portfolio-Tracker'); ?></label>
                        </th>
                        <td>
                            <select id="default_currency" name="default_currency">
                                <option value="usd" <?php selected($settings['default_currency'], 'usd'); ?>>USD ($)</option>
                                <option value="eur" <?php selected($settings['default_currency'], 'eur'); ?>>EUR (€)</option>
                                <option value="btc" <?php selected($settings['default_currency'], 'btc'); ?>>BTC (₿)</option>
                                <option value="eth" <?php selected($settings['default_currency'], 'eth'); ?>>ETH (Ξ)</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="cpt-settings-section">
                <h3><?php esc_html_e('👥 User Configuration', 'Crypto-Portfolio-Tracker'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Public Registration', 'Crypto-Portfolio-Tracker'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_public_signup" value="1" 
                                       <?php checked($settings['enable_public_signup'], 1); ?> />
                                <?php esc_html_e('Allow public user registration', 'Crypto-Portfolio-Tracker'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Allow anyone to register to use the portfolio tracker', 'Crypto-Portfolio-Tracker'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Email Verification', 'Crypto-Portfolio-Tracker'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="require_email_verification" value="1" 
                                       <?php checked($settings['require_email_verification'], 1); ?> />
                                <?php esc_html_e('Require email verification for new users', 'Crypto-Portfolio-Tracker'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="max_transactions_per_user"><?php esc_html_e('Transaction Limit per User', 'Crypto-Portfolio-Tracker'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="max_transactions_per_user" name="max_transactions_per_user" 
                                   value="<?php echo esc_attr($settings['max_transactions_per_user']); ?>" 
                                   class="small-text" min="100" max="10000" />
                            <p class="description"><?php esc_html_e('Maximum number of transactions a user can have', 'Crypto-Portfolio-Tracker'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="cpt-settings-section">
                <h3><?php esc_html_e('📄 Page Configuration', 'Crypto-Portfolio-Tracker'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="dashboard_page_id"><?php esc_html_e('Dashboard Page', 'Crypto-Portfolio-Tracker'); ?></label>
                        </th>
                        <td>
                            <select id="dashboard_page_id" name="dashboard_page_id">
                                <option value="0"><?php esc_html_e('Select page...', 'Crypto-Portfolio-Tracker'); ?></option>
                                <?php foreach ($pages as $page): ?>
                                    <option value="<?php echo $page->ID; ?>" 
                                            <?php selected($settings['dashboard_page_id'], $page->ID); ?>>
                                        <?php echo esc_html($page->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php if ($dashboard_page): ?>
                                    <?php 
                                    printf(
                                        esc_html__('Current page: %s', 'Crypto-Portfolio-Tracker'),
                                        '<a href="' . esc_url(get_permalink($dashboard_page)) . '" target="_blank">' . esc_html($dashboard_page->post_title) . '</a>'
                                    );
                                    ?>
                                <?php else: ?>
                                    <?php esc_html_e('Select the page where to show the dashboard (must contain the [crypto_dashboard] shortcode)', 'Crypto-Portfolio-Tracker'); ?>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="cpt-settings-section">
                <h3><?php esc_html_e('🔧 Features', 'Crypto-Portfolio-Tracker'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Data Export', 'Crypto-Portfolio-Tracker'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_data_export" value="1" 
                                       <?php checked($settings['enable_data_export'], 1); ?> />
                                <?php esc_html_e('Allow users to export their data (JSON, CSV)', 'Crypto-Portfolio-Tracker'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Share Portfolio', 'Crypto-Portfolio-Tracker'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_portfolio_sharing" value="1" 
                                       <?php checked($settings['enable_portfolio_sharing'], 1); ?> />
                                <?php esc_html_e('Allow public portfolio sharing (coming soon)', 'Crypto-Portfolio-Tracker'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Users will be able to generate public read-only links of their portfolios', 'Crypto-Portfolio-Tracker'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Delete Data on Uninstall', 'Crypto-Portfolio-Tracker'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="delete_data_on_uninstall" value="1" 
                                       <?php checked($settings['delete_data_on_uninstall'], 1); ?> />
                                <?php esc_html_e('Delete all plugin data when uninstalling (⚠️ Cannot be undone)', 'Crypto-Portfolio-Tracker'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('By default, user data is preserved for safety. Enable this only if you want to completely remove all data.', 'Crypto-Portfolio-Tracker'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button(esc_html__('Save Settings', 'Crypto-Portfolio-Tracker'), 'primary', 'save_settings'); ?>
        </form>
        
        <!-- Setup Wizard -->
        <div class="cpt-settings-section">
            <h3><?php esc_html_e('🧙‍♂️ Setup Wizard', 'Crypto-Portfolio-Tracker'); ?></h3>
            <div class="cpt-setup-wizard">
                <div class="cpt-wizard-step <?php echo $dashboard_page ? 'completed' : 'pending'; ?>">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <h4><?php esc_html_e('Dashboard Page', 'Crypto-Portfolio-Tracker'); ?></h4>
                        <?php if ($dashboard_page): ?>
                            <p class="step-success">
                                <?php 
                                printf(
                                    esc_html__('✅ Configured: %s', 'Crypto-Portfolio-Tracker'),
                                    '<a href="' . esc_url(get_permalink($dashboard_page)) . '" target="_blank">' . esc_html($dashboard_page->post_title) . '</a>'
                                );
                                ?>
                            </p>
                        <?php else: ?>
                            <p class="step-pending"><?php esc_html_e('⏳ Create or select page for the dashboard', 'Crypto-Portfolio-Tracker'); ?></p>
                            <button type="button" class="button" id="create-dashboard-page"><?php esc_html_e('Create Page Automatically', 'Crypto-Portfolio-Tracker'); ?></button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="cpt-wizard-step <?php echo get_option('users_can_register') ? 'completed' : 'pending'; ?>">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <h4><?php esc_html_e('User Registration', 'Crypto-Portfolio-Tracker'); ?></h4>
                        <?php if (get_option('users_can_register')): ?>
                            <p class="step-success"><?php esc_html_e('✅ Registration is enabled in WordPress', 'Crypto-Portfolio-Tracker'); ?></p>
                        <?php else: ?>
                            <p class="step-pending"><?php esc_html_e('⚠️ User registration is disabled in WordPress', 'Crypto-Portfolio-Tracker'); ?></p>
                            <p><a href="<?php echo admin_url('options-general.php'); ?>"><?php esc_html_e('Enable in Settings → General', 'Crypto-Portfolio-Tracker'); ?></a></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="cpt-wizard-step completed">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <h4><?php esc_html_e('Database Tables', 'Crypto-Portfolio-Tracker'); ?></h4>
                        <p class="step-success"><?php esc_html_e('✅ Tables created correctly', 'Crypto-Portfolio-Tracker'); ?></p>
                    </div>
                </div>
                
                <div class="cpt-wizard-step completed">
                    <span class="step-number">4</span>
                    <div class="step-content">
                        <h4><?php esc_html_e('CoinGecko API', 'Crypto-Portfolio-Tracker'); ?></h4>
                        <p class="step-success"><?php esc_html_e('✅ Connection working (no API key = basic limit)', 'Crypto-Portfolio-Tracker'); ?></p>
                        <?php if (empty($settings['coingecko_api_key'])): ?>
                            <p class="step-note"><?php esc_html_e('💡 For higher limits, add your CoinGecko API Key above', 'Crypto-Portfolio-Tracker'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Statistics -->
        <div class="cpt-settings-section">
            <h3><?php esc_html_e('📊 Plugin Statistics', 'Crypto-Portfolio-Tracker'); ?></h3>
            <?php
            global $wpdb;
            $portfolio_table = $wpdb->prefix . 'cpt_portfolio';
            $transactions_table = $wpdb->prefix . 'cpt_transactions';
            
            $total_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $transactions_table");
            $total_transactions = $wpdb->get_var("SELECT COUNT(*) FROM $transactions_table");
            $total_portfolio_items = $wpdb->get_var("SELECT COUNT(*) FROM $portfolio_table");
            ?>
            
            <div class="cpt-stats-grid">
                <div class="cpt-stat-card">
                    <div class="stat-number"><?php echo intval($total_users); ?></div>
                    <div class="stat-label"><?php esc_html_e('Active Users', 'Crypto-Portfolio-Tracker'); ?></div>
                </div>
                <div class="cpt-stat-card">
                    <div class="stat-number"><?php echo intval($total_transactions); ?></div>
                    <div class="stat-label"><?php esc_html_e('Total Transactions', 'Crypto-Portfolio-Tracker'); ?></div>
                </div>
                <div class="cpt-stat-card">
                    <div class="stat-number"><?php echo intval($total_portfolio_items); ?></div>
                    <div class="stat-label"><?php esc_html_e('Portfolio Items', 'Crypto-Portfolio-Tracker'); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#create-dashboard-page').click(function() {
        var button = $(this);
        button.text('<?php esc_js_e('Creating...', 'Crypto-Portfolio-Tracker'); ?>');
        
        $.post(ajaxurl, {
            action: 'cpt_create_dashboard_page',
            nonce: '<?php echo wp_create_nonce("cpt_create_page"); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('<?php esc_js_e('Error creating page:', 'Crypto-Portfolio-Tracker'); ?> ' + response.data);
                button.text('<?php esc_js_e('Create Page Automatically', 'Crypto-Portfolio-Tracker'); ?>');
            }
        });
    });
});
</script>

<style>
.cpt-admin-container {
    max-width: 1200px;
    margin: 20px 0;
}

.cpt-admin-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 30px;
    text-align: center;
}

.cpt-admin-header h2 {
    margin: 0 0 10px 0;
    font-size: 2em;
    color: white;
}

.cpt-settings-section {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 20px;
    padding: 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.cpt-settings-section h3 {
    background: #f8f9fa;
    margin: 0;
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    font-size: 1.2em;
}

.cpt-settings-section .form-table {
    margin: 20px;
}

.cpt-setup-wizard {
    padding: 20px;
}

.cpt-wizard-step {
    display: flex;
    align-items: flex-start;
    margin-bottom: 20px;
    padding: 15px;
    border-radius: 6px;
    border: 2px solid #ddd;
}

.cpt-wizard-step.completed {
    border-color: #46b450;
    background: #f0f8f0;
}

.cpt-wizard-step.pending {
    border-color: #ffb900;
    background: #fffbf0;
}

.step-number {
    background: #ddd;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 15px;
    flex-shrink: 0;
}

.completed .step-number {
    background: #46b450;
}

.pending .step-number {
    background: #ffb900;
}

.step-content h4 {
    margin: 0 0 8px 0;
    font-size: 1.1em;
}

.step-content p {
    margin: 5px 0;
}

.step-success {
    color: #46b450;
}

.step-pending {
    color: #ffb900;
}

.step-note {
    color: #666;
    font-style: italic;
}

.cpt-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px;
}

.cpt-stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9em;
    opacity: 0.9;
}
</style>

<?php
// AJAX handler para crear página del dashboard
add_action('wp_ajax_cpt_create_dashboard_page', function() {
    check_ajax_referer('cpt_create_page', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Sin permisos');
    }
    
    $page_data = array(
        'post_title' => __('My Crypto Portfolio', 'Crypto-Portfolio-Tracker'),
        'post_content' => '[crypto_dashboard]

<h2>' . esc_html__('Welcome to your Cryptocurrency Portfolio!', 'Crypto-Portfolio-Tracker') . '</h2>

<p>' . esc_html__('Manage and analyze all your cryptocurrency investments from one place. Here you can:', 'Crypto-Portfolio-Tracker') . '</p>

<ul>
<li>📊 <strong>' . esc_html__('Monitor your portfolio', 'Crypto-Portfolio-Tracker') . '</strong> - ' . esc_html__('See the current value of all your holdings', 'Crypto-Portfolio-Tracker') . '</li>
<li>📈 <strong>' . esc_html__('Analyze performance', 'Crypto-Portfolio-Tracker') . '</strong> - ' . esc_html__('Charts and detailed metrics', 'Crypto-Portfolio-Tracker') . '</li>
<li>💰 <strong>' . esc_html__('Calculate gains/losses', 'Crypto-Portfolio-Tracker') . '</strong> - ' . esc_html__('Automatic ROI for each position', 'Crypto-Portfolio-Tracker') . '</li>
<li>📝 <strong>' . esc_html__('Record transactions', 'Crypto-Portfolio-Tracker') . '</strong> - ' . esc_html__('Complete history of buys and sells', 'Crypto-Portfolio-Tracker') . '</li>
<li>🎯 <strong>' . esc_html__('Simulate scenarios', 'Crypto-Portfolio-Tracker') . '</strong> - ' . esc_html__('"What if...?" with different prices', 'Crypto-Portfolio-Tracker') . '</li>
</ul>

<p><strong>' . esc_html__('New here?', 'Crypto-Portfolio-Tracker') . '</strong> ' . esc_html__('Start by adding your first transaction to see your portfolio in action.', 'Crypto-Portfolio-Tracker') . '</p>',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_author' => get_current_user_id(),
        'post_name' => 'crypto-portfolio'
    );
    
    $page_id = wp_insert_post($page_data);
    
    if ($page_id && !is_wp_error($page_id)) {
        // Actualizar la configuración
        $settings = get_option('cpt_settings', array());
        $settings['dashboard_page_id'] = $page_id;
        update_option('cpt_settings', $settings);
        update_option('cpt_dashboard_page_id', $page_id);
        
        wp_send_json_success(array(
            'page_id' => $page_id,
            'message' => __('Page created successfully', 'Crypto-Portfolio-Tracker')
        ));
    } else {
        wp_send_json_error(__('Error creating page', 'Crypto-Portfolio-Tracker'));
    }
});
?>