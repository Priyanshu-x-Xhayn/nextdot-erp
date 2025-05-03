<?php 
include './backend/connect.php';
include 'auth_check.php';

// Initialize message variables
$success_message = "";
$error_message = "";

// Check if we need to create the project_managers table
$table_check = $conn->query("SHOW TABLES LIKE 'project_managers'");
if ($table_check->num_rows == 0) {
    // Create the project_managers junction table if it doesn't exist
    $conn->query("CREATE TABLE project_managers (
        id INT(11) NOT NULL AUTO_INCREMENT,
        project_id INT(11) NOT NULL,
        employee_id INT(11) NOT NULL,
        role VARCHAR(50) DEFAULT 'Project Manager',
        PRIMARY KEY (id),
        UNIQUE KEY project_manager (project_id, employee_id),
        FOREIGN KEY (project_id) REFERENCES project(id) ON DELETE CASCADE,
        FOREIGN KEY (employee_id) REFERENCES employee(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Check if we need to create the project_departments table
$table_check = $conn->query("SHOW TABLES LIKE 'project_departments'");
if ($table_check->num_rows == 0) {
    // Create the project_departments junction table if it doesn't exist
    $conn->query("CREATE TABLE project_departments (
        id INT(11) NOT NULL AUTO_INCREMENT,
        project_id INT(11) NOT NULL,
        department_id INT(11) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY project_dept (project_id, department_id),
        FOREIGN KEY (project_id) REFERENCES project(id) ON DELETE CASCADE,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Handle form submission - now implementing PRG pattern
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $sd = $_POST['sd'] ?? '';
    $np = $_POST['np'] ?? '';
    $nc = $_POST['nc'] ?? '';
    $pm = $_POST['pm'] ?? ''; // Now this is employee_id
    $rc = $_POST['rc'] ?? '';
    $cd = $_POST['cd'] ?? ''; // Now optional
    $departments = isset($_POST['departments']) ? $_POST['departments'] : []; // Array for multiple departments
    
    // Validate required fields
    if (empty($sd) || empty($np) || empty($nc) || empty($pm) || empty($rc) || empty($departments)) {
        $error_message = "Please fill all required fields and select at least one department!";
    } else {
        // Begin transaction for inserting multiple department relations
        $conn->begin_transaction();
        $transaction_success = true;
        
        try {
            // Insert base project record without pm field
            $sql_insert = "INSERT INTO project (sd, np, nc, rc, cd) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql_insert);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("sssss", $sd, $np, $nc, $rc, $cd);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $project_id = $stmt->insert_id;
            $stmt->close();
            
            // Insert project manager relation
            $sql_pm = "INSERT INTO project_managers (project_id, employee_id) VALUES (?, ?)";
            $stmt_pm = $conn->prepare($sql_pm);
            
            if (!$stmt_pm) {
                throw new Exception("Prepare failed for project manager: " . $conn->error);
            }
            
            $stmt_pm->bind_param("ii", $project_id, $pm);
            
            if (!$stmt_pm->execute()) {
                throw new Exception("Execute failed for project manager: " . $stmt_pm->error);
            }
            
            $stmt_pm->close();
            
            // Insert project_department relations
            foreach ($departments as $dept_id) {
                $sql_dept = "INSERT INTO project_departments (project_id, department_id) VALUES (?, ?)";
                $stmt_dept = $conn->prepare($sql_dept);
                
                if (!$stmt_dept) {
                    throw new Exception("Prepare failed for department relation: " . $conn->error);
                }
                
                $stmt_dept->bind_param("ii", $project_id, $dept_id);
                
                if (!$stmt_dept->execute()) {
                    throw new Exception("Execute failed for department relation: " . $stmt_dept->error);
                }
                
                $stmt_dept->close();
            }
            
            // Commit transaction if all statements executed successfully
            $conn->commit();
            
            // Success - redirect to prevent form resubmission
            header("Location: project.php?success=1");
            exit();
            
        } catch (Exception $e) {
            // Roll back transaction on error
            $conn->rollback();
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Handle success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Project added successfully!";
}

if (isset($_GET['update_success']) && $_GET['update_success'] == '1') {
    $success_message = "Project updated successfully!";
}

if (isset($_GET['delete_success']) && $_GET['delete_success'] == '1') {
    $success_message = "Project deleted successfully!";
}

// Fetch departments from departments table
$departments_query = "SELECT * FROM departments ORDER BY department_name";
$departments_result = $conn->query($departments_query);
$departments = [];
if ($departments_result) {
    while ($dept = $departments_result->fetch_assoc()) {
        $departments[$dept['id']] = $dept['department_name'];
    }
}

// Fetch projects with their departments and managers
$sql_projects = "SELECT p.*, 
                 GROUP_CONCAT(DISTINCT d.department_name SEPARATOR ', ') as departments,
                 GROUP_CONCAT(DISTINCT d.id SEPARATOR ',') as department_ids,
                 e.name as manager_name, e.id as manager_id
                 FROM project p
                 LEFT JOIN project_departments pd ON p.id = pd.project_id
                 LEFT JOIN departments d ON pd.department_id = d.id
                 LEFT JOIN project_managers pm ON p.id = pm.project_id
                 LEFT JOIN employee e ON pm.employee_id = e.id
                 GROUP BY p.id
                 ORDER BY p.id DESC";
$result_projects = $conn->query($sql_projects);

// Function to get departments for a project
function getProjectDepartments($conn, $project_id) {
    $stmt = $conn->prepare("SELECT d.id, d.department_name 
                           FROM departments d
                           JOIN project_departments pd ON d.id = pd.department_id
                           WHERE pd.project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[$row['id']] = $row['department_name'];
    }
    $stmt->close();
    return $departments;
}

$projects_by_department = [];

// Process projects into department groups
if ($result_projects && $result_projects->num_rows > 0) {
    while ($project = $result_projects->fetch_assoc()) {
        // Get all departments for this project
        $project_departments = [];
        if (!empty($project['department_ids'])) {
            $dept_ids = explode(',', $project['department_ids']);
            foreach ($dept_ids as $dept_id) {
                if (isset($departments[$dept_id])) {
                    $project_departments[$dept_id] = $departments[$dept_id];
                }
            }
        }
        
        // Add project to each department's projects list
        foreach ($project_departments as $dept_id => $dept_name) {
            if (!isset($projects_by_department[$dept_id])) {
                $projects_by_department[$dept_id] = [
                    'name' => $dept_name,
                    'projects' => []
                ];
            }
            
            // Add this department list to the project data
            $project['department_list'] = $project_departments;
            $projects_by_department[$dept_id]['projects'][] = $project;
        }
    }
}

// Calculate project status counts for chart
$status_query = "SELECT 
                    SUM(CASE WHEN CURDATE() BETWEEN sd AND IFNULL(cd, DATE_ADD(CURDATE(), INTERVAL 30 DAY)) THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN CURDATE() < sd THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN cd IS NOT NULL AND CURDATE() > cd THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN cd IS NULL THEN 1 ELSE 0 END) as ongoing
                FROM project
                WHERE sd IS NOT NULL";
$status_result = $conn->query($status_query);
$status_data = $status_result->fetch_assoc();

// Ensure we have non-null values for the chart
$in_progress = $status_data['in_progress'] ?? 0;
$pending = $status_data['pending'] ?? 0;
$completed = $status_data['completed'] ?? 0;
$ongoing = $status_data['ongoing'] ?? 0;

// Calculate projects by department for chart
$dept_counts_query = "SELECT d.department_name, COUNT(pd.project_id) as count
                      FROM departments d
                      LEFT JOIN project_departments pd ON d.id = pd.department_id
                      GROUP BY d.id
                      ORDER BY count DESC";
$dept_counts_result = $conn->query($dept_counts_query);

$dept_labels = [];
$dept_counts = [];

if ($dept_counts_result) {
    while ($row = $dept_counts_result->fetch_assoc()) {
        $dept_labels[] = $row['department_name'];
        $dept_counts[] = (int)$row['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>ProManager - Enterprise Project Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="./src/css/adminlte.min.css">
<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>

/* Extend the existing theme variables */
:root {
  --primary-color: #072e63;
  --primary-light: #1a4275;
  --primary-dark: #051e42;
  --accent-color: #f8c300;
  --accent-light: #ffdf80;
  --accent-dark: #d9aa00;
  --text-color: #ffffff;
  --text-muted: #cccccc;
  --danger-color: #e74a3b;
  --success-color: #1cc88a;
  --info-color: #36b9cc;
  --warning-color: #f6c23e;
  --secondary-color: #5a5c69;
  --bg-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
  --card-gradient: linear-gradient(135deg, rgba(7, 46, 99, 0.95) 0%, rgba(5, 30, 66, 0.98) 100%);
  --border-radius: 12px;
  --border-radius-sm: 8px;
  --shadow: 0 10px 20px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0, 0, 0, 0.06);
  --hover-shadow: 0 15px 30px rgba(0, 0, 0, 0.15), 0 8px 12px rgba(0, 0, 0, 0.08);
  --card-shadow: 0 15px 35px rgba(0, 0, 0, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07);
  --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  --glass-bg: rgba(255, 255, 255, 0.1);
  --glass-border: 1px solid rgba(255, 255, 255, 0.18);
  --blur-effect: blur(10px);
}

/* Project Modal Styling */
#adminModal .modal-content {
  border-radius: var(--border-radius);
  border: none;
  box-shadow: var(--shadow);
  overflow: hidden;
}

#adminModal .modal-header {
  background: var(--bg-gradient);
  color: var(--text-color);
  border-bottom: none;
  padding: 1.5rem;
}

#adminModal .modal-title {
  font-weight: 600;
  font-size: 1.2rem;
  position: relative;
  padding-left: 28px;
}
.select2 {
  width : 100% !important;

}
.select2-selection--single, .select2-selection--multiple {
  height : 2rem !important;
}
#adminModal .modal-title::before {
  content: '\f187';
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  color: var(--accent-color);
}

#adminModal .btn-close {
  color: white;
  opacity: 1;
  filter: brightness(0) invert(1);
}

#adminModal .modal-body {
  padding: 1.5rem;
}

#adminModal .form-label {
  font-weight: 500;
  color: var(--primary-dark);
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

#adminModal .form-control,
#adminModal .form-select {
  border-radius: var(--border-radius-sm);
  border: 1px solid rgba(7, 46, 99, 0.1);
  padding: 0.7rem 1rem;
  transition: var(--transition);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
}

#adminModal .form-control:focus,
#adminModal .form-select:focus {
  border-color: var(--primary-light);
  box-shadow: 0 0 0 0.2rem rgba(7, 46, 99, 0.1);
}

