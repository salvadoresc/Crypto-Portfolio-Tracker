=== Crypto Portfolio Tracker ===
Contributors: salvadoresc
Donate link: https://salvadoresc.com/donate/
Tags: cryptocurrency, portfolio, bitcoin, trading, investment, crypto, blockchain, dashboard, analytics, tracking
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Complete cryptocurrency portfolio tracking with real-time prices, P&L analysis, and interactive dashboard for WordPress.

== Description ==

**Crypto Portfolio Tracker** transforms your WordPress site into a powerful cryptocurrency investment tracking platform. Perfect for traders, investors, and crypto enthusiasts who want to monitor their portfolio performance with professional-grade tools.

= 🚀 Key Features =

**Portfolio Management**
* 📊 **Real-time Portfolio Tracking** - Monitor your crypto holdings with live market prices
* 💰 **Automatic P&L Calculations** - Track profits, losses, and ROI automatically
* 📈 **Performance Analytics** - Detailed insights into your investment performance
* 📄 **Transaction History** - Complete record of all buys, sells, and trades

**Advanced Dashboard**
* 📱 **Modern React Interface** - Fast, responsive, and intuitive user experience
* 📊 **Interactive Charts** - Beautiful visualizations with Recharts library
* 🎯 **Real-time Data** - Live prices from CoinGecko API with intelligent caching
* 📱 **Mobile Optimized** - Perfect experience on all devices

**Multi-User Support**
* 👥 **Private Portfolios** - Each user has their own secure portfolio
* 🔒 **Privacy Protected** - Admins cannot see individual user portfolio amounts
* 🚪 **Public Registration** - Allow users to sign up and start tracking
* 📊 **Admin Analytics** - Site-wide statistics without compromising privacy

**Technical Excellence**
* ⚡ **High Performance** - Intelligent caching and optimized queries
* 🛡️ **Security First** - WordPress nonces, data sanitization, and validation
* 🔌 **REST API** - Complete API for developers and integrations
* 📤 **Data Export** - Users can export their data in JSON/CSV formats

= 🎯 Perfect For =

* **Individual Investors** - Track personal crypto investments
* **Trading Communities** - Multi-user crypto tracking platform
* **Investment Clubs** - Collaborative portfolio management
* **Financial Advisors** - Client portfolio tracking services
* **Educational Platforms** - Teaching crypto investment concepts
* **Crypto Blogs** - Enhance content with live portfolio features

= 💎 Supported Cryptocurrencies =

Access to **8,000+ cryptocurrencies** including:
* Bitcoin (BTC)
* Ethereum (ETH)
* Binance Coin (BNB)
* Cardano (ADA)
* Dogecoin (DOGE)
* And thousands more via CoinGecko API

= 🔧 Technical Highlights =

* **WordPress 5.0+** compatibility with Gutenberg support
* **PHP 7.4+** with modern coding standards and type hints
* **React 18** powered frontend with concurrent features
* **MySQL** optimized database structure with proper indexing
* **RESTful API** design following WordPress standards
* **Responsive CSS** framework with mobile-first approach
* **No external dependencies** for core functionality
* **Internationalization ready** with English and Spanish included

= 🌐 API Integration =

* **CoinGecko API** for real-time pricing (free tier included)
* **Intelligent caching** to minimize API calls and improve performance
* **Rate limiting** protection to prevent API abuse
* **Automatic fallbacks** for enhanced reliability
* **Batch processing** for efficient data retrieval

= 🔒 Privacy & Security =

* **Data Isolation** - Users can only see their own portfolios
* **Admin Privacy** - Administrators cannot view individual user amounts
* **Secure Transactions** - All data properly sanitized and validated
* **WordPress Security Standards** - Nonces, capability checks, and more
* **No Data Sharing** - All data stays on your WordPress installation

= 🎨 Customization =

* **Shortcode Support** - Easy integration with `[crypto_dashboard]`
* **Developer Hooks** - WordPress actions and filters for customization
* **CSS Classes** - Comprehensive styling options
* **Color Themes** - Customizable glassmorphism design
* **Widget Ready** - Compatible with WordPress widgets and blocks

== Installation ==

= Automatic Installation =
1. Go to your WordPress admin area and select **Plugins → Add New**
2. Search for **"Crypto Portfolio Tracker"**
3. Click **Install Now** and then **Activate**
4. Navigate to **Crypto Portfolio** in your admin menu
5. Follow the **Setup Wizard** for automatic configuration

= Manual Installation =
1. Download the plugin zip file
2. Upload it to `/wp-content/plugins/crypto-portfolio-tracker/`
3. Activate the plugin through the **Plugins** screen in WordPress
4. Navigate to **Crypto Portfolio** in your admin menu
5. Complete the setup wizard

= After Installation =
1. The plugin creates all necessary database tables automatically
2. A dashboard page is created with the `[crypto_dashboard]` shortcode
3. Users can register (if enabled) and start tracking their portfolios
4. Admins can view site statistics in the admin dashboard

