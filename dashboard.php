<?php
include './backend/connect.php'; // Ensure this path is correct and establishes $conn

session_start();

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// --- Updated User Info Retrieval ---
$user_id = $_SESSION['user_id'];
$user_name = "User"; // Default name
$user_role = "User"; // Default role
$user_department_id = null; // Will store the user's department ID
$user_department_name = "N/A"; // Will store the user's department name

// Get user info from employee table
$stmt = $conn->prepare("SELECT id, name, role, depart FROM employee WHERE id = ?");
if ($stmt === false) {
    error_log("Prepare failed (employee lookup): (" . $conn->errno . ") " . $conn->error);
    die("Error connecting to database or preparing statement."); // More user-friendly error
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result && $user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
    $user_name = $user_data['name'];
    $user_role = $user_data['role'];
    $department_value = $user_data['depart']; // Value from 'depart' column

    // Determine Department ID and Name
    if (!empty($department_value)) {
        if (is_numeric($department_value)) {
            // Assume it's a department ID
            $user_department_id = (int)$department_value;
            // Fetch department name based on ID
            $dept_name_stmt = $conn->prepare("SELECT department_name FROM departments WHERE id = ?");
             if ($dept_name_stmt) {
                $dept_name_stmt->bind_param("i", $user_department_id);
                $dept_name_stmt->execute();
                $dept_name_result = $dept_name_stmt->get_result();
                if($dept_name_result && $dept_name_result->num_rows > 0) {
                    $user_department_name = $dept_name_result->fetch_assoc()['department_name'];
                } else {
                     $user_department_name = "Unknown Dept (ID: {$user_department_id})";
                }
                $dept_name_stmt->close();
            } else {
                 error_log("Prepare failed (dept name lookup): (" . $conn->errno . ") " . $conn->error);
            }

        } else {
            // Assume it's a department name, look up the ID
            $user_department_name = $department_value; // Store the name we already have
            $dept_id_stmt = $conn->prepare("SELECT id FROM departments WHERE department_name = ?");
             if ($dept_id_stmt) {
                $dept_id_stmt->bind_param("s", $department_value);
                $dept_id_stmt->execute();
                $dept_id_result = $dept_id_stmt->get_result();
                if ($dept_id_result && $dept_id_result->num_rows > 0) {
                    $user_department_id = (int)$dept_id_result->fetch_assoc()['id'];
                } else {
                    // Name exists in employee table but not departments table
                    $user_department_id = null; // Cannot filter reliably
                    $user_department_name .= " (Unknown ID)";
                }
                 $dept_id_stmt->close();
            } else {
                 error_log("Prepare failed (dept id lookup): (" . $conn->errno . ") " . $conn->error);
            }
        }
    }
} else {
    // Fallback: Try the 'users' table (less likely to have department info)
    $stmt_users = $conn->prepare("SELECT name FROM users WHERE id = ?");
     if ($stmt_users) {
        $stmt_users->bind_param("i", $user_id);
        $stmt_users->execute();
        $user_result_users = $stmt_users->get_result();
        if ($user_result_users && $user_result_users->num_rows > 0) {
            $user_data_users = $user_result_users->fetch_assoc();
            $user_name = $user_data_users['name'];
            // Assuming 'Admin' role if found only in 'users' table, adjust if needed
            $user_role = 'Admin';
        }
        $stmt_users->close();
    } else {
        error_log("Prepare failed (users lookup): (" . $conn->errno . ") " . $conn->error);
    }
}
$stmt->close();

$is_dept_restricted = ($user_role == 'User' || $user_role == 'Team Lead') && !is_null($user_department_id);

// Get current date
$current_date = date("F j, Y");

// --- Prepare Department Filter SQL parts ---
// Filter based on TASK ASSIGNEE's department
$dept_filter_join_employee = ""; // SQL for joining tasks/employee table
$dept_filter_where_employee = ""; // SQL for WHERE clause on employee department
$dept_params = []; // Parameters for prepared statements related to task assignee
$dept_param_type = ""; // Parameter types string

if ($is_dept_restricted) {
    // Use CAST because assigned_to might be stored as varchar
    $dept_filter_join_employee = " INNER JOIN employee emp_filter ON CAST(t.assigned_to AS UNSIGNED) = emp_filter.id ";
    $dept_filter_where_employee = " AND emp_filter.depart = ? ";
    $dept_params[] = $user_department_id;
    $dept_param_type .= "i";
}

// Filter based on EMPLOYEE table directly (using alias 'e')
$team_filter_where_employee_direct = ""; // SQL for WHERE clause on employee table 'e'
$team_params = []; // Parameters for prepared statements related to employee table
$team_param_type = ""; // Parameter types string

if ($is_dept_restricted) {
     $team_filter_where_employee_direct = " AND e.depart = ? ";
     $team_params[] = $user_department_id;
     $team_param_type .= "i";
}

// Filter based on PROJECT's linked department
$project_filter_join = "";
$project_filter_where = "";
$project_params_arr = []; // Use a different name to avoid confusion
$project_param_type = "";

if($is_dept_restricted) {
    $project_filter_join = " INNER JOIN project_departments pd ON p.id = pd.project_id ";
    $project_filter_where = " AND pd.department_id = ? ";
    $project_params_arr[] = $user_department_id;
    $project_param_type .= "i";
}

