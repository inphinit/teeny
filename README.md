<div align="center">
    <a href="https://github.com/inphinit/teeny/">
        <img src="./badges/php.png" width="160" alt="Teeny route system for PHP">
    </a>
    <a href="https://github.com/inphinit/teeny.js/">
    <img src="./badges/javascript.png" width="160" alt="Teeny route system for JavaScript (Node.js)">
    </a>
    <a href="https://github.com/inphinit/teeny.go/">
    <img src="./badges/golang.png" width="160" alt="Teeny route system for Golang">
    </a>
    <a href="https://github.com/inphinit/teeny.py/">
    <img src="./badges/python.png" width="160" alt="Teeny route system for Python">
    </a>
</div>

# Teeny route system for PHP

Teeny is a micro-route system that is really micro, supports **PHP 5.3** to **PHP 8**, is extremely simple and ready to use.

## Install using composer

For create your project use:

```
composer create-project inphinit/teeny <project name>
```

Replace `<project name>` by your project name, for exemple, if want create your project with "blog" name (folder name), use:

```
composer create-project inphinit/teeny blog
```

## Download without composer

If is not using `composer` try direct download from https://github.com/inphinit/teeny/releases

## Apache (`.htaccess`)

The `.htaccess` will only need some adjustment if you are using it in a subfolder, you will need to change all `ErrorDocument`. See more details in https://httpd.apache.org/docs/2.4/custom-error.html.

If the address is something like `https://<domain>/`, then do:

```apacheconf
ErrorDocument 403 /index.php/RESERVED.TEENY-403.html
ErrorDocument 500 /index.php/RESERVED.TEENY-500.html
ErrorDocument 501 /index.php/RESERVED.TEENY-501.html
```

If the address is something like `https://<domain>/foo/`, then do:

```apacheconf
ErrorDocument 403 /foo/index.php/RESERVED.TEENY-403.html
ErrorDocument 500 /foo/index.php/RESERVED.TEENY-500.html
ErrorDocument 501 /foo/index.php/RESERVED.TEENY-501.html
```

If the address is something like `https://<domain>/foo/bar/`, then do:

```apacheconf
ErrorDocument 403 /foo/bar/index.php/RESERVED.TEENY-403.html
ErrorDocument 500 /foo/bar/index.php/RESERVED.TEENY-500.html
ErrorDocument 501 /foo/bar/index.php/RESERVED.TEENY-501.html
```

## NGINX

