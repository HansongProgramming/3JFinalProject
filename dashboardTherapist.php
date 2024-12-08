<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'config.php';

$therapist_name = $_SESSION['user_name']; // Get the therapist's name from the session

// Query for upcoming appointments, filter based on therapist's name
$upcoming_stmt = $conn->prepare("SELECT a.appointment_date, a.start_time, s.service_name, 
    u.full_name as therapist_name, c.full_name as client_name
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u ON a.therapist_id = u.user_id
    JOIN users c ON a.user_id = c.user_id
    WHERE u.full_name = ? AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date, a.start_time");
$upcoming_stmt->bind_param("s", $therapist_name); // Binding therapist's name as a string parameter
$upcoming_stmt->execute();
$upcoming_appointments = $upcoming_stmt->get_result();

// Query for past appointments, filter based on therapist's name
$past_stmt = $conn->prepare("SELECT a.appointment_date, a.start_time, s.service_name, 
    u.full_name as therapist_name, c.full_name as client_name
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u ON a.therapist_id = u.user_id
    JOIN users c ON a.user_id = c.user_id
    WHERE u.full_name = ? AND a.appointment_date < CURDATE()
    ORDER BY a.appointment_date DESC, a.start_time DESC");
$past_stmt->bind_param("s", $therapist_name); // Binding therapist's name as a string parameter
$past_stmt->execute();
$past_appointments = $past_stmt->get_result();

// Query to get the therapist's availability
$availability_stmt = $conn->prepare("SELECT date,start_time, end_time FROM availability 
    WHERE therapist_id = (SELECT user_id FROM users WHERE full_name = ?)");
$availability_stmt->bind_param("s", $therapist_name);
$availability_stmt->execute();
$availability = $availability_stmt->get_result();

// Query to get the therapist's reviews
$reviews_stmt = $conn->prepare("SELECT r.rating, r.comment, r.created_at, a.appointment_date, u.full_name as client_name 
    FROM reviews r
    JOIN appointments a ON r.appointment_id = a.appointment_id
    JOIN users u ON r.user_id = u.user_id
    WHERE a.therapist_id = (SELECT user_id FROM users WHERE full_name = ?)
    ORDER BY r.created_at DESC");
$reviews_stmt->bind_param("s", $therapist_name);
$reviews_stmt->execute();
$reviews = $reviews_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="aesthetics.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.2/main.min.js"></script>
</head>
<body>
    <div class="dashboardContainer">
        <div class="sidebar">
        <h1>Welcome, <span class="highlight"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span></h1>
            <div id="calendar"></div> <br>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var calendarEl = document.getElementById('calendar');
                    
                    var calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth' 
                    });

                    calendar.render();
                });
            </script>
            <a href="logout.php">Logout</a> 
        </div>

        <div class="appointments">
        <h2><span class="highlight">Upcoming</span> Appointments</h2>
        <div class="cards">
            <?php if ($upcoming_appointments->num_rows > 0): ?>
                <?php while ($row = $upcoming_appointments->fetch_assoc()): ?>
                    <div class="card">
                        <h3><?php echo $row['service_name']; ?></h3>
                        <p><strong>Date:</strong> <?php echo $row['appointment_date']; ?></p>
                        <p><strong>Time:</strong> <?php echo $row['start_time']; ?></p>
                        <p><strong>Therapist:</strong> <?php echo $row['therapist_name']; ?></p>
                        <p><strong>Client:</strong> <?php echo $row['client_name']; ?></p> <!-- Client Name -->
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No upcoming appointments.</p>
            <?php endif; ?>
        </div>

        <h2><span class="highlight">Past</span> Appointments</h2>
        <div class="cards">
            <?php if ($past_appointments->num_rows > 0): ?>
                <?php while ($row = $past_appointments->fetch_assoc()): ?>
                    <div class="card">
                        <h3><?php echo $row['service_name']; ?></h3>
                        <p><strong>Date:</strong> <?php echo $row['appointment_date']; ?></p>
                        <p><strong>Time:</strong> <?php echo $row['start_time']; ?></p>
                        <p><strong>Therapist:</strong> <?php echo $row['therapist_name']; ?></p>
                        <p><strong>Client:</strong> <?php echo $row['client_name']; ?></p> <!-- Client Name -->
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No past appointments.</p>
            <?php endif; ?>
        </div>
        </div>

        <div class="filler">
            <div id="date-time"></div>
            <h2><span class="highlight"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>'s Schedule</h2>
            <div class="availability">
                <div class="cards">
                    <?php if ($availability->num_rows > 0): ?>
                        <?php while ($row = $availability->fetch_assoc()): ?>
                            <div class="card">
                                <p><strong>Date: </strong> <?php echo $row['date']; ?></p>
                                <p><strong>From: <Span class="highlight"><?php echo $row['start_time']; ?> - <?php echo $row['end_time']; ?></Span></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No availability listed.</p>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Display the therapist's reviews and comments -->
            <div class="reviews">
                <h2>Reviews and Comments</h2>
                <div class="cards">
                    <?php if ($reviews->num_rows > 0): ?>
                        <?php while ($row = $reviews->fetch_assoc()): ?>
                            <div class="card">
                                <p><strong>Client:</strong> <?php echo htmlspecialchars($row['client_name']); ?></p>
                                <p><strong>Date:</strong> <?php echo htmlspecialchars($row['appointment_date']); ?></p>
                                <p><strong>Rating:</strong> <?php echo htmlspecialchars($row['rating']); ?>/5</p>
                                <p><strong>Comment:</strong> <?php echo htmlspecialchars($row['comment']); ?></p>
                                <p><strong>Reviewed On:</strong> <?php echo htmlspecialchars($row['created_at']); ?></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No reviews available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
            function updateDateTime() {
                const dateTimeElement = document.getElementById('date-time');
                const now = new Date();

                const date = now.toLocaleDateString(undefined, {
                    year: 'numeric',
                    month: 'numeric',
                    day: 'numeric',
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
