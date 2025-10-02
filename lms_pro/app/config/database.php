<?php

/**
 * Database Configuration
 * LMS Pro - Learning Management System
 */

defined('BASEPATH') OR exit('No direct script access allowed');

return [
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
            'prefix' => $_ENV['DB_PREFIX'] ?? '',
            'strict' => true,
            'engine' => null,
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ]
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => $_ENV['DB_DATABASE'] ?? __DIR__ . '/../../storage/database.sqlite',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '5432',
            'database' => $_ENV['DB_DATABASE'] ?? 'lms_pro',
            'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8',
            'prefix' => $_ENV['DB_PREFIX'] ?? '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'testing' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ],
    ],

    'migrations' => [
        'table' => 'migrations',
        'path' => __DIR__ . '/../../database/migrations',
    ],

    'redis' => [
        'client' => $_ENV['REDIS_CLIENT'] ?? 'predis',
        'options' => [
            'cluster' => $_ENV['REDIS_CLUSTER'] ?? 'redis',
            'prefix' => $_ENV['REDIS_PREFIX'] ?? 'lms_pro_database_',
        ],
        'default' => [
            'url' => $_ENV['REDIS_URL'] ?? null,
            'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
            'port' => $_ENV['REDIS_PORT'] ?? 6379,
            'database' => $_ENV['REDIS_DB'] ?? 0,
        ],
        'cache' => [
            'url' => $_ENV['REDIS_URL'] ?? null,
            'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
            'port' => $_ENV['REDIS_PORT'] ?? 6379,
            'database' => $_ENV['REDIS_CACHE_DB'] ?? 1,
        ],
    ],
];