For NGINX you can use [`try_files`](https://nginx.org/en/docs/http/ngx_http_core_module.html#try_files) in Nginx. See a example:

```
location / {
    root /home/foo/bar/teeny;

    # Redirect page errors to route system
    error_page 403 /index.php/RESERVED.TEENY-403.html;
    error_page 500 /index.php/RESERVED.TEENY-500.html;
    error_page 501 /index.php/RESERVED.TEENY-501.html;

    try_files /public$uri /index.php?$query_string;

    location = / {
        try_files $uri /index.php?$query_string;
    }

    location ~ /\. {
        try_files /index.php$uri /index.php?$query_string;
    }

    location ~ \.php$ {
        # Replace by your FPM or FastCGI
        fastcgi_pass 127.0.0.1:9000;

        fastcgi_index index.php;
        include fastcgi_params;

        set $teeny_suffix "";

        if ($uri != "/index.php") {
            set $teeny_suffix "/public";
        }

        fastcgi_param SCRIPT_FILENAME $realpath_root$teeny_suffix$fastcgi_script_name;
    }
}
```

> **Note:** For FPM use `fastcgi_pass unix:/var/run/php/php<version>-fpm.sock` (replace `<version>` by PHP version in your server)

## Built-in web server

You can use [built-in server](https://www.php.net/manual/en/features.commandline.webserver.php) to facilitate the development, Teeny provides the relative static files, which will facilitate the use, example of use (navigate to project folder using `cd` command):

```sh
php -S localhost:8080 index.php
```

You can edit the server.bat (Windows) or server (Linux or macOS) files to make it easier to start the project with a simple command

### Windows (server.bat file)

Configure the `server.bat` variables according to your environment:

```bat
set PHP_BIN=C:\php\php.exe
set PHP_INI=C:\php\php.ini

set HOST_HOST=localhost
set HOST_PORT=9000
```

Once configured, you can navigate to the project folder and run the command that will start built-in server, see an example:

```bat
cd c:\projets\blog
server
```

### Linux and macOS (server file)

Configure the `./server` variables according to your environment:

```sh
PHP_BIN=/usr/bin/php
PHP_INI=/etc/php.ini

HOST_HOST=localhost
HOST_PORT=9000
```

Once configured, you can navigate to the project folder and run the command that will start built-in server, see an example:

```sh
cd ~/projets/blog
./server
```

## API

Methods from `Teeny` class

Method | Description
---|---
`Teeny::path(): string` | Get current path from URL (ignores subfolders if it is located in a subfolder on your webserver)
`Teeny::status([int $code]): int` | Get or set HTTP status
`Teeny::action(mixed $methods, string $path, mixed $callback): void` | Add or remove or update a route, supports functions, closures and paths to PHP scripts
`Teeny::handlerCodes(array $codes, mixed $callback): int` | Detect if SAPI or script change HTTP status
`Teeny::setPattern(string $pattern, mixed $regex): void` | Add or replace a pattern for custom routes, like `/foo/<variable1:pattern>`
`Teeny::exec(): bool` | Execute defined route


## Add and remove routes

For create a new route in `index.php` put like this:

```php
$app->action('GET', '/myroute', function () {
    echo 'Test!';
});
```

You can use `return`:

```php
$app->action('GET', '/myroute', function () {
    return 'Test!';
});
```

For remove a route use `null` value, like this:

```php
$app->action('GET', '/myroute', null);
```

## Route include file

For include a file uses like this:

```php
$app->action('GET', '/myroute', 'foo/bar/test.php');
```

If `foo/bar/test.php` not found in project will display the following error:

```
Warning: require(foo/bar/test.php): failed to open stream: No such file or directory in /home/user/blog/vendor/teeny.php on line 156

Fatal error: require(): Failed opening required 'foo/bar/test.php' (include_path='.') /home/user/blog/vendor/teeny.php on line 156
```

## HTTP status

For retrieve HTTP status from SAPI (Apache, Ngnix, IIS) or previously defined in the script itself use like this:

```php
$var = $app->status();
```

For retrieve into a route use like this:

```php
$app->action('GET', '/myroute', function () use ($app) {
    echo 'HTTP status: ', $app->status();
});
```

For set a new HTTP status use like this (eg.: emit 404 Not Found):

```php
$app->status(404);
```

For set into route use like this (a example with condition/if):

```php
$app->action('GET', '/report', function () use ($app) {
    $file = 'data/foo.csv';

    if (is_file($file)) {
        header('Content-Type: text/csv');
        readfile($file);
        /**
         * Note: this is just an example, about sending a file,
         * if possible use "X-Sendfile" or equivalent
         */
    } else {
        $app->status(404);

        echo 'Report not found';
    }
});
```

## Named params in route

You can use params like this:

```php
$app->action('GET', '/user/<user>', function ($params) {
    var_dump($params);
});
```

If access a URL like this `http://mywebsite/user/mary` returns:

```php
array(2) {
  ["user"]=>
  string(3) "mary"
}
```

Another example:

```php
$app->action('GET', '/article/<name>-<id>', function ($params) use ($app) {
    // Only ID numerics are valids
    if (ctype_digit($params['id'])) {
        echo 'Article ID: ', $params['id'], '<br>';
        echo 'Article name: ', $params['name'];
    } else {
        $app->status(400);

        echo 'Invalid URL';
    }
});
```

If access a URL like this `http://mywebsite/article/mary-1000` returns:

```
Article ID: mary
Article name: 1000
```

## Supported types for named parameters in routes

An example, only numeric id are valids:

```php
$app->action('GET', '/article/<name>-<id:num>', function ($params) {
    echo 'Article ID: ', $params['id'], '<br>';
    echo 'Article name: ', $params['name'];
});
```

Type | Example | Description
---|---|---
`alnum` | `$app->action('GET', '/baz/<video:alnum>', ...);` | Only accepts parameters with alpha-numeric format and `$params` returns `array( video => ...)`
`alpha` | `$app->action('GET', '/foo/bar/<name:alpha>', ...);` | Only accepts parameters with alpha format and `$params` returns `array( name => ...)`
`decimal` | `$app->action('GET', '/baz/<price:decimal>', ...);` | Only accepts parameters with decimal format and `$params` returns `array( price => ...)`
`num` | `$app->action('GET', '/foo/<id:num>', ...);` | Only accepts parameters with integer format and `$params` returns `array( id => ...)`
`nospace` | `$app->action('GET', '/foo/<nospace:nospace>', ...);` | Accepts any characters expcet spaces, like white-spaces (`%20`), tabs (`%0A`) and others (see about `\S` in regex)
`uuid` | `$app->action('GET', '/bar/<barcode:alnum>', ...);` | Only accepts parameters with uuid format and `$params` returns `array( barcode => ...)`
`version` | `$app->action('GET', '/baz/<api:version>', ...);` | Only accepts parameters with [semversion (v2)](https://semver.org/spec/v2.0.0.html) format and `$params` returns `array( api => ...)`

For add new patterns use like this `Teeny::setPattern()`, examples:

```php
$app->setPattern('example', '[A-Z]\d+');

$app->action('GET', '/custom/<myexample:example>', function ($params) use ($app) {
    echo '<h1>custom pattern</h1>';
    echo '<pre>';
    print_r($params);
    echo '</pre>';
});
```

And for access this route exemple use `http://mysite/test/A00001` or `http://mysite/test/C02`, start with upper-case letter and after width a integer number

## Dealing with large files

To work with large files you can choose to use the following server modules:

Module | Server | Documentation
--- | --- | ---
`X-Sendfile` | Apache | https://tn123.org/mod_xsendfile/
`X-Accel-Redirect` | NGINX | https://www.nginx.com/resources/wiki/start/topics/examples/x-accel/
`X-LIGHTTPD-send-file` and `X-Sendfile2` | Lighttpd | https://redmine.lighttpd.net/projects/1/wiki/X-LIGHTTPD-send-file

A simple implementation:

```php
$software = $_SERVER['SERVER_SOFTWARE'];
$send = null;

if (stripos($software, 'apache') !== false) {
    $send = 'X-Sendfile';
} else if (stripos($software, 'nginx') !== false) {
    $send = 'X-Accel-Redirect';
} else if (stripos($software, 'lighttpd') !== false) {
    $send = 'X-LIGHTTPD-send-file';
}

$app->action('GET', '/download', function () use ($sendHeader) {
    $file = '/protected/iso.img';

    if ($send) {
        header($send . ': ' . $file);
        return;
    }

    if ($handle = fopen($file, 'r')) {
        $app->status(500);
        return 'Failed to read file';
    }

    // fallback (this is just an example)
    $length = 2097152;

    header('Content-Disposition: attachment; filename="iso.img"');
    header('Content-Length: ' . filesize($file));

    while (!feof($handle)) {
        echo fgets($handle, $length);
        flush();
    }

    fclose($handle);
});
```

## Serving public files (and scripts)

To serve public files (or scripts) you must add them to the public folder. The prefix `/public/*` will not be displayed in the URL, for example, if there is a file like `public/foobar.html`, then the user will simply access the address `https://<domain>/foobar.html`.

Subfolders will also work, if it has a file like `public/foo/bar/baz/video.webm` then the user should go to `https://<domain>/foo/bar/baz/video.webm`.

You can add PHP scripts, and they will be executed normally, if you have a script like `public/sample/helloworld.php`, just access `https://<domain>/sample/helloworld.php`

If you want to make a blog available, such as Wordpress, you must also place it inside the folder, an example of structure:

```
├─── .htaccess
├─── index.php
├─── composer.json
├─── vendor/
└─── public/
     ├─── helloword.html
     └─── blog/
          ├─── .htaccess
          ├─── index.php
          ├─── wp-activate.php
          ├─── wp-blog-header.php
          ├─── wp-comments-post.php
          ├─── wp-config-sample.php
          ├─── wp-config.php
          ├─── wp-cron.php
          ├─── wp-links-opml.php
          ├─── wp-load.php
          ├─── wp-login.php
          ├─── wp-mail.php
          ├─── wp-settings.php
          ├─── wp-signup.php
          ├─── wp-trackback.php
          ├─── xmlrpc.php
          ├─── wp-admin/
          ├─── wp-content/
          └─── wp-includes/
```

And then just access `https://<domain>/blog/`. Other samples:

- `https://<domain>/blog/wp-admin/`
- `https://<domain>/blog/2021/03/24/astronomy-messier-87-black-hole/`
- `https://<domain>/blog/2023/04/17/researchers-discover-small-galaxy/`

---

If you need more features you can experience the **Inphinit PHP framework**: https://inphinit.github.io
