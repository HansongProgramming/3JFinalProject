<?php
require 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, full_name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id, $full_name, $hashed_password, $role);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $full_name;
        $_SESSION['role'] = $role;

        switch ($role) {
            case 'admin':
                header("Location: dashboardAdmin.php");
                break;
            case 'therapist':
                header("Location: dashboardTherapist.php");
                break;
            case 'customer':
                header("Location: dashboardUser.php");
                break;
            default:
                echo "Invalid role.";
                exit();
        }
        exit();
    } else {
        echo "<p style='color: red;'>Invalid email or password!</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="aesthetics.css?v=1.0">
</head>
<body>
    <div class="index">
                <!-- Login Form -->
            <div class="login">
            <img src="images/userIcon.png" alt="">
            <h1>Login</h1>
            <form method="POST">
                <input type="email" name="email" placeholder="   Email" required><br>
                <input type="password" name="password" placeholder="   Password" required><br>
                <button type="submit" class="btn">Login</button>
            </form>
            <a href="registration.php">Don't have an account yet? Click here!</a>
        </div>

        <!-- Custom Carousel container -->
        <div class="carousel-container">
        <h1>Our Services:</h1>
            <div class="carousel-track">
                <?php
                $query = "SELECT * FROM services";
                $result = $conn->query($query);

                $imageFiles = ['1.png', '2.png', '3.png', '4.png', '5.png', '6.png'];

                if ($result->num_rows > 0) {
                    $i = 0;
                    while ($row = $result->fetch_assoc()) {
                        $imagePath = 'images/' . $imageFiles[$i % count($imageFiles)];

                        echo '<div class="carousel-item">';
                        echo '<img src="' . $imagePath . '" alt="Service Image" class="service-image">';
                        echo '<div class="service-card">';
                        echo '<h3>' . htmlspecialchars($row['service_name']) . '</h3>';
                        echo '<p>' . htmlspecialchars($row['description']) . '</p>';
                        echo '<p class="price">₱' . number_format($row['price'], 2) . '</p>';
                        echo '</div>';
                        echo '</div>';

                        $i++;
                    }
                } else {
                    echo '<div class="carousel-item"><p>No services available at the moment.</p></div>';
                }
                ?>
            </div>
            <!-- Carousel control buttons -->
            <button class="carousel-prev">‹</button>
            <button class="carousel-next">›</button>
        </div>


    </div>

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
</body>
</html>
