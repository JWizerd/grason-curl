<?php 

class Account 
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

    public static function get($user_role) 
    {
      $self = self::create_self();
      $stmt = $self->db->pdo->prepare("SELECT * FROM accounts WHERE user_role = ?"); 
      $stmt->execute([$user_role]);
      $listing = $stmt->fetch();
      return $listing;
    }

    public static function update($post) 
    {

      $self = self::create_self();
      $stmt = $self->db->pdo->prepare(
                                      "UPDATE accounts
                                        SET 
                                          org = ?,
                                          com = ?,
                                          net = ?,
                                          facebook = ?,
                                          hootsuite = ?
                                        WHERE  
                                          user_role = ?"
                                      );
      $stmt->execute(
        [
            trim(htmlspecialchars($post['org'])), 
            trim(htmlspecialchars($post['com'])), 
            trim(htmlspecialchars($post['net'])), 
            trim(htmlspecialchars($post['facebook'])), 
            trim(htmlspecialchars($post['hootsuite'])), 
            trim(htmlspecialchars($post['user_role']))
        ]
    );

    }

    public static function save($post) 
    {

      $self = self::create_self();

      if (empty(self::get($post['user_role']))) {

        $stmt = $self->db->pdo->prepare(
                  "INSERT INTO accounts 
                    (org, net, com, facebook, hootsuite, user_role) 
                      VALUES (?, ?, ?, ?, ?, ?)"
                );

        $stmt->execute(
            [
                trim(htmlspecialchars($post['org'])), 
                trim(htmlspecialchars($post['net'])), 
                trim(htmlspecialchars($post['com'])), 
                trim(htmlspecialchars($post['facebook'])), 
                trim(htmlspecialchars($post['hootsuite'])), 
                trim(htmlspecialchars($post['user_role']))
            ]
        );

      } else {

        self::update($post);

      }

    }
}