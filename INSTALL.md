# 📦 Installation Guide - Crypto Portfolio Tracker

A complete step-by-step guide to install and configure the Crypto Portfolio Tracker plugin on WordPress.

## 📋 System Requirements

### Minimum Requirements
- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher (recommended: 8.0+)
- **MySQL**: 5.7 or higher (or MariaDB 10.2+)
- **PHP Memory**: Minimum 128MB (recommended: 256MB+)
- **Browsers**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

### Automatic Dependencies
The plugin automatically loads these dependencies:
- **React 18** (via WordPress wp-element)
- **Recharts 2.12.7** (via CDN)
- **PropTypes** (via CDN)
- **WordPress REST API** (included in WP 5.0+)

## 🚀 Step-by-Step Installation

### Option 1: Manual Installation (Recommended)

#### Step 1: Download the Plugin
```bash
# Via Git (recommended for developers)
git clone https://github.com/salvadoresc/crypto-portfolio-tracker.git
cd crypto-portfolio-tracker

# Or download ZIP from GitHub
```

#### Step 2: File Structure
Make sure you have this exact structure:

```
/wp-content/plugins/crypto-portfolio-tracker/
├── crypto-portfolio-tracker.php          # ✅ Main file
├── README.md                             # ✅ Documentation
├── INSTALL.md                            # ✅ This guide
├── includes/                             # ✅ Main classes
│   ├── class-database.php               # ✅ Database handling
│   ├── class-api-handler.php            # ✅ REST API
│   ├── class-user-portfolio.php         # ✅ Portfolio logic
│   └── class-coingecko-api.php          # ✅ CoinGecko API
├── admin/                               # ✅ Admin panel
│   ├── dashboard-admin.php              # ✅ Admin dashboard
│   └── settings.php                     # ✅ Settings
└── assets/                              # ✅ Frontend assets
    ├── js/
    │   └── dashboard.js                 # ✅ React dashboard
    └── css/
        └── dashboard.css                # ✅ Custom styles
```

#### Step 3: Upload to WordPress
```bash
# Method 1: FTP/SFTP
# Upload complete folder to /wp-content/plugins/

# Method 2: cPanel File Manager
# Compress as ZIP and upload via WordPress Admin

# Method 3: WP-CLI (if available)
wp plugin install crypto-portfolio-tracker.zip
```

#### Step 4: Activate Plugin
1. Go to **WordPress Admin → Plugins**
2. Find "Crypto Portfolio Tracker"
3. Click **"Activate"**

🎉 **The plugin will automatically run initial setup!**

### Option 2: Installation via WordPress Admin

#### Step 1: Upload ZIP
1. Go to **WordPress Admin → Plugins → Add New**
2. Click **"Upload Plugin"**
3. Select `crypto-portfolio-tracker.zip` file
4. Click **"Install Now"**

#### Step 2: Activate
1. Click **"Activate Plugin"**
2. Setup wizard will run automatically

## ⚙️ Automatic Configuration (Setup Wizard)

### Step 1: Access Admin Panel
After activation, you'll see a new menu:
- **Crypto Portfolio** → Main admin dashboard
- **Crypto Portfolio** → **Settings** → Plugin settings

### Step 2: Automatic System Verification
The plugin will automatically verify:

#### ✅ Database
- **cpt_portfolio**: User holdings table
- **cpt_transactions**: Transactions table
- **cpt_watchlist**: Watchlist table

#### ✅ Dashboard Page
- Automatically creates a page with `[crypto_dashboard]` shortcode
- Typical URL: `yourdomain.com/crypto-portfolio/`

#### ✅ CoinGecko API
- Verifies API connectivity
- Sets up default cache (5 minutes)

#### ✅ Frontend Dependencies
- React/WordPress integration
- Recharts loading
- CSS/JS assets

### Step 3: Manual Configuration (If Needed)

#### Enable User Registration
```php
// If registration is disabled:
// 1. Go to WordPress Admin → Settings → General
// 2. Check "Anyone can register"
// 3. Save changes
```

#### Configure CoinGecko API Key (Optional)
```php
// For high-traffic sites:
// 1. Register at https://www.coingecko.com/en/api/pricing
// 2. Go to Crypto Portfolio → Settings
// 3. Add API Key in corresponding field
// 4. Increases limit from 50 requests/min to 500/min
```

## 🔧 Advanced Configuration

### Customize Price Caching
```php
// In wp-config.php or Crypto Portfolio → Settings
$settings = array(
    'cache_duration' => 300,    // 5 minutes (recommended)
    'coingecko_api_key' => '',  // Optional
    'default_currency' => 'usd',
    'max_transactions_per_user' => 1000
);
```

### Configure Advanced Permissions
```php
// Customize capabilities (in theme's functions.php)
add_filter('cpt_user_can_add_transaction', function($can, $user_id) {
    $user = get_user_by('id', $user_id);
    return in_array('subscriber', $user->roles) || in_array('author', $user->roles);
}, 10, 2);
```

