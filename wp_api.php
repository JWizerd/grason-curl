<?php  
/**
 * @author 02.09.2018 Jeremiah Wodke <[<jeremiah.wodke@madwiremedia.com>]> 
 */

require_once('api/database.php');
require_once('api/listing.php');
require_once('api/curl_handler.php');
require_once('api/org.php');

// Run the API Calls Upon Estate Sale Custom Post Type CREATION OR DELETION
add_action( 'transition_post_status', 'post_estate_sale_to_apis', 10, 3 );

function post_estate_sale_to_apis( $new_status, $old_status, $post ) { 

  $id       = $post->ID;
  $fields   = $_POST['acf'];  
  $title    = get_the_title($post->ID);
  $address  = $fields['field_5a69f982b7d3a'];
  $account  = $fields['field_5a83313334dc9'];
  $city     = $fields['field_5a69f998b7d3b'];
  $state    = $fields['field_5a8332d3fb503'];
  $zipcode  = $fields['field_5a833310fb504'];
  $timezone = $fields['field_5a833d1c1c319'];
  $descr    = $fields['field_5a8330f0e668a'];
  $dates    = $fields['field_5a69f9e9b7d3c'];
  $images   = $fields['field_5a69fa731c0b6'];

  $org = new Org($account, $address, $city, $zipcode, $state);
  $org->set_content_type('x-www-form-urlencoded');
  $org->set_auth('basic');

  if ($post->post_type == 'estatesales') {

    if (($old_status == 'draft' || $old_status == 'auto-draft') && $new_status == 'publish') {
      
      $params = [
        'descr' => $descr, 
        'title' => $title,
        'timezone' => $timezone, 
        'dates' => $org->format_dates($dates)
      ];

      $org->post_listing($params);

      if (!empty($images)) {
        $org->post_images($images);  
      }
      
      Listing::save($id, $org->get_listing_id(), $account);
      
    } elseif ($old_status == 'publish' &&  $new_status == 'trash') {


      $org->hide_listing($id);
      Listing::delete($id);
      Listing::delete_images(get_field('estate_sale_gallery', $post->ID), $post->ID);

      
    } elseif ($old_status == 'publish' &&  $new_status == 'publish') {

      $org->hide_listing($id);

      $params = [
        'descr' => $descr, 
        'title' => $title,
        'timezone' => $timezone, 
        'dates' => $org->format_dates($dates)
      ];

      $org->post_listing($params);

      if (!empty($images)) {
        $org->post_images($images);
      }

      Listing::update($id, $org->get_listing_id(), $account);

    }
    
  }

}