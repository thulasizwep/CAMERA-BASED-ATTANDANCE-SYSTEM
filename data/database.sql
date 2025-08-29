-- Create Database
CREATE DATABASE IF NOT EXISTS attendance_system;
USE attendance_system;

-- Admin table
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Modules table
CREATE TABLE modules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lecturers table
CREATE TABLE lecturers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lecturer modules junction table
CREATE TABLE lecturer_modules (
    lecturer_id INT,
    module_id INT,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    PRIMARY KEY (lecturer_id, module_id)
);

-- Venues table
CREATE TABLE venues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    capacity INT NOT NULL,
    ip_camera VARCHAR(255) DEFAULT '0',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students table (with pictures column for face recognition)
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    student_number VARCHAR(20) NOT NULL UNIQUE,
    pictures JSON DEFAULT NULL,
    face_encoding_data TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Student modules junction table
CREATE TABLE student_modules (
    student_id INT,
    module_id INT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    PRIMARY KEY (student_id, module_id)
);

-- Attendance schedule table
CREATE TABLE attendance_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lecturer_id INT,
    module_id INT,
    venue_id INT,
    schedule_date DATE NOT NULL,
    schedule_time TIME NOT NULL,
    status ENUM('scheduled', 'in-progress', 'completed') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id),
    FOREIGN KEY (module_id) REFERENCES modules(id),
    FOREIGN KEY (venue_id) REFERENCES venues(id)
);

-- Attendance records table
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    schedule_id INT,
    attendance_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('present', 'absent', 'late') DEFAULT 'present',
    verification_picture VARCHAR(255) DEFAULT NULL,
    confidence_score FLOAT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (schedule_id) REFERENCES attendance_schedule(id)
);

-- Classroom pictures table
CREATE TABLE classroom_pictures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venue_id INT,
    picture_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
);

-- Face recognition models table
CREATE TABLE face_models (
    id INT PRIMARY KEY AUTO_INCREMENT,
    model_name VARCHAR(100) NOT NULL,
    model_path VARCHAR(255) NOT NULL,
    accuracy FLOAT DEFAULT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- System settings table
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Attendance reports table
CREATE TABLE attendance_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_id INT,
    total_students INT DEFAULT 0,
    present_count INT DEFAULT 0,
    absent_count INT DEFAULT 0,
    late_count INT DEFAULT 0,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES attendance_schedule(id)
);

-- Insert default admin (username: admin, password: admin123)
INSERT INTO admins (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample modules
INSERT INTO modules (name, code) VALUES 
('Mathematics', 'MATH101'),
('Computer Science', 'CS201'),
('Physics', 'PHYS301'),
('Engineering', 'ENG401');

-- Insert sample lecturers
INSERT INTO lecturers (first_name, last_name, email) VALUES 
('John', 'Smith', 'john.smith@university.edu'),
('Sarah', 'Johnson', 'sarah.johnson@university.edu'),
('Michael', 'Brown', 'michael.brown@university.edu');

-- Assign modules to lecturers
INSERT INTO lecturer_modules (lecturer_id, module_id) VALUES 
(1, 1), (1, 2), (2, 3), (3, 4);

-- Insert sample venues
INSERT INTO venues (name, capacity, ip_camera) VALUES 
('Lecture Hall A', 150, '0'),
('Computer Lab B', 30, '0'),
('Science Building Room 101', 50, '0'),
('Engineering Lab C', 25, '0');

-- Insert sample students
INSERT INTO students (first_name, last_name, student_number) VALUES 
('Alice', 'Williams', 'S1001'),
('Bob', 'Davis', 'S1002'),
('Charlie', 'Wilson', 'S1003'),
('Diana', 'Moore', 'S1004');

-- Assign modules to students
INSERT INTO student_modules (student_id, module_id) VALUES 
(1, 1), (1, 2),
(2, 1), (2, 3),
(3, 2), (3, 4),
(4, 3), (4, 4);

-- Insert sample attendance schedule
INSERT INTO attendance_schedule (lecturer_id, module_id, venue_id, schedule_date, schedule_time) VALUES 
(1, 1, 1, CURDATE(), '09:00:00'),
(2, 3, 3, CURDATE() + INTERVAL 1 DAY, '11:00:00'),
(3, 4, 4, CURDATE() + INTERVAL 2 DAY, '14:00:00');

-- Insert sample attendance records
INSERT INTO attendance (student_id, schedule_id, status) VALUES 
(1, 1, 'present'),
(2, 1, 'present'),
(3, 2, 'present'),
(4, 3, 'present');

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES 
('face_recognition_threshold', '0.6', 'Minimum confidence score for face recognition'),
('attendance_late_threshold', '15', 'Minutes after which attendance is marked late'),
('camera_capture_interval', '5', 'Seconds between camera captures during attendance'),
('max_attendance_attempts', '3', 'Maximum face recognition attempts per student');

-- Create indexes for better performance
CREATE INDEX idx_students_number ON students(student_number);
CREATE INDEX idx_attendance_student ON attendance(student_id);
CREATE INDEX idx_attendance_schedule ON attendance(schedule_id);
CREATE INDEX idx_schedule_date ON attendance_schedule(schedule_date);
CREATE INDEX idx_schedule_status ON attendance_schedule(status);