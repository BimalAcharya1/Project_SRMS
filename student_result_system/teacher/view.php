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

if ($course_code) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get course details and verify teacher assignment
        $course_query = "SELECT * FROM courses WHERE course_code = ? AND assigned_teacher_id = ?";
        $course_stmt = $conn->prepare($course_query);
        $course_stmt->bind_param("si", $course_code, $teacher_id);
        $course_stmt->execute();
        $course_result = $course_stmt->get_result();
        $course = $course_result->fetch_assoc();
        $course_stmt->close();
        
        if ($course) {
            // Get students enrolled in this course
            $students_query = "SELECT s.*, sc.enrollment_date, 
                              (SELECT status FROM results WHERE student_id = s.student_id AND course_code = ?) as result_status
                              FROM students s
                              JOIN student_courses sc ON s.student_id = sc.student_id
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
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        // Sample data for demo
        $course = [
            'course_code' => $course_code,
            'course_name' => 'Sample Course',
            'credit_hours' => 3,
            'semester' => '1st',
            'program' => 'BSc CSIT'
        ];
        
        $students = [
            ['student_id' => 'S001', 'full_name' => 'RAM BAHADUR', 'roll_number' => '001', 
             'email' => 'ram@example.com', 'program' => 'BSc CSIT', 'semester' => '1st',
             'contact' => '9841000001', 'enrollment_date' => '2024-01-15', 'result_status' => 'passed'],
            ['student_id' => 'S002', 'full_name' => 'SITA KUMARI', 'roll_number' => '002',
             'email' => 'sita@example.com', 'program' => 'BSc CSIT', 'semester' => '1st',
             'contact' => '9841000002', 'enrollment_date' => '2024-01-15', 'result_status' => 'passed'],
            ['student_id' => 'S003', 'full_name' => 'HARI PRASAD', 'roll_number' => '003',
             'email' => 'hari@example.com', 'program' => 'BSc CSIT', 'semester' => '1st',
             'contact' => '9841000003', 'enrollment_date' => '2024-01-15', 'result_status' => 'passed'],
            ['student_id' => 'S004', 'full_name' => 'GITA DEVI', 'roll_number' => '004',
             'email' => 'gita@example.com', 'program' => 'BSc CSIT', 'semester' => '1st',
             'contact' => '9841000004', 'enrollment_date' => '2024-01-15', 'result_status' => 'passed'],
            ['student_id' => 'S005', 'full_name' => 'SHYAM KUMAR', 'roll_number' => '005',
             'email' => 'shyam@example.com', 'program' => 'BSc CSIT', 'semester' => '1st',
             'contact' => '9841000005', 'enrollment_date' => '2024-01-15', 'result_status' => 'failed']
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
            padding: 15px;
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
        
        .action-btns {
            display: flex;
            gap: 5px;
        }
        
        .action-btn {
            padding: 6px 10px;
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
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
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
                flex-wrap: wrap;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 10px;
            }
            
            .action-btns {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> Course Students</h1>
            <a href="my_courses.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Courses
            </a>
        </div>
        
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
            <a href="add_student_to_course.php?course=<?php echo $course['course_code']; ?>" class="btn btn-warning">
                <i class="fas fa-user-plus"></i> Add Student
            </a>
            <button onclick="printTable()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print List
            </button>
        </div>
        
        <div class="table-container">
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
                            <th>Result Status</th>
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
                                <span class="badge <?php echo $status_badge; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="view_student_marks.php?student_id=<?php echo $student['student_id']; ?>" 
                                       class="action-btn action-btn-success" title="View Marks">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>
                                    <a href="enter_marks.php?course=<?php echo $course_code; ?>&student=<?php echo $student['student_id']; ?>" 
                                       class="action-btn action-btn-primary" title="Enter Marks">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="remove_student.php?course=<?php echo $course_code; ?>&student=<?php echo $student['student_id']; ?>" 
                                       class="action-btn action-btn-warning" title="Remove" 
                                       onclick="return confirm('Are you sure you want to remove this student from the course?')">
                                        <i class="fas fa-trash"></i>
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
                    <a href="add_student_to_course.php?course=<?php echo $course['course_code']; ?>" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-user-plus"></i> Add Students
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function printTable() {
        var printWindow = window.open('', '', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Student List - <?php echo $course["course_code"]; ?></title>');
        printWindow.document.write('<style>');
        printWindow.document.write(`
            body { font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
            th { background-color: #f5f5f5; font-weight: bold; }
            .header { text-align: center; margin-bottom: 20px; }
            .course-info { margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
            .print-date { text-align: right; margin-bottom: 20px; color: #666; }
        `);
        printWindow.document.write('</style>');
        printWindow.document.write('</head><body>');
        
        // Header
        printWindow.document.write('<div class="header">');
        printWindow.document.write('<h2>TU Result System</h2>');
        printWindow.document.write('<h3>Student List for <?php echo $course["course_code"] . " - " . $course["course_name"]; ?></h3>');
        printWindow.document.write('</div>');
        
        // Course info
        printWindow.document.write('<div class="course-info">');
        printWindow.document.write('<p><strong>Course:</strong> <?php echo $course["course_code"] . " - " . $course["course_name"]; ?></p>');
        printWindow.document.write('<p><strong>Program:</strong> <?php echo $course["program"]; ?> | <strong>Semester:</strong> <?php echo $course["semester"]; ?></p>');
        printWindow.document.write('<p><strong>Total Students:</strong> <?php echo count($students); ?></p>');
        printWindow.document.write('</div>');
        
        // Print date
        printWindow.document.write('<div class="print-date">');
        printWindow.document.write('Printed on: ' + new Date().toLocaleDateString());
        printWindow.document.write('</div>');
        
        // Table
        printWindow.document.write(document.getElementById('studentsTable').outerHTML);
        
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }
    </script>
</body>
</html>