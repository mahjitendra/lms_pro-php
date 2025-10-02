<?php

/**
 * Enrollment Model
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Model.php';

class Enrollment extends Model
{
    protected $table = 'enrollments';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'user_id', 'course_id', 'status', 'progress', 'enrolled_at',
        'started_at', 'completed_at', 'certificate_issued_at',
        'last_accessed_at', 'total_time_spent'
    ];
    
    protected $casts = [
        'user_id' => 'integer',
        'course_id' => 'integer',
        'progress' => 'float',
        'total_time_spent' => 'integer',
        'enrolled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'certificate_issued_at' => 'datetime',
        'last_accessed_at' => 'datetime'
    ];

    /**
     * Get user
     */
    public function user()
    {
        return $this->database->table('users')
            ->where('id', $this->user_id)
            ->first();
    }

    /**
     * Get course
     */
    public function course()
    {
        return $this->database->table('courses')
            ->where('id', $this->course_id)
            ->first();
    }

    /**
     * Check if enrollment is active
     */
    public function isActive()
    {
        return $this->status === ENROLLMENT_STATUS_ACTIVE;
    }

    /**
     * Check if enrollment is completed
     */
    public function isCompleted()
    {
        return $this->status === ENROLLMENT_STATUS_COMPLETED;
    }

    /**
     * Get lesson progress for this enrollment
     */
    public function getLessonProgress()
    {
        return $this->database->table('lesson_progress lp')
            ->join('lessons l', 'lp.lesson_id = l.id')
            ->where('lp.user_id', $this->user_id)
            ->where('l.course_id', $this->course_id)
            ->select(['lp.*', 'l.title as lesson_title', 'l.type as lesson_type'])
            ->orderBy('l.sort_order', 'ASC')
            ->get();
    }

    /**
     * Get completed lessons count
     */
    public function getCompletedLessonsCount()
    {
        return $this->database->table('lesson_progress lp')
            ->join('lessons l', 'lp.lesson_id = l.id')
            ->where('lp.user_id', $this->user_id)
            ->where('l.course_id', $this->course_id)
            ->where('lp.status', 'completed')
            ->count();
    }

    /**
     * Get total lessons count
     */
    public function getTotalLessonsCount()
    {
        return $this->database->table('lessons')
            ->where('course_id', $this->course_id)
            ->where('is_published', 1)
            ->count();
    }

    /**
     * Calculate and update progress
     */
    public function updateProgress()
    {
        $totalLessons = $this->getTotalLessonsCount();
        
        if ($totalLessons === 0) {
            $progress = 100;
        } else {
            $completedLessons = $this->getCompletedLessonsCount();
            $progress = round(($completedLessons / $totalLessons) * 100, 2);
        }
        
        $updateData = [
            'progress' => $progress,
            'updated_at' => date('Y-m-d H:i:s'),
            'last_accessed_at' => date('Y-m-d H:i:s')
        ];
        
        // Mark as completed if 100%
        if ($progress >= 100 && $this->status !== ENROLLMENT_STATUS_COMPLETED) {
            $updateData['status'] = ENROLLMENT_STATUS_COMPLETED;
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        }
        
        // Mark as started if progress > 0 and not started yet
        if ($progress > 0 && !$this->started_at) {
            $updateData['started_at'] = date('Y-m-d H:i:s');
        }
        
        $this->database->update('enrollments', $updateData, 'id = :id', ['id' => $this->id]);
        
        // Update object properties
        $this->progress = $progress;
        if (isset($updateData['status'])) {
            $this->status = $updateData['status'];
        }
        if (isset($updateData['completed_at'])) {
            $this->completed_at = $updateData['completed_at'];
        }
        if (isset($updateData['started_at'])) {
            $this->started_at = $updateData['started_at'];
        }
        
        return $progress;
    }

    /**
     * Get next lesson to study
     */
    public function getNextLesson()
    {
        // Get first incomplete lesson
        $nextLesson = $this->database->table('lessons l')
            ->leftJoin('lesson_progress lp', function($join) {
                $join->on('l.id', '=', 'lp.lesson_id')
                     ->where('lp.user_id', '=', $this->user_id);
            })
            ->where('l.course_id', $this->course_id)
            ->where('l.is_published', 1)
            ->where(function($query) {
                $query->whereNull('lp.status')
                      ->orWhere('lp.status', '!=', 'completed');
            })
            ->select(['l.*'])
            ->orderBy('l.sort_order', 'ASC')
            ->first();
            
        return $nextLesson;
    }

    /**
     * Get current lesson (last accessed or next to study)
     */
    public function getCurrentLesson()
    {
        // First try to get last accessed lesson
        $lastAccessed = $this->database->table('lesson_progress lp')
            ->join('lessons l', 'lp.lesson_id = l.id')
            ->where('lp.user_id', $this->user_id)
            ->where('l.course_id', $this->course_id)
            ->where('l.is_published', 1)
            ->select(['l.*', 'lp.updated_at as last_accessed'])
            ->orderBy('lp.updated_at', 'DESC')
            ->first();
            
        if ($lastAccessed && $lastAccessed['status'] !== 'completed') {
            return $lastAccessed;
        }
        
        // If no last accessed or it's completed, get next lesson
        return $this->getNextLesson();
    }

    /**
     * Get learning statistics
     */
    public function getStatistics()
    {
        $stats = [];
        
        // Basic progress
        $stats['progress'] = $this->progress;
        $stats['completed_lessons'] = $this->getCompletedLessonsCount();
        $stats['total_lessons'] = $this->getTotalLessonsCount();
        
        // Time statistics
        $timeStats = $this->database->table('lesson_progress lp')
            ->join('lessons l', 'lp.lesson_id = l.id')
            ->where('lp.user_id', $this->user_id)
            ->where('l.course_id', $this->course_id)
            ->selectRaw('
                SUM(lp.time_spent) as total_time_spent,
                AVG(lp.time_spent) as avg_time_per_lesson,
                COUNT(lp.id) as lessons_accessed
            ')
            ->first();
            
        $stats['total_time_spent'] = $timeStats ? (int)$timeStats['total_time_spent'] : 0;
        $stats['avg_time_per_lesson'] = $timeStats ? (int)$timeStats['avg_time_per_lesson'] : 0;
        $stats['lessons_accessed'] = $timeStats ? (int)$timeStats['lessons_accessed'] : 0;
        
        // Quiz statistics
        $quizStats = $this->database->table('quiz_attempts qa')
            ->join('quizzes q', 'qa.quiz_id = q.id')
            ->where('qa.user_id', $this->user_id)
            ->where('q.course_id', $this->course_id)
            ->selectRaw('
                COUNT(qa.id) as total_attempts,
                AVG(qa.score) as avg_score,
                MAX(qa.score) as best_score
            ')
            ->first();
            
        $stats['quiz_attempts'] = $quizStats ? (int)$quizStats['total_attempts'] : 0;
        $stats['avg_quiz_score'] = $quizStats ? round($quizStats['avg_score'], 1) : 0;
        $stats['best_quiz_score'] = $quizStats ? round($quizStats['best_score'], 1) : 0;
        
        // Assignment statistics
        $assignmentStats = $this->database->table('assignment_submissions asub')
            ->join('assignments a', 'asub.assignment_id = a.id')
            ->where('asub.user_id', $this->user_id)
            ->where('a.course_id', $this->course_id)
            ->selectRaw('
                COUNT(asub.id) as total_submissions,
                AVG(asub.grade) as avg_grade
            ')
            ->first();
            
        $stats['assignment_submissions'] = $assignmentStats ? (int)$assignmentStats['total_submissions'] : 0;
        $stats['avg_assignment_grade'] = $assignmentStats ? round($assignmentStats['avg_grade'], 1) : 0;
        
        return $stats;
    }

    /**
     * Get enrollment certificate
     */
    public function getCertificate()
    {
        return $this->database->table('certificates')
            ->where('user_id', $this->user_id)
            ->where('course_id', $this->course_id)
            ->where('status', CERTIFICATE_STATUS_ISSUED)
            ->first();
    }

    /**
     * Check if certificate is available
     */
    public function hasCertificate()
    {
        return $this->getCertificate() !== null;
    }

    /**
     * Get enrollment by user and course
     */
    public static function findByUserAndCourse($userId, $courseId)
    {
        $database = App::getInstance()->getDatabase();
        $enrollmentData = $database->table('enrollments')
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();
            
        if ($enrollmentData) {
            $enrollment = new self($database);
            $enrollment->fill($enrollmentData);
            return $enrollment;
        }
        
        return null;
    }

    /**
     * Get enrollments by user
     */
    public static function getByUser($userId, $status = null)
    {
        $database = App::getInstance()->getDatabase();
        $query = $database->table('enrollments e')
            ->join('courses c', 'e.course_id = c.id')
            ->join('users u', 'c.instructor_id = u.id')
            ->where('e.user_id', $userId)
            ->select([
                'e.*',
                'c.title as course_title',
                'c.slug as course_slug',
                'c.thumbnail as course_thumbnail',
                'c.level as course_level',
                'u.first_name as instructor_first_name',
                'u.last_name as instructor_last_name'
            ]);
            
        if ($status) {
            $query->where('e.status', $status);
        }
        
        return $query->orderBy('e.enrolled_at', 'DESC')->get();
    }

    /**
     * Get enrollments by course
     */
    public static function getByCourse($courseId, $status = null)
    {
        $database = App::getInstance()->getDatabase();
        $query = $database->table('enrollments e')
            ->join('users u', 'e.user_id = u.id')
            ->where('e.course_id', $courseId)
            ->select([
                'e.*',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.avatar'
            ]);
            
        if ($status) {
            $query->where('e.status', $status);
        }
        
        return $query->orderBy('e.enrolled_at', 'DESC')->get();
    }

    /**
     * Get enrollment statistics
     */
    public static function getStatistics($courseId = null, $dateFrom = null, $dateTo = null)
    {
        $database = App::getInstance()->getDatabase();
        $query = $database->table('enrollments');
        
        if ($courseId) {
            $query->where('course_id', $courseId);
        }
        
        if ($dateFrom) {
            $query->where('enrolled_at', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->where('enrolled_at', '<=', $dateTo);
        }
        
        $stats = $query->selectRaw('
            COUNT(*) as total_enrollments,
            COUNT(CASE WHEN status = "active" THEN 1 END) as active_enrollments,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_enrollments,
            COUNT(CASE WHEN status = "dropped" THEN 1 END) as dropped_enrollments,
            AVG(progress) as avg_progress,
            AVG(total_time_spent) as avg_time_spent
        ')->first();
        
        return [
            'total_enrollments' => (int)$stats['total_enrollments'],
            'active_enrollments' => (int)$stats['active_enrollments'],
            'completed_enrollments' => (int)$stats['completed_enrollments'],
            'dropped_enrollments' => (int)$stats['dropped_enrollments'],
            'completion_rate' => $stats['total_enrollments'] > 0 ? 
                round(($stats['completed_enrollments'] / $stats['total_enrollments']) * 100, 1) : 0,
            'avg_progress' => round($stats['avg_progress'], 1),
            'avg_time_spent' => (int)$stats['avg_time_spent']
        ];
    }

    /**
     * Get monthly enrollment trends
     */
    public static function getMonthlyTrends($courseId = null, $months = 12)
    {
        $database = App::getInstance()->getDatabase();
        $query = $database->table('enrollments')
            ->where('enrolled_at', '>=', date('Y-m-d', strtotime("-{$months} months")));
            
        if ($courseId) {
            $query->where('course_id', $courseId);
        }
        
        return $query->selectRaw('
            DATE_FORMAT(enrolled_at, "%Y-%m") as month,
            COUNT(*) as enrollments,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completions
        ')
        ->groupBy('month')
        ->orderBy('month', 'ASC')
        ->get();
    }
}