<?php 

/** (°_ʖ°) */

class Curl_Handler
{

  protected $user_key;
  protected $url;
  protected $headers = [];
  protected $base_query;
  protected $token;

  /**
   * @return [set initial values for API request]
   */

  public function __construct($user_key, $username = null, $password = null)  {

    $this->user_key = $user_key;
    $this->username = $username;
    $this->password = $password;
    $this->token    = $token;

  }

  public function set_header($type) {
    array_push($this->headers, $type);
  }

  public function set_content_type($type) {
    switch ($type) {
      case 'urlencoded':
        array_push($this->headers, 'Content-Type:application/x-www-form-urlencoded' . $type);
        break;
      case 'application':
        array_push($this->headers, 'Content-Type:application/' . $type);
        break;
    }
  }

  public function set_auth($type) {
    switch ($type) {
      case 'basic':
        array_push($this->headers, 'Authorization: Basic '. base64_encode($this->username . ':' . $this->password));  
        break;
      
      case 'xauth':
        array_push($this->headers, 'X-Authorization: '. $this->user_key);  
        break;

      case 'bearer':
        array_push($this->headers, 'Authorization: Bearer '. $this->token);
    }
  }

  public function get_token() {
    return $this->token;
  }

  public function get_headers() {
    return $this->headers;
  }

  /**
   * @param [type] $arr [arr of params to bind to cURL request in build_param()]
   */
  public function set_base_query($arr) {
    $this->base_query = $arr;
  }

  public function request($url, $endpoint = null, $headers, $message_body = null, $message_type = null) {

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);

    if (!is_null($endpoint)) {

      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($endpoint, '', '&')); 

    } elseif(!is_null($message_body)) {

      if ($message_type === 'json') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message_body));
      } elseif ($message_type === 'form') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($message_body, '', '&')); 
      }
     
    }
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

  protected function build_message_body($params) {
    $body = $this->base_query;
    array_push($message, $params);
    return $body;
  }

}