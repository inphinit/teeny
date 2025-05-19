<?php
/**
 * Teeny
 *
 * Copyright (c) 2025 Guilherme Nascimento (brcontainer@yahoo.com.br)
 *
 * Released under the MIT license
 */

namespace Inphinit;

class Teeny
{
    private $builtIn = false;

    private $code;
    private $pathInfo;

    private $codes = array();
    private $routes = array();
    private $paramRoutes = array();

    private $hasParams = false;
    private $patternNames;
    private $paramPatterns = array(
        'alnum' => '[\da-zA-Z]+',
        'alpha' => '[a-zA-Z]+',
        'decimal' => '(\d|[1-9]\d+)\.\d+',
        'nospace' => '[^/\s]+',
        'num' => '\d+',
        'uuid' => '[\da-fA-F]{8}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{12}',
        'version' => '\d+\.\d+(\.\d+(-[\da-zA-Z]+(\.[\da-zA-Z]+)*(\+[\da-zA-Z]+(\.[\da-zA-Z]+)*)?)?)?'
    );

    public function __construct()
    {
        header_remove('X-Powered-By');

        $this->builtIn = PHP_SAPI === 'cli-server';

        $path = rawurldecode(strtok($_SERVER['REQUEST_URI'], '?'));

        if ($this->builtIn === false) {
            $pos = strpos($_SERVER['SCRIPT_NAME'], '/index.php');
            $path = substr($path, $pos);
        }

        $this->pathInfo = $path;
    }

    /**
     * Get current path from URL (ignores subfolders if it is located in a subfolder on your webserver)
     *
     * @return string
     */
    public function path()
    {
        return $this->pathInfo;
    }

    /**
     * Get or set HTTP status code
     *
     * @param int $code
     * @return int
     */
    public function status($code = null)
    {
        if (function_exists('http_response_code')) {
            return $code ? http_response_code($code) : http_response_code();
        }

        if ($this->code === null) {
            if (preg_match('#/RESERVED\.TEENY-(\d{3})\.html#', $_SERVER['PHP_SELF'], $match)) {
                $this->code = (int) $match[1];
            } else {
                $this->code = 200;
            }
        }

        if ($code === null || $code === $this->code) {
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
     * Register a callback or script for a route
     *
     * @param string|array    $methods
     * @param string          $path
     * @param string|callable $callback
     * @return void
     */
    public function action($methods, $path, $callback)
    {
        $path = '/' . ltrim($path, '/');

        if (strpos($path, '<') !== false) {
            $routes = &$this->paramRoutes;

            $this->hasParams = true;
        } else {
            $routes = &$this->routes;
        }

        if (isset($routes[$path]) === false) {
            $routes[$path] = array();
        }

        if (is_array($methods)) {
            foreach ($methods as $method) {
                $routes[$path][strtoupper($method)] = $callback;
            }
        } else {
            $routes[$path][strtoupper($methods)] = $callback;
        }
    }

    /**
     * Create or replace a pattern for URL slugs
     *
     * @param string $name
     * @param string $regex
     * @return void
     */
    public function setPattern($name, $regex)
    {
        $this->paramPatterns[preg_quote($name)] = $regex;
        $this->patternNames = null;
    }

    /**
     * Handler HTTP status code
     *
     * @param array           $codes
     * @param callable|string $callback Define function or script file
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
     * @return bool Returns false if request matches a file in built-in web server, otherwise returns true
     */
    public function exec()
    {
        $code = $this->status();
        $params = null;
        $callback = null;

        if ($code === 200) {
            if ($this->builtIn && $this->fileInBuiltIn()) {
                return false;
            }

            $path = $this->pathInfo;
            $method = $_SERVER['REQUEST_METHOD'];
            $routes = null;

            if (isset($this->routes[$path])) {
                $routes = &$this->routes[$path];
            } elseif ($this->hasParams) {
                $this->params($routes, $params);
            }

            if (isset($routes[$method])) {
                $callback = $routes[$method];
            } elseif (isset($routes['ANY'])) {
                $callback = $routes['ANY'];
            } else {
                $code = $routes === null ? 404 : 405;
            }
        }

        if ($code !== 200) {
            $this->status($code);

            if (isset($this->codes[$code])) {
                $callback = $this->codes[$code];
                $params = array('code' => $code);
            }
        }

        if (is_string($callback) && strpos($callback, '.') !== false) {
            teeny_sandbox($this, $callback, $params);
        } else if ($params) {
            echo $callback($params);
        } else {
            echo $callback();
        }

        return true;
    }

    private function params(&$routes, &$params)
    {
        if ($this->patternNames === null) {
            $this->patternNames = implode('|', array_keys($this->paramPatterns));
        }

        $pathinfo = $this->pathInfo;
        $patterns = &$this->paramPatterns;
        $getParams = '#\\\\[<]([A-Za-z]\\w+)(\\\\:(' . $this->patternNames . ')|)\\\\[>]#';

        $limit = 20;
        $total = count($this->paramRoutes);

        for ($indexRoutes = 0; $indexRoutes < $total; $indexRoutes += $limit) {
            $slice = array_slice($this->paramRoutes, $indexRoutes, $limit);

            $j = 0;
            $callbacks = array();

            foreach ($slice as $regexPath => &$param) {
                $callbacks[] = $param;
                $param = '#route_' . (++$j) . '>' . preg_quote($regexPath);
            }

            $groupRegex = implode(')|(', $slice);
            $groupRegex = preg_replace($getParams, '(?P<$1><$3>)', $groupRegex);
            $groupRegex = str_replace('<>)', '[^/]+)', $groupRegex);

            foreach ($patterns as $pattern => $regex) {
                $groupRegex = str_replace('<' . $pattern . '>)', $regex . ')', $groupRegex);
            }

            $groupRegex = str_replace('#route_', '?<route_', $groupRegex);

            if (preg_match('#^((?J)(' . $groupRegex . '))$#', $pathinfo, $params)) {
                foreach ($params as $index => $value) {
                    if ($value === '' || is_int($index)) {
                        unset($params[$index]);
                    } elseif (strpos($index, 'route_') === 0) {
                        $routes = $callbacks[substr($index, 6) - 1];
                        unset($params[$index]);
                    }
                }

                break;
            }
        }
    }

    private function fileInBuiltIn()
    {
        $path = $this->pathInfo;
        $public = $_SERVER['DOCUMENT_ROOT'];

        return (
            $path !== '/' &&
            strpos($path, '.') !== 0 &&
            strpos($path, '/.') === false &&
            is_file($public . '/' . $path)
        );
    }
}

/**
 * Require file
 *
 * @param \Inphinit\Teeny $app      Teeny (or custom) context
 * @param string          $callback File required
 * @param array           $params   Params from route pattern
 * @return mixed
 */
function teeny_sandbox(Teeny $app, $callback, $params)
{
    return require $callback;
}
