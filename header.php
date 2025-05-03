<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

$currentPage = basename($_SERVER['SCRIPT_NAME']);


$menuItems = [
    [
        "menuTitle" => "Management",
        "icon" => "nav-icon fas fa-user-tie",
        "pages" => [
            ["title" => "Admin Management", "url" => "admin_management.php"],
            ["title" => "Employee Management", "url" => "employee.php"],
            ["title" => "Department Management", "url" => "manage_depart.php"],
            ["title" => "Project  Management", "url" => "project.php"],

        ],
    ],

  //   [
  //     "menuTitle" => "Event Management",
  //     "icon" => "fas fa-money-bill-wave",
  //     "url" => "calender.php", 
  //     "pages" => [],
  // ],
    // [
    //     "menuTitle" => "Depart Management",
    //     "icon" => "nav-icon fas fa-building",
    //     "pages" => [
    //         ["title" => "Add Department", "url" => "add_depart.php"],
    //         ["title" => "Manage Department", "url" => "manage_depart.php"],
    //     ],
    // ],
];


if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
    $menuItems[] = [
        "menuTitle" => "Settings",
        "icon" => "fas fa-cogs",
        "pages" => [
            // ["title" => "Project Profile", "url" => "profile.php"], 
            ["title" => "Employee Profile", "url" => "department_employees.php"],
        ],
    ];
}
  

$activePageInfo = array_reduce($menuItems, function ($carry, $menuItem) use ($currentPage) {
    foreach ($menuItem['pages'] as $page) {
        if ($currentPage === $page['url']) {
            return [
                "breadcrumbItems" => [
                    ["title" => $menuItem['menuTitle'], "url" => "dashboard.php"],
                    ["title" => $page['title'], "url" => $page['url']]
                ],
                "pageTitle" => $page['title'],
                "activeMenu" => $menuItem,
                "activePage" => $page
            ];
        }
      
    }
    return $carry;
}, null);

$breadcrumbItems = $activePageInfo['breadcrumbItems'] ?? [];
$pageTitle = $activePageInfo['pageTitle'] ?? '';
$activeMenu = $activePageInfo['activeMenu'] ?? null;
$activePage = $activePageInfo['activePage'] ?? $currentPage;

// Check if user is logged in - Redirect to login if not authenticated
if (!isset($_SESSION['user_id']) && $currentPage != 'index.php') {
    header("Location: index.php");
    exit();
}

// Get user information for display (if logged in)
$userRole = $_SESSION['role'] ?? 'Guest';
$userName = $_SESSION['name'] ?? 'Guest';
$userDepartment = $_SESSION['department'] ?? '';
?>

<title><?= $pageTitle ?></title>
<link rel="icon" type="image/png" href="./src/images/nd-white-favicon.png">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<style>
    /* Main Dashboard Styles */
:root {
--primary-color: #072e63;
--primary-light: #1a4275;
--primary-dark: #051e42;
--accent-color: #f8c300;
--text-color: #ffffff;
--text-muted: #cccccc;
--border-radius: 8px;
--transition: all 0.3s ease;
--shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
--hover-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
  --ndt-light: #f8f9fc;
  --ndt-dark: #5a5c69;
  --ndt-white: #fff;
  --ndt-gray-100: #f8f9fc;
  --ndt-gray-200: #eaecf4;
  --ndt-gray-300: #dddfeb;
  --ndt-gray-400: #d1d3e2;
  --ndt-gray-500: #b7b9cc;
  --ndt-gray-600: #858796;
  --ndt-gray-700: #6e707e;
  --ndt-gray-800: #5a5c69;
  --ndt-gray-900: #3a3b45;
  --ndt-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
  --ndt-radius: 0.35rem;
  --ndt-transition: all 0.2s ease-in-out;
}

body {
  font-family: 'Nunito', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  background-color: var(--ndt-gray-100);
  color: var(--ndt-gray-800);
  overflow-x : hidden ;
}

