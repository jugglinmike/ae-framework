<?php
require_once(__DIR__ . '/../../../utils/models/BaseModel.php');
require_once(__DIR__ . '/../../AEFrameworkTestCase.php');

function defaultFunction()
{
  return 'via function';
}

class Model extends BaseModel {
  protected $_json_type = 'test json type';
  protected $_db_table = 'test_table';
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
  protected $_attributes = array('test_attr1', 'test_attr2', 'test_attr3', 'inactive');
}

class BaseModelTest extends AEFrameworkTestCase
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
      `test_attr3` string DEFAULT 'foo',
      `inactive` bool DEFAULT 0
    );
SQL;

  public function setUp(){
    $this->m = new Model();
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
      'Derives from `defaultFunction` where specified (prefering over ' .
        '`default` if present)'
    );
  }

  public function testInitFromIdLiteral()
  {
   $r = new Model(2);

   $data = $r->get_data();

   print_r($data);

   $this->assertSame(2, $data['prim']);
   $this->assertSame(5, $data['test_attr1']);
   $this->assertSame(6, $data['test_attr2']);
   #$this->assertSame('foo', $data['test_attr3']);
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
}
