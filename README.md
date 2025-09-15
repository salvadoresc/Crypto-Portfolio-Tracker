# 🚀 Crypto Portfolio Tracker - WordPress Plugin

Un plugin completo de WordPress para crear un tracker de portfolio de criptomonedas con soporte multi-usuario, diseñado para uso público.

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/license-GPL%20v2-green)

## ✨ Características Principales

### 🎯 Para Usuarios
- **Portfolio Personalizado**: Cada usuario registrado tiene su propio portfolio privado
- **Análisis en Tiempo Real**: Precios actualizados desde CoinGecko API con cache inteligente
- **Cálculo Automático de P&L**: ROI y ganancias/pérdidas calculadas automáticamente
- **Gestión de Transacciones**: Añadir compras/ventas con historial completo
- **Dashbaord Moderno**: Interfaz React con glassmorphism y animaciones
- **Gráficos Interactivos**: Visualizaciones con Recharts para análisis
- **Exportación de Datos**: Descarga tu portfolio en JSON/CSV
- **Watchlist**: Monitorea cryptos sin invertir (próximamente)

### 🔧 Para Administradores
- **Setup Wizard**: Configuración automática paso a paso
- **Dashboard de Administración**: Estadísticas y métricas del sitio (respetando privacidad)
- **Gestión Multi-Usuario**: Soporte para registro público
- **Control de Cache**: Optimización de rendimiento de API
- **Estadísticas Agregadas**: Usuarios activos, transacciones, cryptos populares
- **Exportación de Stats**: Backup de estadísticas generales
- **Sistema de Permisos**: Control granular de funcionalidades

## 🗂️ Arquitectura Técnica

### Frontend
- **React 18** con Hooks modernos
- **Tailwind CSS** personalizado para styling
- **Recharts** para gráficos y visualizaciones
- **WordPress REST API** para comunicación backend
- **Cache inteligente** para optimización

### Backend
- **WordPress REST API** personalizada con endpoints seguros
- **Tablas MySQL** optimizadas con índices
- **CoinGecko API** para precios en tiempo real
- **Sistema de Cache** transient de WordPress (5 min por defecto)
- **Hooks y Filters** de WordPress para extensibilidad

### Base de Datos
- **cpt_portfolio**: Holdings de usuarios con precios actuales
- **cpt_transactions**: Historial completo de transacciones
- **cpt_watchlist**: Lista de seguimiento de cryptos

## 📦 Instalación

### Instalación Manual

1. **Descarga el Plugin**
   ```bash
   git clone https://github.com/tu-usuario/crypto-portfolio-tracker.git
   cd crypto-portfolio-tracker
   ```

2. **Sube a WordPress**
   - Copia la carpeta completa a `/wp-content/plugins/`
   - O sube el archivo ZIP desde WordPress Admin → Plugins → Añadir nuevo

3. **Activa el Plugin**
   - Ve a WordPress Admin → Plugins
   - Busca "Crypto Portfolio Tracker"
   - Haz clic en "Activar"

4. **Configuración Automática**
   - Ve a **Crypto Portfolio** en el menú de administración
   - El Setup Wizard se ejecutará automáticamente
   - ¡Listo! 🎉

### Verificación de Instalación

El plugin incluye un sistema de verificación que comprueba:
- ✅ Creación automática de tablas de BD
- ✅ Configuración de página con shortcode
- ✅ Verificación de dependencias React
- ✅ Conexión a CoinGecko API
- ✅ Configuración de cache

## ⚙️ Configuración

### Setup Wizard (Recomendado)

El plugin incluye un asistente de configuración que se ejecuta automáticamente:

1. **Configuración de Página**: Crea automáticamente la página del dashboard
2. **Verificación de Permisos**: Comprueba registro de usuarios
3. **Configuración de API**: Configura CoinGecko API
4. **Tablas de BD**: Crea las tablas necesarias
5. **Verificación de Frontend**: Comprueba dependencias React/Recharts

### Configuración Manual

#### 1. CoinGecko API (Opcional)
```php
// Para mayor límite de requests (recomendado para sitios grandes)
// En Crypto Portfolio → Configuración
$settings['coingecko_api_key'] = 'tu_api_key_aqui';
$settings['cache_duration'] = 300; // 5 minutos
```

#### 2. Habilitar Registro Público
```php
// En wp-admin → Configuración → General
update_option('users_can_register', 1);
```

