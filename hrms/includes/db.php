<?php
$conn = new mysqli("localhost", "root", "", "hrms_intern");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>