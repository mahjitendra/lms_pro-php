<?php

/**
 * Role-based Access Control Middleware
 * LMS Pro - Learning Management System
 */

class RoleMiddleware
{
    /**
     * Handle the request
     */
    public function handle($request, $response, $roles = [])
    {
        $auth = App::getInstance()->get('auth');
        
        // Check if user is authenticated
        if (!$auth->check()) {
            return $this->unauthorized($request, $response, 'Authentication required');
        }
        
        // If no specific roles required, just check authentication
        if (empty($roles)) {
            return true;
        }
        
        // Parse roles from route parameters or middleware config
        if (is_string($roles)) {
            $roles = explode(',', $roles);
        }
        
        // Check if user has required role
        if (!$auth->hasAnyRole($roles)) {
            return $this->forbidden($request, $response, 'Insufficient permissions');
        }
        
        return true;
    }
    
    /**
     * Handle unauthorized access
     */
    private function unauthorized($request, $response, $message)
    {
        if ($request->expectsJson()) {
            $response->json([
                'success' => false,
                'message' => $message,
                'redirect' => '/login'
            ], 401);
            $response->send();
        } else {
            $session = App::getInstance()->getSession();
            $session->setFlash('error', $message);
            $response->redirect('/login');
            $response->send();
        }
        
        return false;
    }
    
    /**
     * Handle forbidden access
     */
    private function forbidden($request, $response, $message)
    {
        if ($request->expectsJson()) {
            $response->json([
                'success' => false,
                'message' => $message
            ], 403);
            $response->send();
        } else {
            http_response_code(403);
            include __DIR__ . '/../views/errors/403.php';
        }
        
        return false;
    }
}