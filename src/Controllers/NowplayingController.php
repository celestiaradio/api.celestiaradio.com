<?php

namespace Celestiaradio\Api\Controllers;

use SimpleXMLElement;

/**
 * Now Playing controller
 * 
 * @package celestiaradio-api
 * @author  Mark Seymour <mark.seymour.ns@gmail.com>
 */
class NowplayingController {
  
  // API access URLs
  private static $centova_url = 'http://audio.celestiaradio.com:2199/api.php?f=json';
  private static $centova_streaminfo_url = 'http://audio.celestiaradio.com:2199/rpc/celestia/streaminfo.get';
  private static $icecast_url = 'http://audio.celestiaradio.com:8062/admin/stats.xml';
  private static $eqb_url = 'http://eqbeats.org/tracks/search/exact/json/';
  
  // Rolling cURL
  private $rcURL;
  
  // Storage of data for processing
  private $data = array();
  private $eqb_data;
  
  // Storage of data for final output
  protected $station; #icecast/stats
  protected $listeners; #icecast/stats
  protected $current_song; #centova/getsongs
  protected $song_history; #centova/getsongs
  
  /**
   * Extra metadata to be added in `station`.
   */
  protected static $metadata = [
    'irc' => 'irc.canternet.org/#CelestiaRadio',
    'twitter_url' => 'https://twitter.com/celestiaradio',
    'web_url' => 'http://celestiaradio.com',
    'category' => 'audio'
  ];
  
  /**
   * Constructor
   */
  function __construct()
  {
    $this->rcURL = new \RollingCurl\RollingCurl();
    $this->curl_eqb = curl_init(self::$eqb_url);
  }
  
  /**
   * Deconstructor
   */
  function __destruct()
  {
    curl_close($this->curl_eqb);
  }
  
  /**
   * GET method.
   * 
   * @param  Request $request
   * @return string
   */
  public function get($request)
  {
    $this->fetch_data();
    $this->get_server_data();
    
    if (isset($request->url_elements[1])) {
      switch ($request->url_elements[1]) {
        case 'current':
          $this->get_song_data(1);
          break;
      
        default:
          $this->get_song_data();
          break;
      }
    } else {
      $this->get_song_data();
    }

    return [
      'station' => $this->station,
      'listeners' => $this->listeners,
      'current_song' => $this->current_song,
      'song_history' => $this->song_history
    ];
  }
  
  /**
   * Fetches all data at the same time for use later.
   */
  private function fetch_data()
  {
    $sites = [
      self::$centova_url => [
        'label' => 'songs',
        'options' => [
          CURLOPT_POST => true, 
          CURLOPT_POSTFIELDS => [
            'xm' => 'server.getsongs',
            'a[username]'=>\Config::get('apikeys')['centova']['username'],
            'a[password]'=>\Config::get('apikeys')['centova']['password'],
            'a[mountpoints]' => '/stream'
          ], 
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_FOLLOWLOCATION => false
        ]
      ],
      self::$icecast_url => [
        'label' => 'icecast',
        'options' => [
          CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
          CURLOPT_USERPWD => "admin:".\Config::get('apikeys')['centova']['password'],
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_FOLLOWLOCATION => false
        ]
      ],
      self::$centova_streaminfo_url => [
        'label' => 'streaminfo',
        'options' => [
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_FOLLOWLOCATION => false
        ]
      ]
    ];

    foreach ($sites as $url => $options) {
        $request = new \RollingCurl\Request($url);
        $request->setExtraInfo($options['label']);
        $this->rcURL->add(
            $request->addOptions($options['options'])
        );
    }

    $this->rcURL->setCallback(function(\RollingCurl\Request $request, \RollingCurl\RollingCurl $rollingCurl) {
      if($request->getResponseError() != '') throw new \Exception('Could not connect to Icecast or other service.');
      
      $parts = array_map('trim', explode(";", $request->getResponseInfo()['content_type'], 2));
      switch ($parts[0]) {
        case 'application/json':
          $this->data[$request->getExtraInfo()] = json_decode($request->getResponseText());
          break;
        case 'text/xml':
          $this->data[$request->getExtraInfo()] = new SimpleXMLElement($request->getResponseText());
          break;
        default:
          $this->data[$request->getExtraInfo()] = $request->getResponseText();
          break;
      }
    })->execute();
  }
  