// --- Task Metrics ---
// Active Tasks
$active_tasks = 0; // Default value
$active_tasks_query = "SELECT COUNT(t.id) as count FROM tasks t {$dept_filter_join_employee} WHERE t.status IN ('0', '', ' ') {$dept_filter_where_employee}";
$stmt = $conn->prepare($active_tasks_query);
if($stmt){
    if ($is_dept_restricted) {
        $stmt->bind_param($dept_param_type, ...$dept_params);
    }
    $stmt->execute();
    $active_result = $stmt->get_result();
    $active_tasks = $active_result ? $active_result->fetch_assoc()['count'] : 0;
    $stmt->close();
} else { error_log("Prepare failed (active tasks): ".$conn->error); }

// Completed Tasks
$completed_tasks = 0;
$completed_tasks_query = "SELECT COUNT(t.id) as count FROM tasks t {$dept_filter_join_employee} WHERE t.status = '2' {$dept_filter_where_employee}";
$stmt = $conn->prepare($completed_tasks_query);
if($stmt){
    if ($is_dept_restricted) {
        $stmt->bind_param($dept_param_type, ...$dept_params);
    }
    $stmt->execute();
    $completed_result = $stmt->get_result();
    $completed_tasks = $completed_result ? $completed_result->fetch_assoc()['count'] : 0;
    $stmt->close();
} else { error_log("Prepare failed (completed tasks): ".$conn->error); }

// Pending Tasks
$pending_tasks = 0;
$pending_tasks_query = "SELECT COUNT(t.id) as count FROM tasks t {$dept_filter_join_employee} WHERE (t.status IN ('0', '', ' ')) AND STR_TO_DATE(t.due_date, '%Y-%m-%d') >= CURDATE() {$dept_filter_where_employee}";
$stmt = $conn->prepare($pending_tasks_query);
if($stmt){
    if ($is_dept_restricted) {
        $stmt->bind_param($dept_param_type, ...$dept_params);
    }
    $stmt->execute();
    $pending_result = $stmt->get_result();
    $pending_tasks = $pending_result ? $pending_result->fetch_assoc()['count'] : 0;
    $stmt->close();
} else { error_log("Prepare failed (pending tasks): ".$conn->error); }

// Overdue Tasks
$overdue_tasks = 0;
$overdue_tasks_query = "SELECT COUNT(t.id) as count FROM tasks t {$dept_filter_join_employee} WHERE (t.status IN ('0', '', ' ')) AND STR_TO_DATE(t.due_date, '%Y-%m-%d') < CURDATE() {$dept_filter_where_employee}";
$stmt = $conn->prepare($overdue_tasks_query);
if($stmt){
    if ($is_dept_restricted) {
        $stmt->bind_param($dept_param_type, ...$dept_params);
    }
    $stmt->execute();
    $overdue_result = $stmt->get_result();
    $overdue_tasks = $overdue_result ? $overdue_result->fetch_assoc()['count'] : 0;
    $stmt->close();
} else { error_log("Prepare failed (overdue tasks): ".$conn->error); }


// --- Task Completion Trend ---
$last_week_completed = 1; // Default to 1 to avoid division by zero
$last_week_completed_query = "SELECT COUNT(t.id) as count FROM tasks t {$dept_filter_join_employee}
                              WHERE t.status = '2'
                              AND STR_TO_DATE(t.completion_date, '%Y-%m-%d') BETWEEN
                                  DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                              {$dept_filter_where_employee}";
$stmt = $conn->prepare($last_week_completed_query);
if($stmt){
    if ($is_dept_restricted) {
        $stmt->bind_param($dept_param_type, ...$dept_params);
    }
    $stmt->execute();
    $last_week_result = $stmt->get_result();
    $last_week_completed = $last_week_result ? max(1, $last_week_result->fetch_assoc()['count']) : 1;
    $stmt->close();
} else { error_log("Prepare failed (last week trend): ".$conn->error); }

$this_week_completed = 0;
$this_week_completed_query = "SELECT COUNT(t.id) as count FROM tasks t {$dept_filter_join_employee}
                               WHERE t.status = '2'
                               AND STR_TO_DATE(t.completion_date, '%Y-%m-%d') > DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                               {$dept_filter_where_employee}";
$stmt = $conn->prepare($this_week_completed_query);
if($stmt){
    if ($is_dept_restricted) {
        $stmt->bind_param($dept_param_type, ...$dept_params);
    }
    $stmt->execute();
    $this_week_result = $stmt->get_result();
    $this_week_completed = $this_week_result ? $this_week_result->fetch_assoc()['count'] : 0;
    $stmt->close();
} else { error_log("Prepare failed (this week trend): ".$conn->error); }

// Calculate completion trend
$completion_trend = $last_week_completed > 0 ? round((($this_week_completed - $last_week_completed) / $last_week_completed) * 100) : ($this_week_completed > 0 ? 100 : 0);


// --- Weekly Task Completion Data ---
$weekly_labels = [];
$weekly_data = [];
$weekly_data_query = "SELECT
                        YEARWEEK(STR_TO_DATE(t.completion_date, '%Y-%m-%d'), 3) as yearweek,
                        MIN(STR_TO_DATE(t.completion_date, '%Y-%m-%d')) as first_day,
                        COUNT(t.id) as completed_count
                      FROM tasks t
                      {$dept_filter_join_employee} /* JOIN ADDED */
                      WHERE t.completion_date IS NOT NULL AND t.completion_date != '0000-00-00' AND t.completion_date != ''
                      {$dept_filter_where_employee} /* FILTER ADDED */
                      GROUP BY yearweek
                      ORDER BY yearweek DESC
                      LIMIT 7";
