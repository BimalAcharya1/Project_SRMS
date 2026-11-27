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
        case 'add_teacher':
            $teacher_id = $_POST['teacher_id'];
            $full_name = $_POST['full_name'];
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            $phone = $_POST['phone'] ?? '';
            $faculty = $_POST['faculty'];
            
            // Validate passwords match
            if ($password !== $confirm_password) {
                $message = "Passwords do not match!";
                $message_type = "error";
                break;
            }
            
            // Check if teacher ID already exists
            $checkStmt = $conn->prepare("SELECT id FROM teachers WHERE teacher_id = ?");
            $checkStmt->bind_param("s", $teacher_id);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows > 0) {
                $message = "Teacher ID already exists!";
                $message_type = "error";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO teachers (teacher_id, full_name, email, password, phone, department) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $teacher_id, $full_name, $email, $hashed_password, $phone, $faculty);
                
                if ($stmt->execute()) {
                    $message = "Teacher added successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error adding teacher: " . $conn->error;
                    $message_type = "error";
                }
            }
            break;
            
        case 'update_teacher':
            $id = $_POST['id'];
            $teacher_id = $_POST['teacher_id'];
            $full_name = $_POST['full_name'];
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $faculty = $_POST['faculty'];
            
            // Check if password is being updated
            if (!empty($_POST['password'])) {
                if ($_POST['password'] !== $_POST['confirm_password']) {
                    $message = "Passwords do not match!";
                    $message_type = "error";
                    break;
                }
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE teachers SET teacher_id = ?, full_name = ?, email = ?, password = ?, phone = ?, faculty = ? WHERE id = ?");
                $stmt->bind_param("ssssssi", $teacher_id, $full_name, $email, $hashed_password, $phone, $faculty, $id);
            } else {
                $stmt = $conn->prepare("UPDATE teachers SET teacher_id = ?, full_name = ?, email = ?, phone = ?, faculty = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $teacher_id, $full_name, $email, $phone, $faculty, $id);
            }
            
            if ($stmt->execute()) {
                $message = "Teacher updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating teacher: " . $conn->error;
                $message_type = "error";
            }
            break;
            
        case 'delete_teacher':
            $id = $_POST['id'];
            
            $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Teacher deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting teacher: " . $conn->error;
                $message_type = "error";
            }
            break;
    }
}

// Get teacher for editing
$edit_teacher = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_teacher = $stmt->get_result()->fetch_assoc();
}

// Get all teachers with pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get search parameters
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';

// Build query
$where_conditions = [];
$params = [];
$param_types = "";

if ($search) {
    $where_conditions[] = "(teacher_id LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= "sss";
}

if ($department_filter) {
    $where_conditions[] = "faculty = ?";
    $params[] = $department_filter;
    $param_types .= "s";
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_query = "SELECT COUNT(*) as total FROM teachers $where_clause";
$count_stmt = $conn->prepare($count_query);
if ($params) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_teachers = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_teachers / $limit);

