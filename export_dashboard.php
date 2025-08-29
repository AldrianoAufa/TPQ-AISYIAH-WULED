 <?php
session_start();
include 'db_connect.php';

// Query ulang data dashboard (samakan dengan yang di dashboard)
$academic_start_year = date('Y'); // Atur sesuai logika tahun akademik Anda
$academic_end_year = $academic_start_year + 1;

// Contoh query, sesuaikan dengan logika dashboard Anda!
$query = "SELECT s.nama_siswa, 
    SUM(CASE WHEN p.tipe = 'spp1' THEN p.jumlah ELSE 0 END) AS total_spp1,
    SUM(CASE WHEN p.tipe = 'spp2' THEN p.jumlah ELSE 0 END) AS total_spp2
    -- Tambahkan kolom biaya lain jika perlu
    FROM siswa s
    LEFT JOIN pembayaran_spp p ON s.id_siswa = p.id_siswa
    GROUP BY s.id_siswa, s.nama_siswa";

$result = $conn->query($query);

// Header untuk download file CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=dashboard_spp.csv');

$output = fopen('php://output', 'w');
// Header kolom
fputcsv($output, ['Nama Siswa', 'Total SPP Harian', 'Total SPP Mingguan']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['nama_siswa'],
        $row['total_spp1'],
        $row['total_spp2'],
    ]);
}
fclose($output);
exit;
?