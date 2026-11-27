// Modal Functions
function showStudentLogin() {
    document.getElementById('studentLoginModal').style.display = 'block';
}

function closeStudentLogin() {
    document.getElementById('studentLoginModal').style.display = 'none';
    document.getElementById('studentResult').style.display = 'none';
    document.getElementById('studentResult').innerHTML = '';
}

function showTeacherLogin() {
    document.getElementById('teacherLoginModal').style.display = 'block';
}

function closeTeacherLogin() {
    document.getElementById('teacherLoginModal').style.display = 'none';
}

function showAdminLogin() {
    document.getElementById('adminLoginModal').style.display = 'block';
}

function closeAdminLogin() {
    document.getElementById('adminLoginModal').style.display = 'none';
}

function openEditModal(student) {
    document.getElementById('modalTitle').textContent = 'Edit Student';
    document.getElementById('action').value = 'update_student';
    document.getElementById('studentId').value = student.id;
    document.getElementById('student_id').value = student.student_id;
    document.getElementById('full_name').value = student.full_name;
    document.getElementById('dob').value = student.dob;
    document.getElementById('email').value = student.email;
    document.getElementById('phone').value = student.phone;
    document.getElementById('semester').value = student.semester;
    document.getElementById('batch').value = student.batch;
    document.getElementById('department').value = student.department || student.faculty || ''; // Handle both column names
    document.getElementById('program').value = student.program;
    document.getElementById('roll_no').value = student.roll_no;
    document.getElementById('studentModal').style.display = 'block';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = ['studentLoginModal', 'teacherLoginModal', 'adminLoginModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target == modal) {
            modal.style.display = 'none';
            if (modalId === 'studentLoginModal') {
                document.getElementById('studentResult').style.display = 'none';
                document.getElementById('studentResult').innerHTML = '';
            }
        }
    });
}

// Student Result Check
function checkStudentResult(event) {
    event.preventDefault();
    
    const studentId = document.getElementById('student_id').value;
    const dob = document.getElementById('dob').value;
    const semester = document.getElementById('semester').value;
    
    if (!studentId || !dob || !semester) {
        alert('Please fill all fields');
        return false;
    }
    
    const resultDiv = document.getElementById('studentResult');
    resultDiv.innerHTML = '<div class="loading"></div><p>Loading result...</p>';
    resultDiv.style.display = 'block';
    
    // Create form data
    const formData = new FormData();
    formData.append('action', 'getResult');
    formData.append('student_id', studentId);
    formData.append('dob', dob);
    formData.append('semester', semester);
    
    $.ajax({
        url: 'api/student_api.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            console.log('Response:', response); // Debug log
            if (response.success) {
                displayStudentResult(response.data);
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> ${response.message}
                    </div>
                `;
            }
        },
        error: function(xhr, status, error) {
            console.log('Error:', xhr.responseText); // Debug log
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error loading result. Please try again.
                </div>
            `;
        }
    });
}