#adminModal .modal-footer {
  border-top: 1px solid rgba(0, 0, 0, 0.05);
  padding: 1.2rem 1.5rem;
}

#adminModal .btn-primary {
  background: var(--primary-color);
  border: none;
  border-radius: var(--border-radius-sm);
  padding: 0.6rem 1.5rem;
  font-weight: 500;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: var(--transition);
}

#adminModal .btn-primary:hover {
  background: var(--primary-light);
  transform: translateY(-2px);
  box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
}

/* Charts Grid Styling */
.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.chart-card {
  border: none;
  border-radius: var(--border-radius);
  box-shadow: var(--card-shadow);
  overflow: hidden;
  transition: var(--transition);
  height: 100%;
}

.chart-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--hover-shadow);
}

.chart-card .card-header {
  background: var(--bg-gradient);
  color: var(--text-color);
  border-bottom: none;
  padding: 1rem 1.5rem;
  font-weight: 600;
  font-size: 1.1rem;
}

.chart-card .card-header span {
  display: flex;
  align-items: center;
}

.chart-card .card-header i {
  color: var(--accent-color);
  margin-right: 0.5rem;
}

.chart-card .card-body {
  padding: 1.5rem;
  background: white;
}

.chart-container {
  position: relative;
  height: auto;
  width: 100%;
}

