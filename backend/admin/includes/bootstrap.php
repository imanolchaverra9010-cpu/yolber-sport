<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

function requireAdmin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function adminSlugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'item';
}

/** Resolve uploads/... or relative paths for admin pages under /admin/. */
function adminMediaUrl(?string $url): string {
    $url = trim((string)$url);
    if ($url === '') return '';
    if (preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, 'data:') || str_starts_with($url, '/')) {
        return $url;
    }
    return '../' . ltrim($url, '/');
}

function getSetting(PDO|Database $db, string $key, string $default = ''): string {
    try {
        $row = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        return $row['setting_value'] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function setSetting(Database $db, string $key, string $value): void {
    $exists = $db->fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
    if ($exists) {
        $db->query("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
    } else {
        $db->query("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)", [$key, $value]);
    }
}

function flashSet(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flashRender(): void {
    if (empty($_SESSION['flash'])) return;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $cls = $flash['type'] === 'success'
        ? 'border-green-500/40 bg-green-500/10 text-green-400'
        : 'border-red-500/40 bg-red-500/10 text-red-400';
    echo '<div class="mb-4 rounded border px-4 py-3 ' . $cls . '">' . htmlspecialchars($flash['message']) . '</div>';
}

function adminNavItems(): array {
    return [
        'dashboard' => ['href' => 'dashboard.php', 'icon' => 'fa-chart-line', 'label' => 'Dashboard'],
        'products' => ['href' => 'products.php', 'icon' => 'fa-box', 'label' => 'Productos'],
        'categories' => ['href' => 'categories.php', 'icon' => 'fa-tags', 'label' => 'Categorías'],
        'orders' => ['href' => 'orders.php', 'icon' => 'fa-shopping-cart', 'label' => 'Pedidos'],
        'users' => ['href' => 'users.php', 'icon' => 'fa-users', 'label' => 'Usuarios'],
        'settings' => ['href' => 'settings.php', 'icon' => 'fa-cog', 'label' => 'Configuración'],
    ];
}

function adminLayoutStart(string $title, string $active = 'dashboard'): void {
    $userName = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
    $items = adminNavItems();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> — Yolber Sport Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-950 min-h-screen text-white">
    <nav class="sticky top-0 z-40 border-b border-gray-800 bg-gray-900/95 backdrop-blur">
        <div class="mx-auto flex max-w-[1400px] items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <div class="grid h-9 w-9 place-items-center rounded-full border border-yellow-500/40 bg-black text-xs font-bold text-yellow-500">YS</div>
                <div>
                    <p class="text-sm font-bold text-yellow-500">Yolber Sport</p>
                    <p class="text-[10px] uppercase tracking-widest text-gray-500">Panel admin</p>
                </div>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <span class="hidden text-gray-400 sm:inline">Hola, <?php echo $userName; ?></span>
                <a href="../../" target="_blank" class="text-gray-400 transition hover:text-yellow-500">
                    <i class="fas fa-external-link-alt mr-1"></i> Ver sitio
                </a>
                <a href="logout.php" class="text-red-400 transition hover:text-red-300">
                    <i class="fas fa-sign-out-alt mr-1"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="mx-auto flex max-w-[1400px]">
        <aside class="hidden w-60 flex-shrink-0 border-r border-gray-800 bg-gray-900 p-4 lg:block min-h-[calc(100vh-57px)]">
            <nav class="space-y-1">
                <?php foreach ($items as $key => $item): ?>
                    <a href="<?php echo $item['href']; ?>"
                       class="flex items-center gap-3 rounded-lg px-4 py-3 transition <?php echo $active === $key ? 'bg-yellow-500/15 text-yellow-500' : 'text-gray-300 hover:bg-gray-800'; ?>">
                        <i class="fas <?php echo $item['icon']; ?> w-5 text-center"></i>
                        <?php echo htmlspecialchars($item['label']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <main class="min-w-0 flex-1 p-4 md:p-6">
            <div class="mb-4 flex gap-2 overflow-x-auto pb-2 lg:hidden">
                <?php foreach ($items as $key => $item): ?>
                    <a href="<?php echo $item['href']; ?>"
                       class="whitespace-nowrap rounded-full px-3 py-1.5 text-xs <?php echo $active === $key ? 'bg-yellow-500 text-black' : 'bg-gray-800 text-gray-300'; ?>">
                        <?php echo htmlspecialchars($item['label']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php flashRender(); ?>
    <?php
}

function adminLayoutEnd(): void {
    ?>
        </main>
    </div>
</body>
</html>
    <?php
}