function displayStudentResult(data) {
    const resultDiv = document.getElementById('studentResult');
    
    let resultHTML = `
        <div class="result-header">
            <h3><i class="fas fa-graduation-cap"></i> Result Details</h3>
            <p>Semester: ${data.semester}</p>
        </div>
        
        <div class="student-info">
            <div>
                <p><strong>Student ID:</strong> ${data.student_id}</p>
                <p><strong>Name:</strong> ${data.full_name}</p>
                <p><strong>Date of Birth:</strong> ${data.dob}</p>
            </div>
            <div>
                <p><strong>Program:</strong> ${data.program}</p>
                <p><strong>Batch:</strong> ${data.batch}</p>
                <p><strong>Faculty:</strong> ${data.faculty}</p>
            </div>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>SN</th>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Cr. Hr.</th>
                        <th>Theory</th>
                        <th>Practical</th>
                        <th>Total</th>
                        <th>Percentage</th>
                        <th>Grade</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    let totalCredit = 0;
    let totalPoints = 0;
    
    data.subjects.forEach((subject, index) => {
        totalCredit += parseFloat(subject.credit_hours);
        
        // Calculate grade points (4.0 scale)
        let gradePoints = 0;
        if (subject.grade === 'A') gradePoints = 4.0;
        else if (subject.grade === 'B') gradePoints = 3.0;
        else if (subject.grade === 'C') gradePoints = 2.0;
        else if (subject.grade === 'D') gradePoints = 1.0;
        else if (subject.grade === 'E') gradePoints = 0.5;
        else gradePoints = 0;
        
        totalPoints += gradePoints * parseFloat(subject.credit_hours);
        
        resultHTML += `
            <tr>
                <td>${index + 1}</td>
                <td>${subject.course_code}</td>
                <td>${subject.course_name}</td>
                <td>${subject.credit_hours}</td>
                <td>${subject.theory_marks || '-'}/${subject.theory_full}</td>
                <td>${subject.practical_marks || '-'}/${subject.practical_full}</td>
                <td>${subject.total_marks || '-'}</td>
                <td>${subject.percentage ? subject.percentage.toFixed(2) + '%' : '-'}</td>
                <td class="grade-${subject.grade.toLowerCase()}">${subject.grade}</td>
                <td class="${subject.status === 'Pass' ? 'status-pass' : 'status-fail'}">
                    ${subject.status}
                </td>
            </tr>
        `;
    });
    
    const gpa = totalPoints / totalCredit;
    let finalStatus = 'Pass';
    if (gpa < 2.0 || data.subjects.some(sub => sub.status === 'Fail')) {
        finalStatus = 'Fail';
    }
    
    resultHTML += `
                </tbody>
            </table>
        </div>
        
        <div class="summary">
            <div class="summary-card">
                <h4><i class="fas fa-calculator"></i> Result Summary</h4>
                <div class="summary-grid">
                    <div>
                        <p><strong>Total Credit Hours:</strong> ${totalCredit}</p>
                        <p><strong>Total Points:</strong> ${totalPoints.toFixed(2)}</p>
                    </div>
                    <div>
                        <p><strong>GPA:</strong> ${gpa.toFixed(2)}</p>
                        <p><strong>Final Status:</strong> 
                            <span class="${finalStatus === 'Pass' ? 'status-pass' : 'status-fail'}">
                                ${finalStatus}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="actions">
                <button class="btn btn-primary" onclick="printResult()">
                    <i class="fas fa-print"></i> Print Result
                </button>
                <button class="btn" onclick="downloadPDF('${data.student_id}', ${data.semester})">
                    <i class="fas fa-download"></i> Download PDF
                </button>
            </div>
        </div>
    `;
    
    resultDiv.innerHTML = resultHTML;
}

// Teacher Login
function teacherLogin(event) {
    event.preventDefault();
    
    const teacherId = document.getElementById('teacher_id').value;
    const password = document.getElementById('teacher_password').value;
    
    // Create form data
    const formData = new FormData();
    formData.append('action', 'teacherLogin');
    formData.append('teacher_id', teacherId);
    formData.append('password', password);
    
    $.ajax({
        url: 'api/login.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            console.log('Login Response:', response); // Debug log
            if (response.success) {
                window.location.href = 'teacher/dashboard.php';
            } else {
                alert(response.message);
            }
        },
        error: function(xhr, status, error) {
            console.log('Login Error:', xhr.responseText); // Debug log
            alert('Login failed. Please try again.');
        }
    });
}

// Admin Login
function adminLogin(event) {
    event.preventDefault();
    
    const username = document.getElementById('admin_username').value;
    const password = document.getElementById('admin_password').value;
    
    // Create form data
    const formData = new FormData();
    formData.append('action', 'adminLogin');
    formData.append('username', username);
    formData.append('password', password);
    
    $.ajax({
        url: 'api/login.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            console.log('Admin Login Response:', response); // Debug log
            if (response.success) {
                window.location.href = 'admin/dashboard.php';
            } else {
                alert(response.message);
            }
        },
        error: function(xhr, status, error) {
            console.log('Admin Login Error:', xhr.responseText); // Debug log
            alert('Login failed. Please try again.');
        }
    });
}

// Print Result
function printResult() {
    window.print();
}

// Download PDF
function downloadPDF(studentId, semester) {
    window.open(`api/result_api.php?action=downloadPDF&student_id=${studentId}&semester=${semester}`, '_blank');
}

// Additional CSS for result display
const style = document.createElement('style');
style.textContent = `
    .alert {
        padding: 1rem;
        border-radius: 5px;
        margin: 1rem 0;
    }
    .alert-danger {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }
    .alert-success {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }
    .summary {
        margin-top: 2rem;
        padding: 1.5rem;
        background: #f8f9fa;
        border-radius: 10px;
    }
    .summary-card {
        background: white;
        padding: 1.5rem;
        border-radius: 5px;
        margin-bottom: 1.5rem;
    }
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    .actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
`;
document.head.appendChild(style);