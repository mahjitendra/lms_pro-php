<?php

namespace LmsPro\App\Controllers\Auth;

use LmsPro\Core\Controller;
use LmsPro\App\Models\User;
use LmsPro\Core\Request;

class RegisterController extends Controller
{
    /**
     * Show the user registration form.
     */
    public function create()
    {
        $this->view('auth.register');
    }

    /**
     * Handle the user registration request.
     */
    public function store()
    {
        $name = Request::get('name');
        $email = Request::get('email');
        $password = Request::get('password');

        // Basic validation - a dedicated validator class would be better for a real app.
        if (empty($name) || empty($email) || empty($password)) {
            // Set a flash message for the user
            // For now, we just redirect. A session service would handle this.
            return $this->redirect('/register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Handle invalid email
            return $this->redirect('/register');
        }

        // Check if user already exists
        if (User::findByEmail($email)) {
            // Handle existing user error
            return $this->redirect('/register');
        }

        // Create the new user
        $user = new User();
        $user->fill([
            'name' => $name,
            'email' => $email,
            'password' => $password // The model's mutator will hash this
        ]);
        $user->save();

        // Redirect to login page with a success message
        // A session service would be used for flash messages.
        return $this->redirect('/login');
    }
}