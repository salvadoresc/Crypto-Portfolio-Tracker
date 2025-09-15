<?php
if (!defined('ABSPATH')) {
    exit;
}

class CPT_CoinGecko_API {

    private $base_url = 'https://api.coingecko.com/api/v3';
    private $cache_duration = 300; // 5 minutos

    public function __construct() {
        // nada
    }

    /** Normaliza uno o varios IDs a array de strings en minúscula, sin espacios */
    private function normalize_ids($ids) {
        if (empty($ids)) {
            return array();
        }
        if (!is_array($ids)) {
            $ids = explode(',', (string) $ids);
        }
        $out = array();
        foreach ($ids as $id) {
            $id = strtolower(trim((string) $id));
            if ($id !== '') {
                $out[] = $id;
            }
        }
        // quitar duplicados preservando orden
        return array_values(array_unique($out));
    }

    /** Parte un array de IDs en grupos de tamaño fijo (CoinGecko permite hasta ~250) */
    private function chunk_ids($ids, $size = 200) {
        if (empty($ids)) {
            return array();
        }
        return array_chunk($ids, max(1, (int) $size));
    }

    /** GET simple con cache vía transient */
    private function make_request($endpoint, $params = array()) {
        $cache_key = 'cpt_api_' . md5($endpoint . '|' . serialize($params));
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            error_log("CPT CoinGecko: Cache HIT para {$endpoint}");
            return $cached;
        }

