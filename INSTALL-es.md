# 📦 Guía de Instalación - Crypto Portfolio Tracker

Una guía completa paso a paso para instalar y configurar el plugin Crypto Portfolio Tracker en WordPress.

## 📋 Requisitos del Sistema

### Requisitos Mínimos
- **WordPress**: 5.0 o superior
- **PHP**: 7.4 o superior (recomendado: 8.0+)
- **MySQL**: 5.7 o superior (o MariaDB 10.2+)
- **Memoria PHP**: Mínimo 128MB (recomendado: 256MB+)
- **Navegadores**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

### Dependencias Automáticas
El plugin carga automáticamente estas dependencias:
- **React 18** (vía WordPress wp-element)
- **Recharts 2.12.7** (vía CDN)
- **PropTypes** (vía CDN)
- **WordPress REST API** (incluido en WP 5.0+)

## 🚀 Instalación Paso a Paso

### Opción 1: Instalación Manual (Recomendada)

#### Paso 1: Descargar el Plugin
```bash
# Via Git (recomendado para desarrolladores)
git clone https://github.com/tu-usuario/crypto-portfolio-tracker.git
cd crypto-portfolio-tracker

# O descargar ZIP desde GitHub
```

#### Paso 2: Estructura de Archivos
Asegúrate de tener esta estructura exacta:

```
/wp-content/plugins/crypto-portfolio-tracker/
├── crypto-portfolio-tracker.php          # ✅ Archivo principal
├── README.md                             # ✅ Documentación
├── INSTALL.md                            # ✅ Esta guía
├── includes/                             # ✅ Clases principales
│   ├── class-database.php               # ✅ Manejo de BD
│   ├── class-api-handler.php            # ✅ REST API
│   ├── class-user-portfolio.php         # ✅ Lógica portfolio
│   └── class-coingecko-api.php          # ✅ API CoinGecko
├── admin/                               # ✅ Panel administración
│   ├── dashboard-admin.php              # ✅ Dashboard admin
│   └── settings.php                     # ✅ Configuración
└── assets/                              # ✅ Frontend assets
    ├── js/
    │   └── dashboard.js                 # ✅ React dashboard
    └── css/
        └── dashboard.css                # ✅ Estilos personalizados
```

#### Paso 3: Subir a WordPress
```bash
# Método 1: FTP/SFTP
# Sube la carpeta completa a /wp-content/plugins/

# Método 2: cPanel File Manager
# Comprime como ZIP y sube vía WordPress Admin

# Método 3: WP-CLI (si está disponible)
wp plugin install crypto-portfolio-tracker.zip
```

#### Paso 4: Activar el Plugin
1. Ve a **WordPress Admin → Plugins**
2. Busca "Crypto Portfolio Tracker"
3. Haz clic en **"Activar"**

🎉 **¡El plugin ejecutará automáticamente el setup inicial!**

### Opción 2: Instalación vía WordPress Admin

#### Paso 1: Subir ZIP
1. Ve a **WordPress Admin → Plugins → Añadir nuevo**
2. Haz clic en **"Subir plugin"**
3. Selecciona el archivo `crypto-portfolio-tracker.zip`
4. Haz clic en **"Instalar ahora"**

#### Paso 2: Activar
1. Haz clic en **"Activar plugin"**
2. El setup wizard se ejecutará automáticamente

## ⚙️ Configuración Automática (Setup Wizard)

### Paso 1: Acceder al Panel de Administración
Después de activar, verás un nuevo menú:
- **Crypto Portfolio** → Dashboard principal del admin
- **Crypto Portfolio** → **Configuración** → Ajustes del plugin

### Paso 2: Verificación Automática del Sistema
El plugin verificará automáticamente:

#### ✅ Base de Datos
- **cpt_portfolio**: Tabla de holdings de usuarios
- **cpt_transactions**: Tabla de transacciones
- **cpt_watchlist**: Tabla de lista de seguimiento

#### ✅ Página del Dashboard
- Crea automáticamente una página con el shortcode `[crypto_dashboard]`
- URL típica: `tudominio.com/crypto-portfolio/`

#### ✅ API de CoinGecko
- Verifica conectividad con la API
- Configura cache por defecto (5 minutos)

#### ✅ Dependencias Frontend
- React/WordPress integration
- Recharts loading
- CSS/JS assets

