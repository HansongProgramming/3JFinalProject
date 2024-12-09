<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve appointment_id and user_id from hidden fields
    $appointment_id = $_POST['appointment_id'];
    $user_id = $_POST['user_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    // Insert the review into the database
    $sql = "INSERT INTO reviews (appointment_id, user_id, rating, comment)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $appointment_id, $user_id, $rating, $comment);

    if ($stmt->execute()) {
        echo "<script>alert('Review submitted successfully!'); window.location.href = 'dashboardUser.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    // Retrieve appointment_id and user_id from query parameters
    if (isset($_GET['appointment_id']) && isset($_GET['user_id'])) {
        $appointment_id = $_GET['appointment_id'];
        $user_id = $_GET['user_id'];
    } else {
        echo "<script>alert('Missing required parameters.'); window.location.href = 'dashboardUser.php';</script>";
        exit();
    }
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
    <form class="review-form" method="POST">
        <!-- Hidden fields to pass appointment_id and user_id -->
        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment_id); ?>">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">

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
