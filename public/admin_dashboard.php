<?php
session_start();
include '../config/db.php';
include '../public/header.php';

// Count total students
$studentCount = $conn->query("SELECT COUNT(*) AS count FROM student")->fetch_assoc()['count'];
$mentorCount = $conn->query("SELECT COUNT(*) AS count FROM mentor")->fetch_assoc()['count'];
$staffCount = $conn->query("SELECT COUNT(*) AS count FROM staff")->fetch_assoc()['count'];

// Fetch students with role 'student'
$students = $conn->query("SELECT id, full_name, username FROM users WHERE role = 'student'");
// Fetch mentor data by joining users and mentor tables
$stmt = $conn->prepare("
    SELECT users.id AS user_id, users.full_name, mentor.working_organization 
    FROM users 
    INNER JOIN mentor ON users.id = mentor.user_id 
    WHERE users.role = 'mentor'
");


// Execute the mentor query
$stmt->execute();
$result = $stmt->get_result();

$mentors = [];
while ($row = $result->fetch_assoc()) {
    $mentors[] = $row;
}
$stmt->close();
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_mentor'])) {
    $studentId = $_POST['student_id'];
    $mentorId = $_POST['mentor_id'];

    // Check if the student already has a mentor assigned
    $checkStmt = $conn->prepare("SELECT mentor_id FROM student WHERE user_id = ?");
    $checkStmt->bind_param("i", $studentId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // Record exists, update the mentor assignment
        $stmt = $conn->prepare("UPDATE student SET mentor_id = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $mentorId, $studentId);
    } else {
        // Record does not exist, insert new mentor assignment
        $stmt = $conn->prepare("INSERT INTO student (user_id, mentor_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $studentId, $mentorId);
    }

    // Execute the statement
    if ($stmt->execute()) {
        echo "Mentor assigned successfully!";
    } else {
        echo "Error assigning mentor: " . $stmt->error;
    }

    // Close statements
    $checkStmt->close();
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
      /* Global styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f5f5f5;
    color: #333;
    
}

.dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background-color: #fff;
    
}

/* Header */
.header {
    text-align: center;
    padding: 10px 0;
    font-size: 1.5em;
    font-weight: bold;
    color: #444;
}

/* Metric boxes */
.metrics {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
}

.metric-box {
    width: 200px;
    padding: 20px;
    margin: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    text-align: center;
    background-color: #fafafa;
    transition: box-shadow 0.3s ease;
}

.metric-box:hover {
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

/* Tables */
.tables {
    margin: 20px 0;
    margin-bottom:50px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    background-color: #fff;
}

th, td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
}

th {
    background-color: #f2f2f2;
    font-weight: bold;
}

/* Sidebar for navigation */
.sidebar {
    width: 200px;
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    background-color: #333;
    padding-top: 20px;
    color: #fff;
}

.sidebar a {
    padding: 10px 20px;
    text-decoration: none;
    color: #ddd;
    display: block;
    transition: background-color 0.3s;
}

.sidebar a:hover {
    background-color: #555;
}

/* Sections */
.section {
    display: none; /* Hide sections by default */
}

.section.active {
    display: block; /* Show active section */
}

/* Buttons */
button {
    padding: 10px 20px;
    margin-top: 10px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #0056b3;
}

/* Footer */


/* Responsive adjustments */
@media (max-width: 768px) {
    .metrics {
        flex-direction: column;
        align-items: center;
    }

    .metric-box, table, th, td {
        width: 100%;
    }

    .sidebar {
        position: relative;
        width: 100%;
    }
}

    </style>
</head>
<body>

<div class="dashboard">
    <div class="header">
        <h1>Welcome to the Admin Dashboard</h1>
        

        <!-- Section Toggle Buttons -->
        <button onclick="showSection('assignMentor')">Assign Mentors</button>
        <button onclick="showSection('manage')">Manage</button>
    </div>

   

    <!-- Assign Mentor Section -->
<div id="assignMentor" class="section">
    <h2>Assign a Mentor to a Student</h2>
    <form method="POST">
        
        <!-- Select Student -->
        <label for="student">Select a Student:</label>
        <select name="student_id" id="student" required>
            <option value="">-- Select Student --</option>
            <?php
            // Fetch students from the `users` table where role is 'student'
            foreach ($students as $student): ?>
                <option value="<?= htmlspecialchars($student['id']) ?>">
                    <?= htmlspecialchars($student['full_name']) ?> (<?= htmlspecialchars($student['username']) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Select Mentor -->
        <label for="mentor">Select a Mentor:</label>
        <select name="mentor_id" id="mentor" required>
            <option value="">-- Select Mentor --</option>
            <?php
            // Fetch mentors
            foreach ($mentors as $mentor): ?>
                <option value="<?= htmlspecialchars($mentor['user_id']) ?>">
                    <?= htmlspecialchars($mentor['full_name']) ?> - <?= htmlspecialchars($mentor['working_organization']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" name="assign_mentor">Assign Mentor</button>
    </form>
</div>


    <!-- Manage Section -->
    <div id="manage" class="section">
    <p>Manage students, mentors,staf and monitor key statistics.</p>
         <!-- Key Metrics -->
    <div class="metrics">
        <div class="metric-box">
            <h2>Total Students</h2>
            <p><?php echo $studentCount; ?></p>
        </div>
        <div class="metric-box">
            <h2>Total Mentors</h2>
            <p><?php echo $mentorCount; ?></p>
        </div>
        <div class="metric-box">
            <h2>Total Staff</h2>
            <p><?php echo $staffCount; ?></p>
        </div>
    </div>
    
        <!-- Students Table -->
        <h2>Registered Students</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Registration No</th>
                <th>Academic year</th>
                <th>Phone No</th>
                <th>Email</th>
            </tr>
            <?php
            $query = "SELECT student.student_id, student.reg_no, student.academic_year, student.phone_no, student.email_id, users.full_name 
                      FROM student 
                      INNER JOIN users ON student.user_id = users.id";
            $result = $conn->query($query);
            if ($result->num_rows > 0) {
                while ($student = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$student['student_id']}</td>
                            <td>{$student['full_name']}</td>
                            <td>{$student['reg_no']}</td>
                            <td>{$student['academic_year']}</td>
                            <td>{$student['phone_no']}</td>
                            <td>{$student['email_id']}</td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No students found.</td></tr>";
            }
            ?>
        </table>

        <!-- Mentors Table -->
        <h2>Registered Mentors</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Working Organization</th>
            </tr>
            <?php
            $query = "SELECT mentor.mentor_id, mentor.phone_no, mentor.email_id, mentor.working_organization, users.full_name 
                      FROM mentor 
                      INNER JOIN users ON mentor.user_id = users.id";
            $result = $conn->query($query);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['mentor_id']}</td>
                            <td>{$row['full_name']}</td>
                            <td>{$row['email_id']}</td>
                            <td>{$row['phone_no']}</td>
                            <td>{$row['working_organization']}</td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No mentors found.</td></tr>";
            }
            ?>
        </table>

        <!-- Staff Table -->
        <h2>Registered Staff</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone No</th>
                <th>Email</th>
            </tr>
            <?php
            $query = "SELECT staff.staff_id, staff.phone_no, staff.email_id, users.full_name 
                      FROM staff 
                      INNER JOIN users ON staff.user_id = users.id";
            $result = $conn->query($query);
            if ($result->num_rows > 0) {
                while ($staff = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$staff['staff_id']}</td>
                            <td>{$staff['full_name']}</td>
                            <td>{$staff['phone_no']}</td>
                            <td>{$staff['email_id']}</td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No staff found.</td></tr>";
            }
            ?>
        </table>
    </div>
</div>

<script>
    // Function to toggle section visibility
    function showSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.section').forEach(section => section.style.display = 'none');
        // Show the selected section
        document.getElementById(sectionId).style.display = 'block';
    }

window.addEventListener('load', function() {
    const headerHeight = document.querySelector('header').offsetHeight;
    document.body.style.paddingTop = headerHeight + 'px';
});
</script>
<?php include '../public/footer.php'; ?>
</body>
</html>
