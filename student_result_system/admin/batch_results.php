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

// Get filter parameters
$semester = $_GET['semester'] ?? 4;
$batch = $_GET['batch'] ?? '';
$course_code = $_GET['course'] ?? '';

// Get distinct values for filters
$batches = $conn->query("SELECT DISTINCT batch FROM students WHERE batch IS NOT NULL ORDER BY batch DESC")->fetch_all(MYSQLI_ASSOC);
$courses = $conn->query("SELECT DISTINCT course_code, course_name FROM courses WHERE semester = $semester ORDER BY course_code")->fetch_all(MYSQLI_ASSOC);

// Get results for batch if batch is selected
$results = [];
$students = [];
$course_details = null;

if ($batch && $course_code) {
    // Get course details
    $stmt = $conn->prepare("SELECT * FROM courses WHERE course_code = ?");
    $stmt->bind_param("s", $course_code);
    $stmt->execute();
    $course_details = $stmt->get_result()->fetch_assoc();
    
    // Get all students in the batch
    $stmt = $conn->prepare("SELECT * FROM students WHERE batch = ? AND semester = ? ORDER BY roll_no");
    $stmt->bind_param("si", $batch, $semester);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get results for these students
    if (!empty($students)) {
        $student_ids = array_column($students, 'student_id');
        $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';
        
        $query = "
            SELECT r.*, s.full_name, s.roll_no 
            FROM results r
            JOIN students s ON r.student_id = s.student_id
            WHERE r.course_code = ? AND r.semester = ? 
            AND r.student_id IN ($placeholders)
            ORDER BY s.roll_no
        ";
        
        $params = array_merge([$course_code, $semester], $student_ids);
        $stmt = $conn->prepare($query);
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Create result map for easy lookup
        $result_map = [];
        foreach ($results as $result) {
            $result_map[$result['student_id']] = $result;
        }
    }
}

