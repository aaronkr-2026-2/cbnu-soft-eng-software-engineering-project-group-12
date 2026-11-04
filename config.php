<?php
// ==== EDIT THESE 4 LINES FOR YOUR SERVER ====
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'korean_app');
// =============================================

define('WORDS_PER_DAY', 10);

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die('Database connection failed: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

session_start();

function current_user_id() {
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function require_login() {
    if (!current_user_id()) {
        header('Location: login.php');
        exit;
    }
}

function e($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
