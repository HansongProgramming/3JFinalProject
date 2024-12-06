<?php
session_start();
// if (isset($_SESSION['user_id'])) {
//     header("Location: dashboardUser.php");
//     exit();
// }
?>
<!DOCTYPE html>
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
        <a href="login.php" class="btn">Book Now</a>
        <a href="services.php" class="btn">View Services</a>
        
        <!-- Video Section -->
        <div class="video-container">
            <iframe width="560" height="315" src="https://www.youtube.com/embed/your-video-id" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
    </div>
</body>
</html>
