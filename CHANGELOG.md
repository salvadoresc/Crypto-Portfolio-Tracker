# Changelog - Crypto Portfolio Tracker

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- Watchlist functionality with price alerts
- CSV import for bulk transaction uploads
- Portfolio sharing with public read-only links
- Additional chart types and analytics
- Mobile app companion
- Advanced tax reporting

## [1.0.0] - 2024-12-15

### 🎉 Initial Release

#### ✨ Added
- **Core Portfolio Tracking**
  - Real-time cryptocurrency portfolio monitoring
  - Multi-user support with private portfolios
  - Automatic P&L calculations and ROI tracking
  - Support for 8,000+ cryptocurrencies via CoinGecko API

- **Transaction Management**
  - Complete buy/sell transaction recording
  - Transaction history with full CRUD operations
  - Automatic portfolio recalculation on changes
  - Smart coin lookup with autocompletion

- **Interactive Dashboard**
  - Modern React 18-powered interface
  - Responsive glassmorphism design
  - Real-time price updates with intelligent caching
  - Mobile-optimized responsive layout

- **Advanced Visualizations**
  - Portfolio evolution timeline charts
  - Asset distribution pie charts
  - Performance comparison bar charts
  - Interactive tooltips and animations

- **Admin Features**
  - Comprehensive admin dashboard
  - Privacy-protected aggregate statistics
  - Automated setup wizard
  - System health monitoring
  - Cache management tools

- **Developer Features**
  - Complete REST API endpoints
  - WordPress hooks and filters
  - Extensible architecture
  - Comprehensive error handling

#### 🔧 Technical Implementation
- **WordPress Integration**
  - WordPress 5.0+ compatibility
  - Native WordPress REST API usage
  - Standard WordPress coding practices
  - Proper database table creation with dbDelta

- **Security & Privacy**
  - WordPress nonces for CSRF protection
  - Input sanitization and validation
  - User permission checks
  - Privacy-by-design architecture

- **Performance Optimization**
  - Intelligent API response caching (5-minute default)
  - Optimized database queries with proper indexing
  - Lazy loading of React components
  - Efficient batch API calls

- **API Integration**
  - CoinGecko API integration with fallbacks
  - Rate limiting protection
  - Automatic price mapping for common symbols
  - Error handling and retry logic

#### 🛡️ Security Features
- User data isolation (admins cannot see individual portfolios)
- Secure transaction recording with validation
- Nonce-protected AJAX requests
- Sanitized data input/output
- No external data transmission (except CoinGecko API)

#### 📱 User Experience
- Intuitive transaction form with real-time validation
- One-click portfolio overview
- Drag-free responsive tables
- Loading states and error messaging
- Consistent modern UI design

#### 🔌 Extensibility
- WordPress action hooks for transaction events
- Filter hooks for data modification
- Modular class architecture
- Developer-friendly code documentation

### 📊 Database Schema
- `wp_cpt_portfolio` - User portfolio holdings
- `wp_cpt_transactions` - Complete transaction history  
- `wp_cpt_watchlist` - Future watchlist functionality

### 🌐 Supported Browsers
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### 📋 System Requirements
- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher (8.0+ recommended)
- **MySQL**: 5.7 or higher (MariaDB 10.2+ supported)
- **Memory**: 128MB minimum (256MB+ recommended)

### 🔄 Migration Notes
- Fresh installation only (no migration needed)
- Automatic database table creation
- Default settings applied automatically
- Setup wizard guides initial configuration

### 🐛 Known Issues
- None reported in initial release

### 🔒 Security Notes
- All user inputs are sanitized and validated
- WordPress security standards followed
- No known vulnerabilities at release
- Regular security updates planned

---

## Version Numbering

This project uses semantic versioning:
- **MAJOR** version for incompatible API changes
- **MINOR** version for backwards-compatible functionality additions  
- **PATCH** version for backwards-compatible bug fixes

## Support

For support and bug reports:
- **GitHub Issues**: [https://github.com/salvadoresc/crypto-portfolio-tracker/issues](https://github.com/salvadoresc/crypto-portfolio-tracker/issues)
- **WordPress.org Forums**: [https://wordpress.org/support/plugin/crypto-portfolio-tracker/](https://wordpress.org/support/plugin/crypto-portfolio-tracker/)

## Credits

**Developer**: Emigdio Salvador Corado  
**Website**: https://salvadoresc.com/  
**License**: GPL v2 or later