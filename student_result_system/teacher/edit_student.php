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
$message = '';
$message_type = '';

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
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_student'])) {
            $full_name = $_POST['full_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $program = $_POST['program'] ?? '';
            $semester = $_POST['semester'] ?? '';
            $roll_number = $_POST['roll_number'] ?? '';
            $contact = $_POST['contact'] ?? '';
            $address = $_POST['address'] ?? '';
            $date_of_birth = $_POST['date_of_birth'] ?? '';
            
            if (!empty($full_name) && !empty($email)) {
                $update_query = "UPDATE students SET 
                                full_name = ?, email = ?, program = ?, semester = ?, 
                                roll_number = ?, contact = ?, address = ?, date_of_birth = ?
                                WHERE student_id = ?";
                
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("sssssssss", 
                    $full_name, $email, $program, $semester,
                    $roll_number, $contact, $address, $date_of_birth, $student_id
                );
                
                if ($update_stmt->execute()) {
                    $message = "Student information updated successfully!";
                    $message_type = "success";
                    
                    // Refresh student data
                    $student_stmt = $conn->prepare($student_query);
                    $student_stmt->bind_param("s", $student_id);
                    $student_stmt->execute();
                    $student_result = $student_stmt->get_result();
                    $student = $student_result->fetch_assoc();
                    $student_stmt->close();
                } else {
                    $message = "Error updating student: " . $update_stmt->error;
                    $message_type = "error";
                }
                
                $update_stmt->close();
            } else {
                $message = "Please fill in all required fields!";
                $message_type = "error";
            }
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        $message = "Database error: " . $e->getMessage();
        $message_type = "error";
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
    <title>Edit Student - TU Result System</title>
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
            max-width: 800px;
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-group label.required::after {
            content: " *";
            color: #e74c3c;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
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
        
        .student-id-display {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            font-family: monospace;
            font-weight: bold;
            color: #2c3e50;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-edit"></i> Edit Student Information</h1>
            <a href="view_students.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> 
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label>Student ID</label>
                    <div class="student-id-display">
                        <?php echo htmlspecialchars($student['student_id']); ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required" for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="required" for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($student['email']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="required" for="program">Program</label>
                        <select id="program" name="program" class="form-control" required>
                            <option value="BBA" <?php echo $student['program'] == 'BBA' ? 'selected' : ''; ?>>BBA</option>
                            <option value="BCA" <?php echo $student['program'] == 'BCA' ? 'selected' : ''; ?>>BCA</option>
                            <option value="BSc CSIT" <?php echo $student['program'] == 'BSc CSIT' ? 'selected' : ''; ?>>BSc CSIT</option>
                            <option value="BIT" <?php echo $student['program'] == 'BIT' ? 'selected' : ''; ?>>BIT</option>
                            <option value="BSW" <?php echo $student['program'] == 'BSW' ? 'selected' : ''; ?>>BSW</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="required" for="semester">Semester</label>
                        <select id="semester" name="semester" class="form-control" required>
                            <option value="1st" <?php echo $student['semester'] == '1st' ? 'selected' : ''; ?>>1st Semester</option>
                            <option value="2nd" <?php echo $student['semester'] == '2nd' ? 'selected' : ''; ?>>2nd Semester</option>
                            <option value="3rd" <?php echo $student['semester'] == '3rd' ? 'selected' : ''; ?>>3rd Semester</option>
                            <option value="4th" <?php echo $student['semester'] == '4th' ? 'selected' : ''; ?>>4th Semester</option>
                            <option value="5th" <?php echo $student['semester'] == '5th' ? 'selected' : ''; ?>>5th Semester</option>
                            <option value="6th" <?php echo $student['semester'] == '6th' ? 'selected' : ''; ?>>6th Semester</option>
                            <option value="7th" <?php echo $student['semester'] == '7th' ? 'selected' : ''; ?>>7th Semester</option>
                            <option value="8th" <?php echo $student['semester'] == '8th' ? 'selected' : ''; ?>>8th Semester</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="roll_number">Roll Number</label>
                        <input type="text" id="roll_number" name="roll_number" class="form-control" 
                               value="<?php echo htmlspecialchars($student['roll_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact">Contact Number</label>
                        <input type="tel" id="contact" name="contact" class="form-control" 
                               value="<?php echo htmlspecialchars($student['contact'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                           value="<?php echo htmlspecialchars($student['date_of_birth'] ?? ''); ?>">
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_student" class="btn btn-success">
                        <i class="fas fa-save"></i> Update Student
                    </button>
                    <a href="view_students.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>