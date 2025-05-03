<?php
// migrate_projects.php - Run once to migrate from old structure to new structure
include './backend/connect.php';

// Create the project_departments table if it doesn't exist
$table_check = $conn->query("SHOW TABLES LIKE 'project_departments'");
if ($table_check->num_rows == 0) {
    echo "Creating project_departments table...<br>";
    
    $conn->query("CREATE TABLE project_departments (
        id INT(11) NOT NULL AUTO_INCREMENT,
        project_id INT(11) NOT NULL,
        department_id INT(11) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY project_dept (project_id, department_id),
        FOREIGN KEY (project_id) REFERENCES project(id) ON DELETE CASCADE,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    echo "Table created successfully.<br>";
}

// Check if column 'dt' exists in project table
$column_check = $conn->query("SHOW COLUMNS FROM project LIKE 'dt'");
if ($column_check->num_rows > 0) {
    // Column exists, migrate data
    echo "Migrating project department data...<br>";
    
    // Get existing projects with departments
    $result = $conn->query("SELECT id, dt FROM project WHERE dt IS NOT NULL AND dt != ''");
    
    if ($result->num_rows > 0) {
        $migrated_count = 0;
        
        while ($row = $result->fetch_assoc()) {
            $project_id = $row['id'];
            $department_id = $row['dt'];
            
            // Insert into project_departments
            $insert = $conn->prepare("INSERT IGNORE INTO project_departments (project_id, department_id) VALUES (?, ?)");
            $insert->bind_param("ii", $project_id, $department_id);
            
            if ($insert->execute()) {
                $migrated_count++;
            }
            
            $insert->close();
        }
        
        echo "Migrated $migrated_count department relationships.<br>";
    } else {
        echo "No projects with departments to migrate.<br>";
    }
    
    // Optionally drop the dt column (uncomment if you want to remove it after migration)
    // $conn->query("ALTER TABLE project DROP COLUMN dt");
    // echo "Removed old dt column.<br>";
}

echo "Migration completed.";
?>