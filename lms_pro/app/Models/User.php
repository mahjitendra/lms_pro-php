<?php

namespace LmsPro\App\Models;

use LmsPro\Core\Model;

class User extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected static $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Hash the user's password upon setting it.
     *
     * @param string $password
     */
    public function setPasswordAttribute($password)
    {
        // Ensure the password is not empty and hash it using BCRYPT
        if (!empty($password)) {
            $this->attributes['password'] = password_hash($password, PASSWORD_BCRYPT);
        }
    }

    /**
     * Find a user by their email address.
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail(string $email)
    {
        $stmt = static::db()->prepare("SELECT * FROM " . static::$table . " WHERE email = ?");
        $stmt->execute([$email]);
        $attributes = $stmt->fetch();

        return $attributes ? new static($attributes) : null;
    }
}