<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<link rel="stylesheet" href="aesthetics.css">

<h1>Welcome, <?php echo $_SESSION['user_name']; ?></h1>
<a href="services.php">View Services</a>
<a href="booking.php">Make a Booking</a>
<a href="logout.php">Logout</a>
