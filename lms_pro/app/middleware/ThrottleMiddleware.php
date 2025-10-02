<?php

/**
 * Rate Limiting Middleware
 * LMS Pro - Learning Management System
 */

class ThrottleMiddleware
{
    private $cacheFile;
    
    public function __construct()
    {
        $this->cacheFile = CACHE_PATH . '/throttle.json';
        
        // Ensure cache directory exists
        if (!is_dir(CACHE_PATH)) {
            mkdir(CACHE_PATH, 0755, true);
        }
    }
    
    /**
     * Handle the request
     */
    public function handle($request, $response, $maxAttempts = 60, $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);
        
        if ($this->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            return $this->buildResponse($request, $response, $key, $maxAttempts, $decayMinutes);
        }
        
        $this->hit($key, $decayMinutes);
        
        return true;
    }
    
    /**
     * Resolve request signature
     */
    protected function resolveRequestSignature($request)
    {
        $ip = $request->ip();
        $route = $request->getUri();
        
        return sha1($ip . '|' . $route);
    }
    
    /**
     * Determine if the given key has been "accessed" too many times
     */
    protected function tooManyAttempts($key, $maxAttempts, $decayMinutes)
    {
        $attempts = $this->attempts($key);
        
        if ($attempts >= $maxAttempts) {
            if ($this->availableAt($key, $decayMinutes) > time()) {
                return true;
            }
            
            $this->resetAttempts($key);
        }
        
        return false;
    }
    
    /**
     * Increment the counter for a given key for a given decay time
     */
    protected function hit($key, $decayMinutes = 1)
    {
        $cache = $this->getCache();
        
        if (!isset($cache[$key])) {
            $cache[$key] = [
                'attempts' => 0,
                'reset_time' => time() + ($decayMinutes * 60)
            ];
        }
        
        $cache[$key]['attempts']++;
        
        $this->saveCache($cache);
    }
    
    /**
     * Get the number of attempts for the given key
     */
    protected function attempts($key)
    {
        $cache = $this->getCache();
        
        if (!isset($cache[$key])) {
            return 0;
        }
        
        // Check if attempts have expired
        if ($cache[$key]['reset_time'] <= time()) {
            unset($cache[$key]);
            $this->saveCache($cache);
            return 0;
        }
        
        return $cache[$key]['attempts'];
    }
    
    /**
     * Reset the number of attempts for the given key
     */
    protected function resetAttempts($key)
    {
        $cache = $this->getCache();
        unset($cache[$key]);
        $this->saveCache($cache);
    }
    
    /**
     * Get the number of seconds until the "key" is accessible again
     */
    protected function availableAt($key, $decayMinutes)
    {
        $cache = $this->getCache();
        
        if (!isset($cache[$key])) {
            return time();
        }
        
        return $cache[$key]['reset_time'];
    }
    
    /**
     * Get cache data
     */
    protected function getCache()
    {
        if (!file_exists($this->cacheFile)) {
            return [];
        }
        
        $content = file_get_contents($this->cacheFile);
        $cache = json_decode($content, true);
        
        if (!is_array($cache)) {
            return [];
        }
        
        // Clean expired entries
        $now = time();
        foreach ($cache as $key => $data) {
            if ($data['reset_time'] <= $now) {
                unset($cache[$key]);
            }
        }
        
        return $cache;
    }
    
    /**
     * Save cache data
     */
    protected function saveCache($cache)
    {
        file_put_contents($this->cacheFile, json_encode($cache), LOCK_EX);
    }
    
    /**
     * Create a 'too many attempts' response
     */
    protected function buildResponse($request, $response, $key, $maxAttempts, $decayMinutes)
    {
        $retryAfter = $this->availableAt($key, $decayMinutes) - time();
        
        $response->setHeader('Retry-After', $retryAfter);
        $response->setHeader('X-RateLimit-Limit', $maxAttempts);
        $response->setHeader('X-RateLimit-Remaining', 0);
        $response->setHeader('X-RateLimit-Reset', $this->availableAt($key, $decayMinutes));
        
        if ($request->expectsJson()) {
            $response->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter
            ], 429);
        } else {
            $response->setStatusCode(429);
            $response->setContent('<h1>429 - Too Many Requests</h1><p>Please try again later.</p>');
        }
        
        $response->send();
        return false;
    }
}