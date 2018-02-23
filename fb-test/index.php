<?
require_once __DIR__ . '/Facebook/autoload.php'; // change path as needed

$app_secret = '1659aa3b796f4b5f122ad6932f0c6bd6';
$app_id     = '2008641572724248';
/**
 * @var string [a never expriring access token]
 * @link(https://developers.facebook.com/tools/explorer, generate never expriring access token)
 * @link(https://developers.facebook.com/docs/marketing-api/access, in article see section 'Exchange for Long-Lived Access Token')
 */
$api_key    = 'EAAci2U9sXhgBAL9hDM8xqkWKXjm1KKzfJAtetOIGVIsZALc90DhTE8LemZBVtZCqCXvyYkGACrQ0b0HBVOgv1meS9i6ignVtt1ZAERZCD3eJn2wNqkQNw1gPiHxhsXW4ZCeSCGe0m3sNAShZCu5DZCq1CZBbTFZAHGCBQZD';

$fb = new \Facebook\Facebook([
  'app_id' => $app_id,
  'app_secret' => $app_secret,
  'default_graph_version' => 'v2.12',
  'default_access_token' => $api_key
]);

try {

  $pages = $fb->get('/me/accounts');
  $pages = $pages->getGraphEdge()->asArray();

} catch(Facebook\Exceptions\FacebookResponseException $e) {

  // When Graph returns an error
  echo 'Error Retrieving Pages from Facebook. Please Contact Administrator with this Error Message: ' . $e->getMessage();
  exit;

} catch(Facebook\Exceptions\FacebookSDKException $e) {

  // When validation fails or other local issues
  echo 'There was an Error with Facebook SDK. Please Contact Administrator with this Error Message: ' . $e->getMessage();
  exit;

}

foreach ($pages as $key) {
  
  // here we will iterate through a collection of facebook pages whose id will be compared to 
  // another collection of ACF dropdown whose values are equal to a facebook business page id
  if ($key['id'] == 382465355264738) {
    $post = $fb->post('/' . $key['id'] . '/feed', array('message' => 'just for testing...'), $key['access_token']);
    $post = $post->getGraphNode()->asArray();
    // store post id for updating and deleting posts upon wp post update and delete
    print_r($post);
  }

}