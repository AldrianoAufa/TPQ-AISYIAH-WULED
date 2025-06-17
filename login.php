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
        $stmt = $conn->prepare("SELECT id_user, username, password_hash, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Verifikasi password yang dimasukkan dengan hash di database
            if (password_verify($password, $user['password_hash'])) {
                // Login berhasil
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id_user'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_role'] = $user['role'];

                // Redirect ke halaman admin
                header("Location: admin.php");
                exit();
            } else {
                $message = "Password salah.";
                $message_type = "error";
            }
        } else {
            $message = "Username tidak ditemukan.";
            $message_type = "error";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen text-gray-900">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
        <h1 class="text-3xl font-extrabold text-center text-purple-700 mb-8">Login Admin</h1>

        <?php if ($message): ?>
            <div class="p-4 mb-6 rounded-lg text-white font-medium
                <?= $message_type == 'success' ? 'bg-green-500' : 'bg-red-500' ?>" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-6">
            <div>
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username:</label>
                <input type="text" id="username" name="username"
                       class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-purple-500 focus:border-purple-500"
                       placeholder="Masukkan username" required autocomplete="username">
            </div>
            <div>
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
                <input type="password" id="password" name="password"
                       class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline focus:ring-purple-500 focus:border-purple-500"
                       placeholder="Masukkan password" required autocomplete="current-password">
            </div>
            <button type="submit"
                    class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-md shadow-lg transition duration-300 ease-in-out transform hover:scale-105">
                Login
            </button>
        </form>
        <p class="text-center text-sm text-gray-600 mt-4">
            <a href="index.php" class="text-blue-600 hover:underline font-semibold">Kembali ke Tampilan User</a>
        </p>
    </div>
</body>
</html>