#### 3. Configurar Página del Dashboard
El plugin crea automáticamente una página con el shortcode `[crypto_dashboard]`, pero puedes usar el shortcode en cualquier página.

## 🎨 Uso del Plugin

### Shortcodes Disponibles

#### Dashboard Principal
```php
[crypto_dashboard]
// Muestra el dashboard completo para usuarios logueados
```

#### Dashboard Público (Próximamente)
```php
[crypto_dashboard public="true"]
// Permite ver datos sin registro
```

### Hooks Disponibles

#### Actions
```php
// Después de añadir transacción
do_action('cpt_transaction_added', $user_id, $transaction_data);

// Después de actualizar portfolio
do_action('cpt_portfolio_updated', $user_id, $portfolio_data);

// Después de login exitoso
do_action('cpt_user_dashboard_accessed', $user_id);
```

#### Filters
```php
// Modificar configuraciones por defecto
$settings = apply_filters('cpt_default_settings', $settings);

// Personalizar datos del portfolio
$portfolio = apply_filters('cpt_portfolio_data', $portfolio, $user_id);

// Modificar precios mostrados
$prices = apply_filters('cpt_coin_prices', $prices, $coin_ids);
```

## 🛠️ API Endpoints

### Portfolio
```javascript
// Obtener portfolio del usuario
GET /wp-json/crypto-portfolio/v1/portfolio

// Actualizar item del portfolio
POST /wp-json/crypto-portfolio/v1/portfolio

// Eliminar item del portfolio
DELETE /wp-json/crypto-portfolio/v1/portfolio/{coin_id}

// Limpiar duplicados
POST /wp-json/crypto-portfolio/v1/portfolio/clean
```

### Transacciones
```javascript
// Obtener transacciones
GET /wp-json/crypto-portfolio/v1/transactions

// Añadir nueva transacción
POST /wp-json/crypto-portfolio/v1/transactions
{
  "coin_id": "bitcoin",
  "coin_symbol": "BTC", 
  "coin_name": "Bitcoin",
  "type": "buy",
  "amount": 100.00,     // Monto total invertido
  "price": 45000.00,    // Precio por unidad
  "quantity": 0.00222,  // Cantidad exacta recibida
  "date": "2024-01-15",
  "exchange": "Binance",
  "notes": "Compra mensual"
}

// Actualizar transacción
PUT /wp-json/crypto-portfolio/v1/transactions/{id}

// Eliminar transacción
DELETE /wp-json/crypto-portfolio/v1/transactions/{id}
```

### Market Data
```javascript
// Buscar cryptos
GET /wp-json/crypto-portfolio/v1/market/search?q=bitcoin

// Obtener precios actuales
GET /wp-json/crypto-portfolio/v1/market/prices?ids=bitcoin,ethereum

// Cryptos trending
GET /wp-json/crypto-portfolio/v1/market/trending
```

### Watchlist
```javascript
// Obtener watchlist
GET /wp-json/crypto-portfolio/v1/watchlist

// Añadir a watchlist
POST /wp-json/crypto-portfolio/v1/watchlist

// Remover de watchlist
DELETE /wp-json/crypto-portfolio/v1/watchlist/{coin_id}
```

## 🎯 Características Técnicas

### Seguridad
- **Nonces de WordPress** para todas las peticiones AJAX
- **Sanitización** de todos los inputs
- **Validación** de permisos por usuario
- **Escape** de outputs para prevenir XSS
- **Privacidad**: El admin NO puede ver montos individuales

### Performance
- **Cache inteligente** de precios (5 min configurable)
- **Lazy loading** de componentes React
- **Optimización de queries** con índices de BD
- **Chunking** de peticiones a APIs externas
- **Transients** de WordPress para cache

### Compatibilidad
- **WordPress 5.0+** con Gutenberg
- **PHP 7.4+** con type hints
- **React 18** con concurrent features
- **Recharts 2.x** para gráficos
- **Responsive design** para móviles

## 🔧 Desarrollo y Personalización

### Estructura de Archivos
```
crypto-portfolio-tracker/
├── crypto-portfolio-tracker.php    # Archivo principal
├── includes/
│   ├── class-database.php          # Manejo de BD
│   ├── class-api-handler.php       # REST API endpoints
│   ├── class-user-portfolio.php    # Lógica de portfolio
│   └── class-coingecko-api.php     # Integración CoinGecko
├── admin/
│   ├── dashboard-admin.php         # Dashboard de admin
│   └── settings.php                # Página de configuración
├── assets/
│   ├── js/dashboard.js             # React dashboard
│   └── css/dashboard.css           # Estilos personalizados
├── README.md
└── INSTALL.md
```

