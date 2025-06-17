<?php
// index.php - Halaman User

// Nonaktifkan pelaporan error PHP setelah debugging selesai
error_reporting(0); // Matikan semua pelaporan error
ini_set('display_errors', 0); // Jangan tampilkan error di halaman

include 'db_connect.php'; // Memasukkan file koneksi database

$selected_siswa_id = isset($_GET['siswa_id']) ? intval($_GET['siswa_id']) : 0;

// --- PERUBAHAN: Penentuan Tahun Akademik ---
$current_calendar_year = date('Y');
$current_calendar_month = date('n'); // 1 for January, 12 for December

if ($current_calendar_month >= 7) { // Jika bulan saat ini Juli (7) atau setelahnya (sampai Desember)
    $academic_start_year = $current_calendar_year;
    $academic_end_year = $current_calendar_year + 1;
} else { // Jika bulan saat ini Januari (1) hingga Juni (6)
    $academic_start_year = $current_calendar_year - 1;
    $academic_end_year = $current_calendar_year;
}

$academic_start_date_obj = new DateTime("{$academic_start_year}-07-01"); // Tahun akademik dimulai 1 Juli
$academic_end_date_obj = new DateTime("{$academic_end_year}-06-30");   // Tahun akademik berakhir 30 Juni

// Mendapatkan daftar siswa untuk dropdown
$siswa_list = [];
$sql_siswa = "SELECT id_siswa, nama_siswa FROM siswa ORDER BY id_siswa ASC"; // Diurutkan berdasarkan ID Siswa
$result_siswa = $conn->query($sql_siswa);

if ($result_siswa) { // Pastikan query berhasil dieksekusi
    if ($result_siswa->num_rows > 0) {
        while ($row = $result_siswa->fetch_assoc()) {
            $siswa_list[] = $row;
        }
    }
}

// Mendapatkan daftar tanggal libur dan keterangannya dari database untuk tahun akademik ini
$holidays = [];
// --- PERUBAHAN: Query berdasarkan rentang tanggal tahun akademik ---
$stmt_holidays = $conn->prepare("SELECT tanggal, keterangan FROM libur WHERE tanggal BETWEEN ? AND ?");
if ($stmt_holidays) {
    $start_date_str = $academic_start_date_obj->format('Y-m-d');
    $end_date_str = $academic_end_date_obj->format('Y-m-d');
    $stmt_holidays->bind_param("ss", $start_date_str, $end_date_str);
    $stmt_holidays->execute();
    $result_holidays = $stmt_holidays->get_result();
    while ($row = $result_holidays->fetch_assoc()) {
        $holidays[$row['tanggal']] = $row['keterangan']; // Simpan tanggal sebagai kunci, keterangan sebagai nilai
    }
    $stmt_holidays->close();
}


// Mendapatkan daftar pembayaran siswa yang dipilih untuk tahun akademik ini
$student_payments = [];
if ($selected_siswa_id > 0) {
    // --- PERUBAHAN: Query berdasarkan rentang tanggal tahun akademik ---
    $stmt_payments = $conn->prepare("SELECT tanggal, jumlah FROM pembayaran WHERE id_siswa = ? AND tanggal BETWEEN ? AND ?");
    if ($stmt_payments) {
        $start_date_str = $academic_start_date_obj->format('Y-m-d');
        $end_date_str = $academic_end_date_obj->format('Y-m-d');
        $stmt_payments->bind_param("iss", $selected_siswa_id, $start_date_str, $end_date_str);
        $stmt_payments->execute();
        $result_payments = $stmt_payments->get_result();
        while ($row = $result_payments->fetch_assoc()) {
            $student_payments[$row['tanggal']] = $row['jumlah'];
        }
        $stmt_payments->close();
    }
}

