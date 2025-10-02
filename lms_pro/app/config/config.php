<?php

/**
 * Main Configuration File
 * LMS Pro - Learning Management System
 */

defined('BASEPATH') OR exit('No direct script access allowed');

// Load environment variables
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

return [
    // Application Settings
    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'LMS Pro',
        'version' => '1.0.0',
        'environment' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => $_ENV['APP_DEBUG'] ?? false,
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
        'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
        'locale' => $_ENV['APP_LOCALE'] ?? 'en',
        'key' => $_ENV['APP_KEY'] ?? 'your-secret-key-here',
    ],

    // Database Configuration
    'database' => [
        'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? '3306',
                'database' => $_ENV['DB_DATABASE'] ?? 'lms_pro',
                'username' => $_ENV['DB_USERNAME'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            ]
        ]
    ],

    // Cache Configuration
    'cache' => [
        'default' => $_ENV['CACHE_DRIVER'] ?? 'file',
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => __DIR__ . '/../../storage/cache/data',
            ],
            'redis' => [
                'driver' => 'redis',
                'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                'port' => $_ENV['REDIS_PORT'] ?? 6379,
                'password' => $_ENV['REDIS_PASSWORD'] ?? null,
                'database' => $_ENV['REDIS_DB'] ?? 0,
            ]
        ]
    ],

    // Session Configuration
    'session' => [
        'driver' => $_ENV['SESSION_DRIVER'] ?? 'file',
        'lifetime' => $_ENV['SESSION_LIFETIME'] ?? 120,
        'path' => __DIR__ . '/../../storage/sessions',
        'cookie_name' => 'lms_pro_session',
        'cookie_path' => '/',
        'cookie_domain' => $_ENV['SESSION_DOMAIN'] ?? null,
        'cookie_secure' => $_ENV['SESSION_SECURE_COOKIE'] ?? false,
        'cookie_httponly' => true,
    ],

    // Mail Configuration
    'mail' => [
        'default' => $_ENV['MAIL_MAILER'] ?? 'smtp',
        'mailers' => [
            'smtp' => [
                'transport' => 'smtp',
                'host' => $_ENV['MAIL_HOST'] ?? 'localhost',
                'port' => $_ENV['MAIL_PORT'] ?? 587,
                'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
                'username' => $_ENV['MAIL_USERNAME'] ?? null,
                'password' => $_ENV['MAIL_PASSWORD'] ?? null,
            ],
        ],
        'from' => [
            'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@lmspro.com',
            'name' => $_ENV['MAIL_FROM_NAME'] ?? 'LMS Pro',
        ],
    ],

    // File Storage Configuration
    'storage' => [
        'default' => $_ENV['FILESYSTEM_DRIVER'] ?? 'local',
        'disks' => [
            'local' => [
                'driver' => 'local',
                'root' => __DIR__ . '/../../storage/uploads',
                'url' => '/uploads',
            ],
            's3' => [
                'driver' => 's3',
                'key' => $_ENV['AWS_ACCESS_KEY_ID'] ?? null,
                'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'] ?? null,
                'region' => $_ENV['AWS_DEFAULT_REGION'] ?? 'us-east-1',
                'bucket' => $_ENV['AWS_BUCKET'] ?? null,
                'url' => $_ENV['AWS_URL'] ?? null,
            ],
        ],
    ],

    // Payment Gateway Configuration
    'payment' => [
        'default' => $_ENV['PAYMENT_GATEWAY'] ?? 'stripe',
        'gateways' => [
            'stripe' => [
                'public_key' => $_ENV['STRIPE_PUBLIC_KEY'] ?? null,
                'secret_key' => $_ENV['STRIPE_SECRET_KEY'] ?? null,
                'webhook_secret' => $_ENV['STRIPE_WEBHOOK_SECRET'] ?? null,
            ],
            'paypal' => [
                'client_id' => $_ENV['PAYPAL_CLIENT_ID'] ?? null,
                'client_secret' => $_ENV['PAYPAL_CLIENT_SECRET'] ?? null,
                'sandbox' => $_ENV['PAYPAL_SANDBOX'] ?? true,
            ],
            'razorpay' => [
                'key_id' => $_ENV['RAZORPAY_KEY_ID'] ?? null,
                'key_secret' => $_ENV['RAZORPAY_KEY_SECRET'] ?? null,
            ],
        ],
    ],

    // AI/ML Configuration
    'ai' => [
        'tensorflow' => [
            'model_path' => __DIR__ . '/../../storage/ai-models',
            'python_path' => $_ENV['PYTHON_PATH'] ?? '/usr/bin/python3',
            'api_url' => $_ENV['TENSORFLOW_API_URL'] ?? null,
        ],
        'opencv' => [
            'library_path' => $_ENV['OPENCV_PATH'] ?? '/usr/local/lib',
        ],
        'openai' => [
            'api_key' => $_ENV['OPENAI_API_KEY'] ?? null,
            'model' => $_ENV['OPENAI_MODEL'] ?? 'gpt-3.5-turbo',
        ],
        'huggingface' => [
            'api_key' => $_ENV['HUGGINGFACE_API_KEY'] ?? null,
        ],
    ],

    // Video Streaming Configuration
    'video' => [
        'vimeo' => [
            'client_id' => $_ENV['VIMEO_CLIENT_ID'] ?? null,
            'client_secret' => $_ENV['VIMEO_CLIENT_SECRET'] ?? null,
            'access_token' => $_ENV['VIMEO_ACCESS_TOKEN'] ?? null,
        ],
        'youtube' => [
            'api_key' => $_ENV['YOUTUBE_API_KEY'] ?? null,
        ],
        'zoom' => [
            'api_key' => $_ENV['ZOOM_API_KEY'] ?? null,
            'api_secret' => $_ENV['ZOOM_API_SECRET'] ?? null,
        ],
    ],

    // Security Configuration
    'security' => [
        'encryption_key' => $_ENV['ENCRYPTION_KEY'] ?? 'your-encryption-key-here',
        'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'your-jwt-secret-here',
        'jwt_expiry' => $_ENV['JWT_EXPIRY'] ?? 3600,
        'password_min_length' => 8,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
        'two_factor_enabled' => $_ENV['TWO_FACTOR_ENABLED'] ?? false,
    ],

    // API Configuration
    'api' => [
        'rate_limit' => $_ENV['API_RATE_LIMIT'] ?? 60,
        'rate_limit_window' => $_ENV['API_RATE_LIMIT_WINDOW'] ?? 60,
        'version' => 'v1',
        'prefix' => 'api',
    ],

    // Logging Configuration
    'logging' => [
        'default' => $_ENV['LOG_CHANNEL'] ?? 'file',
        'channels' => [
            'file' => [
                'driver' => 'file',
                'path' => __DIR__ . '/../../storage/logs/app.log',
                'level' => $_ENV['LOG_LEVEL'] ?? 'info',
            ],
            'error' => [
                'driver' => 'file',
                'path' => __DIR__ . '/../../storage/logs/error.log',
                'level' => 'error',
            ],
            'api' => [
                'driver' => 'file',
                'path' => __DIR__ . '/../../storage/logs/api.log',
                'level' => 'info',
            ],
            'ai' => [
                'driver' => 'file',
                'path' => __DIR__ . '/../../storage/logs/ai.log',
                'level' => 'info',
            ],
        ],
    ],

    // Gamification Settings
    'gamification' => [
        'points' => [
            'course_completion' => 100,
            'quiz_completion' => 20,
            'assignment_submission' => 30,
            'forum_post' => 5,
            'daily_login' => 2,
        ],
        'badges' => [
            'first_course' => 'Course Starter',
            'course_master' => 'Course Master',
            'quiz_champion' => 'Quiz Champion',
            'helpful_student' => 'Helpful Student',
        ],
    ],

    // Notification Settings
    'notifications' => [
        'channels' => ['email', 'database', 'push'],
        'push' => [
            'firebase_key' => $_ENV['FIREBASE_SERVER_KEY'] ?? null,
        ],
    ],

    // Course Settings
    'course' => [
        'max_file_size' => $_ENV['MAX_UPLOAD_SIZE'] ?? '100M',
        'allowed_video_formats' => ['mp4', 'avi', 'mov', 'wmv'],
        'allowed_document_formats' => ['pdf', 'doc', 'docx', 'ppt', 'pptx'],
        'max_students_per_course' => $_ENV['MAX_STUDENTS_PER_COURSE'] ?? 1000,
    ],
];