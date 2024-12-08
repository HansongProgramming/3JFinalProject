<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require 'config.php';

$therapists_stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE role = 'therapist'");
$therapists_stmt->execute();
$therapists = $therapists_stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $therapist_id = $_POST['therapist_id'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $stmt = $conn->prepare("INSERT INTO availability (therapist_id, date, start_time, end_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $therapist_id, $date, $start_time, $end_time);

    if ($stmt->execute()) {
        $success_message = "Schedule assigned successfully!";
    } else {
        $error_message = "Error assigning schedule: " . $stmt->error;
    }
}

$schedules_stmt = $conn->prepare("
    SELECT a.date, a.start_time, a.end_time, u.full_name AS therapist_name
    FROM availability a
    JOIN users u ON a.therapist_id = u.user_id
    ORDER BY a.date, a.start_time
");
$schedules_stmt->execute();
$schedules = $schedules_stmt->get_result();

$schedule_data = [];
while ($schedule = $schedules->fetch_assoc()) {
    $schedule_data[$schedule['date']][] = $schedule;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="aesthetics.css">
</head>
<body>
    <div class="dashboardContainer">
    <div class="sidebar">
        <h2>Admin Dashboard</h2>
        <a href="dashboardAdmin.php">Assign Schedule</a>
        <a href="logout.php">Logout</a>

        <h3>Assign Schedule</h3>
        <?php
        if (isset($success_message)) echo "<p style='color: green;'>$success_message</p>";
        if (isset($error_message)) echo "<p style='color: red;'>$error_message</p>";
        ?>

        <form method="POST">
            <label for="therapist_id">Therapist:</label>
            <select name="therapist_id" required>
                <option value="">-- Select Therapist --</option>
                <?php while ($therapist = $therapists->fetch_assoc()): ?>
                    <option value="<?php echo $therapist['user_id']; ?>">
                        <?php echo htmlspecialchars($therapist['full_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select><br>

            <label for="date">Date:</label>
            <input type="date" name="date" required><br>

            <label for="start_time">Start Time:</label>
            <input type="time" name="start_time" required><br>

            <label for="end_time">End Time:</label>
            <input type="time" name="end_time" required><br>

            <button type="submit">Assign Schedule</button>
        </form>
    </div>


        <div class="content">
            <div id="date-time"></div>

            <div class="calendar">
                <?php
                $current_date = new DateTime();
                $current_date->modify('first day of this month');
                $start_date = $current_date->format('Y-m-d');
                $current_date->modify('last day of this month');
                $end_date = $current_date->format('Y-m-d');

                $days_of_week = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                foreach ($days_of_week as $day) {
                    echo "<div class='calendar-day'><div class='calendar-day-header'>$day</div></div>";
                }

                $start_timestamp = strtotime($start_date);
                $end_timestamp = strtotime($end_date);

                for ($date = $start_timestamp; $date <= $end_timestamp; $date = strtotime("+1 day", $date)) {
                    $current_day = date('Y-m-d', $date);
                    echo "<div class='calendar-day'>";
                    echo "<div class='calendar-day-header'>" . date('d', $date) . "</div>";

                    if (isset($schedule_data[$current_day])) {
                        foreach ($schedule_data[$current_day] as $schedule) {
                            echo "<div class='schedule'>{$schedule['therapist_name']} - {$schedule['start_time']} to {$schedule['end_time']}</div>";
                        }
                    }

                    echo "</div>";
                }
                ?>
            </div>
        </div>
        <script>
            function updateDateTime() {
                const dateTimeElement = document.getElementById('date-time');
                const now = new Date();
                const date = now.toLocaleDateString(undefined, {
                    year: 'numeric',
                    month: 'long',
                });
                const time = now.toLocaleTimeString(undefined, {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                });
                dateTimeElement.textContent = `${date}, ${time}`;
            }
            setInterval(updateDateTime, 1000);
            updateDateTime();
        </script>

        
    </div>


</body>
</html>
