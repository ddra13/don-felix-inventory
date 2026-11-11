<?php
$conn = new mysqli("localhost", "root", "", "don_felix_inventory");
if ($conn->connect_error) { die("Database Connection Failed: " . $conn->connect_error); }
?>