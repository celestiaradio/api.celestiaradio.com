Celestia Radio API
==================

This document is designed to document the various commands supported by the new Celestia Radio API that was initially developed by [Mark Seymour][email] in early April 2014, as well as outlining possible additions and upgrades to the API as time goes on.

The API itself uses Composer's `autoload` feature to automatically load classes as required—no need to use `include` except for the autoload feature itself at the beginning of `index.php`.

The API can be used from <http://api.celestiaradio.com/>.

Methods
-------

With the API, the general format of the response is:
```json
{
  "status": "success",
  "result": { "...": "..." }
}
```
however `result` may contain any kind of object, depending on the method. If the API encounters an error, the response will be:
```json
{
  "status": "error",
  "error": "An error message"
}
```

### /nowplaying

This method aggregates various pieces of data from different sources in order to make a plethora of data available, mainly for use in services such as [Hoofsounds][hs] as well as Celestia Radio's own [Aiko][azbot].

By default, the API makes available the current song as well as the previous 9 songs played. Due to an issue with Centova, if a DJ is on, anything played by the DJ as well as the AutoDJ in the background will be reported.

In order to minimize the amount of data sent by the API (and not necessarily to the aggregated APIs, *this may change in the future*), users can use `/nowplaying/current` to only retrieve the latest song data.

Configuring
-----------

Adding internal configuration is easy. Just create a new directory under `/src` named `config`, and individual configuration groups can be created under individual files with the extension `.php`. The format of the files takes a page from [Laravel](http://laravel.com/), where they are formatted like this:
```php
<?php
return [
  'key' => 'value',
  'another_key' => [
    'a_sub_key' => 'a sub value'
  ]
];
```

The configuration then can be accessed anywhere by calling `Config::get('my_config_file')['key']`. In the future, the `::get` method may be updated to allow a single string with dot notation (`Config::get('my_config_file.key')`) as well as the normal array notation.

### Setting up access for CentovaCast and Icecast*
\* _For the Celestia Radio Web Team only—Sorry!_

In order to configure the username and password used for accessing Celestia Radio's CentovaCast and Icecast servers (for use in `/nowplaying`), you must create a new directory under `/src` named "config", and under that create a new file named `apikeys.php`.

The format of the file should be as follows:
```php
<?php
return [
  'centova' => [
    'username' => '<CENTOVA USERNAME>',
    'password' => '<CENTOVA PASSWORD>'
  ]
];
```

Contributing
------------

1. Fork it.
2. Create a branch (`git checkout -b new_api_feature`)
3. Commit your changes (`git commit -am "Added a super-fancy API feature!"`)
4. Push to the branch (`git push origin new_api_feature`)
5. Open a [Pull Request][1]

[email]: mark.seymour.ns@gmail.com
[hs]: https://hoofsounds.little.my/
[azbot]: https://github.com/mseymour/azurebot
[1]: https://github.com/celestia-radio/api.celestiaradio.com/pulls