  /**
   * Compiles together all server data from Icecast and Centova.
   */
  private function get_server_data()
  {
    $result = $this->data['icecast'];
    
    if(is_string($result)) throw new \Exception(trim($result));
    
    $streampublic = $result->xpath('/icestats/source[@mount="/stream"]/public');
    $stream = ($streampublic) ? $result->xpath('/icestats/source[@mount="/stream"]') : $result->xpath('/icestats/source[@mount="/autodj"]');
    
    $keys = array(
      'bitrate' => null,
      'listeners' => null,
      'listener_peak' => null,
      'server_name' => 'name',
      'server_description' => 'description',
      'genre' => null,
      'server_url' => 'url');
    
    $status = array();
    foreach ($keys as $key => $trans) {
      $status[($trans ? $trans : $key)] = (is_numeric((string)$stream[0]->$key)) ? (int)$stream[0]->$key : (string)$stream[0]->$key;
    }
    
    $streamstatus = $this->data['streaminfo'];

    $status['stream_status'] = ($streamstatus->data[0]->server === 'Online') ? true : false ;
  
    $this->listeners = ['current' => (int)$result->xpath('/icestats')[0]->clients, 'unique' => $status['listeners'], 'peak' => $status['listener_peak'], 'maximum' => $streamstatus->data[0]->maxlisteners];
    unset($status['listeners'], $status['listener_peak']);
    $this->station = $status + self::$metadata;
  }
  
  /**
   * Sorts all song data into a specific format and retrieves EQBeats data.
   */
  private function get_song_data($limit = 10)
  {
    $result = $this->data['songs'];
    
    $this->eqb_data = array();
    foreach (array_slice($result->response->data->songs, 0, $limit) as $song) {
      // Pre-sets the array to have all times available, in case of items not finishing all the way. This should not be happening at all, but I am not sure what else to do.
      $this->eqb_data[$song->time] = null;
      
      $request = new \RollingCurl\Request(self::$eqb_url);
      $request->setExtraInfo($song->time);
      $at = $this->format_title($song->title);
      $this->rcURL->add(
          $request->addOptions([
            CURLOPT_POSTFIELDS => http_build_query(['track' => trim($at[1]), 'artist' => $at[0]]), 
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false
          ])
      );
    }
    $this->rcURL->setCallback(function(\RollingCurl\Request $request, \RollingCurl\RollingCurl $rollingCurl) {
      if($request->getResponseError() != '') throw new \Exception('Could not connect to Icecast or other service.');
      $this->eqb_data[$request->getExtraInfo()] = json_decode($request->getResponseText());
    })->execute();
    
    $song_history = array();
    foreach (array_slice($result->response->data->songs, 0, $limit) as $song) {
      array_push($song_history, $this->format_song($song, $this->eqb_data[$song->time]));
    }
    
    $this->current_song = array_shift($song_history);
    if ($limit > 1) $this->song_history = $song_history;
  }
  
  /**
   * Formats song data into a specific format.
   * @param Object $song
   */
  private function format_song($song)
  {
    $artist_title = $this->format_title($song->title);
      
    return [
      'played_at' => $song->time,
      'song' => [
        'artist' => $artist_title[0],
        'eqbeats' => $this->get_eqbeats_ids($this->eqb_data[$song->time]),
        'text' => trim($song->title),
        'title' => $artist_title[1],
      ]
    ];
  }
  
  /**
   * Formats a whole title string from Centova into an array containing artist and title.
   * @param String $title the title string
   */
  private function format_title($title)
  {
    $at = explode(' - ', trim($title), 2);
    if(count($at) < 2) array_unshift($at, null);
    return $at;
  }
  
  /**
   * Formats EQBeats data into a specific format.
   * @param Object $result EQBeats JSON object response
   */
  protected function get_eqbeats_ids($result)
  {
    return [
      'artist_id' => !empty($result) ? $result[0]->artist->id : null,
      'song_id' => !empty($result) ? $result[0]->id : null
    ];
  }
  
}