/* Header Section */
.ndt-header-section {
  background-color: var(--ndt-white);
  padding: 1.5rem 1rem;
  box-shadow: var(--ndt-shadow);
  border-radius: var(--ndt-radius);
  margin-bottom: 1.5rem;
}

.ndt-welcome-heading {
  font-weight: 700;
  margin-bottom: 0.25rem;
  color: var(--ndt-gray-800);
}

.ndt-user-name {
  color: var(--ndt-primary);
}

.ndt-date-display {
  color: var(--ndt-gray-600);
  margin-bottom: 0;
}

.ndt-action-btn {
  margin-left: 0.5rem;
  border-radius: var(--ndt-radius);
  transition: var(--ndt-transition);
}

.ndt-create-task-btn {
  background-color: var(--ndt-primary);
  color: var(--ndt-white);
  padding: 0.5rem 1rem;
}

.ndt-filter-btn {
  background-color: var(--ndt-white);
  color: var(--ndt-gray-700);
  border: 1px solid var(--ndt-gray-300);
  padding: 0.5rem 1rem;
}

.ndt-user-profile-mini {
  position: relative;
  margin-left: 1rem;
}

.ndt-user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--ndt-gray-200);
}

.ndt-notification-indicator {
  position: absolute;
  top: 0;
  right: 0;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background-color: var(--ndt-danger);
  border: 2px solid var(--ndt-white);
}

/* Metric Cards */
.ndt-metric-card {
  border-radius: var(--ndt-radius);
  box-shadow: var(--ndt-shadow);
  margin-bottom: 1.5rem;
  overflow: hidden;
  transition: var(--ndt-transition);
}

.ndt-metric-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
}

.ndt-card-primary {
  background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
  color: var(--ndt-white);
}

.ndt-card-success {
  background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
  color: var(--ndt-white);
}

.ndt-card-warning {
  background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
  color: var(--ndt-white);
}

.ndt-card-danger {
  background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);
  color: var(--ndt-white);
}

.ndt-card-body {
  padding: 1.5rem;
  display: flex;
  align-items: center;
}

.ndt-card-icon {
  font-size: 2.5rem;
  margin-right: 1rem;
  opacity: 0.8;
}

.ndt-card-content {
  flex: 1;
}

.ndt-card-value {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.ndt-card-label {
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
  opacity: 0.8;
}

.ndt-trend-indicator {
  font-size: 0.75rem;
  display: flex;
  align-items: center;
}

.ndt-trend-indicator i {
  margin-right: 0.25rem;
}

/* Chart Cards */
.ndt-card {
  background-color: var(--ndt-white);
  border-radius: var(--ndt-radius);
  box-shadow: var(--ndt-shadow);
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.ndt-card-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--ndt-gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.ndt-card-title {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  color: var(--ndt-gray-800);
}

.ndt-card-subtitle {
  font-size: 0.875rem;
  color: var(--ndt-gray-600);
  margin-bottom: 0;
}

.ndt-time-filter .ndt-btn-outline {
  border: 1px solid var(--ndt-gray-300);
  color: var(--ndt-gray-700);
  background-color: transparent;
  padding: 0.25rem 0.75rem;
  font-size: 0.875rem;
}
.ndt-time-filter .ndt-btn-outline.active {
  background-color: var(--ndt-primary);
  color: var(--ndt-white);
  border-color: var(--ndt-primary);
}

.ndt-chart-legend {
  display: flex;
  flex-wrap: wrap;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--ndt-gray-200);
}

.ndt-legend-item {
  display: flex;
  align-items: center;
  margin-right: 1.5rem;
  margin-bottom: 0.5rem;
}

.ndt-legend-color {
  width: 12px;
  height: 12px;
  border-radius: 2px;
  margin-right: 0.5rem;
}

.ndt-legend-label {
  font-size: 0.875rem;
  color: var(--ndt-gray-700);
  margin-right: 0.5rem;
}

.ndt-legend-value {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--ndt-gray-800);
}

