<?php  

/**
 * @author 02.09.2018 Jeremiah Wodke <[<jeremiah.wodke@madwiremedia.com>]> 
 */

class Curl_Handler 
{

	protected $user_key;
	protected $url;
	protected $username = null;
	protected $password = null;
	protected $base_url = null;
	protected $headers = [];

	/**
	 * @return [set initial values for API request]
	 */

	public function __construct($user_key, $username = null, $password = null)  {

		$this->user_key = $user_key;
		$this->username = $username;
		$this->password = $password;

	}

	public function set_content_type($type) {
		array_push($this->headers, 'Content-Type:application/' . $type);
	}

	public function set_auth($type) {
		if ($type == 'basic') {
			array_push($this->headers, 'Authorization: Basic '. base64_encode($this->username . ':' . $this->password));	
		}
	}

	public function request($url, $endpoint, $headers) {

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $endpoint);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec ($ch);
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); //get status code
		curl_close ($ch);

		return $response;

	}

/**
 * Because I'm lazy and want to just port over one file instead of five files and worry about referencing them on the client's site I'm just going to use one file. Sowwwy.
 */ 

}

class Org extends Curl_Handler {

	protected $username = null;
	protected $password = null;
	protected $base_url = 'https://estatesales.org/api/v2';	
	protected $timezone = 'US/Central';
	protected $address;
	protected $city;
	protected $postal_code;
	protected $state_code;
	protected $lat = null;
	protected $lon = null;

	public function __construct($user_key, $address, $city, $postal_code, $state_code, $username, $password)  {

		$this->user_key    = $user_key;
		$this->address     = $address;
		$this->city        = $city;
		$this->postal_code = $postal_code;
		$this->state_code  = $state_code;
		$this->username    = $username;
		$this->password    = $password;

	}

	/**
	 * [for estatesales.org ONLY. Used to POST listings based from GPS coordinates to specify a specific ]
	 * @param  
	 * @return json response 
	 */
	public function get_coordinates() {

		$geocode_url = $this->base_url . '/geocode/get';

		return json_decode($this->request($geocode_url, 'user_key=' . $this->user_key . '&address=' . $this->address . '&city=' . $this->city . '&state_code=' . $this->state_code . '&postal_code=' . $this->postal_code, $this->headers));

	}

	/**
	 * [set the locations coordinates from the cURL response get_coordinates()]
	 */

	public function set_coordinates() {
		
		$coords = $this->get_coordinates();

		$this->lat = $coords->location->lat;
		$this->lon = $coords->location->lon;

	}

}

$org = new Org('5749-0950-0d1d-4c13-9ed8-6154', '18308 Wind Valley Way', 'Pflugerville', '78660', 'TX', 'grasons', 'DgR7s253iSui3yFwmwcyGqH5tGNeJb');

$org->set_content_type('x-www-form-urlencoded');
$org->set_auth('basic');

$org->set_coordinates();