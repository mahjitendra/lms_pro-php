<?php

use LmsPro\Core\Router;
use LmsPro\App\Services\AuthService;

/** @var Router $router */

// A simple welcome route
$router->get('', function () {
    echo '<h1>Welcome to LMS Pro</h1><p>A functional, work-in-progress application.</p><a href="/login">Login</a> or <a href="/register">Register</a>.';
});

// --- Authentication Routes ---
$router->get('register', 'Auth\RegisterController@create');
$router->post('register', 'Auth\RegisterController@store');
$router->get('login', 'Auth\LoginController@create');
$router->post('login', 'Auth\LoginController@store');
$router->get('logout', 'Auth\LoginController@destroy');


// --- Protected Routes ---
// A simple dashboard route to demonstrate protected access.
$router->get('dashboard', function () {
    $auth = new AuthService();
    if (!$auth->check()) {
        // If the user is not logged in, redirect to the login page.
        header('Location: /login');
        exit();
    }

    $user = $auth->user();

    // In a real application, you would render a proper dashboard view.
    echo "<h1>Welcome to your dashboard, " . htmlspecialchars($user->name) . "!</h1>";
    echo '<p>From here, you can manage your courses.</p>';
    echo '<a href="/courses">Manage Courses</a> | <a href="/logout">Logout</a>';
});

// --- Course Management Routes ---
$router->get('courses', 'Course\CourseController@index');
$router->get('courses/create', 'Course\CourseController@create');
$router->post('courses/store', 'Course\CourseController@store');
$router->get('courses/edit/:id', 'Course\CourseController@edit');
$router->post('courses/update/:id', 'Course\CourseController@update');
$router->post('courses/delete/:id', 'Course\CourseController@destroy');