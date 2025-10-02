<?php

/**
 * Student Dashboard Controller
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Controller.php';

class DashboardController extends Controller
{
    protected $middleware = ['auth', 'role:student'];

    /**
     * Show student dashboard
     */
    public function index()
    {
        $userId = $this->userId();
        
        $data = [
            'title' => 'Dashboard - ' . APP_NAME,
            'stats' => $this->getStudentStats($userId),
            'enrolled_courses' => $this->getEnrolledCourses($userId),
            'recent_activities' => $this->getRecentActivities($userId),
            'recommended_courses' => $this->getRecommendedCourses($userId),
            'upcoming_deadlines' => $this->getUpcomingDeadlines($userId),
            'achievements' => $this->getRecentAchievements($userId),
            'learning_streak' => $this->getLearningStreak($userId),
            'progress_chart' => $this->getProgressChart($userId)
        ];
        
        return $this->view('student/dashboard/index', $data, 'student');
    }

    /**
     * Get student statistics
     */
    private function getStudentStats($userId)
    {
        $stats = [];
        
        // Total enrolled courses
        $stats['enrolled_courses'] = $this->database->table('enrollments')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->count();
            
        // Completed courses
        $stats['completed_courses'] = $this->database->table('enrollments')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->count();
            
        // Certificates earned
        $stats['certificates'] = $this->database->table('certificates')
            ->where('user_id', $userId)
            ->where('status', CERTIFICATE_STATUS_ISSUED)
            ->count();
            
        // Total learning time (in hours)
        $timeResult = $this->database->table('lesson_progress')
            ->where('user_id', $userId)
            ->selectRaw('SUM(time_spent) as total_time')
            ->first();
        $stats['learning_hours'] = $timeResult ? round($timeResult['total_time'] / 3600, 1) : 0;
        
        // Total points
        $pointsResult = $this->database->table('user_points')
            ->where('user_id', $userId)
            ->selectRaw('SUM(points) as total_points')
            ->first();
        $stats['total_points'] = $pointsResult ? (int)$pointsResult['total_points'] : 0;
        
        // Average quiz score
        $quizResult = $this->database->table('quiz_attempts')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->selectRaw('AVG(score) as avg_score')
            ->first();
        $stats['avg_quiz_score'] = $quizResult ? round($quizResult['avg_score'], 1) : 0;
        
        return $stats;
    }

    /**
     * Get enrolled courses with progress
     */
    private function getEnrolledCourses($userId, $limit = 6)
    {
        return $this->database->table('enrollments e')
            ->join('courses c', 'e.course_id = c.id')
            ->join('users u', 'c.instructor_id = u.id')
            ->where('e.user_id', $userId)
            ->where('e.status', 'active')
            ->select([
                'e.*',
                'c.title',
                'c.slug',
                'c.thumbnail',
                'c.level',
                'u.first_name as instructor_first_name',
                'u.last_name as instructor_last_name'
            ])
            ->orderBy('e.last_accessed_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent learning activities
     */
    private function getRecentActivities($userId, $limit = 10)
    {
        return $this->database->table('user_activities')
            ->where('user_id', $userId)
            ->whereIn('activity_type', [
                'lesson_completion', 'quiz_attempt', 'assignment_submission',
                'course_enrollment', 'certificate_earned', 'badge_earned'
            ])
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recommended courses
     */
    private function getRecommendedCourses($userId, $limit = 4)
    {
        // Simple recommendation based on user's enrolled course categories
        $userCategories = $this->database->table('enrollments e')
            ->join('courses c', 'e.course_id = c.id')
            ->where('e.user_id', $userId)
            ->whereNotNull('c.category_id')
            ->selectRaw('c.category_id, COUNT(*) as count')
            ->groupBy('c.category_id')
            ->orderBy('count', 'DESC')
            ->limit(3)
            ->get();
            
        if (empty($userCategories)) {
            // If no enrollments, show popular courses
            return $this->database->table('courses c')
                ->leftJoin('enrollments e', 'c.id = e.course_id')
                ->leftJoin('users u', 'c.instructor_id = u.id')
                ->where('c.status', COURSE_STATUS_PUBLISHED)
                ->select([
                    'c.*',
                    'u.first_name as instructor_first_name',
                    'u.last_name as instructor_last_name'
                ])
                ->selectRaw('COUNT(e.id) as enrollment_count')
                ->groupBy('c.id')
                ->orderBy('enrollment_count', 'DESC')
                ->limit($limit)
                ->get();
        }
        
        $categoryIds = array_column($userCategories, 'category_id');
        
        return $this->database->table('courses c')
            ->leftJoin('users u', 'c.instructor_id = u.id')
            ->leftJoin('enrollments e', function($join) use ($userId) {
                $join->on('c.id', '=', 'e.course_id')
                     ->where('e.user_id', '=', $userId);
            })
            ->where('c.status', COURSE_STATUS_PUBLISHED)
            ->whereIn('c.category_id', $categoryIds)
            ->whereNull('e.id') // Not already enrolled
            ->select([
                'c.*',
                'u.first_name as instructor_first_name',
                'u.last_name as instructor_last_name'
            ])
            ->orderBy('c.created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get upcoming deadlines
     */
    private function getUpcomingDeadlines($userId, $limit = 5)
    {
        $deadlines = [];
        
        // Quiz deadlines
        $quizDeadlines = $this->database->table('quizzes q')
            ->join('enrollments e', 'q.course_id = e.course_id')
            ->leftJoin('quiz_attempts qa', function($join) use ($userId) {
                $join->on('q.id', '=', 'qa.quiz_id')
                     ->where('qa.user_id', '=', $userId);
            })
            ->where('e.user_id', $userId)
            ->where('e.status', 'active')
            ->where('q.is_published', 1)
            ->whereNotNull('q.available_until')
            ->where('q.available_until', '>', date('Y-m-d H:i:s'))
            ->whereNull('qa.id') // Not attempted yet
            ->select([
                'q.id',
                'q.title',
                'q.available_until as deadline',
                'quiz' as 'type',
                'q.course_id'
            ])
            ->get();
            
        // Assignment deadlines
        $assignmentDeadlines = $this->database->table('assignments a')
            ->join('enrollments e', 'a.course_id = e.course_id')
            ->leftJoin('assignment_submissions asub', function($join) use ($userId) {
                $join->on('a.id', '=', 'asub.assignment_id')
                     ->where('asub.user_id', '=', $userId);
            })
            ->where('e.user_id', $userId)
            ->where('e.status', 'active')
            ->where('a.is_published', 1)
            ->whereNotNull('a.due_date')
            ->where('a.due_date', '>', date('Y-m-d H:i:s'))
            ->whereNull('asub.id') // Not submitted yet
            ->select([
                'a.id',
                'a.title',
                'a.due_date as deadline',
                'assignment' as 'type',
                'a.course_id'
            ])
            ->get();
            
        $deadlines = array_merge($quizDeadlines, $assignmentDeadlines);
        
        // Sort by deadline
        usort($deadlines, function($a, $b) {
            return strtotime($a['deadline']) - strtotime($b['deadline']);
        });
        
        return array_slice($deadlines, 0, $limit);
    }

    /**
     * Get recent achievements
     */
    private function getRecentAchievements($userId, $limit = 5)
    {
        $achievements = [];
        
        // Recent badges
        $badges = $this->database->table('user_badges ub')
            ->join('badges b', 'ub.badge_id = b.id')
            ->where('ub.user_id', $userId)
            ->select([
                'b.name',
                'b.description',
                'b.icon',
                'ub.earned_at',
                'badge' as 'type'
            ])
            ->orderBy('ub.earned_at', 'DESC')
            ->limit(3)
            ->get();
            
        // Recent certificates
        $certificates = $this->database->table('certificates cert')
            ->join('courses c', 'cert.course_id = c.id')
            ->where('cert.user_id', $userId)
            ->where('cert.status', CERTIFICATE_STATUS_ISSUED)
            ->select([
                'c.title as name',
                'Certificate earned' as 'description',
                'certificate' as 'icon',
                'cert.issued_at as earned_at',
                'certificate' as 'type'
            ])
            ->orderBy('cert.issued_at', 'DESC')
            ->limit(2)
            ->get();
            
        $achievements = array_merge($badges, $certificates);
        
        // Sort by earned date
        usort($achievements, function($a, $b) {
            return strtotime($b['earned_at']) - strtotime($a['earned_at']);
        });
        
        return array_slice($achievements, 0, $limit);
    }

    /**
     * Get learning streak
     */
    private function getLearningStreak($userId)
    {
        $activities = $this->database->table('user_activities')
            ->where('user_id', $userId)
            ->where('activity_type', 'lesson_completion')
            ->selectRaw('DATE(created_at) as activity_date')
            ->groupBy('activity_date')
            ->orderBy('activity_date', 'DESC')
            ->limit(365) // Check last year
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
     * Get progress chart data
     */
    private function getProgressChart($userId)
    {
        // Get lesson completions for last 30 days
        $data = $this->database->table('lesson_progress')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->where('completed_at', '>=', date('Y-m-d', strtotime('-30 days')))
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();
            
        // Fill missing days with zero
        $dates = [];
        $counts = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dates[] = date('M j', strtotime($date));
            
            $found = false;
            foreach ($data as $item) {
                if ($item['date'] === $date) {
                    $counts[] = (int)$item['count'];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $counts[] = 0;
            }
        }
        
        return [
            'labels' => $dates,
            'data' => $counts
        ];
    }

    /**
     * Get dashboard data via AJAX
     */
    public function getData()
    {
        if (!$this->request->isAjax()) {
            return $this->abort(404);
        }
        
        $type = $this->query('type');
        $userId = $this->userId();
        
        switch ($type) {
            case 'stats':
                return $this->json($this->getStudentStats($userId));
                
            case 'courses':
                return $this->json($this->getEnrolledCourses($userId));
                
            case 'activities':
                return $this->json($this->getRecentActivities($userId));
                
            case 'recommendations':
                return $this->json($this->getRecommendedCourses($userId));
                
            case 'deadlines':
                return $this->json($this->getUpcomingDeadlines($userId));
                
            case 'achievements':
                return $this->json($this->getRecentAchievements($userId));
                
            case 'progress_chart':
                return $this->json($this->getProgressChart($userId));
                
            default:
                return $this->error('Invalid data type', [], 400);
        }
    }

    /**
     * Continue learning (get next lesson)
     */
    public function continuelearning()
    {
        $userId = $this->userId();
        
        // Get the most recently accessed course
        $recentEnrollment = $this->database->table('enrollments e')
            ->join('courses c', 'e.course_id = c.id')
            ->where('e.user_id', $userId)
            ->where('e.status', 'active')
            ->where('e.progress', '<', 100)
            ->orderBy('e.last_accessed_at', 'DESC')
            ->select(['e.*', 'c.slug as course_slug'])
            ->first();
            
        if (!$recentEnrollment) {
            // No active courses, redirect to course catalog
            return $this->redirect('/courses');
        }
        
        // Get next lesson to study
        $nextLesson = $this->database->table('lessons l')
            ->leftJoin('lesson_progress lp', function($join) use ($userId) {
                $join->on('l.id', '=', 'lp.lesson_id')
                     ->where('lp.user_id', '=', $userId);
            })
            ->where('l.course_id', $recentEnrollment['course_id'])
            ->where('l.is_published', 1)
            ->where(function($query) {
                $query->whereNull('lp.status')
                      ->orWhere('lp.status', '!=', 'completed');
            })
            ->select(['l.*'])
            ->orderBy('l.sort_order', 'ASC')
            ->first();
            
        if ($nextLesson) {
            return $this->redirect('/student/lessons/' . $nextLesson['slug']);
        } else {
            // All lessons completed, go to course page
            return $this->redirect('/student/courses/' . $recentEnrollment['course_slug']);
        }
    }

    /**
     * Mark notification as read
     */
    public function markNotificationRead($id)
    {
        if (!$this->request->isAjax()) {
            return $this->abort(404);
        }
        
        $updated = $this->database->update('notifications', [
            'read_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id AND user_id = :user_id', [
            'id' => $id,
            'user_id' => $this->userId()
        ]);
        
        if ($updated) {
            return $this->success('Notification marked as read');
        } else {
            return $this->error('Notification not found', [], 404);
        }
    }

    /**
     * Get notifications
     */
    public function getNotifications()
    {
        if (!$this->request->isAjax()) {
            return $this->abort(404);
        }
        
        $notifications = $this->database->table('notifications')
            ->where('user_id', $this->userId())
            ->orderBy('created_at', 'DESC')
            ->limit(20)
            ->get();
            
        return $this->json($notifications);
    }
}