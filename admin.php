 <?php
// admin.php - Halaman Admin
session_start(); // Mulai sesi (HARUS DI PALING ATAS!)

// Periksa apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Jika belum login, arahkan ke halaman login
    header("Location: login.php");
    exit();
}

include 'db_connect.php'; // Memasukkan file koneksi database

$message = '';
$message_type = ''; // success or error
$change_info = null; // Variabel untuk menyimpan informasi kembalian

// Ambil pesan dan informasi kembalian dari sesi jika ada
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['message_type'])) {
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message_type']);
}
if (isset($_SESSION['last_change_info'])) {
    $change_info = $_SESSION['last_change_info'];
    unset($_SESSION['last_change_info']); // Hapus setelah diambil untuk ditampilkan
}


// Handle Logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_unset(); // Hapus semua variabel sesi
    session_destroy(); // Hancurkan sesi
    header("Location: login.php"); // Arahkan kembali ke halaman login
    exit();
}

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

$academic_start_date_str = $academic_start_date_obj->format('Y-m-d');
$academic_end_date_str = $academic_end_date_obj->format('Y-m-d');

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


// --- FUNGSI CRUD SISWA ---
if (isset($_POST['add_siswa'])) {
    $nama_siswa = trim($_POST['nama_siswa']);
    $login_code = trim($_POST['login_code']);

    if (empty($nama_siswa) || empty($login_code)) {
        $_SESSION['message'] = "Nama siswa dan Kode Login tidak boleh kosong.";
        $_SESSION['message_type'] = "error";
    } else {
        // Cek apakah login_code sudah ada
        $stmt_check_code = $conn->prepare("SELECT COUNT(*) FROM siswa WHERE login_code = ?");
        $stmt_check_code->bind_param("s", $login_code);
        $stmt_check_code->execute();
        $stmt_check_code->bind_result($count_code);
        $stmt_check_code->fetch();
        $stmt_check_code->close();

        if ($count_code > 0) {
            $_SESSION['message'] = "Kode Login sudah digunakan. Harap gunakan kode lain.";
            $_SESSION['message_type'] = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO siswa (nama_siswa, login_code) VALUES (?, ?)");
            $stmt->bind_param("ss", $nama_siswa, $login_code);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Siswa berhasil ditambahkan!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error menambah siswa: " . $stmt->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt->close();
        }
    }
    header("Location: admin.php?section=siswa");
    exit();
}

if (isset($_POST['edit_siswa'])) {
    $id_siswa = intval($_POST['id_siswa']);
    $nama_siswa = trim($_POST['nama_siswa']);
    $login_code = trim($_POST['login_code']);

    if ($id_siswa <= 0 || empty($nama_siswa) || empty($login_code)) {
        $_SESSION['message'] = "ID siswa, nama siswa, atau Kode Login tidak valid.";
        $_SESSION['message_type'] = "error";
    } else {
        // Cek apakah login_code sudah ada untuk siswa lain
        $stmt_check_code = $conn->prepare("SELECT COUNT(*) FROM siswa WHERE login_code = ? AND id_siswa != ?");
        $stmt_check_code->bind_param("si", $login_code, $id_siswa);
        $stmt_check_code->execute();
        $stmt_check_code->bind_result($count_code);
        $stmt_check_code->fetch();
        $stmt_check_code->close();

        if ($count_code > 0) {
            $_SESSION['message'] = "Kode Login sudah digunakan oleh siswa lain. Harap gunakan kode lain.";
            $_SESSION['message_type'] = "error";
        } else {
            $stmt = $conn->prepare("UPDATE siswa SET nama_siswa = ?, login_code = ? WHERE id_siswa = ?");
            $stmt->bind_param("ssi", $nama_siswa, $login_code, $id_siswa);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Siswa berhasil diperbarui!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error memperbarui siswa: " . $stmt->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt->close();
        }
    }
    header("Location: admin.php?section=siswa");
    exit();
}

if (isset($_GET['delete_siswa'])) {
    $id_siswa = intval($_GET['delete_siswa']);
    if ($id_siswa > 0) {
        $stmt = $conn->prepare("DELETE FROM siswa WHERE id_siswa = ?");
        $stmt->bind_param("i", $id_siswa);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Siswa berhasil dihapus!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error menghapus siswa: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    }
    header("Location: admin.php?section=siswa");
    exit();
}

// --- FUNGSI CRUD BIAYA LAIN (MASTER DATA) ---
if (isset($_POST['add_biaya_lain'])) {
    $nama_biaya = trim($_POST['nama_biaya']);
    $jumlah = intval($_POST['jumlah']);
    if (!empty($nama_biaya) && $jumlah > 0) {
        $stmt = $conn->prepare("INSERT INTO biaya_lain (nama_biaya, jumlah) VALUES (?, ?)");
        $stmt->bind_param("si", $nama_biaya, $jumlah);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Biaya lain berhasil ditambahkan!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error menambah biaya lain: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Nama biaya dan jumlah tidak boleh kosong atau tidak valid.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: admin.php?section=biaya_lain");
    exit();
}

if (isset($_POST['edit_biaya_lain'])) {
    $id_biaya = intval($_POST['id_biaya']);
    $nama_biaya = trim($_POST['nama_biaya']);
    $jumlah = intval($_POST['jumlah']);
    if ($id_biaya > 0 && !empty($nama_biaya) && $jumlah > 0) {
        $stmt = $conn->prepare("UPDATE biaya_lain SET nama_biaya = ?, jumlah = ? WHERE id_biaya = ?");
        $stmt->bind_param("sii", $nama_biaya, $jumlah, $id_biaya);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Biaya lain berhasil diperbarui!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error memperbarui biaya lain: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "ID biaya, nama biaya, atau jumlah tidak valid.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: admin.php?section=biaya_lain");
    exit();
}

if (isset($_GET['delete_biaya_lain'])) {
    $id_biaya = intval($_GET['delete_biaya_lain']);
    if ($id_biaya > 0) {
        $stmt = $conn->prepare("DELETE FROM biaya_lain WHERE id_biaya = ?");
        $stmt->bind_param("i", $id_biaya);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Biaya lain berhasil dihapus!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error menghapus biaya lain: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    }
    header("Location: admin.php?section=biaya_lain");
    exit();
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
            $_SESSION['message'] = "Tanggal libur sudah ada.";
            $_SESSION['message_type'] = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO libur (tanggal, keterangan) VALUES (?, ?)");
            $stmt->bind_param("ss", $tanggal, $keterangan);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Hari libur berhasil ditambahkan!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error menambah hari libur: " . $stmt->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt->close();
        }
    } else {
        $_SESSION['message'] = "Tanggal dan keterangan tidak boleh kosong.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: admin.php?section=libur");
    exit();
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
            $_SESSION['message'] = "Tanggal libur sudah ada untuk hari libur lain.";
            $_SESSION['message_type'] = "error";
        } else {
            $stmt = $conn->prepare("UPDATE libur SET tanggal = ?, keterangan = ? WHERE id_libur = ?");
            $stmt->bind_param("ssi", $tanggal, $keterangan, $id_libur);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Hari libur berhasil diperbarui!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error memperbarui hari libur: " . $stmt->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt->close();
        }
    } else {
        $_SESSION['message'] = "ID libur, tanggal, atau keterangan tidak valid.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: admin.php?section=libur");
    exit();
}

if (isset($_GET['delete_libur'])) {
    $id_libur = intval($_GET['delete_libur']);
    if ($id_libur > 0) {
        $stmt = $conn->prepare("DELETE FROM libur WHERE id_libur = ?");
        $stmt->bind_param("i", $id_libur);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Hari libur berhasil dihapus!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error menghapus hari libur: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    }
    header("Location: admin.php?section=libur");
    exit();
}


// --- FUNGSI TAMBAH LIBUR PER RENTANG ---
if (isset($_POST['add_libur_range'])) {
    $tanggal_mulai = trim($_POST['tanggal_mulai_libur']);
    $tanggal_akhir = trim($_POST['tanggal_akhir_libur']);
    $keterangan_libur_range = trim($_POST['keterangan_libur_range']);

    if (empty($tanggal_mulai) || empty($tanggal_akhir) || empty($keterangan_libur_range)) {
        $_SESSION['message'] = "Semua field untuk hari libur rentang harus diisi.";
        $_SESSION['message_type'] = "error";
    } elseif (strtotime($tanggal_mulai) > strtotime($tanggal_akhir)) {
        $_SESSION['message'] = "Tanggal mulai tidak boleh lebih besar dari tanggal akhir.";
        $_SESSION['message_type'] = "error";
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
        $_SESSION['message'] = "Penambahan hari libur rentang selesai. Ditambahkan: {$added_count} hari, Dilewati (sudah ada): {$skipped_count} hari.";
        $_SESSION['message_type'] = "success";
    }
    header("Location: admin.php?section=libur");
    exit();
}


// --- FUNGSI BAYAR SPP PERTAMA (HARIAN) BERDASARKAN JUMLAH UANG ---
if (isset($_POST['pay_spp_by_amount'])) {
    $id_siswa_pay = intval($_POST['id_siswa_pay']);
    $amount_received = intval($_POST['jumlah_pembayaran']);
    $spp_per_day_fixed = 500; // Tarif SPP per hari
    $admin_username = $_SESSION['admin_username'] ?? 'unknown'; // Dapatkan username admin yang login

    // Ambil nama siswa untuk pesan kembalian
    $siswa_name_for_change = '';
    $stmt_siswa_name = $conn->prepare("SELECT nama_siswa FROM siswa WHERE id_siswa = ?");
    if ($stmt_siswa_name) {
        $stmt_siswa_name->bind_param("i", $id_siswa_pay);
        $stmt_siswa_name->execute();
        $result_siswa_name = $stmt_siswa_name->get_result();
        if ($result_siswa_name->num_rows > 0) {
            $siswa_name_for_change = $result_siswa_name->fetch_assoc()['nama_siswa'];
        }
        $stmt_siswa_name->close();
    }

    if ($id_siswa_pay <= 0 || $amount_received <= 0) {
        $_SESSION['message'] = "Pilih siswa dan masukkan jumlah pembayaran yang valid (lebih dari Rp 0).";
        $_SESSION['message_type'] = "error";
        header("Location: admin.php?section=pembayaran&spp_tab=spp1_mgmt");
        exit();
    }

    // Hitung berapa hari yang bisa dibayar
    $days_to_mark_paid_potential = floor($amount_received / $spp_per_day_fixed);

    if ($days_to_mark_paid_potential == 0) {
        $_SESSION['message'] = "Jumlah pembayaran Rp " . number_format($amount_received, 0, ',', '.') . " tidak cukup untuk satu hari SPP (Rp " . number_format($spp_per_day_fixed, 0, ',', '.') . ").";
        $_SESSION['message_type'] = "error";
        header("Location: admin.php?section=pembayaran&spp_tab=spp1_mgmt");
        exit();
    }

    // 1. Dapatkan semua tanggal libur untuk tahun akademik ini
    $holidays_in_academic_year = [];
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
        $_SESSION['message'] = "Error saat mengambil data hari libur.";
        $_SESSION['message_type'] = "error";
        header("Location: admin.php?section=pembayaran&spp_tab=spp1_mgmt");
        exit();
    }

    // 2. Dapatkan semua tanggal yang sudah dibayar oleh siswa ini di tahun akademik ini
    $paid_dates_for_student = [];
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
        $_SESSION['message'] = "Error saat mengambil data pembayaran siswa.";
        $_SESSION['message_type'] = "error";
        header("Location: admin.php?section=pembayaran&spp_tab=spp1_mgmt");
        exit();
    }

    // 3. Bangun daftar tanggal-tanggal SPP yang belum dibayar, diurutkan dari yang terlama ke yang terbaru
    $unpaid_spp_days = [];
    $current_date_iterator = clone $academic_start_date_obj;
    $end_date_loop_exclusive = clone $academic_end_date_obj;
    $end_date_loop_exclusive->modify('+1 day');
    while ($current_date_iterator < $end_date_loop_exclusive) {
        $date_str = $current_date_iterator->format('Y-m-d');
        $day_of_week = $current_date_iterator->format('w'); // 0=Minggu, 5=Jumat
        $is_friday = ($day_of_week == 5);
        $is_holiday_db = in_array($date_str, $holidays_in_academic_year);
        $is_paid_already = in_array($date_str, $paid_dates_for_student);

        // Perbaikan: Hanya mengecualikan hari Jumat dan hari libur. Hari Sabtu dan Minggu dihitung jika bukan hari libur.
        if (!$is_friday && !$is_holiday_db && !$is_paid_already) {
            $unpaid_spp_days[] = $date_str;
        }
        $current_date_iterator->modify('+1 day');
    }

    $paid_count = 0;
    $spp_inserted_successfully = true;
    $conn->begin_transaction();

    foreach ($unpaid_spp_days as $day_to_pay) {
        if ($paid_count >= $days_to_mark_paid_potential) {
            break;
        }
        $stmt_insert_payment = $conn->prepare("INSERT INTO pembayaran (id_siswa, tanggal, jumlah, created_by_admin_username) VALUES (?, ?, ?, ?)");
        if ($stmt_insert_payment) {
            $stmt_insert_payment->bind_param("isis", $id_siswa_pay, $day_to_pay, $spp_per_day_fixed, $admin_username);
            if ($stmt_insert_payment->execute()) {
                $paid_count++;
            } else {
                $spp_inserted_successfully = false;
                $_SESSION['message'] = "Gagal memasukkan pembayaran untuk tanggal " . $day_to_pay . ": " . $stmt_insert_payment->error;
                $_SESSION['message_type'] = "error";
                break;
            }
            $stmt_insert_payment->close();
        } else {
            $spp_inserted_successfully = false;
            $_SESSION['message'] = "Gagal menyiapkan statement pembayaran: " . $conn->error;
            $_SESSION['message_type'] = "error";
            break;
        }
    }

    $total_spp_covered = $paid_count * $spp_per_day_fixed;
    $change_amount = $amount_received - $total_spp_covered; // Kembalian

    if ($spp_inserted_successfully && $paid_count > 0) {
        $conn->commit();
        $_SESSION['message'] = "Pembayaran Ikhsan berhasil diproses. " . $paid_count . " hari SPP (Rp " . number_format($total_spp_covered, 0, ',', '.') . ") telah dibayarkan.";
        $_SESSION['message_type'] = "success";
        if ($change_amount >= 0) { // Pastikan kembalian tidak negatif
            $_SESSION['last_change_info'] = [
                'siswa_name' => $siswa_name_for_change,
                'spp_type' => 'Harian',
                'amount_paid' => $amount_received,
                'amount_covered' => $total_spp_covered,
                'change_amount' => $change_amount
            ];
        }
    } elseif ($spp_inserted_successfully && $paid_count == 0) {
        $conn->rollback();
        $_SESSION['message'] = "Tidak ada hari SPP yang perlu dibayar untuk jumlah tersebut, atau semua hari sudah dibayar/libur.";
        $_SESSION['message_type'] = "error";
    } else {
        $conn->rollback();
    }
    header("Location: admin.php?section=pembayaran&spp_tab=spp1_mgmt");
    exit();
}