/* Team Workload */
.ndt-team-workload {
  padding: 0.5rem 0;
}

.ndt-team-member {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--ndt-gray-200);
}

.ndt-team-member:last-child {
  border-bottom: none;
}

.ndt-member-info {
  display: flex;
  align-items: center;
}

.ndt-member-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  margin-right: 1rem;
}

.ndt-member-name {
  font-size: 0.95rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: var(--ndt-gray-800);
}

.ndt-member-role {
  font-size: 0.8rem;
  color: var(--ndt-gray-600);
  margin-bottom: 0;
}

.ndt-workload-bar {
  display: flex;
  align-items: center;
  width: 50%;
}

.ndt-progress {
  flex: 1;
  height: 8px;
  margin-bottom: 0;
  margin-right: 1rem;
}

.ndt-workload-value {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--ndt-gray-800);
  min-width: 40px;
  text-align: right;
}

/* Todo List */
.ndt-todo-card {
  height: calc(100% - 1.5rem);
}

.ndt-todo-list {
  padding: 0.5rem 0;
}

.ndt-todo-item {
  display: flex;
  align-items: flex-start;
  padding: 1rem 0;
  border-bottom: 1px solid var(--ndt-gray-200);
  position: relative;
}

.ndt-todo-item:last-child {
  border-bottom: none;
}

.ndt-todo-item.ndt-priority-high::before {
  content: '';
  position: absolute;
  left: -1.5rem;
  top: 0;
  bottom: 0;
  width: 4px;
  background-color: var(--ndt-danger);
}

.ndt-todo-item.ndt-priority-medium::before {
  content: '';
  position: absolute;
  left: -1.5rem;
  top: 0;
  bottom: 0;
  width: 4px;
  background-color: var(--ndt-warning);
}

.ndt-todo-item.ndt-priority-low::before {
  content: '';
  position: absolute;
  left: -1.5rem;
  top: 0;
  bottom: 0;
  width: 4px;
  background-color: var(--ndt-info);
}

.ndt-todo-checkbox {
  margin-right: 1rem;
  padding-top: 0.25rem;
}

.ndt-checkbox {
  display: none;
}

.ndt-checkbox-label {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 2px solid var(--ndt-gray-400);
  border-radius: 4px;
  position: relative;
  cursor: pointer;
  transition: var(--ndt-transition);
}

.ndt-checkbox:checked + .ndt-checkbox-label {
  background-color: var(--ndt-primary);
  border-color: var(--ndt-primary);
}

.ndt-checkbox:checked + .ndt-checkbox-label::after {
  content: '✓';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: var(--ndt-white);
  font-size: 0.75rem;
}

.ndt-todo-content {
  flex: 1;
}

.ndt-todo-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: var(--ndt-gray-800);
}

.ndt-checkbox:checked ~ .ndt-todo-content .ndt-todo-title {
  text-decoration: line-through;
  color: var(--ndt-gray-500);
}

.ndt-todo-details {
  font-size: 0.875rem;
  color: var(--ndt-gray-600);
  margin-bottom: 0.5rem;
}

.ndt-todo-meta {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
}

.ndt-todo-due {
  font-size: 0.75rem;
  color: var(--ndt-gray-600);
  margin-right: 1rem;
  display: flex;
  align-items: center;
}

.ndt-todo-due i {
  margin-right: 0.25rem;
}

.ndt-todo-tag {
  font-size: 0.7rem;
  padding: 0.15rem 0.5rem;
  border-radius: 12px;
  font-weight: 600;
  margin-right: 0.5rem;
}

.ndt-tag-design {
  background-color: rgba(230, 126, 34, 0.15);
  color: #e67e22;
}

.ndt-tag-development {
  background-color: rgba(52, 152, 219, 0.15);
  color: #3498db;
}

.ndt-tag-meeting {
  background-color: rgba(155, 89, 182, 0.15);
  color: #9b59b6;
}

