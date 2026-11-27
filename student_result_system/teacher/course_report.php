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
$statistics = [];

if ($course_code) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get course details and verify teacher assignment
        $course_query = "SELECT c.*, t.full_name as teacher_name 
                        FROM courses c 
                        LEFT JOIN teachers t ON c.assigned_teacher_id = t.teacher_id 
                        WHERE c.course_code = ? AND c.assigned_teacher_id = ?";
        $course_stmt = $conn->prepare($course_query);
        $course_stmt->bind_param("si", $course_code, $teacher_id);
        $course_stmt->execute();
        $course_result = $course_stmt->get_result();
        $course = $course_result->fetch_assoc();
        $course_stmt->close();
        
        if ($course) {
            // Get students enrolled in this course with their marks
            $students_query = "SELECT s.*, r.theory_marks, r.practical_marks, r.internal_marks, 
                              r.total_marks, r.grade, r.gpa, r.status, r.last_updated
                              FROM students s
                              LEFT JOIN student_courses sc ON s.student_id = sc.student_id
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
            
            // Calculate statistics
            if (!empty($students)) {
                $total_students = count($students);
                $total_marks = array_sum(array_column($students, 'total_marks'));
                $avg_marks = $total_students > 0 ? $total_marks / $total_students : 0;
                
                $passed = array_filter($students, function($s) { return $s['status'] == 'passed'; });
                $failed = array_filter($students, function($s) { return $s['status'] == 'failed'; });
                $pending = array_filter($students, function($s) { return empty($s['status']); });
                
                $passed_count = count($passed);
                $failed_count = count($failed);
                $pending_count = count($pending);
                $pass_percentage = $total_students > 0 ? ($passed_count / $total_students) * 100 : 0;
                
                // Grade distribution
                $grades = array_column($students, 'grade');
                $grade_counts = array_count_values($grades);
                
                $statistics = [
                    'total_students' => $total_students,
                    'avg_marks' => $avg_marks,
                    'passed_count' => $passed_count,
                    'failed_count' => $failed_count,
                    'pending_count' => $pending_count,
                    'pass_percentage' => $pass_percentage,
                    'grade_distribution' => $grade_counts,
                    'highest_mark' => max(array_column($students, 'total_marks')),
                    'lowest_mark' => min(array_column($students, 'total_marks'))
                ];
            }
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
            'program' => 'BSc CSIT',
            'course_type' => 'Theory',
            'teacher_name' => $_SESSION['teacher_name'] ?? 'Teacher'
        ];
        
        $students = [
            ['student_id' => 'S001', 'full_name' => 'RAM BAHADUR', 'roll_number' => '001', 
             'theory_marks' => 65, 'practical_marks' => 25, 'internal_marks' => 18, 'total_marks' => 108, 
             'grade' => 'B', 'gpa' => 3.0, 'status' => 'passed'],
            ['student_id' => 'S002', 'full_name' => 'SITA KUMARI', 'roll_number' => '002',
             'theory_marks' => 72, 'practical_marks' => 28, 'internal_marks' => 20, 'total_marks' => 120,
             'grade' => 'A', 'gpa' => 4.0, 'status' => 'passed'],
            ['student_id' => 'S003', 'full_name' => 'HARI PRASAD', 'roll_number' => '003',
             'theory_marks' => 45, 'practical_marks' => 20, 'internal_marks' => 15, 'total_marks' => 80,
             'grade' => 'C', 'gpa' => 2.0, 'status' => 'passed'],
            ['student_id' => 'S004', 'full_name' => 'GITA DEVI', 'roll_number' => '004',
             'theory_marks' => 30, 'practical_marks' => 15, 'internal_marks' => 10, 'total_marks' => 55,
             'grade' => 'D', 'gpa' => 1.0, 'status' => 'passed'],
            ['student_id' => 'S005', 'full_name' => 'SHYAM KUMAR', 'roll_number' => '005',
             'theory_marks' => 25, 'practical_marks' => 10, 'internal_marks' => 8, 'total_marks' => 43,
             'grade' => 'F', 'gpa' => 0.0, 'status' => 'failed']
        ];
        
        $statistics = [
            'total_students' => 5,
            'avg_marks' => 81.2,
            'passed_count' => 4,
            'failed_count' => 1,
            'pending_count' => 0,
            'pass_percentage' => 80.0,
            'grade_distribution' => ['A' => 1, 'B' => 1, 'C' => 1, 'D' => 1, 'F' => 1],
            'highest_mark' => 120,
            'lowest_mark' => 43
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
    <title>Course Report - <?php echo htmlspecialchars($course['course_code']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-card.courses .stat-number { color: #3498db; }
        .stat-card.students .stat-number { color: #2ecc71; }
        .stat-card.marks .stat-number { color: #e74c3c; }
        .stat-card.updates .stat-number { color: #f39c12; }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .chart-box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2c3e50;
            text-align: center;
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
        
        .grade-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            min-width: 40px;
            text-align: center;
            display: inline-block;
        }
        
        .grade-A { background: #2ecc71; color: white; }
        .grade-B { background: #3498db; color: white; }
        .grade-C { background: #f39c12; color: white; }
        .grade-D { background: #9b59b6; color: white; }
        .grade-F { background: #e74c3c; color: white; }
        
        .status-passed { color: #27ae60; font-weight: 600; }
        .status-failed { color: #e74c3c; font-weight: 600; }
        
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
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .print-btn {
            background: #f39c12;
            color: white;
        }
        
        .export-btn {
            background: #9b59b6;
            color: white;
        }
        
        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-wrap: wrap;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chart-pie"></i> Course Performance Report</h1>
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
                <div class="info-item">
                    <span class="info-label">Course Type</span>
                    <span class="info-value"><?php echo ucfirst($course['course_type']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Assigned Teacher</span>
                    <span class="info-value"><?php echo htmlspecialchars($course['teacher_name']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="enter_marks.php?course=<?php echo $course['course_code']; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Enter Marks
            </a>
            <a href="view_course_students.php?course=<?php echo $course['course_code']; ?>" class="btn btn-success">
                <i class="fas fa-users"></i> View Students
            </a>
            <button onclick="window.print()" class="btn print-btn">
                <i class="fas fa-print"></i> Print Report
            </button>
            <button onclick="exportToExcel()" class="btn export-btn">
                <i class="fas fa-file-excel"></i> Export to Excel
            </button>
        </div>
        
        <div class="stats-cards">
            <div class="stat-card students">
                <div class="stat-number"><?php echo $statistics['total_students'] ?? 0; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card marks">
                <div class="stat-number"><?php echo number_format($statistics['avg_marks'] ?? 0, 1); ?></div>
                <div class="stat-label">Average Marks</div>
            </div>
            <div class="stat-card courses">
                <div class="stat-number"><?php echo $statistics['passed_count'] ?? 0; ?></div>
                <div class="stat-label">Passed Students</div>
            </div>
            <div class="stat-card updates">
                <div class="stat-number"><?php echo number_format($statistics['pass_percentage'] ?? 0, 1); ?>%</div>
                <div class="stat-label">Pass Percentage</div>
            </div>
        </div>
        
        <div class="charts-container">
            <div class="chart-box">
                <div class="chart-title">Grade Distribution</div>
                <canvas id="gradeChart"></canvas>
            </div>
            
            <div class="chart-box">
                <div class="chart-title">Performance Overview</div>
                <canvas id="performanceChart"></canvas>
            </div>
        </div>
        
        <div class="table-container">
            <h3 style="margin-bottom: 20px; color: #2c3e50;">Detailed Student Results</h3>
            
            <table>
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Student Name</th>
                        <th>Theory</th>
                        <th>Practical</th>
                        <th>Internal</th>
                        <th>Total</th>
                        <th>Grade</th>
                        <th>GPA</th>
                        <th>Status</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($students)): ?>
                        <?php foreach($students as $student): 
                            $percentage = 120 > 0 ? ($student['total_marks'] / 120) * 100 : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                                <small style="color: #7f8c8d;">ID: <?php echo htmlspecialchars($student['student_id']); ?></small>
                            </td>
                            <td><?php echo $student['theory_marks'] ?? 0; ?></td>
                            <td><?php echo $student['practical_marks'] ?? 0; ?></td>
                            <td><?php echo $student['internal_marks'] ?? 0; ?></td>
                            <td><strong><?php echo $student['total_marks'] ?? 0; ?></strong></td>
                            <td>
                                <?php if (isset($student['grade'])): ?>
                                    <span class="grade-badge grade-<?php echo $student['grade']; ?>">
                                        <?php echo $student['grade']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="grade-badge grade-F">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo $student['gpa'] ?? '0.0'; ?></strong></td>
                            <td class="status-<?php echo $student['status'] ?? 'pending'; ?>">
                                <?php echo ucfirst($student['status'] ?? 'pending'); ?>
                            </td>
                            <td><strong><?php echo number_format($percentage, 1); ?>%</strong></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                <i class="fas fa-users" style="font-size: 48px; margin-bottom: 15px; display: block; color: #bdc3c7;"></i>
                                <h3>No Students Enrolled</h3>
                                <p>No students have been enrolled in this course yet.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
    // Grade Distribution Chart
    const gradeCtx = document.getElementById('gradeChart').getContext('2d');
    const gradeChart = new Chart(gradeCtx, {
        type: 'pie',
        data: {
            labels: ['A', 'B', 'C', 'D', 'F', 'N/A'],
            datasets: [{
                data: [
                    <?php echo $statistics['grade_distribution']['A'] ?? 0; ?>,
                    <?php echo $statistics['grade_distribution']['B'] ?? 0; ?>,
                    <?php echo $statistics['grade_distribution']['C'] ?? 0; ?>,
                    <?php echo $statistics['grade_distribution']['D'] ?? 0; ?>,
                    <?php echo $statistics['grade_distribution']['F'] ?? 0; ?>,
                    <?php echo $statistics['pending_count'] ?? 0; ?>
                ],
                backgroundColor: [
                    '#2ecc71', // A - Green
                    '#3498db', // B - Blue
                    '#f39c12', // C - Orange
                    '#9b59b6', // D - Purple
                    '#e74c3c', // F - Red
                    '#95a5a6'  // N/A - Gray
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                title: {
                    display: false
                }
            }
        }
    });
    
    // Performance Overview Chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    const performanceChart = new Chart(performanceCtx, {
        type: 'bar',
        data: {
            labels: ['Passed', 'Failed', 'Pending'],
            datasets: [{
                label: 'Students',
                data: [
                    <?php echo $statistics['passed_count'] ?? 0; ?>,
                    <?php echo $statistics['failed_count'] ?? 0; ?>,
                    <?php echo $statistics['pending_count'] ?? 0; ?>
                ],
                backgroundColor: [
                    '#2ecc71', // Passed - Green
                    '#e74c3c', // Failed - Red
                    '#f39c12'  // Pending - Orange
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Students'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Status'
                    }
                }
            },
            plugins: {
                title: {
                    display: false
                }
            }
        }
    });
    
    // Export to Excel function
    function exportToExcel() {
        const table = document.querySelector('table');
        const rows = table.querySelectorAll('tr');
        let csv = [];
        
        for (let i = 0; i < rows.length; i++) {
            const row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                // Remove HTML tags and get text content
                let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").replace(/(\s\s)/gm, " ");
                row.push(text);
            }
            csv.push(row.join(","));
        }
        
        // Download CSV file
        const csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
        const downloadLink = document.createElement("a");
        downloadLink.download = "<?php echo $course['course_code']; ?>_report.csv";
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
    </script>
</body>
</html>