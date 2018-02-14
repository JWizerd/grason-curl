<?

/**
 * @todo  need to include the curl class and api classes 
 */

// Run the API Calls Upon Estate Sale Custom Post Type Submission
add_action( 'transition_post_status', 'post_estate_sale_to_apis', 10, 3 );

function post_estate_sale_to_apis( $new_status, $old_status, $post ) { 
  print_r($post);
  if ($old_status == 'auto-draft' && $new_status == 'publish') {

    $org = new Org('5749-0950-0d1d-4c13-9ed8-6154', '18308 Wind Valley Way', 'Pflugerville', '78660', 'TX');

    $org->set_content_type('x-www-form-urlencoded');
    $org->set_auth('basic');
    $org->post_listing();


  }
}