<?php
namespace Nateritter\Binance;

class BinanceAPI
{
    protected $key;         // API key
    protected $secret;      // API secret
    protected $url;         // API base URL
    protected $recvWindow;  // API base URL
    protected $version;     // API version
    protected $curl;        // curl handle

    /**
     * Constructor for BinanceAPI
     */
    function __construct()
    {
        $this->key        = config('binance.auth.key');
        $this->secret     = config('binance.auth.secret');
        $this->url        = config('binance.urls.api');
        $this->recvWindow = config('binance.settings.timing');
        $this->curl       = curl_init();

        $curl_options     = [
            CURLOPT_SSL_VERIFYPEER => config('binance.settings.ssl'),
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'Binance PHP API Agent',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 300
        ];

        curl_setopt_array($this->curl, $curl_options);
    }

    /**
     * Close CURL
     */
    function __destruct()
    {
        curl_close($this->curl);
    }

    /**
     * Key and Secret setter function. It's required for TRADE, USER_DATA, USER_STREAM, MARKET_DATA endpoints.
     * https://github.com/binance-exchange/binance-official-api-docs/blob/master/rest-api.md#endpoint-security-type
     *
     * @param string $key    API Key
     * @param string $secret API Secret
     */
    function setAPI($key, $secret)
    {
       $this->key    = $key;
       $this->secret = $secret;
    }


    //------ PUBLIC API CALLS --------
    /*
    * getTicker
    * getPrice
    * getCurrencies
    * getMarkets
    * getKlines
    *
    *
    *
    *
    */

    /**
     * Get ticker
     *
     * @return mixed
     * @throws \Exception
     */
    public function getTicker($symbol)
    {
        $data = [
            'symbol' => $symbol
        ];
        return $this->request('v1/ticker/24hr', $data);
    }

    /**
     * Get price
     *
     * @param  string $symbol
     * @return mixed
     * @throws \Exception
     */
    public function getPrice($symbol)
    {
        $data = [
            'symbol' => $symbol
        ];
        return $this->request('v3/ticker/price', $data);
    }

    public function getCurrencies()
    {
       //Seems to be no such functionality
       return false;
    }

    /**
     * Current exchange trading rules and symbol information
     *
     * @return mixed
     * @throws \Exception
     */
    public function getMarkets()
    {
        $return = $this->request('v1/exchangeInfo');
        return $return['symbols'];
    }

    /**
     * Kline/candlestick bars for a symbol and interval.
     * @return mixed
     * @throws \Exception
     */
    public function getKlines($symbol, $interval, $limit = 500)
    {
        $data = [
            'symbol' => $symbol,
            'interval' => $interval,
            'limit' => $limit
        ];
        return $this->request('v1/klines', $data);
    }



    //------ PRIVATE API CALLS ----------
    /*
    * getBalances
    * getRecentTrades
    * getOpenOrders
    * getAllOrders
    * trade
    * marketSell
    * marketBuy
    * limitSell
    * limitBuy
    */

    /**
     * Get current account information
     *
     * @return mixed
     * @throws \Exception
     */
    public function getBalances() {
        $b = $this->privateRequest('v3/account');
        return $b['balances'];
    }

    /**
     * Get trades for a specific account and symbol
     *
     * @param string $symbol Currency pair
     * @param int $limit     Limit of trades. Max. 500
     * @return mixed
     * @throws \Exception
     */
    public function getRecentTrades($symbol = 'BNBBTC', $limit = 500)
    {
        $data = [
            'symbol' => $symbol,
            'limit'  => $limit,
        ];
        $b = $this->privateRequest('v3/myTrades', $data);
        return $b;
    }

    public function getOpenOrders()
    {
        $b = $this->privateRequest('v3/openOrders');
        return $b;
    }

    public function getAllOrders($symbol)
    {
        $data = [
            'symbol' => $symbol
        ];
        $b = $this->privateRequest('v3/allOrders', $data);
        return $b;
    }

    /**
     * Base trade function
     *
     * @param string $symbol   Asset pair to trade
     * @param string $quantity Amount of trade asset
     * @param string $side     BUY, SELL
     * @param string $type     MARKET, LIMIT, STOP_LOSS, STOP_LOSS_LIMIT, TAKE_PROFIT, TAKE_PROFIT_LIMIT, LIMIT_MAKER
     * @param bool $price      Limit price
     * @param bool $test       Test trade
     * @return mixed
     * @throws \Exception
     */
    public function trade($symbol, $quantity, $side, $type = 'MARKET', $price = false, $test = false)
    {
        $data = [
            'symbol'   => $symbol,
            'side'     => $side,
            'type'     => $type,
            'quantity' => $quantity
        ];

        if ($price !== false) {
            $data['price'] = $price;
        }

        $uri = ($test) ? 'v3/order/test' : 'v3/order';

        $b = $this->privateRequest($uri, $data, 'POST');

        return $b;
    }

