<?php

/**
 * Quiz Model
 * LMS Pro - Learning Management System
 */

require_once __DIR__ . '/../../core/Model.php';

class Quiz extends Model
{
    protected $table = 'quizzes';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'course_id', 'lesson_id', 'title', 'description', 'instructions',
        'time_limit', 'max_attempts', 'passing_score', 'randomize_questions',
        'show_results', 'show_correct_answers', 'is_required', 'is_published',
        'available_from', 'available_until'
    ];
    
    protected $casts = [
        'course_id' => 'integer',
        'lesson_id' => 'integer',
        'time_limit' => 'integer',
        'max_attempts' => 'integer',
        'passing_score' => 'float',
        'randomize_questions' => 'boolean',
        'show_results' => 'boolean',
        'show_correct_answers' => 'boolean',
        'is_required' => 'boolean',
        'is_published' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime'
    ];

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
     * Get lesson
     */
    public function lesson()
    {
        if (!$this->lesson_id) {
            return null;
        }
        
        return $this->database->table('lessons')
            ->where('id', $this->lesson_id)
            ->first();
    }

    /**
     * Get quiz questions
     */
    public function questions($randomize = false)
    {
        $query = $this->database->table('quiz_questions qq')
            ->join('questions q', 'qq.question_id = q.id')
            ->where('qq.quiz_id', $this->id)
            ->select(['q.*', 'qq.sort_order', 'qq.points'])
            ->orderBy('qq.sort_order', 'ASC');
            
        $questions = $query->get();
        
        if ($randomize && $this->randomize_questions) {
            shuffle($questions);
        }
        
        // Get options for each question
        foreach ($questions as &$question) {
            $question['options'] = $this->database->table('question_options')
                ->where('question_id', $question['id'])
                ->orderBy('sort_order', 'ASC')
                ->get();
        }
        
        return $questions;
    }

    /**
     * Get quiz attempts
     */
    public function attempts($userId = null)
    {
        $query = $this->database->table('quiz_attempts qa')
            ->join('users u', 'qa.user_id = u.id')
            ->where('qa.quiz_id', $this->id)
            ->select(['qa.*', 'u.first_name', 'u.last_name', 'u.email']);
            
        if ($userId) {
            $query->where('qa.user_id', $userId);
        }
        
        return $query->orderBy('qa.started_at', 'DESC')->get();
    }

    /**
     * Get user's best attempt
     */
    public function getBestAttempt($userId)
    {
        return $this->database->table('quiz_attempts')
            ->where('quiz_id', $this->id)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->orderBy('score', 'DESC')
            ->first();
    }

    /**
     * Get user's latest attempt
     */
    public function getLatestAttempt($userId)
    {
        return $this->database->table('quiz_attempts')
            ->where('quiz_id', $this->id)
            ->where('user_id', $userId)
            ->orderBy('started_at', 'DESC')
            ->first();
    }

    /**
     * Get user's attempt count
     */
    public function getUserAttemptCount($userId)
    {
        return $this->database->table('quiz_attempts')
            ->where('quiz_id', $this->id)
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * Check if user can take quiz
     */
    public function canUserTake($userId)
    {
        // Check if quiz is published
        if (!$this->is_published) {
            return false;
        }
        
        // Check availability dates
        $now = date('Y-m-d H:i:s');
        
        if ($this->available_from && $this->available_from > $now) {
            return false;
        }
        
        if ($this->available_until && $this->available_until < $now) {
            return false;
        }
        
        // Check attempt limit
        if ($this->max_attempts > 0) {
            $attemptCount = $this->getUserAttemptCount($userId);
            if ($attemptCount >= $this->max_attempts) {
                return false;
            }
        }
        
        // Check if user is enrolled in course
        $enrollment = $this->database->table('enrollments')
            ->where('course_id', $this->course_id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();
            
        return $enrollment !== null;
    }

    /**
     * Start quiz attempt
     */
    public function startAttempt($userId)
    {
        if (!$this->canUserTake($userId)) {
            return false;
        }
        
        // Check for existing incomplete attempt
        $existingAttempt = $this->database->table('quiz_attempts')
            ->where('quiz_id', $this->id)
            ->where('user_id', $userId)
            ->where('status', 'in_progress')
            ->first();
            
        if ($existingAttempt) {
            return $existingAttempt['id'];
        }
        
        // Create new attempt
        $attemptData = [
            'quiz_id' => $this->id,
            'user_id' => $userId,
            'status' => 'in_progress',
            'started_at' => date('Y-m-d H:i:s'),
            'expires_at' => $this->time_limit ? date('Y-m-d H:i:s', time() + ($this->time_limit * 60)) : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->database->insert('quiz_attempts', $attemptData);
    }

    /**
     * Submit quiz attempt
     */
    public function submitAttempt($attemptId, $answers)
    {
        $attempt = $this->database->table('quiz_attempts')
            ->where('id', $attemptId)
            ->where('quiz_id', $this->id)
            ->first();
            
        if (!$attempt || $attempt['status'] !== 'in_progress') {
            return false;
        }
        
        // Check if attempt has expired
        if ($attempt['expires_at'] && $attempt['expires_at'] < date('Y-m-d H:i:s')) {
            $this->database->update('quiz_attempts', [
                'status' => 'expired',
                'completed_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $attemptId]);
            
            return false;
        }
        
        // Calculate score
        $score = $this->calculateScore($answers);
        
        // Update attempt
        $this->database->update('quiz_attempts', [
            'status' => 'completed',
            'score' => $score,
            'answers' => json_encode($answers),
            'completed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $attemptId]);
        
        // Save individual answers
        foreach ($answers as $questionId => $answer) {
            $this->database->insert('quiz_answers', [
                'attempt_id' => $attemptId,
                'question_id' => $questionId,
                'answer' => is_array($answer) ? json_encode($answer) : $answer,
                'is_correct' => $this->isAnswerCorrect($questionId, $answer),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Log activity
        UserActivity::logQuizAttempt($attempt['user_id'], $this->id, $score);
        
        // Award points if passed
        if ($score >= $this->passing_score) {
            $this->awardPoints($attempt['user_id']);
        }
        
        return [
            'score' => $score,
            'passed' => $score >= $this->passing_score,
            'total_questions' => count($answers)
        ];
    }

    /**
     * Calculate quiz score
     */
    private function calculateScore($answers)
    {
        $totalPoints = 0;
        $earnedPoints = 0;
        
        foreach ($answers as $questionId => $answer) {
            $question = $this->database->table('quiz_questions qq')
                ->join('questions q', 'qq.question_id = q.id')
                ->where('qq.quiz_id', $this->id)
                ->where('q.id', $questionId)
                ->select(['q.*', 'qq.points'])
                ->first();
                
            if ($question) {
                $points = $question['points'] ?: 1;
                $totalPoints += $points;
                
                if ($this->isAnswerCorrect($questionId, $answer)) {
                    $earnedPoints += $points;
                }
            }
        }
        
        return $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
    }

    /**
     * Check if answer is correct
     */
    private function isAnswerCorrect($questionId, $answer)
    {
        $question = $this->database->table('questions')
            ->where('id', $questionId)
            ->first();
            
        if (!$question) {
            return false;
        }
        
        switch ($question['type']) {
            case QUIZ_TYPE_MULTIPLE_CHOICE:
                $correctOption = $this->database->table('question_options')
                    ->where('question_id', $questionId)
                    ->where('is_correct', 1)
                    ->first();
                return $correctOption && $correctOption['id'] == $answer;
                
            case QUIZ_TYPE_TRUE_FALSE:
                return $question['correct_answer'] == $answer;
                
            case QUIZ_TYPE_SHORT_ANSWER:
                $correctAnswers = json_decode($question['correct_answer'], true);
                if (is_array($correctAnswers)) {
                    return in_array(strtolower(trim($answer)), array_map('strtolower', $correctAnswers));
                }
                return strtolower(trim($answer)) === strtolower(trim($question['correct_answer']));
                
            case QUIZ_TYPE_FILL_BLANK:
                // Simple text matching for now
                return strtolower(trim($answer)) === strtolower(trim($question['correct_answer']));
                
            default:
                return false;
        }
    }

    /**
     * Award points for quiz completion
     */
    private function awardPoints($userId)
    {
        $points = POINTS_QUIZ_COMPLETION;
        
        $pointData = [
            'user_id' => $userId,
            'points' => $points,
            'type' => 'quiz_completion',
            'related_type' => 'quiz',
            'related_id' => $this->id,
            'description' => "Completed quiz: {$this->title}",
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->database->insert('user_points', $pointData);
    }

    /**
     * Get quiz statistics
     */
    public function getStatistics()
    {
        $stats = [];
        
        // Total attempts
        $stats['total_attempts'] = $this->database->table('quiz_attempts')
            ->where('quiz_id', $this->id)
            ->count();
            
        // Completed attempts
        $stats['completed_attempts'] = $this->database->table('quiz_attempts')
            ->where('quiz_id', $this->id)
            ->where('status', 'completed')
            ->count();
            
        // Average score
        $avgResult = $this->database->table('quiz_attempts')
            ->where('quiz_id', $this->id)
            ->where('status', 'completed')
            ->selectRaw('AVG(score) as avg_score')
            ->first();
        $stats['average_score'] = $avgResult ? round($avgResult['avg_score'], 1) : 0;
        
        // Pass rate
        $passedAttempts = $this->database->table('quiz_attempts')
            ->where('quiz_id', $this->id)
            ->where('status', 'completed')
            ->where('score', '>=', $this->passing_score)
            ->count();
        $stats['pass_rate'] = $stats['completed_attempts'] > 0 ? 
            round(($passedAttempts / $stats['completed_attempts']) * 100, 1) : 0;
            
        // Score distribution
        $stats['score_distribution'] = $this->database->table('quiz_attempts')
            ->where('quiz_id', $this->id)
            ->where('status', 'completed')
            ->selectRaw('
                COUNT(CASE WHEN score >= 90 THEN 1 END) as excellent,
                COUNT(CASE WHEN score >= 80 AND score < 90 THEN 1 END) as good,
                COUNT(CASE WHEN score >= 70 AND score < 80 THEN 1 END) as average,
                COUNT(CASE WHEN score < 70 THEN 1 END) as poor
            ')
            ->first();
            
        return $stats;
    }

    /**
     * Duplicate quiz
     */
    public function duplicate($newCourseId = null, $newLessonId = null)
    {
        $newCourseId = $newCourseId ?: $this->course_id;
        
        $quizData = [
            'course_id' => $newCourseId,
            'lesson_id' => $newLessonId,
            'title' => $this->title . ' (Copy)',
            'description' => $this->description,
            'instructions' => $this->instructions,
            'time_limit' => $this->time_limit,
            'max_attempts' => $this->max_attempts,
            'passing_score' => $this->passing_score,
            'randomize_questions' => $this->randomize_questions,
            'show_results' => $this->show_results,
            'show_correct_answers' => $this->show_correct_answers,
            'is_required' => $this->is_required,
            'is_published' => 0, // Start as draft
            'available_from' => $this->available_from,
            'available_until' => $this->available_until,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $newQuizId = $this->database->insert('quizzes', $quizData);
        
        if ($newQuizId) {
            // Duplicate questions
            $questions = $this->questions();
            foreach ($questions as $question) {
                // Create new question
                $newQuestionId = $this->database->insert('questions', [
                    'type' => $question['type'],
                    'question_text' => $question['question_text'],
                    'correct_answer' => $question['correct_answer'],
                    'explanation' => $question['explanation'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                // Link to quiz
                $this->database->insert('quiz_questions', [
                    'quiz_id' => $newQuizId,
                    'question_id' => $newQuestionId,
                    'sort_order' => $question['sort_order'],
                    'points' => $question['points']
                ]);
                
                // Duplicate options
                foreach ($question['options'] as $option) {
                    $this->database->insert('question_options', [
                        'question_id' => $newQuestionId,
                        'option_text' => $option['option_text'],
                        'is_correct' => $option['is_correct'],
                        'sort_order' => $option['sort_order']
                    ]);
                }
            }
        }
        
        return $newQuizId;
    }

    /**
     * Get quizzes by course
     */
    public static function getByCourse($courseId, $publishedOnly = true)
    {
        $database = App::getInstance()->getDatabase();
        $query = $database->table('quizzes')
            ->where('course_id', $courseId);
            
        if ($publishedOnly) {
            $query->where('is_published', 1);
        }
        
        return $query->orderBy('created_at', 'ASC')->get();
    }
}