// Fungsi untuk membuat kalender berdasarkan rentang tahun akademik
// Parameter: objek tanggal mulai akademik, objek tanggal akhir akademik, array tanggal libur, array pembayaran siswa
function generateCalendar($academic_start_date_obj, $academic_end_date_obj, $holidays, $student_payments) {
    $html = '';
    $total_spp_due = 0;
    $spp_per_day = 500;

    // Kloning objek tanggal awal akademik untuk iterasi bulanan
    $current_month_iterator = clone $academic_start_date_obj;
    // Tentukan batas akhir iterasi untuk kalender (hingga akhir tahun akademik)
    $end_of_calendar_loop = clone $academic_end_date_obj;
    $end_of_calendar_loop->modify('+1 day'); // Pastikan bulan terakhir disertakan dalam loop

    // Loop untuk setiap bulan dalam rentang tahun akademik
    while ($current_month_iterator < $end_of_calendar_loop) {
        $year = $current_month_iterator->format('Y');
        $month = $current_month_iterator->format('n');
        // Mendapatkan nama bulan (e.g., Januari, Februari)
        $month_name = date('F', mktime(0, 0, 0, $month, 1));
        // Mendapatkan jumlah hari dalam bulan ini
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        // Mendapatkan hari dalam seminggu untuk tanggal 1 bulan ini (0=Minggu, 6=Sabtu)
        $first_day_of_month_w = date('w', strtotime("$year-$month-01"));

        $html .= '<div class="bg-white p-6 rounded-lg shadow-md mb-8 flex flex-col items-center">';
        $html .= '<h3 class="text-xl font-bold text-gray-800 mb-4 text-center">' . $month_name . ' ' . $year . '</h3>';

        // Header hari (Minggu, Senin, dst.)
        $html .= '<div class="grid grid-cols-7 gap-1 text-center font-semibold text-gray-600 w-full mb-2">';
        $html .= '<div>Min</div><div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div>';
        $html .= '</div>';

        // Grid untuk tanggal-tanggal
        $html .= '<div class="grid grid-cols-7 gap-1 text-center w-full">';

        // Mengisi sel kosong di awal bulan agar tanggal 1 berada di posisi yang benar
        for ($i = 0; $i < $first_day_of_month_w; $i++) {
            $html .= '<div class="p-2"></div>'; // Sel kosong
        }

        // Loop untuk setiap hari dalam bulan
        for ($day = 1; $day <= $days_in_month; $day++) {
            $current_date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $day_of_week_w = date('w', strtotime($current_date_str)); // 0=Minggu, 1=Senin, ..., 5=Jumat, 6=Sabtu

            $current_date_obj_check = new DateTime($current_date_str);

            // Cek apakah tanggal berada dalam rentang tahun akademik yang aktif
            if ($current_date_obj_check < $academic_start_date_obj || $current_date_obj_check > $academic_end_date_obj) {
                // Jika di luar tahun akademik, beri gaya abu-abu dan lewati perhitungan SPP
                $day_classes = 'p-2 rounded-md bg-gray-200 text-gray-400 opacity-70 cursor-not-allowed flex flex-col items-center justify-center h-20 w-full';
                $html .= '<div class="' . $day_classes . '">';
                $html .= '<span class="text-lg">' . $day . '</span>';
                $html .= '<span class="text-xs mt-1">Tidak Aktif</span>'; // Atau "Non-aktif"
                $html .= '</div>';
                continue; // Lanjutkan ke hari berikutnya
            }

            $is_friday = ($day_of_week_w == 5); // Cek apakah hari Jumat (index 5)
            $is_holiday_from_db = isset($holidays[$current_date_str]); // Cek apakah tanggal ada di daftar libur dari database
            $holiday_description = $is_holiday_from_db ? $holidays[$current_date_str] : '';

            $is_paid = isset($student_payments[$current_date_str]); // Cek apakah tanggal sudah dibayar

            $day_classes = 'p-2 rounded-md transition duration-300 ease-in-out flex flex-col items-center justify-center h-20 w-full'; // Styling setiap hari

            // Kombinasikan kondisi hari libur: Jumat atau hari libur dari database
            if ($is_friday || $is_holiday_from_db) {
                // Hari Jumat atau hari libur: warna merah, tidak dihitung SPP
                $day_classes .= ' bg-red-500 text-white font-bold';
            } else {
                // Hari biasa (Senin, Selasa, Rabu, Kamis, Sabtu, Minggu): dihitung SPP
                if ($is_paid) {
                    // Jika sudah dibayar
                    $day_classes .= ' bg-green-200 text-green-800';
                } else {
                    // Jika belum dibayar
                    $day_classes .= ' bg-blue-100 hover:bg-blue-200 text-blue-800';
                    $total_spp_due += $spp_per_day; // Tambahkan ke total SPP yang harus dibayar
                }
            }

            $html .= '<div class="' . $day_classes . '">';
            $html .= '<span class="text-lg">' . $day . '</span>'; // Tampilkan nomor hari
            if ($is_friday || $is_holiday_from_db) {
                // Tampilkan keterangan libur jika hari Jumat atau libur dari database
                // Prioritaskan keterangan dari database jika ada
                $display_desc = $is_holiday_from_db ? $holiday_description : 'Hari Jumat';
                $html .= '<span class="text-xs mt-1">' . htmlspecialchars($display_desc) . '</span>';
            } elseif (!$is_paid) {
                // Tampilkan tarif SPP jika bukan hari merah dan belum dibayar
                $html .= '<span class="text-xs mt-1">Rp ' . number_format($spp_per_day, 0, ',', '.') . '</span>';
            }
            $html .= '</div>';
        }
        $html .= '</div>'; // End grid-cols-7
        $html .= '</div>'; // End bg-white div

        // Maju ke bulan berikutnya untuk iterasi kalender
        $current_month_iterator->modify('+1 month');
    }
    return ['html' => $html, 'total_spp' => $total_spp_due];
}

$calendar_data = ['html' => '', 'total_spp' => 0];
if ($selected_siswa_id > 0) {
    // Generate kalender hanya jika siswa sudah dipilih
    $calendar_data = generateCalendar($academic_start_date_obj, $academic_end_date_obj, $holidays, $student_payments);
}

