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

// Add new lecturer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_lecturer'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $modules = $_POST['modules'] ?? [];
    
    $query = "INSERT INTO lecturers (first_name, last_name, email) VALUES (:first_name, :last_name, :email)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':email', $email);
    
    if ($stmt->execute()) {
        $lecturer_id = $db->lastInsertId();
        
        // Assign modules to lecturer
        if (!empty($modules)) {
            foreach ($modules as $module_id) {
                $query = "INSERT INTO lecturer_modules (lecturer_id, module_id) VALUES (:lecturer_id, :module_id)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':lecturer_id', $lecturer_id);
                $stmt->bindParam(':module_id', $module_id);
                $stmt->execute();
            }
        }
    }
}

// Delete lecturer
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM lecturers WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
}

// Get all lecturers with their modules
$query = "SELECT l.*, GROUP_CONCAT(m.name) as module_names 
          FROM lecturers l 
          LEFT JOIN lecturer_modules lm ON l.id = lm.lecturer_id 
          LEFT JOIN modules m ON lm.module_id = m.id 
          GROUP BY l.id 
          ORDER BY l.last_name, l.first_name";
$stmt = $db->prepare($query);
$stmt->execute();
$lecturers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all modules for the form
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
    <title>Manage Lecturers - Attendance System</title>
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
            <h2>Manage Lecturers</h2>
            
            <div class="form-section">
                <h3>Add New Lecturer</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="first_name">First Name:</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Assign Modules:</label>
                        <div class="checkbox-group">
                            <?php foreach ($modules as $module): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="modules[]" value="<?php echo $module['id']; ?>">
                                <?php echo htmlspecialchars($module['name']); ?> (<?php echo htmlspecialchars($module['code']); ?>)
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" name="add_lecturer">Add Lecturer</button>
                </form>
            </div>
            
            <div class="table-section">
                <h3>Existing Lecturers</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Modules</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lecturers as $lecturer): ?>
                        <tr>
                            <td><?php echo $lecturer['id']; ?></td>
                            <td><?php echo htmlspecialchars($lecturer['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($lecturer['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($lecturer['email']); ?></td>
                            <td><?php echo $lecturer['module_names'] ? htmlspecialchars($lecturer['module_names']) : 'None'; ?></td>
                            <td>
                                <a href="?delete=<?php echo $lecturer['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
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