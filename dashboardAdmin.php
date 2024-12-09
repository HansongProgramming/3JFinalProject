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

$success_message = $error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    switch ($action) {
        case 'assign_schedule':
            if (isset($_POST['therapist_id'], $_POST['date'], $_POST['start_time'], $_POST['end_time'])) {
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
            break;

        case 'approve':
        case 'cancel':
        case 'reschedule':
            if (isset($_POST['appointment_id'])) {
                $appointment_id = intval($_POST['appointment_id']);
                switch ($action) {
                    case 'approve':
                        $update_stmt = $conn->prepare("UPDATE appointments SET status = 'confirmed' WHERE appointment_id = ?");
                        break;
                    case 'cancel':
                        $update_stmt = $conn->prepare("UPDATE appointments SET status = 'canceled' WHERE appointment_id = ?");
                        break;
                    case 'reschedule':
                        break;
                }

                if (isset($update_stmt)) {
                    $update_stmt->bind_param("i", $appointment_id);
                    $update_stmt->execute();
                }
            }
            break;

        case 'add_service':
            if (isset($_POST['service_name'], $_POST['description'], $_POST['duration'], $_POST['price'])) {
                $stmt = $conn->prepare("INSERT INTO services (service_name, description, duration, price) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssdi", $_POST['service_name'], $_POST['description'], $_POST['duration'], $_POST['price']);
                $stmt->execute();
            }
            break;

        case 'delete':
            // Handle service deletion
            if (isset($_POST['service_id'])) {
                $stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
                $stmt->bind_param("i", $_POST['service_id']);
                $stmt->execute();
            }
            break;
    }
}

// Fetch schedules, bookings, and services data
$schedules_stmt = $conn->prepare("SELECT a.date, a.start_time, a.end_time, u.full_name AS therapist_name FROM availability a JOIN users u ON a.therapist_id = u.user_id ORDER BY a.date, a.start_time");
$schedules_stmt->execute();
$schedules = $schedules_stmt->get_result();

$schedule_data = [];
while ($schedule = $schedules->fetch_assoc()) {
    $schedule_data[$schedule['date']][] = $schedule;
}

$bookings_stmt = $conn->prepare("SELECT a.appointment_id, u.full_name AS customer_name, t.full_name AS therapist_name, s.service_name, a.appointment_date, a.start_time, a.end_time, a.status FROM appointments a JOIN users u ON a.user_id = u.user_id JOIN users t ON a.therapist_id = t.user_id JOIN services s ON a.service_id = s.service_id ORDER BY a.appointment_date, a.start_time");
$bookings_stmt->execute();
$bookings = $bookings_stmt->get_result();

$services_stmt = $conn->prepare("SELECT * FROM services ORDER BY created_at DESC");
$services_stmt->execute();
$services = $services_stmt->get_result();

$payments_stmt = $conn->prepare("SELECT p.payment_id, a.appointment_id, u.full_name AS customer_name, p.amount, p.payment_status, p.payment_date FROM payments p JOIN appointments a ON p.appointment_id = a.appointment_id JOIN users u ON a.user_id = u.user_id ORDER BY p.payment_date DESC");
$payments_stmt->execute();
$payments = $payments_stmt->get_result();

