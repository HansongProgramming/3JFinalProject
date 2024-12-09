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

$promotions_query = "SELECT * FROM promotions";
$promotions_result = $conn->query($promotions_query);
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

    </script>
</body>
</html>
