<?php

// Ensure the root path is defined
if (!defined('LMS_PRO_ROOT')) {
    define('LMS_PRO_ROOT', dirname(__DIR__));
}

// Load Composer autoloader
require LMS_PRO_ROOT . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables from .env file
if (file_exists(LMS_PRO_ROOT . '/.env')) {
    $dotenv = Dotenv::createImmutable(LMS_PRO_ROOT);
    $dotenv->load();
}

// Set error reporting based on APP_DEBUG environment variable
if (getenv('APP_DEBUG') === 'true') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Load core framework files
require_once LMS_PRO_ROOT . '/lms_pro/core/Request.php';
require_once LMS_PRO_ROOT . '/lms_pro/core/Response.php';
require_once LMS_PRO_ROOT . '/lms_pro/core/Router.php';
require_once LMS_PRO_ROOT . '/lms_pro/core/Database.php';
require_once LMS_PRO_ROOT . '/lms_pro/core/Model.php';
require_once LMS_PRO_ROOT . '/lms_pro/core/Controller.php';
require_once LMS_PRO_ROOT . '/lms_pro/core/helpers.php';

/**
 * Globally accessible function to get configuration values.
 *
 * @param string $key The configuration key (e.g., 'app.name').
 * @param mixed $default The default value to return if the key is not found.
 * @return mixed
 */
function config($key, $default = null)
{
    $keys = explode('.', $key);
    $file = array_shift($keys);
    $configPath = LMS_PRO_ROOT . '/lms_pro/config/' . $file . '.php';

    if (!file_exists($configPath)) {
        return $default;
    }

    $config = require $configPath;

    foreach ($keys as $segment) {
        if (is_array($config) && array_key_exists($segment, $config)) {
            $config = $config[$segment];
        } else {
            return $default;
        }
    }

    return $config;
}