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
        case 'add_course':
            $course_code = $_POST['course_code'];
            $course_name = $_POST['course_name'];
            $credit_hours = $_POST['credit_hours'];
            $semester = $_POST['semester'];
            $course_type = $_POST['course_type'];
            $theory_full_mark = $_POST['theory_full_mark'] ?? 60;
            $theory_pass_mark = $_POST['theory_pass_mark'] ?? 24;
            $practical_full_mark = $_POST['practical_full_mark'] ?? 40;
            $practical_pass_mark = $_POST['practical_pass_mark'] ?? 16;
            $status = $_POST['status'];
            
            // Check if course code already exists
            $checkStmt = $conn->prepare("SELECT id FROM courses WHERE course_code = ?");
            $checkStmt->bind_param("s", $course_code);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows > 0) {
                $message = "Course code already exists!";
                $message_type = "error";
            } else {
                $stmt = $conn->prepare("INSERT INTO courses (course_code, course_name, credit_hours, semester, course_type, theory_full_mark, theory_pass_mark, practical_full_mark, practical_pass_mark, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdissiiis", $course_code, $course_name, $credit_hours, $semester, $course_type, $theory_full_mark, $theory_pass_mark, $practical_full_mark, $practical_pass_mark, $status);
                
                if ($stmt->execute()) {
                    $message = "Course added successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error adding course: " . $conn->error;
                    $message_type = "error";
                }
            }
            break;

            
            
        case 'update_course':
            $id = $_POST['id'];
            $course_code = $_POST['course_code'];
            $course_name = $_POST['course_name'];
            $credit_hours = $_POST['credit_hours'];
            $semester = $_POST['semester'];
            $course_type = $_POST['course_type'];
            $theory_full_mark = $_POST['theory_full_mark'] ?? 60;
            $theory_pass_mark = $_POST['theory_pass_mark'] ?? 24;
            $practical_full_mark = $_POST['practical_full_mark'] ?? 40;
            $practical_pass_mark = $_POST['practical_pass_mark'] ?? 16;
            $status = $_POST['status'];
            
            $stmt = $conn->prepare("UPDATE courses SET course_code = ?, course_name = ?, credit_hours = ?, semester = ?, course_type = ?, theory_full_mark = ?, theory_pass_mark = ?, practical_full_mark = ?, practical_pass_mark = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssdissiiiisi", $course_code, $course_name, $credit_hours, $semester, $course_type, $theory_full_mark, $theory_pass_mark, $practical_full_mark, $practical_pass_mark, $status, $id);
            
            if ($stmt->execute()) {
                $message = "Course updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating course: " . $conn->error;
                $message_type = "error";
            }
            break;
            
        case 'delete_course':
            $id = $_POST['id'];
            
            $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Course deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting course: " . $conn->error;
                $message_type = "error";
            }
            break;
    }
}

// Get course for editing
$edit_course = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_course = $stmt->get_result()->fetch_assoc();
}

