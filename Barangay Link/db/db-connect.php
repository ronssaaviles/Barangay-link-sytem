<?php
// Database connection details
$host = 'localhost'; // Hostname where the database is hosted
$dbname = 'barangay_db'; // Name of the database
$username = 'root'; // Database username
$password = ''; // Database password

try {
    // Create a new PDO instance to connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Set PDO error mode to exception for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Handle connection error by displaying a failure message
    die("Database connection failed: " . $e->getMessage());
}
?>