<?php
require_once 'vendor/teeny.php';

$app = new Teeny;

$app->action('POST', '/foo/bar', function () {
    echo 'Hello foo bar!';
});

$app->action('PUT', '/cat', function () use ($app) {
    $app->status(201);

    echo 'Resource created';
});

$app->action('GET', '/', function () {
    echo 'Hello world!';
});

$app->action('GET', '/include', 'foo.php');

$app->handlerCodes(array(404, 405), function ($code) {
    echo 'Custom page error ', $code;
});

//Remove true in argument if use Apache, Ngnix or IIS
return $app->exec(true);
