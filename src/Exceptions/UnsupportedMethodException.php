<?php

namespace Celestiaradio\Api\Exceptions;
use Exception;

/**
* Provides a formatted exception for use in HTTP requests.
*/
class UnsupportedMethodException extends Exception
{
  
  /**
   * Constructor method.
   * @param $method [String] The method used by the request.
   */
  public function __construct($method, $code = 0, Exception $previous = null) {
    $message = "HTTP method $method is currently not supported.";
    parent::__construct($message, $code, $previous);
  }
}

