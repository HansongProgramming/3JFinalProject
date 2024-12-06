<?php
require 'config.php';
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
    $therapist_id = $_POST['therapist_id'];

    // Validate therapist availability
    $availability_query = $conn->prepare("
        SELECT * 
        FROM availability 
        WHERE therapist_id = ? 
          AND date = ? 
          AND start_time <= ? 
          AND end_time > ? 
          AND NOT EXISTS (
              SELECT 1 
              FROM appointments 
              WHERE therapist_id = availability.therapist_id 
                AND appointment_date = availability.date 
                AND start_time = ?
          )
    ");
    $availability_query->bind_param("issss", $therapist_id, $appointment_date, $start_time, $start_time, $start_time);
    $availability_query->execute();
    $availability_result = $availability_query->get_result();

    if ($availability_result->num_rows > 0) {
        // Therapist is available, insert the booking
        $stmt = $conn->prepare("INSERT INTO appointments (user_id, service_id, appointment_date, start_time, therapist_id, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iissi", $user_id, $service_id, $appointment_date, $start_time, $therapist_id);

        if ($stmt->execute()) {
            $success_message = "Booking confirmed!";
        } else {
            $error_message = "Error: " . $conn->error;
        }
    } else {
        $error_message = "The selected therapist is not available at this time.";
    }
}

$services = $conn->query("SELECT * FROM services");
$therapists = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'therapist'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Make a Booking</title>
    <link rel="stylesheet" href="aesthetics.css">
</head>
<body>
    <h1>Book a Service</h1>

    <?php
    if (isset($success_message)) {
        echo "<p style='color: green;'>$success_message</p>";
    }
    if (isset($error_message)) {
        echo "<p style='color: red;'>$error_message</p>";
    }
    ?>

    <form method="POST">
        <label for="service_id">Select a Service:</label>
        <select name="service_id" required>
            <option value="">-- Select a Service --</option>
            <?php while ($row = $services->fetch_assoc()): ?>
                <option value="<?php echo $row['service_id']; ?>">
                    <?php echo htmlspecialchars($row['service_name']); ?>
                </option>
            <?php endwhile; ?>
        </select><br>

        <label for="therapist_id">Select a Therapist:</label>
        <select name="therapist_id" required>
            <option value="">-- Select a Therapist --</option>
            <?php while ($row = $therapists->fetch_assoc()): ?>
                <option value="<?php echo $row['user_id']; ?>">
                    <?php echo htmlspecialchars($row['full_name']); ?>
                </option>
            <?php endwhile; ?>
        </select><br>

        <label for="appointment_date">Date:</label>
        <input type="date" name="appointment_date" required><br>

        <label for="start_time">Start Time:</label>
        <input type="time" name="start_time" required><br>

        <button type="submit">Confirm Booking</button>
    </form>
</body>
</html>
