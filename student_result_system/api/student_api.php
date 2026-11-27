<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$db = new Database();
$conn = $db->getConnection();



// Debug: Log received data
error_log("Student API - Received POST data: " . print_r($_POST, true));

$action = $_POST['action'] ?? '';

// Debug: Log action
error_log("Student API - Action: " . $action);

switch ($action) {
    case 'getResult':
        $studentId = $_POST['student_id'] ?? '';
        $dob = $_POST['dob'] ?? '';
        $semester = $_POST['semester'] ?? '';
        
        // Debug: Log received values
        error_log("Student API - getResult called with: student_id=$studentId, dob=$dob, semester=$semester");
        
        if (empty($studentId) || empty($dob) || empty($semester)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }
        
        // Check if student exists
        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ? AND dob = ?");
        $stmt->bind_param("ss", $studentId, $dob);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Student not found or invalid credentials']);
            exit;
        }
        
        $student = $result->fetch_assoc();
        
        // Get student's results for the semester
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
        $stmt->bind_param("si", $studentId, $semester);
        $stmt->execute();
        $results = $stmt->get_result();
        
        $subjects = [];
        while ($row = $results->fetch_assoc()) {
            $subjects[] = $row;
        }
        
        // If no results found, get all courses for the semester
        if (empty($subjects)) {
            $query = "SELECT * FROM courses WHERE semester = ? AND status = 'Active'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $semester);
            $stmt->execute();
            $courses = $stmt->get_result();
            
            while ($course = $courses->fetch_assoc()) {
                $subjects[] = array_merge($course, [
                    'theory_marks' => null,
                    'practical_marks' => null,
                    'total_marks' => null,
                    'percentage' => null,
                    'grade' => 'N/A',
                    'status' => 'Not Entered'
                ]);
            }
        }
        
        $response = [
            'success' => true,
            'data' => [
                'student_id' => $student['student_id'],
                'full_name' => $student['full_name'],
                'dob' => $student['dob'],
                'semester' => $semester,
                'batch' => $student['batch'],
                'program' => $student['program'],
                'faculty' => $student['faculty'],
                'roll_no' => $student['roll_no'],
                'subjects' => $subjects
            ]
        ];
        
        echo json_encode($response);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
}
?>