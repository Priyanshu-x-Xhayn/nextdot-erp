<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Department Management</title>

  <!-- Stylesheets -->
  <link rel="stylesheet" href="./src/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
  <style>
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

/* Global Styles */
body {
  background: #f8faff;
  font-family: 'Inter', 'Segoe UI', sans-serif;
}

/* Modern Container */
.container, .container-fluid {
  padding: 1.5rem;
}

/* ==== METRIC CARDS STYLING ==== */
.row {
  margin-bottom: 2rem;
}

.ndt-metric-card {
  height: 100%;
  border-radius: var(--border-radius);
  overflow: hidden;
  position: relative;
  transition: var(--transition);
  box-shadow: var(--card-shadow);
  background: var(--card-gradient);
  backdrop-filter: var(--blur-effect);
  -webkit-backdrop-filter: var(--blur-effect);
  border: var(--glass-border);
  color: var(--text-color);
}

.ndt-metric-card:before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
  transform: rotate(30deg);
  pointer-events: none;
  z-index: 1;
}

.ndt-metric-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--hover-shadow);
}

.ndt-card-body {
  padding: 1.8rem;
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
}

.ndt-card-icon {
  background: rgba(248, 195, 0, 0.15);
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 1.2rem;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
  position: relative;
  overflow: hidden;
}

.ndt-card-icon:after {
  content: '';
  position: absolute;
  width: 100%;
  height: 3px;
  bottom: 0;
  left: 0;
  background: var(--accent-color);
  box-shadow: 0 0 15px 5px rgba(248, 195, 0, 0.3);
}

.ndt-card-icon i {
  font-size: 1.5rem;
  color: var(--accent-color);
}

.ndt-card-content {
  flex-grow: 1;
}

table{
  --bs-table-bg : transparent !important ;
}

.ndt-card-value {
  font-size: 1.4rem;
  font-weight: 700;
  margin-bottom: 0.3rem;
  color: var(--text-color);
  position: relative;
  display: inline-block;
}

.ndt-card-value:after {
  content: '';
  position: absolute;
  bottom: -4px;
  left: 0;
  width: 40px;
  height: 3px;
  background: var(--accent-color);
  border-radius: 2px;
}

.ndt-card-label {
  font-size: 0.85rem;
  margin-bottom: 0.7rem;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.ndt-trend-indicator {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 20px;
  padding: 0.5rem 0.8rem;
  font-size: 0.8rem;
  display: inline-flex;
  align-items: center;
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
}

.ndt-trend-indicator i {
  color: var(--success-color);
  margin-right: 0.4rem;
  font-size: 0.75rem;
}

.ndt-card-actions {
    margin-top: 0.8rem;
    display: flex;
    gap: 0.5rem;
}

.ndt-btn {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: var(--text-color);
    border-radius: 20px;
    padding: 0.3rem 0.8rem;
    font-size: 0.8rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    transition: var(--transition);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
}

.ndt-btn i {
    margin-right: 0.3rem;
    font-size: 0.75rem;
}

.ndt-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

/* ==== EMPLOYEE TABLE STYLING ==== */
#subject-records {
  background: var(--glass-bg);
  border-radius: var(--border-radius);
  padding: 2rem;
  box-shadow: var(--shadow);
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
  border: var(--glass-border);
  position: relative;
  overflow: hidden;
}

#subject-records h3 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.2rem;
  color: var(--primary-dark);
  position: relative;
  display: inline-block;
}

#subject-records h3:after {
  content: '';
  position: absolute;
  left: 0;
  bottom: -8px;
  height: 4px;
  width: 60px;
  background: var(--accent-color);
  border-radius: 2px;
}

/* Export Buttons */
#subject-records .mb-2 {
  margin-bottom: 1.5rem !important;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

#subject-records .btn {
  border-radius: var(--border-radius-sm);
  padding: 0.6rem 1.2rem;
  font-weight: 500;
  transition: var(--transition);
  border: none;
  display: flex;
  align-items: center;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
}

#subject-records .btn::before {
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  margin-right: 0.5rem;
}

#subject-records .btn-success {
  background: var(--success-color);
}
/* #subject-records .btn-success::before {
  content: '\f1c3';
} */

#subject-records .btn-warning {
  background: var(--warning-color);
}
/* #subject-records .btn-warning::before {
  content: '\f1c9';
} */

#subject-records .btn-info {
  background: var(--info-color);
}
/* #subject-records .btn-info::before {
  content: '\f1c1';
} */

#subject-records .btn-secondary {
  background: var(--secondary-color);
}
/* #subject-records .btn-secondary::before {
  content: '\f02f';
} */

#subject-records .btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

