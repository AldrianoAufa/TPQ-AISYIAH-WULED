 <?php
// login.php

session_start(); // Mulai sesi

include 'db_connect.php'; // Memasukkan file koneksi database

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $message = "Username dan password tidak boleh kosong.";
        $message_type = "error";
    } else {
        // Coba login sebagai Admin
        $stmt_admin = $conn->prepare("SELECT id_user, username, password_hash, role FROM users WHERE username = ?");
        $stmt_admin->bind_param("s", $username);
        $stmt_admin->execute();
        $result_admin = $stmt_admin->get_result();

        if ($result_admin->num_rows > 0) {
            $user = $result_admin->fetch_assoc();
            // Verifikasi password yang dimasukkan dengan hash di database
            if (password_verify($password, $user['password_hash'])) {
                // Login Admin berhasil
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id_user'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_role'] = 'admin'; // Pastikan role admin terdefinisi
                // Redirect ke halaman admin
                header("Location: admin.php");
                exit();
            } else {
                $message = "Password salah untuk admin.";
                $message_type = "error";
            }
        } else {
            // Jika bukan admin, coba login sebagai Siswa
            // Di sini, login_code berfungsi sebagai username dan password
            $stmt_siswa = $conn->prepare("SELECT id_siswa, nama_siswa, login_code FROM siswa WHERE login_code = ?");
            $stmt_siswa->bind_param("s", $username); // Gunakan username sebagai login_code
            $stmt_siswa->execute();
            $result_siswa = $stmt_siswa->get_result();

            if ($result_siswa->num_rows > 0) {
                $siswa = $result_siswa->fetch_assoc();
                // Karena login_code adalah username dan password, kita hanya perlu membandingkan
                if ($password === $siswa['login_code']) { // Membandingkan input password dengan login_code
                    // Login Siswa berhasil
                    $_SESSION['student_logged_in'] = true;
                    $_SESSION['student_id'] = $siswa['id_siswa'];
                    $_SESSION['student_name'] = $siswa['nama_siswa'];
                    $_SESSION['student_role'] = 'siswa'; // Set role untuk siswa

                    // Redirect ke halaman index (tampilan user)
                    header("Location: index.php");
                    exit();
                } else {
                    $message = "Kode login salah untuk siswa.";
                    $message_type = "error";
                }
            } else {
                $message = "Username atau Kode Login tidak ditemukan.";
                $message_type = "error";
            }
            $stmt_siswa->close();
        }
        $stmt_admin->close();
    }
}

$conn->close();
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
    <link rel="icon" href="assets/logo.png" type="image/png">
    <meta name="google-site-verification" content="kNC0ggF_TivAWU0iFyc9W16pFvffcmfEhMhcoC__beg" />
    <title>Login - Sistem Pembayaran SPP</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Inter dari Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Material Icons Outlined (untuk ikon sekolah) -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f4c5c 0%, #2a9d8f 100%); /* Hijau ke Teal, warna Islami yang menenangkan */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
            position: relative;
        }
        /* Pola geometris Islami yang sangat halus di latar belakang */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="%23ffffff" fill-opacity="0.05"%3E%3Cpath d="M96 95h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zM96 84h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zM96 73h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zM96 62h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zM96 51h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zM96 40h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zM96 29h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zM96 18h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zM96 7h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4z"/%3E%3C/g%3E%3C/svg%3E');
            background-size: 100px 100px;
            opacity: 0.1; /* Sangat halus */
            z-index: 1;
        }

        .login-container {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3), 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            text-align: center;
            position: relative;
            z-index: 10;
            animation: fadeInScale 0.8s ease-out forwards;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
        }
        @keyframes fadeInScale {
            from { opacity: 0; transform: translateY(-30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .title-islamic {
            font-family: 'Amiri', serif; /* Font kaligrafi untuk judul */
            color: #0f4c5c; /* Warna teks yang kontras dengan latar belakang */
            font-weight: 700; /* Bold */
            letter-spacing: 0.02em; /* Sedikit spasi antar huruf */
        }

        .input-field {
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            font-size: 1.05rem;
            transition: all 0.3s ease-in-out;
            background-color: #f8fafc;
        }
        .input-field:focus {
            outline: none;
            border-color: #2a9d8f; /* Warna teal dari gradient */
            box-shadow: 0 0 0 4px rgba(42, 157, 143, 0.2);
            background-color: #ffffff;
        }

        .btn-login {
            background: linear-gradient(90deg, #2a9d8f 0%, #0f4c5c 100%); /* Gradient teal ke hijau gelap */
            color: white;
            font-weight: 800;
            padding: 1rem 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 15px 25px -5px rgba(42, 157, 143, 0.4), 0 6px 10px -3px rgba(42, 157, 143, 0.2);
            transition: all 0.3s ease-in-out;
            letter-spacing: 0.05em;
        }
        .btn-login:hover {
            background: linear-gradient(90deg, #0f4c5c 0%, #2a9d8f 100%); /* Reverse gradient on hover */
            transform: translateY(-5px) scale(1.03);
            box-shadow: 0 20px 30px -8px rgba(42, 157, 143, 0.5), 0 8px 12px -4px rgba(42, 157, 143, 0.3);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            font-weight: 600;
            text-align: left;
            border: 1px solid;
            animation: slideInFromTop 0.5s ease-out;
        }
        @keyframes slideInFromTop {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-error {
            background-color: #fef2f2;
            color: #b91c1c;
            border-color: #ef4444;
        }

        /* Ikon dekoratif */
        .decorative-icon {
            color: #2a9d8f; /* Warna teal */
            font-size: 3rem; /* Ukuran ikon */
            margin-bottom: 1.5rem;
            animation: bounceIn 1s ease-out;
        }
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.1); opacity: 1; }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <!-- Pola latar belakang Islami halus -->
    <div class="absolute inset-0 z-10 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="%23ffffff" fill-opacity="0.05"%3E%3Cpath d="M96 95h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zM96 84h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zM96 73h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zM96 62h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zM96 51h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zM96 40h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zM96 29h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zM96 18h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zM96 7h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4zm-11 0h4v1h-4v4h-1v-4h-4v-1h4v-4z/%3E%3C/g%3E%3C/svg%3E');"></div>

    <div class="login-container">
        <span class="material-icons-outlined decorative-icon">school</span> <!-- Ikon sekolah/pendidikan -->
        <h2 class="text-4xl font-extrabold title-islamic mb-8">Sistem Pembayaran TPQ</h2>
        <p class="text-gray-600 mb-6">Masuk sebagai Admin atau Siswa</p>

        <?php if ($message): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-6">
                <label for="username" class="block text-gray-700 text-base font-semibold mb-2 text-left">Username / Kode Login:</label>
                <input type="text" id="username" name="username"class="input-field w-full text-gray-700" placeholder="Masukkan username admin atau kode login siswa" required autocomplete="username">
            </div>
            <div class="mb-8">
                <label for="password" class="block text-gray-700 text-base font-semibold mb-2 text-left">Password:</label>
                <input type="password" id="password" name="password"class="input-field w-full text-gray-700"placeholder="Masukkan password admin atau kode login siswa" required autocomplete="current-password">
            </div>
            <button type="submit" class="w-full btn-login">
                Masuk
            </button>
        </form>
    </div>
</body>
</html>