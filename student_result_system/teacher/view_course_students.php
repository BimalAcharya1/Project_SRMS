<?php
session_start();

// Database Configuration
class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "student_result_system";
    public $conn;
    
    public function __construct() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// Check if teacher is logged in
if (!isset($_SESSION['teacher_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header('Location: teacher_login.php');
    exit;
}

$course_code = $_GET['course'] ?? '';
$teacher_id = $_SESSION['teacher_id'];
$course = null;
$students = [];
$message = '';
$message_type = '';

// Handle remove student action
if (isset($_GET['remove']) && isset($_GET['student_id'])) {
    $remove_student_id = $_GET['student_id'];
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // First, delete from results table (if exists)
            $delete_results_query = "DELETE FROM results WHERE student_id = ? AND course_code = ?";
            $delete_results_stmt = $conn->prepare($delete_results_query);
            $delete_results_stmt->bind_param("ss", $remove_student_id, $course_code);
            $delete_results_stmt->execute();
            $delete_results_stmt->close();
            
            // Then delete from student_courses
            $remove_query = "DELETE FROM student_courses WHERE student_id = ? AND course_code = ?";
            $remove_stmt = $conn->prepare($remove_query);
            $remove_stmt->bind_param("ss", $remove_student_id, $course_code);
            
            if ($remove_stmt->execute()) {
                $conn->commit();
                $message = "Student removed from course successfully!";
                $message_type = "success";
                
                // JavaScript redirect to refresh page
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'view_course_students.php?course=" . urlencode($course_code) . "&msg=Student+removed+successfully&type=success';
                    }, 100);
                </script>";
                exit;
            } else {
                $conn->rollback();
                $message = "Error removing student: " . $remove_stmt->error;
                $message_type = "error";
            }
            
            $remove_stmt->close();
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        $message = "Error removing student: " . $e->getMessage();
        $message_type = "error";
    }
}

