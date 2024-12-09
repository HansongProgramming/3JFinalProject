<?php
session_start();
include 'config.php'; // Include your database connection file

// Fetch services from the database
$query = "SELECT * FROM services";
$result = $conn->query($query);

// List of image files in the images directory (now named 1.png, 2.png, etc.)
$imageFiles = ['1.png', '2.png', '3.png', '4.png', '5.png', '6.png'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking System</title>
    <link rel="stylesheet" type="text/css" href="aesthetics.css?v=1.0">

</head>
<body>
    <div class="index">
        <div class="hero">
            <img src="images/logo.png" alt="">
            <h1>hatid ng kaginhawaan</h1>
            <h2>isang masahe sa bawat pagkakataon </h2>
            <a href="login.php" class="btn">Book Now</a>
        </div>

        <!-- Custom Carousel container -->
        <div class="carousel-container">
            <h1>Our Services:</h1>
            <div class="carousel-track">
                <?php
                if ($result->num_rows > 0) {
                    $i = 0; // Variable to keep track of image index
                    while ($row = $result->fetch_assoc()) {
                        // Use the image based on the index (1.png, 2.png, etc.)
                        $imagePath = 'images/' . $imageFiles[$i % count($imageFiles)];

                        echo '<div class="carousel-item">';
                        echo '<img src="' . $imagePath . '" alt="Service Image" class="service-image">';
                        echo '<div class="service-card">';
                        echo '<h3>' . htmlspecialchars($row['service_name']) . '</h3>';
                        echo '<p>' . htmlspecialchars($row['description']) . '</p>';
                        echo '<p class="price">₱' . number_format($row['price'], 2) . '</p>';
                        echo '</div>';
                        echo '</div>';

                        // Increment the image index for the next service
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
    </div>
    <img src="images/overlayedLeaf.png" alt="" class="overlayindex">

</body>
</html>
