<?php
// admin.php - Halaman Admin
session_start(); // Mulai sesi

// Periksa apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Jika belum login, arahkan ke halaman login
    header("Location: login.php");
    exit();
}

include 'db_connect.php'; // Memasukkan file koneksi database

$message = '';
$message_type = ''; // success or error

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_unset(); // Hapus semua variabel sesi
    session_destroy(); // Hancurkan sesi
    header("Location: login.php"); // Arahkan kembali ke halaman login
    exit();
}

// --- Penentuan Tahun Akademik untuk Admin ---
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

$academic_start_date_str = $academic_start_date_obj->format('Y-m-d');
$academic_end_date_str = $academic_end_date_obj->format('Y-m-d');


// --- FUNGSI CRUD SISWA ---
if (isset($_POST['add_siswa'])) {
    $nama_siswa = trim($_POST['nama_siswa']);
    if (!empty($nama_siswa)) {
        $stmt = $conn->prepare("INSERT INTO siswa (nama_siswa) VALUES (?)");
        $stmt->bind_param("s", $nama_siswa);
        if ($stmt->execute()) {
            $message = "Siswa berhasil ditambahkan!";
            $message_type = "success";
        } else {
            $message = "Error menambah siswa: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "Nama siswa tidak boleh kosong.";
        $message_type = "error";
    }
}

if (isset($_POST['edit_siswa'])) {
    $id_siswa = intval($_POST['id_siswa']);
    $nama_siswa = trim($_POST['nama_siswa']);
    if ($id_siswa > 0 && !empty($nama_siswa)) {
        $stmt = $conn->prepare("UPDATE siswa SET nama_siswa = ? WHERE id_siswa = ?");
        $stmt->bind_param("si", $nama_siswa, $id_siswa);
        if ($stmt->execute()) {
            $message = "Siswa berhasil diperbarui!";
            $message_type = "success";
        } else {
            $message = "Error memperbarui siswa: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "ID siswa atau nama siswa tidak valid.";
        $message_type = "error";
    }
}

if (isset($_GET['delete_siswa'])) {
    $id_siswa = intval($_GET['delete_siswa']);
    if ($id_siswa > 0) {
        $stmt = $conn->prepare("DELETE FROM siswa WHERE id_siswa = ?");
        $stmt->bind_param("i", $id_siswa);
        if ($stmt->execute()) {
            $message = "Siswa berhasil dihapus!";
            $message_type = "success";
        } else {
            $message = "Error menghapus siswa: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

// --- FUNGSI CRUD BIAYA LAIN ---
if (isset($_POST['add_biaya_lain'])) {
    $nama_biaya = trim($_POST['nama_biaya']);
    $jumlah = intval($_POST['jumlah']);
    if (!empty($nama_biaya) && $jumlah > 0) {
        $stmt = $conn->prepare("INSERT INTO biaya_lain (nama_biaya, jumlah) VALUES (?, ?)");
        $stmt->bind_param("si", $nama_biaya, $jumlah);
        if ($stmt->execute()) {
            $message = "Biaya lain berhasil ditambahkan!";
            $message_type = "success";
        } else {
            $message = "Error menambah biaya lain: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "Nama biaya dan jumlah tidak boleh kosong atau tidak valid.";
        $message_type = "error";
    }
}

if (isset($_POST['edit_biaya_lain'])) {
    $id_biaya = intval($_POST['id_biaya']);
    $nama_biaya = trim($_POST['nama_biaya']);
    $jumlah = intval($_POST['jumlah']);
    if ($id_biaya > 0 && !empty($nama_biaya) && $jumlah > 0) {
        $stmt = $conn->prepare("UPDATE biaya_lain SET nama_biaya = ?, jumlah = ? WHERE id_biaya = ?");
        $stmt->bind_param("sii", $nama_biaya, $jumlah, $id_biaya);
        if ($stmt->execute()) {
            $message = "Biaya lain berhasil diperbarui!";
            $message_type = "success";
        } else {
            $message = "Error memperbarui biaya lain: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = "ID biaya, nama biaya, atau jumlah tidak valid.";
        $message_type = "error";
    }
}

if (isset($_GET['delete_biaya_lain'])) {
    $id_biaya = intval($_GET['delete_biaya_lain']);
    if ($id_biaya > 0) {
        $stmt = $conn->prepare("DELETE FROM biaya_lain WHERE id_biaya = ?");
        $stmt->bind_param("i", $id_biaya);
        if ($stmt->execute()) {
            $message = "Biaya lain berhasil dihapus!";
            $message_type = "success";
        } else {
            $message = "Error menghapus biaya lain: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

// --- FUNGSI CRUD LIBUR --- (Individual, existing)
if (isset($_POST['add_libur'])) {
    $tanggal = trim($_POST['tanggal']);
    $keterangan = trim($_POST['keterangan']);
    if (!empty($tanggal) && !empty($keterangan)) {
        // Cek apakah tanggal sudah ada
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM libur WHERE tanggal = ?");
        $stmt_check->bind_param("s", $tanggal);
        $stmt_check->execute();
        $stmt_check->bind_result($count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($count > 0) {
            $message = "Tanggal libur sudah ada.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO libur (tanggal, keterangan) VALUES (?, ?)");
            $stmt->bind_param("ss", $tanggal, $keterangan);
            if ($stmt->execute()) {
                $message = "Hari libur berhasil ditambahkan!";
                $message_type = "success";
            } else {
                $message = "Error menambah hari libur: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    } else {
        $message = "Tanggal dan keterangan tidak boleh kosong.";
        $message_type = "error";
    }
}

if (isset($_POST['edit_libur'])) {
    $id_libur = intval($_POST['id_libur']);
    $tanggal = trim($_POST['tanggal']);
    $keterangan = trim($_POST['keterangan']);
    if ($id_libur > 0 && !empty($tanggal) && !empty($keterangan)) {
        // Cek apakah tanggal sudah ada untuk ID lain
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM libur WHERE tanggal = ? AND id_libur != ?");
        $stmt_check->bind_param("si", $tanggal, $id_libur);
        $stmt_check->execute();
        $stmt_check->bind_result($count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($count > 0) {
            $message = "Tanggal libur sudah ada untuk hari libur lain.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("UPDATE libur SET tanggal = ?, keterangan = ? WHERE id_libur = ?");
            $stmt->bind_param("ssi", $tanggal, $keterangan, $id_libur);
            if ($stmt->execute()) {
                $message = "Hari libur berhasil diperbarui!";
                $message_type = "success";
            } else {
                $message = "Error memperbarui hari libur: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    } else {
        $message = "ID libur, tanggal, atau keterangan tidak valid.";
        $message_type = "error";
    }
}

if (isset($_GET['delete_libur'])) {
    $id_libur = intval($_GET['delete_libur']);
    if ($id_libur > 0) {
        $stmt = $conn->prepare("DELETE FROM libur WHERE id_libur = ?");
        $stmt->bind_param("i", $id_libur);
        if ($stmt->execute()) {
            $message = "Hari libur berhasil dihapus!";
            $message_type = "success";
        } else {
            $message = "Error menghapus hari libur: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}


// --- FUNGSI TAMBAH LIBUR PER RENTANG ---
if (isset($_POST['add_libur_range'])) {
    $tanggal_mulai = trim($_POST['tanggal_mulai_libur']);
    $tanggal_akhir = trim($_POST['tanggal_akhir_libur']);
    $keterangan_libur_range = trim($_POST['keterangan_libur_range']);

    if (empty($tanggal_mulai) || empty($tanggal_akhir) || empty($keterangan_libur_range)) {
        $message = "Semua field untuk hari libur rentang harus diisi.";
        $message_type = "error";
    } elseif (strtotime($tanggal_mulai) > strtotime($tanggal_akhir)) {
        $message = "Tanggal mulai tidak boleh lebih besar dari tanggal akhir.";
        $message_type = "error";
    } else {
        $start_date = new DateTime($tanggal_mulai);
        $end_date = new DateTime($tanggal_akhir);
        $interval = new DateInterval('P1D'); // Interval 1 hari
        $period = new DatePeriod($start_date, $interval, $end_date->modify('+1 day')); // Tambah 1 hari untuk menyertakan tanggal akhir

        $added_count = 0;
        $skipped_count = 0;

        foreach ($period as $date) {
            $current_date = $date->format('Y-m-d');

            // Cek apakah tanggal sudah ada di tabel libur
            $stmt_check_libur = $conn->prepare("SELECT COUNT(*) FROM libur WHERE tanggal = ?");
            $stmt_check_libur->bind_param("s", $current_date);
            $stmt_check_libur->execute();
            $stmt_check_libur->bind_result($count_libur);
            $stmt_check_libur->fetch();
            $stmt_check_libur->close();

            if ($count_libur == 0) { // Jika belum ada, masukkan
                $stmt_insert_libur = $conn->prepare("INSERT INTO libur (tanggal, keterangan) VALUES (?, ?)");
                $stmt_insert_libur->bind_param("ss", $current_date, $keterangan_libur_range);
                if ($stmt_insert_libur->execute()) {
                    $added_count++;
                } else {
                    // Log error tapi jangan hentikan proses jika ada error insert
                    // error_log("Error inserting holiday for date " . $current_date . ": " . $stmt_insert_libur->error);
                }
                $stmt_insert_libur->close();
            } else {
                $skipped_count++;
            }
        }
        $message = "Penambahan hari libur rentang selesai. Ditambahkan: {$added_count} hari, Dilewati (sudah ada): {$skipped_count} hari.";
        $message_type = "success";
    }
}


// --- FUNGSI BAYAR SPP BERDASARKAN JUMLAH UANG ---
if (isset($_POST['pay_spp_by_amount'])) {
    $id_siswa_pay = intval($_POST['id_siswa_pay']);
    $amount_received = intval($_POST['jumlah_pembayaran']);
    $spp_per_day_fixed = 500; // Tarif SPP per hari

    if ($id_siswa_pay <= 0 || $amount_received <= 0) {
        $message = "Pilih siswa dan masukkan jumlah pembayaran yang valid (lebih dari Rp 0).";
        $message_type = "error";
    } else {
        // Hitung berapa hari yang bisa dibayar
        $days_to_mark_paid = floor($amount_received / $spp_per_day_fixed);
        $remaining_amount = $amount_received % $spp_per_day_fixed;

        if ($days_to_mark_paid == 0) {
            $message = "Jumlah pembayaran Rp " . number_format($amount_received, 0, ',', '.') . " tidak cukup untuk satu hari SPP (Rp " . number_format($spp_per_day_fixed, 0, ',', '.') . ").";
            $message_type = "error";
        } else {
            // 1. Dapatkan semua tanggal libur untuk tahun akademik ini
            $holidays_in_academic_year = [];
            // --- Menggunakan rentang tanggal tahun akademik ---
            $stmt_holidays_pay = $conn->prepare("SELECT tanggal FROM libur WHERE tanggal BETWEEN ? AND ?");
            if ($stmt_holidays_pay) {
                $stmt_holidays_pay->bind_param("ss", $academic_start_date_str, $academic_end_date_str);
                $stmt_holidays_pay->execute();
                $result_holidays_pay = $stmt_holidays_pay->get_result();
                while ($row = $result_holidays_pay->fetch_assoc()) {
                    $holidays_in_academic_year[] = $row['tanggal'];
                }
                $stmt_holidays_pay->close();
            } else {
                $message = "Error saat mengambil data hari libur.";
                $message_type = "error";
                goto end_payment_process; // Lompat ke bagian akhir proses jika ada error
            }

            // 2. Dapatkan semua tanggal yang sudah dibayar oleh siswa ini di tahun akademik ini
            $paid_dates_for_student = [];
            // --- Menggunakan rentang tanggal akademik ---
            $stmt_paid_dates = $conn->prepare("SELECT tanggal FROM pembayaran WHERE id_siswa = ? AND tanggal BETWEEN ? AND ?");
            if ($stmt_paid_dates) {
                $stmt_paid_dates->bind_param("iss", $id_siswa_pay, $academic_start_date_str, $academic_end_date_str);
                $stmt_paid_dates->execute();
                $result_paid_dates = $stmt_paid_dates->get_result();
                while ($row = $result_paid_dates->fetch_assoc()) {
                    $paid_dates_for_student[] = $row['tanggal'];
                }
                $stmt_paid_dates->close();
            } else {
                $message = "Error saat mengambil data pembayaran siswa.";
                $message_type = "error";
                goto end_payment_process;
            }

            // 3. Bangun daftar tanggal-tanggal SPP yang belum dibayar, diurutkan dari yang terlama ke yang terbaru
            $unpaid_spp_days = [];
            // Iterasi dari tanggal awal akademik (Juli) hingga tanggal akhir akademik (Juni tahun berikutnya)
            $current_date_iterator = clone $academic_start_date_obj;
            $end_date_loop_exclusive = clone $academic_end_date_obj;
            $end_date_loop_exclusive->modify('+1 day'); // Agar tanggal akhir ikut terhitung

            while ($current_date_iterator < $end_date_loop_exclusive) {
                $date_str = $current_date_iterator->format('Y-m-d');
                $day_of_week = $current_date_iterator->format('w'); // 0=Minggu, 5=Jumat

                $is_friday = ($day_of_week == 5);
                $is_holiday_db = in_array($date_str, $holidays_in_academic_year);
                $is_paid_already = in_array($date_str, $paid_dates_for_student);

                // Jika bukan Jumat, bukan hari libur, dan belum dibayar
                if (!$is_friday && !$is_holiday_db && !$is_paid_already) {
                    $unpaid_spp_days[] = $date_str; // Menambahkan tanggal yang belum dibayar
                }
                $current_date_iterator->modify('+1 day'); // Maju satu hari
            }

            // Sekarang $unpaid_spp_days berisi tanggal dari yang paling lama ke paling baru
            // Pembayaran akan diterapkan sesuai urutan ini

            $paid_count = 0;
            $spp_inserted_successfully = true;

            // Mulai transaksi database
            $conn->begin_transaction();

            foreach ($unpaid_spp_days as $day_to_pay) {
                if ($paid_count >= $days_to_mark_paid) {
                    break; // Berhenti jika sudah cukup hari yang dibayar
                }

                $stmt_insert_payment = $conn->prepare("INSERT INTO pembayaran (id_siswa, tanggal, jumlah) VALUES (?, ?, ?)");
                if ($stmt_insert_payment) {
                    $stmt_insert_payment->bind_param("isi", $id_siswa_pay, $day_to_pay, $spp_per_day_fixed);
                    if ($stmt_insert_payment->execute()) {
                        $paid_count++;
                    } else {
                        $spp_inserted_successfully = false;
                        $message = "Gagal memasukkan pembayaran untuk tanggal " . $day_to_pay . ": " . $stmt_insert_payment->error;
                        $message_type = "error";
                        break; // Hentikan jika ada error insert
                    }
                    $stmt_insert_payment->close();
                } else {
                    $spp_inserted_successfully = false;
                    $message = "Gagal menyiapkan statement pembayaran: " . $conn->error;
                    $message_type = "error";
                    break;
                }
            }

            if ($spp_inserted_successfully && $paid_count > 0) {
                $conn->commit();
                $message = "Pembayaran SPP berhasil diproses. " . $paid_count . " hari SPP (Rp " . number_format($paid_count * $spp_per_day_fixed, 0, ',', '.') . ") telah dibayarkan.";
                if ($remaining_amount > 0) {
                    $message .= " Sisa pembayaran Rp " . number_format($remaining_amount, 0, ',', '.') . " (tidak cukup untuk satu hari SPP).";
                }
                $message_type = "success";
            } elseif ($spp_inserted_successfully && $paid_count == 0) {
                $conn->rollback(); // Tidak ada hari yang bisa dibayar
                $message = "Tidak ada hari SPP yang perlu dibayar untuk jumlah tersebut, atau semua hari sudah dibayar/libur.";
                $message_type = "error";
            } else {
                $conn->rollback(); // Rollback jika ada error di tengah jalan
                // Pesan error sudah di set di dalam loop atau di atas
            }
        }
    }
    end_payment_process:; // Label untuk goto
}


// --- FUNGSI HAPUS PEMBAYARAN KELOMPOK ---
if (isset($_GET['delete_payment_group'])) {
    $id_siswa_delete = intval($_GET['id_siswa']);
    $bulan_delete = intval($_GET['bulan']);
    $tahun_delete = intval($_GET['tahun']);

    if ($id_siswa_delete > 0 && $bulan_delete > 0 && $tahun_delete > 0) {
        $stmt_delete_payments = $conn->prepare("DELETE FROM pembayaran WHERE id_siswa = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
        $stmt_delete_payments->bind_param("iii", $id_siswa_delete, $bulan_delete, $tahun_delete);

        if ($stmt_delete_payments->execute()) {
            $message = "Semua pembayaran untuk siswa ID {$id_siswa_delete} pada bulan {$bulan_delete}/{$tahun_delete} berhasil dihapus!";
            $message_type = "success";
        } else {
            $message = "Error menghapus pembayaran: " . $stmt_delete_payments->error;
            $message_type = "error";
        }
        $stmt_delete_payments->close();
    } else {
        $message = "Parameter hapus pembayaran tidak valid.";
        $message_type = "error";
    }
}


// Mengatur tampilan default admin
$admin_section = isset($_GET['section']) ? $_GET['section'] : 'siswa';

// Mendapatkan data untuk ditampilkan
$data = [];
$edit_data = null;

// Mendapatkan daftar siswa untuk dropdown di form pembayaran
$siswa_list_for_forms = [];
$sql_siswa_forms = "SELECT id_siswa, nama_siswa FROM siswa ORDER BY nama_siswa ASC";
$result_siswa_forms = $conn->query($sql_siswa_forms);
if ($result_siswa_forms->num_rows > 0) {
    while ($row = $result_siswa_forms->fetch_assoc()) {
        $siswa_list_for_forms[] = $row;
    }
}

switch ($admin_section) {
    case 'siswa':
        $sql = "SELECT id_siswa, nama_siswa FROM siswa ORDER BY id_siswa ASC"; // Order by ID juga bisa membantu melihat urutan
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        if (isset($_GET['edit']) && $_GET['edit'] == 'siswa' && isset($_GET['id'])) {
            $edit_id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT id_siswa, nama_siswa FROM siswa WHERE id_siswa = ?");
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $edit_data = $result->fetch_assoc();
            }
            $stmt->close();
        }
        break;
    case 'biaya_lain':
        $sql = "SELECT id_biaya, nama_biaya, jumlah FROM biaya_lain ORDER BY nama_biaya ASC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        if (isset($_GET['edit']) && $_GET['edit'] == 'biaya_lain' && isset($_GET['id'])) {
            $edit_id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT id_biaya, nama_biaya, jumlah FROM biaya_lain WHERE id_biaya = ?");
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $edit_data = $result->fetch_assoc();
            }
            $stmt->close();
        }
        break;
    case 'libur':
        $sql = "SELECT id_libur, tanggal, keterangan FROM libur ORDER BY tanggal ASC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        if (isset($_GET['edit']) && $_GET['edit'] == 'libur' && isset($_GET['id'])) {
            $edit_id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT id_libur, tanggal, keterangan FROM libur WHERE id_libur = ?");
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $edit_data = $result->fetch_assoc();
            }
            $stmt->close();
        }
        break;
    case 'pembayaran':
        // Query untuk Ringkasan Pembayaran Per Bulan (Tahun Akademik)
        $sql_payments = "
            SELECT
                s.id_siswa,
                s.nama_siswa,
                YEAR(p.tanggal) as tahun_pembayaran,
                MONTH(p.tanggal) as bulan_pembayaran,
                SUM(p.jumlah) as total_dibayar
            FROM
                pembayaran p
            JOIN
                siswa s ON p.id_siswa = s.id_siswa
            WHERE
                p.tanggal BETWEEN ? AND ? -- Filter berdasarkan tahun akademik
            GROUP BY
                s.id_siswa, tahun_pembayaran, bulan_pembayaran
            ORDER BY
                tahun_pembayaran ASC, bulan_pembayaran ASC, s.nama_siswa ASC
            "; // Tanpa LIMIT 100

        $stmt_payments = $conn->prepare($sql_payments);
        $stmt_payments->bind_param("ss", $academic_start_date_str, $academic_end_date_str);
        $stmt_payments->execute();
        $result_payments = $stmt_payments->get_result();

        if ($result_payments->num_rows > 0) {
            while ($row = $result_payments->fetch_assoc()) {
                $data[] = $row;
            }
        }
        $stmt_payments->close();
        break;
}

$conn->close(); // Menutup koneksi database
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin SPP</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Style untuk scrollbar jika tabel terlalu lebar */
        .overflow-x-auto::-webkit-scrollbar {
            height: 8px;
        }
        .overflow-x-auto::-webkit-scrollbar-thumb {
            background-color: #cbd5e0; /* gray-300 */
            border-radius: 4px;
        }
        .overflow-x-auto::-webkit-scrollbar-track {
            background-color: #f7fafc; /* gray-50 */
        }
        /* Style untuk dropdown select agar bisa di-scroll */
        select {
            max-height: 200px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* KHUSUS UNTUK DROPDOWN PEMBAYARAN DI ADMIN */
        select#id_siswa_pay {
            max-height: 300px; /* Tinggikan sedikit jika 200px masih kurang */
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            /* Tambahan untuk memastikan style diterapkan */
            display: block; /* Memastikan elemen mengambil lebar penuh */
            width: 100%; /* Penting untuk responsif */
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 leading-normal tracking-normal">
    <div class="container mx-auto p-4 md:p-8">
        <h1 class="text-3xl font-extrabold text-center text-purple-700 mb-8 mt-4 rounded-lg bg-white p-4 shadow-lg">
            Panel Admin Sistem Pembayaran SPP
            <br>
            <span class="text-lg font-normal text-gray-600">Selamat datang, <?= htmlspecialchars($_SESSION['admin_username']) ?>!</span>
        </h1>

        <!-- Navigation Tabs -->
        <div class="bg-white p-4 rounded-lg shadow-md mb-8 flex flex-wrap justify-center space-x-4 space-y-2 md:space-y-0">
            <a href="?section=siswa" class="px-6 py-3 rounded-md text-lg font-semibold transition duration-300
                <?= $admin_section == 'siswa' ? 'bg-purple-600 text-white shadow-md' : 'text-purple-700 hover:bg-purple-100' ?>">
                Manajemen Siswa
            </a>
            <a href="?section=biaya_lain" class="px-6 py-3 rounded-md text-lg font-semibold transition duration-300
                <?= $admin_section == 'biaya_lain' ? 'bg-purple-600 text-white shadow-md' : 'text-purple-700 hover:bg-purple-100' ?>">
                Manajemen Biaya Lain

            </a>
            <a href="?section=libur" class="px-6 py-3 rounded-md text-lg font-semibold transition duration-300
                <?= $admin_section == 'libur' ? 'bg-purple-600 text-white shadow-md' : 'text-purple-700 hover:bg-purple-100' ?>">
                Manajemen Hari Libur
            </a>
            <a href="?section=pembayaran" class="px-6 py-3 rounded-md text-lg font-semibold transition duration-300
                <?= $admin_section == 'pembayaran' ? 'bg-purple-600 text-white shadow-md' : 'text-purple-700 hover:bg-purple-100' ?>">
                Manajemen Pembayaran
            </a>
             <a href="index.php" class="px-6 py-3 rounded-md text-lg font-semibold transition duration-300 bg-blue-600 text-white hover:bg-blue-700 shadow-md">
                Kembali ke User View
            </a>
             <a href="admin.php?action=logout" class="px-6 py-3 rounded-md text-lg font-semibold transition duration-300 bg-red-600 text-white hover:bg-red-700 shadow-md"
                onclick="return confirm('Apakah Anda yakin ingin logout?');">
                Logout
            </a>
        </div>

        <?php if ($message): ?>
            <div class="p-4 mb-6 rounded-lg text-white font-medium
                <?= $message_type == 'success' ? 'bg-green-500' : 'bg-red-500' ?>" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Content based on selected section -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <?php if ($admin_section == 'siswa'): ?>
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Manajemen Siswa</h2>

                <!-- Form Tambah/Edit Siswa -->
                <form action="admin.php?section=siswa" method="POST" class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <h3 class="text-xl font-semibold mb-4"><?= $edit_data ? 'Edit Siswa' : 'Tambah Siswa Baru' ?></h3>
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="id_siswa" value="<?= htmlspecialchars($edit_data['id_siswa']) ?>">
                    <?php endif; ?>
                    <div class="mb-4">
                        <label for="nama_siswa" class="block text-gray-700 text-sm font-bold mb-2">Nama Siswa:</label>
                        <input type="text" id="nama_siswa" name="nama_siswa" value="<?= htmlspecialchars($edit_data['nama_siswa'] ?? '') ?>"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" name="<?= $edit_data ? 'edit_siswa' : 'add_siswa' ?>"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                            <?= $edit_data ? 'Perbarui Siswa' : 'Tambah Siswa' ?>
                        </button>
                        <?php if ($edit_data): ?>
                            <a href="admin.php?section=siswa" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                                Batal
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Daftar Siswa -->
                <h3 class="text-xl font-semibold mb-4">Daftar Siswa</h3>
                <?php if (!empty($data)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white rounded-lg overflow-hidden border border-gray-200">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">ID</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Nama Siswa</th>
                                    <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($data as $row): ?>
                                    <tr>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['id_siswa']) ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-center">
                                            <a href="admin.php?section=siswa&edit=siswa&id=<?= htmlspecialchars($row['id_siswa']) ?>"
                                               class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-1 px-3 rounded-md text-xs mr-2 transition duration-300">Edit</a>
                                            <a href="admin.php?section=siswa&delete_siswa=<?= htmlspecialchars($row['id_siswa']) ?>"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus siswa ini? Data pembayaran terkait juga akan dihapus.');"
                                               class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded-md text-xs transition duration-300">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">Belum ada siswa yang terdaftar.</p>
                <?php endif; ?>

            <?php elseif ($admin_section == 'biaya_lain'): ?>
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Manajemen Biaya Lain</h2>

                <!-- Form Tambah/Edit Biaya Lain -->
                <form action="admin.php?section=biaya_lain" method="POST" class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <h3 class="text-xl font-semibold mb-4"><?= $edit_data ? 'Edit Biaya Lain' : 'Tambah Biaya Lain Baru' ?></h3>
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="id_biaya" value="<?= htmlspecialchars($edit_data['id_biaya']) ?>">
                    <?php endif; ?>
                    <div class="mb-4">
                        <label for="nama_biaya" class="block text-gray-700 text-sm font-bold mb-2">Nama Biaya:</label>
                        <input type="text" id="nama_biaya" name="nama_biaya" value="<?= htmlspecialchars($edit_data['nama_biaya'] ?? '') ?>"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="mb-4">
                        <label for="jumlah" class="block text-gray-700 text-sm font-bold mb-2">Jumlah (Rp):</label>
                        <input type="number" id="jumlah" name="jumlah" value="<?= htmlspecialchars($edit_data['jumlah'] ?? '') ?>"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required min="0">
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" name="<?= $edit_data ? 'edit_biaya_lain' : 'add_biaya_lain' ?>"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                            <?= $edit_data ? 'Perbarui Biaya' : 'Tambah Biaya' ?>
                        </button>
                        <?php if ($edit_data): ?>
                            <a href="admin.php?section=biaya_lain" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                                Batal
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Daftar Biaya Lain -->
                <h3 class="text-xl font-semibold mb-4">Daftar Biaya Lain</h3>
                <?php if (!empty($data)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white rounded-lg overflow-hidden border border-gray-200">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">ID</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Nama Biaya</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Jumlah</th>
                                    <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($data as $row): ?>
                                    <tr>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['id_biaya']) ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['nama_biaya']) ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-center">
                                            <a href="admin.php?section=biaya_lain&edit=biaya_lain&id=<?= htmlspecialchars($row['id_biaya']) ?>"
                                               class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-1 px-3 rounded-md text-xs mr-2 transition duration-300">Edit</a>
                                            <a href="admin.php?section=biaya_lain&delete_biaya_lain=<?= htmlspecialchars($row['id_biaya']) ?>"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus biaya ini?');"
                                               class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded-md text-xs transition duration-300">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">Belum ada biaya lain yang terdaftar.</p>
                <?php endif; ?>

            <?php elseif ($admin_section == 'libur'): ?>
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Manajemen Hari Libur</h2>

                <!-- Form Tambah/Edit Hari Libur (Individual) -->
                <form action="admin.php?section=libur" method="POST" class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <h3 class="text-xl font-semibold mb-4"><?= $edit_data ? 'Edit Hari Libur (Individual)' : 'Tambah Hari Libur Baru (Individual)' ?></h3>
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="id_libur" value="<?= htmlspecialchars($edit_data['id_libur']) ?>">
                    <?php endif; ?>
                    <div class="mb-4">
                        <label for="tanggal" class="block text-gray-700 text-sm font-bold mb-2">Tanggal:</label>
                        <input type="date" id="tanggal" name="tanggal" value="<?= htmlspecialchars($edit_data['tanggal'] ?? date('Y-m-d')) ?>"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="mb-4">
                        <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan:</label>
                        <input type="text" id="keterangan" name="keterangan" value="<?= htmlspecialchars($edit_data['keterangan'] ?? '') ?>"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" name="<?= $edit_data ? 'edit_libur' : 'add_libur' ?>"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                            <?= $edit_data ? 'Perbarui Libur' : 'Tambah Libur' ?>
                        </button>
                        <?php if ($edit_data): ?>
                            <a href="admin.php?section=libur" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                                Batal
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Form Tambah Hari Libur Per Rentang -->
                <form action="admin.php?section=libur" method="POST" class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <h3 class="text-xl font-semibold mb-4">Tambah Hari Libur Per Rentang Tanggal</h3>
                    <div class="mb-4">
                        <label for="tanggal_mulai_libur" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Mulai:</label>
                        <input type="date" id="tanggal_mulai_libur" name="tanggal_mulai_libur" value="<?= date('Y-m-d') ?>"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="mb-4">
                        <label for="tanggal_akhir_libur" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Akhir:</label>
                        <input type="date" id="tanggal_akhir_libur" name="tanggal_akhir_libur" value="<?= date('Y-m-d') ?>"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="mb-4">
                        <label for="keterangan_libur_range" class="block text-gray-700 text-sm font-bold mb-2">Keterangan:</label>
                        <input type="text" id="keterangan_libur_range" name="keterangan_libur_range" placeholder="Contoh: Libur Semesteran"
                               class="class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <button type="submit" name="add_libur_range" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                        Tambah Libur Rentang
                    </button>
                </form>

                <!-- Daftar Hari Libur -->
                <h3 class="text-xl font-semibold mb-4">Daftar Hari Libur (Tahun Akademik <?= htmlspecialchars($academic_start_year) ?> - <?= htmlspecialchars($academic_end_year) ?>)</h3>
                <?php if (!empty($data)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white rounded-lg overflow-hidden border border-gray-200">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">ID</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Tanggal</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Keterangan</th>
                                    <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($data as $row): ?>
                                    <tr>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['id_libur']) ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['tanggal']) ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['keterangan']) ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-center">
                                            <a href="admin.php?section=libur&edit=libur&id=<?= htmlspecialchars($row['id_libur']) ?>"
                                               class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-1 px-3 rounded-md text-xs mr-2 transition duration-300">Edit</a>
                                            <a href="admin.php?section=libur&delete_libur=<?= htmlspecialchars($row['id_libur']) ?>"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus hari libur ini?');"
                                               class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded-md text-xs transition duration-300">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">Belum ada hari libur yang terdaftar.</p>
                <?php endif; ?>

            <?php elseif ($admin_section == 'pembayaran'): ?>
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Manajemen Pembayaran SPP</h2>

                <!-- Form Bayar SPP Berdasarkan Jumlah Uang -->
                <form action="admin.php?section=pembayaran" method="POST" class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <h3 class="text-xl font-semibold mb-4">Bayar SPP Berdasarkan Jumlah Uang</h3>
                    <div class="mb-4">
                        <label for="id_siswa_pay" class="block text-gray-700 text-sm font-bold mb-2">Pilih Siswa:</label>
                        <select name="id_siswa_pay" id="id_siswa_pay"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="">--- Pilih Siswa ---</option>
                            <?php foreach ($siswa_list_for_forms as $siswa): ?>
                                <option value="<?= htmlspecialchars($siswa['id_siswa']) ?>">
                                    <?= htmlspecialchars($siswa['nama_siswa']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="jumlah_pembayaran" class="block text-gray-700 text-sm font-bold mb-2">Jumlah Pembayaran (Rp):</label>
                        <input type="number" id="jumlah_pembayaran" name="jumlah_pembayaran" placeholder="Masukkan jumlah uang, contoh: 50000"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required min="1">
                    </div>
                    <button type="submit" name="pay_spp_by_amount" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                        Proses Pembayaran SPP
                    </button>
                </form>

                <!-- Tombol Ekspor Excel -->
                <div class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50 text-center">
                    <h3 class="text-xl font-semibold mb-4">Ekspor Data Pembayaran</h3>
                    <a href="export_payments.php?start_date=<?= htmlspecialchars($academic_start_date_str) ?>&end_date=<?= htmlspecialchars($academic_end_date_str) ?>"
                       class="inline-flex items-center bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Ekspor Data Pembayaran ke Excel
                    </a>
                    <p class="text-sm text-gray-600 mt-2">Data yang diekspor adalah ringkasan pembayaran untuk Tahun Akademik ini (<?= htmlspecialchars($academic_start_year) ?> - <?= htmlspecialchars($academic_end_year) ?>).</p>
                </div>


                <!-- Daftar Pembayaran Terakhir (Ringkasan Bulanan) -->
                <h3 class="text-xl font-semibold mb-4">Ringkasan Pembayaran SPP (Per Bulan Tahun Akademik <?= htmlspecialchars($academic_start_year) ?> - <?= htmlspecialchars($academic_end_year) ?>)</h3>
                <?php if (!empty($data)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white rounded-lg overflow-hidden border border-gray-200">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Nama Siswa</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Bulan & Tahun</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Total Dibayar</th>
                                    <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php
                                // Array untuk mengkonversi nomor bulan ke nama bulan dalam Bahasa Indonesia
                                $nama_bulan = [
                                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                ];
                                foreach ($data as $row): ?>
                                    <tr>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800">
                                            <?= htmlspecialchars($nama_bulan[$row['bulan_pembayaran']]) . ' ' . htmlspecialchars($row['tahun_pembayaran']) ?>
                                        </td>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800">Rp <?= number_format($row['total_dibayar'], 0, ',', '.') ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-center">
                                            <a href="admin.php?section=pembayaran&delete_payment_group=true&id_siswa=<?= htmlspecialchars($row['id_siswa']) ?>&bulan=<?= htmlspecialchars($row['bulan_pembayaran']) ?>&tahun=<?= htmlspecialchars($row['tahun_pembayaran']) ?>"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus semua pembayaran SPP untuk <?= htmlspecialchars($row['nama_siswa']) ?> di bulan <?= htmlspecialchars($nama_bulan[$row['bulan_pembayaran']]) ?> <?= htmlspecialchars($row['tahun_pembayaran']) ?>?');"
                                               class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded-md text-xs transition duration-300">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">Belum ada ringkasan pembayaran.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
