<?php 

/**
 * @author  Jeremiah Wodke <[<jeremiah.wodke@madwiremedia.com>]> 2018
 */

class Net extends BaseApi 
{
    const BASE_URL = 'https://www.estatesales.net';

    /**
     * the default sale type for 'EstateSale' as defined by .net
     */
    const SALE_TYPE = 1;

    /**
     * We always want to show the end time if available 
     * when creating dates for estate sales
     */
    const SHOW_END_TIME = true;

    /**
     * show address type is required I have no idea what 
     * the different types are as there is no documentation
     */
    const SHOW_ADDRESS_TYPE = 1;

    public function __construct(array $details, $images = [], $dates = [])  
    {
        $this->company     = $details['net_id'];
        $this->post        = $details['post_id'];
        $this->name        = $details['title'];
        $this->description = $details['description'];
        $this->address     = $details['address'];
        $this->zip         = $details['zip'];
        $this->url         = $details['url'];
        $this->timezone    = $details['timezone'];
        $this->set_api_base();

        if (!empty($images)) {
          $this->images = $images;
        }
        
        if (!empty($dates)) {
          $this->format_dates($dates);
        }
    }

    /**
     * use this when building all Guzzle requests EXCEPT @see SELF::generate_temporary_access_token()
     */
    protected function set_api_base() 
    {
        if (!is_null($this->company)) {
        
            $creds = $this->get_credentials('net');
            
            try {

                if ($creds === false) {
                    throw new Exception('Net credentials do not exist. Please provide proper username and password');
                } 

                $this->api_base  = $this::BASE_URL . '/api/';
                $this->set_header('Authorization', 'Bearer ' . $this->generate_temporary_access_token($creds['refresh_token']));
                $this->set_header('X_XSRF', 'X_XSRF');

            } catch(Exception $e) {

                echo $e->getMessage();

            }
        }
    }

    /**
     * All api requests require a temporary access_token that technically expires in 30 min
     * but since this is GoLive and we don't have time to accommodate for the myriad of edge cases
     * that an application such as this provides... We generate an access token every time a request is made
     * @return [string] [temporary access token for all api requests]
     */
    protected function generate_temporary_access_token(string $token) 
    {
        $client = new GuzzleHttp\Client(['base_uri' => 'https://www.estatesales.net']);

        $headers = [
            'Cache-Control:no-cache'
        ];

        $form_params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $token
        ];

        $response = $client->post('/token', [ 'headers' => $headers, 'form_params' => $form_params ]);

