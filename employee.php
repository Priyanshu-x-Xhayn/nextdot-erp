<?php
include './backend/connect.php';
include 'auth_check.php';
include './update_employee.php';
include './delete_employee.php';

// Ensure the uploads directory exists
$targetDir = "./backend/uploads/";
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$selected_department = "";
$selected_designation = "";
$selected_role = "";

// Fetch last assigned department, designation, and role from database
$sql_fetch = "SELECT e.depart, d.department_name, e.desig, e.role 
              FROM employee e
              LEFT JOIN departments d ON e.depart = d.id 
              ORDER BY e.id DESC LIMIT 1"; 
$result = $conn->query($sql_fetch);

if ($result && $result->num_rows > 0) { 
    $row = $result->fetch_assoc();
    $selected_department = $row['depart'] ?? "";
    $selected_designation = $row['desig'] ?? "";
    $selected_role = $row['role'] ?? "User"; // Default to User if not set
}

// Fetch all departments from the departments table
$departments_query = "SELECT * FROM departments ORDER BY department_name";
$departments_result = $conn->query($departments_query);
$departments = [];
if ($departments_result && $departments_result->num_rows > 0) {
    while ($dept = $departments_result->fetch_assoc()) {
        $departments[] = $dept;
    }
}

// Fetch the employee records from the database
$employee_query = "SELECT e.*, d.department_name 
                  FROM employee e
                  LEFT JOIN departments d ON e.depart = d.id
                  ORDER BY e.id DESC";
