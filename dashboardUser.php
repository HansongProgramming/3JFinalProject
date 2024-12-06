<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'config.php';

$user_id = $_SESSION['user_id'];

$upcoming_stmt = $conn->prepare("SELECT a.appointment_date, a.start_time, s.service_name, u.full_name as therapist_name 
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u ON a.therapist_id = u.user_id
    WHERE a.user_id = ? AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date, a.start_time");
$upcoming_stmt->bind_param("i", $user_id);
$upcoming_stmt->execute();
$upcoming_appointments = $upcoming_stmt->get_result();

$past_stmt = $conn->prepare("SELECT a.appointment_date, a.start_time, s.service_name, u.full_name as therapist_name 
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u ON a.therapist_id = u.user_id
    WHERE a.user_id = ? AND a.appointment_date < CURDATE()
    ORDER BY a.appointment_date DESC, a.start_time DESC");
$past_stmt->bind_param("i", $user_id);
$past_stmt->execute();
$past_appointments = $past_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="aesthetics.css">
</head>
<body>
    <h1>Welcome, <?php echo $_SESSION['user_name']; ?></h1>
    <a href="services.php">View Services</a>
    <a href="booking.php">Make a Booking</a>
    <a href="logout.php">Logout</a>

    <h2>Upcoming Appointments</h2>
    <div class="cards">
        <?php if ($upcoming_appointments->num_rows > 0): ?>
            <?php while ($row = $upcoming_appointments->fetch_assoc()): ?>
                <div class="card">
                    <h3><?php echo $row['service_name']; ?></h3>
                    <p><strong>Date:</strong> <?php echo $row['appointment_date']; ?></p>
                    <p><strong>Time:</strong> <?php echo $row['start_time']; ?></p>
                    <p><strong>Therapist:</strong> <?php echo $row['therapist_name']; ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No upcoming appointments.</p>
        <?php endif; ?>
    </div>

    <h2>Past Appointments</h2>
    <div class="cards">
        <?php if ($past_appointments->num_rows > 0): ?>
            <?php while ($row = $past_appointments->fetch_assoc()): ?>
                <div class="card">
                    <h3><?php echo $row['service_name']; ?></h3>
                    <p><strong>Date:</strong> <?php echo $row['appointment_date']; ?></p>
                    <p><strong>Time:</strong> <?php echo $row['start_time']; ?></p>
                    <p><strong>Therapist:</strong> <?php echo $row['therapist_name']; ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No past appointments.</p>
        <?php endif; ?>
    </div>
</body>
</html>
