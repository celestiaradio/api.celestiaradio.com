<?php

namespace Celestiaradio\Api;

/**
 * Request class.
 * 
 * @package api-framework
 * @author  Martin Bean <martin@martinbean.co.uk>, Mark Seymour <mark.seymour.ns@gmail.com>
 */
class Request 
{
  /**
   * URL elements.
   *
   * @var array
   */
  public $url_elements = array();

  /**
   * The HTTP method used.
   *
   * @var string
   */
  public $method;

  /**
   * Any parameters sent with the request.
   *
   * @var array
   */
  public $parameters;
}