// --- FUNGSI HAPUS PEMBAYARAN SPP PERTAMA KELOMPOK (PER BULAN) ---
// CATATAN: Fungsi ini tidak lagi digunakan oleh tabel SPP Harian yang menampilkan individual payments.
// Namun, kode ini dipertahankan jika Anda ingin menggunakannya di tempat lain atau mengembalikan tampilan ringkasan bulanan.
if (isset($_GET['delete_payment_group'])) {
    $id_siswa_delete = intval($_GET['id_siswa']);
    $bulan_delete = intval($_GET['bulan']);
    $tahun_delete = intval($_val['tahun']);

    if ($id_siswa_delete > 0 && $bulan_delete > 0 && $tahun_delete > 0) {
        $stmt_delete_payments = $conn->prepare("DELETE FROM pembayaran WHERE id_siswa = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
        $stmt_delete_payments->bind_param("iii", $id_siswa_delete, $bulan_delete, $tahun_delete);
        if ($stmt_delete_payments->execute()) {
            $_SESSION['message'] = "Semua pembayaran Ikhsan untuk siswa ID {$id_siswa_delete} pada bulan {$bulan_delete}/{$tahun_delete} berhasil dihapus!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error menghapus pembayaran Ikhsan: " . $stmt_delete_payments->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt_delete_payments->close();
    } else {
        $_SESSION['message'] = "Parameter hapus pembayaran Ikhsan tidak valid.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: admin.php?section=pembayaran&spp_tab=spp1_mgmt");
    exit();
}

// --- FUNGSI HAPUS PEMBAYARAN SPP PERTAMA INDIVIDUAL (BULK DELETE) ---
if (isset($_POST['bulk_delete_spp1'])) {
    if (!empty($_POST['selected_spp1_ids']) && is_array($_POST['selected_spp1_ids'])) {
        $ids_to_delete = array_map('intval', $_POST['selected_spp1_ids']);
        $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));
        $types = str_repeat('i', count($ids_to_delete));

        $stmt = $conn->prepare("DELETE FROM pembayaran WHERE id_pembayaran IN ($placeholders)");
        // Menggunakan call_user_func_array untuk bind_param dengan jumlah parameter dinamis
        $stmt->bind_param($types, ...$ids_to_delete);

        if ($stmt->execute()) {
            $_SESSION['message'] = count($ids_to_delete) . " pembayaran Ikhsan berhasil dihapus.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error menghapus pembayaran Ikhsan: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Tidak ada pembayaran Ikhsan yang dipilih untuk dihapus.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: admin.php?section=pembayaran&spp_tab=spp1_mgmt");
    exit();
}


// --- FUNGSI BAYAR SPP KEDUA (MINGGUAN) BERDASARKAN JUMLAH UANG ---
if (isset($_POST['pay_spp2_by_amount'])) {
    $id_siswa_pay_spp2 = intval($_POST['id_siswa_pay_spp2']);
    $amount_received_spp2 = intval($_POST['jumlah_pembayaran_spp2']); // Ini akan menjadi jumlah uang yang dibayarkan
    $spp_per_week_fixed = 5000; // Tarif SPP Kedua per minggu
    $admin_username = $_SESSION['admin_username'] ?? 'unknown'; // Dapatkan username admin yang login

    // Ambil nama siswa untuk pesan kembalian
    $siswa_name_for_change = '';
    $stmt_siswa_name = $conn->prepare("SELECT nama_siswa FROM siswa WHERE id_siswa = ?");
    if ($stmt_siswa_name) {
        $stmt_siswa_name->bind_param("i", $id_siswa_pay_spp2);
        $stmt_siswa_name->execute();
        $result_siswa_name = $stmt_siswa_name->get_result();
        if ($result_siswa_name->num_rows > 0) {
            $siswa_name_for_change = $result_siswa_name->fetch_assoc()['nama_siswa'];
        }
        $stmt_siswa_name->close();
    }

    if ($id_siswa_pay_spp2 <= 0 || $amount_received_spp2 <= 0) {
        $_SESSION['message'] = "Pilih siswa dan masukkan jumlah pembayaran yang valid (lebih dari Rp 0) untuk Infaq.";
        $_SESSION['message_type'] = "error";
        header("Location: admin.php?section=pembayaran&spp_tab=spp2_mgmt");
        exit();
    }

    $weeks_to_mark_paid_potential = floor($amount_received_spp2 / $spp_per_week_fixed);

    if ($weeks_to_mark_paid_potential == 0) {
        $_SESSION['message'] = "Jumlah pembayaran Rp " . number_format($amount_received_spp2, 0, ',', '.') . " tidak cukup untuk satu minggu SPP (Rp " . number_format($spp_per_week_fixed, 0, ',', '.') . ").";
        $_SESSION['message_type'] = "error";
        header("Location: admin.php?section=pembayaran&spp_tab=spp2_mgmt");
        exit();
    }

    // 1. Dapatkan semua minggu yang sudah dibayar oleh siswa ini di tahun akademik ini
    $paid_weeks_for_student = [];
    $stmt_paid_weeks = $conn->prepare("SELECT minggu_ke_akademik FROM pembayaran_spp_kedua WHERE id_siswa = ? AND tahun_akademik_mulai = ?");
    if ($stmt_paid_weeks) {
        $stmt_paid_weeks->bind_param("ii", $id_siswa_pay_spp2, $academic_start_year);
        $stmt_paid_weeks->execute();
        $result_paid_weeks = $stmt_paid_weeks->get_result();
        while ($row = $result_paid_weeks->fetch_assoc()) {
            $paid_weeks_for_student[] = $row['minggu_ke_akademik'];
        }
        $stmt_paid_weeks->close();
    } else {
        $_SESSION['message'] = "Error saat mengambil data pembayaran mingguan siswa.";
        $_SESSION['message_type'] = "error";
        header("Location: admin.php?section=pembayaran&spp_tab=spp2_mgmt");
        exit();
    }

    // 2. Bangun daftar minggu-minggu SPP yang belum dibayar, diurutkan dari yang terlama ke yang terbaru
    $unpaid_spp2_weeks = [];

    // Calculate the Monday of the week containing the academic start date (July 1st)
    $first_monday_of_academic_year = clone $academic_start_date_obj;
    if ($first_monday_of_academic_year->format('N') != 1) { // If not Monday (1)
        $first_monday_of_academic_year->modify('last Monday');
    }

    $current_week_num = 1; // Start with week 1
    // Loop from the first Monday of the academic year's start week
    $current_week_start_date_iterator = clone $first_monday_of_academic_year;

    while ($current_week_start_date_iterator->format('Y-m-d') <= $academic_end_date_obj->format('Y-m-d')) {
        $week_start_date_str = $current_week_start_date_iterator->format('Y-m-d');
        $week_end_date_obj = clone $current_week_start_date_iterator;
        $week_end_date_obj->modify('+6 days'); // This makes it Sunday
        $week_end_date_str = $week_end_date_obj->format('Y-m-d');

        // Only add if the week is within or overlaps the academic year significantly
        // And if it's not already paid
        if (!in_array($current_week_num, $paid_weeks_for_student)) {
            $unpaid_spp2_weeks[] = [
                'minggu_ke_akademik' => $current_week_num,
                'tanggal_mulai_minggu' => $week_start_date_str,
                'tanggal_akhir_minggu' => $week_end_date_str
            ];
        }

        $current_week_start_date_iterator->modify('+1 week');
        $current_week_num++;
    }

    $paid_count_spp2 = 0;
    $spp2_inserted_successfully = true;

    $conn->begin_transaction();

    foreach ($unpaid_spp2_weeks as $week_to_pay) {
        if ($paid_count_spp2 >= $weeks_to_mark_paid_potential) {
            break;
        }

        $stmt_insert_spp2_payment = $conn->prepare("INSERT INTO pembayaran_spp_kedua (id_siswa, tahun_akademik_mulai, minggu_ke_akademik, tanggal_mulai_minggu, tanggal_akhir_minggu, jumlah, created_by_admin_username) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_insert_spp2_payment) {
            $stmt_insert_spp2_payment->bind_param("iiissis", $id_siswa_pay_spp2, $academic_start_year, $week_to_pay['minggu_ke_akademik'], $week_to_pay['tanggal_mulai_minggu'], $week_to_pay['tanggal_akhir_minggu'], $spp_per_week_fixed, $admin_username);
            if ($stmt_insert_spp2_payment->execute()) {
                $paid_count_spp2++;
            } else {
                $spp2_inserted_successfully = false;
                $_SESSION['message'] = "Gagal memasukkan pembayaran Infaq untuk minggu ke-" . $week_to_pay['minggu_ke_akademik'] . ": " . $stmt_insert_spp2_payment->error;
                $_SESSION['message_type'] = "error";
                break;
            }
            $stmt_insert_spp2_payment->close();
        } else {
            $spp2_inserted_successfully = false;
            $_SESSION['message'] = "Gagal menyiapkan statement pembayaran Infaq: " . $conn->error;
            $_SESSION['message_type'] = "error";
            break;
        }
    }
    $total_spp_covered_spp2 = $paid_count_spp2 * $spp_per_week_fixed;
    $change_amount_spp2 = $amount_received_spp2 - $total_spp_covered_spp2; // Kembalian

    if ($spp2_inserted_successfully && $paid_count_spp2 > 0) {
        $conn->commit();
        $_SESSION['message'] = "Pembayaran Infaq berhasil diproses. " . $paid_count_spp2 . " minggu SPP (Rp " . number_format($total_spp_covered_spp2, 0, ',', '.') . ") telah dibayarkan.";
        $_SESSION['message_type'] = "success";

        if ($change_amount_spp2 >= 0) { // Pastikan kembalian tidak negatif
            $_SESSION['last_change_info'] = [
                'siswa_name' => $siswa_name_for_change,
                'spp_type' => 'Mingguan',
                'amount_paid' => $amount_received_spp2,
                'amount_covered' => $total_spp_covered_spp2,
                'change_amount' => $change_amount_spp2
            ];
        }
    } elseif ($spp2_inserted_successfully && $paid_count_spp2 == 0) {
        $conn->rollback();
        $_SESSION['message'] = "Tidak ada Infaq yang perlu dibayar untuk jumlah tersebut, atau semua minggu sudah dibayar.";
        $_SESSION['message_type'] = "error";
    } else {
        $conn->rollback();
    }
    header("Location: admin.php?section=pembayaran&spp_tab=spp2_mgmt");
    exit();
}

// --- FUNGSI HAPUS PEMBAYARAN SPP KEDUA KELOMPOK ---
if (isset($_GET['delete_spp2_group'])) {
    $id_siswa_delete_spp2 = intval($_GET['id_siswa']);
    $minggu_delete_spp2 = intval($_GET['minggu']);
    $tahun_delete_spp2 = intval($_GET['tahun']);

    if ($id_siswa_delete_spp2 > 0 && $minggu_delete_spp2 > 0 && $tahun_delete_spp2 > 0) {
        $stmt_delete_spp2_payments = $conn->prepare("DELETE FROM pembayaran_spp_kedua WHERE id_siswa = ? AND minggu_ke_akademik = ? AND tahun_akademik_mulai = ?");
        $stmt_delete_spp2_payments->bind_param("iii", $id_siswa_delete_spp2, $minggu_delete_spp2, $tahun_delete_spp2);

        if ($stmt_delete_spp2_payments->execute()) {
            $_SESSION['message'] = "Pembayaran Infaq untuk siswa ID {$id_siswa_delete_spp2} pada minggu ke-{$minggu_delete_spp2} tahun akademik {$tahun_delete_spp2} berhasil dihapus!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error menghapus pembayaran Infaq: " . $stmt_delete_spp2_payments->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt_delete_spp2_payments->close();
    } else {
        $_SESSION['message'] = "Parameter hapus pembayaran Infaq tidak valid.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: admin.php?section=pembayaran&spp_tab=spp2_mgmt");
    exit();
}

// --- FUNGSI HAPUS PEMBAYARAN SPP KEDUA INDIVIDUAL (BULK DELETE) ---
if (isset($_POST['bulk_delete_spp2'])) {
    if (!empty($_POST['selected_spp2_ids']) && is_array($_POST['selected_spp2_ids'])) {
        $ids_to_delete = array_map('intval', $_POST['selected_spp2_ids']);
        $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));
        $types = str_repeat('i', count($ids_to_delete));

        $stmt = $conn->prepare("DELETE FROM pembayaran_spp_kedua WHERE id_pembayaran_spp_kedua IN ($placeholders)");
        $stmt->bind_param($types, ...$ids_to_delete);

        if ($stmt->execute()) {
            $_SESSION['message'] = count($ids_to_delete) . " pembayaran Infaq berhasil dihapus.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error menghapus pembayaran Infaq: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Tidak ada pembayaran Infaq yang dipilih untuk dihapus.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: admin.php?section=pembayaran&spp_tab=spp2_mgmt");
    exit();
}

// --- FUNGSI PEMBAYARAN BIAYA LAIN ---
if (isset($_POST['pay_biaya_lain'])) {
    $id_siswa_pay_biaya_lain = intval($_POST['id_siswa_pay_biaya_lain']);
    $id_biaya_type = intval($_POST['id_biaya_type']);
    $jumlah_dibayar_biaya_lain = intval($_POST['jumlah_dibayar_biaya_lain']);
    $tanggal_pembayaran_biaya_lain = trim($_POST['tanggal_pembayaran_biaya_lain']);
    $admin_username = $_SESSION['admin_username'] ?? 'unknown';

    if ($id_siswa_pay_biaya_lain <= 0 || $id_biaya_type <= 0 || $jumlah_dibayar_biaya_lain <= 0 || empty($tanggal_pembayaran_biaya_lain)) {
        $_SESSION['message'] = "Semua field pembayaran biaya lain harus diisi dengan valid.";
        $_SESSION['message_type'] = "error";
        header("Location: admin.php?section=pembayaran&spp_tab=biaya_lain_mgmt");
        exit();
    }

    // Ambil nama siswa dan nama biaya untuk pesan kembalian
    $siswa_name_for_change = '';
    $biaya_name_for_change = '';

    $stmt_siswa_name = $conn->prepare("SELECT nama_siswa FROM siswa WHERE id_siswa = ?");
    if ($stmt_siswa_name) {
        $stmt_siswa_name->bind_param("i", $id_siswa_pay_biaya_lain);
        $stmt_siswa_name->execute();
        $result_siswa_name = $stmt_siswa_name->get_result();
        if ($result_siswa_name->num_rows > 0) {
            $siswa_name_for_change = $result_siswa_name->fetch_assoc()['nama_siswa'];
        }
        $stmt_siswa_name->close();
    }

    $stmt_biaya_name = $conn->prepare("SELECT nama_biaya FROM biaya_lain WHERE id_biaya = ?");
    if ($stmt_biaya_name) {
        $stmt_biaya_name->bind_param("i", $id_biaya_type);
        $stmt_biaya_name->execute();
        $result_biaya_name = $stmt_biaya_name->get_result();
        if ($result_biaya_name->num_rows > 0) {
            $biaya_name_for_change = $result_biaya_name->fetch_assoc()['nama_biaya'];
        }
        $stmt_biaya_name->close();
    }


    $stmt = $conn->prepare("INSERT INTO pembayaran_biaya_lain (id_siswa, id_biaya, jumlah_dibayar, tanggal_pembayaran, created_by_admin_username) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiiss", $id_siswa_pay_biaya_lain, $id_biaya_type, $jumlah_dibayar_biaya_lain, $tanggal_pembayaran_biaya_lain, $admin_username);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Pembayaran biaya lain ('{$biaya_name_for_change}') sebesar Rp " . number_format($jumlah_dibayar_biaya_lain, 0, ',', '.') . " untuk siswa {$siswa_name_for_change} berhasil dicatat!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error mencatat pembayaran biaya lain: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Gagal menyiapkan statement pembayaran biaya lain: " . $conn->error;
        $_SESSION['message_type'] = "error";
    }
    header("Location: admin.php?section=pembayaran&spp_tab=biaya_lain_mgmt");
    exit();
}

