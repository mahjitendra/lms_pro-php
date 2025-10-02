<?php

/**
 * HTTP Request Handler Class
 * LMS Pro - Learning Management System
 */

class Request
{
    private $method;
    private $uri;
    private $headers;
    private $query;
    private $post;
    private $files;
    private $server;
    private $cookies;
    private $input;
    private $json;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $this->parseUri();
        $this->headers = $this->parseHeaders();
        $this->query = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->cookies = $_COOKIE;
        $this->parseInput();
    }

    /**
     * Parse request URI
     */
    private function parseUri()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Remove script name from URI if present
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($scriptName && strpos($uri, $scriptName) === 0) {
            $uri = substr($uri, strlen($scriptName));
        }
        
        // Remove index.php if present
        $uri = preg_replace('/^\/index\.php/', '', $uri);
        
        return '/' . ltrim($uri, '/');
    }

    /**
     * Parse request headers
     */
    private function parseHeaders()
    {
        $headers = [];
        
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $header = str_replace('_', '-', $key);
                $headers[$header] = $value;
            }
        }
        
        return $headers;
    }

    /**
     * Parse input data
     */
    private function parseInput()
    {
        $this->input = array_merge($this->query, $this->post);
        
        // Parse JSON input for API requests
        if ($this->isJson()) {
            $jsonInput = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->json = $jsonInput;
                $this->input = array_merge($this->input, $jsonInput);
            }
        }
    }

    /**
     * Get request method
     */
    public function getMethod()
    {
        // Check for method override
        if ($this->method === 'POST') {
            $override = $this->input('_method');
            if ($override && in_array(strtoupper($override), ['PUT', 'PATCH', 'DELETE'])) {
                return strtoupper($override);
            }
        }
        
        return $this->method;
    }

    /**
     * Get request URI
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Get full URL
     */
    public function getUrl()
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $this->getHost();
        $port = $this->getPort();
        
        $url = $scheme . '://' . $host;
        
        if (($scheme === 'http' && $port != 80) || ($scheme === 'https' && $port != 443)) {
            $url .= ':' . $port;
        }
        
        $url .= $this->uri;
        
        if (!empty($this->query)) {
            $url .= '?' . http_build_query($this->query);
        }
        
        return $url;
    }

    /**
     * Get request host
     */
    public function getHost()
    {
        return $this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? 'localhost';
    }

    /**
     * Get request port
     */
    public function getPort()
    {
        return (int) ($this->server['SERVER_PORT'] ?? 80);
    }

    /**
     * Check if request is secure (HTTPS)
     */
    public function isSecure()
    {
        return (
            (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ||
            (!empty($this->server['HTTP_X_FORWARDED_PROTO']) && $this->server['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (!empty($this->server['HTTP_X_FORWARDED_SSL']) && $this->server['HTTP_X_FORWARDED_SSL'] === 'on') ||
            $this->getPort() === 443
        );
    }

    /**
     * Get header value
     */
    public function getHeader($name, $default = null)
    {
        $name = strtoupper(str_replace('-', '_', $name));
        return $this->headers[$name] ?? $default;
    }

    /**
     * Get all headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Check if header exists
     */
    public function hasHeader($name)
    {
        $name = strtoupper(str_replace('-', '_', $name));
        return isset($this->headers[$name]);
    }

    /**
     * Get input value
     */
    public function input($key = null, $default = null)
    {
        if ($key === null) {
            return $this->input;
        }
        
        return $this->input[$key] ?? $default;
    }

    /**
     * Get all input data
     */
    public function all()
    {
        return $this->input;
    }

    /**
     * Get only specified input fields
     */
    public function only($keys)
    {
        if (is_string($keys)) {
            $keys = [$keys];
        }
        
        $result = [];
        foreach ($keys as $key) {
            if (isset($this->input[$key])) {
                $result[$key] = $this->input[$key];
            }
        }
        
        return $result;
    }

    /**
     * Get all input except specified fields
     */
    public function except($keys)
    {
        if (is_string($keys)) {
            $keys = [$keys];
        }
        
        $result = $this->input;
        foreach ($keys as $key) {
            unset($result[$key]);
        }
        
        return $result;
    }

    /**
     * Check if input has key
     */
    public function has($key)
    {
        return isset($this->input[$key]);
    }

    /**
     * Check if input has keys
     */
    public function hasAny($keys)
    {
        if (is_string($keys)) {
            $keys = [$keys];
        }
        
        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if input is filled (not empty)
     */
    public function filled($key)
    {
        return $this->has($key) && !empty($this->input[$key]);
    }

    /**
     * Get query parameter
     */
    public function query($key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        
        return $this->query[$key] ?? $default;
    }

    /**
     * Get POST data
     */
    public function post($key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        
        return $this->post[$key] ?? $default;
    }

    /**
     * Get JSON data
     */
    public function json($key = null, $default = null)
    {
        if ($key === null) {
            return $this->json;
        }
        
        return $this->json[$key] ?? $default;
    }

    /**
     * Get uploaded file
     */
    public function file($key)
    {
        if (!isset($this->files[$key])) {
            return null;
        }
        
        $file = $this->files[$key];
        
        // Handle multiple files
        if (is_array($file['name'])) {
            $files = [];
            $count = count($file['name']);
            
            for ($i = 0; $i < $count; $i++) {
                $files[] = [
                    'name' => $file['name'][$i],
                    'type' => $file['type'][$i],
                    'tmp_name' => $file['tmp_name'][$i],
                    'error' => $file['error'][$i],
                    'size' => $file['size'][$i]
                ];
            }
            
            return $files;
        }
        
        return $file;
    }

    /**
     * Check if request has file
     */
    public function hasFile($key)
    {
        if (!isset($this->files[$key])) {
            return false;
        }
        
        $file = $this->files[$key];
        
        if (is_array($file['error'])) {
            return !empty(array_filter($file['error'], function($error) {
                return $error === UPLOAD_ERR_OK;
            }));
        }
        
        return $file['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get all uploaded files
     */
    public function allFiles()
    {
        return $this->files;
    }

    /**
     * Get cookie value
     */
    public function cookie($key = null, $default = null)
    {
        if ($key === null) {
            return $this->cookies;
        }
        
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get server variable
     */
    public function server($key = null, $default = null)
    {
        if ($key === null) {
            return $this->server;
        }
        
        return $this->server[$key] ?? $default;
    }

    /**
     * Get user agent
     */
    public function userAgent()
    {
        return $this->getHeader('USER_AGENT', '');
    }

    /**
     * Get client IP address
     */
    public function ip()
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($this->server[$key])) {
                $ips = explode(',', $this->server[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax()
    {
        return $this->getHeader('X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    /**
     * Check if request expects JSON
     */
    public function expectsJson()
    {
        return $this->isAjax() || $this->wantsJson();
    }

    /**
     * Check if request wants JSON response
     */
    public function wantsJson()
    {
        $acceptable = $this->getAcceptableContentTypes();
        return isset($acceptable[0]) && strpos($acceptable[0], 'json') !== false;
    }

    /**
     * Check if request is JSON
     */
    public function isJson()
    {
        $contentType = $this->getHeader('CONTENT_TYPE', '');
        return strpos($contentType, 'application/json') !== false;
    }

    /**
     * Get acceptable content types
     */
    public function getAcceptableContentTypes()
    {
        $accept = $this->getHeader('ACCEPT', '');
        
        if (!$accept) {
            return [];
        }
        
        $types = explode(',', $accept);
        $acceptable = [];
        
        foreach ($types as $type) {
            $type = trim($type);
            if (strpos($type, ';') !== false) {
                $type = substr($type, 0, strpos($type, ';'));
            }
            $acceptable[] = $type;
        }
        
        return $acceptable;
    }

    /**
     * Check request method
     */
    public function isMethod($method)
    {
        return strtoupper($this->getMethod()) === strtoupper($method);
    }

    /**
     * Check if GET request
     */
    public function isGet()
    {
        return $this->isMethod('GET');
    }

    /**
     * Check if POST request
     */
    public function isPost()
    {
        return $this->isMethod('POST');
    }

    /**
     * Check if PUT request
     */
    public function isPut()
    {
        return $this->isMethod('PUT');
    }

    /**
     * Check if PATCH request
     */
    public function isPatch()
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Check if DELETE request
     */
    public function isDelete()
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Get request path info
     */
    public function getPathInfo()
    {
        return $this->uri;
    }

    /**
     * Get request scheme
     */
    public function getScheme()
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Get HTTP protocol version
     */
    public function getProtocolVersion()
    {
        return $this->server['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
    }

    /**
     * Get content length
     */
    public function getContentLength()
    {
        return (int) $this->getHeader('CONTENT_LENGTH', 0);
    }

    /**
     * Get content type
     */
    public function getContentType()
    {
        return $this->getHeader('CONTENT_TYPE', '');
    }

    /**
     * Get raw input data
     */
    public function getRawInput()
    {
        return file_get_contents('php://input');
    }

    /**
     * Validate input data
     */
    public function validate($rules, $messages = [])
    {
        $validator = new Validator();
        return $validator->validate($this->all(), $rules, $messages);
    }

    /**
     * Get bearer token from Authorization header
     */
    public function bearerToken()
    {
        $header = $this->getHeader('AUTHORIZATION', '');
        
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Magic getter
     */
    public function __get($key)
    {
        return $this->input($key);
    }

    /**
     * Magic isset
     */
    public function __isset($key)
    {
        return $this->has($key);
    }
}