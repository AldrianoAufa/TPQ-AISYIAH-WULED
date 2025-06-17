<?php
// db_connect.php

// Konfigurasi koneksi database
$servername = "localhost"; // Ganti jika host database Anda berbeda
$username = "root";        // Ganti dengan username MySQL Anda
$password = "";            // Ganti dengan password MySQL Anda
$dbname = "pembayaran_spp"; // Nama database yang telah Anda buat

// Membuat koneksi ke database
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
    // Menghentikan eksekusi skrip dan menampilkan pesan error jika koneksi gagal
    die("Koneksi gagal: " . $conn->connect_error);
}
// Set character set to UTF-8 for proper handling of special characters
$conn->set_charset("utf8mb4");
?>
