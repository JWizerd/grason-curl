<?php 

require_once('../api/curl_handler.php');

class Net extends Curl_Handler {
	protected $company;
	protected $account = 1111;
	protected $url = 'https://www.estatesales.net/token';
	protected $country = 'US';
	protected $address;
	protected $city;
	protected $postal_code;
	protected $state_code;

	public function __construct($company, $address, $city, $postal_code, $state_code)  {
	  $this->company     = $company;
	  $this->address     = $address;
	  $this->city        = $city;
	  $this->postal_code = $postal_code;
	  $this->state_code  = $state_code;
      $this->set_auth_key();
	}

	protected function set_auth_token() {
		$this->user_key = '17d28ad3e7604ce28e9212270584531953056298'
	}

	protected function get_auth_token() {
		return $this->password;
	}

	protected function get_account() {
		return $this->account;
	}
}

class Com extends Curl_Handler {
	protected $company;
	protected $account = 1626; 
	protected $url = 'https://www.estatesale.com/api/v1/trans.php'; 
	protected $country = 'US';
	protected $address;
	protected $city;
	protected $postal_code;
	protected $state_code;

	public function __construct($company, $address, $city, $postal_code, $state_code)  {
		$this->company     = $company;
	  $this->address     = $address;
	  $this->city        = $city;
	  $this->postal_code = $postal_code;
	  $this->state_code  = $state_code;
      $this->set_auth_key();
	}
  
  /**
   * @var string [<the master auth key for the account>]
   * @todo set auth key from ACF option in WP
   */
	protected function set_auth_key() {
		$this->user_key  = '61BSC-iGw4C-2Ot0D-v5gd8-QONC2';
	}
  
  /**
   * @return [string] [the .com auth hash]
   */
	protected function get_auth_key() {
		return $this->password;
	}

	protected function get_account() {
		return $this->account;
	}

	public function encode_params(){
		$params = [
      'operation' => 'save',
      'accountNumber' => $this->accountNumber,
      'data' => []
		];
	}

	/**
	 * post a listing to estatesale.com
	 * @param  [arr] $params_arr [array of parameters to post to message body in cURL handler]
	 * @return [response]        [a response containing info about successfully created listing or an error message]
	 */
	public function post_listing($params_arr) {

		$params = [
      'operation' => 'save'
		];

    return json_decode($this->request($this->url, null, $this->headers, $params_arr));

  }

}

$com = new Com(3501, '1714 keyes court', 'loveland', 80537, 'CO');
$com->set_auth('xauth');
$com->set_content_type('json');

$params = [
  'accountNumber' => 1626,
  'data' => [
  	'companyInformation' => [ 
      'estatesaleCompanyId' => 3501
  	],
	  'listings' => [
	  	[
        'listingId' => '365bro',
        'listingType' => 'sale',
        'title' => 'test listing CREATE from code',
        'description' => 'test description',
        'address1' => '1714 keyes court',
        'city' => 'loveland',
        'stateAbbrev' => 'CO',
        'country' => 'US',
        'zipCode' => '80537',
        'saleDates' => [
          'saleDate1' => '2018-02-28T09:05:00ZPT',
          'saleEndTime1' => '09:05:00ZPT'
        ],
        'images' => [
          [
            'id' => '2123123',
            'fileName' => 'cta-downsizing.jpg',
            'url' => 'http://grasonscodev.madwirebuild4.com/wp-content/uploads/2018/01/cta-downsizing.jpg'
          ]
        ]
	  	]
    ]
  ]
];

print_r($com->post_listing($params));