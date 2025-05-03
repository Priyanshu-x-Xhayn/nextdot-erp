<?php

// Add for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'auth_check.php';
include './backend/connect.php';

// Debugging: Log session variables
error_log("User ID: " . ($_SESSION['user_id'] ?? 'Not set'));
error_log("User Role: " . ($_SESSION['role'] ?? 'Not set'));
error_log("User Department: " . ($_SESSION['department'] ?? 'Not set'));

// Get current user information
$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role'] ?? 'User';
$userDepartment = $_SESSION['department'] ?? '';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            addEvent();
            break;
        case 'update':
            updateEvent();
            break;
        case 'delete':
            deleteEvent();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} elseif (isset($_GET['fetch']) && $_GET['fetch'] === 'true') {
    // Fetch events for calendar display
    fetchEventsAndTasks();
} elseif (isset($_GET['departments']) && $_GET['departments'] === 'true') {
    // Fetch departments for filter dropdown
    fetchDepartments();
}

// Fetch departments for filter dropdown
function fetchDepartments() {
    global $conn;
    
    $sql = "SELECT * FROM departments ORDER BY department_name";
    $result = $conn->query($sql);
    
    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[] = [
            'id' => $row['id'],
            'name' => $row['department_name']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($departments);
}

// Add new event
function addEvent() {
    global $conn, $userId, $userDepartment;
    
    // Debugging
    error_log("Adding event for user: $userId, department: $userDepartment");
    
    $title = $_POST['title'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $description = $_POST['description'] ?? '';
    $visibility = $_POST['visibility'] ?? 'private';
    $category = $_POST['category'] ?? 'general';
    $priority = $_POST['priority'] ?? 'medium';
    $status = $_POST['status'] ?? 'upcoming';
    $color = $_POST['color'] ?? '#3788d8';
    $location = $_POST['location'] ?? '';
    
    // Log received data
    error_log("Event data: " . json_encode($_POST));
    
    // Validate inputs
    if (empty($title) || empty($start_date)) {
        echo json_encode(['success' => false, 'message' => 'Title and start date are required']);
        return;
    }
    
    // Make sure userId is valid
    if (empty($userId) || $userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID. Please log in again.']);
        return;
    }
    
    // Prepare SQL
    $sql = "INSERT INTO calendar_event_master (user_id, department, title, start_date, end_date, description, 
            visibility, category, priority, color, status, location) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("isssssssssss", $userId, $userDepartment, $title, $start_date, $end_date, 
                      $description, $visibility, $category, $priority, $color, $status, $location);
        
        $result = $stmt->execute();
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Event added successfully', 'event_id' => $conn->insert_id]);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error adding event: ' . $e->getMessage()]);
    }
}

// Update existing event
function updateEvent() {
    global $conn, $userId, $userRole, $userDepartment;
    
    $event_id = $_POST['event_id'] ?? 0;
    $title = $_POST['title'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $description = $_POST['description'] ?? '';
    $visibility = $_POST['visibility'] ?? 'private';
    $category = $_POST['category'] ?? 'general';
    $priority = $_POST['priority'] ?? 'medium';
    $color = $_POST['color'] ?? '#3788d8';
    $status = $_POST['status'] ?? 'upcoming';
    $location = $_POST['location'] ?? '';
    
    // Validate inputs
    if (empty($event_id) || empty($title) || empty($start_date)) {
        echo json_encode(['success' => false, 'message' => 'Event ID, title and start date are required']);
        return;
    }
    
    // Check if user has permission to edit this event
    $checkSql = "SELECT user_id FROM calendar_event_master WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $event_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        return;
    }
    
    $event = $result->fetch_assoc();
    
    // Only the creator, admins, or managers can edit an event
    if ($event['user_id'] != $userId && $userRole !== 'Admin' && $userRole !== 'Manager') {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to edit this event']);
        return;
    }
    
    // Prepare SQL
    $sql = "UPDATE calendar_event_master SET title = ?, start_date = ?, end_date = ?, 
            description = ?, visibility = ?, category = ?, priority = ?, color = ?, 
            status = ?, location = ? WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssi", $title, $start_date, $end_date, $description, 
                       $visibility, $category, $priority, $color, $status, $location, $event_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating event: ' . $stmt->error]);
    }
}

