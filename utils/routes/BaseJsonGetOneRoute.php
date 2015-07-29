<?php

class BaseJsonGetOneRoute extends BaseJsonRoute {

  public function render($identifier = null) {
    $identifier = $this->sanitize_model_identifer($identifier);
    $class_name = $this->get_model_class();
    $include = $this->get_include();

    try {
      $model = new $class_name($identifier);
      $res = JsonResHandler::render($model->get_linked_data($include), 200);
    } catch (Exception $err) {
      $this->_handle_get_one_error($err);
    }
  }

  protected function _handle_get_one_error(Exception $err) {
    $message = $err->getMessage();

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
