<?php

/**
 * Lesson Model
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Model.php';

class Lesson extends Model
{
    protected $table = 'lessons';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'course_id', 'module_id', 'title', 'slug', 'content', 'type',
        'video_url', 'video_duration', 'audio_url', 'document_url',
        'interactive_content', 'sort_order', 'is_preview', 'is_published',
        'resources', 'notes'
    ];
    
    protected $casts = [
        'course_id' => 'integer',
        'module_id' => 'integer',
        'video_duration' => 'integer',
        'sort_order' => 'integer',
        'is_preview' => 'boolean',
        'is_published' => 'boolean',
        'interactive_content' => 'json',
        'resources' => 'json'
    ];

    /**
     * Boot the model
     */
    protected function initialize()
    {
        $this->on('creating', function($data) {
            if (!isset($data['slug']) && isset($data['title'])) {
                $data['slug'] = $this->generateUniqueSlug($data['title']);
            }
            
            if (!isset($data['sort_order'])) {
                $data['sort_order'] = $this->getNextSortOrder($data['module_id'] ?? null, $data['course_id']);
            }
            
            if (!isset($data['is_published'])) {
                $data['is_published'] = 1;
            }
            
            if (!isset($data['type'])) {
                $data['type'] = LESSON_TYPE_TEXT;
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
        $query = $this->database->table('lessons')->where('slug', $slug);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Get next sort order
     */
    private function getNextSortOrder($moduleId, $courseId)
    {
        $query = $this->database->table('lessons')
            ->where('course_id', $courseId);
            
        if ($moduleId) {
            $query->where('module_id', $moduleId);
        } else {
            $query->whereNull('module_id');
        }
        
        $result = $query->selectRaw('MAX(sort_order) as max_order')->first();
        
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
     * Get module
     */
    public function module()
    {
        if (!$this->module_id) {
            return null;
        }
        
        return $this->database->table('course_modules')
            ->where('id', $this->module_id)
            ->first();
    }

    /**
     * Get lesson progress for user
     */
    public function getProgressForUser($userId)
    {
        return $this->database->table('lesson_progress')
            ->where('lesson_id', $this->id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Check if user completed this lesson
     */
    public function isUserCompleted($userId)
    {
        $progress = $this->getProgressForUser($userId);
        return $progress && $progress['status'] === 'completed';
    }

    /**
     * Get user progress percentage
     */
    public function getUserProgressPercentage($userId)
    {
        $progress = $this->getProgressForUser($userId);
        return $progress ? $progress['progress_percentage'] : 0;
    }

    /**
     * Update user progress
     */
    public function updateUserProgress($userId, $progressPercentage, $timeSpent = 0, $lastPosition = 0)
    {
        $existingProgress = $this->getProgressForUser($userId);
        
        $status = 'in_progress';
        if ($progressPercentage >= 100) {
            $status = 'completed';
        } elseif ($progressPercentage > 0) {
            $status = 'in_progress';
        } else {
            $status = 'not_started';
        }
        
        $progressData = [
            'progress_percentage' => min(100, max(0, $progressPercentage)),
            'time_spent' => max(0, $timeSpent),
            'last_position' => max(0, $lastPosition),
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($status === 'completed' && (!$existingProgress || $existingProgress['status'] !== 'completed')) {
            $progressData['completed_at'] = date('Y-m-d H:i:s');
        }
        
        if ($existingProgress) {
            // Update existing progress
            $this->database->update('lesson_progress', $progressData, 
                'lesson_id = :lesson_id AND user_id = :user_id', [
                    'lesson_id' => $this->id,
                    'user_id' => $userId
                ]);
        } else {
            // Create new progress record
            $progressData = array_merge($progressData, [
                'user_id' => $userId,
                'lesson_id' => $this->id,
                'course_id' => $this->course_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->database->insert('lesson_progress', $progressData);
        }
        
        // Update course progress
        $this->updateCourseProgress($userId);
        
        // Log activity if completed
        if ($status === 'completed' && (!$existingProgress || $existingProgress['status'] !== 'completed')) {
            UserActivity::logLessonCompletion($userId, $this->id, $timeSpent);
        }
        
        return true;
    }

    /**
     * Mark lesson as completed for user
     */
    public function markAsCompleted($userId, $timeSpent = 0)
    {
        return $this->updateUserProgress($userId, 100, $timeSpent);
    }

    /**
     * Update course progress based on lesson completion
     */
    private function updateCourseProgress($userId)
    {
        // Get total lessons in course
        $totalLessons = $this->database->table('lessons')
            ->where('course_id', $this->course_id)
            ->where('is_published', 1)
            ->count();
            
        if ($totalLessons === 0) {
            return;
        }
        
        // Get completed lessons
        $completedLessons = $this->database->table('lesson_progress lp')
            ->join('lessons l', 'lp.lesson_id = l.id')
            ->where('l.course_id', $this->course_id)
            ->where('lp.user_id', $userId)
            ->where('lp.status', 'completed')
            ->where('l.is_published', 1)
            ->count();
            
        $progress = round(($completedLessons / $totalLessons) * 100, 2);
        
        // Update enrollment progress
        $this->database->update('enrollments', [
            'progress' => $progress,
            'updated_at' => date('Y-m-d H:i:s'),
            'last_accessed_at' => date('Y-m-d H:i:s')
        ], 'course_id = :course_id AND user_id = :user_id', [
            'course_id' => $this->course_id,
            'user_id' => $userId
        ]);
        
        // Mark course as completed if 100%
        if ($progress >= 100) {
            $this->database->update('enrollments', [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s')
            ], 'course_id = :course_id AND user_id = :user_id AND status != :status', [
                'course_id' => $this->course_id,
                'user_id' => $userId,
                'status' => 'completed'
            ]);
        }
    }

    /**
     * Get next lesson
     */
    public function getNext()
    {
        $query = $this->database->table('lessons')
            ->where('course_id', $this->course_id)
            ->where('is_published', 1)
            ->where('sort_order', '>', $this->sort_order)
            ->orderBy('sort_order', 'ASC');
            
        if ($this->module_id) {
            // First try to get next lesson in same module
            $nextInModule = $query->where('module_id', $this->module_id)->first();
            if ($nextInModule) {
                return $nextInModule;
            }
            
            // Then try next module
            $nextModule = $this->database->table('course_modules')
                ->where('course_id', $this->course_id)
                ->where('is_published', 1)
                ->where('sort_order', '>', $this->module()['sort_order'])
                ->orderBy('sort_order', 'ASC')
                ->first();
                
            if ($nextModule) {
                return $this->database->table('lessons')
                    ->where('module_id', $nextModule['id'])
                    ->where('is_published', 1)
                    ->orderBy('sort_order', 'ASC')
                    ->first();
            }
        } else {
            return $query->first();
        }
        
        return null;
    }

    /**
     * Get previous lesson
     */
    public function getPrevious()
    {
        $query = $this->database->table('lessons')
            ->where('course_id', $this->course_id)
            ->where('is_published', 1)
            ->where('sort_order', '<', $this->sort_order)
            ->orderBy('sort_order', 'DESC');
            
        if ($this->module_id) {
            // First try to get previous lesson in same module
            $prevInModule = $query->where('module_id', $this->module_id)->first();
            if ($prevInModule) {
                return $prevInModule;
            }
            
            // Then try previous module
            $prevModule = $this->database->table('course_modules')
                ->where('course_id', $this->course_id)
                ->where('is_published', 1)
                ->where('sort_order', '<', $this->module()['sort_order'])
                ->orderBy('sort_order', 'DESC')
                ->first();
                
            if ($prevModule) {
                return $this->database->table('lessons')
                    ->where('module_id', $prevModule['id'])
                    ->where('is_published', 1)
                    ->orderBy('sort_order', 'DESC')
                    ->first();
            }
        } else {
            return $query->first();
        }
        
        return null;
    }

    /**
     * Check if user can access this lesson
     */
    public function canUserAccess($userId)
    {
        // Check if user is enrolled in course
        $enrollment = $this->database->table('enrollments')
            ->where('course_id', $this->course_id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();
            
        if (!$enrollment) {
            return false;
        }
        
        // If it's a preview lesson, allow access
        if ($this->is_preview) {
            return true;
        }
        
        // Check if previous lessons are completed (if required)
        $course = $this->course();
        if ($course && isset($course['completion_criteria'])) {
            $criteria = json_decode($course['completion_criteria'], true);
            if (isset($criteria['sequential_access']) && $criteria['sequential_access']) {
                return $this->isPreviousLessonsCompleted($userId);
            }
        }
        
        return true;
    }

    /**
     * Check if previous lessons are completed
     */
    private function isPreviousLessonsCompleted($userId)
    {
        $previousLessons = $this->database->table('lessons')
            ->where('course_id', $this->course_id)
            ->where('is_published', 1)
            ->where('sort_order', '<', $this->sort_order);
            
        if ($this->module_id) {
            $previousLessons->where('module_id', $this->module_id);
        }
        
        $previousLessons = $previousLessons->get();
        
        foreach ($previousLessons as $lesson) {
            $progress = $this->database->table('lesson_progress')
                ->where('lesson_id', $lesson['id'])
                ->where('user_id', $userId)
                ->where('status', 'completed')
                ->first();
                
            if (!$progress) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get lesson resources
     */
    public function getResources()
    {
        if (!$this->resources) {
            return [];
        }
        
        $resources = json_decode($this->resources, true);
        return is_array($resources) ? $resources : [];
    }

    /**
     * Add resource to lesson
     */
    public function addResource($name, $url, $type = 'document')
    {
        $resources = $this->getResources();
        
        $resource = [
            'id' => Helper::uuid(),
            'name' => $name,
            'url' => $url,
            'type' => $type,
            'added_at' => date('Y-m-d H:i:s')
        ];
        
        $resources[] = $resource;
        
        return $this->database->update('lessons', [
            'resources' => json_encode($resources),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Remove resource from lesson
     */
    public function removeResource($resourceId)
    {
        $resources = $this->getResources();
        
        $resources = array_filter($resources, function($resource) use ($resourceId) {
            return $resource['id'] !== $resourceId;
        });
        
        return $this->database->update('lessons', [
            'resources' => json_encode(array_values($resources)),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $this->id]);
    }

    /**
     * Reorder lessons
     */
    public static function reorder($lessonIds, $moduleId = null, $courseId = null)
    {
        $database = App::getInstance()->getDatabase();
        
        foreach ($lessonIds as $index => $lessonId) {
            $updateData = [
                'sort_order' => $index + 1,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $whereClause = 'id = :id';
            $whereParams = ['id' => $lessonId];
            
            if ($moduleId !== null) {
                $updateData['module_id'] = $moduleId;
            }
            
            if ($courseId !== null) {
                $whereClause .= ' AND course_id = :course_id';
                $whereParams['course_id'] = $courseId;
            }
            
            $database->update('lessons', $updateData, $whereClause, $whereParams);
        }
        
        return true;
    }

    /**
     * Duplicate lesson
     */
    public function duplicate($newCourseId = null, $newModuleId = null)
    {
        $newCourseId = $newCourseId ?: $this->course_id;
        $newModuleId = $newModuleId ?: $this->module_id;
        
        $lessonData = [
            'course_id' => $newCourseId,
            'module_id' => $newModuleId,
            'title' => $this->title . ' (Copy)',
            'slug' => $this->slug . '-copy',
            'content' => $this->content,
            'type' => $this->type,
            'video_url' => $this->video_url,
            'video_duration' => $this->video_duration,
            'audio_url' => $this->audio_url,
            'document_url' => $this->document_url,
            'interactive_content' => $this->interactive_content,
            'sort_order' => $this->getNextSortOrder($newModuleId, $newCourseId),
            'is_preview' => $this->is_preview,
            'is_published' => $this->is_published,
            'resources' => $this->resources,
            'notes' => $this->notes,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->database->insert('lessons', $lessonData);
    }

    /**
     * Get lessons by course
     */
    public static function getByCourse($courseId, $publishedOnly = true, $userId = null)
    {
        $database = App::getInstance()->getDatabase();
        $query = $database->table('lessons')
            ->where('course_id', $courseId);
            
        if ($publishedOnly) {
            $query->where('is_published', 1);
        }
        
        $lessons = $query->orderBy('sort_order', 'ASC')->get();
        
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
        
        return $lessons;
    }

    /**
     * Find lesson by slug
     */
    public static function findBySlug($slug)
    {
        $database = App::getInstance()->getDatabase();
        $lessonData = $database->table('lessons')
            ->where('slug', $slug)
            ->where('is_published', 1)
            ->first();
            
        if ($lessonData) {
            $lesson = new self($database);
            $lesson->fill($lessonData);
            return $lesson;
        }
        
        return null;
    }
}