<?php

/**
 * Base Controller Class
 * LMS Pro - Learning Management System
 */

abstract class Controller
{
    protected $app;
    protected $request;
    protected $response;
    protected $session;
    protected $database;
    protected $auth;
    protected $validator;
    protected $data = [];
    protected $middleware = [];

    public function __construct()
    {
        $this->app = App::getInstance();
        $this->request = $this->app->getRequest();
        $this->response = $this->app->getResponse();
        $this->session = $this->app->getSession();
        $this->database = $this->app->getDatabase();
        $this->auth = $this->app->get('auth');
        $this->validator = $this->app->get('validator');
        
        $this->initialize();
    }

    /**
     * Initialize method called after constructor
     * Override in child classes for custom initialization
     */
    protected function initialize()
    {
        // Override in child classes
    }

    /**
     * Load a model
     */
    protected function loadModel($modelName)
    {
        return $this->app->loadModel($modelName);
    }

    /**
     * Load a library
     */
    protected function loadLibrary($libraryName)
    {
        return $this->app->loadLibrary($libraryName);
    }

    /**
     * Load a helper
     */
    protected function loadHelper($helperName)
    {
        $this->app->loadHelper($helperName);
    }

    /**
     * Render a view
     */
    protected function view($viewName, $data = [], $layout = null)
    {
        $view = new View();
        return $view->render($viewName, array_merge($this->data, $data), $layout);
    }

    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200)
    {
        $this->response->setHeader('Content-Type', 'application/json');
        $this->response->setStatusCode($statusCode);
        return json_encode($data);
    }

    /**
     * Return success JSON response
     */
    protected function success($message = 'Success', $data = [], $statusCode = 200)
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Return error JSON response
     */
    protected function error($message = 'Error', $errors = [], $statusCode = 400)
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    /**
     * Redirect to URL
     */
    protected function redirect($url, $statusCode = 302)
    {
        $this->app->redirect($url, $statusCode);
    }

    /**
     * Redirect back to previous page
     */
    protected function redirectBack($fallback = '/')
    {
        $referer = $this->request->getHeader('HTTP_REFERER');
        $url = $referer ?: $fallback;
        $this->redirect($url);
    }

    /**
     * Abort with HTTP status code
     */
    protected function abort($statusCode, $message = '')
    {
        $this->app->abort($statusCode, $message);
    }

    /**
     * Validate request data
     */
    protected function validate($data, $rules, $messages = [])
    {
        $validation = $this->validator->validate($data, $rules, $messages);
        
        if (!$validation['valid']) {
            if ($this->request->isAjax()) {
                echo $this->error('Validation failed', $validation['errors'], 422);
                exit;
            } else {
                $this->session->setFlash('errors', $validation['errors']);
                $this->session->setFlash('old_input', $data);
                $this->redirectBack();
            }
        }
        
        return $validation['data'];
    }

    /**
     * Check if user is authenticated
     */
    protected function requireAuth()
    {
        if (!$this->auth->check()) {
            if ($this->request->isAjax()) {
                echo $this->error('Authentication required', [], 401);
                exit;
            } else {
                $this->session->setFlash('error', 'Please login to continue');
                $this->redirect('/login');
            }
        }
    }

    /**
     * Check if user has specific role
     */
    protected function requireRole($role)
    {
        $this->requireAuth();
        
        if (!$this->auth->hasRole($role)) {
            $this->abort(403, 'Insufficient permissions');
        }
    }

    /**
     * Check if user has specific permission
     */
    protected function requirePermission($permission)
    {
        $this->requireAuth();
        
        if (!$this->auth->hasPermission($permission)) {
            $this->abort(403, 'Insufficient permissions');
        }
    }

    /**
     * Get current authenticated user
     */
    protected function user()
    {
        return $this->auth->user();
    }

    /**
     * Get user ID
     */
    protected function userId()
    {
        return $this->auth->id();
    }

    /**
     * Set flash message
     */
    protected function setFlash($type, $message)
    {
        $this->session->setFlash($type, $message);
    }

    /**
     * Get flash message
     */
    protected function getFlash($type)
    {
        return $this->session->getFlash($type);
    }

    /**
     * Get request input
     */
    protected function input($key = null, $default = null)
    {
        return $this->request->input($key, $default);
    }

    /**
     * Get all request input
     */
    protected function all()
    {
        return $this->request->all();
    }

    /**
     * Get only specific input fields
     */
    protected function only($keys)
    {
        return $this->request->only($keys);
    }

    /**
     * Get all input except specific fields
     */
    protected function except($keys)
    {
        return $this->request->except($keys);
    }

    /**
     * Check if request has file
     */
    protected function hasFile($key)
    {
        return $this->request->hasFile($key);
    }

    /**
     * Get uploaded file
     */
    protected function file($key)
    {
        return $this->request->file($key);
    }

    /**
     * Get query parameter
     */
    protected function query($key = null, $default = null)
    {
        return $this->request->query($key, $default);
    }

    /**
     * Paginate results
     */
    protected function paginate($query, $perPage = null, $page = null)
    {
        $perPage = $perPage ?: $this->query('per_page', DEFAULT_PAGE_SIZE);
        $page = $page ?: $this->query('page', 1);
        
        $perPage = min($perPage, MAX_PAGE_SIZE);
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $total = $query->count();
        
        // Get paginated results
        $results = $query->limit($perPage, $offset)->get();
        
        return [
            'data' => $results,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1,
                'next_page' => $page < ceil($total / $perPage) ? $page + 1 : null,
                'prev_page' => $page > 1 ? $page - 1 : null,
            ]
        ];
    }

    /**
     * Cache data
     */
    protected function cache($key, $value = null, $expiry = 3600)
    {
        return $this->app->cache($key, $value, $expiry);
    }

    /**
     * Log message
     */
    protected function log($message, $level = 'info', $context = [])
    {
        $logFile = LOG_PATH . '/app.log';
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] {$level}: {$message}{$contextStr}\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Handle file upload
     */
    protected function uploadFile($file, $directory = 'uploads', $allowedTypes = null)
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed');
        }
        
        $allowedTypes = $allowedTypes ?: array_merge(
            ALLOWED_IMAGE_EXTENSIONS,
            ALLOWED_DOCUMENT_EXTENSIONS,
            ALLOWED_VIDEO_EXTENSIONS
        );
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('File type not allowed');
        }
        
        $filename = uniqid() . '.' . $extension;
        $uploadPath = UPLOAD_PATH . '/' . $directory;
        
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $filePath = $uploadPath . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        return [
            'filename' => $filename,
            'path' => $filePath,
            'url' => UPLOAD_URL . '/' . $directory . '/' . $filename,
            'size' => filesize($filePath),
            'type' => $file['type']
        ];
    }

    /**
     * Generate CSRF token
     */
    protected function generateCsrfToken()
    {
        $token = bin2hex(random_bytes(CSRF_TOKEN_LENGTH / 2));
        $this->session->set('csrf_token', $token);
        return $token;
    }

    /**
     * Verify CSRF token
     */
    protected function verifyCsrfToken($token)
    {
        $sessionToken = $this->session->get('csrf_token');
        return $sessionToken && hash_equals($sessionToken, $token);
    }

    /**
     * Apply middleware
     */
    public function middleware($middleware)
    {
        if (is_string($middleware)) {
            $middleware = [$middleware];
        }
        
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    /**
     * Execute middleware
     */
    public function executeMiddleware()
    {
        foreach ($this->middleware as $middlewareName) {
            $middlewareClass = $middlewareName . 'Middleware';
            
            if (class_exists($middlewareClass)) {
                $middleware = new $middlewareClass();
                $result = $middleware->handle($this->request, $this->response);
                
                if ($result === false) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Before action hook
     */
    protected function beforeAction($action)
    {
        // Override in child classes
        return true;
    }

    /**
     * After action hook
     */
    protected function afterAction($action, $result)
    {
        // Override in child classes
        return $result;
    }

    /**
     * Call controller action
     */
    public function callAction($action, $params = [])
    {
        if (!method_exists($this, $action)) {
            throw new Exception("Action '{$action}' not found in controller");
        }
        
        // Execute middleware
        if (!$this->executeMiddleware()) {
            return false;
        }
        
        // Before action hook
        if (!$this->beforeAction($action)) {
            return false;
        }
        
        // Call the action
        $result = call_user_func_array([$this, $action], $params);
        
        // After action hook
        $result = $this->afterAction($action, $result);
        
        return $result;
    }
}