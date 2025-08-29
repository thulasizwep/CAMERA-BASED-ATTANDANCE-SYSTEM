<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Attendance System</title>
    <link rel="stylesheet" href="css/style.css">
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
                <a href="view_classrooms.php">View Classrooms</a>
                <a href="logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
            </nav>
        </header>
        
        <main>
            <h2>Admin Dashboard</h2>
            <div class="dashboard-cards">
                <div class="card">
                    <h3>Quick Actions</h3>
                    <ul>
                        <li><a href="modules/manage_modules.php">Add New Module</a></li>
                        <li><a href="modules/manage_lecturers.php">Register Lecturer</a></li>
                        <li><a href="modules/manage_students.php">Register Student</a></li>
                        <li><a href="modules/schedule_attendance.php">Schedule Attendance</a></li>
                    </ul>
                </div>
                <div class="card">
                    <h3>System Overview</h3>
                    <p>Welcome to the Attendance Management System. Use the navigation menu to manage different aspects of the system.</p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>