### Personalizar Estilos
Edita `assets/css/dashboard.css` para cambiar:
- Colores del tema (glassmorphism)
- Tamaños de fuente
- Espaciados y animaciones
- Efectos visuales

### Añadir Nuevas Funcionalidades
1. Crea hooks personalizados en el archivo principal
2. Añade endpoints en `includes/class-api-handler.php`
3. Modifica el componente React en `assets/js/dashboard.js`
4. Actualiza la clase Database si necesitas nuevas tablas

## 📊 Dashboard Features

### Estadísticas en Tiempo Real
- **Inversión Total**: Suma de todos los montos invertidos
- **Valor Actual**: Valor del portfolio con precios actuales
- **P&L Total**: Ganancias/pérdidas en USD y porcentaje
- **ROI**: Return on Investment calculado automáticamente

### Gráficos Interactivos
- **Evolución de Inversiones**: Timeline de inversiones acumulativas
- **Distribución del Portfolio**: Pie chart con porcentajes
- **Performance por Crypto**: Bar chart con ROI por moneda

### Gestión de Transacciones
- **Formulario Inteligente**: Autocompletado con CoinGecko
- **Edición/Eliminación**: Gestión completa del historial
- **Validación**: Verificación de datos en tiempo real
- **Recálculo Automático**: Portfolio se actualiza en cada cambio

## 🛡️ Privacidad y Seguridad

### Compromiso de Privacidad
- Los administradores **NO pueden ver** montos individuales de usuarios
- Dashboard de admin muestra solo **estadísticas agregadas**
- **Datos sensibles protegidos** a nivel de código
- **Cumplimiento GDPR** con exportación de datos personales

### Medidas de Seguridad
- **Validación estricta** de todos los inputs
- **Sanitización** antes de guardado en BD
- **Nonces** para prevenir CSRF
- **Permisos granulares** por funcionalidad
- **Rate limiting** en API externa

## 🚀 Roadmap

### v1.1 (Próximamente)
- [ ] Watchlist completa con alertas
- [ ] Importación CSV de transacciones
- [ ] Más exchanges soportados
- [ ] Notificaciones push

### v1.2 (Futuro)
- [ ] Portfolio sharing público
- [ ] Análisis técnico básico
- [ ] Integración con más APIs
- [ ] Dashboard para móvil nativo
- [ ] Sistema de traducciones i18n

## 🆘 Soporte y Troubleshooting

### Problemas Comunes

1. **Dashboard no se muestra**
   - Verifica que la página tenga el shortcode `[crypto_dashboard]`
   - Asegúrate de que el usuario esté logueado
   - Revisa la consola del navegador para errores JS

2. **Error "Class not found"**
   - Verifica que todos los archivos estén en las carpetas correctas
   - Desactiva y reactiva el plugin

3. **Precios no se actualizan**
   - Ve a Crypto Portfolio → Configuración → Limpiar Cache
   - Verifica la conexión a CoinGecko API

4. **Gráficos no aparecen**
   - Recarga la página (Recharts se carga asíncrono)
   - Verifica que hay datos en el portfolio

### Debug Mode
```php
// Añadir a wp-config.php para debug
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Logs del Plugin
El plugin registra información en el log de WordPress:
- Errores de API
- Creación/actualización de transacciones
- Cálculos de portfolio

## 📞 Soporte

Si tienes problemas:
1. **Revisa los logs** de error de WordPress
2. **Activa WP_DEBUG** en wp-config.php
3. **Verifica** que todos los archivos estén en su lugar
4. **Comprueba** que PHP >= 7.4 y WordPress >= 5.0
5. **Prueba** desactivar otros plugins para detectar conflictos

Para reportar bugs o solicitar features, abre un issue en el repositorio de GitHub.

## 📜 Licencia

Este plugin está licenciado bajo GPL v2 o posterior. Es software libre: puedes redistribuirlo y/o modificarlo bajo los términos de la Licencia Pública General GNU.

---

**Desarrollado con ❤️ para la comunidad crypto de WordPress**

¡Gracias por usar Crypto Portfolio Tracker! ⭐️