/* Table Styling */
#employeeTable {
  border-collapse: separate;
  border-spacing: 0;
  width: 100%;
  margin-bottom: 1rem;
  border-radius: var(--border-radius-sm);
  overflow: hidden;
  box-shadow: var(--shadow);
}

#employeeTable thead tr {
  background: transparent;
  color: var(--text-color);
}

#employeeTable th {
  padding: 1rem;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.5px;
  border: none;
  position: relative;
  vertical-align: middle;
}

#employeeTable th:after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 2px;
  background: rgba(248, 195, 0, 0.3);
  transform: scaleX(0.7);
  transform-origin: center;
  transition: var(--transition);
}

#employeeTable tbody tr {
  background: white;
  transition: var(--transition);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

#employeeTable tbody tr:hover {
  background: rgba(7, 46, 99, 0.02);
  transform: scale(1.002);
}

#employeeTable td {
  padding: 1rem;
  vertical-align: middle;
  border-top: none;
  font-size: 0.95rem;
  border-right: 1px solid rgba(0, 0, 0, 0.03);
}

#employeeTable td:last-child {
  border-right: none;
  display : flex ;
}
/* Employee detail styling */
.employee-detail-header {
    background: var(--bg-gradient);
    padding: 2rem;
    position: relative;
    overflow: hidden;
}

.employee-detail-header:before {
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

.modal-dialog.modal-lg {
    max-width: 800px;
}

@media (max-width: 768px) {
    .employee-detail-header {
        text-align: center;
    }
    
    .employee-detail-header img,
    .employee-detail-header .rounded-circle {
        margin-bottom: 1rem;
    }
}
/* Table Image */
#employeeTable td img {
  border-radius: var(--border-radius-sm);
  object-fit: cover;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  transition: var(--transition);
}

#employeeTable td img:hover {
  transform: scale(1.05);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
}

/* Action Buttons */
#employeeTable .btn-sm {
  padding: 0.35rem 0.8rem;
  font-size: 0.8rem;
  border-radius: var(--border-radius-sm);
  transition: var(--transition);
  margin: 0 3px;
  border: none;
  display: inline-flex;
  align-items: center;
}
.content-wrapper .col-md-6 {
  margin-bottom : 2rem;
}
#employeeTable .btn-primary {
  background: var(--primary-color);
}
#subject-records i {
  margin-right : 10px;
}
#employeeTable .btn-primary::before {
  content: '\f044';
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  margin-right: 0.4rem;
  font-size: 0.75rem;
}

#employeeTable .btn-info {
  background: var(--info-color);
}

/* #employeeTable .btn-info::before {
  content: '\f06e';
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  margin-right: 0.4rem;
  font-size: 0.75rem;
} */

#employeeTable .btn-danger {
  background: var(--danger-color);
}

#employeeTable .btn-danger::before {
  content: '\f2ed';
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  margin-right: 0.4rem;
  font-size: 0.75rem;
}

#employeeTable .btn-sm:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
}

/* Modal Styling */
.modal-content {
  border-radius: var(--border-radius);
  border: none;
  box-shadow: var(--shadow);
  overflow: hidden;
}

.modal-header {
  background: var(--bg-gradient);
  color: var(--text-color);
  border-bottom: none;
  padding: 1.5rem;
}

.modal-title {
  font-weight: 600;
  font-size: 1.2rem;
}

.btn-close {
  color: white;
  opacity: 1;
  filter: brightness(0) invert(1);
}

.modal-body {
  padding: 1.5rem;
}

.form-label {
  font-weight: 500;
  color: var(--primary-dark);
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.form-control {
  border-radius: var(--border-radius-sm);
  border: 1px solid rgba(7, 46, 99, 0.1);
  padding: 0.7rem 1rem;
  transition: var(--transition);
}

.form-control:focus {
  border-color: var(--primary-light);
  box-shadow: 0 0 0 0.2rem rgba(7, 46, 99, 0.1);
}

.modal-footer {
  border-top: 1px solid rgba(0, 0, 0, 0.05);
  padding: 1.2rem 1.5rem;
}

.modal-footer .btn {
  border-radius: var(--border-radius-sm);
  padding: 0.6rem 1.5rem;
  font-weight: 500;
}

/* Department detail modal */
#departmentDetailModal .modal-dialog {
  max-width: 800px;
}

#departmentDetailModal .modal-body {
  padding: 0;
}

.dept-detail-header {
  background: var(--bg-gradient);
  padding: 2rem;
  color: white;
}

.dept-detail-content {
  padding: 2rem;
}

.dept-section {
  margin-bottom: 2rem;
}

