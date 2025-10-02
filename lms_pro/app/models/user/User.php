<?php

/**
 * User Model
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Model.php';

class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'first_name', 'last_name', 'email', 'username', 'password',
        'phone', 'date_of_birth', 'gender', 'address', 'city', 'state',
        'country', 'postal_code', 'avatar', 'bio', 'website', 'social_links',
        'timezone', 'language', 'email_verified_at', 'phone_verified_at',
        'two_factor_enabled', 'two_factor_secret', 'status', 'last_login_at',
        'last_login_ip', 'last_api_access', 'preferences'
    ];
    
    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'api_token'
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'last_login_at' => 'datetime',
        'last_api_access' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'social_links' => 'json',
        'preferences' => 'json',
        'status' => 'integer'
    ];
    
    protected $dates = [
        'created_at', 'updated_at', 'email_verified_at', 'phone_verified_at',
        'last_login_at', 'last_api_access'
    ];

    /**
     * Boot the model
     */
    protected function initialize()
    {
        // Set default preferences
        $this->on('creating', function($data) {
            if (!isset($data['preferences'])) {
                $data['preferences'] = json_encode([
                    'notifications' => [
                        'email' => true,
                        'push' => true,
                        'sms' => false
                    ],
                    'privacy' => [
                        'profile_visibility' => 'public',
                        'show_email' => false,
                        'show_phone' => false
                    ],
                    'learning' => [
                        'auto_play_videos' => true,
                        'show_subtitles' => false,
                        'playback_speed' => 1.0
                    ]
                ]);
            }
            
            if (!isset($data['status'])) {
                $data['status'] = USER_STATUS_ACTIVE;
            }
        });
    }

    /**
     * Get user's full name
     */
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get user's initials
     */
    public function getInitialsAttribute()
    {
        $firstInitial = $this->first_name ? substr($this->first_name, 0, 1) : '';
        $lastInitial = $this->last_name ? substr($this->last_name, 0, 1) : '';
        return strtoupper($firstInitial . $lastInitial);
    }

    /**
     * Get user's avatar URL
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return UPLOAD_URL . '/avatars/' . $this->avatar;
        }
        
        // Return gravatar as fallback
        return Helper::gravatar($this->email);
    }

    /**
     * Check if user is active
     */
    public function isActive()
    {
        return $this->status === USER_STATUS_ACTIVE;
    }

    /**
     * Check if user is suspended
     */
    public function isSuspended()
    {
        return $this->status === USER_STATUS_SUSPENDED;
    }

    /**
     * Check if user is pending
     */
    public function isPending()
    {
        return $this->status === USER_STATUS_PENDING;
    }

    /**
     * Check if email is verified
     */
    public function isEmailVerified()
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Check if phone is verified
     */
    public function isPhoneVerified()
    {
        return !is_null($this->phone_verified_at);
    }

    /**
     * Check if two-factor authentication is enabled
     */
    public function hasTwoFactorEnabled()
    {
        return $this->two_factor_enabled && !is_null($this->two_factor_secret);
    }

    /**
     * Get user roles
     */
    public function roles()
    {
        return $this->database->table('user_roles ur')
            ->join('roles r', 'ur.role_id = r.id')
            ->where('ur.user_id', $this->id)
            ->select(['r.*'])
            ->get();
    }

    /**
     * Get user permissions (from roles and direct assignments)
     */
    public function permissions()
    {
        // Get permissions from roles
        $rolePermissions = $this->database->table('user_roles ur')
            ->join('roles r', 'ur.role_id = r.id')
            ->join('role_permissions rp', 'r.id = rp.role_id')
            ->join('permissions p', 'rp.permission_id = p.id')
            ->where('ur.user_id', $this->id)
            ->select(['p.*'])
            ->get();

        // Get direct permissions
        $directPermissions = $this->database->table('user_permissions up')
            ->join('permissions p', 'up.permission_id = p.id')
            ->where('up.user_id', $this->id)
            ->select(['p.*'])
            ->get();

        // Merge and remove duplicates
        $allPermissions = array_merge($rolePermissions, $directPermissions);
        $uniquePermissions = [];
        $seen = [];

        foreach ($allPermissions as $permission) {
            if (!in_array($permission['id'], $seen)) {
                $uniquePermissions[] = $permission;
                $seen[] = $permission['id'];
            }
        }

        return $uniquePermissions;
    }

    /**
     * Check if user has role
     */
    public function hasRole($role)
    {
        $roles = $this->roles();
        
        foreach ($roles as $userRole) {
            if ($userRole['name'] === $role || $userRole['slug'] === $role) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if user has permission
     */
    public function hasPermission($permission)
    {
        $permissions = $this->permissions();
        
        foreach ($permissions as $userPermission) {
            if ($userPermission['name'] === $permission || $userPermission['slug'] === $permission) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get user enrollments
     */
    public function enrollments()
    {
        return $this->database->table('enrollments e')
            ->join('courses c', 'e.course_id = c.id')
            ->where('e.user_id', $this->id)
            ->select(['e.*', 'c.title as course_title', 'c.slug as course_slug'])
            ->get();
    }

    /**
     * Get user's enrolled courses
     */
    public function courses()
    {
        return $this->database->table('enrollments e')
            ->join('courses c', 'e.course_id = c.id')
            ->where('e.user_id', $this->id)
            ->where('e.status', ENROLLMENT_STATUS_ACTIVE)
            ->select(['c.*', 'e.enrolled_at', 'e.progress'])
            ->get();
    }

    /**
     * Get user's completed courses
     */
    public function completedCourses()
    {
        return $this->database->table('enrollments e')
            ->join('courses c', 'e.course_id = c.id')
            ->where('e.user_id', $this->id)
            ->where('e.status', ENROLLMENT_STATUS_COMPLETED)
            ->select(['c.*', 'e.completed_at'])
            ->get();
    }

    /**
     * Get user's certificates
     */
    public function certificates()
    {
        return $this->database->table('certificates cert')
            ->join('courses c', 'cert.course_id = c.id')
            ->where('cert.user_id', $this->id)
            ->where('cert.status', CERTIFICATE_STATUS_ISSUED)
            ->select(['cert.*', 'c.title as course_title'])
            ->get();
    }

    /**
     * Get user's quiz attempts
     */
    public function quizAttempts()
    {
        return $this->database->table('quiz_attempts qa')
            ->join('quizzes q', 'qa.quiz_id = q.id')
            ->where('qa.user_id', $this->id)
            ->select(['qa.*', 'q.title as quiz_title'])
            ->get();
    }

    /**
     * Get user's assignment submissions
     */
    public function assignmentSubmissions()
    {
        return $this->database->table('assignment_submissions as_sub')
            ->join('assignments a', 'as_sub.assignment_id = a.id')
            ->where('as_sub.user_id', $this->id)
            ->select(['as_sub.*', 'a.title as assignment_title'])
            ->get();
    }

    /**
     * Get user's forum posts
     */
    public function forumPosts()
    {
        return $this->database->table('forum_posts')
            ->where('user_id', $this->id)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Get user's badges
     */
    public function badges()
    {
        return $this->database->table('user_badges ub')
            ->join('badges b', 'ub.badge_id = b.id')
            ->where('ub.user_id', $this->id)
            ->select(['b.*', 'ub.earned_at'])
            ->get();
    }

    /**
     * Get user's total points
     */
    public function getTotalPoints()
    {
        $result = $this->database->table('user_points')
            ->where('user_id', $this->id)
            ->selectRaw('SUM(points) as total')
            ->first();
            
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Get user's learning analytics
     */
    public function getLearningAnalytics()
    {
        $analytics = [];
        
        // Total courses enrolled
        $analytics['total_courses'] = $this->database->table('enrollments')
            ->where('user_id', $this->id)
            ->count();
            
        // Completed courses
        $analytics['completed_courses'] = $this->database->table('enrollments')
            ->where('user_id', $this->id)
            ->where('status', ENROLLMENT_STATUS_COMPLETED)
            ->count();
            
        // Total learning time (in minutes)
        $analytics['total_learning_time'] = $this->database->table('lesson_progress')
            ->where('user_id', $this->id)
            ->selectRaw('SUM(time_spent) as total')
            ->first()['total'] ?? 0;
            
        // Quiz average score
        $quizAvg = $this->database->table('quiz_attempts')
            ->where('user_id', $this->id)
            ->where('status', 'completed')
            ->selectRaw('AVG(score) as average')
            ->first();
        $analytics['quiz_average'] = $quizAvg ? round($quizAvg['average'], 2) : 0;
        
        // Certificates earned
        $analytics['certificates_earned'] = $this->database->table('certificates')
            ->where('user_id', $this->id)
            ->where('status', CERTIFICATE_STATUS_ISSUED)
            ->count();
            
        // Current streak (days)
        $analytics['current_streak'] = $this->calculateLearningStreak();
        
        return $analytics;
    }

    /**
     * Calculate learning streak
     */
    private function calculateLearningStreak()
    {
        $activities = $this->database->table('user_activity')
            ->where('user_id', $this->id)
            ->where('activity_type', 'learning')
            ->selectRaw('DATE(created_at) as activity_date')
            ->groupBy('activity_date')
            ->orderBy('activity_date', 'DESC')
            ->get();
            
        if (empty($activities)) {
            return 0;
        }
        
        $streak = 0;
        $currentDate = date('Y-m-d');
        
        foreach ($activities as $activity) {
            if ($activity['activity_date'] === $currentDate) {
                $streak++;
                $currentDate = date('Y-m-d', strtotime($currentDate . ' -1 day'));
            } else {
                break;
            }
        }
        
        return $streak;
    }

    /**
     * Update user preferences
     */
    public function updatePreferences($preferences)
    {
        $currentPreferences = $this->preferences ? json_decode($this->preferences, true) : [];
        $newPreferences = array_merge($currentPreferences, $preferences);
        
        return $this->database->update('users', [
            'preferences' => json_encode($newPreferences),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Get user preference
     */
    public function getPreference($key, $default = null)
    {
        $preferences = $this->preferences ? json_decode($this->preferences, true) : [];
        return Helper::arrayGet($preferences, $key, $default);
    }

    /**
     * Mark email as verified
     */
    public function markEmailAsVerified()
    {
        return $this->database->update('users', [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Mark phone as verified
     */
    public function markPhoneAsVerified()
    {
        return $this->database->update('users', [
            'phone_verified_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Suspend user
     */
    public function suspend($reason = null)
    {
        return $this->database->update('users', [
            'status' => USER_STATUS_SUSPENDED,
            'suspension_reason' => $reason,
            'suspended_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Activate user
     */
    public function activate()
    {
        return $this->database->update('users', [
            'status' => USER_STATUS_ACTIVE,
            'suspension_reason' => null,
            'suspended_at' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Find user by email
     */
    public static function findByEmail($email)
    {
        $database = App::getInstance()->getDatabase();
        $userData = $database->table('users')
            ->where('email', $email)
            ->first();
            
        if ($userData) {
            $user = new self($database);
            $user->fill($userData);
            return $user;
        }
        
        return null;
    }

    /**
     * Find user by username
     */
    public static function findByUsername($username)
    {
        $database = App::getInstance()->getDatabase();
        $userData = $database->table('users')
            ->where('username', $username)
            ->first();
            
        if ($userData) {
            $user = new self($database);
            $user->fill($userData);
            return $user;
        }
        
        return null;
    }

    /**
     * Search users
     */
    public static function search($query, $filters = [])
    {
        $database = App::getInstance()->getDatabase();
        $queryBuilder = $database->table('users');
        
        // Search in name and email
        if ($query) {
            $queryBuilder->where(function($q) use ($query) {
                $q->where('first_name', 'LIKE', "%{$query}%")
                  ->orWhere('last_name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%")
                  ->orWhere('username', 'LIKE', "%{$query}%");
            });
        }
        
        // Apply filters
        if (isset($filters['status'])) {
            $queryBuilder->where('status', $filters['status']);
        }
        
        if (isset($filters['role'])) {
            $queryBuilder->join('user_roles ur', 'users.id = ur.user_id')
                        ->join('roles r', 'ur.role_id = r.id')
                        ->where('r.name', $filters['role']);
        }
        
        if (isset($filters['created_from'])) {
            $queryBuilder->where('created_at', '>=', $filters['created_from']);
        }
        
        if (isset($filters['created_to'])) {
            $queryBuilder->where('created_at', '<=', $filters['created_to']);
        }
        
        return $queryBuilder->select(['users.*'])
                          ->orderBy('created_at', 'DESC')
                          ->get();
    }
}