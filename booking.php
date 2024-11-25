<?php
require 'config.php'; // Database connection
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $service_id = $_POST['service_id'];
    $appointment_date = $_POST['appointment_date'];
    $start_time = $_POST['start_time'];

    $stmt = $conn->prepare("INSERT INTO appointments (user_id, service_id, appointment_date, start_time, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iiss", $user_id, $service_id, $appointment_date, $start_time);

    if ($stmt->execute()) {
        echo "Booking confirmed!";
    } else {
        echo "Error: " . $conn->error;
    }
}

$services = $conn->query("SELECT * FROM services");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Make a Booking</title>
</head>
<body>
    <h1>Book a Service</h1>
    <form method="POST">
        <select name="service_id" required>
            <option value="">Select a Service</option>
            <?php while ($row = $services->fetch_assoc()): ?>
                <option value="<?php echo $row['service_id']; ?>">
                    <?php echo $row['service_name']; ?>
                </option>
            <?php endwhile; ?>
        </select><br>
        <input type="date" name="appointment_date" required><br>
        <input type="time" name="start_time" required><br>
        <button type="submit">Confirm Booking</button>
    </form>
</body>
</html>
