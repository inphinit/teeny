<?php

include_once 'teeny.php';

$app = new Teeny;

$app->action('/foo/bar', 'POST', function () {
    echo 'Hello foo bar!';
});

$app->action('/cat', 'PUT', function () {
    echo 'Olá foo bar!';
});

$app->action('/', 'GET', function () {
    echo 'Hello world!';
});

$app->handlerCodes([404, 405], function ($code) {
    echo 'Custom page error ', $code;
});

$app->exec();