<?php

/**
 * Database Seeder Script
 * LMS Pro - Learning Management System
 */

echo "LMS Pro Database Seeder\n";
echo "=======================\n\n";

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

// Database configuration
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? 'lms_pro';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    // Connect to database
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✓ Connected to database\n\n";
    
    // Seed roles
    echo "Seeding roles...\n";
    seedRoles($pdo);
    
    // Seed permissions
    echo "Seeding permissions...\n";
    seedPermissions($pdo);
    
    // Seed role permissions
    echo "Seeding role permissions...\n";
    seedRolePermissions($pdo);
    
    // Seed categories
    echo "Seeding categories...\n";
    seedCategories($pdo);
    
    // Seed users
    echo "Seeding users...\n";
    seedUsers($pdo);
    
    // Seed settings
    echo "Seeding settings...\n";
    seedSettings($pdo);
    
    // Seed sample courses (optional)
    if (isset($argv[1]) && $argv[1] === '--with-sample-data') {
        echo "Seeding sample data...\n";
        seedSampleCourses($pdo);
    }
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n" . str_repeat("=", 40) . "\n";
echo "Seeding completed successfully!\n";
echo "\nDefault login credentials:\n";
echo "Admin: admin@lmspro.com / admin123\n";
echo "Instructor: instructor@lmspro.com / instructor123\n";
echo "Student: student@lmspro.com / student123\n";
echo str_repeat("=", 40) . "\n";

/**
 * Seed roles
 */
function seedRoles($pdo)
{
    $roles = [
        ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Full system access', 'level' => 1, 'color' => '#dc3545'],
        ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Administrative access', 'level' => 2, 'color' => '#fd7e14'],
        ['name' => 'Instructor', 'slug' => 'instructor', 'description' => 'Can create and manage courses', 'level' => 3, 'color' => '#198754'],
        ['name' => 'Student', 'slug' => 'student', 'description' => 'Can enroll in courses', 'level' => 4, 'is_default' => 1, 'color' => '#0d6efd'],
        ['name' => 'Guest', 'slug' => 'guest', 'description' => 'Limited access', 'level' => 5, 'color' => '#6c757d']
    ];
    
    foreach ($roles as $role) {
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE slug = ?");
        $stmt->execute([$role['slug']]);
        
        if (!$stmt->fetch()) {
            $role['created_at'] = date('Y-m-d H:i:s');
            $role['updated_at'] = date('Y-m-d H:i:s');
            $role['is_default'] = $role['is_default'] ?? 0;
            
            $columns = implode(', ', array_keys($role));
            $placeholders = ':' . implode(', :', array_keys($role));
            
            $stmt = $pdo->prepare("INSERT INTO roles ({$columns}) VALUES ({$placeholders})");
            $stmt->execute($role);
            
            echo "  ✓ Created role: {$role['name']}\n";
        } else {
            echo "  - Role exists: {$role['name']}\n";
        }
    }
}

/**
 * Seed permissions
 */
