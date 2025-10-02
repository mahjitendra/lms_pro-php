<?php

/**
 * Core Application Class
 * LMS Pro - Learning Management System
 */

class App
{
    private static $instance = null;
    private $config = [];
    private $router;
    private $database;
    private $session;
    private $request;
    private $response;
    private $container = [];

    private function __construct()
    {
        $this->loadConfiguration();
        $this->initializeCore();
        $this->registerServices();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadConfiguration()
    {
        // Load configuration files
        $this->config = array_merge(
            require_once __DIR__ . '/../config/config.php',
            [
                'database' => require_once __DIR__ . '/../config/database.php',
                'routes' => require_once __DIR__ . '/../config/routes.php',
                'ai' => require_once __DIR__ . '/../config/ai_config.php'
            ]
        );

        // Load constants
        require_once __DIR__ . '/../config/constants.php';
    }

    private function initializeCore()
    {
        // Set error reporting based on environment
        if ($this->config['app']['debug']) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }

        // Set timezone
        date_default_timezone_set($this->config['app']['timezone']);

        // Initialize core components
        $this->database = new Database($this->config['database']);
        $this->session = new Session($this->config['session']);
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->config['routes']);
    }

    private function registerServices()
    {
        // Register core services in container
        $this->container['config'] = $this->config;
        $this->container['database'] = $this->database;
        $this->container['session'] = $this->session;
        $this->container['request'] = $this->request;
        $this->container['response'] = $this->response;
        $this->container['router'] = $this->router;

        // Register additional services
        $this->container['auth'] = new Auth($this->database, $this->session);
        $this->container['validator'] = new Validator();
        $this->container['helper'] = new Helper();
    }

    public function run()
    {
        try {
            // Start session
            $this->session->start();

            // Handle the request
            $this->router->dispatch($this->request, $this->response);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function get($service)
    {
        if (isset($this->container[$service])) {
            return $this->container[$service];
        }
        throw new Exception("Service '{$service}' not found in container");
    }

    public function set($service, $instance)
    {
        $this->container[$service] = $instance;
    }

    public function getConfig($key = null)
    {
        if ($key === null) {
            return $this->config;
        }

        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return null;
            }
        }

        return $value;
    }

    public function getDatabase()
    {
        return $this->database;
    }

    public function getSession()
    {
        return $this->session;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getRouter()
    {
        return $this->router;
    }

    private function handleException($exception)
    {
        // Log the exception
        $this->logError($exception);

        // Show appropriate error page based on environment
        if ($this->config['app']['debug']) {
            $this->showDebugError($exception);
        } else {
            $this->showProductionError();
        }
    }

    private function logError($exception)
    {
        $logFile = LOG_PATH . '/error.log';
        $message = sprintf(
            "[%s] %s in %s:%d\nStack trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    }

    private function showDebugError($exception)
    {
        http_response_code(500);
        echo "<h1>Application Error</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<h3>Stack Trace:</h3>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    }

    private function showProductionError()
    {
        http_response_code(500);
        include __DIR__ . '/../views/errors/500.php';
    }

    public function redirect($url, $statusCode = 302)
    {
        header("Location: $url", true, $statusCode);
        exit;
    }

    public function abort($statusCode, $message = '')
    {
        http_response_code($statusCode);
        
        switch ($statusCode) {
            case 404:
                include __DIR__ . '/../views/errors/404.php';
                break;
            case 403:
                include __DIR__ . '/../views/errors/403.php';
                break;
            default:
                include __DIR__ . '/../views/errors/500.php';
                break;
        }
        exit;
    }

    public function loadModel($modelName)
    {
        $modelFile = __DIR__ . "/../models/{$modelName}.php";
        if (file_exists($modelFile)) {
            require_once $modelFile;
            return new $modelName($this->database);
        }
        throw new Exception("Model '{$modelName}' not found");
    }

    public function loadController($controllerName)
    {
        $controllerFile = __DIR__ . "/../controllers/{$controllerName}.php";
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            return new $controllerName();
        }
        throw new Exception("Controller '{$controllerName}' not found");
    }

    public function loadLibrary($libraryName)
    {
        $libraryFile = __DIR__ . "/../libraries/{$libraryName}.php";
        if (file_exists($libraryFile)) {
            require_once $libraryFile;
            return new $libraryName();
        }
        throw new Exception("Library '{$libraryName}' not found");
    }

    public function loadHelper($helperName)
    {
        $helperFile = __DIR__ . "/../helpers/{$helperName}.php";
        if (file_exists($helperFile)) {
            require_once $helperFile;
        } else {
            throw new Exception("Helper '{$helperName}' not found");
        }
    }

    public function cache($key, $value = null, $expiry = 3600)
    {
        static $cache = null;
        
        if ($cache === null) {
            $cache = [];
        }

        if ($value === null) {
            // Get from cache
            if (isset($cache[$key])) {
                $item = $cache[$key];
                if ($item['expiry'] > time()) {
                    return $item['value'];
                } else {
                    unset($cache[$key]);
                }
            }
            return null;
        } else {
            // Set to cache
            $cache[$key] = [
                'value' => $value,
                'expiry' => time() + $expiry
            ];
            return $value;
        }
    }

    public function __destruct()
    {
        // Cleanup resources if needed
    }
}