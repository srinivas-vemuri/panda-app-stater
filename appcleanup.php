<?php
// -----------------------------------------------------------------------------
// Panda PHP - Secure Cleanup Utility
// -----------------------------------------------------------------------------
// Deletes all environment data (database, settings, uploaded logos/icons)
// ONLY IF called with the correct 16-character security code.
// -----------------------------------------------------------------------------

error_reporting(E_ALL);
ini_set('display_errors', 1);

// SECURITY CODE (must be exactly 16 alphanumeric characters)
// Example: "A1B2C3D4E5F6G7H8"
$securitycode = "";  // <-- YOU MUST MANUALLY SET THIS BEFORE USE

// --- Validate code structure ---
if (empty($securitycode) || strlen($securitycode) !== 16 || !ctype_alnum($securitycode)) {
    http_response_code(403);
    die("<h3>🚫 Security code invalid or not set.</h3><p>Please edit <code>appcleanup.php</code> and set a 16-character alphanumeric code.</p>");
}

// --- Validate incoming request ---
$code = trim($_GET['code'] ?? '');
if ($code !== $securitycode) {
    http_response_code(403);
    die("<h3>⛔ Access Denied</h3><p>Invalid security code provided.</p>");
}

// --- Begin cleanup ---
echo "<h2>🧹 Panda PHP Cleanup Utility</h2>";
echo "<p>Starting cleanup process...</p>";

// Paths and folders to clean
$targets = [
    __DIR__ . '/data.sqlite',
    __DIR__ . '/service-worker.js',
    __DIR__ . '/manifest.json',
    __DIR__ . '/inc/settings.json'
];
$folders = [
    __DIR__ . '/assets/logos/',
    //__DIR__ . '/assets/icons/'
];

// Initialize log
$logPath = __DIR__ . '/logs';
if (!is_dir($logPath)) {
    mkdir($logPath, 0755, true);
}
$logFile = $logPath . '/appcleanup.log';
$logEntry = "[" . date('c') . "] Cleanup initiated from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";

// --- Delete individual files ---
foreach ($targets as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "<p>✅ Deleted file: <code>" . basename($file) . "</code></p>";
            $logEntry .= "Deleted: " . basename($file) . "\n";
        } else {
            echo "<p>⚠️ Failed to delete file: <code>" . basename($file) . "</code></p>";
            $logEntry .= "Failed: " . basename($file) . "\n";
        }
    } else {
        echo "<p>ℹ️ File not found: <code>" . basename($file) . "</code></p>";
    }
}

// --- Clean folders ---
foreach ($folders as $folder) {
    if (is_dir($folder)) {
        $files = glob($folder . '*');
        foreach ($files as $f) {
            if (is_file($f) && unlink($f)) {
                echo "<p>🗑 Deleted: " . basename($f) . " from " . basename($folder) . "</p>";
                $logEntry .= "Deleted: " . basename($f) . "\n";
            }
        }
    } else {
        echo "<p>ℹ️ Folder not found: <code>" . basename($folder) . "</code></p>";
    }
}

// --- Clear sessions ---
session_start();
session_destroy();
echo "<p>🧠 Session cleared.</p>";
$logEntry .= "Sessions cleared.\n";

// --- Final log entry ---
$logEntry .= "Cleanup completed successfully.\n\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

echo "<h3>✅ Cleanup Completed Successfully!</h3>";
echo "<p>All dynamic files removed. Check <code>/logs/appcleanup.log</code> for a detailed record.</p>";
echo "<hr>";
echo "<p><strong>Note:</strong> You may now reload the app to trigger a fresh installation.</p>";
?>