.dept-section-title {
  font-size: 1.2rem;
  font-weight: 600;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid var(--accent-color);
  color: var(--primary-dark);
}

.team-member-card {
  display: flex;
  align-items: center;
  background: #f8f9fa;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.team-member-card img {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  object-fit: cover;
  margin-right: 1rem;
}

.member-info {
  flex-grow: 1;
}

.member-name {
  font-weight: 600;
  margin: 0;
}

.member-role {
  font-size: 0.85rem;
  color: #6c757d;
}

.member-tags {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.member-tag {
  background: var(--primary-light);
  color: white;
  font-size: 0.75rem;
  padding: 0.2rem 0.6rem;
  border-radius: 12px;
}

.task-item {
  display: flex;
  align-items: center;
  background: #fff;
  border-left: 4px solid var(--primary-color);
  border-radius: 4px;
  padding: 1rem;
  margin-bottom: 0.75rem;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.task-status {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  margin-right: 1rem;
}

.task-status.completed {
  background: var(--success-color);
}

.task-status.in-progress {
  background: var(--warning-color);
}

.task-status.pending {
  background: var(--danger-color);
}

.task-info {
  flex-grow: 1;
}

.task-title {
  font-weight: 500;
  margin: 0;
}

.task-meta {
  display: flex;
  justify-content: space-between;
  margin-top: 0.5rem;
  font-size: 0.8rem;
  color: #6c757d;
}

.empty-section {
  text-align: center;
  padding: 2rem;
  background: #f8f9fa;
  border-radius: 8px;
  color: #6c757d;
}

.empty-section i {
  font-size: 2rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

/* Responsive Design */
@media (max-width: 992px) {
  .ndt-card-body {
    padding: 1.5rem;
  }
  
  .ndt-card-icon {
    width: 50px;
    height: 50px;
  }
  
  .ndt-card-value {
    font-size: 1.2rem;
  }
}

@media (max-width: 768px) {
  #subject-records {
    padding: 1.5rem;
  }
  
  #employeeTable {
    display: block;
    overflow-x: auto;
  }
  
  #employeeTable th,
  #employeeTable td {
    padding: 0.8rem;
  }
  
  .ndt-metric-card {
    margin-bottom: 1rem;
  }
  
  .modal-dialog {
    margin: 0.5rem;
  }
}

@media (max-width: 576px) {
  #subject-records .mb-2 {
    justify-content: center;
  }
  
  #subject-records .btn {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
  }
  
  .ndt-card-body {
    flex-direction: column;
    text-align: center;
  }
  
  .ndt-card-icon {
    margin-right: 0;
    margin-bottom: 1rem;
  }
  
  .ndt-card-value:after {
    left: 50%;
    transform: translateX(-50%);
  }
  
  #employeeTable .btn-sm {
    padding: 0.25rem 0.6rem;
    font-size: 0.75rem;
  }
  
  .team-member-card {
    flex-direction: column;
    text-align: center;
  }
  
  .team-member-card img {
    margin-right: 0;
    margin-bottom: 0.5rem;
  }
  
  .member-tags {
    justify-content: center;
  }
}

/* Animation Effects */
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

.ndt-metric-card:hover .ndt-card-icon {
  animation: pulse 1.5s infinite;
}

/* Department icons */
.dept-icon-web:before {
  content: '\f121'; /* code icon */
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  font-size: 1.5rem;
  color: var(--accent-color);
}

.dept-icon-design:before {
  content: '\f53f'; /* palette icon */
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  font-size: 1.5rem;
  color: var(--accent-color);
}

.dept-icon-content:before {
  content: '\f303'; /* pen icon */
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  font-size: 1.5rem;
  color: var(--accent-color);
}

.dept-icon-seo:before {
  content: '\f002'; /* search icon */
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  font-size: 1.5rem;
  color: var(--accent-color);
}

.dept-icon-marketing:before {
  content: '\f201'; /* chart icon */
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  font-size: 1.5rem;
  color: var(--accent-color);
}

