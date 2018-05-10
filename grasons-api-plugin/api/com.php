<?php 

class Com extends BaseApi 
{
    const ACCOUNT = 1626; 
    const URL = 'https://www.estatesale.com/api/v1/'; 
    const BASE = 'trans.php';
    const TYPE = 'sale';

    public function __construct(array $details, $images = [], $dates = [])  
    {        
        $this->post        = $details['post_id'];
        $this->company     = $details['com_id'];
        $this->address     = $details['address'];
        $this->city        = $details['city'];
        $this->zip         = $details['zip'];
        $this->state       = $details['state'];
        $this->title       = $details['title'];
        $this->description = $details['description'];
        $this->url         = $details['url'];
        if (!empty($images)) {
          $this->images = $this->normalize_images($images);
        }
        
        if (!empty($dates)) {
          $this->format_dates($dates);
        }

        $this->set_api_base();
    }

    protected function set_api_base() 
    {
        $creds = $this->get_credentials('com');

        try {

            if ($creds === false) {
                throw new Exception('Com credentials do not exist. Please provide proper username and password');
            } 

            $this->api_base  = $this::URL;
            $this->set_header('X-Authorization', $creds['token']);
            $this->set_header('Content-Type', 'application/json');

        } catch(Exception $e) {

            echo $e->getMessage();

        }
    }  

    /**
     * post a listing to estatesale.com
     * @param  [arr] $params_arr [array of parameters to post to message body in cURL handler]
     * @return [response]        [a response containing info about successfully created listing or an error message]
     */
    public function create_sale() 
    {
        if (!is_null($this->company)) {
            $body = [
                'operation' => 'save',
                'accountNumber' => $this::ACCOUNT,
                'data' => [
                    'companyInformation' => [ 
                      'estatesaleCompanyId' => $this->company
                    ],
                    'listings' => [
                        [
                            'listingId' => $this->company . mt_rand(10000,100000),
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
                            'saleDates' => $this->dates
                        ]
                    ]
                ]
            ];

            if (!empty($this->images)) {
              $body['data']['listings'][0]['images'] = $this->images;
            }

            /**
             * Convert the returned response object into an array because 
             * The API engineer left an empty space for the property key after listingId i.e. [listingId ]
             */
            $this->ids = (array)$this->create($this::BASE, $body)->data->listings[0];   
        }
    }

    protected function normalize_images($images) 
    {
      $normalized = [];

      foreach ($images as $image) {
        $normalized[] = [
          'url' => $image['url'],
          'id'  => uniqid(),
          'fileName' => $image['filename']
        ];
      }

      return $normalized;
    }

    public function delete_sale($listing_id, $estate_sale_id) 
    {
        if (!is_null($this->company) && !empty($listing_id) && !empty($estate_sale_id)) {
            $this->create(
                $this::BASE,
                [
                    'operation' => 'remove',
                    'accountNumber' => $this::ACCOUNT,
                    'data' => [
                        'companyInformation' => [ 
                          'estatesaleCompanyId' => $this->company
                        ],
                        'listings' => [
                            [
                              'listingId' => $listing_id,
                              'estatesaleListingId' => $estate_sale_id
                            ]
                        ]
                    ]
                ]
            );
        }
    }

    public function update_sale($id)
    {
        $this->delete_sale($id);
        $this->post_sale();
    }

    /**
     * Date format must be in 
     * @param  string $date ACF datetime
     * @return string TZ - PT formated datetime 
     */
    protected function format_dates(array $dates) 
    {
        $this->dates = [];
        $i = 0;
        foreach ($dates as $date) {
          $i++;
          $this->dates['saleDate' . $i] = $this->format_date(
            $date['sale_date_picker'] . ' ' . $date['sale_date_start_time'], 
            "Y-m-d\TH:i:s\Z", 
            null
          ) . 'PT';

          $this->dates['saleEndTime' . $i] = $date['sale_date_end_time'] . 'ZPT';

        }
    }

    /**
     * Date format must be in 
     * @param  string $date ACF datetime
     * @return string TZ - PT formated datetime 
     */
    protected function format_time(string $date) 
    {
        return gmdate("H:i:s\Z", strtotime($date)) . 'PT';
    }
}