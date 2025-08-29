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
$spp_type_export = isset($_GET['spp_type']) ? $_GET['spp_type'] : 'spp1'; // 'spp1' or 'spp2'

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

// Fungsi untuk format tanggal ke Bahasa Indonesia dengan hari
function format_date_indo($date_str) {
    $hari = array ( 1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu');
    $bulan = array ( 1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $pecahkan = explode('-', $date_str);
    $num_day_of_week = date('N', strtotime($date_str)); // 1 (for Monday) through 7 (for Sunday)
    $day_name = $hari[$num_day_of_week];
    return $day_name . ', ' . $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}


// Set header untuk mengunduh file CSV
$filename_prefix = ($spp_type_export == 'spp1') ? 'data_spp_harian' : 'data_spp_mingguan';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename_prefix . '_TA_' . $academic_start_year . '-' . $academic_end_year . '.csv');

// Buat file pointer output
$output = fopen('php://output', 'w');

// Array untuk mengkonversi nomor bulan ke nama bulan dalam Bahasa Indonesia
$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

if ($spp_type_export == 'spp1') {
    // Tambahkan header CSV untuk SPP Harian
    fputcsv($output, array('Nama Siswa', 'Bulan', 'Tahun', 'Total Dibayar (Rp)', 'Dibayar Oleh', 'Waktu Pembayaran Terakhir'), ';');

    // Query untuk mendapatkan data pembayaran SPP Harian
    $sql_payments_export = "
        SELECT
            s.nama_siswa,
            MONTH(p.tanggal) as bulan_pembayaran,
            YEAR(p.tanggal) as tahun_pembayaran,
            SUM(p.jumlah) as total_dibayar,
            MAX(p.created_at_pembayaran) as last_paid_at,
            MAX(p.created_by_admin_username) as last_paid_by
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

    // Loop data dan masukkan ke CSV
    if ($result_payments_export->num_rows > 0) {
        while ($row = $result_payments_export->fetch_assoc()) {
            $bulan_teks = $nama_bulan[$row['bulan_pembayaran']];
            fputcsv($output, [
                $row['nama_siswa'],
                $bulan_teks,
                $row['tahun_pembayaran'],
                $row['total_dibayar'],
                $row['last_paid_by'] ?? '-',
                ($row['last_paid_at'] ? date('d M Y H:i:s', strtotime($row['last_paid_at'])) : '-')
            ], ';');
        }
    }
    $stmt_payments_export->close();

} elseif ($spp_type_export == 'spp2') {
    // Tambahkan header CSV untuk SPP Mingguan
    fputcsv($output, array('Nama Siswa', 'Minggu Ke-Akademik', 'Tanggal Mulai Minggu', 'Tanggal Akhir Minggu', 'Jumlah Dibayar (Rp)', 'Dibayar Oleh', 'Waktu Pembayaran'), ';');

    // Query untuk mendapatkan data pembayaran SPP Mingguan
    $sql_spp2_payments_export = "
        SELECT
            s.nama_siswa,
            psk.minggu_ke_akademik,
            psk.tanggal_mulai_minggu,
            psk.tanggal_akhir_minggu,
            psk.jumlah,
            psk.created_by_admin_username,
            psk.created_at_pembayaran
        FROM
            pembayaran_spp_kedua psk
        JOIN
            siswa s ON psk.id_siswa = s.id_siswa
        WHERE
            psk.tahun_akademik_mulai = ?
        ORDER BY
            psk.tanggal_mulai_minggu ASC, s.nama_siswa ASC
    ";
    $stmt_spp2_payments_export = $conn->prepare($sql_spp2_payments_export);
    $stmt_spp2_payments_export->bind_param("i", $academic_start_year);
    $stmt_spp2_payments_export->execute();
    $result_spp2_payments_export = $stmt_spp2_payments_export->get_result();

    // Loop data dan masukkan ke CSV
    if ($result_spp2_payments_export->num_rows > 0) {
        while ($row = $result_spp2_payments_export->fetch_assoc()) {
            fputcsv($output, [
                $row['nama_siswa'],
                $row['minggu_ke_akademik'],
                $row['tanggal_mulai_minggu'],
                $row['tanggal_akhir_minggu'],
                $row['jumlah'],
                $row['created_by_admin_username'] ?? '-',
                ($row['created_at_pembayaran'] ? date('d M Y H:i:s', strtotime($row['created_at_pembayaran'])) : '-')
            ], ';');
        }
    }
    $stmt_spp2_payments_export->close();
}


// Tutup file pointer dan koneksi database
fclose($output);
$conn->close();
exit(); // Pastikan tidak ada output lain setelah ini
?>