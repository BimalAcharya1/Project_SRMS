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
        case 'add_subject':
            $subject_code = $_POST['subject_code'];
            $subject_name = $_POST['subject_name'];
            $department = $_POST['department'];
            $semester = $_POST['semester'];
            $credit_hours = $_POST['credit_hours'];
            $subject_type = $_POST['subject_type'];
            $description = $_POST['description'] ?? '';
            
            // Check if subject code already exists
            $checkStmt = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ?");
            $checkStmt->bind_param("s", $subject_code);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows > 0) {
                $message = "Subject code already exists!";
                $message_type = "error";
            } else {
                $stmt = $conn->prepare("INSERT INTO subjects (subject_code, subject_name, department, semester, credit_hours, subject_type, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssiss", $subject_code, $subject_name, $department, $semester, $credit_hours, $subject_type, $description);
                
                if ($stmt->execute()) {
                    $message = "Subject added successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error adding subject: " . $conn->error;
                    $message_type = "error";
                }
            }
            break;
            
        case 'update_subject':
            $id = $_POST['id'];
            $subject_code = $_POST['subject_code'];
            $subject_name = $_POST['subject_name'];
            $department = $_POST['department'];
            $semester = $_POST['semester'];
            $credit_hours = $_POST['credit_hours'];
            $subject_type = $_POST['subject_type'];
            $description = $_POST['description'] ?? '';
            
            $stmt = $conn->prepare("UPDATE subjects SET subject_code = ?, subject_name = ?, department = ?, semester = ?, credit_hours = ?, subject_type = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssssissi", $subject_code, $subject_name, $department, $semester, $credit_hours, $subject_type, $description, $id);
            
            if ($stmt->execute()) {
                $message = "Subject updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating subject: " . $conn->error;
                $message_type = "error";
            }
            break;
            
            case 'delete_subject':
            $id = $_POST['id'];
            
            // Check if subject has any results
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM results WHERE subject_id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                $message = "Cannot delete subject. It has associated results!";
                $message_type = "error";
            } else {
                $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $message = "Subject deleted successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error deleting subject: " . $conn->error;
                    $message_type = "error";
                }
            }
            break;
    }
}

// Get subject for editing
$edit_subject = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_subject = $stmt->get_result()->fetch_assoc();
}

// Get all subjects with pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';
$semester_filter = $_GET['semester'] ?? '';
$subject_type_filter = $_GET['subject_type'] ?? '';

// Build query
$where_conditions = [];
$params = [];
$param_types = "";

if ($search) {
    $where_conditions[] = "(subject_code LIKE ? OR subject_name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= "sss";
}

if ($department_filter) {
    $where_conditions[] = "department = ?";
    $params[] = $department_filter;
    $param_types .= "s";
}

if ($semester_filter) {
    $where_conditions[] = "semester = ?";
    $params[] = $semester_filter;
    $param_types .= "i";
}

if ($subject_type_filter) {
    $where_conditions[] = "subject_type = ?";
    $params[] = $subject_type_filter;
    $param_types .= "s";
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_query = "SELECT COUNT(*) as total FROM subjects $where_clause";
$count_stmt = $conn->prepare($count_query);
if ($params) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_subjects = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_subjects / $limit);

// Get subjects
$query = "SELECT * FROM subjects $where_clause ORDER BY semester, subject_code LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get distinct values for filters
$departments = $conn->query("SELECT DISTINCT department FROM subjects WHERE department IS NOT NULL ORDER BY department")->fetch_all(MYSQLI_ASSOC);
$semesters = $conn->query("SELECT DISTINCT semester FROM subjects WHERE semester IS NOT NULL ORDER BY semester")->fetch_all(MYSQLI_ASSOC);
$subject_types = $conn->query("SELECT DISTINCT subject_type FROM subjects WHERE subject_type IS NOT NULL ORDER BY subject_type")->fetch_all(MYSQLI_ASSOC);

// Get subject counts for statistics
$stats_query = "SELECT 
    COUNT(*) as total_subjects,
    COUNT(DISTINCT department) as total_departments,
    COUNT(DISTINCT semester) as total_semesters,
    SUM(credit_hours) as total_credit_hours
    FROM subjects";
