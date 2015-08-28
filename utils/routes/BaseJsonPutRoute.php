<?php

class BaseJsonPutRoute extends BaseJsonRoute {

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

    // now, iterate through each field - validating that it is a valid
    // attribute
    $model = $this->get_model_object();
    $primary_key = $model->get_db_primary_key();
    foreach ($data as $field => $value) {
      // make sure the field exists
      if ($field !== $primary_key && !$model->is_valid_attribute($field)) {
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

  public function update_model($criteria) {
    $criteria = $this->underscoreKeys($criteria);

    // ensure that all of the fields actually exist
    $this->_validate_data($criteria);

    // next, since we know all of the fields actually exist, sanitize the
    // data
    $criteria = $this->_sanitize_data($criteria);

    // finally, create this new model
    $class_name = $this->get_model_class();
    $primary_key = $this->get_model_object()->get_db_primary_key();

    try {
      // retrieve the existing model
      $model = new $class_name(array($primary_key => $criteria[$primary_key]));
    } catch(Exception $e) {
      $message = $e->getMessage();
      if (strpos($message, 'results in no database records')) {
        $error = array(
          'code' => 'NOT_FOUND',
          'detail' => $message
        );
        $res = JsonResHandler::render(array($error), 404);
      }

      trigger_error($message, E_USER_ERROR);
      return;
    }

    // iterate through all of the posted data, setting it on the model object
    foreach ($criteria as $property => $value) {
      if ($property === $primary_key) {
        continue;
      }

      $model->$property = $value;
    }

    // save this new object and render the result
    $model->save();
    $res = JsonResHandler::render($this->dashKeys($model->get_user_friendly_data()), 200);
  }
}
