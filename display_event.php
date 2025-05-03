<?php
// This is a simplified version that just handles deletions
// The full event handling is now in calendar_api.php
include './backend/connect.php';

// Handle AJAX requests for backward compatibility
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Handle delete requests for backward compatibility
    if ($action === 'delete' && isset($_POST['event_id'])) {
        $event_id = $_POST['event_id'];
        
        // Delete the event
        $sql = "DELETE FROM calendar_event_master WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        
        if ($stmt->execute()) {
            echo "Event deleted successfully";
        } else {
            echo "Error deleting event: " . $stmt->error;
        }
    } else {
        // Redirect all other actions to the new API
        header("Location: calendar_api.php");
        exit;
    }
}
?>