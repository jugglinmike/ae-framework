<?php

class BaseRoute {

  /**
   * Used to determine the HTTP method of this route
   *
   * @var string
   */
  protected $_method = 'GET';

  protected $_uri = 'none';

  protected $_router;

  protected $_request;

  protected $_uri_data;

  /**
   * Returns the method this route was registered as.
   *
   * @return string The HTTP method this route was registered as
   */
  public function get_method() {
    return strtolower($this->_method);
  }

  /**
   * get_uri_regex
   * Helper method that returns a regex string that can be used to
   * determine if a given api request is intended for this route or not.
   * The assumption made is that any piece of the URI prefixed with : is
   * meant for numeric ID data.
   *
   * @return string Regex pattern for the route's URI
   */
  public function get_uri_regex() {
    $pattern = '/:\w+/';
    $replacement = preg_replace($pattern, '\d+', $this->_uri);
    return '/^' . str_replace('/', '\/', $replacement) . '$/';
  }

  public function set_request($request) {
    $this->_request = $request;
  }

  public function get_include() {
    if (isset($this->_request['query_params']) &&
      isset($this->_request['query_params']['include'])) {
      return array_map('trim', explode(',', $this->_request['query_params']['include']));
    }

    return array();
  }

  /**
   * Class constructor function.
   */
  public function __construct(Router $router) {
    $this->_router = $router;
    $this->_register_endpoint();
  }


  public function set_uri_data() {
    $request_parts = $this->_request['base_url_parts'];
    $uri_parts = explode('/',$this->_uri);

    for ($i = 0; $i < count($uri_parts); $i++) {
      $uriPart = $uri_parts[$i];
      $requestPart = $request_parts[$i];
      if (strpos($uriPart, ':') === 0 && is_numeric($requestPart)) {
        $this->_uri_data[ltrim($uriPart, ':')] = intval($requestPart);
      }
    }
  }

  public function validate_url() {
    $dependencies = array();

    if (!is_null($this->_uri_data)) {
      foreach ($this->_uri_data as $key => $identifier) {
        $modelName = $key . 'Model';

        $this->_try_fetching_model_dependencies($dependencies, $modelName, $identifier);
        $dependencies[] = $key;
      }
    }
  }

  protected function _try_fetching_model_dependencies($dependencies, $modelName, $identifier) {
    try {
    	      // don't do any searching until we need to
      $dummyModel = new $modelName();
      $primaryKey = $dummyModel->get_db_primary_key();
      $search = array($primaryKey => $identifier);
      $modelAttributes = $dummyModel->get_attributes();

      // iterate through all of the dependencies, adding them to the search
      // requirements
      if (!empty($dependencies)) {
        foreach ($dependencies as $dependency) {
          if (in_array($dependency, $modelAttributes)) {
            $search[$dependency . '-id'] = $this->_uri_data[$dependency];
          }
        }
      }

      // now, perform the search; if this succeeds, we know the model
      // dependencies exist.
      $model = new $modelName($search);
    } catch (Exception $err) {
      $this->_handle_url_validation_error($err, $modelName, $identifier);
    }
  }

  protected function _handle_url_validation_error(Exception $err, $modelName, $identifier) {
    $message = $err->getMessage();

    if (strpos($message, 'results in no database records')) {
      $error = array(
        'code' => 'NOT_FOUND',
        'detail' => $message . ' for ' . $modelName . ' with id ' . $identifier
      );

      $res = JsonResHandler::render(array($error), 404);
    }

    trigger_error($message, E_USER_ERROR);
  }

  /**
   * Registers our endpoint with slim itself.
   *
   * Note: This function takes care of registered the appropriate callbacks
   * correctly so nothing is lost in translation.
   *
   * @return null
   */
  protected function _register_endpoint() {
    $this->_router->add_route($this);
  }

}
