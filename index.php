<?php  

/**
 * @author 02.09.2018 Jeremiah Wodke <[<jeremiah.wodke@madwiremedia.com>]> 
 */

class Curl_Handler 
{

	protected $user_key;
	protected $url;
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
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($endpoint, '', '&'));
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec ($ch);
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); //get status code
		curl_close ($ch);

		return $response;

	}

}

class Org extends Curl_Handler {

	protected $username = 'grasons';
	protected $password = 'DgR7s253iSui3yFwmwcyGqH5tGNeJb';
	protected $base_url = 'https://estatesales.org/api/v2';	
	protected $timezone = 'US/Central';
	protected $address;
	protected $city;
	protected $postal_code;
	protected $state_code;

	public function __construct($user_key, $address, $city, $postal_code, $state_code)  {

		$this->user_key    = $user_key;
		$this->address     = $address;
		$this->city        = $city;
		$this->postal_code = $postal_code;
		$this->state_code  = $state_code;
		$this->set_base_query();

	}

	private function set_base_query() {

		$this->base_query = [
					'user_key' => $this->user_key,
					'address'  => $this->address,
					'city'     => $this->city,
					'state_code' => $this->state_code,
					'postal_code' => $this->postal_code
				];

	}

	/**
	 * [for estatesales.org ONLY. Used to POST listings based from GPS coordinates to specify a specific ]
	 * @param  
	 * @return json response 
	 * https://estatesales.org/api/v2/geocode/get
	 */
	public function get_coordinates() {

		$geocode_url = $this->base_url . '/geocode/get';

		return json_decode($this->request($geocode_url, $this->base_query, $this->headers));

	}

	/**
	 * [set the locations coordinates from the cURL response get_coordinates()]
	 */
	public function set_coordinates() {

		$coords = $this->get_coordinates();

		$this->lat = $coords->location->lat;
		$this->lon = $coords->location->lon;

	}

	/**
	 * https://estatesales.org/api/v2/sale/set
	 */
	public function post_listing() {

		$url = $this->base_url . '/sale/set';

		$this->set_coordinates();

		$params = [
			'type' => 'traditional',
      'lat' => $this->lat,
      'lon' => $this->lon, 
      'descr' => 'test description', 
      'title' => 'MadWire',
      'timezone' => $this->timezone, 
      'dates' => json_encode(
      	[ 
      		'2018-02-13' => ["10:30"],  
      		'2018-04-01' => ["16:00"]
      	]
      )
		];

		$endpoint = $this->build_endpoint($params);
		$listing = json_decode($this->request($url, $endpoint, $this->headers));

		$this->publish_listing($listing->sale->id);
    
	}

	/**
	 * @param Associative Array of Params
	 * @return [type] [description]
	 */
	private function build_endpoint($params) {

		$endpoint = $this->base_query;
    
    // Push values to endpoint storing base_query
    foreach ($params as $key => $value) {
    	$endpoint[$key] = $value;
    }

    return $endpoint;

	}

	/**
	 * [publish_listing on estatesales.org. You can remove a listing by firing another API request to the sale and setting publish=false]
	 * @param  [int] $listing_id [id generated by post_listing() api response]
	 * @return response whether listing was properly published or not
	 * https://estatesales.org/api/v2/sale/publish/set
	 */
	public function publish_listing($listing_id) {

		$url = $this->base_url . '/sale/publish/set';

		$params = [

			'id' => $listing_id,
			'publish' => true

		];

		$endpoint = $this->build_endpoint($params);

		$response = json_decode($this->request($url, $endpoint, $this->headers));

	}

}

$org = new Org('5749-0950-0d1d-4c13-9ed8-6154', '18308 Wind Valley Way', 'Pflugerville', '78660', 'TX');

$org->set_content_type('x-www-form-urlencoded');
$org->set_auth('basic');
$org->post_listing();