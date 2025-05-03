<?php
// Include the database connection file
include './backend/connect.php';

try {
    // Query to fetch completed tasks per team member
    $stmt = $pdo->query("
        SELECT 
            assigned_to AS team_member,
            COUNT(*) AS completed_tasks
        FROM 
            tasks
        WHERE 
            status = '2'
        GROUP BY 
            assigned_to
    ");

    // Fetch data as an associative array
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if data is empty
    if (empty($data)) {
        echo json_encode(['message' => 'No completed tasks found']);
    } else {
        // Return data as JSON
        echo json_encode($data);
    }
} catch (PDOException $e) {
    // Handle database errors
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Handle other unexpected errors
    echo json_encode(['error' => 'An unexpected error occurred: ' . $e->getMessage()]);
}
?>