### Paso 3: Configuración Manual (Si es Necesario)

#### Habilitar Registro de Usuarios
```php
// Si el registro está deshabilitado:
// 1. Ve a WordPress Admin → Configuración → General
// 2. Marca "Cualquiera puede registrarse"
// 3. Guarda cambios
```

#### Configurar API Key de CoinGecko (Opcional)
```php
// Para sitios con mucho tráfico:
// 1. Registrarse en https://www.coingecko.com/en/api/pricing
// 2. Ve a Crypto Portfolio → Configuración
// 3. Añadir API Key en el campo correspondiente
// 4. Aumenta el límite de 50 requests/min a 500/min
```

## 🔧 Configuración Avanzada

### Personalizar Cache de Precios
```php
// En wp-config.php o en Crypto Portfolio → Configuración
$settings = array(
    'cache_duration' => 300,    // 5 minutos (recomendado)
    'coingecko_api_key' => '',  // Opcional
    'default_currency' => 'usd',
    'max_transactions_per_user' => 1000
);
```

### Configurar Permisos Avanzados
```php
// Personalizar capacidades (en functions.php del tema)
add_filter('cpt_user_can_add_transaction', function($can, $user_id) {
    $user = get_user_by('id', $user_id);
    return in_array('subscriber', $user->roles) || in_array('author', $user->roles);
}, 10, 2);
```

### Optimización de Rendimiento
```php
// En wp-config.php para sitios grandes
define('CPT_CACHE_DURATION', 600);  // 10 minutos
define('CPT_MAX_API_CALLS_PER_HOUR', 100);
define('CPT_ENABLE_QUERY_CACHE', true);
```

## 🎨 Personalización del Frontend

### Modificar Estilos
Edita `/assets/css/dashboard.css` para personalizar:

```css
/* Cambiar colores del tema */
#crypto-portfolio-dashboard .cpt-glass-card {
    background: rgba(255, 255, 255, 0.1) !important;
    border: 1px solid rgba(TU_COLOR, 0.2) !important;
}

/* Personalizar gradientes */
#crypto-portfolio-dashboard .cpt-dashboard-container {
    background: linear-gradient(135deg, 
        TU_COLOR_PRIMARIO 0%, 
        TU_COLOR_SECUNDARIO 100%) !important;
}
```

### Añadir Hooks Personalizados
```php
// En functions.php del tema
add_action('cpt_transaction_added', function($user_id, $transaction) {
    // Tu lógica personalizada cuando se añade una transacción
    error_log("Nueva transacción de usuario $user_id: " . $transaction['coin_symbol']);
});

add_filter('cpt_portfolio_data', function($portfolio, $user_id) {
    // Modificar datos del portfolio antes de mostrar
    return $portfolio;
}, 10, 2);
```

## 🛠️ Troubleshooting y Problemas Comunes

### Error: "Plugin no se puede activar"
```bash
# Verificar permisos de archivos
chmod 755 /wp-content/plugins/crypto-portfolio-tracker/
chmod 644 /wp-content/plugins/crypto-portfolio-tracker/*.php
chmod 644 /wp-content/plugins/crypto-portfolio-tracker/includes/*.php
```

### Error: "Class not found"
```php
// Verificar que todos los archivos están presentes
$required_files = [
    'includes/class-database.php',
    'includes/class-api-handler.php', 
    'includes/class-user-portfolio.php',
    'includes/class-coingecko-api.php'
];

foreach($required_files as $file) {
    if (!file_exists(WP_PLUGIN_DIR . '/crypto-portfolio-tracker/' . $file)) {
        echo "Archivo faltante: $file\n";
    }
}
```

### Error: "Database table doesn't exist"
```sql
-- Verificar tablas en phpMyAdmin o WP-CLI
SHOW TABLES LIKE 'wp_cpt_%';

-- Si faltan, re-ejecutar instalación:
-- Desactivar y reactivar el plugin
```

### Dashboard no se muestra
```html
<!-- Verificar que la página contiene el shortcode -->
[crypto_dashboard]

<!-- Y que el usuario está logueado -->
<?php if (is_user_logged_in()): ?>
    <!-- Dashboard aquí -->
<?php else: ?>
    <!-- Formulario de login -->
<?php endif; ?>
```

