<?php                
require './backend/connect.php';
include 'auth_check.php';

if (isset($_GET['fetch'])) {
    header('Content-Type: application/json');
    $sql = "SELECT id, title, start_date as start, end_date as end FROM calendar_event_master";
    $result = $conn->query($sql);
    $events = array();
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    echo json_encode($events);
    exit;
}

?>
