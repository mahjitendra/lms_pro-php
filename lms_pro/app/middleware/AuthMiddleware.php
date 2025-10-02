<?php

/**
 * Authentication Middleware
 * LMS Pro - Learning Management System
 */

class AuthMiddleware
{
    /**
     * Handle the request
     */
    public function handle($request, $response)
    {
        $auth = App::getInstance()->get('auth');
        $session = App::getInstance()->getSession();
        
        // Check if user is authenticated
        if (!$auth->check()) {
            // Store intended URL for redirect after login
            $session->setIntendedUrl($request->getUrl());
            
            if ($request->expectsJson()) {
                $response->json([
                    'success' => false,
                    'message' => 'Authentication required',
                    'redirect' => '/login'
                ], 401);
                $response->send();
                return false;
            } else {
                $session->setFlash('error', 'Please login to continue');
                $response->redirect('/login');
                $response->send();
                return false;
            }
        }
        
        return true;
    }
}