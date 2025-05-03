<?php
include './backend/connect.php';
include 'auth_check.php';
include './update_raw.php';
include './delete_raw.php';

// Debug errors
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Fetch employee data grouped by roles
$employeesQuery = "SELECT e.*, d.department_name 
                  FROM employee e
                  LEFT JOIN departments d ON e.depart = d.id
                  ORDER BY e.role, e.name";
$employeesResult = $conn->query($employeesQuery);

// Group employees by role
$employees_by_role = [];
while ($employeesResult && $row = $employeesResult->fetch_assoc()) {
    $role = $row['role'] ?: 'User'; // Default to 'User' if role is empty
    if (!isset($employees_by_role[$role])) {
        $employees_by_role[$role] = [];
    }
    $employees_by_role[$role][] = $row;
}

// Fetch departments from departments table
$departmentsQuery = "SELECT id, department_name FROM departments ORDER BY department_name";
$departmentsResult = $conn->query($departmentsQuery);
$departments = [];
if ($departmentsResult) {
    while ($dept = $departmentsResult->fetch_assoc()) {
        $departments[] = $dept;
    }
}

// Check if this is an AJAX request for role update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_role"])) {
    // This is an AJAX request, handle it separately
    $employee_id = isset($_POST['employee_id']) ? filter_var($_POST['employee_id'], FILTER_SANITIZE_NUMBER_INT) : 0;
    $new_role = isset($_POST['new_role']) ? $_POST['new_role'] : '';
    
    $response = [];
    
    if (empty($employee_id) || empty($new_role)) {
        $response['status'] = 'error';
        $response['message'] = 'Missing required parameters';
    } else {
        $updateRoleQuery = "UPDATE employee SET role = ? WHERE id = ?";
        $stmt = $conn->prepare($updateRoleQuery);
        $stmt->bind_param("si", $new_role, $employee_id);
        
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Role updated successfully';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Error updating role: ' . $conn->error;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Check if this is an AJAX request for department addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_department"])) {
    // This is an AJAX request to add a department
    $department_name = isset($_POST['department_name']) ? $_POST['department_name'] : '';
    
    $response = [];
    
    if (empty($department_name)) {
        $response['status'] = 'error';
        $response['message'] = 'Department name cannot be empty';
    } else {
        // Insert directly into departments table
        $insertQuery = "INSERT INTO departments (department_name) VALUES (?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("s", $department_name);
        
        if ($stmt->execute()) {
            $new_dept_id = $conn->insert_id; // Get the new department ID
            
            $response['status'] = 'success';
            $response['message'] = 'Department added successfully';
            $response['department'] = [
                'id' => $new_dept_id,
                'name' => $department_name
            ];
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Error adding department: ' . $conn->error;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$selected_designation = "";

// Fetch the records from the database for raw materials (keeping original functionality)
$result = $conn->query("SELECT * FROM raw_material"); 

// Handle new admin addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $entry_date = $_POST['entry_date'] ?? '';
    $name = $_POST['name'] ?? '';
    $Email = $_POST['Email'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $desig = $_POST['desig'] ?? '';
    $manage_depart = $_POST['manage_depart'] ?? ''; // This is now the department ID

    // Insert employee details into database
    $sql = "INSERT INTO employee (joining_date, name, email, contact, desig, depart, role) 
            VALUES (?, ?, ?, ?, ?, ?, 'Admin')";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $entry_date, $name, $Email, $contact, $desig, $manage_depart);
    
    if ($stmt->execute()) {
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
        exit();
    } else {
        $error_message = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <link rel="stylesheet" href="./src/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
  <!-- Keep your existing CSS -->
  <style>
    /* Keep your existing styles */
  </style>
</head>

<body class="hold-transition sidebar-mini">
  <div class="wrapper">
    <?php include './header.php'; ?>
    <div class="content-wrapper">
      <section class="content">
        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="alert alert-success">
          Admin added successfully!
        </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
          <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="row mb-4">
          <div class="col-md-12">
            <button type="button" class="btn btn-primary d-none" data-toggle="modal" data-target="#adminModal">
              <i class="fas fa-user-plus"></i> Add Admin
            </button>
            <button type="button" class="btn btn-info ml-2" data-toggle="modal" data-target="#editRoleModal">
              <i class="fas fa-user-tag"></i> Edit Role
            </button>
            <button type="button" class="btn btn-success ml-2" data-toggle="modal" data-target="#addDepartmentModal">
              <i class="fas fa-building"></i> Add Department
            </button>
          </div>
        </div>

        <!-- Modal for Add Admin -->
        <div class="modal fade" id="adminModal" tabindex="-1" role="dialog" aria-labelledby="adminModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="adminModalLabel">Add Admin</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">×</span>
                </button>
              </div>
              <div class="modal-body">
                <form action="" method="POST" enctype="multipart/form-data">
                  <div class="row">
                    <div class="form-group col-md-6">
                      <label for="entry_date">Joining Date</label>
                      <input type="date" class="form-control" id="entry_date" name="entry_date" required>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="name">Name</label>
                      <input type="text" class="form-control" id="name" name="name" placeholder="Name" required>
                    </div>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                      <label for="contact">Contact No</label>
                      <input type="text" class="form-control" id="contact" name="contact" placeholder="Contact No" required>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="Email">Email Id</label>
                      <input type="email" class="form-control" id="Email" name="Email" placeholder="Email" required>
                    </div>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                      <label for="desig">Designation</label>
                      <input type="text" class="form-control" id="desig" name="desig" placeholder="Designation" required>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="manage_depart">Department</label>
                      <select class="form-control" id="manage_depart" name="manage_depart" required>
                        <option value="">Select Department</option>
                        <?php foreach($departments as $dept): ?>
                          <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" name="submit">Submit</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Role-based employee sections -->
        <div class="container-fluid">
          <!-- Admins Section -->
          <?php if(isset($employees_by_role['Admin']) && count($employees_by_role['Admin']) > 0): ?>
          <div class="role-section mb-4">
            <div class="role-heading">
              <span>Admins</span>
              <span class="badge"><?php echo count($employees_by_role['Admin']); ?> employees</span>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered" id="adminsTable">
                <thead>
                  <tr>
                    <th>SL.No</th>
                    <th>Date</th>
                    <th>Name</th>
                    <th>Contact No</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $counter = 1;
                  foreach($employees_by_role['Admin'] as $admin): 
                  ?>
                  <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo htmlspecialchars($admin['joining_date']); ?></td>
                    <td><?php echo htmlspecialchars($admin['name']); ?></td>
                    <td><?php echo htmlspecialchars($admin['contact']); ?></td>
                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                    <td><?php echo htmlspecialchars($admin['department_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($admin['desig']); ?></td>
                    <td>
                      <button type="button" class="btn btn-info btn-sm edit-role-btn" 
                          data-id="<?php echo $admin['id']; ?>" 
                          data-name="<?php echo htmlspecialchars($admin['name']); ?>" 
                          data-role="<?php echo htmlspecialchars($admin['role']); ?>">
                          <i class="fas fa-user-tag"></i> Edit Role
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php endif; ?>

          <!-- Managers Section -->
          <?php if(isset($employees_by_role['Manager']) && count($employees_by_role['Manager']) > 0): ?>
          <div class="role-section mb-4">
            <div class="role-heading">
              <span>Managers</span>
              <span class="badge"><?php echo count($employees_by_role['Manager']); ?> employees</span>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered" id="managersTable">
                <thead>
                  <tr>
                    <th>SL.No</th>
                    <th>Date</th>
                    <th>Name</th>
                    <th>Contact No</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $counter = 1;
                  foreach($employees_by_role['Manager'] as $manager): 
                  ?>
                  <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo htmlspecialchars($manager['joining_date']); ?></td>
                    <td><?php echo htmlspecialchars($manager['name']); ?></td>
                    <td><?php echo htmlspecialchars($manager['contact']); ?></td>
                    <td><?php echo htmlspecialchars($manager['email']); ?></td>
                    <td><?php echo htmlspecialchars($manager['department_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($manager['desig']); ?></td>
                    <td>
                      <button type="button" class="btn btn-info btn-sm edit-role-btn" 
                          data-id="<?php echo $manager['id']; ?>" 
                          data-name="<?php echo htmlspecialchars($manager['name']); ?>" 
                          data-role="<?php echo htmlspecialchars($manager['role']); ?>">
                          <i class="fas fa-user-tag"></i> Edit Role
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php endif; ?>

          <!-- Team Leads Section -->
          <?php if(isset($employees_by_role['Team Lead']) && count($employees_by_role['Team Lead']) > 0): ?>
          <div class="role-section mb-4">
            <div class="role-heading">
              <span>Team Leads</span>
              <span class="badge"><?php echo count($employees_by_role['Team Lead']); ?> employees</span>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered" id="teamLeadsTable">
                <thead>
                  <tr>
                    <th>SL.No</th>
                    <th>Date</th>
                    <th>Name</th>
                    <th>Contact No</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $counter = 1;
                  foreach($employees_by_role['Team Lead'] as $teamLead): 
                  ?>
                  <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo htmlspecialchars($teamLead['joining_date']); ?></td>
                    <td><?php echo htmlspecialchars($teamLead['name']); ?></td>
                    <td><?php echo htmlspecialchars($teamLead['contact']); ?></td>
                    <td><?php echo htmlspecialchars($teamLead['email']); ?></td>
                    <td><?php echo htmlspecialchars($teamLead['department_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($teamLead['desig']); ?></td>
                    <td>
                      <button type="button" class="btn btn-info btn-sm edit-role-btn" 
                          data-id="<?php echo $teamLead['id']; ?>" 
                          data-name="<?php echo htmlspecialchars($teamLead['name']); ?>" 
                          data-role="<?php echo htmlspecialchars($teamLead['role']); ?>">
                          <i class="fas fa-user-tag"></i> Edit Role
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php endif; ?>

          <!-- Users Section -->
          <?php if(isset($employees_by_role['User']) && count($employees_by_role['User']) > 0): ?>
          <div class="role-section mb-4">
            <div class="role-heading">
              <span>Regular Users</span>
              <span class="badge"><?php echo count($employees_by_role['User']); ?> employees</span>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered" id="usersTable">
                <thead>
                  <tr>
                    <th>SL.No</th>
                    <th>Date</th>
                    <th>Name</th>
                    <th>Contact No</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $counter = 1;
                  foreach($employees_by_role['User'] as $user): 
                    // Skip department placeholder entries (if any still exist)
                    if (strpos($user['name'], 'Dept:') === 0) continue;
                  ?>
                  <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo htmlspecialchars($user['joining_date']); ?></td>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['contact']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['department_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($user['desig']); ?></td>
                    <td>
                      <button type="button" class="btn btn-info btn-sm edit-role-btn" 
                          data-id="<?php echo $user['id']; ?>" 
                          data-name="<?php echo htmlspecialchars($user['name']); ?>" 
                          data-role="<?php echo htmlspecialchars($user['role']); ?>">
                          <i class="fas fa-user-tag"></i> Edit Role
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Edit Role Modal with Search -->
        <div class="modal fade" id="editRoleModal" tabindex="-1" role="dialog" aria-labelledby="editRoleModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="editRoleModalLabel">Edit Employee Role</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <!-- Employee Search -->
                <div class="employee-search-container">
                  <i class="fas fa-search"></i>
                  <input type="text" class="form-control employee-search" id="employeeSearch" placeholder="Search employees by name...">
                </div>
                
                <!-- Employee List -->
                <div class="employee-list">
                  <div class="list-group" id="employeeList">
                    <?php 
                    // Reset the employee result pointer
                    $employeesResult = $conn->query("SELECT e.id, e.name, e.role, d.department_name 
                                                    FROM employee e
                                                    LEFT JOIN departments d ON e.depart = d.id
                                                    WHERE e.name NOT LIKE 'Dept:%' 
                                                    ORDER BY e.name");
                    
                    if ($employeesResult) {
                        while ($emp = $employeesResult->fetch_assoc()): 
                    ?>
                    <a href="#" class="list-group-item list-group-item-action" 
                       data-id="<?php echo $emp['id']; ?>" 
                       data-role="<?php echo htmlspecialchars($emp['role']); ?>">
                      <?php echo htmlspecialchars($emp['name']); ?>
                      <span class="employee-role">
                        (<?php echo htmlspecialchars($emp['role'] ?: 'User'); ?>)
                        <?php if(!empty($emp['department_name'])): ?>
                        - <?php echo htmlspecialchars($emp['department_name']); ?>
                        <?php endif; ?>
                      </span>
                    </a>
                    <?php 
                        endwhile;
                    }
                    ?>
                  </div>
                </div>
                
                <!-- Selected Employee Info -->
                <div class="form-group">
                  <label>Selected Employee</label>
                  <input type="text" class="form-control" id="selectedEmployee" readonly>
                  <input type="hidden" id="selectedEmployeeId">
                </div>
                
                <!-- Role Selection -->
                <div class="form-group">
                  <label for="new_role">New Role</label>
                  <select class="form-control" id="new_role" name="new_role" required>
                    <option value="">Select Role</option>
                    <option value="Admin">Admin</option>
                    <option value="Manager">Manager</option>
                    <option value="Team Lead">Team Lead</option>
                    <option value="User">User</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" id="saveRoleBtn" class="btn btn-primary">Update Role</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Add Department Modal -->
        <div class="modal fade" id="addDepartmentModal" tabindex="-1" role="dialog" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="addDepartmentModalLabel">Add New Department</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="department-form">
                  <div class="form-group">
                    <label for="department_name">Department Name</label>
                    <input type="text" class="form-control" id="department_name" name="department_name" placeholder="Enter new department name" required>
                  </div>
                  
                  <!-- Current Departments -->
                  <div class="department-list">
                    <h6>Current Departments:</h6>
                    <?php if (count($departments) > 0): ?>
                      <?php foreach($departments as $dept): ?>
                        <div class="department-item"><?php echo htmlspecialchars($dept['department_name']); ?></div>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <div class="department-item">No departments found</div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" id="saveDeptBtn" class="btn btn-success">Add Department</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading Indicator -->
        <div class="ajax-loader">
          <div class="loader-content">
            <div class="spinner"></div>
            <p>Processing...</p>
          </div>
        </div>
        
        <!-- Toast Container for notifications -->
        <div class="toast-container"></div>
      </section>
    </div>
    <?php include './footer.php'; ?>
    <!-- JS Dependencies -->
    <script src="./src/js/jquery.min.js"></script>
    <script src="./src/js/bootstrap.bundle.min.js"></script>
    <script src="./src/js/adminlte.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

    <script>
      $(document).ready(function() {
        console.log("Document ready");
        
        // Function to show toast messages
        function showToast(message, type = 'info') {
          var icon = 'fa-info-circle';
          if (type === 'success') icon = 'fa-check-circle';
          if (type === 'error') icon = 'fa-exclamation-circle';
          
          var toast = $('<div class="toast ' + type + '"><i class="fas ' + icon + '"></i>' + message + '</div>');
          $('.toast-container').append(toast);
          
          // Remove toast after animation completes
          setTimeout(function() {
            toast.remove();
          }, 4000);
        }
        
        // Initialize DataTable with export buttons
        try {
          $('#adminsTable, #managersTable, #teamLeadsTable, #usersTable').DataTable({
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            responsive: true
          });
          console.log("DataTables initialized");
        } catch (e) {
          console.error("DataTables error:", e);
        }

        // Employee search functionality
        $("#employeeSearch").on("keyup", function() {
          var value = $(this).val().toLowerCase();
          $("#employeeList .list-group-item").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
          });
          console.log("Search filtering applied:", value);
        });
        
        // Handle employee selection from list
        $(document).on("click", "#employeeList .list-group-item", function(e) {
          e.preventDefault();
          
          // Clear previous selection
          $("#employeeList .list-group-item").removeClass("active");
          
          // Mark selected
          $(this).addClass("active");
          
          // Get employee data
          var employeeId = $(this).data("id");
          var employeeName = $(this).text();
          var employeeRole = $(this).data("role");
          
          console.log("Employee selected:", employeeId, employeeName, employeeRole);
          
          // Set selected employee in form
          $("#selectedEmployee").val(employeeName);
          $("#selectedEmployeeId").val(employeeId);
          $("#new_role").val(employeeRole);
        });

        // Handle quick edit role button click
        $(document).on('click', '.edit-role-btn', function() {
          var id = $(this).data('id');
          var name = $(this).data('name');
          var role = $(this).data('role');
          
          console.log("Edit role button clicked:", id, name, role);
          
          // Clear previous selections
          $("#employeeList .list-group-item").removeClass("active");
          
          // Mark selected employee in list
          $("#employeeList .list-group-item[data-id='" + id + "']").addClass("active");
          
          // Populate form fields
          $("#selectedEmployee").val(name);
          $("#selectedEmployeeId").val(id);
          $("#new_role").val(role);
          
          // Show the modal
          $('#editRoleModal').modal('show');

             // Scroll to the selected employee in the list
             setTimeout(function() {
            var activeItem = $("#employeeList .list-group-item.active");
            if (activeItem.length) {
              var container = $(".employee-list");
              container.scrollTop(
                activeItem.offset().top - container.offset().top + container.scrollTop()
              );
            }
          }, 300);
        });
        
        // Handle role update submission
        $('#saveRoleBtn').on('click', function() {
          var employeeId = $('#selectedEmployeeId').val();
          var newRole = $('#new_role').val();
          
          if (!employeeId) {
            showToast('Please select an employee from the list.', 'error');
            return;
          }
          
          if (!newRole) {
            showToast('Please select a role.', 'error');
            return;
          }
          
          console.log("Updating role:", employeeId, newRole);
          
          // Show loading indicator
          $('.ajax-loader').css('display', 'flex');
          
          // Send AJAX request
          $.ajax({
            type: 'POST',
            url: window.location.href, // Send to the same page
            data: {
              update_role: true,
              employee_id: employeeId,
              new_role: newRole
            },
            dataType: 'json',
            success: function(response) {
              console.log("Role update response:", response);
              $('.ajax-loader').css('display', 'none');
              
              if (response.status === 'success') {
                // Show success message
                showToast('Role updated successfully!', 'success');
                
                // Close the modal
                $('#editRoleModal').modal('hide');
                
                // Reload the page to reflect changes
                setTimeout(function() {
                  location.reload();
                }, 1500);
              } else {
                // Show error message
                showToast('Error: ' + response.message, 'error');
              }
            },
            error: function(xhr, status, error) {
              console.error("Role update error:", xhr.responseText);
              $('.ajax-loader').css('display', 'none');
              showToast('An error occurred: ' + error, 'error');
            }
          });
        });
        
        // Handle department add submission
        $('#saveDeptBtn').on('click', function() {
          var departmentName = $('#department_name').val();
          
          if (!departmentName) {
            showToast('Please enter a department name.', 'error');
            return;
          }
          
          console.log("Adding department:", departmentName);
          
          // Show loading indicator
          $('.ajax-loader').css('display', 'flex');
          
          // Send AJAX request
          $.ajax({
            type: 'POST',
            url: window.location.href, // Send to the same page
            data: {
              add_department: true,
              department_name: departmentName
            },
            dataType: 'json',
            success: function(response) {
              console.log("Department add response:", response);
              $('.ajax-loader').css('display', 'none');
              
              if (response.status === 'success') {
                // Show success message
                showToast('Department added successfully!', 'success');
                
                // Add the new department to the list in the modal
                $('.department-list').append('<div class="department-item">' + departmentName + '</div>');
                
                // Add the new department to the dropdown in the Add Admin modal
                $('#manage_depart').append('<option value="' + response.department.id + '">' + departmentName + '</option>');
                
                // Clear the input
                $('#department_name').val('');
                
                // Close the modal
                $('#addDepartmentModal').modal('hide');
                
                // Reload the page after a short delay to refresh all department data
                setTimeout(function() {
                  location.reload();
                }, 1500);
              } else {
                // Show error message
                showToast('Error: ' + response.message, 'error');
              }
            },
            error: function(xhr, status, error) {
              console.error("Department add error:", xhr.responseText);
              $('.ajax-loader').css('display', 'none');
              showToast('An error occurred: ' + error, 'error');
            }
          });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
          $('.alert').fadeOut('slow');
        }, 5000);
        
        var logoutModal = document.getElementById('logoutModal');
          if (logoutModal) {
              new bootstrap.Modal(logoutModal);
              
              // Add manual trigger for logout modal
              document.querySelectorAll('[data-target="#logoutModal"]').forEach(function(element) {
                  element.addEventListener('click', function() {
                      var modal = new bootstrap.Modal(logoutModal);
                      modal.show();
                  });
              });
          }

        console.log("All event handlers bound");
      });
    </script>
  </div>
</body>
</html>