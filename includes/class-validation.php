<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validation and Sanitization Helper Class
 * Ensures all data is properly validated and sanitized according to WordPress standards
 * 
 * @package CryptoPortfolioTracker
 * @since 1.0.0
 */
class CPT_Validation {

    /**
     * Validate and sanitize transaction data
     * 
     * @param array $data Raw transaction data
     * @return array|WP_Error Sanitized data or error
     */
    public static function validate_transaction_data($data) {
        $errors = new WP_Error();
        $sanitized = array();

        // Required fields
        $required_fields = array(
            'coin_symbol' => __('Cryptocurrency symbol', 'crypto-portfolio-tracker'),
            'coin_name' => __('Cryptocurrency name', 'crypto-portfolio-tracker'),
            'type' => __('Transaction type', 'crypto-portfolio-tracker'),
            'amount' => __('Amount', 'crypto-portfolio-tracker'),
            'price' => __('Price', 'crypto-portfolio-tracker'),
            'total' => __('Total value', 'crypto-portfolio-tracker'),
            'date' => __('Date', 'crypto-portfolio-tracker')
        );

        // Check required fields
        foreach ($required_fields as $field => $label) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors->add('missing_field', sprintf(
                    __('Required field missing: %s', 'crypto-portfolio-tracker'),
                    $label
                ));
            }
        }

        if ($errors->has_errors()) {
            return $errors;
        }

        // Sanitize and validate coin_id
        $sanitized['coin_id'] = self::sanitize_coin_id($data['coin_id'] ?? $data['coin_symbol']);
        
        // Sanitize coin symbol (uppercase, alphanumeric + dash/underscore)
        $sanitized['coin_symbol'] = strtoupper(sanitize_text_field($data['coin_symbol']));
        if (!preg_match('/^[A-Z0-9_-]+$/', $sanitized['coin_symbol'])) {
            $errors->add('invalid_symbol', __('Invalid cryptocurrency symbol format', 'crypto-portfolio-tracker'));
        }

        // Sanitize coin name
        $sanitized['coin_name'] = sanitize_text_field($data['coin_name']);
        if (strlen($sanitized['coin_name']) > 128) {
            $errors->add('name_too_long', __('Cryptocurrency name is too long (max 128 characters)', 'crypto-portfolio-tracker'));
        }

        // Validate transaction type
        $sanitized['type'] = strtolower(sanitize_text_field($data['type']));
        if (!in_array($sanitized['type'], array('buy', 'sell'), true)) {
            $errors->add('invalid_type', __('Invalid transaction type. Must be "buy" or "sell"', 'crypto-portfolio-tracker'));
        }

        // Validate numeric fields
        $numeric_fields = array('amount', 'price', 'total');
        foreach ($numeric_fields as $field) {
            $value = self::sanitize_float($data[$field]);
            if ($value <= 0) {
                $errors->add('invalid_' . $field, sprintf(
                    __('%s must be greater than 0', 'crypto-portfolio-tracker'),
                    ucfirst($field)
                ));
            }
            $sanitized[$field] = $value;
        }

        // Validate optional numeric fields
        $sanitized['fees'] = isset($data['fees']) ? self::sanitize_float($data['fees']) : 0.0;
        if ($sanitized['fees'] < 0) {
            $errors->add('invalid_fees', __('Fees cannot be negative', 'crypto-portfolio-tracker'));
        }

        // Validate and sanitize date
        $sanitized['date'] = self::validate_date($data['date']);
        if (!$sanitized['date']) {
            $errors->add('invalid_date', __('Invalid date format. Use YYYY-MM-DD or YYYY-MM-DD HH:MM:SS', 'crypto-portfolio-tracker'));
        }

        // Sanitize optional fields
        $sanitized['exchange'] = isset($data['exchange']) ? sanitize_text_field($data['exchange']) : '';
        $sanitized['notes'] = isset($data['notes']) ? sanitize_textarea_field($data['notes']) : '';

        // Validate exchange length
        if (strlen($sanitized['exchange']) > 100) {
            $errors->add('exchange_too_long', __('Exchange name is too long (max 100 characters)', 'crypto-portfolio-tracker'));
        }

        // Validate notes length
        if (strlen($sanitized['notes']) > 1000) {
            $errors->add('notes_too_long', __('Notes are too long (max 1000 characters)', 'crypto-portfolio-tracker'));
        }

        return $errors->has_errors() ? $errors : $sanitized;
    }

    /**
     * Validate portfolio data
     * 
     * @param array $data Raw portfolio data
     * @return array|WP_Error Sanitized data or error
     */
    public static function validate_portfolio_data($data) {
        $errors = new WP_Error();
        $sanitized = array();

        // Required fields
        $required_fields = array(
            'coin_id' => __('Cryptocurrency ID', 'crypto-portfolio-tracker'),
            'coin_symbol' => __('Cryptocurrency symbol', 'crypto-portfolio-tracker'),
            'coin_name' => __('Cryptocurrency name', 'crypto-portfolio-tracker'),
            'total_amount' => __('Total amount', 'crypto-portfolio-tracker'),
            'avg_buy_price' => __('Average buy price', 'crypto-portfolio-tracker'),
            'total_invested' => __('Total invested', 'crypto-portfolio-tracker')
        );

        // Check required fields
        foreach ($required_fields as $field => $label) {
            if (!isset($data[$field])) {
                $errors->add('missing_field', sprintf(
                    __('Required field missing: %s', 'crypto-portfolio-tracker'),
                    $label
                ));
            }
        }

        if ($errors->has_errors()) {
            return $errors;
        }

        // Sanitize fields
        $sanitized['coin_id'] = self::sanitize_coin_id($data['coin_id']);
        $sanitized['coin_symbol'] = strtoupper(sanitize_text_field($data['coin_symbol']));
        $sanitized['coin_name'] = sanitize_text_field($data['coin_name']);

        // Validate numeric fields
        $numeric_fields = array('total_amount', 'avg_buy_price', 'total_invested');
        foreach ($numeric_fields as $field) {
            $value = self::sanitize_float($data[$field]);
            if ($value < 0) {
                $errors->add('invalid_' . $field, sprintf(
                    __('%s cannot be negative', 'crypto-portfolio-tracker'),
                    str_replace('_', ' ', ucfirst($field))
                ));
            }
            $sanitized[$field] = $value;
        }

        // Optional current price
        $sanitized['current_price'] = isset($data['current_price']) ? self::sanitize_float($data['current_price']) : 0.0;

        return $errors->has_errors() ? $errors : $sanitized;
    }

    /**
     * Validate watchlist data
     * 
     * @param array $data Raw watchlist data
     * @return array|WP_Error Sanitized data or error
     */
    public static function validate_watchlist_data($data) {
        $errors = new WP_Error();
        $sanitized = array();

        // Required fields
        $required_fields = array(
            'coin_id' => __('Cryptocurrency ID', 'crypto-portfolio-tracker'),
            'coin_symbol' => __('Cryptocurrency symbol', 'crypto-portfolio-tracker'),
            'coin_name' => __('Cryptocurrency name', 'crypto-portfolio-tracker')
        );

        // Check required fields
        foreach ($required_fields as $field => $label) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors->add('missing_field', sprintf(
                    __('Required field missing: %s', 'crypto-portfolio-tracker'),
                    $label
                ));
            }
        }

        if ($errors->has_errors()) {
            return $errors;
        }

        // Sanitize fields
        $sanitized['coin_id'] = self::sanitize_coin_id($data['coin_id']);
        $sanitized['coin_symbol'] = strtoupper(sanitize_text_field($data['coin_symbol']));
        $sanitized['coin_name'] = sanitize_text_field($data['coin_name']);

        // Optional target price
        if (isset($data['target_price']) && !empty($data['target_price'])) {
            $sanitized['target_price'] = self::sanitize_float($data['target_price']);
            if ($sanitized['target_price'] <= 0) {
                $errors->add('invalid_target_price', __('Target price must be greater than 0', 'crypto-portfolio-tracker'));
            }
        } else {
            $sanitized['target_price'] = null;
        }

        // Optional notes
        $sanitized['notes'] = isset($data['notes']) ? sanitize_textarea_field($data['notes']) : '';
        if (strlen($sanitized['notes']) > 1000) {
            $errors->add('notes_too_long', __('Notes are too long (max 1000 characters)', 'crypto-portfolio-tracker'));
        }

        return $errors->has_errors() ? $errors : $sanitized;
    }

    /**
     * Validate plugin settings
     * 
     * @param array $data Raw settings data
     * @return array Sanitized settings
     */
    public static function validate_settings($data) {
        $sanitized = array();

        // API Key - allow empty or alphanumeric with dashes
        $sanitized['coingecko_api_key'] = sanitize_text_field($data['coingecko_api_key'] ?? '');
        if (!empty($sanitized['coingecko_api_key']) && !preg_match('/^[a-zA-Z0-9_-]+$/', $sanitized['coingecko_api_key'])) {
            $sanitized['coingecko_api_key'] = ''; // Invalid format, clear it
        }

        // Cache duration - between 60 seconds and 1 hour
        $sanitized['cache_duration'] = absint($data['cache_duration'] ?? 300);
        $sanitized['cache_duration'] = max(60, min(3600, $sanitized['cache_duration']));

        // Default currency - must be valid currency code
        $valid_currencies = array('usd', 'eur', 'btc', 'eth');
        $sanitized['default_currency'] = sanitize_text_field($data['default_currency'] ?? 'usd');
        if (!in_array($sanitized['default_currency'], $valid_currencies, true)) {
            $sanitized['default_currency'] = 'usd';
        }

        // Boolean settings
        $boolean_settings = array(
            'enable_public_signup',
            'require_email_verification',
            'enable_data_export',
            'enable_portfolio_sharing',
            'delete_data_on_uninstall'
        );

        foreach ($boolean_settings as $setting) {
            $sanitized[$setting] = isset($data[$setting]) ? 1 : 0;
        }

        // Dashboard page ID
        $sanitized['dashboard_page_id'] = absint($data['dashboard_page_id'] ?? 0);

        // Max transactions per user - between 100 and 10000
        $sanitized['max_transactions_per_user'] = absint($data['max_transactions_per_user'] ?? 1000);
        $sanitized['max_transactions_per_user'] = max(100, min(10000, $sanitized['max_transactions_per_user']));

        return $sanitized;
    }

    /**
     * Sanitize coin ID (lowercase, alphanumeric with dashes/underscores)
     * 
     * @param string $coin_id Raw coin ID
     * @return string Sanitized coin ID
     */
    public static function sanitize_coin_id($coin_id) {
        $coin_id = strtolower(sanitize_text_field($coin_id));
        $coin_id = preg_replace('/[^a-z0-9_-]/', '', $coin_id);
        return substr($coin_id, 0, 100); // Max 100 characters
    }

    /**
     * Sanitize float value with proper locale handling
     * 
     * @param mixed $value Raw value
     * @return float Sanitized float
     */
    public static function sanitize_float($value) {
        // Handle different locale decimal separators
        $value = str_replace(',', '.', (string)$value);
        
        // Remove any non-numeric characters except dots
        $value = preg_replace('/[^0-9.]/', '', $value);
        
        // Handle multiple dots (keep only the last one)
        $parts = explode('.', $value);
        if (count($parts) > 2) {
            $integer_part = implode('', array_slice($parts, 0, -1));
            $decimal_part = end($parts);
            $value = $integer_part . '.' . $decimal_part;
        }

        return (float)$value;
    }

    /**
     * Validate and sanitize date
     * 
     * @param string $date Raw date string
     * @return string|false Sanitized date in Y-m-d H:i:s format or false if invalid
     */
    public static function validate_date($date) {
        $date = sanitize_text_field($date);

        // Try different date formats
        $formats = array(
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
            'm/d/Y H:i:s',
            'm/d/Y H:i',
            'm/d/Y'
        );

        foreach ($formats as $format) {
            $parsed = DateTime::createFromFormat($format, $date);
            if ($parsed && $parsed->format($format) === $date) {
                return $parsed->format('Y-m-d H:i:s');
            }
        }

        // Try strtotime as fallback
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return date('Y-m-d H:i:s', $timestamp);
        }

        return false;
    }

    /**
     * Validate user permissions for specific actions
     * 
     * @param string $action Action to validate
     * @param int $user_id User ID (optional, defaults to current user)
     * @return bool Whether user has permission
     */
    public static function user_can_perform_action($action, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        switch ($action) {
            case 'manage_portfolio':
            case 'add_transaction':
            case 'edit_transaction':
            case 'delete_transaction':
            case 'view_portfolio':
                return user_can($user_id, 'read');

            case 'manage_settings':
            case 'view_admin_stats':
                return user_can($user_id, 'manage_options');

            case 'export_data':
                $settings = get_option('cpt_settings', array());
                return user_can($user_id, 'read') && !empty($settings['enable_data_export']);

            default:
                return false;
        }
    }

    /**
     * Validate transaction limits for user
     * 
     * @param int $user_id User ID
     * @return bool Whether user can add more transactions
     */
    public static function user_can_add_transaction($user_id) {
        if (!self::user_can_perform_action('add_transaction', $user_id)) {
            return false;
        }

        global $wpdb;
        $settings = get_option('cpt_settings', array());
        $max_transactions = $settings['max_transactions_per_user'] ?? 1000;

        $transaction_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cpt_transactions WHERE user_id = %d",
            $user_id
        ));

        return $transaction_count < $max_transactions;
    }

    /**
     * Sanitize search query for coin search
     * 
     * @param string $query Raw search query
     * @return string Sanitized query
     */
    public static function sanitize_search_query($query) {
        $query = sanitize_text_field($query);
        $query = trim($query);
        
        // Remove special characters that could cause issues
        $query = preg_replace('/[^\w\s-]/', '', $query);
        
        // Limit length
        return substr($query, 0, 50);
    }

    /**
     * Validate API request nonce and permissions
     * 
     * @param WP_REST_Request $request Request object
     * @param string $action Action being performed
     * @return bool|WP_Error True if valid, WP_Error if not
     */
    public static function validate_api_request($request, $action = 'read') {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return new WP_Error(
                'not_logged_in',
                __('You must be logged in to perform this action', 'crypto-portfolio-tracker'),
                array('status' => 401)
            );
        }

        // Check user permissions
        if (!self::user_can_perform_action($action)) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to perform this action', 'crypto-portfolio-tracker'),
                array('status' => 403)
            );
        }

        return true;
    }
}