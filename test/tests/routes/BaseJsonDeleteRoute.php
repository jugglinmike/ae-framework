<?php
require_once(__DIR__ . '/../../../src/router.php');
require_once(__DIR__ . '/../../AEFrameworkTestCase.php');
require_once(__DIR__ . '/../../TestModel.php');

class JsonDeleteRoute extends BaseJsonDeleteRoute {
  public function __construct($router)
  {
    $this->_model_object = new TestModel();
    Parent::__construct($router);
  }
}

class BaseJsonDeleteRouteTest extends AEFrameworkTestCase
{
  protected $_table_name = 'test_table';
  protected $_fixture = array(
    array( 'prim' => 1, 'test_attr1' => 4, 'test_attr2' => 6 ),
    array( 'prim' => 2, 'test_attr1' => 5, 'test_attr2' => 6 ),
    array( 'prim' => 3, 'test_attr1' => 5, 'test_attr2' => 7 )
  );
  protected $_schema = <<<SQL
    DROP TABLE IF EXISTS `test_table`;
    CREATE TABLE `test_table` (
      `prim` int(11) NOT NULL,
      `test_attr1` int(11) DEFAULT 0,
      `test_attr2` int(11) DEFAULT 0,
      `inactive` bool DEFAULT 0
    );
SQL;

  protected function setUp()
  {
    $this->router = new Router();
    $this->r = new JsonDeleteRoute($this->router);
    parent::setUp();
  }

  public function testDeleteById()
  {
    $result = $this->captureResponse(function($self) {
      $self->r->delete(array('prim' => 2));
    });

    $queryTable = $this->tableFromQuery('SELECT prim, inactive FROM test_table');

    $expectedTable = $this->tableFromArray(array(
      array( 'prim' => 1, 'inactive' => 0 ),
      array( 'prim' => 2, 'inactive' => 1 ),
      array( 'prim' => 3, 'inactive' => 0 )
    ));
    $this->assertTablesEqual($expectedTable, $queryTable);

    $this->assertEquals(null, $result['exception']);
    $this->assertEquals(204, http_response_code());
  }

  public function testDeleteByIdLiteral()
  {
    $r = $this->r;
    $result = $this->captureResponse(function() use ($r) {
      $r->delete(1);
    });

    $queryTable = $this->tableFromQuery('SELECT prim, inactive FROM test_table');

    $expectedTable = $this->tableFromArray(array(
      array( 'prim' => 1, 'inactive' => 1 ),
      array( 'prim' => 2, 'inactive' => 0 ),
      array( 'prim' => 3, 'inactive' => 0 )
    ));
    $this->assertTablesEqual($expectedTable, $queryTable);

    $this->assertEquals(null, $result['exception']);
    $this->assertEquals(204, http_response_code());
  }

  public function testDeleteByAttr()
  {
    $result = $this->captureResponse(function($self) {
      $self->r->delete(array('test_attr1' => 4));
    });

    $queryTable = $this->tableFromQuery('SELECT prim, inactive FROM test_table');

    $expectedTable = $this->tableFromArray(array(
      array( 'prim' => 1, 'inactive' => 1 ),
      array( 'prim' => 2, 'inactive' => 0 ),
      array( 'prim' => 3, 'inactive' => 0 )
    ));
    $this->assertTablesEqual($expectedTable, $queryTable);

    $this->assertEquals(null, $result['exception']);
    $this->assertEquals(204, http_response_code());
  }

  public function testDeleteNotFound()
  {
    $result = $this->captureResponse(function($self) {
      $self->r->delete(432);
    });

    $queryTable = $this->tableFromQuery('SELECT prim, inactive FROM test_table');

    $expectedTable = $this->tableFromArray(array(
      array( 'prim' => 1, 'inactive' => 0 ),
      array( 'prim' => 2, 'inactive' => 0 ),
      array( 'prim' => 3, 'inactive' => 0 )
    ));
    $this->assertTablesEqual($expectedTable, $queryTable);

    $this->assertNull($result['exception']);
    $this->assertNotEquals(null, $result['decoded']);
    $this->assertCount(1, $result['decoded']);
    $this->assertEquals('NOT_FOUND', $result['decoded'][0]->code);
    $this->assertEquals(404, http_response_code());
  }

  public function testDeleteMultiple()
  {
    $result = $this->captureResponse(function($self) {
      $self->r->delete(array( 'test_attr1' => 5 ));
    });

    $queryTable = $this->tableFromQuery('SELECT prim, inactive FROM test_table');

    $expectedTable = $this->tableFromArray(array(
      array( 'prim' => 1, 'inactive' => 0 ),
      array( 'prim' => 2, 'inactive' => 0 ),
      array( 'prim' => 3, 'inactive' => 0 )
    ));
    $this->assertTablesEqual($expectedTable, $queryTable);

    $this->assertNotEquals(null, $result['exception']);
    $this->assertEquals(404, http_response_code());
  }

  public function testDeleteByMultipleAttrs()
  {
    $result = $this->captureResponse(function($self) {
      $self->r->delete(array('test_attr1' => 5, 'test_attr2' => 6));
    });

    $queryTable = $this->tableFromQuery('SELECT prim, inactive FROM test_table');

    $expectedTable = $this->tableFromArray(array(
      array( 'prim' => 1, 'inactive' => 0 ),
      array( 'prim' => 2, 'inactive' => 1 ),
      array( 'prim' => 3, 'inactive' => 0 )
    ));
    $this->assertTablesEqual($expectedTable, $queryTable);

    $this->assertEquals(null, $result['exception']);
    $this->assertEquals(204, http_response_code());
  }

  public function testDeleteByInvalidAttr()
  {
    $result = $this->captureResponse(function($self) {
      $self->r->delete(array( 'test_attr_valid' => 5 ));
    });

    $queryTable = $this->tableFromQuery('SELECT prim, inactive FROM test_table');

    $expectedTable = $this->tableFromArray(array(
      array( 'prim' => 1, 'inactive' => 0 ),
      array( 'prim' => 2, 'inactive' => 0 ),
      array( 'prim' => 3, 'inactive' => 0 )
    ));
    $this->assertTablesEqual($expectedTable, $queryTable);

    $this->assertNotEquals(null, $result['exception']);
    $this->assertEquals(400, http_response_code());
  }
}
