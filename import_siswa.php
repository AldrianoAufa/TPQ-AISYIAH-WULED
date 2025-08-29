 <?php
// import_siswa.php
session_start(); // Mulai sesi

// Periksa apakah admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php'; // Memasukkan file koneksi database

if (isset($_POST['import_siswa'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['csv_file']['tmp_name'];
        $file_name = $_FILES['csv_file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($file_ext != 'csv') {
            $_SESSION['message'] = "File yang diunggah harus berformat CSV.";
            $_SESSION['message_type'] = "error";
            header("Location: admin.php?section=siswa");
            exit();
        }

        $handle = fopen($file_tmp_path, "r");
        if ($handle === FALSE) {
            $_SESSION['message'] = "Gagal membuka file CSV.";
            $_SESSION['message_type'] = "error";
            header("Location: admin.php?section=siswa");
            exit();
        }

        $imported_count = 0;
        $skipped_count = 0;
        $conn->begin_transaction(); // Mulai transaksi

        // Lewati baris header jika ada (asumsi tidak ada header)
        // Jika CSV Anda memiliki header, uncomment baris di bawah ini:
        // fgetcsv($handle);

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Asumsi: kolom 0 = nama_siswa, kolom 1 = login_code
            $nama_siswa = trim($data[0] ?? '');
            $login_code = trim($data[1] ?? '');

            if (!empty($nama_siswa) && !empty($login_code)) {
                // Cek apakah login_code sudah ada
                $stmt_check = $conn->prepare("SELECT COUNT(*) FROM siswa WHERE login_code = ?");
                $stmt_check->bind_param("s", $login_code);
                $stmt_check->execute();
                $stmt_check->bind_result($count);
                $stmt_check->fetch();
                $stmt_check->close();

                if ($count == 0) { // Jika kode belum ada, masukkan
                    $stmt_insert = $conn->prepare("INSERT INTO siswa (nama_siswa, login_code) VALUES (?, ?)");
                    $stmt_insert->bind_param("ss", $nama_siswa, $login_code);
                    if ($stmt_insert->execute()) {
                        $imported_count++;
                    }
                    $stmt_insert->close();
                } else {
                    $skipped_count++;
                }
            }
        }
        fclose($handle);

        if ($imported_count > 0) {
            $conn->commit(); // Commit transaksi jika ada data yang diimpor
            $_SESSION['message'] = "Impor siswa berhasil! Ditambahkan: {$imported_count} siswa baru. Dilewati (sudah ada): {$skipped_count} siswa.";
            $_SESSION['message_type'] = "success";
        } else {
            $conn->rollback(); // Rollback jika tidak ada yang diimpor atau ada kesalahan
            $_SESSION['message'] = "Tidak ada siswa baru yang diimpor atau terjadi kesalahan. Dilewati (sudah ada): {$skipped_count} siswa.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Gagal mengunggah file. Kode error: " . $_FILES['csv_file']['error'];
        $_SESSION['message_type'] = "error";
    }
} else {
    $_SESSION['message'] = "Akses tidak sah ke halaman impor siswa.";
    $_SESSION['message_type'] = "error";
}

$conn->close(); // Tutup koneksi database
header("Location: admin.php?section=siswa");
exit();