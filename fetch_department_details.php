<?php
include './backend/connect.php';

if(isset($_POST['department_id'])) {
    $department_id = $_POST['department_id'];
    
    // Get department info
    $dept_query = "SELECT * FROM departments WHERE id = ?";
    $dept_stmt = $conn->prepare($dept_query);
    $dept_stmt->bind_param("i", $department_id);
    $dept_stmt->execute();
    $dept_result = $dept_stmt->get_result();
    
    if($dept_result->num_rows == 0) {
        echo '<div class="alert alert-danger m-3">Department not found!</div>';
        exit;
    }
    
    $department = $dept_result->fetch_assoc();
    
    // Get team leads
    $leads_query = "SELECT * FROM employee 
                   WHERE depart = ? 
                   AND (role = 'Team Lead' OR desig = 'Team Leader')
                   ORDER BY name";
    $leads_stmt = $conn->prepare($leads_query);
    $leads_stmt->bind_param("i", $department_id);
    $leads_stmt->execute();
    $leads_result = $leads_stmt->get_result();
    
    // Get team members
    $members_query = "SELECT * FROM employee 
                     WHERE depart = ? 
                     AND role != 'Team Lead' 
                     AND desig != 'Team Leader'
                     ORDER BY name";
    $members_stmt = $conn->prepare($members_query);
    $members_stmt->bind_param("i", $department_id);
    $members_stmt->execute();
    $members_result = $members_stmt->get_result();
    
    // Get projects assigned to this department
    $projects_query = "SELECT p.* FROM project p
                      JOIN project_departments pd ON p.id = pd.project_id
                      WHERE pd.department_id = ?
                      ORDER BY p.sd DESC";
    $projects_stmt = $conn->prepare($projects_query);
    $projects_stmt->bind_param("i", $department_id);
    $projects_stmt->execute();
    $projects_result = $projects_stmt->get_result();
    
    // Build the response
    $output = '';
    
    // Department header
    $output .= '<div class="dept-detail-header">';
    $output .= '<h3>' . htmlspecialchars($department['department_name']) . '</h3>';
    $output .= '<p>Department ID: ' . $department_id . '</p>';
    $output .= '</div>';
    
    $output .= '<div class="dept-detail-content">';
    
    // Team Leads Section
    $output .= '<div class="dept-section">';
    $output .= '<h4 class="dept-section-title"><i class="fas fa-user-tie mr-2"></i> Team Leadership</h4>';
    
    if($leads_result->num_rows > 0) {
        while($lead = $leads_result->fetch_assoc()) {
            $output .= '<div class="team-member-card">';
            if(!empty($lead['Image'])) {
                $output .= '<img src="./backend/uploads/' . htmlspecialchars($lead['Image']) . '" alt="Profile">';
            } else {
                $output .= '<img src="https://via.placeholder.com/50" alt="No Image">';
            }
            $output .= '<div class="member-info">';
            $output .= '<h5 class="member-name">' . htmlspecialchars($lead['name']) . '</h5>';
            $output .= '<div class="member-role">' . htmlspecialchars($lead['desig']) . '</div>';
            $output .= '<div class="member-tags">';
            $output .= '<span class="member-tag">' . htmlspecialchars($lead['role']) . '</span>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';
        }
    } else {
        $output .= '<div class="empty-section">';
        $output .= '<i class="fas fa-user-slash"></i>';
        $output .= '<h5>No Team Leads</h5>';
        $output .= '<p>This department currently has no team leads assigned.</p>';
        $output .= '</div>';
    }
    $output .= '</div>';
    
    // Team Members Section
    $output .= '<div class="dept-section">';
    $output .= '<h4 class="dept-section-title"><i class="fas fa-users mr-2"></i> Team Members</h4>';
    
    if($members_result->num_rows > 0) {
        $output .= '<div class="row">';
        while($member = $members_result->fetch_assoc()) {
            $output .= '<div class="col-md-6 mb-3">';
            $output .= '<div class="team-member-card">';
            if(!empty($member['Image'])) {
                $output .= '<img src="./backend/uploads/' . htmlspecialchars($member['Image']) . '" alt="Profile">';
            } else {
                $output .= '<img src="https://via.placeholder.com/50" alt="No Image">';
            }
            $output .= '<div class="member-info">';
            $output .= '<h5 class="member-name">' . htmlspecialchars($member['name']) . '</h5>';
            $output .= '<div class="member-role">' . htmlspecialchars($member['desig'] ?: 'Team Member') . '</div>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';
        }
        $output .= '</div>';
    } else {
        $output .= '<div class="empty-section">';
        $output .= '<i class="fas fa-users-slash"></i>';
        $output .= '<h5>No Team Members</h5>';
        $output .= '<p>This department currently has no team members assigned.</p>';
        $output .= '</div>';
    }
    $output .= '</div>';
    
    // Projects Section
    $output .= '<div class="dept-section">';
    $output .= '<h4 class="dept-section-title"><i class="fas fa-project-diagram mr-2"></i> Active Projects</h4>';
    
    if($projects_result->num_rows > 0) {
        while($project = $projects_result->fetch_assoc()) {
            // Determine project status
            $status_class = 'pending';
            $status_text = 'Pending';
            
            $today = new DateTime();
            $start_date = new DateTime($project['sd']);
            $end_date = !empty($project['cd']) ? new DateTime($project['cd']) : null;
            
            if ($end_date && $today > $end_date) {
                $status_class = 'completed';
                $status_text = 'Completed';
            } elseif ($today >= $start_date && (!$end_date || $today <= $end_date)) {
                $status_class = 'in-progress';
                $status_text = 'In Progress';
            }
            
            $output .= '<div class="task-item">';
            $output .= '<div class="task-status ' . $status_class . '" title="' . $status_text . '"></div>';
            $output .= '<div class="task-info">';
            $output .= '<h5 class="task-title">' . htmlspecialchars($project['np']) . '</h5>';
            $output .= '<div class="task-meta">';
            $output .= '<span>Client: ' . htmlspecialchars($project['nc']) . '</span>';
            $output .= '<span>Manager: ' . htmlspecialchars($project['pm']) . '</span>';
            $output .= '</div>';
            $output .= '<div class="task-meta">';
            $output .= '<span>Start: ' . htmlspecialchars($project['sd']) . '</span>';
            if(!empty($project['cd'])) {
                $output .= '<span>Due: ' . htmlspecialchars($project['cd']) . '</span>';
            } else {
                $output .= '<span>Due: Not set</span>';
            }
            $output .= '</div>';
            $output .= '</div>';
            $output .= '</div>';
        }
    } else {
        $output .= '<div class="empty-section">';
        $output .= '<i class="fas fa-clipboard-list"></i>';
        $output .= '<h5>No Active Projects</h5>';
        $output .= '<p>This department currently has no active projects assigned.</p>';
        $output .= '</div>';
    }
    $output .= '</div>';
    
    $output .= '</div>'; // Close dept-detail-content
    
    echo $output;
} else {
    echo '<div class="alert alert-danger m-3"><i class="fas fa-exclamation-circle mr-2"></i> Invalid department selection.</div>';
}
?>