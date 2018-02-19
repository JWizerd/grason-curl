<?php  

/**
 * @author 02.09.2018 Jeremiah Wodke <[<jeremiah.wodke@madwiremedia.com>]> 
 */

class DB 
{
  protected $host    = 'localhost';
  protected $db      = 'grason_curl_wordpress';
  protected $user    = 'root';
  protected $pass    = 'root';
  protected $charset = 'utf8mb4';
  public $pdo;

  public function __construct() {
    $this->open_connection();
  }  

  public function get_pdo_obj($pdo) {
    return $this->pdo;
  }

  public function open_connection() {
    $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
      $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
    }
    catch(Exception $e) {
      echo $e->getMessage();
    }
  }

}

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

class Org extends Curl_Handler 
{

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
          'user_key'    => $this->user_key,
          'address'     => $this->address,
          'city'        => $this->city,
          'state_code'  => $this->state_code,
          'postal_code' => $this->postal_code,
          'type'        => 'traditional'
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
  private function set_coordinates() {

    $coords = $this->get_coordinates();

    $this->base_query['lat'] = $coords->location->lat;
    $this->base_query['lon'] = $coords->location->lon;

  }

  private function set_listing_id($id) {
    $this->listing_id = $id;
  }

  public function get_listing_id() {
    return $this->listing_id;
  }

  /**
   * retrieve listing id related to post
   * @param  [int] $post_id
   * @return [id] [the listing id taken from api]
   */
  private function get_db_listing_info($post_id) {
    $db = new DB();
    $stmt = $db->pdo->prepare("SELECT listing_id, user_key FROM listings WHERE post_id = ?"); 
    $stmt->execute([$post_id]);
    $listing = $stmt->fetch();
    // close connection
    $db = null;
    $stmt = null;
    return $listing;
  }