$reviews_stmt = $conn->prepare("SELECT r.rating, r.comment, r.created_at, a.appointment_date, u.full_name as client_name 
    FROM reviews r
    JOIN appointments a ON r.appointment_id = a.appointment_id
    JOIN users u ON r.user_id = u.user_id
    ORDER BY r.created_at DESC");
$reviews_stmt->execute();
$reviews = $reviews_stmt->get_result();

?>

<!-- HTML and JavaScript remains the same as before -->


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
        <button class="adminbutton" onclick="showSection('assign-schedule')">Assign Schedule</button> <br>
        <button class="adminbutton" onclick="showSection('manage-bookings')">Manage Bookings</button> <br>
        <button class="adminbutton" onclick="showSection('manage-services')">Manage Services</button> <br>
        <button class="adminbutton" onclick="showSection('payments-reports')">Payments and Reports</button> <br>
        <a href="logout.php">Logout</a>        
    </div>


        <div class="content">
            <div id="date-time"></div>

            <div id="assign-schedule" class="content-section">
                <div class="assignment">
                    <form method="POST">
                        <label for="therapist_id">Therapist:</label>
                        <select name="therapist_id" required>
                            <option value="">-- Select Therapist --</option>
                            <?php while ($therapist = $therapists->fetch_assoc()): ?>
                                <option value="<?php echo $therapist['user_id']; ?>">
                                    <?php echo htmlspecialchars($therapist['full_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <label for="date">Date:</label>
                        <input type="date" name="date" required>

                        <label for="start_time">Start Time:</label>
                        <input type="time" name="start_time" required>

                        <label for="end_time">End Time:</label>
                        <input type="time" name="end_time" required>

                        <button type="submit">Assign Schedule</button>
                    </form>
                </div>

                        <div class="assign-schedule">
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
                                        echo "<div class='schedule'>{$schedule['therapist_name']} - {$schedule['start_time']} to {$schedule['end_time']}</div> <hr> <br>";
                                    }
                                }

                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>


            <div id="manage-bookings" class="content-section">
                <h3>Manage Bookings</h3>
                <div class="sertablis">
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Therapist</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = $bookings->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['therapist_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                    <td><?php echo $booking['appointment_date']; ?></td>
                                    <td><?php echo "{$booking['start_time']} - {$booking['end_time']}"; ?></td>
                                    <td><?php echo $booking['status']; ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="appointment_id" value="<?php echo $booking['appointment_id']; ?>">
                                            <button type="submit" name="action" value="approve">approve</button>
                                            <button type="submit" name="action" value="cancel">cancel</button>
                                            <button type="submit" name="action" value="reschedule">reschedule</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="sertablisR">
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
            </div>

            <div id="manage-services" class="content-section">
                <h3>Manage Services</h3>
                <div class="sertablis">
                <table>
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Description</th>
                            <th>Duration</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($service = $services->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                                <td><?php echo htmlspecialchars($service['description']); ?></td>
                                <td><?php echo $service['duration'] . " mins"; ?></td>
                                <td><?php echo "$" . $service['price']; ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
                                        <button type="submit" name="action" value="edit">Edit</button>
                                        <button type="submit" name="action" value="delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
                <h4>Add New Service</h4>
                <form method="POST">
                    <label for="service_name">Name:</label> <br>
                    <input type="text" name="service_name" required> <br>
                    <label for="description">Description:</label> <br>
                    <textarea name="description"></textarea><br> <br>
                    <label for="duration">Duration (mins):</label> <br>
                    <input type="number" name="duration" required> <br>
                    <label for="price">Price ($):</label><br>
                    <input type="number" step="0.01" name="price" required> <br>
                    <button type="submit" name="action" value="add_service">Add Service</button>
                </form>
            </div>
            
            <div id="payments-reports" class="content-section">
                <h3>Payments and Reports</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($payment = $payments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                                <td><?php echo "$" . $payment['amount']; ?></td>
                                <td><?php echo $payment['payment_status']; ?></td>
                                <td><?php echo $payment['payment_date']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <script>
                function showSection(sectionId) {
                    const sections = document.querySelectorAll('.content-section');
                    sections.forEach(section => section.style.display = 'none');

                    const selectedSection = document.getElementById(sectionId);
                    if (selectedSection) {
                        selectedSection.style.display = 'grid';
                    }

                    const buttons = document.querySelectorAll('.sidebar button');
                    buttons.forEach(button => button.classList.remove('active'));

                    const activeButton = Array.from(buttons).find(button =>
                        button.textContent.trim().includes(sectionId.replace('-', ' '))
                    );
                    if (activeButton) {
                        activeButton.classList.add('active');
                    }
                }

                document.addEventListener('DOMContentLoaded', () => {
                    showSection('assign-schedule');
                });


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
