 <?php
include './backend/connect.php'; // Include your database connection

$project_data = null; // Initialize variable to hold project data
$error_message = '';  // Initialize error message

// 1. Check if ID is provided in the URL and is numeric
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $project_id = intval($_GET['id']); // Convert to integer for safety

    // 2. Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM project WHERE id = ?");

    if ($stmt) {
        // 3. Bind the ID parameter
        $stmt->bind_param("i", $project_id); // 'i' means integer type

        // 4. Execute the query
        $stmt->execute();

        // 5. Get the result
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            // 6. Fetch the project data
            $project_data = $result->fetch_assoc();
        } else {
            // Project with the given ID not found
            $error_message = "Project not found.";
        }

        // 7. Close the statement
        $stmt->close();
    } else {
        // Error preparing the statement
        $error_message = "Database error: Could not prepare statement.";
        // Log the actual error: error_log($conn->error);
    }
} else {
    // ID not provided or invalid
    $error_message = "Invalid or missing Project ID.";
}

// 8. Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <!-- Use a consistent title -->
    <title>Project Profile - <?php echo $project_data ? htmlspecialchars($project_data['np']) : 'Not Found'; ?> - ProManager</title>
    <!-- Include necessary CSS (Bootstrap, FontAwesome, AdminLTE, your custom styles) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./src/css/adminlte.min.css">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- You might want to link the same stylesheet as your main page for consistency -->
    <style>
        /* Add specific styles for the profile page if needed */
        body { background-color: #f4f6f9; /* AdminLTE default */ }
        .profile-card {
            border-radius: var(--border-radius, 12px);
            box-shadow: var(--shadow, 0 10px 20px rgba(0,0,0,0.1));
            margin-top: 2rem;
            border: none;
            overflow: hidden;
        }
        .profile-header {
             background: var(--bg-gradient, linear-gradient(135deg, #072e63 0%, #051e42 100%));
             color: var(--text-color, white);
             padding: 1.5rem;
             font-size: 1.5rem;
             font-weight: 600;
        }
         .profile-body { padding: 1.5rem 2rem; }
         .detail-item { margin-bottom: 1rem; }
         .detail-label { font-weight: 600; color: #555; display: block; margin-bottom: 0.25rem; }
         .detail-value { font-size: 1.05rem; color: #333; }
         .back-button { margin-top: 1.5rem; }

         /* Use root variables if defined in your main CSS */
        :root {
          --primary-color: #072e63;
          --primary-dark: #051e42;
          --text-color: #ffffff;
          --border-radius: 12px;
          --shadow: 0 10px 20px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0, 0, 0, 0.06);
          --bg-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        }
    </style>
</head>
<body class="hold-transition sidebar-mini"> 
<div class="wrapper"> 

    <?php include './header.php'; // Reuse your header ?>

    <div class="content-wrapper"> 
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Project Profile</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li> 
                            <li class="breadcrumb-item active">Project Profile</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                        <a href="index.php" class="btn btn-sm btn-outline-danger ms-3">Go Back</a> 
                    </div>
                <?php elseif ($project_data): ?>
                    <div class="card profile-card">
                        <div class="profile-header">
                           <i class="fas fa-folder-open me-2"></i> <?php echo htmlspecialchars($project_data['np']); ?>
                        </div>
                        <div class="profile-body">
                           <div class="row">
                                <div class="col-md-6 detail-item">
                                    <span class="detail-label"><i class="bi bi-calendar-event me-2 text-primary"></i>Start Date</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($project_data['sd'] ? date("d M Y", strtotime($project_data['sd'])) : 'N/A'); ?></span>
                                </div>
                                <div class="col-md-6 detail-item">
                                    <span class="detail-label"><i class="bi bi-calendar-check me-2 text-success"></i>Target Completion Date</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($project_data['cd'] ? date("d M Y", strtotime($project_data['cd'])) : 'N/A'); ?></span>
                                </div>
                                <div class="col-md-6 detail-item">
                                    <span class="detail-label"><i class="bi bi-person-badge me-2 text-info"></i>Client Name</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($project_data['nc']); ?></span>
                                </div>
                                <div class="col-md-6 detail-item">
                                    <span class="detail-label"><i class="bi bi-person-workspace me-2 text-warning"></i>Project Manager</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($project_data['pm']); ?></span>
                                </div>
                                <div class="col-md-12 detail-item">
                                    <span class="detail-label"><i class="bi bi-card-list me-2 text-secondary"></i>Client Requirement</span>
                                    <span class="detail-value"><?php echo nl2br(htmlspecialchars($project_data['rc'])); // Use nl2br if requirement can have line breaks ?></span>
                                </div>
                                <div class="col-md-6 detail-item">
                                    <span class="detail-label"><i class="bi bi-building me-2" style="color: #6f42c1;"></i>Department</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($project_data['dt']); ?></span>
                                </div>
                                 <div class="col-md-6 detail-item">
                                    <span class="detail-label"><i class="bi bi-key me-2 text-muted"></i>Project ID</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($project_data['id']); ?></span>
                                </div>
                           </div>

                           <div class="text-center back-button">
                               <a href="index.php" class="btn btn-outline-secondary"> <i class="fas fa-arrow-left me-1"></i> Back to Dashboard</a> 
                        </div>
                    </div>
                <?php else: ?>
                    
                     <div class="alert alert-warning" role="alert">
                       
                     </div>
                <?php endif; ?>
            </div><!-- /.container-fluid -->
        </section><!-- /.content -->
    </div><!-- /.content-wrapper -->

    <?php include './footer.php'; // Reuse your footer ?>

</div><!-- ./wrapper -->

<!-- Include necessary JS (jQuery, Bootstrap Bundle, AdminLTE) -->
<script src="./src/js/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="./src/js/adminlte.min.js"></script>

</body>
</html>