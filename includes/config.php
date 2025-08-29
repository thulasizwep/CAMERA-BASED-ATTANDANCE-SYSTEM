<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'attendance_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL
define('BASE_URL', 'http://localhost/attendance-system/');

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>