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

// Handle delete operation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_result') {
    $id = $_POST['id'];
    
    $stmt = $conn->prepare("DELETE FROM results WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Result deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting result: " . $conn->error;
        $message_type = "error";
    }
}

// Get filter parameters
$semester = $_GET['semester'] ?? '';
$course_code = $_GET['course'] ?? '';
$batch = $_GET['batch'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];
$param_types = "";

if ($semester) {
    $where_conditions[] = "r.semester = ?";
    $params[] = $semester;
    $param_types .= "i";
}

if ($course_code) {
    $where_conditions[] = "r.course_code = ?";
    $params[] = $course_code;
    $param_types .= "s";
}

if ($batch) {
    $where_conditions[] = "s.batch = ?";
    $params[] = $batch;
    $param_types .= "s";
}

if ($status_filter) {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if ($search) {
    $where_conditions[] = "(s.student_id LIKE ? OR s.full_name LIKE ? OR r.course_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= "sss";
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count
$count_query = "
    SELECT COUNT(*) as total 
    FROM results r
    JOIN students s ON r.student_id = s.student_id
    JOIN courses c ON r.course_code = c.course_code
    $where_clause
";
$count_stmt = $conn->prepare($count_query);
if ($params) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_results = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_results / $limit);

// Get results
$query = "
    SELECT 
        r.*,
        s.full_name,
        s.batch,
        s.roll_no,
        c.course_name,
        c.course_type,
        c.theory_full_mark,
        c.practical_full_mark,
        t.full_name as teacher_name
    FROM results r
    JOIN students s ON r.student_id = s.student_id
    JOIN courses c ON r.course_code = c.course_code
    LEFT JOIN teachers t ON r.entered_by = t.id
    $where_clause
    ORDER BY r.entered_date DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get distinct values for filters
$batches = $conn->query("SELECT DISTINCT batch FROM students WHERE batch IS NOT NULL ORDER BY batch DESC")->fetch_all(MYSQLI_ASSOC);
$courses = $conn->query("SELECT DISTINCT course_code, course_name FROM courses ORDER BY course_code")->fetch_all(MYSQLI_ASSOC);
$semesters = $conn->query("SELECT DISTINCT semester FROM results ORDER BY semester DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Results - TU Result System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-box i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .stat-box.total i { color: #3498db; }
        .stat-box.pass i { color: #27ae60; }
        .stat-box.fail i { color: #e74c3c; }
        .stat-box.incomplete i { color: #f39c12; }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 5px 0;
        }
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .result-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1><i class="fas fa-chart-bar"></i> Manage Results</h1>
                <p>View and manage all student results</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="dashboard.php" class="logout-btn">
                   <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <?php if (isset($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <span><?php echo htmlspecialchars($message); ?></span>
            <span class="close-message" onclick="this.parentElement.style.display='none'">&times;</span>
        </div>
        <?php endif; ?>

        <!-- Statistics Summary -->
        <div class="stats-summary">
            <?php
            // Get statistics
            $total_stats = $conn->query("SELECT COUNT(*) as total FROM results")->fetch_assoc();
            $pass_stats = $conn->query("SELECT COUNT(*) as total FROM results WHERE status = 'Pass'")->fetch_assoc();
            $fail_stats = $conn->query("SELECT COUNT(*) as total FROM results WHERE status = 'Fail'")->fetch_assoc();
            $incomplete_stats = $conn->query("
                SELECT COUNT(*) as total 
                FROM students s 
                LEFT JOIN results r ON s.student_id = r.student_id 
                WHERE r.id IS NULL
            ")->fetch_assoc();
            ?>
            <div class="stat-box total">
                <i class="fas fa-file-alt"></i>
                <div class="stat-number"><?php echo $total_stats['total']; ?></div>
                <div>Total Results</div>
            </div>
            <div class="stat-box pass">
                <i class="fas fa-check-circle"></i>
                <div class="stat-number"><?php echo $pass_stats['total']; ?></div>
                <div>Pass Results</div>
            </div>
            <div class="stat-box fail">
                <i class="fas fa-times-circle"></i>
                <div class="stat-number"><?php echo $fail_stats['total']; ?></div>
                <div>Fail Results</div>
            </div>
            <div class="stat-box incomplete">
                <i class="fas fa-hourglass-half"></i>
                <div class="stat-number"><?php echo $incomplete_stats['total']; ?></div>
                <div>Pending Results</div>
            </div>
        </div>

        <div class="export-buttons">
            <button class="btn" onclick="exportToCSV()">
                <i class="fas fa-file-csv"></i> Export to CSV
            </button>
            <a href="batch_results.php" class="btn btn-primary">
                <i class="fas fa-file-pdf"></i> Generate Batch PDF
            </a>
        </div>

        <div class="filters">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Student ID, Name, or Course Code">
                </div>
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester">
                        <option value="">All Semesters</option>
                        <?php foreach ($semesters as $sem): ?>
                        <option value="<?php echo htmlspecialchars($sem['semester']); ?>" 
                                <?php echo $sem['semester'] == $semester ? 'selected' : ''; ?>>
                            Semester <?php echo htmlspecialchars($sem['semester']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="course">Course</label>
                    <select id="course" name="course">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                        <option value="<?php echo htmlspecialchars($course['course_code']); ?>" 
                                <?php echo $course['course_code'] == $course_code ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_code']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="batch">Batch</label>
                    <select id="batch" name="batch">
                        <option value="">All Batches</option>
                        <?php foreach ($batches as $b): ?>
                        <option value="<?php echo htmlspecialchars($b['batch']); ?>" 
                                <?php echo $b['batch'] == $batch ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['batch']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Status</option>
                        <option value="Pass" <?php echo $status_filter == 'Pass' ? 'selected' : ''; ?>>Pass</option>
                        <option value="Fail" <?php echo $status_filter == 'Fail' ? 'selected' : ''; ?>>Fail</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="manage_results.php" class="btn">
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
                        <th>Student Name</th>
                        <th>Batch</th>
                        <th>Course</th>
                        <th>Semester</th>
                        <th>Theory</th>
                        <th>Practical</th>
                        <th>Total</th>
                        <th>Percentage</th>
                        <th>Grade</th>
                        <th>Status</th>
                        <th>Entered By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                    <tr>
                        <td colspan="15" style="text-align: center;">No results found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($results as $index => $result): ?>
                    <tr>
                        <td><?php echo $offset + $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($result['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($result['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($result['batch']); ?></td>
                        <td><?php echo htmlspecialchars($result['course_code'] . ' - ' . $result['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($result['semester']); ?></td>
                        <td>
                            <?php echo $result['theory_marks'] ? $result['theory_marks'] . '/' . $result['theory_full_mark'] : '-'; ?>
                        </td>
                        <td>
                            <?php echo $result['practical_marks'] ? $result['practical_marks'] . '/' . $result['practical_full_mark'] : '-'; ?>
                        </td>
                        <td><?php echo $result['total_marks'] ?? '-'; ?></td>
                        <td><?php echo $result['percentage'] ? number_format($result['percentage'], 2) . '%' : '-'; ?></td>
                        <td class="grade-<?php echo strtolower($result['grade']); ?>">
                            <?php echo $result['grade']; ?>
                        </td>
                        <td class="<?php echo $result['status'] === 'Pass' ? 'status-pass' : 'status-fail'; ?>">
                            <?php echo htmlspecialchars($result['status']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($result['teacher_name'] ?? 'System'); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($result['entered_date'])); ?></td>
                        <td>
                            <button class="btn" onclick="viewResultDetails(<?php echo $result['id']; ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-danger" onclick="confirmDelete(<?php echo $result['id']; ?>, '<?php echo htmlspecialchars($result['student_id']); ?>')">
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
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo $semester; ?>&course=<?php echo urlencode($course_code); ?>&batch=<?php echo urlencode($batch); ?>&status=<?php echo urlencode($status_filter); ?>">
                &laquo; Previous
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo $semester; ?>&course=<?php echo urlencode($course_code); ?>&batch=<?php echo urlencode($batch); ?>&status=<?php echo urlencode($status_filter); ?>"
               class="<?php echo $i == $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
            <span>...</span>
            <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo $semester; ?>&course=<?php echo urlencode($course_code); ?>&batch=<?php echo urlencode($batch); ?>&status=<?php echo urlencode($status_filter); ?>">
                Next &raquo;
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- View Details Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close" onclick="closeViewModal()">&times;</span>
            <h2>Result Details</h2>
            <div id="resultDetails"></div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Delete</h3>
            <p id="deleteMessage">Are you sure you want to delete this result?</p>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete_result">
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
    function viewResultDetails(resultId) {
        $.ajax({
            url: '../api/result_api.php',
            type: 'GET',
            data: { action: 'getResultDetails', id: resultId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const result = response.data;
                    let html = `
                        <div class="result-details">
                            <div class="form-row">
                                <div><strong>Student ID:</strong> ${result.student_id}</div>
                                <div><strong>Student Name:</strong> ${result.full_name}</div>
                            </div>
                            <div class="form-row">
                                <div><strong>Course:</strong> ${result.course_code} - ${result.course_name}</div>
                                <div><strong>Semester:</strong> ${result.semester}</div>
                            </div>
                            <div class="form-row">
                                <div><strong>Batch:</strong> ${result.batch}</div>
                                <div><strong>Roll No:</strong> ${result.roll_no}</div>
                            </div>
                            <div class="form-row">
                                <div><strong>Course Type:</strong> ${result.course_type}</div>
                                <div><strong>Credit Hours:</strong> ${result.credit_hours}</div>
                            </div>
                            <div class="form-row">
                                <div><strong>Theory Marks:</strong> ${result.theory_marks || '-'}/${result.theory_full_mark}</div>
                                <div><strong>Practical Marks:</strong> ${result.practical_marks || '-'}/${result.practical_full_mark}</div>
                            </div>
                            <div class="form-row">
                                <div><strong>Total Marks:</strong> ${result.total_marks || '-'}</div>
                                <div><strong>Percentage:</strong> ${result.percentage ? result.percentage.toFixed(2) + '%' : '-'}</div>
                            </div>
                            <div class="form-row">
                                <div><strong>Grade:</strong> <span class="grade-${result.grade.toLowerCase()}">${result.grade}</span></div>
                                <div><strong>Status:</strong> <span class="${result.status === 'Pass' ? 'status-pass' : 'status-fail'}">${result.status}</span></div>
                            </div>
                            <div class="form-row">
                                <div><strong>Entered By:</strong> ${result.teacher_name || 'System'}</div>
                                <div><strong>Date:</strong> ${result.entered_date}</div>
                            </div>
                        </div>
                    `;
                    document.getElementById('resultDetails').innerHTML = html;
                    document.getElementById('viewModal').style.display = 'block';
                } else {
                    alert('Error loading result details');
                }
            },
            error: function() {
                alert('Error loading result details');
            }
        });
    }
    
    function closeViewModal() {
        document.getElementById('viewModal').style.display = 'none';
    }
    
    function confirmDelete(id, studentId) {
        document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete result for student: ' + studentId + '?';
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteModal').style.display = 'block';
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
    
    function exportToCSV() {
        const params = new URLSearchParams(window.location.search);
        window.open('../api/result_api.php?action=exportCSV&' + params.toString(), '_blank');
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = ['viewModal', 'deleteModal'];
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