<?php
require_once('./AEFrameworkTestCase.php');

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

class BaseJsonPutRouteTest extends AEFrameworkTestCase
{
  protected $_table_name = 'test_table';
  protected $_fixture = [
    [ 'prim' => 13, 'test_attr' => 123 ],
    [ 'prim' => 33, 'test_attr' => 456 ]
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
    parent::setUp();
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

    $queryTable = $this->tableFromQuery('SELECT * FROM test_table');

    $expectedTable = $this->tableFromArray([
        ['prim' => 13, 'test_attr' => 123],
        ['prim' => 33, 'test_attr' => 456],
        ['prim' => 0, 'test_attr' => 23]
      ]);
    $this->assertTablesEqual($expectedTable, $queryTable);

    $this->assertEquals($data->{'test-attr'}, 23);
  }
}