    /**
     * Sell at market price
     *
     * @param string $symbol   Asset pair to trade
     * @param string $quantity Amount of trade asset
     * @param bool   $test     Test trade
     * @return mixed
     * @throws \Exception
     */
    public function marketSell($symbol, $quantity, $test = false)
    {
        return $this->trade($symbol, $quantity, 'SELL', 'MARKET', false, $test);
    }

    /**
     * Buy at market price
     *
     * @param string $symbol   Asset pair to trade
     * @param string $quantity Amount of trade asset
     * @param bool   $test     Test trade
     * @return mixed
     * @throws \Exception
     */
    public function marketBuy($symbol, $quantity, $test = false)
    {
        return $this->trade($symbol, $quantity, 'BUY', 'MARKET', false, $test);
    }

    /**
     * Sell limit
     *
     * @param string $symbol   Asset pair to trade
     * @param string $quantity Amount of trade asset
     * @param float  $price    Limit price to sell
     * @param bool   $test     Test trade
     * @return mixed
     * @throws \Exception
     */
    public function limitSell($symbol, $quantity, $price, $test = false)
    {
        return $this->trade($symbol, $quantity, 'SELL', 'LIMIT', $price, $test);
    }

    /**
     * Buy limit
     *
     * @param string $symbol   Asset pair to trade
     * @param string $quantity Amount of trade asset
     * @param float  $price    Limit price to buy
     * @param bool   $test     Test trade
     * @return mixed
     * @throws \Exception
     */
    public function limitBuy($symbol, $quantity, $price, $test = false)
    {
        return $this->trade($symbol, $quantity, 'BUY', 'LIMIT', $price, $test);
    }




    //------ REQUESTS FUNCTIONS ------

    /**
     * Make public requests (Security Type: NONE)
     *
     * @param string $url    URL Endpoint
     * @param array $params  Required and optional parameters
     * @param string $method GET, POST, PUT, DELETE
     * @return mixed
     * @throws \Exception
     */
    private function request($url, $params = [], $method = 'GET')
    {

        // Add post vars
        if ($method == 'POST') {
            curl_setopt($this->curl, CURLOPT_POST, count($params));
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
        } else {
            $url = $url . '?' . http_build_query($params);
        }

        // Set URL & Header
        curl_setopt($this->curl, CURLOPT_URL, $this->url . $url);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array());

        // Get result
        $result = curl_exec($this->curl);
        if ($result === false) {
            throw new \Exception('CURL error: ' . curl_error($this->curl));
        }

        // Decode results
        $result = json_decode($result, true);

        if (!is_array($result) || json_last_error()) {
            throw new \Exception('JSON decode error');
        }

        return $result;

    }

    /**
     * Make private requests (Security Type: TRADE, USER_DATA, USER_STREAM, MARKET_DATA)
     *
     * @param string $url    URL Endpoint
     * @param array $params  Required and optional parameters
     * @param string $method GET, POST, PUT, DELETE
     * @return mixed
     * @throws \Exception
     */
    private function privateRequest($url, $params = [], $method = 'GET')
    {
        // Build the POST data string
        $params['timestamp']  = number_format((microtime(true) * 1000), 0, '.', '');
        $params['recvWindow'] = $this->recvWindow;

        $query   = http_build_query($params, '', '&');

        // Set API key and sign the message
        $sign    = hash_hmac('sha256', $query, $this->secret);

        $headers = array(
            'X-MBX-APIKEY: ' . $this->key
        );

        // Make request
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

        // Build the POST data string
        $postdata = $params;

        // Set URL & Header
        curl_setopt($this->curl, CURLOPT_URL, $this->url . $url."?{$query}&signature={$sign}");

        // Add post vars
        if ($method == "POST") {
            curl_setopt($this->curl,CURLOPT_POST, 1);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, array());
        }

        // Get result
        $result = curl_exec($this->curl);
        if ($result === false) {
            throw new \Exception('CURL error: ' . curl_error($this->curl));
        }

        // Decode results
        $result = json_decode($result, true);
        if (!is_array($result) || json_last_error()) {
            throw new \Exception('JSON decode error');
        }

        return $result;

    }

}
