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
     * show address type is required I have no idea what 
     * the different types are as there is no documentation
     */
    const SHOW_ADDRESS_TYPE = 1;

	public function __construct(array $details, $token)  
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
     * for better usability of GuzzleHttp\Client instantiation
     * @return [obj]
     */
    protected function api() 
    {
        return (new GuzzleHttp\Client(['base_uri' => $this->api_base]));   
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

        $response = $client->request('POST', '/token', [ 'headers' => $headers, 'form_params' => $form_params ]);

        return json_decode($response->getBody())->access_token;
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
     * @see   [<description>]
     */
    protected function convert_image_to_byte_array($image_path) 
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
        
        return json_encode($image);

    }

    /**
     * POST a sale to a .net account
     * @return response obj containing a new listings id
     * @todo  store listing id in Listing database
     */
    public function create() 
    {
        $response = $this->api()->request('POST', 'public-sales', [
            'headers' => $this->headers,
            'json' => [
                'orgId' => $this->orgId,
                'saleType' => $this::SALE_TYPE,
                'postalCodeNumber' => $this->zip,
                'address' => $this->address,
                'name' => $this->name,
                'description' => $this->description,
                'showAddressType' => $this::SHOW_ADDRESS_TYPE,
                'url' => $this->url,
                'terms' => '',
                'directions' => ''
            ]
        ]);

        return json_decode($response->getBody());
    }

    protected function post_images(array $image_paths) 
    {

    }

    public function update($id) 
    {

    }

    public function delete($id) 
    {
        
    }
}

// testing

$details = [
    'account'     => 23126,
    'title'       => 'GRASON TEST FROM APP',
    'description' =>  'test description',
    'address'     => '1714 keyes court',
    'zip'         => '80538',
    'url'         => 'http://example.com'
];

$net = new Net($details, $creds['net']['refresh_token']);
// print_r($net->create());