function seedPermissions($pdo)
{
    $permissions = [
        // System
        ['name' => 'Access Admin Panel', 'slug' => 'access_admin_panel', 'category' => 'system'],
        ['name' => 'Manage System Settings', 'slug' => 'manage_system_settings', 'category' => 'system'],
        ['name' => 'View System Analytics', 'slug' => 'view_system_analytics', 'category' => 'system'],
        
        // User Management
        ['name' => 'View Users', 'slug' => 'view_users', 'category' => 'user_management'],
        ['name' => 'Create Users', 'slug' => 'create_users', 'category' => 'user_management'],
        ['name' => 'Edit Users', 'slug' => 'edit_users', 'category' => 'user_management'],
        ['name' => 'Delete Users', 'slug' => 'delete_users', 'category' => 'user_management'],
        ['name' => 'Suspend Users', 'slug' => 'suspend_users', 'category' => 'user_management'],
        
        // Course Management
        ['name' => 'View All Courses', 'slug' => 'view_all_courses', 'category' => 'course_management'],
        ['name' => 'Create Courses', 'slug' => 'create_courses', 'category' => 'course_management'],
        ['name' => 'Edit Own Courses', 'slug' => 'edit_own_courses', 'category' => 'course_management'],
        ['name' => 'Edit All Courses', 'slug' => 'edit_all_courses', 'category' => 'course_management'],
        ['name' => 'Delete Courses', 'slug' => 'delete_courses', 'category' => 'course_management'],
        ['name' => 'Publish Courses', 'slug' => 'publish_courses', 'category' => 'course_management'],
        
        // AI Tools
        ['name' => 'Access AI Tools', 'slug' => 'access_ai_tools', 'category' => 'ai_management'],
        ['name' => 'Train AI Models', 'slug' => 'train_ai_models', 'category' => 'ai_management'],
        ['name' => 'Manage Datasets', 'slug' => 'manage_datasets', 'category' => 'ai_management'],
        
        // Student
        ['name' => 'Enroll in Courses', 'slug' => 'enroll_in_courses', 'category' => 'student'],
        ['name' => 'Take Quizzes', 'slug' => 'take_quizzes', 'category' => 'student'],
        ['name' => 'Submit Assignments', 'slug' => 'submit_assignments', 'category' => 'student'],
        ['name' => 'Download Certificates', 'slug' => 'download_certificates', 'category' => 'student']
    ];
    
    foreach ($permissions as $permission) {
        $stmt = $pdo->prepare("SELECT id FROM permissions WHERE slug = ?");
        $stmt->execute([$permission['slug']]);
        
        if (!$stmt->fetch()) {
            $permission['guard_name'] = 'web';
            $permission['created_at'] = date('Y-m-d H:i:s');
            $permission['updated_at'] = date('Y-m-d H:i:s');
            
            $columns = implode(', ', array_keys($permission));
            $placeholders = ':' . implode(', :', array_keys($permission));
            
            $stmt = $pdo->prepare("INSERT INTO permissions ({$columns}) VALUES ({$placeholders})");
            $stmt->execute($permission);
            
            echo "  ✓ Created permission: {$permission['name']}\n";
        } else {
            echo "  - Permission exists: {$permission['name']}\n";
        }
    }
}

/**
 * Seed role permissions
 */
function seedRolePermissions($pdo)
{
    // Get role and permission IDs
    $roles = $pdo->query("SELECT id, slug FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);
    $permissions = $pdo->query("SELECT id, slug FROM permissions")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Define role-permission mappings
    $rolePermissions = [
        'super_admin' => array_keys($permissions), // All permissions
        'admin' => [
            'access_admin_panel', 'manage_system_settings', 'view_system_analytics',
            'view_users', 'create_users', 'edit_users', 'suspend_users',
            'view_all_courses', 'create_courses', 'edit_all_courses', 'delete_courses', 'publish_courses'
        ],
        'instructor' => [
            'create_courses', 'edit_own_courses', 'access_ai_tools'
        ],
        'student' => [
            'enroll_in_courses', 'take_quizzes', 'submit_assignments', 
            'download_certificates', 'access_ai_tools'
        ]
    ];
    
    foreach ($rolePermissions as $roleSlug => $permissionSlugs) {
        if (!isset($roles[$roleSlug])) continue;
        
        $roleId = $roles[$roleSlug];
        
        foreach ($permissionSlugs as $permissionSlug) {
            if (!isset($permissions[$permissionSlug])) continue;
            
            $permissionId = $permissions[$permissionSlug];
            
            // Check if already exists
            $stmt = $pdo->prepare("SELECT id FROM role_permissions WHERE role_id = ? AND permission_id = ?");
            $stmt->execute([$roleId, $permissionId]);
            
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id, assigned_at) VALUES (?, ?, ?)");
                $stmt->execute([$roleId, $permissionId, date('Y-m-d H:i:s')]);
            }
        }
        
        echo "  ✓ Assigned permissions to role: {$roleSlug}\n";
    }
}

/**
 * Seed categories
 */
