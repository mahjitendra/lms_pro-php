<?php

/**
 * Router Class
 * LMS Pro - Learning Management System
 */

class Router
{
    private $routes = [];
    private $middleware = [];
    private $groups = [];
    private $currentGroup = null;
    private $namedRoutes = [];

    public function __construct($config = [])
    {
        if (isset($config['routes'])) {
            $this->loadRoutes($config['routes']);
        }
        
        if (isset($config['middleware'])) {
            $this->middleware = $config['middleware'];
        }
        
        if (isset($config['groups'])) {
            $this->groups = $config['groups'];
        }
    }

    /**
     * Load routes from configuration
     */
    private function loadRoutes($routes)
    {
        foreach ($routes as $pattern => $handler) {
            $this->addRoute($pattern, $handler);
        }
    }

    /**
     * Add a route
     */
    public function addRoute($pattern, $handler, $name = null)
    {
        // Parse pattern for methods and URI
        $parts = explode(' ', $pattern, 2);
        
        if (count($parts) === 2) {
            $methods = explode('|', $parts[0]);
            $uri = $parts[1];
        } else {
            $methods = ['GET'];
            $uri = $parts[0];
        }
        
        // Apply current group settings
        if ($this->currentGroup) {
            $groupConfig = $this->groups[$this->currentGroup];
            
            if (isset($groupConfig['prefix'])) {
                $uri = '/' . trim($groupConfig['prefix'], '/') . '/' . ltrim($uri, '/');
            }
            
            if (isset($groupConfig['middleware'])) {
                $handler = [
                    'handler' => $handler,
                    'middleware' => $groupConfig['middleware']
                ];
            }
        }
        
        $route = [
            'methods' => array_map('strtoupper', $methods),
            'uri' => $uri,
            'handler' => $handler,
            'pattern' => $this->compilePattern($uri),
            'parameters' => $this->extractParameters($uri)
        ];
        
        $this->routes[] = $route;
        
        // Store named route
        if ($name) {
            $this->namedRoutes[$name] = $route;
        }
        
        return $this;
    }

    /**
     * Add GET route
     */
    public function get($uri, $handler, $name = null)
    {
        return $this->addRoute("GET {$uri}", $handler, $name);
    }

    /**
     * Add POST route
     */
    public function post($uri, $handler, $name = null)
    {
        return $this->addRoute("POST {$uri}", $handler, $name);
    }

    /**
     * Add PUT route
     */
    public function put($uri, $handler, $name = null)
    {
        return $this->addRoute("PUT {$uri}", $handler, $name);
    }

    /**
     * Add DELETE route
     */
    public function delete($uri, $handler, $name = null)
    {
        return $this->addRoute("DELETE {$uri}", $handler, $name);
    }

    /**
     * Add PATCH route
     */
    public function patch($uri, $handler, $name = null)
    {
        return $this->addRoute("PATCH {$uri}", $handler, $name);
    }

    /**
     * Add route for multiple methods
     */
    public function match($methods, $uri, $handler, $name = null)
    {
        $methodString = implode('|', $methods);
        return $this->addRoute("{$methodString} {$uri}", $handler, $name);
    }

    /**
     * Add route for all methods
     */
    public function any($uri, $handler, $name = null)
    {
        return $this->addRoute("GET|POST|PUT|DELETE|PATCH {$uri}", $handler, $name);
    }

    /**
     * Create route group
     */
    public function group($name, $callback)
    {
        $previousGroup = $this->currentGroup;
        $this->currentGroup = $name;
        
        $callback($this);
        
        $this->currentGroup = $previousGroup;
        
        return $this;
    }

    /**
     * Dispatch request
     */
    public function dispatch($request, $response)
    {
        $method = $request->getMethod();
        $uri = $request->getUri();
        
        // Find matching route
        $route = $this->findRoute($method, $uri);
        
        if (!$route) {
            $this->handleNotFound();
            return;
        }
        
        // Extract route parameters
        $parameters = $this->extractRouteParameters($route, $uri);
        
        // Execute middleware
        if (!$this->executeMiddleware($route, $request, $response)) {
            return;
        }
        
        // Execute route handler
        $this->executeHandler($route['handler'], $parameters, $request, $response);
    }

    /**
     * Find matching route
     */
    private function findRoute($method, $uri)
    {
        foreach ($this->routes as $route) {
            if (in_array($method, $route['methods']) && preg_match($route['pattern'], $uri)) {
                return $route;
            }
        }
        
        return null;
    }

