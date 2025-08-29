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

// Add new student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $student_number = $_POST['student_number'];
    $modules = $_POST['modules'] ?? [];
    
    // Handle picture uploads
    $pictures = [];
    if (!empty($_FILES['pictures']['name'][0])) {
        $uploadDir = '../uploads/students/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        for ($i = 0; $i < count($_FILES['pictures']['name']); $i++) {
            if ($_FILES['pictures']['error'][$i] === UPLOAD_ERR_OK) {
                $extension = pathinfo($_FILES['pictures']['name'][$i], PATHINFO_EXTENSION);
                $filename = $student_number . '_' . time() . '_' . $i . '.' . $extension;
                $destination = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['pictures']['tmp_name'][$i], $destination)) {
                    $pictures[] = $filename;
                }
            }
        }
    }
    
    $query = "INSERT INTO students (first_name, last_name, student_number, pictures) VALUES (:first_name, :last_name, :student_number, :pictures)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':student_number', $student_number);
    $stmt->bindParam(':pictures', json_encode($pictures));
    
    if ($stmt->execute()) {
        $student_id = $db->lastInsertId();
        
        // Assign modules to student
        if (!empty($modules)) {
            foreach ($modules as $module_id) {
                $query = "INSERT INTO student_modules (student_id, module_id) VALUES (:student_id, :module_id)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':student_id', $student_id);
                $stmt->bindParam(':module_id', $module_id);
                $stmt->execute();
            }
        }
        
        $success = "Student added successfully with " . count($pictures) . " pictures";
    } else {
        $error = "Error adding student";
    }
}

// Delete student
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get student pictures to delete them
    $query = "SELECT pictures FROM students WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete pictures from server
    if ($student && $student['pictures']) {
        $pictures = json_decode($student['pictures'], true);
        foreach ($pictures as $picture) {
            $filePath = '../uploads/students/' . $picture;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
    
    $query = "DELETE FROM students WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
}

// Get all students with their modules
$query = "SELECT s.*, GROUP_CONCAT(m.name) as module_names 
          FROM students s 
          LEFT JOIN student_modules sm ON s.id = sm.student_id 
          LEFT JOIN modules m ON sm.module_id = m.id 
          GROUP BY s.id 
          ORDER BY s.last_name, s.first_name";
$stmt = $db->prepare($query);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Manage Students - Attendance System</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .webcam-container {
            margin: 20px 0;
            border: 2px dashed #ccc;
            padding: 10px;
            border-radius: 5px;
        }
        #video, #canvas {
            width: 100%;
            max-width: 400px;
            border: 1px solid #ddd;
        }
        .capture-buttons {
            margin: 10px 0;
        }
        .captured-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 10px 0;
        }
        .captured-images img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 4px;
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
            <h2>Manage Students</h2>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-section">
                <h3>Add New Student</h3>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="first_name">First Name:</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label for="student_number">Student Number:</label>
                        <input type="text" id="student_number" name="student_number" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Capture Student Pictures (Minimum 5):</label>
                        <div class="webcam-container">
                            <video id="video" autoplay></video>
                            <canvas id="canvas" style="display:none;"></canvas>
                            <div class="capture-buttons">
                                <button type="button" id="start-camera">Start Camera</button>
                                <button type="button" id="capture" disabled>Capture Picture</button>
                            </div>
                            <div class="captured-images" id="captured-images"></div>
                            <input type="hidden" name="captured_images" id="captured-images-input">
                        </div>
                        <p>Or upload pictures:</p>
                        <input type="file" name="pictures[]" multiple accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label>Select Modules:</label>
                        <div class="checkbox-group">
                            <?php foreach ($modules as $module): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="modules[]" value="<?php echo $module['id']; ?>">
                                <?php echo htmlspecialchars($module['name']); ?> (<?php echo htmlspecialchars($module['code']); ?>)
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" name="add_student">Add Student</button>
                </form>
            </div>
            
            <div class="table-section">
                <h3>Existing Students</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Student Number</th>
                            <th>Pictures</th>
                            <th>Modules</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): 
                            $pictures = json_decode($student['pictures'] ?? '[]', true);
                        ?>
                        <tr>
                            <td><?php echo $student['id']; ?></td>
                            <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                            <td>
                                <?php if (!empty($pictures)): ?>
                                    <?php foreach ($pictures as $picture): ?>
                                        <img src="../uploads/students/<?php echo $picture; ?>" width="50" height="50" style="object-fit: cover; margin-right: 5px;">
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    No pictures
                                <?php endif; ?>
                            </td>
                            <td><?php echo $student['module_names'] ? htmlspecialchars($student['module_names']) : 'None'; ?></td>
                            <td>
                                <a href="?delete=<?php echo $student['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Webcam functionality
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const startCamera = document.getElementById('start-camera');
        const captureButton = document.getElementById('capture');
        const capturedImages = document.getElementById('captured-images');
        const capturedImagesInput = document.getElementById('captured-images-input');
        let stream = null;
        let capturedCount = 0;

        startCamera.addEventListener('click', async () => {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                captureButton.disabled = false;
                startCamera.disabled = true;
            } catch (err) {
                console.error('Error accessing camera:', err);
                alert('Error accessing camera: ' + err.message);
            }
        });

        captureButton.addEventListener('click', () => {
            if (capturedCount >= 10) {
                alert('Maximum 10 pictures allowed');
                return;
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const imageData = canvas.toDataURL('image/png');
            const img = document.createElement('img');
            img.src = imageData;
            
            capturedImages.appendChild(img);
            capturedCount++;
            
            // Store image data in hidden input
            const currentImages = capturedImagesInput.value ? JSON.parse(capturedImagesInput.value) : [];
            currentImages.push(imageData);
            capturedImagesInput.value = JSON.stringify(currentImages);
            
            if (capturedCount >= 5) {
                captureButton.textContent = 'Capture (' + (10 - capturedCount) + ' left)';
            }
            
            if (capturedCount >= 10) {
                captureButton.disabled = true;
            }
        });

        // Clean up when leaving the page
        window.addEventListener('beforeunload', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });
    </script>
</body>
</html>