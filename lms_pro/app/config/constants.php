<?php

/**
 * System Constants
 * LMS Pro - Learning Management System
 */

defined('BASEPATH') OR exit('No direct script access allowed');

// Application Constants
define('APP_NAME', 'LMS Pro');
define('APP_VERSION', '1.0.0');
define('APP_AUTHOR', 'LMS Pro Team');

// Path Constants
define('ROOT_PATH', dirname(dirname(__DIR__)));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('UPLOAD_PATH', STORAGE_PATH . '/uploads');
define('CACHE_PATH', STORAGE_PATH . '/cache');
define('LOG_PATH', STORAGE_PATH . '/logs');

// URL Constants
define('BASE_URL', $_ENV['APP_URL'] ?? 'http://localhost');
define('ASSET_URL', BASE_URL . '/assets');
define('UPLOAD_URL', BASE_URL . '/uploads');

// Database Constants
define('DB_PREFIX', $_ENV['DB_PREFIX'] ?? '');

// User Roles
define('ROLE_SUPER_ADMIN', 1);
define('ROLE_ADMIN', 2);
define('ROLE_INSTRUCTOR', 3);
define('ROLE_STUDENT', 4);
define('ROLE_GUEST', 5);

// User Status
define('USER_STATUS_ACTIVE', 1);
define('USER_STATUS_INACTIVE', 0);
define('USER_STATUS_SUSPENDED', 2);
define('USER_STATUS_PENDING', 3);

// Course Status
define('COURSE_STATUS_DRAFT', 0);
define('COURSE_STATUS_PUBLISHED', 1);
define('COURSE_STATUS_ARCHIVED', 2);
define('COURSE_STATUS_SUSPENDED', 3);

// Course Types
define('COURSE_TYPE_FREE', 'free');
define('COURSE_TYPE_PAID', 'paid');
define('COURSE_TYPE_SUBSCRIPTION', 'subscription');

// Lesson Types
define('LESSON_TYPE_VIDEO', 'video');
define('LESSON_TYPE_TEXT', 'text');
define('LESSON_TYPE_AUDIO', 'audio');
define('LESSON_TYPE_DOCUMENT', 'document');
define('LESSON_TYPE_INTERACTIVE', 'interactive');

// Quiz Types
define('QUIZ_TYPE_MULTIPLE_CHOICE', 'multiple_choice');
define('QUIZ_TYPE_TRUE_FALSE', 'true_false');
define('QUIZ_TYPE_SHORT_ANSWER', 'short_answer');
define('QUIZ_TYPE_ESSAY', 'essay');
define('QUIZ_TYPE_FILL_BLANK', 'fill_blank');

// Assignment Types
define('ASSIGNMENT_TYPE_UPLOAD', 'upload');
define('ASSIGNMENT_TYPE_TEXT', 'text');
define('ASSIGNMENT_TYPE_QUIZ', 'quiz');
define('ASSIGNMENT_TYPE_PROJECT', 'project');

// Payment Status
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_COMPLETED', 'completed');
define('PAYMENT_STATUS_FAILED', 'failed');
define('PAYMENT_STATUS_CANCELLED', 'cancelled');
define('PAYMENT_STATUS_REFUNDED', 'refunded');

// Subscription Status
define('SUBSCRIPTION_STATUS_ACTIVE', 'active');
define('SUBSCRIPTION_STATUS_CANCELLED', 'cancelled');
define('SUBSCRIPTION_STATUS_EXPIRED', 'expired');
define('SUBSCRIPTION_STATUS_SUSPENDED', 'suspended');

// Enrollment Status
define('ENROLLMENT_STATUS_ACTIVE', 'active');
define('ENROLLMENT_STATUS_COMPLETED', 'completed');
define('ENROLLMENT_STATUS_DROPPED', 'dropped');
define('ENROLLMENT_STATUS_SUSPENDED', 'suspended');

// Certificate Status
define('CERTIFICATE_STATUS_PENDING', 'pending');
define('CERTIFICATE_STATUS_ISSUED', 'issued');
define('CERTIFICATE_STATUS_REVOKED', 'revoked');

