<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $db = Database::getInstance();

    try {
        $user = $db->fetchOne("SELECT id, email, password_hash, name, is_admin FROM users WHERE email = ?", [$email]);
    } catch (Throwable $e) {
        $user = $db->fetchOne("SELECT id, email, password_hash, name FROM users WHERE email = ?", [$email]);
        if ($user) $user['is_admin'] = 1;
    }

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'] ?: 'Admin';
        $_SESSION['is_admin'] = (int)($user['is_admin'] ?? 1);
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Credenciales inválidas';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Yolber Sport Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-black flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-2xl border border-white/10 bg-gray-900 p-8 shadow-xl">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-full border border-yellow-500/40 text-lg font-bold text-yellow-500">YS</div>
            <h1 class="text-2xl font-bold text-white">Yolber Sport</h1>
            <p class="text-sm text-gray-400">Panel de administración</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 rounded border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-400"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm text-gray-400">Email</label>
                <input type="email" name="email" required
                    class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2.5 text-white focus:border-yellow-500 focus:outline-none"
                    placeholder="admin@yolbersport.com">
            </div>
            <div>
                <label class="mb-1 block text-sm text-gray-400">Contraseña</label>
                <input type="password" name="password" required
                    class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2.5 text-white focus:border-yellow-500 focus:outline-none">
            </div>
            <button type="submit" class="w-full rounded-lg bg-yellow-500 py-2.5 font-bold text-black transition hover:bg-yellow-400">
                Entrar
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-gray-500">
            ¿Primera vez? Ejecuta <a class="text-yellow-500" href="setup.php">setup.php</a>
        </p>
    </div>
</body>
</html>
