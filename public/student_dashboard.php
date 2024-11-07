<?php
session_start();
include '../config/db.php';
include '../public/header.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/logout.php');
    exit();
}





$diary_entries = [];
$month_selected = false;
$overall_report_submitted = false;
$inspection_reports = [];
$progress_reports = [];  // New variable for progress reports

// Handle Overall Process Report Submission (for students only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'student' && isset($_POST['overall_report'])) {
    $student_id = $_SESSION['user_id'];
    $summary = $_POST['summary'];
    $challenges = $_POST['challenges'];
    $improvements = $_POST['improvements'];

    // Check if overall report already exists
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM overall_reports WHERE student_id = ?");
    $checkStmt->bind_param("i", $student_id);
    $checkStmt->execute();
    $checkStmt->bind_result($count);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($count > 0) {
        echo "<script>alert('Overall Process Report has already been submitted.');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO overall_reports (student_id, summary, challenges, improvements) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $student_id, $summary, $challenges, $improvements);

        if ($stmt->execute()) {
            $overall_report_submitted = true;
            echo "<script>alert('Overall Process Report submitted successfully.');</script>";
        } else {
            echo "<script>alert('Failed to submit the report. Please try again.');</script>";
        }
    }
}





if (isset($_POST['submit_diary'])) {
    $user_id = $_SESSION['user_id']; // Replace with the logged-in user's ID if using a login system
    $week_number = $_POST['week_number'];
    $report = $_POST['report'];
    $month = $_POST['month']; // Get selected month
    $year = $_POST['year'];   // Get selected year

    // Debug: Check if data is retrieved correctly
    

    // Check if an entry for the selected week already exists in the chosen month and year
    $checkQuery = "SELECT * FROM diaries WHERE student_id = ? AND week_number = ? AND month = ? AND year = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("iiii", $user_id, $week_number, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('You have already submitted a report for Week $week_number in " . date("F", mktime(0, 0, 0, $month, 1)) . " $year.');</script>";
    } else{
        // Insert new diary entry
        $upload_date = date('Y-m-d'); // Store the full date of submission
        $insertQuery = "INSERT INTO diaries (student_id, upload_date, report, week_number, month, year) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("issiii", $user_id, $upload_date, $report, $week_number, $month, $year);

        // Corrected alert syntax for success message
        if ($stmt->execute()) {
            echo "<script>alert('Report submitted successfully for Week $week_number in " . date("F", mktime(0, 0, 0, $month, 1)) . " $year!');</script>";
        } else {
            echo "<script>alert('Failed to submit the report. Please try again.');</script>";
        }
    }

}   





// Fetch the overall report for the student
if ($_SESSION['role'] == 'student') {
    $student_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT summary, challenges, improvements FROM overall_reports WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
   
    if ($result->num_rows > 0) {
        $report = $result->fetch_assoc();
    }
}

if (isset($_POST['choose_mentor'])) {
    $student_id = $_SESSION['user_id'];  // Get the logged-in student's ID
    $mentor_id = $_POST['mentor_id'];    // Get selected mentor ID

    // Update the student table or student_mentor table with the chosen mentor
    $stmt = $conn->prepare("UPDATE student SET mentor_id = ? WHERE student_id = ?");
    $stmt->bind_param("ii", $mentor_id, $student_id);

    if ($stmt->execute()) {
        echo "<p>Mentor selection saved successfully!</p>";
    } else {
        echo "<p>Failed to save mentor selection. Please try again.</p>";
    }
    $stmt->close();
}


// Fetch inspection reports for the logged-in student
$inspection_reports = [];
$student_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT inspection_date, inspector_name, supervisor_remarks, student_remarks 
                        FROM inspection_reports 
                        WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $inspection_reports[] = $row;
}
$stmt->close();



// Fetch progress reports for the student (replace 'progress_reports' with your actual table name and fields)
$student_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT DISTINCT week_number, report, month, year, 
                               IFNULL(feedback, 'Pending Review') AS feedback 
                        FROM diaries
                        WHERE student_id = ?");
$stmt->bind_param("i", $student_id); 
$stmt->execute();
$result = $stmt->get_result();

$progress_reports = []; // Initialize the array to store reports

while ($row = $result->fetch_assoc()) {
    $progress_reports[] = $row;
}
$stmt->close();




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../styles/student_sty.css">
</head>
<body>

