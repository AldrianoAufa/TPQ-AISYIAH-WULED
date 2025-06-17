<?php
// export_payments.php
session_start(); // Mulai sesi

// Periksa apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Jika belum login, arahkan ke halaman login
    header("Location: login.php");
    exit();
}

include 'db_connect.php'; // Memasukkan file koneksi database

// Dapatkan rentang tanggal dari parameter GET (yang dikirim dari admin.php)
$academic_start_date_str = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$academic_end_date_str = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Variabel untuk tahun akademik, akan digunakan di nama file
$academic_start_year = '';
$academic_end_year = '';

// Jika parameter tidak ada atau tidak valid, gunakan logika penentuan tahun akademik default
if (empty($academic_start_date_str) || empty($academic_end_date_str) || !strtotime($academic_start_date_str) || !strtotime($academic_end_date_str)) {
    $current_calendar_year = date('Y');
    $current_calendar_month = date('n');

    if ($current_calendar_month >= 7) {
        $academic_start_year = $current_calendar_year;
        $academic_end_year = $current_calendar_year + 1;
    } else {
        $academic_start_year = $current_calendar_year - 1;
        $academic_end_year = $current_calendar_year;
    }
    $academic_start_date_str = "{$academic_start_year}-07-01";
    $academic_end_date_str = "{$academic_end_year}-06-30";
} else {
    // Ambil tahun dari string tanggal yang valid
    $academic_start_year = date('Y', strtotime($academic_start_date_str));
    $academic_end_year = date('Y', strtotime($academic_end_date_str));
}


// Set header untuk mengunduh file CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=data_pembayaran_spp_TA_' . $academic_start_year . '-' . $academic_end_year . '.csv');

// Buat file pointer output
$output = fopen('php://output', 'w');

// Tambahkan header CSV (gunakan titik koma sebagai pemisah)
fputcsv($output, array('Nama Siswa', 'Bulan', 'Tahun', 'Total Dibayar (Rp)'), ';'); // <--- PERUBAHAN UTAMA DI SINI

// Array untuk mengkonversi nomor bulan ke nama bulan dalam Bahasa Indonesia
$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Query untuk mendapatkan data pembayaran
$sql_payments_export = "
    SELECT
        s.nama_siswa,
        MONTH(p.tanggal) as bulan_pembayaran,
        YEAR(p.tanggal) as tahun_pembayaran,
        SUM(p.jumlah) as total_dibayar
    FROM
        pembayaran p
    JOIN
        siswa s ON p.id_siswa = s.id_siswa
    WHERE
        p.tanggal BETWEEN ? AND ?
    GROUP BY
        s.id_siswa, tahun_pembayaran, bulan_pembayaran
    ORDER BY
        tahun_pembayaran ASC, bulan_pembayaran ASC, s.nama_siswa ASC
";

$stmt_payments_export = $conn->prepare($sql_payments_export);
$stmt_payments_export->bind_param("ss", $academic_start_date_str, $academic_end_date_str);
$stmt_payments_export->execute();
$result_payments_export = $stmt_payments_export->get_result();

// Loop data dan masukkan ke CSV (gunakan titik koma sebagai pemisah)
if ($result_payments_export->num_rows > 0) {
    while ($row = $result_payments_export->fetch_assoc()) {
        $bulan_teks = $nama_bulan[$row['bulan_pembayaran']];
        fputcsv($output, [
            $row['nama_siswa'],
            $bulan_teks,
            $row['tahun_pembayaran'],
            $row['total_dibayar']
        ], ';'); // <--- PERUBAHAN UTAMA DI SINI
    }
}

// Tutup file pointer dan koneksi database
fclose($output);
$conn->close();
exit(); // Pastikan tidak ada output lain setelah ini
?>
