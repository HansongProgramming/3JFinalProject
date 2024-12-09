<?php
require 'config.php'; // Ensure this file establishes the $conn database connection

header('Content-Type: application/json');

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Read and decode the JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['appointment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$appointment_id = intval($input['appointment_id']);

// Verify if the appointment belongs to the logged-in user
$query = "SELECT * FROM appointments WHERE appointment_id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $appointment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found or unauthorized access.']);
    exit();
}

// Update the appointment status to 'canceled'
$update_query = "UPDATE appointments SET status = 'canceled', updated_at = NOW() WHERE appointment_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param('i', $appointment_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Appointment canceled successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel the appointment.']);
}

$update_stmt->close();
$stmt->close();
$conn->close();
