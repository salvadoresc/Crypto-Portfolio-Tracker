# 🚀 Crypto Portfolio Tracker - WordPress Plugin

A complete WordPress plugin for creating a cryptocurrency portfolio tracker with multi-user support, designed for public use.

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/license-GPL%20v2-green)

## ✨ Key Features

### 🎯 For Users
- **Personal Portfolio**: Each registered user has their own private portfolio
- **Real-time Analysis**: Updated prices from CoinGecko API with intelligent caching
- **Automatic P&L Calculation**: ROI and profit/loss calculated automatically
- **Transaction Management**: Add buys/sells with complete history
- **Modern Dashboard**: React interface with glassmorphism and animations
- **Interactive Charts**: Visualizations with Recharts for analysis
- **Data Export**: Download your portfolio in JSON/CSV
- **Watchlist**: Monitor cryptos without investing (coming soon)

### 🔧 For Administrators
- **Setup Wizard**: Automatic step-by-step configuration
- **Admin Dashboard**: Site statistics and metrics (privacy-respecting)
- **Multi-User Management**: Support for public registration
- **Cache Control**: API performance optimization
- **Aggregate Statistics**: Active users, transactions, popular cryptos
- **Stats Export**: Backup of general statistics
- **Permission System**: Granular functionality control

## 🗂️ Technical Architecture

### Frontend
- **React 18** with modern Hooks
- **Custom Tailwind CSS** for styling
- **Recharts** for charts and visualizations
- **WordPress REST API** for backend communication
- **Intelligent caching** for optimization

### Backend
- **Custom WordPress REST API** with secure endpoints
- **Optimized MySQL tables** with indexes
- **CoinGecko API** for real-time prices
- **WordPress transient cache system** (5 min default)
- **WordPress Hooks and Filters** for extensibility

### Database
- **cpt_portfolio**: User holdings with current prices
- **cpt_transactions**: Complete transaction history
- **cpt_watchlist**: Crypto watchlist

## 📦 Installation

### Manual Installation

1. **Download the Plugin**
   ```bash
   git clone https://github.com/salvadoresc/crypto-portfolio-tracker.git
   cd crypto-portfolio-tracker
   ```

2. **Upload to WordPress**
   - Copy the complete folder to `/wp-content/plugins/`
   - Or upload ZIP file from WordPress Admin → Plugins → Add New

3. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Crypto Portfolio Tracker"
   - Click "Activate"

4. **Automatic Configuration**
   - Go to **Crypto Portfolio** in admin menu
   - Setup Wizard will run automatically
   - Ready! 🎉

### Installation Verification

The plugin includes a verification system that checks:
- ✅ Automatic database table creation
- ✅ Page configuration with shortcode
- ✅ React dependencies verification
- ✅ CoinGecko API connection
- ✅ Cache configuration

## ⚙️ Configuration

### Setup Wizard (Recommended)

The plugin includes a configuration wizard that runs automatically:

1. **Page Configuration**: Automatically creates dashboard page
2. **Permission Verification**: Checks user registration
3. **API Configuration**: Sets up CoinGecko API
4. **Database Tables**: Creates necessary tables
5. **Frontend Verification**: Checks React/Recharts dependencies

### Manual Configuration

#### 1. CoinGecko API (Optional)
```php
// For higher request limits (recommended for large sites)
// In Crypto Portfolio → Settings
$settings['coingecko_api_key'] = 'your_api_key_here';
$settings['cache_duration'] = 300; // 5 minutes
```

#### 2. Enable Public Registration
```php
// In wp-admin → Settings → General
update_option('users_can_register', 1);
```

#### 3. Configure Dashboard Page
The plugin automatically creates a page with `[crypto_dashboard]` shortcode, but you can use the shortcode on any page.

## 🎨 Plugin Usage

### Available Shortcodes

#### Main Dashboard
```php
[crypto_dashboard]
// Shows complete dashboard for logged-in users
```

#### Public Dashboard (Coming Soon)
```php
[crypto_dashboard public="true"]
// Allows viewing data without registration
```

### Available Hooks

#### Actions
```php
// After adding transaction
do_action('cpt_transaction_added', $user_id, $transaction_data);

// After updating portfolio
do_action('cpt_portfolio_updated', $user_id, $portfolio_data);

// After successful login
do_action('cpt_user_dashboard_accessed', $user_id);
```

#### Filters
```php
// Modify default settings
$settings = apply_filters('cpt_default_settings', $settings);

// Customize portfolio data
$portfolio = apply_filters('cpt_portfolio_data', $portfolio, $user_id);

// Modify displayed prices
$prices = apply_filters('cpt_coin_prices', $prices, $coin_ids);
```

## 🛠️ API Endpoints

### Portfolio
```javascript
// Get user portfolio
GET /wp-json/crypto-portfolio/v1/portfolio

// Update portfolio item
POST /wp-json/crypto-portfolio/v1/portfolio

// Delete portfolio item
DELETE /wp-json/crypto-portfolio/v1/portfolio/{coin_id}

// Clean duplicates
POST /wp-json/crypto-portfolio/v1/portfolio/clean
```

### Transactions
```javascript
// Get transactions
GET /wp-json/crypto-portfolio/v1/transactions

// Add new transaction
POST /wp-json/crypto-portfolio/v1/transactions
{
  "coin_id": "bitcoin",
  "coin_symbol": "BTC", 
  "coin_name": "Bitcoin",
  "type": "buy",
  "amount": 100.00,     // Total amount invested
  "price": 45000.00,    // Price per unit
  "quantity": 0.00222,  // Exact quantity received
  "date": "2024-01-15",
  "exchange": "Binance",
  "notes": "Monthly purchase"
}

// Update transaction
PUT /wp-json/crypto-portfolio/v1/transactions/{id}

// Delete transaction
DELETE /wp-json/crypto-portfolio/v1/transactions/{id}
```

