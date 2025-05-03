<?php
// Include database connection
include './backend/connect.php';
include 'auth_check.php';

// Check if tasks table needs to be modified
$check_columns_sql = "SHOW COLUMNS FROM tasks";
$columns_result = $conn->query($check_columns_sql);

$column_names = [];
while ($column = $columns_result->fetch_assoc()) {
    $column_names[] = $column['Field'];
}

// Add missing columns if needed
$add_columns = [];

// Check if project_id column exists
if (!in_array('project_id', $column_names)) {
    $add_columns[] = "ADD COLUMN project_id INT AFTER assigned_to";
}

// Check for other necessary columns
$columns_to_check = [
    'is_overdue' => "ADD COLUMN is_overdue TINYINT(1) DEFAULT 0 AFTER status",
    'pending_age' => "ADD COLUMN pending_age VARCHAR(255) DEFAULT '' AFTER pending_days",
    'priori' => "ADD COLUMN priori VARCHAR(50) DEFAULT 'Medium' AFTER due_date",
];

foreach ($columns_to_check as $column => $add_statement) {
    if (!in_array($column, $column_names)) {
        $add_columns[] = $add_statement;
    }
}

// Add the missing columns if any
if (!empty($add_columns)) {
    $alter_table_sql = "ALTER TABLE tasks " . implode(", ", $add_columns);
    
    if ($conn->query($alter_table_sql) === TRUE) {
        echo "Tasks table modified successfully.<br>";
    } else {
        echo "Error modifying tasks table: " . $conn->error . "<br>";
    }
}

// Create foreign key relationships if they don't exist
$check_fk_sql = "
    SELECT * 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' 
    AND TABLE_NAME = 'tasks' 
    AND CONSTRAINT_NAME = 'fk_tasks_project'";
    
$fk_result = $conn->query($check_fk_sql);

if ($fk_result->num_rows == 0) {
    $add_fk_sql = "
        ALTER TABLE tasks 
        ADD CONSTRAINT fk_tasks_project 
        FOREIGN KEY (project_id) 
        REFERENCES project(id)";
        
    if ($conn->query($add_fk_sql) === TRUE) {
        echo "Foreign key relationship with project table created successfully.<br>";
    } else {
        echo "Error creating foreign key relationship: " . $conn->error . "<br>";
    }
}

// Create an index on start_date for better query performance
$check_index_sql = "
    SHOW INDEX FROM tasks 
    WHERE Key_name = 'idx_start_date'";
    
$index_result = $conn->query($check_index_sql);

if ($index_result->num_rows == 0) {
    $add_index_sql = "
        CREATE INDEX idx_start_date 
        ON tasks(start_date)";
        
    if ($conn->query($add_index_sql) === TRUE) {
        echo "Index on start_date created successfully.<br>";
    } else {
        echo "Error creating index: " . $conn->error . "<br>";
    }
}

echo "Task management system setup complete.";
?>