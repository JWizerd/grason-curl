<?php

require '../vendor/autoload.php';

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Promise as Promise;

abstract class BaseApi
{
    const COUNTRY = 'US';

    protected $headers = [];

    abstract protected function set_api_base();

    /**
     * for better usability of GuzzleHttp\Client instantiation
     * @return [obj]
     */
    protected function api() 
    {
        return (new Guzzle(['base_uri' => $this->api_base]));   
    }

    public function __set(string $name, $value)
    {
        $this->$name = $value;
    }

    public function __get(string $name) 
    {
        return $this->$name;
    }

    protected function set_header(string $type, string $value) 
    {
        $this->headers[$type] = $value;
    }

    public function create(string $endpoint, array $data) 
    {

        return json_decode(
            $this->api()->post(
                $endpoint, 
                [
                    'headers' => $this->headers,
                    'json' => $data
                ]
            )->getBody()
        );
    }

    public function update(string $operation, string $endpoint, array $data) 
    {
        return json_decode(
            $this->api()->request(
                $operation,
                $endpoint,
                [
                    'headers' => $this->headers,
                    'json'    => $data
                ]
            )->getBody()
        );
    }

    public function get(string $endpoint, $params = []) 
    {
        return json_decode(
            $this->api()->get(
                $endpoint, 
                [
                    'headers' => $this->headers,
                    'query' => $params
                ]
            )->getBody()
        );
    }

    public function delete($endpoint) 
    {
        $this->api()->delete( 
            $endpoint,
            [
                'headers' => $this->headers
            ]
        );
    }
}