/* Activity Timeline */
.ndt-activity-timeline {
  padding: 0.5rem 0;
}

.ndt-timeline-item {
  display: flex;
  padding: 0.75rem 0;
  position: relative;
}

.ndt-timeline-item:not(:last-child)::after {
  content: '';
  position: absolute;
  top: 2.5rem;
  left: 1rem;
  bottom: 0;
  width: 2px;
  background-color: var(--ndt-gray-200);
}

.ndt-timeline-icon {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 1rem;
  z-index: 1;
}

.ndt-icon-primary {
  background-color: rgba(78, 115, 223, 0.15);
  color: var(--ndt-primary);
}

.ndt-icon-success {
  background-color: rgba(28, 200, 138, 0.15);
  color: var(--ndt-success);
}

.ndt-icon-warning {
  background-color: rgba(246, 194, 62, 0.15);
  color: var(--ndt-warning);
}

.ndt-icon-danger {
  background-color: rgba(231, 74, 59, 0.15);
  color: var(--ndt-danger);
}

.ndt-icon-info {
  background-color: rgba(54, 185, 204, 0.15);
  color: var(--ndt-info);
}

.ndt-timeline-content {
  flex: 1;
}

.ndt-timeline-title {
  font-size: 0.95rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: var(--ndt-gray-800);
}

.ndt-timeline-text {
  font-size: 0.875rem;
  color: var(--ndt-gray-600);
  margin-bottom: 0.25rem;
}

.ndt-timeline-time {
  font-size: 0.75rem;
  color: var(--ndt-gray-500);
}

/* Project Timeline */
.ndt-project-timeline {
  padding: 1rem 0;
}

.ndt-timeline-header {
  margin-bottom: 1.5rem;
}

.ndt-timeline-dates {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  text-align: center;
}

.ndt-timeline-dates span {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--ndt-gray-700);
}

.ndt-timeline-project {
  display: flex;
  margin-bottom: 1.5rem;
}

.ndt-project-info {
  width: 200px;
  padding-right: 1.5rem;
}

.ndt-project-name {
  font-size: 0.95rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: var(--ndt-gray-800);
}

.ndt-project-status {
  font-size: 0.8rem;
  margin-bottom: 0;
  display: inline-block;
  padding: 0.15rem 0.5rem;
  border-radius: 12px;
}

.ndt-project-timeline {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  grid-gap: 0.5rem;
  align-items: center;
}

.ndt-timeline-bar {
  background-color: var(--ndt-gray-200);
  height: 30px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.ndt-timeline-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--ndt-gray-800);
  z-index: 1;
}

.ndt-status-complete {
  background-color: rgba(28, 200, 138, 0.2);
  border-left: 4px solid var(--ndt-success);
}

.ndt-status-progress {
  background-color: rgba(78, 115, 223, 0.2);
  border-left: 4px solid var(--ndt-primary);
}

.ndt-status-delayed {
  background-color: rgba(231, 74, 59, 0.2);
  border-left: 4px solid var(--ndt-danger);
}
/* Responsive Styles */
@media (max-width: 1199.98px) {
  .ndt-project-info {
    width: 150px;
  }
}

@media (max-width: 991.98px) {
  .ndt-welcome-section {
    text-align: center;
    margin-bottom: 1rem;
  }
  
  .ndt-quick-actions {
    justify-content: center;
  }
  
  .ndt-workload-bar {
    width: 40%;
  }
  
  .ndt-project-timeline {
    display: none;
  }
  
  .ndt-timeline-project {
    flex-direction: column;
  }
  
  .ndt-project-info {
    width: 100%;
    margin-bottom: 0.5rem;
  }
}

