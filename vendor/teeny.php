<?php
class Teeny
{
    private $routes = array();
    private $codes = array();

    private $method;
    private $pathinfo;
    private $code = 200;

    private $done = false;

    private $hasParams = false;

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

            if (strpos($path, '<') !==false && $callback) {
                $this->hasParams = true;
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
     * @param bool $builtin Check file in built-in web-server
     * @return bool
     */
    public function exec($builtin = false)
    {
        if ($this->done) {
            return null;
        }

        $this->done = true;

        $callback = null;
        $newCode = 0;
        $code = $this->status();

        if ($code === 200) {
            $path = $this->path();

            if ($builtin && $this->builtinFile()) {
                return false;
            }
        
            $this->method = $_SERVER['REQUEST_METHOD'];

            if (isset($this->routes[$path])) {
                $routes = &$this->routes[$path];

                if (isset($routes[$this->method])) {
                    $callback = $routes[$this->method];
                } elseif (isset($routes['ANY'])) {
                    $callback = $routes['ANY'];
                } else {
                    $newCode = 405;
                }
            } elseif ($this->hasParams && $this->params()) {
                return true;
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

        $this->dispatch($callback, $newCode, null);

        return true;
    }

    private function params()
    {
        $current = $this->pathinfo;
        $method = $this->method;

        foreach ($this->routes as $path => $value) {
            if (isset($value[$method]) && strpos($path, '<') !== false) {
                $path = preg_replace('#\\\\[<](.*?)(\\\\:(num|alnum|alpha|lower)|)\\\\[>]#i', '(?<$1><$3>)', preg_quote($path));

                $path = str_replace('<>)', '.*?)', $path);
                $path = str_replace('<num>)', '\d+)', $path);
                $path = str_replace('<alnum>)', '[a-z\d]+)', $path);
                $path = str_replace('<alpha>)', '[a-z]+)', $path);

                if (preg_match('#^' . $path . '$#i', $current, $params)) {
                    foreach ($params as $key => $match) {
                        if (is_int($key)) {
                            unset($params[$key]);
                        }
                    }

                    $this->dispatch($value[$method], 0, $params);

                    return true;
                }
            }
        }

        return false;
    }

    private function dispatch($callback, $code = 0, $params = null)
    {
        if (is_string($callback) && strpos($callback, '.') !== false) {
            require $callback;
        } elseif ($code) {
            $callback($code);
        } elseif ($params !== null) {
            $callback($params);
        } else {
            $callback();
        }
    }

    private function builtinFile()
    {
        $path = $this->pathinfo;

        return (
            $path !== '/' &&
            PHP_SAPI === 'cli-server' &&
            strcasecmp($path, '/vendor') !== 0 &&
            stripos($path, '/vendor/') !== 0 &&
            is_file(__DIR__ . '/..' . $path)
        );
    }
}
