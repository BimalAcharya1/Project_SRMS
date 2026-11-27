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

// Get statistics
$stats = [
    'total_students' => $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'],
    'total_teachers' => $conn->query("SELECT COUNT(*) as count FROM teachers")->fetch_assoc()['count'],
    'total_courses' => $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'],
    'total_results' => $conn->query("SELECT COUNT(*) as count FROM results")->fetch_assoc()['count'],
    'total_exams' => $conn->query("SELECT COUNT(*) as count FROM exams")->fetch_assoc()['count'], 
    // Added exam stats
];

// Get recent students
$recent_students = $conn->query("SELECT * FROM students ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TU Result System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard {
            padding: 20px;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .stat-card.students i { color: #3498db; }
        .stat-card.teachers i { color: #2ecc71; }
        .stat-card.courses i { color: #e74c3c; }
        .stat-card.results i { color: #f39c12; }
        .stat-card.exams i { color: #6159b6; } /* Added exam color */
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .admin-nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 20px 0;
        }
        .recent-table {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        /* Added for the new button */
        .admin-nav a.exam-btn {
            background: linear-gradient(135deg, #373cb6, #373cb6);
        }
        .admin-nav a.exam-btn:hover {
            background: linear-gradient(135deg, #373cb6, #373cb6);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <div>
                <h1><i class="fas fa-user-shield"></i> Admin Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</p>
            </div>
            <a href="logout.php" class="logout-btn">
               <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <div class="admin-nav">
            <a href="manage_students.php" class="btn"><i class="fas fa-users"></i> Manage Students</a>
            <a href="manage_teachers.php" class="btn"><i class="fas fa-chalkboard-teacher"></i> Manage Teachers</a>
            <a href="manage_courses.php" class="btn"><i class="fas fa-book"></i> Manage Courses</a>
            <a href="manage_subjects.php" class="btn"><i class="fas fa-graduation-cap"></i> Manage Subjects</a>
            <!-- Added Manage Exam link -->
            <a href="manage_exams.php" class="btn exam-btn"><i class="fas fa-clipboard-list"></i> Manage Exams</a>
            <a href="manage_results.php" class="btn"><i class="fas fa-chart-bar"></i> Manage Results</a>
            <a href="batch_results.php" class="btn"><i class="fas fa-file-pdf"></i> Batch Results PDF</a>
        </div>

        <div class="stats-cards">
            <div class="stat-card students">
                <i class="fas fa-user-graduate"></i>
                <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                <h3>Total Students</h3>
            </div>
            <div class="stat-card teachers">
                <i class="fas fa-chalkboard-teacher"></i>
                <div class="stat-number"><?php echo $stats['total_teachers']; ?></div>
                <h3>Total Teachers</h3>
            </div>
            <div class="stat-card courses">
                <i class="fas fa-book"></i>
                <div class="stat-number"><?php echo $stats['total_courses']; ?></div>
                <h3>Total Courses</h3>
            </div>
            <div class="stat-card exams">
                <i class="fas fa-clipboard-list"></i>
                <div class="stat-number"><?php echo $stats['total_exams']; ?></div>
                <h3>Total Exams</h3>
            </div>
            <div class="stat-card results">
                <i class="fas fa-chart-bar"></i>
                <div class="stat-number"><?php echo $stats['total_results']; ?></div>
                <h3>Results Entered</h3>
            </div>
        </div>

        <div class="recent-table">
            <h3><i class="fas fa-history"></i> Recently Added Students</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Date of Birth</th>
                            <th>Semester</th>
                            <th>Batch</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['dob']); ?></td>
                            <td><?php echo htmlspecialchars($student['semester']); ?></td>
                            <td><?php echo htmlspecialchars($student['batch']); ?></td>
                            <td>
                                <a href="manage_students.php?action=edit&id=<?php echo $student['id']; ?>" class="btn">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>