@media (max-width: 767.98px) {
  .ndt-card-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .ndt-card-actions {
    margin-top: 1rem;
    align-self: flex-end;
  }
  
  .ndt-team-member {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .ndt-workload-bar {
    width: 100%;
    margin-top: 0.75rem;
  }
}

@media (max-width: 575.98px) {
  .ndt-metric-card .ndt-card-body {
    flex-direction: column;
    text-align: center;
  }
  
  .ndt-card-icon {
    margin-right: 0;
    margin-bottom: 0.75rem;
  }
  
  .ndt-todo-item {
    flex-direction: column;
  }
  
  .ndt-todo-checkbox {
    margin-bottom: 0.75rem;
  }
  
  .ndt-todo-actions {
    position: absolute;
    top: 1rem;
    right: 0;
  }
}
/* Navbar Styling for Goel Industries */
.main-header {
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  border: none;
  position: relative;
  z-index: 10;
}

.navbar {
  background: rgba(7, 46, 99, 0.9) !important;
  backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(248, 195, 0, 0.2);
  padding: 0.8rem 1rem;
}

.navbar-light .navbar-nav .nav-link {
  color: rgba(255, 255, 255, 0.8) !important;
  font-family: 'Montserrat', sans-serif;
  letter-spacing: 0.5px;
  padding: 0.5rem 1rem;
  transition: all 0.3s ease;
}

.navbar-light .navbar-nav .nav-link:hover,
.navbar-light .navbar-nav .nav-link:focus {
  color: #f8c300 !important;
  text-shadow: 0 0 8px rgba(248, 195, 0, 0.4);
}

.navbar-light .navbar-nav .nav-link i {
  font-size: 1.2rem;
}

/* Search bar styling */
.form-control-navbar {
  background: rgba(255, 255, 255, 0.05) !important;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
  color: #ffffff !important;
  border-radius: 8px 0 0 8px !important;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2) inset;
}

.form-control-navbar:focus {
  box-shadow: 0 0 0 2px rgba(248, 195, 0, 0.3) !important;
  border-color: rgba(248, 195, 0, 0.5) !important;
}

.form-control-navbar::placeholder {
  color: rgba(255, 255, 255, 0.5);
}

.btn-navbar {
  background: rgba(248, 195, 0, 0.2) !important;
  border: 1px solid rgba(248, 195, 0, 0.3) !important;
  color: #f8c300 !important;
  border-radius: 0 8px 8px 0 !important;
  transition: all 0.3s ease;
}

.btn-navbar:hover {
  background: rgba(248, 195, 0, 0.3) !important;
  color: #ffffff !important;
}

/* Badge styling */
.badge-danger {
  background-color: #ff3e3e !important;
  font-family: 'Montserrat', sans-serif;
  font-weight: 500;
  box-shadow: 0 0 10px rgba(255, 62, 62, 0.5);
}

.badge-warning {
  background-color: #f8c300 !important;
  color: #072e63 !important;
  font-family: 'Montserrat', sans-serif;
  font-weight: 500;
  box-shadow: 0 0 10px rgba(248, 195, 0, 0.5);
}

.navbar-badge {
  font-size: 0.6rem;
  padding: 3px 5px;
  right: 3px;
  top: 5px;
}

/* User role badges */
.role-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 600;
  margin-left: 5px;
}

.role-admin {
  background-color: rgba(231, 74, 59, 0.2);
  color: #e74a3b;
}

.role-manager {
  background-color: rgba(246, 194, 62, 0.2);
  color: #f6c23e;
}

.role-team-lead {
  background-color: rgba(54, 185, 204, 0.2);
  color: #36b9cc;
}

.role-user {
  background-color: rgba(78, 115, 223, 0.2);
  color: #4e73df;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .navbar-nav {
    padding: 0.5rem 0;
  }
  
  .form-inline {
    margin: 0.5rem 0;
  }
}
.navbar-nav img {
  display : unset;
}
#logoutModal {
    z-index: 9999 !important;
}
.modal-backdrop {
    z-index: 800 !important;
}
</style>


