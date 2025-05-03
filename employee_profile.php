<?php
include './backend/connect.php';
include 'auth_check.php';

// if(!isset($_GET['id']) || empty($_GET['id'])) {
//     header("Location: employee.php");
//     exit;
// }

$employee_id = $_GET['id'];

// Fetch employee details
$employee_query = "SELECT e.*, d.department_name 
                  FROM employee e 
                  LEFT JOIN departments d ON e.depart = d.id 
                  WHERE e.id = ?";
$stmt = $conn->prepare($employee_query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$employee_result = $stmt->get_result();

if($employee_result->num_rows == 0) {
    header("Location: employee.php");
    exit;
}

$employee = $employee_result->fetch_assoc();

// Fetch projects managed by this employee
$projects_query = "SELECT p.*, GROUP_CONCAT(d.department_name SEPARATOR ', ') as dept_names
                  FROM project p
                  JOIN project_managers pm ON p.id = pm.project_id
                  LEFT JOIN project_departments pd ON p.id = pd.project_id
                  LEFT JOIN departments d ON pd.department_id = d.id
                  WHERE pm.employee_id = ?
                  GROUP BY p.id
                  ORDER BY p.sd DESC";
$stmt_projects = $conn->prepare($projects_query);
$stmt_projects->bind_param("i", $employee_id);
$stmt_projects->execute();
$projects_result = $stmt_projects->get_result();

// Fetch tasks assigned to this employee
$tasks_query = "SELECT t.*, p.np as project_name 
               FROM tasks t
               LEFT JOIN project p ON t.project_id = p.id
               WHERE t.assigned_to = ?
               ORDER BY t.due_date ASC";
$stmt_tasks = $conn->prepare($tasks_query);
$stmt_tasks->bind_param("s", $employee_id);
$stmt_tasks->execute();
$tasks_result = $stmt_tasks->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee Profile</title>
    <link rel="stylesheet" href="./src/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <style>
        :root {
            --primary-color: #072e63;
            --primary-light: #1a4275;
            --primary-dark: #051e42;
            --accent-color: #f8c300;
            --text-color: #ffffff;
            --border-radius: 12px;
            --shadow: 0 10px 20px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0, 0, 0, 0.06);
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 3rem 0;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }
        
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: var(--shadow);
            object-fit: cover;
        }
        
        .profile-info h2 {
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .profile-role {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background-color: rgba(255, 255, 255, 0.2);
            margin-bottom: 1rem;
        }
        
        .profile-stats {
            display: flex;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .stat-box {
            text-align: center;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent-color);
        }
        
        .stat-label {
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        
        .profile-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }
        
        .section-title {
            margin-bottom: 1.5rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid var(--accent-color);
            font-weight: 600;
            position: relative;
        }
        
        .project-card {
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .project-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .task-row {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .task-row:last-child {
            border-bottom: none;
        }
        
        .priority-badge {
            padding: 0.25rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .priority-high {
            background: #f8d7da;
            color: #721c24;
        }
        
        .priority-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .priority-low {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .profile-contact-info {
            margin-top: 1.5rem;
        }
        
        .contact-item {
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
        }
        
        .contact-item i {
            width: 20px;
            color: var(--accent-color);
            margin-right: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                text-align: center;
                padding: 2rem 0;
            }
            
            .profile-img {
                margin-bottom: 1.5rem;
            }
            
            .profile-stats {
                justify-content: center;
            }
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <?php include './header.php'; ?>
        
        <div class="content-wrapper">
            <div class="container-fluid py-4">
                
                <div class="profile-header">
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center">
                                <?php if(!empty($employee['Image'])): ?>
                                <img src="./backend/uploads/<?php echo htmlspecialchars($employee['Image']); ?>" alt="Profile" class="profile-img">
                                <?php else: ?>
                                <div class="profile-img d-flex align-items-center justify-content-center bg-light">
                                    <i class="fas fa-user fa-4x text-secondary"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-9 profile-info">
                                <h2><?php echo htmlspecialchars($employee['name']); ?></h2>
                                <div class="profile-role">
                                    <?php echo htmlspecialchars($employee['desig'] ?: 'Employee'); ?> | 
                                    <?php echo htmlspecialchars($employee['role'] ?: 'User'); ?>
                                </div>
                                
                                <div class="profile-contact-info">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="contact-item">
                                                <i class="fas fa-envelope"></i>
                                                <span><?php echo htmlspecialchars($employee['email']); ?></span>
                                            </div>
                                            <div class="contact-item">
                                                <i class="fas fa-phone"></i>
                                                <span><?php echo htmlspecialchars($employee['contact']); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="contact-item">
                                                <i class="fas fa-building"></i>
                                                <span><?php echo htmlspecialchars($employee['department_name'] ?: 'Not Assigned'); ?></span>
                                            </div>
                                            <div class="contact-item">
                                                <i class="fas fa-calendar-check"></i>
                                                <span>Joined: <?php echo htmlspecialchars($employee['joining_date']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="profile-stats">
                                    <div class="stat-box">
                                        <div class="stat-value"><?php echo $projects_result->num_rows; ?></div>
                                        <div class="stat-label">Projects Managed</div>
                                    </div>
                                    <div class="stat-box">
                                        <div class="stat-value"><?php echo $tasks_result->num_rows; ?></div>
                                        <div class="stat-label">Tasks Assigned</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="container">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="profile-section">
                                <h4 class="section-title">
                                    <i class="fas fa-tasks mr-2"></i> Managed Projects
                                </h4>
                                
                                <?php if($projects_result->num_rows > 0): ?>
                                <div class="projects-container">
                                    <?php while($project = $projects_result->fetch_assoc()): ?>
                                    <div class="project-card">
                                        <a href="view_project.php?id=<?php echo $project['id']; ?>" class="project-title">
                                            <?php echo htmlspecialchars($project['np']); ?>
                                        </a>
                                        <div class="project-meta">
                                            <p><strong>Client:</strong> <?php echo htmlspecialchars($project['nc']); ?></p>
                                            <p><strong>Duration:</strong> <?php echo htmlspecialchars($project['sd']); ?> to 
                                            <?php echo !empty($project['cd']) ? htmlspecialchars($project['cd']) : 'Ongoing'; ?></p>
                                            <p><strong>Departments:</strong> <?php echo htmlspecialchars($project['dept_names']); ?></p>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                    <p>No projects managed by this employee yet.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="profile-section">
                                <h4 class="section-title">
                                    <i class="fas fa-check-square mr-2"></i> Assigned Tasks
                                </h4>
                                
                                <?php if($tasks_result->num_rows > 0): ?>
                                <div class="tasks-container">
                                    <?php while($task = $tasks_result->fetch_assoc()): ?>
                                        <div class="task-row">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($task['task_name']); ?></h6>
                                                <?php 
                                                $priority_class = 'priority-medium';
                                                if($task['priori'] == 'High') {
                                                    $priority_class = 'priority-high';
                                                } elseif($task['priori'] == 'Low') {
                                                    $priority_class = 'priority-low';
                                                }
                                                ?>
                                                <span class="priority-badge <?php echo $priority_class; ?>">
                                                    <?php echo htmlspecialchars($task['priori']); ?>
                                                </span>
                                            </div>
                                            <div class="small text-muted">
                                                <?php echo !empty($task['project_name']) ? 'Project: ' . htmlspecialchars($task['project_name']) : 'No project'; ?>
                                            </div>
                                            <div class="small">
                                                Due: <?php echo htmlspecialchars($task['due_date']); ?>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                                    <p>No tasks assigned to this employee yet.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <?php include './footer.php'; ?>
    </div>

    <script src="./src/js/jquery.min.js"></script>
    <script src="./src/js/bootstrap.bundle.min.js"></script>
    <script src="./src/js/adminlte.min.js"></script>
</body>
</html>