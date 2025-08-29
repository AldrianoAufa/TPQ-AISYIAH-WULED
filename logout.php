 <?php
// logout.php
session_start(); // Mulai sesi

// Hapus semua variabel sesi
session_unset();

// Hancurkan sesi
session_destroy();

// Arahkan kembali ke halaman login
header("Location: login.php");
exit();
?>