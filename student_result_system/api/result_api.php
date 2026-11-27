<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$db = new Database();
$conn = $db->getConnection();

$action = $_POST['action'] ?? $_GET['action'] ?? '';



// Function to calculate result values
function calculateResultValues($theory_marks, $practical_marks, $course_type, $theory_full, $theory_pass, $practical_full, $practical_pass) {
    $total = null;
    $percentage = null;
    $grade = 'N/A';
    $status = 'Not Entered';
    
    if ($theory_marks !== null || $practical_marks !== null) {
        // Calculate total
        $theory = $theory_marks ?? 0;
        $practical = $practical_marks ?? 0;
        $total = $theory + $practical;
        
        // Calculate percentage based on course type
        if ($course_type == 'Theory') {
            $percentage = $theory_full > 0 ? ($theory / $theory_full) * 100 : 0;
        } else if ($course_type == 'Practical') {
            $percentage = $practical_full > 0 ? ($practical / $practical_full) * 100 : 0;
        } else {
            $full_total = $theory_full + $practical_full;
            $percentage = $full_total > 0 ? ($total / $full_total) * 100 : 0;
        }
        
        // Calculate grade
        if ($percentage !== null) {
            if ($percentage >= 80) $grade = 'A';
            else if ($percentage >= 70) $grade = 'B';
            else if ($percentage >= 60) $grade = 'C';
            else if ($percentage >= 50) $grade = 'D';
            else if ($percentage >= 40) $grade = 'E';
            else $grade = 'F';
        }
        
        // Determine status
        if ($course_type == 'Theory') {
            $status = ($theory_marks >= $theory_pass) ? 'Pass' : 'Fail';
        } else if ($course_type == 'Practical') {
            $status = ($practical_marks >= $practical_pass) ? 'Pass' : 'Fail';
        } else {
            $status = ($theory_marks >= $theory_pass && $practical_marks >= $practical_pass) ? 'Pass' : 'Fail';
        }
    }
    
    return [
        'total' => $total,
        'percentage' => $percentage,
        'grade' => $grade,
        'status' => $status
    ];
}

