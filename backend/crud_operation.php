<?php
include 'connect.php';
function insertData($table_name, $post)
{
    // echo $post;
     global $conn;
    $key = array();
    $data = array();

    foreach ($post as $p => $value) {
        array_push($key, '`' . $p . '`');
        array_push($data, "'" . $value . "'");
    }

    $sql = "INSERT INTO  `$table_name` (" . implode(',', $key) . ")  VALUES (" . implode(',', $data) . ")";
    if (mysqli_query($conn, $sql)) {
        return "SUCCESS";
    } else {
        return "unseccesslly";
    }

}


// function updateAll($table_name, $post, $condition = 1)
// {
//     global $conn;  // he getting the global connection
//     $data = array(); //for storing the all data of the post request
//     foreach ($post as $p => $value) {
//         array_push($data, '' . $p . '=' . "'" . $value . "'");
//     }
//     $query = "UPDATE $table_name SET " . implode(',', $data) . "WHERE" . $condition;
//     if (mysqli_query($conn, $query)) {
//         return "success";
//     } else {
//         return $conn->error;
//     }
// }


?>