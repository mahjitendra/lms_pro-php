<?php

/**
 * Session Management Class
 * LMS Pro - Learning Management System
 */

class Session
{
    private $config;
    private $started = false;
    private $flashData = [];

    public function __construct($config = [])
    {
        $this->config = array_merge([
            'driver' => 'file',
            'lifetime' => 120,
            'path' => sys_get_temp_dir(),
            'cookie_name' => 'lms_pro_session',
            'cookie_path' => '/',
            'cookie_domain' => null,
            'cookie_secure' => false,
            'cookie_httponly' => true,
        ], $config);
    }

    /**
     * Start the session
     */
    public function start()
    {
        if ($this->started) {
            return true;
        }

        // Configure session settings
        ini_set('session.gc_maxlifetime', $this->config['lifetime'] * 60);
        ini_set('session.cookie_lifetime', $this->config['lifetime'] * 60);
        
        if ($this->config['driver'] === 'file') {
            ini_set('session.save_path', $this->config['path']);
        }

        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => $this->config['lifetime'] * 60,
            'path' => $this->config['cookie_path'],
            'domain' => $this->config['cookie_domain'],
            'secure' => $this->config['cookie_secure'],
            'httponly' => $this->config['cookie_httponly'],
            'samesite' => 'Lax'
        ]);

        // Set session name
        session_name($this->config['cookie_name']);

        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            $result = session_start();
            
            if ($result) {
                $this->started = true;
                $this->loadFlashData();
                $this->regenerateIfNeeded();
            }
            
            return $result;
        }

        $this->started = true;
        return true;
    }

    /**
     * Regenerate session ID if needed
     */
    private function regenerateIfNeeded()
    {
        $lastRegeneration = $this->get('_last_regeneration', 0);
        
        // Regenerate every 30 minutes for security
        if (time() - $lastRegeneration > 1800) {
            $this->regenerate();
        }
    }

    /**
     * Regenerate session ID
     */
    public function regenerate($deleteOld = true)
    {
        if (!$this->started) {
            $this->start();
        }

        session_regenerate_id($deleteOld);
        $this->set('_last_regeneration', time());
        
        return $this;
    }

    /**
     * Get session value
     */
    public function get($key, $default = null)
    {
        if (!$this->started) {
            $this->start();
        }

        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set session value
     */
    public function set($key, $value)
    {
        if (!$this->started) {
            $this->start();
        }

        $_SESSION[$key] = $value;
        return $this;
    }

    /**
     * Check if session has key
     */
    public function has($key)
    {
        if (!$this->started) {
            $this->start();
        }

        return isset($_SESSION[$key]);
    }

    /**
     * Remove session key
     */
    public function remove($key)
    {
        if (!$this->started) {
            $this->start();
        }

        unset($_SESSION[$key]);
        return $this;
    }

    /**
     * Get all session data
     */
    public function all()
    {
        if (!$this->started) {
            $this->start();
        }

        return $_SESSION;
    }

    /**
     * Clear all session data
     */
    public function clear()
    {
        if (!$this->started) {
            $this->start();
        }

        $_SESSION = [];
        return $this;
    }

    /**
     * Destroy session
     */
    public function destroy()
    {
        if (!$this->started) {
            return true;
        }

        $_SESSION = [];

        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        $result = session_destroy();
        $this->started = false;
        
        return $result;
    }

    /**
     * Set flash message
     */
    public function setFlash($key, $value)
    {
        if (!$this->started) {
            $this->start();
        }

        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }

        $_SESSION['_flash'][$key] = $value;
        return $this;
    }

    /**
     * Get flash message
     */
    public function getFlash($key, $default = null)
    {
        if (!$this->started) {
            $this->start();
        }

        $value = $this->flashData[$key] ?? $default;
        
        // Remove flash data after reading
        unset($this->flashData[$key]);
        
        return $value;
    }

    /**
     * Check if flash message exists
     */
    public function hasFlash($key)
    {
        return isset($this->flashData[$key]);
    }

    /**
     * Get all flash messages
     */
    public function getAllFlash()
    {
        $flash = $this->flashData;
        $this->flashData = [];
        return $flash;
    }

    /**
     * Keep flash data for next request
     */
    public function keepFlash($keys = null)
    {
        if (!$this->started) {
            $this->start();
        }

        if ($keys === null) {
            // Keep all flash data
            foreach ($this->flashData as $key => $value) {
                $this->setFlash($key, $value);
            }
        } else {
            // Keep specific keys
            if (is_string($keys)) {
                $keys = [$keys];
            }
            
            foreach ($keys as $key) {
                if (isset($this->flashData[$key])) {
                    $this->setFlash($key, $this->flashData[$key]);
                }
            }
        }
        
        return $this;
    }

    /**
     * Load flash data from session
     */
    private function loadFlashData()
    {
        if (isset($_SESSION['_flash'])) {
            $this->flashData = $_SESSION['_flash'];
            unset($_SESSION['_flash']);
        }
    }

    /**
     * Get session ID
     */
    public function getId()
    {
        if (!$this->started) {
            $this->start();
        }

        return session_id();
    }

    /**
     * Set session ID
     */
    public function setId($id)
    {
        if ($this->started) {
            throw new Exception('Cannot set session ID after session has started');
        }

        session_id($id);
        return $this;
    }

    /**
     * Get session name
     */
    public function getName()
    {
        return session_name();
    }

    /**
     * Set session name
     */
    public function setName($name)
    {
        if ($this->started) {
            throw new Exception('Cannot set session name after session has started');
        }

        session_name($name);
        return $this;
    }

    /**
     * Check if session is started
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * Get session save path
     */
    public function getSavePath()
    {
        return session_save_path();
    }

    /**
     * Set session save path
     */
    public function setSavePath($path)
    {
        if ($this->started) {
            throw new Exception('Cannot set save path after session has started');
        }

        session_save_path($path);
        return $this;
    }

    /**
     * Push value to session array
     */
    public function push($key, $value)
    {
        $array = $this->get($key, []);
        
        if (!is_array($array)) {
            $array = [$array];
        }
        
        $array[] = $value;
        $this->set($key, $array);
        
        return $this;
    }

    /**
     * Pull value from session (get and remove)
     */
    public function pull($key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->remove($key);
        return $value;
    }

    /**
     * Increment session value
     */
    public function increment($key, $amount = 1)
    {
        $value = $this->get($key, 0);
        $this->set($key, $value + $amount);
        return $this;
    }

    /**
     * Decrement session value
     */
    public function decrement($key, $amount = 1)
    {
        return $this->increment($key, -$amount);
    }

    /**
     * Get and increment session value
     */
    public function getAndIncrement($key, $amount = 1)
    {
        $value = $this->get($key, 0);
        $this->set($key, $value + $amount);
        return $value;
    }

    /**
     * Get CSRF token
     */
    public function getCsrfToken()
    {
        $token = $this->get('_csrf_token');
        
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $this->set('_csrf_token', $token);
        }
        
        return $token;
    }

    /**
     * Verify CSRF token
     */
    public function verifyCsrfToken($token)
    {
        $sessionToken = $this->get('_csrf_token');
        return $sessionToken && hash_equals($sessionToken, $token);
    }

    /**
     * Store previous URL
     */
    public function setPreviousUrl($url)
    {
        $this->set('_previous_url', $url);
        return $this;
    }

    /**
     * Get previous URL
     */
    public function getPreviousUrl($default = '/')
    {
        return $this->get('_previous_url', $default);
    }

    /**
     * Store intended URL (for redirecting after login)
     */
    public function setIntendedUrl($url)
    {
        $this->set('_intended_url', $url);
        return $this;
    }

    /**
     * Get intended URL
     */
    public function getIntendedUrl($default = '/')
    {
        return $this->pull('_intended_url', $default);
    }

    /**
     * Magic getter
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Magic setter
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Magic isset
     */
    public function __isset($key)
    {
        return $this->has($key);
    }

    /**
     * Magic unset
     */
    public function __unset($key)
    {
        $this->remove($key);
    }
}