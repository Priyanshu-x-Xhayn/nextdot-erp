<?php
include './backend/connect.php';

session_start();
// Add timezone setting


// Get user information
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'User';

// Default to today's date for filtering
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$filter_department = isset($_GET['department']) ? $_GET['department'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_project = isset($_GET['project']) ? $_GET['project'] : '';
$filter_priority = isset($_GET['priority']) ? $_GET['priority'] : '';
$filter_team_member = isset($_GET['team_member']) ? $_GET['team_member'] : '';

// Fetch departments from the departments table
$dept_query = "SELECT id, department_name FROM departments ORDER BY department_name";
$dept_result = $conn->query($dept_query);
$departments = [];
while ($dept_row = $dept_result->fetch_assoc()) {
    $departments[$dept_row['id']] = $dept_row['department_name'];
}

// Fetch user's department
$user_dept_query = "SELECT depart FROM employee WHERE id = '$user_id'";
$user_dept_result = $conn->query($user_dept_query);
$user_department = '';
if ($user_dept_result && $user_dept_result->num_rows > 0) {
    $user_dept_row = $user_dept_result->fetch_assoc();
    $user_department = $user_dept_row['depart'];
}

// Fetch employees for assignee dropdown based on user role
if ($user_role == 'Admin' || $user_role == 'Manager') {
  // Admins and managers can see all employees
  $employee_query = "SELECT id, name, depart FROM employee ORDER BY name";
} else if ($user_role == 'Team Lead') {
  // Team leads can only see employees from their department
  $employee_query = "SELECT id, name, depart FROM employee WHERE depart = '$user_department' ORDER BY name";
} else {
  // Regular users should only see themselves
  $employee_query = "SELECT id, name, depart FROM employee WHERE id = '$user_id' ORDER BY name";
}

$employee_result = $conn->query($employee_query);
$employees = [];
while ($emp_row = $employee_result->fetch_assoc()) {
  $employees[$emp_row['id']] = $emp_row['name'];
}
// Fetch projects for dropdown with department info
$projects_query = "SELECT p.id, p.np as project_name, 
                  GROUP_CONCAT(d.department_name SEPARATOR ', ') as departments
                  FROM project p
                  LEFT JOIN project_departments pd ON p.id = pd.project_id
                  LEFT JOIN departments d ON pd.department_id = d.id
                  GROUP BY p.id
                  ORDER BY p.np";
$projects_result = $conn->query($projects_query);
$projects = [];
while ($project_row = $projects_result->fetch_assoc()) {
    if(!empty($project_row['project_name'])) {
        $projects[$project_row['id']] = $project_row['project_name'];
    }
}

// Construct query based on filters and user role
$base_query = "SELECT t.*, e.name as assignee_name, p.np as project_name, 
               GROUP_CONCAT(DISTINCT d.department_name SEPARATOR ', ') as department 
               FROM tasks t 
               LEFT JOIN employee e ON t.assigned_to = e.id 
               LEFT JOIN project p ON t.project_id = p.id 
               LEFT JOIN project_departments pd ON p.id = pd.project_id 
               LEFT JOIN departments d ON pd.department_id = d.id";

$where = [];
$params = [];
$types = "";

// Role-based restrictions
if ($user_role == 'User') {
    // Users can only see their own tasks
    $where[] = "t.assigned_to = ?";
    $params[] = $user_id;
    $types .= "i";
} elseif ($user_role == 'Team Lead') {
    if (!empty($filter_team_member)) {
        // If team member is selected, show only their tasks
        $where[] = "t.assigned_to = ?";
        $params[] = $filter_team_member;
        $types .= "i";
    } else {
        // Otherwise show all tasks in the team lead's department
        $where[] = "e.depart = ?";
        $params[] = $user_department;
        $types .= "s";
    }
}

// Date filter
if (!empty($filter_date)) {
    $where[] = "DATE(t.start_date) = ?";
    $params[] = $filter_date;
    $types .= "s";
}

// Department filter (only for Admin and Manager)
if (($user_role == 'Admin' || $user_role == 'Manager') && !empty($filter_department)) {
    $where[] = "d.id = ?";
    $params[] = $filter_department;
    $types .= "i";
}

// Project filter
if (!empty($filter_project)) {
    $where[] = "t.project_id = ?";
    $params[] = $filter_project;
    $types .= "i";
}

// Status filter
if (!empty($filter_status)) {
    $where[] = "t.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

// Priority filter
if (!empty($filter_priority)) {
    $where[] = "t.priori = ?";
    $params[] = $filter_priority;
    $types .= "s";
}

// Build final query
$query = $base_query;
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}
$query .= " GROUP BY t.id ORDER BY t.priori DESC, t.due_date ASC";

// Prepare and execute
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Calculate summary statistics based on user role
$stats_sql = "SELECT 
    COUNT(DISTINCT t.id) as total_tasks, 
    SUM(CASE WHEN t.status = '0' THEN 1 ELSE 0 END) as pending_tasks, 
    SUM(CASE WHEN t.status = '1' THEN 1 ELSE 0 END) as in_progress_tasks, 
    SUM(CASE WHEN t.status = '2' THEN 1 ELSE 0 END) as completed_tasks, 
    SUM(CASE WHEN t.priori = 'High' THEN 1 ELSE 0 END) as high_priority_tasks, 
    SUM(CASE WHEN t.due_date < CURDATE() AND t.status != '2' THEN 1 ELSE 0 END) as overdue_tasks 
FROM tasks t 
LEFT JOIN employee e ON t.assigned_to = e.id 
LEFT JOIN project p ON t.project_id = p.id 
LEFT JOIN project_departments pd ON p.id = pd.project_id 
LEFT JOIN departments d ON pd.department_id = d.id 
WHERE 1=1";

// Apply role-based restrictions to stats
if ($user_role == 'User') {
    $stats_sql .= " AND t.assigned_to = '$user_id'";
} else if ($user_role == 'Team Lead') {
    if (!empty($filter_team_member)) {
        $stats_sql .= " AND t.assigned_to = '$filter_team_member'";
    } else {
        $stats_sql .= " AND e.depart = '$user_department'";
    }
}

// Apply filters to stats
if (!empty($filter_date)) {
    $stats_sql .= " AND DATE(t.start_date) = '$filter_date'";
}

if (($user_role == 'Admin' || $user_role == 'Manager') && !empty($filter_department)) {
    $stats_sql .= " AND d.id = '$filter_department'";
}

if (!empty($filter_project)) {
    $stats_sql .= " AND t.project_id = '$filter_project'";
}

if (!empty($filter_status)) {
    $stats_sql .= " AND t.status = '$filter_status'";
}

if (!empty($filter_priority)) {
    $stats_sql .= " AND t.priori = '$filter_priority'";
}

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Fetch team members for team leads
$team_members = [];
if ($user_role == 'Team Lead') {
    $team_members_query = "SELECT id, name FROM employee WHERE depart = '$user_department' AND id != '$user_id' ORDER BY name";
    $team_members_result = $conn->query($team_members_query);
    while ($member_row = $team_members_result->fetch_assoc()) {
        $team_members[$member_row['id']] = $member_row['name'];
    }
}

// Handle task submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_task"])) {
  $task_name = $_POST['task_name'];
  $description = $_POST['description'];
  $assigned_to = $_POST['assigned_to'];
  $project_id = $_POST['project_id'];
  $start_date = $_POST['start_date'];
  $due_date = $_POST['due_date'];
  $priority = $_POST['priority'];
  $hyperlinks = isset($_POST['hyperlinks']) ? $_POST['hyperlinks'] : '';
  $status = 0; // Default: Pending

  // Prepare SQL
  $sql = "INSERT INTO tasks (task_name, description, assigned_to, project_id, start_date, due_date, priori, status, hyperlinks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ssiisssis", $task_name, $description, $assigned_to, $project_id, $start_date, $due_date, $priority, $status, $hyperlinks);
  
    if ($stmt->execute()) {
        // Create notification for the assigned user
        $notification_sql = "INSERT INTO calendar_event_master (user_id,department,title, start_date, end_date,description,visibility,category,priority,status,color,location) 
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
        $notification_stmt = $conn->prepare($notification_sql);
        $message = "You have been assigned a new task: " . $task_name;
        $visibility='private';
        $category='general';
        $color='#3788d8';
        $team_member=$filter_team_member;
        $end_date=$due_date;
        $location='';
        $notification_stmt->bind_param("isssssssssss",$assigned_to,$team_member,$task_name, $start_date,$end_date, $message, $visibility, $category,$priority,$status, $color, $location);
        $notification_stmt->execute();
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?task_added=1");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Check for task_added parameter to show success message
$task_added = isset($_GET['task_added']) ? true : false;
$dept_result = $conn->query($dept_query);
if (!$dept_result) {
    die("Database error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Task Management Dashboard</title>
  <link rel="stylesheet" href="./src/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #072e63;
      --primary-light: #2a5298;
      --primary-dark: #051e42;
      --accent-color: #f8c300;
      --success-color: #1cc88a;
      --warning-color: #f6c23e;
      --danger-color: #e74a3b;
      --info-color: #36b9cc;
      --text-color: #f8f9fc;
      --border-radius: 10px;
      --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      --hover-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
      --transition: all 0.3s ease;
      --card-bg: rgba(255, 255, 255, 0.95);
      --gradient-bg: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background: var(--gradient-bg);
      color: #333;
    }
    
    .content-wrapper {
      background: var(--gradient-bg);
    }
    
    /* Dashboard Stats */
    .stats-container {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 25px;
    }
    
    .stat-card {
      flex: 1;
      min-width: 200px;
      background: var(--card-bg);
      border-radius: var(--border-radius);
      padding: 20px;
      box-shadow: var(--shadow);
      transition: var(--transition);
      border-left: 5px solid var(--primary-color);
      display: flex;
      flex-direction: column;
      position: relative;
      overflow: hidden;
    }
    
    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
      z-index: 1;
    }
    .due-task .table-responsive {
      width : 100% !important;
    }
    .due-task .table-responsive::before {
      width : 100% !important;
    }
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--hover-shadow);
    }
    
    .stat-card.pending {
      border-left-color: var(--warning-color);
    }
    
    .stat-card.progresss {
      border-left-color: var(--primary-light);
    }
    
    .stat-card.completed {
      border-left-color: var(--success-color);
    }
    
    .stat-card.high-priority {
      border-left-color: var(--danger-color);
    }
    
    .stat-card.overdue {
      border-left-color: #ff6b6b;
    }
    
    .stat-title {
      color: var(--primary-dark);
      font-size: 0.9rem;
      text-transform: uppercase;
      margin-bottom: 10px;
      font-weight: 600;
      letter-spacing: 1px;
      position: relative;
      z-index: 2;
    }
    
    .stat-value {
      font-size: 2.2rem;
      font-weight: 700;
      color: var(--primary-color);
      position: relative;
      z-index: 2;
    }
    
    .stat-icon {
      position: absolute;
      bottom: 10px;
      right: 10px;
      font-size: 3rem;
      opacity: 0.1;
      color: var(--primary-dark);
    }
    
    /* Filter Controls */
    .filter-container {
      background: var(--card-bg);
      padding: 20px;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      margin-bottom: 25px;
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      align-items: flex-end;
      position: relative;
      overflow: hidden;
    }
    
    .filter-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
    }
    
    .filter-group {
      flex: 1;
      min-width: 200px;
      display: flex;
      flex-direction: column;
      gap: 5px;
    }
    
    .filter-label {
      font-weight: 600;
      font-size: 0.9rem;
      color: var(--primary-dark);
      margin-bottom: 5px;
    }
    
    .form-control {
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      padding: 10px 15px;
      transition: var(--transition);
    }
    
    .form-control:focus {
      border-color: var(--primary-light);
      box-shadow: 0 0 0 0.2rem rgba(42, 82, 152, 0.25);
    }
    
    /* Priority and Status Badges */
    .badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    
    .badge i {
      font-size: 0.7rem;
    }
    
    .badge-high {
      background-color: rgba(231, 74, 59, 0.1);
      color: var(--danger-color);
      border: 1px solid rgba(231, 74, 59, 0.3);
    }
    
    .badge-medium {
      background-color: rgba(246, 194, 62, 0.1);
      color: var(--warning-color);
      border: 1px solid rgba(246, 194, 62, 0.3);
    }
    
    .badge-low {
      background-color: rgba(28, 200, 138, 0.1);
      color: var(--success-color);
      border: 1px solid rgba(28, 200, 138, 0.3);
    }
    
    .badge-0 {
      background-color: rgba(246, 194, 62, 0.1);
      color: var(--warning-color);
      border: 1px solid rgba(246, 194, 62, 0.3);
    }
    
    .badge-1 {
      background-color: rgba(42, 82, 152, 0.1);
      color: var(--primary-light);
      border: 1px solid rgba(42, 82, 152, 0.3);
    }
    
    .badge-2 {
      background-color: rgba(28, 200, 138, 0.1);
      color: var(--success-color);
      border: 1px solid rgba(28, 200, 138, 0.3);
    }
    
    /* Status Change Buttons */
    .status-btn {
      padding: 5px 10px;
      border-radius: 4px;
      border: none;
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
    }
    
    .status-btn-0 {
      background-color: var(--warning-color);
      color: white;
    }
    
    .status-btn-1 {
      background-color: var(--primary-light);
      color: white;
    }
    
    .status-btn-2 {
      background-color: var(--success-color);
      color: white;
    }
    
    /* Task Table */
    .table-responsive {
      margin-top: 30px;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(7, 46, 99, 0.15), 0 0 10px rgba(248, 195, 0, 0.1);
      overflow: hidden;
      position: relative;
      padding: 5px;
      border: 1px solid rgba(7, 46, 99, 0.08);
      overflow-x: auto;
      width : 110%;
    }
    .table-responsive::before{
      width : 110%;
    }
    #taskTable {
      border-collapse: separate;
      border-spacing: 0 8px;   
      background-color: transparent;
      border: none;
      margin-bottom: 0;
    }
    
    #taskTable td, #taskTable th {
      border: none;
    }
    
    #taskTable thead {
      background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    }
    
    #taskTable th {
      padding: 16px 12px;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.85rem;
      letter-spacing: 1px;
      color: var(--text-color);
      vertical-align: middle;
      position: relative;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
      border-bottom: none;
    }
    
    #taskTable tbody tr {
      background: white;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.03);
      border-radius: 10px;
      margin-bottom: 8px;
      transform-origin: center;
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    
    #taskTable tbody tr:hover {
      background: linear-gradient(to right, #f9f9ff, #ffffff, #f9f9ff);
      transform: translateY(-3px) scale(1.005);
      box-shadow: 0 8px 16px rgba(7, 46, 99, 0.06), 0 0 0 1px rgba(7, 46, 99, 0.02);
      z-index: 10;
    }
    
    /* New Tasks and Due Tasks Tables */
    #newTasksTable, #dueTasksTable {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }
    
    #newTasksTable th, #dueTasksTable th {
      background-color: var(--primary-color);
      color: white;
      padding: 12px 15px;
      text-align: left;
      font-weight: 600;
      font-size: 0.9rem;
      text-transform: uppercase;
    }
    
    #newTasksTable td, #dueTasksTable td {
      padding: 12px 15px;
      border-bottom: 1px solid #e3e6f0;
    }
    
    #newTasksTable tr:hover, #dueTasksTable tr:hover {
      background-color: rgba(7, 46, 99, 0.05);
    }
    
    .complete-task {
      background-color: var(--success-color);
      color: white;
      border: none;
      transition: all 0.3s;
    }
    
    .complete-task:hover {
      background-color: #169b6b;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    /* Card styling for the tables */
    .card {
      border: none;
      border-radius: 10px;
      box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
      margin-bottom: 30px;
      background: var(--card-bg);
      overflow: hidden;
    }
    
    .card-header {
      background-color: #f8f9fc;
      border-bottom: 1px solid #e3e6f0;
      padding: 1rem 1.25rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .card-header h6 {
      color: var(--primary-color);
      font-weight: 700;
      font-size: 1rem;
      margin: 0;
    }
    
    /* Buttons */
    .btn {
      border-radius: 8px;
      padding: 8px 16px;
      font-weight: 600;
      letter-spacing: 0.5px;
      transition: all 0.3s;
    }
    
    .btn-primary {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }
    
    .btn-primary:hover {
      background-color: var(--primary-dark);
      border-color: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(7, 46, 99, 0.2);
    }
    
    .btn-success {
      background-color: var(--success-color);
      border-color: var(--success-color);
    }
    
    .btn-success:hover {
      background-color: #169b6b;
      border-color: #169b6b;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(28, 200, 138, 0.2);
    }
    
    .btn-info {
      background-color: var(--info-color);
      border-color: var(--info-color);
      color: white;
    }
    
    .btn-info:hover {
      background-color: #2a9faf;
      border-color: #2a9faf;
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(54, 185, 204, 0.2);
    }
    
    /* Modal Styling */
    .modal-content {
      border-radius: 15px;
      border: none;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    }
    
    .modal-header {
      background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
      color: white;
      border-top-left-radius: 15px;
      border-top-right-radius: 15px;
      border-bottom: none;
    }
    
    .modal-title {
      font-weight: 600;
    }
    
    .modal-body {
      padding: 25px;
    }
    
    .modal-footer {
      border-top: 1px solid #e9ecef;
      padding: 15px 25px;
    }
    
    /* Hyperlinks Section */
    .hyperlinks-container {
      margin-top: 15px;
    }
    
    .hyperlink-item {
      display: flex;
      align-items: center;
      margin-bottom: 8px;
    }
    
    .hyperlink-item input {
      flex: 1;
      margin-right: 10px;
    }
    
    /* Loading Spinner */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(255, 255, 255, 0.8);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      visibility: hidden;
      opacity: 0;
      transition: all 0.3s;
    }
    
    .loading-overlay.show {
      visibility: visible;
      opacity: 1;
    }
    
    .spinner {
      width: 50px;
      height: 50px;
      border: 5px solid #f3f3f3;
      border-top: 5px solid var(--primary-color);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    /* Success Alert */
    .alert-success {
      background-color: rgba(28, 200, 138, 0.1);
      border-color: rgba(28, 200, 138, 0.3);
      color: var(--success-color);
      border-radius: 10px;
      padding: 15px 20px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .alert-success i {
      font-size: 1.5rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
      .stat-card {
        min-width: 100%;
      }
      
      .filter-group {
        min-width: 100%;
      }
    }
    
    /* Task detail view */
    .task-detail-section {
      margin-bottom: 20px;
    }
    
    .task-detail-header {
      font-weight: 600;
      color: var(--primary-color);
      margin-bottom: 10px;
      border-bottom: 1px solid #e3e6f0;
      padding-bottom: 5px;
    }
    
    .task-hyperlinks a {
      display: block;
      margin-bottom: 5px;
      color: var(--primary-light);
      transition: all 0.2s;
    }
    
    .task-hyperlinks a:hover {
      color: var(--primary-color);
      text-decoration: underline;
    }
    
    /* Animation for new tasks */
    @keyframes highlight {
      0% { background-color: rgba(54, 185, 204, 0.2); }
      100% { background-color: transparent; }
    }
    
    .highlight-new {
      animation: highlight 2s ease-in-out;
    }
  </style>
</head>

<body class="hold-transition sidebar-mini">
  <!-- Loading Overlay -->
  <div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
  </div>

  <div class="wrapper">
    <?php include './header.php'; ?>
    <div class="content-wrapper">
      <section class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1><i class="fas fa-tasks mr-2"></i>Task Management Dashboard</h1>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active">Tasks</li>
              </ol>
            </div>
          </div>
        </div>
      </section>

      <section class="content">
        <div class="container-fluid">
          
          <?php if ($task_added): ?>
          <div class="alert alert-success" role="alert">
            <i class="fas fa-check-circle"></i>
            <div>
              <strong>Success!</strong> Task has been added successfully.
            </div>
          </div>
          <?php endif; ?>
          
          <!-- Stats Cards -->
          <div class="stats-container">
            <div class="stat-card">
              <div class="stat-title">Total Tasks</div>
              <div class="stat-value"><?php echo $stats['total_tasks'] ?? 0; ?></div>
              <i class="fas fa-clipboard-list stat-icon"></i>
              </div>
        <div class="stat-card pending">
          <div class="stat-title">Pending</div>
          <div class="stat-value"><?php echo $stats['pending_tasks'] ?? 0; ?></div>
          <i class="fas fa-clock stat-icon"></i>
        </div>
        <div class="stat-card progresss">
          <div class="stat-title">In Progress</div>
          <div class="stat-value"><?php echo $stats['in_progress_tasks'] ?? 0; ?></div>
          <i class="fas fa-spinner stat-icon"></i>
        </div>
        <div class="stat-card completed">
          <div class="stat-title">Completed</div>
          <div class="stat-value"><?php echo $stats['completed_tasks'] ?? 0; ?></div>
          <i class="fas fa-check-circle stat-icon"></i>
        </div>
        <div class="stat-card high-priority">
          <div class="stat-title">High Priority</div>
          <div class="stat-value"><?php echo $stats['high_priority_tasks'] ?? 0; ?></div>
          <i class="fas fa-exclamation-triangle stat-icon"></i>
        </div>
        <div class="stat-card overdue">
          <div class="stat-title">Overdue</div>
          <div class="stat-value"><?php echo $stats['overdue_tasks'] ?? 0; ?></div>
          <i class="fas fa-calendar-times stat-icon"></i>
        </div>
      </div>

      <!-- Filters -->
     <!-- Filters -->
<div class="filter-container">
    <form action="" method="GET" class="w-100 d-flex flex-wrap gap-3">
        <?php if ($user_role != 'User'): // Hide date filter from regular users ?>
            <div class="filter-group mx-3">
                <label class="filter-label" for="date">Date</label>
                <input type="date" class="form-control" id="date" name="date" value="<?php echo $filter_date; ?>">
            </div>
        <?php endif; ?>
        
        <?php if ($user_role == 'Admin' || $user_role == 'Manager'): // Only Admin and Manager can filter by department ?>
            <div class="filter-group mx-3">
                <label class="filter-label" for="department">Department</label>
                <select class="form-control" id="department" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept_id => $dept_name): ?>
                        <option value="<?php echo $dept_id; ?>" <?php echo ($filter_department == $dept_id) ? 'selected' : ''; ?>>
                            <?php echo $dept_name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group mx-3">
                <label class="filter-label" for="project">Project</label>
                <select class="form-control" id="project" name="project">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $project_id => $project_name): ?>
                        <option value="<?php echo $project_id; ?>" <?php echo ($filter_project == $project_id) ? 'selected' : ''; ?>>
                            <?php echo $project_name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        
        <?php if ($user_role == 'Team Lead'): // Team Lead can only filter by team members ?>
            <!-- Hidden field to maintain the team lead's department -->
            <input type="hidden" name="department" value="<?php echo $user_department; ?>">
            
            <div class="filter-group mx-3">
                <label class="filter-label" for="project">Project</label>
                <select class="form-control" id="project" name="project">
                    <option value="">All Projects</option>
                    <?php 
                    // Get projects associated with the team lead's department
                    $dept_projects_query = "SELECT DISTINCT p.id, p.np as project_name 
                                           FROM project p 
                                           JOIN project_departments pd ON p.id = pd.project_id 
                                           WHERE pd.department_id = '$user_department' 
                                           ORDER BY p.np";
                    $dept_projects_result = $conn->query($dept_projects_query);
                    while ($project_row = $dept_projects_result->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $project_row['id']; ?>" <?php echo ($filter_project == $project_row['id']) ? 'selected' : ''; ?>>
                            <?php echo $project_row['project_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <?php if (!empty($team_members)): ?>
                <div class="filter-group mx-3">
                    <label class="filter-label" for="team_member">Team Member</label>
                    <select class="form-control" id="team_member" name="team_member">
                        <option value="">All Team Members</option>
                        <?php foreach ($team_members as $member_id => $member_name): ?>
                            <option value="<?php echo $member_id; ?>" <?php echo ($filter_team_member == $member_id) ? 'selected' : ''; ?>>
                                <?php echo $member_name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($user_role != 'User'): // Hide status and priority filters from regular users ?>
            <div class="filter-group mx-3">
                <label class="filter-label" for="status">Status</label>
                <select class="form-control" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="0" <?php echo ($filter_status === '0') ? 'selected' : ''; ?>>Pending</option>
                    <option value="1" <?php echo ($filter_status === '1') ? 'selected' : ''; ?>>In Progress</option>
                    <option value="2" <?php echo ($filter_status === '2') ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            
            <div class="filter-group mx-3">
                <label class="filter-label" for="priority">Priority</label>
                <select class="form-control" id="priority" name="priority">
                    <option value="">All Priorities</option>
                    <option value="Low" <?php echo ($filter_priority === 'Low') ? 'selected' : ''; ?>>Low</option>
                    <option value="Medium" <?php echo ($filter_priority === 'Medium') ? 'selected' : ''; ?>>Medium</option>
                    <option value="High" <?php echo ($filter_priority === 'High') ? 'selected' : ''; ?>>High</option>
                </select>
            </div>
            
            <div class="filter-group mx-3" style="flex: 0 0 auto; align-self: flex-end;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter mr-1"></i> Apply Filters
                </button>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addTaskModal">
                    <i class="fas fa-plus mr-1"></i> Add New Task
                </button>
            </div>
        <?php else: // For regular users, just show their tasks with no filters ?>
            <div class="filter-group mx-3" style="flex: 0 0 auto; align-self: flex-end;">
                <p class="mb-0 mr-3"><strong>Showing your assigned tasks</strong></p>
            </div>
        <?php endif; ?>
    </form>
</div>

      <!-- Task Table -->
      <div class="table-responsive">
        <table class="table" id="taskTable">
          <thead>
            <tr data-task-id="<?php echo $row['id']; ?>">
              <th>ID</th>
              <th>Task</th>
              <th>Project</th>
              <th>Description</th>
              <th>Assignee</th>
              <th>Department</th>
              <th>Priority</th>
              <th>Due Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr data-task-id="<?php echo $row['id']; ?>">
                  <td><?php echo $row['id']; ?></td>
                  <td><?php echo $row['task_name']; ?></td>
                  <td><?php echo $row['project_name']; ?></td>
                  <td><?php echo substr($row['description'], 0, 50) . (strlen($row['description']) > 50 ? '...' : ''); ?></td>
                  <td><?php echo $row['assignee_name']; ?></td>
                  <td><?php echo htmlspecialchars($row['department'] ?? 'No Department'); ?></td>
                  <td>
                    <span class="badge badge-<?php echo strtolower($row['priori']); ?>">
                      <i class="fas fa-<?php echo strtolower($row['priori']) == 'high' ? 'exclamation-circle' : (strtolower($row['priori']) == 'medium' ? 'dot-circle' : 'arrow-circle-down'); ?>"></i>
                      <?php echo $row['priori'] ?: 'Medium'; ?>
                    </span>
                  </td>
                  <td>
                    <?php 
                    $due_date = new DateTime($row['due_date']);
                    $today = new DateTime();
                    $due_class = '';
                    
                    if($due_date < $today && $row['status'] != '2') {
                        $due_class = 'text-danger font-weight-bold';
                        echo '<span class="'.$due_class.'"><i class="fas fa-exclamation-circle mr-1"></i>'.date('M d, Y', strtotime($row['due_date'])).' (Overdue)</span>';
                    } else {
                        echo date('M d, Y', strtotime($row['due_date']));
                    }
                    ?>
                  </td>
                  <td>
                    <?php 
                    $status_text = '';
                    $status_icon = '';
                    switch($row['status']) {
                        case '0':
                            $status_text = 'Pending';
                            $status_icon = 'clock';
                            break;
                        case '1':
                            $status_text = 'In Progress';
                            $status_icon = 'spinner';
                            break;
                        case '2':
                            $status_text = 'Completed';
                            $status_icon = 'check-circle';
                            break;
                        default:
                            $status_text = 'Pending';
                            $status_icon = 'clock';
                    }
                    ?>
                    <span class="badge badge-<?php echo $row['status']; ?>">
                      <i class="fas fa-<?php echo $status_icon; ?> mr-1"></i>
                      <?php echo $status_text; ?>
                    </span>
                  </td>
                  <td>
                    <div class="btn-group">
                      <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#viewTaskModal<?php echo $row['id']; ?>">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#editTaskModal<?php echo $row['id']; ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                      <div class="dropdown d-inline">
                        <button class="btn btn-success btn-sm dropdown-toggle" type="button" id="statusDropdown<?php echo $row['id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                          <i class="fas fa-tasks"></i>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="statusDropdown<?php echo $row['id']; ?>">
                          <a class="dropdown-item status-change" href="update_task_status.php?id=<?php echo $row['id']; ?>&status=0">
                            <i class="fas fa-clock mr-2"></i> Mark as Pending
                          </a>
                          <a class="dropdown-item status-change" href="update_task_status.php?id=<?php echo $row['id']; ?>&status=1">
                            <i class="fas fa-spinner mr-2"></i> Mark as In Progress
                          </a>
                          <a class="dropdown-item status-change" href="update_task_status.php?id=<?php echo $row['id']; ?>&status=2">
                            <i class="fas fa-check-circle mr-2"></i> Mark as Completed
                          </a>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
                
                <!-- View Task Modal -->
                <!-- View Task Modal -->
<div class="modal fade" id="viewTaskModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewTaskModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTaskModalLabel<?php echo $row['id']; ?>">
                    <i class="fas fa-clipboard-check mr-2"></i>Task Details
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="task-detail-section">
                            <h6 class="task-detail-header">Basic Information</h6>
                            <p><strong>Task:</strong> <?php echo $row['task_name']; ?></p>
                            <p><strong>Project:</strong> <?php echo $row['project_name']; ?></p>
                            <p><strong>Assignee:</strong> <?php echo $row['assignee_name']; ?></p>
                            <p><strong>Department:</strong> <?php echo $row['department']; ?></p>
                            <p><strong>Priority:</strong> 
                                <span class="badge badge-<?php echo strtolower($row['priori']); ?>">
                                    <i class="fas fa-<?php echo strtolower($row['priori']) == 'high' ? 'exclamation-circle' : (strtolower($row['priori']) == 'medium' ? 'dot-circle' : 'arrow-circle-down'); ?>"></i>
                                    <?php echo $row['priori'] ?: 'Medium'; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="task-detail-section">
                            <h6 class="task-detail-header">Timeline</h6>
                            <p><strong>Created On:</strong> <?php echo date('M d, Y', strtotime($row['start_date'])); ?></p>
                            <p><strong>Due Date:</strong> 
                                <?php 
                                $due_date = new DateTime($row['due_date']);
                                $today = new DateTime();
                                if($due_date < $today && $row['status'] != '2') {
                                    echo '<span class="text-danger font-weight-bold"><i class="fas fa-exclamation-circle mr-1"></i>'.date('M d, Y', strtotime($row['due_date'])).' (Overdue)</span>';
                                } else {
                                    echo date('M d, Y', strtotime($row['due_date']));
                                }
                                ?>
                            </p>
                            <p><strong>Status:</strong> 
                                <span class="badge badge-<?php echo $row['status']; ?>">
                                    <i class="fas fa-<?php echo $status_icon; ?> mr-1"></i>
                                    <?php echo $status_text; ?>
                                </span>
                            </p>
                            <?php if(!empty($row['completion_date']) && $row['completion_date'] != '0000-00-00'): ?>
                                <p><strong>Completed On:</strong> <?php echo date('M d, Y', strtotime($row['completion_date'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="task-detail-section">
                    <h6 class="task-detail-header">Task Description</h6>
                    <div class="p-3 bg-light rounded">
                        <?php echo nl2br($row['description']); ?>
                    </div>
                </div>
                
                <?php if (!empty($row['hyperlinks'])): ?>
                <div class="task-detail-section">
                    <h6 class="task-detail-header">Hyperlinks</h6>
                    <div class="task-hyperlinks">
                        <?php 
                        $hyperlinks = explode(',', $row['hyperlinks']);
                        foreach ($hyperlinks as $link) {
                            $link = trim($link);
                            if (!empty($link)) {
                                // Add http:// if not present
                                if (!preg_match("~^(?:f|ht)tps?://~i", $link)) {
                                    $link = "http://" . $link;
                                }
                                echo '<a href="' . $link . '" target="_blank"><i class="fas fa-external-link-alt mr-2"></i>' . htmlspecialchars($link) . '</a>';
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($row['notes'])): ?>
                <div class="task-detail-section">
                    <h6 class="task-detail-header">Notes & Feedback</h6>
                    <div class="p-3 bg-light rounded">
                        <?php echo nl2br($row['notes']); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editTaskModal<?php echo $row['id']; ?>" data-dismiss="modal">
                    <i class="fas fa-edit"></i> Edit Task
                </button>
            </div>
        </div>
    </div>
</div>
                
                <!-- Edit Task Modal -->
                <div class="modal fade" id="editTaskModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editTaskModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                  <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="editTaskModalLabel<?php echo $row['id']; ?>">
                          <i class="fas fa-edit mr-2"></i>Edit Task
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">×</span>
                        </button>
                      </div>
                      <form action="update_task.php" method="POST" class="task-form">
                        <div class="modal-body">
                          <!-- Hidden fields -->
                          <input type="hidden" name="task_id" value="<?php echo $row['id']; ?>">
                          <input type="hidden" name="update_task" value="1">
                          
                          <div class="row">
                            <div class="col-md-6">
                              <div class="form-group">
                                <label for="edit_task_name<?php echo $row['id']; ?>">Task Name</label>
                                <input type="text" class="form-control" id="edit_task_name<?php echo $row['id']; ?>" name="task_name" value="<?php echo $row['task_name']; ?>" required>
                              </div>
                              
                              <div class="form-group">
                                <label for="edit_project_id<?php echo $row['id']; ?>">Project</label>
                                <select class="form-control" id="edit_project_id<?php echo $row['id']; ?>" name="project_id" required>
                                  <option value="">Select Project</option>
                                  <?php foreach ($projects as $project_id => $project_name): ?>
                                    <option value="<?php echo $project_id; ?>" <?php echo ($row['project_id'] == $project_id) ? 'selected' : ''; ?>>
                                      <?php echo $project_name; ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                              
                              <div class="form-group">
                                <label for="edit_assigned_to<?php echo $row['id']; ?>">Assignee</label>
                                <select class="form-control" id="edit_assigned_to<?php echo $row['id']; ?>" name="assigned_to" required>
                                  <option value="">Select Assignee</option>
                                  <?php foreach ($employees as $employee_id => $employee_name): ?>
                                    <option value="<?php echo $employee_id; ?>" <?php echo ($row['assigned_to'] == $employee_id) ? 'selected' : ''; ?>>
                                      <?php echo $employee_name; ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                            </div>
                            
                            <div class="col-md-6">
                              <div class="form-group">
                                <label for="edit_start_date<?php echo $row['id']; ?>">Start Date</label>
                                <input type="date" class="form-control" id="edit_start_date<?php echo $row['id']; ?>" name="start_date" value="<?php echo $row['start_date']; ?>" required>
                              </div>
                              
                              <div class="form-group">
                                <label for="edit_due_date<?php echo $row['id'];?>">Due Date</label>
                                <input type="date" class="form-control" id="edit_due_date<?php echo $row['id'];?>" name="due_date" value="<?php echo $row['due_date']; ?>" required>
                              </div>
                              
                              <div class="form-group">
                                <label for="edit_priority<?php echo $row['id']; ?>">Priority</label>
                                <select class="form-control" id="edit_priority<?php echo $row['id']; ?>" name="priority">
                                  <option value="Low" <?php echo ($row['priori'] == 'Low') ? 'selected' : ''; ?>>Low</option>
                                  <option value="Medium" <?php echo ($row['priori'] == 'Medium' || empty($row['priori'])) ? 'selected' : ''; ?>>Medium</option>
                                  <option value="High" <?php echo ($row['priori'] == 'High') ? 'selected' : ''; ?>>High</option>
                                </select>
                              </div>
                            </div>
                          </div>
                          
                          <div class="form-group">
                            <label for="edit_description<?php echo $row['id']; ?>">Description</label>
                            <textarea class="form-control" id="edit_description<?php echo $row['id']; ?>" name="description" rows="4" required><?php echo $row['description']; ?></textarea>
                          </div>
                          
                          <div class="form-group">
    <label for="edit_hyperlinks<?php echo $row['id']; ?>">Hyperlinks (comma-separated)</label>
    <input type="text" class="form-control" id="edit_hyperlinks<?php echo $row['id']; ?>" name="hyperlinks" value="<?php echo $row['hyperlinks']; ?>" placeholder="Enter hyperlinks separated by commas">
    <small class="form-text text-muted">Example: https://example.com, https://another-site.com</small>
</div>
                          
                          <div class="form-group">
                            <label for="edit_status<?php echo $row['id']; ?>">Status</label>
                            <select class="form-control" id="edit_status<?php echo $row['id']; ?>" name="status" required>
                              <option value="0" <?php echo ($row['status'] == '0') ? 'selected' : ''; ?>>Pending</option>
                              <option value="1" <?php echo ($row['status'] == '1') ? 'selected' : ''; ?>>In Progress</option>
                              <option value="2" <?php echo ($row['status'] == '2') ? 'selected' : ''; ?>>Completed</option>
                            </select>
                          </div>
                          
                          <div class="form-group">
                            <label for="edit_notes<?php echo $row['id']; ?>">Notes & Feedback</label>
                            <textarea class="form-control" id="edit_notes<?php echo $row['id']; ?>" name="notes" rows="3"><?php echo $row['notes']; ?></textarea>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="10" class="text-center">No tasks found for the selected criteria</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Add Task Modal -->
      <div class="modal fade" id="addTaskModal" tabindex="-1" role="dialog" aria-labelledby="addTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="addTaskModalLabel">
                <i class="fas fa-plus-circle mr-2"></i>Add New Task
              </h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">×</span>
              </button>
            </div>
            <form action="" method="POST" class="task-form">
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="task_name">Task Name</label>
                      <input type="text" class="form-control" id="task_name" name="task_name" placeholder="Enter task name" required>
                    </div>
                    
                    <div class="form-group">
                      <label for="project_id">Project</label>
                      <select class="form-control" id="project_id" name="project_id" required>
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $project_id => $project_name): ?>
                          <option value="<?php echo $project_id; ?>">
                            <?php echo $project_name; ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    
                    <div class="form-group">
                      <label for="assigned_to">Assignee</label>
                      <select class="form-control" id="assigned_to" name="assigned_to" required>
                        <option value="">Select Assignee</option>
                        <?php foreach ($employees as $employee_id => $employee_name): ?>
                          <option value="<?php echo $employee_id; ?>">
                            <?php echo $employee_name; ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="start_date">Start Date</label>
                      <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                      <label for="due_date">Due Date</label>
                      <input type="date" class="form-control" id="due_date" name="due_date" required>
                    </div>
                    
                    <div class="form-group">
                      <label for="priority">Priority</label>
                      <select class="form-control" id="priority" name="priority">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                      </select>
                    </div>
                  </div>
                </div>
             
                <div class="form-group">
                  <label for="description">Description</label>
                  <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter task description" required></textarea>
                </div>
                
                <div class="form-group">
    <label for="hyperlinks">Hyperlinks (comma-separated)</label>
    <input type="text" class="form-control" id="hyperlinks" name="hyperlinks" placeholder="Enter hyperlinks separated by commas">
    <small class="form-text text-muted">Example: https://example.com, https://another-site.com</small>
</div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" name="submit_task">Create Task</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <div class="row mt-4">
        <div class="col-lg-12">
          <!-- Due Tasks -->
          <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
              <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-calendar-alt mr-2"></i>Due Tasks
              </h6>
              <span class="badge badge-warning">Pending & In Progress</span>
            </div>
            <div class="card-body due-task ">
              <div class="table-responsive ">
                <table class="table table-hover" id="dueTasksTable">
                  <thead>
                    <tr>
                      <th>Task Name</th>
                      <th>Due Date</th>
                      <th>Days Left</th>
                      <th>Priority</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    // Get due tasks (pending or in progress) based on user role
$due_tasks_query = "SELECT t.*, e.name as assignee_name, p.np as project_name 
FROM tasks t 
LEFT JOIN employee e ON t.assigned_to = e.id 
LEFT JOIN project p ON t.project_id = p.id 
LEFT JOIN project_departments pd ON p.id = pd.project_id 
LEFT JOIN departments d ON pd.department_id = d.id 
WHERE t.status IN ('0', '1')";

// Apply role-based restrictions
if ($user_role == 'User') {
$due_tasks_query .= " AND t.assigned_to = '$user_id'";
} else if ($user_role == 'Team Lead') {
if (!empty($filter_team_member)) {
$due_tasks_query .= " AND t.assigned_to = '$filter_team_member'";
} else {
$due_tasks_query .= " AND e.depart = '$user_department'";
}
}

// Apply filters
if (!empty($filter_department) && ($user_role == 'Admin' || $user_role == 'Manager')) {
$due_tasks_query .= " AND d.id = '$filter_department'";
}

if (!empty($filter_project)) {
$due_tasks_query .= " AND t.project_id = '$filter_project'";
}

$due_tasks_query .= " ORDER BY t.due_date ASC";
                    $due_tasks_result = $conn->query($due_tasks_query);
                    
                    if ($due_tasks_result && $due_tasks_result->num_rows > 0) {
                        while ($task = $due_tasks_result->fetch_assoc()) {
                            // Calculate days left
                            $due_date = new DateTime($task['due_date']);
                            $today = new DateTime();
                            $interval = $today->diff($due_date);
                            $days_left = $interval->format('%R%a');
                            
                            // Determine status text
                            $status_text = ($task['status'] == '0') ? 'Pending' : 'In Progress';
                            
                            // Determine days left class
                            $days_class = '';
                            if ($days_left < 0) {
                                $days_class = 'text-danger font-weight-bold';
                                $days_text = abs($days_left) . ' days overdue';
                            } else if ($days_left == 0) {
                                $days_class = 'text-warning font-weight-bold';
                                $days_text = 'Due today';
                            } else if ($days_left <= 2) {
                                $days_class = 'text-warning';
                                $days_text = $days_left . ' days left';
                            } else {
                                $days_text = $days_left . ' days left';
                            }
                            ?>
                            <tr>
                            <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($task['due_date'])); ?></td>
                                <td class="<?php echo $days_class; ?>"><?php echo $days_text; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($task['priori']); ?>">
                                        <?php echo $task['priori'] ?: 'Medium'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editTaskModal<?php echo $task['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success complete-task" data-task-id="<?php echo $task['id']; ?>">
                                            <i class="fas fa-check"></i> Complete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php
                        }
                    } else {
                        echo '<tr><td colspan="5" class="text-center">No pending or in-progress tasks</td></tr>';
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>  
        </div>

        <div class="col-lg-12">
          <!-- Newly Added Tasks -->
          <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
              <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-plus-circle mr-2"></i>Newly Added Tasks
              </h6>
              <span class="badge badge-info">Last 7 days</span>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover" id="newTasksTable">
                  <thead>
                    <tr>
                      <th>Task Name</th>
                      <th>Project</th>
                      <th>Assignee</th>
                      <th>Start Date</th>
                      <th>Due Date</th>
                      <th>Priority</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php
// Get tasks added in the last 7 days
// Get tasks added in the last 7 days
$new_tasks_query = "SELECT t.*, e.name as assignee_name, p.np as project_name 
                   FROM tasks t 
                   LEFT JOIN employee e ON t.assigned_to = e.id 
                   LEFT JOIN project p ON t.project_id = p.id 
                   LEFT JOIN project_departments pd ON p.id = pd.project_id 
                   LEFT JOIN departments d ON pd.department_id = d.id 
                   WHERE t.start_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";

// Apply role-based restrictions
if ($user_role == 'User') {
    $new_tasks_query .= " AND t.assigned_to = '$user_id'";
} else if ($user_role == 'Team Lead') {
    if (!empty($filter_team_member)) {
        $new_tasks_query .= " AND t.assigned_to = '$filter_team_member'";
    } else {
        $new_tasks_query .= " AND e.depart = '$user_department'";
    }
}

// Apply filters
if (!empty($filter_department) && ($user_role == 'Admin' || $user_role == 'Manager')) {
    $new_tasks_query .= " AND d.id = '$filter_department'";
}

if (!empty($filter_project)) {
    $new_tasks_query .= " AND t.project_id = '$filter_project'";
}

$new_tasks_query .= " ORDER BY t.start_date DESC";
$new_tasks_result = $conn->query($new_tasks_query);

if ($new_tasks_result && $new_tasks_result->num_rows > 0) {
    while ($task = $new_tasks_result->fetch_assoc()) {
        // Get status text
        $status_text = '';
        switch ($task['status']) {
            case '0': $status_text = 'Pending'; break;
            case '1': $status_text = 'In Progress'; break;
            case '2': $status_text = 'Completed'; break;
            default: $status_text = 'Pending';
        }
      
        ?>
        <tr>
            <td><?php echo $task['task_name']; ?></td>
            <td><?php echo $task['project_name']; ?></td>
            <td><?php echo $task['assignee_name']; ?></td>
            <td><?php echo date('M d, Y', strtotime($task['start_date'])); ?></td>
            <td>
                <?php
                $due_date = new DateTime($task['due_date']);
                $today = new DateTime();
                $due_class = '';
                if ($due_date < $today && $task['status'] != '2') {
                    $due_class = 'text-danger font-weight-bold';
                    echo '<span class="'.$due_class.'">'.date('M d, Y', strtotime($task['due_date'])).'</span>';
                } else {
                    echo date('M d, Y', strtotime($task['due_date']));
                }
                ?>
            </td>
            <td>
                <span class="badge badge-<?php echo strtolower($task['priori']); ?>">
                    <?php echo $task['priori'] ?: 'Medium'; ?>
                </span>
            </td>
            <td>
                <span class="badge badge-<?php echo $task['status']; ?>">
                    <?php echo $status_text; ?>
                </span>
            </td>
        </tr>
        <?php
    }
} else {
    echo '<tr><td colspan="7" class="text-center">No new tasks in the last 7 days</td></tr>';
}
?>
                                            </tbody>
                                          </table>
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                  <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success" role="alert">
        <i class="fas fa-check-circle"></i>
        <div>
            <strong>Success!</strong> <?php echo $_SESSION['success_message']; ?>
        </div>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-circle"></i>
        <div>
            <strong>Error!</strong> <?php echo $_SESSION['error_message']; ?>
        </div>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>
                                </div>
                              </div>
                            </section>
                          </div>
                          <?php include './footer.php'; ?>
                          
                          <!-- JS Dependencies -->
                          <script src="./src/js/jquery.min.js"></script>
                          <script src="./src/js/bootstrap.bundle.min.js"></script>
                          <script src="./src/js/adminlte.min.js"></script>
                          <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
                          <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
                          <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
                          <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
                          <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
                          <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
                          <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
                          <script>
                            // Show loading overlay
                            function showLoading() {
                                $('#loadingOverlay').addClass('show');
                            }
                          
                            // Hide loading overlay
                            function hideLoading() {
                                $('#loadingOverlay').removeClass('show');
                            }
                          
                            // Task completion functionality
                            // Task completion functionality
$('.complete-task').on('click', function() {
    const taskId = $(this).data('task-id');
    
    // Confirm before completing
    if (confirm('Mark this task as completed?')) {
        showLoading();
        
        $.ajax({
            url: 'update_task_status.php',
            type: 'POST',
            data: {
                task_id: taskId,
                status: 2 // Mark as completed
            },
            dataType: 'json',
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    // Remove the row from the due tasks table
                    $(`button[data-task-id="${taskId}"]`).closest('tr').fadeOut(500, function() {
                        $(this).remove();
                        
                        // Update the stats
                        let completedCount = parseInt($('.stat-card.completed .stat-value').text()) || 0;
                        let pendingCount = parseInt($('.stat-card.pending .stat-value').text()) || 0;
                        let inProgressCount = parseInt($('.stat-card.progresss .stat-value').text()) || 0;
                        
                        // Increment completed count
                        $('.stat-card.completed .stat-value').text(completedCount + 1);
                        
                        // Update the main task table status
                        $(`#taskTable tr[data-task-id="${taskId}"] td:nth-child(9)`).html(
                            '<span class="badge badge-2"><i class="fas fa-check-circle mr-1"></i> Completed</span>'
                        );
                        
                        // Show success message
                        alert('Task marked as completed successfully!');
                    });
                }
                 else {
                    alert('Error updating task status: ' + response.error);
                }
            },
            error: function() {
                hideLoading();
                alert('Error connecting to server. Please try again.');
            }
        });
    }
});
                          
                            // Initialize DataTables for the new tables
                            $('#newTasksTable').DataTable({
                                order: [[3, 'desc']], // Sort by start date (newest first)
                                pageLength: 5,
                                lengthMenu: [5, 10, 25, 50],
                                language: {
                                    search: "Search new tasks:"
                                }
                            });
                          
                            $('#dueTasksTable').DataTable({
                                order: [[1, 'asc']], // Sort by due date (soonest first)
                                pageLength: 5,
                                lengthMenu: [5, 10, 25, 50],
                                language: {
                                    search: "Search due tasks:"
                                }
                            });
                          </script>
                         <script>
// Function to filter employees based on department
function filterEmployeesByDepartment(departmentId) {
    showLoading();
    $.ajax({
        url: 'get_department_employees.php',
        type: 'POST',
        data: {
            department_id: departmentId
        },
        success: function(response) {
            $('#assigned_to').html(response);
            $('#edit_assigned_to').html(response);
            hideLoading();
        },
        error: function() {
            hideLoading();
            alert('Error fetching employees');
        }
    });
}

// Add event listener to department dropdown
$(document).ready(function() {
    $('#department').on('change', function() {
        filterEmployeesByDepartment($(this).val());
    });
    
    // Initialize with current department if selected
    var initialDept = $('#department').val();
    if (initialDept) {
        filterEmployeesByDepartment(initialDept);
    }
});
</script>
                          </div>
                          </body>
                          </html>


