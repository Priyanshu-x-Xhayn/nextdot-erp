<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: index.php");
    exit;
}

// Make sure department is set
if (!isset($_SESSION['department']) || empty($_SESSION['department'])) {
    $_SESSION['department'] = 'General'; // Set a default department if not set
}

// Make sure role is set
if (!isset($_SESSION['role']) || empty($_SESSION['role'])) {
    $_SESSION['role'] = 'User'; // Set a default role if not set
}
?>