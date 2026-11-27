<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Handle form submissions
$message = '';
$message_type = '';

// Check if exams table exists
$table_check = $conn->query("SHOW TABLES LIKE 'exams'");
if ($table_check->num_rows == 0) {
    // Create exams table if it doesn't exist
    $create_table_sql = "CREATE TABLE exams (
        id INT PRIMARY KEY AUTO_INCREMENT,
        exam_name VARCHAR(100) NOT NULL,
        exam_code VARCHAR(50) UNIQUE NOT NULL,
        subject_id INT,
        course_id INT,
        semester INT,
        exam_type ENUM('regular', 'back', 'improvement', 'practical', 'term_test') DEFAULT 'regular',
        max_marks DECIMAL(5,2) DEFAULT 100.00,
        passing_marks DECIMAL(5,2) DEFAULT 40.00,
        exam_date DATE,
        start_time TIME,
        end_time TIME,
        duration_minutes INT DEFAULT 180,
        status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table_sql)) {
        $message = "Exams table created successfully!";
        $message_type = "success";
    } else {
        $message = "Error creating exams table: {$conn->error}";
        $message_type = "error";
    }
} else {
    // Check each column individually and add if missing
    $columns_to_add = [
        'start_time' => "TIME AFTER exam_date",
        'end_time' => "TIME AFTER start_time",
        'duration_minutes' => "INT DEFAULT 180 AFTER end_time"
    ];
    
    foreach ($columns_to_add as $column_name => $column_definition) {
        $column_check = $conn->query("SHOW COLUMNS FROM exams LIKE '$column_name'");
        if ($column_check->num_rows == 0) {
            $alter_sql = "ALTER TABLE exams ADD COLUMN $column_name $column_definition";
            if ($conn->query($alter_sql)) {
                $message .= "Added column '$column_name' successfully! ";
                $message_type = "success";
            } else {
                $message .= "Error adding column '$column_name': {$conn->error} ";
                $message_type = "error";
            }
        }
    }
    
    // Also check for other important columns
    $important_columns = ['subject_id', 'course_id', 'semester', 'created_by'];
    foreach ($important_columns as $column_name) {
        $column_check = $conn->query("SHOW COLUMNS FROM exams LIKE '$column_name'");
        if ($column_check->num_rows == 0) {
            if ($column_name === 'subject_id' || $column_name === 'course_id' || $column_name === 'created_by') {
                $alter_sql = "ALTER TABLE exams ADD COLUMN $column_name INT";
            } elseif ($column_name === 'semester') {
                $alter_sql = "ALTER TABLE exams ADD COLUMN $column_name INT";
            }
            
            if (isset($alter_sql) && $conn->query($alter_sql)) {
                $message .= "Added column '$column_name' successfully! ";
                $message_type = "success";
            } elseif (isset($alter_sql)) {
                $message .= "Error adding column '$column_name': {$conn->error} ";
                $message_type = "error";
            }
        }
    }
}

