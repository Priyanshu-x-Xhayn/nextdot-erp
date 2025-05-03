
<?php
include './backend/connect.php';

if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
// Default to today's date for filtering
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Fetch departmental stats - Updated to use departments table and project_departments relationship
$dept_stats_sql = "SELECT 
    d.department_name as department,
    COUNT(t.id) as total_tasks,
    SUM(CASE WHEN t.status = '0' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN t.status = '1' THEN 1 ELSE 0 END) as in_progress_tasks,
    SUM(CASE WHEN t.status = '2' THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN t.priori = 'High' THEN 1 ELSE 0 END) as high_priority_tasks,
    SUM(CASE WHEN t.is_overdue = 1 THEN 1 ELSE 0 END) as overdue_tasks
    FROM tasks t
    JOIN project p ON t.project_id = p.id
    JOIN project_departments pd ON p.id = pd.project_id
    JOIN departments d ON pd.department_id = d.id
    WHERE DATE(t.start_date) = ?
    GROUP BY d.department_name
    ORDER BY d.department_name";

$dept_stmt = $conn->prepare($dept_stats_sql);
$dept_stmt->bind_param("s", $filter_date);
$dept_stmt->execute();
$dept_stats_result = $dept_stmt->get_result();

// Fetch employee productivity stats - Updated to use proper employee and department relationships
$employee_stats_sql = "SELECT 
    e.name as employee_name,
    COUNT(t.id) as assigned_tasks,
    SUM(CASE WHEN t.status = '2' THEN 1 ELSE 0 END) as completed_tasks,
    ROUND((SUM(CASE WHEN t.status = '2' THEN 1 ELSE 0 END) / COUNT(t.id)) * 100, 1) as completion_rate,
    d.department_name as department
    FROM tasks t
    JOIN employee e ON t.assigned_to = e.id
    JOIN project p ON t.project_id = p.id
    JOIN project_departments pd ON p.id = pd.project_id
    JOIN departments d ON pd.department_id = d.id
    WHERE DATE(t.start_date) = ?
    GROUP BY t.assigned_to, d.department_name
    ORDER BY completion_rate DESC";

$emp_stmt = $conn->prepare($employee_stats_sql);
$emp_stmt->bind_param("s", $filter_date);
$emp_stmt->execute();
$employee_stats_result = $emp_stmt->get_result();

// Fetch project progress stats - Updated to use proper project and department relationships
$project_stats_sql = "SELECT 
    p.np as project_name,
    COUNT(t.id) as total_tasks,
    SUM(CASE WHEN t.status = '2' THEN 1 ELSE 0 END) as completed_tasks,
    ROUND((SUM(CASE WHEN t.status = '2' THEN 1 ELSE 0 END) / COUNT(t.id)) * 100, 1) as completion_percentage,
    d.department_name as department
    FROM tasks t
    JOIN project p ON t.project_id = p.id
    JOIN project_departments pd ON p.id = pd.project_id
    JOIN departments d ON pd.department_id = d.id
    WHERE DATE(t.start_date) = ?
    GROUP BY t.project_id, d.department_name
    ORDER BY completion_percentage DESC";

$proj_stmt = $conn->prepare($project_stats_sql);
$proj_stmt->bind_param("s", $filter_date);
$proj_stmt->execute();
$project_stats_result = $proj_stmt->get_result();

// Fetch overall stats
$overall_sql = "SELECT 
    COUNT(*) as total_tasks,
    SUM(CASE WHEN status = '0' THEN 1 ELSE 0 END) as pending_tasks,
    SUM(CASE WHEN status = '1' THEN 1 ELSE 0 END) as in_progress_tasks,
    SUM(CASE WHEN status = '2' THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN priori = 'High' THEN 1 ELSE 0 END) as high_priority_tasks,
    SUM(CASE WHEN is_overdue = 1 THEN 1 ELSE 0 END) as overdue_tasks,
    ROUND(AVG(CASE WHEN status = '2' THEN 1 ELSE 0 END) * 100, 1) as overall_completion_rate
    FROM tasks 
    WHERE DATE(start_date) = ?";