= Shortcode Usage =
Use `[crypto_dashboard]` on any page or post to display the portfolio dashboard.

== Frequently Asked Questions ==

= Do I need an API key to use this plugin? =

No! The plugin works perfectly with CoinGecko's free API tier, which provides 50 calls per minute. For high-traffic websites, you can optionally upgrade to a premium CoinGecko API key for higher rate limits (500 calls per minute).

= Is user portfolio data private? =

Absolutely! Each user's portfolio is completely private and secure. Even site administrators cannot view individual portfolio amounts or holdings. The admin dashboard only shows aggregated, anonymous statistics.

= Which cryptocurrencies are supported? =

All cryptocurrencies listed on CoinGecko are supported - that's over 8,000 coins and tokens! Popular ones include Bitcoin, Ethereum, Binance Coin, Cardano, Dogecoin, Solana, and many more.

= Can users export their data? =

Yes! Users can export their complete portfolio data including transactions and holdings in both JSON and CSV formats for backup or analysis purposes.

= Does this work on mobile devices? =

Absolutely! The dashboard is fully responsive and optimized for mobile devices, tablets, and desktops. The React-based interface provides a smooth experience on all screen sizes.

= How accurate are the price updates? =

Prices are fetched in real-time from CoinGecko's professional API and cached intelligently for 5 minutes by default. This ensures accuracy while maintaining fast load times and optimal performance.

= Can I customize the appearance? =

Yes! The plugin includes comprehensive CSS classes for customization. Advanced users can also modify the React components or use WordPress hooks and filters for deeper customization. The glassmorphism design is fully customizable.

= Is this compatible with my theme? =

Yes! The plugin is designed to work with any properly coded WordPress theme. It uses modern, isolated CSS to prevent conflicts while maintaining your site's design integrity.

= What happens to user data if I deactivate the plugin? =

User data remains safely stored in your WordPress database. When you reactivate the plugin, all portfolios and transactions will be exactly as users left them. Data is only deleted if you specifically enable the "Delete data on uninstall" option.

= Can I limit the number of transactions per user? =

Yes! In the plugin settings, you can set maximum limits for transactions per user, enable/disable public registration, and control other user-related features.

= Does this plugin slow down my website? =

No! The plugin is optimized for performance with intelligent caching, lazy loading, and optimized database queries. It only loads resources on pages where needed.

= Is this plugin GDPR compliant? =

Yes! The plugin follows WordPress privacy standards and includes data export functionality. Users can export all their data, and no personal information is shared with external services except for cryptocurrency prices from CoinGecko.

= Can I use this for client portfolios? =

Absolutely! The multi-user architecture makes it perfect for financial advisors, investment clubs, or any scenario where multiple people need to track separate portfolios.

= What languages are supported? =

The plugin is fully internationalized and currently includes English and Spanish translations. Additional languages can be added using standard WordPress translation tools.

= Is there a pro version? =

Currently, this is a complete free plugin with all features included. Future premium features may include advanced analytics, additional exchanges, and portfolio sharing capabilities.

== Screenshots ==

1. **Main Dashboard** - Complete portfolio overview with real-time values, P&L calculations, and performance statistics
2. **Interactive Charts** - Beautiful visualizations showing portfolio evolution, distribution, and performance analytics  
3. **Transaction Management** - Intuitive interface for adding, editing, and managing cryptocurrency transactions
4. **Admin Dashboard** - Site-wide statistics and management tools with privacy protection
5. **Mobile Experience** - Fully responsive design optimized for smartphones and tablets
6. **Settings Panel** - Comprehensive configuration options for API keys, caching, and user permissions

== Changelog ==

= 1.0.0 =
**Initial Release** - December 2024

**🎉 Core Features:**
* Real-time cryptocurrency portfolio tracking with 8,000+ supported coins
* Complete transaction management system with buy/sell recording
* Interactive dashboard powered by React 18 with modern UI
* Multi-user support with privacy protection for individual portfolios
* CoinGecko API integration with intelligent caching (5-minute default)
* Responsive mobile-optimized design for all device types
* WordPress REST API endpoints for developers and integrations
* Data export functionality in JSON and CSV formats
* Admin dashboard with aggregated statistics (no sensitive data)
* Automatic setup wizard for easy configuration
* Security-first architecture with WordPress standards compliance

**🔧 Technical Implementation:**
* WordPress 5.0+ compatibility with full Gutenberg support
* PHP 7.4+ requirement with modern coding standards and type hints
* MySQL database optimization with proper indexing and relationships
* RESTful API design following WordPress best practices
* Comprehensive input validation and data sanitization
* WordPress coding standards compliance throughout codebase
* Internationalization support with English and Spanish included

**🛡️ Security Features:**
* User data isolation (admins cannot see individual portfolios)
* Secure transaction recording with comprehensive validation
* Nonce-protected AJAX requests and form submissions
* Sanitized data input/output with WordPress functions
* No external data transmission (except CoinGecko API for prices)
* Privacy-by-design architecture protecting user financial data

