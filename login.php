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
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="aesthetics.css">
</head>
<body>
    <h1>Login</h1>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Login</button>
    </form>
    <a href="registration.php">Don't have an account yet? Click here!</a>
</body>
</html>
