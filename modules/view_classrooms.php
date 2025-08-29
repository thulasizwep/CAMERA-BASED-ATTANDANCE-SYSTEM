<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get all venues with their pictures
$query = "SELECT v.*, 
                 (SELECT GROUP_CONCAT(picture_path) FROM classroom_pictures WHERE venue_id = v.id) as pictures
          FROM venues v
          ORDER BY v.name";
$stmt = $db->prepare($query);
$stmt->execute();
$venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Classrooms - Attendance System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .classroom-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .classroom-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
        }
        .classroom-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 10px 0;
        }
        .classroom-images img {
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
            <h2>View Classrooms</h2>
            
            <div class="classroom-gallery">
                <?php foreach ($venues as $venue): 
                    $pictures = $venue['pictures'] ? explode(',', $venue['pictures']) : [];
                ?>
                <div class="classroom-card">
                    <h3><?php echo htmlspecialchars($venue['name']); ?></h3>
                    <p><strong>Capacity:</strong> <?php echo $venue['capacity']; ?> students</p>
                    <p><strong>Camera:</strong> 
                        <?php if ($venue['ip_camera'] != '0'): ?>
                            <a href="<?php echo htmlspecialchars($venue['ip_camera']); ?>" target="_blank">View Live Feed</a>
                        <?php else: ?>
                            Webcam
                        <?php endif; ?>
                    </p>
                    
                    <h4>Classroom Pictures:</h4>
                    <div class="classroom-images">
                        <?php if (!empty($pictures)): ?>
                            <?php foreach ($pictures as $picture): ?>
                                <img src="uploads/classrooms/<?php echo $picture; ?>" alt="Classroom Picture">
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No pictures available</p>
                        <?php endif; ?>
                    </div>
                    
                    <a href="modules/manage_venues.php">Edit Venue</a>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html>