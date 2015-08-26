<?php
define('DB_HOST', '127.0.0.1');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_DATABASE', 'api');

/**
 * Concrete subclasses must define the following attributes:
 *
 * - _schema
 * - _table_name
 * - _fixture
 */
abstract class AEFrameworkTestCase extends PHPUnit_Extensions_Database_TestCase
{
  static private $pdo = null;
  private $conn = null;

  public static function setupBeforeClass()
  {
    $tmppdo = new PDO('mysql:host=' . DB_HOST, DB_USERNAME, DB_PASSWORD);
    $tmppdo->exec('CREATE DATABASE IF NOT EXISTS ' + DB_DATABASE);
    self::$pdo = new PDO(
      'mysql:host=' . DB_HOST . ';dbname=' . DB_DATABASE, DB_USERNAME,
       DB_PASSWORD);
  }

  protected function setUp()
  {
    self::$pdo->exec($this->_schema);
    parent::setUp();
  }

  public static function tearDownAfterClass()
  {
    self::$pdo = NULL;
  }

  public function getConnection()
  {
    if ($this->conn === null) {
      $this->conn = $this->createDefaultDBConnection(self::$pdo, DB_DATABASE);
    }
    return $this->conn;
  }

  public function getDataSet()
  {
    $dataset = array(
      $this->_table_name => $this->_fixture
    );

    return $this->createArrayDataSet($dataset);
  }

  public function tableFromQuery($query)
  {
    return $this->getConnection()->createQueryTable(
      $this->_table_name, $query
    );
  }

  public function tableFromArray($array)
  {
    return $this->createArrayDataSet([ $this->_table_name => $array])
      ->getTable($this->_table_name);
  }
}