### Performance Optimization
```php
// In wp-config.php for large sites
define('CPT_CACHE_DURATION', 600);  // 10 minutes
define('CPT_MAX_API_CALLS_PER_HOUR', 100);
define('CPT_ENABLE_QUERY_CACHE', true);
```

## 🎨 Frontend Customization

### Modify Styles
Edit `/assets/css/dashboard.css` to customize:

```css
/* Change theme colors */
#crypto-portfolio-dashboard .cpt-glass-card {
    background: rgba(255, 255, 255, 0.1) !important;
    border: 1px solid rgba(YOUR_COLOR, 0.2) !important;
}

/* Customize gradients */
#crypto-portfolio-dashboard .cpt-dashboard-container {
    background: linear-gradient(135deg, 
        YOUR_PRIMARY_COLOR 0%, 
        YOUR_SECONDARY_COLOR 100%) !important;
}
```

### Add Custom Hooks
```php
// In theme's functions.php
add_action('cpt_transaction_added', function($user_id, $transaction) {
    // Your custom logic when transaction is added
    error_log("New transaction from user $user_id: " . $transaction['coin_symbol']);
});

add_filter('cpt_portfolio_data', function($portfolio, $user_id) {
    // Modify portfolio data before display
    return $portfolio;
}, 10, 2);
```

## 🛠️ Troubleshooting and Common Issues

### Error: "Plugin cannot be activated"
```bash
# Check file permissions
chmod 755 /wp-content/plugins/crypto-portfolio-tracker/
chmod 644 /wp-content/plugins/crypto-portfolio-tracker/*.php
chmod 644 /wp-content/plugins/crypto-portfolio-tracker/includes/*.php
```

### Error: "Class not found"
```php
// Verify all files are present
$required_files = [
    'includes/class-database.php',
    'includes/class-api-handler.php', 
    'includes/class-user-portfolio.php',
    'includes/class-coingecko-api.php'
];

foreach($required_files as $file) {
    if (!file_exists(WP_PLUGIN_DIR . '/crypto-portfolio-tracker/' . $file)) {
        echo "Missing file: $file\n";
    }
}
```

### Error: "Database table doesn't exist"
```sql
-- Check tables in phpMyAdmin or WP-CLI
SHOW TABLES LIKE 'wp_cpt_%';

-- If missing, re-run installation:
-- Deactivate and reactivate plugin
```

### Dashboard not showing
```html
<!-- Verify page contains shortcode -->
[crypto_dashboard]

<!-- And user is logged in -->
<?php if (is_user_logged_in()): ?>
    <!-- Dashboard here -->
<?php else: ?>
    <!-- Login form -->
<?php endif; ?>
```

### Charts not appearing (Recharts)
```javascript
// Check in browser console
console.log('Recharts available:', !!window.Recharts);
console.log('React available:', !!window.React);

// If errors, reload page
// Recharts loads asynchronously from CDN
```

### CoinGecko API not responding
```php
// Check connectivity
$response = wp_remote_get('https://api.coingecko.com/api/v3/ping');
if (is_wp_error($response)) {
    echo 'Connectivity error: ' . $response->get_error_message();
} else {
    echo 'CoinGecko API: OK';
}
```

### Cache Issues
```php
// Clear cache manually
// Go to Crypto Portfolio → Settings → "Clear Cache"
// Or execute this in wp-admin/admin-ajax.php
delete_transient('cpt_api_*');
```

## 🔒 Security and Permissions

### Verify WordPress Permissions
```php
// Check current user has permissions
if (!current_user_can('read')) {
    wp_die('No permissions to access dashboard');
}

// For administrators
if (!current_user_can('manage_options')) {
    wp_die('No admin permissions');
}
```

### Configure HTTPS (Recommended)
```apache
# In .htaccess to force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Data Backup
```bash
# Backup plugin tables
mysqldump -u USER -p DATABASE wp_cpt_portfolio wp_cpt_transactions wp_cpt_watchlist > crypto-backup.sql

# Restore
mysql -u USER -p DATABASE < crypto-backup.sql
```

## 🎯 Testing and Validation

### Verify Complete Installation
1. ✅ **Activation**: Plugin appears in active plugins list
2. ✅ **Admin Menu**: "Crypto Portfolio" visible in admin
3. ✅ **Frontend Page**: Shortcode works correctly
4. ✅ **API**: Endpoints respond at `/wp-json/crypto-portfolio/v1/`
5. ✅ **Database**: Tables created with correct prefix
6. ✅ **Assets**: CSS and JS load without 404 errors

### End User Testing
1. **Registration**: User can register (if enabled)
2. **Login**: Access dashboard without errors
3. **Transaction**: Can add transactions successfully
4. **Portfolio**: See holdings and statistics
5. **Charts**: Visualizations load correctly
6. **Responsive**: Works on mobile and desktop

### Performance Testing
```php
// Measure dashboard load time
$start = microtime(true);
// Load dashboard
$end = microtime(true);
echo "Load time: " . ($end - $start) . " seconds";

