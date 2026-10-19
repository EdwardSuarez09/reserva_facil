<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: ../public/html/login.html");
    exit;
}
?>
