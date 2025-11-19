<?php
// Configuration file for GIS Minahasa app

// Data file paths
define('DATA_DIR', __DIR__ . '/data/');
define('BANJIR_FILE', DATA_DIR . 'banjir.json');
define('LONGSOR_FILE', DATA_DIR . 'longsor.json');
define('SEKOLAH_FILE', DATA_DIR . 'sekolah.json');
define('RS_FILE', DATA_DIR . 'rs.json');

// Admin credentials (in production, use proper hashing and database)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'sebas'); // In production, hash this!

// Session configuration
session_start();

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function loadJsonData($filePath) {
    if (file_exists($filePath)) {
        $data = json_decode(file_get_contents($filePath), true);
        return is_array($data) ? $data : [];
    }
    return [];
}

function saveJsonData($filePath, $data) {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function generateId() {
    return 'id_' . time() . '_' . rand(1000, 9999);
}
?>
