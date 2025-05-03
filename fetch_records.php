<?php
include 'backend/connect.php';

include 'delete_employee.php';
include 'update_employee.php';

$subject = '';
if (isset($_POST['subject'])) {
    $subject = $_POST['subject'];
} elseif (isset($_GET['subject'])) {
    $subject = $_GET['subject'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Employee List</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
</head>
<body>

<div class="container mt-4 ">
  <?php
  if (!empty($subject)) {
      $subject_safe = mysqli_real_escape_string($conn, $subject);
      $sql = "SELECT id, name, depart, contact, email, desig, joining_date, Image FROM employee WHERE depart = '$subject_safe'";
      $result = $conn->query($sql);

      echo "
      <div class='table-responsive p-4'>
      <h3>Manage Department: <strong>$subject</strong></h3>";

      if ($result->num_rows > 0) {
          echo "
          <table id='employeeTable' class='table table-bordered table-striped'>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Designation</th>
                    <th>Joining Date</th>
                    <th>Upload Document</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>";

          $modals = "";

          while ($row = $result->fetch_assoc()) {
              $id = $row['id'];
              $name = $row['name'];
              $depart = $row['depart'];
              $contact = $row['contact'];
              $email = $row['email'];
              $desig = $row['desig'];
              $joining_date = $row['joining_date'];
              $image = $row['Image'];

              echo "<tr>
                      <td>$id</td>
                      <td>$name</td>
                      <td>$depart</td>
                      <td>$contact</td>
                      <td>$email</td>
                      <td>$desig</td>
                      <td>$joining_date</td>
                      <td>".(!empty($image) ? "<img src='../backend/uploads/$image' width='80'>" : "No Image")."</td>
                      <td>
                        <button class='btn btn-primary btn-sm' data-bs-toggle='modal' data-bs-target='#editModal$id'></button>
                        <a href='delete_employee.php?id=$id' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure?\")'></a>
                      </td>
                    </tr>";

              $modals .= "
              <div class='modal fade' id='editModal$id' tabindex='-1' aria-labelledby='editModalLabel$id' aria-hidden='true'>
                <div class='modal-dialog modal-lg'>
                  <form action='update_employee.php' method='POST' enctype='multipart/form-data'>
                    <div class='modal-content'>
                      <div class='modal-header'>
                        <h5 class='modal-title' id='editModalLabel$id'>Edit Employee</h5>
                        <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                      </div>
                      <div class='modal-body'>
                        <input type='hidden' name='Id' value='$id'>
                        <div class='row'>
                          <div class='mb-3 col-md-6'>
                            <label class='form-label'>Name</label>
                            <input type='text' class='form-control' name='name' value='$name' required>
                          </div>
                          <div class='mb-3 col-md-6'>
                            <label class='form-label'>Department</label>
                            <input type='text' class='form-control' name='depart' value='$depart' required>
                          </div>
                          <div class='mb-3 col-md-6'>
                            <label class='form-label'>Contact</label>
                            <input type='text' class='form-control' name='contact' value='$contact'>
                          </div>
                          <div class='mb-3 col-md-6'>
                            <label class='form-label'>Email</label>
                            <input type='email' class='form-control' name='email' value='$email'>
                          </div>
                          <div class='mb-3 col-md-6'>
                            <label class='form-label'>Designation</label>
                            <input type='text' class='form-control' name='desig' value='$desig'>
                          </div>
                          <div class='mb-3 col-md-6'>
                            <label class='form-label'>Joining Date</label>
                            <input type='date' class='form-control' name='joining_date' value='$joining_date'>
                          </div>
                          <div class='mb-3 col-md-6'>
                            <label class='form-label'>Current Image</label><br>
                            ".(!empty($image) ? "<img src='backend/uploads/$image' width='80'>" : "No Image")."
                          </div>
                          <div class='mb-3 col-md-6'>
                            <label class='form-label'>Upload New Image</label>
                            <input type='file' class='form-control' name='Image'>
                          </div>
                        </div>
                      </div>
                      <div class='modal-footer'>
                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                        <button type='submit' name='Update' class='btn btn-primary'>Save Changes</button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>";
          }

          echo "</tbody></table></div>";
          echo $modals;
      } else {
          echo "<p>No records found for <strong>$subject</strong>.</p>";
      }
  }
  ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<!-- File Export Dependencies -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<!-- DataTables Initialization -->
<script>
  $(document).ready(function () {
    $('#employeeTable').DataTable({
      dom: 'Bfrtip',
      buttons: [
        {
          extend: 'excelHtml5',
          title: 'Employee_List'
        },
        {
          extend: 'csvHtml5',
          title: 'Employee_List'
        },
        {
          extend: 'pdfHtml5',
          orientation: 'landscape',
          pageSize: 'A4',
          title: 'Employee_List'
        },
        {
          extend: 'print',
          title: 'Employee_List'
        }
      ]
    });
  });
</script>

</body>
</html>
