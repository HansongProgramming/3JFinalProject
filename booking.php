<?php
require 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$services = $conn->query("SELECT * FROM services");
$therapists = $conn->query("SELECT user_id, full_name FROM users WHERE role = 'therapist'");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_booking'])) {
    $user_id = $_SESSION['user_id'];
    $service_id = $_POST['service_id'];
    $appointment_date = $_POST['appointment_date'];
    $start_time = $_POST['start_time'];
    $therapist_id = $_POST['therapist_id'];
    $payment_method = $_POST['payment_method'];

    // Get service price
    $service_query = $conn->prepare("SELECT price FROM services WHERE service_id = ?");
    $service_query->bind_param("i", $service_id);
    $service_query->execute();
    $service_result = $service_query->get_result();

    if ($service_result->num_rows > 0) {
        $service_data = $service_result->fetch_assoc();
        $amount = $service_data['price'];

        // Insert appointment
        $appointment_stmt = $conn->prepare("INSERT INTO appointments (user_id, service_id, appointment_date, start_time, therapist_id, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $appointment_stmt->bind_param("iissi", $user_id, $service_id, $appointment_date, $start_time, $therapist_id);

        if ($appointment_stmt->execute()) {
            $appointment_id = $appointment_stmt->insert_id;

            // Insert payment record
            $payment_stmt = $conn->prepare("INSERT INTO payments (appointment_id, amount, payment_method, payment_status) VALUES (?, ?, ?, 'unpaid')");
            $payment_stmt->bind_param("ids", $appointment_id, $amount, $payment_method);

            if ($payment_stmt->execute()) {
                // Redirect to dashboard
                header("Location: dashboardUser.php?success=1");
                exit();
            } else {
                $error_message = "Error creating payment record: " . $conn->error;
            }
        } else {
            $error_message = "Error creating appointment: " . $conn->error;
        }
    } else {
        $error_message = "Invalid service selected.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Make a Booking</title>
    <link rel="stylesheet" href="aesthetics.css?v=1.0">
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            width: 400px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .modal-content button {
            margin: 10px;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .modal-content button:hover {
            background-color: #ddd;
        }
    </style>
    <script>
        function openModal() {
            const serviceId = document.getElementById('service_id').value;
            const therapistId = document.querySelector('select[name="therapist_id"]').value;
            const appointmentDate = document.querySelector('input[name="appointment_date"]').value;
            const startTime = document.querySelector('input[name="start_time"]').value;

            if (!serviceId || !therapistId || !appointmentDate || !startTime) {
                alert('Please fill in all the fields before confirming your booking.');
                return;
            }

            document.getElementById('modal_service_id').value = serviceId;
            document.getElementById('modal_appointment_date').value = appointmentDate;
            document.getElementById('modal_start_time').value = startTime;
            document.getElementById('modal_therapist_id').value = therapistId;

            document.getElementById("paymentModal").style.display = "flex";
        }

        function closeModal() {
            document.getElementById("paymentModal").style.display = "none";
        }
    </script>
</head>
<body>
    <br><br>
    <div class="bookingContainer">
        <div class="book">
            <h1>Book a Service</h1>
            <?php
            if (isset($error_message)) {
                echo "<p style='color: red;'>$error_message</p>";
            }
            ?>

            <form method="POST" id="bookingForm">
                <label for="service_id">Select a Service:</label><br>
                <select name="service_id" id="service_id" required>
                    <option value="">-- Select a Service --</option>
                    <?php while ($row = $services->fetch_assoc()): ?>
                        <option value="<?php echo $row['service_id']; ?>">
                            <?php echo htmlspecialchars($row['service_name']); ?> - $<?php echo number_format($row['price'], 2); ?>
                        </option>
                    <?php endwhile; ?>
                </select><br>

                <label for="therapist_id">Select a Therapist:</label><br>
                <select name="therapist_id" required>
                    <option value="">-- Select a Therapist --</option>
                    <?php while ($row = $therapists->fetch_assoc()): ?>
                        <option value="<?php echo $row['user_id']; ?>">
                            <?php echo htmlspecialchars($row['full_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select><br>

                <label for="appointment_date">Date:</label><br>
                <input type="date" name="appointment_date" required><br>

                <label for="start_time">Start Time:</label><br>
                <input type="time" name="start_time" required><br><br>

                <button type="button" onclick="openModal()">Confirm Booking</button>
            </form>
            <br> 
                <div class="circ"><a href="dashboardUser.php"><</a>
                </div>

            <!-- Payment Modal -->
            <div id="paymentModal" class="modal">
                <div class="modal-content">
                    <h2>Choose Payment Method</h2>
                    <form method="POST">
                        <input type="hidden" name="confirm_booking" value="1">
                        <input type="hidden" name="service_id" id="modal_service_id">
                        <input type="hidden" name="appointment_date" id="modal_appointment_date">
                        <input type="hidden" name="start_time" id="modal_start_time">
                        <input type="hidden" name="therapist_id" id="modal_therapist_id">
                        <label for="payment_method">Payment Method:</label><br>
                        <select name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="paypal">PayPal</option>
                        </select><br><br>
                        <button type="submit">Submit Payment</button>
                        <button type="button" onclick="closeModal()">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
