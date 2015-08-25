<?php

class BaseJsonPostRoute extends BaseJsonRoute {

  protected function _sanitize_field(&$value, $name = '') {
    $value = $this->get_model_object()->sanitize_db_field_value($name, $value);
  }

  protected function _validate_data($data = array()) {
    // ensure we're dealing with an array
    if (!is_array($data)) {
      $error = array(
        'code' => 'NO_POSTED_DATA',
        'description' => 'There was a problem receiving the posted data'
      );
      $res = JsonResHandler::render(array($error), 400);
    }

    // now, iterate through each field - validating that it is a valid attribute
    // that can be modified
    $model = $this->get_model_object();
    foreach ($data as $field => $value) {
      // make sure the field exists
      if (!$model->is_valid_attribute($field)) {
        $error = array(
          'code' => 'INVALID_FIELD',
          'detail' => 'This endpoint does not support the "' .
            $field . '" field'
        );
        $res = JsonResHandler::render(array($error), 400);
      }
    }
  }

  protected function _sanitize_data($data = array()) {
    array_walk($data, array($this, '_sanitize_field'));
    return $data;
  }

  public function create_new_model($data = array()) {
    $data = $this->underscoreKeys($data);

    // ensure that all of the fields actually exist
    $this->_validate_data($data);

    // next, since we know all of the fields actually exist, sanitize the
    // data
    $data = $this->_sanitize_data($data);

    // finally, create this new model
    $class_name = $this->get_model_class();
    $model = new $class_name();

    // iterate through all of the posted data, setting it on the model object
    foreach ($data as $property => $value) {
      $model->$property = $value;
    }

    // save this new object and render the result
    $model->save();
    $res = JsonResHandler::render($this->dashKeys($model->get_user_friendly_data()), 200);
  }

}