/* Stats Grid Styling */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: var(--card-gradient);
  border-radius: var(--border-radius);
  padding: 1.5rem;
  color: var(--text-color);
  text-align: center;
  box-shadow: var(--card-shadow);
  cursor: pointer;
  transition: var(--transition);
  position: relative;
  overflow: hidden;
  border: var(--glass-border);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
  transform: rotate(30deg);
  pointer-events: none;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--hover-shadow);
}

.stat-card h5 {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  position: relative;
  display: inline-block;
}

.stat-card h5::after {
  content: '';
  position: absolute;
  bottom: -5px;
  left: 50%;
  transform: translateX(-50%);
  width: 30px;
  height: 3px;
  background: var(--accent-color);
  border-radius: 2px;
}

.stat-card .stat-value {
  font-size: 2.5rem;
  font-weight: 700;
  margin-top: 0.5rem;
  color: var(--accent-color);
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

/* Department Project Tables */
.department-table {
  border-radius: var(--border-radius);
  padding: 1.5rem;
  box-shadow: var(--shadow);
  margin-bottom: 2rem;
  overflow: hidden;
}

.table-responsive::before {
  width:100%;
}

.department-table h3 {
  color: var(--primary-dark);
  font-weight: 600;
  margin-bottom: 1.2rem;
  position: relative;
  display: inline-block;
  padding-bottom: 0.5rem;
}

.department-table h3::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  height: 4px;
  width: 60px;
  background: var(--accent-color);
  border-radius: 2px;
}


