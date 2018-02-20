<?php 

class Curl_Handler
{

  protected $user_key;
  protected $url;
  protected $headers = [];
  protected $base_query;

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

  /**
   * @param [type] $arr [arr of params to bind to cURL request in build_param()]
   */
  public function set_base_query($arr) {
    $this->base_query = $arr;
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

  /**
   * @param Associative Array of Params
   * @return [type] [description]
   */
  protected function build_endpoint($params) {

    $endpoint = $this->base_query;
    
    // Push values to endpoint storing base_query
    foreach ($params as $key => $value) {
      $endpoint[$key] = $value;
    }

    return $endpoint;

  }

}