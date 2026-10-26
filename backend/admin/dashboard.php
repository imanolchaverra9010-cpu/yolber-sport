<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireAdmin();
$db = Database::getInstance();

$stats = [
    'products' => (int)($db->fetchOne("SELECT COUNT(*) as c FROM products")['c'] ?? 0),
    'active_products' => (int)($db->fetchOne("SELECT COUNT(*) as c FROM products WHERE status = 'active'")['c'] ?? 0),
    'categories' => 0,
    'orders' => (int)($db->fetchOne("SELECT COUNT(*) as c FROM orders")['c'] ?? 0),
    'pending' => (int)($db->fetchOne("SELECT COUNT(*) as c FROM orders WHERE status = 'pending'")['c'] ?? 0),
    'users' => (int)($db->fetchOne("SELECT COUNT(*) as c FROM users")['c'] ?? 0),
    'revenue' => (float)($db->fetchOne("SELECT COALESCE(SUM(total_amount),0) as t FROM orders WHERE status != 'cancelled'")['t'] ?? 0),
];

try {
    $stats['categories'] = (int)($db->fetchOne("SELECT COUNT(*) as c FROM categories WHERE is_active = 1")['c'] ?? 0);
} catch (Throwable $e) {}

$recent_orders = $db->fetchAll("SELECT * FROM orders ORDER BY created_at DESC LIMIT 6");
$low_stock = $db->fetchAll("
    SELECT p.title, pv.title as variant_title, pv.inventory_quantity, p.id as product_id
    FROM product_variants pv
    JOIN products p ON pv.product_id = p.id
    WHERE pv.inventory_quantity < 5
    ORDER BY pv.inventory_quantity ASC
    LIMIT 6
");
$latest_products = $db->fetchAll("SELECT id, title, status, created_at FROM products ORDER BY created_at DESC LIMIT 5");

adminLayoutStart('Dashboard', 'dashboard');
?>

<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Dashboard</h2>
        <p class="text-sm text-gray-400">Resumen de Yolber Sport</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="products.php" class="rounded-lg bg-yellow-500 px-4 py-2 text-sm font-bold text-black hover:bg-yellow-400">+ Producto</a>
        <a href="orders.php" class="rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-200 hover:bg-gray-800">Pedidos</a>
        <a href="settings.php" class="rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-200 hover:bg-gray-800">Config</a>
    </div>
</div>

<div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <?php
    $cards = [
        ['Productos activos', $stats['active_products'], 'fa-box', 'text-blue-400', 'bg-blue-500/15'],
        ['Pedidos', $stats['orders'], 'fa-bag-shopping', 'text-green-400', 'bg-green-500/15'],
        ['Pendientes', $stats['pending'], 'fa-clock', 'text-orange-400', 'bg-orange-500/15'],
        ['Ingresos', '$' . number_format($stats['revenue'], 0, ',', '.'), 'fa-dollar-sign', 'text-yellow-400', 'bg-yellow-500/15'],
    ];
    foreach ($cards as [$label, $value, $icon, $color, $bg]):
    ?>
        <div class="rounded-2xl border border-gray-800 bg-gray-900 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500"><?php echo $label; ?></p>
                    <p class="mt-2 text-2xl font-bold <?php echo $color; ?>"><?php echo $value; ?></p>
                </div>
                <div class="grid h-11 w-11 place-items-center rounded-xl <?php echo $bg; ?>">
                    <i class="fas <?php echo $icon; ?> <?php echo $color; ?>"></i>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
    <a href="products.php" class="rounded-xl border border-gray-800 bg-gray-900 p-4 hover:border-yellow-500/40">
        <p class="text-2xl font-bold"><?php echo $stats['products']; ?></p>
        <p class="text-xs text-gray-400">Productos totales</p>
    </a>
    <a href="categories.php" class="rounded-xl border border-gray-800 bg-gray-900 p-4 hover:border-yellow-500/40">
        <p class="text-2xl font-bold"><?php echo $stats['categories']; ?></p>
        <p class="text-xs text-gray-400">Categorías</p>
    </a>
    <a href="users.php" class="rounded-xl border border-gray-800 bg-gray-900 p-4 hover:border-yellow-500/40">
        <p class="text-2xl font-bold"><?php echo $stats['users']; ?></p>
        <p class="text-xs text-gray-400">Usuarios</p>
    </a>
    <a href="settings.php" class="rounded-xl border border-gray-800 bg-gray-900 p-4 hover:border-yellow-500/40">
        <p class="text-sm font-bold text-yellow-500">WhatsApp / tienda</p>
        <p class="text-xs text-gray-400">Configuración</p>
    </a>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-gray-800 bg-gray-900 p-5">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-bold">Pedidos recientes</h3>
            <a href="orders.php" class="text-xs text-yellow-500">Ver todos</a>
        </div>
        <?php if (!$recent_orders): ?>
            <p class="py-8 text-center text-sm text-gray-500">Sin pedidos aún</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($recent_orders as $order): ?>
                    <a href="order-details.php?id=<?php echo (int)$order['id']; ?>" class="flex items-center justify-between rounded-xl bg-gray-800/60 px-3 py-3 hover:bg-gray-800">
                        <div>
                            <p class="font-medium">#<?php echo (int)$order['id']; ?> · <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-yellow-500">$<?php echo number_format($order['total_amount'], 0, ',', '.'); ?></p>
                            <p class="text-xs text-gray-400"><?php echo htmlspecialchars($order['status']); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-800 bg-gray-900 p-5">
            <h3 class="mb-4 font-bold">Stock bajo</h3>
            <?php if (!$low_stock): ?>
                <p class="py-6 text-center text-sm text-gray-500">Inventario OK</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($low_stock as $item): ?>
                        <a href="edit.php?id=<?php echo (int)$item['product_id']; ?>" class="flex justify-between rounded-xl border border-red-500/20 bg-red-500/5 px-3 py-2">
                            <span class="text-sm"><?php echo htmlspecialchars($item['title']); ?> · <?php echo htmlspecialchars($item['variant_title']); ?></span>
                            <span class="font-bold text-red-400"><?php echo (int)$item['inventory_quantity']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="rounded-2xl border border-gray-800 bg-gray-900 p-5">
            <h3 class="mb-4 font-bold">Últimos productos</h3>
            <div class="space-y-2">
                <?php foreach ($latest_products as $p): ?>
                    <a href="edit.php?id=<?php echo (int)$p['id']; ?>" class="flex items-center justify-between rounded-xl bg-gray-800/60 px-3 py-2 hover:bg-gray-800">
                        <span class="text-sm"><?php echo htmlspecialchars($p['title']); ?></span>
                        <span class="text-xs text-gray-400"><?php echo htmlspecialchars($p['status']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php adminLayoutEnd(); ?>