// Handle PDF generation
if (isset($_POST['generate_pdf'])) {
    require_once('../tcpdf/tcpdf.php');
    
    // Create PDF
    $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('TU Result System');
    $pdf->SetAuthor('Tribhuvan University');
    $pdf->SetTitle('Batch Results - ' . $batch . ' - Semester ' . $semester);
    $pdf->SetSubject('Batch Results');
    
    // Add a page
    $pdf->AddPage();
    
    // Header
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'TRIBHUVAN UNIVERSITY', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'BATCH RESULT SUMMARY', 0, 1, 'C');
    
    // Batch Information
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Ln(5);
    $pdf->Cell(0, 10, 'Program: BCA (Bachelor in Computer Application)', 0, 1);
    $pdf->Cell(0, 10, 'Semester: ' . $semester, 0, 1);
    $pdf->Cell(0, 10, 'Batch: ' . $batch, 0, 1);
    $pdf->Cell(0, 10, 'Course: ' . $course_details['course_code'] . ' - ' . $course_details['course_name'], 0, 1);
    $pdf->Cell(0, 10, 'Course Type: ' . $course_details['course_type'], 0, 1);
    $pdf->Cell(0, 10, 'Credit Hours: ' . $course_details['credit_hours'], 0, 1);
    $pdf->Ln(5);
    
    // Results Table
    $html = '<table border="1" cellpadding="5">
        <thead>
            <tr style="background-color:#f2f2f2;">
                <th width="8%">SN</th>
                <th width="12%">Roll No</th>
                <th width="15%">Student ID</th>
                <th width="25%">Student Name</th>';
    
    if ($course_details['course_type'] == 'Theory' || $course_details['course_type'] == 'Both') {
        $html .= '<th width="10%">Theory<br><small>' . $course_details['theory_full_mark'] . ' Full</small></th>';
    }
    
    if ($course_details['course_type'] == 'Practical' || $course_details['course_type'] == 'Both') {
        $html .= '<th width="10%">Practical<br><small>' . $course_details['practical_full_mark'] . ' Full</small></th>';
    }
    
    $html .= '<th width="10%">Total</th>
                <th width="10%">Percentage</th>
                <th width="10%">Grade</th>
                <th width="10%">Status</th>
            </tr>
        </thead>
        <tbody>';
    
    $pass_count = 0;
    $fail_count = 0;
    
    foreach ($students as $index => $student) {
        $result = $result_map[$student['student_id']] ?? null;
        
        $html .= '<tr>';
        $html .= '<td>' . ($index + 1) . '</td>';
        $html .= '<td>' . $student['roll_no'] . '</td>';
        $html .= '<td>' . $student['student_id'] . '</td>';
        $html .= '<td>' . $student['full_name'] . '</td>';
        
        if ($course_details['course_type'] == 'Theory' || $course_details['course_type'] == 'Both') {
            $html .= '<td>' . ($result['theory_marks'] ?? '-') . '</td>';
        }
        
        if ($course_details['course_type'] == 'Practical' || $course_details['course_type'] == 'Both') {
            $html .= '<td>' . ($result['practical_marks'] ?? '-') . '</td>';
        }
        
        $html .= '<td>' . ($result['total_marks'] ?? '-') . '</td>';
        $html .= '<td>' . ($result['percentage'] ? number_format($result['percentage'], 2) . '%' : '-') . '</td>';
        $html .= '<td>' . ($result['grade'] ?? '-') . '</td>';
        
        if ($result) {
            $status = $result['status'];
            $html .= '<td>' . $status . '</td>';
            
            if ($status == 'Pass') {
                $pass_count++;
            } else {
                $fail_count++;
            }
        } else {
            $html .= '<td>Not Entered</td>';
        }
        
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    
    // Statistics
    $html .= '<br><br>';
    $html .= '<table border="1" cellpadding="5" width="50%">
        <tr style="background-color:#f2f2f2;">
            <th>Statistics</th>
            <th>Count</th>
            <th>Percentage</th>
        </tr>
        <tr>
            <td>Total Students</td>
            <td>' . count($students) . '</td>
            <td>100%</td>
        </tr>
        <tr>
            <td>Passed</td>
            <td>' . $pass_count . '</td>
            <td>' . (count($students) > 0 ? number_format(($pass_count / count($students)) * 100, 2) : '0.00') . '%</td>
        </tr>
        <tr>
            <td>Failed</td>
            <td>' . $fail_count . '</td>
            <td>' . (count($students) > 0 ? number_format(($fail_count / count($students)) * 100, 2) : '0.00') . '%</td>
        </tr>
        <tr>
            <td>Not Entered</td>
            <td>' . (count($students) - count($results)) . '</td>
            <td>' . (count($students) > 0 ? number_format(((count($students) - count($results)) / count($students)) * 100, 2) : '0.00') . '%</td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Output PDF
    $pdf->Output('batch_' . $batch . '_sem' . $semester . '_' . $course_code . '.pdf', 'D');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Results PDF - TU Result System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .preview-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            max-height: 500px;
            overflow-y: auto;
        }
        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .preview-table th, .preview-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .preview-table th {
            background-color: #f2f2f2;
        }
        .preview-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .stats-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 5px 0;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1><i class="fas fa-file-pdf"></i> Batch Results PDF Generator</h1>
                <p>Generate PDF results for entire batch</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="dashboard.php" class="logout-btn">
                     <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div class="filters">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="semester">Semester *</label>
                    <select id="semester" name="semester" required onchange="this.form.submit()">
                        <?php for($i = 1; $i <= 8; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $i == $semester ? 'selected' : ''; ?>>
                            Semester <?php echo $i; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="batch">Batch *</label>
                    <select id="batch" name="batch" required onchange="this.form.submit()">
                        <option value="">Select Batch</option>
                        <?php foreach ($batches as $b): ?>
                        <option value="<?php echo htmlspecialchars($b['batch']); ?>" 
                                <?php echo $b['batch'] == $batch ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['batch']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="course">Course *</label>
                    <select id="course" name="course" required onchange="this.form.submit()">
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $c): ?>
                        <option value="<?php echo htmlspecialchars($c['course_code']); ?>" 
                                <?php echo $c['course_code'] == $course_code ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($batch && $course_code): ?>
        <?php
        $pass_count = 0;
        $fail_count = 0;
        foreach ($results as $result) {
            if ($result['status'] == 'Pass') {
                $pass_count++;
            } else {
                $fail_count++;
            }
        }
        $not_entered = count($students) - count($results);
        ?>
        
        <div class="stats-box">
            <div class="stat-item">
                <div class="stat-value"><?php echo count($students); ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: #27ae60;"><?php echo $pass_count; ?></div>
                <div class="stat-label">Passed</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: #e74c3c;"><?php echo $fail_count; ?></div>
                <div class="stat-label">Failed</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" style="color: #f39c12;"><?php echo $not_entered; ?></div>
                <div class="stat-label">Not Entered</div>
            </div>
        </div>

        <div class="preview-section">
            <h3>Preview - <?php echo $course_details['course_code']; ?> (<?php echo $batch; ?> Batch)</h3>
            <div class="table-container">
                <table class="preview-table">
                    <thead>
                        <tr>
                            <th>SN</th>
                            <th>Roll No</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <?php if ($course_details['course_type'] == 'Theory' || $course_details['course_type'] == 'Both'): ?>
                            <th>Theory<br><small><?php echo $course_details['theory_full_mark']; ?> Full</small></th>
                            <?php endif; ?>
                            <?php if ($course_details['course_type'] == 'Practical' || $course_details['course_type'] == 'Both'): ?>
                            <th>Practical<br><small><?php echo $course_details['practical_full_mark']; ?> Full</small></th>
                            <?php endif; ?>
                            <th>Total</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $index => $student): ?>
                        <?php $result = $result_map[$student['student_id']] ?? null; ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($student['roll_no']); ?></td>
                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <?php if ($course_details['course_type'] == 'Theory' || $course_details['course_type'] == 'Both'): ?>
                            <td><?php echo $result['theory_marks'] ?? '-'; ?></td>
                            <?php endif; ?>
                            <?php if ($course_details['course_type'] == 'Practical' || $course_details['course_type'] == 'Both'): ?>
                            <td><?php echo $result['practical_marks'] ?? '-'; ?></td>
                            <?php endif; ?>
                            <td><?php echo $result['total_marks'] ?? '-'; ?></td>
                            <td><?php echo $result['percentage'] ? number_format($result['percentage'], 2) . '%' : '-'; ?></td>
                            <td class="grade-<?php echo strtolower($result['grade'] ?? 'na'); ?>">
                                <?php echo $result['grade'] ?? '-'; ?>
                            </td>
                            <td class="<?php echo $result ? ($result['status'] === 'Pass' ? 'status-pass' : 'status-fail') : ''; ?>">
                                <?php echo $result ? $result['status'] : 'Not Entered'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" style="margin-top: 20px; text-align: center;">
            <input type="hidden" name="generate_pdf" value="1">
            <input type="hidden" name="semester" value="<?php echo $semester; ?>">
            <input type="hidden" name="batch" value="<?php echo $batch; ?>">
            <input type="hidden" name="course" value="<?php echo $course_code; ?>">
            
            <button type="submit" class="btn btn-primary" style="padding: 15px 30px; font-size: 1.1rem;">
                <i class="fas fa-file-pdf"></i> Generate PDF Report
            </button>
            <button type="button" class="btn" onclick="printPreview()">
                <i class="fas fa-print"></i> Print Preview
            </button>
        </form>
        
        <?php else: ?>
        <div class="message info">
            <i class="fas fa-info-circle"></i> Please select batch and course to generate PDF report.
        </div>
        <?php endif; ?>
    </div>

    <script>
    function printPreview() {
        window.print();
    }
    
    // Auto-refresh course list when semester changes
    document.getElementById('semester').addEventListener('change', function() {
        const semester = this.value;
        if (semester) {
            window.location.href = `batch_results.php?semester=${semester}&batch=<?php echo $batch; ?>&course=<?php echo $course_code; ?>`;
        }
    });
    </script>
</body>
</html>