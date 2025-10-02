<?php

/**
 * User Activity Model
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Model.php';

class UserActivity extends Model
{
    protected $table = 'user_activities';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'user_id', 'activity_type', 'activity_description', 'related_type',
        'related_id', 'ip_address', 'user_agent', 'metadata'
    ];
    
    protected $casts = [
        'user_id' => 'integer',
        'related_id' => 'integer',
        'metadata' => 'json'
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
     * Get related object
     */
    public function getRelatedObject()
    {
        if (!$this->related_type || !$this->related_id) {
            return null;
        }
        
        switch ($this->related_type) {
            case 'course':
                return $this->database->table('courses')
                    ->where('id', $this->related_id)
                    ->first();
                    
            case 'lesson':
                return $this->database->table('lessons')
                    ->where('id', $this->related_id)
                    ->first();
                    
            case 'quiz':
                return $this->database->table('quizzes')
                    ->where('id', $this->related_id)
                    ->first();
                    
            case 'assignment':
                return $this->database->table('assignments')
                    ->where('id', $this->related_id)
                    ->first();
                    
            default:
                return null;
        }
    }

    /**
     * Log user activity
     */
    public static function log($userId, $activityType, $description, $relatedType = null, $relatedId = null, $metadata = [])
    {
        $database = App::getInstance()->getDatabase();
        
        $activityData = [
            'user_id' => $userId,
            'activity_type' => $activityType,
            'activity_description' => $description,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'ip_address' => Helper::getClientIp(),
            'user_agent' => Helper::getUserAgent(),
            'metadata' => !empty($metadata) ? json_encode($metadata) : null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $database->insert('user_activities', $activityData);
    }

    /**
     * Log login activity
     */
    public static function logLogin($userId, $successful = true)
    {
        $description = $successful ? 'User logged in' : 'Failed login attempt';
        return self::log($userId, 'login', $description);
    }

    /**
     * Log logout activity
     */
    public static function logLogout($userId)
    {
        return self::log($userId, 'logout', 'User logged out');
    }

    /**
     * Log course enrollment
     */
    public static function logCourseEnrollment($userId, $courseId)
    {
        return self::log($userId, 'enrollment', 'Enrolled in course', 'course', $courseId);
    }

    /**
     * Log lesson completion
     */
    public static function logLessonCompletion($userId, $lessonId, $timeSpent = null)
    {
        $metadata = [];
        if ($timeSpent) {
            $metadata['time_spent'] = $timeSpent;
        }
        
        return self::log($userId, 'lesson_completion', 'Completed lesson', 'lesson', $lessonId, $metadata);
    }

    /**
     * Log quiz attempt
     */
    public static function logQuizAttempt($userId, $quizId, $score = null)
    {
        $metadata = [];
        if ($score !== null) {
            $metadata['score'] = $score;
        }
        
        return self::log($userId, 'quiz_attempt', 'Attempted quiz', 'quiz', $quizId, $metadata);
    }

    /**
     * Log assignment submission
     */
    public static function logAssignmentSubmission($userId, $assignmentId)
    {
        return self::log($userId, 'assignment_submission', 'Submitted assignment', 'assignment', $assignmentId);
    }

    /**
     * Log certificate earned
     */
    public static function logCertificateEarned($userId, $courseId)
    {
        return self::log($userId, 'certificate_earned', 'Earned course certificate', 'course', $courseId);
    }

    /**
     * Log badge earned
     */
    public static function logBadgeEarned($userId, $badgeId)
    {
        return self::log($userId, 'badge_earned', 'Earned badge', 'badge', $badgeId);
    }

    /**
     * Get user activities
     */
    public static function getUserActivities($userId, $limit = 50, $activityType = null)
    {
        $database = App::getInstance()->getDatabase();
        $query = $database->table('user_activities')
            ->where('user_id', $userId);
            
        if ($activityType) {
            $query->where('activity_type', $activityType);
        }
        
        return $query->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Get recent activities for all users
     */
    public static function getRecentActivities($limit = 100, $activityTypes = null)
    {
        $database = App::getInstance()->getDatabase();
        $query = $database->table('user_activities ua')
            ->join('users u', 'ua.user_id = u.id')
            ->select([
                'ua.*',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.avatar'
            ]);
            
        if ($activityTypes && is_array($activityTypes)) {
            $query->whereIn('ua.activity_type', $activityTypes);
        }
        
        return $query->orderBy('ua.created_at', 'DESC')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Get activity statistics
     */
    public static function getActivityStats($userId = null, $dateFrom = null, $dateTo = null)
    {
        $database = App::getInstance()->getDatabase();
        $query = $database->table('user_activities');
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }
        
        $stats = $query->selectRaw('
            activity_type,
            COUNT(*) as count,
            DATE(created_at) as activity_date
        ')
        ->groupBy('activity_type', 'activity_date')
        ->orderBy('activity_date', 'DESC')
        ->get();
        
        return $stats;
    }

    /**
     * Clean old activities
     */
    public static function cleanOldActivities($daysToKeep = 90)
    {
        $database = App::getInstance()->getDatabase();
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        
        return $database->delete('user_activities', 'created_at < :cutoff', ['cutoff' => $cutoffDate]);
    }
}