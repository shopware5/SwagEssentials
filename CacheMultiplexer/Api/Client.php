<?php

namespace SwagEssentials\CacheMultiplexer\Api;

class Client
{
    const METHODE_GET    = 'GET';
    const METHODE_PUT    = 'PUT';
    const METHODE_POST   = 'POST';
    const METHODE_DELETE = 'DELETE';

    protected $validMethods = array(
        self::METHODE_GET,
        self::METHODE_PUT,
        self::METHODE_POST,
        self::METHODE_DELETE
    );

    protected $apiUrl;
    protected $cURL;

    public $lastCallTime;

    public function __construct($apiUrl, $username, $apiKey)
    {
        if (!filter_var($apiUrl, FILTER_VALIDATE_URL)) {
            throw new \Exception('Invalid URL given');
        }

        $this->apiUrl = rtrim($apiUrl, '/') . '/';

        //Initializes the cURL instance
        $this->cURL = curl_init();
        curl_setopt($this->cURL, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->cURL, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($this->cURL, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($this->cURL, CURLOPT_USERPWD, $username . ':' . $apiKey);
        curl_setopt($this->cURL, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
        ));
    }

    public function call($url, $method = self::METHODE_GET, $data = array(), $params = array())
    {
        if (!in_array($method, $this->validMethods)) {
            throw new \Exception('Invalid HTTP-Methode: ' . $method);
        }
        $queryString = '';
        if (!empty($params)) {
            $queryString = http_build_query($params);
        }
        $url = rtrim($url, '?') . '?';
        $url = $this->apiUrl . $url . $queryString;
        $dataString = json_encode($data);

        curl_setopt($this->cURL, CURLOPT_URL, $url);
        curl_setopt($this->cURL, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->cURL, CURLOPT_POSTFIELDS, $dataString);

        $start = microtime(true);
        $body = curl_exec($this->cURL);
        $end = microtime(true);

        $this->lastCallTime = $end - $start;

        return new Response($body, $this->cURL);
    }

    public function get($url, $params = array())
    {
        return $this->call($url, self::METHODE_GET, array(), $params);
    }

    public function post($url, $data = array(), $params = array())
    {
        return $this->call($url, self::METHODE_POST, $data, $params);
    }

    public function put($url, $data = array(), $params = array())
    {
        return $this->call($url, self::METHODE_PUT, $data, $params);
    }

    public function delete($url, $data = array(), $params = array())
    {
        return $this->call($url, self::METHODE_DELETE, $data, $params);
    }
}
