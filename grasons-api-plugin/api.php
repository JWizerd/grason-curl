<?php  
/**
 * @author 05.03.2018 Jeremiah Wodke <jeremiah.wodke@madwiremedia.com> 
 * API Integration Pipeline
 * All requests to the "Post to External Websites" action are handled here. 
 * Can also be thought of as a Controller since user interface actions are handled here.
 */

require_once('api/database.php');
require_once('api/listing.php');
require_once('api/base_api.php');
require_once('api/org.php');
require_once('api/com.php');
require_once('api/net.php');
require_once('external_websites/account.php');

// Run the API Calls Upon Estate Sale Custom Post Type CREATION OR DELETION
add_action( 'transition_post_status', 'post_estate_sale_to_apis', 10, 3 );

function post_estate_sale_to_apis( $new_status, $old_status, $post ) { 
  
  $details = [];
  $user_role               = get_userdata(get_current_user_id())->roles[0];  
  $user                    = Account::get($user_role);
  $id                      = $post->ID;
  $fields                  = $_POST['acf'];  
  $details['org_id']       = $user['org'];
  $details['net_id']       = $user['net'];
  $details['com_id']       = $user['com'];
  $details['facebook_id']  = $user['facebook'];
  $details['hootsuite_id'] = $user['hootsuite'];
  $details['post_id']      = $id;
  $details['title']        = get_the_title($id);
  $details['address']      = get_field('estate_sale_address');
  $details['account']      = get_field('estate_sale_account'); 
  $details['city']         = get_field('estate_sale_address_city');
  $details['state']        = get_field('estate_sale_address_state_code');
  $details['zip']          = get_field('estate_sale_address_postal_code');
  $details['timezone']     = get_field('estate_sale_timezone');
  $details['description']  = get_field('estate_sale_short_description');
  $details['url']          = get_the_permalink($id);
  $dates                   = get_field('estate_sale_sale_dates');
  $images                  = get_field('estate_sale_photo_gallery');

  if ($post->post_type == 'estatesales') {

    if ($old_status == 'publish') {

      /**
       * External Estate Sales APIs
       */
      $org = new Org($details, $images, $dates);
      $net = new Net($details, $images, $dates);
      $com = new Com($details, $images, $dates);

      if (isset($_POST['post_to_external'])) {

        $org->create_sale();
        $com->create_sale();
        $net->create_sale();

        /**
         * You may notice the extra space in ListingId. 
         * In the response the API formatted the property that way.
         * If remove the space 
         *
         * You may also notice that all props are being set to null if they are empty
         * this is because some users do not have all of these accounts but we don't want to throw
         * a database error or PHP to throw an error if it can't access the id from the obj instance so  
         */
        $comListingId       = empty($com->ids['listingId ']) ? null : $com->ids['listingId '];
        $comEstateListingId = empty($com->ids['EstateSaleListingId']) ? null : $com->ids['EstateSaleListingId'];
        $netId              = empty($net->id) ? null : $net->id;
        $orgId              = empty($org->id) ? null : $org->id;

        Listing::save(
          $id, 
          $comListingId, 
          $comEstateListingId, 
          $netId, 
          $orgId
        );

      }

      if ($new_status == 'trash') {

        $listing_ids = Listing::get($id);
        $org->hide_listing($listing_ids['org_id']);
        $net->delete_sale($listing_ids['net_id']);
        $com->delete_sale($listing_ids['com_id'], $listing_ids['com_sale_id']);
        Listing::delete($id);
        Listing::delete_images(get_field('estate_sale_gallery', $id), $id);
            
      }

    }

  }

}