<?php 

/**
 * class Listing is a database abstraction layer / Service for handling 
 * db operations for all listings communicating with our application. 
 * I thought this would be a good design so that db operations were 
 * abstracted from specific classes but the class represented a listing model 
 * rather than the DB set up itself. Static methods were used because I don't 
 * want instances created and causing unnecessary code bloat at this point in time
 * since there aren't properties that need to be set for this class to fulfill it's
 * purpose.
 */
class Listing {
  /**
   * retrieve listing id related to post
   * @param  [int] $post_id
   * @return [id] [the listing id taken from api]
   */
  
  public static function get($post_id) {
    $db = new DB();
    $stmt = $db->pdo->prepare("SELECT listing_id, user_key FROM listings WHERE post_id = ?"); 
    $stmt->execute([$post_id]);
    $listing = $stmt->fetch();
    // close connection
    $db = null;
    $stmt = null;
    return $listing;
  }

  public static function delete($post_id) {
  
    $db = new DB();
    $stmt = $db->pdo->prepare("DELETE FROM listings WHERE post_id = ?");
    $stmt->execute([$post_id]);
    // close connection
    $db = null;
    $stmt = null;

  }

  public static function update($post_id, $listing_id, $account) {
    $db = new DB();
    $stmt = $db->pdo->prepare("UPDATE listings SET listing_id = ?, user_key = ? WHERE  post_id = ?");
    $stmt->execute([$listing_id, $account, $post_id]);

    // close connection
    $db = null;
    $stmt = null; 
  }

  /**
   * Store Listing in DB for DELETION and UPDATE Methods
   * @param  [int]    $post_id 
   * @param  [int]    $listing_id [listing id generated from api to estatesales.org]
   * @param  [string] $account [the user_key taken from $account acf]
   */
  public static function save($post_id, $listing_id, $account) {
    $db = new DB();
    $stmt = $db->pdo->prepare("INSERT INTO listings (post_id, listing_id, user_key) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $listing_id, $account]);

    // close connection
    $db = null;
    $stmt = null; 
  }
}