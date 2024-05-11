<?php
namespace Inphinit;

/**
 * Based on Inphinit\Routing\Route class
 *
 * @author Guilherme Nascimento <brcontainer@yahoo.com.br>
 * @see    https://github.com/inphinit/framework/blob/master/src/Inphinit/Routing/Route.php
 */
class Teeny
{
    private $builtIn = false;

    private $code;
    private $pathInfo;

    private $codes = array();
    private $routes = array();
    private $paramRoutes = array();

    private $hasParams = false;
    private $paramPatterns = array(
        'alnum' => '[\da-zA-Z]+',
        'alpha' => '[a-zA-Z]+',
        'decimal' => '\d+\.\d+',
        'num' => '\d+',
        'noslash' => '[^\/]+',
        'nospace' => '[^\/\s]+',
        'uuid' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}',
        'version' => '\d+\.\d+(\.\d+(-[\da-zA-Z]+(\.[\da-zA-Z]+)*(\+[\da-zA-Z]+(\.[\da-zA-Z]+)*)?)?)?'
    );

    public function __construct()
    {
        header_remove('X-Powered-By');

        $this->builtIn = PHP_SAPI === 'cli-server';

        $uri = urldecode(strtok($_SERVER['REQUEST_URI'], '?'));

        if (!$this->builtIn) {
            $uri = substr($uri, stripos($_SERVER['SCRIPT_NAME'], '/index.php'));
        }

        $this->pathInfo = $uri;
    }

    /**
     * Get current path from application
     *
     * @return string
     */
    public function path()
    {
        return $this->pathInfo;
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
     * @param string|\Closure $callback
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

        if (!isset($routes[$path])) {
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
     * @return bool
     */
    public function exec()
    {
        $code = $this->status();
        $callback = null;

        if ($code === 200) {
            $path = $this->pathInfo;

            if ($this->builtIn && $this->fileInBuiltIn()) {
                return false;
            }

            $method = $_SERVER['REQUEST_METHOD'];

            if (isset($this->routes[$path])) {
                $routes = &$this->routes[$path];

                if (isset($routes[$method])) {
                    $callback = $routes[$method];
                } elseif (isset($routes['ANY'])) {
                    $callback = $routes['ANY'];
                } else {
                    $code = 405;
                }
            } elseif ($this->hasParams) {
                $code = $this->params($method);
            } else {
                $code = 404;
            }
        }

        if ($code !== 0) {
            $this->dispatch($callback, $code, array());
        }

        return true;
    }

    /**
     * Create or remove a pattern for URL slugs
     *
     * @param string|null $pattern Set pattern for URL slug params like this /foo/<var:pattern>
     * @return void
     */
    public function setPattern($pattern, $regex)
    {
        if ($regex === null) {
            unset($this->paramPatterns[preg_quote($pattern)]);
        } else {
            $this->paramPatterns[preg_quote($pattern)] = $regex;
        }
    }

    private function params($method)
    {
        $pathinfo = $this->pathInfo;
        $patterns = $this->paramPatterns;
        $getParams = '#\\\\[<]([A-Za-z]\\w+)(\\\\:(' . implode('|', array_keys($patterns)) . ')|)\\\\[>]#';

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

            $groupRegex = preg_replace($getParams, '(?<$1><$3>)', $groupRegex);
            $groupRegex = str_replace('<>)', '.*?)', $groupRegex);

            foreach ($patterns as $pattern => $regex) {
                $groupRegex = str_replace('<' . $pattern . '>)', $regex . ')', $groupRegex);
            }

            $groupRegex = str_replace('#route_', '?<route_', $groupRegex);

            if (preg_match('#^((?J)(' . $groupRegex . '))$#', $pathinfo, $params)) {
                foreach ($params as $index => $value) {
                    if ($value === '' || is_int($index)) {
                        unset($params[$index]);
                    } else if (strpos($index, 'route_') === 0) {
                        $callbacks = $callbacks[substr($index, 6) - 1];

                        unset($params[$index]);
                    }
                }

                $code = 200;

                if (isset($callbacks[$method])) {
                    $callback = $callbacks[$method];
                } elseif (isset($callbacks['ANY'])) {
                    $callback = $callbacks['ANY'];
                } else {
                    $code = 405;
                    $callback = null;
                }

                $this->dispatch($callback, $code, $params);

                return 0;
            }
        }

        return 404;
    }

    private function dispatch($callback, $code, $params)
    {
        if ($code !== 200) {
            $this->status($code);

            if (isset($this->codes[$code])) {
                $callback = $this->codes[$code];
                echo $callback($code);
            }
        } elseif (is_string($callback) && strpos($callback, '.') !== false) {
            teeny_sandbox($this, $callback, $params);
        } else if ($params) {
            echo $callback($params);
        } else {
            echo $callback();
        }
    }

    private function fileInBuiltIn()
    {
        $path = $this->pathInfo;
        return (
            $path !== '/' &&
            strpos($path, '.') !== 0 &&
            strpos($path, '/.') === false &&
            is_file('public' . $path)
        );
    }
}

/**
 * Require file
 *
 * @param Teeny  $app      Teeny (or custom) context
 * @param string $callback File required
 * @param array  $params   Params from route pattern
 * @return mixed
 */
function teeny_sandbox(Teeny $app, $callback, $params)
{
    return require $callback;
}
