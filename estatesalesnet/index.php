<?php 

require_once('../api/curl_handler.php');

class Net extends Curl_Handler {
	protected $company;
	protected $account;
	protected $base_url = 'https://www.estatesales.net';
    protected $api;
	protected $country = 'US';
	protected $address;
	protected $city;
	protected $postal_code;
	protected $state_code;
    protected $refresh_token = '17d28ad3e7604ce28e9212270584531953056298';

    protected $headers = [
        'Content-Type:application/json',
        'X_XSRF:X_XSRF'
    ];

	public function __construct($company, $address, $city, $postal_code, $state_code, $account)  {
	  $this->company     = $company;
	  $this->address     = $address;
	  $this->city        = $city;
	  $this->postal_code = $postal_code;
	  $this->state_code  = $state_code;
      $this->account     = $account;
      $this->api = $this->base_url . '/api';
      $this->generate_access_token();
      $this->set_auth('bearer');
      $this->set_header('Content-Type: application/json');
      $this->set_header('X_XSRF: X_XSRF');
	}

	protected function generate_access_token() {
        $headers = [
            'Content-Type:application/x-www-form-urlencoded',
            'Cache-Control:no-cache'
        ];

        $message = [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->refresh_token
        ];

		$response = json_decode($this->request($this->base_url . '/token', null, $headers, $message, 'form'));

        $this->token = $response->access_token;
	}

	protected function get_account() {
		return $this->account;
	}
}

$net = new Net('Grasons TEST', '1714 keyes court', 'loveland', 'postal_code', 'CO', 23126);

print_r($net->get_headers());