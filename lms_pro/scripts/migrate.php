<?php

/**
 * Database Migration Script
 * LMS Pro - Learning Management System
 */

echo "LMS Pro Database Migration\n";
echo "==========================\n\n";

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

// Database configuration
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? 'lms_pro';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    // Connect to database
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✓ Connected to database server\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '{$database}' created/verified\n";
    
    // Connect to the specific database
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Create migrations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `migrations` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `migration` varchar(255) NOT NULL,
            `batch` int(11) NOT NULL,
            `executed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "✓ Migrations table created\n";
    
    // Get migration files
    $migrationDir = __DIR__ . '/../database/migrations';
    $migrationFiles = glob($migrationDir . '/*.sql');
    sort($migrationFiles);
    
    if (empty($migrationFiles)) {
        echo "⚠ No migration files found\n";
        exit(0);
    }
    
    // Get executed migrations
    $stmt = $pdo->query("SELECT migration FROM migrations");
    $executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $batch = 1;
    if (!empty($executedMigrations)) {
        $stmt = $pdo->query("SELECT MAX(batch) as max_batch FROM migrations");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $batch = ($result['max_batch'] ?? 0) + 1;
    }
    
    echo "\nRunning migrations...\n";
    
    $newMigrations = 0;
    foreach ($migrationFiles as $migrationFile) {
        $migrationName = basename($migrationFile, '.sql');
        
        if (in_array($migrationName, $executedMigrations)) {
            echo "- Skipping: {$migrationName} (already executed)\n";
            continue;
        }
        
        echo "- Running: {$migrationName}\n";
        
        try {
            $sql = file_get_contents($migrationFile);
            
            // Split SQL file by semicolons and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
            
            // Record migration
            $stmt = $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
            $stmt->execute([$migrationName, $batch]);
            
            echo "  ✓ Completed\n";
            $newMigrations++;
            
        } catch (PDOException $e) {
            echo "  ✗ Failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    if ($newMigrations === 0) {
        echo "✓ No new migrations to run\n";
    } else {
        echo "\n✓ Executed {$newMigrations} migration(s) successfully\n";
    }
    
    // Run schema.sql if no migrations were executed (fresh install)
    if (empty($executedMigrations)) {
        $schemaFile = __DIR__ . '/../database/schema.sql';
        if (file_exists($schemaFile)) {
            echo "\nRunning database schema...\n";
            
            try {
                $sql = file_get_contents($schemaFile);
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                foreach ($statements as $statement) {
                    if (!empty($statement) && !preg_match('/^\s*(--|\/\*)/i', $statement)) {
                        $pdo->exec($statement);
                    }
                }
                
                echo "✓ Database schema applied\n";
                
            } catch (PDOException $e) {
                echo "⚠ Schema application failed: " . $e->getMessage() . "\n";
                echo "This is normal if migrations were already run\n";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n" . str_repeat("=", 40) . "\n";
echo "Migration completed successfully!\n";
echo "Next: Run 'php scripts/seed.php' to seed default data\n";
echo str_repeat("=", 40) . "\n";