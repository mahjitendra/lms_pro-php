<?php

/**
 * Application Entry Point
 * LMS Pro - Learning Management System
 */

// Define base path constant
define('BASEPATH', true);

// Start output buffering
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('UTC');

// Include autoloader (if using Composer)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Include core files
require_once __DIR__ . '/../app/core/App.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/View.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Request.php';
require_once __DIR__ . '/../app/core/Response.php';
require_once __DIR__ . '/../app/core/Validator.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Helper.php';

// Include middleware files
$middlewareFiles = glob(__DIR__ . '/../app/middleware/*.php');
foreach ($middlewareFiles as $file) {
    require_once $file;
}

// Include helper files
$helperFiles = glob(__DIR__ . '/../app/helpers/*.php');
foreach ($helperFiles as $file) {
    require_once $file;
}

// Include trait files
$traitFiles = glob(__DIR__ . '/../app/traits/*.php');
foreach ($traitFiles as $file) {
    require_once $file;
}

try {
    // Initialize and run the application
    $app = App::getInstance();
    
    // Handle remember me login
    $auth = $app->get('auth');
    if (!$auth->check()) {
        $auth->viaRemember();
    }
    
    // Run the application
    $app->run();
    
} catch (Exception $e) {
    // Handle uncaught exceptions
    http_response_code(500);
    
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<h1>Application Error</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<h3>Stack Trace:</h3>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        echo "<h1>500 - Internal Server Error</h1>";
        echo "<p>Something went wrong. Please try again later.</p>";
    }
    
    // Log the error
    $logFile = __DIR__ . '/../storage/logs/error.log';
    $message = sprintf(
        "[%s] %s in %s:%d\nStack trace:\n%s\n\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    
    if (is_dir(dirname($logFile))) {
        file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    }
}

// End output buffering and send response
ob_end_flush();