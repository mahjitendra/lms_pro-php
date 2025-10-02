<?php

/**
 * Route Configuration
 * LMS Pro - Learning Management System
 */

defined('BASEPATH') OR exit('No direct script access allowed');

return [
    // Default route
    'default' => 'auth/login',
    
    // Route patterns
    'routes' => [
        // Authentication Routes
        'GET|POST /login' => 'auth/LoginController@index',
        'POST /login/authenticate' => 'auth/LoginController@authenticate',
        'GET|POST /register' => 'auth/RegisterController@index',
        'POST /register/store' => 'auth/RegisterController@store',
        'GET /logout' => 'auth/LoginController@logout',
        'GET|POST /forgot-password' => 'auth/ForgotPasswordController@index',
        'POST /forgot-password/send' => 'auth/ForgotPasswordController@send',
        'GET|POST /reset-password/{token}' => 'auth/ResetPasswordController@index',
        'POST /reset-password/update' => 'auth/ResetPasswordController@update',
        'GET|POST /two-factor' => 'auth/TwoFactorController@index',
        'POST /two-factor/verify' => 'auth/TwoFactorController@verify',

        // Admin Routes
        'GET /admin' => 'admin/DashboardController@index',
        'GET /admin/dashboard' => 'admin/DashboardController@index',
        
        // Admin Users
        'GET /admin/users' => 'admin/UserController@index',
        'GET /admin/users/create' => 'admin/UserController@create',
        'POST /admin/users/store' => 'admin/UserController@store',
        'GET /admin/users/{id}' => 'admin/UserController@show',
        'GET /admin/users/{id}/edit' => 'admin/UserController@edit',
        'POST /admin/users/{id}/update' => 'admin/UserController@update',
        'POST /admin/users/{id}/delete' => 'admin/UserController@delete',
        
        // Admin Courses
        'GET /admin/courses' => 'admin/CourseController@index',
        'GET /admin/courses/create' => 'admin/CourseController@create',
        'POST /admin/courses/store' => 'admin/CourseController@store',
        'GET /admin/courses/{id}' => 'admin/CourseController@show',
        'GET /admin/courses/{id}/edit' => 'admin/CourseController@edit',
        'POST /admin/courses/{id}/update' => 'admin/CourseController@update',
        'POST /admin/courses/{id}/delete' => 'admin/CourseController@delete',
        
        // Admin Categories
        'GET /admin/categories' => 'admin/CategoryController@index',
        'GET /admin/categories/create' => 'admin/CategoryController@create',
        'POST /admin/categories/store' => 'admin/CategoryController@store',
        'GET /admin/categories/{id}/edit' => 'admin/CategoryController@edit',
        'POST /admin/categories/{id}/update' => 'admin/CategoryController@update',
        'POST /admin/categories/{id}/delete' => 'admin/CategoryController@delete',
        
        // Admin Roles & Permissions
        'GET /admin/roles' => 'admin/RoleController@index',
        'GET /admin/roles/create' => 'admin/RoleController@create',
        'POST /admin/roles/store' => 'admin/RoleController@store',
        'GET /admin/roles/{id}/edit' => 'admin/RoleController@edit',
        'POST /admin/roles/{id}/update' => 'admin/RoleController@update',
        'POST /admin/roles/{id}/delete' => 'admin/RoleController@delete',
        
        'GET /admin/permissions' => 'admin/PermissionController@index',
        'GET /admin/permissions/create' => 'admin/PermissionController@create',
        'POST /admin/permissions/store' => 'admin/PermissionController@store',
        
        // Admin Settings
        'GET /admin/settings' => 'admin/SettingsController@index',
        'POST /admin/settings/update' => 'admin/SettingsController@update',
        
        // Admin Analytics & Reports
        'GET /admin/analytics' => 'admin/AnalyticsController@index',
        'GET /admin/reports' => 'admin/ReportController@index',
        'GET /admin/reports/generate' => 'admin/ReportController@generate',
        
        // Admin System
        'GET /admin/system' => 'admin/SystemController@index',
        'POST /admin/system/maintenance' => 'admin/SystemController@maintenance',

        // Instructor Routes
        'GET /instructor' => 'instructor/DashboardController@index',
        'GET /instructor/dashboard' => 'instructor/DashboardController@index',
        
        // Instructor Courses
        'GET /instructor/courses' => 'instructor/CourseController@index',
        'GET /instructor/courses/create' => 'instructor/CourseController@create',
        'POST /instructor/courses/store' => 'instructor/CourseController@store',
        'GET /instructor/courses/{id}' => 'instructor/CourseController@show',
        'GET /instructor/courses/{id}/edit' => 'instructor/CourseController@edit',
        'POST /instructor/courses/{id}/update' => 'instructor/CourseController@update',
        
        // Instructor Modules
        'GET /instructor/courses/{course_id}/modules' => 'instructor/ModuleController@index',
        'GET /instructor/courses/{course_id}/modules/create' => 'instructor/ModuleController@create',
        'POST /instructor/courses/{course_id}/modules/store' => 'instructor/ModuleController@store',
        'GET /instructor/modules/{id}/edit' => 'instructor/ModuleController@edit',
        'POST /instructor/modules/{id}/update' => 'instructor/ModuleController@update',
        
        // Instructor Lessons
        'GET /instructor/modules/{module_id}/lessons' => 'instructor/LessonController@index',
        'GET /instructor/modules/{module_id}/lessons/create' => 'instructor/LessonController@create',
        'POST /instructor/modules/{module_id}/lessons/store' => 'instructor/LessonController@store',
        'GET /instructor/lessons/{id}/edit' => 'instructor/LessonController@edit',
        'POST /instructor/lessons/{id}/update' => 'instructor/LessonController@update',
        
        // Instructor Quizzes
        'GET /instructor/courses/{course_id}/quizzes' => 'instructor/QuizController@index',
        'GET /instructor/courses/{course_id}/quizzes/create' => 'instructor/QuizController@create',
        'POST /instructor/courses/{course_id}/quizzes/store' => 'instructor/QuizController@store',
        'GET /instructor/quizzes/{id}/edit' => 'instructor/QuizController@edit',
        'POST /instructor/quizzes/{id}/update' => 'instructor/QuizController@update',
        
        // Instructor Assignments
        'GET /instructor/courses/{course_id}/assignments' => 'instructor/AssignmentController@index',
        'GET /instructor/courses/{course_id}/assignments/create' => 'instructor/AssignmentController@create',
        'POST /instructor/courses/{course_id}/assignments/store' => 'instructor/AssignmentController@store',
        'GET /instructor/assignments/{id}/edit' => 'instructor/AssignmentController@edit',
        'POST /instructor/assignments/{id}/update' => 'instructor/AssignmentController@update',
        
        // Instructor Students & Grading
        'GET /instructor/courses/{course_id}/students' => 'instructor/StudentController@index',
        'GET /instructor/courses/{course_id}/grading' => 'instructor/GradingController@index',
        'POST /instructor/grading/{submission_id}/grade' => 'instructor/GradingController@grade',
        
        // Instructor Live Sessions
        'GET /instructor/live-sessions' => 'instructor/LiveSessionController@index',
        'GET /instructor/live-sessions/create' => 'instructor/LiveSessionController@create',
        'POST /instructor/live-sessions/store' => 'instructor/LiveSessionController@store',
        
        // Instructor Analytics
        'GET /instructor/analytics' => 'instructor/AnalyticsController@index',

        // Student Routes
        'GET /student' => 'student/DashboardController@index',
        'GET /student/dashboard' => 'student/DashboardController@index',
        
        // Student Courses
        'GET /student/courses' => 'student/CourseController@index',
        'GET /student/courses/{id}' => 'student/CourseController@show',
        'POST /student/courses/{id}/enroll' => 'student/CourseController@enroll',
        
        // Student Lessons
        'GET /student/lessons/{id}' => 'student/LessonController@show',
        'POST /student/lessons/{id}/complete' => 'student/LessonController@complete',
        
        // Student Quizzes
        'GET /student/quizzes/{id}' => 'student/QuizController@show',
        'POST /student/quizzes/{id}/start' => 'student/QuizController@start',
        'POST /student/quizzes/{id}/submit' => 'student/QuizController@submit',
        
        // Student Assignments
        'GET /student/assignments/{id}' => 'student/AssignmentController@show',
        'POST /student/assignments/{id}/submit' => 'student/AssignmentController@submit',
        
        // Student Certificates
        'GET /student/certificates' => 'student/CertificateController@index',
        'GET /student/certificates/{id}/download' => 'student/CertificateController@download',
        
        // Student Profile & Progress
        'GET /student/profile' => 'student/ProfileController@index',
        'POST /student/profile/update' => 'student/ProfileController@update',
        'GET /student/progress' => 'student/ProgressController@index',

        // AI Routes
        'GET /ai/computer-vision' => 'ai/ComputerVisionController@index',
        'POST /ai/computer-vision/image-analysis' => 'ai/ComputerVisionController@analyzeImage',
        'POST /ai/computer-vision/video-analysis' => 'ai/ComputerVisionController@analyzeVideo',
        'POST /ai/computer-vision/face-recognition' => 'ai/ComputerVisionController@faceRecognition',
        'POST /ai/computer-vision/ocr' => 'ai/ComputerVisionController@ocr',
        
        'GET /ai/deep-learning' => 'ai/DeepLearningController@index',
        'POST /ai/deep-learning/train' => 'ai/DeepLearningController@train',
        'POST /ai/deep-learning/predict' => 'ai/DeepLearningController@predict',
        
        'GET /ai/machine-learning' => 'ai/MachineLearningController@index',
        'POST /ai/machine-learning/experiment' => 'ai/MachineLearningController@experiment',
        'POST /ai/machine-learning/cluster' => 'ai/MachineLearningController@cluster',
        
        'GET /ai/nlp' => 'ai/NLPController@index',
        'POST /ai/nlp/analyze' => 'ai/NLPController@analyze',
        'POST /ai/nlp/generate' => 'ai/NLPController@generate',
        'POST /ai/nlp/chatbot' => 'ai/NLPController@chatbot',
        
        'GET /ai/models' => 'ai/ModelController@index',
        'POST /ai/models/upload' => 'ai/ModelController@upload',
        'GET /ai/models/{id}/download' => 'ai/ModelController@download',
        
        'GET /ai/datasets' => 'ai/DatasetController@index',
        'POST /ai/datasets/upload' => 'ai/DatasetController@upload',
        'GET /ai/datasets/{id}/download' => 'ai/DatasetController@download',

        // Payment Routes
        'GET /payment/checkout/{course_id}' => 'payment/PaymentController@checkout',
        'POST /payment/process' => 'payment/PaymentController@process',
        'GET /payment/success' => 'payment/PaymentController@success',
        'GET /payment/failed' => 'payment/PaymentController@failed',
        'POST /payment/webhook' => 'payment/PaymentController@webhook',
        
        'GET /subscription' => 'payment/SubscriptionController@index',
        'POST /subscription/subscribe' => 'payment/SubscriptionController@subscribe',
        'POST /subscription/cancel' => 'payment/SubscriptionController@cancel',
        
        'GET /invoices' => 'payment/InvoiceController@index',
        'GET /invoices/{id}/download' => 'payment/InvoiceController@download',

        // Communication Routes
        'GET /forums' => 'communication/ForumController@index',
        'GET /forums/{id}' => 'communication/ForumController@show',
        'POST /forums/{id}/post' => 'communication/ForumController@post',
        
        'GET /chat' => 'communication/ChatController@index',
        'POST /chat/send' => 'communication/ChatController@send',
        
        'GET /announcements' => 'communication/AnnouncementController@index',
        'GET /announcements/{id}' => 'communication/AnnouncementController@show',
        
        'GET /notifications' => 'communication/NotificationController@index',
        'POST /notifications/{id}/mark-read' => 'communication/NotificationController@markRead',
        
        'POST /chatbot/message' => 'communication/ChatbotController@message',

        // Gamification Routes
        'GET /badges' => 'gamification/BadgeController@index',
        'GET /leaderboard' => 'gamification/LeaderboardController@index',
        'GET /points' => 'gamification/PointsController@index',

        // API Routes (v1)
        'POST /api/v1/auth/login' => 'api/v1/AuthApiController@login',
        'POST /api/v1/auth/register' => 'api/v1/AuthApiController@register',
        'POST /api/v1/auth/refresh' => 'api/v1/AuthApiController@refresh',
        'POST /api/v1/auth/logout' => 'api/v1/AuthApiController@logout',
        
        'GET /api/v1/courses' => 'api/v1/CourseApiController@index',
        'GET /api/v1/courses/{id}' => 'api/v1/CourseApiController@show',
        'POST /api/v1/courses' => 'api/v1/CourseApiController@store',
        'PUT /api/v1/courses/{id}' => 'api/v1/CourseApiController@update',
        'DELETE /api/v1/courses/{id}' => 'api/v1/CourseApiController@delete',
        
        'GET /api/v1/users' => 'api/v1/UserApiController@index',
        'GET /api/v1/users/{id}' => 'api/v1/UserApiController@show',
        'POST /api/v1/users' => 'api/v1/UserApiController@store',
        'PUT /api/v1/users/{id}' => 'api/v1/UserApiController@update',
        'DELETE /api/v1/users/{id}' => 'api/v1/UserApiController@delete',
        
        'POST /api/v1/ai/predict' => 'api/v1/AIApiController@predict',
        'POST /api/v1/ai/train' => 'api/v1/AIApiController@train',
        'GET /api/v1/ai/models' => 'api/v1/AIApiController@models',
        
        'POST /api/v1/webhooks/{provider}' => 'api/v1/WebhookController@handle',

        // File Upload Routes
        'POST /upload/image' => 'system/UploadController@image',
        'POST /upload/video' => 'system/UploadController@video',
        'POST /upload/document' => 'system/UploadController@document',
        'POST /upload/dataset' => 'system/UploadController@dataset',

        // Error Routes
        '404' => 'errors/ErrorController@notFound',
        '403' => 'errors/ErrorController@forbidden',
        '500' => 'errors/ErrorController@serverError',
    ],

    // Route middleware
    'middleware' => [
        'auth' => 'AuthMiddleware',
        'role' => 'RoleMiddleware',
        'permission' => 'PermissionMiddleware',
        'throttle' => 'ThrottleMiddleware',
        'cors' => 'CorsMiddleware',
        'api.auth' => 'ApiAuthMiddleware',
    ],

    // Route groups with middleware
    'groups' => [
        'admin' => [
            'middleware' => ['auth', 'role:admin'],
            'prefix' => 'admin',
        ],
        'instructor' => [
            'middleware' => ['auth', 'role:instructor'],
            'prefix' => 'instructor',
        ],
        'student' => [
            'middleware' => ['auth', 'role:student'],
            'prefix' => 'student',
        ],
        'api' => [
            'middleware' => ['api.auth', 'throttle:60,1'],
            'prefix' => 'api/v1',
        ],
    ],
];