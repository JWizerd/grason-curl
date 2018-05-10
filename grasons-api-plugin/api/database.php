<?php 

class DB 
{
  protected $host    = 'localhost';
  protected $db      = 'grasonsc_wp';
  protected $user    = 'grasonsc_admin';
  protected $pass    = 'OOFTd[P133Dh';
  protected $charset = 'utf8mb4';
  public $pdo;

  /**
   * @param run open connection method checks db connection provides error reporting and 
   */
  public function __construct() {
    $this->open_connection();
  }  

  public function get_pdo_obj($pdo) {
    return $this->pdo;
  }

  public function open_connection() {
    $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
      $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
    }
    catch(Exception $e) {
      echo $e->getMessage();
    }
  }

}