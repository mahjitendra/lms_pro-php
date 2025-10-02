<?php

/**
 * API Authentication Middleware
 * LMS Pro - Learning Management System
 */

class ApiAuthMiddleware
{
    /**
     * Handle the request
     */
    public function handle($request, $response)
    {
        $token = $this->getTokenFromRequest($request);
        
        if (!$token) {
            return $this->unauthorized($response, 'API token required');
        }
        
        $user = $this->validateToken($token);
        
        if (!$user) {
            return $this->unauthorized($response, 'Invalid API token');
        }
        
        // Set authenticated user in session or request context
        $auth = App::getInstance()->get('auth');
        $auth->loginById($user['id']);
        
        return true;
    }
    
    /**
     * Get token from request
     */
    protected function getTokenFromRequest($request)
    {
        // Check Authorization header
        $token = $request->bearerToken();
        
        if ($token) {
            return $token;
        }
        
        // Check query parameter
        $token = $request->query('api_token');
        
        if ($token) {
            return $token;
        }
        
        // Check request body
        $token = $request->input('api_token');
        
        return $token;
    }
    
    /**
     * Validate API token
     */
    protected function validateToken($token)
    {
        $database = App::getInstance()->getDatabase();
        
        // Hash the token for comparison
        $hashedToken = hash('sha256', $token);
        
        // Find user by API token
        $user = $database->table('users')
            ->where('api_token', $hashedToken)
            ->where('status', USER_STATUS_ACTIVE)
            ->first();
        
        if (!$user) {
            return null;
        }
        
        // Update last API access
        $database->update('users', [
            'last_api_access' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $user['id']]);
        
        return $user;
    }
    
    /**
     * Generate API token for user
     */
    public static function generateApiToken($userId)
    {
        $database = App::getInstance()->getDatabase();
        
        // Generate random token
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);
        
        // Update user with new API token
        $database->update('users', [
            'api_token' => $hashedToken,
            'api_token_created' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $userId]);
        
        return $token;
    }
    
    /**
     * Revoke API token for user
     */
    public static function revokeApiToken($userId)
    {
        $database = App::getInstance()->getDatabase();
        
        $database->update('users', [
            'api_token' => null,
            'api_token_created' => null
        ], 'id = :id', ['id' => $userId]);
        
        return true;
    }
    
    /**
     * Handle unauthorized access
     */
    protected function unauthorized($response, $message)
    {
        $response->json([
            'success' => false,
            'message' => $message,
            'error' => 'unauthorized'
        ], 401);
        
        $response->send();
        return false;
    }
}