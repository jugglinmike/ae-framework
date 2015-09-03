<?php
require_once(__DIR__ . '/../../../utils/routes/BaseJsonPostRoute.php');
require_once(__DIR__ . '/../../AEFrameworkTestCase.php');
require_once(__DIR__ . '/../../TestModel.php');

class JsonGetOneRoute extends BaseJsonGetOneRoute {
  public function __construct()
  {
    $this->_model_object = new TestModel();
  }
}

class BaseJsonGetOneRouteTest extends AEFrameworkTestCase
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
      `prim` int(11) NOT NULL AUTO_INCREMENT,
      `test_attr1` int(11) NOT NULL,
      `test_attr2` int(11) NOT NULL,
      `inactive` boolean DEFAULT 0,
      PRIMARY KEY (`prim`)
    );
SQL;

  protected function setUp()
  {
    $this->r = new JsonGetOneRoute();
    parent::setUp();
  }

  public function testGetById()
  {
    $result = $this->captureResponse(function($self) {
      $self->r->render(array( 'prim' => 3 ));
    });

    $this->assertNull($result['exception']);

    $this->assertEquals(3, $result['decoded']->prim);

    $this->assertEquals(
      5,
      $result['decoded']->{'test-attr1'},
      'replaces underscore characters with dashes in reported field'
    );
    $this->assertObjectNotHasAttribute(
      'test_attr1',
      $result['decoded'],
      'does not report field using underscore characters'
    );

    $this->assertEquals(
      7,
      $result['decoded']->{'test-attr2'},
      'replaces underscore characters with dashes in reported field'
    );
    $this->assertObjectNotHasAttribute(
      'test_attr2',
      $result['decoded'],
      'does not report field using underscore characters'
    );

    $this->assertObjectNotHasAttribute(
      'inactive', $result['decoded'], 'does not report value of "inactive" field'
    );

    $this->assertEquals(200, http_response_code());
  }

  public function testGetNotFound()
  {
    $result = $this->captureResponse(function($self) {
      $self->r->render(array( 'prim' => 999 ));
    });

    $this->assertNull($result['exception']);

    $this->assertCount(1, $result['decoded']);

    $this->assertSame('NOT_FOUND', $result['decoded'][0]->code);
    $this->assertObjectHasAttribute('detail', $result['decoded'][0]);

    $this->assertEquals(404, http_response_code());
  }

  public function testGetIncludeUnsupported()
  {
    $this->r->set_request(array( 'query_params' => array('include' => 'foo') ));
    $result = $this->captureResponse(function($self) {
      $self->r->render(array( 'prim' => 1 ));
    });

    $this->assertNotNull($result['exception']);
  }

  public function testGetByIdLiteral()
  {
    $result = $this->captureResponse(function($self) {
      $self->r->render(2);
    });

    $this->assertNull($result['exception']);

    $this->assertEquals(2, $result['decoded']->prim);

    $this->assertEquals(
      5,
      $result['decoded']->{'test-attr1'},
      'replaces underscore characters with dashes in reported field'
    );
    $this->assertObjectNotHasAttribute(
      'test_attr1',
      $result['decoded'],
      'does not report field using underscore characters'
    );

    $this->assertEquals(
      6,
      $result['decoded']->{'test-attr2'},
      'replaces underscore characters with dashes in reported field'
    );
    $this->assertObjectNotHasAttribute(
      'test_attr2',
      $result['decoded'],
      'does not report field using underscore characters'
    );

    $this->assertObjectNotHasAttribute(
      'inactive', $result['decoded'], 'does not report value of "inactive" field'
    );

    $this->assertEquals(200, http_response_code());
  }

  public function testGetByAttr()
  {
    $result = $this->captureResponse(function($self) {
      $self->r->render(array( 'test_attr1' => 5, 'test_attr2' => 6 ));
    });

    $this->assertNull($result['exception']);

    $this->assertEquals(2, $result['decoded']->prim);

    $this->assertEquals(
      5,
      $result['decoded']->{'test-attr1'},
      'replaces underscore characters with dashes in reported field'
    );
    $this->assertObjectNotHasAttribute(
      'test_attr1',
      $result['decoded'],
      'does not report field using underscore characters'
    );

    $this->assertEquals(
      6,
      $result['decoded']->{'test-attr2'},
      'replaces underscore characters with dashes in reported field'
    );
    $this->assertObjectNotHasAttribute(
      'test_attr2',
      $result['decoded'],
      'does not report field using underscore characters'
    );

    $this->assertObjectNotHasAttribute(
      'inactive', $result['decoded'], 'does not report value of "inactive" field'
    );

    $this->assertEquals(200, http_response_code());
  }
}
