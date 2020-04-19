<?php
class Teeny
{
    private $routes = array();
    private $codes = array();

    private $pathinfo;
    private $code = 200;

    private $done = false;

    public function __construct()
    {
        header_remove('X-Powered-By');
    }

    /**
     * Get current path from application
     *
     * @return string
     */
    public function path()
    {
        if ($this->pathinfo === null) {
            $requri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $sname = $_SERVER['SCRIPT_NAME'];

            if ($requri !== $sname && $sname !== '/index.php') {
                $pathinfo = rtrim(strtr(dirname($sname), '\\', '/'), '/');
                $pathinfo = substr(urldecode($requri), strlen($pathinfo));

                if ($pathinfo === false) {
                    $this->pathinfo = '/';
                } else {
                    $this->pathinfo = $pathinfo;
                }
            } else {
                $this->pathinfo = urldecode($requri);
            }
        }

        return $this->pathinfo;
    }

    /**
     * Get or set HTTP status
     *
     * @param int $code
     * @return int
     */
    public function status($code = null)
    {
        if (function_exists('http_response_code')) {
            return http_response_code($code);
        }

        if ($this->code === null) {
            $initial = 200;

            if (preg_match('#/RESERVED\.TENNY\-(\d{3})\.html$#', $_SERVER['PHP_SELF'], $match)) {
                $this->code = (int) $match[1];
            }
        }

        if ($code === null || $this->code === $code) {
            return $this->code;
        } elseif (headers_sent() || $code < 100 || $code > 599) {
            return false;
        }

        header('X-PHP-Response-Code: ' . $code, true, $code);

        $lastCode = $this->code;

        $this->code = $code;

        return $lastCode;
    }

    /**
     * Register or remove a callback or script for a route
     *
     * @param string|array         $methods
     * @param string               $path
     * @param string|\Closure|null $callback
     * @return void
     */
    public function action($methods, $path, $callback)
    {
        if (is_array($methods)) {
            foreach ($methods as $method) {
                $this->action($method, $path, $callback);
            }
        } else {
            if (!isset($this->routes[$path])) {
                $this->routes[$path] = array();
            }

            $this->routes[$path][strtoupper(trim($methods))] = $callback;
        }
    }

    /**
     * Handler HTTP status code
     *
     * @param array    $codes
     * @param callable $callback
     * @return void
     */
    public function handlerCodes(array $codes, $callback)
    {
        foreach ($codes as $code) {
            $this->codes[$code] = $callback;
        }
    }

    /**
     * Execute application
     *
     * @return void
     */
    public function exec()
    {
        if ($this->done) {
            return null;
        }

        $this->done = true;

        $callback = null;
        $newCode = 0;
        $code = $this->status();

        if ($code === 200) {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = $this->path();

            if (isset($this->routes[$path])) {
                $routes = &$this->routes[$path];

                if (isset($routes[$method])) {
                    $callback = $routes[$method];
                } elseif (isset($routes['ANY'])) {
                    $callback = $routes['ANY'];
                } else {
                    $newCode = 405;
                }

                if (is_string($callback) && strpos($callback, '.') !== false) {
                    $callback = function () use ($callback) {
                        require $callback;
                    };
                }
            } else {
                $newCode = 404;
            }

            if ($newCode) {
                $this->status($newCode);

                $code = $newCode;
            }
        }

        if ($newCode && isset($this->codes[$newCode])) {
            $callback = $this->codes[$newCode];
        }

        //Executa a action de uma rota ou do código 404 ou 405
        if ($callback) {
            if ($newCode) {
                $callback($newCode);
            } else {
                $callback();
            }
        }
    }
}
