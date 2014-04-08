<?php

namespace Celestiaradio\Api\Controllers;

use Celestiaradio\Api\Exceptions\UnsupportedMethodException;

/**
 * Base API controller
 * 
 * @package celestiaradio-api
 * @author  Mark Seymour <mark.seymour.ns@gmail.com>
 */
abstract class BaseController {
  
  /**
   * POST method.
   *
   * @param  Request $request
   * @throw Exception
   */
  public function post($request)
  {
    throw new UnsupportedMethodException('POST');
  }
  
  /**
   * PUT method.
   *
   * @param  Request $request
   * @throw Exception
   */
  public function put($request)
  {
    throw new UnsupportedMethodException('PUT');
  }
  
  /**
   * GET method.
   * 
   * @param  Request $request
   * @throw Exception
   */
  public function get($request)
  {
    throw new UnsupportedMethodException('GET');
  }
  
}
