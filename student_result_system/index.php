<?php
session_start();
require_once 'config/database.php';
$db = new Database();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Result Management System - TU BCA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1><i class="fas fa-graduation-cap"></i> TU Result System</h1>
                <p>Tribhuvan University - BCA Program</p>
            </div>
            <nav>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <section class="hero" id="home">
                <div class="hero-content">
                    <h2>Student Result Management System</h2>
                    <p>BCA 4th Semester - Tribhuvan University</p>
                    <div class="features">
                        <div class="feature-card">
                            <i class="fas fa-user-graduate"></i>
                            <h3>Student Login</h3>
                            <p>View your results using Student ID and Date of Birth</p>
                            <button class="btn" onclick="showStudentLogin()">Access Result</button>
                        </div>
                        <div class="feature-card">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <h3>Teacher Login</h3>
                            <p>Enter and manage student marks</p>
                            <button class="btn" onclick="showTeacherLogin()">Teacher Login</button>
                        </div>
                        <div class="feature-card">
                            <i class="fas fa-user-shield"></i>
                            <h3>Admin Login</h3>
                            <p>System administration and management</p>
                            <button class="btn" onclick="showAdminLogin()">Admin Login</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Student Login Form -->
            <div id="studentLoginModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeStudentLogin()">&times;</span>
                    <h2><i class="fas fa-user-graduate"></i> Student Result Access</h2>
                    <form id="studentLoginForm" onsubmit="return checkStudentResult(event)">
                        <div class="form-group">
                            <label for="student_id"><i class="fas fa-id-card"></i> Student ID</label>
                            <input type="text" id="student_id" name="student_id" required placeholder="Enter your student ID">
                        </div>
                        <div class="form-group">
                            <label for="dob"><i class="fas fa-calendar-alt"></i> Date of Birth</label>
                            <input type="date" id="dob" name="dob" required>
                        </div>
                        <div class="form-group">
                            <label for="semester"><i class="fas fa-layer-group"></i> Semester</label>
                            <select id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="1">First Semester</option>
                                <option value="2">Second Semester</option>
                                <option value="3">Third Semester</option>
                                <option value="4" selected>Fourth Semester</option>
                                <option value="5">Fifth Semester</option>
                                <option value="6">Sixth Semester</option>
                                <option value="7">Seventh Semester</option>
                                <option value="8">Eighth Semester</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> View Result
                        </button>
                    </form>
                    <div id="studentResult" class="result-container" style="display: none;"></div>
                </div>
            </div>

            <!-- Teacher Login Form -->
            <div id="teacherLoginModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeTeacherLogin()">&times;</span>
                    <h2><i class="fas fa-chalkboard-teacher"></i> Teacher Login</h2>
                    <form id="teacherLoginForm" onsubmit="return teacherLogin(event)">
                        <div class="form-group">
                            <label for="teacher_id"><i class="fas fa-user-tie"></i> Teacher ID</label>
                            <input type="text" id="teacher_id" name="teacher_id" required placeholder="Enter teacher ID">
                        </div>
                        <div class="form-group">
                            <label for="teacher_password"><i class="fas fa-lock"></i> Password</label>
                            <input type="password" id="teacher_password" name="password" required placeholder="Enter password">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </form>
                </div>
            </div>

            <!-- Admin Login Form -->
            <div id="adminLoginModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeAdminLogin()">&times;</span>
                    <h2><i class="fas fa-user-shield"></i> Admin Login</h2>
                    <form id="adminLoginForm" onsubmit="return adminLogin(event)">
                        <div class="form-group">
                            <label for="admin_username"><i class="fas fa-user"></i> Username</label>
                            <input type="text" id="admin_username" name="username" required placeholder="Enter username">
                        </div>
                        <div class="form-group">
                            <label for="admin_password"><i class="fas fa-lock"></i> Password</label>
                            <input type="password" id="admin_password" name="password" required placeholder="Enter password">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </form>
                </div>
            </div>

            <section class="about" id="about">
                <h2>About the System</h2>
                <div class="about-content">
                    <div class="about-card">
                        <i class="fas fa-university"></i>
                        <h3>Tribhuvan University</h3>
                        <p>Official result management system for BCA 4th Semester as per TU guidelines.</p>
                    </div>
                    <div class="about-card">
                        <i class="fas fa-chart-bar"></i>
                        <h3>Result Format</h3>
                        <p>Theory: 60 Full, 24 Pass | Practical: 40 Full, 16 Pass | Grade based on percentage</p>
                    </div>
                    <div class="about-card">
                        <i class="fas fa-print"></i>
                        <h3>PDF Export</h3>
                        <p>Generate and print individual and batch results in PDF format.</p>
                    </div>
                </div>
            </section>

            <section class="contact" id="contact">
                <h2>Contact Information</h2>
                <div class="contact-info">
                    <p><i class="fas fa-envelope"></i> Email: results@tu.edu.np</p>
                    <p><i class="fas fa-phone"></i> Phone: +977-1-1234567</p>
                    <p><i class="fas fa-map-marker-alt"></i> Address: Tribhuvan University, Kathmandu, Nepal</p>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Tribhuvan University - Student Result Management System. All rights reserved.</p>
            <p>Developed for BCA 4th Semester Project</p>
        </footer>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>