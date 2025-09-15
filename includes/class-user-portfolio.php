<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * High-level user portfolio service
 * - Pulls data from CPT_Database
 * - Optionally enriches with CoinGecko
 * - Adds analytics, exports, and timelines
 */
class CPT_User_Portfolio {

    /** @var CPT_Database */
    private $database;
    /** @var CPT_CoinGecko_API|null */
    private $coingecko;
    /** @var int */
    private $user_id;

    /**
     * @param int|null $user_id
     * @param CPT_Database|null $database
     * @param CPT_CoinGecko_API|null $coingecko
     */
    public function __construct($user_id = null, $database = null, $coingecko = null) {
        $this->user_id   = $user_id ? (int)$user_id : (int)get_current_user_id();
        $this->database  = $database instanceof CPT_Database ? $database : new CPT_Database();
        // coingecko es opcional (si no está disponible, no fallamos)
        $this->coingecko = $coingecko instanceof CPT_CoinGecko_API ? $coingecko
                           : (class_exists('CPT_CoinGecko_API') ? new CPT_CoinGecko_API() : null);
    }

    /**
     * Fast summary with (cached) prices
     */
    public function get_portfolio_summary() {
        $portfolio = $this->database->get_user_portfolio($this->user_id);
        $stats     = $this->database->get_user_stats($this->user_id);

        if (!empty($portfolio)) {
            $portfolio = $this->update_prices_with_cache($portfolio);
        }

        return array(
            'portfolio'     => $portfolio,
            'stats'         => $stats,
            'last_updated'  => current_time('mysql'),
        );
    }

    /**
     * Full portfolio + analytics (allocations, P/L, best/worst, diversity)
     */
    public function get_portfolio_with_analytics() {
        $portfolio = $this->database->get_user_portfolio($this->user_id);
        if (empty($portfolio)) {
            return array(
                'portfolio' => array(),
                'analytics' => $this->get_empty_analytics(),
            );
        }

        $portfolio = $this->update_prices_with_cache($portfolio);

        $analytics = $this->calculate_portfolio_analytics($portfolio);
        return array(
            'portfolio' => $portfolio,
            'analytics' => $analytics,
        );
    }

    /**
     * Update current prices using a short-lived cache to avoid frequent API calls.
     * Cache key includes user_id to reflect each user's coin set/holdings.
     */
    private function update_prices_with_cache($portfolio) {
        if (!$this->coingecko) {
            // Sin API, regresamos el portfolio tal cual
            return $portfolio;
        }

        $cache_key = 'cpt_portfolio_prices_u_' . $this->user_id;
        $cached    = get_transient($cache_key);
        if (is_array($cached) && !empty($cached)) {
            // Mezcla de precios cacheados con portfolio actual (por coin_id)
            $byId = array();
            foreach ($cached as $row) {
                if (!empty($row->coin_id)) {
                    $byId[$row->coin_id] = $row;
                }
            }
            foreach ($portfolio as $idx => $item) {
                if (!empty($item->coin_id) && isset($byId[$item->coin_id])) {
                    // Copiamos campos de precio si existen en cache
                    $portfolio[$idx]->current_price    = isset($byId[$item->coin_id]->current_price) ? (float)$byId[$item->coin_id]->current_price : (float)$portfolio[$idx]->current_price;
                    $portfolio[$idx]->price_change_24h = isset($byId[$item->coin_id]->price_change_24h) ? (float)$byId[$item->coin_id]->price_change_24h : (isset($portfolio[$idx]->price_change_24h) ? (float)$portfolio[$idx]->price_change_24h : 0.0);
                }
            }
            return $portfolio;
        }

        // No hay cache → actualizar vía API (método del plugin)
        try {
            $updated = $this->coingecko->update_portfolio_prices($portfolio);
            // Cachear 5 minutos
            set_transient($cache_key, $updated, 5 * MINUTE_IN_SECONDS);
            return $updated;
        } catch (\Throwable $e) {
            // Si falla, devolvemos sin romper
            // error_log('CPT User Portfolio WARN (prices): ' . $e->getMessage());
            return $portfolio;
        }
    }

