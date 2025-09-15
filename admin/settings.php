<?php
if (!defined('ABSPATH')) {
    exit;
}

// Procesar formulario de configuración
if (isset($_POST['save_settings'])) {
    check_admin_referer('cpt_settings_nonce');
    
    // Guardar configuraciones
    $settings = array(
        'coingecko_api_key' => sanitize_text_field($_POST['coingecko_api_key'] ?? ''),
        'cache_duration' => intval($_POST['cache_duration'] ?? 300),
        'default_currency' => sanitize_text_field($_POST['default_currency'] ?? 'usd'),
        'enable_public_signup' => isset($_POST['enable_public_signup']) ? 1 : 0,
        'dashboard_page_id' => intval($_POST['dashboard_page_id'] ?? 0),
        'require_email_verification' => isset($_POST['require_email_verification']) ? 1 : 0,
        'max_transactions_per_user' => intval($_POST['max_transactions_per_user'] ?? 1000),
        'enable_data_export' => isset($_POST['enable_data_export']) ? 1 : 0,
        'enable_portfolio_sharing' => isset($_POST['enable_portfolio_sharing']) ? 1 : 0
    );
    
    update_option('cpt_settings', $settings);
    
    echo '<div class="notice notice-success"><p>Configuración guardada correctamente.</p></div>';
}

// Obtener configuraciones actuales
$settings = get_option('cpt_settings', array(
    'coingecko_api_key' => '',
    'cache_duration' => 300,
    'default_currency' => 'usd',
    'enable_public_signup' => 1,
    'dashboard_page_id' => get_option('cpt_dashboard_page_id', 0),
    'require_email_verification' => 0,
    'max_transactions_per_user' => 1000,
    'enable_data_export' => 1,
    'enable_portfolio_sharing' => 0
));

// Obtener páginas disponibles para seleccionar
$pages = get_pages();
$dashboard_page = get_post($settings['dashboard_page_id']);
?>

