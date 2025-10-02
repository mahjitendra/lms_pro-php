<?php

/**
 * User Role Pivot Model
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Model.php';

class UserRole extends Model
{
    protected $table = 'user_roles';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'user_id', 'role_id', 'assigned_by', 'assigned_at', 'expires_at'
    ];
    
    protected $casts = [
        'user_id' => 'integer',
        'role_id' => 'integer',
        'assigned_by' => 'integer'
    ];
    
    protected $dates = [
        'assigned_at', 'expires_at'
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->database->table('users')
            ->where('id', $this->user_id)
            ->first();
    }

    /**
     * Get the role
     */
    public function role()
    {
        return $this->database->table('roles')
            ->where('id', $this->role_id)
            ->first();
    }

    /**
     * Get who assigned this role
     */
    public function assignedBy()
    {
        if (!$this->assigned_by) {
            return null;
        }
        
        return $this->database->table('users')
            ->where('id', $this->assigned_by)
            ->first();
    }

    /**
     * Check if role assignment is expired
     */
    public function isExpired()
    {
        if (!$this->expires_at) {
            return false;
        }
        
        return strtotime($this->expires_at) < time();
    }

    /**
     * Check if role assignment is active
     */
    public function isActive()
    {
        return !$this->isExpired();
    }
}