<?php
class Teeny
{
    private $routes = array();
    private $codes = array();

    public function action($path, $method, $callback)
    {
        if (!isset($this->routes[$path])) {
            $this->routes[$path] = array();
        }

        $this->routes[$path][strtoupper(trim($method))] = $callback;
    }

    public function handlerCodes(array $codes, $callback)
    {
        foreach ($codes as $code) {
            $this->codes[$code] = $callback;
        }
    }

    public function exec()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        $callback = null;
        $code = 0;

        if (isset($this->routes[$path])) {
            if (isset($this->routes[$path][$method])) {
                $callback = $this->routes[$path][$method];
            } else {
                $code = 405;
            }
        } else {
            $code = 404;
        }

        if ($code) {
            http_response_code($code); //Emite código 404 ou 405

            if (isset($this->codes[$code])) {
                $callback = $this->codes[$code];
            }
        }

        //Executa a action de uma rota ou do código 404 ou 405
        if ($callback) {
            if ($code) {
                $callback($code);
            } else {
                $callback();
            }
        }
    }
}