    private function calculate_portfolio_analytics($portfolio) {
        $total_invested     = 0.0;
        $current_value      = 0.0;
        $total_profit_loss  = 0.0;
        $allocations        = array();
        $performance_data   = array();

        foreach ($portfolio as $item) {
            $amount           = isset($item->total_amount) ? (float)$item->total_amount : 0.0;
            $price            = isset($item->current_price) ? (float)$item->current_price : 0.0;
            $invested         = isset($item->total_invested) ? (float)$item->total_invested : 0.0;
            $current_item_val = $amount * $price;

            $profit_loss      = $current_item_val - $invested;
            $profit_pct       = $invested > 0 ? (($profit_loss / $invested) * 100.0) : 0.0;

            $total_invested   += $invested;
            $current_value    += $current_item_val;
            $total_profit_loss += $profit_loss;

            $allocations[] = array(
                'coin'       => isset($item->coin_symbol) ? (string)$item->coin_symbol : (isset($item->coin_id) ? (string)$item->coin_id : ''),
                'value'      => $current_item_val,
                'percentage' => 0.0, // lo calculamos al final
            );

            $performance_data[] = array(
                'coin'               => isset($item->coin_symbol) ? (string)$item->coin_symbol : (isset($item->coin_id) ? (string)$item->coin_id : ''),
                'invested'           => $invested,
                'current_value'      => $current_item_val,
                'profit_loss'        => $profit_loss,
                'profit_percentage'  => $profit_pct,
                'price_change_24h'   => isset($item->price_change_24h) ? (float)$item->price_change_24h : 0.0,
            );
        }

        // calcular % de asignación
        if ($current_value > 0) {
            foreach ($allocations as &$a) {
                $a['percentage'] = ($a['value'] / $current_value) * 100.0;
            }
            unset($a);
        }

        $total_profit_percentage = $total_invested > 0 ? (($total_profit_loss / $total_invested) * 100.0) : 0.0;

        return array(
            'total_invested'        => $total_invested,
            'current_value'         => $current_value,
            'total_profit_loss'     => $total_profit_loss,
            'total_profit_percentage'=> $total_profit_percentage,
            'allocations'           => $allocations,
            'performance_data'      => $performance_data,
            'best_performer'        => $this->get_best_performer($performance_data),
            'worst_performer'       => $this->get_worst_performer($performance_data),
            'diversity_score'       => $this->calculate_diversity_score($allocations),
        );
    }

    private function get_best_performer($performance_data) {
        if (empty($performance_data)) return null;
        $best = null;
        $best_percentage = -INF;
        foreach ($performance_data as $item) {
            if ($item['profit_percentage'] > $best_percentage) {
                $best_percentage = $item['profit_percentage'];
                $best = $item;
            }
        }
        return $best;
    }

    private function get_worst_performer($performance_data) {
        if (empty($performance_data)) return null;
        $worst = null;
        $worst_percentage = INF;
        foreach ($performance_data as $item) {
            if ($item['profit_percentage'] < $worst_percentage) {
                $worst_percentage = $item['profit_percentage'];
                $worst = $item;
            }
        }
        return $worst;
    }

    /**
     * Shannon entropy diversity (0..100)
     */
    private function calculate_diversity_score($allocations) {
        if (empty($allocations)) return 0.0;

        $n = count($allocations);
        $entropy = 0.0;

        foreach ($allocations as $a) {
            $p = isset($a['percentage']) ? ((float)$a['percentage'] / 100.0) : 0.0;
            if ($p > 0) {
                // log base 2
                $entropy += $p * log($p, 2);
            }
        }
        $max_entropy = log($n, 2);
        $score = ($max_entropy > 0) ? ((-$entropy / $max_entropy) * 100.0) : 0.0;
        return round($score, 2);
    }

    private function get_empty_analytics() {
        return array(
            'total_invested'         => 0.0,
            'current_value'          => 0.0,
            'total_profit_loss'      => 0.0,
            'total_profit_percentage'=> 0.0,
            'allocations'            => array(),
            'performance_data'       => array(),
            'best_performer'         => null,
            'worst_performer'        => null,
            'diversity_score'        => 0.0,
        );
    }

