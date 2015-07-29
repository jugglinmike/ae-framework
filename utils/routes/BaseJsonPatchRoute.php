<?php

class BaseJsonPatchRoute extends BaseJsonRoute {

  public function patch($identifier = 0, $base_url = '', $operations = array()) {
    $identifier = $this->sanitize_model_identifer($identifier);

    // make sure we can get the model
    $model = $this->_get_model($identifier);

    // validate all of the operations that were specified
    $this->_validate_operations($base_url, $operations);

    // now that we know all operations are valid, sanitize the values of each
    $operations = $this->_sanitize_operation_values($base_url, $operations);

    // next, perform the operations on this model
    foreach ($operations as $operation) {
      $type = strtolower($operation['op']);
      $field = $this->_parse_field_from_operation_path($base_url, $operation['path']);

      if ($type === 'replace') {
        $model->$field = $operation['value'];
      }
    }

    // save the model and render the new data for the user
    $model->save();
    $res = JsonResHandler::render($model->get_data(), 200);
  }

  protected function _sanitize_operation_values($base_url = '', $operations = array()) {
    $model = $this->get_model_object();

    foreach ($operations as $operation_id => $operation) {
      // get the name of the field (so we can sanitize accordingly)
      $field = $this->_parse_field_from_operation_path($base_url, $operation['path']);

      $operation['value'] = $model->sanitize_db_field_value($field, $operation['value']);
      $operations[$operation_id] = $operation;
    }

    return $operations;
  }

  protected function _validate_operations($base_url = '', $operations = array()) {
    // ensure we're dealing with an array
    if (!is_array($operations)) {
      $error = array(
        'code' => 'INVALID_PATCH_OPERATIONS',
        'detail' => 'The JSON PATCH operations couldn\'t be parsed'
      );
      $res = JsonResHandler::render(array($error), 400);
    }

    // next, iterate through all of the operations - validating each one
    $required_keys = array('op', 'path', 'value');
    foreach ($operations as $operation) {
      // first, ensure all of the required keys exist for this operation
      $all_required_keys_exist = true;

      foreach ($required_keys as $required_key) {
        if (!array_key_exists($required_key, $operation)) {
          $all_required_keys_exist = false;
          break;
        }
      }

      // if a required key did not exist, let the user know
      if (!$all_required_keys_exist) {
        $error = array(
          'code' => 'INVALID_PATCH_OPERATION',
          'detail' => 'A required operation key is missing; expected "op", "path" and "value"'
        );
        $res = JsonResHandler::render(array($error), 400);
      }

      // secondly, make sure that the path of this operation is correct
      $this->_validate_operation_path($base_url, $operation['path']);
    }
  }

  protected function _validate_operation_path($base_url, $path = '') {
    $field = $this->_parse_field_from_operation_path($base_url, $path);

    if (!$this->get_model_object()->is_valid_attribute($field)) {
      $error = array(
        'code' => 'INVALID_OPERATION_PATH',
        'detail' => '"' . $path . '" is not a valid operation path for this endpoint'
      );
      $res = JsonResHandler::render(array($error), 400);
    }
  }

  protected function _parse_field_from_operation_path($base_url = '', $path = '') {
    $base_url .= '/';
    return str_replace($base_url, '', $path);
  }

  protected function _get_model($identifier = null) {
    $identifier = $this->sanitize_model_identifer($identifier);
    $class_name = $this->get_model_class();

    try {
      // fetch the model and then return it
      $model = new $class_name($identifier);
      return $model;
    } catch (Exception $error) {
      $this->_handle_get_model_error($error);
    }
  }

  protected function _handle_get_model_error(Exception $error) {
    $message = $error->getMessage();

    if (strpos($message, 'results in no database records')) {
      $error = array(
        'code' => 'NOT_FOUND',
        'detail' => $message
      );
      $res = JsonResHandler::render(array($error), 404);
    }

    trigger_error($message, E_USER_ERROR);
  }

}
