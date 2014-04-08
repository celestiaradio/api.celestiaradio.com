<?php
require 'vendor/autoload.php';

/**
* Basic config access. Very basic.
*/
class Config
{
  private static $prefix = "./src/config/";
  private static $suffix = ".php";
  public static function get($conf)
  {
    if(is_file(self::$prefix . $conf . self::$suffix)) {
      return include(self::$prefix . $conf . self::$suffix);
    } else {
      return null;
    }
  }
}


/**
 * Parse the incoming request.
 */
$request = new Celestiaradio\Api\Request();
if (isset($_SERVER['PATH_INFO'])) {
    $request->url_elements = array_filter( explode('/', trim($_SERVER['PATH_INFO'], '/')), 'strlen' );
} elseif (isset($_SERVER['QUERY_STRING'])) {
    $request->url_elements = array_filter( explode('/', trim($_SERVER['QUERY_STRING'], '/')), 'strlen' );
}
$request->method = strtoupper($_SERVER['REQUEST_METHOD']);
switch ($request->method) {
    case 'GET':
        $request->parameters = $_GET;
    break;
    case 'POST':
        $request->parameters = $_POST;
    break;
    case 'PUT':
        parse_str(file_get_contents('php://input'), $request->parameters);
    break;
}

try {
  
  /**
   * Route the request.
   */
  
  if (!empty($request->url_elements)) {
    $controller_name = ucfirst($request->url_elements[0]) . 'Controller';
    $namespaced_controller_name = 'Celestiaradio\Api\Controllers\\'.$controller_name;
  
    if (class_exists($namespaced_controller_name) && $controller_name != 'BaseController') {
      $controller = new $namespaced_controller_name;
      $action_name = strtolower($request->method);
      $response_str = call_user_func_array(array($controller, $action_name), array($request));
      $response_success = true;
    } else {
      throw new Exception('Unknown request: ' . $request->url_elements[0]);
    }
  }
  else {
    $response_str = "The Celestia Radio API is online and functioning.";
    $response_success = true;
  }
  
} catch (Exception $e) {
  header($_SERVER['SERVER_PROTOCOL'].' 422'.$e->getMessage(), true, 422);
  $response_str = $e->getMessage();
  $response_success = false;
}

/**
 * Send the response to the client.
 */
$response_obj = Celestiaradio\Api\Response::create($response_str, $response_success, $_SERVER['HTTP_ACCEPT']);
echo $response_obj->render();