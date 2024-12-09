<?php
include 'commentsite.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $appointment_id = $_POST['appointment_id'];
    $user_id = $_POST['user_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    $sql = "INSERT INTO reviews (appointment_id, user_id, rating, comment)
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $appointment_id, $user_id, $rating, $comment);

    if ($stmt->execute()) {
        echo "Review submitted successfully!";
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="aesthetics.css?v=1.0">
    <title>User Review</title>
</head>
<body class="review-page">
    <h1 class="form-title">Submit Your Review</h1>
    <form class="review-form" action="submit_review.php" method="POST">
        <label class="form-label" for="appointment_id">Appointment ID:</label>
        <input class="form-input" type="number" id="appointment_id" name="appointment_id" required>
        <br><br>

        <label class="form-label" for="user_id">User ID:</label>
        <input class="form-input" type="number" id="user_id" name="user_id" required>
        <br><br>

        <label class="form-label" for="rating">Rating (1-5):</label>
        <input class="form-input" type="number" id="rating" name="rating" min="1" max="5" required>
        <br><br>

        <label class="form-label" for="comment">Comment:</label>
        <textarea class="form-textarea" id="comment" name="comment" rows="4" cols="50"></textarea>
        <br><br>

        <button class="submit-button" type="submit">Submit Review</button>
    </form>
</body>
</html>