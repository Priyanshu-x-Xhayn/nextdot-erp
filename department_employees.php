<?php
include './backend/connect.php';
include 'auth_check.php';

// Fetch all departments for the dropdown
$dept_query = "SELECT * FROM departments ORDER BY department_name";
$dept_result = $conn->query($dept_query);

// Initialize variables
$selected_dept = '';
$employees = [];

// Check if a department was selected
if(isset($_GET['department']) && !empty($_GET['department'])) {
    $selected_dept = $_GET['department'];
    
    // Fetch employees in the selected department
    $emp_query = "SELECT e.* 
                 FROM employee e 
                 WHERE e.depart = ?
                 ORDER BY e.name";
    $stmt = $conn->prepare($emp_query);
    $stmt->bind_param("s", $selected_dept);
    $stmt->execute();
    $emp_result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Department Employees</title>
    <link rel="stylesheet" href="./src/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <style>
        .employee-card {
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .employee-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .employee-img {
            height: 150px;
            width: 100%;
            object-fit: cover;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .employee-img img {
            max-height: 100%;
            max-width: 100%;
        }
        
        .employee-info {
            padding: 15px;
        }
        
        .employee-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .employee-role {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .employee-contact {
            font-size: 0.85rem;
            margin-bottom: 15px;
        }
        
        .department-selector {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
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
                            <h1>Department Employees</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active">Department Employees</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <!-- Department Selection Form -->
                    <div class="department-selector">
                        <form method="GET" action="" id="deptForm">
                            <div class="row align-items-end">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="department">Select Department</label>
                                        <select class="form-control" id="department" name="department" onchange="this.form.submit()">
                                            <option value="">-- Select Department --</option>
                                            <?php while($dept = $dept_result->fetch_assoc()): ?>
                                                <option value="<?php echo $dept['id']; ?>" <?php echo ($selected_dept == $dept['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <?php if(!empty($selected_dept)): ?>
                                        <a href="department_employees.php" class="btn btn-outline-secondary ml-2">
                                            <i class="fas fa-times"></i> Clear Filter
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Employee Listing -->
                    <div class="row">
                        <?php 
                        if(isset($emp_result) && $emp_result->num_rows > 0): 
                            while($employee = $emp_result->fetch_assoc()):
                        ?>
                            <div class="col-md-4">
                                <div class="card employee-card">
                                    <div class="employee-img">
                                    <?php if(isset($_SESSION['user_image']) && !empty($_SESSION['user_image'])): ?>
            <img src="./backend/uploads/<?= htmlspecialchars($_SESSION['user_image']) ?>" class="img-circle elevation-2" alt="User Image">
        <?php else: ?>
            <img src="./src/images/default.png" class="img-circle elevation-2" alt="User Image">
        <?php endif; ?>
                                    </div>
                                    <div class="employee-info">
                                        <h5 class="employee-name"><?php echo htmlspecialchars($employee['name']); ?></h5>
                                        <div class="employee-role">
                                            <?php echo htmlspecialchars($employee['desig'] ?: 'Employee'); ?> | 
                                            <?php echo htmlspecialchars($employee['role']); ?>
                                        </div>
                                        <div class="employee-contact">
                                            <div><i class="fas fa-envelope mr-2"></i> <?php echo htmlspecialchars($employee['email']); ?></div>
                                            <div><i class="fas fa-phone mr-2"></i> <?php echo htmlspecialchars($employee['contact']); ?></div>
                                        </div>
                                        <a href="employee_profile.php?id=<?php echo $employee['id']; ?>" class="btn btn-info btn-block">
                                            <i class="fas fa-eye"></i> View Profile
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        elseif(isset($emp_result) && $emp_result->num_rows == 0): 
                        ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i> No employees found in this department.
                                </div>
                            </div>
                        <?php elseif(empty($selected_dept)): ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i> Please select a department to view employees.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
        
        <?php include './footer.php'; ?>
    </div>

    <script src="./src/js/jquery.min.js"></script>
    <script src="./src/js/bootstrap.bundle.min.js"></script>
    <script src="./src/js/adminlte.min.js"></script>
</body>
</html>