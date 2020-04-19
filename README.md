## Install using composer

For create your project use:

```
composer create-project inphinit/tenny <project name>
```

Replace `<project name>` by your project name, for exemple, if want create your project with "blog" name (folder name), use:

```
composer create-project inphinit/tenny blog
```

## Download without composer

If is not using `composer` try direct download from https://github.com/inphinit/tenny/releases

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
`Tenny::exec([bool $builtin]): bool` | Execute defined route, use `$builtin` for built-in-server for detect if file exists


## Add and remove routes

For create a new route in `index.php` put like this:

```php
$app->action('GET', '/myroute', function () {
    echo 'Test!';
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
