<?php
require_once 'db.php';

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function login($username, $password) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM admins WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug output (remove in production)
        // echo "Input password: " . $password . "<br>";
        // echo "Stored hash: " . $admin['password'] . "<br>";
        // echo "Verification result: " . (password_verify($password, $admin['password']) ? 'true' : 'false');
        
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            return true;
        }
    }
    return false;
}

// Function to create a new admin with hashed password
function createAdmin($username, $password) {
    $database = new Database();
    $db = $database->getConnection();
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $query = "INSERT INTO admins (username, password) VALUES (:username, :password)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashed_password);
    
    return $stmt->execute();
}
?>