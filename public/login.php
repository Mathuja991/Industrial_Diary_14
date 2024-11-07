<?php
session_start();
include '../config/db.php';




// Check if an admin already exists
$result = $conn->query("SELECT * FROM admin LIMIT 1");
if ($result->num_rows === 0) {
    // No admin exists, create one
    $username = 'admin_user';
    $password = '123';

    // Hash the password before inserting it
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hashed_password);

    if ($stmt->execute()) {
        echo "Admin user created successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // First, check the `users` table for a student, mentor, or staff login
    $result = $conn->query("SELECT * FROM users WHERE username='$username'");
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Regular user (student, mentor, or staff) login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'student') {
            header('Location: student_dashboard.php');
        } elseif ($user['role'] === 'mentor') {
            header('Location: mentor_dashboard.php');
        } elseif ($user['role'] === 'staff') {
            header('Location: staff_dashboard.php');
        } else {
            header('Location: dashboard.php'); // Default dashboard for unrecognized roles
        }
        exit();

    } else {
      // Check `admin` table for admin login
      $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
      $stmt->bind_param("s", $username);
      $stmt->execute();
      $adminResult = $stmt->get_result();
      $admin = $adminResult->fetch_assoc();

      if ($admin && password_verify($password, $admin['password'])) {
          // Set session for admin
          $_SESSION['admin_id'] = $admin['id'];
        

          // Redirect to admin dashboard
          header('Location: admin_dashboard.php');
          exit();
      } else {
          echo "Invalid username or password.";
      }
  }
  $stmt->close();
       
    
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../styles/login_sty.css">
</head>
<body>
    <form method="POST">
        <h2>Login to UoJ_IDMS</h2>

        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Login</button>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <p>Don't have an account? <a href="../public/register.php">Register here</a></p>
    </form>
    
</body>
</html>