$result = $conn->query($employee_query); 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $joining_date = $_POST['joining_date'];
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password for security
    $depart = $_POST['department']; // Now this will be the department ID
    $desig = $_POST['designation'];
    $role = $_POST['role'];
    $fileName = '';

    

    // Check if the file input is set and not empty
    if (isset($_FILES["Image"]) && $_FILES["Image"]["error"] == 0) {
        $targetDir = "./backend/uploads/";
        $fileName = basename($_FILES["Image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        // Allow certain file formats
        $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'pdf');
        if (in_array($fileType, $allowTypes)) {
            // Upload file to server
            if (!move_uploaded_file($_FILES["Image"]["tmp_name"], $targetFilePath)) {
                echo "Error uploading your file.";
            }
        }
    }
  
  
    // Check if username already exists
    $check_username = $conn->prepare("SELECT id FROM employee WHERE username = ?");
    $check_username->bind_param("s", $username);
    $check_username->execute();
    $username_result = $check_username->get_result();
    
    if ($username_result->num_rows > 0) {
        echo "<script>alert('Username already exists. Please choose a different username.');</script>";
    } else {
        // Insert employee data including authentication fields
        $sql = "INSERT INTO employee (joining_date, name, contact, email, username, password, depart, desig, role, Image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssss", $joining_date, $name, $contact, $email, $username, $password, $depart, $desig, $role, $fileName);
        
        if ($stmt->execute()) {
            // Redirect to avoid form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
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
</head>

<body class="hold-transition sidebar-mini">
  <div class="wrapper">
    <?php include './header.php'; ?>
    <div class="content-wrapper">
      <section class="content">
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#adminModal">Add Employee</button>

        <!-- Modal -->
        <div class="modal fade" id="adminModal" tabindex="-1" role="dialog" aria-labelledby="adminModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="adminModalLabel">Add Employee</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">×</span>
                </button>
              </div>
              <div class="modal-body">
                <form action="employee.php" method="POST" enctype="multipart/form-data">
                  <div class="row">
                    <div class="form-group col-md-6">
                      <label for="joining_date">Date Of Joining</label>
                      <input type="date" class="form-control" id="joining_date" name="joining_date" placeholder="Date">
                    </div>
                    <div class="form-group col-md-6">
                      <label for="name">Name Of Employee</label>
                      <input type="text" class="form-control" id="name" name="name" placeholder="Name" required>
                    </div>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                      <label for="contact">Contact No</label>
                      <input type="contact" class="form-control" id="contact" name="contact" placeholder="Contact No" required>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="email">Email Id</label>
                      <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                    </div>
                  </div>
                  
                  <!-- New Authentication Fields -->
                  <div class="row">
                    <div class="form-group col-md-6">
                      <label for="username">Username</label>
                      <input type="text" class="form-control" id="username" name="username" placeholder="Username for login" required>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="password">Password</label>
                      <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    </div>
                  </div>
                  
                  <div class="row">
                    <div class="form-group col-md-6">
                      <label for="department">Assigned Department</label>
                      <select name="department" id="department" class="form-control" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= ($selected_department == $dept['id']) ? "selected" : ""; ?>>
                          <?= htmlspecialchars($dept['department_name']) ?>
                        </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="designation">Designation</label>
                      <select name="designation" id="designation" class="form-control">
                        <option value="">Designation</option>
                        <option value="Team Leader" <?= ($selected_designation == "Team Leader") ? "selected" : ""; ?>>Team Leader</option>
                        <option value="Team Member" <?= ($selected_designation == "Team Member") ? "selected" : ""; ?>>Team Member</option>
                      </select>
                    </div>
                  </div>
                  
                  <div class="row">
                    <div class="form-group col-md-6">
                      <label for="role">System Role</label>
                      <select name="role" id="role" class="form-control" required>
                        <option value="">Select Role</option>
                        <option value="Admin" <?= ($selected_role == "Admin") ? "selected" : ""; ?>>Admin</option>
                        <option value="Manager" <?= ($selected_role == "Manager") ? "selected" : ""; ?>>Manager</option>
                        <option value="Team Lead" <?= ($selected_role == "Team Lead") ? "selected" : ""; ?>>Team Lead</option>
                        <option value="User" <?= ($selected_role == "User") ? "selected" : ""; ?>>User</option>
                      </select>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="document">Document</label>
                      <input type="file" class="form-control" name="Image">
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

        <div class="table-responsive">
          <table class="table table-bordered" id="myTable">
            <thead>
              <tr>
                <th class="">SL.No</th>
                <th>Date of Joining</th>
                <th>Name Of Employee</th>
                <th>Contact No</th>
                <th>Email Id</th>
                <th>Username</th>
                <th>Assigned Department</th>
                <th>Designation</th>
                <th>Role</th>
                <th>Document</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                  <td><?php echo $row['id']; ?></td>
                  <td><?php echo $row['joining_date']; ?></td>
                  <td><?php echo $row['name']; ?></td>
                  <td><?php echo $row['contact']; ?></td>
                  <td><?php echo $row['email']; ?></td>
                  <td><?php echo $row['username'] ?? 'N/A'; ?></td>
                  <td><?php echo $row['department_name'] ?? 'Not Assigned'; ?></td>
                  <td><?php echo $row['desig']; ?></td>
                  <td>
                    <span class="badge badge-<?php
                      switch($row['role'] ?? 'User') {
                        case 'Admin': echo 'danger'; break;
                        case 'Manager': echo 'warning'; break;
                        case 'Team Lead': echo 'primary'; break;
                        default: echo 'secondary';
                      }
                    ?>"><?php echo $row['role'] ?? 'User'; ?></span>
                  </td>
                  <td>
                    <?php if (!empty($row['Image'])) { ?>
                      <a href="./backend/uploads/<?php echo htmlspecialchars($row['Image'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                        <img style="width:25px; height:25px;" src="./backend/uploads/download.png" alt="Download">
                      </a>
                    <?php } else { ?>
                      Pending
                    <?php } ?>
                  </td>
                  <td>
                    <span class='label label-primary'><?php echo $row['Image'] ? 'Uploaded' : 'Pending'; ?></span>
                  </td>
                  <td>
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#editModal<?php echo $row['id']; ?>">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <form action="delete_employee.php" method="POST" style="display:inline;">
                      <input type="hidden" name="Id" value="<?php echo $row['id']; ?>">
                      <button type="submit" name="delete" class="btn btn-danger btn-sm">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                  <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Edit Employee</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">×</span>
                        </button>
                      </div>
                      <div class="modal-body">
                        <form action="update_employee.php" method="POST" enctype="multipart/form-data">
                          <input type="hidden" name="Id" value="<?php echo $row['id']; ?>">
                          <div class="row">
                            <div class="col-md-6">
                              <div class="form-group">
                                <label>Date of Joining</label>
                                <input type="date" class="form-control" name="joining_date" value="<?php echo $row['joining_date']; ?>" required>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-group">
                                <label>Name Of Employee</label>
                                <input type="text" class="form-control" name="name" value="<?php echo $row['name']; ?>" required>
                              </div>
                            </div>
                          </div>

                          <div class="row">
                            <div class="col-md-6">
                              <div class="form-group">
                                <label>Email ID</label>
                                <input type="text" class="form-control" name="email" value="<?php echo $row['email']; ?>" required>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-group">
                                <label>Contact No</label>
                                <input type="text" class="form-control" name="contact" value="<?php echo $row['contact']; ?>" required>
                              </div>
                            </div>
                          </div>
                          
                          <!-- Username and Password Edit -->
                          <div class="row">
                            <div class="col-md-6">
                              <div class="form-group">
                                <label>Username</label>
                                <input type="text" class="form-control" name="username" value="<?php echo $row['username'] ?? ''; ?>" required>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-group">
                                <label>Password</label>
                                <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password">
                                <small class="form-text text-muted">Only enter a new password if you want to change it.</small>
                              </div>
                            </div>
                          </div>

                          <div class="row">
                            <div class="col-md-6">
                              <div class="form-group">
                                <label>Assigned Department</label>
                                <select class="form-control" name="depart" required>
                                  <option value="">Select Department</option>
                                  <?php foreach ($departments as $dept): ?>
                                  <option value="<?= $dept['id'] ?>" <?= ($row['depart'] == $dept['id']) ? "selected" : ""; ?>>
                                    <?= htmlspecialchars($dept['department_name']) ?>
                                  </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-group">
                                <label>Designation</label>
                                <select class="form-control" name="desig" required>
                                  <option value="">Select Designation</option>
                                  <option value="Team Leader" <?= ($row['desig'] == "Team Leader") ? "selected" : ""; ?>>Team Leader</option>
                                  <option value="Team Member" <?= ($row['desig'] == "Team Member") ? "selected" : ""; ?>>Team Member</option>
                                </select>
                              </div>
                            </div>
                          </div>
                          
                          <div class="row">
                            <div class="col-md-6">
                              <div class="form-group">
                                <label>System Role</label>
                                <select class="form-control" name="role" required>
                                  <option value="">Select Role</option>
                                  <option value="Admin" <?= ($row['role'] == "Admin") ? "selected" : ""; ?>>Admin</option>
                                  <option value="Manager" <?= ($row['role'] == "Manager") ? "selected" : ""; ?>>Manager</option>
                                  <option value="Team Lead" <?= ($row['role'] == "Team Lead") ? "selected" : ""; ?>>Team Lead</option>
                                  <option value="User" <?= ($row['role'] == "User" || empty($row['role'])) ? "selected" : ""; ?>>User</option>
                                </select>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-group">
                                <label>Upload Document</label>
                                <input type="file" name="Image" id="fileToUpload" accept="application/pdf" class="form-control">
                              </div>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="Update" class="btn btn-primary">Update</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              <?php } ?>
            </tbody>
          </table>
        </div>
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
        // Initialize DataTable with export buttons
        const table = $('#myTable').DataTable({
          dom: 'Bfrtip',
          buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
        });
      });
    </script>
  </div>
</body>
</html>