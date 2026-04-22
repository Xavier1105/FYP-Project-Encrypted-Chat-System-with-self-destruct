<?php
session_start();
require_once 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    // Update the last_seen time to NOW
    $conn->query("UPDATE users SET last_seen = NOW() WHERE user_id = $uid");
}
