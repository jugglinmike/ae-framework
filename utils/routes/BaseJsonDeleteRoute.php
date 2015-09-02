<?php

class BaseJsonDeleteRoute extends BaseJsonRoute {

  public function delete($identifier = null) {
    $identifier = $this->sanitize_model_identifer($identifier);

    try {
      // retrieve the existing model (if it exists) and then
      // flag it as inactive
      $model = $this->createModel($identifier);
      $model->soft_delete();
      http_response_code(204);
    } catch (Exception $err) {
      $this->_handle_deletion_error($err);
    }
  }

  protected function _handle_deletion_error(Exception $err) {
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
