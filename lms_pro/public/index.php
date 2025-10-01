<?php

// Define the root path of the application
define('LMS_PRO_ROOT', dirname(__DIR__));

// Load the bootstrap file to initialize the application
require LMS_PRO_ROOT . '/lms_pro/core/bootstrap.php';

use LmsPro\Core\Router;
use LmsPro\Core\Request;

// Load the defined routes and direct the request to the appropriate controller
try {
    Router::load(LMS_PRO_ROOT . '/lms_pro/app/routes.php')
        ->direct(Request::uri(), Request::method());
} catch (Exception $e) {
    // In a real application, you would have a more sophisticated error handler
    // that logs the error and shows a user-friendly error page.
    // For now, we will just display the exception message for debugging.
    http_response_code(500);
    echo '<h1>Application Error</h1>';
    echo '<p>' . $e->getMessage() . '</p>';
}