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

// Schedule attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['schedule_attendance'])) {
        $lecturer_id = $_POST['lecturer_id'];
        $module_id = $_POST['module_id'];
        $venue_id = $_POST['venue_id'];
        $schedule_date = $_POST['schedule_date'];
        $schedule_time = $_POST['schedule_time'];
        
        $query = "INSERT INTO attendance_schedule (lecturer_id, module_id, venue_id, schedule_date, schedule_time) 
                  VALUES (:lecturer_id, :module_id, :venue_id, :schedule_date, :schedule_time)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':lecturer_id', $lecturer_id);
        $stmt->bindParam(':module_id', $module_id);
        $stmt->bindParam(':venue_id', $venue_id);
        $stmt->bindParam(':schedule_date', $schedule_date);
        $stmt->bindParam(':schedule_time', $schedule_time);
        $stmt->execute();
        
        $success = "Attendance scheduled successfully";
    } 
    elseif (isset($_POST['start_now'])) {
        $lecturer_id = $_POST['lecturer_id'];
        $module_id = $_POST['module_id'];
        $venue_id = $_POST['venue_id'];
        
        // Create an immediate schedule
        $query = "INSERT INTO attendance_schedule (lecturer_id, module_id, venue_id, schedule_date, schedule_time) 
                  VALUES (:lecturer_id, :module_id, :venue_id, CURDATE(), CURTIME())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':lecturer_id', $lecturer_id);
        $stmt->bindParam(':module_id', $module_id);
        $stmt->bindParam(':venue_id', $venue_id);
        $stmt->execute();
        
        $schedule_id = $db->lastInsertId();
        header("Location: take_attendance.php?schedule_id=" . $schedule_id);
        exit;
    }
    elseif (isset($_POST['upload_classroom'])) {
        $venue_id = $_POST['venue_id'];
        
        // Handle classroom picture upload
        if (!empty($_FILES['classroom_picture']['name'])) {
            $uploadDir = '../uploads/classrooms/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $extension = pathinfo($_FILES['classroom_picture']['name'], PATHINFO_EXTENSION);
            $filename = 'venue_' . $venue_id . '_' . time() . '.' . $extension;
            $destination = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['classroom_picture']['tmp_name'], $destination)) {
                $query = "INSERT INTO classroom_pictures (venue_id, picture_path) VALUES (:venue_id, :picture_path)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':venue_id', $venue_id);
                $stmt->bindParam(':picture_path', $filename);
                $stmt->execute();
                
                $success = "Classroom picture uploaded successfully";
            } else {
                $error = "Error uploading classroom picture";
            }
        }
    }
}

// Delete schedule
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM attendance_schedule WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
}

// Get all schedules with related data
$query = "SELECT a.*, l.first_name as lecturer_first_name, l.last_name as lecturer_last_name, 
                 m.name as module_name, m.code as module_code, v.name as venue_name 
          FROM attendance_schedule a 
          JOIN lecturers l ON a.lecturer_id = l.id 
          JOIN modules m ON a.module_id = m.id 
          JOIN venues v ON a.venue_id = v.id 
          ORDER BY a.schedule_date DESC, a.schedule_time DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all lecturers for the form
$query = "SELECT * FROM lecturers ORDER BY last_name, first_name";
$stmt = $db->prepare($query);
$stmt->execute();
$lecturers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all modules for the form
$query = "SELECT * FROM modules ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all venues for the form
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
    <title>Schedule Attendance - Attendance System</title>
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
            <h2>Schedule Attendance</h2>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-section">
                <h3>Create New Schedule</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="lecturer_id">Select Lecturer:</label>
                        <select id="lecturer_id" name="lecturer_id" required>
                            <option value="">-- Select Lecturer --</option>
                            <?php foreach ($lecturers as $lecturer): ?>
                            <option value="<?php echo $lecturer['id']; ?>">
                                <?php echo htmlspecialchars($lecturer['first_name'] . ' ' . $lecturer['last_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="module_id">Select Module:</label>
                        <select id="module_id" name="module_id" required>
                            <option value="">-- Select Module --</option>
                            <?php foreach ($modules as $module): ?>
                            <option value="<?php echo $module['id']; ?>">
                                <?php echo htmlspecialchars($module['name'] . ' (' . $module['code'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="venue_id">Select Venue:</label>
                        <select id="venue_id" name="venue_id" required>
                            <option value="">-- Select Venue --</option>
                            <?php foreach ($venues as $venue): ?>
                            <option value="<?php echo $venue['id']; ?>">
                                <?php echo htmlspecialchars($venue['name'] . ' (Capacity: ' . $venue['capacity'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="schedule_date">Date:</label>
                        <input type="date" id="schedule_date" name="schedule_date" required>
                    </div>
                    <div class="form-group">
                        <label for="schedule_time">Time:</label>
                        <input type="time" id="schedule_time" name="schedule_time" required>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="schedule_attendance">Schedule Attendance</button>
                        <button type="submit" name="start_now" style="background-color: #28a745;">Start Attendance Now</button>
                    </div>
                </form>
            </div>
            
            <div class="form-section">
                <h3>Upload Classroom Picture</h3>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="venue_id_upload">Select Venue:</label>
                        <select id="venue_id_upload" name="venue_id" required>
                            <option value="">-- Select Venue --</option>
                            <?php foreach ($venues as $venue): ?>
                            <option value="<?php echo $venue['id']; ?>">
                                <?php echo htmlspecialchars($venue['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="classroom_picture">Classroom Picture:</label>
                        <input type="file" id="classroom_picture" name="classroom_picture" accept="image/*" required>
                    </div>
                    <button type="submit" name="upload_classroom">Upload Picture</button>
                </form>
            </div>
            
            <div class="table-section">
                <h3>Scheduled Attendance</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Lecturer</th>
                            <th>Module</th>
                            <th>Venue</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?php echo $schedule['id']; ?></td>
                            <td><?php echo htmlspecialchars($schedule['lecturer_first_name'] . ' ' . $schedule['lecturer_last_name']); ?></td>
                            <td><?php echo htmlspecialchars($schedule['module_name'] . ' (' . $schedule['module_code'] . ')'); ?></td>
                            <td><?php echo htmlspecialchars($schedule['venue_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($schedule['schedule_date'])); ?></td>
                            <td><?php echo date('g:i A', strtotime($schedule['schedule_time'])); ?></td>
                            <td>
                                <a href="take_attendance.php?schedule_id=<?php echo $schedule['id']; ?>">Take Attendance</a>
                                <a href="?delete=<?php echo $schedule['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script>
        // Set default date and time to current
        document.getElementById('schedule_date').valueAsDate = new Date();
        document.getElementById('schedule_time').value = new Date().toTimeString().substr(0, 5);
    </script>
</body>
</html>