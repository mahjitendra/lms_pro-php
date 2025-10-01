<?php

namespace LmsPro\App\Controllers\Auth;

use LmsPro\Core\Controller;
use LmsPro\App\Models\User;
use LmsPro\Core\Request;
use LmsPro\App\Services\AuthService;

class LoginController extends Controller
{
    /**
     * @var AuthService
     */
    protected $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = new AuthService();
    }

    /**
     * Show the user login form.
     */
    public function create()
    {
        $this->view('auth.login');
    }

    /**
     * Handle the user login request.
     */
    public function store()
    {
        $email = Request::get('email');
        $password = Request::get('password');

        if ($this->auth->attempt($email, $password)) {
            // Authentication successful, redirect to the dashboard
            return $this->redirect('/dashboard');
        }

        // Authentication failed, redirect back to login
        // In a real app, you would use flash messages to show an error.
        return $this->redirect('/login');
    }

    /**
     * Log the user out and destroy the session.
     */
    public function destroy()
    {
        $this->auth->logout();
        return $this->redirect('/');
    }
}