// Get teachers
$query = "SELECT * FROM teachers $where_clause ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$teachers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get distinct faculty
$departments = $conn->query("SELECT DISTINCT faculty FROM teachers WHERE faculty IS NOT NULL ORDER BY faculty")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - TU Result System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .password-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 35px;
            cursor: pointer;
            color: #666;
        }
        .password-strength {
            margin-top: 5px;
            font-size: 0.9rem;
        }
        .strength-weak { color: #e74c3c; }
        .strength-medium { color: #f39c12; }
        .strength-strong { color: #27ae60; }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1><i class="fas fa-chalkboard-teacher"></i> Manage Teachers</h1>
                <p>Add, edit, or delete teacher accounts</p>
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
                <i class="fas fa-plus"></i> Add New Teacher
            </button>
        </div>

        <div class="filters">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Teacher ID, Name, or Email">
                </div>
                <div class="form-group">
                    <label for="faculty">faculty</label>
                    <select id="faculty" name="faculty">
                        <option value="">All faculty</option>
                        <?php foreach ($faculty as $facs): ?>
                        <option value="<?php echo htmlspecialchars($facs['department']); ?>" 
                                <?php echo $facs['faculty'] == $faculty_filter ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($facs['faculty']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="manage_teachers.php" class="btn">
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
                        <th>Teacher ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>faculty</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($teachers)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No teachers found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($teachers as $index => $teacher): ?>
                    <tr>
                        <td><?php echo $offset + $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($teacher['teacher_id']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['faculty']); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($teacher['created_at'])); ?></td>
                        <td>
                            <button class="btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($teacher)); ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger" onclick="confirmDelete(<?php echo $teacher['id']; ?>, '<?php echo htmlspecialchars($teacher['full_name']); ?>')">
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
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&faculty=<?php echo urlencode($faculty_filter); ?>">
                &laquo; Previous
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&faculty=<?php echo urlencode($faculty_filter); ?>"
               class="<?php echo $i == $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
            <span>...</span>
            <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&faculty=<?php echo urlencode($faculty_filter); ?>">
                Next &raquo;
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add/Edit Modal -->
    <div id="teacherModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add New Teacher</h2>
            <form id="teacherForm" method="POST" onsubmit="return validateForm()">
                <input type="hidden" id="action" name="action" value="add_teacher">
                <input type="hidden" id="teacherId" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="teacher_id">Teacher ID *</label>
                        <input type="text" id="teacher_id" name="teacher_id" required 
                               pattern="TECH-[0-9]{3}" title="Format: TECH-001" placeholder="TECH-001">
                    </div>
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" title="10 digit phone number">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="faculty">faculty *</label>
                        <select id="faculty" name="faculty" required>
                            <option value="">Select faculty</option>
                            <option value="Humanities">Humanities</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="Physics">Physics</option>
                            <option value="Management">Management</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group password-container">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" 
                               oninput="checkPasswordStrength(this.value)" required>
                        <span class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </span>
                        <div id="passwordStrength" class="password-strength"></div>
                    </div>
                    <div class="form-group password-container">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <span class="toggle-password" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Teacher
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
            <p id="deleteMessage">Are you sure you want to delete this teacher?</p>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete_teacher">
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
        document.getElementById('modalTitle').textContent = 'Add New Teacher';
        document.getElementById('action').value = 'add_teacher';
        document.getElementById('teacherId').value = '';
        document.getElementById('teacherForm').reset();
        document.getElementById('password').required = true;
        document.getElementById('confirm_password').required = true;
        document.getElementById('teacherModal').style.display = 'block';
    }
    
    function openEditModal(teacher) {
        document.getElementById('modalTitle').textContent = 'Edit Teacher';
        document.getElementById('action').value = 'update_teacher';
        document.getElementById('teacherId').value = teacher.id;
        document.getElementById('teacher_id').value = teacher.teacher_id;
        document.getElementById('full_name').value = teacher.full_name;
        document.getElementById('email').value = teacher.email;
        document.getElementById('phone').value = teacher.phone;
        document.getElementById('faculty').value = teacher.faculty;
        document.getElementById('password').required = false;
        document.getElementById('confirm_password').required = false;
        document.getElementById('password').placeholder = 'Leave blank to keep current password';
        document.getElementById('confirm_password').placeholder = 'Leave blank to keep current password';
        document.getElementById('teacherModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('teacherModal').style.display = 'none';
    }
    
    function confirmDelete(id, name) {
        document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete teacher: ' + name + '?';
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteModal').style.display = 'block';
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
    
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = field.parentElement.querySelector('.toggle-password i');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    function checkPasswordStrength(password) {
        const strengthDiv = document.getElementById('passwordStrength');
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        let message = '';
        let className = '';
        
        switch(strength) {
            case 0:
            case 1:
            case 2:
                message = 'Weak';
                className = 'strength-weak';
                break;
            case 3:
            case 4:
                message = 'Medium';
                className = 'strength-medium';
                break;
            case 5:
                message = 'Strong';
                className = 'strength-strong';
                break;
        }
        
        strengthDiv.textContent = message;
        strengthDiv.className = 'password-strength ' + className;
    }
    
    function validateForm() {
        const teacherId = document.getElementById('teacher_id').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        // Validate Teacher ID format
        if (!/^TECH-[0-9]{3}$/.test(teacherId)) {
            alert('Teacher ID must be in format: TECH-001');
            return false;
        }
        
        // Validate email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Please enter a valid email address');
            return false;
        }
        
        // Check if passwords match (only if password field is filled)
        if (password && password !== confirmPassword) {
            alert('Passwords do not match!');
            return false;
        }
        
        // Validate password strength if creating new account
        if (document.getElementById('action').value === 'add_teacher' && password.length < 8) {
            alert('Password must be at least 8 characters long');
            return false;
        }
        
        return true;
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = ['teacherModal', 'deleteModal'];
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