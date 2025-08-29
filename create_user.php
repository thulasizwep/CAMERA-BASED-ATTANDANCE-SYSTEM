<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_admin'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        try {
            // Check if username already exists
            $query = "SELECT id FROM admins WHERE username = :username";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = 'Username already exists';
            } else {
                // Create new admin
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $query = "INSERT INTO admins (username, password) VALUES (:username, :password)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $hashed_password);
                
                if ($stmt->execute()) {
                    $success = 'Admin user created successfully';
                    // Clear form fields
                    $_POST = array();
                } else {
                    $error = 'Error creating admin user';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User - Attendance System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .password-strength {
            margin-top: 5px;
            height: 5px;
            width: 100%;
            background-color: #f0f0f0;
            border-radius: 3px;
        }
        
        .password-strength-meter {
            height: 100%;
            width: 0;
            border-radius: 3px;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .weak { background-color: #ff4757; width: 33%; }
        .medium { background-color: #ffa502; width: 66%; }
        .strong { background-color: #2ed573; width: 100%; }
        
        .password-requirements {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .requirement {
            margin-bottom: 5px;
        }
        
        .requirement.met {
            color: #2ed573;
        }
        
        .requirement.unmet {
            color: #ff4757;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Attendance Management System</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="modules/manage_modules.php">Manage Modules</a>
                <a href="modules/manage_lecturers.php">Manage Lecturers</a>
                <a href="modules/manage_venues.php">Manage Venues</a>
                <a href="modules/manage_students.php">Manage Students</a>
                <a href="modules/schedule_attendance.php">Schedule Attendance</a>
                <a href="create_user.php">Create Admin</a>
                <a href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
            </nav>
        </header>
        
        <main>
            <h2>Create Admin User</h2>
            
            <?php if ($success): ?>
                <div class="success" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-section">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required 
                               oninput="checkPasswordStrength()">
                        <div class="password-strength">
                            <div class="password-strength-meter" id="password-strength-meter"></div>
                        </div>
                        <div class="password-requirements">
                            <div class="requirement unmet" id="length-requirement">At least 6 characters</div>
                            <div class="requirement unmet" id="number-requirement">Contains a number</div>
                            <div class="requirement unmet" id="special-requirement">Contains a special character</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               oninput="checkPasswordMatch()">
                        <div id="password-match" style="margin-top: 5px; font-size: 0.9rem;"></div>
                    </div>
                    
                    <button type="submit" name="create_admin">Create Admin User</button>
                </form>
            </div>
            
            <div class="table-section">
                <h3>Existing Admin Users</h3>
                <?php
                try {
                    $query = "SELECT id, username FROM admins ORDER BY id";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($admins) > 0) {
                        echo '<table>';
                        echo '<thead><tr><th>ID</th><th>Username</th><th>Actions</th></tr></thead>';
                        echo '<tbody>';
                        
                        foreach ($admins as $admin) {
                            echo '<tr>';
                            echo '<td>' . $admin['id'] . '</td>';
                            echo '<td>' . htmlspecialchars($admin['username']) . '</td>';
                            echo '<td>';
                            // Don't allow deletion of the current user or default admin
                            if ($admin['id'] != $_SESSION['admin_id'] && $admin['id'] != 1) {
                                echo '<a href="?delete=' . $admin['id'] . '" onclick="return confirm(\'Are you sure you want to delete this admin user?\')">Delete</a>';
                            } else {
                                echo '<span style="color: #999;">Cannot delete</span>';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                        
                        echo '</tbody></table>';
                    } else {
                        echo '<p>No admin users found.</p>';
                    }
                } catch (PDOException $e) {
                    echo '<p>Error retrieving admin users: ' . $e->getMessage() . '</p>';
                }
                ?>
            </div>
        </main>
    </div>

    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthMeter = document.getElementById('password-strength-meter');
            const lengthReq = document.getElementById('length-requirement');
            const numberReq = document.getElementById('number-requirement');
            const specialReq = document.getElementById('special-requirement');
            
            // Reset classes
            strengthMeter.className = 'password-strength-meter';
            lengthReq.className = 'requirement unmet';
            numberReq.className = 'requirement unmet';
            specialReq.className = 'requirement unmet';
            
            if (password.length === 0) {
                return;
            }
            
            // Check requirements
            const hasMinLength = password.length >= 6;
            const hasNumber = /\d/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            
            // Update requirement indicators
            if (hasMinLength) lengthReq.className = 'requirement met';
            if (hasNumber) numberReq.className = 'requirement met';
            if (hasSpecial) specialReq.className = 'requirement met';
            
            // Calculate strength
            let strength = 0;
            if (hasMinLength) strength += 1;
            if (hasNumber) strength += 1;
            if (hasSpecial) strength += 1;
            
            // Update strength meter
            if (strength === 1) {
                strengthMeter.className = 'password-strength-meter weak';
            } else if (strength === 2) {
                strengthMeter.className = 'password-strength-meter medium';
            } else if (strength === 3) {
                strengthMeter.className = 'password-strength-meter strong';
            }
        }
        
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchElement = document.getElementById('password-match');
            
            if (confirmPassword.length === 0) {
                matchElement.textContent = '';
                matchElement.style.color = '';
            } else if (password === confirmPassword) {
                matchElement.textContent = 'Passwords match';
                matchElement.style.color = '#2ed573';
            } else {
                matchElement.textContent = 'Passwords do not match';
                matchElement.style.color = '#ff4757';
            }
        }
        
        // Handle delete action
        <?php
        if (isset($_GET['delete'])) {
            $delete_id = $_GET['delete'];
            
            // Don't allow deletion of current user or default admin (ID 1)
            if ($delete_id != $_SESSION['admin_id'] && $delete_id != 1) {
                try {
                    $query = "DELETE FROM admins WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $delete_id);
                    
                    if ($stmt->execute()) {
                        echo 'alert("Admin user deleted successfully");';
                        echo 'window.location.href = "create_user.php";';
                    } else {
                        echo 'alert("Error deleting admin user");';
                    }
                } catch (PDOException $e) {
                    echo 'alert("Database error: ' . addslashes($e->getMessage()) . '");';
                }
            } else {
                echo 'alert("Cannot delete this admin user");';
                echo 'window.location.href = "create_user.php";';
            }
        }
        ?>
    </script>
</body>
</html>