    /**
     * Compile URI pattern to regex
     */
    private function compilePattern($uri)
    {
        // Escape forward slashes
        $pattern = str_replace('/', '\/', $uri);
        
        // Replace parameter placeholders with regex
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '([^\/]+)', $pattern);
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\?\}/', '([^\/]*)', $pattern);
        
        return '/^' . $pattern . '$/';
    }

    /**
     * Extract parameter names from URI
     */
    private function extractParameters($uri)
    {
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\??\}/', $uri, $matches);
        return $matches[1];
    }

    /**
     * Extract parameter values from URI
     */
    private function extractRouteParameters($route, $uri)
    {
        preg_match($route['pattern'], $uri, $matches);
        array_shift($matches); // Remove full match
        
        $parameters = [];
        foreach ($route['parameters'] as $i => $name) {
            $parameters[$name] = $matches[$i] ?? null;
        }
        
        return $parameters;
    }

    /**
     * Execute middleware
     */
    private function executeMiddleware($route, $request, $response)
    {
        $middlewareList = [];
        
        // Get middleware from route
        if (is_array($route['handler']) && isset($route['handler']['middleware'])) {
            $middlewareList = array_merge($middlewareList, $route['handler']['middleware']);
        }
        
        // Execute middleware
        foreach ($middlewareList as $middlewareName) {
            if (isset($this->middleware[$middlewareName])) {
                $middlewareClass = $this->middleware[$middlewareName];
                
                if (class_exists($middlewareClass)) {
                    $middleware = new $middlewareClass();
                    
                    if (method_exists($middleware, 'handle')) {
                        $result = $middleware->handle($request, $response);
                        
                        if ($result === false) {
                            return false;
                        }
                    }
                }
            }
        }
        
        return true;
    }

    /**
     * Execute route handler
     */
    private function executeHandler($handler, $parameters, $request, $response)
    {
        // If handler is array with middleware, extract actual handler
        if (is_array($handler) && isset($handler['handler'])) {
            $handler = $handler['handler'];
        }
        
        if (is_string($handler)) {
            // Handle controller@method format
            if (strpos($handler, '@') !== false) {
                list($controllerName, $method) = explode('@', $handler);
                $this->executeControllerAction($controllerName, $method, $parameters);
            } else {
                // Handle controller/method format
                $parts = explode('/', $handler);
                if (count($parts) >= 2) {
                    $method = array_pop($parts);
                    $controllerName = implode('/', $parts) . 'Controller';
                    $this->executeControllerAction($controllerName, $method, $parameters);
                }
            }
        } elseif (is_callable($handler)) {
            // Handle closure
            call_user_func_array($handler, array_values($parameters));
        } else {
            throw new Exception("Invalid route handler");
        }
    }

    /**
     * Execute controller action
     */
    private function executeControllerAction($controllerName, $method, $parameters)
    {
        // Convert controller path to class name
        $controllerClass = str_replace('/', '\\', $controllerName);
        
        // Try to load controller file
        $controllerFile = __DIR__ . "/../controllers/{$controllerName}.php";
        
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
        }
        
        // Get just the class name without namespace
        $className = basename(str_replace('\\', '/', $controllerClass));
        
        if (!class_exists($className)) {
            throw new Exception("Controller class not found: {$className}");
        }
        
        $controller = new $className();
        
        if (!method_exists($controller, $method)) {
            throw new Exception("Method '{$method}' not found in controller '{$className}'");
        }
        
        // Call the controller method
        $result = $controller->callAction($method, array_values($parameters));
        
        // Output result if it's a string
        if (is_string($result)) {
            echo $result;
        }
    }

    /**
     * Handle 404 Not Found
     */
    private function handleNotFound()
    {
        http_response_code(404);
        
        // Try to load 404 error page
        $errorFile = __DIR__ . '/../views/errors/404.php';
        if (file_exists($errorFile)) {
            include $errorFile;
        } else {
            echo "<h1>404 - Page Not Found</h1>";
        }
    }

    /**
     * Generate URL for named route
     */
    public function route($name, $parameters = [])
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new Exception("Named route '{$name}' not found");
        }
        
        $route = $this->namedRoutes[$name];
        $uri = $route['uri'];
        
        // Replace parameters in URI
        foreach ($parameters as $key => $value) {
            $uri = str_replace('{' . $key . '}', $value, $uri);
            $uri = str_replace('{' . $key . '?}', $value, $uri);
        }
        
        // Remove optional parameters that weren't provided
        $uri = preg_replace('/\{[^}]+\?\}/', '', $uri);
        
        return rtrim(BASE_URL, '/') . '/' . ltrim($uri, '/');
    }

    /**
     * Generate URL
     */
    public function url($path = '')
    {
        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Get current route
     */
    public function getCurrentRoute()
    {
        $request = App::getInstance()->getRequest();
        return $this->findRoute($request->getMethod(), $request->getUri());
    }

    /**
     * Check if current route matches pattern
     */
    public function is($pattern)
    {
        $request = App::getInstance()->getRequest();
        $uri = $request->getUri();
        
        // Convert pattern to regex
        $regex = str_replace('*', '.*', preg_quote($pattern, '/'));
        
        return preg_match('/^' . $regex . '$/', $uri);
    }

    /**
     * Get all routes
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Clear all routes
     */
    public function clear()
    {
        $this->routes = [];
        $this->namedRoutes = [];
        return $this;
    }
}