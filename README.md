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

## Execute

Copy for Apache ou Nginx folder and configure Vhost in Apache or execute direct from folder

## API

Methods from `Tenny` class

Method | Description
---|---
`Tenny::path(): string` | Get current path from URL (ignores subfolders if it is located in a subfolder on your webserver)
`Tenny::status([int $code]): int` | Get or set HTTP status
`Tenny::action(mixed $methods, string $path, mixd $callback): void` | Add or remove or update a route, supports functions, closures and paths to PHP scripts
`Tenny::handlerCodes(array $codes, mixd $callback): int` | Detect if SAPI or script change HTTP status
`Tenny::setPattern(string $pattern, mixed $regex): void` | Add or remove pattern for custom routes, like `/foo/<variable1:pattern>`
`Tenny::exec([bool $builtin]): bool` | Execute defined route, use `$builtin` for built-in-server for detect if file exists


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
    } else {
        $app->status(404);

        echo 'Report not found';
    }
});
```

## Built-in web-server vs normal web-servers

In normal servers you can use like this:

```php
return $app->exec();
```

In built-in web-sever use exactly that way:

```php
return $app->exec(true);
```

This way can be used on normal servers, it has no effect on normal servers.


## Named params in route

You can use params like this:

```php
$app->action('GET', '/user/<user>', function ($params) {
    var_dump($params);
});
```

If access a URL like this `http://mywebsite/user/mary` returns:

```
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

## Types of params named in routes

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
`uuid` | `$app->action('GET', '/bar/<barcode:alnum>', ...);` | Only accepts parameters with uuid format and `$params` returns `array( barcode => ...)`
`version` | `$app->action('GET', '/baz/<api:version>', ...);` | Only accepts parameters with [semversion (v2)](https://semver.org/spec/v2.0.0.html) format and `$params` returns `array( api => ...)`

For add new patterns use like this `$app->setPattern('example', '[A-Z]\d+');`, in routes use:

```php
$app->action('GET', '/test/<mytest:example>', function () use ($app) {
    var_dump($app->params['mytest']);
});
```
