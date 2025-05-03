<?php
include './backend/connect.php';

if(isset($_POST['department_id'])) {
    $department_id = $_POST['department_id'];
    
    // Get department name first
    $dept_query = "SELECT department_name FROM departments WHERE id = ?";
    $dept_stmt = $conn->prepare($dept_query);
    $dept_stmt->bind_param("i", $department_id);
    $dept_stmt->execute();
    $dept_result = $dept_stmt->get_result();
    $dept_name = "";
    
    if($dept_row = $dept_result->fetch_assoc()) {
        $dept_name = $dept_row['department_name'];
    }
    
    // Now fetch employees from this department
    $sql = "SELECT e.*, CASE WHEN tl.id IS NOT NULL THEN 1 ELSE 0 END as is_team_lead
            FROM employee e
            LEFT JOIN (
                SELECT id FROM employee 
                WHERE depart = ? AND (desig = 'Team Leader' OR role = 'Team Lead')
            ) tl ON e.id = tl.id
            WHERE e.depart = ?
            ORDER BY is_team_lead DESC, e.name";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $department_id, $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $output = '';
    $output .= '<h3>' . htmlspecialchars($dept_name) . ' Department Employees</h3>';
    $output .= '<div class="mb-2">';
    $output .= '<button class="btn btn-success export-btn" data-type="excel"><i class="fas fa-file-excel"></i> Export to Excel</button>';
    $output .= '<button class="btn btn-warning export-btn" data-type="csv"><i class="fas fa-file-csv"></i> Export to CSV</button>';
    $output .= '<button class="btn btn-info export-btn" data-type="pdf"><i class="fas fa-file-pdf"></i> Export to PDF</button>';
    $output .= '<button class="btn btn-secondary export-btn" data-type="print"><i class="fas fa-print"></i> Print</button>';
    $output .= '</div>';
    
    if($result->num_rows > 0) {
        $output .= '<div class="table-responsive">';
        $output .= '<table id="employeeTable" class="table table-bordered table-hover">';
        $output .= '<thead style="background: var(--bg-gradient);">';
        $output .= '<tr>';
        $output .= '<th>Employee Name</th>';
        $output .= '<th>Contact</th>';
        $output .= '<th>Email</th>';
        $output .= '<th>Designation</th>';
        $output .= '<th>Role</th>';
        $output .= '<th>Actions</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody>';
        
        while($row = $result->fetch_assoc()) {
            $roleClass = '';
            switch($row['role']) {
                case 'Admin': $roleClass = 'badge bg-danger'; break;
                case 'Manager': $roleClass = 'badge bg-warning text-dark'; break;
                case 'Team Lead': $roleClass = 'badge bg-primary'; break;
                default: $roleClass = 'badge bg-secondary';
            }
            
            $output .= '<tr>';
            $output .= '<td>' . htmlspecialchars($row['name']) . ($row['is_team_lead'] ? ' <span class="badge bg-info">Team Lead</span>' : '') . '</td>';
            $output .= '<td>' . htmlspecialchars($row['contact']) . '</td>';
            $output .= '<td>' . htmlspecialchars($row['email']) . '</td>';
            $output .= '<td>' . htmlspecialchars($row['desig']) . '</td>';
            $output .= '<td><span class="' . $roleClass . '">' . htmlspecialchars($row['role'] ?? 'User') . '</span></td>';
            $output .= '<td>';
            $output .= '<a href="employee_profile.php?id=' . $row['id'] . '" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> View Profile</a>';
            $output .= '</td>';
            $output .= '</tr>';
        }
        
        $output .= '</tbody>';
        $output .= '</table>';
        
        // Add DataTables initialization script
        $output .= '<script>
            $(document).ready(function() {
                var table = $("#employeeTable").DataTable({
                    "responsive": true,
                    "lengthChange": false,
                    "autoWidth": false
                });
                
                $(".export-btn").on("click", function() {
                    var type = $(this).data("type");
                    switch(type) {
                        case "excel":
                            table.button(".buttons-excel").trigger();
                            break;
                        case "csv":
                            table.button(".buttons-csv").trigger();
                            break;
                        case "pdf":
                            table.button(".buttons-pdf").trigger();
                            break;
                        case "print":
                            table.button(".buttons-print").trigger();
                            break;
                    }
                });
                
                $(".view-employee").on("click", function() {
                    var employeeId = $(this).data("id");
                    $.ajax({
                        url: "fetch_employee_details.php",
                        type: "POST",
                        data: { employee_id: employeeId },
                        success: function(response) {
                            $("#employeeDetailContent").html(response);
                            $("#employeeDetailModal").modal("show");
                        },
                        error: function(xhr, status, error) {
                            console.error("Error fetching employee details:", error);
                            alert("Failed to load employee details");
                        }
                    });
                });
            });
        </script>';
    } else {
        $output .= '<div class="empty-section">';
        $output .= '<i class="fas fa-users-slash"></i>';
        $output .= '<h4>No Employees Found</h4>';
        $output .= '<p>There are no employees assigned to this department yet.</p>';
        $output .= '</div>';
    }
    
    echo $output;
} else {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Invalid department selection.</div>';
}
?>