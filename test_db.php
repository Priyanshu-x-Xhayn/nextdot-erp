<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include './backend/connect.php';

if ($conn) {
    echo "Database connection successful!<br>";
    
    // Test the calendar_event_master table structure
    $sql = "DESCRIBE calendar_event_master";
    $result = $conn->query($sql);
    
    if ($result) {
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["Field"] . "</td>";
            echo "<td>" . $row["Type"] . "</td>";
            echo "<td>" . $row["Null"] . "</td>";
            echo "<td>" . $row["Key"] . "</td>";
            echo "<td>" . $row["Default"] . "</td>";
            echo "<td>" . $row["Extra"] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "Error describing table: " . $conn->error;
    }
    
    // Check if session variables are set correctly
    session_start();
    echo "<h3>Session Variables:</h3>";
    echo "user_id: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
    echo "role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
    echo "department: " . ($_SESSION['department'] ?? 'Not set') . "<br>";
    
} else {
    echo "Database connection failed!";
}
?>