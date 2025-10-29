<?php
// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        die("Error: .env file not found");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

// Load .env from parent directory
loadEnv(__DIR__ . '/.env');

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'english_dictionary');
define('DB_PORT', getenv('DB_PORT') ?: '3306');

// Create database connection
function getDBConnection() {
    try {
        // First connect without database to create it if needed
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
        $conn = new PDO($dsn, DB_USER, DB_PASSWORD);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $dbName = DB_NAME;
        $conn->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $conn->exec("USE `$dbName`");
        
        return $conn;
    } catch (PDOException $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

// Initialize database tables
function initDatabase() {
    $conn = getDBConnection();
    
    // Create words table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS words (
            id INT AUTO_INCREMENT PRIMARY KEY,
            word VARCHAR(255) NOT NULL,
            definition TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_word (word),
            FULLTEXT INDEX idx_fulltext_word (word),
            FULLTEXT INDEX idx_fulltext_definition (definition)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create search statistics table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS search_stats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            word_id INT,
            search_count INT DEFAULT 0,
            last_searched TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (word_id) REFERENCES words(id) ON DELETE CASCADE,
            UNIQUE INDEX idx_word_id (word_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create search history table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS search_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            word_id INT,
            searched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            FOREIGN KEY (word_id) REFERENCES words(id) ON DELETE CASCADE,
            INDEX idx_searched_at (searched_at),
            INDEX idx_word_id (word_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Create favorites table for users to save words
    $conn->exec("
        CREATE TABLE IF NOT EXISTS favorites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            word_id INT,
            session_id VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (word_id) REFERENCES words(id) ON DELETE CASCADE,
            INDEX idx_session (session_id),
            UNIQUE KEY unique_favorite (word_id, session_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // PDO doesn't need explicit close
    $conn = null;
}

// Initialize database on first load
initDatabase();
?>

