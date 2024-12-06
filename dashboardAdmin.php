<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require 'config.php';

// Fetch therapists
$therapists_stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE role = 'therapist'");
$therapists_stmt->execute();
$therapists = $therapists_stmt->get_result();

// Handle form submission to assign schedules
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $therapist_id = $_POST['therapist_id'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Insert schedule into availability
    $stmt = $conn->prepare("INSERT INTO availability (therapist_id, date, start_time, end_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $therapist_id, $date, $start_time, $end_time);

    if ($stmt->execute()) {
        $success_message = "Schedule assigned successfully!";
    } else {
        $error_message = "Error assigning schedule: " . $stmt->error;
    }
}

// Fetch all schedules for calendar view
$schedules_stmt = $conn->prepare("
    SELECT a.date, a.start_time, a.end_time, u.full_name AS therapist_name
    FROM availability a
    JOIN users u ON a.therapist_id = u.user_id
    ORDER BY a.date, a.start_time
");
$schedules_stmt->execute();
$schedules = $schedules_stmt->get_result();

// Group schedules by date for calendar display
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
    <style>
        /* Add some basic styling for the sidebar */
        .sidebar {
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #333;
            color: #fff;
            padding: 15px;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 10px;
            margin: 5px 0;
            background-color: #444;
            border-radius: 5px;
        }

        .sidebar a:hover {
            background-color: #555;
        }

        .sidebar form {
            margin-top: 20px;
        }

        .sidebar label {
            color: #fff;
        }

        .sidebar select, .sidebar input, .sidebar button {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .sidebar button {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
        }

        .sidebar button:hover {
            background-color: #45a049;
        }

        /* Main content */
        .content {
            margin-left: 270px;
            padding: 20px;
        }

        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-top: 20px;
        }

        .calendar-day {
            padding: 10px;
            border: 1px solid #ddd;
            height: 100px;
            position: relative;
        }

        .calendar-day-header {
            font-weight: bold;
            text-align: center;
        }

        .schedule {
            position: absolute;
            bottom: 5px;
            left: 5px;
            font-size: 12px;
            color: #007bff;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
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

    <!-- Main Content -->
    <div class="content">
        <h1>Assigned Schedules (Calendar View)</h1>

        <div class="calendar">
            <?php
            // Generate calendar for the current month
            $current_date = new DateTime();
            $current_date->modify('first day of this month');
            $start_date = $current_date->format('Y-m-d');
            $current_date->modify('last day of this month');
            $end_date = $current_date->format('Y-m-d');

            // Display days of the week
            $days_of_week = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            foreach ($days_of_week as $day) {
                echo "<div class='calendar-day'><div class='calendar-day-header'>$day</div></div>";
            }

            // Get the dates for the current month
            $start_timestamp = strtotime($start_date);
            $end_timestamp = strtotime($end_date);

            for ($date = $start_timestamp; $date <= $end_timestamp; $date = strtotime("+1 day", $date)) {
                $current_day = date('Y-m-d', $date);
                echo "<div class='calendar-day'>";
                echo "<div class='calendar-day-header'>" . date('d', $date) . "</div>";

                // Display schedules for the current day
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

</body>
</html>
