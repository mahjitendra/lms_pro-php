<?php

/**
 * Installation Script
 * LMS Pro - Learning Management System
 */

echo "LMS Pro Installation Script\n";
echo "===========================\n\n";

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    echo "Error: PHP 7.4 or higher is required. Current version: " . PHP_VERSION . "\n";
    exit(1);
}

// Check required extensions
$requiredExtensions = [
    'pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'curl', 'gd', 'zip', 'json'
];

$missingExtensions = [];
foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        $missingExtensions[] = $extension;
    }
}

if (!empty($missingExtensions)) {
    echo "Error: Missing required PHP extensions:\n";
    foreach ($missingExtensions as $extension) {
        echo "  - {$extension}\n";
    }
    exit(1);
}

echo "✓ PHP version check passed\n";
echo "✓ Required extensions check passed\n\n";

// Create directories
$directories = [
    'storage/logs',
    'storage/cache/views',
    'storage/cache/data',
    'storage/cache/routes',
    'storage/sessions',
    'storage/uploads/courses',
    'storage/uploads/assignments',
    'storage/uploads/profiles',
    'storage/uploads/certificates',
    'storage/uploads/datasets',
    'storage/ai-models/trained',
    'storage/ai-models/checkpoints',
    'storage/ai-models/exports',
    'public/uploads/videos',
    'public/uploads/documents',
    'public/uploads/assignments',
    'public/uploads/datasets'
];

echo "Creating directories...\n";
foreach ($directories as $dir) {
    $fullPath = __DIR__ . '/../' . $dir;
    if (!is_dir($fullPath)) {
        if (mkdir($fullPath, 0755, true)) {
            echo "✓ Created: {$dir}\n";
        } else {
            echo "✗ Failed to create: {$dir}\n";
        }
    } else {
        echo "✓ Exists: {$dir}\n";
    }
    
    // Create .gitkeep file
    $gitkeepFile = $fullPath . '/.gitkeep';
    if (!file_exists($gitkeepFile)) {
        file_put_contents($gitkeepFile, '');
    }
}

// Set permissions
echo "\nSetting permissions...\n";
$permissionDirs = [
    'storage' => 0755,
    'public/uploads' => 0755
];

foreach ($permissionDirs as $dir => $permission) {
    $fullPath = __DIR__ . '/../' . $dir;
    if (is_dir($fullPath)) {
        if (chmod($fullPath, $permission)) {
            echo "✓ Set permissions for: {$dir}\n";
        } else {
            echo "✗ Failed to set permissions for: {$dir}\n";
        }
    }
}

// Create .env file if it doesn't exist
$envFile = __DIR__ . '/../.env';
$envExampleFile = __DIR__ . '/../.env.example';

if (!file_exists($envFile) && file_exists($envExampleFile)) {
    echo "\nCreating .env file...\n";
    if (copy($envExampleFile, $envFile)) {
        echo "✓ Created .env file from .env.example\n";
        echo "⚠ Please update the .env file with your configuration\n";
    } else {
        echo "✗ Failed to create .env file\n";
    }
}

// Generate application key
echo "\nGenerating application key...\n";
$appKey = bin2hex(random_bytes(32));
$envContent = file_get_contents($envFile);
$envContent = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $appKey, $envContent);
file_put_contents($envFile, $envContent);
echo "✓ Application key generated\n";

// Check database connection
echo "\nChecking database connection...\n";
try {
    // Load environment variables
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
    
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $database = $_ENV['DB_DATABASE'] ?? 'lms_pro';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✓ Database connection successful\n";
    
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "⚠ Please check your database configuration in .env file\n";
}

// Create default log files
echo "\nCreating log files...\n";
$logFiles = [
    'storage/logs/app.log',
    'storage/logs/error.log',
    'storage/logs/api.log',
    'storage/logs/ai.log'
];

foreach ($logFiles as $logFile) {
    $fullPath = __DIR__ . '/../' . $logFile;
    if (!file_exists($fullPath)) {
        if (file_put_contents($fullPath, '')) {
            echo "✓ Created: {$logFile}\n";
        } else {
            echo "✗ Failed to create: {$logFile}\n";
        }
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Installation completed!\n\n";

echo "Next steps:\n";
echo "1. Update your .env file with proper configuration\n";
echo "2. Run database migrations: php scripts/migrate.php\n";
echo "3. Seed default data: php scripts/seed.php\n";
echo "4. Configure your web server to point to the 'public' directory\n";
echo "5. Visit your application in a web browser\n\n";

echo "Default admin credentials (after seeding):\n";
echo "Email: admin@lmspro.com\n";
echo "Password: admin123\n\n";

echo "For more information, see README.md\n";
echo str_repeat("=", 50) . "\n";