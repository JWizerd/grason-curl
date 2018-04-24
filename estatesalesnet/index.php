<?php 

/**
 * @author  Jeremiah Wodke <[<jeremiah.wodke@madwiremedia.com>]> 2018
 */

$creds = include '../api_credentials.php';
require '../BaseApi.php';

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

	public function __construct(array $details, string $token)  
    {
        $this->orgId         = $details['account'];
        $this->name          = $details['title'];
        $this->description   = $details['description'];
        $this->address       = $details['address'];
        $this->zip           = $details['zip'];
        $this->url           = $details['url'];
        $this->refresh_token = $token;
        $this->set_api_base();
	}

    /**
     * use this when building all Guzzle requests EXCEPT @see SELF::generate_temporary_access_token()
     */
    protected function set_api_base() 
    {
        $this->api_base  = $this::BASE_URL . '/api/';
        $this->set_header('Authorization', 'Bearer ' . $this->generate_temporary_access_token());
        $this->set_header('X_XSRF', 'X_XSRF');
    }

    /**
     * All api requests require a temporary access_token that technically expires in 30 min
     * but since this is GoLive and we don't have time to accommodate for the myriad of edge cases
     * that an application such as this provides... We generate an access token every time a request is made
     * @return [string] [temporary access token for all api requests]
     */
	protected function generate_temporary_access_token() 
    {
        $client = new GuzzleHttp\Client(['base_uri' => 'https://www.estatesales.net']);

        $headers = [
            'Cache-Control:no-cache'
        ];

        $form_params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refresh_token
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
        return $this->create(
            'public-sales',
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
        return $this->create(
            'sale-pictures', 
            [
            'saleId'       => $this->listingId,
            'description'  => $image['description'],
            'url'          => $image['website_url'],
            'imageData'    => $this->convert_image_to_byte_array($image['url']),
            'thumbnailUrl' => $image['url']
            ]
        )->id;
    }

    /**
     * post every image from ACF Gallery Collection
     * @param  array  $images ACF Gallery Collection
     * @return an array newly posted image ids
     */
    public function post_images(array $images) {
        return array_map([$this, 'post_image'], $images);
    }

    /**
     * Date format must be in 
     * @param  string $date ACF datetime
     * @return string TZ - UTC formated datetime 
     */
    protected function format_date(string $date) 
    {
        return gmdate("Y-m-d\TH:i:s\Z", strtotime($date));
    }

    protected function post_date($date) 
    {
        return $this->create(
            'sale-dates',
            [
                'saleId' => $this->listingId,
                'utcStartDate' => $this->format_date($date['start']),
                'utcEndDate' => $this->format_date($date['end']),
                'showEndTime' => $this::SHOW_END_TIME
            ]
        )->id;
    }

    protected function post_dates(array $dates)
    {
        return array_map([$this, 'post_date'], $dates);
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
    public function create_sale(array $images_arr, $dates_arr) 
    {
        $this->listingId = $this->post_sale()->id;
        $images = json_encode($this->post_images($images_arr));
        $dates = json_encode($this->post_dates($dates_arr));

        echo $images;
        echo $dates;
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
        $this->delete('public-sales/' . (string)$id);
    }

}

// testing

$details = [
    'account'     => 23126,
    'title'       => 'GRASON TEST FROM APP 2',
    'description' =>  'test description',
    'address'     => '1714 keyes court',
    'zip'         => '80538',
    'url'         => 'http://example.com'
];

$images = [
    [
        'description' => 'sample desc',
        'url' => 'https://grasons.com/wp-content/uploads/2013/06/older-people-smiling.jpg',
        'website_url' => '"https://grasons.com'
    ],
    [
        'description' => 'sample desc 2',
        'url' => 'https://grasons.com/wp-content/uploads/2013/06/older-people-smiling.jpg',
        'website_url' => '"https://grasons.com'
    ],
    [
        'description' => 'sample desc 3',
        'url' => 'https://grasons.com/wp-content/uploads/2013/06/older-people-smiling.jpg',
        'website_url' => '"https://grasons.com'
    ]
];

$dates = [
    [
        'start' => date("Y-m-d H:i:s"),
        'end' => '2018-04-27 23:06:17'
    ]
];

$net = new Net($details, $creds['net']['refresh_token']);

$net->create_sale($images, $dates);
