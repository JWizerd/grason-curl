<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Promise as Promise;

abstract class BaseApi
{
    const COUNTRY = 'US';

    protected $headers = [];

    abstract protected function set_api_base();

    abstract protected function format_dates(array $dates);

    protected function format_date($time, $format = null, $modify = null)
    {
        if (!is_null($modify)) {
            return (new DateTime($time))->modify($modify)->format($format);    
        }

        return (new DateTime($time))->format($format);
    }

    /**
     * The implementation of this method ensures that child classes 
     * and more importantly, instantiated classes do not have direct access
     * to credentials. This prevents security measures such as full object display
     * via printing, __string(), and echo properties that could reveal sensitive information.
     * @param  string $type the type of credential you need
     * @return return the nested array of creds if exists
     */
    protected function get_credentials(string $type) 
    {
        $creds = require 'api_credentials.php';

        if (array_key_exists($type, $creds)) {
            return $creds[$type];
        }

        return false;
    }

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

    protected function set_header(string $type, $value) 
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

    public function post_form($endpoint, $data) {
        return json_decode(
            $this->api()->post(
                $endpoint, 
                [
                    'headers' => $this->headers,
                    'form_params' => $data
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
};