<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" data-widget="pushmenu" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block"><a href="dashboard.php" class="nav-link">Home</a></li>
    </ul>
    <form class="form-inline invisible ml-3">
        <div class="input-group input-group-sm">
            <input class="form-control form-control-navbar" type="search" placeholder="Search" name="search">
            <div class="input-group-append">
                <button class="btn btn-navbar" type="submit"><i class="fas fa-search"></i></button>
            </div>
        </div>
    </form>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown invisible"><a class="nav-link" href="#messages"><i class="far fa-comments"></i><span
                    class="badge badge-danger navbar-badge">2</span></a></li>
        <li class="nav-item dropdown"><a class="nav-link d-none" href="#notifications"><i class="far fa-bell"></i><span
                    class="badge badge-warning navbar-badge">5</span></a></li>
        <?php if(isset($_SESSION['user_id'])): ?>
          <li class="nav-item dropdown">

          
          <a class="nav-link" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <?php if(isset($_SESSION['user_image']) && !empty($_SESSION['user_image'])): ?>
            <img src="./backend/uploads/<?= htmlspecialchars($_SESSION['user_image']) ?>" class="img-circle" alt="User Image" style="width: 25px; height: 25px; margin-right: 5px;">
        <?php else: ?>
            <i class="far fa-user-circle"></i>
        <?php endif; ?>
        <span class="d-none d-lg-inline-block ml-1"><?= $_SESSION['name'] ?? 'User' ?></span>
        <span class="role-badge role-<?= strtolower(str_replace(' ', '-', $_SESSION['role'] ?? 'guest')) ?>"><?= $_SESSION['role'] ?? 'Guest' ?></span>
    </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown" style="z-index : 4;">
            <a class="dropdown-item" href="employee_profile.php?id=<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">
    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
    Profile
</a>
                <!-- <a class="dropdown-item" href="settings.php">
                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                    Settings
                </a> -->
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Logout
                </a>
            </div>
        </li>
        <?php endif; ?>
    </ul>
</nav>

<div class="main-header" style="padding: 0px 10px; background-color: #f4f6f9; border-bottom: none !important;">
    <div class="content-header " style="display : none;">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?= $pageTitle ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <?php foreach ($breadcrumbItems as $item): ?>
                        <li class="breadcrumb-item <?= $item['url'] === '#' ? 'active' : '' ?>">
                            <?= $item['url'] === '#' ? $item['title'] : "<a href='{$item['url']}'>{$item['title']}</a>" ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    </div>
