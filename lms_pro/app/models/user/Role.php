<?php

/**
 * Role Model
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Model.php';

class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'name', 'slug', 'description', 'level', 'is_default', 'color'
    ];
    
    protected $casts = [
        'level' => 'integer',
        'is_default' => 'boolean'
    ];

    /**
     * Boot the model
     */
    protected function initialize()
    {
        // Generate slug from name if not provided
        $this->on('creating', function($data) {
            if (!isset($data['slug']) && isset($data['name'])) {
                $data['slug'] = Helper::slug($data['name']);
            }
        });
        
        $this->on('updating', function($data) {
            if (isset($data['name']) && !isset($data['slug'])) {
                $data['slug'] = Helper::slug($data['name']);
            }
        });
    }

    /**
     * Get users with this role
     */
    public function users()
    {
        return $this->database->table('user_roles ur')
            ->join('users u', 'ur.user_id = u.id')
            ->where('ur.role_id', $this->id)
            ->select(['u.*', 'ur.assigned_at'])
            ->get();
    }

    /**
     * Get permissions for this role
     */
    public function permissions()
    {
        return $this->database->table('role_permissions rp')
            ->join('permissions p', 'rp.permission_id = p.id')
            ->where('rp.role_id', $this->id)
            ->select(['p.*', 'rp.assigned_at'])
            ->get();
    }

    /**
     * Check if role has permission
     */
    public function hasPermission($permission)
    {
        $permissions = $this->permissions();
        
        foreach ($permissions as $rolePermission) {
            if ($rolePermission['name'] === $permission || $rolePermission['slug'] === $permission) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Assign permission to role
     */
    public function givePermission($permissionId)
    {
        // Check if permission is already assigned
        $exists = $this->database->table('role_permissions')
            ->where('role_id', $this->id)
            ->where('permission_id', $permissionId)
            ->exists();

        if (!$exists) {
            return $this->database->insert('role_permissions', [
                'role_id' => $this->id,
                'permission_id' => $permissionId,
                'assigned_at' => date('Y-m-d H:i:s')
            ]);
        }

        return true;
    }

    /**
     * Revoke permission from role
     */
    public function revokePermission($permissionId)
    {
        return $this->database->delete('role_permissions', 
            'role_id = :role_id AND permission_id = :permission_id', 
            [
                'role_id' => $this->id,
                'permission_id' => $permissionId
            ]
        );
    }

    /**
     * Sync permissions for role
     */
    public function syncPermissions($permissionIds)
    {
        // Remove all existing permissions
        $this->database->delete('role_permissions', 'role_id = :role_id', ['role_id' => $this->id]);
        
        // Add new permissions
        foreach ($permissionIds as $permissionId) {
            $this->givePermission($permissionId);
        }
        
        return true;
    }

    /**
     * Get users count for this role
     */
    public function getUsersCount()
    {
        return $this->database->table('user_roles')
            ->where('role_id', $this->id)
            ->count();
    }

    /**
     * Get permissions count for this role
     */
    public function getPermissionsCount()
    {
        return $this->database->table('role_permissions')
            ->where('role_id', $this->id)
            ->count();
    }

    /**
     * Check if role is system role (cannot be deleted)
     */
    public function isSystemRole()
    {
        $systemRoles = ['super_admin', 'admin', 'instructor', 'student'];
        return in_array($this->slug, $systemRoles);
    }

    /**
     * Get role hierarchy level
     */
    public function getHierarchyLevel()
    {
        $levels = [
            'super_admin' => 1,
            'admin' => 2,
            'instructor' => 3,
            'student' => 4,
            'guest' => 5
        ];
        
        return $levels[$this->slug] ?? $this->level ?? 999;
    }

    /**
     * Check if role has higher level than another role
     */
    public function isHigherThan($otherRole)
    {
        if (is_string($otherRole)) {
            $otherRole = self::findBySlug($otherRole);
        }
        
        if (!$otherRole) {
            return false;
        }
        
        return $this->getHierarchyLevel() < $otherRole->getHierarchyLevel();
    }

    /**
     * Check if role has lower level than another role
     */
    public function isLowerThan($otherRole)
    {
        if (is_string($otherRole)) {
            $otherRole = self::findBySlug($otherRole);
        }
        
        if (!$otherRole) {
            return false;
        }
        
        return $this->getHierarchyLevel() > $otherRole->getHierarchyLevel();
    }

    /**
     * Get default role
     */
    public static function getDefault()
    {
        $database = App::getInstance()->getDatabase();
        $roleData = $database->table('roles')
            ->where('is_default', 1)
            ->first();
            
        if ($roleData) {
            $role = new self($database);
            $role->fill($roleData);
            return $role;
        }
        
        // Fallback to student role
        return self::findBySlug('student');
    }

    /**
     * Find role by slug
     */
    public static function findBySlug($slug)
    {
        $database = App::getInstance()->getDatabase();
        $roleData = $database->table('roles')
            ->where('slug', $slug)
            ->first();
            
        if ($roleData) {
            $role = new self($database);
            $role->fill($roleData);
            return $role;
        }
        
        return null;
    }

    /**
     * Find role by name
     */
    public static function findByName($name)
    {
        $database = App::getInstance()->getDatabase();
        $roleData = $database->table('roles')
            ->where('name', $name)
            ->first();
            
        if ($roleData) {
            $role = new self($database);
            $role->fill($roleData);
            return $role;
        }
        
        return null;
    }

    /**
     * Get all system roles
     */
    public static function getSystemRoles()
    {
        $database = App::getInstance()->getDatabase();
        $systemSlugs = ['super_admin', 'admin', 'instructor', 'student', 'guest'];
        
        return $database->table('roles')
            ->whereIn('slug', $systemSlugs)
            ->orderBy('level', 'ASC')
            ->get();
    }

    /**
     * Get roles for select dropdown
     */
    public static function getForSelect($excludeSystemRoles = false)
    {
        $database = App::getInstance()->getDatabase();
        $query = $database->table('roles');
        
        if ($excludeSystemRoles) {
            $systemSlugs = ['super_admin', 'admin'];
            $query->whereNotIn('slug', $systemSlugs);
        }
        
        $roles = $query->orderBy('level', 'ASC')->get();
        
        $options = [];
        foreach ($roles as $role) {
            $options[$role['id']] = $role['name'];
        }
        
        return $options;
    }

    /**
     * Create default roles
     */
    public static function createDefaults()
    {
        $database = App::getInstance()->getDatabase();
        
        $defaultRoles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'description' => 'Full system access with all permissions',
                'level' => 1,
                'is_default' => 0,
                'color' => '#dc3545'
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrative access to manage system',
                'level' => 2,
                'is_default' => 0,
                'color' => '#fd7e14'
            ],
            [
                'name' => 'Instructor',
                'slug' => 'instructor',
                'description' => 'Can create and manage courses',
                'level' => 3,
                'is_default' => 0,
                'color' => '#198754'
            ],
            [
                'name' => 'Student',
                'slug' => 'student',
                'description' => 'Can enroll in and take courses',
                'level' => 4,
                'is_default' => 1,
                'color' => '#0d6efd'
            ],
            [
                'name' => 'Guest',
                'slug' => 'guest',
                'description' => 'Limited access to public content',
                'level' => 5,
                'is_default' => 0,
                'color' => '#6c757d'
            ]
        ];
        
        foreach ($defaultRoles as $roleData) {
            // Check if role already exists
            $exists = $database->table('roles')
                ->where('slug', $roleData['slug'])
                ->exists();
                
            if (!$exists) {
                $roleData['created_at'] = date('Y-m-d H:i:s');
                $roleData['updated_at'] = date('Y-m-d H:i:s');
                $database->insert('roles', $roleData);
            }
        }
        
        return true;
    }
}