if ($course_code) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Check if tables exist, create them if not
        $tables_check = $conn->query("SHOW TABLES LIKE 'courses'");
        if ($tables_check->num_rows == 0) {
            // Create courses table
            $conn->query("CREATE TABLE courses (
                course_code VARCHAR(20) PRIMARY KEY,
                course_name VARCHAR(100) NOT NULL,
                credit_hours INT,
                semester VARCHAR(20),
                program VARCHAR(50),
                course_type VARCHAR(20),
                assigned_teacher_id INT,
                description TEXT
            )");
            
            // Insert sample courses
            $sample_courses = [
                "INSERT INTO courses VALUES ('CSC101', 'Introduction to Programming', 3, '1st', 'BSc CSIT', 'Theory', 1, 'Basic programming concepts using C language')",
                "INSERT INTO courses VALUES ('CSC102', 'Data Structures', 4, '2nd', 'BSc CSIT', 'Theory', 1, 'Study of various data structures and algorithms')",
                "INSERT INTO courses VALUES ('CSC103', 'Database Management', 3, '3rd', 'BSc CSIT', 'Theory', 1, 'Introduction to database systems and SQL')",
                "INSERT INTO courses VALUES ('CSC104', 'Web Technology', 3, '4th', 'BSc CSIT', 'Theory', 1, 'Web development using HTML, CSS, JavaScript')"
            ];
            
            foreach ($sample_courses as $sql) {
                $conn->query($sql);
            }
        }
        
        // Check if student_courses table exists
        $student_courses_check = $conn->query("SHOW TABLES LIKE 'student_courses'");
        if ($student_courses_check->num_rows == 0) {
            $conn->query("CREATE TABLE student_courses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id VARCHAR(50),
                course_code VARCHAR(20),
                enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
                FOREIGN KEY (course_code) REFERENCES courses(course_code) ON DELETE CASCADE
            )");
        }
        
        // Check if results table exists
        $results_check = $conn->query("SHOW TABLES LIKE 'results'");
        if ($results_check->num_rows == 0) {
            $conn->query("CREATE TABLE results (
                result_id INT AUTO_INCREMENT PRIMARY KEY,
                student_id VARCHAR(50),
                course_code VARCHAR(20),
                theory_marks INT DEFAULT 0,
                practical_marks INT DEFAULT 0,
                internal_marks INT DEFAULT 0,
                total_marks INT,
                grade VARCHAR(2),
                gpa DECIMAL(3,2),
                status VARCHAR(20),
                entered_by INT,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
                FOREIGN KEY (course_code) REFERENCES courses(course_code) ON DELETE CASCADE
            )");
        }
        
        // Get course details
        $course_query = "SELECT * FROM courses WHERE course_code = ? AND assigned_teacher_id = ?";
        $course_stmt = $conn->prepare($course_query);
        $course_stmt->bind_param("si", $course_code, $teacher_id);
        $course_stmt->execute();
        $course_result = $course_stmt->get_result();
        $course = $course_result->fetch_assoc();
        $course_stmt->close();
        
        if ($course) {
            // Get students enrolled in this course
            $students_query = "SELECT s.*, 
                              DATE_FORMAT(sc.enrollment_date, '%Y-%m-%d') as enrollment_date,
                              r.status as result_status,
                              r.total_marks,
                              r.grade
                              FROM students s
                              JOIN student_courses sc ON s.student_id = sc.student_id
                              LEFT JOIN results r ON s.student_id = r.student_id AND r.course_code = ?
                              WHERE sc.course_code = ? 
                              ORDER BY s.roll_number";
            
            $students_stmt = $conn->prepare($students_query);
            $students_stmt->bind_param("ss", $course_code, $course_code);
            $students_stmt->execute();
            $students_result = $students_stmt->get_result();
            
            while ($row = $students_result->fetch_assoc()) {
                $students[] = $row;
            }
            $students_stmt->close();
            
            // If no students are enrolled, enroll some sample students
            if (empty($students)) {
                // Get all students from the same program
                $available_students_query = "SELECT student_id FROM students WHERE program = ? LIMIT 5";
                $avail_stmt = $conn->prepare($available_students_query);
                $avail_stmt->bind_param("s", $course['program']);
                $avail_stmt->execute();
                $avail_result = $avail_stmt->get_result();
                
                while ($student_row = $avail_result->fetch_assoc()) {
                    // Enroll student in course
                    $enroll_query = "INSERT INTO student_courses (student_id, course_code) VALUES (?, ?)";
                    $enroll_stmt = $conn->prepare($enroll_query);
                    $enroll_stmt->bind_param("ss", $student_row['student_id'], $course_code);
                    $enroll_stmt->execute();
                    $enroll_stmt->close();
                    
                    // Add sample marks for some students
                    if (rand(0, 1)) {
                        $theory = rand(0, 60);
                        $practical = rand(0, 20);
                        $internal = rand(10, 20);
                        $total = $theory + $practical + $internal;
                        
                        // Calculate grade and status
                        $grade = 'F';
                        $gpa = 0.0;
                        $status = 'failed';
                        
                        if ($total >= 80) {
                            $grade = 'A'; $gpa = 4.0; $status = 'passed';
                        } elseif ($total >= 70) {
                            $grade = 'B'; $gpa = 3.0; $status = 'passed';
                        } elseif ($total >= 60) {
                            $grade = 'C'; $gpa = 2.0; $status = 'passed';
                        } elseif ($total >= 50) {
                            $grade = 'D'; $gpa = 1.0; $status = 'passed';
                        }
                        
                        $marks_query = "INSERT INTO results 
                                       (student_id, course_code, theory_marks, practical_marks, internal_marks, 
                                       total_marks, grade, gpa, status, entered_by) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $marks_stmt = $conn->prepare($marks_query);
                        $marks_stmt->bind_param("ssiiiisdii", 
                            $student_row['student_id'], $course_code,
                            $theory, $practical, $internal,
                            $total, $grade, $gpa, $status,
                            $teacher_id
                        );
                        $marks_stmt->execute();
                        $marks_stmt->close();
                    }
                }
                $avail_stmt->close();
                
                // Refresh students list
                $students_stmt = $conn->prepare($students_query);
                $students_stmt->bind_param("ss", $course_code, $course_code);
                $students_stmt->execute();
                $students_result = $students_stmt->get_result();
                
                while ($row = $students_result->fetch_assoc()) {
                    $students[] = $row;
                }
                $students_stmt->close();
            }
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        // Fallback to sample data
        $course = [
            'course_code' => $course_code,
            'course_name' => 'Sample Course',
            'credit_hours' => 3,
            'semester' => '1st',
            'program' => 'BSc CSIT',
            'course_type' => 'Theory',
            'description' => 'Course description'
        ];
        
        $students = [
            ['student_id' => 'ABC-123-457', 'full_name' => 'AASHIS ACHARYA', 'roll_number' => '001', 
             'email' => 'pradipacharya9090@gmail.com', 'program' => 'BBA', 'semester' => '1st',
             'contact' => '9841000001', 'enrollment_date' => '2024-01-15', 'result_status' => 'passed', 'total_marks' => 85, 'grade' => 'B'],
            ['student_id' => 'ABC-123-456', 'full_name' => 'Sharma Bimal', 'roll_number' => '002',
             'email' => 'test@gmail.com', 'program' => 'BCA', 'semester' => '1st',
             'contact' => '9841000002', 'enrollment_date' => '2024-01-15', 'result_status' => 'passed', 'total_marks' => 92, 'grade' => 'A'],
            ['student_id' => 'S003', 'full_name' => 'RAM BAHADUR', 'roll_number' => '003',
             'email' => 'ram@example.com', 'program' => 'BSc CSIT', 'semester' => '1st',
             'contact' => '9841000003', 'enrollment_date' => '2024-01-15', 'result_status' => 'passed', 'total_marks' => 78, 'grade' => 'C'],
            ['student_id' => 'S004', 'full_name' => 'SITA KUMARI', 'roll_number' => '004',
             'email' => 'sita@example.com', 'program' => 'BSc CSIT', 'semester' => '1st',
             'contact' => '9841000004', 'enrollment_date' => '2024-01-15', 'result_status' => 'failed', 'total_marks' => 45, 'grade' => 'F'],
            ['student_id' => 'S005', 'full_name' => 'HARI PRASAD', 'roll_number' => '005',
             'email' => 'hari@example.com', 'program' => 'BSc CSIT', 'semester' => '1st',
             'contact' => '9841000005', 'enrollment_date' => '2024-01-15', 'result_status' => 'pending', 'total_marks' => null, 'grade' => null]
        ];
    }
}

