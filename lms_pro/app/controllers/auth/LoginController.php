<?php

/**
 * Login Controller
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Controller.php';

class LoginController extends Controller
{
    /**
     * Show login form
     */
    public function index()
    {
        // Redirect if already authenticated
        if ($this->auth->check()) {
            return $this->redirectToDashboard();
        }
        
        $data = [
            'title' => 'Login - ' . APP_NAME,
            'csrf_token' => $this->session->getCsrfToken()
        ];
        
        return $this->view('auth/login', $data, 'guest');
    }

    /**
     * Handle login authentication
     */
    public function authenticate()
    {
        // Validate CSRF token
        $csrfToken = $this->input('csrf_token');
        if (!$this->session->verifyCsrfToken($csrfToken)) {
            if ($this->request->isAjax()) {
                return $this->error('Invalid security token', [], 403);
            }
            
            $this->setFlash('error', 'Invalid security token');
            return $this->redirect('/login');
        }
        
        // Validate input
        $credentials = $this->validate([
            'email' => $this->input('email'),
            'password' => $this->input('password'),
            'remember' => $this->input('remember', false)
        ], [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);
        
        // Check rate limiting
        if ($this->isRateLimited($credentials['email'])) {
            if ($this->request->isAjax()) {
                return $this->error('Too many login attempts. Please try again later.', [], 429);
            }
            
            $this->setFlash('error', 'Too many login attempts. Please try again later.');
            return $this->redirect('/login');
        }
        
        // Attempt authentication
        if ($this->auth->attempt($credentials, $credentials['remember'])) {
            $user = $this->auth->user();
            
            // Check if account is active
            if (!$user || $user['status'] !== USER_STATUS_ACTIVE) {
                $this->auth->logout();
                
                if ($this->request->isAjax()) {
                    return $this->error('Your account is not active. Please contact support.', [], 403);
                }
                
                $this->setFlash('error', 'Your account is not active. Please contact support.');
                return $this->redirect('/login');
            }
            
            // Log successful login
            UserActivity::logLogin($user['id'], true);
            
            // Clear rate limiting
            $this->clearRateLimit($credentials['email']);
            
            if ($this->request->isAjax()) {
                return $this->success('Login successful', [
                    'redirect_url' => $this->getRedirectUrl()
                ]);
            }
            
            $this->setFlash('success', 'Welcome back, ' . $user['first_name'] . '!');
            return $this->redirect($this->getRedirectUrl());
        }
        
        // Authentication failed
        $this->recordFailedAttempt($credentials['email']);
        
        if ($this->request->isAjax()) {
            return $this->error('Invalid email or password', [], 401);
        }
        
        $this->setFlash('error', 'Invalid email or password');
        return $this->redirect('/login');
    }

    /**
     * Handle logout
     */
    public function logout()
    {
        if ($this->auth->check()) {
            $userId = $this->auth->id();
            
            // Log logout activity
            UserActivity::logLogout($userId);
            
            // Logout user
            $this->auth->logout();
            
            $this->setFlash('success', 'You have been logged out successfully.');
        }
        
        return $this->redirect('/login');
    }

    /**
     * Get redirect URL after login
     */
    private function getRedirectUrl()
    {
        // Check for intended URL
        $intendedUrl = $this->session->getIntendedUrl();
        if ($intendedUrl && $intendedUrl !== '/login') {
            return $intendedUrl;
        }
        
        // Redirect based on user role
        $user = $this->auth->user();
        if (!$user) {
            return '/login';
        }
        
        $roles = $this->auth->getUserRoles();
        
        if (in_array('super_admin', $roles) || in_array('admin', $roles)) {
            return '/admin/dashboard';
        } elseif (in_array('instructor', $roles)) {
            return '/instructor/dashboard';
        } else {
            return '/student/dashboard';
        }
    }

    /**
     * Redirect to appropriate dashboard
     */
    private function redirectToDashboard()
    {
        return $this->redirect($this->getRedirectUrl());
    }

    /**
     * Check if IP is rate limited
     */
    private function isRateLimited($email)
    {
        $cacheKey = 'login_attempts_' . md5($email . $this->request->ip());
        $attempts = $this->cache($cacheKey);
        
        return $attempts && $attempts >= MAX_LOGIN_ATTEMPTS;
    }

    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt($email)
    {
        $cacheKey = 'login_attempts_' . md5($email . $this->request->ip());
        $attempts = $this->cache($cacheKey, 0) + 1;
        
        // Cache for lockout duration
        $this->cache($cacheKey, $attempts, LOCKOUT_DURATION);
        
        // Log failed attempt in database
        $this->database->insert('login_attempts', [
            'email' => $email,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'attempted_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Clear rate limiting for email/IP
     */
    private function clearRateLimit($email)
    {
        $cacheKey = 'login_attempts_' . md5($email . $this->request->ip());
        $this->cache($cacheKey, 0, 1); // Set to 0 with short expiry to clear
        
        // Clear from database
        $this->database->delete('login_attempts', 
            'email = :email AND ip_address = :ip', [
                'email' => $email,
                'ip' => $this->request->ip()
            ]
        );
    }

    /**
     * Show two-factor authentication form
     */
    public function twoFactor()
    {
        // Check if user is in 2FA verification state
        $userId = $this->session->get('2fa_user_id');
        if (!$userId) {
            return $this->redirect('/login');
        }
        
        $user = $this->database->table('users')
            ->where('id', $userId)
            ->where('two_factor_enabled', 1)
            ->first();
            
        if (!$user) {
            $this->session->remove('2fa_user_id');
            return $this->redirect('/login');
        }
        
        $data = [
            'title' => 'Two-Factor Authentication - ' . APP_NAME,
            'user' => $user,
            'csrf_token' => $this->session->getCsrfToken()
        ];
        
        return $this->view('auth/two-factor', $data, 'guest');
    }

    /**
     * Verify two-factor authentication code
     */
    public function verifyTwoFactor()
    {
        $userId = $this->session->get('2fa_user_id');
        if (!$userId) {
            if ($this->request->isAjax()) {
                return $this->error('Invalid session', [], 400);
            }
            return $this->redirect('/login');
        }
        
        // Validate CSRF token
        $csrfToken = $this->input('csrf_token');
        if (!$this->session->verifyCsrfToken($csrfToken)) {
            if ($this->request->isAjax()) {
                return $this->error('Invalid security token', [], 403);
            }
            
            $this->setFlash('error', 'Invalid security token');
            return $this->redirect('/two-factor');
        }
        
        // Validate input
        $data = $this->validate([
            'code' => $this->input('code')
        ], [
            'code' => 'required|numeric|min:6|max:6'
        ]);
        
        // Verify 2FA code
        if ($this->auth->verifyTwoFactorCode($userId, $data['code'])) {
            // Complete login
            $this->auth->loginById($userId);
            $this->session->remove('2fa_user_id');
            
            // Log successful login
            UserActivity::logLogin($userId, true);
            
            if ($this->request->isAjax()) {
                return $this->success('Authentication successful', [
                    'redirect_url' => $this->getRedirectUrl()
                ]);
            }
            
            $user = $this->auth->user();
            $this->setFlash('success', 'Welcome back, ' . $user['first_name'] . '!');
            return $this->redirect($this->getRedirectUrl());
        }
        
        // Invalid code
        if ($this->request->isAjax()) {
            return $this->error('Invalid authentication code', [], 401);
        }
        
        $this->setFlash('error', 'Invalid authentication code');
        return $this->redirect('/two-factor');
    }

    /**
     * Handle social login (placeholder for future implementation)
     */
    public function socialLogin($provider)
    {
        // This would integrate with OAuth providers like Google, Facebook, etc.
        // For now, just redirect to regular login
        $this->setFlash('info', 'Social login is not yet available. Please use email and password.');
        return $this->redirect('/login');
    }

    /**
     * Check login status (AJAX endpoint)
     */
    public function checkStatus()
    {
        if (!$this->request->isAjax()) {
            return $this->abort(404);
        }
        
        return $this->json([
            'authenticated' => $this->auth->check(),
            'user' => $this->auth->check() ? [
                'id' => $this->auth->id(),
                'name' => $this->auth->user()['first_name'] . ' ' . $this->auth->user()['last_name'],
                'email' => $this->auth->user()['email'],
                'roles' => $this->auth->getUserRoles()
            ] : null
        ]);
    }
}