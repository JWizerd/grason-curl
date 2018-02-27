<?
require_once __DIR__ . '/Facebook/autoload.php'; // change path as needed

class Facebook_Api {

  protected $fb;
  protected $app_id;
  protected $app_secret;
  /**
   * @var string access_token [a never expriring access token]
   * @link(https://developers.facebook.com/tools/explorer, generate never expriring access token)
   * @link(https://developers.facebook.com/docs/marketing-api/access, in article see section 'Exchange for Long-Lived Access Token')
   */
  protected $access_token;
  protected $page_token;
  protected $me;
  protected $pages;

  public function __construct($app_id, $app_secret) {
    $this->fb = new \Facebook\Facebook([
      'app_id' => $app_id,
      'app_secret' => $app_secret,
      'default_graph_version' => 'v2.12'
    ]);

    // retrieve access token from db
    $this->fb->setDefaultAccessToken($this->get_access_token());
  }
  

  /**
   * @return [string] access token [the access token retrieved from ACF option]
   */
  protected function get_access_token() {
    // $access_token = get_option('fb_access_token', 'option');
    $this->set_access_token('EAAci2U9sXhgBAL9hDM8xqkWKXjm1KKzfJAtetOIGVIsZALc90DhTE8LemZBVtZCqCXvyYkGACrQ0b0HBVOgv1meS9i6ignVtt1ZAERZCD3eJn2wNqkQNw1gPiHxhsXW4ZCeSCGe0m3sNAShZCu5DZCq1CZBbTFZAHGCBQZD');

    // temporarily hard code default access token
    return $this->access_token;
  }
  
  protected function set_access_token($access_token) {
    $this->access_token = $access_token;
  }

  /**
   * [get all pages managed by facebook app / facebook user]
   * @return [array] [a collection of facebook pages]
   */
  public function get_pages() {
    try {

      $fb_user = $this->fb->get('/me/accounts');
      return $fb_user->getGraphEdge()->asArray();

    } catch(Facebook\Exceptions\FacebookResponseException $e) {

      // When Graph returns an error
      echo 'Error Retrieving Pages from Facebook. Please Contact Administrator with this Error Message: ' . $e->getMessage();
      exit;

    } catch(Facebook\Exceptions\FacebookSDKException $e) {

      // When validation fails or other local issues
      echo 'There was an Error with Facebook SDK. Please Contact Administrator with this Error Message: ' . $e->getMessage();
      exit;

    }
  }

  public function post_exists($post_id) {

    return !empty( $this->fb->get($post_id, $this->get_access_token()) );

  }
  
  /**
   * [post to a facebook page]
   * @param  [int]   $facebook_page_id  [the id of the facebook page you want to post to]
   * @param  [array] $content           [a collection of parameters to add to the post i.e. link, message, schedule, attachments]
   * @return [int]   post id            [the id of the newly created facebook post]
   */
  public function post_post($facebook_page_id, $content) {
    
    foreach ($this->get_pages() as $page) {
      
      // here we will iterate through a collection of facebook pages whose id will be compared to 
      // another collection of ACF dropdown whose values are equal to a facebook business page id
      try {

        if ($page['id'] == $facebook_page_id) {

          $post = $this->fb->post('/' . $page['id'] . '/feed', $content, $page['access_token']);

          /**
           * [status 200 response containing the facebook post id]
           * @var [array]
           */
          
          $this->page_token = $page['access_token'];
          print_r($this->page_token);
          return $post->getGraphNode()->asArray()['id'];

        }


      } catch(Facebook\Exceptions\FacebookResponseException $e) {

          echo 'There was an Error POSTING your message. Please Contact Administrator with this Error Message: ' . $e->getMessage();
          exit;

      } catch(Facebook\Exceptions\FacebookSDKException $e) {

        // When validation fails or other local issues
        echo 'There was an Error with Facebook SDK. Please Contact Administrator with this Error Message: ' . $e->getMessage();
        exit;

      }

    }

  }

  public function update_post($facebook_page_id, $content, $fb_post_id) {

    if ($this->post_exists($fb_post_id)) {

      try {

        // Returns a `Facebook\FacebookResponse` object
        $response = $this->fb->post( $fb_post_id, $content, $this->get_access_token() );

      } catch(Facebook\Exceptions\FacebookResponseException $e) {

        echo 'Graph returned an error: ' . $e->getMessage();
        exit;

      } catch(Facebook\Exceptions\FacebookSDKException $e) {

        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;

      }
      
    } else {

      $this->post_post($facebook_page_id, $content);

    }
    
  }

  public function delete_post($fb_post_id) {
    try {
      // Returns a `Facebook\FacebookResponse` object
      $response = $this->fb->delete($fb_post_id, [], $this->page_token);

    } catch(Facebook\Exceptions\FacebookResponseException $e) {

      echo 'Graph returned an error: ' . $e->getMessage();
      exit;

    } catch(Facebook\Exceptions\FacebookSDKException $e) {

      echo 'Facebook SDK returned an error: ' . $e->getMessage();
      exit;

    }
  }

}

$app_secret = '1659aa3b796f4b5f122ad6932f0c6bd6';
$app_id     = '2008641572724248';

$fb_api = new Facebook_Api($app_id, $app_secret);

$content = [
  'link'   => 'http://railroad-injuries.com',
  'message' => 'testing from $fb_api instance'
];

echo '<pre>';
echo '<strong>connected</strong> </br>';
print_r($fb_api);
echo '<pre>';