.department-table table {
  margin-bottom: 0;
  border-collapse: separate;
  border-spacing: 0;
  width: 100%;
  box-shadow: var(--shadow);
}

.department-table thead tr {
  background: linear-gradient(45deg, #a3cdff, transparent, #0047ab, transparent, #a3cdff);
  color: var(--text-color);
}

.department-table th {
  padding: 1rem;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.5px;
  border: none;
  white-space: nowrap;
  background : transparent;
}

.department-table tbody tr {
  transition: var(--transition);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.department-table tbody tr:hover {
  background: rgba(7, 46, 99, 0.02);
}

.department-table td {
  padding: 0.8rem 1rem;
  vertical-align: middle;
  border-top: none;
  font-size: 0.95rem;
  background : transparent;
}
.department-table tr:hover {
  background: #fff;
}

/* Action Buttons in Tables */
.department-table .btn-sm {
  padding: 0.35rem 0.8rem;
  font-size: 0.8rem;
  border-radius: var(--border-radius-sm);
  transition: var(--transition);
  margin: 0 3px;
  border: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.department-table .btn-warning {
  background: var(--warning-color);
  color: var(--primary-dark);
}

.department-table .btn-danger {
  background: var(--danger-color);
  color: white;
}

.department-table .btn-sm:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Edit Project Modals */
[id^="editModal_"] .modal-content {
  border-radius: var(--border-radius);
  border: none;
  box-shadow: var(--shadow);
  overflow: hidden;
}

[id^="editModal_"] .modal-header {
  background: var(--warning-color);
  color: var(--primary-dark);
  border-bottom: none;
  padding: 1.5rem;
}

[id^="editModal_"] .modal-header h5 {
  font-weight: 600;
  position: relative;
  padding-left: 28px;
}

[id^="editModal_"] .modal-header h5::before {
  content: '\f044';
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  color: var(--primary-dark);
}

[id^="editModal_"] .btn-close {
  color: var(--primary-dark);
  opacity: 1;
}

[id^="editModal_"] .modal-body {
  padding: 1.5rem;
}

[id^="editModal_"] label {
  font-weight: 500;
  color: var(--primary-dark);
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

[id^="editModal_"] .form-control {
  border-radius: var(--border-radius-sm);
  border: 1px solid rgba(7, 46, 99, 0.1);
  padding: 0.7rem 1rem;
  transition: var(--transition);
}

[id^="editModal_"] .form-control:focus {
  border-color: var(--warning-color);
  box-shadow: 0 0 0 0.2rem rgba(246, 194, 62, 0.25);
}

[id^="editModal_"] .modal-footer {
  border-top: 1px solid rgba(0, 0, 0, 0.05);
  padding: 1.2rem 1.5rem;
}

[id^="editModal_"] .btn-secondary {
  background: var(--secondary-color);
  border: none;
  border-radius: var(--border-radius-sm);
}

[id^="editModal_"] .btn-primary {
  background: var(--primary-color);
  border: none;
  border-radius: var(--border-radius-sm);
}

/* Delete Form Styling */
form[action=""] {
  display: inline-block;
}

/* Responsive Adjustments */
@media (max-width: 992px) {
  .charts-grid {
    grid-template-columns: 1fr;
  }
  
  . {
    height: auto;
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .department-table {
    padding: 1rem;
  }
  
  .department-table h3 {
    font-size: 1.3rem;
  }
  
  .department-table table {
    display: block;
    overflow-x: auto;
    white-space: nowrap;
  }
  
  .department-table td,
  .department-table th {
    padding: 0.7rem;
  }
  
  [id^="editModal_"] .modal-dialog {
    margin: 0.5rem;
  }
}

@media (max-width: 576px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .stat-card {
    padding: 1.2rem;
  }
  
  .stat-card .stat-value {
    font-size: 2rem;
  }
  
  .department-table h3 {
    font-size: 1.2rem;
  }
  
  .department-table .btn-sm {
    padding: 0.25rem 0.6rem;
    font-size: 0.75rem;
  }
  
  #adminModal .row [class*="col-"] {
    padding: 0 0.5rem;
  }
}

/* Animation for stats cards */
@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(248, 195, 0, 0.4);
  }
  70% {
    box-shadow: 0 0 0 10px rgba(248, 195, 0, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(248, 195, 0, 0);
  }
}

.stat-card:hover {
  animation: pulse 1.5s infinite;
}
/* Manager link styling */
.manager-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.manager-link:hover {
    color: var(--primary-light);
    text-decoration: underline;
}

/* Required and optional field labels */
.required-field::after {
    content: ' *';
    color: #e74a3b;
    font-weight: bold;
}

.optional-field::after {
    content: ' (optional)';
    color: #6c757d;
    font-size: 0.85em;
    font-weight: normal;
}

/* Department tags */
.department-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 5px;
}

.department-tag {
    background: #1a4275;
    color: white;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 0.8rem;
    display: inline-block;
}

/* Select2 customization */
.select2-container--default .select2-selection--multiple {
    border-radius: var(--border-radius-sm);
    border: 1px solid rgba(7, 46, 99, 0.1);
    padding: 0.4rem 0.5rem;
    min-height: 42px;
}

.select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 0.2rem rgba(7, 46, 99, 0.1);
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 4px;
    padding: 3px 8px;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: white;
    margin-right: 5px;
}

/* Auto-hide alerts */
.auto-hide-alert {
    animation: fadeOut 1s ease 4s forwards;
}

@keyframes fadeOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
        height: 0;
        padding: 0;
        margin: 0;
        overflow: hidden;
    }
}
  </style>
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">
<?php include './header.php'; ?>
<div class="content-wrapper">
<div class="container-fluid">

