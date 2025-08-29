 <?php
// db_connect.php

// Konfigurasi koneksi database
// Disesuaikan dengan detail dari panel hosting InfinityFree Anda.

$servername = "sql106.infinityfree.com"; // MySQL Hostname dari panel hosting
$username = "if0_39258256";        // MySQL Username dari panel hosting
$password = "XSaa11FpDCCf9Gs";     // GANTI DENGAN PASSWORD DATABASE YANG ANDA SET UNTUK USER if0_39258256
                                   // PASTIKAN INI ADALAH PASSWORD DATABASE, BUKAN PASSWORD CPANEL.
$dbname = "if0_39258256_pembayaran_spp"; // Nama Database LENGKAP dari panel hosting

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