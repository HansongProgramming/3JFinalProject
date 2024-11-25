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
    <link rel="stylesheet" href="aesthetics.css">
</head>
<body>
    <div class="hero">    
        <h1>Providing Wellness</h1>
        <h2>One massage at a time</h2>
        <a href="login.php">Book Now</a>
        <a href="services.php">View Services</a>
    </div>

</body>
</html>
