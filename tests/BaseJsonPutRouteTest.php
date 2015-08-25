<?php

define('DB_HOST', '127.0.0.1');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_DATABASE', 'api');

class TestModel extends BaseModel {
  protected $_json_type = 'test json type';
  protected $_db_table = 'test_table';
  protected $_db_schema = ['prim' => ['type' => 'int'], 'test_attr' => ['type' => 'int']];
  protected $_db_primary_key = 'prim';
  protected $_attributes = ['test_attr'];
}

class JsonPutRoute extends BaseJsonPutRoute {
  public function __construct($router)
  {
    $this->_model_object = new TestModel();
    Parent::__construct($router);
  }
}

class BaseJsonPutRouteTest extends PHPUnit_Extensions_Database_TestCase
{
  static private $pdo = null;
  private $conn = null;

  protected $_table_name = 'test_table';
  protected $_fixture = [
    'prim' => 33,
    'test_attr' => 123
  ];
  protected $_schema = <<<SQL
    DROP TABLE IF EXISTS `test_table`;
    CREATE TABLE `test_table` (
      `prim` int(11) NOT NULL,
      `test_attr` int(11) NOT NULL
    );
SQL;

  protected function setUp()
  {
    $this->router = new Router();
    $this->r = new JsonPutRoute($this->router);
  }

  public function getConnection()
  {
    if ($this->conn === null) {
      if (self::$pdo === null) {
        self::$pdo = new PDO(
          'mysql:host=' . DB_HOST . ';dbname=' . DB_DATABASE, DB_USERNAME,
           DB_PASSWORD);
        self::$pdo->exec($this->_schema);
       }
       $this->conn = $this->createDefaultDBConnection(self::$pdo, DB_DATABASE);
    }
    return $this->conn;
  }

  public function getDataSet()
  {
    $dataset = array(
      $this->_table_name => array(
        $this->_fixture
      )
    );

    return $this->createArrayDataSet($dataset);
  }

  public function wrap($fn)
  {
    ob_start();
    $fn();
    $output = json_decode(ob_get_contents());
    ob_end_clean();
    return $output;
  }

  public function testFoo()
  {
    $r = $this->r;
    $data = $this->wrap(function() use ($r) {
      $r->update_model(['test_attr' => 23]);
    });

    $this->assertEquals($data->{'test-attr'}, 23);
  }
}
