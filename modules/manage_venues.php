<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Add new venue
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_venue'])) {
    $name = $_POST['name'];
    $capacity = $_POST['capacity'];
    $ip_camera = $_POST['ip_camera'];
    
    $query = "INSERT INTO venues (name, capacity, ip_camera) VALUES (:name, :capacity, :ip_camera)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':capacity', $capacity);
    $stmt->bindParam(':ip_camera', $ip_camera);
    $stmt->execute();
}

// Delete venue
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM venues WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
}

// Get all venues
$query = "SELECT * FROM venues ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Venues - Attendance System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Attendance Management System</h1>
            <nav>
                <a href="../dashboard.php">Dashboard</a>
                <a href="manage_modules.php">Manage Modules</a>
                <a href="manage_lecturers.php">Manage Lecturers</a>
                <a href="manage_venues.php">Manage Venues</a>
                <a href="manage_students.php">Manage Students</a>
                <a href="schedule_attendance.php">Schedule Attendance</a>
                <a href="../logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a>
            </nav>
        </header>
        
        <main>
            <h2>Manage Venues</h2>
            
            <div class="form-section">
                <h3>Add New Venue</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Venue Name:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="capacity">Capacity:</label>
                        <input type="number" id="capacity" name="capacity" required min="1">
                    </div>
                    <div class="form-group">
                        <label for="ip_camera">IP Camera (0 for webcam):</label>
                        <input type="text" id="ip_camera" name="ip_camera" value="0" required>
                    </div>
                    <button type="submit" name="add_venue">Add Venue</button>
                </form>
            </div>
            
            <div class="table-section">
                <h3>Existing Venues</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Capacity</th>
                            <th>IP Camera</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($venues as $venue): ?>
                        <tr>
                            <td><?php echo $venue['id']; ?></td>
                            <td><?php echo htmlspecialchars($venue['name']); ?></td>
                            <td><?php echo $venue['capacity']; ?></td>
                            <td><?php echo htmlspecialchars($venue['ip_camera']); ?></td>
                            <td>
                                <a href="?delete=<?php echo $venue['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>