// Delete event
function deleteEvent() {
    global $conn, $userId, $userRole;
    
    $event_id = $_POST['event_id'] ?? 0;
    
    // Validate input
    if (empty($event_id)) {
        echo json_encode(['success' => false, 'message' => 'Event ID is required']);
        return;
    }
    
    // Check if user has permission to delete this event
    $checkSql = "SELECT user_id FROM calendar_event_master WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $event_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        return;
    }
    
    $event = $result->fetch_assoc();
    
    // Only the creator, admins, or managers can delete an event
    if ($event['user_id'] != $userId && $userRole !== 'Admin' && $userRole !== 'Manager') {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this event']);
        return;
    }
    
    // Prepare SQL
    $sql = "DELETE FROM calendar_event_master WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $event_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting event: ' . $stmt->error]);
    }
}

// Fetch events and tasks based on user role and department
function fetchEventsAndTasks() {
    global $conn, $userId, $userRole, $userDepartment;
    
    $events = [];
    
    // 1. Fetch calendar events
    $events = array_merge($events, fetchCalendarEvents());
    
    // 2. Fetch tasks
    $events = array_merge($events, fetchTasks());
    
    // Return JSON
    header('Content-Type: application/json');
    echo json_encode($events);
}

// Fetch calendar events based on user role and department
function fetchCalendarEvents() {
    global $conn, $userId, $userRole, $userDepartment;
    
    // Base query - start with events the user created (personal events)
    $sql = "SELECT e.*, d.department_name FROM calendar_event_master e 
            LEFT JOIN departments d ON e.department = d.department_name 
            WHERE e.user_id = ?";
    
    // Add conditions based on role
    if ($userRole === 'Admin' || $userRole === 'Manager') {
        // Admins and Managers see all events
        $sql = "SELECT e.*, d.department_name FROM calendar_event_master e 
                LEFT JOIN departments d ON e.department = d.department_name";
        $stmt = $conn->prepare($sql);
    } elseif ($userRole === 'Team Lead') {
        // Team Leads see their department events and public events
        $sql .= " OR e.department = ? OR e.visibility = 'public'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $userId, $userDepartment);
    } else {
        // Regular users see their own events, department events, and public events
        $sql .= " OR (e.department = ? AND e.visibility = 'department') OR e.visibility = 'public'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $userId, $userDepartment);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        // Format for FullCalendar
        $event = [
            'id' => 'event_' . $row['id'], // Prefix to distinguish from tasks
            'title' => $row['title'],
            'start' => $row['start_date'],
            'end' => $row['end_date'],
            'description' => $row['description'],
            'backgroundColor' => $row['color'] ?? getColorForEvent($row),
            'borderColor' => $row['color'] ?? getColorForEvent($row),
            'textColor' => '#ffffff',
            'extendedProps' => [
                'department' => $row['department_name'] ?? $row['department'],
                'visibility' => $row['visibility'],
                'category' => $row['category'],
                'priority' => $row['priority'],
                'status' => $row['status'],
                'location' => $row['location'],
                'userId' => $row['user_id'],
                'type' => 'event' // Mark as calendar event
            ]
        ];
        
        // Add icon for private/department/public events
        $event['title'] = getEventIcon($row['visibility']) . ' ' . $row['title'];
        
        // Add department to title if it exists
        if (!empty($row['department_name']) || !empty($row['department'])) {
            $deptName = $row['department_name'] ?? $row['department'];
            $event['title'] .= ' (' . $deptName . ')';
        }
        
        $events[] = $event;
    }
    
    return $events;
}

