<?php
// Database Configuration for XAMPP (Localhost)
$servername = "localhost";
$username = "root";      // Default XAMPP username
$password = "";          // Default XAMPP password is empty
$dbname = "sentinel_chat"; // The name of your database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // If connection fails, stop the script and show the error
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8 (Essential for handling encrypted strings correctly)
$conn->set_charset("utf8mb4");
