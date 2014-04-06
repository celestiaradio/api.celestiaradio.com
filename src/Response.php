<?php

namespace Celestiaradio\Api;

/**
 * Response class factory.
 * 
 * @package api-framework
 * @author  Martin Bean <martin@martinbean.co.uk>, Mark Seymour <mark.seymour.ns@gmail.com>
 */

class Response
{
    /**
     * Constructor.
     *
     * @param string $data
     * @param boolean $success 
     * @param string $format
     */
    public static function create($data, $success, $format)
    {
      header("Access-Control-Allow-Origin: *");
      header('Cache-Control: max-age=300');
      switch ($format) {
          case 'application/json':
          default:
            $obj = new ResponseJson([
              'status' => ($success ? 'success' : 'error'),
              ($success ? 'result' : 'error') => $data
            ]);
          break;
      }

      return $obj;
    }
}
