<?php

/**
 * Authentication Class
 * LMS Pro - Learning Management System
 */

class Auth
{
    private $database;
    private $session;
    private $user = null;
    private $userModel = 'User';
    private $roleModel = 'Role';
    private $permissionModel = 'Permission';

    public function __construct($database, $session)
    {
        $this->database = $database;
        $this->session = $session;
        
        // Load user from session if logged in
        $this->loadUserFromSession();
    }

    /**
     * Load user from session
     */
    private function loadUserFromSession()
    {
        $userId = $this->session->get(SESSION_USER_ID);
        
        if ($userId) {
            $this->user = $this->database->table('users')
                ->where('id', $userId)
                ->where('status', USER_STATUS_ACTIVE)
                ->first();
                
            if (!$this->user) {
                $this->logout();
            }
        }
    }

    /**
     * Attempt to authenticate user
     */
    public function attempt($credentials, $remember = false)
    {
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;

        if (!$email || !$password) {
            return false;
        }

        // Find user by email
        $user = $this->database->table('users')
            ->where('email', $email)
            ->where('status', USER_STATUS_ACTIVE)
            ->first();

        if (!$user) {
            return false;
        }

        // Verify password
        if (!$this->verifyPassword($password, $user['password'])) {
            $this->recordFailedAttempt($email);
            return false;
        }

        // Check if account is locked
        if ($this->isAccountLocked($user)) {
            return false;
        }

        // Login successful
        $this->login($user, $remember);
        $this->clearFailedAttempts($email);
        $this->updateLastLogin($user['id']);

        return true;
    }

    /**
     * Login user
     */
    public function login($user, $remember = false)
    {
        $this->user = $user;
        
        // Store user ID in session
        $this->session->set(SESSION_USER_ID, $user['id']);
        $this->session->regenerate();

        // Set remember me cookie if requested
        if ($remember) {
            $this->setRememberToken($user['id']);
        }

        return true;
    }

    /**
     * Login user by ID
     */
    public function loginById($userId, $remember = false)
    {
        $user = $this->database->table('users')
            ->where('id', $userId)
            ->where('status', USER_STATUS_ACTIVE)
            ->first();

        if ($user) {
            return $this->login($user, $remember);
        }

        return false;
    }

    /**
     * Logout user
     */
    public function logout()
    {
        if ($this->user) {
            $this->clearRememberToken($this->user['id']);
        }

        $this->user = null;
        $this->session->remove(SESSION_USER_ID);
        $this->session->remove(SESSION_USER_ROLE);
        $this->session->remove(SESSION_USER_PERMISSIONS);
        $this->session->regenerate();

        return true;
    }

    /**
     * Check if user is authenticated
     */
    public function check()
    {
        return $this->user !== null;
    }

    /**
     * Check if user is guest (not authenticated)
     */
    public function guest()
    {
        return $this->user === null;
    }

    /**
     * Get authenticated user
     */
    public function user()
    {
        return $this->user;
    }

    /**
     * Get authenticated user ID
     */
    public function id()
    {
        return $this->user ? $this->user['id'] : null;
    }

    /**
     * Verify password
     */
    private function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Hash password
     */
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt($email)
    {
        $this->database->insert('login_attempts', [
            'email' => $email,
            'ip_address' => $this->getClientIp(),
            'attempted_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Clear failed login attempts
     */
    private function clearFailedAttempts($email)
    {
        $this->database->delete('login_attempts', 'email = :email', ['email' => $email]);
    }

    /**
     * Check if account is locked due to failed attempts
     */
    private function isAccountLocked($user)
    {
        $attempts = $this->database->table('login_attempts')
            ->where('email', $user['email'])
            ->where('attempted_at', '>', date('Y-m-d H:i:s', time() - LOCKOUT_DURATION))
            ->count();

        return $attempts >= MAX_LOGIN_ATTEMPTS;
    }

    /**
     * Update last login timestamp
     */
    private function updateLastLogin($userId)
    {
        $this->database->update('users', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $this->getClientIp()
        ], 'id = :id', ['id' => $userId]);
    }

    /**
     * Set remember me token
     */
    private function setRememberToken($userId)
    {
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);

        // Store hashed token in database
        $this->database->update('users', [
            'remember_token' => $hashedToken,
            'remember_expires' => date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)) // 30 days
        ], 'id = :id', ['id' => $userId]);

