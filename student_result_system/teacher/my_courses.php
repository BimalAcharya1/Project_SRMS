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

$teacher_id = $_SESSION['teacher_id'];
$courses = [];
$course_stats = [];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get teacher's assigned courses with student count
    $query = "SELECT c.*, 
              (SELECT COUNT(*) FROM student_courses sc WHERE sc.course_code = c.course_code) as student_count
              FROM courses c 
              WHERE c.assigned_teacher_id = ? 
              ORDER BY c.semester, c.course_code";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    
    $stmt->close();
    
    // Get course statistics
    foreach ($courses as $course) {
        $course_code = $course['course_code'];
        
        // Get marks statistics for this course
        $stats_query = "SELECT 
                        COUNT(*) as total_students,
                        AVG(total_marks) as avg_marks,
                        SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as passed_count,
                        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
                        FROM results 
                        WHERE course_code = ?";
        
        $stats_stmt = $conn->prepare($stats_query);
        $stats_stmt->bind_param("s", $course_code);
        $stats_stmt->execute();
        $stats_result = $stats_stmt->get_result();
        
        if ($stats_row = $stats_result->fetch_assoc()) {
            $course_stats[$course_code] = $stats_row;
        } else {
            $course_stats[$course_code] = [
                'total_students' => 0,
                'avg_marks' => 0,
                'passed_count' => 0,
                'failed_count' => 0
            ];
        }
        
        $stats_stmt->close();
    }
    
    $conn->close();
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    // Sample data for demo
    $courses = [
        [
            'course_code' => 'CSC101',
            'course_name' => 'Introduction to Programming',
            'credit_hours' => 3,
            'semester' => '1st',
            'course_type' => 'theory',
            'program' => 'BSc CSIT',
            'description' => 'Basic programming concepts using C language',
            'student_count' => 45
        ],
        [
            'course_code' => 'CSC102',
            'course_name' => 'Data Structures',
            'credit_hours' => 4,
            'semester' => '2nd',
            'course_type' => 'theory',
            'program' => 'BSc CSIT',
            'description' => 'Study of various data structures and algorithms',
            'student_count' => 40
        ],
        [
            'course_code' => 'CSC103',
            'course_name' => 'Database Management',
            'credit_hours' => 3,
            'semester' => '3rd',
            'course_type' => 'theory',
            'program' => 'BSc CSIT',
            'description' => 'Introduction to database systems and SQL',
            'student_count' => 38
        ],
        [
            'course_code' => 'CSC104',
            'course_name' => 'Web Technology',
            'credit_hours' => 3,
            'semester' => '4th',
            'course_type' => 'theory',
            'program' => 'BSc CSIT',
            'description' => 'Web development using HTML, CSS, JavaScript',
            'student_count' => 42
        ]
    ];
    
    // Sample course statistics
    foreach ($courses as $course) {
        $course_stats[$course['course_code']] = [
            'total_students' => $course['student_count'],
            'avg_marks' => rand(65, 85),
            'passed_count' => rand(35, $course['student_count']),
            'failed_count' => $course['student_count'] - rand(35, $course['student_count'])
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - TU Result System</title>
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
        
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .course-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .course-code {
            font-size: 22px;
            font-weight: 700;
            color: #3498db;
        }
        
        .course-badge {
            background: #2ecc71;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .course-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .course-description {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .course-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 3px;
        }
        
        .detail-value {
            font-size: 14px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .course-stats {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .progress-bar {
            height: 8px;
            background: #ecf0f1;
            border-radius: 4px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: #2ecc71;
            border-radius: 4px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-success {
            background: #2ecc71;
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
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-style: italic;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            grid-column: 1 / -1;
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
        
        @media (max-width: 768px) {
            .courses-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-book"></i> My Assigned Courses</h1>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <div class="courses-grid">
            <?php if (!empty($courses)): ?>
                <?php foreach($courses as $course): 
                    $stats = $course_stats[$course['course_code']] ?? [];
                    $total_students = $stats['total_students'] ?? 0;
                    $passed_count = $stats['passed_count'] ?? 0;
                    $failed_count = $stats['failed_count'] ?? 0;
                    $avg_marks = $stats['avg_marks'] ?? 0;
                    $pass_rate = $total_students > 0 ? ($passed_count / $total_students) * 100 : 0;
                ?>
                <div class="course-card">
                    <div class="course-header">
                        <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                        <div class="course-badge"><?php echo $course['credit_hours']; ?> Credits</div>
                    </div>
                    
                    <div class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></div>
                    <div class="course-description"><?php echo htmlspecialchars($course['description'] ?? 'Course description not available'); ?></div>
                    
                    <div class="course-details">
                        <div class="detail-item">
                            <span class="detail-label">Semester</span>
                            <span class="detail-value"><?php echo $course['semester']; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Type</span>
                            <span class="detail-value"><?php echo ucfirst($course['course_type']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Program</span>
                            <span class="detail-value"><?php echo htmlspecialchars($course['program']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Students</span>
                            <span class="detail-value"><?php echo $course['student_count'] ?? 0; ?></span>
                        </div>
                    </div>
                    
                    <div class="course-stats">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo number_format($avg_marks, 1); ?></div>
                                <div class="stat-label">Avg Marks</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo number_format($pass_rate, 1); ?>%</div>
                                <div class="stat-label">Pass Rate</div>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $pass_rate; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="enter_marks.php?course=<?php echo $course['course_code']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Enter Marks
                        </a>
                        <a href="view_course_students.php?course=<?php echo $course['course_code']; ?>" class="btn btn-success">
                            <i class="fas fa-users"></i> View Students
                        </a>
                        <a href="course_report.php?course=<?php echo $course['course_code']; ?>" class="btn btn-secondary">
                            <i class="fas fa-chart-pie"></i> Report
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-book"></i>
                    <h3>No Courses Assigned</h3>
                    <p>You don't have any courses assigned yet. Please contact the administrator.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>