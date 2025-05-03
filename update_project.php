<?php
include './backend/connect.php';

if(isset($_POST['update'])) {
    $id = $_POST['id'] ?? 0;
    $sd = $_POST['sd'] ?? '';
    $np = $_POST['np'] ?? '';
    $nc = $_POST['nc'] ?? '';
    $pm = $_POST['pm'] ?? ''; // Now employee_id
    $rc = $_POST['rc'] ?? '';
    $cd = $_POST['cd'] ?? null; // Now optional
    $departments = isset($_POST['departments']) ? $_POST['departments'] : [];
    
    // Validate required fields
    if (empty($id) || empty($sd) || empty($np) || empty($nc) || empty($pm) || empty($rc) || empty($departments)) {
        echo "<script>
                alert('Required fields cannot be empty and at least one department must be selected!');
                window.location.href='project.php';
              </script>";
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update base project record (removed pm field)
        if (empty($cd)) {
            $query = "UPDATE project SET sd=?, np=?, nc=?, rc=?, cd=NULL WHERE id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssi", $sd, $np, $nc, $rc, $id);
        } else {
            $query = "UPDATE project SET sd=?, np=?, nc=?, rc=?, cd=? WHERE id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssi", $sd, $np, $nc, $rc, $cd, $id);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed for project: " . $stmt->error);
        }
        
        $stmt->close();
        
        // Update project manager relation
        // First delete existing manager relation
        $delete_pm = "DELETE FROM project_managers WHERE project_id = ?";
        $stmt_delete_pm = $conn->prepare($delete_pm);
        
        if (!$stmt_delete_pm) {
            throw new Exception("Prepare failed for delete manager: " . $conn->error);
        }
        
        $stmt_delete_pm->bind_param("i", $id);
        $stmt_delete_pm->execute();
        $stmt_delete_pm->close();
        
        // Then insert new manager relation
        $sql_pm = "INSERT INTO project_managers (project_id, employee_id) VALUES (?, ?)";
        $stmt_pm = $conn->prepare($sql_pm);
        
        if (!$stmt_pm) {
            throw new Exception("Prepare failed for project manager: " . $conn->error);
        }
        
        $stmt_pm->bind_param("ii", $id, $pm);
        
        if (!$stmt_pm->execute()) {
            throw new Exception("Execute failed for project manager: " . $stmt_pm->error);
        }
        
        $stmt_pm->close();
        
        // Remove existing department associations
        $delete_dept = "DELETE FROM project_departments WHERE project_id = ?";
        $stmt_delete = $conn->prepare($delete_dept);
        
        if (!$stmt_delete) {
            throw new Exception("Prepare failed for delete: " . $conn->error);
        }
        
        $stmt_delete->bind_param("i", $id);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        // Insert new department associations
        foreach ($departments as $dept_id) {
            $sql_dept = "INSERT INTO project_departments (project_id, department_id) VALUES (?, ?)";
            $stmt_dept = $conn->prepare($sql_dept);
            
            if (!$stmt_dept) {
                throw new Exception("Prepare failed for department relation: " . $conn->error);
            }
            
            $stmt_dept->bind_param("ii", $id, $dept_id);
            
            if (!$stmt_dept->execute()) {
                throw new Exception("Execute failed for department relation: " . $stmt_dept->error);
            }
            
            $stmt_dept->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Redirect to prevent form resubmission
        header("Location: project.php?update_success=1");
        exit;
        
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        
        echo "<script>
                alert('Error updating project: " . $e->getMessage() . "');
                window.location.href='project.php';
              </script>";
    }
} else {
    // Redirect if accessed directly
    header("Location: project.php");
    exit;
}
?>