$stmt = $conn->prepare($weekly_data_query);
if($stmt){
    if ($is_dept_restricted) {
        $stmt->bind_param($dept_param_type, ...$dept_params);
    }
    $stmt->execute();
    $weekly_result = $stmt->get_result();

    if ($weekly_result && $weekly_result->num_rows > 0) {
         while ($row = $weekly_result->fetch_assoc()) {
             try { // Add try-catch for DateTime creation
                $date = new DateTime($row['first_day']);
                $weekly_labels[] = "Week " . $date->format('W') . ", " . $date->format('Y');
                $weekly_data[] = (int)$row['completed_count'];
             } catch (Exception $e) {
                 error_log("DateTime error in weekly completion: " . $e->getMessage());
                 // Handle error, maybe skip this entry or log it
             }
         }
         // Ensure data is in chronological order for the chart
         $weekly_labels = array_reverse($weekly_labels);
         $weekly_data = array_reverse($weekly_data);
    }
    $stmt->close();
} else { error_log("Prepare failed (weekly data): ".$conn->error); }

// Generate default labels/data if query failed or returned no rows
if (empty($weekly_labels)) {
     for ($i = 6; $i >= 0; $i--) {
         $date = new DateTime();
         $date->modify("-$i weeks");
         $weekly_labels[] = "Week " . $date->format('W') . ", " . $date->format('Y');
         $weekly_data[] = 0;
     }
}


// --- Task Distribution by Department --- (Data fetched only if needed)
$dept_labels = [];
$dept_data = [];
$dept_colors = ["#4e73df", "#1cc88a", "#36b9cc", "#f6c23e", "#e74a3b", "#6f42c1"];
$dept_percentages = [];

if (!$is_dept_restricted) {
    // Fetch data for the chart only for Admin/Manager
    $dept_query = "SELECT d.id, d.department_name, COUNT(t.id) as task_count
                   FROM departments d
                   LEFT JOIN project_departments pd ON d.id = pd.department_id
                   LEFT JOIN tasks t ON pd.project_id = t.project_id
                   GROUP BY d.id, d.department_name
                   ORDER BY task_count DESC
                   LIMIT 6"; // Simplified query, adjust join logic if needed based on exact schema relations
    $dept_result = $conn->query($dept_query); // Using query() as it's not user-input dependent here

    if ($dept_result && $dept_result->num_rows > 0) {
        $total_tasks_dept = 0;
        while ($row = $dept_result->fetch_assoc()) {
            $dept_labels[] = $row['department_name'];
            $dept_data[] = (int)$row['task_count'];
            $total_tasks_dept += $row['task_count'];
        }
        if ($total_tasks_dept > 0) {
             foreach ($dept_data as $count) {
                 $dept_percentages[] = round(($count / $total_tasks_dept) * 100);
             }
        } else {
             $dept_percentages = array_fill(0, count($dept_data), 0);
        }
        $dept_result->free(); // Free result set
    } else {
        // Sample data if no departments found or query fails
        $dept_labels = ['Web Dev', 'Design', 'Marketing', 'Sales', 'Support', 'Other'];
        $dept_data = [30, 22, 18, 15, 10, 5];
        $dept_percentages = [32, 23, 19, 16, 10, 5]; // Example percentages
    }
}
// JSON encoding for dept chart will happen later, within the conditional HTML


// --- Team Productivity ---
$employee_names = [];
$employee_tasks = [];
$team_query = "SELECT e.id, e.name, COUNT(t.id) as completed_tasks
               FROM employee e
               LEFT JOIN tasks t ON e.id = CAST(t.assigned_to AS UNSIGNED) AND t.status = '2'
               WHERE e.name IS NOT NULL AND e.name != '' {$team_filter_where_employee_direct} /* FILTER ADDED */
               GROUP BY e.id, e.name
               ORDER BY completed_tasks DESC
               LIMIT 5";
$stmt = $conn->prepare($team_query);
if($stmt){
    if ($is_dept_restricted) {
        $stmt->bind_param($team_param_type, ...$team_params);
    }
    $stmt->execute();
    $team_result = $stmt->get_result();

    if ($team_result && $team_result->num_rows > 0) {
         while ($row = $team_result->fetch_assoc()) {
             $employee_names[] = $row['name'];
             $employee_tasks[] = (int)$row['completed_tasks'];
         }
    }
    $stmt->close();
} else { error_log("Prepare failed (team productivity): ".$conn->error); }

// Default if no data
if (empty($employee_names)) {
    $employee_names = ['No Data Available'];
    $employee_tasks = [0];
}

// --- Team Workload ---
$team_workload = [];
$workload_query = "SELECT
                        e.id, e.name, e.desig,
                        COUNT(CASE WHEN (t.status IN ('0', '', ' ')) THEN 1 ELSE NULL END) as active_tasks,
                        COUNT(t.id) as total_tasks
                    FROM employee e
                    LEFT JOIN tasks t ON e.id = CAST(t.assigned_to AS UNSIGNED)
                    WHERE e.name IS NOT NULL AND e.name != '' {$team_filter_where_employee_direct} /* FILTER ADDED */
                    GROUP BY e.id, e.name, e.desig
                    ORDER BY active_tasks DESC
                    LIMIT 4";
