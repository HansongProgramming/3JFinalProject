<?php
require 'config.php'; // Database connection
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$result = $conn->query("SELECT * FROM services");
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Services</title>
    <link rel="stylesheet" href="aesthetics.css">
</head>
<body>
    <h1>Available Services</h1>
    <?php if ($result->num_rows > 0): ?>
        <ul>
            <?php while ($row = $result->fetch_assoc()): ?>
                <li>
                    <strong><?php echo $row['service_name']; ?></strong><br>
                    <?php echo $row['description']; ?><br>
                    Duration: <?php echo $row['duration']; ?> minutes<br>
                    Price: $<?php echo $row['price']; ?><br>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No services available.</p>
    <?php endif; ?>
</body>
</html>
