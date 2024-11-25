<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone_number = $_POST['phone_number'];
    $role = $_POST['role']; // Role dropdown
    
    // Validate input fields
    if (empty($full_name) || empty($email) || empty($password) || empty($phone_number) || empty($role)) {
        echo "All fields are required.";
        exit;
    }
    
    // Prepare the SQL query to insert data
    $sql = "INSERT INTO users (full_name, email, password, phone_number, role) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssss', $full_name, $email, $password, $phone_number, $role);
    
    if ($stmt->execute()) {
        echo "Registration successful. You can <a href='login.php'>Login here</a>";
    } else {
        echo "Error: " . $stmt->error;
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
    <title>Registration</title>
</head>
<body>

<h2>Register</h2>
<form method="POST" action="registration.php">
    <label for="full_name">Full Name</label>
    <input type="text" name="full_name" id="full_name" required>

    <label for="email">Email</label>
    <input type="email" name="email" id="email" required>

    <label for="password">Password</label>
    <input type="password" name="password" id="password" required>

    <label for="phone_number">Phone Number</label>
    <input type="text" name="phone_number" id="phone_number" required>

    <label for="role">Role</label>
    <select name="role" id="role" required>
        <option value="customer">Customer</option>
        <option value="admin">Admin</option>
        <option value="therapist">Therapist</option>
    </select>

    <button type="submit">Register</button>
</form>

</body>
</html>
