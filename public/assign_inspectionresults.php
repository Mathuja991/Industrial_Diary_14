<?php
// Database connection
include '../config/db.php';
include '../public/header.php';

$students = []; // Initialize student list
$selected_student_results = null;
$student_id = null; // Initialize student ID
$success_message = ''; // To store success message for marks submission
$error_message = ''; // To store error messages

// Fetch all students from the 'users' table where role is 'student'
$query = "SELECT id, username, full_name FROM users WHERE role = 'student'";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
} else {
    echo "Error retrieving students: " . $conn->error; // Error handling
}

// Check if a student username was submitted
if (isset($_POST['selected_student'])) {
    $username = $_POST['selected_student'];

    // Retrieve student ID from the users table based on the selected username
    $student_query = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($student_query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $student_result = $stmt->get_result();
    
    if ($student_result->num_rows > 0) {
        $student_data = $student_result->fetch_assoc();
        $student_id = $student_data['id'];

        // Retrieve the inspection report from inspection_reports table based on student ID
        $report_query = "SELECT inspection_date, inspector_name, supervisor_remarks, student_remarks FROM inspection_reports WHERE student_id = ?";
        $report_stmt = $conn->prepare($report_query);
        $report_stmt->bind_param("i", $student_id);
        $report_stmt->execute();
        $selected_student_results = $report_stmt->get_result()->fetch_assoc();
    }
}

// Check if inspection marks were submitted
if (isset($_POST['submit_results'])) {
    // Retrieve student ID from POST data
    $student_id = $_POST['student_id'];
    $inspection_marks = $_POST['inspection_marks'];

    // Check if marks already exist for this student
    $check_query = "SELECT id FROM inspection_results WHERE student_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $student_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error_message = "Error: Marks have already been assigned for this student.";
    } else {
        // Insert the inspection marks into the inspection_results table
        $insert_query = "INSERT INTO inspection_results (student_id, inspection_marks) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("ii", $student_id, $inspection_marks);
        
        if ($insert_stmt->execute()) {
            $success_message = "Results submitted successfully.";
        } else {
            $error_message = "Error submitting results: " . $conn->error; // Error handling
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inspection Reports Results</title>
    <link rel="stylesheet" href="path/to/your/styles.css"> <!-- Link to external CSS file -->
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4; /* Light background color */
            margin: 0;
            padding: 20px;
        }

        h2 {
            color: #333; /* Darker color for headings */
        }

        /* Section Styling */
        .section {
            background-color: #fff; /* White background for sections */
            border-radius: 8px; /* Rounded corners */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Soft shadow */
            padding: 20px;
            margin-bottom: 20px; /* Spacing between sections */
        }

        /* Form Styles */
        form {
            width: 80%;
            display: flex;
            flex-direction: column; /* Stack form elements vertically */
        }

        label {
            margin-bottom: 5px; /* Spacing between label and input */
            font-weight: bold; /* Bold labels */
        }

        select, input[type="number"], button {
            padding: 10px; /* Padding inside form elements */
            margin-bottom: 15px; /* Spacing between elements */
            border: 1px solid #ccc; /* Light border */
            border-radius: 5px; /* Rounded corners */
            font-size: 16px; /* Font size for inputs */
        }

        /* Button Styles */
        button {
            background-color: #007bff; /* Bootstrap primary color */
            color: #fff; /* White text on button */
            cursor: pointer; /* Pointer cursor on hover */
            border: none; /* Remove default border */
            transition: background-color 0.3s; /* Smooth transition for hover effect */
        }

        button:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }

        /* Table Styles */
        table {
            width: 80%; /* Full width for tables */
            border-collapse: collapse; /* Merge table borders */
            margin-bottom: 20px; /* Spacing below the table */
        }

        th, td {
            padding: 10px; /* Padding inside table cells */
            border: 1px solid #ddd; /* Light border for cells */
            text-align: left; /* Left align text */
        }

        th {
            background-color: #f2f2f2; /* Light gray background for headers */
        }

        /* Alert Styles */
        .alert {
            padding: 15px; /* Padding inside alert messages */
            margin-bottom: 20px; /* Spacing below alerts */
            border: 1px solid transparent; /* Initial border */
            border-radius: 5px; /* Rounded corners */
        }

        .alert-success {
            color: #155724; /* Green text */
            background-color: #d4edda; /* Light green background */
            border-color: #c3e6cb; /* Border color */
        }

        .alert-error {
            color: #721c24; /* Red text */
            background-color: #f8d7da; /* Light red background */
            border-color: #f5c6cb; /* Border color */
        }
    </style>
</head>
<body>
    <div class="section" id="assignResultsSection">
        <h2>Assign Marks to Inspection Reports</h2>

        <!-- Display Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Student Selection Form -->
        <form method="POST" id="studentForm">
            <label for="student_id">Select Student (Username):</label>
            <select id="student_id" name="selected_student" required>
                <option value="" disabled selected>Select a student</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?php echo htmlspecialchars($student['username']); ?>" <?php echo isset($_POST['selected_student']) && $_POST['selected_student'] == $student['username'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($student['username']) . " - " . htmlspecialchars($student['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" id="showReportButton">Show Inspection Report</button>
        </form>

        <!-- Display Inspection Report if Available -->
        <?php if ($selected_student_results): ?>
            <div id="inspectionReport">
                <h3>Inspection Report for Selected Student</h3>
                <table>
                    <tr><th>Inspection Date</th><td><?php echo htmlspecialchars($selected_student_results['inspection_date']); ?></td></tr>
                    <tr><th>Inspector Name</th><td><?php echo htmlspecialchars($selected_student_results['inspector_name']); ?></td></tr>
                    <tr><th>Supervisor Remarks</th><td><?php echo htmlspecialchars($selected_student_results['supervisor_remarks']); ?></td></tr>
                    <tr><th>Student Remarks</th><td><?php echo htmlspecialchars($selected_student_results['student_remarks']); ?></td></tr>
                </table>

                <h3>Enter Results</h3>
                <form method="POST">
                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
                    <label for="inspection_marks">Inspection Report Marks:</label>
                    <input type="number" name="inspection_marks" required>
                    <button type="submit" name="submit_results">Submit Results</button>
                </form>
            </div>
        <?php else: ?>
            <p>No inspection report available for the selected student.</p>
        <?php endif; ?>
    </div>
    <div>
    <button type="button" onclick="window.history.back();">Back</button>
    </div>

    <script>
       window.addEventListener('load', function() {
    const headerHeight = document.querySelector('header').offsetHeight;
    document.body.style.paddingTop = headerHeight + 'px';
});
    </script>
</body>
</html>
