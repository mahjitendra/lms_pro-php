<?php

/**
 * Registration Controller
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Controller.php';

class RegisterController extends Controller
{
    /**
     * Show registration form
     */
    public function index()
    {
        // Check if registration is enabled
        if (!$this->isRegistrationEnabled()) {
            $this->setFlash('error', 'Registration is currently disabled.');
            return $this->redirect('/login');
        }
        
        // Redirect if already authenticated
        if ($this->auth->check()) {
            return $this->redirect('/student/dashboard');
        }
        
        $data = [
            'title' => 'Register - ' . APP_NAME,
            'csrf_token' => $this->session->getCsrfToken(),
            'terms_url' => '/terms',
            'privacy_url' => '/privacy'
        ];
        
        return $this->view('auth/register', $data, 'guest');
    }

    /**
     * Handle user registration
     */
    public function store()
    {
        // Check if registration is enabled
        if (!$this->isRegistrationEnabled()) {
            if ($this->request->isAjax()) {
                return $this->error('Registration is currently disabled.', [], 403);
            }
            
            $this->setFlash('error', 'Registration is currently disabled.');
            return $this->redirect('/login');
        }
        
        // Validate CSRF token
        $csrfToken = $this->input('csrf_token');
        if (!$this->session->verifyCsrfToken($csrfToken)) {
            if ($this->request->isAjax()) {
                return $this->error('Invalid security token', [], 403);
            }
            
            $this->setFlash('error', 'Invalid security token');
            return $this->redirect('/register');
        }
        
        // Validate input
        $data = $this->validate([
            'first_name' => $this->input('first_name'),
            'last_name' => $this->input('last_name'),
            'email' => $this->input('email'),
            'password' => $this->input('password'),
            'password_confirmation' => $this->input('password_confirmation'),
            'terms' => $this->input('terms'),
            'newsletter' => $this->input('newsletter', false)
        ], [
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'email' => 'required|email|max:191',
            'password' => 'required|min:8|max:128',
            'password_confirmation' => 'required|same:password',
            'terms' => 'required|boolean'
        ]);
        
        // Check if email already exists
        if ($this->emailExists($data['email'])) {
            if ($this->request->isAjax()) {
                return $this->error('Email address is already registered.', [
                    'email' => ['Email address is already registered.']
                ], 422);
            }
            
            $this->setFlash('error', 'Email address is already registered.');
            return $this->redirectBack();
        }
        
        // Check terms acceptance
        if (!$data['terms']) {
            if ($this->request->isAjax()) {
                return $this->error('You must accept the terms and conditions.', [
                    'terms' => ['You must accept the terms and conditions.']
                ], 422);
            }
            
            $this->setFlash('error', 'You must accept the terms and conditions.');
            return $this->redirectBack();
        }
        
        try {
            // Create user account
            $userId = $this->createUser($data);
            
            if ($userId) {
                // Assign default role
                $this->assignDefaultRole($userId);
                
                // Create user profile
                $this->createUserProfile($userId);
                
                // Send welcome email
                $this->sendWelcomeEmail($data['email'], $data['first_name']);
                
                // Log registration activity
                UserActivity::log($userId, 'registration', 'User registered');
                
                // Auto-login if email verification is not required
                if (!$this->requiresEmailVerification()) {
                    $this->auth->loginById($userId);
                    
                    if ($this->request->isAjax()) {
                        return $this->success('Registration successful! Welcome to ' . APP_NAME, [
                            'redirect_url' => '/student/dashboard'
                        ]);
                    }
                    
                    $this->setFlash('success', 'Registration successful! Welcome to ' . APP_NAME);
                    return $this->redirect('/student/dashboard');
                }
                
                // Email verification required
                $this->sendVerificationEmail($data['email'], $userId);
                
                if ($this->request->isAjax()) {
                    return $this->success('Registration successful! Please check your email to verify your account.');
                }
                
                $this->setFlash('success', 'Registration successful! Please check your email to verify your account.');
                return $this->redirect('/login');
            }
            
        } catch (Exception $e) {
            $this->log('Registration error: ' . $e->getMessage(), 'error');
            
            if ($this->request->isAjax()) {
                return $this->error('Registration failed. Please try again.', [], 500);
            }
            
            $this->setFlash('error', 'Registration failed. Please try again.');
            return $this->redirectBack();
        }
    }

    /**
     * Verify email address
     */
    public function verifyEmail($token)
    {
        if (!$token) {
            $this->setFlash('error', 'Invalid verification token.');
            return $this->redirect('/login');
        }
        
        // Find user by verification token
        $user = $this->database->table('users')
            ->where('email_verification_token', $token)
            ->whereNull('email_verified_at')
            ->first();
            
        if (!$user) {
            $this->setFlash('error', 'Invalid or expired verification token.');
            return $this->redirect('/login');
        }
        
        // Check if token is expired (24 hours)
        if (strtotime($user['created_at']) < strtotime('-24 hours')) {
            $this->setFlash('error', 'Verification token has expired. Please request a new one.');
            return $this->redirect('/resend-verification');
        }
        
        // Mark email as verified
        $this->database->update('users', [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'email_verification_token' => null,
            'status' => USER_STATUS_ACTIVE,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $user['id']]);
        
        // Log activity
        UserActivity::log($user['id'], 'email_verification', 'Email address verified');
        
        $this->setFlash('success', 'Email verified successfully! You can now login.');
        return $this->redirect('/login');
    }

    /**
     * Resend verification email
     */
    public function resendVerification()
    {
        if ($this->request->isPost()) {
            $email = $this->input('email');
            
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                if ($this->request->isAjax()) {
                    return $this->error('Please provide a valid email address.', [], 400);
                }
                
                $this->setFlash('error', 'Please provide a valid email address.');
                return $this->redirectBack();
            }
            
            $user = $this->database->table('users')
                ->where('email', $email)
                ->whereNull('email_verified_at')
                ->first();
                
            if ($user) {
                // Generate new verification token
                $token = bin2hex(random_bytes(32));
                
                $this->database->update('users', [
                    'email_verification_token' => $token,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $user['id']]);
                
                // Send verification email
                $this->sendVerificationEmail($email, $user['id'], $token);
            }
            
            // Always show success message for security
            if ($this->request->isAjax()) {
                return $this->success('If the email exists and is unverified, a verification link has been sent.');
            }
            
            $this->setFlash('success', 'If the email exists and is unverified, a verification link has been sent.');
            return $this->redirect('/login');
        }
        
        $data = [
            'title' => 'Resend Verification - ' . APP_NAME,
            'csrf_token' => $this->session->getCsrfToken()
        ];
        
        return $this->view('auth/resend-verification', $data, 'guest');
    }

    /**
     * Check if registration is enabled
     */
    private function isRegistrationEnabled()
    {
        // Check system setting
        $setting = $this->database->table('settings')
            ->where('key', 'registration_enabled')
            ->first();
            
        return $setting ? (bool)$setting['value'] : true;
    }

    /**
     * Check if email verification is required
     */
    private function requiresEmailVerification()
    {
        $setting = $this->database->table('settings')
            ->where('key', 'email_verification_required')
            ->first();
            
        return $setting ? (bool)$setting['value'] : false;
    }

    /**
     * Check if email already exists
     */
    private function emailExists($email)
    {
        return $this->database->table('users')
            ->where('email', $email)
            ->exists();
    }

    /**
     * Create new user account
     */
    private function createUser($data)
    {
        $userData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $this->auth->hashPassword($data['password']),
            'status' => $this->requiresEmailVerification() ? USER_STATUS_PENDING : USER_STATUS_ACTIVE,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Add verification token if required
        if ($this->requiresEmailVerification()) {
            $userData['email_verification_token'] = bin2hex(random_bytes(32));
        } else {
            $userData['email_verified_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->database->insert('users', $userData);
    }

    /**
     * Assign default role to user
     */
    private function assignDefaultRole($userId)
    {
        // Get default role (usually 'student')
        $defaultRole = $this->database->table('roles')
            ->where('is_default', 1)
            ->first();
            
        if (!$defaultRole) {
            // Fallback to student role
            $defaultRole = $this->database->table('roles')
                ->where('slug', 'student')
                ->first();
        }
        
        if ($defaultRole) {
            $this->database->insert('user_roles', [
                'user_id' => $userId,
                'role_id' => $defaultRole['id'],
                'assigned_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Create user profile
     */
    private function createUserProfile($userId)
    {
        $this->database->insert('user_profiles', [
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Send welcome email
     */
    private function sendWelcomeEmail($email, $firstName)
    {
        try {
            $subject = 'Welcome to ' . APP_NAME;
            $message = "
                <h2>Welcome to " . APP_NAME . "!</h2>
                <p>Hi {$firstName},</p>
                <p>Thank you for joining our learning platform. We're excited to have you on board!</p>
                <p>You can now:</p>
                <ul>
                    <li>Browse and enroll in courses</li>
                    <li>Track your learning progress</li>
                    <li>Earn certificates</li>
                    <li>Connect with other learners</li>
                </ul>
                <p>Get started by exploring our course catalog.</p>
                <p>Happy learning!</p>
                <p>The " . APP_NAME . " Team</p>
            ";
            
            Helper::sendEmail($email, $subject, $message);
            
        } catch (Exception $e) {
            $this->log('Failed to send welcome email: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Send email verification
     */
    private function sendVerificationEmail($email, $userId, $token = null)
    {
        try {
            if (!$token) {
                $user = $this->database->table('users')
                    ->where('id', $userId)
                    ->first();
                $token = $user['email_verification_token'];
            }
            
            $verificationUrl = $this->app->getConfig('app.url') . '/verify-email/' . $token;
            
            $subject = 'Verify Your Email Address - ' . APP_NAME;
            $message = "
                <h2>Verify Your Email Address</h2>
                <p>Thank you for registering with " . APP_NAME . "!</p>
                <p>Please click the link below to verify your email address:</p>
                <p><a href='{$verificationUrl}' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify Email Address</a></p>
                <p>Or copy and paste this URL into your browser:</p>
                <p>{$verificationUrl}</p>
                <p>This link will expire in 24 hours.</p>
                <p>If you didn't create an account with us, please ignore this email.</p>
                <p>The " . APP_NAME . " Team</p>
            ";
            
            Helper::sendEmail($email, $subject, $message);
            
        } catch (Exception $e) {
            $this->log('Failed to send verification email: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Check username availability (AJAX endpoint)
     */
    public function checkUsername()
    {
        if (!$this->request->isAjax()) {
            return $this->abort(404);
        }
        
        $username = $this->input('username');
        
        if (!$username) {
            return $this->json(['available' => false, 'message' => 'Username is required']);
        }
        
        if (strlen($username) < 3 || strlen($username) > 20) {
            return $this->json(['available' => false, 'message' => 'Username must be 3-20 characters']);
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return $this->json(['available' => false, 'message' => 'Username can only contain letters, numbers, and underscores']);
        }
        
        $exists = $this->database->table('users')
            ->where('username', $username)
            ->exists();
            
        return $this->json([
            'available' => !$exists,
            'message' => $exists ? 'Username is already taken' : 'Username is available'
        ]);
    }

    /**
     * Check email availability (AJAX endpoint)
     */
    public function checkEmail()
    {
        if (!$this->request->isAjax()) {
            return $this->abort(404);
        }
        
        $email = $this->input('email');
        
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['available' => false, 'message' => 'Please provide a valid email address']);
        }
        
        $exists = $this->emailExists($email);
        
        return $this->json([
            'available' => !$exists,
            'message' => $exists ? 'Email address is already registered' : 'Email address is available'
        ]);
    }
}