<!-- Display success/error messages -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success auto-hide-alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
</div>
<?php endif; ?>

<div class="header-bar d-flex justify-content-between align-items-center flex-wrap my-3">
          <h2 class="mb-2 mb-md-0">Task Management Tracker</h2>
          <button class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#adminModal">
                <i class="fas fa-plus me-2"></i> New Project
          </button>
</div>

<!-- Modal for adding new project -->
<div class="modal fade" id="adminModal" tabindex="-1" aria-labelledby="adminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adminModalLabel">Add Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="project.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="sd" class="form-label required-field">Start Date</label>
                            <input type="date" class="form-control" id="sd" name="sd" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="np" class="form-label required-field">Name Of Project</label>
                            <input type="text" class="form-control" id="np" name="np" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nc" class="form-label required-field">Name Of Client</label>
                            <input type="text" class="form-control" id="nc" name="nc" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="pm" class="form-label required-field">Project Manager</label>
                            <select class="form-control select2-employees" name="pm" id="pm" required>
                                <option value="">Select Project Manager</option>
                                <?php
                                // Fetch employees who could be project managers (excluding basic users)
                                $manager_query = "SELECT id, name, role FROM employee 
                                                WHERE role IN ('Admin', 'Manager', 'Team Lead') OR desig LIKE '%manager%' OR desig LIKE '%lead%'
                                                ORDER BY name";
                                $manager_result = $conn->query($manager_query);
                                if ($manager_result && $manager_result->num_rows > 0) {
                                    while ($manager = $manager_result->fetch_assoc()) {
                                        echo '<option value="' . $manager['id'] . '">' . 
                                             htmlspecialchars($manager['name']) . 
                                             ' (' . htmlspecialchars($manager['role']) . ')</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="rc" class="form-label required-field">Requirement of Client</label>
                            <input type="text" class="form-control" id="rc" name="rc" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cd" class="form-label optional-field">Completion Date</label>
                            <input type="date" class="form-control" id="cd" name="cd">
                            <small class="text-muted">Leave blank if completion date is unknown</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="departments" class="form-label required-field">Departments</label>
                            <select name="departments[]" id="departments" class="form-select select2" multiple required>
                                <?php foreach ($departments as $dept_id => $dept_name): ?>
                                <option value="<?php echo $dept_id; ?>">
                                    <?php echo htmlspecialchars($dept_name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple departments or use the search box</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Project Status Chart -->
<div class="charts-grid">
    <div class="chart-card card">
        <div class="card-header"><span><i class="fas fa-chart-pie me-2"></i>Project Status</span></div>
        <div class="card-body">
            <div class="">
                <canvas id="projectStatusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Department Distribution Chart -->
    <div class="chart-card card">
        <div class="card-header"><span><i class="fas fa-chart-bar me-2"></i>Department Distribution</span></div>
        <div class="card-body">
            <div class="">
                <canvas id="departmentChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Department Statistics and Charts -->
<?php if (count($projects_by_department) > 0): ?>
<div class="stats-grid">
    <?php foreach ($projects_by_department as $dept_id => $dept_data): ?>
    <div class="stat-card" onclick="showTable('<?php echo md5($dept_id); ?>')">
        <h5><?php echo htmlspecialchars($dept_data['name']); ?></h5>
        <div class="stat-value"><?php echo count($dept_data['projects']); ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="table-responsive">
    <div class="wrapper_table">
        <?php foreach ($projects_by_department as $dept_id => $dept_data): ?>
        <div class="department-table overflow-x-scroll" id="table_<?php echo md5($dept_id); ?>" style="display: none;">
            <h3><?php echo htmlspecialchars($dept_data['name']); ?> Projects</h3>
            <table class="table table-bordered" id="table_<?php echo md5($dept_id); ?>_data">
                <thead>
                    <tr>
                        <th>Start Date</th>
                        <th>Name Of Project</th>
                        <th>Name Of Client</th>
                        <th>Project Manager</th>
                        <th>Requirement</th>
                        <th>Completion Date</th>
                        <th>Departments</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($dept_data['projects'] as $project): ?>
<tr>
    <td><?php echo htmlspecialchars($project['sd']); ?></td>
    <td><?php echo htmlspecialchars($project['np']); ?></td>
    <td><?php echo htmlspecialchars($project['nc']); ?></td>
    <td>
        <?php if (!empty($project['manager_name'])): ?>
        <a href="employee_profile.php?id=<?php echo $project['manager_id']; ?>" class="manager-link">
            <?php echo htmlspecialchars($project['manager_name']); ?>
        </a>
        <?php else: ?>
        <span class="text-muted">Not Assigned</span>
        <?php endif; ?>
    </td>
    <td><?php echo htmlspecialchars($project['rc']); ?></td>
    <td><?php echo !empty($project['cd']) ? htmlspecialchars($project['cd']) : '<span class="text-muted">Not set</span>'; ?></td>
    <td>
        <div class="department-tags">
            <?php foreach ($project['department_list'] as $dept_id => $dept_name): ?>
                <span class="department-tag"><?php echo htmlspecialchars($dept_name); ?></span>
            <?php endforeach; ?>
        </div>
    </td>
    <td>
        <?php
        $project_id = htmlspecialchars($project['id']); 
        $editModalTargetId = 'editModal_' . $project_id;
        $view_link = "profile.php?id=" . urlencode($project_id); 
        ?>
        <a href="<?php echo $view_link; ?>" class="btn btn-info btn-sm" title="View Details">
            <i class="bi bi-eye"></i> <span class="d-none d-md-inline">View</span> 
        </a>

        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal_<?php echo $project['id']; ?>">
            <i class="bi bi-pencil-square"></i>
        </button>
        <form action="delete_project.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this project?');">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($project['id']); ?>"> 
            <button type="submit" name="delete" class="btn btn-danger btn-sm">
                <i class="fa-solid fa-trash"></i>
            </button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endforeach; ?>
</div>
</div>
<?php else: ?>
<!-- Empty state when no projects exist -->
<div class="no-projects">
    <i class="fas fa-folder-open empty-animation"></i>
    <h4>No Projects Found</h4>
    <p>Start by adding your first project using the "New Project" button above.</p>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adminModal">
        <i class="fas fa-plus me-2"></i> Add First Project
    </button>
</div>
<?php endif; ?>

<!-- Edit Modals for each project -->
<?php foreach ($projects_by_department as $dept_id => $dept_data): ?>
    <?php 
    $processed_projects = []; 
    ?>
    
    <?php foreach ($dept_data['projects'] as $project): ?>
        <?php if (in_array($project['id'], $processed_projects)) continue; ?>
        <?php $processed_projects[] = $project['id']; ?>
        
        <div class="modal fade" id="editModal_<?php echo $project['id']; ?>" tabindex="-1" aria-labelledby="editModal_<?php echo $project['id']; ?>Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="editModal_<?php echo $project['id']; ?>Label">Edit Project Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <form action="update_project.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?php echo $project['id']; ?>">

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="required-field">Start Date</label>
                                    <input type="date" class="form-control" name="sd" value="<?php echo $project['sd']; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="required-field">Name Of Project</label>
                                    <input type="text" class="form-control" name="np" value="<?php echo htmlspecialchars($project['np']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="required-field">Name Of Client</label>
                                    <input type="text" class="form-control" name="nc" value="<?php echo htmlspecialchars($project['nc']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="required-field">Project Manager</label>
                                    <select class="form-control select2-employees" name="pm" required>
                                        <option value="">Select Project Manager</option>
                                        <?php
                                        $manager_query = "SELECT id, name, role FROM employee 
                                                         WHERE role IN ('Admin', 'Manager', 'Team Lead') OR desig LIKE '%manager%' OR desig LIKE '%lead%'
                                                         ORDER BY name";
                                        $manager_result = $conn->query($manager_query);
                                        if ($manager_result && $manager_result->num_rows > 0) {
                                            while ($manager = $manager_result->fetch_assoc()) {
                                                $selected = ($manager['id'] == $project['manager_id']) ? 'selected' : '';
                                                echo '<option value="' . $manager['id'] . '" ' . $selected . '>' . 
                                                     htmlspecialchars($manager['name']) . 
                                                     ' (' . htmlspecialchars($manager['role']) . ')</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="required-field">Requirement of Client</label>
                                    <input type="text" class="form-control" name="rc" value="<?php echo htmlspecialchars($project['rc']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="optional-field">Completion Date</label>
                                    <input type="date" class="form-control" name="cd" value="<?php echo $project['cd']; ?>">
                                    <small class="text-muted">Leave blank if completion date is unknown</small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="required-field">Departments</label>
                                    <select class="form-control select2-edit" name="departments[]" multiple required>
                                        <?php foreach ($departments as $dept_id_option => $dept_name_option): ?>
                                        <option value="<?php echo $dept_id_option; ?>" 
                                                <?php echo isset($project['department_list'][$dept_id_option]) ? "selected" : ""; ?>>
                                            <?php echo htmlspecialchars($dept_name_option); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple departments or use the search box</small>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="update" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endforeach; ?>


<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery and AdminLTE -->
<script src="./src/js/jquery.min.js"></script>
<script src="./src/js/bootstrap.bundle.min.js"></script>
<script src="./src/js/adminlte.min.js"></script>

<!-- Select2 for better multi-select -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Department Table Toggle -->
<script>
  function showTable(id) {
    document.querySelectorAll('.department-table').forEach(div => {
      div.style.display = 'none';
    });

    const selectedTable = document.getElementById('table_' + id);
    if (selectedTable) {
      selectedTable.style.display = 'block';
      selectedTable.scrollIntoView({ behavior: 'smooth' });
    }
  }
  
  // Auto-hide alerts after 5 seconds
  document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(function(alert) {
        alert.classList.add('auto-hide-alert');
      });
    }, 500);
    
    // Initialize Select2 for dropdowns
    $('.select2').select2({
      placeholder: "Select departments",
      allowClear: true,
      dropdownParent: $('#adminModal')
    });
    
    // Initialize Select2 for employee search
    $('.select2-employees').select2({
      placeholder: "Search for a project manager",
      allowClear: true,
      dropdownParent: $('#adminModal')
    });
    
    // Initialize Select2 for edit modals
    $('.modal').on('shown.bs.modal', function() {
      $(this).find('.select2-edit').select2({
        placeholder: "Select departments",
        allowClear: true,
        dropdownParent: $(this).closest('.modal')
      });
      
      $(this).find('.select2-employees').select2({
        placeholder: "Search for a project manager",
        allowClear: true,
        dropdownParent: $(this).closest('.modal')
      });
    });
  });
  
  // Show first department table by default if it exists
  document.addEventListener('DOMContentLoaded', function() {
    const firstTable = document.querySelector('.department-table');
    if (firstTable) {
      firstTable.style.display = 'block';
    }
  });
</script>
<!-- DataTables Export & Actions Script -->
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.0/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.0/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.0/js/buttons.print.min.js"></script>

<script>
  $(document).ready(function() {
    // Initialize DataTable for each department table
    <?php foreach ($projects_by_department as $dept_id => $dept_data): ?>
    $('#table_<?php echo md5($dept_id); ?>_data').DataTable({
      dom: 'Bfrtip',
      buttons: [
        'copy', 'csv', 'excel', 'pdf', 'print'
      ],
      responsive: true
    });
    <?php endforeach; ?>
  });
</script>

<!-- Dynamic Chart Initialization -->
<script>
  document.addEventListener("DOMContentLoaded", function () {
    // Project Status Chart - Using actual data from database
    const ctxStatus = document.getElementById('projectStatusChart').getContext('2d');
    const projectStatusChart = new Chart(ctxStatus, {
      type: 'doughnut',
      data: {
        labels: ['In Progress', 'Pending', 'Completed', 'Ongoing (No End Date)'],
        datasets: [{
          data: [
            <?php echo $in_progress; ?>,
            <?php echo $pending; ?>,
            <?php echo $completed; ?>,
            <?php echo $ongoing; ?>
          ],
          backgroundColor: ['#0a2463', '#d4af37', '#68b1e0', '#4a8fe7'],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        cutout: '70%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 20,
              font: {
                size: 12,
                weight: 'bold'
              }
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const label = context.label || '';
                const value = context.raw || 0;
                const total = context.dataset.data.reduce((acc, data) => acc + data, 0);
                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                return `${label}: ${value} (${percentage}%)`;
              }
            }
          }
        },
        animation: {
          animateScale: true,
          animateRotate: true
        }
      }
    });

    // Department Distribution Chart
    const ctxDept = document.getElementById('departmentChart').getContext('2d');
    const deptChart = new Chart(ctxDept, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($dept_labels); ?>,
        datasets: [{
          label: 'Number of Projects',
          data: <?php echo json_encode($dept_counts); ?>,
          backgroundColor: [
            '#3e5cb0', '#0a2463', '#d4af37', '#68b1e0', '#4a8fe7',
            '#2c3e50', '#e74c3c', '#9b59b6', '#34495e', '#16a085'
          ],
          borderRadius: 6,
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.05)'
            },
            ticks: {
              precision: 0
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        },
        plugins: {
          legend: { display: false },
          title: {
            display: true,
            text: 'Projects by Department',
            font: {
              size: 16
            }
          }
        },
        animation: {
          duration: 2000
        }
      }
    });
  });
</script>
  </div>
  </div>
  
  <?php include './footer.php'; ?>
  </body>
</html>
<?php $conn->close(); ?>