<?php
/**
 * One-time setup: creates/resets admin user + settings table.
 * Visit: /backend/admin/setup.php
 */
require_once __DIR__ . '/includes/bootstrap.php';

$db = Database::getInstance();
$messages = [];
$ok = true;

try {
    $db->query("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $messages[] = 'Tabla settings OK';
} catch (Throwable $e) {
    $ok = false;
    $messages[] = 'settings: ' . $e->getMessage();
}

try {
    $db->query("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0");
    $messages[] = 'Columna is_admin agregada';
} catch (Throwable $e) {
    $messages[] = 'is_admin: ya existe o OK';
}

$defaults = [
    'store_name' => 'Yolber Sport',
    'store_slogan' => 'Luce siempre tu mejor estilo',
    'whatsapp_phone' => '573000000000',
    'contact_email' => 'info@yolbersport.com',
    'contact_phone' => '+57 300 000 0000',
    'contact_city' => 'Colombia',
    'shipping_note' => 'Envíos a todo el país',
    'currency' => 'COP',
];

foreach ($defaults as $k => $v) {
    try {
        setSetting($db, $k, $v);
    } catch (Throwable $e) {
        $messages[] = "setting $k: " . $e->getMessage();
    }
}
$messages[] = 'Settings por defecto cargados';

$email = 'admin@yolbersport.com';
$pass = 'admin123';
$hash = password_hash($pass, PASSWORD_DEFAULT);

try {
    $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existing) {
        try {
            $db->query(
                "UPDATE users SET password_hash = ?, name = ?, is_admin = 1 WHERE email = ?",
                [$hash, 'Administrador', $email]
            );
        } catch (Throwable $e) {
            $db->query(
                "UPDATE users SET password_hash = ?, name = ? WHERE email = ?",
                [$hash, 'Administrador', $email]
            );
        }
        $messages[] = "Admin actualizado: $email / $pass";
    } else {
        try {
            $db->query(
                "INSERT INTO users (email, password_hash, name, is_admin) VALUES (?, ?, ?, 1)",
                [$email, $hash, 'Administrador']
            );
        } catch (Throwable $e) {
            $db->query(
                "INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?)",
                [$email, $hash, 'Administrador']
            );
        }
        $messages[] = "Admin creado: $email / $pass";
    }

    // Verify password works against stored hash
    $check = $db->fetchOne("SELECT password_hash FROM users WHERE email = ?", [$email]);
    if ($check && password_verify($pass, $check['password_hash'])) {
        $messages[] = 'Verificación de contraseña: OK';
    } else {
        $ok = false;
        $messages[] = 'ERROR: la contraseña no verifica. Revisa la columna password_hash.';
    }
} catch (Throwable $e) {
    $ok = false;
    $messages[] = 'Admin: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Setup Admin — Yolber Sport</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-950 text-white min-h-screen flex items-center justify-center p-6">
    <div class="max-w-lg w-full rounded-2xl border border-gray-800 bg-gray-900 p-8">
        <h1 class="text-2xl font-bold <?php echo $ok ? 'text-yellow-500' : 'text-red-400'; ?> mb-4">
            <?php echo $ok ? 'Setup completado' : 'Setup con errores'; ?>
        </h1>
        <ul class="space-y-2 text-sm text-gray-300 mb-6">
            <?php foreach ($messages as $m): ?>
                <li>• <?php echo htmlspecialchars($m); ?></li>
            <?php endforeach; ?>
        </ul>
        <p class="text-sm text-gray-400 mb-4">
            Entra con <strong class="text-white">admin@yolbersport.com</strong> /
            <strong class="text-white">admin123</strong>
        </p>
        <a href="login.php" class="inline-flex rounded-lg bg-yellow-500 px-4 py-2 font-bold text-black">Ir al login</a>
        <p class="mt-4 text-xs text-red-400">Borra setup.php después de usarlo.</p>
    </div>
</body>
</html>