        return json_decode($response->getBody())->access_token;
   }

    /**
     * POST a sale to a .net account
     * @return response obj containing a new listings id
     * @todo  store listing id in Listing database
     */
    protected function post_sale() 
    {
        try {
            return $this->create(
                'public-sales',
                [
                    'orgId'            => $this->company,
                    'saleType'         => $this::SALE_TYPE,
                    'postalCodeNumber' => $this->zip,
                    'address'          => $this->address,
                    'name'             => $this->name,
                    'description'      => $this->description,
                    'showAddressType'  => $this::SHOW_ADDRESS_TYPE,
                    'url'              => $this->url,
                    'terms'            => '',
                    'directions'       => ''
                ]
            );
        } catch(GuzzleHttp\Exception\ClientException $e) {
            return;
        }
    }

    /**
     * Take a path to an image [in this case the post thumbnail url]
     * convert into a byte array which in this case is a massive array 
     * of ASCII chart Indexes
     *
     * Of course this is only one parameter that is required for an image 
     * post request. The url to the image is also required LOL. 
     * @param  [string] absolute image path
     * @return [array] massive image byte array
     * @see   [SELF::post_images()]
     */
    protected function convert_image_to_byte_array(string $image_path) 
    {

        $opts = [
          "http" => [
            "method" => "GET",
            "header" => "Content-Type: image/jpeg\r\n"
          ]
        ];

        // the stream, in this instance, is a set of conditions for 
        // which the file will be encoded when it is returns. 
        // this was needed as the intial response gave us the default 
        // HTML encoding rather than image/jpeg encoding
        $context = stream_context_create($opts);

        $file = file_get_contents(
                    $image_path, 
                    false, 
                    $context
                );

        $image = [];

        foreach(str_split($file) as $char){ 
            // convert each character into ASCII indexes 
            // see ASCII chart for examples
            array_push($image, ord($char)); 
        }
        
        return $image;

    }

    /**
     * @param  array  Wordpress image array. Most likely a child of a parent image collection.
     * @return the id of the newly posted image
     */
    protected function post_image(array $image) 
    {
        try {
            return $this->create(
                'sale-pictures',
                /**
                 * @todo using BaseApi::get_attachment_urls() first change it get attachment array. Then correctly pass data to this function and all other post image methods.
                 */
                [
                    'saleId'       => $this->id,
                    'imageData'    => $this->convert_image_to_byte_array($image['url']),
                    'thumbnailUrl' => $image['url']
                ]
            )->id;
        } catch (GuzzleHttp\Exception\ServerException $e) {
            return;
        }
    }

    /**
     * post every image from ACF Gallery Collection
     * @param  array  $images ACF Gallery Collection
     * @return an array newly posted image ids
     */
    public function post_images() {
        if (!empty($this->images)) {
            return array_map([$this, 'post_image'], $this->images);
        }
        return;
    }

    /**
     * Method that is ran inside of the constructor which 
     * normalizes the ACF dates array for easier usage 
     * IF dates (Y-M-D) are equal to each other stop execution
     * IF date 2 (Y-M-D) is greater than date 1 by 4 days stop execution
     * @param  string $date ACF datetime
     */
    public function format_dates(array $dates) 
    {
        if (!empty($dates[1])) {
            if (
                strtotime($dates[0]['sale_date_picker']) !== strtotime($dates[1]['sale_date_picker'])
                ||
                strtotime($dates[1]['sale_date_picker']) < strtotime($this->format_date($dates[0]['sale_date_picker'], 'Y-m-d', '+4 days'))
            ) {
                $this->formatter($dates);
            } else {
                return;
            }   
        } else {
            $this->formatter($dates);
        }
    }

    protected function formatter(array $dates) 
    {
        $formatted = [];
        $format = 'Y-m-d\TH:i:s\Z';
        foreach ($dates as $date) {
            $formatted[] = 
                [
                    'start' => $this->format_date(
                                    $date['sale_date_picker'] . ' ' . $date['sale_date_start_time'], 
                                    $format,
                                    '+6 hours'
                                ),
                    'end'   => $this->format_date(
                                    $date['sale_date_picker'] . ' ' . $date['sale_date_end_time'], 
                                    $format,
                                    '+6 hours'
                                )
                ];
        }

        $this->dates = $formatted;   
    }

    protected function post_date($date) 
    {
        try {
            return $this->create(
                'sale-dates',
                [
                    'saleId' => $this->id,
                    'utcStartDate' => $date['start'],
                    'utcEndDate' => $date['end'],
                    'showEndTime' => $this::SHOW_END_TIME
                ]
            )->id;
        } catch (GuzzleHttp\Exception\ServerException $e) {
            return;
        }
    }

    protected function post_dates()
    {
        return array_map([$this, 'post_date'], $this->dates);
    }

    /**
     * POST a Sale, it's images and it's dates to the API
     * then, store the id of the listing and serialized arrays 
     * into the Listings table
     * IMPORTANT NOTE: due to 
     * @param  $images_arr ACF Gallery Collection
     * @param  $dates_arr ACF DATETIME Collection
     * @todo  Store data in Listings Table
     */
    public function create_sale() 
    {
        if (!is_null($this->company)) {
            $this->id = $this->post_sale()->id;

            json_encode($this->post_images());

            if (!empty($this->dates)) {
                json_encode($this->post_dates());
            }
        }
    }

    public function update_sale($id) 
    {
        if ($this->exists($id)) {
            $this->update(
                'put',
                'public-sales/' . (string)$id,
                [
                    'orgId'            => $this->orgId,
                    'saleType'         => $this::SALE_TYPE,
                    'postalCodeNumber' => $this->zip,
                    'address'          => $this->address,
                    'name'             => $this->name,
                    'description'      => $this->description,
                    'showAddressType'  => $this::SHOW_ADDRESS_TYPE,
                    'url'              => $this->url,
                    'terms'            => '',
                    'directions'       => ''
                ]
            );
        } else {
            $this->post_sale();
        }
    }

    protected function exists($id) {
        try { 
            $this->get('public-sales/' . (string)$id);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            return 0;
        }

        return 1;
    }

    public function delete_sale($id) 
    {
        if (!is_null($this->company) && !empty($id)) {
            $this->delete('public-sales/' . (string)$id);
        }
    }
}