<?php
require_once 'config.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fault Prediction System</title>
    <link rel="stylesheet" href="style/index.css">
</head>
<body>
    <div class="container">
        <button class="close-btn" onclick="window.close()">×</button>
        <h1>Fault Prediction System</h1>
        <a href="login.php" class="btn btn-primary">Login</a>
        <a href="register.php" class="btn btn-secondary">Register</a>
    </div>
</body>
</html>