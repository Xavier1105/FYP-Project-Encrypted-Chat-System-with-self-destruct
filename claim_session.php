<?php
session_start();
require_once 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("UPDATE users SET active_session_id = ? WHERE user_id = ?");
    $my_session = session_id();
    $stmt->bind_param("si", $my_session, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}