function seedCategories($pdo)
{
    $categories = [
        ['name' => 'Programming', 'slug' => 'programming', 'description' => 'Programming and software development courses', 'icon' => 'fas fa-code', 'color' => '#007bff'],
        ['name' => 'Data Science', 'slug' => 'data-science', 'description' => 'Data science and analytics courses', 'icon' => 'fas fa-chart-bar', 'color' => '#28a745'],
        ['name' => 'Artificial Intelligence', 'slug' => 'artificial-intelligence', 'description' => 'AI and machine learning courses', 'icon' => 'fas fa-robot', 'color' => '#dc3545'],
        ['name' => 'Web Development', 'slug' => 'web-development', 'description' => 'Web development and design courses', 'icon' => 'fas fa-globe', 'color' => '#ffc107'],
        ['name' => 'Mobile Development', 'slug' => 'mobile-development', 'description' => 'Mobile app development courses', 'icon' => 'fas fa-mobile-alt', 'color' => '#17a2b8'],
        ['name' => 'Business', 'slug' => 'business', 'description' => 'Business and entrepreneurship courses', 'icon' => 'fas fa-briefcase', 'color' => '#6f42c1'],
        ['name' => 'Design', 'slug' => 'design', 'description' => 'Design and creative courses', 'icon' => 'fas fa-paint-brush', 'color' => '#e83e8c']
    ];
    
    foreach ($categories as $index => $category) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->execute([$category['slug']]);
        
        if (!$stmt->fetch()) {
            $category['sort_order'] = $index + 1;
            $category['status'] = 1;
            $category['created_at'] = date('Y-m-d H:i:s');
            $category['updated_at'] = date('Y-m-d H:i:s');
            
            $columns = implode(', ', array_keys($category));
            $placeholders = ':' . implode(', :', array_keys($category));
            
            $stmt = $pdo->prepare("INSERT INTO categories ({$columns}) VALUES ({$placeholders})");
            $stmt->execute($category);
            
            echo "  ✓ Created category: {$category['name']}\n";
        } else {
            echo "  - Category exists: {$category['name']}\n";
        }
    }
}

/**
 * Seed users
 */
function seedUsers($pdo)
{
    // Get role IDs
    $roles = $pdo->query("SELECT id, slug FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $users = [
        [
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@lmspro.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'super_admin',
            'status' => 1,
            'email_verified_at' => date('Y-m-d H:i:s')
        ],
        [
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => 'admin@lmspro.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'status' => 1,
            'email_verified_at' => date('Y-m-d H:i:s')
        ],
        [
            'first_name' => 'John',
            'last_name' => 'Instructor',
            'email' => 'instructor@lmspro.com',
            'password' => password_hash('instructor123', PASSWORD_DEFAULT),
            'role' => 'instructor',
            'status' => 1,
            'email_verified_at' => date('Y-m-d H:i:s')
        ],
        [
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student@lmspro.com',
            'password' => password_hash('student123', PASSWORD_DEFAULT),
            'role' => 'student',
            'status' => 1,
            'email_verified_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    foreach ($users as $userData) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$userData['email']]);
        
        if (!$stmt->fetch()) {
            $roleSlug = $userData['role'];
            unset($userData['role']);
            
            $userData['created_at'] = date('Y-m-d H:i:s');
            $userData['updated_at'] = date('Y-m-d H:i:s');
            
            $columns = implode(', ', array_keys($userData));
            $placeholders = ':' . implode(', :', array_keys($userData));
            
            $stmt = $pdo->prepare("INSERT INTO users ({$columns}) VALUES ({$placeholders})");
            $stmt->execute($userData);
            
            $userId = $pdo->lastInsertId();
            
            // Assign role
            if (isset($roles[$roleSlug])) {
                $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id, assigned_at) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $roles[$roleSlug], date('Y-m-d H:i:s')]);
            }
            
            // Create user profile
            $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, created_at, updated_at) VALUES (?, ?, ?)");
            $stmt->execute([$userId, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
            
            echo "  ✓ Created user: {$userData['email']}\n";
        } else {
            echo "  - User exists: {$userData['email']}\n";
        }
    }
}

/**
 * Seed settings
 */
