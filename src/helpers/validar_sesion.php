<?php
session_start();
if (!isset($_SESSION['id'])) {
    echo "no";
    exit;
}
echo "ok";
?>
