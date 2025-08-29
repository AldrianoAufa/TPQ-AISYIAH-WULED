 <?php
// index.php - Halaman User

session_start(); // Mulai sesi

include 'db_connect.php'; // Memasukkan file koneksi database

// Periksa apakah siswa sudah login
if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
    // Jika belum login, arahkan ke halaman login
    header("Location: login.php");
    exit();
}

// Ambil ID dan nama siswa dari sesi
$selected_siswa_id = $_SESSION['student_id'];
$selected_siswa_name = $_SESSION['student_name'];
$current_spp_type = isset($_GET['spp_type']) ? $_GET['spp_type'] : 'spp1'; // 'spp1' or 'spp2' or 'biaya_lain'

// --- Penentuan Tahun Akademik ---
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

// Mendapatkan daftar tanggal libur dan keterangannya dari database untuk tahun akademik ini
$holidays = [];
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

// --- Fungsi untuk SPP Pertama (Harian) ---
// Mendapatkan daftar pembayaran siswa yang dipilih untuk tahun akademik ini
$student_payments_spp1 = [];
if ($selected_siswa_id > 0) { // Tidak perlu cek $current_spp_type di sini, ambil saja semua data yang relevan
    $stmt_payments = $conn->prepare("SELECT tanggal, jumlah FROM pembayaran WHERE id_siswa = ? AND tanggal BETWEEN ? AND ?");
    if ($stmt_payments) {
        $start_date_str = $academic_start_date_obj->format('Y-m-d');
        $end_date_str = $academic_end_date_obj->format('Y-m-d');
        $stmt_payments->bind_param("iss", $selected_siswa_id, $start_date_str, $end_date_str);
        $stmt_payments->execute();
        $result_payments = $stmt_payments->get_result();
        while ($row = $result_payments->fetch_assoc()) {
            $student_payments_spp1[$row['tanggal']] = $row['jumlah'];
        }
        $stmt_payments->close();
    }
}

