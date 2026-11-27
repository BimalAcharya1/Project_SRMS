<?php
class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "student_result_system";
    public $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->username, $this->password);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

        $this->initializeDatabase();
    }

    private function initializeDatabase() {
        $this->conn->query("CREATE DATABASE IF NOT EXISTS {$this->database}");
        $this->conn->select_db($this->database);
        $this->createTables();
        $this->insertDefaultData();
    }

    private function createTables() {

        /* ================= ADMINS ================= */
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                full_name VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        /* ================= TEACHERS ================= */
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS teachers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                teacher_id VARCHAR(20) UNIQUE NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100),
                password VARCHAR(255) NOT NULL,
                phone VARCHAR(15),
                department VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        /* ================= STUDENTS ================= */
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS students (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id VARCHAR(20) UNIQUE NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                dob DATE,
                email VARCHAR(100),
                phone VARCHAR(15),
                semester INT DEFAULT 1,
                batch VARCHAR(10),
                faculty VARCHAR(50),
                program VARCHAR(50),
                roll_no INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        /* ================= SUBJECTS ================= */
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS subjects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                subject_code VARCHAR(20) UNIQUE NOT NULL,
                subject_name VARCHAR(100) NOT NULL,
                department VARCHAR(100),
                semester INT NOT NULL,
                credit_hours INT NOT NULL,
                subject_type VARCHAR(50),
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        /* ================= COURSES ================= */
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS courses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_code VARCHAR(20) UNIQUE NOT NULL,
                course_name VARCHAR(200) NOT NULL,
                credit_hours DECIMAL(3,1),
                semester INT NOT NULL,
                course_type ENUM('Theory','Practical','Both') DEFAULT 'Theory',
                theory_full_mark INT DEFAULT 60,
                theory_pass_mark INT DEFAULT 24,
                practical_full_mark INT DEFAULT 40,
                practical_pass_mark INT DEFAULT 16,
                status ENUM('Active','Inactive') DEFAULT 'Active'
            )
        ");

        /* ================= EXAMS ================= */
        $this->conn->query("DROP TABLE IF EXISTS exams");
        $this->conn->query("
            CREATE TABLE exams (
                id INT AUTO_INCREMENT PRIMARY KEY,
                exam_name VARCHAR(100) NOT NULL,
                exam_code VARCHAR(50) UNIQUE NOT NULL,
                subject_id INT,
                course_id INT,
                semester INT,
                exam_type ENUM('regular','back','improvement','practical','term_test') DEFAULT 'regular',
                max_marks DECIMAL(5,2) DEFAULT 100.00,
                passing_marks DECIMAL(5,2) DEFAULT 40.00,
                exam_date DATE NOT NULL,
                start_time TIME,
                end_time TIME,
                duration_minutes INT DEFAULT 180,
                status ENUM('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        /* ================= RESULTS ================= */
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS results (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id VARCHAR(20),
                course_code VARCHAR(20),
                semester INT,
                batch VARCHAR(10),
                theory_marks DECIMAL(5,2),
                practical_marks DECIMAL(5,2),
                total_marks DECIMAL(5,2),
                percentage DECIMAL(5,2),
                grade VARCHAR(2),
                status ENUM('Pass','Fail','Not Entered') DEFAULT 'Not Entered',
                entered_by INT,
                entered_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_result (student_id, course_code, semester)
            )
        ");
    }

    private function insertDefaultData() {

        /* ===== DEFAULT ADMIN ===== */
        $check = $this->conn->query("SELECT id FROM admins WHERE username='admin'");
        if ($check->num_rows == 0) {
            $password = password_hash("admin123", PASSWORD_DEFAULT);
            $this->conn->query("
                INSERT INTO admins (username, password, email, full_name)
                VALUES ('admin', '$password', 'admin@result.edu.np', 'System Administrator')
            ");
        }

        /* ===== SAMPLE COURSE ===== */
        $this->conn->query("
            INSERT IGNORE INTO courses
            (course_code, course_name, credit_hours, semester, course_type)
            VALUES ('CSC-251', 'Web Technology', 3.0, 4, 'Both')
        ");
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>