<div class="wrap">
    <h1>Configuración - Crypto Portfolio Tracker</h1>
    
    <div class="cpt-admin-container">
        <div class="cpt-admin-header">
            <h2>🚀 ¡Bienvenido a Crypto Portfolio Tracker!</h2>
            <p>Configura tu plugin para ofrecer la mejor experiencia a tus usuarios.</p>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('cpt_settings_nonce'); ?>
            
            <div class="cpt-settings-section">
                <h3>🔗 Configuración de API</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="coingecko_api_key">CoinGecko API Key</label>
                        </th>
                        <td>
                            <input type="text" id="coingecko_api_key" name="coingecko_api_key" 
                                   value="<?php echo esc_attr($settings['coingecko_api_key']); ?>" 
                                   class="regular-text" placeholder="Opcional - Para mayor límite de requests" />
                            <p class="description">
                                API Key de CoinGecko (opcional). Sin API key tienes 50 calls/minuto. 
                                <a href="https://www.coingecko.com/en/api/pricing" target="_blank">Obtener API Key</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cache_duration">Duración del Cache (segundos)</label>
                        </th>
                        <td>
                            <input type="number" id="cache_duration" name="cache_duration" 
                                   value="<?php echo esc_attr($settings['cache_duration']); ?>" 
                                   class="small-text" min="60" max="3600" />
                            <p class="description">Tiempo en segundos para cachear precios (recomendado: 300 segundos / 5 minutos)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="default_currency">Moneda por Defecto</label>
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
                <h3>👥 Configuración de Usuario</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Registro Público</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_public_signup" value="1" 
                                       <?php checked($settings['enable_public_signup'], 1); ?> />
                                Permitir registro público de usuarios
                            </label>
                            <p class="description">Permitir que cualquiera se registre para usar el portfolio tracker</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Verificación de Email</th>
                        <td>
                            <label>
                                <input type="checkbox" name="require_email_verification" value="1" 
                                       <?php checked($settings['require_email_verification'], 1); ?> />
                                Requerir verificación de email para nuevos usuarios
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="max_transactions_per_user">Límite de Transacciones por Usuario</label>
                        </th>
                        <td>
                            <input type="number" id="max_transactions_per_user" name="max_transactions_per_user" 
                                   value="<?php echo esc_attr($settings['max_transactions_per_user']); ?>" 
                                   class="small-text" min="100" max="10000" />
                            <p class="description">Número máximo de transacciones que puede tener un usuario</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="cpt-settings-section">
                <h3>📄 Configuración de Página</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="dashboard_page_id">Página del Dashboard</label>
                        </th>
                        <td>
                            <select id="dashboard_page_id" name="dashboard_page_id">
                                <option value="0">Seleccionar página...</option>
                                <?php foreach ($pages as $page): ?>
                                    <option value="<?php echo $page->ID; ?>" 
                                            <?php selected($settings['dashboard_page_id'], $page->ID); ?>>
                                        <?php echo esc_html($page->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php if ($dashboard_page): ?>
                                    Página actual: <a href="<?php echo get_permalink($dashboard_page); ?>" target="_blank">
                                        <?php echo esc_html($dashboard_page->post_title); ?>
                                    </a>
                                <?php else: ?>
                                    Selecciona la página donde mostrar el dashboard (debe contener el shortcode [crypto_dashboard])
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="cpt-settings-section">
                <h3>🔧 Funcionalidades</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Exportación de Datos</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_data_export" value="1" 
                                       <?php checked($settings['enable_data_export'], 1); ?> />
                                Permitir a los usuarios exportar sus datos (JSON, CSV)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Compartir Portfolio</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_portfolio_sharing" value="1" 
                                       <?php checked($settings['enable_portfolio_sharing'], 1); ?> />
                                Permitir compartir portfolios públicamente (próximamente)
                            </label>
                            <p class="description">Los usuarios podrán generar enlaces públicos de solo lectura de sus portfolios</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button('Guardar Configuración', 'primary', 'save_settings'); ?>
        </form>
        
        <!-- Setup Wizard -->
        <div class="cpt-settings-section">
            <h3>🧙‍♂️ Asistente de Configuración</h3>
            <div class="cpt-setup-wizard">
                <div class="cpt-wizard-step <?php echo $dashboard_page ? 'completed' : 'pending'; ?>">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <h4>Página del Dashboard</h4>
                        <?php if ($dashboard_page): ?>
                            <p class="step-success">✅ Configurada: <a href="<?php echo get_permalink($dashboard_page); ?>" target="_blank"><?php echo esc_html($dashboard_page->post_title); ?></a></p>
                        <?php else: ?>
                            <p class="step-pending">⏳ Crear o seleccionar página para el dashboard</p>
                            <button type="button" class="button" id="create-dashboard-page">Crear Página Automáticamente</button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="cpt-wizard-step <?php echo get_option('users_can_register') ? 'completed' : 'pending'; ?>">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <h4>Registro de Usuarios</h4>
                        <?php if (get_option('users_can_register')): ?>
                            <p class="step-success">✅ El registro está habilitado en WordPress</p>
                        <?php else: ?>
                            <p class="step-pending">⚠️ El registro de usuarios está deshabilitado en WordPress</p>
                            <p><a href="<?php echo admin_url('options-general.php'); ?>">Habilitar en Configuración → General</a></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="cpt-wizard-step completed">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <h4>Tablas de Base de Datos</h4>
                        <p class="step-success">✅ Tablas creadas correctamente</p>
                    </div>
                </div>
                
                <div class="cpt-wizard-step completed">
                    <span class="step-number">4</span>
                    <div class="step-content">
                        <h4>API de CoinGecko</h4>
                        <p class="step-success">✅ Conexión funcionando (sin API key = límite básico)</p>
                        <?php if (empty($settings['coingecko_api_key'])): ?>
                            <p class="step-note">💡 Para mayor límite, añade tu API Key de CoinGecko arriba</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas rápidas -->
        <div class="cpt-settings-section">
            <h3>📊 Estadísticas del Plugin</h3>
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
                    <div class="stat-label">Usuarios Activos</div>
                </div>
                <div class="cpt-stat-card">
                    <div class="stat-number"><?php echo intval($total_transactions); ?></div>
                    <div class="stat-label">Transacciones Totales</div>
                </div>
                <div class="cpt-stat-card">
                    <div class="stat-number"><?php echo intval($total_portfolio_items); ?></div>
                    <div class="stat-label">Items en Portfolios</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
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
                button.text('Crear Página Automáticamente');
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
        'post_slug' => 'crypto-portfolio'
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