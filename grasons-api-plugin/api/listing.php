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
class Listing 
{
  protected $db;

  public function __construct() 
  {
    $this->db = new DB();
  }

  public function __destruct()  
  {
    unset($this->db);
  }

  /**
   * self initialize a new object and open a database connection
   * @return [type] [description]
   */
  protected static function create_self() 
  {
    return (new self());
  }

  /**
   * retrieve listing id related to post
   * @param  [int] $post_id
   * @return [id] [the listing id taken from api]
   */
  public static function get($post_id) 
  {
    $self = self::create_self();
    $stmt = $self->db->pdo->prepare("SELECT * FROM listings WHERE post_id = ?"); 
    $stmt->execute([$post_id]);
    $listing = $stmt->fetch();
    return $listing;
  }

  public static function delete($post_id) 
  {
  
    $self = self::create_self();
    $stmt = $self->db->pdo->prepare("DELETE FROM listings WHERE post_id = ?");
    $stmt->execute([$post_id]);

  }

  public static function update($post_id, $com_id, $com_sale_id = null, $net_id, $org_id) 
  {

    $self = self::create_self();
    $stmt = $self->db->pdo->prepare(
                                    "UPDATE listings 
                                      SET 
                                        com_id      = ?,
                                        com_sale_id = ?,
                                        net_id      = ?,
                                        org_id      = ?
                                      WHERE  
                                        post_id = ?"
                                    );
    $stmt->execute([$com_id, $com_sale_id, $net_id, $org_id, $post_id]);

  }

  /**
   * Store Listing in DB for DELETION and UPDATE Methods
   * @param  [int]    $post_id 
   * @param  [int]    $listing_id [listing id generated from api to estatesales.org]
   */
  public static function save($post_id, $com_id = null, $com_sale_id = null, $net_id = null, $org_id = null) 
  {

    $self = self::create_self();

    if (empty(self::get($post_id))) {

      $stmt = $self->db->pdo->prepare(
                "INSERT INTO listings 
                  (post_id, com_id, com_sale_id, net_id, org_id) 
                    VALUES (?, ?, ?, ?, ?)"
              );

      $stmt->execute([$post_id, $com_id, $com_sale_id, $net_id, $org_id]);

    } else {

      self::update($post_id, $com_id, $com_sale_id, $net_id, $org_id);

    }

  }

  /**
   * permanently delete images related to a post from the application
   * @param  [int] $post_id
   * @param  [array] $gallery [a collection of gallery images from estate sales ACF]
   */
  public static function delete_images($gallery, $post_id) 
  {

    if (!empty($gallery)) {

      foreach ($gallery as $row) {
        wp_delete_attachment( $row['gallery_image']['id'], true );
      }

    }

  }
}