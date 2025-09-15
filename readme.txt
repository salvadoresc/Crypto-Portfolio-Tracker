=== Crypto Portfolio Tracker ===
Contributors: salvadoresc
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
* 🔄 **Transaction History** - Complete record of all buys, sells, and trades

**Advanced Dashboard**
* 📱 **Modern React Interface** - Fast, responsive, and intuitive user experience
* 📊 **Interactive Charts** - Beautiful visualizations with Recharts library
* 🎯 **Real-time Data** - Live prices from CoinGecko API with intelligent caching
* 📱 **Mobile Optimized** - Perfect experience on all devices

**Multi-User Support**
* 👥 **Private Portfolios** - Each user has their own secure portfolio
* 🔐 **Privacy Protected** - Admins cannot see individual user portfolio amounts
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

* **WordPress 5.0+** compatibility
* **PHP 7.4+** with modern coding standards
* **React 18** powered frontend
* **MySQL** optimized database structure
* **RESTful API** design
* **Responsive CSS** framework
* **No external dependencies** for core functionality

= 🌐 API Integration =

* **CoinGecko API** for real-time pricing (free tier included)
* **Intelligent caching** to minimize API calls
* **Rate limiting** protection
* **Automatic fallbacks** for reliability

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

== Frequently Asked Questions ==

= Do I need an API key to use this plugin? =

No! The plugin works perfectly with CoinGecko's free API tier, which provides 50 calls per minute. For high-traffic websites, you can optionally upgrade to a premium CoinGecko API key for higher rate limits.

= Is user portfolio data private? =

Absolutely! Each user's portfolio is completely private and secure. Even site administrators cannot view individual portfolio amounts or holdings. The admin dashboard only shows aggregated, anonymous statistics.

= Which cryptocurrencies are supported? =

All cryptocurrencies listed on CoinGecko are supported - that's over 8,000 coins and tokens! Popular ones include Bitcoin, Ethereum, Binance Coin, Cardano, Dogecoin, and many more.

= Can users export their data? =

Yes! Users can export their complete portfolio data including transactions and holdings in both JSON and CSV formats for backup or analysis purposes.

= Does this work on mobile devices? =

Absolutely! The dashboard is fully responsive and optimized for mobile devices, tablets, and desktops. The React-based interface provides a smooth experience on all screen sizes.

= How accurate are the price updates? =

Prices are fetched in real-time from CoinGecko's professional API and cached intelligently for 5 minutes by default. This ensures accuracy while maintaining fast load times.

= Can I customize the appearance? =

Yes! The plugin includes comprehensive CSS classes for customization. Advanced users can also modify the React components or use WordPress hooks and filters for deeper customization.

= Is this compatible with my theme? =

Yes! The plugin is designed to work with any properly coded WordPress theme. It uses modern, isolated CSS to prevent conflicts while maintaining your site's design.

= What happens to user data if I deactivate the plugin? =

User data remains safely stored in your WordPress database. When you reactivate the plugin, all portfolios and transactions will be exactly as users left them.

= Can I limit the number of transactions per user? =

Yes! In the plugin settings, you can set maximum limits for transactions per user, enable/disable public registration, and control other user-related features.

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

**Core Features:**
* Real-time cryptocurrency portfolio tracking
* Complete transaction management system
* Interactive dashboard with React 18
* Multi-user support with privacy protection
* CoinGecko API integration with intelligent caching
* Responsive mobile-optimized design
* WordPress REST API endpoints
* Data export functionality
* Admin dashboard with aggregated statistics
* Automatic setup wizard
* Security-first architecture

**Technical Implementation:**
* WordPress 5.0+ compatibility
* PHP 7.4+ requirement with modern standards
* MySQL database optimization with proper indexing
* RESTful API design
* Comprehensive input validation and sanitization
* WordPress coding standards compliance

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
* No tracking cookies or analytics
* No user data sharing with third parties

**User Rights:**
* Users can export all their data
* Users can delete their portfolios at any time
* All data remains on your WordPress installation

**Third Party Services:**
* CoinGecko API for cryptocurrency prices (see CoinGecko's privacy policy)

== Support ==

**Documentation:** Complete guides available in the plugin's README.md file

**GitHub Repository:** https://github.com/salvadoresc/crypto-portfolio-tracker

**Support Forum:** Use WordPress.org support forums for questions and issues

**Security Issues:** Please report security vulnerabilities privately through the WordPress.org security team

== Credits ==

**Developed by:** Emigdio Salvador Corado
**Website:** https://salvadoresc.com/
**API Provider:** CoinGecko (https://www.coingecko.com/)
**Charts Library:** Recharts (https://recharts.org/)
**Icons:** Built-in WordPress Dashicons and Unicode emojis