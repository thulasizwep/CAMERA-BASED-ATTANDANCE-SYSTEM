<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Only allow this script to run in local environment
if ($_SERVER['SERVER_NAME'] != 'localhost' && $_SERVER['SERVER_NAME'] != '127.0.0.1') {
    die('This script can only run on localhost');
}

$database = new Database();
$db = $database->getConnection();

// Reset admin password to "admin123"
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$query = "UPDATE admins SET password = :password WHERE username = 'admin'";
$stmt = $db->prepare($query);
$stmt->bindParam(':password', $hashed_password);

if ($stmt->execute()) {
    echo "Password reset successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    echo "<a href='login.php'>Go to Login</a>";
} else {
    echo "Error resetting password.";
}
?>