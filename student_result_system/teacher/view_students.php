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

// Initialize variables
$students = [];
$search = '';
$program_filter = '';
$semester_filter = '';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get search and filter parameters
    $search = $_GET['search'] ?? '';
    $program_filter = $_GET['program'] ?? '';
    $semester_filter = $_GET['semester'] ?? '';
    
    // Build query
    $query = "SELECT * FROM students WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($search)) {
        $query .= " AND (full_name LIKE ? OR student_id LIKE ? OR email LIKE ?)";
        $search_term = "%{$search}%";
        $params = array_merge($params, [$search_term, $search_term, $search_term]);
        $types .= "sss";
    }
    
    if (!empty($program_filter)) {
        $query .= " AND program = ?";
        $params[] = $program_filter;
        $types .= "s";
    }
    
    if (!empty($semester_filter)) {
        $query .= " AND semester = ?";
        $params[] = $semester_filter;
        $types .= "s";
    }
    
    $query .= " ORDER BY full_name ASC";
    
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        
        $stmt->close();
    }
    
    // Get unique programs and semesters for filters
    $programs_result = $conn->query("SELECT DISTINCT program FROM students ORDER BY program");
    $programs = [];
    if ($programs_result) {
        while ($row = $programs_result->fetch_assoc()) {
            $programs[] = $row['program'];
        }
    }
    
    $semesters_result = $conn->query("SELECT DISTINCT semester FROM students ORDER BY semester");
    $semesters = [];
    if ($semesters_result) {
        while ($row = $semesters_result->fetch_assoc()) {
            $semesters[] = $row['semester'];
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    // Create connection for fallback
    $conn = new mysqli("localhost", "root", "", "student_result_system");
    
    // Check if students table exists, if not create it
    $table_check = $conn->query("SHOW TABLES LIKE 'students'");
    if ($table_check->num_rows == 0) {
        // Create students table
        $create_table = "CREATE TABLE students (
            student_id VARCHAR(50) PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE,
            program VARCHAR(50),
            semester VARCHAR(20),
            roll_number VARCHAR(20),
            contact VARCHAR(20),
            address TEXT,
            date_of_birth DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($create_table)) {
            // Insert sample data
            $sample_data = [
                "INSERT INTO students (student_id, full_name, email, program, semester, roll_number, contact) VALUES 
                ('ABC-123-457', 'AASHIS ACHARYA', 'pradipacharya9090@gmail.com', 'BBA', '1st', '001', '9841000001')",
                "INSERT INTO students (student_id, full_name, email, program, semester, roll_number, contact) VALUES 
                ('ABC-123-456', 'Sharma Bimal', 'test@gmail.com', 'BCA', '1st', '002', '9841000002')",
                "INSERT INTO students (student_id, full_name, email, program, semester, roll_number, contact) VALUES 
                ('S003', 'RAM BAHADUR', 'ram@example.com', 'BSc CSIT', '1st', '003', '9841000003')",
                "INSERT INTO students (student_id, full_name, email, program, semester, roll_number, contact) VALUES 
                ('S004', 'SITA KUMARI', 'sita@example.com', 'BSc CSIT', '1st', '004', '9841000004')",
                "INSERT INTO students (student_id, full_name, email, program, semester, roll_number, contact) VALUES 
                ('S005', 'HARI PRASAD', 'hari@example.com', 'BCA', '2nd', '005', '9841000005')",
                "INSERT INTO students (student_id, full_name, email, program, semester, roll_number, contact) VALUES 
                ('S006', 'GITA DEVI', 'gita@example.com', 'BBA', '2nd', '006', '9841000006')",
                "INSERT INTO students (student_id, full_name, email, program, semester, roll_number, contact) VALUES 
                ('S007', 'SHYAM KUMAR', 'shyam@example.com', 'BSc CSIT', '3rd', '007', '9841000007')"
            ];
            
            foreach ($sample_data as $sql) {
                $conn->query($sql);
            }
        }
    }
    
    // Now fetch the data
    $result = $conn->query("SELECT * FROM students ORDER BY full_name");
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    // Get unique programs and semesters
    $programs_result = $conn->query("SELECT DISTINCT program FROM students ORDER BY program");
    $programs = [];
    while ($row = $programs_result->fetch_assoc()) {
        $programs[] = $row['program'];
    }
    
    $semesters_result = $conn->query("SELECT DISTINCT semester FROM students ORDER BY semester");
    $semesters = [];
    while ($row = $semesters_result->fetch_assoc()) {
        $semesters[] = $row['semester'];
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students - TU Result System</title>
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
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-control {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
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
            padding: 3px 8px;
            border-radius: 3px;
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
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
        
        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .stats-bar {
                flex-direction: column;
                gap: 15px;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> View All Students</h1>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-number"><?php echo count($students); ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">
                    <?php 
                    $unique_programs = array_unique(array_column($students, 'program'));
                    echo count($unique_programs); 
                    ?>
                </div>
                <div class="stat-label">Programs</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">
                    <?php 
                    $unique_semesters = array_unique(array_column($students, 'semester'));
                    echo count($unique_semesters); 
                    ?>
                </div>
                <div class="stat-label">Semesters</div>
            </div>
        </div>
        
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <div class="form-group">
                    <label for="search"><i class="fas fa-search"></i> Search Student</label>
                    <input type="text" id="search" name="search" class="form-control" 
                           placeholder="Search by name, ID, or email..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="form-group">
                    <label for="program"><i class="fas fa-graduation-cap"></i> Filter by Program</label>
                    <select id="program" name="program" class="form-control">
                        <option value="">All Programs</option>
                        <?php foreach($programs as $program): ?>
                            <option value="<?php echo htmlspecialchars($program); ?>" 
                                <?php echo $program_filter == $program ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($program); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="semester"><i class="fas fa-calendar-alt"></i> Filter by Semester</label>
                    <select id="semester" name="semester" class="form-control">
                        <option value="">All Semesters</option>
                        <?php foreach($semesters as $semester): ?>
                            <option value="<?php echo htmlspecialchars($semester); ?>" 
                                <?php echo $semester_filter == $semester ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($semester); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="view_students.php" class="btn btn-secondary" style="margin-top: 5px;">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
        
        <div class="table-container">
            <?php if (!empty($students)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Program</th>
                            <th>Semester</th>
                            <th>Roll No</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $student): 
                            $program_color = '';
                            switch($student['program']) {
                                case 'BBA': $program_color = 'badge-primary'; break;
                                case 'BCA': $program_color = 'badge-success'; break;
                                case 'BSc CSIT': $program_color = 'badge-warning'; break;
                                default: $program_color = 'badge-primary';
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($student['student_id']); ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td>
                                <span class="badge <?php echo $program_color; ?>">
                                    <?php echo htmlspecialchars($student['program']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-warning"><?php echo htmlspecialchars($student['semester']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($student['roll_number'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($student['contact'] ?? 'N/A'); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_student_marks.php?student_id=<?php echo $student['student_id']; ?>" 
                                       class="action-btn action-btn-success" title="View Marks">
                                        <i class="fas fa-chart-bar"></i> Marks
                                    </a>
                                    <a href="edit_student.php?student_id=<?php echo $student['student_id']; ?>" 
                                       class="action-btn action-btn-primary" title="Edit">
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
                    <h3>No Students Found</h3>
                    <p>No students match your search criteria. Try different filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>