<?php
session_start();
include '../config/db.php';
require '../vendor/autoload.php';
use Fpdf\Fpdf;

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/logout.php');
    exit();
}

// Retrieve student details
$student_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, full_name, username FROM users WHERE role = 'student' AND id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student_details = $student_result->fetch_assoc();
$stmt->close();

// Check if student details were retrieved
if (!$student_details) {
    die('Student details not found.');
}

// Retrieve inspection reports
$stmt = $conn->prepare("SELECT year, month, week_number, feedback
                        FROM diaries
                        WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$inspection_reports = [];

while ($row = $result->fetch_assoc()) {
    $inspection_reports[] = $row;
}
$stmt->close();

// Create PDF
$pdf = new Fpdf();
$pdf->AddPage();

// Set Title
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, "Progress Report", 0, 1, 'C');
$pdf->Ln(10);

// Student Details
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, "Student Details", 0, 1);
$pdf->Cell(0, 10, "Name: " . $student_details['full_name'], 0, 1);
$pdf->Cell(0, 10, "Student ID: " . $student_details['id'], 0, 1);
$pdf->Cell(0, 10, "Registration Number: " . $student_details['username'], 0, 1); // Added registration number
$pdf->Ln(10);

// Inspection Reports Table Header
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(40, 10, 'year', 1, 0, 'C', 1);
$pdf->Cell(50, 10, ' month', 1, 0, 'C', 1);
$pdf->Cell(50, 10, 'Week Number', 1, 0, 'C', 1);
$pdf->Cell(50, 10, 'Mentor feedback', 1, 1, 'C', 1);

// Table Content
$pdf->SetFont('Arial', '', 10);
foreach ($inspection_reports as $report) {
    $pdf->Cell(40, 10, $report['year'], 1);
    $pdf->Cell(50, 10, $report['month'], 1);
    $pdf->Cell(50, 10, $report['week_number'], 1);
    $pdf->Cell(50, 10, $report['feedback'], 1, 1);
}

// Output PDF
$pdf->Output('D', 'Progress_Report.pdf');  // Download file directly as Progress_Report.pdf

exit;
?>
