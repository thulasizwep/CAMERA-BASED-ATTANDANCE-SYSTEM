<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['schedule_id'])) {
    header('Location: schedule_attendance.php');
    exit;
}

$schedule_id = $_GET['schedule_id'];
$database = new Database();
$db = $database->getConnection();

// Get schedule details
$query = "SELECT a.*, l.first_name, l.last_name, m.name as module_name, 
                 m.code as module_code, v.name as venue_name, v.ip_camera
          FROM attendance_schedule a
          JOIN lecturers l ON a.lecturer_id = l.id
          JOIN modules m ON a.module_id = m.id
          JOIN venues v ON a.venue_id = v.id
          WHERE a.id = :schedule_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':schedule_id', $schedule_id);
$stmt->execute();
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    die('Invalid schedule ID');
}

// Get students enrolled in this module
$query = "SELECT s.* FROM students s
          JOIN student_modules sm ON s.id = sm.student_id
          WHERE sm.module_id = :module_id
          ORDER BY s.last_name, s.first_name";
$stmt = $db->prepare($query);
$stmt->bindParam(':module_id', $schedule['module_id']);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {
    $student_id = $_POST['student_id'];
    
    // Handle verification picture
    $verification_picture = null;
    if (!empty($_FILES['verification_picture']['name'])) {
        $uploadDir = '../uploads/attendance/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $extension = pathinfo($_FILES['verification_picture']['name'], PATHINFO_EXTENSION);
        $filename = 'attendance_' . $schedule_id . '_' . $student_id . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['verification_picture']['tmp_name'], $destination)) {
            $verification_picture = $filename;
        }
    }
    
    // Check if attendance already exists
    $query = "SELECT id FROM attendance WHERE student_id = :student_id AND schedule_id = :schedule_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->bindParam(':schedule_id', $schedule_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Update existing attendance
        $query = "UPDATE attendance SET verification_picture = :picture WHERE student_id = :student_id AND schedule_id = :schedule_id";
    } else {
        // Create new attendance record
        $query = "INSERT INTO attendance (student_id, schedule_id, verification_picture) 
                  VALUES (:student_id, :schedule_id, :picture)";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->bindParam(':schedule_id', $schedule_id);
    $stmt->bindParam(':picture', $verification_picture);
    $stmt->execute();
    
    $success = "Attendance marked successfully";
}

// Get attendance records for this schedule
$query = "SELECT a.*, s.first_name, s.last_name, s.student_number 
          FROM attendance a
          JOIN students s ON a.student_id = s.id
          WHERE a.schedule_id = :schedule_id
          ORDER BY a.attendance_time DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':schedule_id', $schedule_id);
$stmt->execute();
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Attendance - Attendance System</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .camera-feed {
            width: 100%;
            max-width: 600px;
            border: 2px solid #ddd;
            border-radius: 5px;
            margin: 10px 0;
        }
        .attendance-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
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
            <h2>Take Attendance</h2>
            
            <div class="attendance-info">
                <h3>Class Information</h3>
                <p><strong>Module:</strong> <?php echo htmlspecialchars($schedule['module_name'] . ' (' . $schedule['module_code'] . ')'); ?></p>
                <p><strong>Lecturer:</strong> <?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?></p>
                <p><strong>Venue:</strong> <?php echo htmlspecialchars($schedule['venue_name']); ?></p>
                <p><strong>Scheduled:</strong> <?php echo date('M j, Y g:i A', strtotime($schedule['schedule_date'] . ' ' . $schedule['schedule_time'])); ?></p>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="attendance-section">
                <h3>Camera Feed</h3>
                <?php if ($schedule['ip_camera'] != '0'): ?>
                    <img src="<?php echo htmlspecialchars($schedule['ip_camera']); ?>" alt="Classroom Camera" class="camera-feed">
                <?php else: ?>
                    <p>Webcam feed would appear here. Camera not configured for this venue.</p>
                <?php endif; ?>
            </div>
            
            <div class="attendance-section">
                <h3>Mark Attendance</h3>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="student_id">Select Student:</label>
                        <select id="student_id" name="student_id" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_number'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="verification_picture">Verification Picture (Face Recognition):</label>
                        <input type="file" id="verification_picture" name="verification_picture" accept="image/*" capture="camera" required>
                    </div>
                    
                    <button type="submit" name="mark_attendance">Mark Attendance</button>
                </form>
            </div>
            
            <div class="attendance-section">
                <h3>Attendance Records</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student Number</th>
                            <th>Time</th>
                            <th>Verification</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['student_number']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($record['attendance_time'])); ?></td>
                            <td>
                                <?php if ($record['verification_picture']): ?>
                                    <img src="../uploads/attendance/<?php echo $record['verification_picture']; ?>" width="50" height="50" style="object-fit: cover;">
                                <?php else: ?>
                                    No picture
                                <?php endif; ?>
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