function seedSettings($pdo)
{
    $settings = [
        ['key' => 'site_name', 'value' => 'LMS Pro', 'type' => 'string'],
        ['key' => 'site_description', 'value' => 'Advanced Learning Management System', 'type' => 'string'],
        ['key' => 'timezone', 'value' => 'UTC', 'type' => 'string'],
        ['key' => 'date_format', 'value' => 'M d, Y', 'type' => 'string'],
        ['key' => 'time_format', 'value' => 'H:i', 'type' => 'string'],
        ['key' => 'currency', 'value' => 'USD', 'type' => 'string'],
        ['key' => 'language', 'value' => 'en', 'type' => 'string'],
        ['key' => 'registration_enabled', 'value' => '1', 'type' => 'boolean'],
        ['key' => 'email_verification_required', 'value' => '0', 'type' => 'boolean'],
        ['key' => 'two_factor_auth_enabled', 'value' => '0', 'type' => 'boolean'],
        ['key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean']
    ];
    
    foreach ($settings as $setting) {
        $stmt = $pdo->prepare("SELECT id FROM settings WHERE `key` = ?");
        $stmt->execute([$setting['key']]);
        
        if (!$stmt->fetch()) {
            $setting['created_at'] = date('Y-m-d H:i:s');
            $setting['updated_at'] = date('Y-m-d H:i:s');
            
            $columns = implode(', `', array_keys($setting));
            $placeholders = ':' . implode(', :', array_keys($setting));
            
            $stmt = $pdo->prepare("INSERT INTO settings (`{$columns}`) VALUES ({$placeholders})");
            $stmt->execute($setting);
            
            echo "  ✓ Created setting: {$setting['key']}\n";
        } else {
            echo "  - Setting exists: {$setting['key']}\n";
        }
    }
}

/**
 * Seed sample courses (optional)
 */
function seedSampleCourses($pdo)
{
    // Get instructor user ID
    $stmt = $pdo->prepare("SELECT u.id FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.slug = 'instructor' LIMIT 1");
    $stmt->execute();
    $instructor = $stmt->fetch();
    
    if (!$instructor) {
        echo "  ⚠ No instructor found, skipping sample courses\n";
        return;
    }
    
    // Get category IDs
    $categories = $pdo->query("SELECT id, slug FROM categories")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $courses = [
        [
            'title' => 'Introduction to Machine Learning',
            'slug' => 'intro-to-machine-learning',
            'description' => 'Learn the fundamentals of machine learning and AI',
            'short_description' => 'A comprehensive introduction to ML concepts and algorithms',
            'instructor_id' => $instructor['id'],
            'category_id' => $categories['artificial-intelligence'] ?? null,
            'level' => 'beginner',
            'price' => 99.99,
            'type' => 'paid',
            'status' => 1,
            'is_featured' => 1
        ],
        [
            'title' => 'Web Development with PHP',
            'slug' => 'web-development-php',
            'description' => 'Master PHP web development from basics to advanced',
            'short_description' => 'Complete PHP web development course',
            'instructor_id' => $instructor['id'],
            'category_id' => $categories['web-development'] ?? null,
            'level' => 'intermediate',
            'price' => 0.00,
            'type' => 'free',
            'status' => 1,
            'is_featured' => 1
        ]
    ];
    
    foreach ($courses as $course) {
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE slug = ?");
        $stmt->execute([$course['slug']]);
        
        if (!$stmt->fetch()) {
            $course['published_at'] = date('Y-m-d H:i:s');
            $course['created_at'] = date('Y-m-d H:i:s');
            $course['updated_at'] = date('Y-m-d H:i:s');
            
            $columns = implode(', ', array_keys($course));
            $placeholders = ':' . implode(', :', array_keys($course));
            
            $stmt = $pdo->prepare("INSERT INTO courses ({$columns}) VALUES ({$placeholders})");
            $stmt->execute($course);
            
            echo "  ✓ Created course: {$course['title']}\n";
        } else {
            echo "  - Course exists: {$course['title']}\n";
        }
    }
}