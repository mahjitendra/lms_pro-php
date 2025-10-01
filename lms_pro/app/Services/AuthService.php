<?php

namespace LmsPro\App\Services;

use LmsPro\App\Models\User;

class AuthService
{
    public function __construct()
    {
        // Start session if it's not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Attempt to authenticate a user with email and password.
     *
     * @param string $email
     * @param string $password
     * @return bool
     */
    public function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);

        if (!$user) {
            return false;
        }

        if (password_verify($password, $user->password)) {
            $this->login($user);
            return true;
        }

        return false;
    }

    /**
     * Log a user in by setting their session.
     *
     * @param User $user
     */
    public function login(User $user): void
    {
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->id;
    }

    /**
     * Log the current user out.
     */
    public function logout(): void
    {
        session_unset();
        session_destroy();
    }

    /**
     * Check if a user is currently authenticated.
     *
     * @return bool
     */
    public function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get the currently authenticated user.
     *
     * @return User|null
     */
    public function user(): ?User
    {
        if ($this->check()) {
            return User::find($_SESSION['user_id']);
        }
        return null;
    }
}