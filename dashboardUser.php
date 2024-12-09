<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'config.php';

$user_id = $_SESSION['user_id'];

$services_query = "SELECT * FROM services";
$services_result = $conn->query($services_query);

$promotions_query = "SELECT * FROM promotions";
$promotions_result = $conn->query($promotions_query);

$upcoming_stmt = $conn->prepare("SELECT a.appointment_id, a.appointment_date, a.start_time, s.service_name, u.full_name as therapist_name 
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    JOIN users u ON a.therapist_id = u.user_id
    WHERE a.user_id = ? AND a.appointment_date >= CURDATE() AND a.status != 'canceled'
    ORDER BY a.appointment_date, a.start_time");
$upcoming_stmt->bind_param("i", $user_id);
$upcoming_stmt->execute();
$upcoming_appointments = $upcoming_stmt->get_result();

$past_stmt = $conn->prepare("SELECT a.appointment_id, a.appointment_date, a.start_time, s.service_name, u.full_name as therapist_name 
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
    <link rel="stylesheet" href="aesthetics.css?v=1.0">
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
            <a href="services.php">View Services</a> <br> <br>
            <a href="booking.php">Make a Booking</a> <br> <br>
            <a href="logout.php">Logout</a> 
        </div>


        <div class="appointments">
        <h2><span class="highlight">Upcoming</span> Appointments</h2>
        <div class="cards">
            <?php if ($upcoming_appointments->num_rows > 0): ?>
                <?php while ($row = $upcoming_appointments->fetch_assoc()): ?>
                    <div class="card" id="appointment-<?php echo $row['appointment_id']; ?>">
                        <h3><?php echo $row['service_name']; ?></h3>
                        <p><strong>Date:</strong> <?php echo $row['appointment_date']; ?></p>
                        <p><strong>Time:</strong> <?php echo $row['start_time']; ?></p>
                        <p><strong>Therapist:</strong> <?php echo $row['therapist_name']; ?></p>
                        <button class="cancel-button" data-appointment-id="<?php echo $row['appointment_id']; ?>">Cancel</button>
                        <button class="reschedule-button" data-appointment-id="<?php echo $row['appointment_id']; ?>">Reschedule</button>
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
                            
                            <!-- Review button added here -->
                            <a href="commentsite.php?appointment_id=<?php echo $row['appointment_id']; ?>&user_id=<?php echo $user_id; ?>" class="review-button">Review</a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No past appointments.</p>
                <?php endif; ?>
            </div>

        </div>
        <div class="filler">
            <div id="date-time"></div>

            <h3>Services for You!</h3>
            <?php if ($services_result->num_rows > 0): ?>
                <div class="carousel-container">
                    <div class="carousel-track">
                        <?php while ($service = $services_result->fetch_assoc()): ?>
                            <div class="carousel-item">
                                <div class="service-card">
                                    <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                                    <p><?php echo htmlspecialchars($service['description']); ?></p>
                                    <p class="price">Price: $<?php echo $service['price']; ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <button class="carousel-prev">‹</button>
                    <button class="carousel-next">›</button>
                </div>
            <?php else: ?>
                <p>No services available.</p>
            <?php endif; ?>

            <h3>Promos Available</h3>
            <?php if ($promotions_result->num_rows > 0): ?>
                <div class="carousel-container">
                    <div class="carousel-track">
                        <?php while ($promo = $promotions_result->fetch_assoc()): ?>
                            <div class="carousel-item">
                                <div class="service-card">
                                    <h3><?php echo htmlspecialchars($promo['promo_code']); ?></h3>
                                    <p><?php echo htmlspecialchars($promo['description']); ?></p>
                                    <p> Discount: <span class="discount"><?php echo $promo['discount_percent']; ?></span>%</p>
                                    <p class="date">Valid: <?php echo $promo['start_date']; ?> to <?php echo $promo['end_date']; ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <button class="carousel-prev">‹</button>
                    <button class="carousel-next">›</button>
                </div>
            <?php else: ?>
                <p>No promotions available at the moment.</p>
            <?php endif; ?>
        </div>

        <script>
        document.querySelectorAll('.reschedule-button').forEach(button => {
            button.addEventListener('click', function () {
                const appointmentId = this.getAttribute('data-appointment-id');
                window.location.href = `reschedule.php?appointment_id=${appointmentId}`;
            });
        });



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

        <script>
            document.querySelectorAll('.cancel-button').forEach(button => {
                button.addEventListener('click', function() {
                    const appointmentId = this.getAttribute('data-appointment-id');
                    const appointmentCard = document.getElementById(`appointment-${appointmentId}`);

                    if (confirm("Are you sure you want to cancel this appointment?")) {
                        // Send an AJAX request to the server to cancel the appointment
                        fetch('cancel_appointment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ appointment_id: appointmentId })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                appointmentCard.remove();
                                alert("Appointment canceled successfully.");
                            } else {
                                alert("An error occurred while canceling the appointment.");
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            alert("An error occurred while canceling the appointment.");
                        });
                    }
                });
            });
        </script>
                <script>
            // Get carousel elements
            const track = document.querySelector('.carousel-track');
            const items = document.querySelectorAll('.carousel-item');
            const prevButton = document.querySelector('.carousel-prev');
            const nextButton = document.querySelector('.carousel-next');

            let currentIndex = 0;
            const totalItems = items.length;

            // Update the carousel track position
            function updateCarouselPosition() {
                track.style.transform = `translateX(-${currentIndex * 100}%)`;
            }

            // Show next item in the carousel
            nextButton.addEventListener('click', () => {
                currentIndex = (currentIndex + 1) % totalItems;
                updateCarouselPosition();
            });

            // Show previous item in the carousel
            prevButton.addEventListener('click', () => {
                currentIndex = (currentIndex - 1 + totalItems) % totalItems;
                updateCarouselPosition();
            });

            // Optional: Auto-slide every 3 seconds
            setInterval(() => {
                currentIndex = (currentIndex + 1) % totalItems;
                updateCarouselPosition();
            }, 3000);
        </script>

        <script>
                        document.querySelectorAll('.carousel-container').forEach((carousel, index) => {
                        const track = carousel.querySelector('.carousel-track');
                        const items = carousel.querySelectorAll('.carousel-item');
                        const prevButton = carousel.querySelector('.carousel-prev');
                        const nextButton = carousel.querySelector('.carousel-next');

                        let currentIndex = 0;
                        const totalItems = items.length;

                        function updateCarouselPosition() {
                            track.style.transform = `translateX(-${currentIndex * 100}%)`;
                        }

                        nextButton.addEventListener('click', () => {
                            currentIndex = (currentIndex + 1) % totalItems;
                            updateCarouselPosition();
                        });

                        prevButton.addEventListener('click', () => {
                            currentIndex = (currentIndex - 1 + totalItems) % totalItems;
                            updateCarouselPosition();
                        });
                                // Optional: Auto-slide every 3 seconds
                        setInterval(() => {
                            currentIndex = (currentIndex + 1) % totalItems;
                            updateCarouselPosition();
                        }, 3000);
                    });

        </script>
    </div>
</body>
</html>
