<?php
require_once 'config.php';

// If already logged in, redirect to admin
if (isLoggedIn()) {
    header('Location: admin.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        // Try database authentication first
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT password_hash FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                header('Location: admin.php');
                exit;
            }
        } catch(PDOException $e) {
            // Fallback to simple auth if database fails
            if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                header('Location: admin.php');
                exit;
            }
        }

        $message = 'Username atau password salah!';
        $messageType = 'error';
    } else {
        $message = 'Harap isi semua field!';
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - SIG Minahasa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">

    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <div class="bg-blue-100 p-3 rounded-full">
                    <i data-lucide="shield" class="w-8 h-8 text-blue-600"></i>
                </div>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Login Admin</h1>
            <p class="text-gray-500 mt-2">Sistem Informasi Geografis Minahasa</p>
        </div>

        <!-- Message -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded <?php echo $messageType === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; ?> border">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                <input type="text" id="username" name="username" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Masukkan username">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Masukkan password">
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Masuk
            </button>
        </form>

        <!-- Back Link -->
        <div class="mt-6 text-center">
            <a href="index.php" class="text-sm text-gray-600 hover:text-gray-800">← Kembali ke Peta</a>
        </div>

        <!-- Info -->
        <div class="mt-6 p-4 bg-blue-50 rounded border border-blue-100">
            <p class="text-xs text-blue-800">
                <strong>Demo Credentials:</strong><br>
                Username: admin<br>
                Password: sebas
            </p>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