switch ($action) {
    case 'saveMarks':
        if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'teacher') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }
        
        $student_id = $_POST['student_id'];
        $course_code = $_POST['course_code'];
        $semester = $_POST['semester'];
        $theory_marks = !empty($_POST['theory_marks']) ? $_POST['theory_marks'] : null;
        $practical_marks = !empty($_POST['practical_marks']) ? $_POST['practical_marks'] : null;
        
        // Get course details for calculations
        $courseStmt = $conn->prepare("SELECT * FROM courses WHERE course_code = ?");
        $courseStmt->bind_param("s", $course_code);
        $courseStmt->execute();
        $course = $courseStmt->get_result()->fetch_assoc();
        
        if (!$course) {
            echo json_encode(['success' => false, 'message' => 'Course not found']);
            exit;
        }
        
        // Calculate result values
        $calculated = calculateResultValues(
            $theory_marks, 
            $practical_marks, 
            $course['course_type'],
            $course['theory_full_mark'],
            $course['theory_pass_mark'],
            $course['practical_full_mark'],
            $course['practical_pass_mark']
        );
        
        // Check if result already exists
        $checkStmt = $conn->prepare("SELECT id FROM results WHERE student_id = ? AND course_code = ? AND semester = ?");
        $checkStmt->bind_param("ssi", $student_id, $course_code, $semester);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->num_rows > 0;
        
        if ($exists) {
            // Update existing result
            $stmt = $conn->prepare("UPDATE results SET theory_marks = ?, practical_marks = ?, total_marks = ?, percentage = ?, grade = ?, status = ?, entered_by = ? WHERE student_id = ? AND course_code = ? AND semester = ?");
            $stmt->bind_param("dddddsssi", $theory_marks, $practical_marks, $calculated['total'], $calculated['percentage'], $calculated['grade'], $calculated['status'], $_SESSION['teacher_id'], $student_id, $course_code, $semester);
        } else {
            // Insert new result
            // Get batch from student
            $batchStmt = $conn->prepare("SELECT batch FROM students WHERE student_id = ?");
            $batchStmt->bind_param("s", $student_id);
            $batchStmt->execute();
            $batchResult = $batchStmt->get_result();
            $batch = $batchResult->num_rows > 0 ? $batchResult->fetch_assoc()['batch'] : null;
            
            $stmt = $conn->prepare("INSERT INTO results (student_id, course_code, semester, batch, theory_marks, practical_marks, total_marks, percentage, grade, status, entered_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisddddssi", $student_id, $course_code, $semester, $batch, $theory_marks, $practical_marks, $calculated['total'], $calculated['percentage'], $calculated['grade'], $calculated['status'], $_SESSION['teacher_id']);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Marks saved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error saving marks: ' . $conn->error]);
        }
        break;
        
    case 'saveAllMarks':
        if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'teacher') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }
        
        $course_code = $_POST['course_code'];
        $semester = $_POST['semester'];
        $batch = $_POST['batch'];
        $marks = json_decode($_POST['marks'], true);
        
        // Get course details for calculations
        $courseStmt = $conn->prepare("SELECT * FROM courses WHERE course_code = ?");
        $courseStmt->bind_param("s", $course_code);
        $courseStmt->execute();
        $course = $courseStmt->get_result()->fetch_assoc();
        
        if (!$course) {
            echo json_encode(['success' => false, 'message' => 'Course not found']);
            exit;
        }
        
        $conn->autocommit(FALSE);
        $success = true;
        $errors = [];
        
        foreach ($marks as $student_id => $markData) {
            $theory_marks = !empty($markData['theory']) ? $markData['theory'] : null;
            $practical_marks = !empty($markData['practical']) ? $markData['practical'] : null;
            
            // Calculate result values
            $calculated = calculateResultValues(
                $theory_marks, 
                $practical_marks, 
                $course['course_type'],
                $course['theory_full_mark'],
                $course['theory_pass_mark'],
                $course['practical_full_mark'],
                $course['practical_pass_mark']
            );
            
            // Check if result exists
            $checkStmt = $conn->prepare("SELECT id FROM results WHERE student_id = ? AND course_code = ? AND semester = ?");
            $checkStmt->bind_param("ssi", $student_id, $course_code, $semester);
            $checkStmt->execute();
            $exists = $checkStmt->get_result()->num_rows > 0;
            
            if ($exists) {
                $stmt = $conn->prepare("UPDATE results SET theory_marks = ?, practical_marks = ?, total_marks = ?, percentage = ?, grade = ?, status = ?, entered_by = ? WHERE student_id = ? AND course_code = ? AND semester = ?");
                $stmt->bind_param("dddddsssi", $theory_marks, $practical_marks, $calculated['total'], $calculated['percentage'], $calculated['grade'], $calculated['status'], $_SESSION['teacher_id'], $student_id, $course_code, $semester);
            } else {
                $stmt = $conn->prepare("INSERT INTO results (student_id, course_code, semester, batch, theory_marks, practical_marks, total_marks, percentage, grade, status, entered_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisddddssi", $student_id, $course_code, $semester, $batch, $theory_marks, $practical_marks, $calculated['total'], $calculated['percentage'], $calculated['grade'], $calculated['status'], $_SESSION['teacher_id']);
            }
            
            if (!$stmt->execute()) {
                $success = false;
                $errors[] = "Student $student_id: " . $stmt->error;
                break;
            }
        }
        
        if ($success) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'All marks saved successfully']);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error saving marks: ' . implode(', ', $errors)]);
        }
        $conn->autocommit(TRUE);
        break;
        
    case 'getResultDetails':
        if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }
        
        $id = $_GET['id'];
        
        $query = "
            SELECT 
                r.*,
                s.full_name,
                s.batch,
                s.roll_no,
                c.course_name,
                c.course_type,
                c.credit_hours,
                c.theory_full_mark,
                c.practical_full_mark,
                t.full_name as teacher_name
            FROM results r
            JOIN students s ON r.student_id = s.student_id
            JOIN courses c ON r.course_code = c.course_code
            LEFT JOIN teachers t ON r.entered_by = t.id
            WHERE r.id = ?
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result) {
            echo json_encode(['success' => true, 'data' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Result not found']);
        }
        break;
        
    case 'exportCSV':
        if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
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
        
        $query = "
            SELECT 
                r.student_id,
                s.full_name,
                s.batch,
                s.roll_no,
                r.course_code,
                c.course_name,
                r.semester,
                r.theory_marks,
                r.practical_marks,
                r.total_marks,
                r.percentage,
                r.grade,
                r.status,
                r.entered_date
            FROM results r
            JOIN students s ON r.student_id = s.student_id
            JOIN courses c ON r.course_code = c.course_code
            $where_clause
            ORDER BY r.entered_date DESC
        ";
        
        if ($params) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param($param_types, ...$params);
            $stmt->execute();
            $results = $stmt->get_result();
        } else {
            $results = $conn->query($query);
        }
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="results_export_' . date('Y-m-d') . '.csv"');
        
        // Create CSV file
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        // Add headers
        fputcsv($output, [
            'Student ID',
            'Student Name',
            'Batch',
            'Roll No',
            'Course Code',
            'Course Name',
            'Semester',
            'Theory Marks',
            'Practical Marks',
            'Total Marks',
            'Percentage',
            'Grade',
            'Status',
            'Entered Date'
        ]);
        
        // Add data
        while ($row = $results->fetch_assoc()) {
            fputcsv($output, [
                $row['student_id'],
                $row['full_name'],
                $row['batch'],
                $row['roll_no'],
                $row['course_code'],
                $row['course_name'],
                $row['semester'],
                $row['theory_marks'],
                $row['practical_marks'],
                $row['total_marks'],
                $row['percentage'] ? number_format($row['percentage'], 2) . '%' : '',
                $row['grade'],
                $row['status'],
                $row['entered_date']
            ]);
        }
        
        fclose($output);
        exit;
        break;
        
    case 'logout':
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
        break;
        
    case 'downloadPDF':
        require_once('../tcpdf/tcpdf.php');
        
        $student_id = $_GET['student_id'];
        $semester = $_GET['semester'];
        
        // Fetch student data
        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        
        if (!$student) {
            die("Student not found");
        }
        
        // Fetch results
        $query = "
            SELECT 
                r.*,
                c.course_name,
                c.credit_hours,
                c.course_type,
                c.theory_full_mark,
                c.practical_full_mark
            FROM results r
            JOIN courses c ON r.course_code = c.course_code
            WHERE r.student_id = ? AND r.semester = ?
            ORDER BY c.course_code
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $student_id, $semester);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Create PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('TU Result System');
        $pdf->SetAuthor('Tribhuvan University');
        $pdf->SetTitle('Result - ' . $student_id);
        $pdf->SetSubject('Student Result');
        
        // Add a page
        $pdf->AddPage();
        
        // Header
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'TRIBHUVAN UNIVERSITY', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'STUDENT RESULT', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Student Information
        $pdf->SetFont('helvetica', '', 12);
        $info = "Student ID: " . $student_id . "\n";
        $info .= "Name: " . $student['full_name'] . "\n";
        $info .= "Semester: " . $semester . "\n";
        $info .= "Batch: " . $student['batch'] . "\n";
        $info .= "Program: BCA\n";
        $pdf->MultiCell(0, 10, $info, 0, 'L');
        $pdf->Ln(5);
        
        // Results Table
        $html = '<table border="1" cellpadding="5">
            <thead>
                <tr style="background-color:#f2f2f2;">
                    <th width="15%">Course Code</th>
                    <th width="35%">Course Name</th>
                    <th width="10%">Cr. Hr.</th>
                    <th width="10%">Theory</th>
                    <th width="10%">Practical</th>
                    <th width="10%">Total</th>
                    <th width="10%">Grade</th>
                </tr>
            </thead>
            <tbody>';
        
        $total_credit = 0;
        $total_points = 0;
        
        foreach ($results as $row) {
            $html .= '<tr>';
            $html .= '<td>' . $row['course_code'] . '</td>';
            $html .= '<td>' . $row['course_name'] . '</td>';
            $html .= '<td>' . $row['credit_hours'] . '</td>';
            $html .= '<td>' . ($row['theory_marks'] ? $row['theory_marks'] . '/' . $row['theory_full_mark'] : '-') . '</td>';
            $html .= '<td>' . ($row['practical_marks'] ? $row['practical_marks'] . '/' . $row['practical_full_mark'] : '-') . '</td>';
            $html .= '<td>' . $row['total_marks'] . '</td>';
            $html .= '<td>' . $row['grade'] . '</td>';
            $html .= '</tr>';
            
            $total_credit += $row['credit_hours'];
            // Calculate grade points
            if ($row['grade'] == 'A') $grade_points = 4.0;
            elseif ($row['grade'] == 'B') $grade_points = 3.0;
            elseif ($row['grade'] == 'C') $grade_points = 2.0;
            elseif ($row['grade'] == 'D') $grade_points = 1.0;
            elseif ($row['grade'] == 'E') $grade_points = 0.5;
            else $grade_points = 0;
            
            $total_points += $grade_points * $row['credit_hours'];
        }
        
        $gpa = $total_credit > 0 ? $total_points / $total_credit : 0;
        
        $html .= '</tbody></table>';
        $html .= '<br><br>';
        $html .= '<p><strong>GPA: ' . number_format($gpa, 2) . '</strong></p>';
        $html .= '<p><strong>Status: ' . ($gpa >= 2.0 ? 'PASS' : 'FAIL') . '</strong></p>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Output PDF
        $pdf->Output('result_' . $student_id . '_sem' . $semester . '.pdf', 'D');
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>