// Mendapatkan daftar biaya lain
$biaya_lain_list = [];
$sql_biaya_lain = "SELECT nama_biaya, jumlah FROM biaya_lain ORDER BY nama_biaya ASC";
$result_biaya_lain = $conn->query($sql_biaya_lain);
if ($result_biaya_lain) { // Pastikan query berhasil dieksekusi
    if ($result_biaya_lain->num_rows > 0) {
        while ($row = $result_biaya_lain->fetch_assoc()) {
            $biaya_lain_list[] = $row;
        }
    }
}


$conn->close(); // Menutup koneksi database
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran SPP Siswa</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Mengatur tinggi maksimum untuk dropdown agar bisa di-scroll */
        select#siswa_select {
            max-height: 200px; /* Atur tinggi maksimum sesuai kebutuhan */
            overflow-y: auto; /* Aktifkan scrollbar vertikal */
            -webkit-overflow-scrolling: touch; /* Untuk smooth scrolling di iOS */
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 leading-normal tracking-normal">
    <div class="container mx-auto p-4 md:p-8">
        <h1 class="text-3xl font-extrabold text-center text-blue-700 mb-8 mt-4 rounded-lg bg-white p-4 shadow-lg">
            Sistem Pembayaran Ikhsan TPQ AISYIAH WULED (tpq 1)
        </h1>

        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Pilih Siswa</h2>
            <form action="index.php" method="GET" class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-4">
                <select name="siswa_id" id="siswa_select" class="flex-grow p-3 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-gray-700">
                    <option value="0">--- Pilih Siswa ---</option>
                    <?php
                    // Pastikan $siswa_list tidak kosong sebelum di-loop
                    if (!empty($siswa_list)) {
                        foreach ($siswa_list as $siswa): ?>
                            <option value="<?= htmlspecialchars($siswa['id_siswa']) ?>"
                                <?= ($selected_siswa_id == $siswa['id_siswa']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($siswa['nama_siswa']) ?>
                            </option>
                        <?php endforeach;
                    } else {
                        echo "<option value='0' disabled>Tidak ada siswa ditemukan.</option>";
                    }
                    ?>
                </select>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md shadow-md transition duration-300 ease-in-out transform hover:scale-105">
                    Calender Pembayaran Ikhsan
                </button>
                <a href="login.php" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-md shadow-md transition duration-300 ease-in-out transform hover:scale-105">
                    Panel Admin
                </a>
            </form>
        </div>

        <?php if ($selected_siswa_id > 0): ?>
            <div class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                    Kalender Pembayaran Ikhsan 1 Tahun  <?= htmlspecialchars($academic_start_year) ?> - <?= htmlspecialchars($academic_end_year) ?>
                    <?php
                        $selected_siswa_name = 'Siswa Tidak Ditemukan';
                        foreach ($siswa_list as $siswa) {
                            if ($siswa['id_siswa'] == $selected_siswa_id) {
                                $selected_siswa_name = $siswa['nama_siswa'];
                                break;
                            }
                        }
                        echo "untuk " . htmlspecialchars($selected_siswa_name);
                    ?>
                </h2>
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <div class="flex flex-wrap justify-between items-center text-sm font-medium mb-4 gap-4">
                        <div class="flex items-center space-x-2">
                            <div class="w-4 h-4 bg-blue-100 rounded-sm border border-blue-300"></div>
                            <span>Hari Biasa (Belum Dibayar)</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-4 h-4 bg-green-200 rounded-sm border border-green-300"></div>
                            <span>Hari Biasa (Sudah Dibayar)</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-4 h-4 bg-red-500 rounded-sm border border-red-600"></div>
                            <span>Hari Jumat / Hari Libur</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-4 h-4 bg-gray-200 rounded-sm border border-gray-300"></div>
                            <span>Di Luar Tahun Akademik</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?= $calendar_data['html'] ?>
                </div>

                <div class="bg-blue-700 text-white p-6 rounded-lg shadow-md mt-8 text-center">
                    <h3 class="text-2xl font-bold mb-2">Total SPP yang Harus Dibayarkan (Tahun Akademik Ini)</h3>
                    <p class="text-4xl font-extrabold">Rp <?= number_format($calendar_data['total_spp'], 0, ',', '.') ?></p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md mt-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Daftar Biaya Lain</h2>
                <?php if (!empty($biaya_lain_list)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white rounded-lg overflow-hidden">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tl-lg">Nama Biaya</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tr-lg">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($biaya_lain_list as $biaya): ?>
                                    <tr>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($biaya['nama_biaya']) ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800">Rp <?= number_format($biaya['jumlah'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">Belum ada biaya lain yang ditambahkan.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-lg shadow-md" role="alert">
                <p class="font-bold">Selamat Datang!</p>
                <p>Silakan pilih siswa dari daftar di atas untuk melihat kalender pembayaran SPP dan biaya lainnya.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