$overall_stmt = $conn->prepare($overall_sql);
$overall_stmt->bind_param("s", $filter_date);
$overall_stmt->execute();
$overall_stats = $overall_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Supervisor Dashboard</title>
  <link rel="stylesheet" href="./src/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
  <style>
    :root {
      --primary-color: #072e63;
      --primary-light: #2a5298;
      --primary-dark: #051e42;
      --accent-color: #f8c300;
      --success-color: #1cc88a;
      --warning-color: #f6c23e;
      --danger-color: #e74a3b;
      --text-color: #f8f9fc;
      --border-radius: 8px;
      --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      --hover-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
      --transition: all 0.3s ease;
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
      background: white;
      border-radius: var(--border-radius);
      padding: 20px;
      box-shadow: var(--shadow);
      transition: var(--transition);
      border-left: 5px solid var(--primary-color);
      display: flex;
      flex-direction: column;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--hover-shadow);
    }
    
    .stat-card.pending {
      border-left-color: var(--warning-color);
    }
    
    .stat-card.progress {
      border-left-color: var(--primary-light);
    }
    
    .stat-card.completed {
      border-left-color: var(--success-color);
    }
    
    .stat-card.high-priority {
      border-left-color: var(--danger-color);
    }
    
    .stat-card.overdue {
      border-left-color: #e74a3b;
    }
    
    .stat-title {
      color: var(--primary-dark);
      font-size: 0.9rem;
      text-transform: uppercase;
      margin-bottom: 10px;
      font-weight: 600;
    }
    
    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      color: var(--primary-color);
    }
    
    /* Department Cards */
    .department-card {
      background: white;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      transition: var(--transition);
      margin-bottom: 20px;
      overflow: hidden;
    }
    
    .department-card:hover {
      box-shadow: var(--hover-shadow);
    }
    
    .department-header {
      background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
      color: white;
      padding: 15px 20px;
      font-weight: 600;
      font-size: 1.1rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .department-body {
      padding: 20px;
    }
    
    .department-stats {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 15px;
    }
    
    .dept-stat {
      flex: 1;
      min-width: 120px;
      padding: 10px;
      text-align: center;
      border-radius: var(--border-radius);
      background: rgba(7, 46, 99, 0.05);
    }
    
    .dept-stat-label {
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--primary-dark);
      margin-bottom: 5px;
    }
    
    .dept-stat-value {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--primary-color);
    }
    
    /* Progress Bars */
    .progress-container {
      margin-bottom: 15px;
    }
    
    .progress-label {
      display: flex;
      justify-content: space-between;
      margin-bottom: 5px;
    }
    
    .progress-title {
      font-weight: 600;
      font-size: 0.9rem;
      color: var(--primary-dark);
    }
    
    .progress-value {
      font-weight: 600;
      font-size: 0.9rem;
      color: var(--primary-color);
    }
    
    .progress {
      height: auto !important;
      border-radius: 5px;
      overflow: hidden;
      background-color: rgba(7, 46, 99, 0.1);
    }
    
    .progress-bar {
      background: linear-gradient(to right, var(--primary-color), var(--accent-color));
      border-radius: 5px;
    }
    
    /* Date Selector */
    .date-selector {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 30px;
      gap: 15px;
    }
    
    .date-input {
      padding: 8px 15px;
      border-radius: var(--border-radius);
      border: 1px solid rgba(7, 46, 99, 0.2);
      font-size: 1rem;
    }
    
    /* Employee Progress Table */
    .employee-progress-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }
    
    .employee-progress-table th,
    .employee-progress-table td {
      padding: 12px 15px;
      text-align: left;
    }
    
    .employee-progress-table thead th {
      background-color: rgba(7, 46, 99, 0.05);
      font-weight: 600;
      font-size: 0.9rem;
      color: var(--primary-dark);
      border-bottom: 2px solid rgba(7, 46, 99, 0.1);
    }
    
    .employee-progress-table tbody tr {
      border-bottom: 1px solid rgba(7, 46, 99, 0.05);
    }
    
    .employee-progress-table tbody tr:hover {
      background-color: rgba(7, 46, 99, 0.02);
    }
    
    .employee-name {
      font-weight: 600;
      color: var(--primary-color);
    }
    
    .completion-rate {
      font-weight: 600;
    }
    
    .completion-rate-high {
      color: var(--success-color);
    }
    
    .completion-rate-medium {
      color: var(--warning-color);
    }
    
    .completion-rate-low {
      color: var(--danger-color);
    }
  </style>
</head>

