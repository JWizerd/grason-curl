<?php

require '../BaseApi.php';

class Org extends BaseApi 
{
    const BASE_URL = 'https://estatesales.org/api/v2/'; 
    const TIMEZONE = 'US/Central';
    const TYPE     = 'traditional';

    public function __construct(array $details, array $images, array $dates)  
    {
        $this->account     = $details['account'];
        $this->title       = $details['title'];
        $this->description = $details['description'];
        $this->images      = $images;
        $this->dates       = $dates;
        $this->base_query  =
          [
            'user_key'    => $this->account,
            'address'     => $details['address'],
            'city'        => $details['city'],
            'state_code'  => $details['state'],
            'postal_code' => $details['zip'],
            'type'        => $this::TYPE, 
            'timezone'    => $this::TIMEZONE
          ];
        $this->set_api_base();
        $this->set_coordinates();
    }

    protected function set_api_base() 
    {  
        $creds = $this->get_credentials('org');

        try {

            if ($creds === false) {
                throw new Exception('Org credentials do not exist. Please provide proper username and password');
            } 

            $this->api_base  = $this::BASE_URL;
            $this->set_header('Authorization', 'Basic ' . base64_encode($creds['username'] . ':' . $creds['password']));
            $this->set_header('Content-Type', 'application/x-www-form-urlencoded');
        } catch(Exception $e) {

            echo $e->getMessage();

        }
    }  

  /**
   * [for estatesales.org ONLY. Used to POST listings based 
   * from GPS coordinates to specify a specific sale/listing]
   */
    protected function set_coordinates() 
    {
        $response = $this->post_form('geocode/get', $this->base_query);

        $this->base_query['lat'] = $response->location->lat;
        $this->base_query['lon'] = $response->location->lon;
    }

  /**
   * [publish_listing on estatesales.org. You can remove a listing by 
   * firing another API request to the sale and setting publish=false]
   * @param  [int] $listing_id [id generated by post_listing() api response]
   * @return response whether listing was properly published or not
   */
  protected function display_listing($user_key, $listing_id, $show_hide) 
  {
    
    $this->post_form(
        'sale/publish/set', 
        [
          
          'user_key' => $user_key,
          'id' => $listing_id,
          'publish' => $show_hide

        ]
    );

  }

  public function hide_listing($id) 
  {

    // $info = Listing::get($post_id);
    // $user_key = $user_key || $info['user_key'];
    // $listing_id = $info['listing_id'];

    $this->display_listing($this->account, $id, 'false');

  }

  /**
   * [post listing to estate sales .org api.]
   * @param $[params_arr] [<an arr containing all params to 
   * add for initial content of post. Images will come next in sequence.>]
   */
  public function create_listing() 
  {
    $this->id = $this->post_sale()->sale->id;
    print_r($this->id);
    $this->post_images();
    $this->display_listing($this->account, $this->id, 'true'); 
  } 

  protected function post_sale() 
  {
    $params = $this->base_query;
    $params['title'] = $this->title;
    $params['descr'] = $this->description;
    $params['dates'] = json_encode(
        [
            '2018-4-28' => ['09:00','14:00'], 
            '2018-4-29' => ['08:30']
        ]
    );

    return $this->post_form('sale/set', $params);
  }

  /**
   * @param  array  Wordpress image array. Most likely 
   * a child of a parent image collection.
   * @return the id of the newly posted image
   */
  protected function post_image(array $image) 
  {
    return $this->post_form(
        'sale/photo/remote/add', 
        [
            'user_key' => $this->account,
            'sale_id' => $this->id,
            'url' => $image['url']
        ]
    );
  }

  /**
   * post every image from ACF Gallery Collection
   * @param  array  $images ACF Gallery Collection
   * @return an array newly posted image ids
   */
  public function post_images() 
  {
    if (!empty($this->images)) {
        return array_map([$this, 'post_image'], $this->images);
    }
    return;
  }
  
  /**
   * 
   * this method can be used to query the recently created 
   * sale to check for errors. Useful for image upload errors.
   * @return response sale obj
   */
  public function get_sale($listing_id) 
  {
    $this->get(
        'sale/get', 
        [
            'user_key' => $this->user_key,
            'id'       => $listing_id
        ]
    );
  }

  /**
   * return json formatted obj of date ACFs in proper format for API
   * @param  an array of dates and times
   * @return the formatted json dates obj
   */
  public function format_dates($dates_arr) 
  {

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

}

$org = new Org($details, $images, $dates);

// $details = [
//     'account' => '5749-0950-0d1d-4c13-9ed8-6154',
//     'title' => 'TESTING CREATE',
//     'description' => 'Sample',
//     'address' => '3608 Madison Ave',
//     'city' => 'loveland',
//     'state' => 'CO',
//     'zip' => '80538'
// ];

// $images = [
//     [
//         'description' => 'sample desc',
//         'url' => 'https://grasons.com/wp-content/uploads/2013/06/older-people-smiling.jpg',
//         'website_url' => '"https://grasons.com'
//     ]
// ];

// $dates = [];

// print_r($org->create_listing());
// $org->hide_listing(1509299);