</div>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="./" class="brand-link">
        <img src="./src/images/nd-white-favicon.png" alt="Admin Panel Logo" class="brand-image img-square elevation-4">
        <span class="brand-text font-weight-bold" style="font-size: 17px !important;">Nextdot</span>
    </a>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <?php if(isset($_SESSION['user_image']) && !empty($_SESSION['user_image'])): ?>
                        <img src="./backend/uploads/<?= htmlspecialchars($_SESSION['user_image']) ?>" class="img-circle elevation-2" alt="User Image">
                    <?php else: ?>
                        <img src="./src/images/default.png" class="img-circle elevation-2" alt="User Image">
                    <?php endif; ?>
                </div>
                <div class="info">
                    <a href="profile.php" class="d-block">
                        <?= $_SESSION['name'] ?? 'User' ?>
                       
                    </a>
                    <span class="role-badge role-<?= strtolower(str_replace(' ', '-', $_SESSION['role'] ?? 'guest')) ?>"><?= $_SESSION['role'] ?? 'Guest' ?></span>
                    <small class="text-muted d-block"><?= $_SESSION['department'] ?? '' ?></small>
                    
                    
                </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-home"></i>
                        <p>Home</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="calender.php" class="nav-link <?= $currentPage === 'calender.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-calendar-alt"></i>
                        <p>Event Management</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="task.php" class="nav-link <?= $currentPage === 'task.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tasks"></i>
                        <p>Task Management</p>
                    </a>
                </li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
              <li class="nav-item">
             <a href="supervisor_view.php" class="nav-link <?= $currentPage === 'supervisor_view.php' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-tasks"></i>
            <p>Supervisor View</p>
           </a>
         </li>
      <?php endif; ?>
                <?php foreach ($menuItems as $menuItem): ?>
                    <?php 
                    // Check if user has permission to see this menu
                    $showMenuItem = true;
                    
                    // Hide management sections from regular users
                    if ($menuItem['menuTitle'] === 'Management' && ($_SESSION['role'] ?? '') === 'User') {
                        $showMenuItem = false;
                    }
                    
                    if ($showMenuItem):
                    ?>
                    <li class="nav-item has-treeview <?= $menuItem['menuTitle'] === $activeMenu ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= $menuItem['menuTitle'] === $activeMenu ? 'active' : '' ?>">
                            <i class="nav-icon <?= $menuItem['icon'] ?>"></i>
                            <p>
                                <?= $menuItem['menuTitle'] ?>
                                <?php if (!empty($menuItem['pages'])): ?>
                                    <i class="right fas fa-angle-left"></i>
                                <?php endif; ?>
                            </p>
                        </a>

                        <?php if (!empty($menuItem['pages'])): ?>
                            <ul class="nav nav-treeview">
                                <?php foreach ($menuItem['pages'] as $page): ?>
                                    <?php 
                                    // Check if user has permission to see this page
                                    $showPage = true;
                                    
                                    // Hide admin pages from non-admins
                                    if (strpos($page['url'], 'admin_') !== false && ($_SESSION['role'] ?? '') !== 'Admin') {
                                        $showPage = false;
                                    }
                                    
                                    if ($showPage):
                                    ?>
                                    <li class="nav-item">
                                        <a href="<?= $page['url'] ?>" class="nav-link <?= $page['url'] === $currentPage ? 'active' : '' ?>">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p><?= $page['title'] ?></p>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                    <?php endif; ?>
                <?php endforeach; ?>

                <li class="nav-item">
                    <!-- Change this in header.php -->
<a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#logoutModal">
    <i class="nav-icon fas fa-sign-out-alt"></i>
    <p>Logout</p>
</a>
                </li>

            </ul>
        </nav>
    </div>
</aside>

<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-primary" href="logout.php">Logout</a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Keep the existing logout confirmations for backward compatibility
    function logout(e) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You will be logged out!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, log me out!'
        }).then((result) => {
            if (result.value) {
                window.location.href = 'index.php';
            }
        });
    }
    

        

</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Make all logout buttons work correctly
    var logoutButtons = document.querySelectorAll('[data-toggle="modal"][data-target="#logoutModal"], [data-bs-toggle="modal"][data-bs-target="#logoutModal"]');
    
    logoutButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Try Bootstrap 5 way first
            var bsModal = window.bootstrap && bootstrap.Modal;
            if (bsModal) {
                var modal = new bsModal(document.getElementById('logoutModal'));
                modal.show();
            } 
            // Fallback to jQuery/Bootstrap 4 way if Bootstrap 5 is not available
            else if (window.jQuery && window.jQuery.fn.modal) {
                jQuery('#logoutModal').modal('show');
            } 
            // Pure JavaScript fallback for basic functionality
            else {
                var modal = document.getElementById('logoutModal');
                if (modal) {
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    document.body.classList.add('modal-open');
                    
                    // Create backdrop
                    var backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    document.body.appendChild(backdrop);
                }
            }
        });
    });
    
    // Handle closing the modal with pure JS if needed
    var closeButtons = document.querySelectorAll('#logoutModal .close, #logoutModal ');s
    closeButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            if (!(window.jQuery && window.jQuery.fn.modal) && !(window.bootstrap && bootstrap.Modal)) {
                var modal = document.getElementById('logoutModal');
                if (modal) {
                    modal.style.display = 'none';
                    modal.classList.remove('show');
                    document.body.classList.remove('modal-open');
                    
                    // Remove backdrop
                    var backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.parentNode.removeChild(backdrop);
                    }
                }
            }
        });
    });
});
</script>
 