### Market Data
```javascript
// Search cryptos
GET /wp-json/crypto-portfolio/v1/market/search?q=bitcoin

// Get current prices
GET /wp-json/crypto-portfolio/v1/market/prices?ids=bitcoin,ethereum

// Trending cryptos
GET /wp-json/crypto-portfolio/v1/market/trending
```

### Watchlist
```javascript
// Get watchlist
GET /wp-json/crypto-portfolio/v1/watchlist

// Add to watchlist
POST /wp-json/crypto-portfolio/v1/watchlist

// Remove from watchlist
DELETE /wp-json/crypto-portfolio/v1/watchlist/{coin_id}
```

## 🎯 Technical Features

### Security
- **WordPress Nonces** for all AJAX requests
- **Input Sanitization** for all inputs
- **Permission Validation** per user
- **Output Escaping** to prevent XSS
- **Privacy**: Admins CANNOT see individual amounts

### Performance
- **Intelligent price caching** (5 min configurable)
- **Lazy loading** of React components
- **Query optimization** with database indexes
- **API request chunking** for external APIs
- **WordPress transients** for caching

### Compatibility
- **WordPress 5.0+** with Gutenberg
- **PHP 7.4+** with type hints
- **React 18** with concurrent features
- **Recharts 2.x** for charts
- **Responsive design** for mobile

## 🔧 Development and Customization

### File Structure
```
crypto-portfolio-tracker/
├── crypto-portfolio-tracker.php    # Main file
├── includes/
│   ├── class-database.php          # Database handling
│   ├── class-api-handler.php       # REST API endpoints
│   ├── class-user-portfolio.php    # Portfolio logic
│   └── class-coingecko-api.php     # CoinGecko integration
├── admin/
│   ├── dashboard-admin.php         # Admin dashboard
│   └── settings.php                # Settings page
├── assets/
│   ├── js/dashboard.js             # React dashboard
│   └── css/dashboard.css           # Custom styles
├── README.md
└── INSTALL.md
```

### Customize Styles
Edit `assets/css/dashboard.css` to change:
- Theme colors (glassmorphism)
- Font sizes
- Spacing and animations
- Visual effects

### Add New Features
1. Create custom hooks in main file
2. Add endpoints in `includes/class-api-handler.php`
3. Modify React component in `assets/js/dashboard.js`
4. Update Database class if new tables needed

## 📊 Dashboard Features

### Real-time Statistics
- **Total Investment**: Sum of all invested amounts
- **Current Value**: Portfolio value with current prices
- **Total P&L**: Profit/loss in USD and percentage
- **ROI**: Return on Investment calculated automatically

### Interactive Charts
- **Investment Evolution**: Timeline of cumulative investments
- **Portfolio Distribution**: Pie chart with percentages
- **Performance per Crypto**: Bar chart with ROI per currency

### Transaction Management
- **Intelligent Form**: Autocomplete with CoinGecko
- **Edit/Delete**: Complete history management
- **Real-time Validation**: Data verification in real time
- **Automatic Recalculation**: Portfolio updates on each change

## 🛡️ Privacy and Security

### Privacy Commitment
- Administrators **CANNOT see** individual user amounts
- Admin dashboard shows only **aggregate statistics**
- **Sensitive data protected** at code level
- **GDPR compliance** with personal data export

### Security Measures
- **Strict validation** of all inputs
- **Sanitization** before database storage
- **Nonces** to prevent CSRF
- **Granular permissions** per functionality
- **Rate limiting** on external API

## 🚀 Roadmap

### v1.1 (Coming Soon)
- [ ] Complete watchlist with alerts
- [ ] CSV transaction import
- [ ] More supported exchanges
- [ ] Push notifications

### v1.2 (Future)
- [ ] Public portfolio sharing
- [ ] Basic technical analysis
- [ ] More API integrations
- [ ] Native mobile dashboard

## 🆘 Support and Troubleshooting

### Common Issues

1. **Dashboard not showing**
   - Verify page has `[crypto_dashboard]` shortcode
   - Ensure user is logged in
   - Check browser console for JS errors

2. **"Class not found" error**
   - Verify all files are in correct folders
   - Deactivate and reactivate plugin

3. **Prices not updating**
   - Go to Crypto Portfolio → Settings → Clear Cache
   - Verify CoinGecko API connection

4. **Charts not appearing**
   - Reload page (Recharts loads asynchronously)
   - Verify there's data in portfolio

### Debug Mode
```php
// Add to wp-config.php for debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Plugin Logs
The plugin logs information to WordPress log:
- API errors
- Transaction creation/updates
- Portfolio calculations

## 📞 Support

If you have problems:
1. **Check WordPress error logs**
2. **Enable WP_DEBUG** in wp-config.php
3. **Verify** all files are in place
4. **Check** PHP >= 7.4 and WordPress >= 5.0
5. **Test** deactivating other plugins to detect conflicts

To report bugs or request features, open an issue on the GitHub repository.

## 📜 License

This plugin is licensed under GPL v2 or later. It's free software: you can redistribute it and/or modify it under the terms of the GNU General Public License.

---

**Developed with ❤️ for the WordPress crypto community**

Thank you for using Crypto Portfolio Tracker! ⭐️

## 📖 Documentation in Other Languages

- **Español**: [README-es.md](README-es.md)
- **Installation Guide**: [INSTALL.md](INSTALL.md) | [INSTALL-es.md](INSTALL-es.md)