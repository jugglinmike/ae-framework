<?php
require_once(__DIR__ . '/../../../utils/models/BaseModel.php');
require_once(__DIR__ . '/../../AEFrameworkTestCase.php');

function defaultFunction()
{
  return 'via function';
}

class Model extends BaseModel {
  protected $_json_type = 'test json type';
  protected $_db_table = 'model_test_table';
  protected $_db_schema = array(
    'prim' => array('type' => 'int'),
    'test_attr1' => array('type' => 'int'),
    'test_attr2' => array('type' => 'int', 'default' => 45),
    'test_attr3' => array(
      'type' => 'string',
      'default' => 'via value',
      'defaultFunction' => 'defaultFunction'
    ),
    'inactive' => array('type' => 'bool')
  );
  protected $_db_primary_key = 'prim';
  protected $_attributes = array(
    'test_attr1', 'test_attr2', 'test_attr3', 'inactive'
  );
}

class BaseModelTest extends AEFrameworkTestCase
{
  protected $_table_name = 'model_test_table';
  protected $_fixture = array(
    array( 'prim' => 1, 'test_attr1' => 4, 'test_attr2' => 6 ),
    array( 'prim' => 2, 'test_attr1' => 5, 'test_attr2' => 6 ),
    array( 'prim' => 3, 'test_attr1' => 5, 'test_attr2' => 7 )
  );
  protected $_schema = <<<SQL
    DROP TABLE IF EXISTS `model_test_table`;
    CREATE TABLE `model_test_table` (
      `prim` int(11) NOT NULL AUTO_INCREMENT,
      `test_attr1` int(11) DEFAULT 0,
      `test_attr2` int(11) DEFAULT 0,
      `test_attr3` varchar(30) DEFAULT 'foo',
      `inactive` bool DEFAULT 0,
      PRIMARY KEY (`prim`)
    );
SQL;

  public function setUp(){
    $this->m = new Model();
    parent::setUp();
  }

  public function testInitFromDefaults()
  {
    $data = $this->m->get_data();

    $this->assertSame(
      null,
      $data['test_attr1'],
      'Uses `null` as default value when no value is specified'
    );
    $this->assertSame(45, $data['test_attr2']);
    $this->assertSame(
      'via function',
      $data['test_attr3'],
      'Derives from `defaultFunction` where specified (prefering over `default` if present)'
    );
  }

  public function testInitFromIdLiteral()
  {
    $r = new Model(2);

    $data = $r->get_data();

    $this->assertSame(2, $data['prim']);
    $this->assertSame(5, $data['test_attr1']);
    $this->assertSame(6, $data['test_attr2']);
    $this->assertSame('foo', $data['test_attr3']);
    $this->assertArrayNotHasKey(
      'inactive', $data, 'removes the `inactive` attribute'
    );
  }

  public function testInitFromArrayWithId()
  {
    $r = new Model(array( 'prim' => 3 ));

    $data = $r->get_data();

    $this->assertSame(3, $data['prim']);
    $this->assertSame(5, $data['test_attr1']);
    $this->assertSame(7, $data['test_attr2']);
    $this->assertSame('foo', $data['test_attr3']);
    $this->assertArrayNotHasKey(
      'inactive', $data, 'removes the `inactive` attribute'
    );
  }

  public function testInitFromNotFound()
  {
    $error = null;

    try {
      new Model(array( 'test_attr2' => 4000 ));
    } catch (Exception $e) {
      $error = $e;
    }

    $this->assertNotNull($error);
  }

  public function testInitFromUnderconstrained()
  {
    $error = null;

    try {
      new Model(array( 'test_attr2' => 4 ));
    } catch (Exception $e) {
      $error = $e;
    }

    $this->assertNotNull($error);
  }

  public function testInitFromArrayWithoutId()
  {
    $r = new Model(array( 'test_attr2' => 6, 'test_attr1' => 4 ));

    $data = $r->get_data();

    $this->assertSame(1, $data['prim']);
    $this->assertSame(4, $data['test_attr1']);
    $this->assertSame(6, $data['test_attr2']);
    $this->assertSame('foo', $data['test_attr3']);
    $this->assertArrayNotHasKey(
      'inactive', $data, 'removes the `inactive` attribute'
    );
  }

  public function testInitFromArrayWithoutInvalid()
  {
    $error = null;

    try {
      new Model(array( 'test_attr2' => 'not a number' ));
    } catch (Exception $e) {
      $error = $e;
    }

    $this->assertNotNull($error);
  }

  public function testGetUnknown()
  {
    $error = null;

    try {
      $this->m->foo;
    } catch (Exception $e) {
      $error = $e;
    }

    $this->assertNotNull($error);
  }

  public function testSetValid()
  {
    $this->m->test_attr1 = 86;

    $this->assertSame(86, $this->m->get_data()['test_attr1']);
  }

  public function testSetUnknownAttr()
  {
    $err = null;

    try {
      $this->m->foo = 23;
    } catch (Exception $e) {
      $err = $e;
    }

    $this->assertNotNull($err);
  }