    // ============== Queries de historial / timeline / export ==============

    public function get_transaction_history($limit = 50, $coin_id = null) {
        $limit = max(1, (int)$limit);
        if ($coin_id) {
            return $this->database->get_coin_transactions($this->user_id, (string)$coin_id, $limit);
        }
        return $this->database->get_user_transactions($this->user_id, $limit);
    }

    public function get_portfolio_timeline() {
        $transactions = $this->database->get_user_transactions($this->user_id, 1000);
        if (empty($transactions)) {
            return array();
        }

        $timeline = array();
        foreach ($transactions as $tx) {
            $date = date('Y-m-d', strtotime($tx->transaction_date));
            if (!isset($timeline[$date])) {
                $timeline[$date] = array(
                    'date'           => $date,
                    'transactions'   => 0,
                    'invested'       => 0.0,
                    'coins_touched'  => array(),
                );
            }
            $timeline[$date]['transactions']++;
            $timeline[$date]['invested'] += (float)$tx->total_value;
            $timeline[$date]['coins_touched'][] = $tx->coin_symbol;
        }

        // a array ordenado por fecha
        $timeline_array = array_values($timeline);
        usort($timeline_array, function($a, $b) {
            return strtotime($a['date']) <=> strtotime($b['date']);
        });

        return $timeline_array;
    }

    public function export_portfolio_data($format = 'json') {
        $portfolio    = $this->get_portfolio_with_analytics();
        $transactions = $this->get_transaction_history(1000);
        $watchlist    = $this->database->get_user_watchlist($this->user_id);

        $export_data = array(
            'user_id'     => $this->user_id,
            'export_date' => current_time('mysql'),
            'portfolio'   => $portfolio,
            'transactions'=> $transactions,
            'watchlist'   => $watchlist,
        );

        switch (strtolower((string)$format)) {
            case 'json':
                return json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            case 'csv':
                return $this->convert_to_csv($transactions);
            default:
                return $export_data;
        }
    }

    private function convert_to_csv($transactions) {
        if (empty($transactions)) {
            return '';
        }

        $fh = fopen('php://temp', 'r+');
        // Headers
        fputcsv($fh, array(
            'Date','Coin ID','Coin Symbol','Coin Name','Type',
            'Amount','Price','Total Value','Fees','Exchange','Notes'
        ));
        // Rows
        foreach ($transactions as $tx) {
            fputcsv($fh, array(
                $tx->transaction_date,
                $tx->coin_id,
                $tx->coin_symbol,
                $tx->coin_name,
                $tx->transaction_type,
                (float)$tx->amount,
                (float)$tx->price_per_coin,
                (float)$tx->total_value,
                (float)$tx->fees,
                $tx->exchange,
                $tx->notes,
            ));
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $csv;
    }

    public function get_performance_metrics($days = 30) {
        $days = max(1, (int)$days);
        $start_date   = date('Y-m-d', strtotime("-{$days} days"));
        $transactions = $this->database->get_user_transactions_by_date_range($this->user_id, $start_date);

        if (empty($transactions)) {
            return array(
                'period_investment'   => 0.0,
                'period_transactions' => 0,
                'most_traded_coin'    => null,
                'avg_transaction_size'=> 0.0,
            );
        }

        $period_investment = 0.0;
        $coin_counts       = array();
        $sizes             = array();

        foreach ($transactions as $tx) {
            $val = (float)$tx->total_value;
            $period_investment += $val;
            $sizes[] = $val;

            $sym = $tx->coin_symbol ?: $tx->coin_id;
            if (!isset($coin_counts[$sym])) {
                $coin_counts[$sym] = 0;
            }
            $coin_counts[$sym]++;
        }

        $most_traded = null;
        if (!empty($coin_counts)) {
            // obtener la clave con el máximo conteo
            $max = max($coin_counts);
            $most_traded = array_search($max, $coin_counts, true);
        }

        return array(
            'period_investment'    => $period_investment,
            'period_transactions'  => count($transactions),
            'most_traded_coin'     => $most_traded,
            'avg_transaction_size' => !empty($sizes) ? (array_sum($sizes) / count($sizes)) : 0.0,
        );
    }
}
