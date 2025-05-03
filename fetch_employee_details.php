<?php
include './backend/connect.php';

if(isset($_POST['employee_id'])) {
    $employee_id = $_POST['employee_id'];
    
    // Fetch employee details
    $query = "SELECT e.*, d.department_name 
              FROM employee e 
              LEFT JOIN departments d ON e.depart = d.id 
              WHERE e.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 0) {
        echo '<div class="alert alert-danger">Employee not found!</div>';
        exit;
    }
    
    $employee = $result->fetch_assoc();
    
    // Fetch assigned projects (from project_departments table if using many-to-many)
    $projects_query = "SELECT p.* FROM project p
                      JOIN project_departments pd ON p.id = pd.project_id
                      WHERE pd.department_id = ?
                      ORDER BY p.sd DESC";
    $projects_stmt = $conn->prepare($projects_query);
    $projects_stmt->bind_param("i", $employee['depart']);
    $projects_stmt->execute();
    $projects_result = $projects_stmt->get_result();
    
    // Build the output
    $output = '<div class="employee-detail-header bg-primary text-white p-4">';
    $output .= '<div class="row align-items-center">';
    
    // Employee image
    $output .= '<div class="col-md-3 text-center">';
    if(!empty($employee['Image'])) {
        $output .= '<img src="./backend/uploads/' . htmlspecialchars($employee['Image']) . '" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover; border: 4px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.2);" alt="Profile">';
    } else {
        $output .= '<div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 120px; height: 120px; background: rgba(255,255,255,0.2); margin: 0 auto; font-size: 3rem;">';
        $output .= '<i class="fas fa-user"></i>';
        $output .= '</div>';
    }
    $output .= '</div>';
    
    // Employee info
    $output .= '<div class="col-md-9">';
    $output .= '<h3>' . htmlspecialchars($employee['name']) . '</h3>';
    
    $roleClass = '';
    switch($employee['role']) {
        case 'Admin': $roleClass = 'badge bg-danger'; break;
        case 'Manager': $roleClass = 'badge bg-warning text-dark'; break;
        case 'Team Lead': $roleClass = 'badge bg-primary'; break;
        default: $roleClass = 'badge bg-secondary';
    }
    
    $output .= '<p>' . htmlspecialchars($employee['desig'] ?? 'Employee') . ' <span class="' . $roleClass . '">' . htmlspecialchars($employee['role'] ?? 'User') . '</span></p>';
    $output .= '<div class="row mt-3">';
    $output .= '<div class="col-md-6">';
    $output .= '<p><i class="fas fa-envelope mr-2"></i> ' . htmlspecialchars($employee['email']) . '</p>';
    $output .= '<p><i class="fas fa-phone mr-2"></i> ' . htmlspecialchars($employee['contact']) . '</p>';
    $output .= '</div>';
    $output .= '<div class="col-md-6">';
    $output .= '<p><i class="fas fa-building mr-2"></i> ' . htmlspecialchars($employee['department_name'] ?? 'Not Assigned') . '</p>';
    $output .= '<p><i class="fas fa-calendar-alt mr-2"></i> Joined: ' . htmlspecialchars($employee['joining_date']) . '</p>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
    
    // Employee details content
    $output .= '<div class="p-4">';
    
    // Projects section
    $output .= '<h4 class="mb-3">Current Projects</h4>';
    
    if($projects_result->num_rows > 0) {
        $output .= '<div class="table-responsive">';
        $output .= '<table class="table table-bordered table-hover">';
        $output .= '<thead class="bg-light">';
        $output .= '<tr>';
        $output .= '<th>Project Name</th>';
        $output .= '<th>Client</th>';
        $output .= '<th>Start Date</th>';
        $output .= '<th>End Date</th>';
        $output .= '<th>Status</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody>';
        
        while($project = $projects_result->fetch_assoc()) {
            // Determine project status
            $status_class = 'bg-warning';
            $status_text = 'Pending';
            
            $today = new DateTime();
            $start_date = new DateTime($project['sd']);
            $end_date = !empty($project['cd']) ? new DateTime($project['cd']) : null;
            
            if ($end_date && $today > $end_date) {
                $status_class = 'bg-success';
                $status_text = 'Completed';
            } elseif ($today >= $start_date && (!$end_date || $today <= $end_date)) {
                $status_class = 'bg-primary';
                $status_text = 'In Progress';
            }
            
            $output .= '<tr>';
            $output .= '<td>' . htmlspecialchars($project['np']) . '</td>';
            $output .= '<td>' . htmlspecialchars($project['nc']) . '</td>';
            $output .= '<td>' . htmlspecialchars($project['sd']) . '</td>';
            $output .= '<td>' . (!empty($project['cd']) ? htmlspecialchars($project['cd']) : 'Not set') . '</td>';
            $output .= '<td><span class="badge ' . $status_class . '">' . $status_text . '</span></td>';
            $output .= '</tr>';
        }
        
        $output .= '</tbody>';
        $output .= '</table>';
        $output .= '</div>';
    } else {
        $output .= '<div class="alert alert-info">';
        $output .= '<i class="fas fa-info-circle mr-2"></i> This employee is not assigned to any active projects.';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    echo $output;
} else {
    echo '<div class="alert alert-danger">Invalid employee selection.</div>';
}
?>