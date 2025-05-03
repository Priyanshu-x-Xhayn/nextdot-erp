<?php
include('connect.php');

if (isset($_POST["submit"])) {
  $entry_date = $_POST['entry_date'];
  $name = $_POST['name'];
  $Email = $_POST['Email'];
  $contact = $_POST['contact'];
  $desig = $_POST['desig'];
  $sql = "INSERT INTO raw_material(entry_date,name,Email,contact,desig) 
            VALUES ('$entry_date','$name','$Email', '$contact','$desig')";

  $conn->query($sql);
}