<?php
include("conn.php");

$sql = "SELECT 
            UserID AS userid, 
            Username AS username, 
            Gender AS gender, 
            DOB AS dob, 
            Contact AS contact, 
            RoleID AS roleid,
            LastActive AS lastactive
        FROM user";

$result = $conn->query($sql);
$data = [];
if ($result) {
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }
}

echo json_encode($data);
?>