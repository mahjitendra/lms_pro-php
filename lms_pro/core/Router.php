<?php

namespace LmsPro\Core;

use Exception;

class Router
{
    protected $routes = [
        'GET' => [],
        'POST' => []
    ];

    /**
     * Load the routes from a file.
     *
     * @param string $file
     * @return self
     */
    public static function load($file)
    {
        $router = new static;
        require $file;
        return $router;
    }

    /**
     * Register a GET route.
     *
     * @param string $uri
     * @param string $controllerAction
     */
    public function get($uri, $controllerAction)
    {
        $this->routes['GET'][$this->prepareUri($uri)] = $controllerAction;
    }

    /**
     * Register a POST route.
     *
     * @param string $uri
     * @param string $controllerAction
     */
    public function post($uri, $controllerAction)
    {
        $this->routes['POST'][$this->prepareUri($uri)] = $controllerAction;
    }

    /**
     * Direct the request to the matched route's controller action.
     *
     * @param string $uri
     * @param string $requestType
     * @return mixed
     * @throws Exception
     */
    public function direct($uri, $requestType)
    {
        foreach ($this->routes[$requestType] as $route => $controllerAction) {
            $pattern = preg_replace('/:([a-zA-Z_]+)/', '(?<$1>[^/]+)', $route);
            $pattern = "#^$pattern$#";

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Handle both class@method strings and closures
                if (is_string($controllerAction)) {
                    return $this->callAction(...explode('@', $controllerAction), $params);
                }

                return call_user_func_array($controllerAction, $params);
            }
        }

        throw new Exception("No route defined for this URI: /{$uri}");
    }

    /**
     * Call the specified controller action.
     *
     * @param string $controller
     * @param string $action
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    protected function callAction($controller, $action, $params = [])
    {
        $controllerClass = "LmsPro\\App\\Controllers\\{$controller}";

        if (!class_exists($controllerClass)) {
            throw new Exception("Controller class {$controllerClass} not found.");
        }

        $controllerInstance = new $controllerClass;

        if (!method_exists($controllerInstance, $action)) {
            throw new Exception("{$controllerClass} does not respond to the {$action} action.");
        }

        return $controllerInstance->$action(...array_values($params));
    }

    /**
     * Prepare the URI by trimming slashes.
     *
     * @param string $uri
     * @return string
     */
    private function prepareUri($uri)
    {
        return trim($uri, '/');
    }
}