// Notification Types
define('NOTIFICATION_TYPE_INFO', 'info');
define('NOTIFICATION_TYPE_SUCCESS', 'success');
define('NOTIFICATION_TYPE_WARNING', 'warning');
define('NOTIFICATION_TYPE_ERROR', 'error');

// AI Model Types
define('AI_MODEL_TYPE_CLASSIFICATION', 'classification');
define('AI_MODEL_TYPE_REGRESSION', 'regression');
define('AI_MODEL_TYPE_CLUSTERING', 'clustering');
define('AI_MODEL_TYPE_NLP', 'nlp');
define('AI_MODEL_TYPE_COMPUTER_VISION', 'computer_vision');
define('AI_MODEL_TYPE_DEEP_LEARNING', 'deep_learning');

// AI Model Status
define('AI_MODEL_STATUS_TRAINING', 'training');
define('AI_MODEL_STATUS_TRAINED', 'trained');
define('AI_MODEL_STATUS_FAILED', 'failed');
define('AI_MODEL_STATUS_DEPLOYED', 'deployed');

// File Types
define('FILE_TYPE_IMAGE', 'image');
define('FILE_TYPE_VIDEO', 'video');
define('FILE_TYPE_AUDIO', 'audio');
define('FILE_TYPE_DOCUMENT', 'document');
define('FILE_TYPE_ARCHIVE', 'archive');
define('FILE_TYPE_DATASET', 'dataset');

// File Size Limits (in bytes)
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_VIDEO_SIZE', 500 * 1024 * 1024); // 500MB
define('MAX_DOCUMENT_SIZE', 50 * 1024 * 1024); // 50MB
define('MAX_DATASET_SIZE', 1024 * 1024 * 1024); // 1GB

