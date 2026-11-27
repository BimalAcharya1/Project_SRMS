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

$student_id = $_GET['student_id'] ?? '';
$student = null;
$marks = [];

if ($student_id) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get student details
        $student_query = "SELECT * FROM students WHERE student_id = ?";
        $student_stmt = $conn->prepare($student_query);
        $student_stmt->bind_param("s", $student_id);
        $student_stmt->execute();
        $student_result = $student_stmt->get_result();
        $student = $student_result->fetch_assoc();
        $student_stmt->close();
        
        // Get student marks
        $marks_query = "SELECT r.*, c.course_name, c.credit_hours 
                       FROM results r 
                       JOIN courses c ON r.course_code = c.course_code 
                       WHERE r.student_id = ? 
                       ORDER BY c.semester";
        $marks_stmt = $conn->prepare($marks_query);
        $marks_stmt->bind_param("s", $student_id);
        $marks_stmt->execute();
        $marks_result = $marks_stmt->get_result();
        
        while ($row = $marks_result->fetch_assoc()) {
            $marks[] = $row;
        }
        $marks_stmt->close();
        
        $conn->close();
        
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
    }
}

if (!$student) {
    header('Location: view_students.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Marks - TU Result System</title>
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
            max-width: 1200px;
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
        
        .student-info {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .marks-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .grade-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
            min-width: 40px;
            text-align: center;
        }
        
        .grade-A { background: #2ecc71; color: white; }
        .grade-B { background: #3498db; color: white; }
        .grade-C { background: #f39c12; color: white; }
        .grade-D { background: #9b59b6; color: white; }
        .grade-F { background: #e74c3c; color: white; }
        
        .status-passed { color: #27ae60; font-weight: 600; }
        .status-failed { color: #e74c3c; font-weight: 600; }
        
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
        
        .stats-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
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
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chart-bar"></i> Student Marks Report</h1>
            <a href="view_students.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
        </div>
        
        <div class="student-info">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Student ID</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['student_id']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Full Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['full_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Program</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['program']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Semester</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['semester']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Roll Number</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['roll_number'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Contact</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['contact'] ?? 'N/A'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="marks-container">
            <h2 style="margin-bottom: 20px; color: #2c3e50;">Academic Performance</h2>
            
            <?php if (!empty($marks)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Credit</th>
                            <th>Theory</th>
                            <th>Practical</th>
                            <th>Internal</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th>GPA</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_credits = 0;
                        $total_gpa = 0;
                        foreach($marks as $mark): 
                            $total_credits += $mark['credit_hours'];
                            $total_gpa += ($mark['gpa'] * $mark['credit_hours']);
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($mark['course_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($mark['course_name']); ?></td>
                            <td><?php echo $mark['credit_hours']; ?></td>
                            <td><?php echo $mark['theory_marks'] ?? 0; ?></td>
                            <td><?php echo $mark['practical_marks'] ?? 0; ?></td>
                            <td><?php echo $mark['internal_marks'] ?? 0; ?></td>
                            <td><strong><?php echo $mark['total_marks']; ?></strong></td>
                            <td>
                                <span class="grade-badge grade-<?php echo $mark['grade']; ?>">
                                    <?php echo $mark['grade']; ?>
                                </span>
                            </td>
                            <td><strong><?php echo number_format($mark['gpa'], 2); ?></strong></td>
                            <td class="status-<?php echo $mark['status']; ?>">
                                <?php echo ucfirst($mark['status']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php 
                $sgpa = $total_credits > 0 ? $total_gpa / $total_credits : 0;
                $passed_count = count(array_filter($marks, function($m) { return $m['status'] == 'passed'; }));
                $failed_count = count($marks) - $passed_count;
                $pass_percentage = count($marks) > 0 ? ($passed_count / count($marks)) * 100 : 0;
                ?>
                
                <div class="stats-box">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($sgpa, 2); ?></div>
                        <div class="stat-label">SGPA</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_credits; ?></div>
                        <div class="stat-label">Total Credits</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $passed_count; ?></div>
                        <div class="stat-label">Passed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($pass_percentage, 1); ?>%</div>
                        <div class="stat-label">Pass Rate</div>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-chart-bar"></i>
                    <h3>No Marks Recorded</h3>
                    <p>No marks have been recorded for this student yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>