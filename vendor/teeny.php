<?php
namespace Inphinit;

/**
* Based on Inphinit\Routing\Route class
*
* @author   Guilherme Nascimento <brcontainer@yahoo.com.br>
* @version  0.2.0
* @see      https://github.com/inphinit/framework/blob/master/src/Inphinit/Routing/Route.php
*/
class Teeny
{
    private $codes = array();
    private $routes = array();

    private $code = 200;
    private $method;
    private $pathinfo;

    private $hasParams = false;
    private $paramPatterns = array(
        'alnum' => '[\da-zA-Z]+',
        'alpha' => '[a-zA-Z]+',
        'decimal' => '\d+\.\d+',
        'num' => '\d+',
        'noslash' => '[^\/]+',
        'nospace' => '\S+',
        'uuid' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}',
        'version' => '\d+\.\d+(\.\d+(-[\da-zA-Z]+(\.[\da-zA-Z]+)*(\+[\da-zA-Z]+(\.[\da-zA-Z]+)*)?)?)?'
    );

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
            $requri = urldecode(strtok($_SERVER['REQUEST_URI'], '?'));
            $sname = $_SERVER['SCRIPT_NAME'];
            $sdir = dirname($sname);

            if ($sdir !== '\\' && $sdir !== '/' && $requri !== $sname && $requri !== $sdir) {
                $sdir = rtrim($sdir, '/');
                $requri = substr($requri, strlen($sdir));
            }

            $this->pathinfo = $requri;
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

            if (preg_match('#/RESERVED\.TEENY\-(\d{3})\.html$#', $_SERVER['PHP_SELF'], $match)) {
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
            $path = '/' . ltrim($path, '/');

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
     * @return bool
     */
    public function exec()
    {
        $callback = null;
        $newCode = 0;
        $code = $this->status();

        if ($code === 200) {
            $path = $this->path();

            if (PHP_SAPI === 'cli-server' && $this->builtinFile()) {
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

            if ($newCode !== 0) {
                $this->status($newCode);
            }
        }

        if ($newCode !== 0 && isset($this->codes[$newCode])) {
            $callback = $this->codes[$newCode];
        }

        if ($callback) {
            $this->dispatch($callback, $newCode, null);
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

    private function params()
    {
        $method = $this->method;
        $pathinfo = $this->pathinfo;
        $patterns = $this->paramPatterns;
        $getParams = '#\\\\[<](.*?)(\\\\:(' . implode('|', array_keys($patterns)) . ')|)\\\\[>]#';

        foreach ($this->routes as $path => $value) {
            if (isset($value[$method]) && strpos($path, '<') !== false) {
                $path = preg_replace($getParams, '(?<$1><$3>)', preg_quote($path));
                $path = str_replace('<>)', '.*?)', $path);

                foreach ($patterns as $pattern => $regex) {
                    $path = str_replace('<' . $pattern . '>)', $regex . ')', $path);
                }

                if (preg_match('#^' . $path . '$#', $pathinfo, $params)) {
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
            TeenyLoader($this, $callback);
        } elseif ($code) {
            echo $callback($code);
        } elseif ($params !== null) {
            echo $callback($params);
        } else {
            echo $callback();
        }
    }

    private function builtinFile()
    {
        $path = $this->pathinfo;

        return (
            $path !== '/' &&
            strcasecmp($path, '/vendor') !== 0 &&
            stripos($path, '/vendor/') !== 0 &&
            is_file(__DIR__ . '/..' . $path)
        );
    }
}

/**
 * Require file
 *
 * @param Teeny $app Teeny (or custom) context
 * @param string $callback file required
 * @return mixed
 */
function TeenyLoader(Teeny $app, $callback)
{
    return require $callback;
}
