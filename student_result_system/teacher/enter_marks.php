<?php
session_start();

// Database Configuration for Main Database
class MainDatabase {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "student_result_system"; // Main database
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
$course_code = $_GET['course'] ?? '';
$students = [];
$course = null;
$success_message = '';
$error_message = '';

try {
    // Connect to main database
    $mainDb = new MainDatabase();
    $conn = $mainDb->getConnection();
    
    // Get teacher's courses from main database
    $courses_query = "SELECT course_code, course_name FROM courses WHERE assigned_teacher_id = ? ORDER BY course_code";
    $courses_stmt = $conn->prepare($courses_query);
    $courses_stmt->bind_param("i", $teacher_id);
    $courses_stmt->execute();
    $courses_result = $courses_stmt->get_result();
    $teacher_courses = [];
    while ($row = $courses_result->fetch_assoc()) {
        $teacher_courses[] = $row;
    }
    $courses_stmt->close();
    
    // If course is selected, get students and their marks
    if ($course_code && !empty($teacher_courses)) {
        // Get course details from main database
        $course_query = "SELECT * FROM courses WHERE course_code = ? AND assigned_teacher_id = ?";
        $course_stmt = $conn->prepare($course_query);
        $course_stmt->bind_param("si", $course_code, $teacher_id);
        $course_stmt->execute();
        $course_result = $course_stmt->get_result();
        $course = $course_result->fetch_assoc();
        $course_stmt->close();
        
        if ($course) {
            // Get students enrolled in this course from main database
            $students_query = "SELECT s.* FROM students s
                              JOIN student_courses sc ON s.student_id = sc.student_id
                              WHERE sc.course_code = ? AND s.program = ?
                              ORDER BY s.roll_number";
            
            $students_stmt = $conn->prepare($students_query);
            $students_stmt->bind_param("ss", $course_code, $course['program']);
            $students_stmt->execute();
            $students_result = $students_stmt->get_result();
            
            $enrolled_students = [];
            while ($row = $students_result->fetch_assoc()) {
                $enrolled_students[] = $row;
            }
            $students_stmt->close();
            
            // Get marks for these students
            foreach ($enrolled_students as $student) {
                $student_id = $student['student_id'];
                
                // Get marks from main database
                $marks_query = "SELECT * FROM results WHERE student_id = ? AND course_code = ?";
                $marks_stmt = $conn->prepare($marks_query);
                $marks_stmt->bind_param("ss", $student_id, $course_code);
                $marks_stmt->execute();
                $marks_result = $marks_stmt->get_result();
                
                if ($marks_row = $marks_result->fetch_assoc()) {
                    // Student has existing marks
                    $student_data = array_merge($student, $marks_row);
                } else {
                    // No marks yet
                    $student_data = array_merge($student, [
                        'theory_marks' => 0,
                        'practical_marks' => 0,
                        'internal_marks' => 0,
                        'total_marks' => 0,
                        'grade' => null,
                        'gpa' => 0.0,
                        'status' => 'pending',
                        'result_id' => null
                    ]);
                }
                
                $marks_stmt->close();
                $students[] = $student_data;
            }
            
            // Handle form submission - Save marks to MAIN database
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_marks'])) {
                $conn->begin_transaction();
                
                try {
                    foreach ($_POST['marks'] as $student_id => $marks) {
                        $theory_marks = $marks['theory'] ?? 0;
                        $practical_marks = $marks['practical'] ?? 0;
                        $internal_marks = $marks['internal'] ?? 0;
                        $total_marks = $theory_marks + $practical_marks + $internal_marks;
                        
                        // Calculate grade and GPA
                        $grade = 'F';
                        $gpa = 0.0;
                        $status = 'failed';
                        
                        if ($total_marks >= 80) {
                            $grade = 'A';
                            $gpa = 4.0;
                            $status = 'passed';
                        } elseif ($total_marks >= 70) {
                            $grade = 'B';
                            $gpa = 3.0;
                            $status = 'passed';
                        } elseif ($total_marks >= 60) {
                            $grade = 'C';
                            $gpa = 2.0;
                            $status = 'passed';
                        } elseif ($total_marks >= 50) {
                            $grade = 'D';
                            $gpa = 1.0;
                            $status = 'passed';
                        }
                        
                        // Check if result already exists
                        $check_query = "SELECT result_id FROM results WHERE student_id = ? AND course_code = ?";
                        $check_stmt = $conn->prepare($check_query);
                        $check_stmt->bind_param("ss", $student_id, $course_code);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();
                        
                        if ($check_result->num_rows > 0) {
                            // Update existing result
                            $update_query = "UPDATE results SET 
                                            theory_marks = ?, practical_marks = ?, internal_marks = ?,
                                            total_marks = ?, grade = ?, gpa = ?, status = ?,
                                            last_updated = NOW(), entered_by = ?
                                            WHERE student_id = ? AND course_code = ?";
                            
                            $update_stmt = $conn->prepare($update_query);
                            $update_stmt->bind_param("iiiiisdiss", 
                                $theory_marks, $practical_marks, $internal_marks,
                                $total_marks, $grade, $gpa, $status,
                                $teacher_id, $student_id, $course_code
                            );
                            $update_stmt->execute();
                            $update_stmt->close();
                        } else {
                            // Insert new result
                            $insert_query = "INSERT INTO results 
                                            (student_id, course_code, theory_marks, practical_marks, 
                                            internal_marks, total_marks, grade, gpa, status, 
                                            entered_by, last_updated)
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                            
                            $insert_stmt = $conn->prepare($insert_query);
                            $insert_stmt->bind_param("ssiiiiisdi", 
                                $student_id, $course_code,
                                $theory_marks, $practical_marks, $internal_marks,
                                $total_marks, $grade, $gpa, $status,
                                $teacher_id
                            );
                            $insert_stmt->execute();
                            $insert_stmt->close();
                        }
                        
                        $check_stmt->close();
                    }
                    
                    $conn->commit();
                    $success_message = "Marks saved successfully in main database!";
                    
                    // Refresh students data to show updated marks
                    $students = [];
                    foreach ($enrolled_students as $student) {
                        $student_id = $student['student_id'];
                        
                        // Get updated marks
                        $updated_marks_query = "SELECT * FROM results WHERE student_id = ? AND course_code = ?";
                        $updated_marks_stmt = $conn->prepare($updated_marks_query);
                        $updated_marks_stmt->bind_param("ss", $student_id, $course_code);
                        $updated_marks_stmt->execute();
                        $updated_marks_result = $updated_marks_stmt->get_result();
                        
                        if ($updated_marks_row = $updated_marks_result->fetch_assoc()) {
                            $student_data = array_merge($student, $updated_marks_row);
                        } else {
                            $student_data = array_merge($student, [
                                'theory_marks' => 0,
                                'practical_marks' => 0,
                                'internal_marks' => 0,
                                'total_marks' => 0,
                                'grade' => null,
                                'gpa' => 0.0,
                                'status' => 'pending'
                            ]);
                        }
                        
                        $updated_marks_stmt->close();
                        $students[] = $student_data;
                    }
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $error_message = "Error saving marks: " . $e->getMessage();
                }
            }
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    // Fallback to sample data if database not accessible
    $teacher_courses = [
        ['course_code' => 'CSC101', 'course_name' => 'Introduction to Programming'],
        ['course_code' => 'CSC102', 'course_name' => 'Data Structures'],
        ['course_code' => 'CSC103', 'course_name' => 'Database Management']
    ];
    
    if ($course_code) {
        $course = ['course_code' => $course_code, 'course_name' => 'Sample Course', 'program' => 'BCA'];
        $students = [
            ['student_id' => 'S001', 'full_name' => 'RAM BAHADUR', 'roll_number' => '001', 
             'theory_marks' => 55, 'practical_marks' => 20, 'internal_marks' => 18, 'total_marks' => 93, 
             'grade' => 'B', 'gpa' => 3.0, 'status' => 'passed'],
            ['student_id' => 'S002', 'full_name' => 'SITA KUMARI', 'roll_number' => '002',
             'theory_marks' => 52, 'practical_marks' => 18, 'internal_marks' => 20, 'total_marks' => 90,
             'grade' => 'A', 'gpa' => 4.0, 'status' => 'passed'],
            ['student_id' => 'S003', 'full_name' => 'HARI PRASAD', 'roll_number' => '003',
             'theory_marks' => 45, 'practical_marks' => 20, 'internal_marks' => 15, 'total_marks' => 80,
             'grade' => 'C', 'gpa' => 2.0, 'status' => 'passed']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Marks - TU Result System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS styles remain the same */
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
        
        .course-selector {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .selector-form {
            display: grid;
            grid-template-columns: 1fr auto;
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
            font-size: 16px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .marks-form-container {
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
        
        .input-field {
            width: 70px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-align: center;
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
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
        }
        
        .database-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            background: #2c3e50;
            color: white;
        }
        
        @media (max-width: 768px) {
            .selector-form {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 8px;
            }
            
            .input-field {
                width: 60px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-edit"></i> Enter Student Marks</h1>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                <br><small>All changes are stored directly in the main student_result_system database.</small>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="course-selector">
            <form method="GET" action="" class="selector-form">
                <div class="form-group">
                    <label for="course"><i class="fas fa-book"></i> Select Course</label>
                    <select id="course" name="course" class="form-control" required onchange="this.form.submit()">
                        <option value="">-- Select a Course --</option>
                        <?php foreach($teacher_courses as $c): ?>
                            <option value="<?php echo $c['course_code']; ?>" 
                                <?php echo $course_code == $c['course_code'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Load Students
                    </button>
                </div>
            </form>
        </div>
        
        <?php if ($course): ?>
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
                    <span class="info-label">Total Students</span>
                    <span class="info-value"><?php echo count($students); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Database</span>
                    <span class="info-value">
                        <div class="database-badge">
                            <i class="fas fa-database"></i> student_result_system
                        </div>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Teacher ID</span>
                    <span class="info-value" style="color: #3498db;">
                        <i class="fas fa-chalkboard-teacher"></i> <?php echo $teacher_id; ?>
                    </span>
                </div>
            </div>
        </div>
        
        <form method="POST" action="">
            <div class="marks-form-container">
                <?php if (!empty($students)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Roll No</th>
                                <th>Student Name</th>
                                <th>Theory (60)</th>
                                <th>Practical (20)</th>
                                <th>Internal (20)</th>
                                <th>Total (100)</th>
                                <th>Grade</th>
                                <th>GPA</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $student): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($student['full_name']); ?></strong><br>
                                    <small style="color: #7f8c8d;">ID: <?php echo htmlspecialchars($student['student_id']); ?></small>
                                </td>
                                <td>
                                    <input type="number" name="marks[<?php echo $student['student_id']; ?>][theory]" 
                                           class="input-field" min="0" max="60" 
                                           value="<?php echo $student['theory_marks'] ?? 0; ?>"
                                           onchange="calculateTotal(this)">
                                </td>
                                <td>
                                    <input type="number" name="marks[<?php echo $student['student_id']; ?>][practical]" 
                                           class="input-field" min="0" max="20" 
                                           value="<?php echo $student['practical_marks'] ?? 0; ?>"
                                           onchange="calculateTotal(this)">
                                </td>
                                <td>
                                    <input type="number" name="marks[<?php echo $student['student_id']; ?>][internal]" 
                                           class="input-field" min="0" max="20" 
                                           value="<?php echo $student['internal_marks'] ?? 0; ?>"
                                           onchange="calculateTotal(this)">
                                </td>
                                <td id="total_<?php echo $student['student_id']; ?>">
                                    <strong><?php echo $student['total_marks'] ?? 0; ?></strong>
                                </td>
                                <td id="grade_<?php echo $student['student_id']; ?>">
                                    <?php if (isset($student['grade'])): ?>
                                        <span class="grade-badge grade-<?php echo $student['grade']; ?>">
                                            <?php echo $student['grade']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="grade-badge grade-F">F</span>
                                    <?php endif; ?>
                                </td>
                                <td id="gpa_<?php echo $student['student_id']; ?>">
                                    <strong><?php echo $student['gpa'] ?? '0.0'; ?></strong>
                                </td>
                                <td id="status_<?php echo $student['student_id']; ?>" class="status-<?php echo $student['status'] ?? 'failed'; ?>">
                                    <?php echo ucfirst($student['status'] ?? 'failed'); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="form-actions">
                        <button type="submit" name="save_marks" class="btn btn-success">
                            <i class="fas fa-save"></i> Save to Main Database
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-users"></i>
                        <h3>No Students Enrolled</h3>
                        <p>There are no students enrolled in this course yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </form>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-book"></i>
                <h3>Select a Course</h3>
                <p>Please select a course from the dropdown above to enter marks.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    function calculateTotal(input) {
        const row = input.closest('tr');
        const studentId = row.querySelector('input[name^="marks["]').name.match(/\[(.*?)\]/)[1];
        
        const theory = parseFloat(row.querySelector('input[name="marks[' + studentId + '][theory]"]').value) || 0;
        const practical = parseFloat(row.querySelector('input[name="marks[' + studentId + '][practical]"]').value) || 0;
        const internal = parseFloat(row.querySelector('input[name="marks[' + studentId + '][internal]"]').value) || 0;
        
        const total = theory + practical + internal;
        
        // Update total display
        document.getElementById('total_' + studentId).innerHTML = '<strong>' + total + '</strong>';
        
        // Calculate grade, GPA, and status
        let grade = 'F';
        let gpa = 0.0;
        let status = 'failed';
        
        if (total >= 80) {
            grade = 'A';
            gpa = 4.0;
            status = 'passed';
        } else if (total >= 70) {
            grade = 'B';
            gpa = 3.0;
            status = 'passed';
        } else if (total >= 60) {
            grade = 'C';
            gpa = 2.0;
            status = 'passed';
        } else if (total >= 50) {
            grade = 'D';
            gpa = 1.0;
            status = 'passed';
        }
        
        // Update grade display
        const gradeElement = document.getElementById('grade_' + studentId);
        gradeElement.innerHTML = '<span class="grade-badge grade-' + grade + '">' + grade + '</span>';
        
        // Update GPA display
        document.getElementById('gpa_' + studentId).innerHTML = '<strong>' + gpa.toFixed(1) + '</strong>';
        
        // Update status display
        const statusElement = document.getElementById('status_' + studentId);
        statusElement.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        statusElement.className = 'status-' + status;
    }
    
    // Calculate totals on page load for existing values
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.input-field');
        inputs.forEach(input => {
            if (input.value > 0) {
                calculateTotal(input);
            }
        });
    });
    </script>
</body>
</html>