// Fetch tasks based on user role and department
function fetchTasks() {
    global $conn, $userId, $userRole, $userDepartment;
    
    // Base query for tasks
    $sql = "";
    
    if ($userRole === 'Admin' || $userRole === 'Manager') {
        // Admins and Managers see all tasks
        $sql = "SELECT t.*, e.name as assignee_name, p.np as project_name, 
                GROUP_CONCAT(DISTINCT d.department_name SEPARATOR ', ') as department_names
                FROM tasks t 
                LEFT JOIN employee e ON t.assigned_to = e.id
                LEFT JOIN project p ON t.project_id = p.id
                LEFT JOIN project_departments pd ON p.id = pd.project_id
                LEFT JOIN departments d ON pd.department_id = d.id
                WHERE t.status != '2' AND t.due_date IS NOT NULL AND t.due_date != '' AND t.due_date != '0000-00-00'
                GROUP BY t.id
                ORDER BY t.due_date ASC";
        $stmt = $conn->prepare($sql);
    } elseif ($userRole === 'Team Lead') {
        // Team Leads see tasks for their department and tasks assigned to them
        $sql = "SELECT t.*, e.name as assignee_name, p.np as project_name, 
                GROUP_CONCAT(DISTINCT d.department_name SEPARATOR ', ') as department_names
                FROM tasks t 
                LEFT JOIN employee e ON t.assigned_to = e.id
                LEFT JOIN project p ON t.project_id = p.id
                LEFT JOIN project_departments pd ON p.id = pd.project_id
                LEFT JOIN departments d ON pd.department_id = d.id
                WHERE t.status != '2' AND t.due_date IS NOT NULL AND t.due_date != '' AND t.due_date != '0000-00-00'
                AND (t.assigned_to = ? OR d.department_name = ?)
                GROUP BY t.id
                ORDER BY t.due_date ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $userId, $userDepartment);
    } else {
        // Regular users see only tasks assigned to them
        $sql = "SELECT t.*, e.name as assignee_name, p.np as project_name, 
                GROUP_CONCAT(DISTINCT d.department_name SEPARATOR ', ') as department_names
                FROM tasks t 
                LEFT JOIN employee e ON t.assigned_to = e.id
                LEFT JOIN project p ON t.project_id = p.id
                LEFT JOIN project_departments pd ON p.id = pd.project_id
                LEFT JOIN departments d ON pd.department_id = d.id
                WHERE t.status != '2' AND t.due_date IS NOT NULL AND t.due_date != '' AND t.due_date != '0000-00-00'
                AND t.assigned_to = ?
                GROUP BY t.id
                ORDER BY t.due_date ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        // Get task status text
        $statusText = '';
        switch($row['status']) {
            case '0':
                $statusText = 'Pending';
                break;
            case '1':
                $statusText = 'In Progress';
                break;
            case '2':
                $statusText = 'Completed';
                break;
            default:
                $statusText = 'Pending';
        }
        
        // Get task priority
        $priority = strtolower($row['priori'] ?: 'medium');
        
        // Get project name for display
        $projectName = $row['project_name'] ?: 'No Project';
        
        // Format for FullCalendar
        $task = [
            'id' => 'task_' . $row['id'], // Prefix to distinguish from events
            'title' => '📋 ' . $row['task_name'] . ' (Project: ' . $projectName . ')', // Add task icon and project
            'start' => $row['due_date'], // Use due date as the event date
            'allDay' => true, // Tasks are typically all-day events
            'description' => $row['description'],
            'backgroundColor' => getColorForTaskPriority($priority),
            'borderColor' => getColorForTaskPriority($priority),
            'textColor' => '#ffffff',
            'editable' => false, // Make tasks non-editable in calendar
            'extendedProps' => [
                'type' => 'task', // Mark as task
                'taskId' => $row['id'],
                'assignee' => $row['assignee_name'],
                'project' => $projectName,
                'department' => $row['department_names'] ?: 'General',
                'priority' => $priority,
                'status' => $statusText,
                'startDate' => $row['start_date'],
                'dueDate' => $row['due_date']
            ]
        ];
        
        $tasks[] = $task;
    }
    
    return $tasks;
}

// Get appropriate color based on event properties
function getColorForEvent($event) {
    // Priority-based colors
    switch($event['priority']) {
        case 'critical':
            return '#dc3545'; // Red
        case 'high':
            return '#fd7e14'; // Orange
        case 'medium':
            return '#007bff'; // Blue
        case 'low':
            return '#28a745'; // Green
        default:
            return '#007bff'; // Default blue
    }
}

// Get color based on task priority
function getColorForTaskPriority($priority) {
    switch($priority) {
        case 'high':
            return '#dc3545'; // Red
        case 'medium':
            return '#fd7e14'; // Orange
        case 'low':
            return '#28a745'; // Green
        default:
            return '#6c757d'; // Gray for unknown priority
    }
}

// Get icon for event visibility
function getEventIcon($visibility) {
    switch($visibility) {
        case 'private':
            return '🔒'; // Lock for private
        case 'department':
            return '👥'; // People for department
        case 'public':
            return '🌐'; // Globe for public
        default:
            return '';
    }
}
?>