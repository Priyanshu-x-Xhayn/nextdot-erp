<?php
include './backend/connect.php';

if(isset($_POST['delete'])) {
    $id = $_POST['id'] ?? 0;
    
    if (empty($id)) {
        echo "<script>
                alert('Invalid project ID!');
                window.location.href='project.php';
              </script>";
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete project manager relations
        $delete_pm = "DELETE FROM project_managers WHERE project_id = ?";
        $stmt_pm = $conn->prepare($delete_pm);
        
        if (!$stmt_pm) {
            throw new Exception("Prepare failed for delete project managers: " . $conn->error);
        }
        
        $stmt_pm->bind_param("i", $id);
        $stmt_pm->execute();
        $stmt_pm->close();
        
        // Delete project department relations
        $delete_dept = "DELETE FROM project_departments WHERE project_id = ?";
        $stmt_dept = $conn->prepare($delete_dept);
        
        if (!$stmt_dept) {
            throw new Exception("Prepare failed for delete departments: " . $conn->error);
        }
        
        $stmt_dept->bind_param("i", $id);
        $stmt_dept->execute();
        $stmt_dept->close();
        
        // Finally delete the project itself
        $query = "DELETE FROM project WHERE id = ?";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed for delete project: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed for delete project: " . $stmt->error);
        }
        
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Redirect to prevent form resubmission
        header("Location: project.php?delete_success=1");
        exit;
        
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        
        echo "<script>
                alert('Error deleting project: " . $e->getMessage() . "');
                window.location.href='project.php';
              </script>";
    }
} else {
    // Redirect if accessed directly
    header("Location: project.php");
    exit;
}
?>