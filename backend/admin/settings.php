<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireAdmin();
$db = Database::getInstance();

// Ensure settings table
try {
    $db->query("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Throwable $e) {}

$keys = [
    'store_name' => 'Nombre de la tienda',
    'store_slogan' => 'Slogan',
    'whatsapp_phone' => 'WhatsApp (código país, sin +)',
    'contact_email' => 'Email de contacto',
    'contact_phone' => 'Teléfono visible',
    'contact_city' => 'Ciudad / país',
    'shipping_note' => 'Nota de envíos',
    'currency' => 'Moneda',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($keys as $key => $label) {
        if (isset($_POST[$key])) {
            setSetting($db, $key, trim($_POST[$key]));
        }
    }
    flashSet('success', 'Configuración guardada. Usa estos valores en el .env del frontend (VITE_WHATSAPP_PHONE).');
    header('Location: settings.php');
    exit;
}

$values = [];
foreach ($keys as $key => $_) {
    $values[$key] = getSetting($db, $key, '');
}

adminLayoutStart('Configuración', 'settings');
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold">Configuración</h2>
    <p class="text-sm text-gray-400">Datos generales de Yolber Sport</p>
</div>

<form method="POST" class="max-w-2xl space-y-4 rounded-2xl border border-gray-800 bg-gray-900 p-6">
    <?php foreach ($keys as $key => $label): ?>
        <div>
            <label class="mb-1 block text-xs uppercase tracking-wider text-gray-400"><?php echo htmlspecialchars($label); ?></label>
            <input name="<?php echo htmlspecialchars($key); ?>"
                value="<?php echo htmlspecialchars($values[$key]); ?>"
                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2.5 focus:border-yellow-500 focus:outline-none">
        </div>
    <?php endforeach; ?>

    <div class="rounded-xl border border-yellow-500/20 bg-yellow-500/5 p-4 text-sm text-gray-300">
        <p class="font-semibold text-yellow-500 mb-1">Frontend</p>
        <p>En el archivo <code class="text-white">.env</code> del proyecto React:</p>
        <pre class="mt-2 overflow-x-auto rounded bg-black/40 p-3 text-xs text-yellow-100">VITE_API_URL=http://localhost/backend
VITE_WHATSAPP_PHONE=<?php echo htmlspecialchars($values['whatsapp_phone'] ?: '573000000000'); ?></pre>
        <p class="mt-2 text-xs text-gray-500">Y en <code>src/lib/shopify.ts</code> pon <code>USE_MOCK_DATA = false</code> para usar el backend real.</p>
    </div>

    <button class="rounded-lg bg-yellow-500 px-5 py-2.5 font-bold text-black hover:bg-yellow-400">Guardar configuración</button>
</form>

<?php adminLayoutEnd(); ?>