### Gráficos no aparecen (Recharts)
```javascript
// Verificar en consola del navegador
console.log('Recharts disponible:', !!window.Recharts);
console.log('React disponible:', !!window.React);

// Si hay errores, recargar la página
// Recharts se carga asíncrono desde CDN
```

### API de CoinGecko no responde
```php
// Verificar conectividad
$response = wp_remote_get('https://api.coingecko.com/api/v3/ping');
if (is_wp_error($response)) {
    echo 'Error de conectividad: ' . $response->get_error_message();
} else {
    echo 'API CoinGecko: OK';
}
```

### Problemas de Cache
```php
// Limpiar cache manualmente
// Ve a Crypto Portfolio → Configuración → "Limpiar Cache"
// O ejecuta esto en wp-admin/admin-ajax.php
delete_transient('cpt_api_*');
```

## 🔒 Seguridad y Permisos

### Verificar Permisos de WordPress
```php
// Verificar que el usuario actual tiene permisos
if (!current_user_can('read')) {
    wp_die('Sin permisos para acceder al dashboard');
}

// Para administradores
if (!current_user_can('manage_options')) {
    wp_die('Sin permisos de administración');
}
```

### Configurar HTTPS (Recomendado)
```apache
# En .htaccess para forzar HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Backup de Datos
```bash
# Backup de tablas del plugin
mysqldump -u USER -p DATABASE wp_cpt_portfolio wp_cpt_transactions wp_cpt_watchlist > crypto-backup.sql

# Restaurar
mysql -u USER -p DATABASE < crypto-backup.sql
```

## 🎯 Testing y Validación

### Verificar Instalación Completa
1. ✅ **Activación**: Plugin aparece en lista de plugins activos
2. ✅ **Menú Admin**: "Crypto Portfolio" visible en admin
3. ✅ **Página Frontend**: Shortcode funciona correctamente
4. ✅ **API**: Endpoints responden en `/wp-json/crypto-portfolio/v1/`
5. ✅ **Base de Datos**: Tablas creadas con prefijo correcto
6. ✅ **Assets**: CSS y JS cargan sin errores 404

### Test de Usuario Final
1. **Registro**: Usuario puede registrarse (si está habilitado)
2. **Login**: Acceso al dashboard sin errores
3. **Transacción**: Puede añadir transacciones exitosamente
4. **Portfolio**: Ve sus holdings y estadísticas
5. **Gráficos**: Visualizaciones cargan correctamente
6. **Responsive**: Funciona en móvil y desktop

### Test de Rendimiento
```php
// Medir tiempo de carga del dashboard
$start = microtime(true);
// Cargar dashboard
$end = microtime(true);
echo "Tiempo de carga: " . ($end - $start) . " segundos";

// Debería ser < 2 segundos en hosting normal
```

## 📱 Dispositivos Móviles

### Responsive Design
El plugin está optimizado para móviles con:
- **Breakpoints**: 768px para tablet, 480px para móvil
- **Touch-friendly**: Botones y elementos táctiles
- **Viewport meta**: Configuración automática
- **Performance**: Carga optimizada en conexiones lentas

### PWA (Opcional)
Para convertir en Progressive Web App:
```javascript
// Añadir service worker en el tema
navigator.serviceWorker.register('/sw.js');

