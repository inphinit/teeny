<?php
require_once 'vendor/teeny.php';

//Uncomment next line for composer projects
# require_once 'vendor/autoload.php';

$app = new Teeny;

$app->action('GET', '/', 'examples/home.php');

$app->action('GET', '/include', 'examples/foo.php');

$app->action('GET', '/about', function () {
    echo 'Tenny is very small and easy route system';
});

$app->action('POST', '/foo/bar', function () {
    echo 'Hello foo bar!';
});

$app->action('PUT', '/cat', function () use ($app) {
    $app->status(201);

    echo 'Resource created';
});

$app->action('GET', '/foo/<foo>/<bar>', function ($params) {
    echo 'response from /&lt;foo>/&lt;bar>';
    echo '<pre>';
    print_r($params);
    echo '</pre>';
});

$app->action('GET', '/foo/<foo>-<bar>', function ($params) {
    echo 'response from /&lt;foo>-&lt;bar>';
    echo '<pre>';
    print_r($params);
    echo '</pre>';
});

// Example: http://localhost:8000/article/foo-1000
$app->action('GET', '/article/<name>/<id>', function ($params) use ($app) {
    if (ctype_digit($params['id'])) {
        echo 'Article ID: ', $params['id'], '<br>';
        echo 'Article name: ', $params['name'];
    } else {
        $app->status(400);

        echo 'Invalid URL';
    }
});

// Example: http://localhost:8000/blog/foo-1000
$app->action('GET', '/blog/<name>-<id:num>', function ($params) {
    echo 'Article ID: ', $params['id'], '<br>';
    echo 'Article name: ', $params['name'];
});

// Example: http://localhost:8000/test/foo-1000
$app->action('GET', '/test/<id:num>', 'teste');

// Example: http://localhost:8000/test/foo/abc
$app->action('GET', '/test/foo/<name:alpha>', 'teste');

// Example: http://localhost:8000/test/bar/f0f0f0
$app->action('GET', '/test/bar/<barcode:alnum>', 'teste');

$app->action('GET', '/decimal/<value:decimal>', 'teste');

$app->action('GET', '/uuid/<value:uuid>', 'teste');

$app->action('GET', '/version/<value:version>', 'teste');

function teste($params) {
    echo '<h1>Results:</h1>';
    echo '<pre>';
    print_r($params);
    echo '</pre>';
}

//Handle the HTTP response when the code is different than 200
$app->handlerCodes(array(404, 405), function ($code) {
    echo 'Custom page error ', $code;
});

//Remove true in argument if use Apache, Ngnix or IIS
return $app->exec(true);