$stmt = $conn->prepare($workload_query);
if($stmt){
     if ($is_dept_restricted) {
        $stmt->bind_param($team_param_type, ...$team_params);
     }
     $stmt->execute();
     $workload_result = $stmt->get_result();

    if ($workload_result && $workload_result->num_rows > 0) {
         while ($row = $workload_result->fetch_assoc()) {
             $max_capacity = 5; // Assuming 5 tasks is full capacity - Adjust if needed
             $workload_percentage = $max_capacity > 0 ? min(95, round(($row['active_tasks'] / $max_capacity) * 100)) : 0;
             if ($workload_percentage < 10 && $row['active_tasks'] > 0) $workload_percentage = 10; // Min display threshold if > 0 tasks
             elseif ($row['active_tasks'] == 0) $workload_percentage = 0; // Ensure 0% if no tasks

             $team_workload[] = [
                 'id' => $row['id'],
                 'name' => $row['name'],
                 'role' => !empty($row['desig']) ? $row['desig'] : 'Team Member',
                 'workload' => $workload_percentage,
                 'color' => ($workload_percentage < 50) ? 'bg-info' : (($workload_percentage < 75) ? 'bg-success' : (($workload_percentage < 90) ? 'bg-warning' : 'bg-danger'))
             ];
         }
    }
     $stmt->close();
} else { error_log("Prepare failed (team workload): ".$conn->error); }


// --- Today's Tasks ---
$today_tasks = [];
$today_tasks_query = "SELECT
                        t.id, t.task_name, t.description, t.due_date,
                        p.np as project_name, t.priori as priority
                      FROM tasks t
                      {$dept_filter_join_employee} /* JOIN ADDED */
                      LEFT JOIN project p ON t.project_id = p.id
                      WHERE (t.status IN ('0', '', ' '))
                      {$dept_filter_where_employee} /* FILTER ADDED */
                      ORDER BY
                        CASE
                          WHEN t.priori = 'High' THEN 1
                          WHEN t.priori = 'Medium' THEN 2
                          ELSE 3
                        END,
                        STR_TO_DATE(t.due_date, '%Y-%m-%d') ASC
                      LIMIT 5";
$stmt = $conn->prepare($today_tasks_query);
if($stmt){
    if ($is_dept_restricted) {
        $stmt->bind_param($dept_param_type, ...$dept_params);
    }
    $stmt->execute();
    $today_result = $stmt->get_result();

    if ($today_result && $today_result->num_rows > 0) {
         while ($row = $today_result->fetch_assoc()) {
             $priority_class = '';
             switch ($row['priority']) {
                 case 'High': $priority_class = 'ndt-priority-high'; break;
                 case 'Medium': $priority_class = 'ndt-priority-medium'; break;
                 default: $priority_class = 'ndt-priority-low'; break;
             }

             $today_tasks[] = [
                 'id' => $row['id'],
                 'name' => $row['task_name'],
                 'description' => $row['description'],
                 'due_date' => $row['due_date'],
                 'priority' => $row['priority'],
                 'priority_class' => $priority_class,
                 'project' => !empty($row['project_name']) ? $row['project_name'] : 'General Project'
             ];
         }
    }
    $stmt->close();
} else { error_log("Prepare failed (today tasks): ".$conn->error); }


// --- Recent Activities ---
$recent_activities = [];

// Add completed task activities
$activity_query = "SELECT t.task_name, e.name as employee_name, t.completion_date
                   FROM tasks t
                   LEFT JOIN employee e ON CAST(t.assigned_to AS UNSIGNED) = e.id
                   WHERE t.completion_date IS NOT NULL AND t.completion_date != '' AND t.completion_date != '0000-00-00'
                   {$team_filter_where_employee_direct} /* FILTER ADDED (using e alias) */
                   ORDER BY STR_TO_DATE(t.completion_date, '%Y-%m-%d') DESC
                   LIMIT 2";
$stmt = $conn->prepare($activity_query);
if($stmt){
    if ($is_dept_restricted) {
        $stmt->bind_param($team_param_type, ...$team_params);
    }
    $stmt->execute();
    $activity_result = $stmt->get_result();
    if ($activity_result && $activity_result->num_rows > 0) {
        while ($row = $activity_result->fetch_assoc()) {
             try {
                 $completion_date = new DateTime($row['completion_date']);
                 $now = new DateTime();
                 $interval = $now->diff($completion_date);
                 $time_ago = "";
                 if ($interval->days == 0) { $time_ago = "Today"; }
                 elseif ($interval->days == 1) { $time_ago = "Yesterday"; }
                 elseif ($interval->days < 7) { $time_ago = $interval->days . " days ago"; }
                 else { $time_ago = $completion_date->format('M j'); }

                 $recent_activities[] = [
                    'icon' => 'fas fa-check-circle', 'icon_class' => 'ndt-icon-success',
                    'title' => 'Task completed',
                    'description' => ($row['employee_name'] ?: 'Someone') . ' completed "' . htmlspecialchars($row['task_name']) . '"',
                    'time_ago' => $time_ago
                 ];
            } catch (Exception $e) { error_log("DateTime error in completed activities: ".$e->getMessage()); }
        }
    }
    $stmt->close();
} else { error_log("Prepare failed (activity completed tasks): ".$conn->error); }


// Add new project activities
$project_query = "SELECT p.np as project_name, p.nc as client_name, p.sd as start_date
                  FROM project p
                  {$project_filter_join} /* JOIN ADDED */
                  WHERE 1=1 {$project_filter_where} /* FILTER ADDED */
                  ORDER BY p.id DESC
                  LIMIT 2";
