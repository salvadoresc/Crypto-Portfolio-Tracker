<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crypto Portfolio Tracker – Database layer
 * - Uses dbDelta (no IF NOT EXISTS)
 * - Stable index names
 * - BIGINT UNSIGNED for ids
 * - Defensive logging
 */
class CPT_Database {

    /** @var wpdb */
    private $wpdb;

    /** @var string */
    private $portfolio_table;
    private $transactions_table;
    private $watchlist_table;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->portfolio_table    = $wpdb->prefix . 'cpt_portfolio';
        $this->transactions_table = $wpdb->prefix . 'cpt_transactions';
        $this->watchlist_table    = $wpdb->prefix . 'cpt_watchlist';
    }

    /**
     * Create/upgrade tables with dbDelta
     */
    public function create_tables() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $this->create_portfolio_table();
        $this->create_transactions_table();
        $this->create_watchlist_table();

        $this->verify_tables_created();

        update_option('cpt_db_version', CPT_VERSION);
        update_option('cpt_tables_created', true);
    }

    /**
     * Extra safety net to re-try any missing table and log errors
     */
    private function verify_tables_created() {
        $map = array(
            'portfolio'    => $this->portfolio_table,
            'transactions' => $this->transactions_table,
            'watchlist'    => $this->watchlist_table,
        );

        foreach ($map as $name => $table) {
            $exists = ($this->wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table);
            if (!$exists) {
                error_log("CPT DB: Table missing after dbDelta: {$name} ({$table}) – retrying creation.");
                switch ($name) {
                    case 'portfolio':
                        $this->create_portfolio_table();
                        break;
                    case 'transactions':
                        $this->create_transactions_table();
                        break;
                    case 'watchlist':
                        $this->create_watchlist_table();
                        break;
                }

                // log any SQL error from wpdb
                if (!empty($this->wpdb->last_error)) {
                    error_log("CPT DB ERROR ({$name}): " . $this->wpdb->last_error);
                }
            }
        }
    }

    /**
     * Portfolio table
     * - One row per (user_id, coin_id)
     */
    private function create_portfolio_table() {
        $charset_collate = $this->wpdb->get_charset_collate();
        $table = $this->portfolio_table;

        // IMPORTANT: no IF NOT EXISTS for dbDelta
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            coin_id VARCHAR(100) NOT NULL,
            coin_symbol VARCHAR(24) NOT NULL,
            coin_name VARCHAR(128) NOT NULL,

            total_amount DECIMAL(32,12) NOT NULL DEFAULT 0,
            avg_buy_price DECIMAL(32,12) NOT NULL DEFAULT 0,
            total_invested DECIMAL(32,12) NOT NULL DEFAULT 0,
            current_price DECIMAL(32,12) NOT NULL DEFAULT 0,

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            UNIQUE KEY cpt_portfolio_user_coin (user_id, coin_id),
            KEY cpt_portfolio_user (user_id),
            KEY cpt_portfolio_coin (coin_id)
        ) {$charset_collate};";

        $result = dbDelta($sql);
        error_log('CPT DB: dbDelta portfolio => ' . print_r($result, true));
    }

    /**
     * Transactions table
     * - Append-only ledger
     */
    private function create_transactions_table() {
        $charset_collate = $this->wpdb->get_charset_collate();
        $table = $this->transactions_table;

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            coin_id VARCHAR(100) NOT NULL,
            coin_symbol VARCHAR(24) NOT NULL,
            coin_name VARCHAR(128) NOT NULL,

            transaction_type ENUM('buy','sell') NOT NULL,
            amount DECIMAL(32,12) NOT NULL DEFAULT 0,
            price_per_coin DECIMAL(32,12) NOT NULL DEFAULT 0,
            total_value DECIMAL(32,12) NOT NULL DEFAULT 0,
            fees DECIMAL(32,12) NOT NULL DEFAULT 0,

            exchange VARCHAR(100) NOT NULL DEFAULT '',
            notes TEXT NULL,

            transaction_date DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            KEY cpt_transactions_user_date (user_id, transaction_date),
            KEY cpt_transactions_user_coin (user_id, coin_id),
            KEY cpt_transactions_coin (coin_id)
        ) {$charset_collate};";

        $result = dbDelta($sql);
        error_log('CPT DB: dbDelta transactions => ' . print_r($result, true));
    }

    /**
     * Watchlist table
     */
    private function create_watchlist_table() {
        $charset_collate = $this->wpdb->get_charset_collate();
        $table = $this->watchlist_table;

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            coin_id VARCHAR(100) NOT NULL,
            coin_symbol VARCHAR(24) NOT NULL,
            coin_name VARCHAR(128) NOT NULL,

            target_price DECIMAL(32,12) NULL,
            notes TEXT NULL,

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            UNIQUE KEY cpt_watchlist_user_coin_unique (user_id, coin_id),
            KEY cpt_watchlist_user (user_id)
        ) {$charset_collate};";

        $result = dbDelta($sql);
        error_log('CPT DB: dbDelta watchlist => ' . print_r($result, true));
    }

    // =========================
    // ===== DATA METHODS ======
    // =========================

    // Portfolio
    public function get_user_portfolio($user_id) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->portfolio_table} WHERE user_id = %d ORDER BY total_invested DESC",
                $user_id
            )
        );
    }

    public function update_portfolio_item($user_id, $coin_data) {
        // Cast defensively to avoid locale commas
        $total_amount   = isset($coin_data['total_amount']) ? (float)$coin_data['total_amount'] : 0.0;
        $avg_buy_price  = isset($coin_data['avg_buy_price']) ? (float)$coin_data['avg_buy_price'] : 0.0;
        $total_invested = isset($coin_data['total_invested']) ? (float)$coin_data['total_invested'] : 0.0;
        $current_price  = isset($coin_data['current_price']) ? (float)$coin_data['current_price'] : 0.0;

        return $this->wpdb->replace(
            $this->portfolio_table,
            array(
                'user_id'        => (int)$user_id,
                'coin_id'        => (string)$coin_data['coin_id'],
                'coin_symbol'    => (string)$coin_data['coin_symbol'],
                'coin_name'      => (string)$coin_data['coin_name'],
                'total_amount'   => $total_amount,
                'avg_buy_price'  => $avg_buy_price,
                'total_invested' => $total_invested,
                'current_price'  => $current_price,
                // updated_at se autogestiona con ON UPDATE
            ),
            array('%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f')
        );
    }

    public function delete_portfolio_item($user_id, $coin_id) {
        return $this->wpdb->delete(
            $this->portfolio_table,
            array('user_id' => (int)$user_id, 'coin_id' => (string)$coin_id),
            array('%d', '%s')
        );
    }

    // Transactions
    public function add_transaction($user_id, $tx) {
        $result = $this->wpdb->insert(
            $this->transactions_table,
            array(
                'user_id'          => (int)$user_id,
                'coin_id'          => (string)$tx['coin_id'],
                'coin_symbol'      => (string)$tx['coin_symbol'],
                'coin_name'        => (string)$tx['coin_name'],
                'transaction_type' => (string)$tx['type'],   // 'buy' | 'sell'
                'amount'           => (float)$tx['amount'],
                'price_per_coin'   => (float)$tx['price'],
                'total_value'      => (float)$tx['total'],
                'fees'             => isset($tx['fees']) ? (float)$tx['fees'] : 0,
                'exchange'         => isset($tx['exchange']) ? (string)$tx['exchange'] : '',
                'notes'            => isset($tx['notes']) ? (string)$tx['notes'] : '',
                'transaction_date' => (string)$tx['date'],   // 'Y-m-d H:i:s'
            ),
            array('%d','%s','%s','%s','%s','%f','%f','%f','%f','%s','%s','%s')
        );

        if ($result) {
            $this->recalculate_portfolio($user_id, $tx['coin_id']);
        } else if (!empty($this->wpdb->last_error)) {
            error_log('CPT DB ERROR add_transaction: ' . $this->wpdb->last_error);
        }

        return $result;
    }

    public function get_user_transactions($user_id, $limit = 100) {
        $limit = max(1, (int)$limit);
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->transactions_table}
                 WHERE user_id = %d
                 ORDER BY transaction_date DESC
                 LIMIT %d",
                $user_id, $limit
            )
        );
    }

    public function get_transaction($user_id, $transaction_id) {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->transactions_table} WHERE id = %d AND user_id = %d",
                $transaction_id, $user_id
            )
        );
    }

    public function update_transaction($user_id, $transaction_id, $tx) {
        // Verificar que la transacción pertenece al usuario
        $existing = $this->get_transaction($user_id, $transaction_id);
        if (!$existing) {
            return false;
        }

        $old_coin_id = $existing->coin_id;

        $result = $this->wpdb->update(
            $this->transactions_table,
            array(
                'coin_id'          => (string)$tx['coin_id'],
                'coin_symbol'      => (string)$tx['coin_symbol'],
                'coin_name'        => (string)$tx['coin_name'],
                'transaction_type' => (string)$tx['type'],
                'amount'           => (float)$tx['amount'],
                'price_per_coin'   => (float)$tx['price'],
                'total_value'      => (float)$tx['total'],
                'fees'             => isset($tx['fees']) ? (float)$tx['fees'] : 0,
                'exchange'         => isset($tx['exchange']) ? (string)$tx['exchange'] : '',
                'notes'            => isset($tx['notes']) ? (string)$tx['notes'] : '',
                'transaction_date' => (string)$tx['date'],
            ),
            array(
                'id'      => (int)$transaction_id,
                'user_id' => (int)$user_id
            ),
            array('%s','%s','%s','%s','%f','%f','%f','%f','%s','%s','%s'),
            array('%d','%d')
        );

        if ($result !== false) {
            // Recalcular portfolio para ambas monedas (la anterior y la nueva)
            $this->recalculate_portfolio($user_id, $old_coin_id);
            if ($old_coin_id !== $tx['coin_id']) {
                $this->recalculate_portfolio($user_id, $tx['coin_id']);
            }
        } else if (!empty($this->wpdb->last_error)) {
            error_log('CPT DB ERROR update_transaction: ' . $this->wpdb->last_error);
        }

        return $result;
    }

    public function delete_transaction($user_id, $transaction_id) {
        $tx = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->transactions_table} WHERE id = %d AND user_id = %d",
                $transaction_id, $user_id
            )
        );

        if (!$tx) return false;

        $deleted = $this->wpdb->delete(
            $this->transactions_table,
            array('id' => (int)$transaction_id, 'user_id' => (int)$user_id),
            array('%d','%d')
        );

        if ($deleted) {
            $this->recalculate_portfolio($user_id, $tx->coin_id);
        }

        return $deleted;
    }

    // Watchlist
    public function get_user_watchlist($user_id) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->watchlist_table} WHERE user_id = %d ORDER BY created_at DESC",
                $user_id
            )
        );
    }

    public function add_to_watchlist($user_id, $coin) {
        $target_price = isset($coin['target_price']) && $coin['target_price'] !== '' ? (float)$coin['target_price'] : null;

        return $this->wpdb->replace(
            $this->watchlist_table,
            array(
                'user_id'      => (int)$user_id,
                'coin_id'      => (string)$coin['coin_id'],
                'coin_symbol'  => (string)$coin['coin_symbol'],
                'coin_name'    => (string)$coin['coin_name'],
                'target_price' => $target_price,
                'notes'        => isset($coin['notes']) ? (string)$coin['notes'] : '',
            ),
            array('%d','%s','%s','%s','%f','%s')
        );
    }

    public function remove_from_watchlist($user_id, $coin_id) {
        return $this->wpdb->delete(
            $this->watchlist_table,
            array('user_id' => (int)$user_id, 'coin_id' => (string)$coin_id),
            array('%d','%s')
        );
    }

    /**
     * Recalculate portfolio from transactions - MEJORADO CON AGRUPAMIENTO
     */
    private function recalculate_portfolio($user_id, $coin_id) {
        error_log("CPT DB: Recalculando portfolio para user_id={$user_id}, coin_id={$coin_id}");
        
        $txs = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->transactions_table}
                 WHERE user_id = %d AND coin_id = %s
                 ORDER BY transaction_date ASC",
                $user_id, $coin_id
            )
        );

        if (empty($txs)) {
            error_log("CPT DB: No hay transacciones, eliminando item del portfolio");
            $this->delete_portfolio_item($user_id, $coin_id);
            return;
        }

        $total_amount   = 0.0;
        $total_invested = 0.0;
        $running_coins  = 0.0;

        foreach ($txs as $tx) {
            if ($tx->transaction_type === 'buy') {
                $total_amount   += (float)$tx->amount;
                $total_invested += (float)$tx->total_value;
                $running_coins  += (float)$tx->amount;
            } else { // sell
                $sell_amt = (float)$tx->amount;
                $total_amount -= $sell_amt;

                if ($running_coins > 0) {
                    $sell_ratio     = min(1.0, $sell_amt / $running_coins);
                    $total_invested = max(0.0, $total_invested * (1 - $sell_ratio));
                }
                $running_coins = max(0.0, $running_coins - $sell_amt);
            }
        }

        if ($total_amount <= 0) {
            error_log("CPT DB: Total amount <= 0, eliminando item del portfolio");
            $this->delete_portfolio_item($user_id, $coin_id);
            return;
        }

        $avg_buy_price = $total_amount > 0 ? ($total_invested / $total_amount) : 0.0;

        // MEJORAR: Obtener precio actual usando múltiples intentos
        $current_price = $this->get_current_price_for_coin($coin_id, $txs[0]->coin_symbol);
        
        $first = $txs[0];

        error_log("CPT DB: Datos calculados - amount: {$total_amount}, invested: {$total_invested}, avg_price: {$avg_buy_price}, current_price: {$current_price}");

        // IMPORTANTE: Usar REPLACE para evitar duplicados por coin_id
        $this->wpdb->replace(
            $this->portfolio_table,
            array(
                'user_id'        => (int)$user_id,
                'coin_id'        => $coin_id,
                'coin_symbol'    => $first->coin_symbol,
                'coin_name'      => $first->coin_name,
                'total_amount'   => $total_amount,
                'avg_buy_price'  => $avg_buy_price,
                'total_invested' => $total_invested,
                'current_price'  => $current_price,
            ),
            array('%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f')
        );
    }

    /**
     * Limpiar duplicados de portfolio - NUEVO MÉTODO
     */
    public function clean_portfolio_duplicates($user_id) {
        // Obtener todos los coin_ids únicos para este usuario
        $unique_coins = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT DISTINCT coin_id FROM {$this->portfolio_table} WHERE user_id = %d",
                $user_id
            )
        );

        foreach ($unique_coins as $coin) {
            // Para cada coin_id, recalcular completamente
            $this->recalculate_portfolio($user_id, $coin->coin_id);
        }
    }

    /**
     * Obtener precio actual con múltiples intentos y fallbacks
     */
    private function get_current_price_for_coin($coin_id, $coin_symbol) {
        // Si no tenemos la clase CoinGecko, retornar 0
        if (!class_exists('CPT_CoinGecko_API')) {
            error_log("CPT DB: Clase CPT_CoinGecko_API no disponible");
            return 0.0;
        }

        try {
            $coingecko = new CPT_CoinGecko_API();
            
            // Intento 1: Usar el coin_id tal como está
            $price = (float) $coingecko->get_coin_price($coin_id);
            if ($price > 0) {
                error_log("CPT DB: Precio obtenido con coin_id '{$coin_id}': {$price}");
                return $price;
            }
            
            // Intento 2: Si coin_id no funcionó, mapear símbolos conocidos
            $symbol_map = array(
                'BTC' => 'bitcoin',
                'ETH' => 'ethereum', 
                'DOGE' => 'dogecoin',
                'XRP' => 'ripple',
                'ADA' => 'cardano',
                'LTC' => 'litecoin',
                'BCH' => 'bitcoin-cash',
                'DOT' => 'polkadot',
                'LINK' => 'chainlink',
                'BNB' => 'binancecoin',
                'UNI' => 'uniswap',
                'MATIC' => 'polygon',
                'AVAX' => 'avalanche-2',
                'SOL' => 'solana',
                'ATOM' => 'cosmos',
                'ALGO' => 'algorand'
            );
            
            $mapped_id = isset($symbol_map[strtoupper($coin_symbol)]) ? $symbol_map[strtoupper($coin_symbol)] : null;
            if ($mapped_id) {
                $price = (float) $coingecko->get_coin_price($mapped_id);
                if ($price > 0) {
                    error_log("CPT DB: Precio obtenido con mapeo '{$coin_symbol}' -> '{$mapped_id}': {$price}");
                    return $price;
                }
            }
            
            // Intento 3: Buscar por símbolo
            $search_results = $coingecko->search_coins($coin_symbol);
            if (!empty($search_results)) {
                foreach ($search_results as $result) {
                    if (strtoupper($result['symbol']) === strtoupper($coin_symbol)) {
                        $price = (float) $coingecko->get_coin_price($result['id']);
                        if ($price > 0) {
                            error_log("CPT DB: Precio obtenido por búsqueda '{$coin_symbol}' -> '{$result['id']}': {$price}");
                            return $price;
                        }
                    }
                }
            }
            
            error_log("CPT DB WARN: No se pudo obtener precio para coin_id='{$coin_id}', symbol='{$coin_symbol}'");
            return 0.0;
            
        } catch (\Throwable $e) {
            error_log('CPT DB ERROR get_current_price_for_coin: ' . $e->getMessage());
            return 0.0;
        }
    }

    // Stats helpers
    public function get_user_stats($user_id) {
        $portfolio = $this->get_user_portfolio($user_id);

        $transactions_count = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->transactions_table} WHERE user_id = %d",
                $user_id
            )
        );

        $total_invested = 0.0;
        $current_value  = 0.0;

        foreach ($portfolio as $row) {
            $total_invested += (float)$row->total_invested;
            $current_value  += ((float)$row->total_amount * (float)$row->current_price);
        }

        $profit_loss            = $current_value - $total_invested;
        $profit_loss_percentage = $total_invested > 0 ? (($profit_loss / $total_invested) * 100.0) : 0.0;

        return array(
            'total_invested'      => $total_invested,
            'current_value'       => $current_value,
            'profit_loss'         => $profit_loss,
            'profit_loss_percentage' => $profit_loss_percentage,
            'total_coins'         => count($portfolio),
            'total_transactions'  => $transactions_count,
        );
    }

    public function get_user_transactions_by_date_range($user_id, $start_date, $end_date = null) {
        if ($end_date === null) {
            $end_date = current_time('Y-m-d');
        }
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->transactions_table}
                 WHERE user_id = %d
                 AND DATE(transaction_date) BETWEEN %s AND %s
                 ORDER BY transaction_date DESC",
                $user_id, $start_date, $end_date
            )
        );
    }

    public function get_coin_transactions($user_id, $coin_id, $limit = 100) {
        $limit = max(1, (int)$limit);
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->transactions_table}
                 WHERE user_id = %d AND coin_id = %s
                 ORDER BY transaction_date DESC
                 LIMIT %d",
                $user_id, $coin_id, $limit
            )
        );
    }
}