// --- FUNGSI HAPUS PEMBAYARAN BIAYA LAIN INDIVIDUAL (BULK DELETE) ---
if (isset($_POST['bulk_delete_biaya_lain'])) {
    if (!empty($_POST['selected_biaya_lain_ids']) && is_array($_POST['selected_biaya_lain_ids'])) {
        $ids_to_delete = array_map('intval', $_POST['selected_biaya_lain_ids']);
        $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));
        $types = str_repeat('i', count($ids_to_delete));

        $stmt = $conn->prepare("DELETE FROM pembayaran_biaya_lain WHERE id_pembayaran_biaya_lain IN ($placeholders)");
        $stmt->bind_param($types, ...$ids_to_delete);

        if ($stmt->execute()) {
            $_SESSION['message'] = count($ids_to_delete) . " pembayaran biaya lain berhasil dihapus.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error menghapus pembayaran biaya lain: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Tidak ada pembayaran biaya lain yang dipilih untuk dihapus.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: admin.php?section=pembayaran&spp_tab=biaya_lain_mgmt");
    exit();
}


// Mengatur tampilan default admin
$admin_section = isset($_GET['section']) ? $_GET['section'] : 'dashboard'; // Default ke dashboard
$spp_tab_active = isset($_GET['spp_tab']) ? $_GET['spp_tab'] : 'spp1_mgmt'; // Default ke spp1_mgmt

// Variabel untuk fitur search, sort, dan pagination
$search_query = $_GET['search'] ?? '';
$order_by = $_GET['order_by'] ?? 'tanggal_desc'; // Default urutan tanggal terbaru
$records_per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10; // Default 10 data per halaman
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Default halaman 1

// Pastikan records_per_page adalah nilai yang valid
if (!in_array($records_per_page, [10, 20, 50, 100, 1000, 10000, 100000])) { // Tambahkan opsi lain jika perlu
    $records_per_page = 10;
}

$offset = ($current_page - 1) * $records_per_page;

// Mendapatkan daftar siswa untuk dropdown di form pembayaran dan manajemen siswa
$siswa_list = [];
// SELECT id_siswa, nama_siswa, login_code FROM siswa ORDER BY nama_siswa ASC
$sql_siswa = "SELECT id_siswa, nama_siswa, login_code FROM siswa ORDER BY nama_siswa ASC";
$result_siswa = $conn->query($sql_siswa);
if ($result_siswa->num_rows > 0) {
    while ($row = $result_siswa->fetch_assoc()) {
        $siswa_list[] = $row;
    }
}

// Mendapatkan daftar biaya lain untuk dropdown
$biaya_lain_list_dropdown = [];
$sql_biaya_lain_dropdown = "SELECT id_biaya, nama_biaya FROM biaya_lain ORDER BY nama_biaya ASC";
$result_biaya_lain_dropdown = $conn->query($sql_biaya_lain_dropdown);
if ($result_biaya_lain_dropdown->num_rows > 0) {
    while ($row = $result_biaya_lain_dropdown->fetch_assoc()) {
        $biaya_lain_list_dropdown[] = $row;
    }
}


// Inisialisasi $edit_data sebagai array kosong untuk menghindari warning
$edit_data = [];