// Should be < 2 seconds on normal hosting
```

## 📱 Mobile Devices

### Responsive Design
The plugin is optimized for mobile with:
- **Breakpoints**: 768px for tablet, 480px for mobile
- **Touch-friendly**: Buttons and touch elements
- **Viewport meta**: Automatic configuration
- **Performance**: Optimized loading on slow connections

### PWA (Optional)
To convert to Progressive Web App:
```javascript
// Add service worker in theme
navigator.serviceWorker.register('/sw.js');

// Manifest.json for installable app
{
  "name": "Crypto Portfolio",
  "short_name": "CryptoTracker",
  "start_url": "/crypto-portfolio/",
  "display": "standalone"
}
```

## 🔄 Updates

### Backup Before Updating
```bash
# Always backup before updating
1. Database backup
2. Plugin folder backup
3. Theme backup if modifications made
```

### Update Process
1. **Deactivate** current plugin
2. **Replace** files with new version
3. **Reactivate** plugin
4. **Verify** everything works correctly

### Database Migrations
Plugin handles migrations automatically:
```php
// Executed automatically on activation
$current_version = get_option('cpt_db_version', '0');
if (version_compare($current_version, CPT_VERSION, '<')) {
    // Execute necessary migrations
    update_option('cpt_db_version', CPT_VERSION);
}
```

## 📞 Post-Installation Support

### Logs and Debug
```php
// Enable logs in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Plugin logs will appear in:
// /wp-content/debug.log
```

### System Information
Go to **Crypto Portfolio → Dashboard** to see:
- PHP and WordPress status
- API connectivity
- Usage statistics
- Database table status

### Contact for Support
- **GitHub Issues**: For bugs and feature requests
- **WordPress Forum**: For general questions
- **Documentation**: README.md and code comments

## ✅ Final Checklist

After installation, verify:

- [ ] Plugin activated without errors
- [ ] "Crypto Portfolio" menu visible in WordPress Admin
- [ ] Dashboard page created automatically
- [ ] `[crypto_dashboard]` shortcode works correctly
- [ ] Database tables created (cpt_portfolio, cpt_transactions, cpt_watchlist)
- [ ] API endpoints respond: `/wp-json/crypto-portfolio/v1/portfolio`
- [ ] CSS/JS assets load without 404 errors
- [ ] CoinGecko API connects correctly
- [ ] User can register (if enabled)
- [ ] React dashboard renders without JavaScript errors
- [ ] Recharts loads and shows charts
- [ ] Price caching works (5 min default)
- [ ] Responsive design works on mobile
- [ ] Transactions can be added/edited/deleted
- [ ] Portfolio calculates P&L correctly
- [ ] Transaction form validates data
- [ ] Crypto autocomplete works
- [ ] Admin statistics show (without sensitive data)

### ✅ Complete User Test

1. **Registration/Login**
   - [ ] User can register or login
   - [ ] Dashboard redirection works

2. **Add First Transaction**
   - [ ] "Add Transaction" button visible
   - [ ] Form opens correctly
   - [ ] Crypto autocomplete works
   - [ ] Field validation works
   - [ ] Transaction saves successfully

3. **View Portfolio**
   - [ ] Stats cards show correct values
   - [ ] Portfolio table shows holdings
   - [ ] Charts render correctly
   - [ ] Current prices display

4. **Transaction Management**
   - [ ] Complete history shows
   - [ ] Transaction editing works
   - [ ] Deletion with confirmation works
   - [ ] Portfolio recalculates automatically

5. **Admin Dashboard**
   - [ ] General statistics show
   - [ ] No sensitive individual data shown
   - [ ] Setup wizard indicates correct status
   - [ ] Configuration buttons work

## 🎉 Installation Complete!

If all checks are marked, your Crypto Portfolio Tracker installation is complete and ready to use!

### Next Steps

1. **Customize**: Adjust colors and styles in `assets/css/dashboard.css`
2. **Configure**: Go to Crypto Portfolio → Settings for advanced settings
3. **Promote**: Add dashboard links to your navigation menu
4. **Monitor**: Regularly check statistics in admin
5. **Update**: Stay current with new versions

### Useful Post-Installation Links

- **Public Dashboard**: `yourdomain.com/crypto-portfolio/` (or your created page)
- **Admin Dashboard**: `yourdomain.com/wp-admin/admin.php?page=crypto-portfolio-tracker`
- **Settings**: `yourdomain.com/wp-admin/admin.php?page=crypto-portfolio-settings`
- **API Docs**: `yourdomain.com/wp-json/crypto-portfolio/v1/`

---

**Enjoy your new Cryptocurrency Portfolio Tracker!** 🚀💰📈

For additional support, check README.md or open an issue on GitHub.

## 📖 Documentation in Other Languages

- **Español**: [INSTALL-es.md](INSTALL-es.md)
- **Main Documentation**: [README.md](README.md) | [README-es.md](README-es.md)