$stats_result = $conn->query($stats_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects - TU Result System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card i {
            font-size: 2em;
            color: #3498db;
            margin-bottom: 10px;
        }
        .stat-card h3 {
            margin: 0;
            font-size: 1.8em;
            color: #2c3e50;
        }
        .stat-card p {
            margin: 5px 0 0;
            color: #7f8c8d;
            font-size: 0.9em;
        }
        .credit-badge {
            background: #27ae60;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .type-badge {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 500;
        }
        .type-theory { background: #e3f2fd; color: #1976d2; }
        .type-practical { background: #f3e5f5; color: #7b1fa2; }
        .type-lab { background: #e8f5e8; color: #388e3c; }
        .type-project { background: #fff3e0; color: #f57c00; }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1><i class="fas fa-book"></i> Manage Subjects</h1>
                <p>Add, edit, or delete academic subjects</p>
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

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <i class="fas fa-book-open"></i>
                <h3><?php echo $stats_result['total_subjects']; ?></h3>
                <p>Total Subjects</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-building"></i>
                <h3><?php echo $stats_result['total_departments']; ?></h3>
                <p>Departments</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-layer-group"></i>
                <h3><?php echo $stats_result['total_semesters']; ?></h3>
                <p>Semesters</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <h3><?php echo $stats_result['total_credit_hours']; ?></h3>
                <p>Total Credit Hours</p>
            </div>
        </div>

        <div class="action-buttons">
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New Subject
            </button>
            <a href="export_subjects.php" class="btn btn-success">
                <i class="fas fa-file-export"></i> Export to Excel
            </a>
        </div>

        <div class="filters">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Subject Code, Name, or Description">
                </div>
                <div class="form-group">
                    <label for="department">Department</label>
                    <select id="department" name="department">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                <?php echo $dept['department'] == $department_filter ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['department']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester">
                        <option value="">All Semesters</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $i == $semester_filter ? 'selected' : ''; ?>>
                            Semester <?php echo $i; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="subject_type">Subject Type</label>
                    <select id="subject_type" name="subject_type">
                        <option value="">All Types</option>
                        <?php foreach ($subject_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type['subject_type']); ?>" 
                                <?php echo $type['subject_type'] == $subject_type_filter ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['subject_type']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="manage_subjects.php" class="btn">
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
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Credit Hours</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subjects)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">No subjects found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($subjects as $index => $subject): ?>
                    <tr>
                        <td><?php echo $offset + $index + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($subject['department']); ?></td>
                        <td>Semester <?php echo $subject['semester']; ?></td>
                        <td><span class="credit-badge"><?php echo $subject['credit_hours']; ?> Cr</span></td>
                        <td>
                            <?php 
                            $type_class = '';
                            switch($subject['subject_type']) {
                                case 'Theory': $type_class = 'type-theory'; break;
                                case 'Practical': $type_class = 'type-practical'; break;
                                case 'Lab': $type_class = 'type-lab'; break;
                                case 'Project': $type_class = 'type-project'; break;
                                default: $type_class = '';
                            }
                            ?>
                            <span class="type-badge <?php echo $type_class; ?>">
                                <?php echo htmlspecialchars($subject['subject_type']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars(substr($subject['description'] ?? '', 0, 50)); ?>
                            <?php if (strlen($subject['description'] ?? '') > 50): ?>...<?php endif; ?>
                        </td>
                        <td>
                            <button class="btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($subject)); ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger" onclick="confirmDelete(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars(addslashes($subject['subject_name'])); ?>')">
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
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department_filter); ?>&semester=<?php echo urlencode($semester_filter); ?>&subject_type=<?php echo urlencode($subject_type_filter); ?>">
                &laquo; Previous
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department_filter); ?>&semester=<?php echo urlencode($semester_filter); ?>&subject_type=<?php echo urlencode($subject_type_filter); ?>"
               class="<?php echo $i == $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
            <span>...</span>
            <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo urlencode($department_filter); ?>&semester=<?php echo urlencode($semester_filter); ?>&subject_type=<?php echo urlencode($subject_type_filter); ?>">
                Next &raquo;
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add/Edit Modal -->
    <div id="subjectModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add New Subject</h2>
            <form id="subjectForm" method="POST" onsubmit="return validateForm()">
                <input type="hidden" id="action" name="action" value="add_subject">
                <input type="hidden" id="subjectId" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="subject_code">Subject Code *</label>
                        <input type="text" id="subject_code" name="subject_code" required 
                               pattern="[A-Z]{3}-[0-9]{3}" title="Format: CSE-101" placeholder="CSE-101">
                        <small>Format: DEP-XXX (e.g., CSE-101)</small>
                    </div>
                    <div class="form-group">
                        <label for="subject_name">Subject Name *</label>
                        <input type="text" id="subject_name" name="subject_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="department">Department *</label>
                        <select id="department" name="department" required>
                            <option value="">Select Department</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Mechanical">Mechanical</option>
                            <option value="Civil">Civil</option>
                            <option value="Electrical">Electrical</option>
                            <option value="Humanities">Humanities</option>
                            <option value="Management">Management</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="semester">Semester *</label>
                        <select id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="credit_hours">Credit Hours *</label>
                        <select id="credit_hours" name="credit_hours" required>
                            <option value="">Select Credit Hours</option>
                            <option value="1">1 Credit</option>
                            <option value="2">2 Credits</option>
                            <option value="3">3 Credits</option>
                            <option value="4">4 Credits</option>
                            <option value="5">5 Credits</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subject_type">Subject Type *</label>
                        <select id="subject_type" name="subject_type" required>
                            <option value="">Select Type</option>
                            <option value="Theory">Theory</option>
                            <option value="Practical">Practical</option>
                            <option value="Both">Theory & Practical</option>
                            <option value="Project">Project</option>
                            <option value="Seminar">Seminar</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="Optional subject description..."></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Subject
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
            <p id="deleteMessage">Are you sure you want to delete this subject?</p>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete_subject">
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
        document.getElementById('modalTitle').textContent = 'Add New Subject';
        document.getElementById('action').value = 'add_subject';
        document.getElementById('subjectId').value = '';
        document.getElementById('subjectForm').reset();
        document.getElementById('subjectModal').style.display = 'block';
    }
    
    function openEditModal(subject) {
        document.getElementById('modalTitle').textContent = 'Edit Subject';
        document.getElementById('action').value = 'update_subject';
        document.getElementById('subjectId').value = subject.id;
        document.getElementById('subject_code').value = subject.subject_code;
        document.getElementById('subject_name').value = subject.subject_name;
        document.getElementById('department').value = subject.department;
        document.getElementById('semester').value = subject.semester;
        document.getElementById('credit_hours').value = subject.credit_hours;
        document.getElementById('subject_type').value = subject.subject_type;
        document.getElementById('description').value = subject.description || '';
        document.getElementById('subjectModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('subjectModal').style.display = 'none';
    }
    
    function confirmDelete(id, name) {
        document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete subject: ' + name + '? This action cannot be undone.';
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteModal').style.display = 'block';
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
    
    function validateForm() {
        const subjectCode = document.getElementById('subject_code').value;
        const subjectName = document.getElementById('subject_name').value;
        
        // Validate Subject Code format
        if (!/^[A-Z]{3}-[0-9]{3}$/.test(subjectCode)) {
            alert('Subject Code must be in format: DEP-XXX (e.g., CSE-101)');
            return false;
        } 
        
        // Validate Subject Name length
        if (subjectName.length < 3 || subjectName.length > 100) {
            alert('Subject Name must be between 3 and 100 characters');
            return false;
        }
        
        // Validate description length if provided
        const description = document.getElementById('description').value;
        if (description.length > 500) {
            alert('Description cannot exceed 500 characters');
            return false;
        }
        
        return true;
    }
    
    // Auto-format subject code
    document.getElementById('subject_code')?.addEventListener('input', function(e) {
        let value = e.target.value.toUpperCase();
        value = value.replace(/[^A-Z0-9-]/g, '');
        e.target.value = value;
    });
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = ['subjectModal', 'deleteModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
    }
    
    // Quick filter functionality
    function applyQuickFilter(type, value) {
        window.location.href = `manage_subjects.php?${type}=${encodeURIComponent(value)}`;
    }
    </script>
</body>
</html>