// Mendapatkan data untuk ditampilkan berdasarkan section
switch ($admin_section) {
    case 'siswa':
        // Data siswa sudah diambil di $siswa_list di atas
        if (isset($_GET['edit']) && $_GET['edit'] == 'siswa' && isset($_GET['id'])) {
            $edit_id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT id_siswa, nama_siswa, login_code FROM siswa WHERE id_siswa = ?");
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
        $biaya_lain_list = []; // Inisialisasi untuk section ini
        $sql = "SELECT id_biaya, nama_biaya, jumlah FROM biaya_lain ORDER BY nama_biaya ASC";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $biaya_lain_list[] = $row;
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
        $libur_list = []; // Inisialisasi untuk section ini
        $sql = "SELECT id_libur, tanggal, keterangan FROM libur WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal ASC";
        $stmt_libur = $conn->prepare($sql);
        $stmt_libur->bind_param("ss", $academic_start_date_str, $academic_end_date_str);
        $stmt_libur->execute();
        $result_libur = $stmt_libur->get_result();
        if ($result_libur->num_rows > 0) {
            while ($row = $result_libur->fetch_assoc()) {
                $libur_list[] = $row;
            }
        }
        $stmt_libur->close();

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
        // Logika untuk pembayaran ada di bawah, di luar switch ini untuk memudahkan penanganan pagination/search
        break;
    case 'dashboard':
        // Kueri untuk dashboard
        $result_total_siswa = $conn->query("SELECT COUNT(*) AS total_siswa FROM siswa");
        $total_siswa = $result_total_siswa->fetch_assoc()['total_siswa'];

        $result_total_libur = $conn->query("SELECT COUNT(*) AS total_libur FROM libur WHERE tanggal BETWEEN '{$academic_start_date_str}' AND '{$academic_end_date_str}'");
        $total_libur = $result_total_libur->fetch_assoc()['total_libur'];

        // --- Data untuk Tabel Dashboard Kesimpulan ---
        $dashboard_summary = [];

        // Ambil semua siswa
        $sql_all_siswa = "SELECT id_siswa, nama_siswa FROM siswa ORDER BY nama_siswa ASC";
        $result_all_siswa = $conn->query($sql_all_siswa);
        $all_siswa_data = [];
        if ($result_all_siswa->num_rows > 0) {
            while ($row = $result_all_siswa->fetch_assoc()) {
                $all_siswa_data[$row['id_siswa']] = $row['nama_siswa'];
                $dashboard_summary[$row['id_siswa']] = [
                    'nama_siswa' => $row['nama_siswa'],
                    'spp_harian_dibayar' => 0,
                    'spp_harian_kekurangan' => 0,
                    'spp_harian_kembalian' => '-', // Placeholder, kembalian ditangani saat input
                    'spp_mingguan_dibayar' => 0,
                    'spp_mingguan_kekurangan' => 0,
                    'spp_mingguan_kembalian' => '-', // Placeholder, kembalian ditangani saat input
                ];
            }
        }

        // Ambil semua jenis biaya lain yang ada untuk header dinamis
        $biaya_lain_types = [];
        $sql_biaya_types = "SELECT id_biaya, nama_biaya FROM biaya_lain ORDER BY nama_biaya ASC";
        $result_biaya_types = $conn->query($sql_biaya_types);
        if ($result_biaya_types->num_rows > 0) {
            while ($row = $result_biaya_types->fetch_assoc()) {
                $biaya_lain_types[$row['id_biaya']] = $row['nama_biaya'];
                // Inisialisasi kolom biaya lain untuk setiap siswa
                foreach ($dashboard_summary as $id_siswa => &$data) {
                    $data['biaya_lain_' . $row['id_biaya']] = 0;
                }
                unset($data); // Putuskan referensi
            }
        }


        // Hitung total hari sekolah yang seharusnya dibayar per tahun akademik (misal: 240 hari, Senin-Kamis, tanpa libur)
        $total_spp_harian_seharusnya = 0;
        $current_date_calc = clone $academic_start_date_obj;
        $end_date_calc_exclusive = clone $academic_end_date_obj;
        $end_date_calc_exclusive->modify('+1 day');
        $holidays_for_calc = [];
        $stmt_holidays_calc = $conn->prepare("SELECT tanggal FROM libur WHERE tanggal BETWEEN ? AND ?");
        if ($stmt_holidays_calc) {
            $stmt_holidays_calc->bind_param("ss", $academic_start_date_str, $academic_end_date_str);
            $stmt_holidays_calc->execute();
            $result_holidays_calc = $stmt_holidays_calc->get_result();
            while ($row = $result_holidays_calc->fetch_assoc()) {
                $holidays_for_calc[] = $row['tanggal'];
            }
            $stmt_holidays_calc->close();
        }

        while ($current_date_calc < $end_date_calc_exclusive) {
            $date_str = $current_date_calc->format('Y-m-d');
            $day_of_week = $current_date_calc->format('w'); // 0=Minggu, 5=Jumat
            if ($day_of_week != 5 && !in_array($date_str, $holidays_for_calc)) { // Bukan Jumat dan bukan hari libur
                $total_spp_harian_seharusnya += 500; // Asumsi Rp 500 per hari
            }
            $current_date_calc->modify('+1 day');
        }

        // Hitung total minggu yang seharusnya dibayar per tahun akademik (misal: 40 minggu)
        $total_spp_mingguan_seharusnya = 0;
        // Calculate the Monday of the week containing the academic start date (July 1st)
        $first_monday_of_academic_year_for_calc = clone $academic_start_date_obj;
        if ($first_monday_of_academic_year_for_calc->format('N') != 1) { // If not Monday (1)
            $first_monday_of_academic_year_for_calc->modify('last Monday');
        }

        $current_week_calc = clone $first_monday_of_academic_year_for_calc;

        while ($current_week_calc->format('Y-m-d') <= $academic_end_date_obj->format('Y-m-d')) {
            $start_of_current_week = clone $current_week_calc;
            // Only count if the week starts before or within the academic end date
            if ($start_of_current_week > $academic_end_date_obj) {
                break;
            }
            $total_spp_mingguan_seharusnya += 5000; // Asumsi Rp 5000 per minggu
            $current_week_calc->modify('+1 week');
        }


        // Ambil data pembayaran SPP Harian
        $sql_spp1_summary = "
            SELECT id_siswa, SUM(jumlah) as total_dibayar
            FROM pembayaran
            WHERE tanggal BETWEEN ? AND ?
            GROUP BY id_siswa
        ";
        $stmt_spp1_summary = $conn->prepare($sql_spp1_summary);
        $stmt_spp1_summary->bind_param("ss", $academic_start_date_str, $academic_end_date_str);
        $stmt_spp1_summary->execute();
        $result_spp1_summary = $stmt_spp1_summary->get_result();
        while ($row = $result_spp1_summary->fetch_assoc()) {
            if (isset($dashboard_summary[$row['id_siswa']])) {
                $dashboard_summary[$row['id_siswa']]['spp_harian_dibayar'] = $row['total_dibayar'];
                $kekurangan = $total_spp_harian_seharusnya - $row['total_dibayar'];
                $dashboard_summary[$row['id_siswa']]['spp_harian_kekurangan'] = max(0, $kekurangan); // Kekurangan tidak boleh negatif
            }
        }
        $stmt_spp1_summary->close();

        // Ambil data pembayaran SPP Mingguan
        $sql_spp2_summary = "
            SELECT id_siswa, SUM(jumlah) as total_dibayar
            FROM pembayaran_spp_kedua
            WHERE tahun_akademik_mulai = ?
            GROUP BY id_siswa
        ";
        $stmt_spp2_summary = $conn->prepare($sql_spp2_summary);
        $stmt_spp2_summary->bind_param("i", $academic_start_year);
        $stmt_spp2_summary->execute();
        $result_spp2_summary = $stmt_spp2_summary->get_result();
        while ($row = $result_spp2_summary->fetch_assoc()) {
            if (isset($dashboard_summary[$row['id_siswa']])) {
                $dashboard_summary[$row['id_siswa']]['spp_mingguan_dibayar'] = $row['total_dibayar'];
                $kekurangan = $total_spp_mingguan_seharusnya - $row['total_dibayar'];
                $dashboard_summary[$row['id_siswa']]['spp_mingguan_kekurangan'] = max(0, $kekurangan); // Kekurangan tidak boleh negatif
            }
        }
        $stmt_spp2_summary->close();

        // Ambil data pembayaran Biaya Lain dari tabel baru
        $sql_biaya_lain_payment_summary = "
            SELECT pbl.id_siswa, pbl.id_biaya, SUM(pbl.jumlah_dibayar) as total_dibayar
            FROM pembayaran_biaya_lain pbl
            GROUP BY pbl.id_siswa, pbl.id_biaya
        ";
        $result_biaya_lain_payment_summary = $conn->query($sql_biaya_lain_payment_summary);
        if ($result_biaya_lain_payment_summary->num_rows > 0) {
            while ($row = $result_biaya_lain_payment_summary->fetch_assoc()) {
                if (isset($dashboard_summary[$row['id_siswa']]) && isset($biaya_lain_types[$row['id_biaya']])) {
                    $dashboard_summary[$row['id_siswa']]['biaya_lain_' . $row['id_biaya']] = $row['total_dibayar'];
                }
            }
        }
        break;
}

// --- LOGIKA UNTUK TAB SPP PERTAMA (HARIAN) ---
$spp1_payments_summary = [];
$total_spp1_records = 0;

if ($admin_section == 'pembayaran' && $spp_tab_active == 'spp1_mgmt') {
    // Bangun query dasar
    $sql_spp1_base = "
        SELECT
            p.id_pembayaran,
            s.id_siswa, -- Tambahkan id_siswa untuk link hapus grup
            s.nama_siswa,
            p.tanggal,
            p.jumlah,
            p.created_at_pembayaran,
            p.created_by_admin_username
        FROM
            pembayaran p
        JOIN
            siswa s ON p.id_siswa = s.id_siswa
        WHERE
            p.tanggal BETWEEN ? AND ?
    ";
    $params = [$academic_start_date_str, $academic_end_date_str];
    $types = "ss";

    // Tambahkan kondisi pencarian
    if (!empty($search_query)) {
        $sql_spp1_base .= " AND s.nama_siswa LIKE ?";
        $params[] = "%" . $search_query . "%";
        $types .= "s";
    }

    // Hitung total records untuk pagination
    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM ($sql_spp1_base) AS subquery");
    $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $stmt_count->bind_result($total_spp1_records);
    $stmt_count->fetch();
    $stmt_count->close();

    // Tambahkan ORDER BY
    $order_sql = "";
    switch ($order_by) {
        case 'tanggal_asc':
            $order_sql = " ORDER BY p.tanggal ASC, s.nama_siswa ASC";
            break;
        case 'nama_siswa_asc':
            $order_sql = " ORDER BY s.nama_siswa ASC, p.tanggal ASC";
            break;
        case 'nama_siswa_desc':
            $order_sql = " ORDER BY s.nama_siswa DESC, p.tanggal DESC";
            break;
        case 'tanggal_desc':
        default:
            $order_sql = " ORDER BY p.tanggal DESC, s.nama_siswa DESC";
            break;
    }

    // Tambahkan LIMIT dan OFFSET untuk pagination
    $sql_spp1_full = $sql_spp1_base . $order_sql . " LIMIT ? OFFSET ?";
    $params[] = $records_per_page;
    $params[] = $offset;
    $types .= "ii";

    $stmt_spp1_payments = $conn->prepare($sql_spp1_full);
    $stmt_spp1_payments->bind_param($types, ...$params);
    $stmt_spp1_payments->execute();
    $result_spp1_payments = $stmt_spp1_payments->get_result();

    if ($result_spp1_payments->num_rows > 0) {
        while ($row = $result_spp1_payments->fetch_assoc()) {
            $spp1_payments_summary[] = $row;
        }
    }
    $stmt_spp1_payments->close();
}

// --- LOGIKA UNTUK TAB SPP KEDUA (MINGGUAN) ---
$spp2_payments_summary = [];
$total_spp2_records = 0;

if ($admin_section == 'pembayaran' && $spp_tab_active == 'spp2_mgmt') {
    // Bangun query dasar
    $sql_spp2_base = "
        SELECT
            psk.id_pembayaran_spp_kedua,
            s.nama_siswa,
            psk.id_siswa,
            psk.tahun_akademik_mulai,
            psk.minggu_ke_akademik,
            psk.tanggal_mulai_minggu,
            psk.tanggal_akhir_minggu,
            psk.jumlah,
            psk.created_at_pembayaran,
            psk.created_by_admin_username
        FROM
            pembayaran_spp_kedua psk
        JOIN
            siswa s ON psk.id_siswa = s.id_siswa
        WHERE
            psk.tahun_akademik_mulai = ?
    ";
    $params = [$academic_start_year];
    $types = "i";

    // Tambahkan kondisi pencarian
    if (!empty($search_query)) {
        $sql_spp2_base .= " AND s.nama_siswa LIKE ?";
        $params[] = "%" . $search_query . "%";
        $types .= "s";
    }

    // Hitung total records untuk pagination
    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM ($sql_spp2_base) AS subquery");
    $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $stmt_count->bind_result($total_spp2_records);
    $stmt_count->fetch();
    $stmt_count->close();

    // Tambahkan ORDER BY
    $order_sql = "";
    switch ($order_by) {
        case 'tanggal_asc':
            $order_sql = " ORDER BY psk.tanggal_mulai_minggu ASC, s.nama_siswa ASC";
            break;
        case 'nama_siswa_asc':
            $order_sql = " ORDER BY s.nama_siswa ASC, psk.tanggal_mulai_minggu ASC";
            break;
        case 'nama_siswa_desc':
            $order_sql = " ORDER BY s.nama_siswa DESC, psk.tanggal_mulai_minggu DESC";
            break;
        case 'tanggal_desc':
        default:
            $order_sql = " ORDER BY psk.tanggal_mulai_minggu DESC, s.nama_siswa DESC";
            break;
    }

    // Tambahkan LIMIT dan OFFSET untuk pagination
    $sql_spp2_full = $sql_spp2_base . $order_sql . " LIMIT ? OFFSET ?";
    $params[] = $records_per_page;
    $params[] = $offset;
    $types .= "ii";

    $stmt_spp2_payments = $conn->prepare($sql_spp2_full);
    $stmt_spp2_payments->bind_param($types, ...$params);
    $stmt_spp2_payments->execute();
    $result_spp2_payments = $stmt_spp2_payments->get_result();

    if ($result_spp2_payments->num_rows > 0) {
        while ($row = $result_spp2_payments->fetch_assoc()) {
            $spp2_payments_summary[] = $row;
        }
    }
    $stmt_spp2_payments->close();
}

// --- LOGIKA UNTUK TAB BIAYA LAIN (PEMBAYARAN) ---
$biaya_lain_payments_summary = [];
$total_biaya_lain_records = 0;

if ($admin_section == 'pembayaran' && $spp_tab_active == 'biaya_lain_mgmt') {
    // Bangun query dasar
    $sql_biaya_lain_base = "
        SELECT
            pbl.id_pembayaran_biaya_lain,
            s.nama_siswa,
            bl.nama_biaya,
            pbl.jumlah_dibayar,
            pbl.tanggal_pembayaran,
            pbl.created_by_admin_username,
            pbl.created_at_pembayaran
        FROM
            pembayaran_biaya_lain pbl
        JOIN
            siswa s ON pbl.id_siswa = s.id_siswa
        JOIN
            biaya_lain bl ON pbl.id_biaya = bl.id_biaya
        WHERE
            pbl.tanggal_pembayaran BETWEEN ? AND ?
    ";
    $params = [$academic_start_date_str, $academic_end_date_str];
    $types = "ss";

    // Tambahkan kondisi pencarian
    if (!empty($search_query)) {
        $sql_biaya_lain_base .= " AND (s.nama_siswa LIKE ? OR bl.nama_biaya LIKE ?)";
        $params[] = "%" . $search_query . "%";
        $params[] = "%" . $search_query . "%";
        $types .= "ss";
    }

    // Hitung total records untuk pagination
    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM ($sql_biaya_lain_base) AS subquery");
    $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $stmt_count->bind_result($total_biaya_lain_records);
    $stmt_count->fetch();
    $stmt_count->close();

    // Tambahkan ORDER BY
    $order_sql = "";
    switch ($order_by) {
        case 'tanggal_asc':
            $order_sql = " ORDER BY pbl.tanggal_pembayaran ASC, s.nama_siswa ASC";
            break;
        case 'nama_siswa_asc':
            $order_sql = " ORDER BY s.nama_siswa ASC, pbl.tanggal_pembayaran ASC";
            break;
        case 'nama_siswa_desc':
            $order_sql = " ORDER BY s.nama_siswa DESC, pbl.tanggal_pembayaran DESC";
            break;
        case 'tanggal_desc':
        default:
            $order_sql = " ORDER BY pbl.tanggal_pembayaran DESC, s.nama_siswa DESC";
            break;
    }

    // Tambahkan LIMIT dan OFFSET untuk pagination
    $sql_biaya_lain_full = $sql_biaya_lain_base . $order_sql . " LIMIT ? OFFSET ?";
    $params[] = $records_per_page;
    $params[] = $offset;
    $types .= "ii";

    $stmt_biaya_lain_payments = $conn->prepare($sql_biaya_lain_full);
    $stmt_biaya_lain_payments->bind_param($types, ...$params);
    $stmt_biaya_lain_payments->execute();
    $result_biaya_lain_payments = $stmt_biaya_lain_payments->get_result();

    if ($result_biaya_lain_payments->num_rows > 0) {
        while ($row = $result_biaya_lain_payments->fetch_assoc()) {
            $biaya_lain_payments_summary[] = $row;
        }
    }
    $stmt_biaya_lain_payments->close();
}


// Fungsi untuk membuat link pagination
function create_pagination_link($base_url, $page, $per_page, $search_q, $order_by_q, $section_q, $spp_tab_q)
{
    // Pastikan page tidak kurang dari 1
    if ($page < 1) $page = 1;

    $query_params = [
        'section' => $section_q,
        'spp_tab' => $spp_tab_q,
        'per_page' => $per_page,
        'page' => $page
    ];
    if (!empty($search_q)) {
        $query_params['search'] = $search_q;
    }
    if (!empty($order_by_q)) {
        $query_params['order_by'] = $order_by_q;
    }
    return $base_url . '?' . http_build_query($query_params);
}

$total_spp_harian_seharusnya = 0;
$total_hari_masuk = 0;
$current_date_calc = clone $academic_start_date_obj;
$end_date_calc_exclusive = clone $academic_end_date_obj;
$end_date_calc_exclusive->modify('+1 day');
$holidays_for_calc = [];
$stmt_holidays_calc = $conn->prepare("SELECT tanggal FROM libur WHERE tanggal BETWEEN ? AND ?");
if ($stmt_holidays_calc) {
    $stmt_holidays_calc->bind_param("ss", $academic_start_date_str, $academic_end_date_str);
    $stmt_holidays_calc->execute();
    $result_holidays_calc = $stmt_holidays_calc->get_result();
    while ($row = $result_holidays_calc->fetch_assoc()) {
        $holidays_for_calc[] = $row['tanggal'];
    }
    $stmt_holidays_calc->close();
}

while ($current_date_calc < $end_date_calc_exclusive) {
    $date_str = $current_date_calc->format('Y-m-d');
    $day_of_week = $current_date_calc->format('w'); // 0=Minggu, 5=Jumat
    if ($day_of_week != 5 && !in_array($date_str, $holidays_for_calc)) { // Bukan Jumat dan bukan hari libur
        $total_spp_harian_seharusnya += 500; // Asumsi Rp 500 per hari
        $total_hari_masuk++; // Tambah hari masuk
    }
    $current_date_calc->modify('+1 day');
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta name="description" content="Website resmi TPQ Aisyiyah Wuled. Sistem pembayaran SPP, informasi siswa, biaya lain, jadwal kegiatan, dan profil TPQ Aisyiyah Wuled.">
    <meta name="keywords" content="TPQ, Aisyiyah Wuled, SPP, pembayaran, sekolah, aisyiyah, infaq, ikhsan, pendidikan, Islam">
    <meta name="author" content="TPQ Aisyiyah Wuled">
    <meta property="og:title" content="TPQ Aisyiyah Wuled - Sistem Pembayaran SPP Siswa" />
    <meta property="og:description" content="Website resmi TPQ Aisyiyah Wuled untuk pembayaran SPP, informasi siswa, biaya lain, jadwal kegiatan, dan profil TPQ Aisyiyah Wuled." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://tpq-aisyiah-wuled.wuaze.com/" />
    <meta property="og:image" content="https://tpq-aisyiah-wuled.wuaze.com/assets/logo.png" />
    <link rel="canonical" href="https://tpq-aisyiah-wuled.wuaze.com/"/>
    <title>Admin - Sistem Pembayaran SPP TPQ Aisyiah Wuled</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Menggunakan font Inter dan Noto Naskh Arabic seperti di index.php -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Noto+Naskh+Arabic:wght@400;700&display=swap" rel="stylesheet">
    <!-- Material Icons for icons in navigation -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <!-- <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #e0f2f2;
            /* Warna latar belakang hijau muda dari gambar index.php */
        }

        /* Mengatur tinggi maksimum untuk dropdown agar bisa di-scroll */
        select#id_siswa_pay,
        select#id_siswa_pay_spp2,
        select#id_siswa_pay_biaya_lain,
        select#id_biaya_type {
            max-height: 300px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            display: block;
            width: 100%;
        }

        /* Gaya umum untuk container dan card */
        .container {
            max-width: 1200px;
        }

        .card {
            background-color: #ffffff;
            border-radius: 0.75rem;
            /* rounded-lg */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            /* shadow-md */
        }

        .shadow-lg {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .rounded-lg {
            border-radius: 0.5rem;
        }

        .rounded-xl {
            border-radius: 0.75rem;
        }

        /* Custom styles for the admin panel header, matching index.php's aesthetic */
        .admin-header-card {
            background: linear-gradient(90deg, #004d40 0%, #00796b 100%);
            /* Gradien hijau gelap ke teal */
            color: white;
            padding: 2.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .admin-header-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
            z-index: 0;
            opacity: 0.2;
        }

        .admin-header-content {
            position: relative;
            z-index: 1;
        }

        .admin-title-arabic {
            font-family: 'Noto Naskh Arabic', serif;
            /* Font Naskh untuk teks Arab */
            font-size: 2.5rem;
            /* Ukuran lebih besar */
            color: #f0fdf4;
            /* Warna hijau sangat muda untuk kontras */
            margin-bottom: 0.75rem;
            display: block;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            /* Bayangan teks */
            direction: rtl;
            /* Untuk penulisan Arab dari kanan ke kiri */
            line-height: 1.2;
        }

        .admin-main-title {
            font-family: 'Inter', sans-serif;
            /* Tetap Inter untuk teks Latin */
            font-size: 2.5rem;
            /* Ukuran font utama */
            font-weight: 800;
            /* Extra bold */
            color: #ffffff;
            /* Putih bersih */
            margin-bottom: 0.5rem;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        }

        .admin-subtitle {
            font-family: 'Inter', sans-serif;
            font-size: 1.25rem;
            /* Ukuran font untuk teks selamat datang */
            color: #e0e7ff;
            /* Biru muda keunguan */
            font-weight: 600;
        }

        .admin-username {
            color: #b2f5ea;
            /* light teal */
            font-weight: 800;
        }

        /* Navigation Tabs (matching index.php) */
        .tab-button {
            padding: 0.75rem 1.5rem;
            /* py-3 px-6 */
            border-radius: 0.5rem;
            /* rounded-md */
            transition: all 0.3s ease-in-out;
            font-weight: 600;
            /* font-semibold */
            text-decoration: none;
            /* remove underline */
            display: inline-flex;
            /* for better alignment with icon if any */
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            /* prevent wrapping */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            /* Bayangan halus */
        }

        .tab-button.active {
            background: linear-gradient(90deg, #004d40 0%, #00796b 100%);
            /* Gradien hijau gelap ke teal */
            color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            /* Bayangan lebih dalam */
            transform: translateY(-2px);
            /* Sedikit naik */
        }

        .tab-button:not(.active) {
            background-color: #e0f2f1;
            /* Hijau mint sangat muda */
            color: #004d40;
            /* Hijau gelap */
        }

        .tab-button:not(.active):hover {
            background-color: #b2dfdb;
            /* Teal muda */
            color: #004d40;
            /* Hijau gelap */
            transform: translateY(-1px);
            /* Sedikit naik saat hover */
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            /* Bayangan saat hover */
        }

        /* Logout Button (matching index.php) */
        .logout-button-admin {
            background-color: #ef4444;
            /* red-500 */
            color: #ffffff;
            font-weight: 700;
            /* font-bold */
            padding: 0.875rem 1.75rem;
            /* Meningkatkan padding */
            border-radius: 0.375rem;
            /* rounded-md */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            /* shadow-md */
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 1.5rem;
            /* Tambahkan margin atas */
        }

        .logout-button-admin:hover {
            background-color: #dc2626;
            /* red-600 */
            transform: translateY(-1px);
        }

        /* General button styles for forms */
        .btn {
            padding: 0.75rem 1.25rem;
            border-radius: 0.375rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out, transform 0.2s ease-in-out;
        }

        .btn-primary {
            background-color: #00796b;
            /* Teal dari index.php */
            color: white;
        }

        .btn-primary:hover {
            background-color: #004d40;
            /* Darker teal */
            transform: translateY(-1px);
        }

        .btn-danger {
            background-color: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
            transform: translateY(-1px);
        }

        .btn-success {
            background-color: #22c55e;
            color: white;
        }

        .btn-success:hover {
            background-color: #16a34a;
            transform: translateY(-1px);
        }

        .btn-warning {
            background-color: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background-color: #d97706;
            transform: translateY(-1px);
        }

        /* Alert messages */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #34d399;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        /* Table styles */
        .table-container {
            overflow-x: auto;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background-color: #f9fafb;
            color: #374151;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* Form group styles */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group input[type="date"]:focus,
        .form-group select:focus {
            border-color: #00796b;
            /* Teal dari index.php */
            box-shadow: 0 0 0 3px rgba(0, 121, 107, 0.4);
            /* Shadow teal */
            outline: none;
        }

        /* Pagination links */
        .pagination-link {
            display: inline-block;
            padding: 0.5rem 0.8rem;
            margin: 0 0.2rem;
            border-radius: 0.375rem;
            background-color: #e5e7eb;
            color: #4b5563;
            text-decoration: none;
            transition: background-color 0.2s ease-in-out;
        }

        .pagination-link:hover {
            background-color: #d1d5db;
        }

        .pagination-link.active {
            background-color: #00796b;
            /* Teal dari index.php */
            color: white;
        }

        /* Responsive adjustments for admin header */
        @media (max-width: 768px) {
            .admin-header-card {
                padding: 1.5rem;
            }

            .admin-title-arabic {
                font-size: 2rem;
            }

            .admin-main-title {
                font-size: 1.75rem;
            }

            .admin-subtitle {
                font-size: 1rem;
            }

            .logout-button-admin {
                padding: 0.75rem 1.25rem;
                font-size: 0.9rem;
            }
        }
    </style> -->
</head>

<body class="bg-gray-100 text-gray-900 leading-normal tracking-normal">
    <div class="container mx-auto p-4 md:p-8">
        <!-- Top Header Card (Admin) -->
        <div class="admin-header-card mb-8">
            <div class="admin-header-content">
                <!-- PHP akan mengisi $random_arabic_phrase di sini -->
                <p id="adminArabicPhrase" class="admin-title-arabic"></p>
                <h1 class="admin-main-title">Panel Admin Sistem Pembayaran SPP</h1>
                <!-- PHP akan mengisi $_SESSION['admin_username'] di sini -->
                <p class="text-2xl md:text-3xl text-white-600">Selamat Datang <span class=""><?= htmlspecialchars($_SESSION['admin_username']) ?></span>
                <p class="text-md text-white-600 p-3">Tahun Akademik: <span class="font-semibold text-white-700"><?= $academic_start_year ?>/<?= $academic_end_year ?></span></p>
                <a href="admin.php?action=logout" class="logout-button-admin"
                    onclick="return confirm('Apakah Anda yakin ingin logout?');">
                    <span class="material-icons-outlined mr-2 align-middle">logout</span>Logout
                </a>
            </div>
        </div>

        <!-- Navigation Tabs Utama -->
        <div class="bg-white p-5 md:p-6 rounded-xl shadow-lg mb-10">
            <div class="flex flex-wrap justify-center items-center gap-3 sm:gap-4">
                <a href="?section=dashboard" id="navDashboard" class="tab-button">
                    <span class="material-icons-outlined mr-2 align-middle">dashboard</span>Dashboard
                </a>
                <a href="?section=siswa" id="navSiswa" class="tab-button">
                    <span class="material-icons-outlined mr-2 align-middle">people_alt</span>Manajemen Siswa
                </a>
                <a href="?section=biaya_lain" id="navBiayaLain" class="tab-button">
                    <span class="material-icons-outlined mr-2 align-middle">request_quote</span>Manajemen Biaya Lain
                </a>
                <a href="?section=libur" id="navLibur" class="tab-button">
                    <span class="material-icons-outlined mr-2 align-middle">event_busy</span>Manajemen Hari Libur
                </a>
                <a href="?section=pembayaran&spp_tab=spp1_mgmt" id="navPembayaran" class="tab-button">
                    <span class="material-icons-outlined mr-2 align-middle">payments</span>Manajemen SPP
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="p-4 mb-6 rounded-lg text-white font-medium
                <?= $message_type == 'success' ? 'bg-green-500' : 'bg-red-500' ?>" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($change_info): ?>
            <div class="bg-indigo-100 border-l-4 border-indigo-500 text-indigo-700 p-4 rounded-lg shadow-md mb-6" role="alert">
                <p class="font-bold text-lg mb-2">Kembalian Ditemukan!</p>
                <div class="flex flex-col md:flex-row md:justify-between md:items-center text-sm">
                    <p>Siswa: <span class="font-semibold"><?= htmlspecialchars($change_info['siswa_name']) ?></span></p>
                    <p>Jenis SPP: <span class="font-semibold"><?= htmlspecialchars($change_info['spp_type']) ?></span></p>
                    <p>Uang Dibayar: <span class="font-semibold">Rp <?= number_format($change_info['amount_paid'], 0, ',', '.') ?></span></p>
                    <p>SPP Terpakai: <span class="font-semibold">Rp <?= number_format($change_info['amount_covered'], 0, ',', '.') ?></span></p>
                    <p class="text-xl font-extrabold text-indigo-900 mt-2 md:mt-0">Kembalian: Rp <?= number_format($change_info['change_amount'], 0, ',', '.') ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Content based on selected section -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <?php if ($admin_section == 'dashboard'): ?>
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Dashboard Ringkasan Pembayaran</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Total Siswa</h3>
                        <p class="text-3xl font-bold text-teal-700"><?= $total_siswa ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Tahun Akademik Aktif</h3>
                        <p class="text-3xl font-bold text-teal-700"><?= $academic_start_year ?>/<?= $academic_end_year ?></p>
                        <p class="text-gray-600 text-sm">1 Juli <?= $academic_start_year ?> - 30 Juni <?= $academic_end_year ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Total Hari Libur</h3>
                        <p class="text-3xl font-bold text-teal-700"><?= $total_libur ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Total Hari Masuk</h3>
                        <p class="text-3xl font-bold text-teal-700"><?= $total_hari_masuk ?></p>
                    </div>
                </div>

                <h3 class="text-xl font-semibold text-gray-800 mb-4">Ringkasan Pembayaran Per Siswa (Tahun Akademik <?= $academic_start_year ?>/<?= $academic_end_year ?>)</h3>

                <a href="export_dashboard.php" class="inline-block mb-4 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded hover:bg-green-700 transition">
                    Ekspor ke Excel
                </a>

                <?php if (!empty($dashboard_summary)): ?>
                    <div class="overflow-x-auto table-container">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tl-lg">Nama Siswa</th>
                                    <th colspan="3" class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider border-b border-gray-300">Ikhsan</th>
                                    <th colspan="3" class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider border-b border-gray-300">Infaq</th>
                                    <th colspan="<?= count($biaya_lain_types) ?>" class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tr-lg border-b border-gray-300">Biaya Lain</th>
                                </tr>
                                <tr>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Sudah Bayar</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Kekurangan</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Kembalian</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Sudah Bayar</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Kekurangan</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Kembalian</th>
                                    <?php foreach ($biaya_lain_types as $biaya_id => $biaya_name): ?>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider"><?= htmlspecialchars($biaya_name) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($dashboard_summary as $siswa_id => $data): ?>
                                    <tr>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800 font-semibold"><?= htmlspecialchars($data['nama_siswa']) ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800">Rp <?= number_format($data['spp_harian_dibayar'], 0, ',', '.') ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-red-600">Rp <?= number_format($data['spp_harian_kekurangan'], 0, ',', '.') ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($data['spp_harian_kembalian']) ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800">Rp <?= number_format($data['spp_mingguan_dibayar'], 0, ',', '.') ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-red-600">Rp <?= number_format($data['spp_mingguan_kekurangan'], 0, ',', '.') ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($data['spp_mingguan_kembalian']) ?></td>
                                        <?php foreach ($biaya_lain_types as $biaya_id => $biaya_name): ?>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800">Rp <?= number_format($data['biaya_lain_' . $biaya_id], 0, ',', '.') ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">Belum ada data siswa untuk menampilkan ringkasan pembayaran.</p>
                <?php endif; ?>

            <?php elseif ($admin_section == 'siswa'): ?>
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Manajemen Siswa</h2>

                <!-- Form Tambah/Edit Siswa -->
                <form action="admin.php?section=siswa" method="POST" class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <h3 class="text-xl font-semibold mb-4"><?= !empty($edit_data) ? 'Edit Siswa' : 'Tambah Siswa Baru' ?></h3>
                    <?php if (!empty($edit_data)): ?>
                        <input type="hidden" name="id_siswa" value="<?= htmlspecialchars($edit_data['id_siswa'] ?? '') ?>">
                    <?php endif; ?>
                    <div class="mb-4">
                        <label for="nama_siswa" class="block text-gray-700 text-sm font-bold mb-2">Nama Siswa:</label>
                        <input type="text" id="nama_siswa" name="nama_siswa" value="<?= htmlspecialchars($edit_data['nama_siswa'] ?? '') ?>"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div class="mb-4">
                        <label for="login_code" class="block text-gray-700 text-sm font-bold mb-2">Kode Login (Username & Password Siswa):</label>
                        <input type="text" id="login_code" name="login_code" value="<?= htmlspecialchars($edit_data['login_code'] ?? '') ?>"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <p class="text-sm text-gray-500 mt-1">Kode ini akan digunakan siswa untuk login. Pastikan unik.</p>
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" name="<?= !empty($edit_data) ? 'edit_siswa' : 'add_siswa' ?>"
                            class="io bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                            <?= !empty($edit_data) ? 'Perbarui Siswa' : 'Tambah Siswa' ?>
                        </button>
                        <?php if (!empty($edit_data)): ?>
                            <a href="admin.php?section=siswa" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                                Batal
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Fitur Impor Siswa -->
                <div class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <h3 class="text-xl font-semibold mb-4">Impor Data Siswa (dari CSV)</h3>
                    <p class="text-gray-600 text-sm mb-3">
                        Unggah file CSV dengan dua kolom: "nama_siswa" dan "login_code" (tanpa header).
                        Pastikan kode login unik untuk setiap siswa.
                        Contoh format CSV:
                    </p>
                    <pre class="bg-gray-100 p-2 rounded-md text-sm mb-4 border border-gray-300">
nama_siswa_1,kode_1
nama_siswa_2,kode_2
nama_siswa_3,kode_3</pre>
                    <form action="import_siswa.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="csv_file" class="block text-gray-700 text-sm font-bold mb-2">Pilih File CSV:</label>
                            <input type="file" id="csv_file" name="csv_file" accept=".csv" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <button type="submit" name="import_siswa" class="bg-teal-700 hover:bg-teal-600 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                            Impor Siswa dari CSV
                        </button>
                    </form>
                </div>

                <!-- Daftar Siswa -->
                <h3 class="text-xl font-semibold mb-4">Daftar Siswa</h3>
                <?php if (!empty($siswa_list)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white rounded-lg overflow-hidden border border-gray-200">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tl-lg">ID Siswa</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Nama Siswa</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Kode Login</th>
                                    <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tr-lg">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($siswa_list as $siswa): ?>
                                    <tr>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($siswa['id_siswa']) ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($siswa['nama_siswa']) ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-gray-800 font-mono"><?= htmlspecialchars($siswa['login_code']) ?></td>
                                        <td class="py-3 px-4 whitespace-nowrap text-center">
                                            <button onclick="openEditSiswaModal(<?= $siswa['id_siswa'] ?>, '<?= htmlspecialchars($siswa['nama_siswa']) ?>', '<?= htmlspecialchars($siswa['login_code']) ?>')"
                                                class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-1 px-3 rounded-md text-xs transition duration-300">Edit</button>
                                            <a href="admin.php?section=siswa&delete_siswa=<?= htmlspecialchars($siswa['id_siswa']) ?>"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus siswa <?= htmlspecialchars($siswa['nama_siswa']) ?>? Ini akan menghapus semua data pembayaran terkait!');"
                                                class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded-md text-xs transition duration-300">Hapus</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">Belum ada data siswa.</p>
                <?php endif; ?>
        </div>

        <!-- Modal Edit Siswa -->
        <div id="editSiswaModal" class="modal fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
            <div class="modal-content bg-white p-6 rounded-lg shadow-xl w-11/12 md:w-1/2 lg:w-1/3 relative">
                <button class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-2xl font-bold" onclick="closeEditSiswaModal()">&times;</button>
                <h3 class="text-xl font-bold text-gray-800 mb-4">Edit Siswa</h3>
                <form action="admin.php?section=siswa" method="POST">
                    <input type="hidden" id="edit_siswa_id" name="id_siswa">
                    <div class="form-group mb-4">
                        <label for="edit_nama_siswa" class="block text-gray-700 text-sm font-bold mb-2">Nama Siswa:</label>
                        <input type="text" id="edit_nama_siswa" name="nama_siswa" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="form-group mb-4">
                        <label for="edit_login_code" class="block text-gray-700 text-sm font-bold mb-2">Kode Login (Username & Password Siswa):</label>
                        <input type="text" id="edit_login_code" name="login_code" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <p class="text-sm text-gray-500 mt-1">Kode ini akan digunakan siswa untuk login. Pastikan unik.</p>
                    </div>
                    <button type="submit" name="edit_siswa" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">Simpan Perubahan</button>
                </form>
            </div>
        </div>

        <script>
            function openEditSiswaModal(id, nama, loginCode) {
                document.getElementById('edit_siswa_id').value = id;
                document.getElementById('edit_nama_siswa').value = nama;
                document.getElementById('edit_login_code').value = loginCode;
                document.getElementById('editSiswaModal').classList.remove('hidden');
            }

            function closeEditSiswaModal() {
                document.getElementById('editSiswaModal').classList.add('hidden');
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('editSiswaModal');
                if (event.target == modal) {
                    modal.classList.add('hidden');
                }
            }
        </script>

    <?php elseif ($admin_section == 'biaya_lain'): ?>
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Manajemen Biaya Lain (Master Data)</h2>
        <p class="text-gray-600 mb-6">Kelola jenis-jenis biaya lain yang dapat dibayarkan oleh siswa. Ini adalah daftar master, bukan catatan pembayaran per siswa.</p>

        <!-- Form Tambah/Edit Biaya Lain -->
        <form action="admin.php?section=biaya_lain" method="POST" class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
            <h3 class="text-xl font-semibold mb-4"><?= !empty($edit_data) ? 'Edit Biaya Lain' : 'Tambah Biaya Lain Baru' ?></h3>
            <?php if (!empty($edit_data)): ?>
                <input type="hidden" name="id_biaya" value="<?= htmlspecialchars($edit_data['id_biaya'] ?? '') ?>">
            <?php endif; ?>
            <div class="mb-4">
                <label for="nama_biaya" class="block text-gray-700 text-sm font-bold mb-2">Nama Biaya:</label>
                <input type="text" id="nama_biaya" name="nama_biaya" value="<?= htmlspecialchars($edit_data['nama_biaya'] ?? '') ?>"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            <div class="mb-4">
                <label for="jumlah" class="block text-gray-700 text-sm font-bold mb-2">Jumlah Default (Rp):</label>
                <input type="number" id="jumlah" name="jumlah" value="<?= htmlspecialchars($edit_data['jumlah'] ?? '') ?>"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required min="0">
            </div>
            <div class="flex space-x-2">
                <button type="submit" name="<?= !empty($edit_data) ? 'edit_biaya_lain' : 'add_biaya_lain' ?>"
                    class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                    <?= !empty($edit_data) ? 'Perbarui Biaya' : 'Tambah Biaya' ?>
                </button>
                <?php if (!empty($edit_data)): ?>
                    <a href="admin.php?section=biaya_lain" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                        Batal
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Daftar Biaya Lain -->
        <h3 class="text-xl font-semibold mb-4">Daftar Biaya Lain</h3>
        <?php if (!empty($biaya_lain_list)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white rounded-lg overflow-hidden border border-gray-200">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tl-lg">ID Biaya</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Nama Biaya</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Jumlah Default</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tr-lg">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($biaya_lain_list as $biaya): ?>
                            <tr>
                                <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($biaya['id_biaya']) ?></td>
                                <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($biaya['nama_biaya']) ?></td>
                                <td class="py-3 px-4 whitespace-nowrap text-gray-800">Rp <?= number_format($biaya['jumlah'], 0, ',', '.') ?></td>
                                <td class="py-3 px-4 whitespace-nowrap text-center">
                                    <button onclick="openEditBiayaModal(<?= $biaya['id_biaya'] ?>, '<?= htmlspecialchars($biaya['nama_biaya']) ?>', <?= $biaya['jumlah'] ?>)"
                                        class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-1 px-3 rounded-md text-xs transition duration-300">Edit</button>
                                    <a href="admin.php?section=biaya_lain&delete_biaya_lain=<?= htmlspecialchars($biaya['id_biaya']) ?>"
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus biaya <?= htmlspecialchars($biaya['nama_biaya']) ?>? Ini akan menghapus semua data pembayaran terkait!');"
                                        class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded-md text-xs transition duration-300">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-600">Belum ada data biaya lain.</p>
        <?php endif; ?>

        <!-- Modal Edit Biaya Lain -->
        <div id="editBiayaModal" class="modal fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
            <div class="modal-content bg-white p-6 rounded-lg shadow-xl w-11/12 md:w-1/2 lg:w-1/3 relative">
                <button class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-2xl font-bold" onclick="closeEditBiayaModal()">&times;</button>
                <h3 class="text-xl font-bold text-gray-800 mb-4">Edit Biaya Lain</h3>
                <form action="admin.php?section=biaya_lain" method="POST">
                    <input type="hidden" id="edit_biaya_id" name="id_biaya">
                    <div class="form-group mb-4">
                        <label for="edit_nama_biaya" class="block text-gray-700 text-sm font-bold mb-2">Nama Biaya:</label>
                        <input type="text" id="edit_nama_biaya" name="nama_biaya" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="form-group mb-4">
                        <label for="edit_jumlah_biaya" class="block text-gray-700 text-sm font-bold mb-2">Jumlah Default (Rp):</label>
                        <input type="number" id="edit_jumlah_biaya" name="jumlah" required min="0"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <button type="submit" name="edit_biaya_lain" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">Simpan Perubahan</button>
                </form>
            </div>
        </div>

        <script>
            function openEditBiayaModal(id, nama, jumlah) {
                document.getElementById('edit_biaya_id').value = id;
                document.getElementById('edit_nama_biaya').value = nama;
                document.getElementById('edit_jumlah_biaya').value = jumlah;
                document.getElementById('editBiayaModal').classList.remove('hidden');
            }

            function closeEditBiayaModal() {
                document.getElementById('editBiayaModal').classList.add('hidden');
            }

            window.onclick = function(event) {
                const modal = document.getElementById('editBiayaModal');
                if (event.target == modal) {
                    modal.classList.add('hidden');
                }
            }
        </script>

    <?php elseif ($admin_section == 'libur'): ?>
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Manajemen Hari Libur</h2>

        <!-- Form Tambah/Edit Hari Libur (Individual) -->
        <form action="admin.php?section=libur" method="POST" class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
            <h3 class="text-xl font-semibold mb-4"><?= !empty($edit_data) ? 'Edit Hari Libur (Individual)' : 'Tambah Hari Libur Baru (Individual)' ?></h3>
            <?php if (!empty($edit_data)): ?>
                <input type="hidden" name="id_libur" value="<?= htmlspecialchars($edit_data['id_libur'] ?? '') ?>">
            <?php endif; ?>
            <div class="mb-4">
                <label for="tanggal" class="block text-gray-700 text-sm font-bold mb-2">Tanggal:</label>
                <input type="date" id="tanggal" name="tanggal" value="<?= htmlspecialchars($edit_data['tanggal'] ?? date('Y-m-d')) ?>"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            <div class="mb-4">
                <label for="keterangan" class="block text-gray-700 text-sm font-bold mb-2">Keterangan:</label>
                <input type="text" id="keterangan" name="keterangan" value="<?= htmlspecialchars($edit_data['keterangan'] ?? '') ?>"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Contoh: Libur Nasional" required>
            </div>
            <div class="flex space-x-2">
                <button type="submit" name="<?= !empty($edit_data) ? 'edit_libur' : 'add_libur' ?>"
                    class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                    <?= !empty($edit_data) ? 'Perbarui Libur' : 'Tambah Libur' ?>
                </button>
                <?php if (!empty($edit_data)): ?>
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
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            <button type="submit" name="add_libur_range" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                Tambah Libur Rentang
            </button>
        </form>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Daftar Hari Libur (Tahun Akademik <?= htmlspecialchars($academic_start_year) ?> - <?= htmlspecialchars($academic_end_year) ?>)</h3>
            <?php if (!empty($libur_list)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-lg overflow-hidden border border-gray-200">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tl-lg">ID Libur</th>
                                <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Tanggal</th>
                                <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Keterangan</th>
                                <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tr-lg">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($libur_list as $libur): ?>
                                <tr>
                                    <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($libur['id_libur']) ?></td>
                                    <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars(format_date_indo($libur['tanggal'])) ?></td>
                                    <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($libur['keterangan']) ?></td>
                                    <td class="py-3 px-4 whitespace-nowrap text-center">
                                        <button onclick="openEditLiburModal(<?= $libur['id_libur'] ?>, '<?= htmlspecialchars($libur['tanggal']) ?>', '<?= htmlspecialchars($libur['keterangan']) ?>')"
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-1 px-3 rounded-md text-xs transition duration-300">Edit</button>
                                        <a href="admin.php?section=libur&delete_libur=<?= htmlspecialchars($libur['id_libur']) ?>"
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus hari libur <?= htmlspecialchars(format_date_indo($libur['tanggal'])) ?>?');"
                                            class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded-md text-xs transition duration-300">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-600">Belum ada data hari libur untuk tahun akademik ini.</p>
            <?php endif; ?>
        </div>

        <!-- Modal Edit Libur -->
        <div id="editLiburModal" class="modal fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
            <div class="modal-content bg-white p-6 rounded-lg shadow-xl w-11/12 md:w-1/2 lg:w-1/3 relative">
                <button class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-2xl font-bold" onclick="closeEditLiburModal()">&times;</button>
                <h3 class="text-xl font-bold text-gray-800 mb-4">Edit Hari Libur</h3>
                <form action="admin.php?section=libur" method="POST">
                    <input type="hidden" id="edit_libur_id" name="id_libur">
                    <div class="form-group mb-4">
                        <label for="edit_tanggal_libur" class="block text-gray-700 text-sm font-bold mb-2">Tanggal:</label>
                        <input type="date" id="edit_tanggal_libur" name="tanggal" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="form-group mb-4">
                        <label for="edit_keterangan_libur" class="block text-gray-700 text-sm font-bold mb-2">Keterangan:</label>
                        <input type="text" id="edit_keterangan_libur" name="keterangan" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <button type="submit" name="edit_libur" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">Simpan Perubahan</button>
                </form>
            </div>
        </div>

        <script>
            function openEditLiburModal(id, tanggal, keterangan) {
                document.getElementById('edit_libur_id').value = id;
                document.getElementById('edit_tanggal_libur').value = tanggal;
                document.getElementById('edit_keterangan_libur').value = keterangan;
                document.getElementById('editLiburModal').classList.remove('hidden');
            }

            function closeEditLiburModal() {
                document.getElementById('editLiburModal').classList.add('hidden');
            }

            window.onclick = function(event) {
                const modal = document.getElementById('editLiburModal');
                if (event.target == modal) {
                    modal.classList.add('hidden');
                }
            }
        </script>

    <?php elseif ($admin_section == 'pembayaran'): ?>
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Manajemen Pembayaran SPP</h2>

        <!-- Sub-Navigation untuk SPP 1, SPP 2, dan Biaya Lain -->
        <div class="bg-white p-4 rounded-lg shadow-md mb-8 flex flex-wrap justify-center space-x-2 sm:space-x-4">
            <a href="?section=pembayaran&spp_tab=spp1_mgmt"
                class="tab-button px-6 py-3 text-lg transition duration-300 <?= $spp_tab_active == 'spp1_mgmt' ? ' active' : 'text-purple-700 hover:bg-purple-600' ?>">
                Ikhsan
            </a>
            <a href="?section=pembayaran&spp_tab=spp2_mgmt"
                class="tab-button px-6 py-3 text-lg transition duration-300 <?= $spp_tab_active == 'spp2_mgmt' ? 'active' : 'text-gray-600 hover:text-purple-600' ?>">
                Infaq
            </a>
            <a href="?section=pembayaran&spp_tab=biaya_lain_mgmt"
                class="tab-button px-6 py-3 text-lg transition duration-300 <?= $spp_tab_active == 'biaya_lain_mgmt' ? 'active' : 'text-gray-600 hover:text-purple-600' ?>">
                Biaya Lain
            </a>
        </div>

        <div class="tab-content">
            <?php if ($spp_tab_active == 'spp1_mgmt'): ?>
                <h3 class="text-xl font-bold text-gray-800 mb-4">Pembayaran Ikhsan (Tahun Akademik <?= $academic_start_year ?>/<?= $academic_end_year ?>)</h3>

                <div class="bg-gray-50 p-4 rounded-lg shadow-sm mb-6">
                    <h4 class="text-lg font-semibold text-gray-700 mb-3">Input Pembayaran Ikhsan</h4>
                    <form action="admin.php?section=pembayaran&spp_tab=spp1_mgmt" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="form-group">
                            <label for="id_siswa_pay">Pilih Siswa:</label>
                            <select id="id_siswa_pay" name="id_siswa_pay" required class="w-full">
                                <option value="">-- Pilih Siswa --</option>
                                <?php foreach ($siswa_list as $siswa): ?>
                                    <option value="<?= htmlspecialchars($siswa['id_siswa']) ?>"><?= htmlspecialchars($siswa['nama_siswa']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="jumlah_pembayaran">Jumlah Pembayaran (Rp):</label>
                            <input type="number" id="jumlah_pembayaran" name="jumlah_pembayaran" placeholder="Contoh: 5000" required min="0">
                        </div>
                        <div class="flex items-center">
                            <button type="submit" name="pay_spp_by_amount" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out w-full">Proses Pembayaran Ikhsan</button>
                        </div>
                    </form>
                </div>

                <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                    <!-- Search Bar -->
                    <form action="admin.php" method="GET" class="w-full md:w-1/3">
                        <input type="hidden" name="section" value="pembayaran">
                        <input type="hidden" name="spp_tab" value="spp1_mgmt">
                        <input type="hidden" name="per_page" value="<?= htmlspecialchars($records_per_page) ?>">
                        <input type="hidden" name="order_by" value="<?= htmlspecialchars($order_by) ?>">
                        <input type="text" name="search" placeholder="Cari nama siswa..." value="<?= htmlspecialchars($search_query) ?>"
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                    </form>

                    <!-- Sort By -->
                    <form action="admin.php" method="GET" class="w-full md:w-1/4">
                        <input type="hidden" name="section" value="pembayaran">
                        <input type="hidden" name="spp_tab" value="spp1_mgmt">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                        <input type="hidden" name="per_page" value="<?= htmlspecialchars($records_per_page) ?>">
                        <label for="order_by_spp1" class="sr-only">Urutkan Berdasarkan:</label>
                        <select name="order_by" id="order_by_spp1" onchange="this.form.submit()"
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="tanggal_desc" <?= ($order_by == 'tanggal_desc') ? 'selected' : '' ?>>Tanggal Terlama</option>
                            <option value="tanggal_asc" <?= ($order_by == 'tanggal_asc') ? 'selected' : '' ?>>Tanggal Terbaru</option>
                            <option value="nama_siswa_asc" <?= ($order_by == 'nama_siswa_asc') ? 'selected' : '' ?>>Nama Siswa (A-Z)</option>
                            <option value="nama_siswa_desc" <?= ($order_by == 'nama_siswa_desc') ? 'selected' : '' ?>>Nama Siswa (Z-A)</option>
                        </select>
                    </form>

                    <!-- Records per page -->
                    <form action="admin.php" method="GET" class="w-full md:w-1/4">
                        <input type="hidden" name="section" value="pembayaran">
                        <input type="hidden" name="spp_tab" value="spp1_mgmt">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                        <input type="hidden" name="order_by" value="<?= htmlspecialchars($order_by) ?>">
                        <label for="per_page_spp1" class="sr-only">Data per halaman:</label>
                        <select name="per_page" id="per_page_spp1" onchange="this.form.submit()"
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="10" <?= ($records_per_page == 10) ? 'selected' : '' ?>>10 per halaman</option>
                            <option value="20" <?= ($records_per_page == 20) ? 'selected' : '' ?>>20 per halaman</option>
                            <option value="50" <?= ($records_per_page == 50) ? 'selected' : '' ?>>50 per halaman</option>
                            <option value="1000" <?= ($records_per_page == 1000) ? 'selected' : '' ?>>1000 per halaman</option>
                            <option value="10000" <?= ($records_per_page == 10000) ? 'selected' : '' ?>>10000 per halaman</option>
                            <option value="100000" <?= ($records_per_page == 100000) ? 'selected' : '' ?>>100000 per halaman</option>
                        </select>
                    </form>
                </div>

                <!-- Tombol Ekspor SPP Harian -->
                <div class="mb-4 text-right">
                    <a href="export_payments.php?spp_type=spp1&start_date=<?= urlencode($academic_start_date_str) ?>&end_date=<?= urlencode($academic_end_date_str) ?>"
                        class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                        <span class="material-icons-outlined align-middle mr-1">download</span> Ekspor Data Ikhsan
                    </a>
                </div>


                <?php if (!empty($spp1_payments_summary)): ?>
                    <form id="bulkDeleteSpp1Form" action="admin.php?section=pembayaran&spp_tab=spp1_mgmt" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pembayaran Ikhsan yang dipilih?');">
                        <input type="hidden" name="bulk_delete_spp1" value="true">
                        <div class="table-container">
                            <table class="min-w-full">
                                <thead>
                                    <tr>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tl-lg">
                                            <input type="checkbox" id="selectAllSpp1" class="form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out">
                                        </th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Nama Siswa</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Tanggal</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Jumlah</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Admin Input</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Waktu Input</th>
                                        <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tr-lg">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($spp1_payments_summary as $row): ?>
                                        <tr>
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <input type="checkbox" name="selected_spp1_ids[]" value="<?= htmlspecialchars($row['id_pembayaran']) ?>" class="spp1-checkbox form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out">
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars(format_date_indo($row['tanggal'])) ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['created_by_admin_username'] ?? 'N/A') ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['created_at_pembayaran']) ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-center">
                                                <span class="text-gray-500 text-xs">Hapus via checkbox</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out mt-4">Hapus yang Dipilih</button>
                    </form>

                    <!-- Pagination SPP1 -->
                    <div class="mt-6 flex justify-center items-center space-x-2">
                        <?php
                        $total_pages_spp1 = ceil($total_spp1_records / $records_per_page);
                        if ($total_pages_spp1 > 1) {
                            // Previous Page
                            if ($current_page > 1) {
                                echo '<a href="' . create_pagination_link('admin.php', $current_page - 1, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">&laquo; Sebelumnya</a>';
                            }

                            // Page Numbers
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages_spp1, $current_page + 2);

                            if ($start_page > 1) {
                                echo '<a href="' . create_pagination_link('admin.php', 1, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="pagination-link">...</span>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                $active_class = ($i == $current_page) ? 'active' : '';
                                echo '<a href="' . create_pagination_link('admin.php', $i, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link ' . $active_class . '">' . $i . '</a>';
                            }

                            if ($end_page < $total_pages_spp1) {
                                if ($end_page < $total_pages_spp1 - 1) {
                                    echo '<span class="pagination-link">...</span>';
                                }
                                echo '<a href="' . create_pagination_link('admin.php', $total_pages_spp1, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">' . $total_pages_spp1 . '</a>';
                            }

                            // Next Page
                            if ($current_page < $total_pages_spp1) {
                                echo '<a href="' . create_pagination_link('admin.php', $current_page + 1, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">Selanjutnya &raquo;</a>';
                            }
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">Belum ada ringkasan pembayaran Ikhsan.</p>
                <?php endif; ?>

                <script>
                    document.getElementById('selectAllSpp1').addEventListener('change', function() {
                        const checkboxes = document.querySelectorAll('.spp1-checkbox');
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = this.checked;
                        });
                    });
                </script>

            <?php elseif ($spp_tab_active == 'spp2_mgmt'): ?>
                <h3 class="text-xl font-bold text-gray-800 mb-4">Pembayaran Infaq (Tahun Akademik <?= $academic_start_year ?>/<?= $academic_end_year ?>)</h3>

                <div class="bg-gray-50 p-4 rounded-lg shadow-sm mb-6">
                    <h4 class="text-lg font-semibold text-gray-700 mb-3">Input Pembayaran Infaq</h4>
                    <!-- Perbaikan: Form diubah agar sesuai dengan logika pay_spp2_by_amount -->
                    <form action="admin.php?section=pembayaran&spp_tab=spp2_mgmt" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="form-group">
                            <label for="id_siswa_pay_spp2">Pilih Siswa:</label>
                            <select id="id_siswa_pay_spp2" name="id_siswa_pay_spp2" required class="w-full">
                                <option value="">-- Pilih Siswa --</option>
                                <?php foreach ($siswa_list as $siswa): ?>
                                    <option value="<?= htmlspecialchars($siswa['id_siswa']) ?>"><?= htmlspecialchars($siswa['nama_siswa']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="jumlah_pembayaran_spp2">Jumlah Pembayaran (Rp):</label>
                            <input type="number" id="jumlah_pembayaran_spp2" name="jumlah_pembayaran_spp2" placeholder="Contoh: 5000" required min="0">
                        </div>
                        <div class="flex items-center">
                            <button type="submit" name="pay_spp2_by_amount" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out w-full">Proses Pembayaran Infaq</button>
                        </div>
                    </form>
                </div>

                <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                    <!-- Search Bar -->
                    <form action="admin.php" method="GET" class="w-full md:w-1/3">
                        <input type="hidden" name="section" value="pembayaran">
                        <input type="hidden" name="spp_tab" value="spp2_mgmt">
                        <input type="hidden" name="per_page" value="<?= htmlspecialchars($records_per_page) ?>">
                        <input type="hidden" name="order_by" value="<?= htmlspecialchars($order_by) ?>">
                        <input type="text" name="search" placeholder="Cari nama siswa..." value="<?= htmlspecialchars($search_query) ?>"
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                    </form>

                    <!-- Sort By -->
                    <form action="admin.php" method="GET" class="w-full md:w-1/4">
                        <input type="hidden" name="section" value="pembayaran">
                        <input type="hidden" name="spp_tab" value="spp2_mgmt">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                        <input type="hidden" name="per_page" value="<?= htmlspecialchars($records_per_page) ?>">
                        <label for="order_by_spp2" class="sr-only">Urutkan Berdasarkan:</label>
                        <select name="order_by" id="order_by_spp2" onchange="this.form.submit()"
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="tanggal_desc" <?= ($order_by == 'tanggal_desc') ? 'selected' : '' ?>>Tanggal Terbaru</option>
                            <option value="tanggal_asc" <?= ($order_by == 'tanggal_asc') ? 'selected' : '' ?>>Tanggal Terlama</option>
                            <option value="nama_siswa_asc" <?= ($order_by == 'nama_siswa_asc') ? 'selected' : '' ?>>Nama Siswa (A-Z)</option>
                            <option value="nama_siswa_desc" <?= ($order_by == 'nama_siswa_desc') ? 'selected' : '' ?>>Nama Siswa (Z-A)</option>
                        </select>
                    </form>

                    <!-- Records per page -->
                    <form action="admin.php" method="GET" class="w-full md:w-1/4">
                        <input type="hidden" name="section" value="pembayaran">
                        <input type="hidden" name="spp_tab" value="spp2_mgmt">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                        <input type="hidden" name="order_by" value="<?= htmlspecialchars($order_by) ?>">
                        <label for="per_page_spp2" class="sr-only">Data per halaman:</label>
                        <select name="per_page" id="per_page_spp2" onchange="this.form.submit()"
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="10" <?= ($records_per_page == 10) ? 'selected' : '' ?>>10 per halaman</option>
                            <option value="20" <?= ($records_per_page == 20) ? 'selected' : '' ?>>20 per halaman</option>
                            <option value="50" <?= ($records_per_page == 50) ? 'selected' : '' ?>>50 per halaman</option>
                            <option value="100" <?= ($records_per_page == 100) ? 'selected' : '' ?>>100 per halaman</option>
                            <option value="1000" <?= ($records_per_page == 1000) ? 'selected' : '' ?>>1000 per halaman</option>
                        </select>
                    </form>
                </div>

                <!-- Tombol Ekspor SPP Mingguan -->
                <div class="mb-4 text-right">
                    <a href="export_payments.php?spp_type=spp2&start_date=<?= urlencode($academic_start_date_str) ?>&end_date=<?= urlencode($academic_end_date_str) ?>"
                        class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out">
                        <span class="material-icons-outlined align-middle mr-1">download</span> Ekspor Data Infaq
                    </a>
                </div>

                <?php if (!empty($spp2_payments_summary)): ?>
                    <form id="bulkDeleteSpp2Form" action="admin.php?section=pembayaran&spp_tab=spp2_mgmt" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pembayaran Infaq yang dipilih?');">
                        <input type="hidden" name="bulk_delete_spp2" value="true">
                        <div class="table-container">
                            <table class="min-w-full">
                                <thead>
                                    <tr>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tl-lg">
                                            <input type="checkbox" id="selectAllSpp2" class="form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out">
                                        </th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Nama Siswa</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Minggu Ke-</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Tgl Mulai Minggu</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Tgl Akhir Minggu</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Jumlah</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Admin Input</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Waktu Input</th>
                                        <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tr-lg">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($spp2_payments_summary as $row): ?>
                                        <tr>
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <input type="checkbox" name="selected_spp2_ids[]" value="<?= htmlspecialchars($row['id_pembayaran_spp_kedua']) ?>" class="spp2-checkbox form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out">
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-center"><?= htmlspecialchars($row['minggu_ke_akademik']) ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars(format_date_indo($row['tanggal_mulai_minggu'])) ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars(format_date_indo($row['tanggal_akhir_minggu'])) ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['created_by_admin_username'] ?? 'N/A') ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['created_at_pembayaran']) ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-center">
                                                <a href="admin.php?section=pembayaran&spp_tab=spp2_mgmt&delete_spp2_group=true&id_siswa=<?= htmlspecialchars($row['id_siswa']) ?>&minggu=<?= htmlspecialchars($row['minggu_ke_akademik']) ?>&tahun=<?= htmlspecialchars($row['tahun_akademik_mulai']) ?>"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus pembayaran Infaq untuk siswa <?= htmlspecialchars($row['nama_siswa']) ?> minggu ke-<?= htmlspecialchars($row['minggu_ke_akademik']) ?>?');"
                                                    class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded-md text-xs transition duration-300">Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out mt-4">Hapus yang Dipilih</button>
                    </form>

                    <!-- Pagination SPP2 -->
                    <div class="mt-6 flex justify-center items-center space-x-2">
                        <?php
                        $total_pages_spp2 = ceil($total_spp2_records / $records_per_page);
                        if ($total_pages_spp2 > 1) {
                            // Previous Page
                            if ($current_page > 1) {
                                echo '<a href="' . create_pagination_link('admin.php', $current_page - 1, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">&laquo; Sebelumnya</a>';
                            }

                            // Page Numbers
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages_spp2, $current_page + 2);

                            if ($start_page > 1) {
                                echo '<a href="' . create_pagination_link('admin.php', 1, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="pagination-link">...</span>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                $active_class = ($i == $current_page) ? 'active' : '';
                                echo '<a href="' . create_pagination_link('admin.php', $i, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link ' . $active_class . '">' . $i . '</a>';
                            }

                            if ($end_page < $total_pages_spp2) {
                                if ($end_page < $total_pages_spp2 - 1) {
                                    echo '<span class="pagination-link">...</span>';
                                }
                                echo '<a href="' . create_pagination_link('admin.php', $total_pages_spp2, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">' . $total_pages_spp2 . '</a>';
                            }

                            // Next Page
                            if ($current_page < $total_pages_spp2) {
                                echo '<a href="' . create_pagination_link('admin.php', $current_page + 1, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">Selanjutnya &raquo;</a>';
                            }
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">Belum ada ringkasan pembayaran Infaq.</p>
                <?php endif; ?>

                <script>
                    document.getElementById('selectAllSpp2').addEventListener('change', function() {
                        const checkboxes = document.querySelectorAll('.spp2-checkbox');
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = this.checked;
                        });
                    });
                </script>

            <?php elseif ($spp_tab_active == 'biaya_lain_mgmt'): ?>
                <h3 class="text-xl font-bold text-gray-800 mb-4">Pembayaran Biaya Lain (Tahun Akademik <?= $academic_start_year ?>/<?= $academic_end_year ?>)</h3>

                <div class="bg-gray-50 p-4 rounded-lg shadow-sm mb-6">
                    <h4 class="text-lg font-semibold text-gray-700 mb-3">Input Pembayaran Biaya Lain</h4>
                    <form action="admin.php?section=pembayaran&spp_tab=biaya_lain_mgmt" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="form-group">
                            <label for="id_siswa_pay_biaya_lain">Pilih Siswa:</label>
                            <select id="id_siswa_pay_biaya_lain" name="id_siswa_pay_biaya_lain" required class="w-full">
                                <option value="">-- Pilih Siswa --</option>
                                <?php foreach ($siswa_list as $siswa): ?>
                                    <option value="<?= htmlspecialchars($siswa['id_siswa']) ?>"><?= htmlspecialchars($siswa['nama_siswa']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="id_biaya_type">Pilih Jenis Biaya:</label>
                            <select id="id_biaya_type" name="id_biaya_type" required class="w-full">
                                <option value="">-- Pilih Biaya --</option>
                                <?php foreach ($biaya_lain_list_dropdown as $biaya): ?>
                                    <option value="<?= htmlspecialchars($biaya['id_biaya']) ?>"><?= htmlspecialchars($biaya['nama_biaya']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="jumlah_dibayar_biaya_lain">Jumlah Dibayar (Rp):</label>
                            <input type="number" id="jumlah_dibayar_biaya_lain" name="jumlah_dibayar_biaya_lain" placeholder="Contoh: 50000" required min="0">
                        </div>
                        <div class="form-group">
                            <label for="tanggal_pembayaran_biaya_lain">Tanggal Pembayaran:</label>
                            <input type="date" id="tanggal_pembayaran_biaya_lain" name="tanggal_pembayaran_biaya_lain" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="flex items-center col-span-full md:col-span-1">
                            <button type="submit" name="pay_biaya_lain" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out w-full">Catat Pembayaran</button>
                        </div>
                    </form>
                </div>

                <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                    <!-- Search Bar -->
                    <form action="admin.php" method="GET" class="w-full md:w-1/3">
                        <input type="hidden" name="section" value="pembayaran">
                        <input type="hidden" name="spp_tab" value="biaya_lain_mgmt">
                        <input type="hidden" name="per_page" value="<?= htmlspecialchars($records_per_page) ?>">
                        <input type="hidden" name="order_by" value="<?= htmlspecialchars($order_by) ?>">
                        <input type="text" name="search" placeholder="Cari nama siswa atau biaya..." value="<?= htmlspecialchars($search_query) ?>"
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                    </form>

                    <!-- Sort By -->
                    <form action="admin.php" method="GET" class="w-full md:w-1/4">
                        <input type="hidden" name="section" value="pembayaran">
                        <input type="hidden" name="spp_tab" value="biaya_lain_mgmt">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                        <input type="hidden" name="per_page" value="<?= htmlspecialchars($records_per_page) ?>">
                        <label for="order_by_biaya_lain" class="sr-only">Urutkan Berdasarkan:</label>
                        <select name="order_by" id="order_by_biaya_lain" onchange="this.form.submit()"
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="tanggal_desc" <?= ($order_by == 'tanggal_desc') ? 'selected' : '' ?>>Tanggal Terbaru</option>
                            <option value="tanggal_asc" <?= ($order_by == 'tanggal_asc') ? 'selected' : '' ?>>Tanggal Terlama</option>
                            <option value="nama_siswa_asc" <?= ($order_by == 'nama_siswa_asc') ? 'selected' : '' ?>>Nama Siswa (A-Z)</option>
                            <option value="nama_siswa_desc" <?= ($order_by == 'nama_siswa_desc') ? 'selected' : '' ?>>Nama Siswa (Z-A)</option>
                        </select>
                    </form>

                    <!-- Records per page -->
                    <form action="admin.php" method="GET" class="w-full md:w-1/4">
                        <input type="hidden" name="section" value="pembayaran">
                        <input type="hidden" name="spp_tab" value="biaya_lain_mgmt">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                        <input type="hidden" name="order_by" value="<?= htmlspecialchars($order_by) ?>">
                        <label for="per_page_biaya_lain" class="sr-only">Data per halaman:</label>
                        <select name="per_page" id="per_page_biaya_lain" onchange="this.form.submit()"
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="10" <?= ($records_per_page == 10) ? 'selected' : '' ?>>10 per halaman</option>
                            <option value="20" <?= ($records_per_page == 20) ? 'selected' : '' ?>>20 per halaman</option>
                            <option value="50" <?= ($records_per_page == 50) ? 'selected' : '' ?>>50 per halaman</option>
                            <option value="1000" <?= ($records_per_page == 1000) ? 'selected' : '' ?>>1000 per halaman</option>
                            <option value="10000" <?= ($records_per_page == 10000) ? 'selected' : '' ?>>10000 per halaman</option>
                            <option value="100000" <?= ($records_per_page == 100000) ? 'selected' : '' ?>>100000 per halaman</option>
                        </select>
                    </form>
                </div>

                <?php if (!empty($biaya_lain_payments_summary)): ?>
                    <form id="bulkDeleteBiayaLainForm" action="admin.php?section=pembayaran&spp_tab=biaya_lain_mgmt" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pembayaran biaya lain yang dipilih?');">
                        <input type="hidden" name="bulk_delete_biaya_lain" value="true">
                        <div class="table-container">
                            <table class="min-w-full">
                                <thead>
                                    <tr>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tl-lg">
                                            <input type="checkbox" id="selectAllBiayaLain" class="form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out">
                                        </th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Nama Siswa</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Jenis Biaya</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Jumlah Dibayar</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Tanggal Pembayaran</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Admin Input</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Waktu Input</th>
                                        <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider rounded-tr-lg">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($biaya_lain_payments_summary as $row): ?>
                                        <tr>
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <input type="checkbox" name="selected_biaya_lain_ids[]" value="<?= htmlspecialchars($row['id_pembayaran_biaya_lain']) ?>" class="biaya-lain-checkbox form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out">
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['nama_biaya']) ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800">Rp <?= number_format($row['jumlah_dibayar'], 0, ',', '.') ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars(format_date_indo($row['tanggal_pembayaran'])) ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['created_by_admin_username'] ?? 'N/A') ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-gray-800"><?= htmlspecialchars($row['created_at_pembayaran']) ?></td>
                                            <td class="py-3 px-4 whitespace-nowrap text-center">
                                                <span class="text-gray-500 text-xs">Hapus via checkbox</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300 ease-in-out mt-4">Hapus yang Dipilih</button>
                    </form>

                    <!-- Pagination Biaya Lain -->
                    <div class="mt-6 flex justify-center items-center space-x-2">
                        <?php
                        $total_pages_biaya_lain = ceil($total_biaya_lain_records / $records_per_page);
                        if ($total_pages_biaya_lain > 1) {
                            // Previous Page
                            if ($current_page > 1) {
                                echo '<a href="' . create_pagination_link('admin.php', $current_page - 1, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">&laquo; Sebelumnya</a>';
                            }

                            // Page Numbers
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages_biaya_lain, $current_page + 2);

                            if ($start_page > 1) {
                                echo '<a href="' . create_pagination_link('admin.php', 1, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="pagination-link">...</span>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                $active_class = ($i == $current_page) ? 'active' : '';
                                echo '<a href="' . create_pagination_link('admin.php', $i, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link ' . $active_class . '">' . $i . '</a>';
                            }

                            if ($end_page < $total_pages_biaya_lain) {
                                if ($end_page < $total_pages_biaya_lain - 1) {
                                    echo '<span class="pagination-link">...</span>';
                                }
                                echo '<a href="' . create_pagination_link('admin.php', $total_pages_biaya_lain, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">' . $total_pages_biaya_lain . '</a>';
                            }

                            // Next Page
                            if ($current_page < $total_pages_biaya_lain) {
                                echo '<a href="' . create_pagination_link('admin.php', $current_page + 1, $records_per_page, $search_query, $order_by, $admin_section, $spp_tab_active) . '" class="pagination-link">Selanjutnya &raquo;</a>';
                            }
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">Belum ada pembayaran biaya lain.</p>
                <?php endif; ?>

                <script>
                    document.getElementById('selectAllBiayaLain').addEventListener('change', function() {
                        const checkboxes = document.querySelectorAll('.biaya-lain-checkbox');
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = this.checked;
                        });
                    });
                </script>

            <?php endif; ?>
        </div>
    <?php endif; ?>
    </div>
    <script>
        // Array teks Arab bervariasi
        const arabicPhrases = [
            'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ', // Bismillah
            'اَلْحَمْدُ لِلّٰهِ رَبِّ الْعٰلَمِيْنَ', // Segala puji bagi Allah, Tuhan seluruh alam
            'اَللّٰهُ أَكْبَرُ', // Allah Maha Besar
            'سُبْحَانَ اللَّهِ وَبِحَمْدِهِ', // Maha Suci Allah dan dengan memuji-Nya
            'لَا إِلَٰهَ إِلَّا ٱللَّٰهُ', // Tiada Tuhan selain Allah
            'مَا شَاءَ اللَّهُ', // Apa yang dikehendaki Allah
            'إِنْ شَاءَ اللَّهُ', // Insya Allah (Jika Allah menghendaki)
            'جَزَاكَ اللَّهُ خَيْرًا', // Semoga Allah membalasmu dengan kebaikan
            'أَسْتَغْفِرُ اللَّهَ', // Aku memohon ampun kepada Allah
            'صَلَّى اللَّهُ عَلَيْهِ وَسَلَّمَ', // Semoga shalawat dan salam Allah tercurah kepadanya (Nabi Muhammad)
            'وَعَلَيْكُمُ السَّلَامُ وَرَحْمَةُ اللَّهِ وَبَرَكَاتُهُ', // Dan semoga keselamatan, rahmat Allah, dan keberkahan-Nya tercurah juga kepadamu
            'مَرْحَبًا', // Selamat datang
            'شُكْرًا', // Terima kasih
            'إِنَّا لِلَّٰهِ وَإِنَّا إِلَيْهِ رَاجِعُونَ', // Sesungguhnya kami milik Allah dan kepada-Nya kami kembali
            'تَبَارَكَ اللَّهُ', // Maha Suci Allah / Berkah Allah
            'فِي سَبِيلِ اللَّهِ', // Di jalan Allah
            'الْحَمْدُ لِلَّهِ عَلَى كُلِّ حَالٍ', // Segala puji bagi Allah dalam setiap keadaan
            'رَبِّ زِدْنِي عِلْمًا', // Ya Tuhanku, tambahkanlah ilmu kepadaku
            'لَا حَوْلَ وَلَا قُوَّةَ إِلَّا بِاللَّهِ', // Tiada daya dan upaya kecuali dengan pertolongan Allah
            'حَسْبُنَا اللَّهُ وَنِعْمَ الْوَكِيلُ', // Cukuplah Allah bagiku dan Dia sebaik-baiknya pelindung
        ];

        // Fungsi untuk memilih frasa Arab secara acak dan menampilkannya
        function displayRandomArabicPhrase() {
            const randomIndex = Math.floor(Math.random() * arabicPhrases.length);
            document.getElementById('adminArabicPhrase').innerText = arabicPhrases[randomIndex];
        }

        // Panggil fungsi saat DOMContentLoaded
        document.addEventListener('DOMContentLoaded', displayRandomArabicPhrase);

        // Fungsi untuk mengelola modal (Edit Siswa, Edit Biaya Lain, Edit Libur)
        function openEditModal(modalId, data) {
            const modal = document.getElementById(modalId);
            if (modalId === 'editSiswaModal') {
                document.getElementById('edit_siswa_id').value = data.id;
                document.getElementById('edit_nama_siswa').value = data.nama;
                document.getElementById('edit_login_code').value = data.loginCode;
            } else if (modalId === 'editBiayaModal') {
                document.getElementById('edit_biaya_id').value = data.id;
                document.getElementById('edit_nama_biaya').value = data.nama;
                document.getElementById('edit_jumlah_biaya').value = data.jumlah;
            } else if (modalId === 'editLiburModal') {
                document.getElementById('edit_libur_id').value = data.id;
                document.getElementById('edit_tanggal_libur').value = data.tanggal;
                document.getElementById('edit_keterangan_libur').value = data.keterangan;
            }
            modal.classList.remove('hidden');
        }

        function closeEditModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = ['editSiswaModal', 'editBiayaModal', 'editLiburModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal && event.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        }

        // Fungsi untuk mengelola checkbox "Select All"
        function setupSelectAll(checkboxId, targetClass) {
            const selectAllCheckbox = document.getElementById(checkboxId);
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll(targetClass);
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }
        }

        // Panggil setupSelectAll untuk setiap tab yang relevan
        document.addEventListener('DOMContentLoaded', () => {
            setupSelectAll('selectAllSpp1', '.spp1-checkbox');
            setupSelectAll('selectAllSpp2', '.spp2-checkbox');
            setupSelectAll('selectAllBiayaLain', '.biaya-lain-checkbox');
        });

        // JavaScript untuk mengelola kelas 'active' pada tab navigasi
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.tab-button');
            const currentUrl = window.location.href;

            navLinks.forEach(link => {
                // Hapus kelas 'active' default dari PHP
                link.classList.remove('bg-purple-600', 'text-white', 'shadow-lg');
                link.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-purple-500', 'hover:text-white');

                // Terapkan gaya aktif jika URL cocok
                if (currentUrl.includes(link.getAttribute('href'))) {
                    link.classList.add('bg-purple-600', 'text-white', 'shadow-lg');
                    link.classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-purple-500', 'hover:text-white');
                }
            });

            // Logika khusus untuk tab pembayaran (SPP Harian, Mingguan, Biaya Lain)
            const spp1MgmtLink = document.getElementById('navPembayaran'); // Ini adalah link utama 'Manajemen SPP'
            const spp2MgmtLink = document.querySelector('a[href="?section=pembayaran&spp_tab=spp2_mgmt"]');
            const biayaLainMgmtLink = document.querySelector('a[href="?section=pembayaran&spp_tab=biaya_lain_mgmt"]');

            // Hapus kelas aktif dari link utama Manajemen SPP jika sub-tab aktif
            if (currentUrl.includes('spp_tab=spp2_mgmt') || currentUrl.includes('spp_tab=biaya_lain_mgmt')) {
                spp1MgmtLink.classList.remove('bg-purple-600', 'text-white', 'shadow-lg', 'ring-2', 'ring-purple-300', 'ring-offset-1');
                spp1MgmtLink.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-purple-500', 'hover:text-white');
            }

            // Terapkan gaya aktif pada sub-tab pembayaran jika URL cocok
            if (spp2MgmtLink && currentUrl.includes('spp_tab=spp2_mgmt')) {
                spp2MgmtLink.classList.add('active');
                spp2MgmtLink.classList.remove('text-gray-600', 'hover:text-purple-600');
            } else if (biayaLainMgmtLink && currentUrl.includes('spp_tab=biaya_lain_mgmt')) {
                biayaLainMgmtLink.classList.add('active');
                biayaLainMgmtLink.classList.remove('text-gray-600', 'hover:text-purple-600');
            }
        });
    </script>
</body>

</html>
<?php
// Menutup koneksi database di akhir skrip PHP setelah semua operasi selesai
$conn->close();
?