<?php
if (!defined('ABSPATH')) {
    exit;
}

class CPT_API_Handler {

    /** @var CPT_Database */
    private $database;
    /** @var CPT_CoinGecko_API */
    private $coingecko;

    public function __construct() {
        $this->database  = new CPT_Database();
        $this->coingecko = class_exists('CPT_CoinGecko_API') ? new CPT_CoinGecko_API() : null;
    }

    public function register_routes() {
        $ns = 'crypto-portfolio/v1';

        // ===== Portfolio =====
        register_rest_route($ns, '/portfolio', array(
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'get_portfolio'),
                'permission_callback' => array($this, 'check_read_permissions'),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'update_portfolio'),
                'permission_callback' => array($this, 'check_manage_permissions'),
                'args'                => array(
                    'coin_id'       => array('required' => true, 'validate_callback' => array($this, 'validate_coin_id')),
                    'coin_symbol'   => array('required' => true),
                    'coin_name'     => array('required' => true),
                    'total_amount'  => array('required' => true),
                    'avg_buy_price' => array('required' => true),
                    'total_invested'=> array('required' => true),
                ),
            ),
        ));

        // ===== Clean duplicates =====
        register_rest_route($ns, '/portfolio/clean', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'clean_duplicates'),
            'permission_callback' => array($this, 'check_manage_permissions'),
        ));

        register_rest_route($ns, '/portfolio/(?P<coin_id>[a-zA-Z0-9\-_]+)', array(
            'methods'             => 'DELETE',
            'callback'            => array($this, 'delete_portfolio_item'),
            'permission_callback' => array($this, 'check_manage_permissions'),
            'args'                => array(
                'coin_id' => array('required' => true, 'validate_callback' => array($this, 'validate_coin_id')),
            ),
        ));

        // ===== Transactions =====
        register_rest_route($ns, '/transactions', array(
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'get_transactions'),
                'permission_callback' => array($this, 'check_read_permissions'),
                'args'                => array(
                    'limit'   => array('validate_callback' => array($this, 'validate_limit')),
                    'coin_id' => array('validate_callback' => array($this, 'validate_coin_id')),
                ),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'add_transaction'),
                'permission_callback' => array($this, 'check_transaction_permissions'),
            ),
        ));

        register_rest_route($ns, '/transactions/(?P<id>\d+)', array(
            array(
                'methods'             => 'PUT',
                'callback'            => array($this, 'update_transaction'),
                'permission_callback' => array($this, 'check_transaction_permissions'),
                'args'                => array(
                    'id' => array('required' => true, 'validate_callback' => function($param){ return (int)$param > 0; }),
                ),
            ),
            array(
                'methods'             => 'DELETE',
                'callback'            => array($this, 'delete_transaction'),
                'permission_callback' => array($this, 'check_transaction_permissions'),
                'args'                => array(
                    'id' => array('required' => true, 'validate_callback' => function($param){ return (int)$param > 0; }),
                ),
            ),
        ));

        // ===== Watchlist =====
        register_rest_route($ns, '/watchlist', array(
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'get_watchlist'),
                'permission_callback' => array($this, 'check_read_permissions'),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'add_to_watchlist'),
                'permission_callback' => array($this, 'check_manage_permissions'),
            ),
        ));

        register_rest_route($ns, '/watchlist/(?P<coin_id>[a-zA-Z0-9\-_]+)', array(
            'methods'             => 'DELETE',
            'callback'            => array($this, 'remove_from_watchlist'),
            'permission_callback' => array($this, 'check_manage_permissions'),
            'args'                => array(
                'coin_id' => array('required' => true, 'validate_callback' => array($this, 'validate_coin_id')),
            ),
        ));

        // ===== Market (público) =====
        register_rest_route($ns, '/market/search', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'search_coins'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'q' => array('required' => true),
            ),
        ));

        register_rest_route($ns, '/market/prices', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_current_prices'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'ids' => array('required' => true),
            ),
        ));

        register_rest_route($ns, '/market/trending', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_trending_coins'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'limit' => array('validate_callback' => array($this, 'validate_limit')),
            ),
        ));

        // ===== Stats =====
        register_rest_route($ns, '/stats', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_user_stats'),
            'permission_callback' => array($this, 'check_read_permissions'),
        ));

        // ===== Import CSV =====
        register_rest_route($ns, '/transactions/import', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'import_transactions'),
            'permission_callback' => array($this, 'check_transaction_permissions'),
        ));
    }

    // ---------- Funciones de permisos mejoradas ----------
    
    /**
     * Verificar permisos básicos de lectura (para subscriber+)
     */
    public function check_read_permissions($request) {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'not_logged_in',
                __('You must be logged in to access this resource', 'Crypto-Portfolio-Tracker'),
                array('status' => 401)
            );
        }

        // Los usuarios con capacidad 'read' incluyen subscriber, author, editor, admin
        if (!current_user_can('read')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to view this content', 'Crypto-Portfolio-Tracker'),
                array('status' => 403)
            );
        }

        return true;
    }

    /**
     * Verificar permisos de gestión de portfolio (para subscriber+)
     */
    public function check_manage_permissions($request) {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'not_logged_in',
                __('You must be logged in to perform this action', 'Crypto-Portfolio-Tracker'),
                array('status' => 401)
            );
        }

        // Verificar capacidad básica de lectura
        if (!current_user_can('read')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to manage portfolio data', 'Crypto-Portfolio-Tracker'),
                array('status' => 403)
            );
        }

        return true;
    }

    /**
     * Verificar permisos para transacciones (incluye límites)
     */
    public function check_transaction_permissions($request) {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'not_logged_in',
                __('You must be logged in to manage transactions', 'Crypto-Portfolio-Tracker'),
                array('status' => 401)
            );
        }

        // Verificar capacidad básica
        if (!current_user_can('read')) {
            return new WP_Error(
                'insufficient_permissions',
                __('You do not have permission to manage transactions', 'Crypto-Portfolio-Tracker'),
                array('status' => 403)
            );
        }

        // Para nuevas transacciones (POST), verificar límites
        if ($request->get_method() === 'POST') {
            $user_id = get_current_user_id();
            
            // Usar la validación existente
            if (class_exists('CPT_Validation') && !CPT_Validation::user_can_add_transaction($user_id)) {
                return new WP_Error(
                    'transaction_limit_exceeded',
                    __('You have reached the maximum number of transactions allowed', 'Crypto-Portfolio-Tracker'),
                    array('status' => 429)
                );
            }
        }

        return true;
    }

    /**
     * Función legacy mantenida para compatibilidad
     * @deprecated Usar check_read_permissions, check_manage_permissions o check_transaction_permissions
     */
    public function check_user_permissions($request) {
        return $this->check_read_permissions($request);
    }
    
    public function validate_coin_id($param) {
        return is_string($param) && preg_match('~^[a-zA-Z0-9\-_]+$~', $param);
    }
    
    public function validate_limit($param) {
        $n = (int)$param;
        return $n > 0 && $n <= 1000; // cap razonable
    }
    
    private function norm_coin_id($id) {
        $id = strtolower((string)$id);
        return preg_replace('~[^a-z0-9\-_]~', '', $id);
    }
    
    private function ok($data, $status = 200) {
        return new WP_REST_Response($data, $status);
    }
    
    private function fail($code, $message, $status = 400) {
        return new WP_Error($code, $message, array('status' => $status));
    }

    // ================== Portfolio ==================
    public function get_portfolio($request) {
        $user_id   = get_current_user_id();
        $portfolio = $this->database->get_user_portfolio($user_id);

        if (empty($portfolio)) {
            return $this->ok(array());
        }

        // Obtener precios actuales de CoinGecko
        if ($this->coingecko) {
            $coin_ids = array();
            foreach ($portfolio as $item) {
                if (!empty($item->coin_id)) {
                    $coin_ids[] = $item->coin_id;
                }
            }
            
            if (!empty($coin_ids)) {
                // Usar método que funciona con batch de IDs
                $market_data = $this->coingecko->get_coins_with_market_data($coin_ids);
                
                foreach ($portfolio as &$item) {
                    $coin_id = strtolower($item->coin_id);
                    
                    if (isset($market_data[$coin_id])) {
                        $item->current_price = (float)$market_data[$coin_id]['price'];
                        $item->price_change_24h = (float)$market_data[$coin_id]['price_change_24h'];
                        $item->market_cap = isset($market_data[$coin_id]['market_cap']) ? (float)$market_data[$coin_id]['market_cap'] : null;
                        
                        // Calcular valor actual y P&L
                        $item->current_value = $item->current_price * $item->total_amount;
                        $item->profit_loss = $item->current_value - $item->total_invested;
                        $item->profit_loss_percentage = $item->total_invested > 0 ? (($item->profit_loss / $item->total_invested) * 100) : 0;
                    }
                }
            }
        }

        return $this->ok($portfolio);
    }

    public function update_portfolio($request) {
        $user_id = get_current_user_id();

        $coin_id        = sanitize_text_field($request->get_param('coin_id'));
        $coin_symbol    = sanitize_text_field($request->get_param('coin_symbol'));
        $coin_name      = sanitize_text_field($request->get_param('coin_name'));
        $total_amount   = (float) $request->get_param('total_amount');
        $avg_buy_price  = (float) $request->get_param('avg_buy_price');
        $total_invested = (float) $request->get_param('total_invested');

        if (empty($coin_id) || $total_amount <= 0 || $avg_buy_price < 0 || $total_invested < 0) {
            return $this->fail('invalid_data', __('Invalid portfolio data', 'Crypto-Portfolio-Tracker'));
        }

        $saved = $this->database->update_portfolio_holding(
            $user_id,
            $coin_id,
            $coin_symbol,
            $coin_name,
            $total_amount,
            $avg_buy_price,
            $total_invested
        );

        if (!$saved) {
            return $this->fail('update_failed', __('Failed to update portfolio', 'Crypto-Portfolio-Tracker'));
        }

        return $this->ok(array('message' => __('Portfolio updated successfully', 'Crypto-Portfolio-Tracker')));
    }

    public function clean_duplicates($request) {
        $user_id = get_current_user_id();
        $cleaned = $this->database->clean_portfolio_duplicates($user_id);
        
        return $this->ok(array(
            'message' => sprintf(__('Cleaned %d duplicate entries', 'Crypto-Portfolio-Tracker'), $cleaned)
        ));
    }

    public function delete_portfolio_item($request) {
        $user_id = get_current_user_id();
        $coin_id = $this->norm_coin_id($request->get_param('coin_id'));

        $deleted = $this->database->delete_portfolio_holding($user_id, $coin_id);

        if (!$deleted) {
            return $this->fail('delete_failed', __('Failed to delete portfolio item', 'Crypto-Portfolio-Tracker'));
        }

        return $this->ok(array('message' => __('Portfolio item deleted successfully', 'Crypto-Portfolio-Tracker')));
    }

    // ================== Transacciones ==================
    public function get_transactions($request) {
        $user_id = get_current_user_id();
        $limit   = (int) $request->get_param('limit') ?: 50;
        $coin_id = $request->get_param('coin_id');

        $coin_id = $coin_id ? $this->norm_coin_id($coin_id) : null;

        $transactions = $this->database->get_user_transactions($user_id, $limit, $coin_id);

        // Convertir a formato API
        foreach ($transactions as &$tx) {
            $tx->amount = (float) $tx->amount;
            $tx->price_per_coin = (float) $tx->price_per_coin;
            $tx->total_cost = (float) $tx->total_cost;
        }

        return $this->ok($transactions);
    }

    public function add_transaction($request) {
        $user_id = get_current_user_id();
        $data = $request->get_json_params();
        
        // Si no hay datos JSON, intentar obtener de parámetros POST
        if (empty($data)) {
            $data = $request->get_params();
        }
        
        // Log para debug
        error_log('CPT: Datos recibidos para transacción: ' . print_r($data, true));

        // Validaciones de campos requeridos - CORREGIDO: usar los nombres exactos que envía el frontend
        $required_fields = array(
            'coin_symbol' => 'Cryptocurrency Symbol', 
            'coin_name' => 'Cryptocurrency Name',
            'type' => 'Transaction Type',        // Frontend envía "type"
            'amount' => 'Amount',                // Frontend envía "amount"
            'price' => 'Price per Coin',         // Frontend envía "price"
            'total' => 'Total Cost',             // Frontend envía "total"
            'date' => 'Transaction Date'         // Frontend envía "date"
        );

        foreach ($required_fields as $field => $label) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                error_log("CPT: Campo faltante: {$field} = " . (isset($data[$field]) ? $data[$field] : 'NOT_SET'));
                return $this->fail('missing_field', "Campo requerido faltante: {$label}", 400);
            }
        }

        // Validar tipo de transacción
        $transaction_type = strtolower(sanitize_text_field($data['type']));
        if (!in_array($transaction_type, array('buy', 'sell'), true)) {
            return $this->fail('invalid_type', 'Tipo de transacción inválido. Debe ser "buy" o "sell"', 400);
        }

        // Validar y sanitizar campos numéricos
        $amount = (float) $data['amount'];      // Total amount invested (from frontend)
        $price_per_coin = (float) $data['price']; // Price per coin (from frontend) 
        $total_cost = (float) $data['total'];   // Should be quantity * price (from frontend)

        if ($amount <= 0) {
            return $this->fail('invalid_amount', 'La cantidad debe ser mayor a 0', 400);
        }

        if ($price_per_coin < 0) {
            return $this->fail('invalid_price', 'El precio no puede ser negativo', 400);
        }

        if ($total_cost <= 0) {
            return $this->fail('invalid_total', 'El total debe ser mayor a 0', 400);
        }

        // Sanitizar campos de texto
        $coin_id = isset($data['coin_id']) ? sanitize_text_field($data['coin_id']) : strtolower($data['coin_symbol']);
        $coin_symbol = strtoupper(sanitize_text_field($data['coin_symbol']));
        $coin_name = sanitize_text_field($data['coin_name']);
        $notes = isset($data['notes']) ? sanitize_textarea_field($data['notes']) : '';

        // Validar y procesar fecha
        $transaction_date = sanitize_text_field($data['date']);
        
        // Usar la clase de validación si está disponible
        if (class_exists('CPT_Validation')) {
            $valid_date = CPT_Validation::validate_date($transaction_date);
            if (!$valid_date) {
                return $this->fail('invalid_date', 'Formato de fecha inválido', 400);
            }
            $transaction_date = $valid_date;
        } else {
            // Validación básica de fecha
            $dt = DateTime::createFromFormat('Y-m-d H:i:s', $transaction_date);
            if (!$dt) {
                $dt = DateTime::createFromFormat('Y-m-d', $transaction_date);
                if ($dt) {
                    $transaction_date = $dt->format('Y-m-d 00:00:00');
                } else {
                    return $this->fail('invalid_date', 'Formato de fecha inválido. Use YYYY-MM-DD', 400);
                }
            }
        }

        // Preparar datos para la base de datos usando los nombres de campos que espera la clase Database
        $transaction_data = array(
            'coin_id'     => $coin_id,
            'coin_symbol' => $coin_symbol,
            'coin_name'   => $coin_name,
            'type'        => $transaction_type,  // La clase Database espera 'type', no 'transaction_type'
            'amount'      => $amount,
            'price'       => $price_per_coin,    // La clase Database espera 'price', no 'price_per_coin'
            'total'       => $total_cost,        // La clase Database espera 'total', no 'total_cost'
            'fees'        => isset($data['fees']) ? (float) $data['fees'] : 0.0,
            'exchange'    => isset($data['exchange']) ? sanitize_text_field($data['exchange']) : '',
            'notes'       => $notes,
            'date'        => $transaction_date   // Frontend envía 'date'
        );

        // Log de los datos procesados
        error_log('CPT: Datos procesados para BD: ' . print_r($transaction_data, true));

        // Verificar límites de transacciones si la validación está disponible
        if (class_exists('CPT_Validation') && !CPT_Validation::user_can_add_transaction($user_id)) {
            return $this->fail('transaction_limit', 'Has alcanzado el límite máximo de transacciones permitidas', 429);
        }

        // Intentar guardar en la base de datos
        $result = $this->database->add_transaction($user_id, $transaction_data);
        
        if ($result === false) {
            error_log('CPT: Error al guardar transacción en BD. Last error: ' . (isset($this->database->wpdb) ? $this->database->wpdb->last_error : 'No disponible'));
            return $this->fail('save_failed', 'Error al guardar la transacción en la base de datos', 500);
        }

        // Obtener el ID de la transacción insertada
        global $wpdb;
        $transaction_id = $wpdb->insert_id;

        // Recalcular portfolio de manera segura
        try {
            // Solo usar el método de Database que sí funciona
            if (method_exists($this->database, 'recalculate_portfolio_public')) {
                error_log('CPT: Usando método público de recalculación...');
                $this->database->recalculate_portfolio_public($user_id, $coin_id);
                error_log('CPT: Recalculación completada exitosamente');
            } else {
                error_log('CPT: Método de recalculación no disponible, pero transacción guardada');
            }
        } catch (Exception $e) {
            error_log('CPT: Error en recalculation: ' . $e->getMessage());
            // No fallar la transacción por un error de recálculo
        }

        return $this->ok(array(
            'success' => true,
            'message' => __('Transaction added successfully', 'Crypto-Portfolio-Tracker'),
            'transaction_id' => $transaction_id,
            'data' => $transaction_data
        ), 201);
    }

    public function update_transaction($request) {
        $user_id = get_current_user_id();
        $transaction_id = (int) $request->get_param('id');

        // Verificar que la transacción pertenece al usuario
        $transaction = $this->database->get_transaction_by_id($transaction_id, $user_id);
        if (!$transaction) {
            return $this->fail('not_found', __('Transaction not found', 'Crypto-Portfolio-Tracker'), 404);
        }

        $coin_id        = sanitize_text_field($request->get_param('coin_id'));
        $coin_symbol    = sanitize_text_field($request->get_param('coin_symbol'));
        $coin_name      = sanitize_text_field($request->get_param('coin_name'));
        $transaction_type = sanitize_text_field($request->get_param('transaction_type'));
        $amount         = (float) $request->get_param('amount');
        $price_per_coin = (float) $request->get_param('price_per_coin');
        $total_cost     = (float) $request->get_param('total_cost');
        $transaction_date = sanitize_text_field($request->get_param('transaction_date'));
        $notes          = sanitize_textarea_field($request->get_param('notes'));

        // Validaciones (reutilizar la misma lógica del add)
        if (empty($coin_id) || empty($transaction_type) || $amount <= 0 || $price_per_coin < 0) {
            return $this->fail('invalid_data', __('Invalid transaction data', 'Crypto-Portfolio-Tracker'));
        }

        if (!in_array($transaction_type, array('buy', 'sell'), true)) {
            return $this->fail('invalid_type', __('Invalid transaction type', 'Crypto-Portfolio-Tracker'));
        }

        $updated = $this->database->update_transaction(
            $transaction_id,
            $user_id,
            $coin_id,
            $coin_symbol,
            $coin_name,
            $transaction_type,
            $amount,
            $price_per_coin,
            $total_cost,
            $transaction_date,
            $notes
        );

        if (!$updated) {
            return $this->fail('update_failed', __('Failed to update transaction', 'Crypto-Portfolio-Tracker'));
        }

        // Recalcular portfolio de manera segura
                try {
                    if (method_exists($this->database, 'recalculate_portfolio_public')) {
                        $this->database->recalculate_portfolio_public($user_id, $coin_id);
                        error_log('CPT: Portfolio recalculado exitosamente para user ' . $user_id);
                    }
                } catch (Exception $e) {
                    error_log('CPT: Error en recalculation: ' . $e->getMessage());
                }

                return $this->ok(array('message' => __('Transaction updated successfully', 'Crypto-Portfolio-Tracker')));

        return $this->ok(array('message' => __('Transaction updated successfully', 'Crypto-Portfolio-Tracker')));
    }

    public function delete_transaction($request) {
        $user_id = get_current_user_id();
        $transaction_id = (int) $request->get_param('id');

        // Verificar que la transacción pertenece al usuario
        $transaction = $this->database->get_transaction_by_id($transaction_id, $user_id);
        if (!$transaction) {
            return $this->fail('not_found', __('Transaction not found', 'Crypto-Portfolio-Tracker'), 404);
        }

        $deleted = $this->database->delete_transaction($transaction_id, $user_id);

        if (!$deleted) {
            return $this->fail('delete_failed', __('Failed to delete transaction', 'Crypto-Portfolio-Tracker'));
        }

        // Recalcular portfolio de manera segura
        try {
            if (method_exists($this->database, 'recalculate_portfolio_public')) {
                $this->database->recalculate_portfolio_public($user_id, $transaction->coin_id);
                error_log('CPT: Portfolio recalculado después de eliminar transacción');
            }
        } catch (Exception $e) {
            error_log('CPT: Error en recalculation tras eliminación: ' . $e->getMessage());
        }

        return $this->ok(array('message' => __('Transaction deleted successfully', 'Crypto-Portfolio-Tracker')));
    }

    // ================== Watchlist ==================
    public function get_watchlist($request) {
        $user_id = get_current_user_id();
        $watchlist = $this->database->get_user_watchlist($user_id);

        // Obtener precios actuales
        if (!empty($watchlist) && $this->coingecko) {
            $coin_ids = array();
            foreach ($watchlist as $item) {
                $coin_ids[] = $item->coin_id;
            }

            $market_data = $this->coingecko->get_coins_with_market_data($coin_ids);
            
            foreach ($watchlist as &$item) {
                $coin_id = strtolower($item->coin_id);
                if (isset($market_data[$coin_id])) {
                    $item->current_price = (float)$market_data[$coin_id]['price'];
                    $item->price_change_24h = (float)$market_data[$coin_id]['price_change_24h'];
                    $item->market_cap = isset($market_data[$coin_id]['market_cap']) ? (float)$market_data[$coin_id]['market_cap'] : null;
                }
            }
        }

        return $this->ok($watchlist);
    }

    public function add_to_watchlist($request) {
        $user_id = get_current_user_id();

        $coin_id     = sanitize_text_field($request->get_param('coin_id'));
        $coin_symbol = sanitize_text_field($request->get_param('coin_symbol'));
        $coin_name   = sanitize_text_field($request->get_param('coin_name'));

        if (empty($coin_id)) {
            return $this->fail('invalid_data', __('Coin ID is required', 'Crypto-Portfolio-Tracker'));
        }

        $added = $this->database->add_to_watchlist($user_id, $coin_id, $coin_symbol, $coin_name);

        if (!$added) {
            return $this->fail('add_failed', __('Failed to add to watchlist', 'Crypto-Portfolio-Tracker'));
        }

        return $this->ok(array('message' => __('Added to watchlist successfully', 'Crypto-Portfolio-Tracker')));
    }

    public function remove_from_watchlist($request) {
        $user_id = get_current_user_id();
        $coin_id = $this->norm_coin_id($request->get_param('coin_id'));

        $removed = $this->database->remove_from_watchlist($user_id, $coin_id);

        if (!$removed) {
            return $this->fail('remove_failed', __('Failed to remove from watchlist', 'Crypto-Portfolio-Tracker'));
        }

        return $this->ok(array('message' => __('Removed from watchlist successfully', 'Crypto-Portfolio-Tracker')));
    }

    // ================== Market Data ==================
    public function search_coins($request) {
        $query = sanitize_text_field($request->get_param('q'));

        if (empty($query) || strlen($query) < 2) {
            return $this->fail('query_too_short', __('Search query must be at least 2 characters', 'Crypto-Portfolio-Tracker'));
        }

        if (!$this->coingecko) {
            return $this->fail('api_unavailable', __('CoinGecko API not available', 'Crypto-Portfolio-Tracker'));
        }

        $results = $this->coingecko->search_coins($query);

        if (is_wp_error($results)) {
            return $this->fail('search_failed', $results->get_error_message());
        }

        return $this->ok($results);
    }

    public function get_current_prices($request) {
        $ids = sanitize_text_field($request->get_param('ids'));

        if (empty($ids)) {
            return $this->fail('missing_ids', __('Coin IDs are required', 'Crypto-Portfolio-Tracker'));
        }

        if (!$this->coingecko) {
            return $this->fail('api_unavailable', __('CoinGecko API not available', 'Crypto-Portfolio-Tracker'));
        }

        $coin_ids = explode(',', $ids);
        $coin_ids = array_map('trim', $coin_ids);
        
        $prices = $this->coingecko->get_simple_prices($coin_ids);

        if (is_wp_error($prices)) {
            return $this->fail('prices_failed', $prices->get_error_message());
        }

        return $this->ok($prices);
    }

    public function get_trending_coins($request) {
        $limit = (int) $request->get_param('limit') ?: 10;

        if (!$this->coingecko) {
            return $this->fail('api_unavailable', __('CoinGecko API not available', 'Crypto-Portfolio-Tracker'));
        }

        $trending = $this->coingecko->get_trending_coins($limit);

        if (is_wp_error($trending)) {
            return $this->fail('trending_failed', $trending->get_error_message());
        }

        return $this->ok($trending);
    }

    // ================== Stats ==================
    public function get_user_stats($request) {
        $user_id = get_current_user_id();

        $stats = array(
            'total_portfolio_value' => 0,
            'total_invested' => 0,
            'total_profit_loss' => 0,
            'total_profit_loss_percentage' => 0,
            'total_transactions' => 0,
            'unique_coins' => 0,
            'biggest_holding' => null,
            'best_performer' => null,
            'worst_performer' => null,
        );

        // Obtener portfolio del usuario
        $portfolio = $this->database->get_user_portfolio($user_id);
        
        if (!empty($portfolio)) {
            $total_value = 0;
            $total_invested = 0;
            $best_performer = null;
            $worst_performer = null;
            $biggest_holding = null;

            foreach ($portfolio as $holding) {
                $current_value = isset($holding->current_value) ? $holding->current_value : ($holding->total_amount * $holding->avg_buy_price);
                $invested = $holding->total_invested;
                $profit_loss = $current_value - $invested;
                $profit_loss_percentage = $invested > 0 ? (($profit_loss / $invested) * 100) : 0;

                $total_value += $current_value;
                $total_invested += $invested;

                // Encontrar el mejor y peor performer
                if ($best_performer === null || $profit_loss_percentage > $best_performer['profit_loss_percentage']) {
                    $best_performer = array(
                        'coin_symbol' => $holding->coin_symbol,
                        'coin_name' => $holding->coin_name,
                        'profit_loss_percentage' => $profit_loss_percentage,
                        'profit_loss' => $profit_loss,
                    );
                }

                if ($worst_performer === null || $profit_loss_percentage < $worst_performer['profit_loss_percentage']) {
                    $worst_performer = array(
                        'coin_symbol' => $holding->coin_symbol,
                        'coin_name' => $holding->coin_name,
                        'profit_loss_percentage' => $profit_loss_percentage,
                        'profit_loss' => $profit_loss,
                    );
                }

                // Encontrar el holding más grande por valor
                if ($biggest_holding === null || $current_value > $biggest_holding['current_value']) {
                    $biggest_holding = array(
                        'coin_symbol' => $holding->coin_symbol,
                        'coin_name' => $holding->coin_name,
                        'current_value' => $current_value,
                        'percentage_of_portfolio' => 0, // Se calculará después
                    );
                }
            }

            $stats['total_portfolio_value'] = $total_value;
            $stats['total_invested'] = $total_invested;
            $stats['total_profit_loss'] = $total_value - $total_invested;
            $stats['total_profit_loss_percentage'] = $total_invested > 0 ? ((($total_value - $total_invested) / $total_invested) * 100) : 0;
            $stats['unique_coins'] = count($portfolio);
            $stats['best_performer'] = $best_performer;
            $stats['worst_performer'] = $worst_performer;
            $stats['biggest_holding'] = $biggest_holding;

            // Calcular porcentaje del portfolio para el holding más grande
            if ($biggest_holding && $total_value > 0) {
                $stats['biggest_holding']['percentage_of_portfolio'] = ($biggest_holding['current_value'] / $total_value) * 100;
            }
        }

        // Obtener número total de transacciones
        $transaction_count = $this->database->get_user_transaction_count($user_id);
        $stats['total_transactions'] = (int) $transaction_count;

        return $this->ok($stats);
    }

    // ================== Import CSV ==================
    public function import_transactions($request) {
        $user_id = get_current_user_id();

        // Verificar que se subió un archivo
        $files = $request->get_file_params();
        if (empty($files['csv_file'])) {
            return $this->fail('no_file', __('No CSV file uploaded', 'Crypto-Portfolio-Tracker'));
        }

        $file = $files['csv_file'];

        // Validar tipo de archivo
        $allowed_types = array('text/csv', 'text/plain', 'application/csv');
        if (!in_array($file['type'], $allowed_types)) {
            return $this->fail('invalid_file_type', __('File must be a CSV', 'Crypto-Portfolio-Tracker'));
        }

        // Validar tamaño (máx 2MB)
        if ($file['size'] > 2097152) {
            return $this->fail('file_too_large', __('File size must be less than 2MB', 'Crypto-Portfolio-Tracker'));
        }

        // Leer y procesar el archivo CSV
        $csv_data = file_get_contents($file['tmp_name']);
        if ($csv_data === false) {
            return $this->fail('read_error', __('Could not read CSV file', 'Crypto-Portfolio-Tracker'));
        }

        $lines = str_getcsv($csv_data, "\n");
        if (empty($lines)) {
            return $this->fail('empty_file', __('CSV file is empty', 'Crypto-Portfolio-Tracker'));
        }

        // Procesar header
        $header = str_getcsv(array_shift($lines));
        $header = array_map('trim', $header);
        $header = array_map('strtolower', $header);

        // Mapear columnas esperadas
        $required_fields = array('coin_id', 'coin_symbol', 'transaction_type', 'amount', 'price_per_coin', 'transaction_date');
        $field_map = array();

        foreach ($required_fields as $field) {
            $found = false;
            foreach ($header as $index => $col) {
                if (strpos($col, str_replace('_', '', $field)) !== false || 
                    strpos($col, str_replace('_', ' ', $field)) !== false ||
                    $col === $field) {
                    $field_map[$field] = $index;
                    $found = true;
                    break;
                }
            }
            if (!$found && $field !== 'coin_id') { // coin_id puede ser opcional si hay coin_symbol
                return $this->fail('missing_column', sprintf(__('Required column "%s" not found in CSV', 'Crypto-Portfolio-Tracker'), $field));
            }
        }

        $imported = 0;
        $errors = array();
        $max_errors = 10; // Limitar errores reportados

        foreach ($lines as $line_num => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $row = str_getcsv($line);
            
            // Extraer datos de la fila
            $transaction_data = array();
            
            foreach ($field_map as $field => $index) {
                $transaction_data[$field] = isset($row[$index]) ? trim($row[$index]) : '';
            }

            // Validar datos de la transacción
            $coin_symbol = sanitize_text_field($transaction_data['coin_symbol']);
            $transaction_type = strtolower(sanitize_text_field($transaction_data['transaction_type']));
            $amount = (float) $transaction_data['amount'];
            $price_per_coin = (float) $transaction_data['price_per_coin'];
            $transaction_date = sanitize_text_field($transaction_data['transaction_date']);

            // Validaciones básicas
            if (empty($coin_symbol) || !in_array($transaction_type, array('buy', 'sell')) || $amount <= 0 || $price_per_coin < 0) {
                if (count($errors) < $max_errors) {
                    $errors[] = sprintf(__('Line %d: Invalid data', 'Crypto-Portfolio-Tracker'), $line_num + 2);
                }
                continue;
            }

            // Obtener coin_id si no está presente
            $coin_id = isset($transaction_data['coin_id']) ? $transaction_data['coin_id'] : strtolower($coin_symbol);
            
            // Validar fecha
            if (class_exists('CPT_Validation')) {
                $valid_date = CPT_Validation::validate_date($transaction_date);
                if (!$valid_date) {
                    if (count($errors) < $max_errors) {
                        $errors[] = sprintf(__('Line %d: Invalid date format', 'Crypto-Portfolio-Tracker'), $line_num + 2);
                    }
                    continue;
                }
                $transaction_date = $valid_date;
            }

            // Calcular total_cost
            $total_cost = $amount * $price_per_coin;

            // Intentar añadir la transacción
            $transaction_id = $this->database->add_transaction(
                $user_id,
                $coin_id,
                $coin_symbol,
                $coin_symbol, // coin_name = coin_symbol por defecto
                $transaction_type,
                $amount,
                $price_per_coin,
                $total_cost,
                $transaction_date,
                sprintf(__('Imported from CSV on %s', 'Crypto-Portfolio-Tracker'), current_time('Y-m-d H:i:s'))
            );

            if ($transaction_id) {
                $imported++;
            } else {
                if (count($errors) < $max_errors) {
                    $errors[] = sprintf(__('Line %d: Failed to save transaction', 'Crypto-Portfolio-Tracker'), $line_num + 2);
                }
            }
        }

        // Recalcular portfolio después de la importación
        if ($imported > 0 && class_exists('CPT_User_Portfolio')) {
            $portfolio = new CPT_User_Portfolio($user_id);
            $portfolio->recalculate_from_transactions();
        }

        $result = array(
            'imported' => $imported,
            'total_lines' => count($lines),
            'message' => sprintf(__('Imported %d transactions successfully', 'Crypto-Portfolio-Tracker'), $imported)
        );

        if (!empty($errors)) {
            $result['errors'] = $errors;
            if (count($errors) >= $max_errors) {
                $result['errors'][] = __('... and more errors', 'Crypto-Portfolio-Tracker');
            }
        }

        return $this->ok($result);
    }
}