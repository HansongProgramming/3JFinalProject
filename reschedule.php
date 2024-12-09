<?php
require 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $appointment_id = $_POST['appointment_id'];
    $new_date = $_POST['appointment_date'];
    $new_start_time = $_POST['start_time'];

    // Validate if the appointment exists and belongs to the current user
    $check_query = $conn->prepare("
        SELECT * 
        FROM appointments 
        WHERE appointment_id = ? AND user_id = ?
    ");
    $check_query->bind_param("ii", $appointment_id, $user_id);
    $check_query->execute();
    $result = $check_query->get_result();

    if ($result->num_rows > 0) {
        $appointment = $result->fetch_assoc();
        $therapist_id = $appointment['therapist_id'];

        // Check therapist's availability
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
        $availability_query->bind_param("issss", $therapist_id, $new_date, $new_start_time, $new_start_time, $new_start_time);
        $availability_query->execute();
        $availability_result = $availability_query->get_result();

        if ($availability_result->num_rows > 0) {
            // Therapist is available, update the appointment
            $update_query = $conn->prepare("
                UPDATE appointments 
                SET appointment_date = ?, start_time = ?, updated_at = NOW() 
                WHERE appointment_id = ?
            ");
            $update_query->bind_param("ssi", $new_date, $new_start_time, $appointment_id);

            if ($update_query->execute()) {
                $success_message = "Appointment rescheduled successfully!";
            } else {
                $error_message = "Error updating appointment: " . $conn->error;
            }
        } else {
            $error_message = "The selected therapist is not available at this time.";
        }
    } else {
        $error_message = "Appointment not found or does not belong to you.";
    }
}

// Fetch existing appointment for the form
if (isset($_GET['appointment_id'])) {
    $appointment_id = $_GET['appointment_id'];

    $appointment_query = $conn->prepare("
        SELECT a.*, s.service_name, u.full_name as therapist_name 
        FROM appointments a
        JOIN services s ON a.service_id = s.service_id
        JOIN users u ON a.therapist_id = u.user_id
        WHERE a.appointment_id = ? AND a.user_id = ?
    ");
    $appointment_query->bind_param("ii", $appointment_id, $_SESSION['user_id']);
    $appointment_query->execute();
    $appointment_result = $appointment_query->get_result();

    if ($appointment_result->num_rows > 0) {
        $appointment = $appointment_result->fetch_assoc();
    } else {
        die("Appointment not found or does not belong to you.");
    }
} else {
    die("No appointment selected for rescheduling.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reschedule Appointment</title>
    <link rel="stylesheet" href="aesthetics.css">
</head>
<body>
    <div class="bookingContainer">
        <div class="book">
        <h1>Reschedule Appointment</h1>

            <?php
            if (isset($success_message)) {
                echo "<p style='color: green;'>$success_message</p>";
            }
            if (isset($error_message)) {
                echo "<p style='color: red;'>$error_message</p>";
            }
            ?>

            <form method="POST">
                <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">

                <p><strong>Service:</strong> <?php echo htmlspecialchars($appointment['service_name']); ?></p>
                <p><strong>Therapist:</strong> <?php echo htmlspecialchars($appointment['therapist_name']); ?></p>

                <label for="appointment_date">New Date:</label><br>
                <input type="date" name="appointment_date" value="<?php echo $appointment['appointment_date']; ?>" required><br>

                <label for="start_time">New Start Time:</label><br>
                <input type="time" name="start_time" value="<?php echo $appointment['start_time']; ?>" required><br>

                <button type="submit">Confirm Reschedule</button>
                <br>
                <br> 
                <div class="circ"><a href="dashboardUser.php"><</a>
                </div>
            </form>
        </div>
    </div>


</body>
</html>