<main>
    <!-- Buttons to toggle the forms -->
    <button id="toggleUploadFormButton">Upload Weekly Diary</button>
    <button id="toggleViewProgressReport">View Diary Reports</button>
    <button id="toggleViewOverallProcessReportButton">View Overall Progress Report</button>
    <button id="toggleOverallReportButton">Submit Overall Progress Report</button>
    <button id="toggleViewInspectionReportsButton">View Inspection Reports</button>

    <!-- Upload Weekly Diary Form -->
    <div id="uploadDiaryForm" class="section toggle-form" style="display:none;">
        <h2>Weekly Diary Submission</h2>
        <form method="POST">
            <label for="month">Select Month:</label>
            <select name="month" required>
                <?php
                for ($m = 1; $m <= 12; $m++) {
                    $monthName = date("F", mktime(0, 0, 0, $m, 1));
                    echo "<option value=\"$m\">$monthName</option>";
                }
                ?>
            </select>

            <label for="year">Select Year:</label>
            <select name="year" required>
                <?php
                $currentYear = date("Y");
                for ($y = $currentYear; $y >= $currentYear - 1; $y--) {
                    echo "<option value=\"$y\">$y</option>";
                }
                ?>
            </select>

            <label for="week_number">Select Week:</label>
            <select name="week_number" required>
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <option value="<?php echo $i; ?>">Week <?php echo $i; ?></option>
                <?php endfor; ?>
            </select>

            <label for="report">Report:</label>
            <textarea name="report" rows="5" placeholder="Describe the work done this week..." required></textarea>
            <button type="submit" name="submit_diary">Submit Report</button>
        </form>
    </div>

    <!-- Submit Overall Process Report Form -->
    <div id="overallProcessReportForm" class="section toggle-form" style="display:none;">
        <h2>Overall Progress Report</h2>
        <form method="POST">
            <input type="hidden" name="overall_report" value="1">
            <label for="summary">Conduct in General:</label>
            <textarea name="summary" rows="5" required></textarea>
            <label for="challenges">Involvement in the project:</label>
            <textarea name="challenges" rows="5" required></textarea>
            <label for="improvements">Any Other Comments:</label>
            <textarea name="improvements" rows="5" required></textarea>
            <button type="submit">Submit Report</button>
        </form>
    </div>

    <!-- View Overall Process Report Section -->
    <div id="viewOverallProcessReport" class="section toggle-form" style="display:none;">
        <h2>Overall Progress Report</h2>
        <?php if (isset($report)): ?>
            <h3>Summary:</h3>
            <p><?php echo htmlspecialchars($report['summary']); ?></p>
            <h3>Challenges:</h3>
            <p><?php echo htmlspecialchars($report['challenges']); ?></p>
            <h3>Improvements:</h3>
            <p><?php echo htmlspecialchars($report['improvements']); ?></p>
        <?php else: ?>
            <p>No report submitted yet.</p>
        <?php endif; ?>
        <a href="../public/generate_overall_report.php" target="_blank">
            <button type="button">Download as PDF</button>
        </a>
    </div>

    <!-- View Inspection Reports Section -->
    <div id="viewInspectionReports" class="section toggle-form" style="display:none;">
        <h2>Inspection Reports</h2>
        <?php if (!empty($inspection_reports)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Inspection Date</th>
                        <th>Inspector Name</th>
                        <th>Supervisor Remarks</th>
                        <th>Student Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inspection_reports as $report): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($report['inspection_date']); ?></td>
                            <td><?php echo htmlspecialchars($report['inspector_name']); ?></td>
                            <td><?php echo htmlspecialchars($report['supervisor_remarks']); ?></td>
                            <td><?php echo htmlspecialchars($report['student_remarks']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="../public/generate_inspec_rep.php" target="_blank">
                <button type="button">Download as PDF</button>
            </a>
        <?php else: ?>
            <p>No inspection reports available.</p>
        <?php endif; ?>
    </div>

    <!-- View Progress Reports Section -->
    <div id="viewProgressReport" class="section toggle-form" style="display:none;">
        <h2>Diary Reports</h2>
        <?php if (!empty($progress_reports)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Month</th>
                        <th>Week Number</th>
                        <th>Report</th>
                        <th>Mentor feedback</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($progress_reports as $report): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($report['year']); ?></td>
                            <td><?php echo htmlspecialchars($report['month']); ?></td>
                            <td><?php echo htmlspecialchars($report['week_number']); ?></td>
                            <td><?php echo htmlspecialchars($report['report']); ?></td>
                            <td><?php echo htmlspecialchars($report['feedback']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No diary reports available.</p>
        <?php endif; ?>
        <a href="../public/generate_diary_pdf.php" target="_blank">
            <button type="button">Download as PDF</button>
        </a>
    </div>
</main>

<script>
    // Function to hide all forms and show the selected one
    function toggleFormVisibility(formId) {
        var forms = document.querySelectorAll('.toggle-form');
        forms.forEach(function(form) {
            form.style.display = "none"; // Hide all forms
        });

        // Show the selected form
        var formToShow = document.getElementById(formId);
        formToShow.style.display = formToShow.style.display === "none" ? "block" : "none";
    }

    // Assigning the toggle function to buttons
    document.getElementById("toggleUploadFormButton").onclick = function() {
        toggleFormVisibility("uploadDiaryForm");
    };

    document.getElementById("toggleViewProgressReport").onclick = function() {
        toggleFormVisibility("viewProgressReport");
    };

    document.getElementById("toggleOverallReportButton").onclick = function() {
        toggleFormVisibility("overallProcessReportForm");
    };

    document.getElementById("toggleViewOverallProcessReportButton").onclick = function() {
        toggleFormVisibility("viewOverallProcessReport");
    };

    document.getElementById("toggleViewInspectionReportsButton").onclick = function() {
        toggleFormVisibility("viewInspectionReports");
    };
</script>

<script>
window.addEventListener('load', function() {
    const headerHeight = document.querySelector('header').offsetHeight;
    document.body.style.paddingTop = headerHeight + 'px';
});
</script>

<?php include '../public/footer.php'; ?>

</body>
</html>
