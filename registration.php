<?php
session_start();
require_once 'config.php';

$message = ""; // Variable to hold success or error messages

// Fetch promotions from the database
$query = "SELECT * FROM promotions WHERE start_date <= CURDATE() AND end_date >= CURDATE()";
$result = $conn->query($query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone_number = $_POST['phone_number'];
    $role = $_POST['role']; // Role dropdown
    
    if (empty($full_name) || empty($email) || empty($password) || empty($phone_number) || empty($role)) {
        $message = "All fields are required.";
    } else {
        $sql = "INSERT INTO users (full_name, email, password, phone_number, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssss', $full_name, $email, $password, $phone_number, $role);
        
        if ($stmt->execute()) {
            header('Location: login.php'); 
            exit();
        } else {
            $message = "Error: " . $stmt->error;
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" type="text/css" href="aesthetics.css?v=1.0">
</head>
<body>
    <div class="index">
        <div class="login">
            <h2>Register</h2>
            <!-- Display the message here if it exists -->
            <?php if (!empty($message)): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST" action="registration.php">
                <input type="text" name="full_name" id="full_name" required placeholder="  full name">
                <br>
                <input type="email" name="email" id="email" required placeholder="  email">
                <br>
                <input type="password" name="password" id="password" required placeholder="  password">
                <br>
                <input type="text" name="phone_number" id="phone_number" required placeholder="  phone number">
                <br>

                <select name="role" id="role" required>
                    <option value="customer">Customer</option>
                    <option value="admin">Admin</option>
                    <option value="therapist">Therapist</option>
                </select>
                <br>
                <button type="submit" class="btn">Register</button>
            </form>
        </div>

        <!-- Carousel for Promotions -->
        <?php if ($result->num_rows > 0): ?>
            <div class="carousel-container">
                <div class="carousel-track">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="carousel-item">
                            <div class="promo-card">
                                <h3><?php echo htmlspecialchars($row['promo_code']); ?></h3>
                                <p><?php echo htmlspecialchars($row['description']); ?></p>
                                <p class="discount">Discount: <?php echo $row['discount_percent']; ?>%</p>
                                <p class="date">Valid from <?php echo $row['start_date']; ?> to <?php echo $row['end_date']; ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <!-- Carousel control buttons -->
                <button class="carousel-prev">‹</button>
                <button class="carousel-next">›</button>
            </div>
        <?php else: ?>
            <p>No promotions available at the moment.</p>
        <?php endif; ?>
    </div>

    <script>
        // Wait for DOM content to load before initializing carousel
        document.addEventListener('DOMContentLoaded', function () {
            // Get carousel elements after the content is rendered
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
                currentIndex = (currentIndex + 1) % totalItems; // Loop back to the first item
                updateCarouselPosition();
            });

            // Show previous item in the carousel
            prevButton.addEventListener('click', () => {
                currentIndex = (currentIndex - 1 + totalItems) % totalItems; // Loop back to the last item
                updateCarouselPosition();
            });

            // Optional: Auto-slide every 3 seconds
            setInterval(() => {
                currentIndex = (currentIndex + 1) % totalItems;
                updateCarouselPosition();
            }, 3000);
        });

    </script>
</body>
</html>
