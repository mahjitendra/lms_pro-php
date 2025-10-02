<?php

/**
 * Admin Dashboard Controller
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Controller.php';

class DashboardController extends Controller
{
    protected $middleware = ['auth', 'role:admin,super_admin'];

    /**
     * Show admin dashboard
     */
    public function index()
    {
        $data = [
            'title' => 'Admin Dashboard - ' . APP_NAME,
            'stats' => $this->getDashboardStats(),
            'recent_activities' => $this->getRecentActivities(),
            'popular_courses' => $this->getPopularCourses(),
            'recent_enrollments' => $this->getRecentEnrollments(),
            'system_info' => $this->getSystemInfo(),
            'charts_data' => $this->getChartsData()
        ];
        
        return $this->view('admin/dashboard/index', $data, 'admin');
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats()
    {
        $stats = [];
        
        // Total users
        $stats['total_users'] = $this->database->table('users')->count();
        
        // New users this month
        $stats['new_users_month'] = $this->database->table('users')
            ->where('created_at', '>=', date('Y-m-01'))
            ->count();
            
        // Total courses
        $stats['total_courses'] = $this->database->table('courses')->count();
        
        // Published courses
        $stats['published_courses'] = $this->database->table('courses')
            ->where('status', COURSE_STATUS_PUBLISHED)
            ->count();
            
        // Total enrollments
        $stats['total_enrollments'] = $this->database->table('enrollments')->count();
        
        // Active enrollments
        $stats['active_enrollments'] = $this->database->table('enrollments')
            ->where('status', ENROLLMENT_STATUS_ACTIVE)
            ->count();
            
        // Completed courses
        $stats['completed_courses'] = $this->database->table('enrollments')
            ->where('status', ENROLLMENT_STATUS_COMPLETED)
            ->count();
            
        // Total revenue (if applicable)
        $revenueResult = $this->database->table('payments')
            ->where('status', 'completed')
            ->selectRaw('SUM(amount) as total_revenue')
            ->first();
        $stats['total_revenue'] = $revenueResult ? $revenueResult['total_revenue'] : 0;
        
        // Revenue this month
        $monthlyRevenueResult = $this->database->table('payments')
            ->where('status', 'completed')
            ->where('created_at', '>=', date('Y-m-01'))
            ->selectRaw('SUM(amount) as monthly_revenue')
            ->first();
        $stats['monthly_revenue'] = $monthlyRevenueResult ? $monthlyRevenueResult['monthly_revenue'] : 0;
        
        // Instructors count
        $stats['total_instructors'] = $this->database->table('user_roles ur')
            ->join('roles r', 'ur.role_id = r.id')
            ->where('r.slug', 'instructor')
            ->count();
            
        // Students count
        $stats['total_students'] = $this->database->table('user_roles ur')
            ->join('roles r', 'ur.role_id = r.id')
            ->where('r.slug', 'student')
            ->count();
            
        // Calculate growth percentages
        $stats['user_growth'] = $this->calculateGrowthPercentage('users', 'created_at');
        $stats['course_growth'] = $this->calculateGrowthPercentage('courses', 'created_at');
        $stats['enrollment_growth'] = $this->calculateGrowthPercentage('enrollments', 'enrolled_at');
        $stats['revenue_growth'] = $this->calculateRevenueGrowth();
        
        return $stats;
    }

    /**
     * Calculate growth percentage for a metric
     */
    private function calculateGrowthPercentage($table, $dateColumn)
    {
        // Current month
        $currentMonth = $this->database->table($table)
            ->where($dateColumn, '>=', date('Y-m-01'))
            ->count();
            
        // Previous month
        $previousMonth = $this->database->table($table)
            ->where($dateColumn, '>=', date('Y-m-01', strtotime('-1 month')))
            ->where($dateColumn, '<', date('Y-m-01'))
            ->count();
            
        if ($previousMonth == 0) {
            return $currentMonth > 0 ? 100 : 0;
        }
        
        return round((($currentMonth - $previousMonth) / $previousMonth) * 100, 1);
    }

    /**
     * Calculate revenue growth percentage
     */
    private function calculateRevenueGrowth()
    {
        // Current month revenue
        $currentRevenue = $this->database->table('payments')
            ->where('status', 'completed')
            ->where('created_at', '>=', date('Y-m-01'))
            ->selectRaw('SUM(amount) as revenue')
            ->first();
        $currentRevenue = $currentRevenue ? $currentRevenue['revenue'] : 0;
        
        // Previous month revenue
        $previousRevenue = $this->database->table('payments')
            ->where('status', 'completed')
            ->where('created_at', '>=', date('Y-m-01', strtotime('-1 month')))
            ->where('created_at', '<', date('Y-m-01'))
            ->selectRaw('SUM(amount) as revenue')
            ->first();
        $previousRevenue = $previousRevenue ? $previousRevenue['revenue'] : 0;
        
        if ($previousRevenue == 0) {
            return $currentRevenue > 0 ? 100 : 0;
        }
        
        return round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1);
    }

    /**
     * Get recent activities
     */
    private function getRecentActivities($limit = 10)
    {
        return $this->database->table('user_activities ua')
            ->join('users u', 'ua.user_id = u.id')
            ->select([
                'ua.*',
                'u.first_name',
                'u.last_name',
                'u.avatar'
            ])
            ->whereIn('ua.activity_type', [
                'registration', 'course_enrollment', 'course_completion',
                'certificate_earned', 'payment_completed'
            ])
            ->orderBy('ua.created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get popular courses
     */
    private function getPopularCourses($limit = 5)
    {
        return $this->database->table('courses c')
            ->leftJoin('enrollments e', 'c.id = e.course_id')
            ->leftJoin('users u', 'c.instructor_id = u.id')
            ->where('c.status', COURSE_STATUS_PUBLISHED)
            ->select([
                'c.id',
                'c.title',
                'c.slug',
                'c.thumbnail',
                'c.price',
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
     * Get recent enrollments
     */
    private function getRecentEnrollments($limit = 10)
    {
        return $this->database->table('enrollments e')
            ->join('users u', 'e.user_id = u.id')
            ->join('courses c', 'e.course_id = c.id')
            ->select([
                'e.*',
                'u.first_name',
                'u.last_name',
                'u.avatar',
                'c.title as course_title',
                'c.slug as course_slug'
            ])
            ->orderBy('e.enrolled_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get system information
     */
    private function getSystemInfo()
    {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_version' => $this->getDatabaseVersion(),
            'storage_used' => $this->getStorageUsed(),
            'memory_usage' => $this->getMemoryUsage(),
            'uptime' => $this->getSystemUptime()
        ];
    }

    /**
     * Get database version
     */
    private function getDatabaseVersion()
    {
        try {
            $result = $this->database->selectOne('SELECT VERSION() as version');
            return $result ? $result['version'] : 'Unknown';
        } catch (Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Get storage usage
     */
    private function getStorageUsed()
    {
        try {
            $uploadPath = UPLOAD_PATH;
            if (is_dir($uploadPath)) {
                $size = $this->getDirectorySize($uploadPath);
                return Helper::formatFileSize($size);
            }
        } catch (Exception $e) {
            // Ignore errors
        }
        
        return 'Unknown';
    }

    /**
     * Get directory size recursively
     */
    private function getDirectorySize($directory)
    {
        $size = 0;
        
        if (is_dir($directory)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }
        
        return $size;
    }

    /**
     * Get memory usage
     */
    private function getMemoryUsage()
    {
        return [
            'current' => Helper::formatFileSize(memory_get_usage(true)),
            'peak' => Helper::formatFileSize(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit')
        ];
    }

    /**
     * Get system uptime (approximate)
     */
    private function getSystemUptime()
    {
        if (function_exists('sys_getloadavg') && file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            $uptime = explode(' ', $uptime)[0];
            
            $days = floor($uptime / 86400);
            $hours = floor(($uptime % 86400) / 3600);
            $minutes = floor(($uptime % 3600) / 60);
            
            return "{$days}d {$hours}h {$minutes}m";
        }
        
        return 'Unknown';
    }

    /**
     * Get charts data
     */
    private function getChartsData()
    {
        return [
            'user_registrations' => $this->getUserRegistrationChart(),
            'course_enrollments' => $this->getCourseEnrollmentChart(),
            'revenue_chart' => $this->getRevenueChart(),
            'course_completion' => $this->getCourseCompletionChart()
        ];
    }

    /**
     * Get user registration chart data (last 12 months)
     */
    private function getUserRegistrationChart()
    {
        $data = $this->database->table('users')
            ->where('created_at', '>=', date('Y-m-d', strtotime('-12 months')))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->get();
            
        // Fill missing months with zero
        $months = [];
        $counts = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $months[] = date('M Y', strtotime($month . '-01'));
            
            $found = false;
            foreach ($data as $item) {
                if ($item['month'] === $month) {
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
            'labels' => $months,
            'data' => $counts
        ];
    }

    /**
     * Get course enrollment chart data
     */
    private function getCourseEnrollmentChart()
    {
        $data = $this->database->table('enrollments')
            ->where('enrolled_at', '>=', date('Y-m-d', strtotime('-12 months')))
            ->selectRaw('DATE_FORMAT(enrolled_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->get();
            
        // Fill missing months with zero
        $months = [];
        $counts = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $months[] = date('M Y', strtotime($month . '-01'));
            
            $found = false;
            foreach ($data as $item) {
                if ($item['month'] === $month) {
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
            'labels' => $months,
            'data' => $counts
        ];
    }

    /**
     * Get revenue chart data
     */
    private function getRevenueChart()
    {
        $data = $this->database->table('payments')
            ->where('status', 'completed')
            ->where('created_at', '>=', date('Y-m-d', strtotime('-12 months')))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as revenue')
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->get();
            
        // Fill missing months with zero
        $months = [];
        $revenues = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $months[] = date('M Y', strtotime($month . '-01'));
            
            $found = false;
            foreach ($data as $item) {
                if ($item['month'] === $month) {
                    $revenues[] = (float)$item['revenue'];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $revenues[] = 0;
            }
        }
        
        return [
            'labels' => $months,
            'data' => $revenues
        ];
    }

    /**
     * Get course completion chart data
     */
    private function getCourseCompletionChart()
    {
        $data = $this->database->table('enrollments')
            ->where('completed_at', '>=', date('Y-m-d', strtotime('-12 months')))
            ->selectRaw('DATE_FORMAT(completed_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->get();
            
        // Fill missing months with zero
        $months = [];
        $counts = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $months[] = date('M Y', strtotime($month . '-01'));
            
            $found = false;
            foreach ($data as $item) {
                if ($item['month'] === $month) {
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
            'labels' => $months,
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
        
        switch ($type) {
            case 'stats':
                return $this->json($this->getDashboardStats());
                
            case 'activities':
                return $this->json($this->getRecentActivities());
                
            case 'popular_courses':
                return $this->json($this->getPopularCourses());
                
            case 'recent_enrollments':
                return $this->json($this->getRecentEnrollments());
                
            case 'charts':
                return $this->json($this->getChartsData());
                
            default:
                return $this->error('Invalid data type', [], 400);
        }
    }

    /**
     * Export dashboard data
     */
    public function export()
    {
        $format = $this->query('format', 'csv');
        $type = $this->query('type', 'stats');
        
        switch ($type) {
            case 'stats':
                $data = $this->getDashboardStats();
                break;
                
            case 'enrollments':
                $data = $this->getRecentEnrollments(1000); // Get more for export
                break;
                
            case 'activities':
                $data = $this->getRecentActivities(1000);
                break;
                
            default:
                return $this->error('Invalid export type', [], 400);
        }
        
        if ($format === 'json') {
            $this->response->setHeader('Content-Type', 'application/json');
            $this->response->setHeader('Content-Disposition', 'attachment; filename="dashboard_' . $type . '_' . date('Y-m-d') . '.json"');
            return json_encode($data, JSON_PRETTY_PRINT);
        }
        
        // Default to CSV
        $csv = $this->arrayToCsv($data);
        $this->response->setHeader('Content-Type', 'text/csv');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="dashboard_' . $type . '_' . date('Y-m-d') . '.csv"');
        return $csv;
    }

    /**
     * Convert array to CSV
     */
    private function arrayToCsv($data)
    {
        if (empty($data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Add header row
        if (is_array($data[0])) {
            fputcsv($output, array_keys($data[0]));
            
            // Add data rows
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        } else {
            // Simple key-value pairs
            fputcsv($output, ['Key', 'Value']);
            foreach ($data as $key => $value) {
                fputcsv($output, [$key, $value]);
            }
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}