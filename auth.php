<?php
// Guard reutilizable — incluir al inicio de cualquier página protegida
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