**📱 User Experience:**
* Intuitive transaction form with real-time validation and feedback
* One-click portfolio overview with detailed statistics
* Drag-free responsive tables optimized for mobile interaction
* Loading states and comprehensive error messaging
* Consistent modern UI design with glassmorphism effects
* Accessibility features with proper ARIA labels and keyboard navigation

**🔌 Extensibility:**
* WordPress action hooks for transaction and portfolio events
* Filter hooks for data modification and customization
* Modular class architecture for easy extension
* Developer-friendly code documentation and examples
* RESTful API for third-party integrations

**🗄️ Database Schema:**
* `wp_cpt_portfolio` - User portfolio holdings with current values
* `wp_cpt_transactions` - Complete transaction history with full details
* `wp_cpt_watchlist` - Future watchlist functionality (prepared)

**🌐 Supported Browsers:**
* Chrome 90+ (full support)
* Firefox 88+ (full support)
* Safari 14+ (full support)
* Edge 90+ (full support)

**📋 System Requirements:**
* **WordPress**: 5.0 or higher (tested up to 6.4)
* **PHP**: 7.4 or higher (8.0+ recommended for better performance)
* **MySQL**: 5.7 or higher (MariaDB 10.2+ also supported)
* **Memory**: 128MB minimum (256MB+ recommended for optimal performance)

**🔄 Migration Notes:**
* Fresh installation only (no migration needed from other plugins)
* Automatic database table creation with proper error handling
* Default settings applied automatically for immediate use
* Setup wizard guides initial configuration step-by-step

**🐛 Known Issues:**
* None reported in initial release

**🔒 Security Notes:**
* All user inputs are sanitized and validated using WordPress functions
* WordPress security standards followed throughout development
* No known vulnerabilities at release time
* Regular security updates planned for ongoing protection

== Upgrade Notice ==

= 1.0.0 =
Welcome to Crypto Portfolio Tracker! This initial release provides everything you need to start tracking cryptocurrency investments on your WordPress site. The plugin includes automatic setup and is ready to use immediately after activation.

== Privacy Policy ==

**Data We Collect:**
* User portfolio holdings and transactions (stored locally in your WordPress database)
* Cryptocurrency price data (fetched from CoinGecko API)
* Basic usage statistics for site administration

**Data We DON'T Collect:**
* No data is sent to external servers (except CoinGecko for prices)
* No tracking cookies or analytics scripts
* No user data sharing with third parties
* No personal information beyond what WordPress normally stores

**User Rights:**
* Users can export all their data in JSON/CSV format
* Users can delete their portfolios at any time
* All data remains on your WordPress installation
* No vendor lock-in - data is yours

**Third Party Services:**
* CoinGecko API for cryptocurrency prices (see CoinGecko's privacy policy)
* No other external services are used

**Data Storage:**
* All portfolio and transaction data is stored in your WordPress database
* No cloud storage or external databases are used
* Standard WordPress database security applies

== Support ==

**Documentation:** 
* Complete installation and usage guides included
* Developer documentation in the plugin's README.md file
* Inline code documentation for developers

**Community Support:**
* WordPress.org support forums for general questions
* GitHub repository for technical issues and feature requests

**Professional Support:**
* Contact the developer for custom implementations
* Available for enterprise installations and customizations

**Reporting Issues:**
* Use WordPress.org support forums for general issues
* GitHub issues for bugs and feature requests
* Security issues should be reported privately

== Credits ==

**Developed by:** Emigdio Salvador Corado  
**Website:** https://salvadoresc.com/  
**GitHub:** https://github.com/salvadoresc/crypto-portfolio-tracker  

**External Services:**
* **API Provider:** CoinGecko (https://www.coingecko.com/) for cryptocurrency price data
* **Charts Library:** Recharts (https://recharts.org/) for data visualization
* **Icons:** WordPress Dashicons and Unicode emojis

**Special Thanks:**
* WordPress community for excellent documentation and standards
* CoinGecko for providing reliable cryptocurrency data API
* React and Recharts teams for excellent development tools

== Technical Notes ==

**Performance Optimization:**
* Intelligent API caching reduces external requests
* Database queries are optimized with proper indexing
* Frontend assets are loaded only when needed
* React components use lazy loading where appropriate

**Development Standards:**
* Follows WordPress Plugin Development Guidelines
* Uses WordPress Coding Standards (WPCS)
* Implements WordPress Security Guidelines
* Adheres to WordPress Accessibility Guidelines

**Future Development:**
* Additional cryptocurrency exchanges integration planned
* Advanced portfolio analytics features in development
* Public portfolio sharing functionality coming soon
* Mobile app companion under consideration

**Contributing:**
* Contributions welcome via GitHub repository
* Translation contributions appreciated
* Code contributions should follow WordPress standards
* Feature requests and bug reports welcome