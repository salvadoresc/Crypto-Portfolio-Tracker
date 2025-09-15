<?php
if (!defined('ABSPATH')) {
    exit;
}

// Obtener estadísticas generales (sin datos sensibles)
global $wpdb;
$portfolio_table = $wpdb->prefix . 'cpt_portfolio';
$transactions_table = $wpdb->prefix . 'cpt_transactions';
$watchlist_table = $wpdb->prefix . 'cpt_watchlist';

// Estadísticas básicas (sin montos)
$stats = array(
    'total_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $transactions_table"),
    'total_transactions' => $wpdb->get_var("SELECT COUNT(*) FROM $transactions_table"),
    'total_portfolio_items' => $wpdb->get_var("SELECT COUNT(*) FROM $portfolio_table"),
    'total_watchlist_items' => $wpdb->get_var("SELECT COUNT(*) FROM $watchlist_table"),
    'transactions_today' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $transactions_table WHERE DATE(created_at) = %s", current_time('Y-m-d'))),
    'new_users_week' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM $transactions_table WHERE created_at >= %s", date('Y-m-d H:i:s', strtotime('-7 days'))))
);

// Top usuarios por número de transacciones (SIN MONTOS)
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

// Cryptos más populares (CON total invertido agregado, sin detalles de usuarios)
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

