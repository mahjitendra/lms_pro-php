<?php

/**
 * CORS (Cross-Origin Resource Sharing) Middleware
 * LMS Pro - Learning Management System
 */

class CorsMiddleware
{
    private $allowedOrigins = ['*'];
    private $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    private $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-Token'];
    private $exposedHeaders = [];
    private $maxAge = 86400; // 24 hours
    private $allowCredentials = true;
    
    public function __construct($config = [])
    {
        if (isset($config['allowed_origins'])) {
            $this->allowedOrigins = $config['allowed_origins'];
        }
        
        if (isset($config['allowed_methods'])) {
            $this->allowedMethods = $config['allowed_methods'];
        }
        
        if (isset($config['allowed_headers'])) {
            $this->allowedHeaders = $config['allowed_headers'];
        }
        
        if (isset($config['exposed_headers'])) {
            $this->exposedHeaders = $config['exposed_headers'];
        }
        
        if (isset($config['max_age'])) {
            $this->maxAge = $config['max_age'];
        }
        
        if (isset($config['allow_credentials'])) {
            $this->allowCredentials = $config['allow_credentials'];
        }
    }
    
    /**
     * Handle the request
     */
    public function handle($request, $response)
    {
        $origin = $request->getHeader('Origin');
        
        // Check if origin is allowed
        if ($this->isOriginAllowed($origin)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin ?: '*');
        }
        
        // Set credentials header
        if ($this->allowCredentials) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }
        
        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            return $this->handlePreflightRequest($request, $response);
        }
        
        // Set exposed headers for actual requests
        if (!empty($this->exposedHeaders)) {
            $response->setHeader('Access-Control-Expose-Headers', implode(', ', $this->exposedHeaders));
        }
        
        return true;
    }
    
    /**
     * Handle preflight request
     */
    protected function handlePreflightRequest($request, $response)
    {
        // Set allowed methods
        $response->setHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
        
        // Set allowed headers
        $requestHeaders = $request->getHeader('Access-Control-Request-Headers');
        if ($requestHeaders) {
            $requestedHeaders = array_map('trim', explode(',', $requestHeaders));
            $allowedRequestHeaders = array_intersect($requestedHeaders, $this->allowedHeaders);
            
            if (!empty($allowedRequestHeaders)) {
                $response->setHeader('Access-Control-Allow-Headers', implode(', ', $allowedRequestHeaders));
            }
        } else {
            $response->setHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
        }
        
        // Set max age
        $response->setHeader('Access-Control-Max-Age', $this->maxAge);
        
        // Send empty response for preflight
        $response->setStatusCode(204);
        $response->setContent('');
        $response->send();
        
        return false; // Stop further processing
    }
    
    /**
     * Check if origin is allowed
     */
    protected function isOriginAllowed($origin)
    {
        if (in_array('*', $this->allowedOrigins)) {
            return true;
        }
        
        if (!$origin) {
            return false;
        }
        
        return in_array($origin, $this->allowedOrigins);
    }
}