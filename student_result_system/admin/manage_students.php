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

// Handle CRUD operations
$action = $_GET['action'] ?? '';
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_student':
            $student_id = $_POST['student_id'];
            $full_name = $_POST['full_name'];
            $dob = $_POST['dob'];
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $semester = $_POST['semester'];
            $batch = $_POST['batch'];
            $faculty = $_POST['faculty']; // Changed from faculty to department
            $program = $_POST['program'];
            $roll_no = $_POST['roll_no'];
            
            // Debug: Check received data
            error_log("Add Student Data Received:");
            error_log("Student ID: $student_id");
            error_log("Name: $full_name");
            error_log("faculty: $faculty");
            error_log("Program: $program");
            
            // Check if student ID already exists
            $checkStmt = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
            $checkStmt->bind_param("s", $student_id);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows > 0) {
                $message = "Student ID already exists!";
                $message_type = "error";
            } else {
                // Check if roll number already exists in same batch and program
                $checkRollStmt = $conn->prepare("SELECT id FROM students WHERE roll_no = ? AND batch = ? AND program = ?");
                $checkRollStmt->bind_param("iss", $roll_no, $batch, $program);
                $checkRollStmt->execute();
                
                if ($checkRollStmt->get_result()->num_rows > 0) {
                    $message = "Roll number already exists in this batch and program!";
                    $message_type = "error";
                    $checkRollStmt->close();
                } else {
                    $checkRollStmt->close();
                    
                    // INSERT query - Fixed: Now using 10 parameters for 10 columns
                    $stmt = $conn->prepare("INSERT INTO students (student_id, full_name, dob, email, phone, semester, batch, faculty, program, roll_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    if ($stmt === false) {
                        $message = "Prepare failed: " . $conn->error;
                        $message_type = "error";
                    } else {
                        // Bind parameters - Fixed: 10 parameters for 10 placeholders
                        $stmt->bind_param("sssssisssi", 
                            $student_id, 
                            $full_name, 
                            $dob, 
                            $email, 
                            $phone, 
                            $semester, 
                            $batch, 
                            $faculty, // Changed from $faculty to $department
                            $program, 
                            $roll_no
                        );
                        
                        if ($stmt->execute()) {
                            $message = "Student added successfully!";
                            $message_type = "success";
                            
                            // Clear POST data after successful insertion
                            $_POST = array();
                        } else {
                            $message = "Error adding student: " . $stmt->error;
                            $message_type = "error";
                        }
                        $stmt->close();
                    }
                }
            }
            $checkStmt->close();
            break;
            
        case 'update_student':
            $id = $_POST['id'];
            $student_id = $_POST['student_id'];
            $full_name = $_POST['full_name'];
            $dob = $_POST['dob'];
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $semester = $_POST['semester'];
            $batch = $_POST['batch'];
            $faculty = $_POST['faculty']; // Changed from faculty to department
            $program = $_POST['program'];
            $roll_no = $_POST['roll_no'];
            
            // Check if student ID already exists (excluding current student)
            $checkStmt = $conn->prepare("SELECT id FROM students WHERE student_id = ? AND id != ?");
            $checkStmt->bind_param("si", $student_id, $id);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows > 0) {
                $message = "Student ID already exists for another student!";
                $message_type = "error";
                $checkStmt->close();
            } else {
                $checkStmt->close();
                
                // Check if roll number already exists in same batch and program (excluding current student)
                $checkRollStmt = $conn->prepare("SELECT id FROM students WHERE roll_no = ? AND batch = ? AND program = ? AND id != ?");
                $checkRollStmt->bind_param("issi", $roll_no, $batch, $program, $id);
                $checkRollStmt->execute();
                
                if ($checkRollStmt->get_result()->num_rows > 0) {
                    $message = "Roll number already exists in this batch and program!";
                    $message_type = "error";
                    $checkRollStmt->close();
                } else {
                    $checkRollStmt->close();
                    
                    $stmt = $conn->prepare("UPDATE students SET student_id = ?, full_name = ?, dob = ?, email = ?, phone = ?, semester = ?, batch = ?, department = ?, program = ?, roll_no = ? WHERE id = ?");
                    
                    if ($stmt === false) {
                        $message = "Prepare failed: " . $conn->error;
                        $message_type = "error";
                    } else {
                        $stmt->bind_param("sssssisssii", 
                            $student_id, 
                            $full_name, 
                            $dob, 
                            $email, 
                            $phone, 
                            $semester, 
                            $batch, 
                            $faculty, 
                            $program, 
                            $roll_no, 
                            $id
                        );
                        
                        if ($stmt->execute()) {
                            $message = "Student updated successfully!";
                            $message_type = "success";
                        } else {
                            $message = "Error updating student: " . $stmt->error;
                            $message_type = "error";
                        }
                        $stmt->close();
                    }
                }
            }
            break;
            
        case 'delete_student':
            $id = $_POST['id'];
            
            // First, check if student has any results
            $checkResultsStmt = $conn->prepare("SELECT id FROM results WHERE student_id = (SELECT student_id FROM students WHERE id = ?)");
            $checkResultsStmt->bind_param("i", $id);
            $checkResultsStmt->execute();
            
            if ($checkResultsStmt->get_result()->num_rows > 0) {
                $message = "Cannot delete student! Student has existing results. Delete results first.";
                $message_type = "error";
                $checkResultsStmt->close();
            } else {
                $checkResultsStmt->close();
                
                $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
                
                if ($stmt === false) {
                    $message = "Prepare failed: " . $conn->error;
                    $message_type = "error";
                } else {
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        $message = "Student deleted successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error deleting student: " . $stmt->error;
                        $message_type = "error";
                    }
                    $stmt->close();
                }
            }
            break;
    }
}
// ... rest of your code remains the same ...

