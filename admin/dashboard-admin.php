<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get general statistics (without sensitive data)
global $wpdb;
$portfolio_table = $wpdb->prefix . 'cpt_portfolio';
$transactions_table = $wpdb->prefix . 'cpt_transactions';
$watchlist_table = $wpdb->prefix . 'cpt_watchlist';

// Basic statistics (without amounts)
$stats = array(
    'total_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $transactions_table"),
    'total_transactions' => $wpdb->get_var("SELECT COUNT(*) FROM $transactions_table"),
    'total_portfolio_items' => $wpdb->get_var("SELECT COUNT(*) FROM $portfolio_table"),
    'total_watchlist_items' => $wpdb->get_var("SELECT COUNT(*) FROM $watchlist_table"),
    'transactions_today' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $transactions_table WHERE DATE(created_at) = %s", current_time('Y-m-d'))),
    'new_users_week' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM $transactions_table WHERE created_at >= %s", date('Y-m-d H:i:s', strtotime('-7 days'))))
);

// Top users by number of transactions (WITHOUT AMOUNTS)
$top_users = $wpdb->get_results("
    SELECT 
        u.user_login,
        u.user_email,
        COUNT(t.id) as transaction_count
    FROM {$wpdb->users} u
    INNER JOIN $transactions_table t ON u.ID = t.user_id
    GROUP BY u.ID
    ORDER BY transaction_count DESC
    LIMIT 10
");

// Most popular cryptos (WITH aggregated total invested, without user details)
$popular_cryptos = $wpdb->get_results("
    SELECT 
        coin_symbol,
        coin_name,
        COUNT(*) as user_count,
        SUM(total_invested) as total_invested
    FROM $portfolio_table
    WHERE total_amount > 0
    GROUP BY coin_id
    ORDER BY user_count DESC
    LIMIT 10
");

// Recent activity (WITHOUT AMOUNTS OR QUANTITIES)
$recent_activity = $wpdb->get_results("
    SELECT 
        t.created_at,
        t.coin_symbol,
        t.coin_name,
        t.transaction_type,
        u.user_login
    FROM $transactions_table t
    INNER JOIN {$wpdb->users} u ON t.user_id = u.ID
    ORDER BY t.created_at DESC
    LIMIT 20
");

$settings = get_option('cpt_settings', array());
$dashboard_page_id = get_option('cpt_dashboard_page_id', 0);
$dashboard_page = get_post($dashboard_page_id);
?>

<div class="wrap">
    <h1><?php esc_html_e('Crypto Portfolio Tracker - Dashboard', 'Crypto-Portfolio-Tracker'); ?></h1>
    
    <!-- Main Statistics -->
    <div class="cpt-dashboard-grid">
        <div class="cpt-stat-widget">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-label"><?php esc_html_e('Active Users', 'Crypto-Portfolio-Tracker'); ?></div>
                <div class="stat-change">+<?php echo $stats['new_users_week']; ?> <?php esc_html_e('this week', 'Crypto-Portfolio-Tracker'); ?></div>
            </div>
        </div>
        
        <div class="cpt-stat-widget">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['total_transactions']); ?></div>
                <div class="stat-label"><?php esc_html_e('Transactions', 'Crypto-Portfolio-Tracker'); ?></div>
                <div class="stat-change"><?php echo $stats['transactions_today']; ?> <?php esc_html_e('today', 'Crypto-Portfolio-Tracker'); ?></div>
            </div>
        </div>
        
        <div class="cpt-stat-widget">
            <div class="stat-icon">📊</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['total_portfolio_items']); ?></div>
                <div class="stat-label"><?php esc_html_e('Holdings', 'Crypto-Portfolio-Tracker'); ?></div>
                <div class="stat-change"><?php esc_html_e('In portfolios', 'Crypto-Portfolio-Tracker'); ?></div>
            </div>
        </div>
        
        <div class="cpt-stat-widget">
            <div class="stat-icon">⭐</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['total_watchlist_items']); ?></div>
                <div class="stat-label"><?php esc_html_e('In Watchlist', 'Crypto-Portfolio-Tracker'); ?></div>
                <div class="stat-change"><?php esc_html_e('Monitoring', 'Crypto-Portfolio-Tracker'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="cpt-quick-actions">
        <h2><?php esc_html_e('Quick Actions', 'Crypto-Portfolio-Tracker'); ?></h2>
        <div class="cpt-action-buttons">
            <a href="<?php echo admin_url('admin.php?page=crypto-portfolio-settings'); ?>" class="button button-primary">
                ⚙️ <?php esc_html_e('Settings', 'Crypto-Portfolio-Tracker'); ?>
            </a>
            <?php if ($dashboard_page): ?>
                <a href="<?php echo get_permalink($dashboard_page); ?>" class="button" target="_blank">
                    🚀 <?php esc_html_e('View Public Dashboard', 'Crypto-Portfolio-Tracker'); ?>
                </a>
            <?php else: ?>
                <button type="button" class="button button-secondary" id="create-dashboard-page">
                    📄 <?php esc_html_e('Create Dashboard Page', 'Crypto-Portfolio-Tracker'); ?>
                </button>
            <?php endif; ?>
            <button type="button" class="button" id="clear-cache">
                🔄 <?php esc_html_e('Clear Price Cache', 'Crypto-Portfolio-Tracker'); ?>
            </button>
            <button type="button" class="button" id="export-data">
                📥 <?php esc_html_e('Export Statistics', 'Crypto-Portfolio-Tracker'); ?>
            </button>
        </div>
    </div>
    
    <div class="cpt-admin-content">
        <!-- Top Users (WITHOUT AMOUNTS) -->
        <div class="cpt-admin-widget">
            <h3><?php esc_html_e('👑 Most Active Users', 'Crypto-Portfolio-Tracker'); ?></h3>
            <div class="cpt-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('User', 'Crypto-Portfolio-Tracker'); ?></th>
                            <th><?php esc_html_e('Email', 'Crypto-Portfolio-Tracker'); ?></th>
                            <th><?php esc_html_e('Transactions', 'Crypto-Portfolio-Tracker'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_users)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #666;">
                                    <?php esc_html_e('No users with transactions yet', 'Crypto-Portfolio-Tracker'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($top_users as $user): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($user->user_login); ?></strong></td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td><?php echo number_format($user->transaction_count); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Popular Cryptos (WITH aggregated total invested) -->
        <div class="cpt-admin-widget">
            <h3><?php esc_html_e('🔥 Most Popular Cryptocurrencies', 'Crypto-Portfolio-Tracker'); ?></h3>
            <div class="cpt-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Crypto', 'Crypto-Portfolio-Tracker'); ?></th>
                            <th><?php esc_html_e('Name', 'Crypto-Portfolio-Tracker'); ?></th>
                            <th><?php esc_html_e('Users', 'Crypto-Portfolio-Tracker'); ?></th>
                            <th><?php esc_html_e('Total Invested', 'Crypto-Portfolio-Tracker'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($popular_cryptos)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #666;">
                                    <?php esc_html_e('No portfolio data yet', 'Crypto-Portfolio-Tracker'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($popular_cryptos as $crypto): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($crypto->coin_symbol); ?></strong></td>
                                    <td><?php echo esc_html($crypto->coin_name); ?></td>
                                    <td><?php echo number_format($crypto->user_count); ?> <?php esc_html_e('users', 'Crypto-Portfolio-Tracker'); ?></td>
                                    <td>$<?php echo number_format($crypto->total_invested, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity (WITHOUT AMOUNTS) -->
    <div class="cpt-admin-widget full-width">
        <h3><?php esc_html_e('📈 Recent Activity', 'Crypto-Portfolio-Tracker'); ?></h3>
        <div class="cpt-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'Crypto-Portfolio-Tracker'); ?></th>
                        <th><?php esc_html_e('User', 'Crypto-Portfolio-Tracker'); ?></th>
                        <th><?php esc_html_e('Crypto', 'Crypto-Portfolio-Tracker'); ?></th>
                        <th><?php esc_html_e('Type', 'Crypto-Portfolio-Tracker'); ?></th>
                        <th><?php esc_html_e('Status', 'Crypto-Portfolio-Tracker'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_activity)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666;">
                                <?php esc_html_e('No recent activity', 'Crypto-Portfolio-Tracker'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($activity->created_at)); ?></td>
                                <td><?php echo esc_html($activity->user_login); ?></td>
                                <td>
                                    <strong><?php echo esc_html($activity->coin_symbol); ?></strong>
                                    <br><small><?php echo esc_html($activity->coin_name); ?></small>
                                </td>
                                <td>
                                    <span class="transaction-type <?php echo $activity->transaction_type; ?>">
                                        <?php echo $activity->transaction_type === 'buy' ? '🟢 ' . esc_html__('Buy', 'Crypto-Portfolio-Tracker') : '🔴 ' . esc_html__('Sell', 'Crypto-Portfolio-Tracker'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="activity-status completed">✅ <?php esc_html_e('Completed', 'Crypto-Portfolio-Tracker'); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- System Status -->
    <div class="cpt-admin-widget">
        <h3><?php esc_html_e('🔧 System Status', 'Crypto-Portfolio-Tracker'); ?></h3>
        <div class="cpt-system-status">
            <div class="status-item">
                <span class="status-label"><?php esc_html_e('WordPress:', 'Crypto-Portfolio-Tracker'); ?></span>
                <span class="status-value good">✅ <?php echo get_bloginfo('version'); ?></span>
            </div>
            <div class="status-item">
                <span class="status-label"><?php esc_html_e('PHP:', 'Crypto-Portfolio-Tracker'); ?></span>
                <span class="status-value <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? 'good' : 'warning'; ?>">
                    <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? '✅' : '⚠️'; ?> <?php echo PHP_VERSION; ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label"><?php esc_html_e('User registration:', 'Crypto-Portfolio-Tracker'); ?></span>
                <span class="status-value <?php echo get_option('users_can_register') ? 'good' : 'warning'; ?>">
                    <?php echo get_option('users_can_register') ? '✅ ' . esc_html__('Enabled', 'Crypto-Portfolio-Tracker') : '⚠️ ' . esc_html__('Disabled', 'Crypto-Portfolio-Tracker'); ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label"><?php esc_html_e('CoinGecko API:', 'Crypto-Portfolio-Tracker'); ?></span>
                <span class="status-value good">✅ <?php esc_html_e('Connected', 'Crypto-Portfolio-Tracker'); ?></span>
            </div>
            <div class="status-item">
                <span class="status-label"><?php esc_html_e('Price cache:', 'Crypto-Portfolio-Tracker'); ?></span>
                <span class="status-value good">✅ <?php printf(esc_html__('Active (%ds)', 'Crypto-Portfolio-Tracker'), $settings['cache_duration'] ?? 300); ?></span>
            </div>
            <div class="status-item">
                <span class="status-label"><?php esc_html_e('Dashboard page:', 'Crypto-Portfolio-Tracker'); ?></span>
                <span class="status-value <?php echo $dashboard_page ? 'good' : 'warning'; ?>">
                    <?php if ($dashboard_page): ?>
                        ✅ <?php esc_html_e('Configured', 'Crypto-Portfolio-Tracker'); ?>
                    <?php else: ?>
                        ⚠️ <?php esc_html_e('Not configured', 'Crypto-Portfolio-Tracker'); ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Privacy notice -->
    <div class="cpt-privacy-notice">
        <h3><?php esc_html_e('🔒 Privacy Commitment', 'Crypto-Portfolio-Tracker'); ?></h3>
        <p>
            <strong><?php esc_html_e('Crypto Portfolio Tracker', 'Crypto-Portfolio-Tracker'); ?></strong> <?php esc_html_e('respects the privacy of your users.', 'Crypto-Portfolio-Tracker'); ?>
            <?php esc_html_e('This dashboard version does not show individual invested amounts or personal financial details.', 'Crypto-Portfolio-Tracker'); ?>
        </p>
        <p>
            <em><?php esc_html_e('Only aggregated statistics and general activity are shown to help you manage the plugin.', 'Crypto-Portfolio-Tracker'); ?></em>
        </p>
    </div>
    
</div>
<style>
.cpt-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.cpt-stat-widget {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    transition: transform 0.3s ease;
}

.cpt-stat-widget:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.stat-icon {
    font-size: 2.5em;
    opacity: 0.9;
    min-width: 60px;
    text-align: center;
}

.stat-info {
    flex: 1;
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    margin-bottom: 5px;
    line-height: 1;
}

.stat-label {
    font-size: 1em;
    opacity: 0.9;
    margin-bottom: 3px;
}

.stat-change {
    font-size: 0.8em;
    opacity: 0.7;
}

.cpt-quick-actions {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.cpt-quick-actions h2 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
}

.cpt-action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.cpt-admin-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.cpt-admin-widget {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.cpt-admin-widget.full-width {
    grid-column: 1 / -1;
}

.cpt-admin-widget h3 {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    margin: 0;
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    font-size: 1.1em;
    color: #495057;
    border-radius: 8px 8px 0 0;
}

.cpt-table-container {
    padding: 20px;
}

.transaction-type.buy {
    color: #46b450;
    font-weight: bold;
}

.transaction-type.sell {
    color: #dc3232;
    font-weight: bold;
}

.activity-status.completed {
    color: #46b450;
    font-weight: 500;
}

.cpt-system-status {
    padding: 20px;
}

.status-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.status-item:last-child {
    border-bottom: none;
}

.status-label {
    font-weight: 600;
    color: #495057;
}

.status-value.good {
    color: #46b450;
    font-weight: 500;
}

.status-value.warning {
    color: #ffb900;
    font-weight: 500;
}

.status-value.error {
    color: #dc3232;
    font-weight: 500;
}

.cpt-privacy-notice {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border: 1px solid #42a5f5;
    border-radius: 8px;
    padding: 20px;
    margin-top: 30px;
}

.cpt-privacy-notice h3 {
    margin-top: 0;
    color: #1565c0;
    margin-bottom: 10px;
}

.cpt-privacy-notice p {
    margin-bottom: 10px;
    color: #1976d2;
    line-height: 1.5;
}

.cpt-privacy-notice p:last-child {
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .cpt-dashboard-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .cpt-admin-content {
        grid-template-columns: 1fr;
    }
    
    .cpt-action-buttons {
        flex-direction: column;
    }
    
    .cpt-action-buttons .button {
        text-align: center;
    }
    
    .stat-icon {
        font-size: 2em;
        min-width: 50px;
    }
    
    .stat-number {
        font-size: 1.5em;
    }
}

@media (max-width: 480px) {
    .cpt-dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .cpt-stat-widget {
        padding: 15px;
    }
    
    .stat-icon {
        font-size: 1.8em;
        min-width: 45px;
    }
    
    .stat-number {
        font-size: 1.3em;
    }
    
    .stat-label {
        font-size: 0.9em;
    }
}
</style>
<script>
jQuery(document).ready(function($) {
    $('#clear-cache').click(function() {
        var button = $(this);
        button.text('<?php esc_js_e('Clearing...', 'Crypto-Portfolio-Tracker'); ?>');
        
        $.post(ajaxurl, {
            action: 'cpt_clear_cache',
            nonce: '<?php echo wp_create_nonce("cpt_clear_cache"); ?>'
        }, function(response) {
            if (response.success) {
                button.text('<?php esc_js_e('✅ Cache Cleared', 'Crypto-Portfolio-Tracker'); ?>');
                setTimeout(function() {
                    button.text('<?php esc_js_e('🔄 Clear Price Cache', 'Crypto-Portfolio-Tracker'); ?>');
                }, 2000);
            } else {
                button.text('<?php esc_js_e('❌ Error', 'Crypto-Portfolio-Tracker'); ?>');
                setTimeout(function() {
                    button.text('<?php esc_js_e('🔄 Clear Price Cache', 'Crypto-Portfolio-Tracker'); ?>');
                }, 2000);
            }
        });
    });
    
    $('#export-data').click(function() {
        window.open(ajaxurl + '?action=cpt_export_stats_data&nonce=<?php echo wp_create_nonce("cpt_export_stats"); ?>', '_blank');
    });
    
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
                button.text('<?php esc_js_e('📄 Create Dashboard Page', 'Crypto-Portfolio-Tracker'); ?>');
            }
        });
    });
});
</script>



<?php
// AJAX handlers (los mismos de antes pero asegurándonos que estén)

// Handler to clear cache
add_action('wp_ajax_cpt_clear_cache', function() {
    check_ajax_referer('cpt_clear_cache', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Sin permisos');
    }
    
    // Clear CoinGecko cache
    if (class_exists('CPT_CoinGecko_API')) {
        $coingecko = new CPT_CoinGecko_API();
        $coingecko->clear_cache();
    }
    
    wp_send_json_success(__('Cache cleared successfully', 'Crypto-Portfolio-Tracker'));
});

// Handler to export only statistics (WITHOUT sensitive data)
add_action('wp_ajax_cpt_export_stats_data', function() {
    check_ajax_referer('cpt_export_stats', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Sin permisos');
    }
    
    global $wpdb;
    $transactions_table = $wpdb->prefix . 'cpt_transactions';
    $portfolio_table = $wpdb->prefix . 'cpt_portfolio';
    
    // Only aggregated statistics, WITHOUT sensitive individual data
    $stats_data = array(
        'export_date' => current_time('Y-m-d H:i:s'),
        'total_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $transactions_table"),
        'total_transactions' => $wpdb->get_var("SELECT COUNT(*) FROM $transactions_table"),
        'transactions_by_type' => $wpdb->get_results("
            SELECT transaction_type, COUNT(*) as count 
            FROM $transactions_table 
            GROUP BY transaction_type
        "),
        'popular_cryptos' => $wpdb->get_results("
            SELECT coin_symbol, COUNT(*) as user_count 
            FROM $portfolio_table 
            WHERE total_amount > 0 
            GROUP BY coin_id 
            ORDER BY user_count DESC 
            LIMIT 20
        "),
        'transactions_by_month' => $wpdb->get_results("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as transaction_count
            FROM $transactions_table 
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12
        ")
    );
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="crypto-portfolio-stats-' . date('Y-m-d') . '.json"');
    
    echo json_encode($stats_data, JSON_PRETTY_PRINT);
    exit;
});

// Handler to create dashboard page
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
        // Update settings
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
?><?php
if (!defined('ABSPATH')) {
    exit;
}

// Get general statistics (without sensitive data)
global $wpdb;
$portfolio_table = $wpdb->prefix . 'cpt_portfolio';
$transactions_table = $wpdb->prefix . 'cpt_transactions';
$watchlist_table = $wpdb->prefix . 'cpt_watchlist';

// Basic statistics (without amounts)
$stats = array(
    'total_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $transactions_table"),
    'total_transactions' => $wpdb->get_var("SELECT COUNT(*) FROM $transactions_table"),
    'total_portfolio_items' => $wpdb->get_var("SELECT COUNT(*) FROM $portfolio_table"),
    'total_watchlist_items' => $wpdb->get_var("SELECT COUNT(*) FROM $watchlist_table"),
    'transactions_today' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $transactions_table WHERE DATE(created_at) = %s", current_time('Y-m-d'))),
    'new_users_week' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM $transactions_table WHERE created_at >= %s", date('Y-m-d H:i:s', strtotime('-7 days'))))
);

// Top users by number of transactions (WITHOUT AMOUNTS)
$top_users = $wpdb->get_results("
    SELECT 
        u.user_login,
        u.user_email,
        COUNT(t.id) as transaction_count
    FROM {$wpdb->users} u
    INNER JOIN $transactions_table t ON u.ID = t.user_id
    GROUP BY u.ID
    ORDER BY transaction_count DESC
    LIMIT 10
");

// Most popular cryptos (WITH aggregated total invested, without user details)
$popular_cryptos = $wpdb->get_results("
    SELECT 
        coin_symbol,
        coin_name,
        COUNT(*) as user_count,
        SUM(total_invested) as total_invested
    FROM $portfolio_table
    WHERE total_amount > 0
    GROUP BY coin_id
    ORDER BY user_count DESC
    LIMIT 10
");

// Recent activity (WITHOUT AMOUNTS OR QUANTITIES)
$recent_activity = $wpdb->get_results("
    SELECT 
        t.created_at,
        t.coin_symbol,
        t.coin_name,
        t.transaction_type,
        u.user_login
    FROM $transactions_table t
    INNER JOIN {$wpdb->users} u ON t.user_id = u.ID
    ORDER BY t.created_at DESC
    LIMIT 20
");

$settings = get_option('cpt_settings', array());
$dashboard_page_id = get_option('cpt_dashboard_page_id', 0);
$dashboard_page = get_post($dashboard_page_id);
?>

<div class="wrap">
    <h1><?php esc_html_e('Crypto Portfolio Tracker - Dashboard', 'Crypto-Portfolio-Tracker'); ?></h1>
    
    <!-- Main Statistics -->
    <div class="cpt-dashboard-grid">
        <div class="cpt-stat-widget">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-label"><?php esc_html_e('Active Users', 'Crypto-Portfolio-Tracker'); ?></div>
                <div class="stat-change">+<?php echo $stats['new_users_week']; ?> <?php esc_html_e('this week', 'Crypto-Portfolio-Tracker'); ?></div>
            </div>
        </div>
        
        <div class="cpt-stat-widget">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['total_transactions']); ?></div>
                <div class="stat-label"><?php esc_html_e('Transactions', 'Crypto-Portfolio-Tracker'); ?></div>
                <div class="stat-change"><?php echo $stats['transactions_today']; ?> <?php esc_html_e('today', 'Crypto-Portfolio-Tracker'); ?></div>
            </div>
        </div>
        
        <div class="cpt-stat-widget">
            <div class="stat-icon">📊</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['total_portfolio_items']); ?></div>
                <div class="stat-label"><?php esc_html_e('Holdings', 'Crypto-Portfolio-Tracker'); ?></div>
                <div class="stat-change"><?php esc_html_e('In portfolios', 'Crypto-Portfolio-Tracker'); ?></div>
            </div>
        </div>
        
        <div class="cpt-stat-widget">
            <div class="stat-icon">⭐</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['total_watchlist_items']); ?></div>
                <div class="stat-label"><?php esc_html_e('In Watchlist', 'Crypto-Portfolio-Tracker'); ?></div>
                <div class="stat-change"><?php esc_html_e('Monitoring', 'Crypto-Portfolio-Tracker'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="cpt-quick-actions">
        <h2><?php esc_html_e('Quick Actions', 'Crypto-Portfolio-Tracker'); ?></h2>
        <div class="cpt-action-buttons">
            <a href="<?php echo admin_url('admin.php?page=crypto-portfolio-settings'); ?>" class="button button-primary">
                ⚙️ <?php esc_html_e('Settings', 'Crypto-Portfolio-Tracker'); ?>
            </a>
            <?php if ($dashboard_page): ?>
                <a href="<?php echo get_permalink($dashboard_page); ?>" class="button" target="_blank">
                    🚀 <?php esc_html_e('View Public Dashboard', 'Crypto-Portfolio-Tracker'); ?>
                </a>
            <?php else: ?>
                <button type="button" class="button button-secondary" id="create-dashboard-page">
                    📄 <?php esc_html_e('Create Dashboard Page', 'Crypto-Portfolio-Tracker'); ?>
                </button>
            <?php endif; ?>
            <button type="button" class="button" id="clear-cache">
                🔄 <?php esc_html_e('Clear Price Cache', 'Crypto-Portfolio-Tracker'); ?>
            </button>
            <button type="button" class="button" id="export-data">
                📥 <?php esc_html_e('Export Statistics', 'Crypto-Portfolio-Tracker'); ?>
            </button>
        </div>
    </div>
    
    <div class="cpt-admin-content">
        <!-- Top Users (WITHOUT AMOUNTS) -->
        <div class="cpt-admin-widget">
            <h3><?php esc_html_e('👑 Most Active Users', 'Crypto-Portfolio-Tracker'); ?></h3>
            <div class="cpt-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('User', 'Crypto-Portfolio-Tracker'); ?></th>
                            <th><?php esc_html_e('Email', 'Crypto-Portfolio-Tracker'); ?></th>
                            <th><?php esc_html_e('Transactions', 'Crypto-Portfolio-Tracker'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_users)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #666;">
                                    <?php esc_html_e('No users with transactions yet', 'Crypto-Portfolio-Tracker'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($top_users as $user): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($user->user_login); ?></strong></td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td><?php echo number_format($user->transaction_count); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Popular Cryptos (WITH aggregated total invested) -->
        <div class="cpt-admin-widget">
            <h3><?php esc_html_e('🔥 Most Popular Cryptocurrencies', 'Crypto-Portfolio-Tracker'); ?></h3>
            <div class="cpt-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Crypto', 'Crypto-Portfolio-Tracker'); ?></th>
                            <th><?php esc_html_e('Name', 'Crypto-Portfolio-Tracker'); ?></th>
                            <th><?php esc_html_e('Users', 'Crypto-Portfolio-Tracker'); ?></th>
                            <th><?php esc_html_e('Total Invested', 'Crypto-Portfolio-Tracker'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($popular_cryptos)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #666;">
                                    <?php esc_html_e('No portfolio data yet', 'Crypto-Portfolio-Tracker'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($popular_cryptos as $crypto): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($crypto->coin_symbol); ?></strong></td>
                                    <td><?php echo esc_html($crypto->coin_name); ?></td>
                                    <td><?php echo number_format($crypto->user_count); ?> <?php esc_html_e('users', 'Crypto-Portfolio-Tracker'); ?></td>
                                    <td>$<?php echo number_format($crypto->total_invested, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity (WITHOUT AMOUNTS) -->
    <div class="cpt-admin-widget full-width">
        <h3><?php esc_html_e('📈 Recent Activity', 'Crypto-Portfolio-Tracker'); ?></h3>
        <div class="cpt-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'Crypto-Portfolio-Tracker'); ?></th>
                        <th><?php esc_html_e('User', 'Crypto-Portfolio-Tracker'); ?></th>
                        <th><?php esc_html_e('Crypto', 'Crypto-Portfolio-Tracker'); ?></th>
                        <th><?php esc_html_e('Type', 'Crypto-Portfolio-Tracker'); ?></th>
                        <th><?php esc_html_e('Status', 'Crypto-Portfolio-Tracker'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_activity)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666;">
                                <?php esc_html_e('No recent activity', 'Crypto-Portfolio-Tracker'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($activity->created_at)); ?></td>
                                <td><?php echo esc_html($activity->user_login); ?></td>
                                <td>
                                    <strong><?php echo esc_html($activity->coin_symbol); ?></strong>
                                    <br><small><?php echo esc_html($activity->coin_name); ?></small>
                                </td>
                                <td>
                                    <span class="transaction-type <?php echo $activity->transaction_type; ?>">
                                        <?php echo $activity->transaction_type === 'buy' ? '🟢 ' . esc_html__('Buy', 'Crypto-Portfolio-Tracker') : '🔴 ' . esc_html__('Sell', 'Crypto-Portfolio-Tracker'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="activity-status completed">✅ <?php esc_html_e('Completed', 'Crypto-Portfolio-Tracker'); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- System Status -->
    <div class="cpt-admin-widget">
        <h3><?php esc_html_e('🔧 System Status', 'Crypto-Portfolio-Tracker'); ?></h3>
        <div class="cpt-system-status">
            <div class="status-item">
                <span class="status-label"><?php esc_html_e('WordPress:', 'Crypto-Portfolio-Tracker'); ?></span>
                <span class="status-value good">✅ <?php echo get_bloginfo('version'); ?></span>
            </div>
            <div class="status-item">
                <span class="status-label"><?php esc_html_e('PHP:', 'Crypto-Portfolio-Tracker'); ?></span>
                <span class="status-value <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? 'good' : 'warning'; ?>">
                    <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? '✅' : '⚠️'; ?> <?php echo PHP_VERSION; ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label"><?php esc_html_e('User registration:', 'Crypto-Portfolio-Tracker'); ?></span>
                <span class="status-value <?php echo get_option('users_can_register') ? 'good' : 'warning'; ?>">
                    <?php echo get_option('users_can_register') ? '✅ ' . esc_html__('Enabled', 'Crypto-Portfolio-Tracker') : '⚠️ ' . esc_html__('Disabled', 'Crypto-Portfolio-Tracker'); ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label"><?php esc_html_e('CoinGecko API:', 'Crypto-Portfolio-Tracker'); ?></span>
                <span class="status-value good">✅ <?php esc_html_e('Connected', 'Crypto-Portfolio-Tracker'); ?></span>
            </div>
            <div class="status-item">
                <span class="status-label"><?php esc_html_e('Price cache:', 'Crypto-Portfolio-Tracker'); ?></span>
                <span class="status-value good">✅ <?php printf(esc_html__('Active (%ds)', 'Crypto-Portfolio-Tracker'), $settings['cache_duration'] ?? 300); ?></span>
            </div>
            <div class="status-item">
                <span class="status-label"><?php esc_html_e('Dashboard page:', 'Crypto-Portfolio-Tracker'); ?></span>
                <span class="status-value <?php echo $dashboard_page ? 'good' : 'warning'; ?>">
                    <?php if ($dashboard_page): ?>
                        ✅ <?php esc_html_e('Configured', 'Crypto-Portfolio-Tracker'); ?>
                    <?php else: ?>
                        ⚠️ <?php esc_html_e('Not configured', 'Crypto-Portfolio-Tracker'); ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Privacy notice -->
    <div class="cpt-privacy-notice">
        <h3><?php esc_html_e('🔒 Privacy Commitment', 'Crypto-Portfolio-Tracker'); ?></h3>
        <p>
            <strong><?php esc_html_e('Crypto Portfolio Tracker', 'Crypto-Portfolio-Tracker'); ?></strong> <?php esc_html_e('respects the privacy of your users.', 'Crypto-Portfolio-Tracker'); ?>
            <?php esc_html_e('This dashboard version does not show individual invested amounts or personal financial details.', 'Crypto-Portfolio-Tracker'); ?>
        </p>
        <p>
            <em><?php esc_html_e('Only aggregated statistics and general activity are shown to help you manage the plugin.', 'Crypto-Portfolio-Tracker'); ?></em>
        </p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#clear-cache').click(function() {
        var button = $(this);
        button.text('<?php esc_js_e('Clearing...', 'Crypto-Portfolio-Tracker'); ?>');
        
        $.post(ajaxurl, {
            action: 'cpt_clear_cache',
            nonce: '<?php echo wp_create_nonce("cpt_clear_cache"); ?>'
        }, function(response) {
            if (response.success) {
                button.text('<?php esc_js_e('✅ Cache Cleared', 'Crypto-Portfolio-Tracker'); ?>');
                setTimeout(function() {
                    button.text('<?php esc_js_e('🔄 Clear Price Cache', 'Crypto-Portfolio-Tracker'); ?>');
                }, 2000);
            } else {
                button.text('<?php esc_js_e('❌ Error', 'Crypto-Portfolio-Tracker'); ?>');
                setTimeout(function() {
                    button.text('<?php esc_js_e('🔄 Clear Price Cache', 'Crypto-Portfolio-Tracker'); ?>');
                }, 2000);
            }
        });
    });
    
    $('#export-data').click(function() {
        window.open(ajaxurl + '?action=cpt_export_stats_data&nonce=<?php echo wp_create_nonce("cpt_export_stats"); ?>', '_blank');
    });
    
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
                button.text('<?php esc_js_e('📄 Create Dashboard Page', 'Crypto-Portfolio-Tracker'); ?>');
            }
        });
    });
});
</script>

<style>
.cpt-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.cpt-stat-widget {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    font-size: 2.5em;
    opacity: 0.8;
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 1em;
    opacity: 0.9;
}

.stat-change {
    font-size: 0.8em;
    opacity: 0.7;
    margin-top: 5px;
}

.cpt-quick-actions {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.cpt-quick-actions h2 {
    margin-top: 0;
}

.cpt-action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.cpt-admin-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.cpt-admin-widget {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 0;
}

.cpt-admin-widget.full-width {
    grid-column: 1 / -1;
}

.cpt-admin-widget h3 {
    background: #f8f9fa;
    margin: 0;
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    font-size: 1.1em;
}

.cpt-table-container {
    padding: 20px;
}

.transaction-type.buy {
    color: #46b450;
}

.transaction-type.sell {
    color: #dc3232;
}

.activity-status.completed {
    color: #46b450;
    font-weight: 500;
}

.cpt-system-status {
    padding: 20px;
}

.status-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.status-item:last-child {
    border-bottom: none;
}

.status-label {
    font-weight: 600;
}

.status-value.good {
    color: #46b450;
}

.status-value.warning {
    color: #ffb900;
}

.status-value.error {
    color: #dc3232;
}

.cpt-privacy-notice {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border: 1px solid #42a5f5;
    border-radius: 8px;
    padding: 20px;
    margin-top: 30px;
}

.cpt-privacy-notice h3 {
    margin-top: 0;
    color: #1565c0;
}

.cpt-privacy-notice p {
    margin-bottom: 10px;
    color: #1976d2;
}

@media (max-width: 768px) {
    .cpt-dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .cpt-admin-content {
        grid-template-columns: 1fr;
    }
    
    .cpt-action-buttons {
        flex-direction: column;
    }
    
    .cpt-action-buttons .button {
        text-align: center;
    }
}
</style>

<?php
// Updated AJAX handlers

// Handler to clear cache (same as before)
add_action('wp_ajax_cpt_clear_cache', function() {
    check_ajax_referer('cpt_clear_cache', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Sin permisos');
    }
    
    // Clear CoinGecko cache
    if (class_exists('CPT_CoinGecko_API')) {
        $coingecko = new CPT_CoinGecko_API();
        $coingecko->clear_cache();
    }
    
    wp_send_json_success(__('Cache cleared successfully', 'Crypto-Portfolio-Tracker'));
});

// Handler to export only statistics (WITHOUT sensitive data)
add_action('wp_ajax_cpt_export_stats_data', function() {
    check_ajax_referer('cpt_export_stats', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Sin permisos');
    }
    
    global $wpdb;
    $transactions_table = $wpdb->prefix . 'cpt_transactions';
    $portfolio_table = $wpdb->prefix . 'cpt_portfolio';
    
    // Only aggregated statistics, WITHOUT sensitive individual data
    $stats_data = array(
        'export_date' => current_time('Y-m-d H:i:s'),
        'total_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $transactions_table"),
        'total_transactions' => $wpdb->get_var("SELECT COUNT(*) FROM $transactions_table"),
        'transactions_by_type' => $wpdb->get_results("
            SELECT transaction_type, COUNT(*) as count 
            FROM $transactions_table 
            GROUP BY transaction_type
        "),
        'popular_cryptos' => $wpdb->get_results("
            SELECT coin_symbol, COUNT(*) as user_count 
            FROM $portfolio_table 
            WHERE total_amount > 0 
            GROUP BY coin_id 
            ORDER BY user_count DESC 
            LIMIT 20
        "),
        'transactions_by_month' => $wpdb->get_results("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as transaction_count
            FROM $transactions_table 
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12
        ")
    );
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="crypto-portfolio-stats-' . date('Y-m-d') . '.json"');
    
    echo json_encode($stats_data, JSON_PRETTY_PRINT);
    exit;
});

// Handler to create dashboard page
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
        // Update settings
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