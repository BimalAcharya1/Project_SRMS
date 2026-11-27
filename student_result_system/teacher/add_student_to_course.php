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
$available_students = [];
$message = '';
$message_type = '';

if ($course_code) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get course details and verify teacher assignment
        $course_query = "SELECT * FROM courses WHERE course_code = ?";
        $course_stmt = $conn->prepare($course_query);
        $course_stmt->bind_param("s", $course_code);
        $course_stmt->execute();
        $course_result = $course_stmt->get_result();
        $course = $course_result->fetch_assoc();
        $course_stmt->close();
        
        if ($course) {
            // Check if teacher is assigned to this course
            if ($course['assigned_teacher_id'] != $teacher_id) {
                $message = "You are not assigned to this course!";
                $message_type = "error";
                $course = null;
            } else {
                // Get students not enrolled in this course but from the same program
                $available_students_query = "SELECT s.* FROM students s 
                                            WHERE s.program = ? 
                                            AND s.student_id NOT IN (
                                                SELECT student_id FROM student_courses WHERE course_code = ?
                                            )
                                            ORDER BY s.full_name";
                
                $available_stmt = $conn->prepare($available_students_query);
                $available_stmt->bind_param("ss", $course['program'], $course_code);
                $available_stmt->execute();
                $available_result = $available_stmt->get_result();
                
                while ($row = $available_result->fetch_assoc()) {
                    $available_students[] = $row;
                }
                $available_stmt->close();
                
                // Handle form submission
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_students'])) {
                    $selected_students = $_POST['students'] ?? [];
                    
                    if (!empty($selected_students)) {
                        $conn->begin_transaction();
                        
                        try {
                            $add_query = "INSERT INTO student_courses (student_id, course_code) VALUES (?, ?)";
                            $add_stmt = $conn->prepare($add_query);
                            
                            $added_count = 0;
                            foreach ($selected_students as $student_id) {
                                // Check if already enrolled
                                $check_query = "SELECT id FROM student_courses WHERE student_id = ? AND course_code = ?";
                                $check_stmt = $conn->prepare($check_query);
                                $check_stmt->bind_param("ss", $student_id, $course_code);
                                $check_stmt->execute();
                                $check_result = $check_stmt->get_result();
                                
                                if ($check_result->num_rows == 0) {
                                    $add_stmt->bind_param("ss", $student_id, $course_code);
                                    if ($add_stmt->execute()) {
                                        $added_count++;
                                    }
                                }
                                $check_stmt->close();
                            }
                            
                            $conn->commit();
                            
                            if ($added_count > 0) {
                                $message = "Successfully added $added_count student(s) to the course!";
                                $message_type = "success";
                                
                                // Refresh available students list
                                $available_students = [];
                                $available_stmt = $conn->prepare($available_students_query);
                                $available_stmt->bind_param("ss", $course['program'], $course_code);
                                $available_stmt->execute();
                                $available_result = $available_stmt->get_result();
                                
                                while ($row = $available_result->fetch_assoc()) {
                                    $available_students[] = $row;
                                }
                                $available_stmt->close();
                            } else {
                                $message = "No students were added. They may already be enrolled.";
                                $message_type = "warning";
                            }
                            
                            $add_stmt->close();
                            
                        } catch (Exception $e) {
                            $conn->rollback();
                            $message = "Error adding students: " . $e->getMessage();
                            $message_type = "error";
                        }
                    } else {
                        $message = "Please select at least one student!";
                        $message_type = "error";
                    }
                }
            }
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        $message = "Database error: " . $e->getMessage();
        $message_type = "error";
        // Sample data for demo
        $course = [
            'course_code' => $course_code,
            'course_name' => 'Sample Course',
            'credit_hours' => 3,
            'semester' => '1st',
            'program' => 'BSc CSIT',
            'course_type' => 'Theory'
        ];
        
        $available_students = [
            ['student_id' => 'S006', 'full_name' => 'NEW STUDENT 1', 'roll_number' => '006', 
             'email' => 'new1@example.com', 'program' => 'BSc CSIT', 'semester' => '1st',
             'contact' => '9841000006'],
            ['student_id' => 'S007', 'full_name' => 'NEW STUDENT 2', 'roll_number' => '007',
             'email' => 'new2@example.com', 'program' => 'BSc CSIT', 'semester' => '1st',
             'contact' => '9841000007'],
            ['student_id' => 'S008', 'full_name' => 'NEW STUDENT 3', 'roll_number' => '008',
             'email' => 'new3@example.com', 'program' => 'BSc CSIT', 'semester' => '1st',
             'contact' => '9841000008']
        ];
    }
}

