<?php
session_start(); // Memulai session

// 1. Hapus semua data sesi
$_SESSION = [];

// 2. Hancurkan sesi
session_unset();
session_destroy();

// 3. Arahkan kembali ke halaman login
header("Location: login.php");
exit;
?>