// Manifest.json para installable app
{
  "name": "Crypto Portfolio",
  "short_name": "CryptoTracker",
  "start_url": "/crypto-portfolio/",
  "display": "standalone"
}
```

## 🔄 Actualizaciones

### Backup Antes de Actualizar
```bash
# Siempre hacer backup antes de actualizar
1. Backup de base de datos
2. Backup de carpeta del plugin
3. Backup del tema si hay modificaciones
```

### Proceso de Actualización
1. **Desactivar** el plugin actual
2. **Reemplazar** archivos por nueva versión
3. **Reactivar** el plugin
4. **Verificar** que todo funciona correctamente

### Migraciones de BD
El plugin maneja automáticamente las migraciones:
```php
// Se ejecutan automáticamente en activación
$current_version = get_option('cpt_db_version', '0');
if (version_compare($current_version, CPT_VERSION, '<')) {
    // Ejecutar migraciones necesarias
    update_option('cpt_db_version', CPT_VERSION);
}
```

## 📞 Soporte Post-Instalación

### Logs y Debug
```php
// Activar logs en wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Los logs del plugin aparecerán en:
// /wp-content/debug.log
```

### Información del Sistema
Ve a **Crypto Portfolio → Dashboard** para ver:
- Estado de PHP y WordPress
- Conectividad de API
- Estadísticas de uso
- Estado de tablas de BD

### Contacto para Soporte
- **GitHub Issues**: Para bugs y feature requests
- **WordPress Forum**: Para dudas generales
- **Documentation**: README.md y comentarios en código

## ✅ Checklist Final

Después de la instalación, verifica:

- [ ] Plugin activado sin errores
- [ ] Menú "Crypto Portfolio" visible en WordPress Admin
- [ ] Página del dashboard creada automáticamente
- [ ] Shortcode `[crypto_dashboard]` funciona correctamente
- [ ] Tablas de BD creadas (cpt_portfolio, cpt_transactions, cpt_watchlist)
- [ ] API endpoints responden: `/wp-json/crypto-portfolio/v1/portfolio`
- [ ] Assets CSS/JS cargan sin errores 404
- [ ] CoinGecko API conecta correctamente
- [ ] Usuario puede registrarse (si está habilitado)
- [ ] Dashboard React se renderiza sin errores JavaScript
- [ ] Recharts se carga y muestra gráficos
- [ ] Cache de precios funciona (5 min por defecto)
- [ ] Responsive design funciona en móvil
- [ ] Transacciones se pueden añadir/editar/eliminar
- [ ] Portfolio calcula P&L correctamente
- [ ] Formulario de transacciones valida datos
- [ ] Autocompletado de cryptos funciona
- [ ] Estadísticas del admin se muestran (sin datos sensibles)

### ✅ Test de Usuario Completo

1. **Registro/Login**
   - [ ] Usuario puede registrarse o hacer login
   - [ ] Redirección al dashboard funciona

2. **Añadir Primera Transacción**
   - [ ] Botón "Añadir Transacción" visible
   - [ ] Formulario se abre correctamente
   - [ ] Autocompletado de cryptos funciona
   - [ ] Validación de campos funciona
   - [ ] Transacción se guarda exitosamente

3. **Ver Portfolio**
   - [ ] Stats cards muestran valores correctos
   - [ ] Tabla de portfolio muestra holdings
   - [ ] Gráficos se renderizan correctamente
   - [ ] Precios actuales se muestran

4. **Gestión de Transacciones**
   - [ ] Historial se muestra completo
   - [ ] Edición de transacciones funciona
   - [ ] Eliminación con confirmación funciona
   - [ ] Portfolio se recalcula automáticamente

5. **Admin Dashboard**
   - [ ] Estadísticas generales se muestran
   - [ ] No se muestran datos sensibles individuales
   - [ ] Setup wizard indica estado correcto
   - [ ] Botones de configuración funcionan

## 🎉 ¡Instalación Completada!

Si todos los checks están marcados, ¡tu instalación de Crypto Portfolio Tracker está completa y lista para usar!

### Próximos Pasos

1. **Personalizar**: Ajusta colores y estilos en `assets/css/dashboard.css`
2. **Configurar**: Ve a Crypto Portfolio → Configuración para ajustes avanzados
3. **Promocionar**: Añade enlaces al dashboard en tu menú de navegación
4. **Monitorear**: Revisa regularmente las estadísticas en el admin
5. **Actualizar**: Mantente al día con las nuevas versiones

### Enlaces Útiles Post-Instalación

- **Dashboard Público**: `tudominio.com/crypto-portfolio/` (o la página que creaste)
- **Admin Dashboard**: `tudominio.com/wp-admin/admin.php?page=crypto-portfolio-tracker`
- **Configuración**: `tudominio.com/wp-admin/admin.php?page=crypto-portfolio-settings`
- **API Docs**: `tudominio.com/wp-json/crypto-portfolio/v1/`

---

**¡Disfruta tu nuevo Portfolio Tracker de Criptomonedas!** 🚀💰📈

Para soporte adicional, consulta el README.md o abre un issue en GitHub.

## 📖 Documentation in Other Languages

- **Español**: [INSTALL-es.md](INSTALL-es.md)
- **Main Documentation**: [README.md](README.md) | [README-es.md](README-es.md)