if (!$course) {
    header('Location: my_courses.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Students - <?php echo htmlspecialchars($course['course_code']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .course-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 3px;
        }
        
        .info-value {
            font-size: 14px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d5edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .stats-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #2ecc71;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .table-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-primary {
            background: #3498db;
            color: white;
        }
        
        .badge-success {
            background: #2ecc71;
            color: white;
        }
        
        .badge-warning {
            background: #f39c12;
            color: white;
        }
        
        .badge-danger {
            background: #e74c3c;
            color: white;
        }
        
        .badge-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .grade-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            min-width: 30px;
            text-align: center;
            display: inline-block;
        }
        
        .grade-A { background: #2ecc71; color: white; }
        .grade-B { background: #3498db; color: white; }
        .grade-C { background: #f39c12; color: white; }
        .grade-D { background: #9b59b6; color: white; }
        .grade-F { background: #e74c3c; color: white; }
        
        .action-btns {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s;
        }
        
        .action-btn-primary {
            background: #3498db;
            color: white;
        }
        
        .action-btn-success {
            background: #2ecc71;
            color: white;
        }
        
        .action-btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .action-btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .search-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-style: italic;
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
        
        @media (max-width: 768px) {
            .stats-bar {
                flex-direction: column;
                gap: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 8px;
            }
            
            .action-btns {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> Course Students: <?php echo htmlspecialchars($course['course_code']); ?></h1>
            <a href="my_courses.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Courses
            </a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> 
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="course-info">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Course Code</span>
                    <span class="info-value"><?php echo htmlspecialchars($course['course_code']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Course Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($course['course_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Credit Hours</span>
                    <span class="info-value"><?php echo $course['credit_hours']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Semester</span>
                    <span class="info-value"><?php echo $course['semester']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Program</span>
                    <span class="info-value"><?php echo htmlspecialchars($course['program']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Course Type</span>
                    <span class="info-value"><?php echo ucfirst($course['course_type']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-number"><?php echo count($students); ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-item">
                <?php 
                $passed = array_filter($students, function($s) { return $s['result_status'] == 'passed'; });
                $passed_count = count($passed);
                ?>
                <div class="stat-number"><?php echo $passed_count; ?></div>
                <div class="stat-label">Passed</div>
            </div>
            <div class="stat-item">
                <?php 
                $failed = array_filter($students, function($s) { return $s['result_status'] == 'failed'; });
                $failed_count = count($failed);
                ?>
                <div class="stat-number"><?php echo $failed_count; ?></div>
                <div class="stat-label">Failed</div>
            </div>
            <div class="stat-item">
                <?php 
                $pending = array_filter($students, function($s) { return empty($s['result_status']); });
                $pending_count = count($pending);
                ?>
                <div class="stat-number"><?php echo $pending_count; ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="enter_marks.php?course=<?php echo $course['course_code']; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Enter Marks
            </a>
            <a href="course_report.php?course=<?php echo $course['course_code']; ?>" class="btn btn-success">
                <i class="fas fa-chart-pie"></i> View Report
            </a>
            
            </a>
            <button onclick="printTable()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print List
            </button>
        </div>
        
        <div class="table-container">
            <div class="search-box">
                <input type="text" id="searchInput" class="search-input" placeholder="Search students by name, roll number, or ID...">
            </div>
            
            <?php if (!empty($students)): ?>
                <table id="studentsTable">
                    <thead>
                        <tr>
                            <th>Roll No</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Program</th>
                            <th>Semester</th>
                            <th>Contact</th>
                            <th>Enrollment Date</th>
                            <th>Marks</th>
                            <th>Grade</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $student): 
                            $status_badge = 'badge-secondary';
                            $status_text = 'Pending';
                            
                            if ($student['result_status'] == 'passed') {
                                $status_badge = 'badge-success';
                                $status_text = 'Passed';
                            } elseif ($student['result_status'] == 'failed') {
                                $status_badge = 'badge-danger';
                                $status_text = 'Failed';
                            }
                            
                            $grade_badge = '';
                            if ($student['grade']) {
                                $grade_badge = 'grade-' . $student['grade'];
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                                <small style="color: #7f8c8d;">ID: <?php echo htmlspecialchars($student['student_id']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td>
                                <span class="badge badge-primary"><?php echo htmlspecialchars($student['program']); ?></span>
                            </td>
                            <td>
                                <span class="badge badge-warning"><?php echo htmlspecialchars($student['semester']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($student['contact'] ?? 'N/A'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($student['enrollment_date'])); ?></td>
                            <td>
                                <?php if ($student['total_marks']): ?>
                                    <strong><?php echo $student['total_marks']; ?>/120</strong>
                                <?php else: ?>
                                    <span style="color: #95a5a6;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($student['grade']): ?>
                                    <span class="grade-badge <?php echo $grade_badge; ?>">
                                        <?php echo $student['grade']; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #95a5a6;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $status_badge; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="view_student_marks.php?student_id=<?php echo $student['student_id']; ?>&course=<?php echo $course['course_code']; ?>" 
                                       class="action-btn action-btn-success" title="View Marks">
                                        <i class="fas fa-chart-bar"></i> Marks
                                    </a>
                                    <a href="enter_marks.php?course=<?php echo $course_code; ?>&student=<?php echo $student['student_id']; ?>" 
                                       class="action-btn action-btn-primary" title="Enter/Edit Marks">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                   
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-users"></i>
                    <h3>No Students Enrolled</h3>
                    <p>No students have been enrolled in this course yet.</p>
                   
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#studentsTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
    
    // Print functionality
    function printTable() {
        const printWindow = window.open('', '', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Student List - <?php echo $course["course_code"]; ?></title>');
        printWindow.document.write('<style>');
        printWindow.document.write(`
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1, h2, h3 { color: #333; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
            th { background-color: #f5f5f5; font-weight: bold; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #3498db; padding-bottom: 20px; }
            .course-info { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
            .print-date { text-align: right; margin: 20px 0; color: #666; font-size: 14px; }
            .stats { display: flex; justify-content: space-between; margin: 20px 0; }
            .stat-box { text-align: center; padding: 15px; background: white; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
            .badge { padding: 3px 8px; border-radius: 3px; font-size: 12px; }
            .badge-success { background: #2ecc71; color: white; }
            .badge-danger { background: #e74c3c; color: white; }
            .badge-secondary { background: #95a5a6; color: white; }
        `);
        printWindow.document.write('</style>');
        printWindow.document.write('</head><body>');
        
        // Header
        printWindow.document.write('<div class="header">');
        printWindow.document.write('<h1>TU Result System</h1>');
        printWindow.document.write('<h2>Student List - <?php echo $course["course_code"] . ": " . $course["course_name"]; ?></h2>');
        printWindow.document.write('<p>Printed on: ' + new Date().toLocaleDateString() + '</p>');
        printWindow.document.write('</div>');
        
        // Course info
        printWindow.document.write('<div class="course-info">');
        printWindow.document.write('<p><strong>Course:</strong> <?php echo $course["course_code"] . " - " . $course["course_name"]; ?></p>');
        printWindow.document.write('<p><strong>Program:</strong> <?php echo $course["program"]; ?> | <strong>Semester:</strong> <?php echo $course["semester"]; ?> | <strong>Credits:</strong> <?php echo $course["credit_hours"]; ?></p>');
        printWindow.document.write('</div>');
        
        // Statistics
        printWindow.document.write('<div class="stats">');
        printWindow.document.write('<div class="stat-box"><h3><?php echo count($students); ?></h3><p>Total Students</p></div>');
        printWindow.document.write('<div class="stat-box"><h3><?php echo count(array_filter($students, function($s) { return $s["result_status"] == "passed"; })); ?></h3><p>Passed</p></div>');
        printWindow.document.write('<div class="stat-box"><h3><?php echo count(array_filter($students, function($s) { return $s["result_status"] == "failed"; })); ?></h3><p>Failed</p></div>');
        printWindow.document.write('<div class="stat-box"><h3><?php echo count(array_filter($students, function($s) { return empty($s["result_status"]); })); ?></h3><p>Pending</p></div>');
        printWindow.document.write('</div>');
        
        // Table
        printWindow.document.write(document.getElementById('studentsTable').outerHTML);
        
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }
    
    // Auto-focus search input
    document.getElementById('searchInput').focus();
    </script>
</body>
</html>