        // Set cookie
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
    }

    /**
     * Clear remember me token
     */
    private function clearRememberToken($userId)
    {
        $this->database->update('users', [
            'remember_token' => null,
            'remember_expires' => null
        ], 'id = :id', ['id' => $userId]);

        // Clear cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }

    /**
     * Attempt login via remember token
     */
    public function viaRemember()
    {
        $token = $_COOKIE['remember_token'] ?? null;

        if (!$token) {
            return false;
        }

        $hashedToken = hash('sha256', $token);

        $user = $this->database->table('users')
            ->where('remember_token', $hashedToken)
            ->where('remember_expires', '>', date('Y-m-d H:i:s'))
            ->where('status', USER_STATUS_ACTIVE)
            ->first();

        if ($user) {
            $this->login($user, true);
            return true;
        }

        return false;
    }

    /**
     * Check if user has role
     */
    public function hasRole($role)
    {
        if (!$this->user) {
            return false;
        }

        $userRoles = $this->getUserRoles();
        
        if (is_array($role)) {
            return !empty(array_intersect($role, $userRoles));
        }

        return in_array($role, $userRoles);
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole($roles)
    {
        return $this->hasRole($roles);
    }

    /**
     * Check if user has all of the given roles
     */
    public function hasAllRoles($roles)
    {
        if (!$this->user) {
            return false;
        }

        $userRoles = $this->getUserRoles();
        
        foreach ($roles as $role) {
            if (!in_array($role, $userRoles)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get user roles
     */
    public function getUserRoles()
    {
        if (!$this->user) {
            return [];
        }

        $roles = $this->session->get(SESSION_USER_ROLE);

        if ($roles === null) {
            $roles = $this->database->table('user_roles ur')
                ->join('roles r', 'ur.role_id = r.id')
                ->where('ur.user_id', $this->user['id'])
                ->select(['r.name'])
                ->get();

            $roles = array_column($roles, 'name');
            $this->session->set(SESSION_USER_ROLE, $roles);
        }

        return $roles;
    }

    /**
     * Check if user has permission
     */
    public function hasPermission($permission)
    {
        if (!$this->user) {
            return false;
        }

        $userPermissions = $this->getUserPermissions();
        
        if (is_array($permission)) {
            return !empty(array_intersect($permission, $userPermissions));
        }

        return in_array($permission, $userPermissions);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission($permissions)
    {
        return $this->hasPermission($permissions);
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions($permissions)
    {
        if (!$this->user) {
            return false;
        }

        $userPermissions = $this->getUserPermissions();
        
        foreach ($permissions as $permission) {
            if (!in_array($permission, $userPermissions)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions()
    {
        if (!$this->user) {
            return [];
        }

        $permissions = $this->session->get(SESSION_USER_PERMISSIONS);

        if ($permissions === null) {
            // Get permissions from roles
            $permissions = $this->database->table('user_roles ur')
                ->join('roles r', 'ur.role_id = r.id')
                ->join('role_permissions rp', 'r.id = rp.role_id')
                ->join('permissions p', 'rp.permission_id = p.id')
                ->where('ur.user_id', $this->user['id'])
                ->select(['p.name'])
                ->get();

            // Get direct user permissions
            $directPermissions = $this->database->table('user_permissions up')
                ->join('permissions p', 'up.permission_id = p.id')
                ->where('up.user_id', $this->user['id'])
                ->select(['p.name'])
                ->get();

            $allPermissions = array_merge($permissions, $directPermissions);
            $permissions = array_unique(array_column($allPermissions, 'name'));
            
            $this->session->set(SESSION_USER_PERMISSIONS, $permissions);
        }

        return $permissions;
    }

    /**
     * Assign role to user
     */
    public function assignRole($userId, $roleId)
    {
        // Check if role assignment already exists
        $exists = $this->database->table('user_roles')
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->exists();

        if (!$exists) {
            $this->database->insert('user_roles', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'assigned_at' => date('Y-m-d H:i:s')
            ]);

            // Clear cached roles and permissions
            if ($this->user && $this->user['id'] == $userId) {
                $this->session->remove(SESSION_USER_ROLE);
                $this->session->remove(SESSION_USER_PERMISSIONS);
            }
        }

        return true;
    }

    /**
     * Remove role from user
     */
    public function removeRole($userId, $roleId)
    {
        $this->database->delete('user_roles', 'user_id = :user_id AND role_id = :role_id', [
            'user_id' => $userId,
            'role_id' => $roleId
        ]);

        // Clear cached roles and permissions
        if ($this->user && $this->user['id'] == $userId) {
            $this->session->remove(SESSION_USER_ROLE);
            $this->session->remove(SESSION_USER_PERMISSIONS);
        }

        return true;
    }

    /**
     * Give permission to user
     */
    public function givePermission($userId, $permissionId)
    {
        // Check if permission assignment already exists
        $exists = $this->database->table('user_permissions')
            ->where('user_id', $userId)
            ->where('permission_id', $permissionId)
            ->exists();

        if (!$exists) {
            $this->database->insert('user_permissions', [
                'user_id' => $userId,
                'permission_id' => $permissionId,
                'assigned_at' => date('Y-m-d H:i:s')
            ]);

            // Clear cached permissions
            if ($this->user && $this->user['id'] == $userId) {
                $this->session->remove(SESSION_USER_PERMISSIONS);
            }
        }

        return true;
    }

    /**
     * Revoke permission from user
     */
    public function revokePermission($userId, $permissionId)
    {
        $this->database->delete('user_permissions', 'user_id = :user_id AND permission_id = :permission_id', [
            'user_id' => $userId,
            'permission_id' => $permissionId
        ]);

        // Clear cached permissions
        if ($this->user && $this->user['id'] == $userId) {
            $this->session->remove(SESSION_USER_PERMISSIONS);
        }

        return true;
    }

    /**
     * Generate password reset token
     */
    public function generatePasswordResetToken($email)
    {
        $user = $this->database->table('users')
            ->where('email', $email)
            ->where('status', USER_STATUS_ACTIVE)
            ->first();

        if (!$user) {
            return false;
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        $this->database->insert('password_resets', [
            'email' => $email,
            'token' => hash('sha256', $token),
            'expires_at' => $expires,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $token;
    }

    /**
     * Reset password using token
     */
    public function resetPassword($token, $password)
    {
        $hashedToken = hash('sha256', $token);

        $reset = $this->database->table('password_resets')
            ->where('token', $hashedToken)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->first();

        if (!$reset) {
            return false;
        }

        // Update user password
        $this->database->update('users', [
            'password' => $this->hashPassword($password),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'email = :email', ['email' => $reset['email']]);

        // Delete password reset record
        $this->database->delete('password_resets', 'token = :token', ['token' => $hashedToken]);

        return true;
    }

    /**
     * Get client IP address
     */
    private function getClientIp()
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
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Enable two-factor authentication
     */
    public function enableTwoFactor($userId)
    {
        $secret = $this->generateTwoFactorSecret();
        
        $this->database->update('users', [
            'two_factor_secret' => $secret,
            'two_factor_enabled' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $userId]);

        return $secret;
    }

    /**
     * Disable two-factor authentication
     */
    public function disableTwoFactor($userId)
    {
        $this->database->update('users', [
            'two_factor_secret' => null,
            'two_factor_enabled' => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $userId]);

        return true;
    }

    /**
     * Generate two-factor secret
     */
    private function generateTwoFactorSecret()
    {
        return base32_encode(random_bytes(20));
    }

    /**
     * Verify two-factor code
     */
    public function verifyTwoFactorCode($userId, $code)
    {
        $user = $this->database->table('users')
            ->where('id', $userId)
            ->where('two_factor_enabled', 1)
            ->first();

        if (!$user || !$user['two_factor_secret']) {
            return false;
        }

        // Here you would implement TOTP verification
        // For now, we'll just check if it's a 6-digit number
        return preg_match('/^\d{6}$/', $code);
    }
}

// Helper function for base32 encoding
if (!function_exists('base32_encode')) {
    function base32_encode($data) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;
        
        for ($i = 0, $j = strlen($data); $i < $j; $i++) {
            $v <<= 8;
            $v += ord($data[$i]);
            $vbits += 8;
            
            while ($vbits >= 5) {
                $vbits -= 5;
                $output .= $alphabet[$v >> $vbits];
                $v &= ((1 << $vbits) - 1);
            }
        }
        
        if ($vbits > 0) {
            $v <<= (5 - $vbits);
            $output .= $alphabet[$v];
        }
        
        return $output;
    }
}