        $url = rtrim($this->base_url, '/') . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params, '', '&');
        }

        error_log("CPT CoinGecko: Haciendo request a {$url}");

        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept'     => 'application/json',
                'User-Agent' => 'WP Crypto Portfolio Tracker'
            )
        ));

        if (is_wp_error($response)) {
            error_log('CoinGecko API Error: ' . $response->get_error_message());
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            error_log('CoinGecko API HTTP Error: ' . $code . ' for ' . $url);
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('CoinGecko JSON Error: ' . json_last_error_msg());
            return false;
        }

        error_log("CPT CoinGecko: Response data: " . substr(print_r($data, true), 0, 500));
        
        set_transient($cache_key, $data, (int) $this->cache_duration);
        return $data;
    }

    /* ========================= BÚSQUEDA / PRECIOS ========================= */

    public function search_coins($query) {
        $q = trim((string) $query);
        if ($q === '') {
            return array();
        }
        $result = $this->make_request('/search', array('query' => $q));
        if (!$result || !isset($result['coins']) || !is_array($result['coins'])) {
            return array();
        }
        $coins = array_slice($result['coins'], 0, 20);
        $out = array();
        foreach ($coins as $coin) {
            $out[] = array(
                'id'              => isset($coin['id']) ? $coin['id'] : '',
                'symbol'          => isset($coin['symbol']) ? strtoupper($coin['symbol']) : '',
                'name'            => isset($coin['name']) ? $coin['name'] : '',
                'thumb'           => isset($coin['thumb']) ? $coin['thumb'] : '',
                'market_cap_rank' => isset($coin['market_cap_rank']) ? $coin['market_cap_rank'] : null
            );
        }
        return $out;
    }

    public function get_coin_price($coin_id) {
        $coin_id = strtolower(trim((string) $coin_id));
        if ($coin_id === '') {
            return 0;
        }

        error_log("CPT CoinGecko: Obteniendo precio para coin_id: {$coin_id}");

        // Mapeo de símbolos conocidos primero
        $symbol_map = array(
            'btc' => 'bitcoin',
            'eth' => 'ethereum', 
            'doge' => 'dogecoin',
            'xrp' => 'ripple',
            'ada' => 'cardano',
            'ltc' => 'litecoin',
            'bch' => 'bitcoin-cash',
            'dot' => 'polkadot',
            'link' => 'chainlink',
            'bnb' => 'binancecoin',
            'uni' => 'uniswap',
            'matic' => 'polygon',
            'avax' => 'avalanche-2',
            'sol' => 'solana',
            'atom' => 'cosmos',
            'algo' => 'algorand'
        );

        // Si el coin_id es un símbolo conocido, usar el mapeo
        if (isset($symbol_map[$coin_id])) {
            $coin_id = $symbol_map[$coin_id];
            error_log("CPT CoinGecko: Mapeado a: {$coin_id}");
        }

        $result = $this->make_request('/simple/price', array(
            'ids'           => $coin_id,
            'vs_currencies' => 'usd'
        ));

        if (!$result || !isset($result[$coin_id]['usd'])) {
            error_log("CPT CoinGecko: No se encontró precio para {$coin_id}");
            return 0;
        }

        $price = (float) $result[$coin_id]['usd'];
        error_log("CPT CoinGecko: Precio encontrado para {$coin_id}: {$price}");
        return $price;
    }

    /** Regresa array id => price (USD) */
    public function get_coins_prices($coin_ids) {
        $ids = $this->normalize_ids($coin_ids);
        if (empty($ids)) {
            return array();
        }

        // Aplicar mapeo a todos los IDs
        $symbol_map = array(
            'btc' => 'bitcoin',
            'eth' => 'ethereum', 
            'doge' => 'dogecoin',
            'xrp' => 'ripple',
            'ada' => 'cardano',
            'ltc' => 'litecoin',
            'bch' => 'bitcoin-cash',
            'dot' => 'polkadot',
            'link' => 'chainlink',
            'bnb' => 'binancecoin',
            'uni' => 'uniswap',
            'matic' => 'polygon',
            'avax' => 'avalanche-2',
            'sol' => 'solana',
            'atom' => 'cosmos',
            'algo' => 'algorand'
        );

        $mapped_ids = array();
        foreach ($ids as $id) {
            $mapped_ids[] = isset($symbol_map[$id]) ? $symbol_map[$id] : $id;
        }

        $prices = array();

        foreach ($this->chunk_ids($mapped_ids) as $chunk) {
            $ids_string = implode(',', $chunk);
            $result = $this->make_request('/simple/price', array(
                'ids'                 => $ids_string,
                'vs_currencies'       => 'usd',
                'include_24hr_change' => 'true'
            ));
            if (!$result || !is_array($result)) {
                continue;
            }
            foreach ($result as $id => $data) {
                if (isset($data['usd'])) {
                    $prices[$id] = (float) $data['usd'];
                }
            }
        }
        return $prices;
    }

    /**
     * Regresa array id => [price, price_change_24h, volume_24h, market_cap]
     * Hace chunking para listas largas de IDs.
     */
    public function get_coins_with_market_data($coin_ids) {
        $coin_ids = $this->normalize_ids($coin_ids);
        if (empty($coin_ids)) {
            return array();
        }

        error_log("CPT CoinGecko: get_coins_with_market_data para IDs: " . implode(', ', $coin_ids));

        // Aplicar mapeo de símbolos
        $symbol_map = array(
            'btc' => 'bitcoin',
            'eth' => 'ethereum', 
            'doge' => 'dogecoin',
            'xrp' => 'ripple',
            'ada' => 'cardano',
            'ltc' => 'litecoin',
            'bch' => 'bitcoin-cash',
            'dot' => 'polkadot',
            'link' => 'chainlink',
            'bnb' => 'binancecoin',
            'uni' => 'uniswap',
            'matic' => 'polygon',
            'avax' => 'avalanche-2',
            'sol' => 'solana',
            'atom' => 'cosmos',
            'algo' => 'algorand'
        );

        $mapped_ids = array();
        $original_to_mapped = array();
        
        foreach ($coin_ids as $id) {
            $mapped = isset($symbol_map[$id]) ? $symbol_map[$id] : $id;
            $mapped_ids[] = $mapped;
            $original_to_mapped[$id] = $mapped;
        }

        error_log("CPT CoinGecko: IDs mapeados: " . implode(', ', $mapped_ids));

        $market_data = array();

        foreach ($this->chunk_ids($mapped_ids) as $chunk) {
            $ids_string = implode(',', $chunk);
            $result = $this->make_request('/simple/price', array(
                'ids'                 => $ids_string,
                'vs_currencies'       => 'usd',
                'include_24hr_change' => 'true',
                'include_24hr_vol'    => 'true',
                'include_market_cap'  => 'true'
            ));
            if (!$result || !is_array($result)) {
                continue;
            }

            foreach ($result as $mapped_id => $data) {
                // Encontrar el ID original
                $original_id = array_search($mapped_id, $original_to_mapped);
                if ($original_id === false) {
                    $original_id = $mapped_id; // fallback
                }

                $market_data[$original_id] = array(
                    'price'            => isset($data['usd']) ? (float) $data['usd'] : 0.0,
                    'price_change_24h' => isset($data['usd_24h_change']) ? (float) $data['usd_24h_change'] : 0.0,
                    'volume_24h'       => isset($data['usd_24h_vol']) ? (float) $data['usd_24h_vol'] : 0.0,
                    'market_cap'       => isset($data['usd_market_cap']) ? (float) $data['usd_market_cap'] : 0.0
                );

                error_log("CPT CoinGecko: Datos para {$original_id} (mapeado: {$mapped_id}): price=" . $market_data[$original_id]['price']);
            }
        }

        return $market_data;
    }

    public function get_price_change_24h($coin_id) {
        $coin_id = strtolower(trim((string) $coin_id));
        if ($coin_id === '') {
            return 0;
        }
        $result = $this->make_request('/simple/price', array(
            'ids'                 => $coin_id,
            'vs_currencies'       => 'usd',
            'include_24hr_change' => 'true'
        ));
        if (!$result || !isset($result[$coin_id]['usd_24h_change'])) {
            return 0;
        }
        return (float) $result[$coin_id]['usd_24h_change'];
    }

    public function get_trending_coins($limit = 10) {
        $limit = max(1, min((int) $limit, 50));
        $result = $this->make_request('/search/trending');
        if (!$result || !isset($result['coins']) || !is_array($result['coins'])) {
            return array();
        }
        $trending = array_slice($result['coins'], 0, $limit);
        $out = array();
        foreach ($trending as $item) {
            $coin = isset($item['item']) ? $item['item'] : array();
            $out[] = array(
                'id'              => isset($coin['id']) ? $coin['id'] : '',
                'symbol'          => isset($coin['symbol']) ? strtoupper($coin['symbol']) : '',
                'name'            => isset($coin['name']) ? $coin['name'] : '',
                'thumb'           => isset($coin['thumb']) ? $coin['thumb'] : '',
                'market_cap_rank' => isset($coin['market_cap_rank']) ? $coin['market_cap_rank'] : null,
                'price_btc'       => isset($coin['price_btc']) ? (float) $coin['price_btc'] : 0
            );
        }
        return $out;
    }

    /* ========================= MERCADOS / HISTORIAL ========================= */

    public function get_market_data($coin_ids = null, $limit = 100) {
        $params = array(
            'vs_currency' => 'usd',
            'order'       => 'market_cap_desc',
            'per_page'    => min(max(1, (int) $limit), 250),
            'page'        => 1,
            'sparkline'   => 'false',
            'price_change_percentage' => '24h,7d'
        );

        if ($coin_ids) {
            $ids = $this->normalize_ids($coin_ids);
            if (!empty($ids)) {
                $params['ids'] = implode(',', $ids);
            }
        }

        $result = $this->make_request('/coins/markets', $params);
        if (!$result || !is_array($result)) {
            return array();
        }

        $out = array();
        foreach ($result as $coin) {
            $out[] = array(
                'id'                                  => isset($coin['id']) ? $coin['id'] : '',
                'symbol'                              => isset($coin['symbol']) ? strtoupper($coin['symbol']) : '',
                'name'                                => isset($coin['name']) ? $coin['name'] : '',
                'image'                               => isset($coin['image']) ? $coin['image'] : '',
                'current_price'                       => isset($coin['current_price']) ? (float) $coin['current_price'] : 0,
                'market_cap'                          => isset($coin['market_cap']) ? (float) $coin['market_cap'] : 0,
                'market_cap_rank'                     => isset($coin['market_cap_rank']) ? $coin['market_cap_rank'] : null,
                'total_volume'                        => isset($coin['total_volume']) ? (float) $coin['total_volume'] : 0,
                'price_change_percentage_24h'         => isset($coin['price_change_percentage_24h']) ? (float) $coin['price_change_percentage_24h'] : 0,
                'price_change_percentage_7d_in_currency' => isset($coin['price_change_percentage_7d_in_currency']) ? (float) $coin['price_change_percentage_7d_in_currency'] : 0,
                'circulating_supply'                  => isset($coin['circulating_supply']) ? (float) $coin['circulating_supply'] : 0,
                'total_supply'                        => isset($coin['total_supply']) ? (float) $coin['total_supply'] : 0,
                'max_supply'                          => isset($coin['max_supply']) ? (float) $coin['max_supply'] : 0,
                'ath'                                 => isset($coin['ath']) ? (float) $coin['ath'] : 0,
                'ath_change_percentage'               => isset($coin['ath_change_percentage']) ? (float) $coin['ath_change_percentage'] : 0,
                'ath_date'                            => isset($coin['ath_date']) ? $coin['ath_date'] : '',
                'atl'                                 => isset($coin['atl']) ? (float) $coin['atl'] : 0,
                'atl_change_percentage'               => isset($coin['atl_change_percentage']) ? (float) $coin['atl_change_percentage'] : 0,
                'atl_date'                            => isset($coin['atl_date']) ? $coin['atl_date'] : ''
            );
        }
        return $out;
    }

    /** Fecha: dd-mm-yyyy */
    public function get_coin_history($coin_id, $date) {
        $coin_id = strtolower(trim((string) $coin_id));
        $date = trim((string) $date);
        if ($coin_id === '' || $date === '') {
            return null;
        }
        $result = $this->make_request('/coins/' . rawurlencode($coin_id) . '/history', array(
            'date'         => $date,
            'localization' => 'false'
        ));
        if (!$result || !isset($result['market_data']['current_price']['usd'])) {
            return null;
        }
        return array(
            'price'       => isset($result['market_data']['current_price']['usd']) ? (float) $result['market_data']['current_price']['usd'] : 0,
            'market_cap'  => isset($result['market_data']['market_cap']['usd']) ? (float) $result['market_data']['market_cap']['usd'] : 0,
            'total_volume'=> isset($result['market_data']['total_volume']['usd']) ? (float) $result['market_data']['total_volume']['usd'] : 0
        );
    }

    public function get_coin_chart_data($coin_id, $days = 7) {
        $coin_id = strtolower(trim((string) $coin_id));
        $days = max(1, (int) $days);
        if ($coin_id === '') {
            return array();
        }
        $result = $this->make_request('/coins/' . rawurlencode($coin_id) . '/market_chart', array(
            'vs_currency' => 'usd',
            'days'        => $days
        ));
        if (!$result || !isset($result['prices']) || !is_array($result['prices'])) {
            return array();
        }
        $out = array();
        foreach ($result['prices'] as $point) {
            if (is_array($point) && count($point) >= 2) {
                $ts = (int) $point[0];
                $price = (float) $point[1];
                $out[] = array(
                    'timestamp' => $ts,
                    'date'      => date('Y-m-d H:i', $ts / 1000),
                    'price'     => $price
                );
            }
        }
        return $out;
    }

    public function get_global_data() {
        $result = $this->make_request('/global');
        if (!$result || !isset($result['data']) || !is_array($result['data'])) {
            return array();
        }
        $d = $result['data'];
        return array(
            'active_cryptocurrencies'          => isset($d['active_cryptocurrencies']) ? (int) $d['active_cryptocurrencies'] : 0,
            'upcoming_icos'                    => isset($d['upcoming_icos']) ? (int) $d['upcoming_icos'] : 0,
            'ongoing_icos'                     => isset($d['ongoing_icos']) ? (int) $d['ongoing_icos'] : 0,
            'ended_icos'                       => isset($d['ended_icos']) ? (int) $d['ended_icos'] : 0,
            'markets'                          => isset($d['markets']) ? (int) $d['markets'] : 0,
            'total_market_cap'                 => isset($d['total_market_cap']['usd']) ? (float) $d['total_market_cap']['usd'] : 0,
            'total_volume'                     => isset($d['total_volume']['usd']) ? (float) $d['total_volume']['usd'] : 0,
            'market_cap_percentage'            => isset($d['market_cap_percentage']) ? $d['market_cap_percentage'] : array(),
            'market_cap_change_percentage_24h' => isset($d['market_cap_change_percentage_24h_usd']) ? (float) $d['market_cap_change_percentage_24h_usd'] : 0
        );
    }

    /* ========================= UTILIDADES DE LOTE ========================= */

    public function update_portfolio_prices($portfolio_items) {
        if (empty($portfolio_items) || !is_array($portfolio_items)) {
            return array();
        }
        $ids = array();
        foreach ($portfolio_items as $item) {
            if (is_object($item) && isset($item->coin_id)) {
                $ids[] = $item->coin_id;
            }
        }
        $ids = $this->normalize_ids($ids);
        if (empty($ids)) {
            return $portfolio_items;
        }

        $mkt = $this->get_coins_with_market_data($ids);
        $out = array();
        foreach ($portfolio_items as $item) {
            if (is_object($item) && isset($item->coin_id)) {
                $id = strtolower($item->coin_id);
                if (isset($mkt[$id])) {
                    $item->current_price    = isset($mkt[$id]['price']) ? (float) $mkt[$id]['price'] : (isset($item->current_price) ? (float) $item->current_price : 0.0);
                    $item->price_change_24h = isset($mkt[$id]['price_change_24h']) ? (float) $mkt[$id]['price_change_24h'] : (isset($item->price_change_24h) ? (float) $item->price_change_24h : 0.0);
                    $item->market_cap       = isset($mkt[$id]['market_cap']) ? (float) $mkt[$id]['market_cap'] : (isset($item->market_cap) ? (float) $item->market_cap : 0.0);
                    $item->volume_24h       = isset($mkt[$id]['volume_24h']) ? (float) $mkt[$id]['volume_24h'] : (isset($item->volume_24h) ? (float) $item->volume_24h : 0.0);
                }
                $out[] = $item;
            }
        }
        return $out;
    }

    /** Limpia cache; si se pasa $coin_id intenta limpiar entradas relacionadas */
    public function clear_cache($coin_id = null) {
        global $wpdb;

        if ($coin_id) {
            // Como los keys están hasheados, no podemos reconstruirlos 1:1;
            // así que hacemos un barrido simple por prefijo de transient.
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cpt_api_%'");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cpt_api_%'");
            return;
        }

        // Limpiar todo lo del prefijo
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cpt_api_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_cpt_api_%'");
    }
}