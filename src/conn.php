<?php
// Database connection
$dbConn = mysqli_connect("localhost", "root", "", "rwdd_assignment");

if (!$dbConn) {
        die('<script>alert("failed");</script>');
    }
?>