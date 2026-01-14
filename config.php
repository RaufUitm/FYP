<?php
// config.php

// --- DEVELOPMENT ONLY: Enable comprehensive error reporting ---
// REMOVE THESE LINES IN PRODUCTION ENVIRONMENT FOR SECURITY REASONS
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration
$host = 'localhost';
$dbname = 'fault_prediction_db';
$username = 'root';
$password = '';

try {
    // Attempt to establish a PDO database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set PDO error mode to exception to catch database errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // If connection fails, log the error and display a user-friendly message (do not expose $e->getMessage() in production)
    error_log("Database connection failed: " . $e->getMessage());
    die("A database connection error occurred. Please try again later.");
}

// Start session
// Ensure session has not already been started to prevent warnings/errors
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
