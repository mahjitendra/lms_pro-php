<?php

/**
 * Course Model
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Model.php';

class Course extends Model
{
    protected $table = 'courses';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'title', 'slug', 'description', 'short_description', 'thumbnail',
        'preview_video', 'instructor_id', 'category_id', 'level', 'language',
        'duration', 'price', 'discount_price', 'currency', 'type',
        'requirements', 'what_you_learn', 'tags', 'status', 'is_featured',
        'max_students', 'certificate_template', 'completion_criteria',
        'seo_title', 'seo_description', 'seo_keywords', 'published_at'
    ];
    
    protected $casts = [
        'instructor_id' => 'integer',
        'category_id' => 'integer',
        'duration' => 'integer',
        'price' => 'float',
        'discount_price' => 'float',
        'status' => 'integer',
        'is_featured' => 'boolean',
        'max_students' => 'integer',
        'requirements' => 'json',
        'what_you_learn' => 'json',
        'tags' => 'json',
        'completion_criteria' => 'json',
        'published_at' => 'datetime'
    ];

    /**
     * Boot the model
     */
    protected function initialize()
    {
        // Generate slug from title if not provided
        $this->on('creating', function($data) {
            if (!isset($data['slug']) && isset($data['title'])) {
                $data['slug'] = $this->generateUniqueSlug($data['title']);
            }
            
            if (!isset($data['status'])) {
                $data['status'] = COURSE_STATUS_DRAFT;
            }
            
            if (!isset($data['currency'])) {
                $data['currency'] = 'USD';
            }
            
            if (!isset($data['language'])) {
                $data['language'] = 'en';
            }
        });
        
        $this->on('updating', function($data) {
            if (isset($data['title']) && !isset($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['title'], $this->id);
            }
        });
    }

    /**
     * Generate unique slug
     */
    private function generateUniqueSlug($title, $excludeId = null)
    {
        $baseSlug = Helper::slug($title);
        $slug = $baseSlug;
        $counter = 1;
        
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Check if slug exists
     */
    private function slugExists($slug, $excludeId = null)
    {
        $query = $this->database->table('courses')->where('slug', $slug);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Get course instructor
     */
    public function instructor()
    {
        return $this->database->table('users')
            ->where('id', $this->instructor_id)
            ->first();
    }

    /**
     * Get course category
     */
    public function category()
    {
        return $this->database->table('categories')
            ->where('id', $this->category_id)
            ->first();
    }

    /**
     * Get course modules
     */
    public function modules()
    {
        return $this->database->table('course_modules')
            ->where('course_id', $this->id)
            ->orderBy('sort_order', 'ASC')
            ->get();
    }

    /**
     * Get course lessons
     */
    public function lessons()
    {
        return $this->database->table('lessons')
            ->where('course_id', $this->id)
            ->orderBy('sort_order', 'ASC')
            ->get();
    }

    /**
     * Get course enrollments
     */
    public function enrollments()
    {
        return $this->database->table('enrollments e')
            ->join('users u', 'e.user_id = u.id')
            ->where('e.course_id', $this->id)
            ->select(['e.*', 'u.first_name', 'u.last_name', 'u.email', 'u.avatar'])
            ->get();
    }

    /**
     * Get course reviews
     */
    public function reviews()
    {
        return $this->database->table('course_reviews cr')
            ->join('users u', 'cr.user_id = u.id')
            ->where('cr.course_id', $this->id)
            ->where('cr.status', 'approved')
            ->select(['cr.*', 'u.first_name', 'u.last_name', 'u.avatar'])
            ->orderBy('cr.created_at', 'DESC')
            ->get();
    }

    /**
     * Get course quizzes
     */
    public function quizzes()
    {
        return $this->database->table('quizzes')
            ->where('course_id', $this->id)
            ->orderBy('created_at', 'ASC')
            ->get();
    }

    /**
     * Get course assignments
     */
    public function assignments()
    {
        return $this->database->table('assignments')
            ->where('course_id', $this->id)
            ->orderBy('created_at', 'ASC')
            ->get();
    }

    /**
     * Check if course is published
     */
    public function isPublished()
    {
        return $this->status === COURSE_STATUS_PUBLISHED;
    }

    /**
     * Check if course is draft
     */
    public function isDraft()
    {
        return $this->status === COURSE_STATUS_DRAFT;
    }

    /**
     * Check if course is free
     */
    public function isFree()
    {
        return $this->type === COURSE_TYPE_FREE || $this->price == 0;
    }

    /**
     * Check if course is paid
     */
    public function isPaid()
    {
        return $this->type === COURSE_TYPE_PAID && $this->price > 0;
    }

    /**
     * Get effective price (with discount)
     */
    public function getEffectivePrice()
    {
        if ($this->discount_price && $this->discount_price < $this->price) {
            return $this->discount_price;
        }
        return $this->price;
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentage()
    {
        if (!$this->discount_price || $this->discount_price >= $this->price) {
            return 0;
        }
        
        return round((($this->price - $this->discount_price) / $this->price) * 100);
    }

    /**
     * Get total students enrolled
     */
    public function getTotalStudents()
    {
        return $this->database->table('enrollments')
            ->where('course_id', $this->id)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Get total lessons count
     */
    public function getTotalLessons()
    {
        return $this->database->table('lessons')
            ->where('course_id', $this->id)
            ->where('is_published', 1)
            ->count();
    }

    /**
     * Get total duration in minutes
     */
    public function getTotalDuration()
    {
        $result = $this->database->table('lessons')
            ->where('course_id', $this->id)
            ->where('is_published', 1)
            ->selectRaw('SUM(video_duration) as total_duration')
            ->first();
            
        return $result ? (int)$result['total_duration'] : 0;
    }

    /**
     * Get average rating
     */
    public function getAverageRating()
    {
        $result = $this->database->table('course_reviews')
            ->where('course_id', $this->id)
            ->where('status', 'approved')
            ->selectRaw('AVG(rating) as average_rating, COUNT(*) as total_reviews')
            ->first();
            
        return [
            'average' => $result ? round($result['average_rating'], 1) : 0,
            'total' => $result ? (int)$result['total_reviews'] : 0
        ];
    }

    /**
     * Get completion rate
     */
    public function getCompletionRate()
    {
        $totalEnrollments = $this->database->table('enrollments')
            ->where('course_id', $this->id)
            ->count();
            
        if ($totalEnrollments === 0) {
            return 0;
        }
        
        $completedEnrollments = $this->database->table('enrollments')
            ->where('course_id', $this->id)
            ->where('status', 'completed')
            ->count();
            
        return round(($completedEnrollments / $totalEnrollments) * 100, 1);
    }

    /**
     * Check if user is enrolled
     */
    public function isUserEnrolled($userId)
    {
        return $this->database->table('enrollments')
            ->where('course_id', $this->id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Check if user completed course
     */
    public function isUserCompleted($userId)
    {
        return $this->database->table('enrollments')
            ->where('course_id', $this->id)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Get user progress
     */
    public function getUserProgress($userId)
    {
        $enrollment = $this->database->table('enrollments')
            ->where('course_id', $this->id)
            ->where('user_id', $userId)
            ->first();
            
        return $enrollment ? $enrollment['progress'] : 0;
    }

    /**
     * Enroll user in course
     */
    public function enrollUser($userId)
    {
        // Check if already enrolled
        if ($this->isUserEnrolled($userId)) {
            return false;
        }
        
        $enrollmentData = [
            'user_id' => $userId,
            'course_id' => $this->id,
            'status' => 'active',
            'progress' => 0,
            'enrolled_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $enrollmentId = $this->database->insert('enrollments', $enrollmentData);
        
        if ($enrollmentId) {
            // Log activity
            UserActivity::logCourseEnrollment($userId, $this->id);
            return true;
        }
        
        return false;
    }

    /**
     * Update user progress
     */
    public function updateUserProgress($userId, $progress)
    {
        $updated = $this->database->update('enrollments', [
            'progress' => $progress,
            'updated_at' => date('Y-m-d H:i:s'),
            'last_accessed_at' => date('Y-m-d H:i:s')
        ], 'course_id = :course_id AND user_id = :user_id', [
            'course_id' => $this->id,
            'user_id' => $userId
        ]);
        
        // Check if course is completed
        if ($progress >= 100) {
            $this->markAsCompleted($userId);
        }
        
        return $updated > 0;
    }

    /**
     * Mark course as completed for user
     */
    public function markAsCompleted($userId)
    {
        $updated = $this->database->update('enrollments', [
            'status' => 'completed',
            'progress' => 100,
            'completed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'course_id = :course_id AND user_id = :user_id', [
            'course_id' => $this->id,
            'user_id' => $userId
        ]);
        
        if ($updated > 0) {
            // Issue certificate if configured
            $this->issueCertificate($userId);
            
            // Award points
            $this->awardCompletionPoints($userId);
            
            return true;
        }
        
        return false;
    }

    /**
     * Issue certificate
     */
    private function issueCertificate($userId)
    {
        // Check if certificate already issued
        $exists = $this->database->table('certificates')
            ->where('user_id', $userId)
            ->where('course_id', $this->id)
            ->exists();
            
        if (!$exists) {
            $certificateData = [
                'user_id' => $userId,
                'course_id' => $this->id,
                'certificate_number' => $this->generateCertificateNumber(),
                'status' => CERTIFICATE_STATUS_ISSUED,
                'issued_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->database->insert('certificates', $certificateData);
            
            // Log activity
            UserActivity::logCertificateEarned($userId, $this->id);
        }
    }

    /**
     * Generate certificate number
     */
    private function generateCertificateNumber()
    {
        return 'CERT-' . strtoupper(Helper::randomString(8)) . '-' . date('Y');
    }

    /**
     * Award completion points
     */
    private function awardCompletionPoints($userId)
    {
        $points = POINTS_COURSE_COMPLETION;
        
        $pointData = [
            'user_id' => $userId,
            'points' => $points,
            'type' => 'course_completion',
            'related_type' => 'course',
            'related_id' => $this->id,
            'description' => "Completed course: {$this->title}",
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->database->insert('user_points', $pointData);
    }

    /**
     * Publish course
     */
    public function publish()
    {
        return $this->database->update('courses', [
            'status' => COURSE_STATUS_PUBLISHED,
            'published_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Unpublish course
     */
    public function unpublish()
    {
        return $this->database->update('courses', [
            'status' => COURSE_STATUS_DRAFT,
            'published_at' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Get course analytics
     */
    public function getAnalytics()
    {
        $analytics = [];
        
        // Total enrollments
        $analytics['total_enrollments'] = $this->getTotalStudents();
        
        // Completion rate
        $analytics['completion_rate'] = $this->getCompletionRate();
        
        // Average rating
        $rating = $this->getAverageRating();
        $analytics['average_rating'] = $rating['average'];
        $analytics['total_reviews'] = $rating['total'];
        
        // Revenue (if paid course)
        if ($this->isPaid()) {
            $revenue = $this->database->table('payments p')
                ->join('enrollments e', 'p.enrollment_id = e.id')
                ->where('e.course_id', $this->id)
                ->where('p.status', 'completed')
                ->selectRaw('SUM(p.amount) as total_revenue')
                ->first();
                
            $analytics['total_revenue'] = $revenue ? $revenue['total_revenue'] : 0;
        } else {
            $analytics['total_revenue'] = 0;
        }
        
        // Monthly enrollments
        $monthlyEnrollments = $this->database->table('enrollments')
            ->where('course_id', $this->id)
            ->where('enrolled_at', '>=', date('Y-m-d', strtotime('-12 months')))
            ->selectRaw('DATE_FORMAT(enrolled_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->get();
            
        $analytics['monthly_enrollments'] = $monthlyEnrollments;
        
        return $analytics;
    }

    /**
     * Search courses
     */
    public static function search($query, $filters = [])
    {
        $database = App::getInstance()->getDatabase();
        $queryBuilder = $database->table('courses c')
            ->leftJoin('categories cat', 'c.category_id = cat.id')
            ->leftJoin('users u', 'c.instructor_id = u.id')
            ->select([
                'c.*',
                'cat.name as category_name',
                'u.first_name as instructor_first_name',
                'u.last_name as instructor_last_name'
            ]);
        
        // Search in title and description
        if ($query) {
            $queryBuilder->where(function($q) use ($query) {
                $q->where('c.title', 'LIKE', "%{$query}%")
                  ->orWhere('c.description', 'LIKE', "%{$query}%")
                  ->orWhere('c.short_description', 'LIKE', "%{$query}%");
            });
        }
        
        // Apply filters
        if (isset($filters['category_id'])) {
            $queryBuilder->where('c.category_id', $filters['category_id']);
        }
        
        if (isset($filters['level'])) {
            $queryBuilder->where('c.level', $filters['level']);
        }
        
        if (isset($filters['type'])) {
            $queryBuilder->where('c.type', $filters['type']);
        }
        
        if (isset($filters['instructor_id'])) {
            $queryBuilder->where('c.instructor_id', $filters['instructor_id']);
        }
        
        if (isset($filters['price_min'])) {
            $queryBuilder->where('c.price', '>=', $filters['price_min']);
        }
        
        if (isset($filters['price_max'])) {
            $queryBuilder->where('c.price', '<=', $filters['price_max']);
        }
        
        if (isset($filters['status'])) {
            $queryBuilder->where('c.status', $filters['status']);
        } else {
            // Default to published courses only
            $queryBuilder->where('c.status', COURSE_STATUS_PUBLISHED);
        }
        
        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'DESC';
        
        switch ($sortBy) {
            case 'popularity':
                $queryBuilder->leftJoin('enrollments e', 'c.id = e.course_id')
                           ->selectRaw('c.*, COUNT(e.id) as enrollment_count')
                           ->groupBy('c.id')
                           ->orderBy('enrollment_count', 'DESC');
                break;
            case 'rating':
                $queryBuilder->leftJoin('course_reviews cr', 'c.id = cr.course_id')
                           ->selectRaw('c.*, AVG(cr.rating) as avg_rating')
                           ->groupBy('c.id')
                           ->orderBy('avg_rating', 'DESC');
                break;
            case 'price':
                $queryBuilder->orderBy('c.price', $sortOrder);
                break;
            default:
                $queryBuilder->orderBy("c.{$sortBy}", $sortOrder);
        }
        
        return $queryBuilder->get();
    }

    /**
     * Get featured courses
     */
    public static function getFeatured($limit = 10)
    {
        $database = App::getInstance()->getDatabase();
        return $database->table('courses c')
            ->leftJoin('categories cat', 'c.category_id = cat.id')
            ->leftJoin('users u', 'c.instructor_id = u.id')
            ->where('c.is_featured', 1)
            ->where('c.status', COURSE_STATUS_PUBLISHED)
            ->select([
                'c.*',
                'cat.name as category_name',
                'u.first_name as instructor_first_name',
                'u.last_name as instructor_last_name'
            ])
            ->orderBy('c.created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get popular courses
     */
    public static function getPopular($limit = 10)
    {
        $database = App::getInstance()->getDatabase();
        return $database->table('courses c')
            ->leftJoin('enrollments e', 'c.id = e.course_id')
            ->leftJoin('categories cat', 'c.category_id = cat.id')
            ->leftJoin('users u', 'c.instructor_id = u.id')
            ->where('c.status', COURSE_STATUS_PUBLISHED)
            ->select([
                'c.*',
                'cat.name as category_name',
                'u.first_name as instructor_first_name',
                'u.last_name as instructor_last_name'
            ])
            ->selectRaw('COUNT(e.id) as enrollment_count')
            ->groupBy('c.id')
            ->orderBy('enrollment_count', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent courses
     */
    public static function getRecent($limit = 10)
    {
        $database = App::getInstance()->getDatabase();
        return $database->table('courses c')
            ->leftJoin('categories cat', 'c.category_id = cat.id')
            ->leftJoin('users u', 'c.instructor_id = u.id')
            ->where('c.status', COURSE_STATUS_PUBLISHED)
            ->select([
                'c.*',
                'cat.name as category_name',
                'u.first_name as instructor_first_name',
                'u.last_name as instructor_last_name'
            ])
            ->orderBy('c.published_at', 'DESC')
            ->limit($limit)
            ->get();
    }
}