// Get student for editing
$edit_student = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_student = $stmt->get_result()->fetch_assoc();
}

// Get all students with pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get search parameters
$search = $_GET['search'] ?? '';
$semester_filter = $_GET['semester'] ?? '';
$batch_filter = $_GET['batch'] ?? '';

// Build query
$where_conditions = [];
$params = [];
$param_types = "";

if ($search) {
    $where_conditions[] = "(student_id LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= "sss";
}

if ($semester_filter) {
    $where_conditions[] = "semester = ?";
    $params[] = $semester_filter;
    $param_types .= "i";
}

if ($batch_filter) {
    $where_conditions[] = "batch = ?";
    $params[] = $batch_filter;
    $param_types .= "s";
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_query = "SELECT COUNT(*) as total FROM students $where_clause";
$count_stmt = $conn->prepare($count_query);
if ($params) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_students = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_students / $limit);

// Get students
$query = "SELECT * FROM students $where_clause ORDER BY batch DESC, roll_no ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get distinct batches and semesters for filters
$batches = $conn->query("SELECT DISTINCT batch FROM students WHERE batch IS NOT NULL ORDER BY batch DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - TU Result System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .manage-container {
            padding: 20px;
        }
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            position: relative;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .close-message {
            cursor: pointer;
            font-size: 1.2rem;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .pagination a:hover {
            background-color: #f0f0f0;
        }
        .pagination .active {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1><i class="fas fa-users"></i> Manage Students</h1>
                <p>Add, edit, or delete student records</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="dashboard.php" class="logout-btn">
                     <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <span><?php echo htmlspecialchars($message); ?></span>
            <span class="close-message" onclick="this.parentElement.style.display='none'">&times;</span>
        </div>
        <?php endif; ?>

        <div class="action-buttons">
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New Student
            </button>
            <a href="batch_results.php" class="btn">
                <i class="fas fa-file-pdf"></i> Generate Batch Results
            </a>
        </div>

        <div class="filters">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Student ID, Name, or Email">
                </div>
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester">
                        <option value="">All Semesters</option>
                        <?php for($i = 1; $i <= 8; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $i == $semester_filter ? 'selected' : ''; ?>>
                            Semester <?php echo $i; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="batch">Batch</label>
                    <select id="batch" name="batch">
                        <option value="">All Batches</option>
                        <?php foreach ($batches as $b): ?>
                        <option value="<?php echo htmlspecialchars($b['batch']); ?>" 
                                <?php echo $b['batch'] == $batch_filter ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['batch']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="manage_students.php" class="btn">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table>
                
<thead>
    <tr>
        <th>SN</th>
        <th>Student ID</th>
        <th>Name</th>
        <th>Date of Birth</th>
        <th>Semester</th>
        <th>Batch</th>
        <th>Roll No</th>
        <th>Email</th>
        <th>Phone</th>
        <th>faculty</th> 
        <th>Program</th>
        <th>Actions</th>
    </tr>
</thead>


<tbody>
    <?php if (empty($students)): ?>
    <tr>
        <td colspan="12" style="text-align: center;">No students found</td> <!-- Changed colspan to 12 -->
    </tr>
    <?php else: ?>
    <?php foreach ($students as $index => $student): ?>
    <tr>
        <td><?php echo $offset + $index + 1; ?></td>
        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
        <td><?php echo htmlspecialchars($student['dob']); ?></td>
        <td><?php echo htmlspecialchars($student['semester']); ?></td>
        <td><?php echo htmlspecialchars($student['batch']); ?></td>
        <td><?php echo htmlspecialchars($student['roll_no']); ?></td>
        <td><?php echo htmlspecialchars($student['email']); ?></td>
        <td><?php echo htmlspecialchars($student['phone']); ?></td>
        <td><?php echo htmlspecialchars($student['faculty'] ?? $student['faculty'] ?? ''); ?></td> 
        <td><?php echo htmlspecialchars($student['program']); ?></td>
        <td>
            <button class="btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($student)); ?>)">
                <i class="fas fa-edit"></i> Edit
            </button>
            <button class="btn btn-danger" onclick="confirmDelete(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['full_name']); ?>')">
                <i class="fas fa-trash"></i> Delete
            </button>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
</tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo $semester_filter; ?>&batch=<?php echo urlencode($batch_filter); ?>">
                &laquo; Previous
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo $semester_filter; ?>&batch=<?php echo urlencode($batch_filter); ?>"
               class="<?php echo $i == $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
            <span>...</span>
            <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo $semester_filter; ?>&batch=<?php echo urlencode($batch_filter); ?>">
                Next &raquo;
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add/Edit Modal -->
    <div id="studentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add New Student</h2>
            <form id="studentForm" method="POST" onsubmit="return validateForm()">
                <input type="hidden" id="action" name="action" value="add_student">
                <input type="hidden" id="studentId" name="id" placeholder="BCA-078-001">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_id">Student ID *</label>
                        <input type="text" id="student_id" name="student_id" required placeholder="BCA-078-001">
                    </div>
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="dob">Date of Birth *</label>
                        <input type="date" id="dob" name="dob" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="semester">Semester *</label>
                        <select id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <?php for($i = 1; $i <= 8; $i++): ?>
                            <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="batch">Batch *</label>
                        <input type="text" id="batch" name="batch" required placeholder="e.g., 2078">
                    </div>
                    <div class="form-group">
                        <label for="faculty ">Select faculty *</label>
                       
                       <select id="faculty" name="faculty" required>  
                            <option value="Humanities">Humanities</option>
                            <option value="Computer Science">Computer Science</option>
                           <option value="Information technology">Information technology</option>
                           <option value="Mathematics">Mathematics</option>
                           <option value="Physics">Physics</option>
                           <option value="Management">Management</option>
                       </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="program">Program *</label>
                        <select id="program" name="program" required>
                            <option value="BCA">BCA</option>
                            <option value="BBA">BBA</option>
                            <option value="BSCSIT">BSCSIT</option>
                            <option value="BIT">BIT</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="roll_no">Roll Number *</label>
                        <input type="number" id="roll_no" name="roll_no" required min="1">
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Student
                    </button>
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Delete</h3>
            <p id="deleteMessage">Are you sure you want to delete this student?</p>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete_student">
                <input type="hidden" id="deleteId" name="id">
                <div class="form-group">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button type="button" class="btn" onclick="closeDeleteModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Add New Student';
        document.getElementById('action').value = 'add_student';
        document.getElementById('studentId').value = '';
        document.getElementById('studentForm').reset();
        document.getElementById('faculty').value = 'Science and Technology';
        document.getElementById('program').value = 'BCA';
        document.getElementById('studentModal').style.display = 'block';
    }
    
    function openEditModal(student) {
        document.getElementById('modalTitle').textContent = 'Edit Student';
        document.getElementById('action').value = 'update_student';
        document.getElementById('studentId').value = student.id;
        document.getElementById('student_id').value = student.student_id;
        document.getElementById('full_name').value = student.full_name;
        document.getElementById('dob').value = student.dob;
        document.getElementById('email').value = student.email;
        document.getElementById('phone').value = student.phone;
        document.getElementById('semester').value = student.semester;
        document.getElementById('batch').value = student.batch;
        document.getElementById('faculty').value = student.faculty;
        document.getElementById('program').value = student.program;
        document.getElementById('roll_no').value = student.roll_no;
        document.getElementById('studentModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('studentModal').style.display = 'none';
    }
    
    function confirmDelete(id, name) {
        document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete student: ' + name + '?';
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteModal').style.display = 'block';
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
    
    function validateForm() {
        const studentId = document.getElementById('student_id').value;
        const dob = document.getElementById('dob').value;
        const rollNo = document.getElementById('roll_no').value;
        
        // Validate Student ID format (example: BCA-078-001)
        if (!/^[A-Z]{3}-[0-9]{3}-[0-9]{3}$/.test(studentId)) {
            alert('Student ID must be in format: ABC-123-456');
            return false;
        }
        
        // Validate Date of Birth (must be at least 16 years old)
        const dobDate = new Date(dob);
        const today = new Date();
        const age = today.getFullYear() - dobDate.getFullYear();
        
        if (age < 16) {
            alert('Student must be at least 16 years old');
            return false;
        }
        
        // Validate Roll Number
        if (rollNo < 1) {
            alert('Roll number must be a positive number');
            return false;
        }
        
        return true;
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = ['studentModal', 'deleteModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
    }
    
    // Auto-close success/error messages after 5 seconds
    setTimeout(function() {
        const messages = document.querySelectorAll('.message');
        messages.forEach(msg => {
            if (msg.style.display !== 'none') {
                msg.style.display = 'none';
            }
        });
    }, 5000);
    </script>
</body>
</html>