// Allowed File Extensions
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
define('ALLOWED_VIDEO_EXTENSIONS', ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm']);
define('ALLOWED_AUDIO_EXTENSIONS', ['mp3', 'wav', 'ogg', 'aac', 'm4a']);
define('ALLOWED_DOCUMENT_EXTENSIONS', ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt']);
define('ALLOWED_ARCHIVE_EXTENSIONS', ['zip', 'rar', '7z', 'tar', 'gz']);
define('ALLOWED_DATASET_EXTENSIONS', ['csv', 'json', 'xml', 'xlsx', 'txt', 'zip']);

// Cache Keys
define('CACHE_KEY_SETTINGS', 'app_settings');
define('CACHE_KEY_CATEGORIES', 'course_categories');
define('CACHE_KEY_ROLES', 'user_roles');
define('CACHE_KEY_PERMISSIONS', 'user_permissions');
define('CACHE_KEY_COURSES', 'featured_courses');

// Cache Expiry Times (in seconds)
define('CACHE_EXPIRY_SHORT', 300); // 5 minutes
define('CACHE_EXPIRY_MEDIUM', 1800); // 30 minutes
define('CACHE_EXPIRY_LONG', 3600); // 1 hour
define('CACHE_EXPIRY_DAILY', 86400); // 24 hours

// Session Keys
define('SESSION_USER_ID', 'user_id');
define('SESSION_USER_ROLE', 'user_role');
define('SESSION_USER_PERMISSIONS', 'user_permissions');
define('SESSION_FLASH_MESSAGE', 'flash_message');
define('SESSION_CSRF_TOKEN', 'csrf_token');

// API Response Codes
define('API_SUCCESS', 200);
define('API_CREATED', 201);
define('API_BAD_REQUEST', 400);
define('API_UNAUTHORIZED', 401);
define('API_FORBIDDEN', 403);
define('API_NOT_FOUND', 404);
define('API_METHOD_NOT_ALLOWED', 405);
define('API_VALIDATION_ERROR', 422);
define('API_SERVER_ERROR', 500);

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Security
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_MAX_LENGTH', 128);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes
define('CSRF_TOKEN_LENGTH', 32);
define('API_TOKEN_LENGTH', 64);

// Email Templates
define('EMAIL_TEMPLATE_WELCOME', 'welcome');
define('EMAIL_TEMPLATE_PASSWORD_RESET', 'password_reset');
define('EMAIL_TEMPLATE_COURSE_ENROLLMENT', 'course_enrollment');
define('EMAIL_TEMPLATE_CERTIFICATE_ISSUED', 'certificate_issued');
define('EMAIL_TEMPLATE_ASSIGNMENT_DUE', 'assignment_due');
define('EMAIL_TEMPLATE_QUIZ_REMINDER', 'quiz_reminder');

// Gamification
define('POINTS_COURSE_COMPLETION', 100);
define('POINTS_QUIZ_COMPLETION', 20);
define('POINTS_ASSIGNMENT_SUBMISSION', 30);
define('POINTS_FORUM_POST', 5);
define('POINTS_DAILY_LOGIN', 2);

// Badge Types
define('BADGE_TYPE_ACHIEVEMENT', 'achievement');
define('BADGE_TYPE_MILESTONE', 'milestone');
define('BADGE_TYPE_SKILL', 'skill');
define('BADGE_TYPE_PARTICIPATION', 'participation');

// Live Session Status
define('LIVE_SESSION_STATUS_SCHEDULED', 'scheduled');
define('LIVE_SESSION_STATUS_LIVE', 'live');
define('LIVE_SESSION_STATUS_ENDED', 'ended');
define('LIVE_SESSION_STATUS_CANCELLED', 'cancelled');

// Forum Status
define('FORUM_STATUS_OPEN', 'open');
define('FORUM_STATUS_CLOSED', 'closed');
define('FORUM_STATUS_ARCHIVED', 'archived');

// Chat Message Types
define('CHAT_MESSAGE_TYPE_TEXT', 'text');
define('CHAT_MESSAGE_TYPE_IMAGE', 'image');
define('CHAT_MESSAGE_TYPE_FILE', 'file');
define('CHAT_MESSAGE_TYPE_SYSTEM', 'system');

// Announcement Types
define('ANNOUNCEMENT_TYPE_GENERAL', 'general');
define('ANNOUNCEMENT_TYPE_COURSE', 'course');
define('ANNOUNCEMENT_TYPE_SYSTEM', 'system');
define('ANNOUNCEMENT_TYPE_MAINTENANCE', 'maintenance');

// Log Levels
define('LOG_LEVEL_DEBUG', 'debug');
define('LOG_LEVEL_INFO', 'info');
define('LOG_LEVEL_WARNING', 'warning');
define('LOG_LEVEL_ERROR', 'error');
define('LOG_LEVEL_CRITICAL', 'critical');

// System Settings Keys
define('SETTING_SITE_NAME', 'site_name');
define('SETTING_SITE_DESCRIPTION', 'site_description');
define('SETTING_SITE_LOGO', 'site_logo');
define('SETTING_SITE_FAVICON', 'site_favicon');
define('SETTING_TIMEZONE', 'timezone');
define('SETTING_DATE_FORMAT', 'date_format');
define('SETTING_TIME_FORMAT', 'time_format');
define('SETTING_CURRENCY', 'currency');
define('SETTING_LANGUAGE', 'language');
define('SETTING_MAINTENANCE_MODE', 'maintenance_mode');
define('SETTING_REGISTRATION_ENABLED', 'registration_enabled');
define('SETTING_EMAIL_VERIFICATION', 'email_verification');
define('SETTING_TWO_FACTOR_AUTH', 'two_factor_auth');

// Date Formats
define('DATE_FORMAT_DEFAULT', 'Y-m-d H:i:s');
define('DATE_FORMAT_DISPLAY', 'M d, Y');
define('TIME_FORMAT_DISPLAY', 'h:i A');

// Regex Patterns
define('REGEX_EMAIL', '/^[^\s@]+@[^\s@]+\.[^\s@]+$/');
define('REGEX_PHONE', '/^\+?[1-9]\d{1,14}$/');
define('REGEX_USERNAME', '/^[a-zA-Z0-9_]{3,20}$/');
define('REGEX_SLUG', '/^[a-z0-9-]+$/');

// HTTP Status Messages
$http_status_messages = [
    200 => 'OK',
    201 => 'Created',
    400 => 'Bad Request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    422 => 'Unprocessable Entity',
    500 => 'Internal Server Error',
];

define('HTTP_STATUS_MESSAGES', $http_status_messages);