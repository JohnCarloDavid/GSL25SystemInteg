<?php
// Start the session
session_start();

// Check if the admin is logged in
if (isset($_SESSION['admin_loggedin'])) {
    // Unset admin session variables
    unset($_SESSION['admin_loggedin']);
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['role']);
}

// Check if the employee is logged in
if (isset($_SESSION['employee_loggedin'])) {
    // Unset employee session variables
    unset($_SESSION['employee_loggedin']);
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['role']);
}

// Destroy the session
session_unset();
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
