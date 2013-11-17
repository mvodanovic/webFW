<?php

namespace WebFW\Core;

use ReflectionClass;
use WebFW\CMS\CMSLogin;
use WebFW\CMS\Controllers\User;
use WebFW\Core\Classes\BaseClass;

class Router extends BaseClass
{
    protected static $instance;
    protected static $class = null;
    protected $routeDefs = array();

    const ROUTE_VARIABLE_REGEX = '[a-zA-Z0-9]+';

    /**
     * @return Router
     */
    public static function getInstance()
    {
        if (static::$class === null) {
            static::$class = static::className();
        }

        if (!isset(static::$instance)) {
            static::$instance = new static::$class;
        }

        return static::$instance;
    }

    protected function __construct() {
        $this->routeDefs[] = array(
            'pattern' => 'cms',
            'route' => new Route(CMSLogin::className()),
            'variables' => array(
            ),
        );

        $this->routeDefs[] = array(
            'pattern' => 'cms/webfw/:ctl:/:action:',
            'route' => new Route(null, null, User::classNamespace()),
            'variables' => array(
            ),
        );
    }

    public function getRouteDefs()
    {
        return $this->routeDefs;
    }

    protected function getURLForRouteDef($routeDef, $controller, $action, $params, $amp, $encodeFunction)
    {
        $pattern = Config::get('General', 'rewriteBase') . $routeDef['pattern'];
        $route = &$routeDef['route'];
        $variables = &$routeDef['variables'];
        if ($params === null) {
            $params = array();
        }

        if ($route->namespace !== null) {
            if (strpos($controller, $route->namespace) !== 0) {
                return null;
            }

            $controller = substr($controller, strlen($route->namespace) + 1);
        }

        /// Check parameters with route
        if ($route->controller !== null) {
            if ($controller !== $route->controller) {
                return null;
            }
            $controller = null;
        }
        if ($route->action !== null) {
            if ($action !== $route->action) {
                return null;
            }
            $action = null;
        }
        foreach ($route->params as $key => $value) {
            if (!array_key_exists($key, $params) || $params[$key] !== $value) {
                return null;
            }
            unset($params[$key]);
        }

        /// Get all parameter names from the URI pattern
        if (!(preg_match_all('#:([a-zA-Z0-9]+):#', $pattern, $matches) > 0)) {
            return null;
        }
        $matches = $matches[1];

        /// For each URI parameter...
        foreach ($matches as &$param) {
            /// Get the parameter's required regex pattern and check it
            $paramPattern = array_key_exists($param, $variables) ? $variables[$param] : static::ROUTE_VARIABLE_REGEX;
            switch ($param) {
                case 'ctl':
                    if (!preg_match("#^$paramPattern$#", $controller)) {
                        return null;
                    }
                    break;
                case 'action':
                    if (!preg_match("#^$paramPattern$#", $action)) {
                        return null;
                    }
                    break;
                default:
                    if (!array_key_exists($param, $params)) {
                        return null;
                    }
                    if (!preg_match("#^$paramPattern$#", $params[$param])) {
                        return null;
                    }
                    break;
            }
        }

        /// Replace parameter placeholders in the URI pattern with actual values
        /// Those parameters which were injected are nullified
        $url = preg_replace_callback(
            "#:([a-zA-Z0-9]+):#",
            function($matches) use (&$controller, &$action, &$params, $encodeFunction) {
                switch ($matches[1]) {
                    case 'ctl':
                        $value = $encodeFunction($controller);
                        $controller = null;
                        break;
                    case 'action':
                        $value = $encodeFunction($action);
                        $action = null;
                        break;
                    default:
                        $value = $encodeFunction($params[$matches[1]]);
                        unset($params[$matches[1]]);
                        break;
                }

                return $value;
            },
            $pattern
        );

        /// For the remaining parameters which weren't injected, append the in the query string
        $urlParams = array();
        if ($controller !== null && $controller !== Config::get('General', 'defaultController')) {
            $urlParams[] = 'ctl=' . $encodeFunction($controller);
        }
        if ($action !== null && $action !== $controller::DEFAULT_ACTION_NAME) {
            $urlParams[] = 'action=' . $encodeFunction($action);
        }
        foreach ($params as $key => $value) {
            if ($key === '' || $value === '') {
                continue;
            }

            $urlParams[] = $encodeFunction($key) . '=' . $encodeFunction($value);
        }
        if (!empty($urlParams)) {
            $url .= '?' . implode($amp, $urlParams);
        }

        return $url;
    }

    public function URL($controller, $action = null, $params = array(), $escapeAmps = true, $rawurlencode = true)
    {
        /// Set the query param delimiter
        $amp = '&amp;';
        if ($escapeAmps !== true) {
            $amp = '&';
        }

        /// Set the function which will e used for escaping URI parameters
        $encodeFunction = 'rawurlencode';
        if ($rawurlencode !== true) {
            $encodeFunction = 'urlencode';
        }

        /// Setup empty parameters to their default values
        if ($controller === null) {
            $controller = Config::get('General', 'defaultController');
        }
        if (!class_exists($controller)) {
            return null;
        }
        if ($action === null) {
            $action = $controller::DEFAULT_ACTION_NAME;
        }

        /// Try to match the parameters with existing route definitions
        foreach ($this->routeDefs as &$routeDef) {
            $url = $this->getURLForRouteDef($routeDef, $controller, $action, $params, $amp, $encodeFunction);
            if ($url !== null) {
                return $url;
            }
        }

        /// Fallback, build the URL by appending parameters in the query string
        $urlParams = array();
        if ($controller !== Config::get('General', 'defaultController')) {
            $urlParams[] = 'ctl=' . $encodeFunction($controller);
        }
        if ($action !== $controller::DEFAULT_ACTION_NAME) {
            $urlParams[] = 'action=' . $encodeFunction($action);
        }
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                if ($key === '' || $value === '') {
                    continue;
                }

                $urlParams[] = $encodeFunction($key) . '=' . $encodeFunction($value);
            }
        }

        $url = Config::get('General', 'rewriteBase');
        if (!empty($urlParams)) {
            $url .= '?' . implode($amp, $urlParams);
        }

        return $url;
    }

    public function URLFromRoute(Route $route, $escapeAmps = true, $rawurlencode = true)
    {
        $controller = $route->controller;
        if ($route->namespace !== null) {
            $controller = $route->namespace . '\\' . $controller;
        }
        return $this->URL(
            $controller, $route->action, $route->params, $escapeAmps, $rawurlencode
        );
    }

    public static function getClass()
    {
        return static::$class;
    }

    public static function setClass($className, $forceNewInstance = false)
    {
        if (!class_exists($className)) {
            throw new Exception('Class ' . $className . ' doesn\'t exist');
        }

        $rc = new ReflectionClass($className);
        if (!($rc->newInstanceWithoutConstructor() instanceof Router)) {
            throw new Exception('Class ' . $className . ' is not an instance of WebFW\\Core\\Router');
        }

        static::$class = $className;

        if ($forceNewInstance === true) {
            static::$instance = null;
        }
    }

    final private function __clone() {}
}