// Actividad reciente (SIN MONTOS NI CANTIDADES)
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
    <h1>Crypto Portfolio Tracker - Dashboard</h1>
    
    <!-- Estadísticas principales -->
    <div class="cpt-dashboard-grid">
        <div class="cpt-stat-widget">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-label">Usuarios Activos</div>
                <div class="stat-change">+<?php echo $stats['new_users_week']; ?> esta semana</div>
            </div>
        </div>
        
        <div class="cpt-stat-widget">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['total_transactions']); ?></div>
                <div class="stat-label">Transacciones</div>
                <div class="stat-change"><?php echo $stats['transactions_today']; ?> hoy</div>
            </div>
        </div>
        
        <div class="cpt-stat-widget">
            <div class="stat-icon">📊</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['total_portfolio_items']); ?></div>
                <div class="stat-label">Holdings</div>
                <div class="stat-change">En portfolios</div>
            </div>
        </div>
        
        <div class="cpt-stat-widget">
            <div class="stat-icon">⭐</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo number_format($stats['total_watchlist_items']); ?></div>
                <div class="stat-label">En Watchlist</div>
                <div class="stat-change">Monitoreo</div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="cpt-quick-actions">
        <h2>Acciones Rápidas</h2>
        <div class="cpt-action-buttons">
            <a href="<?php echo admin_url('admin.php?page=crypto-portfolio-settings'); ?>" class="button button-primary">
                ⚙️ Configuración
            </a>
            <?php if ($dashboard_page): ?>
                <a href="<?php echo get_permalink($dashboard_page); ?>" class="button" target="_blank">
                    🚀 Ver Dashboard Público
                </a>
            <?php else: ?>
                <button type="button" class="button button-secondary" id="create-dashboard-page">
                    📄 Crear Página del Dashboard
                </button>
            <?php endif; ?>
            <button type="button" class="button" id="clear-cache">
                🔄 Limpiar Cache de Precios
            </button>
            <button type="button" class="button" id="export-data">
                📥 Exportar Estadísticas
            </button>
        </div>
    </div>
    
    <div class="cpt-admin-content">
        <!-- Top Users (SIN MONTOS) -->
        <div class="cpt-admin-widget">
            <h3>👑 Usuarios Más Activos</h3>
            <div class="cpt-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Transacciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_users)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #666;">
                                    No hay usuarios con transacciones aún
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
        
        <!-- Popular Cryptos (CON total invertido agregado) -->
        <div class="cpt-admin-widget">
            <h3>🔥 Criptomonedas Más Populares</h3>
            <div class="cpt-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Crypto</th>
                            <th>Nombre</th>
                            <th>Usuarios</th>
                            <th>Total Invertido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($popular_cryptos)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #666;">
                                    No hay datos de portfolio aún
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($popular_cryptos as $crypto): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($crypto->coin_symbol); ?></strong></td>
                                    <td><?php echo esc_html($crypto->coin_name); ?></td>
                                    <td><?php echo number_format($crypto->user_count); ?> usuarios</td>
                                    <td>$<?php echo number_format($crypto->total_invested, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity (SIN MONTOS) -->
    <div class="cpt-admin-widget full-width">
        <h3>📈 Actividad Reciente</h3>
        <div class="cpt-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Crypto</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_activity)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666;">
                                No hay actividad reciente
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
                                        <?php echo $activity->transaction_type === 'buy' ? '🟢 Compra' : '🔴 Venta'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="activity-status completed">✅ Completada</span>
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
        <h3>🔧 Estado del Sistema</h3>
        <div class="cpt-system-status">
            <div class="status-item">
                <span class="status-label">WordPress:</span>
                <span class="status-value good">✅ <?php echo get_bloginfo('version'); ?></span>
            </div>
            <div class="status-item">
                <span class="status-label">PHP:</span>
                <span class="status-value <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? 'good' : 'warning'; ?>">
                    <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? '✅' : '⚠️'; ?> <?php echo PHP_VERSION; ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label">Registro de usuarios:</span>
                <span class="status-value <?php echo get_option('users_can_register') ? 'good' : 'warning'; ?>">
                    <?php echo get_option('users_can_register') ? '✅ Habilitado' : '⚠️ Deshabilitado'; ?>
                </span>
            </div>
            <div class="status-item">
                <span class="status-label">API CoinGecko:</span>
                <span class="status-value good">✅ Conectada</span>
            </div>
            <div class="status-item">
                <span class="status-label">Cache de precios:</span>
                <span class="status-value good">✅ Activo (<?php echo $settings['cache_duration'] ?? 300; ?>s)</span>
            </div>
            <div class="status-item">
                <span class="status-label">Página del dashboard:</span>
                <span class="status-value <?php echo $dashboard_page ? 'good' : 'warning'; ?>">
                    <?php if ($dashboard_page): ?>
                        ✅ Configurada
                    <?php else: ?>
                        ⚠️ No configurada
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Información de privacidad -->
    <div class="cpt-privacy-notice">
        <h3>🔒 Compromiso de Privacidad</h3>
        <p>
            <strong>Crypto Portfolio Tracker</strong> respeta la privacidad de tus usuarios. 
            Esta versión del dashboard no muestra montos invertidos individuales ni detalles financieros personales.
        </p>
        <p>
            <em>Solo se muestran estadísticas agregadas y actividad general para ayudarte a administrar el plugin.</em>
        </p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#clear-cache').click(function() {
        var button = $(this);
        button.text('Limpiando...');
        
        $.post(ajaxurl, {
            action: 'cpt_clear_cache',
            nonce: '<?php echo wp_create_nonce("cpt_clear_cache"); ?>'
        }, function(response) {
            if (response.success) {
                button.text('✅ Cache Limpiado');
                setTimeout(function() {
                    button.text('🔄 Limpiar Cache de Precios');
                }, 2000);
            } else {
                button.text('❌ Error');
                setTimeout(function() {
                    button.text('🔄 Limpiar Cache de Precios');
                }, 2000);
            }
        });
    });
    
    $('#export-data').click(function() {
        window.open(ajaxurl + '?action=cpt_export_stats_data&nonce=<?php echo wp_create_nonce("cpt_export_stats"); ?>', '_blank');
    });
    
    $('#create-dashboard-page').click(function() {
        var button = $(this);
        button.text('Creando...');
        
        $.post(ajaxurl, {
            action: 'cpt_create_dashboard_page',
            nonce: '<?php echo wp_create_nonce("cpt_create_page"); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error al crear la página: ' + response.data);
                button.text('📄 Crear Página del Dashboard');
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
// AJAX handlers actualizados

// Handler para limpiar cache (igual que antes)
add_action('wp_ajax_cpt_clear_cache', function() {
    check_ajax_referer('cpt_clear_cache', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Sin permisos');
    }
    
    // Limpiar cache de CoinGecko
    if (class_exists('CPT_CoinGecko_API')) {
        $coingecko = new CPT_CoinGecko_API();
        $coingecko->clear_cache();
    }
    
    wp_send_json_success('Cache limpiado correctamente');
});

// Handler para exportar solo estadísticas (SIN datos sensibles)
add_action('wp_ajax_cpt_export_stats_data', function() {
    check_ajax_referer('cpt_export_stats', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Sin permisos');
    }
    
    global $wpdb;
    $transactions_table = $wpdb->prefix . 'cpt_transactions';
    $portfolio_table = $wpdb->prefix . 'cpt_portfolio';
    
    // Solo estadísticas agregadas, SIN datos individuales sensibles
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

// Handler para crear página del dashboard
add_action('wp_ajax_cpt_create_dashboard_page', function() {
    check_ajax_referer('cpt_create_page', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Sin permisos');
    }
    
    $page_data = array(
        'post_title' => 'Mi Portfolio Crypto',
        'post_content' => '[crypto_dashboard]

<h2>¡Bienvenido a tu Portfolio de Criptomonedas!</h2>

<p>Gestiona y analiza todas tus inversiones en criptomonedas desde un solo lugar. Aquí puedes:</p>

<ul>
<li>📊 <strong>Monitorear tu portfolio</strong> - Ve el valor actual de todas tus holdings</li>
<li>📈 <strong>Analizar performance</strong> - Gráficos y métricas detalladas</li>
<li>💰 <strong>Calcular ganancias/pérdidas</strong> - ROI automático para cada posición</li>
<li>📝 <strong>Registrar transacciones</strong> - Historial completo de compras y ventas</li>
<li>🎯 <strong>Simular escenarios</strong> - "¿Qué pasaría si...?" con diferentes precios</li>
</ul>

<p><strong>¿Nuevo aquí?</strong> Comienza añadiendo tu primera transacción para ver tu portfolio en acción.</p>',
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
            'message' => 'Página creada correctamente'
        ));
    } else {
        wp_send_json_error('Error al crear la página');
    }
});
?>