// Fungsi untuk format tanggal ke Bahasa Indonesia dengan hari
function format_date_indo($date_str)
{
    $hari = array(1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu');
    $bulan = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $pecahkan = explode('-', $date_str);
    $num_day_of_week = date('N', strtotime($date_str)); // 1 (for Monday) through 7 (for Sunday)
    $day_name = $hari[$num_day_of_week];
    return $day_name . ', ' . $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}

function getSemesterRanges($academic_start_year, $academic_end_year)
{
    return [
        [
            'label' => 'Semester 1 (Juli - Desember)',
            'start' => new DateTime("$academic_start_year-07-01"),
            'end'   => new DateTime("$academic_start_year-12-31"),
        ],
        [
            'label' => 'Semester 2 (Januari - Juni)',
            'start' => new DateTime("$academic_end_year-01-01"),
            'end'   => new DateTime("$academic_end_year-06-30"),
        ],
    ];
}

// Fungsi untuk membuat kalender SPP Pertama (Harian)
function generateCalendarSPP1($semester_start, $semester_end, $holidays, $student_payments)
{
    $html = '';
    $total_spp_due = 0;
    $spp_per_day = 500;

    $current_month_iterator = clone $semester_start;
    $end_of_calendar_loop = clone $semester_end;
    $end_of_calendar_loop->modify('+1 day');

    while ($current_month_iterator < $end_of_calendar_loop) {
        $year = $current_month_iterator->format('Y');
        $month = $current_month_iterator->format('n');
        $month_name_en = date('F', mktime(0, 0, 0, $month, 1));
        $month_names_id = [
            'January' => 'Januari',
            'February' => 'Februari',
            'March' => 'Maret',
            'April' => 'April',
            'May' => 'Mei',
            'June' => 'Juni',
            'July' => 'Juli',
            'August' => 'Agustus',
            'September' => 'September',
            'October' => 'Oktober',
            'November' => 'November',
            'December' => 'Desember'
        ];
        $month_name_id = $month_names_id[$month_name_en];

        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $first_day_of_month_w = date('w', strtotime("$year-$month-01"));

        $html .= '<div class="bg-white p-6 rounded-lg shadow-md mb-8 flex flex-col items-center">';
        $html .= '<h3 class="text-xl font-bold text-gray-800 mb-4 text-center">' . $month_name_id . ' ' . $year . '</h3>';
        $html .= '<div class="grid grid-cols-7 gap-1 text-center font-semibold text-gray-600 w-full mb-2">';
        $html .= '<div>Min</div><div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div>';
        $html .= '</div>';
        $html .= '<div class="grid grid-cols-7 gap-1 text-center w-full">';

        for ($i = 0; $i < $first_day_of_month_w; $i++) {
            $html .= '<div class="p-2"></div>';
        }

        for ($day = 1; $day <= $days_in_month; $day++) {
            $current_date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $day_of_week_w = date('w', strtotime($current_date_str));
            $current_date_obj_check = new DateTime($current_date_str);

            if ($current_date_obj_check < $semester_start || $current_date_obj_check > $semester_end) {
                $day_classes = 'calendar-cell inactive';
                $html .= '<div class="' . $day_classes . '">';
                $html .= '<span class="text-lg">' . $day . '</span>';
                $html .= '<span class="text-xs mt-1">Tidak Aktif</span>';
                $html .= '</div>';
                continue;
            }

            $is_friday = ($day_of_week_w == 5);
            $is_holiday_from_db = isset($holidays[$current_date_str]);
            $holiday_description = $is_holiday_from_db ? $holidays[$current_date_str] : '';
            $is_paid = isset($student_payments[$current_date_str]);
            $day_classes = 'calendar-cell';

            if ($is_friday || $is_holiday_from_db) {
                $day_classes .= ' friday-holiday';
            } else {
                if ($is_paid) {
                    $day_classes .= ' paid';
                } else {
                    $day_classes .= ' unpaid';
                    $total_spp_due += $spp_per_day;
                }
            }

            $html .= '<div class="' . $day_classes . '">';
            $html .= '<span class="text-lg">' . $day . '</span>';
            if ($is_friday || $is_holiday_from_db) {
                $display_desc = $is_holiday_from_db ? $holiday_description : 'Hari Jumat';
                $html .= '<span class="text-xs mt-1">' . htmlspecialchars($display_desc) . '</span>';
            } elseif (!$is_paid) {
                $html .= '<span class="text-xs mt-1">Rp ' . number_format($spp_per_day, 0, ',', '.') . '</span>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';
        $current_month_iterator->modify('+1 month');
    }
    return ['html' => $html, 'total_spp' => $total_spp_due];
}

// --- Fungsi untuk SPP Kedua (Mingguan) ---
$student_payments_spp2 = [];
if ($selected_siswa_id > 0) { // Tidak perlu cek $current_spp_type di sini
    $stmt_payments_spp2 = $conn->prepare("SELECT tanggal_mulai_minggu, jumlah FROM pembayaran_spp_kedua WHERE id_siswa = ? AND tahun_akademik_mulai = ? ORDER BY tanggal_mulai_minggu ASC");
    if ($stmt_payments_spp2) {
        $stmt_payments_spp2->bind_param("ii", $selected_siswa_id, $academic_start_year);
        $stmt_payments_spp2->execute();
        $result_payments_spp2 = $stmt_payments_spp2->get_result();
        while ($row = $result_payments_spp2->fetch_assoc()) {
            $student_payments_spp2[$row['tanggal_mulai_minggu']] = $row['jumlah'];
        }
        $stmt_payments_spp2->close();
    }
}

// Fungsi untuk membuat kalender SPP Kedua (Mingguan)
function generateCalendarSPP2($semester_start, $semester_end, $student_payments_spp2)
{
    $spp_per_week = 5000;
    $total_spp_due = 0;
    $monthly_weeks_data = [];

    $month_names_id = [
        'January' => 'Januari',
        'February' => 'Februari',
        'March' => 'Maret',
        'April' => 'April',
        'May' => 'Mei',
        'June' => 'Juni',
        'July' => 'Juli',
        'August' => 'Agustus',
        'September' => 'September',
        'October' => 'Oktober',
        'November' => 'November',
        'December' => 'Desember'
    ];

    $current_month_init = clone $semester_start;
    while ($current_month_init <= $semester_end) {
        $month_name_en = $current_month_init->format('F');
        $month_name_id = $month_names_id[$month_name_en];
        $monthly_weeks_data[$month_name_id] = [];
        $current_month_init->modify('+1 month');
    }

    $current_week_start_date_iterator = clone $semester_start;
    if ($current_week_start_date_iterator->format('N') != 1) { // Jika bukan Senin
        $current_week_start_date_iterator->modify('last Monday');
    }

    while ($current_week_start_date_iterator <= $semester_end) {
        $week_start_date_str = $current_week_start_date_iterator->format('Y-m-d');
        $week_end_date_obj = clone $current_week_start_date_iterator;
        $week_end_date_obj->modify('+6 days');

        // Pastikan minggu tidak keluar dari semester (baik awal maupun akhir)
        if ($week_start_date_str < $semester_start->format('Y-m-d') || $week_end_date_obj > $semester_end) {
            $current_week_start_date_iterator->modify('+1 week');
            continue;
        }

        $month_of_week_start_en = date('F', strtotime($week_start_date_str));
        $month_of_week_start_id = $month_names_id[$month_of_week_start_en];

        if (!isset($monthly_weeks_data[$month_of_week_start_id])) {
            $monthly_weeks_data[$month_of_week_start_id] = [];
        }

        $monthly_weeks_data[$month_of_week_start_id][] = [
            'start_date' => $week_start_date_str,
            'end_date' => $week_end_date_obj->format('Y-m-d'),
            'is_paid' => isset($student_payments_spp2[$week_start_date_str])
        ];

        $current_week_start_date_iterator->modify('+1 week');
    }

    $html = '<div class="flex flex-col lg:flex-row gap-8">';
    $render_table = function ($months, $monthly_weeks_data, $spp_per_week, &$total_spp_due_ref) use ($semester_end, $month_names_id) {
        $table_html = '<div class="flex-1 overflow-x-auto bg-white rounded-lg shadow-md p-4">';
        $table_html .= '<table class="min-w-full border-collapse weekly-spp-table">';
        $table_html .= '<thead>';
        $table_html .= '<tr>';
        $table_html .= '<th class="py-3 px-3 text-left text-base font-semibold text-gray-700 uppercase border-b border-gray-300 rounded-tl-lg">Bulan</th>';
        $table_html .= '<th class="py-3 px-3 text-center text-base font-semibold text-gray-700 uppercase border-b border-gray-300">I</th>';
        $table_html .= '<th class="py-3 px-3 text-center text-base font-semibold text-gray-700 uppercase border-b border-gray-300">II</th>';
        $table_html .= '<th class="py-3 px-3 text-center text-base font-semibold text-gray-700 uppercase border-b border-gray-300">III</th>';
        $table_html .= '<th class="py-3 px-3 text-center text-base font-semibold text-gray-700 uppercase border-b border-gray-300">IV</th>';
        $table_html .= '<th class="py-3 px-3 text-center text-base font-semibold text-gray-700 uppercase border-b border-gray-300 rounded-tr-lg">V</th>';
        $table_html .= '</tr>';
        $table_html .= '</thead>';
        $table_html .= '<tbody>';

        foreach ($months as $month_name_id) {
            $table_html .= '<tr>';
            $table_html .= '<td class="py-4 px-3 whitespace-nowrap font-semibold text-gray-800 border-b border-gray-200 text-base">' . htmlspecialchars($month_name_id) . '</td>';
            $weeks_in_month = $monthly_weeks_data[$month_name_id] ?? [];
            for ($i = 0; $i < 5; $i++) {
                $week = $weeks_in_month[$i] ?? null;
                $cell_classes = 'py-3 px-2 text-center border-b border-gray-200 weekly-spp-cell';
                $content = '';

                if ($month_name_id === 'Juni' && $i === 4) {
                    $week = null;
                }

                if ($week) {
                    $start_date_obj = new DateTime($week['start_date']);
                    $end_date_obj = new DateTime($week['end_date']);
                    $start_day = $start_date_obj->format('j');
                    $start_month_id = $month_names_id[$start_date_obj->format('F')];
                    $start_year = $start_date_obj->format('Y');
                    $end_day = $end_date_obj->format('j');
                    $end_month_id = $month_names_id[$end_date_obj->format('F')];
                    $end_year = $end_date_obj->format('Y');
                    $formatted_date_range = $start_day . ' ' . $start_month_id;
                    if ($start_month_id != $end_month_id) {
                        $formatted_date_range .= ' - ' . $end_day . ' ' . $end_month_id;
                    } else {
                        $formatted_date_range .= ' - ' . $end_day;
                    }
                    $formatted_date_range .= ' ' . $end_year;

                    if ($week['is_paid']) {
                        $cell_classes .= ' bg-green-200 text-green-800';
                        $content = '<span class="font-medium text-sm">' . $formatted_date_range . '</span><br><span class="text-xs">Sudah Bayar</span>';
                    } else {
                        $cell_classes .= ' bg-blue-100 text-blue-800';
                        $content = '<span class="font-medium text-sm">' . $formatted_date_range . '</span><br><span class="text-xs"></span>';
                        $total_spp_due_ref += $spp_per_week;
                    }
                } else {
                    $cell_classes .= ' bg-gray-100 text-gray-400';
                    $content = '<span class="text-sm">N/A</span>';
                }
                $table_html .= '<td class="' . $cell_classes . '">' . $content . '</td>';
            }
            $table_html .= '</tr>';
        }

        $table_html .= '</tbody>';
        $table_html .= '</table>';
        $table_html .= '</div>';
        return $table_html;
    };

    $all_months = array_keys($monthly_weeks_data);
    $html .= $render_table($all_months, $monthly_weeks_data, $spp_per_week, $total_spp_due);
    $html .= '</div>';

    return ['html' => $html, 'total_spp' => $total_spp_due];
}

// --- Data Biaya Lain ---
$biaya_lain_list = [];
$total_biaya_lain_due = 0;
$biaya_lain_payments_summary = []; // Untuk menyimpan total pembayaran per jenis biaya lain

// 1. Ambil daftar semua jenis biaya lain
// Menggunakan 'id_biaya' sesuai skema database Anda
$sql_biaya_lain = "SELECT id_biaya, nama_biaya, jumlah FROM biaya_lain ORDER BY nama_biaya ASC";
$result_biaya_lain = $conn->query($sql_biaya_lain);
if ($result_biaya_lain) {
    if ($result_biaya_lain->num_rows > 0) {
        while ($row = $result_biaya_lain->fetch_assoc()) {
            // Menggunakan 'id_biaya' sebagai kunci array
            $biaya_lain_list[$row['id_biaya']] = $row; // Simpan dengan ID sebagai kunci untuk akses mudah
        }
    }
}

// 2. Ambil semua pembayaran biaya lain untuk siswa yang login
$student_biaya_lain_payments = [];
if ($selected_siswa_id > 0) {
    // Menggunakan 'jumlah_dibayar' sesuai struktur database Anda
    $stmt_biaya_lain_payments = $conn->prepare("SELECT id_biaya, jumlah_dibayar FROM pembayaran_biaya_lain WHERE id_siswa = ?");
    if ($stmt_biaya_lain_payments) {
        $stmt_biaya_lain_payments->bind_param("i", $selected_siswa_id);
        $stmt_biaya_lain_payments->execute();
        $result_biaya_lain_payments = $stmt_biaya_lain_payments->get_result();
        while ($row = $result_biaya_lain_payments->fetch_assoc()) {
            $student_biaya_lain_payments[] = $row;
        }
        $stmt_biaya_lain_payments->close();
    }
}

// 3. Hitung total pembayaran per jenis biaya lain
foreach ($student_biaya_lain_payments as $payment) {
    $id_biaya = $payment['id_biaya'];
    $jumlah_dibayar = $payment['jumlah_dibayar']; // Menggunakan 'jumlah_dibayar' sesuai skema

    if (!isset($biaya_lain_payments_summary[$id_biaya])) {
        $biaya_lain_payments_summary[$id_biaya] = 0;
    }
    $biaya_lain_payments_summary[$id_biaya] += $jumlah_dibayar;
}

// 4. Gabungkan data dan tentukan status
$biaya_lain_display_list = [];
foreach ($biaya_lain_list as $id => $biaya) { // Menggunakan $id untuk ID yang benar
    $total_paid = $biaya_lain_payments_summary[$id] ?? 0;
    $remaining_due = $biaya['jumlah'] - $total_paid;
    $status = ($remaining_due <= 0) ? 'Lunas' : 'Belum Lunas';

    $biaya_lain_display_list[] = [
        'nama_biaya' => $biaya['nama_biaya'],
        'jumlah' => $biaya['jumlah'],
        'total_paid' => $total_paid,
        'remaining_due' => max(0, $remaining_due), // Pastikan tidak negatif
        'status' => $status
    ];

    // Tambahkan ke total kekurangan global jika belum lunas
    if ($status == 'Belum Lunas') {
        $total_biaya_lain_due += max(0, $remaining_due);
    }
}


$calendar_data_spp1 = ['html' => '', 'total_spp' => 0];
$calendar_data_spp2 = ['html' => '', 'total_spp' => 0];

// Hanya generate data kalender jika tab yang relevan aktif
if ($selected_siswa_id > 0) {
    if ($current_spp_type == 'spp1') {
        $calendar_data_spp1 = generateCalendarSPP1($academic_start_date_obj, $academic_end_date_obj, $holidays, $student_payments_spp1);
    } elseif ($current_spp_type == 'spp2') {
        $calendar_data_spp2 = generateCalendarSPP2($academic_start_date_obj, $academic_end_date_obj, $student_payments_spp2);
    }
}

// Hitung total kekurangan keseluruhan
$total_kekurangan_global = 0;
if ($current_spp_type == 'spp1') {
    $total_kekurangan_global = $calendar_data_spp1['total_spp'];
} elseif ($current_spp_type == 'spp2') {
    $total_kekurangan_global = $calendar_data_spp2['total_spp'];
} elseif ($current_spp_type == 'biaya_lain') {
    $total_kekurangan_global = $total_biaya_lain_due;
}


$conn->close(); // Menutup koneksi database
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>TPQ Aisyiyah Wuled</title>
    <meta name="description" content="Website resmi TPQ Aisyiyah Wuled. Sistem pembayaran SPP, informasi siswa, biaya lain, jadwal kegiatan, dan profil TPQ Aisyiyah Wuled.">
    <meta name="keywords" content="TPQ, Aisyiyah Wuled, SPP, pembayaran, sekolah, aisyiyah, infaq, ikhsan, pendidikan, Islam">
    <meta name="author" content="TPQ Aisyiyah Wuled">
    <meta property="og:title" content="TPQ Aisyiyah Wuled - Sistem Pembayaran SPP Siswa" />
    <meta property="og:description" content="Website resmi TPQ Aisyiyah Wuled untuk pembayaran SPP, informasi siswa, biaya lain, jadwal kegiatan, dan profil TPQ Aisyiyah Wuled." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://tpq-aisyiah-wuled.wuaze.com/" />
    <meta property="og:image" content="https://tpq-aisyiah-wuled.wuaze.com/assets/logo.png" />
    <link rel="canonical" href="https://tpq-aisyiah-wuled.wuaze.com/"/>
    <link rel="icon" href="assets/logo.png" type="image/png">
    <meta name="google-site-verification" content="kNC0ggF_TivAWU0iFyc9W16pFvffcmfEhMhcoC__beg" />
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <!-- Font Awesome untuk ikon -->
    <link rel="icon" type="image/png" href="/assets/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="index.css">
</head>

<body class="bg-gray-100 text-gray-900 leading-normal tracking-normal">
    <div class="container mx-auto p-4 md:p-8"> <!-- Mengatur max-width container utama -->

        <!-- Top Header Card -->
        <div class="header-card mb-8">
            <p class="title-arabic"> مَرْحَبًا </p>
            <h1 class="text-lg font-bold text-teal-700 mb-2">Sistem Pembayaran SPP</h1>
            <h1 class="text-2xl md:text-3xl text-teal-700">TPQ AISYIYAH WULED</h1>
            <p class="text-2xl md:text-3xl text-gray-600">Selamat Datang wali, <span class="font-semibold text-teal-700"><?= htmlspecialchars($selected_siswa_name) ?></span></p>
            <p class="text-md text-gray-600 p-3">Tahun Akademik: <span class="font-semibold text-teal-700"><?= $academic_start_year ?>/<?= $academic_end_year ?></span></p>
            <a href="logout.php" class="logout-button inline-flex items-center"
                onclick="return confirm('Apakah Anda yakin ingin logout?');">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </div>

        <!-- Total Kekurangan Pembayaran Card -->
        <div class="total-kekurangan-card mb-8">
            <h2 class="text-xl font-bold mb-1"> Kekurangan </h2>
            <p class="flex flex-col md:flex-row justify-center items-center gap-3 mb-2">
                <?php if ($current_spp_type == 'spp1' || $current_spp_type == 'spp2'): ?>
                    <?php
                    $semester_ranges = getSemesterRanges($academic_start_year, $academic_end_year);
                    $semester_kekurangan = [];
                    foreach ($semester_ranges as $semester) {
                        if ($current_spp_type == 'spp1') {
                            $calendar_data = generateCalendarSPP1($semester['start'], $semester['end'], $holidays, $student_payments_spp1);
                        } else {
                            $calendar_data = generateCalendarSPP2($semester['start'], $semester['end'], $student_payments_spp2);
                        }
                        $semester_kekurangan[] = [
                            'label' => $semester['label'],
                            'total' => $calendar_data['total_spp']
                        ];
                    }
                    ?>
                    <?php foreach ($semester_kekurangan as $sem): ?>
                        <span class="px-6 py-2 rounded-xl font-bold text-white text-lg shadow-lg text-center"
                            >
                            <?= $sem['label'] ?>: Rp <?= number_format($sem['total'], 0, ',', '.') ?>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </p>
        </div>

        <!-- SPP Type Tabs -->
        <div class="card-bg rounded-t-lg shadow-md flex flex-wrap justify-center gap-2 md:gap-4 p-4 mb-0">
            <a href="?spp_type=spp1"
                class="tab-button w-full sm:w-auto <?= $current_spp_type == 'spp1' ? 'active' : '' ?>">
                SPP Harian (Ikhsan)
            </a>
            <a href="?spp_type=spp2"
                class="tab-button w-full sm:w-auto <?= $current_spp_type == 'spp2' ? 'active' : '' ?>">
                SPP Mingguan (Infaq)
            </a>
            <a href="?spp_type=biaya_lain"
                class="tab-button w-full sm:w-auto <?= $current_spp_type == 'biaya_lain' ? 'active' : '' ?>">
                Biaya Lain
            </a>
        </div>

        <!-- Content Area based on Tab -->
        <div class="card p-6 mb-8">
            <?php if ($current_spp_type == 'spp1'): ?>
                <h2 class="text-xl font-bold text-gray-800 mb-4 text-center">Kalender Pembayaran Ikhsan</h2>
                <div class="flex flex-wrap justify-center items-center text-sm font-medium mb-6 gap-x-6 gap-y-2">
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 rounded-sm bg-blue-100 border border-blue-300"></div>
                        <span>Hari Biasa (Belum Dibayar)</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 rounded-sm bg-green-200 border border-green-300"></div>
                        <span>Hari Biasa (Sudah Dibayar)</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 rounded-sm bg-red-500 border border-red-600"></div>
                        <span>Hari Jumat / Hari Libur</span>
                    </div>
                </div>
                <?php
                $semester_ranges = getSemesterRanges($academic_start_year, $academic_end_year);
                foreach ($semester_ranges as $semester) {
                    $calendar_data = generateCalendarSPP1($semester['start'], $semester['end'], $holidays, $student_payments_spp1);
                ?>
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold text-lg"><?= $semester['label'] ?></span>
                            <span class="font-bold text-teal-700">Kekurangan Semester: Rp <?= number_format($calendar_data['total_spp'], 0, ',', '.') ?></span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?= $calendar_data['html'] ?>
                        </div>
                    </div>
                <?php } ?>
            <?php elseif ($current_spp_type == 'spp2'): ?>
                <h2 class="text-xl font-bold text-gray-800 mb-4 text-center">Kalender Pembayaran Infaq</h2>
                <div class="flex flex-wrap justify-center items-center text-sm font-medium mb-6 gap-x-6 gap-y-2">
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 rounded-sm bg-blue-100 border border-blue-300"></div>
                        <span>Minggu Belum Dibayar</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 rounded-sm bg-green-200 border border-green-300"></div>
                        <span>Minggu Sudah Dibayar</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="font-bold text-gray-600">Catatan:</span>
                        <span>Minggu dihitung dari Minggu-Sabtu.</span>
                    </div>
                </div>
                <?php
                $semester_ranges = getSemesterRanges($academic_start_year, $academic_end_year);
                foreach ($semester_ranges as $semester) {
                    $calendar_data = generateCalendarSPP2($semester['start'], $semester['end'], $student_payments_spp2);
                ?>
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold text-lg"><?= $semester['label'] ?></span>
                            <span class="font-bold text-teal-700">Kekurangan Semester: Rp <?= number_format($calendar_data['total_spp'], 0, ',', '.') ?></span>
                        </div>
                        <?= $calendar_data['html'] ?>
                    </div>
                <?php } ?>
            <?php elseif ($current_spp_type == 'biaya_lain'): ?>
                <h2 class="text-xl font-bold text-gray-800 mb-4 text-center">Daftar Biaya Lain</h2>
                <?php if (!empty($biaya_lain_display_list)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full biaya-lain-table rounded-lg overflow-hidden">
                            <thead>
                                <tr>
                                    <th class="rounded-tl-lg">Nama Biaya</th>
                                    <th>Total Biaya</th>
                                    <th>Sudah Dibayar</th>
                                    <th>Kekurangan</th>
                                    <th class="rounded-tr-lg">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($biaya_lain_display_list as $biaya): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($biaya['nama_biaya']) ?></td>
                                        <td>Rp <?= number_format($biaya['jumlah'], 0, ',', '.') ?></td>
                                        <td>Rp <?= number_format($biaya['total_paid'], 0, ',', '.') ?></td>
                                        <td class="<?= $biaya['status'] == 'Belum Lunas' ? 'text-red-600 font-semibold' : 'text-green-600' ?>">
                                            Rp <?= number_format($biaya['remaining_due'], 0, ',', '.') ?>
                                        </td>
                                        <td class="<?= $biaya['status'] == 'Belum Lunas' ? 'text-red-600 font-semibold' : 'text-green-600' ?>">
                                            <?= htmlspecialchars($biaya['status']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600 text-center">Belum ada biaya lain yang ditambahkan.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html