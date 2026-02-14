<?php
// inc/database.php
// SQLite database connection and initialization

function get_db_connection() {
    $db_file = __DIR__ . '/../data.sqlite';
    try {
        $pdo = new PDO("sqlite:$db_file");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

function init_database() {
    $db_file = __DIR__ . '/../data.sqlite';
    
    // Check if database already exists
    if (file_exists($db_file)) {
        return true;
    }
    
    $pdo = get_db_connection();
    
    // Create users table with new user_type system
    $sql = "CREATE TABLE IF NOT EXISTS users (
        user_id TEXT(6) PRIMARY KEY,
        user_username TEXT(50) UNIQUE NOT NULL,
        user_name TEXT(100) NOT NULL,
        user_email TEXT(150) UNIQUE NOT NULL,
        user_password TEXT NOT NULL,
        user_type INTEGER DEFAULT 0,  -- 2=super_admin, 1=admin, 0=regular
        user_createdate TEXT DEFAULT CURRENT_TIMESTAMP,
        user_status INTEGER DEFAULT 1,
        user_resetcode TEXT(6) NULL
    )";
    
    $pdo->exec($sql);
    
    // DO NOT create default user here - installation form will handle it
    return true;
}

function generate_unique_user_id($pdo, $attempt = 0) {
    if ($attempt > 10) {
        throw new Exception("Could not generate unique user ID after 10 attempts");
    }
    
    // Generate 6-character uppercase alphanumeric ID
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $user_id = '';
    for ($i = 0; $i < 6; $i++) {
        $user_id .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    // Check if ID already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $exists = $stmt->fetchColumn();
    
    if ($exists) {
        return generate_unique_user_id($pdo, $attempt + 1);
    }
    
    return $user_id;
}

// Initialize database when this file is included
//init_database();
?>