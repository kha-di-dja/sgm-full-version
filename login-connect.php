<?php
    $host = "localhost";
    $user = "root";
    $password = "";
    $database = "sgm";

    $conn = new mysqli($host, $user, $password, $database,3306);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    echo "";
?>