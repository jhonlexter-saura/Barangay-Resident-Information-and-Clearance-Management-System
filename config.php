<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bricms_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("connection Failed". $conn->connect_error);
}else{
    echo ("connection Success");
}

?>