$stmt = $conn->prepare($project_query);
if($stmt){
    if ($is_dept_restricted) {
         $stmt->bind_param($project_param_type, ...$project_params_arr);
    }
    $stmt->execute();
    $project_result = $stmt->get_result();
    if ($project_result && $project_result->num_rows > 0) {
        while ($row = $project_result->fetch_assoc()) {
            try {
                 $start_date = new DateTime($row['start_date']);
                 $now = new DateTime();
                 $interval = $now->diff($start_date);
                 $time_ago = "";
                 if ($interval->days == 0) { $time_ago = "Today"; }
                 elseif ($interval->days == 1) { $time_ago = "Yesterday"; }
                 elseif ($interval->days < 7) { $time_ago = $interval->days . " days ago"; }
                 else { $time_ago = $start_date->format('M j'); }

                 $recent_activities[] = [
                    'icon' => 'fas fa-folder-open', 'icon_class' => 'ndt-icon-primary',
                    'title' => 'New project',
                    'description' => 'Project "' . htmlspecialchars($row['project_name']) . '" for ' . htmlspecialchars($row['client_name']) . ' was created',
                    'time_ago' => $time_ago
                 ];
            } catch (Exception $e) { error_log("DateTime error in project activities: ".$e->getMessage()); }
        }
    }
    $stmt->close();
} else { error_log("Prepare failed (activity new projects): ".$conn->error); }


// Add new task created activities
$task_created_query = "SELECT t.task_name, t.start_date, e.name as employee_name
                       FROM tasks t
                       LEFT JOIN employee e ON CAST(t.assigned_to AS UNSIGNED) = e.id
                       WHERE t.start_date IS NOT NULL AND t.start_date != '' AND t.start_date != '0000-00-00'
                       {$team_filter_where_employee_direct} /* FILTER ADDED (using e alias) */
                       ORDER BY STR_TO_DATE(t.start_date, '%Y-%m-%d') DESC
                       LIMIT 2";
$stmt = $conn->prepare($task_created_query);
if($stmt){
    if ($is_dept_restricted) {
        $stmt->bind_param($team_param_type, ...$team_params);
    }
    $stmt->execute();
    $task_created_result = $stmt->get_result();
    if ($task_created_result && $task_created_result->num_rows > 0) {
         while ($row = $task_created_result->fetch_assoc()) {
            try {
                $start_date = new DateTime($row['start_date']);
                $now = new DateTime();
                $interval = $now->diff($start_date);
                $time_ago = "";
                 if ($interval->days == 0) { $time_ago = "Today"; }
                 elseif ($interval->days == 1) { $time_ago = "Yesterday"; }
                 elseif ($interval->days < 7) { $time_ago = $interval->days . " days ago"; }
                 else { $time_ago = $start_date->format('M j'); }

                $recent_activities[] = [
                    'icon' => 'fas fa-plus-circle', 'icon_class' => 'ndt-icon-info',
                    'title' => 'New task created',
                    'description' => ($row['employee_name'] ?: 'Someone') . ' was assigned "' . htmlspecialchars($row['task_name']) . '"',
                    'time_ago' => $time_ago
                 ];
            } catch (Exception $e) { error_log("DateTime error in task created activities: ".$e->getMessage()); }
         }
    }
    $stmt->close();
} else { error_log("Prepare failed (activity created tasks): ".$conn->error); }


// Fill remaining activities if needed and sort by approximate time
// Note: This simple filling might not be chronologically perfect
while (count($recent_activities) < 4) {
    $activities_samples = [
        [ 'icon' => 'fas fa-comment', 'icon_class' => 'ndt-icon-info', 'title' => 'System notification', 'description' => 'Welcome to your dashboard!', 'time_ago' => 'Recently' ],
        [ 'icon' => 'fas fa-exclamation-circle', 'icon_class' => 'ndt-icon-warning', 'title' => 'Reminder', 'description' => 'Check project deadlines.', 'time_ago' => 'Earlier' ]
    ];
    $recent_activities[] = $activities_samples[array_rand($activities_samples)];
}
// You might want a more robust way to sort activities if mixing real and sample data

// --- JSON Encoding ---
$weekly_labels_json = json_encode($weekly_labels);
$weekly_data_json = json_encode($weekly_data, JSON_NUMERIC_CHECK);
// Note: Dept JSON is encoded within the conditional HTML block below
$employee_names_json = json_encode($employee_names);
$employee_tasks_json = json_encode($employee_tasks, JSON_NUMERIC_CHECK);

