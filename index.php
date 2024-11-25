<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking System</title>
</head>
<body>
    <h1>Welcome to Our Booking System</h1>
    <a href="login.php">Login</a>
    <a href="registration.php">Register</a> 
    <a href="services.php">View Services</a>
</body>
</html>
