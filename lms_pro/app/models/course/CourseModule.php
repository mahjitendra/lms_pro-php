<?php

/**
 * Course Module Model
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Model.php';

class CourseModule extends Model
{
    protected $table = 'course_modules';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'course_id', 'title', 'description', 'sort_order', 'is_published'
    ];
    
    protected $casts = [
        'course_id' => 'integer',
        'sort_order' => 'integer',
        'is_published' => 'boolean'
    ];

    /**
     * Boot the model
     */
    protected function initialize()
    {
        $this->on('creating', function($data) {
            if (!isset($data['sort_order'])) {
                $data['sort_order'] = $this->getNextSortOrder($data['course_id']);
            }
            
            if (!isset($data['is_published'])) {
                $data['is_published'] = 1;
            }
        });
    }

    /**
     * Get next sort order
     */
    private function getNextSortOrder($courseId)
    {
        $result = $this->database->table('course_modules')
            ->where('course_id', $courseId)
            ->selectRaw('MAX(sort_order) as max_order')
            ->first();
            
        return $result && $result['max_order'] ? $result['max_order'] + 1 : 1;
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
     * Get module lessons
     */
    public function lessons()
    {
        return $this->database->table('lessons')
            ->where('module_id', $this->id)
            ->where('is_published', 1)
            ->orderBy('sort_order', 'ASC')
            ->get();
    }

    /**
     * Get lessons count
     */
    public function getLessonsCount()
    {
        return $this->database->table('lessons')
            ->where('module_id', $this->id)
            ->where('is_published', 1)
            ->count();
    }

    /**
     * Get total duration
     */
    public function getTotalDuration()
    {
        $result = $this->database->table('lessons')
            ->where('module_id', $this->id)
            ->where('is_published', 1)
            ->selectRaw('SUM(video_duration) as total_duration')
            ->first();
            
        return $result ? (int)$result['total_duration'] : 0;
    }

    /**
     * Get user progress for this module
     */
    public function getUserProgress($userId)
    {
        $totalLessons = $this->getLessonsCount();
        
        if ($totalLessons === 0) {
            return 100; // Consider empty module as completed
        }
        
        $completedLessons = $this->database->table('lesson_progress lp')
            ->join('lessons l', 'lp.lesson_id = l.id')
            ->where('l.module_id', $this->id)
            ->where('lp.user_id', $userId)
            ->where('lp.status', 'completed')
            ->count();
            
        return round(($completedLessons / $totalLessons) * 100, 1);
    }

    /**
     * Check if user completed this module
     */
    public function isUserCompleted($userId)
    {
        return $this->getUserProgress($userId) >= 100;
    }

    /**
     * Get next lesson for user
     */
    public function getNextLessonForUser($userId)
    {
        $lessons = $this->lessons();
        
        foreach ($lessons as $lesson) {
            $progress = $this->database->table('lesson_progress')
                ->where('lesson_id', $lesson['id'])
                ->where('user_id', $userId)
                ->first();
                
            if (!$progress || $progress['status'] !== 'completed') {
                return $lesson;
            }
        }
        
        return null; // All lessons completed
    }

    /**
     * Reorder modules
     */
    public static function reorder($moduleIds, $courseId)
    {
        $database = App::getInstance()->getDatabase();
        
        foreach ($moduleIds as $index => $moduleId) {
            $database->update('course_modules', [
                'sort_order' => $index + 1,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id AND course_id = :course_id', [
                'id' => $moduleId,
                'course_id' => $courseId
            ]);
        }
        
        return true;
    }

    /**
     * Duplicate module
     */
    public function duplicate($newCourseId = null)
    {
        $newCourseId = $newCourseId ?: $this->course_id;
        
        // Create new module
        $moduleData = [
            'course_id' => $newCourseId,
            'title' => $this->title . ' (Copy)',
            'description' => $this->description,
            'sort_order' => $this->getNextSortOrder($newCourseId),
            'is_published' => $this->is_published,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $newModuleId = $this->database->insert('course_modules', $moduleData);
        
        if ($newModuleId) {
            // Duplicate lessons
            $lessons = $this->lessons();
            foreach ($lessons as $lesson) {
                $lessonData = [
                    'course_id' => $newCourseId,
                    'module_id' => $newModuleId,
                    'title' => $lesson['title'],
                    'slug' => $lesson['slug'] . '-copy',
                    'content' => $lesson['content'],
                    'type' => $lesson['type'],
                    'video_url' => $lesson['video_url'],
                    'video_duration' => $lesson['video_duration'],
                    'audio_url' => $lesson['audio_url'],
                    'document_url' => $lesson['document_url'],
                    'interactive_content' => $lesson['interactive_content'],
                    'sort_order' => $lesson['sort_order'],
                    'is_preview' => $lesson['is_preview'],
                    'is_published' => $lesson['is_published'],
                    'resources' => $lesson['resources'],
                    'notes' => $lesson['notes'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $this->database->insert('lessons', $lessonData);
            }
            
            return $newModuleId;
        }
        
        return false;
    }

    /**
     * Get modules by course
     */
    public static function getByCourse($courseId, $publishedOnly = true)
    {
        $database = App::getInstance()->getDatabase();
        $query = $database->table('course_modules')
            ->where('course_id', $courseId);
            
        if ($publishedOnly) {
            $query->where('is_published', 1);
        }
        
        $modules = $query->orderBy('sort_order', 'ASC')->get();
        
        // Add lessons count and duration for each module
        foreach ($modules as &$module) {
            $lessonsQuery = $database->table('lessons')
                ->where('module_id', $module['id']);
                
            if ($publishedOnly) {
                $lessonsQuery->where('is_published', 1);
            }
            
            $module['lessons_count'] = $lessonsQuery->count();
            
            $durationResult = $lessonsQuery->selectRaw('SUM(video_duration) as total_duration')->first();
            $module['total_duration'] = $durationResult ? (int)$durationResult['total_duration'] : 0;
        }
        
        return $modules;
    }

    /**
     * Get module with lessons
     */
    public static function getWithLessons($moduleId, $userId = null)
    {
        $database = App::getInstance()->getDatabase();
        
        $module = $database->table('course_modules')
            ->where('id', $moduleId)
            ->first();
            
        if (!$module) {
            return null;
        }
        
        $lessons = $database->table('lessons')
            ->where('module_id', $moduleId)
            ->where('is_published', 1)
            ->orderBy('sort_order', 'ASC')
            ->get();
            
        // Add progress information if user ID provided
        if ($userId) {
            foreach ($lessons as &$lesson) {
                $progress = $database->table('lesson_progress')
                    ->where('lesson_id', $lesson['id'])
                    ->where('user_id', $userId)
                    ->first();
                    
                $lesson['progress'] = $progress ? [
                    'status' => $progress['status'],
                    'progress_percentage' => $progress['progress_percentage'],
                    'time_spent' => $progress['time_spent'],
                    'last_position' => $progress['last_position'],
                    'completed_at' => $progress['completed_at']
                ] : [
                    'status' => 'not_started',
                    'progress_percentage' => 0,
                    'time_spent' => 0,
                    'last_position' => 0,
                    'completed_at' => null
                ];
            }
        }
        
        $module['lessons'] = $lessons;
        
        return $module;
    }

    /**
     * Check if module can be deleted
     */
    public function canBeDeleted()
    {
        // Check if module has lessons
        $hasLessons = $this->database->table('lessons')
            ->where('module_id', $this->id)
            ->exists();
            
        return !$hasLessons;
    }

    /**
     * Delete module and all related data
     */
    public function deleteWithLessons()
    {
        $this->database->beginTransaction();
        
        try {
            // Delete lesson progress for all lessons in this module
            $this->database->query("
                DELETE lp FROM lesson_progress lp
                INNER JOIN lessons l ON lp.lesson_id = l.id
                WHERE l.module_id = :module_id
            ", ['module_id' => $this->id]);
            
            // Delete lessons
            $this->database->delete('lessons', 'module_id = :module_id', ['module_id' => $this->id]);
            
            // Delete module
            $this->database->delete('course_modules', 'id = :id', ['id' => $this->id]);
            
            $this->database->commit();
            return true;
            
        } catch (Exception $e) {
            $this->database->rollback();
            return false;
        }
    }
}