// Initialize dept JSON variables to avoid errors if block is skipped
$dept_labels_json = '[]';
$dept_data_json = '[]';
$dept_colors_json = '[]';
$dept_percentages_json = '[]';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>NextDot Management - Dashboard</title>
    <link rel="stylesheet" href="./src/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <style>
        /* Styles remain the same */
        :root { --primary: #4e73df; --success: #1cc88a; --info: #36b9cc; --warning: #f6c23e; --danger: #e74a3b; --dark: #5a5c69; --secondary: #858796; --light: #f8f9fc; --shadow: 0 .15rem 1.75rem 0 rgba(58, 59, 69, .15); }
        body { background-color: #f8f9fc; font-family: 'Nunito', 'Segoe UI', Arial, sans-serif; }
        .nd-shadow { box-shadow: var(--shadow); }
        .nd-card { background-color: white; border-radius: 0.5rem; box-shadow: var(--shadow); transition: transform 0.2s; margin-bottom: 1.5rem; /* Ensure spacing */ }
        .nd-card:hover { transform: translateY(-3px); }
        .nd-card-primary { border-left: 4px solid var(--primary); }
        .nd-card-success { border-left: 4px solid var(--success); }
        .nd-card-warning { border-left: 4px solid var(--warning); }
        .nd-card-danger { border-left: 4px solid var(--danger); }
        .nd-card-icon-primary { color: var(--primary); } .nd-card-icon-success { color: var(--success); } .nd-card-icon-warning { color: var(--warning); } .nd-card-icon-danger { color: var(--danger); }
        .progress-bar-thin { height: 8px; border-radius: 4px; background-color: #e9ecef; } /* Added background color */
        .chart-container { position: relative; height: 300px; width: 100%; }
        .timeline-dot { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; color: white; z-index: 2; }
        .timeline-line { position: absolute; left: 20px; top: 40px; bottom: -10px; /* Extend below */ width: 2px; background-color: #e9ecef; z-index: 1; }
        .timeline-item:last-child .timeline-line { display: none; } /* Hide line on last item */
        .avatar-initial { display: flex; align-items: center; justify-content: center; background-color: var(--primary); color: white; font-weight: 600; border-radius: 50%; width: 40px; height: 40px; font-size: 1rem; }
        .sticky-header { position: sticky; top: 0; z-index: 3; background-color: white; box-shadow: var(--shadow); }
        /* Custom Scroll Bar */
        ::-webkit-scrollbar { width: 8px; } ::-webkit-scrollbar-track { background: #f1f1f1; } ::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; } ::-webkit-scrollbar-thumb:hover { background: #555; }
        .ndt-priority-high { border-left: 3px solid var(--danger); padding-left: 0.5rem; }
        .ndt-priority-medium { border-left: 3px solid var(--warning); padding-left: 0.5rem; }
        .ndt-priority-low { border-left: 3px solid var(--info); padding-left: 0.5rem; }
        .ndt-icon-success { background-color: var(--success); }
        .ndt-icon-primary { background-color: var(--primary); }
        .ndt-icon-info { background-color: var(--info); }
        .ndt-icon-warning { background-color: var(--warning); }
        /* Ensure progress bar background color */
        .workload-indicator { height: 100%; display: block; }
        .bg-info { background-color: var(--info) !important; } /* Use important if needed */
        .bg-success { background-color: var(--success) !important; }
        .bg-warning { background-color: var(--warning) !important; }
        .bg-danger { background-color: var(--danger) !important; }

        /* Add spacing for timeline items */
        .timeline-item { position: relative; padding-left: 60px; /* Space for dot and line */ padding-bottom: 1.5rem; }

        @media (max-width: 768px) { .timeline-line { left: 20px; } }
    </style>
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <?php include './header.php'; // Make sure this path is correct ?>

    <div class="content-wrapper p-3" style="background-color: #f8f9fc;">
        <div class="sticky-header mb-6 p-4 bg-white rounded-lg nd-shadow">
            <div class="container mx-auto">
                <div class="flex flex-wrap items-center justify-between">
                    <div class="w-full lg:w-auto mb-4 lg:mb-0">
                        <h1 class="text-2xl font-bold text-gray-800">Welcome back, <span class="text-blue-600"><?php echo htmlspecialchars($user_name); ?></span>!</h1>
                        <p class="text-gray-600 text-sm">
                            <?php echo $current_date; ?> | Role: <?php echo htmlspecialchars($user_role); ?>
                            <?php if ($is_dept_restricted) { echo " | Dept: " . htmlspecialchars($user_department_name); } ?>
                        </p>
                    </div>
                    <div class="w-full lg:w-auto flex justify-start lg:justify-end items-center">
                        <button onclick="window.location.href='daily_tasks.php'" class="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition text-sm mr-2">
                            <i class="fas fa-plus-circle mr-1"></i> New Task
                        </button>
                        <?php if ($user_role == 'Admin' || $user_role == 'Manager' || $user_role == 'team lead'): ?>
                        <button onclick="window.location.href='project.php'" class="flex items-center px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded-lg transition text-sm mr-3">
                            <i class="fas fa-folder-plus mr-1"></i> New Project
                        </button>
                        <?php endif; ?>
                        <div class="avatar-initial"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
            <div class="nd-card nd-card-primary p-5">
                <div class="flex items-center">
                    <div class="mr-5 text-3xl nd-card-icon-primary opacity-70"><i class="fas fa-tasks"></i></div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800"><?php echo $active_tasks; ?></div>
                        <div class="text-xs uppercase font-bold text-gray-600">Active Tasks</div>
                         </div>
                </div>
            </div>
             <div class="nd-card nd-card-success p-5">
                 <div class="flex items-center">
                    <div class="mr-5 text-3xl nd-card-icon-success opacity-70"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800"><?php echo $completed_tasks; ?></div>
                        <div class="text-xs uppercase font-bold text-gray-600">Completed Tasks</div>
                         <div class="mt-1 text-xs flex items-center <?php echo $completion_trend >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                           <i class="fas fa-arrow-<?php echo $completion_trend >= 0 ? 'up' : 'down'; ?> mr-1"></i>
                           <span><?php echo abs($completion_trend); ?>% trend</span>
                         </div>
                    </div>
                </div>
            </div>
             <div class="nd-card nd-card-warning p-5">
                 <div class="flex items-center">
                    <div class="mr-5 text-3xl nd-card-icon-warning opacity-70"><i class="fas fa-clock"></i></div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800"><?php echo $pending_tasks; ?></div>
                        <div class="text-xs uppercase font-bold text-gray-600">Pending Tasks</div>
                    </div>
                </div>
            </div>
             <div class="nd-card nd-card-danger p-5">
                <div class="flex items-center">
                    <div class="mr-5 text-3xl nd-card-icon-danger opacity-70"><i class="fas fa-exclamation-triangle"></i></div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800"><?php echo $overdue_tasks; ?></div>
                        <div class="text-xs uppercase font-bold text-gray-600">Overdue Tasks</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 <?php echo $is_dept_restricted ? 'lg:grid-cols-1' : 'lg:grid-cols-3'; ?> gap-6 mb-6">
            <div class="<?php echo $is_dept_restricted ? 'lg:col-span-1' : 'lg:col-span-2'; ?>">
                <div class="nd-card h-full">
                    <div class="border-b p-4">
                        <h2 class="text-lg font-bold text-gray-800">
                            Task Completion Trends <?php echo $is_dept_restricted ? '(' . htmlspecialchars($user_department_name) . ' Dept)' : ''; ?>
                        </h2>
                        <p class="text-sm text-gray-600">Weekly progress overview</p>
                    </div>
                    <div class="p-4">
                        <div class="chart-container">
                            <canvas id="taskCompletionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$is_dept_restricted): // Only show Dept Distribution if user is NOT restricted ?>
            <div class="lg:col-span-1">
                <div class="nd-card h-full">
                    <div class="border-b p-4">
                        <h2 class="text-lg font-bold text-gray-800">Task Distribution</h2>
                        <p class="text-sm text-gray-600">By department (Top 6)</p>
                    </div>
                    <div class="p-4">
                        <div style="height: 220px; margin-bottom: 1rem;">
                            <canvas id="departmentDistributionChart"></canvas>
                        </div>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                            <?php
                            // JSON Encode data for this specific chart now
                            $dept_labels_json = json_encode($dept_labels);
                            $dept_data_json = json_encode($dept_data, JSON_NUMERIC_CHECK);
                            $dept_colors_json = json_encode($dept_colors);
                            $dept_percentages_json = json_encode($dept_percentages, JSON_NUMERIC_CHECK);

                            // Display the legend
                            if(!empty($dept_labels)) {
                                for ($i = 0; $i < count($dept_labels); $i++):
                            ?>
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-sm mr-2 flex-shrink-0" style="background-color: <?php echo $dept_colors[$i % count($dept_colors)]; ?>;"></div>
                                <span class="text-xs text-gray-800 truncate mr-1"><?php echo htmlspecialchars($dept_labels[$i]); ?></span>
                                <span class="text-xs text-gray-500 ml-auto"><?php echo isset($dept_percentages[$i]) ? $dept_percentages[$i] : 0; ?>%</span>
                            </div>
                            <?php
                                endfor;
                            } else {
                                echo '<p class="text-xs text-gray-500 col-span-2">No department task data available.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; // End hiding Dept Distribution ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div>
                <div class="nd-card h-full">
                    <div class="border-b p-4">
                         <h2 class="text-lg font-bold text-gray-800">
                             Team Productivity <?php echo $is_dept_restricted ? '(' . htmlspecialchars($user_department_name) . ' Dept)' : ''; ?>
                          </h2>
                        <p class="text-sm text-gray-600">Completed tasks per member (Top 5)</p>
                    </div>
                    <div class="p-4">
                        <div class="chart-container">
                            <canvas id="teamProductivityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                 <div class="nd-card h-full">
                     <div class="border-b p-4">
                         <h2 class="text-lg font-bold text-gray-800">
                             Team Workload <?php echo $is_dept_restricted ? '(' . htmlspecialchars($user_department_name) . ' Dept)' : ''; ?>
                         </h2>
                         <p class="text-sm text-gray-600">Current active task load (Top 4)</p>
                     </div>
                     <div class="p-4">
                         <?php if (empty($team_workload)): ?>
                             <p class="text-gray-600 text-sm">No workload data available<?php echo $is_dept_restricted ? ' for this department' : ''; ?>.</p>
                         <?php else: ?>
                             <?php foreach ($team_workload as $index => $member): ?>
                             <div class="mb-4 <?php echo $index === count($team_workload) - 1 ? '' : 'pb-3 border-b border-gray-100'; ?>">
                                  <div class="flex items-center mb-2">
                                      <div class="w-8 h-8 avatar-initial mr-3 text-xs flex-shrink-0"><?php echo strtoupper(substr($member['name'], 0, 1)); ?></div>
                                      <div class="flex-grow">
                                          <h3 class="text-sm font-semibold leading-tight"><?php echo htmlspecialchars($member['name']); ?></h3>
                                          <p class="text-xs text-gray-500 leading-tight"><?php echo htmlspecialchars($member['role']); ?></p>
                                      </div>
                                      <span class="text-xs font-semibold text-gray-700 ml-2"><?php echo $member['workload']; ?>%</span>
                                  </div>
                                  <div class="flex items-center">
                                      <div class="flex-grow progress-bar-thin mr-3">
                                         <div class="workload-indicator <?php echo $member['color']; ?> h-full rounded-full" style="width: <?php echo $member['workload']; ?>%;"></div>
                                      </div>
                                  </div>
                             </div>
                             <?php endforeach; ?>
                         <?php endif; ?>
                     </div>
                 </div>
             </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
             <div class="lg:col-span-3">
                 <div class="nd-card h-full">
                     <div class="border-b p-4">
                         <h2 class="text-lg font-bold text-gray-800">
                            Active Tasks <?php echo $is_dept_restricted ? '(' . htmlspecialchars($user_department_name) . ' Dept)' : ''; ?>
                         </h2>
                         <p class="text-sm text-gray-600">Top 5 by priority & due date</p>
                     </div>
                     <div class="p-4">
                         <?php if (empty($today_tasks)): ?>
                             <p class="text-gray-600 text-sm">No active tasks found<?php echo $is_dept_restricted ? ' for your department' : ''; ?>.</p>
                         <?php else: ?>
                             <?php foreach ($today_tasks as $task): ?>
                                 <div class="mb-4 pb-3 border-b border-gray-100 last:border-b-0">
                                     <div class="flex items-start <?php echo htmlspecialchars($task['priority_class']); ?>">
                                         <div class="ml-2 flex-grow">
                                             <div class="flex justify-between items-baseline">
                                                <span class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($task['name']); ?></span>
                                                <span class="text-xs text-gray-500">Due: <?php echo htmlspecialchars($task['due_date'] ? date('M d', strtotime($task['due_date'])) : 'N/A'); ?></span>
                                             </div>
                                             <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars($task['description']); ?></p>
                                             <p class="text-xs text-blue-600 mt-1">Project: <?php echo htmlspecialchars($task['project']); ?></p>
                                         </div>
                                     </div>
                                 </div>
                             <?php endforeach; ?>
                         <?php endif; ?>
                     </div>
                 </div>
             </div>

             <div class="lg:col-span-2">
                 <div class="nd-card h-full">
                     <div class="border-b p-4">
                         <h2 class="text-lg font-bold text-gray-800">
                            Recent Activity <?php echo $is_dept_restricted ? '(' . htmlspecialchars($user_department_name) . ' Dept)' : ''; ?>
                         </h2>
                         <p class="text-sm text-gray-600">Latest updates</p>
                     </div>
                     <div class="p-4">
                         <?php if (empty($recent_activities)): ?>
                             <p class="text-gray-600 text-sm">No recent activities found.</p>
                         <?php else: ?>
                             <?php foreach ($recent_activities as $index => $activity): ?>
                                 <div class="timeline-item">
                                     <div class="timeline-line"></div>
                                     <div class="timeline-dot absolute left-0 top-0 <?php echo htmlspecialchars($activity['icon_class']); ?>">
                                         <i class="<?php echo htmlspecialchars($activity['icon']); ?>"></i>
                                     </div>
                                     <div>
                                         <span class="text-xs text-gray-500 float-right"><?php echo htmlspecialchars($activity['time_ago']); ?></span>
                                         <h3 class="text-sm font-semibold"><?php echo htmlspecialchars($activity['title']); ?></h3>
                                         <p class="text-xs text-gray-600"><?php echo $activity['description']; // Already escaped in PHP ?></p>
                                     </div>
                                 </div>
                             <?php endforeach; ?>
                         <?php endif; ?>
                     </div>
                 </div>
             </div>
        </div>

    </div><?php // include './footer.php'; // Optional footer include ?>

</div><script src="./src/js/jquery.min.js"></script>
<script src="./src/js/bootstrap.bundle.min.js"></script>
<script src="./src/js/adminlte.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctxLine = document.getElementById('taskCompletionChart');
    if (ctxLine) {
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: <?php echo $weekly_labels_json; ?>,
                datasets: [{
                    label: 'Tasks Completed',
                    data: <?php echo $weekly_data_json; ?>,
                    borderColor: 'rgb(78, 115, 223)',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: 'rgb(78, 115, 223)',
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } } // Ensure integer ticks
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    <?php if (!$is_dept_restricted): // Only initialize dept chart if not restricted ?>
    const ctxDoughnut = document.getElementById('departmentDistributionChart');
    if (ctxDoughnut) {
        new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: {
                labels: <?php echo $dept_labels_json; ?>,
                datasets: [{
                    data: <?php echo $dept_data_json; ?>,
                    backgroundColor: <?php echo $dept_colors_json; ?>,
                    hoverOffset: 4,
                    borderWidth: 1,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { display: false }, // Use custom HTML legend
                    tooltip: {
                         callbacks: {
                              label: function(context) {
                                   let label = context.label || '';
                                   if (label) { label += ': '; }
                                   let value = context.parsed || 0;
                                   // Find percentage if available
                                   let percentage = <?php echo $dept_percentages_json; ?>[context.dataIndex] || 0;
                                   label += value + ' tasks (' + percentage + '%)';
                                   return label;
                               }
                          }
                    }
                }
            }
            // If using datalabels plugin:
            // plugins: [ChartDataLabels],
            // options: { plugins: { datalabels: { formatter: (value, ctx) => { ... } } } }
        });
    }
    <?php endif; ?>

    const ctxBar = document.getElementById('teamProductivityChart');
    if (ctxBar) {
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?php echo $employee_names_json; ?>,
                datasets: [{
                    label: 'Completed Tasks',
                    data: <?php echo $employee_tasks_json; ?>,
                    backgroundColor: [ // Add more colors if needed
                         'rgba(78, 115, 223, 0.8)',
                         'rgba(28, 200, 138, 0.8)',
                         'rgba(54, 185, 204, 0.8)',
                         'rgba(246, 194, 62, 0.8)',
                         'rgba(231, 74, 59, 0.8)'
                     ],
                    borderColor: [ // Optional border
                        'rgba(78, 115, 223, 1)',
                        'rgba(28, 200, 138, 1)',
                        'rgba(54, 185, 204, 1)',
                        'rgba(246, 194, 62, 1)',
                        'rgba(231, 74, 59, 1)'
                    ],
                    borderWidth: 1,
                    borderRadius: 4, // Rounded bars
                    barThickness: 'flex', // Or set a fixed px value
                    maxBarThickness: 40 // Max width of bars
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y', // Horizontal bars
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0 } },
                    y: { ticks: { autoSkip: false } } // Prevent labels from skipping
                },
                plugins: {
                     legend: { display: false },
                     tooltip: { enabled: true }
                }
            }
        });
    }
});
</script>

</body>
</html>