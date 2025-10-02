<?php

/**
 * Permission Model
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Model.php';

class Permission extends Model
{
    protected $table = 'permissions';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'name', 'slug', 'description', 'category', 'guard_name'
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
            
            if (!isset($data['guard_name'])) {
                $data['guard_name'] = 'web';
            }
        });
        
        $this->on('updating', function($data) {
            if (isset($data['name']) && !isset($data['slug'])) {
                $data['slug'] = Helper::slug($data['name']);
            }
        });
    }

    /**
     * Get roles that have this permission
     */
    public function roles()
    {
        return $this->database->table('role_permissions rp')
            ->join('roles r', 'rp.role_id = r.id')
            ->where('rp.permission_id', $this->id)
            ->select(['r.*', 'rp.assigned_at'])
            ->get();
    }

    /**
     * Get users that have this permission directly
     */
    public function users()
    {
        return $this->database->table('user_permissions up')
            ->join('users u', 'up.user_id = u.id')
            ->where('up.permission_id', $this->id)
            ->select(['u.*', 'up.assigned_at'])
            ->get();
    }

    /**
     * Get all users that have this permission (through roles or direct assignment)
     */
    public function getAllUsers()
    {
        // Users with direct permission
        $directUsers = $this->database->table('user_permissions up')
            ->join('users u', 'up.user_id = u.id')
            ->where('up.permission_id', $this->id)
            ->select(['u.id', 'u.first_name', 'u.last_name', 'u.email'])
            ->get();

        // Users with permission through roles
        $roleUsers = $this->database->table('role_permissions rp')
            ->join('user_roles ur', 'rp.role_id = ur.role_id')
            ->join('users u', 'ur.user_id = u.id')
            ->where('rp.permission_id', $this->id)
            ->select(['u.id', 'u.first_name', 'u.last_name', 'u.email'])
            ->get();

        // Merge and remove duplicates
        $allUsers = array_merge($directUsers, $roleUsers);
        $uniqueUsers = [];
        $seen = [];

        foreach ($allUsers as $user) {
            if (!in_array($user['id'], $seen)) {
                $uniqueUsers[] = $user;
                $seen[] = $user['id'];
            }
        }

        return $uniqueUsers;
    }

    /**
     * Get roles count that have this permission
     */
    public function getRolesCount()
    {
        return $this->database->table('role_permissions')
            ->where('permission_id', $this->id)
            ->count();
    }

    /**
     * Get users count that have this permission directly
     */
    public function getUsersCount()
    {
        return $this->database->table('user_permissions')
            ->where('permission_id', $this->id)
            ->count();
    }

    /**
     * Check if permission is system permission (cannot be deleted)
     */
    public function isSystemPermission()
    {
        $systemCategories = ['system', 'user_management', 'role_management'];
        return in_array($this->category, $systemCategories);
    }

    /**
     * Find permission by slug
     */
    public static function findBySlug($slug)
    {
        $database = App::getInstance()->getDatabase();
        $permissionData = $database->table('permissions')
            ->where('slug', $slug)
            ->first();
            
        if ($permissionData) {
            $permission = new self($database);
            $permission->fill($permissionData);
            return $permission;
        }
        
        return null;
    }

    /**
     * Find permission by name
     */
    public static function findByName($name)
    {
        $database = App::getInstance()->getDatabase();
        $permissionData = $database->table('permissions')
            ->where('name', $name)
            ->first();
            
        if ($permissionData) {
            $permission = new self($database);
            $permission->fill($permissionData);
            return $permission;
        }
        
        return null;
    }

    /**
     * Get permissions by category
     */
    public static function getByCategory($category)
    {
        $database = App::getInstance()->getDatabase();
        return $database->table('permissions')
            ->where('category', $category)
            ->orderBy('name', 'ASC')
            ->get();
    }

    /**
     * Get all categories
     */
    public static function getCategories()
    {
        $database = App::getInstance()->getDatabase();
        $result = $database->table('permissions')
            ->selectRaw('DISTINCT category')
            ->orderBy('category', 'ASC')
            ->get();
            
        return array_column($result, 'category');
    }

    /**
     * Get permissions grouped by category
     */
    public static function getGroupedByCategory()
    {
        $database = App::getInstance()->getDatabase();
        $permissions = $database->table('permissions')
            ->orderBy('category', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();
            
        $grouped = [];
        foreach ($permissions as $permission) {
            $category = $permission['category'] ?: 'Other';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $permission;
        }
        
        return $grouped;
    }

    /**
     * Get permissions for select dropdown
     */
    public static function getForSelect($category = null)
    {
        $database = App::getInstance()->getDatabase();
        $query = $database->table('permissions');
        
        if ($category) {
            $query->where('category', $category);
        }
        
        $permissions = $query->orderBy('name', 'ASC')->get();
        
        $options = [];
        foreach ($permissions as $permission) {
            $options[$permission['id']] = $permission['name'];
        }
        
        return $options;
    }

    /**
     * Create default permissions
     */
    public static function createDefaults()
    {
        $database = App::getInstance()->getDatabase();
        
        $defaultPermissions = [
            // System Management
            [
                'name' => 'Access Admin Panel',
                'slug' => 'access_admin_panel',
                'description' => 'Access to admin dashboard and panel',
                'category' => 'system',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Manage System Settings',
                'slug' => 'manage_system_settings',
                'description' => 'Manage system configuration and settings',
                'category' => 'system',
                'guard_name' => 'web'
            ],
            [
                'name' => 'View System Analytics',
                'slug' => 'view_system_analytics',
                'description' => 'View system analytics and reports',
                'category' => 'system',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Manage System Maintenance',
                'slug' => 'manage_system_maintenance',
                'description' => 'Put system in maintenance mode',
                'category' => 'system',
                'guard_name' => 'web'
            ],

            // User Management
            [
                'name' => 'View Users',
                'slug' => 'view_users',
                'description' => 'View user list and profiles',
                'category' => 'user_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Create Users',
                'slug' => 'create_users',
                'description' => 'Create new user accounts',
                'category' => 'user_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Edit Users',
                'slug' => 'edit_users',
                'description' => 'Edit user profiles and information',
                'category' => 'user_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Delete Users',
                'slug' => 'delete_users',
                'description' => 'Delete user accounts',
                'category' => 'user_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Suspend Users',
                'slug' => 'suspend_users',
                'description' => 'Suspend or activate user accounts',
                'category' => 'user_management',
                'guard_name' => 'web'
            ],

            // Role & Permission Management
            [
                'name' => 'View Roles',
                'slug' => 'view_roles',
                'description' => 'View roles and permissions',
                'category' => 'role_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Create Roles',
                'slug' => 'create_roles',
                'description' => 'Create new roles',
                'category' => 'role_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Edit Roles',
                'slug' => 'edit_roles',
                'description' => 'Edit roles and their permissions',
                'category' => 'role_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Delete Roles',
                'slug' => 'delete_roles',
                'description' => 'Delete roles',
                'category' => 'role_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Assign Roles',
                'slug' => 'assign_roles',
                'description' => 'Assign roles to users',
                'category' => 'role_management',
                'guard_name' => 'web'
            ],

            // Course Management
            [
                'name' => 'View All Courses',
                'slug' => 'view_all_courses',
                'description' => 'View all courses in the system',
                'category' => 'course_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Create Courses',
                'slug' => 'create_courses',
                'description' => 'Create new courses',
                'category' => 'course_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Edit Own Courses',
                'slug' => 'edit_own_courses',
                'description' => 'Edit own created courses',
                'category' => 'course_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Edit All Courses',
                'slug' => 'edit_all_courses',
                'description' => 'Edit any course in the system',
                'category' => 'course_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Delete Courses',
                'slug' => 'delete_courses',
                'description' => 'Delete courses',
                'category' => 'course_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Publish Courses',
                'slug' => 'publish_courses',
                'description' => 'Publish or unpublish courses',
                'category' => 'course_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Manage Course Categories',
                'slug' => 'manage_course_categories',
                'description' => 'Create and manage course categories',
                'category' => 'course_management',
                'guard_name' => 'web'
            ],

            // Content Management
            [
                'name' => 'Create Lessons',
                'slug' => 'create_lessons',
                'description' => 'Create course lessons',
                'category' => 'content_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Edit Lessons',
                'slug' => 'edit_lessons',
                'description' => 'Edit course lessons',
                'category' => 'content_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Delete Lessons',
                'slug' => 'delete_lessons',
                'description' => 'Delete course lessons',
                'category' => 'content_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Create Quizzes',
                'slug' => 'create_quizzes',
                'description' => 'Create quizzes and assessments',
                'category' => 'content_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Edit Quizzes',
                'slug' => 'edit_quizzes',
                'description' => 'Edit quizzes and assessments',
                'category' => 'content_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Delete Quizzes',
                'slug' => 'delete_quizzes',
                'description' => 'Delete quizzes and assessments',
                'category' => 'content_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Create Assignments',
                'slug' => 'create_assignments',
                'description' => 'Create assignments',
                'category' => 'content_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Edit Assignments',
                'slug' => 'edit_assignments',
                'description' => 'Edit assignments',
                'category' => 'content_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Delete Assignments',
                'slug' => 'delete_assignments',
                'description' => 'Delete assignments',
                'category' => 'content_management',
                'guard_name' => 'web'
            ],

            // Student Management
            [
                'name' => 'View Student Progress',
                'slug' => 'view_student_progress',
                'description' => 'View student progress and analytics',
                'category' => 'student_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Grade Assignments',
                'slug' => 'grade_assignments',
                'description' => 'Grade student assignments',
                'category' => 'student_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Issue Certificates',
                'slug' => 'issue_certificates',
                'description' => 'Issue course certificates',
                'category' => 'student_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Manage Enrollments',
                'slug' => 'manage_enrollments',
                'description' => 'Manage student enrollments',
                'category' => 'student_management',
                'guard_name' => 'web'
            ],

            // AI & ML Management
            [
                'name' => 'Access AI Tools',
                'slug' => 'access_ai_tools',
                'description' => 'Access AI and ML tools',
                'category' => 'ai_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Train AI Models',
                'slug' => 'train_ai_models',
                'description' => 'Train and manage AI models',
                'category' => 'ai_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Manage Datasets',
                'slug' => 'manage_datasets',
                'description' => 'Upload and manage datasets',
                'category' => 'ai_management',
                'guard_name' => 'web'
            ],

            // Payment Management
            [
                'name' => 'View Payments',
                'slug' => 'view_payments',
                'description' => 'View payment transactions',
                'category' => 'payment_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Manage Subscriptions',
                'slug' => 'manage_subscriptions',
                'description' => 'Manage user subscriptions',
                'category' => 'payment_management',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Issue Refunds',
                'slug' => 'issue_refunds',
                'description' => 'Process payment refunds',
                'category' => 'payment_management',
                'guard_name' => 'web'
            ],

            // Communication
            [
                'name' => 'Manage Forums',
                'slug' => 'manage_forums',
                'description' => 'Moderate forums and discussions',
                'category' => 'communication',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Send Announcements',
                'slug' => 'send_announcements',
                'description' => 'Send system announcements',
                'category' => 'communication',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Manage Chat',
                'slug' => 'manage_chat',
                'description' => 'Moderate chat and messaging',
                'category' => 'communication',
                'guard_name' => 'web'
            ],

            // Student Permissions
            [
                'name' => 'Enroll in Courses',
                'slug' => 'enroll_in_courses',
                'description' => 'Enroll in available courses',
                'category' => 'student',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Take Quizzes',
                'slug' => 'take_quizzes',
                'description' => 'Take quizzes and assessments',
                'category' => 'student',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Submit Assignments',
                'slug' => 'submit_assignments',
                'description' => 'Submit assignments',
                'category' => 'student',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Download Certificates',
                'slug' => 'download_certificates',
                'description' => 'Download earned certificates',
                'category' => 'student',
                'guard_name' => 'web'
            ],
            [
                'name' => 'Participate in Forums',
                'slug' => 'participate_in_forums',
                'description' => 'Participate in course forums',
                'category' => 'student',
                'guard_name' => 'web'
            ]
        ];
        
        foreach ($defaultPermissions as $permissionData) {
            // Check if permission already exists
            $exists = $database->table('permissions')
                ->where('slug', $permissionData['slug'])
                ->exists();
                
            if (!$exists) {
                $permissionData['created_at'] = date('Y-m-d H:i:s');
                $permissionData['updated_at'] = date('Y-m-d H:i:s');
                $database->insert('permissions', $permissionData);
            }
        }
        
        return true;
    }
}