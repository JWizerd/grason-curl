<?php 

$creds = include '../api_credentials.php';
require '../BaseApi.php';

class Com extends BaseApi 
{
    const ACCOUNT = 1626; 
    const URL = 'https://www.estatesale.com/api/v1/'; 
    const BASE = 'trans.php';
    const TYPE = 'sale';

    public function __construct(array $details, array $images, array $dates, string $auth_key)  
    {
      $this->company     = $details['company'];
      $this->address     = $details['address'];
      $this->city        = $details['city'];
      $this->zip         = $details['zip'];
      $this->state       = $details['state'];
      $this->title       = $details['title'];
      $this->description = $details['description'];
      $this->url         = $details['url'];
      $this->images      = $images;
      $this->dates       = $dates;
      $this->token       = $auth_key;
      $this->set_api_base();
    }

    protected function set_api_base() 
    {
        $this->api_base  = $this::URL;
        $this->set_header('X-Authorization', $this->token);
        $this->set_header('Content-Type', 'application/json');
    }  

    /**
     * post a listing to estatesale.com
     * @param  [arr] $params_arr [array of parameters to post to message body in cURL handler]
     * @return [response]        [a response containing info about successfully created listing or an error message]
     */
    public function post_sale() 
    {
        $body = [
            'operation' => 'save',
            'accountNumber' => $this::ACCOUNT,
            'data' => [
                'companyInformation' => [ 
                  'estatesaleCompanyId' => $this->company
                ],
                'listings' => [
                    [
                        'listingId' => uniqid(),
                        'listingType' => $this::TYPE,
                        'title' => $this->title,
                        'description' => $this->description,
                        'address1' => $this->address,
                        'city' => $this->city,
                        'stateAbbrev' => $this->state,
                        'country' => $this::COUNTRY,
                        'zipCode' => $this->zip,
                        'auction' => [
                            'catalogUrl' => $this->url
                        ],
                        'saleDates' => [
                            'saleDate1'    => '2018-5-27T09:05:00ZPT',
                            'saleEndTime1' => '02:05:00ZPT',
                            'saleDate2'    => '2018-5-30T09:05:00ZPT',
                            'saleEndTime2' => '02:05:00ZPT'
                        ],
                        'images' => $this->images
                    ]
                ]
            ]
        ];

        return json_encode((array)$this->create($this::BASE, $body)->data->listings[0]);
    }

    public function delete_sale($id) 
    {
        $this->create(
            $this::BASE,
            [
                'operation' => 'remove',
                'accountNumber' => $this::ACCOUNT,
                'data' => [
                    'companyInformation' => [ 
                      'listingId' => $this->company
                    ],
                    'listings' => [
                        'listingId' => '5ae0f7989f389',
                        'estateSaleListingsId' => 171643
                    ]
                ]
            ]
        );
    }

    public function update_sale($id)
    {
        $this->delete_sale($id);
        print_r($this->post_sale());
    }

    /**
     * Date format must be in 
     * @param  string $date ACF datetime
     * @return string TZ - PT formated datetime 
     */
    protected function format_date(string $date) 
    {
        return gmdate("Y-m-d\TH:i:s\ZPT", strtotime($date));
    }

    /**
     * Date format must be in 
     * @param  string $date ACF datetime
     * @return string TZ - PT formated datetime 
     */
    protected function format_time(string $date) 
    {
        return gmdate("H:i:s\ZPT", strtotime($date));
    }

}

$details = [
  'company' => 3501,
  'address' => '1714 keyes court',
  'city'    => 'loveland',
  'zip'     => 80537,
  'state'   => 'CO',
  'title'   => 'FINAL',
  'description' => 'sample description',
  'url'         => 'http://example.com'
];

$images =  [
    [
      'id' => '2123123',
      'fileName' => 'cta-downsizing.jpg',
      'url' => 'http://grasonscodev.madwirebuild4.com/wp-content/uploads/2018/01/cta-downsizing.jpg'
    ],
    [
      'id' => '1234234123',
      'fileName' => 'cta-downsizing.jpg',
      'url' => 'http://grasonscodev.madwirebuild4.com/wp-content/uploads/2018/01/cta-downsizing.jpg'
    ]
];

$dates = [
   [
     'saleDate1' => '2018-02-28T09:05:00ZPT',
     'saleEndTime1' => '09:05:00ZPT'
   ], 
   [
     'saleDate2' => '2018-02-28T09:05:00ZPT',
     'saleEndTime2' => '09:05:00ZPT'
   ]
];

$com = new Com($details, $images, $dates, $creds['com']['token']);
print_r($com->post_sale());
// print_r($com->update_sale('5ae0f47967851'));