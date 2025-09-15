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
                'permission_callback' => array($this, 'check_user_permissions'),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'update_portfolio'),
                'permission_callback' => array($this, 'check_user_permissions'),
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
            'permission_callback' => array($this, 'check_user_permissions'),
        ));

        register_rest_route($ns, '/portfolio/(?P<coin_id>[a-zA-Z0-9\-_]+)', array(
            'methods'             => 'DELETE',
            'callback'            => array($this, 'delete_portfolio_item'),
            'permission_callback' => array($this, 'check_user_permissions'),
            'args'                => array(
                'coin_id' => array('required' => true, 'validate_callback' => array($this, 'validate_coin_id')),
            ),
        ));

        // ===== Transactions =====
        register_rest_route($ns, '/transactions', array(
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'get_transactions'),
                'permission_callback' => array($this, 'check_user_permissions'),
                'args'                => array(
                    'limit'   => array('validate_callback' => array($this, 'validate_limit')),
                    'coin_id' => array('validate_callback' => array($this, 'validate_coin_id')),
                ),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'add_transaction'),
                'permission_callback' => array($this, 'check_user_permissions'),
            ),
        ));

        register_rest_route($ns, '/transactions/(?P<id>\d+)', array(
            array(
                'methods'             => 'PUT',
                'callback'            => array($this, 'update_transaction'),
                'permission_callback' => array($this, 'check_user_permissions'),
                'args'                => array(
                    'id' => array('required' => true, 'validate_callback' => function($param){ return (int)$param > 0; }),
                ),
            ),
            array(
                'methods'             => 'DELETE',
                'callback'            => array($this, 'delete_transaction'),
                'permission_callback' => array($this, 'check_user_permissions'),
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
                'permission_callback' => array($this, 'check_user_permissions'),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'add_to_watchlist'),
                'permission_callback' => array($this, 'check_user_permissions'),
            ),
        ));

        register_rest_route($ns, '/watchlist/(?P<coin_id>[a-zA-Z0-9\-_]+)', array(
            'methods'             => 'DELETE',
            'callback'            => array($this, 'remove_from_watchlist'),
            'permission_callback' => array($this, 'check_user_permissions'),
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
            'permission_callback' => array($this, 'check_user_permissions'),
        ));

        // ===== Import CSV =====
        register_rest_route($ns, '/transactions/import', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'import_transactions'),
            'permission_callback' => array($this, 'check_user_permissions'),
        ));
    }

    // ---------- Helpers de validación/permiso ----------
    public function check_user_permissions($request) {
        return is_user_logged_in();
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
                        
                        // Actualizar en base de datos para cache
                        $this->database->update_portfolio_item($user_id, array(
                            'coin_id'        => $item->coin_id,
                            'coin_symbol'    => $item->coin_symbol,
                            'coin_name'      => $item->coin_name,
                            'total_amount'   => $item->total_amount,
                            'avg_buy_price'  => $item->avg_buy_price,
                            'total_invested' => $item->total_invested,
                            'current_price'  => $item->current_price,
                        ));
                    } else {
                        // Fallback: intentar obtener precio individual
                        $price = $this->coingecko->get_coin_price($coin_id);
                        if ($price > 0) {
                            $item->current_price = (float)$price;
                            
                            // Actualizar en BD
                            $this->database->update_portfolio_item($user_id, array(
                                'coin_id'        => $item->coin_id,
                                'coin_symbol'    => $item->coin_symbol,
                                'coin_name'      => $item->coin_name,
                                'total_amount'   => $item->total_amount,
                                'avg_buy_price'  => $item->avg_buy_price,
                                'total_invested' => $item->total_invested,
                                'current_price'  => $item->current_price,
                            ));
                        }
                    }
                    
                    // Asegurar que price_change_24h existe
                    if (!isset($item->price_change_24h)) {
                        $item->price_change_24h = 0.0;
                    }
                }
                unset($item);
            }
        }

        // Log para debug
        error_log('CPT API Portfolio Response: ' . print_r($portfolio, true));

        return $this->ok($portfolio);
    }

    public function update_portfolio($request) {
        $user_id = get_current_user_id();
        $data    = (array) $request->get_json_params();

        $required = array('coin_id','coin_symbol','coin_name','total_amount','avg_buy_price','total_invested');
        foreach ($required as $f) {
            if (!isset($data[$f])) {
                return $this->fail('missing_field', "Campo requerido: {$f}", 400);
            }
        }

        $data['coin_id']        = $this->norm_coin_id($data['coin_id']);
        $data['coin_symbol']    = strtoupper((string)$data['coin_symbol']);
        $data['coin_name']      = (string)$data['coin_name'];
        $data['total_amount']   = (float)$data['total_amount'];
        $data['avg_buy_price']  = (float)$data['avg_buy_price'];
        $data['total_invested'] = (float)$data['total_invested'];

        // Precio actual (si hay API)
        $data['current_price'] = 0.0;
        if ($this->coingecko && !empty($data['coin_id'])) {
            $data['current_price'] = (float) $this->coingecko->get_coin_price($data['coin_id']);
        }

        $res = $this->database->update_portfolio_item($user_id, $data);
        if ($res === false) {
            return $this->fail('update_failed', 'Error al actualizar el portfolio', 500);
        }
        return $this->ok(array('success' => true, 'message' => 'Portfolio actualizado correctamente'));
    }

    public function delete_portfolio_item($request) {
        $user_id = get_current_user_id();
        $coin_id = $this->norm_coin_id($request['coin_id']);

        $res = $this->database->delete_portfolio_item($user_id, $coin_id);
        if ($res === false) {
            return $this->fail('delete_failed', 'Error al eliminar el item del portfolio', 500);
        }
        return $this->ok(array('success' => true, 'message' => 'Item eliminado correctamente'));
    }

    public function clean_duplicates($request) {
        $user_id = get_current_user_id();
        
        // Limpiar duplicados
        $this->database->clean_portfolio_duplicates($user_id);
        
        return $this->ok(array('success' => true, 'message' => 'Duplicados eliminados correctamente'));
    }

    // ================== Transactions ==================
    public function get_transactions($request) {
        $user_id = get_current_user_id();
        $limit   = (int) ($request->get_param('limit') ?: 100);
        $limit   = max(1, min($limit, 1000));
        $coin_id = $request->get_param('coin_id');

        if ($coin_id) {
            $coin_id = $this->norm_coin_id($coin_id);
            $txs = $this->database->get_coin_transactions($user_id, $coin_id, $limit);
        } else {
            $txs = $this->database->get_user_transactions($user_id, $limit);
        }
        return $this->ok($txs);
    }

    public function add_transaction($request) {
        $user_id = get_current_user_id();
        $data    = (array) $request->get_json_params();

        // Log para debug
        error_log('CPT: Datos recibidos para transacción: ' . print_r($data, true));

        $required = array('coin_symbol','coin_name','type','amount','price','total','date');
        foreach ($required as $f) {
            if (!isset($data[$f]) || $data[$f] === '') {
                return $this->fail('missing_field', "Campo requerido: {$f}", 400);
            }
        }

        // Validaciones y normalización
        $data['type'] = strtolower((string)$data['type']);
        if (!in_array($data['type'], array('buy','sell'), true)) {
            return $this->fail('invalid_type', 'Tipo de transacción inválido', 400);
        }

        // Normalizar coin_id - intentar obtener ID real de CoinGecko
        $coin_id = $this->norm_coin_id($data['coin_symbol']);
        if ($this->coingecko && !empty($data['coin_symbol'])) {
            // Buscar en CoinGecko para obtener el ID correcto
            $search_results = $this->coingecko->search_coins($data['coin_symbol']);
            if (!empty($search_results)) {
                foreach ($search_results as $result) {
                    if (strtoupper($result['symbol']) === strtoupper($data['coin_symbol'])) {
                        $coin_id = $result['id'];
                        break;
                    }
                }
            }
        }

        $data['coin_id'] = $coin_id;
        $data['coin_symbol'] = strtoupper((string)$data['coin_symbol']);
        $data['coin_name']   = (string)$data['coin_name'];
        
        // Usar valores exactos enviados por el usuario
        $quantity = (float)$data['amount']; // Cantidad exacta de tokens recibida
        $price = (float)$data['price'];     // Precio por unidad  
        $total = (float)$data['total'];     // Monto total invertido (incluyendo comisiones)
        
        if ($quantity <= 0 || $price <= 0 || $total <= 0) {
            return $this->fail('invalid_amounts', 'La cantidad, precio y total deben ser mayores a 0', 400);
        }

        // Preparar datos finales
        $transaction_data = array(
            'coin_id'     => $data['coin_id'],
            'coin_symbol' => $data['coin_symbol'],
            'coin_name'   => $data['coin_name'],
            'type'        => $data['type'],
            'amount'      => $quantity,    // Cantidad exacta de tokens/coins
            'price'       => $price,       // Precio por unidad
            'total'       => $total,       // Monto total invertido (real)
            'fees'        => isset($data['fees']) ? (float)$data['fees'] : 0.0,
            'exchange'    => isset($data['exchange']) ? (string)$data['exchange'] : '',
            'notes'       => isset($data['notes']) ? (string)$data['notes'] : '',
            'date'        => $data['date']
        );

        // Validar y normalizar fecha
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', (string)$transaction_data['date']);
        if (!$dt) {
            $alt = DateTime::createFromFormat('Y-m-d', (string)$transaction_data['date']);
            if ($alt) {
                $transaction_data['date'] = $alt->format('Y-m-d H:i:s');
            } else {
                return $this->fail('invalid_date', 'Formato de fecha inválido', 400);
            }
        }

        // Log para debug
        error_log('CPT: Datos procesados para BD: ' . print_r($transaction_data, true));

        $res = $this->database->add_transaction($user_id, $transaction_data);
        if ($res === false) {
            return $this->fail('transaction_failed', 'Error al agregar la transacción', 500);
        }

        global $wpdb;
        return $this->ok(array(
            'success' => true,
            'message' => 'Transacción agregada correctamente',
            'id'      => (int)$wpdb->insert_id,
        ), 201);
    }

    public function update_transaction($request) {
        $user_id        = get_current_user_id();
        $transaction_id = (int)$request['id'];
        $data           = (array) $request->get_json_params();

        // Verificar que la transacción pertenece al usuario
        $existing = $this->database->get_transaction($user_id, $transaction_id);
        if (!$existing) {
            return $this->fail('transaction_not_found', 'Transacción no encontrada', 404);
        }

        $required = array('coin_symbol','coin_name','type','amount','price','total','date');
        foreach ($required as $f) {
            if (!isset($data[$f]) || $data[$f] === '') {
                return $this->fail('missing_field', "Campo requerido: {$f}", 400);
            }
        }

        // Procesar igual que en add_transaction
        $data['type'] = strtolower((string)$data['type']);
        if (!in_array($data['type'], array('buy','sell'), true)) {
            return $this->fail('invalid_type', 'Tipo de transacción inválido', 400);
        }

        // Normalizar coin_id - mantener el existente o buscar nuevo
        $coin_id = $existing->coin_id;
        if ($this->coingecko && strtoupper($data['coin_symbol']) !== strtoupper($existing->coin_symbol)) {
            // Symbol cambió, buscar nuevo ID
            $search_results = $this->coingecko->search_coins($data['coin_symbol']);
            if (!empty($search_results)) {
                foreach ($search_results as $result) {
                    if (strtoupper($result['symbol']) === strtoupper($data['coin_symbol'])) {
                        $coin_id = $result['id'];
                        break;
                    }
                }
            }
        }

        $data['coin_id'] = $coin_id;
        $data['coin_symbol'] = strtoupper((string)$data['coin_symbol']);
        $data['coin_name']   = (string)$data['coin_name'];
        
        // Usar valores exactos enviados por el usuario
        $quantity = (float)$data['amount']; // Cantidad exacta de tokens
        $price = (float)$data['price'];     // Precio por unidad
        $total = (float)$data['total'];     // Monto total invertido
        
        if ($quantity <= 0 || $price <= 0 || $total <= 0) {
            return $this->fail('invalid_amounts', 'La cantidad, precio y total deben ser mayores a 0', 400);
        }

        $transaction_data = array(
            'coin_id'     => $data['coin_id'],
            'coin_symbol' => $data['coin_symbol'],
            'coin_name'   => $data['coin_name'],
            'type'        => $data['type'],
            'amount'      => $quantity,
            'price'       => $price,
            'total'       => $total,
            'fees'        => isset($data['fees']) ? (float)$data['fees'] : 0.0,
            'exchange'    => isset($data['exchange']) ? (string)$data['exchange'] : '',
            'notes'       => isset($data['notes']) ? (string)$data['notes'] : '',
            'date'        => $data['date']
        );

        // Validar fecha
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', (string)$transaction_data['date']);
        if (!$dt) {
            $alt = DateTime::createFromFormat('Y-m-d', (string)$transaction_data['date']);
            if ($alt) {
                $transaction_data['date'] = $alt->format('Y-m-d H:i:s');
            } else {
                return $this->fail('invalid_date', 'Formato de fecha inválido', 400);
            }
        }

        $res = $this->database->update_transaction($user_id, $transaction_id, $transaction_data);
        if ($res === false) {
            return $this->fail('update_failed', 'Error al actualizar la transacción', 500);
        }

        return $this->ok(array(
            'success' => true,
            'message' => 'Transacción actualizada correctamente'
        ));
    }

    public function delete_transaction($request) {
        $user_id        = get_current_user_id();
        $transaction_id = (int)$request['id'];

        $res = $this->database->delete_transaction($user_id, $transaction_id);
        if ($res === false) {
            return $this->fail('delete_failed', 'Error al eliminar la transacción', 500);
        }
        return $this->ok(array('success' => true, 'message' => 'Transacción eliminada correctamente'));
    }

    // ================== Watchlist ==================
    public function get_watchlist($request) {
        $user_id   = get_current_user_id();
        $watchlist = $this->database->get_user_watchlist($user_id);

        if (empty($watchlist) || !$this->coingecko) {
            return $this->ok($watchlist);
        }

        // Batch: precio + 24h change
        $ids = array();
        foreach ($watchlist as $w) {
            if (!empty($w->coin_id)) $ids[] = $w->coin_id;
        }
        $mkt = $this->coingecko->get_coins_with_market_data($ids);

        foreach ($watchlist as &$w) {
            $id = strtolower($w->coin_id);
            $w->current_price    = isset($mkt[$id]['price']) ? (float)$mkt[$id]['price'] : 0.0;
            $w->price_change_24h = isset($mkt[$id]['price_change_24h']) ? (float)$mkt[$id]['price_change_24h'] : 0.0;
        }
        unset($w);

        return $this->ok($watchlist);
    }

    public function add_to_watchlist($request) {
        $user_id = get_current_user_id();
        $data    = (array) $request->get_json_params();

        $required = array('coin_id','coin_symbol','coin_name');
        foreach ($required as $f) {
            if (!isset($data[$f])) {
                return $this->fail('missing_field', "Campo requerido: {$f}", 400);
            }
        }

        $data['coin_id']      = $this->norm_coin_id($data['coin_id']);
        $data['coin_symbol']  = strtoupper((string)$data['coin_symbol']);
        $data['coin_name']    = (string)$data['coin_name'];
        if (isset($data['target_price']) && $data['target_price'] !== '') {
            $data['target_price'] = (float)$data['target_price'];
        }

        $res = $this->database->add_to_watchlist($user_id, $data);
        if ($res === false) {
            return $this->fail('watchlist_failed', 'Error al agregar a la watchlist', 500);
        }
        return $this->ok(array('success' => true, 'message' => 'Agregado a la watchlist correctamente'));
    }

    public function remove_from_watchlist($request) {
        $user_id = get_current_user_id();
        $coin_id = $this->norm_coin_id($request['coin_id']);

        $res = $this->database->remove_from_watchlist($user_id, $coin_id);
        if ($res === false) {
            return $this->fail('remove_failed', 'Error al remover de la watchlist', 500);
        }
        return $this->ok(array('success' => true, 'message' => 'Removido de la watchlist correctamente'));
    }

    // ================== Market (público) ==================
    public function search_coins($request) {
        $q = trim((string)$request->get_param('q'));
        if ($q === '') {
            return $this->fail('missing_query', 'Parámetro de búsqueda requerido', 400);
        }
        if (!$this->coingecko) {
            return $this->ok(array());
        }
        $results = $this->coingecko->search_coins($q);
        return $this->ok($results);
    }

    public function get_current_prices($request) {
        $ids = $request->get_param('ids');
        if (empty($ids)) {
            return $this->fail('missing_ids', 'IDs de coins requeridos', 400);
        }
        if (is_string($ids)) {
            $ids = array_map('trim', explode(',', $ids));
        }
        if (!$this->coingecko) {
            return $this->ok(array());
        }
        $prices = $this->coingecko->get_coins_prices($ids);
        return $this->ok($prices);
    }

    public function get_trending_coins($request) {
        $limit = (int) ($request->get_param('limit') ?: 10);
        $limit = max(1, min($limit, 100));
        if (!$this->coingecko) {
            return $this->ok(array());
        }
        $trending = $this->coingecko->get_trending_coins($limit);
        return $this->ok($trending);
    }

    // ================== Stats ==================
    public function get_user_stats($request) {
        $user_id = get_current_user_id();
        $stats   = $this->database->get_user_stats($user_id);
        return $this->ok($stats);
    }

    // ================== Import CSV ==================
    public function import_transactions($request) {
        $user_id = get_current_user_id();
        $files   = $request->get_file_params();
        if (!isset($files['csv_file'])) {
            return $this->fail('no_file', 'Archivo CSV requerido', 400);
        }

        $file = $files['csv_file'];

        // Validar tipo (algunos hosts reportan text/plain para CSV)
        $allowed = array('text/csv','application/csv','text/plain','application/vnd.ms-excel');
        if (!in_array($file['type'], $allowed, true)) {
            // no detenemos por mimetype si la extensión es .csv
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                return $this->fail('invalid_file_type', 'Tipo de archivo inválido. Solo CSV.', 400);
            }
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            return $this->fail('file_read_error', 'Error al leer el archivo', 500);
        }

        $imported     = 0;
        $errors       = array();
        $line_number  = 0;

        // Esperamos headers: coin_id,coin_symbol,coin_name,type,amount,price,total,date,exchange,fees,notes
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return $this->fail('empty_file', 'El CSV está vacío o ilegible', 400);
        }

        while (($row = fgetcsv($handle)) !== false) {
            $line_number++;
            try {
                $tx = array(
                    'coin_id'    => isset($row[0]) ? $this->norm_coin_id($row[0]) : '',
                    'coin_symbol'=> isset($row[1]) ? strtoupper($row[1]) : '',
                    'coin_name'  => isset($row[2]) ? (string)$row[2] : '',
                    'type'       => isset($row[3]) ? strtolower($row[3]) : '',
                    'amount'     => isset($row[4]) ? (float)$row[4] : 0.0,
                    'price'      => isset($row[5]) ? (float)$row[5] : 0.0,
                    'total'      => isset($row[6]) ? (float)$row[6] : 0.0,
                    'date'       => isset($row[7]) ? (string)$row[7] : '',
                    'exchange'   => isset($row[8]) ? (string)$row[8] : '',
                    'fees'       => isset($row[9]) ? (float)$row[9] : 0.0,
                    'notes'      => isset($row[10]) ? (string)$row[10] : '',
                );

                if (empty($tx['coin_id']) || !in_array($tx['type'], array('buy','sell'), true) || $tx['amount'] <= 0) {
                    $errors[] = "Línea {$line_number}: Datos incompletos o inválidos";
                    continue;
                }

                // Normalizar fecha
                $dt = DateTime::createFromFormat('Y-m-d H:i:s', $tx['date']);
                if (!$dt) {
                    $alt = DateTime::createFromFormat('Y-m-d', $tx['date']);
                    if ($alt) {
                        $tx['date'] = $alt->format('Y-m-d H:i:s');
                    } else {
                        $errors[] = "Línea {$line_number}: Fecha inválida";
                        continue;
                    }
                }

                $res = $this->database->add_transaction($user_id, $tx);
                if ($res) {
                    $imported++;
                } else {
                    $errors[] = "Línea {$line_number}: Error al importar transacción";
                }

            } catch (\Throwable $e) {
                $errors[] = "Línea {$line_number}: " . $e->getMessage();
            }
        }

        fclose($handle);

        return $this->ok(array(
            'success'  => true,
            'imported' => $imported,
            'errors'   => $errors,
            'message'  => "Se importaron {$imported} transacciones correctamente",
        ));
    }

    public function debug_prices($request) {
        if (!$this->coingecko) {
            return $this->ok(array('error' => 'CoinGecko API no disponible'));
        }
        
        // Probar BTC directamente
        $btc_price_1 = $this->coingecko->get_coin_price('btc');
        $btc_price_2 = $this->coingecko->get_coin_price('bitcoin');
        
        // Probar con market data
        $market_data = $this->coingecko->get_coins_with_market_data(array('btc'));
        
        return $this->ok(array(
            'btc_price_symbol' => $btc_price_1,
            'btc_price_id' => $btc_price_2,
            'market_data' => $market_data,
            'test_timestamp' => current_time('mysql')
        ));
    }
}