<body class="hold-transition sidebar-mini">
  <div class="wrapper">
    <?php include './header.php'; ?>
    <div class="content-wrapper">
      <section class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1>Supervisor Dashboard</h1>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active">Supervisor View</li>
              </ol>
            </div>
          </div>
        </div>
      </section>

      <section class="content">
        <div class="container-fluid">
          <!-- Date Selector -->
          <div class="date-selector">
            <form action="" method="GET" class="d-flex align-items-center">
              <label for="date" class="mr-2">Select Date:</label>
              <input type="date" id="date" name="date" class="date-input mr-2" value="<?php echo $filter_date; ?>">
              <button type="submit" class="btn btn-primary">View Report</button>
            </form>
          </div>
          
          <!-- Overall Stats -->
          <div class="stats-container">
            <div class="stat-card">
              <div class="stat-title">Total Tasks</div>
              <div class="stat-value"><?php echo $overall_stats['total_tasks'] ?? 0; ?></div>
            </div>
            <div class="stat-card pending">
              <div class="stat-title">Pending</div>
              <div class="stat-value"><?php echo $overall_stats['pending_tasks'] ?? 0; ?></div>
            </div>
            <div class="stat-card progress">
              <div class="stat-title">In Progress</div>
              <div class="stat-value"><?php echo $overall_stats['in_progress_tasks'] ?? 0; ?></div>
            </div>
            <div class="stat-card completed">
              <div class="stat-title">Completed</div>
              <div class="stat-value"><?php echo $overall_stats['completed_tasks'] ?? 0; ?></div>
  </div>
  <div class="stat-card overdue">
              <div class="stat-title">Overdue</div>
              <div class="stat-value"><?php echo $overall_stats['overdue_tasks'] ?? 0; ?></div>
            </div>
            </div>
        
          </div>
          
          <div class="row">
            <div class="col-lg-8">
              <!-- Department Stats -->
              <?php if ($dept_stats_result && $dept_stats_result->num_rows > 0): ?>
                <?php while ($dept = $dept_stats_result->fetch_assoc()): ?>
                  <div class="department-card">
                    <div class="department-header">
                      <span><?php echo $dept['department']; ?></span>
                      <span>
                        <?php 
                        $completion_rate = 0;
                        if ($dept['total_tasks'] > 0) {
                          $completion_rate = round(($dept['completed_tasks'] / $dept['total_tasks']) * 100, 1);
                        }
                        echo $completion_rate . '% Complete';
                        ?>
                      </span>
                    </div>
                    <div class="department-body">
                      <div class="department-stats">
                        <div class="dept-stat">
                          <div class="dept-stat-label">Total</div>
                          <div class="dept-stat-value"><?php echo $dept['total_tasks']; ?></div>
                        </div>
                        <div class="dept-stat">
                          <div class="dept-stat-label">Pending</div>
                          <div class="dept-stat-value"><?php echo $dept['pending_tasks']; ?></div>
                        </div>
                        <div class="dept-stat">
                          <div class="dept-stat-label">In Progress</div>
                          <div class="dept-stat-value"><?php echo $dept['in_progress_tasks']; ?></div>
                        </div>
                        <div class="dept-stat">
                          <div class="dept-stat-label">Completed</div>
                          <div class="dept-stat-value"><?php echo $dept['completed_tasks']; ?></div>
                        </div>
                        <div class="dept-stat">
                          <div class="dept-stat-label">Overdue</div>
                          <div class="dept-stat-value"><?php echo $dept['overdue_tasks']; ?></div>
                        </div>
                      </div>
                      
                      <div class="progress-container">
                        <div class="progress-label">
                          <span class="progress-title">Task Completion</span>
                          <span class="progress-value"><?php echo $completion_rate; ?>%</span>
                        </div>
                        <div class="progress">
                          <div class="progress-bar" role="progressbar" style="width: <?php echo $completion_rate; ?>%" aria-valuenow="<?php echo $completion_rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                      </div>
                      
                      <!-- Department Projects - Updated to use proper department relationships -->
                      <h6 class="mt-4 mb-3">Projects</h6>
                      <div class="table-responsive">
                        <table class="table table-sm">
                          <thead>
                            <tr>
                              <th>Project</th>
                              <th>Tasks</th>
                              <th>Completed</th>
                              <th>Progress</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php 
                            // Updated query to use proper department relationships
                            $proj_stmt = $conn->prepare("SELECT 
                                p.np as project_name,
                                COUNT(t.id) as total_tasks,
                                SUM(CASE WHEN t.status = '2' THEN 1 ELSE 0 END) as completed_tasks,
                                ROUND((SUM(CASE WHEN t.status = '2' THEN 1 ELSE 0 END) / COUNT(t.id)) * 100, 1) as completion_percentage
                                FROM tasks t
                                JOIN project p ON t.project_id = p.id
                                JOIN project_departments pd ON p.id = pd.project_id
                                JOIN departments d ON pd.department_id = d.id
                                WHERE DATE(t.start_date) = ? AND d.department_name = ?
                                GROUP BY t.project_id
                                ORDER BY completion_percentage DESC");
                            $proj_stmt->bind_param("ss", $filter_date, $dept['department']);
                            $proj_stmt->execute();
                            $dept_projects = $proj_stmt->get_result();
                            
                            if ($dept_projects && $dept_projects->num_rows > 0):
                              while ($project = $dept_projects->fetch_assoc()):
                            ?>
                              <tr>
                                <td><?php echo $project['project_name']; ?></td>
                                <td><?php echo $project['total_tasks']; ?></td>
                                <td><?php echo $project['completed_tasks']; ?></td>
                                <td>
                                  <div class="progress" style="height: 8px;">
                                  <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $project['completion_percentage']; ?>%" 
                                         aria-valuenow="<?php echo $project['completion_percentage']; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                  </div>
                                  <small><?php echo $project['completion_percentage']; ?>%</small>
                                </td>
                              </tr>
                            <?php 
                              endwhile;
                            else:
                            ?>
                              <tr>
                                <td colspan="4" class="text-center">No projects found</td>
                              </tr>
                            <?php endif; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                <?php endwhile; ?>
              <?php else: ?>
                <div class="alert alert-info">No department data available for the selected date.</div>
              <?php endif; ?>
            </div>
            
            <div class="col-lg-4">
              <!-- Employee Productivity -->
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title">Employee Productivity</h3>
                </div>
                <div class="card-body">
                  <?php if ($employee_stats_result && $employee_stats_result->num_rows > 0): ?>
                    <table class="employee-progress-table">
                      <thead>
                        <tr>
                          <th>Employee</th>
                          <th>Tasks</th>
                          <th>Completed</th>
                          <th>Rate</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php while ($employee = $employee_stats_result->fetch_assoc()): ?>
                          <tr>
                            <td class="employee-name"><?php echo $employee['employee_name']; ?></td>
                            <td><?php echo $employee['assigned_tasks']; ?></td>
                            <td><?php echo $employee['completed_tasks']; ?></td>
                            <td>
                              <?php 
                              $rate_class = 'completion-rate-low';
                              if ($employee['completion_rate'] >= 80) {
                                $rate_class = 'completion-rate-high';
                              } else if ($employee['completion_rate'] >= 50) {
                                $rate_class = 'completion-rate-medium';
                              }
                              ?>
                              <span class="completion-rate <?php echo $rate_class; ?>">
                                <?php echo $employee['completion_rate']; ?>%
                              </span>
                            </td>
                          </tr>
                        <?php endwhile; ?>
                      </tbody>
                    </table>
                  <?php else: ?>
                    <div class="alert alert-info">No employee data available for the selected date.</div>
                  <?php endif; ?>
                </div>
              </div>
              
              <!-- Priority Tasks - Updated to use proper department relationships -->
              <div class="card mt-4">
                <div class="card-header">
                  <h3 class="card-title">High Priority Tasks</h3>
                </div>
                <div class="card-body">
                  <?php
                  $priority_sql = "SELECT 
                      t.id,
                      t.task_name,
                      e.name as assignee_name,
                      t.due_date,
                      t.status,
                      d.department_name as department
                      FROM tasks t
                      JOIN employee e ON t.assigned_to = e.id
                      JOIN project p ON t.project_id = p.id
                      JOIN project_departments pd ON p.id = pd.project_id
                      JOIN departments d ON pd.department_id = d.id
                      WHERE DATE(t.start_date) = ? AND t.priori = 'High'
                      ORDER BY t.due_date ASC";
                      
                  $priority_stmt = $conn->prepare($priority_sql);
                  $priority_stmt->bind_param("s", $filter_date);
                  $priority_stmt->execute();
                  $priority_tasks = $priority_stmt->get_result();
                  
                  if ($priority_tasks && $priority_tasks->num_rows > 0):
                  ?>
                    <div class="table-responsive">
                      <table class="table table-sm">
                        <thead>
                          <tr>
                            <th>Task</th>
                            <th>Assignee</th>
                            <th>Due</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php while ($task = $priority_tasks->fetch_assoc()): ?>
                            <tr>
                              <td>
                                <a href="daily_tasks.php?id=<?php echo $task['id']; ?>">
                                  <?php echo substr($task['task_name'], 0, 20) . (strlen($task['task_name']) > 20 ? '...' : ''); ?>
                                </a>
                              </td>
                              <td><?php echo $task['assignee_name']; ?></td>
                              <td>
                                <?php 
                                $due_date = new DateTime($task['due_date']);
                                $today = new DateTime();
                                $due_class = '';
                                
                                if($due_date < $today && $task['status'] != '2') {
                                    $due_class = 'text-danger';
                                }
                                ?>
                                <span class="<?php echo $due_class; ?>">
                                  <?php echo date('M d', strtotime($task['due_date'])); ?>
                                </span>
                              </td>
                              <td>
                                <?php 
                                $status_text = '';
                                $status_class = '';
                                switch($task['status']) {
                                    case '0':
                                        $status_text = 'Pending';
                                        $status_class = 'badge-warning';
                                        break;
                                    case '1':
                                        $status_text = 'In Progress';
                                        $status_class = 'badge-primary';
                                        break;
                                    case '2':
                                        $status_text = 'Completed';
                                        $status_class = 'badge-success';
                                        break;
                                    default:
                                        $status_text = 'Pending';
                                        $status_class = 'badge-warning';
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                  <?php echo $status_text; ?>
                                </span>
                              </td>
                            </tr>
                          <?php endwhile; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php else: ?>
                    <div class="alert alert-info">No high priority tasks for the selected date.</div>
                  <?php endif; ?>
                </div>
              </div>
              
              <!-- Overdue Tasks - Updated to use proper department relationships -->
              <div class="card mt-4">
                <div class="card-header">
                  <h3 class="card-title">Overdue Tasks</h3>
                </div>
                <div class="card-body">
                  <?php
                  $overdue_sql = "SELECT 
                      t.id,
                      t.task_name,
                      e.name as assignee_name,
                      t.due_date,
                      t.pending_age,
                      d.department_name as department
                      FROM tasks t
                      JOIN employee e ON t.assigned_to = e.id
                      JOIN project p ON t.project_id = p.id
                      JOIN project_departments pd ON p.id = pd.project_id
                      JOIN departments d ON pd.department_id = d.id
                      WHERE DATE(t.start_date) = ? AND t.is_overdue = 1 AND t.status != '2'
                      ORDER BY t.due_date ASC";
                      
                  $overdue_stmt = $conn->prepare($overdue_sql);
                  $overdue_stmt->bind_param("s", $filter_date);
                  $overdue_stmt->execute();
                  $overdue_tasks = $overdue_stmt->get_result();
                  
                  if ($overdue_tasks && $overdue_tasks->num_rows > 0):
                  ?>
                    <div class="table-responsive">
                      <table class="table table-sm">
                        <thead>
                          <tr>
                            <th>Task</th>
                            <th>Assignee</th>
                            <th>Department</th>
                            <th>Overdue</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php while ($task = $overdue_tasks->fetch_assoc()): ?>
                            <tr>
                              <td>
                                <a href="daily_tasks.php?id=<?php echo $task['id']; ?>">
                                  <?php echo substr($task['task_name'], 0, 20) . (strlen($task['task_name']) > 20 ? '...' : ''); ?>
                                </a>
                              </td>
                              <td><?php echo $task['assignee_name']; ?></td>
                              <td><?php echo $task['department']; ?></td>
                              <td class="text-danger">
                                <?php echo $task['pending_age']; ?>
                              </td>
                            </tr>
                          <?php endwhile; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php else: ?>
                    <div class="alert alert-success">No overdue tasks for the selected date.</div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
    <?php include './footer.php'; ?>
  </div>
  <!-- JS Dependencies -->
  <script src="./src/js/jquery.min.js"></script>
  <script src="./src/js/bootstrap.bundle.min.js"></script>
  <script src="./src/js/adminlte.min.js"></script>
</body>
</html>