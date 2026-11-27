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
    header('Location:login.php');
    exit;
}

// Initialize variables
$stats = [
    'total_courses' => 0,
    'total_students' => 0,
    'total_marks' => 0,
    'pending_updates' => 0
];

$courses_data = [];
$recent_marks = [];

try {
    $db = new Database();
    $conn = $db->getConnection();
    $teacher_id = $_SESSION['teacher_id'];
    
    // Sample data for demonstration - Replace with actual database queries
    
    // Stats data
    $stats = [
        'total_courses' => 3,
        'total_students' => 45,
        'total_marks' => 125,
        'pending_updates' => 12
    ];
    
    // Sample courses data
    $courses_data = [
        [
            'course_code' => 'CSC101',
            'course_name' => 'Introduction to Programming',
            'credit_hours' => 3,
            'semester' => '1st',
            'course_type' => 'theory',
            'program' => 'BSc CSIT'
        ],
        [
            'course_code' => 'CSC102',
            'course_name' => 'Data Structures',
            'credit_hours' => 4,
            'semester' => '2nd',
            'course_type' => 'theory',
            'program' => 'BSc CSIT'
        ],
        [
            'course_code' => 'CSC103',
            'course_name' => 'Database Management',
            'credit_hours' => 3,
            'semester' => '3rd',
            'course_type' => 'theory',
            'program' => 'BSc CSIT'
        ]
    ];
    
    // Sample recent marks data (empty for "No Marks Entered Yet")
    $recent_marks = [];
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - TU Result System</title>
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
        
        .teacher-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .teacher-avatar {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #3498db;
        }
        
        .teacher-details h1 {
            font-size: 22px;
            margin-bottom: 5px;
        }
        
        .teacher-details p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .logout-btn {
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
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card.courses i { color: #3498db; }
        .stat-card.students i { color: #2ecc71; }
        .stat-card.marks i { color: #e74c3c; }
        .stat-card.updates i { color: #f39c12; }
        
        .stat-number {
            font-size: 2.2rem;
            font-weight: bold;
            margin: 10px 0;
            color: #2c3e50;
        }
        
        .stat-card h3 {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin: 30px 0 15px 0;
            color: #2c3e50;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
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
        }
        
        .course-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }
        
        .course-code {
            font-size: 22px;
            font-weight: 700;
            color: #3498db;
            margin-bottom: 10px;
        }
        
        .course-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
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
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .quick-actions {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-style: italic;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
        
        .no-data h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #7f8c8d;
        }
        
        .no-data p {
            font-size: 14px;
            color: #95a5a6;
        }
        
        @media (max-width: 768px) {
            .courses-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .quick-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="teacher-info">
                <div class="teacher-avatar">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="teacher-details">
                    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['teacher_name'] ?? 'AASHIS ACHARYA'); ?>!</h1>
                    <p>Department: <?php echo htmlspecialchars($_SESSION['teacher_department'] ?? 'Computer Science'); ?></p>
                </div>
            </div>
            <div>
                 <a href="teacher_logout.php" class="logout-btn">
                     <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <div class="quick-actions">
            <a href="view_students.php" class="btn btn-primary">
                <i class="fas fa-users"></i> View All Students
            </a>
            <a href="my_courses.php" class="btn btn-success">
                <i class="fas fa-book"></i> My Courses
            </a>
            <a href="enter_marks.php" class="btn btn-secondary">
                <i class="fas fa-edit"></i> Enter Marks
            </a>
        </div>
        
        <div class="stats-cards">
            <div class="stat-card courses">
                <i class="fas fa-book"></i>
                <div class="stat-number"><?php echo $stats['total_courses']; ?></div>
                <h3>Assigned Courses</h3>
            </div>
            <div class="stat-card students">
                <i class="fas fa-user-graduate"></i>
                <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                <h3>Students</h3>
            </div>
            <div class="stat-card marks">
                <i class="fas fa-chart-bar"></i>
                <div class="stat-number"><?php echo $stats['total_marks']; ?></div>
                <h3>Marks Entered</h3>
            </div>
            <div class="stat-card updates">
                <i class="fas fa-sync-alt"></i>
                <div class="stat-number"><?php echo $stats['pending_updates']; ?></div>
                <h3>Recent Updates</h3>
            </div>
        </div>
        
        <h2 class="section-title">My Assigned Courses</h2>
        <div class="courses-grid">
            <?php if (!empty($courses_data)): ?>
                <?php foreach($courses_data as $course): ?>
                <div class="course-card">
                    <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                    <div class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></div>
                    
                    <div class="course-details">
                        <div class="detail-item">
                            <span class="detail-label">Credit Hours</span>
                            <span class="detail-value"><?php echo $course['credit_hours']; ?></span>
                        </div>
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
                    </div>
                    
                    <div class="action-buttons">
                        <a href="enter_marks.php?course=<?php echo $course['course_code']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Enter Marks
                        </a>
                        <a href="view_course_students.php?course=<?php echo $course['course_code']; ?>" class="btn btn-success">
                            <i class="fas fa-users"></i> View Students
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data" style="grid-column: 1 / -1;">
                    <i class="fas fa-book"></i>
                    <h3>No Courses Assigned</h3>
                    <p>You don't have any courses assigned yet. Please contact the administrator.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <h2 class="section-title">Recently Updated Marks</h2>
        <div class="no-data">
            <i class="fas fa-chart-bar"></i>
            <h3>No Marks Entered Yet</h3>
            <p>Start entering marks for your assigned courses.</p>
        </div>
    </div>
</body>
</html>

<?php
// Clean up resources
if (isset($conn) && $conn) {
    $conn->close();
}
?>