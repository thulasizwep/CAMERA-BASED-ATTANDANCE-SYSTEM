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

// Add new module
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_module'])) {
    $name = $_POST['name'];
    $code = $_POST['code'];
    
    $query = "INSERT INTO modules (name, code) VALUES (:name, :code)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':code', $code);
    $stmt->execute();
}

// Delete module
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM modules WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
}

// Get all modules
$query = "SELECT * FROM modules ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Modules - Attendance System</title>
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
            <h2>Manage Modules</h2>
            
            <div class="form-section">
                <h3>Add New Module</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Module Name:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="code">Module Code:</label>
                        <input type="text" id="code" name="code" required>
                    </div>
                    <button type="submit" name="add_module">Add Module</button>
                </form>
            </div>
            
            <div class="table-section">
                <h3>Existing Modules</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $module): ?>
                        <tr>
                            <td><?php echo $module['id']; ?></td>
                            <td><?php echo htmlspecialchars($module['name']); ?></td>
                            <td><?php echo htmlspecialchars($module['code']); ?></td>
                            <td>
                                <a href="?delete=<?php echo $module['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
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