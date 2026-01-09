<?php
session_start();
require_once 'config/database.php';

// Destruir la sesión en la base de datos
if (isset($_SESSION['session_token'])) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE sessions SET is_valid = 0 WHERE token = ?");
    $stmt->execute([$_SESSION['session_token']]);
}

// Destruir la sesión PHP
session_destroy();

// Redirigir al login
header('Location: login.php?logged_out=1');
exit;
?>