  public function delete_db_listing($post_id) {
  
    $db = new DB();
    $stmt = $db->pdo->prepare("DELETE FROM listings WHERE post_id = ?");
    $stmt->execute([$post_id]);
    // close connection
    $db = null;
    $stmt = null;

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
  public function display_listing($user_key, $listing_id, $show_hide) {

    $url = $this->base_url . '/sale/publish/set';

    $params = [
      
      'user_key' => $user_key,
      'id' => $listing_id,
      'publish' => $show_hide

    ];

    // echo "<pre>";
    //   print_r($params);
    // echo "</pre>";
    
    $response = json_decode($this->request($url, $params, $this->headers));

  }

  public function hide_listing($post_id) {

    $info = $this->get_db_listing_info($post_id);
    $user_key = $info['user_key'];
    $listing_id = $info['listing_id'];
    $this->display_listing($user_key, $listing_id, 'false');

  }

  /**
   * [post listing to estate sales .org api.]
   * @param $[params_arr] [<an arr containing all params to add for initial content of post. Images will come next in sequence.>]
   * https://estatesales.org/api/v2/sale/set
   */
  public function post_listing($params_arr) {

    $url = $this->base_url . '/sale/set';

    $this->set_coordinates();

    $endpoint = $this->build_endpoint($params_arr);
    $listing = json_decode($this->request($url, $endpoint, $this->headers));

    $this->set_listing_id($listing->sale->id);
    $this->display_listing($this->user_key, $listing->sale->id, 'true');
    
  } 

  /**
   * [post an image to the estatesales.org account]
   * @return [respsone obj]
   */
  public function post_images($images_arr) {

    $url = $this->base_url . '/sale/photo/remote/add';

    if (count($images_arr) > 0) {

      foreach ($images_arr as $row => $image) {

        $params = [
          'sale_id' => $this->listing_id,
          'url' => wp_get_attachment_url($image['field_5a69fa961c0b7'])
        ];
        
        $endpoint = $this->build_endpoint($params);
        $response = json_decode($this->request($url, $endpoint, $this->headers));

      }
      
    }
  
  }
  
  /**
   * 
   * this method can be used to query the recently created sale to check for errors. Useful for image upload errors.
   * @return response sale obj
   */
  public function get_sale($listing_id) {
    $url = $this->base_url . '/sale/get';

    $endpoint = [
      'user_key' => $this->user_key,
      'id'       => $listing_id
    ];

     return json_decode($this->request($url, $endpoint, $this->headers));
  }

  /**
   * return json formatted obj of date ACFs in proper format for API
   * @param  an array of dates and times
   * @return the formatted json dates obj
   */
  public function format_dates($dates_arr) {

    $formatted = [];

    foreach ($dates_arr as $row => $date) {

      // format raw format returned from uprocessed ACF
      $format_in = 'Ymd';
      $format_out = 'Y-m-d';
      $temp_date = DateTime::createFromFormat($format_in, $date['field_5a8334c3826bc']);
      $new_date = $temp_date->format( $format_out );

      if (!empty($date['field_5a833588826bf'])) {

        $formatted[$new_date] = [$date['field_5a83356e826be'], $date['field_5a833588826bf']];  

      } else {

        $formatted[$new_date] = [$date['field_5a83356e826be']];

      }

    }

    return json_encode($formatted);

  }

  /**
   * Store Listing in DB for DELETION and UPDATE Methods
   * @param  [int] $post_id 
   * @param  [int] $listing_id [listing id generated from api to estatesales.org]
   */
  public function save($post_id, $listing_id) {
    $db = new DB();
    $stmt = $db->pdo->prepare("INSERT INTO listings (post_id, listing_id, user_key) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $listing_id, $this->user_key]);

    // close connection
    $db = null;
    $stmt = null; 
  }

}


// Run the API Calls Upon Estate Sale Custom Post Type CREATION OR DELETION
add_action( 'transition_post_status', 'post_estate_sale_to_apis', 10, 3 );

function post_estate_sale_to_apis( $new_status, $old_status, $post ) { 

  $id       = $post->ID;
  $fields   = $_POST['acf'];  
  $title    = get_the_title($post->ID);
  $address  = $fields['field_5a69f982b7d3a'];
  $account  = $fields['field_5a83313334dc9'];
  $city     = $fields['field_5a69f998b7d3b'];
  $state    = $fields['field_5a8332d3fb503'];
  $zipcode  = $fields['field_5a833310fb504'];
  $timezone = $fields['field_5a833d1c1c319'];
  $descr    = $fields['field_5a8330f0e668a'];
  $dates    = $fields['field_5a69f9e9b7d3c'];
  $images   = $fields['field_5a69fa731c0b6'];

  $org = new Org($account, $address, $city, $zipcode, $state);
  $org->set_content_type('x-www-form-urlencoded');
  $org->set_auth('basic');

  if ($post->post_type == 'estatesales') {

    if (($old_status == 'draft' || $old_status == 'auto-draft') && $new_status == 'publish') {
      
      $params = [
        'descr' => $descr, 
        'title' => $title,
        'timezone' => $timezone, 
        'dates' => $org->format_dates($dates)
      ];

      $org->post_listing($params);
      $org->post_images($images);
      $org->save($id, $org->get_listing_id());
      
    } elseif ($old_status == 'publish' &&  $new_status == 'trash') {


      $org->hide_listing($id);
      $org->delete_db_listing($id);
      /**
       * @todo  delete all media files related to a post when post is trashed
       * @link( wp_delete_attachment( $gallery_id, true ), https://developer.wordpress.org/reference/functions/wp_delete_attachment/)
       */

      
    } elseif ($old_status == 'publish' &&  $new_status == 'publish') {

      /**
       * @todo  test to see if this section works
       */

      $org->hide_listing($id);
      $org->delete_db_listing($id);

      $params = [
        'descr' => $descr, 
        'title' => $title,
        'timezone' => $timezone, 
        'dates' => $org->format_dates($dates)
      ];

      $org->post_listing($params);
      $org->post_images($images);
      $org->save($id, $org->get_listing_id());

    }
    
  }

}