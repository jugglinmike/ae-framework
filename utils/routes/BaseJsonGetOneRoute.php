<?php

class BaseJsonGetOneRoute extends BaseJsonRoute {

  public function render($identifier = null) {
    $search = $this->sanitize_model_identifer($identifier);
    $include = $this->get_include();
    //make sure it's not been soft deleted
    $search['inactive'] = 0;

    try {
      $model = $this->createModel($search);
      $data = $model->get_linked_data($include);
      $res = JsonResHandler::render($this->dashKeys($data), 200);
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
