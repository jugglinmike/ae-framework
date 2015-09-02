<?php

class BaseJsonRoute extends BaseRoute {

  /**
   * These whitelisted fields are found in the notes here:
   * http://jsonapi.org/format/#errors
   *
   * @var array
   */

  protected $_model_class;
  protected $_model;

  public function __construct(Router $router) {
    Parent::__construct($router);
    $this->_validate_route();
    $this->_model = new $this->_model_class();
  }

  protected function _validate_route() {
    // ensure that the model object was set correctly
    if (!isset($this->_model_class)) {
      trigger_error('Please set the `_model_class` on this route before using it', E_USER_ERROR);
    } else if (class_exists($this->_model_class) === false) {
      trigger_error('Invalid `_model_class`; class not defined', E_USER_ERROR);
    } else if (!is_subclass_of($this->_model_class, 'BaseModel')) {
      trigger_error('Invalid `_model_class`; Must be a subclass of `BaseModel`', E_USER_ERROR);
    }
  }

  public function get_model_object() {
    return $this->_model;
  }

  public function get_model_class() {
    return $this->_model_class;
  }

  public function createModel() {
    return new $this->_model_class(...func_get_args());
  }

  public function sanitize_model_identifer($identifier = null) {
    $model = $this->_model;

    // if this was not an array of search params, assume it was the primary
    // identifier for this table and convert it to an array
    if (!is_array($identifier)) {
      $primary_key = $model->get_db_primary_key();
      $identifier = array($primary_key => $identifier);
    }

    // now sanitize each value in the array
    foreach ($identifier as $field => $value) {
      // ensure this is a valid db field
      if (!$model->is_valid_db_field($field)) {
        $error = array(
          'code' => 'INVALID_MODEL_IDENTIFIER_FIELD',
          'detail' => 'An invalid model identifier was specified; "' .
            $field . '" does not exist within the specified database schema'
        );
        $res = JsonResHandler::render(array($error), 400);
      }

      $identifier[$field] = $model->sanitize_db_field_value($field, $value);
    }

    return $identifier;
  }

  public function dashKeys($array, $arrayHolder = array()) {
    $dashArray = !empty($arrayHolder) ? $arrayHolder : array();
    foreach ($array as $key => $val) {
      $newKey = str_replace('_', '-', $key);
      if (!is_array($val)) {
        $dashArray[$newKey] = $val;
      } else {
        $dashArray[$newKey] = $this->dashKeys($val);
      }
    }

    return $dashArray;
  }

  public function underscoreKeys($array, $arrayHolder = array()) {
    $underscoreArray = !empty($arrayHolder) ? $arrayHolder : array();
    foreach ($array as $key => $val) {
      $newKey = str_replace('-', '_', $key);
      if (!is_array($val)) {
        $underscoreArray[$newKey] = $val;
      } else {
        $underscoreArray[$newKey] = $this->underscoreKeys($val);
      }
    }

    return $underscoreArray;
  }
}