.dept-icon-default:before {
  content: '\f0b1'; /* briefcase icon */
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  font-size: 1.5rem;
  color: var(--accent-color);
}
  </style>
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <?php include './header.php'; ?>
    
    <div class="content-wrapper p-3">
        <?php 
        include './backend/connect.php';

        // Fetch departments from the departments table
        $sql = "SELECT d.id, d.department_name, COUNT(e.id) as total 
                FROM departments d
                LEFT JOIN employee e ON d.id = e.depart
                GROUP BY d.id, d.department_name
                ORDER BY d.department_name";
        $result = $conn->query($sql);
        ?>

        <div class="row">
            <?php if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Determine icon class based on department name
                    $iconClass = 'dept-icon-default';
                    $deptName = strtolower($row['department_name']);
                    
                    if (strpos($deptName, 'web') !== false) {
                        $iconClass = 'dept-icon-web';
                    } elseif (strpos($deptName, 'design') !== false || strpos($deptName, 'art') !== false) {
                        $iconClass = 'dept-icon-design';
                    } elseif (strpos($deptName, 'content') !== false || strpos($deptName, 'writing') !== false) {
                        $iconClass = 'dept-icon-content';
                    } elseif (strpos($deptName, 'seo') !== false || strpos($deptName, 'optimisation') !== false) {
                        $iconClass = 'dept-icon-seo';
                    } elseif (strpos($deptName, 'market') !== false || strpos($deptName, 'performance') !== false) {
                        $iconClass = 'dept-icon-marketing';
                    }
                    ?>
                <div class="col-lg-6 col-md-6 col-12">
                    <div class="ndt-metric-card ndt-card-primary" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['department_name']); ?>">
                        <div class="ndt-card-body">
                            <div class="ndt-card-icon <?php echo $iconClass; ?>"></div>
                            <div class="ndt-card-content">
                                <h3 class="ndt-card-value"><?php echo htmlspecialchars($row['department_name']); ?></h3>
                                <p class="ndt-card-label">Total Employees</p>
                                <div class="ndt-trend-indicator">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $row['total']; ?> employees available</span>
                                </div>
                                <div class="ndt-card-actions">
                                    <button class="ndt-btn view-dept-details" data-id="<?php echo $row['id']; ?>">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } } else { ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        No departments found. Please add departments in the system first.
                    </div>
                </div>
            <?php } ?>
        </div>

        <div id="subject-records" class="mt-4"></div>

    </div>

    <?php include './footer.php'; ?>
</div>

<!-- Department Detail Modal -->
<div class="modal fade" id="departmentDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Department Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="departmentDetailContent">
                <!-- Content will be loaded dynamically -->
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading department details...</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Employee Detail Modal -->
<div class="modal fade" id="employeeDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Employee Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="employeeDetailContent">
                <!-- Content will be loaded dynamically -->
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading employee details...</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- AJAX Fetch Script -->
<script>
$(document).ready(function(){
    // Load DataTables library
    $.getScript("https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js", function() {
        $.getScript("https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js", function() {
            $.getScript("https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js", function() {
                $.getScript("https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js", function() {
                    $.getScript("https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js", function() {
                        $.getScript("https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js", function() {
                            $.getScript("https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js");
                        });
                    });
                });
            });
        });
    });
    
    // Fetch department employees when clicked
    $(".ndt-metric-card").click(function(){
        var deptId = $(this).data("id");
        $.ajax({
            url: "fetch_department_employees.php",
            type: "POST",
            data: { department_id: deptId },
            success: function(response){
                $("#subject-records").html(response);
            },
            error: function(xhr, status, error) {
                console.log("AJAX Error: " + status + " - " + error);
                $("#subject-records").html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i>Failed to load employees data. Please try again.</div>');
            }
        });
    });
    
    // View department details
    $(document).on('click', '.view-dept-details', function(e){
        e.stopPropagation(); // Prevent card click event from firing
        var deptId = $(this).data("id");
        $('#departmentDetailModal').modal('show');
        
        $.ajax({
            url: "fetch_department_details.php",
            type: "POST",
            data: { department_id: deptId },
            success: function(response){
                $("#departmentDetailContent").html(response);
            },
            error: function(xhr, status, error) {
                console.log("AJAX Error: " + status + " - " + error);
                $("#departmentDetailContent").html('<div class="alert alert-danger m-3"><i class="fas fa-exclamation-circle mr-2"></i>Failed to load department details. Please try again.</div>');
            }
        });
    });
    
    // Employee detail view
    $(document).on('click', '.view-employee', function(){
        var employeeId = $(this).data("id");
        $('#employeeDetailModal').modal('show');
        
        $.ajax({
            url: "fetch_employee_details.php",
            type: "POST",
            data: { employee_id: employeeId },
            success: function(response){
                $("#employeeDetailContent").html(response);
            },
            error: function(xhr, status, error) {
                console.log("AJAX Error: " + status + " - " + error);
                $("#employeeDetailContent").html('<div class="alert alert-danger m-3"><i class="fas fa-exclamation-circle mr-2"></i>Failed to load employee details. Please try again.</div>');
            }
        });
    });
    
    // Show first department employees by default
    if($(".ndt-metric-card").length > 0) {
        $(".ndt-metric-card:first").trigger('click');
    }
});
</script>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="./src/js/adminlte.min.js"></script>
</body>
</html>