// Handle Add/Edit Exam
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_exam'])) {
        $exam_name = $_POST['exam_name'];
        $exam_code = $_POST['exam_code'];
        $subject_id = !empty($_POST['subject_id']) ? $_POST['subject_id'] : null;
        $course_id = !empty($_POST['course_id']) ? $_POST['course_id'] : null;
        $semester = !empty($_POST['semester']) ? $_POST['semester'] : null;
        $exam_type = $_POST['exam_type'];
        $max_marks = $_POST['max_marks'];
        $passing_marks = $_POST['passing_marks'];
        $exam_date = $_POST['exam_date'];
        $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : null;
        $end_time = isset($_POST['end_time']) ? $_POST['end_time'] : null;
        $duration_minutes = isset($_POST['duration_minutes']) ? $_POST['duration_minutes'] : 180;
        $status = $_POST['status'];
        $created_by = $_SESSION['admin_id'];

        // Check which columns exist
        $check_columns = $conn->query("SHOW COLUMNS FROM exams");
        $columns = [];
        while ($col = $check_columns->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
        
        // Build dynamic SQL based on available columns
        $sql_columns = [];
        $sql_values = [];
        $param_types = "";
        $param_values = [];
        
        // Always include these columns
        $sql_columns[] = "exam_name";
        $sql_columns[] = "exam_code";
        $sql_columns[] = "exam_type";
        $sql_columns[] = "max_marks";
        $sql_columns[] = "passing_marks";
        $sql_columns[] = "exam_date";
        $sql_columns[] = "status";
        $sql_columns[] = "created_by";
        
        $param_types .= "sssddssi";
        $param_values[] = &$exam_name;
        $param_values[] = &$exam_code;
        $param_values[] = &$exam_type;
        $param_values[] = &$max_marks;
        $param_values[] = &$passing_marks;
        $param_values[] = &$exam_date;
        $param_values[] = &$status;
        $param_values[] = &$created_by;
        
        // Add optional columns if they exist
        if (in_array('subject_id', $columns) && $subject_id !== null) {
            $sql_columns[] = "subject_id";
            $param_types .= "i";
            $param_values[] = &$subject_id;
        }
        
        if (in_array('course_id', $columns) && $course_id !== null) {
            $sql_columns[] = "course_id";
            $param_types .= "i";
            $param_values[] = &$course_id;
        }
        
        if (in_array('semester', $columns) && $semester !== null) {
            $sql_columns[] = "semester";
            $param_types .= "i";
            $param_values[] = &$semester;
        }
        
        if (in_array('start_time', $columns) && $start_time !== null) {
            $sql_columns[] = "start_time";
            $param_types .= "s";
            $param_values[] = &$start_time;
        }
        
        if (in_array('end_time', $columns) && $end_time !== null) {
            $sql_columns[] = "end_time";
            $param_types .= "s";
            $param_values[] = &$end_time;
        }
        
        if (in_array('duration_minutes', $columns) && $duration_minutes !== null) {
            $sql_columns[] = "duration_minutes";
            $param_types .= "i";
            $param_values[] = &$duration_minutes;
        }
        
        // Build SQL query
        $placeholders = str_repeat('?, ', count($sql_columns) - 1) . '?';
        $sql = "INSERT INTO exams (" . implode(', ', $sql_columns) . ") VALUES ($placeholders)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($param_types, ...$param_values);
            
            if ($stmt->execute()) {
                $message = "Exam added successfully!";
                $message_type = "success";
            } else {
                $message = "Error adding exam: {$stmt->error}";
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "Error preparing statement: {$conn->error}";
            $message_type = "error";
        }
    }

    // Handle Edit Exam
    if (isset($_POST['edit_exam'])) {
        $id = $_POST['exam_id'];
        $exam_name = $_POST['exam_name'];
        $exam_code = $_POST['exam_code'];
        $subject_id = !empty($_POST['subject_id']) ? $_POST['subject_id'] : null;
        $course_id = !empty($_POST['course_id']) ? $_POST['course_id'] : null;
        $semester = !empty($_POST['semester']) ? $_POST['semester'] : null;
        $exam_type = $_POST['exam_type'];
        $max_marks = $_POST['max_marks'];
        $passing_marks = $_POST['passing_marks'];
        $exam_date = $_POST['exam_date'];
        $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : null;
        $end_time = isset($_POST['end_time']) ? $_POST['end_time'] : null;
        $duration_minutes = isset($_POST['duration_minutes']) ? $_POST['duration_minutes'] : 180;
        $status = $_POST['status'];

        // Check which columns exist
        $check_columns = $conn->query("SHOW COLUMNS FROM exams");
        $columns = [];
        while ($col = $check_columns->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
        
        // Build dynamic UPDATE SQL
        $sql_sets = [];
        $param_types = "";
        $param_values = [];
        
        // Always include these fields
        $sql_sets[] = "exam_name = ?";
        $sql_sets[] = "exam_code = ?";
        $sql_sets[] = "exam_type = ?";
        $sql_sets[] = "max_marks = ?";
        $sql_sets[] = "passing_marks = ?";
        $sql_sets[] = "exam_date = ?";
        $sql_sets[] = "status = ?";
        
        $param_types .= "sssddss";
        $param_values[] = &$exam_name;
        $param_values[] = &$exam_code;
        $param_values[] = &$exam_type;
        $param_values[] = &$max_marks;
        $param_values[] = &$passing_marks;
        $param_values[] = &$exam_date;
        $param_values[] = &$status;
        
        // Add optional columns if they exist
        if (in_array('subject_id', $columns)) {
            $sql_sets[] = "subject_id = ?";
            $param_types .= "i";
            $param_values[] = &$subject_id;
        }
        
        if (in_array('course_id', $columns)) {
            $sql_sets[] = "course_id = ?";
            $param_types .= "i";
            $param_values[] = &$course_id;
        }
        
        if (in_array('semester', $columns)) {
            $sql_sets[] = "semester = ?";
            $param_types .= "i";
            $param_values[] = &$semester;
        }
        
        if (in_array('start_time', $columns)) {
            $sql_sets[] = "start_time = ?";
            $param_types .= "s";
            $param_values[] = &$start_time;
        }
        
        if (in_array('end_time', $columns)) {
            $sql_sets[] = "end_time = ?";
            $param_types .= "s";
            $param_values[] = &$end_time;
        }
        
        if (in_array('duration_minutes', $columns)) {
            $sql_sets[] = "duration_minutes = ?";
            $param_types .= "i";
            $param_values[] = &$duration_minutes;
        }
        
        // Add ID for WHERE clause
        $param_types .= "i";
        $param_values[] = &$id;
        
        // Build SQL query
        $sql = "UPDATE exams SET " . implode(', ', $sql_sets) . " WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($param_types, ...$param_values);
            
            if ($stmt->execute()) {
                $message = "Exam updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating exam: {$stmt->error}";
                $message_type = "error";
            }
            $stmt->close();
        } else {
            $message = "Error preparing statement: {$conn->error}";
            $message_type = "error";
        }
    }

    // Handle Delete Exam
    if (isset($_POST['delete_exam'])) {
        $id = $_POST['exam_id'];
        
        $sql = "DELETE FROM exams WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Exam deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting exam: {$conn->error}";
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Get all exams with related data
$exams_query = "SELECT e.*, 
                COALESCE(s.subject_name, 'N/A') as subject_name, 
                COALESCE(c.course_name, 'N/A') as course_name 
                FROM exams e 
                LEFT JOIN subjects s ON e.subject_id = s.id 
                LEFT JOIN courses c ON e.course_id = c.id 
                ORDER BY e.exam_date DESC, e.created_at DESC";
$exams_result = $conn->query($exams_query);

// Get subjects for dropdown
$subjects = $conn->query("SELECT id, subject_name, subject_code FROM subjects ORDER BY subject_name");
// Get courses for dropdown
$courses = $conn->query("SELECT id, course_name, course_code FROM courses ORDER BY course_name");

// Check which columns exist in exams table
$columns_exist = [
    'start_time' => false,
    'end_time' => false,
    'duration_minutes' => false,
    'subject_id' => false,
    'course_id' => false,
    'semester' => false
];

$check_columns = $conn->query("SHOW COLUMNS FROM exams");
while ($col = $check_columns->fetch_assoc()) {
    if (isset($columns_exist[$col['Field']])) {
        $columns_exist[$col['Field']] = true;
    }
}

// Check if editing
$edit_exam = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_exam = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams - TU Result System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; display: none; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; display: block; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; display: block; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .tab { padding: 10px 20px; background: #2cba14; border-radius: 5px; cursor: pointer; transition: all 0.3s; }
        .tab.active { background: #3498db; color: white; }
        .tab-content { display: none; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .tab-content.active { display: block; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .btn-primary { background: linear-gradient(135deg, #179ef7, #3498db); color: white; }
        .btn-secondary { background: #3498db; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .btn-back { background: #3012f0; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .table-container { overflow-x: auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; color: #333; }
        tr:hover { background: #f9f9f9; }
        .badge { padding: 4px 8px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-upcoming { background: #3498db; color: white; }
        .badge-ongoing { background: #f39c12; color: white; }
        .badge-completed { background: #2ecc71; color: white; }
        .badge-cancelled { background: #e74c3c; color: white; }
        .action-buttons { display: flex; gap: 5px; }
        .action-btn { padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 3px; }
        .search-box { margin-bottom: 20px; }
        .search-box input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        .optional-fields { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 15px 0;
            border-left: 4px solid #179ef7;
        }
        .optional-fields h4 {
            margin-top: 0;
            color: #179ef7;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .field-note {
            font-size: 12px;
            color: #666;
            font-style: italic;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-clipboard-list"></i> Manage Exams</h1>
                <p>Create, edit, and manage examination schedules</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" onclick="showTab('exams-list')">All Exams</div>
            <div class="tab" onclick="showTab('add-exam')"><?php echo $edit_exam ? 'Edit Exam' : 'Add New Exam'; ?></div>
        </div>

        <!-- Exams List Tab -->
        <div id="exams-list" class="tab-content active">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search exams by name, code, or subject..." onkeyup="searchExams()">
            </div>
            
            <div class="table-container">
                <table id="examsTable">
                    <thead>
                        <tr>
                            <th>Exam Code</th>
                            <th>Exam Name</th>
                            <th>Subject</th>
                            <th>Course</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Marks</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($exams_result && $exams_result->num_rows > 0): ?>
                            <?php while($exam = $exams_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($exam['exam_code']); ?></td>
                                <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                                <td><?php echo htmlspecialchars($exam['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($exam['course_name']); ?></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($exam['exam_date'])); ?>
                                    <?php if (!empty($exam['start_time']) && !empty($exam['end_time'])): ?>
                                        <br><small><?php echo $exam['start_time'] . ' - ' . $exam['end_time']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="text-transform: capitalize;">
                                        <?php echo htmlspecialchars($exam['exam_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    Max: <?php echo $exam['max_marks']; ?><br>
                                    Pass: <?php echo $exam['passing_marks']; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $exam['status']; ?>">
                                        <?php echo ucfirst($exam['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit=<?php echo $exam['id']; ?>" class="action-btn btn-secondary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this exam?');">
                                            <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                            <button type="submit" name="delete_exam" class="action-btn btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-clipboard-list" style="font-size: 48px; color: #ddd; margin-bottom: 10px;"></i><br>
                                    No exams found. <a href="javascript:void(0)" onclick="showTab('add-exam')">Add your first exam</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit Exam Tab -->
        <div id="add-exam" class="tab-content">
            <form method="POST" action="">
                <?php if ($edit_exam): ?>
                    <input type="hidden" name="exam_id" value="<?php echo $edit_exam['id']; ?>">
                <?php endif; ?>
                
                <h3 style="margin-top: 0; color: #9b59b6;">Basic Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="exam_name">Exam Name *</label>
                        <input type="text" id="exam_name" name="exam_name" class="form-control" 
                               value="<?php echo $edit_exam ? htmlspecialchars($edit_exam['exam_name']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="exam_code">Exam Code *</label>
                        <input type="text" id="exam_code" name="exam_code" class="form-control" 
                               value="<?php echo $edit_exam ? htmlspecialchars($edit_exam['exam_code']) : ''; ?>" 
                               required placeholder="CSIT101-MID-2024">
                        <div class="field-note">Unique code for the exam (e.g., CSIT101-MID-2024)</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="exam_type">Exam Type *</label>
                        <select id="exam_type" name="exam_type" class="form-control" required>
                            <option value="regular" <?php echo ($edit_exam && $edit_exam['exam_type'] == 'regular') ? 'selected' : ''; ?>>Regular</option>
                            <option value="back" <?php echo ($edit_exam && $edit_exam['exam_type'] == 'back') ? 'selected' : ''; ?>>Back Paper</option>
                            <option value="improvement" <?php echo ($edit_exam && $edit_exam['exam_type'] == 'improvement') ? 'selected' : ''; ?>>Improvement</option>
                            <option value="practical" <?php echo ($edit_exam && $edit_exam['exam_type'] == 'practical') ? 'selected' : ''; ?>>Practical</option>
                            <option value="term_test" <?php echo ($edit_exam && $edit_exam['exam_type'] == 'term_test') ? 'selected' : ''; ?>>Term Test</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="exam_date">Exam Date *</label>
                        <input type="date" id="exam_date" name="exam_date" class="form-control" 
                               value="<?php echo $edit_exam ? $edit_exam['exam_date'] : date('Y-m-d'); ?>" 
                               required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="max_marks">Maximum Marks *</label>
                        <input type="number" id="max_marks" name="max_marks" class="form-control" 
                               value="<?php echo $edit_exam ? $edit_exam['max_marks'] : '100'; ?>" 
                               step="0.01" min="1" max="200" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="passing_marks">Passing Marks *</label>
                        <input type="number" id="passing_marks" name="passing_marks" class="form-control" 
                               value="<?php echo $edit_exam ? $edit_exam['passing_marks'] : '40'; ?>" 
                               step="0.01" min="1" max="200" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="upcoming" <?php echo ($edit_exam && $edit_exam['status'] == 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="ongoing" <?php echo ($edit_exam && $edit_exam['status'] == 'ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="completed" <?php echo ($edit_exam && $edit_exam['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo ($edit_exam && $edit_exam['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>

                <?php if ($columns_exist['subject_id'] || $columns_exist['course_id'] || $columns_exist['semester']): ?>
                <div class="optional-fields">
                    <h4><i class="fas fa-graduation-cap"></i> Academic Information</h4>
                    
                    <?php if ($columns_exist['subject_id']): ?>
                    <div class="form-group">
                        <label for="subject_id">Subject</label>
                        <select id="subject_id" name="subject_id" class="form-control">
                            <option value="">Select Subject</option>
                            <?php 
                            if ($subjects) {
                                $subjects->data_seek(0);
                                while($subject = $subjects->fetch_assoc()): ?>
                            <option value="<?php echo $subject['id']; ?>"
                                <?php echo ($edit_exam && isset($edit_exam['subject_id']) && $edit_exam['subject_id'] == $subject['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['subject_name'] . ' (' . $subject['subject_code'] . ')'); ?>
                            </option>
                            <?php endwhile; } ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($columns_exist['course_id']): ?>
                    <div class="form-group">
                        <label for="course_id">Course</label>
                        <select id="course_id" name="course_id" class="form-control">
                            <option value="">Select Course</option>
                            <?php 
                            if ($courses) {
                                $courses->data_seek(0);
                                while($course = $courses->fetch_assoc()): ?>
                            <option value="<?php echo $course['id']; ?>"
                                <?php echo ($edit_exam && isset($edit_exam['course_id']) && $edit_exam['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?>
                            </option>
                            <?php endwhile; } ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($columns_exist['semester']): ?>
                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <select id="semester" name="semester" class="form-control">
                            <option value="">Select Semester</option>
                            <?php for($i = 1; $i <= 8; $i++): ?>
                            <option value="<?php echo $i; ?>"
                                <?php echo ($edit_exam && isset($edit_exam['semester']) && $edit_exam['semester'] == $i) ? 'selected' : ''; ?>>
                                Semester <?php echo $i; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($columns_exist['start_time'] || $columns_exist['end_time'] || $columns_exist['duration_minutes']): ?>
                <div class="optional-fields">
                    <h4><i class="fas fa-clock"></i> Time Schedule</h4>
                    <div class="form-row">
                        <?php if ($columns_exist['start_time']): ?>
                        <div class="form-group">
                            <label for="start_time">Start Time</label>
                            <input type="time" id="start_time" name="start_time" class="form-control" 
                                   value="<?php echo $edit_exam ? ($edit_exam['start_time'] ?? '09:00') : '09:00'; ?>">
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($columns_exist['end_time']): ?>
                        <div class="form-group">
                            <label for="end_time">End Time</label>
                            <input type="time" id="end_time" name="end_time" class="form-control" 
                                   value="<?php echo $edit_exam ? ($edit_exam['end_time'] ?? '12:00') : '12:00'; ?>">
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($columns_exist['duration_minutes']): ?>
                        <div class="form-group">
                            <label for="duration_minutes">Duration (minutes)</label>
                            <input type="number" id="duration_minutes" name="duration_minutes" class="form-control" 
                                   value="<?php echo $edit_exam ? ($edit_exam['duration_minutes'] ?? '180') : '180'; ?>" 
                                   min="30" max="480">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-group" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                    <?php if ($edit_exam): ?>
                        <button type="submit" name="edit_exam" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Exam
                        </button>
                        <a href="manage_exams.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php else: ?>
                        <button type="submit" name="add_exam" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Exam
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset Form
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function showTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Update active tab
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            if (tabId === 'add-exam') {
                document.querySelectorAll('.tab')[1].classList.add('active');
            } else {
                document.querySelectorAll('.tab')[0].classList.add('active');
            }
            
            // Scroll to top
            window.scrollTo(0, 0);
        }

        // If editing, show the add/edit tab
        <?php if ($edit_exam): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showTab('add-exam');
        });
        <?php endif; ?>

        // Search functionality
        function searchExams() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('examsTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < td.length - 1; j++) { // Exclude actions column
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = found ? '' : 'none';
            }
        }

        // Auto-calculate duration based on start and end times
        document.addEventListener('DOMContentLoaded', function() {
            const startTime = document.getElementById('start_time');
            const endTime = document.getElementById('end_time');
            const duration = document.getElementById('duration_minutes');
            
            if (startTime && endTime && duration) {
                function calculateDuration() {
                    if (startTime.value && endTime.value) {
                        const start = new Date('2000-01-01T' + startTime.value + ':00');
                        const end = new Date('2000-01-01T' + endTime.value + ':00');
                        
                        if (end < start) {
                            end.setDate(end.getDate() + 1);
                        }
                        
                        const diffMs = end - start;
                        const diffMins = Math.round(diffMs / 60000);
                        
                        duration.value = diffMins;
                    }
                }
                
                startTime.addEventListener('change', calculateDuration);
                endTime.addEventListener('change', calculateDuration);
                
                // Calculate on page load if values exist
                if (startTime.value && endTime.value) {
                    calculateDuration();
                }
            }
        });
    </script>
</body>
</html>