if (!$course_code || !$course) {
    header('Location: my_courses.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Students to Course - <?php echo htmlspecialchars($course['course_code']); ?></title>
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
            max-width: 900px;
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
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .course-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
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
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .students-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .student-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #eee;
            transition: background 0.3s;
        }
        
        .student-item:last-child {
            border-bottom: none;
        }
        
        .student-item:hover {
            background: #f8f9fa;
        }
        
        .student-checkbox {
            margin-right: 15px;
            transform: scale(1.2);
        }
        
        .student-info {
            flex: 1;
        }
        
        .student-name {
            font-weight: 500;
            color: #2c3e50;
            font-size: 15px;
        }
        
        .student-details {
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 3px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .select-all {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .no-students {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .no-students i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
        
        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-plus"></i> Add Students to Course</h1>
            <a href="view_course_students.php?course=<?php echo $course_code; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php 
                    if ($message_type == 'success') echo 'check-circle';
                    elseif ($message_type == 'error') echo 'exclamation-circle';
                    else echo 'exclamation-triangle';
                ?>"></i> 
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
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
                        <span class="info-label">Program</span>
                        <span class="info-value"><?php echo htmlspecialchars($course['program']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Available Students</span>
                        <span class="info-value"><?php echo count($available_students); ?></span>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="">
                <h3 style="margin-bottom: 15px; color: #2c3e50;">Select Students to Add</h3>
                
                <?php if (!empty($available_students)): ?>
                    <div class="select-all">
                        <input type="checkbox" id="selectAll" onchange="toggleAllStudents()">
                        <label for="selectAll" style="font-weight: 600; color: #2c3e50;">Select All Available Students</label>
                    </div>
                    
                    <div class="students-list">
                        <?php foreach($available_students as $student): ?>
                        <div class="student-item">
                            <input type="checkbox" class="student-checkbox" name="students[]" 
                                   value="<?php echo $student['student_id']; ?>" id="student_<?php echo $student['student_id']; ?>">
                            <label for="student_<?php echo $student['student_id']; ?>" class="student-info" style="cursor: pointer;">
                                <div class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                <div class="student-details">
                                    ID: <?php echo htmlspecialchars($student['student_id']); ?> | 
                                    Roll: <?php echo htmlspecialchars($student['roll_number']); ?> | 
                                    Semester: <?php echo htmlspecialchars($student['semester']); ?>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_students" class="btn btn-success">
                            <i class="fas fa-user-plus"></i> Add Selected Students
                        </button>
                        <a href="view_course_students.php?course=<?php echo $course_code; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                <?php else: ?>
                    <div class="no-students">
                        <i class="fas fa-users"></i>
                        <h3>No Students Available</h3>
                        <p>All students from the <?php echo htmlspecialchars($course['program']); ?> program are already enrolled in this course.</p>
                        <a href="view_course_students.php?course=<?php echo $course_code; ?>" class="btn btn-primary" style="margin-top: 15px;">
                            <i class="fas fa-arrow-left"></i> Back to Course Students
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <script>
    function toggleAllStudents() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.student-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });
    }
    
    // Update select all when individual checkboxes change
    document.querySelectorAll('.student-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectAll = document.getElementById('selectAll');
            const allChecked = Array.from(document.querySelectorAll('.student-checkbox'))
                .every(cb => cb.checked);
            selectAll.checked = allChecked;
        });
    });
    </script>
</body>
</html>