// Get all courses with pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get search parameters
$search = $_GET['search'] ?? '';
$semester_filter = $_GET['semester'] ?? '';
$course_type_filter = $_GET['course_type'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$where_conditions = [];
$params = [];
$param_types = "";

if ($search) {
    $where_conditions[] = "(course_code LIKE ? OR course_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= "ss";
}

if ($semester_filter) {
    $where_conditions[] = "semester = ?";
    $params[] = $semester_filter;
    $param_types .= "i";
}

if ($course_type_filter) {
    $where_conditions[] = "course_type = ?";
    $params[] = $course_type_filter;
    $param_types .= "s";
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_query = "SELECT COUNT(*) as total FROM courses $where_clause";
$count_stmt = $conn->prepare($count_query);
if ($params) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_courses = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_courses / $limit);

// Get courses
$query = "SELECT * FROM courses $where_clause ORDER BY semester, course_code LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - TU Result System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .marks-distribution {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .marks-distribution.hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1><i class="fas fa-book"></i> Manage Courses</h1>
                <p>Add, edit, or delete course/subject records</p>
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
                <i class="fas fa-plus"></i> Add New Course
            </button>
        </div>

        <div class="filters">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Course Code or Name">
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
                    <label for="course_type">Course Type</label>
                    <select id="course_type" name="course_type">
                        <option value="">All Types</option>
                        <option value="Theory" <?php echo $course_type_filter == 'Theory' ? 'selected' : ''; ?>>Theory</option>
                        <option value="Practical" <?php echo $course_type_filter == 'Practical' ? 'selected' : ''; ?>>Practical</option>
                        <option value="Both" <?php echo $course_type_filter == 'Both' ? 'selected' : ''; ?>>Both</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Status</option>
                        <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $status_filter == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="manage_courses.php" class="btn">
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
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Credit Hours</th>
                        <th>Semester</th>
                        <th>Type</th>
                        <th>Theory Marks</th>
                        <th>Practical Marks</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($courses)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center;">No courses found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($courses as $index => $course): ?>
                    <tr>
                        <td><?php echo $offset + $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($course['credit_hours']); ?></td>
                        <td><?php echo htmlspecialchars($course['semester']); ?></td>
                        <td><?php echo htmlspecialchars($course['course_type']); ?></td>
                        <td><?php echo $course['theory_full_mark'] . '/' . $course['theory_pass_mark']; ?></td>
                        <td><?php echo $course['practical_full_mark'] . '/' . $course['practical_pass_mark']; ?></td>
                        <td>
                            <span class="<?php echo $course['status'] === 'Active' ? 'status-pass' : 'status-fail'; ?>">
                                <?php echo htmlspecialchars($course['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($course)); ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger" onclick="confirmDelete(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['course_code']); ?>')">
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
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo $semester_filter; ?>&course_type=<?php echo urlencode($course_type_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                &laquo; Previous
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo $semester_filter; ?>&course_type=<?php echo urlencode($course_type_filter); ?>&status=<?php echo urlencode($status_filter); ?>"
               class="<?php echo $i == $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
            <span>...</span>
            <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo $semester_filter; ?>&course_type=<?php echo urlencode($course_type_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                Next &raquo;
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add/Edit Modal -->
    <div id="courseModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add New Course</h2>
            <form id="courseForm" method="POST" onsubmit="return validateForm()">
                <input type="hidden" id="action" name="action" value="add_course">
                <input type="hidden" id="courseId" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="course_code">Course Code *</label>
                        <input type="text" id="course_code" name="course_code" required 
                               pattern="[A-Z]{3}-[0-9]{3}" title="Format: ABC-123" placeholder="ABC-123">
                    </div>
                    <div class="form-group">
                        <label for="course_name">Course Name *</label>
                        <input type="text" id="course_name" name="course_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="credit_hours">Credit Hours *</label>
                        <input type="number" id="credit_hours" name="credit_hours" 
                               step="0.5" min="0.5" max="10" required>
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
                        <label for="course_type">Course Type *</label>
                        <select id="course_type" name="course_type" required onchange="toggleMarksDistribution()">
                            <option value="">Select Type</option>
                            <option value="Theory">Theory Only</option>
                            <option value="Practical">Practical Only</option>
                            <option value="Both">Both Theory & Practical</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="">Select Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <!-- Marks Distribution -->
                <div id="marksDistribution" class="marks-distribution hidden">
                    <div class="form-group">
                        <label for="theory_full_mark">Theory Full Mark</label>
                        <input type="number" id="theory_full_mark" name="theory_full_mark" 
                               min="0" max="60" value="60">
                    </div>
                    <div class="form-group">
                        <label for="theory_pass_mark">Theory Pass Mark</label>
                        <input type="number" id="theory_pass_mark" name="theory_pass_mark" 
                               min="0" max="24" value="24">
                    </div>
                    <div class="form-group">
                        <label for="practical_full_mark">Practical Full Mark</label>
                        <input type="number" id="practical_full_mark" name="practical_full_mark" 
                               min="0" max="40" value="40">
                    </div>
                    <div class="form-group">
                        <label for="practical_pass_mark">Practical Pass Mark</label>
                        <input type="number" id="practical_pass_mark" name="practical_pass_mark" 
                               min="0" max="16" value="16">
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Course
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
            <p id="deleteMessage">Are you sure you want to delete this course?</p>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete_course">
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
        document.getElementById('modalTitle').textContent = 'Add New Course';
        document.getElementById('action').value = 'add_course';
        document.getElementById('courseId').value = '';
        document.getElementById('courseForm').reset();
        document.getElementById('theory_full_mark').value = '60';
        document.getElementById('theory_pass_mark').value = '24';
        document.getElementById('practical_full_mark').value = '40';
        document.getElementById('practical_pass_mark').value = '16';
        toggleMarksDistribution();
        document.getElementById('courseModal').style.display = 'block';
    }
    
    function openEditModal(course) {
        document.getElementById('modalTitle').textContent = 'Edit Course';
        document.getElementById('action').value = 'update_course';
        document.getElementById('courseId').value = course.id;
        document.getElementById('course_code').value = course.course_code;
        document.getElementById('course_name').value = course.course_name;
        document.getElementById('credit_hours').value = course.credit_hours;
        document.getElementById('semester').value = course.semester;
        document.getElementById('course_type').value = course.course_type;
        document.getElementById('status').value = course.status;
        document.getElementById('theory_full_mark').value = course.theory_full_mark;
        document.getElementById('theory_pass_mark').value = course.theory_pass_mark;
        document.getElementById('practical_full_mark').value = course.practical_full_mark;
        document.getElementById('practical_pass_mark').value = course.practical_pass_mark;
        toggleMarksDistribution();
        document.getElementById('courseModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('courseModal').style.display = 'none';
    }
    
    function confirmDelete(id, code) {
        document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete course: ' + code + '?';
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteModal').style.display = 'block';
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
    
    function toggleMarksDistribution() {
        const courseType = document.getElementById('course_type').value;
        const marksDiv = document.getElementById('marksDistribution');
        
        if (courseType === 'Theory') {
            marksDiv.style.display = 'grid';
            document.getElementById('theory_full_mark').disabled = false;
            document.getElementById('theory_pass_mark').disabled = false;
            document.getElementById('practical_full_mark').disabled = true;
            document.getElementById('practical_pass_mark').disabled = true;
            document.getElementById('practical_full_mark').value = '0';
            document.getElementById('practical_pass_mark').value = '0';
        } else if (courseType === 'Practical') {
            marksDiv.style.display = 'grid';
            document.getElementById('theory_full_mark').disabled = true;
            document.getElementById('theory_pass_mark').disabled = true;
            document.getElementById('practical_full_mark').disabled = false;
            document.getElementById('practical_pass_mark').disabled = false;
            document.getElementById('theory_full_mark').value = '0';
            document.getElementById('theory_pass_mark').value = '0';
        } else if (courseType === 'Both') {
            marksDiv.style.display = 'grid';
            document.getElementById('theory_full_mark').disabled = false;
            document.getElementById('theory_pass_mark').disabled = false;
            document.getElementById('practical_full_mark').disabled = false;
            document.getElementById('practical_pass_mark').disabled = false;
            document.getElementById('theory_full_mark').value = '60';
            document.getElementById('theory_pass_mark').value = '24';
            document.getElementById('practical_full_mark').value = '40';
            document.getElementById('practical_pass_mark').value = '16';
        } else {
            marksDiv.style.display = 'none';
        }
    }
    
    function validateForm() {
        const courseCode = document.getElementById('course_code').value;
        const courseName = document.getElementById('course_name').value;
        const creditHours = parseFloat(document.getElementById('credit_hours').value);
        const theoryFull = parseInt(document.getElementById('theory_full_mark').value);
        const theoryPass = parseInt(document.getElementById('theory_pass_mark').value);
        const practicalFull = parseInt(document.getElementById('practical_full_mark').value);
        const practicalPass = parseInt(document.getElementById('practical_pass_mark').value);
        
        // Validate Course Code format
        if (!/^[A-Z]{3}-[0-9]{3}$/.test(courseCode)) {
            alert('Course code must be in format: ABC-123');
            return false;
        }
        
        // Validate credit hours
        if (creditHours < 0.5 || creditHours > 10) {
            alert('Credit hours must be between 0.5 and 10');
            return false;
        }
        
        // Validate marks
        const courseType = document.getElementById('course_type').value;
        
        if (courseType === 'Theory' || courseType === 'Both') {
            if (theoryPass >= theoryFull) {
                alert('Theory pass mark must be less than full mark');
                return false;
            }
        }
        
        if (courseType === 'Practical' || courseType === 'Both') {
            if (practicalPass >= practicalFull) {
                alert('Practical pass mark must be less than full mark');
                return false;
            }
        }
        
        return true;
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = ['courseModal', 'deleteModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
    }
    </script>
</body>
</html>