  public function testSetInvalidValue()
  {
    $err = null;

    try {
      $this->m->test_attr2 = 'not a number';
    } catch (Exception $e) {
      $err = $e;
    }

    $this->assertNotNull($err);
  }

  public function testSaveCreate()
  {
    $this->m->test_attr1 = 45;
    $this->m->test_attr2 = 86;
    $this->m->inactive = 1;

    $this->m->save();

    $this->assertSame(
      $this->m->prim,
      4,
      'sets the primary key on the newly-inserted model object'
    );

    $queryTable = $this->tableFromQuery('SELECT * FROM model_test_table');

    $expectedTable = $this->tableFromArray(array(
      array(
        'prim' => 1,
        'test_attr1' => 4,
        'test_attr2' => 6,
        'test_attr3' => 'foo',
        'inactive' => 0
      ),
      array(
        'prim' => 2,
        'test_attr1' => 5,
        'test_attr2' => 6,
        'test_attr3' => 'foo',
        'inactive' => 0
      ),
      array(
        'prim' => 3,
        'test_attr1' => 5,
        'test_attr2' => 7,
        'test_attr3' => 'foo',
        'inactive' => 0
      ),
      array(
        'prim' => 4,
        'test_attr1' => 45,
        'test_attr2' => 86,
        'test_attr3' => 'via function',
        'inactive' => 1
      )
    ));
    $this->assertTablesEqual($expectedTable, $queryTable);
  }

  public function testSaveUpdateFromDB()
  {
    $m = new Model(2);
    $m->test_attr1 = 45;
    $m->test_attr2 = 86;
    $m->inactive = 1;

    $m->save();

    $queryTable = $this->tableFromQuery('SELECT * FROM model_test_table');

    $expectedTable = $this->tableFromArray(array(
      array(
        'prim' => 1,
        'test_attr1' => 4,
        'test_attr2' => 6,
        'test_attr3' => 'foo',
        'inactive' => 0
      ),
      array(
        'prim' => 2,
        'test_attr1' => 45,
        'test_attr2' => 86,
        'test_attr3' => 'foo',
        'inactive' => 1
      ),
      array(
        'prim' => 3,
        'test_attr1' => 5,
        'test_attr2' => 7,
        'test_attr3' => 'foo',
        'inactive' => 0
      )
    ));
    $this->assertTablesEqual($expectedTable, $queryTable);
  }

  public function testSoftDeleteExisting()
  {
    $m = new Model(3);
    $m->inactive = 0;

    $m->soft_delete();

    $this->assertSame(1, $m->inactive);

    $queryTable = $this->tableFromQuery('SELECT * FROM model_test_table');

    $expectedTable = $this->tableFromArray(array(
      array(
        'prim' => 1,
        'test_attr1' => 4,
        'test_attr2' => 6,
        'test_attr3' => 'foo',
        'inactive' => 0
      ),
      array(
        'prim' => 2,
        'test_attr1' => 5,
        'test_attr2' => 6,
        'test_attr3' => 'foo',
        'inactive' => 0
      ),
      array(
        'prim' => 3,
        'test_attr1' => 5,
        'test_attr2' => 7,
        'test_attr3' => 'foo',
        'inactive' => 1
      )
    ));
    $this->assertTablesEqual($expectedTable, $queryTable);
  }

  public function testDeleteNew()
  {
    $error = null;

    try {
      $this->m->delete();
    } catch (Exception $e) {
      $error = $e;
    }

    $this->assertNotNull($error);
  }

  public function testDeleteSaved()
  {
    $m = new Model(1);

    $m->delete();

    $queryTable = $this->tableFromQuery('SELECT * FROM model_test_table');

    $expectedTable = $this->tableFromArray(array(
      array(
        'prim' => 2,
        'test_attr1' => 5,
        'test_attr2' => 6,
        'test_attr3' => 'foo',
        'inactive' => 0
      ),
      array(
        'prim' => 3,
        'test_attr1' => 5,
        'test_attr2' => 7,
        'test_attr3' => 'foo',
        'inactive' => 0
      )
    ));
    $this->assertTablesEqual($expectedTable, $queryTable);
  }

  public function testToJsonNew()
  {
    $this->m->test_attr1 = 333;
    $this->m->test_attr2 = 444;
    $this->m->test_attr3 = 'mosey';

    $actual = json_decode($this->m->to_json());

    $this->assertSame($actual->test_attr1, 333);
    $this->assertSame($actual->test_attr2, 444);
    $this->assertSame($actual->test_attr3, 'mosey');
    $this->assertSame($actual->inactive, null);
  }

  public function testToJsonSaved()
  {
    $m = new Model(2);

    $actual = json_decode($m->to_json());

    $this->assertSame($actual->test_attr1, 5);
    $this->assertSame($actual->test_attr2, 6);
    $this->assertSame($actual->test_attr3, 'foo');
  }
}
