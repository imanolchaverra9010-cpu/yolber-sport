<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireAdmin();
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $db->query("UPDATE orders SET status = ? WHERE id = ?", [$_POST['status'], (int)$_POST['order_id']]);
    flashSet('success', 'Estado actualizado');
    header('Location: orders.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit;
}

if (isset($_GET['delete'])) {
    $db->query("DELETE FROM orders WHERE id = ?", [(int)$_GET['delete']]);
    flashSet('success', 'Pedido eliminado');
    header('Location: orders.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_order') {
    $name = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['customer_email'] ?? '');
    $phone = trim($_POST['customer_phone'] ?? '');
    $total = (float)($_POST['total_amount'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $status = $_POST['status'] ?? 'pending';
    if ($name !== '' && $total > 0) {
        $db->query(
            "INSERT INTO orders (customer_name, customer_email, customer_phone, total_amount, status, notes) VALUES (?,?,?,?,?,?)",
            [$name, $email, $phone, $total, $status, $notes]
        );
        flashSet('success', 'Pedido creado');
    } else {
        flashSet('error', 'Nombre y total son obligatorios');
    }
    header('Location: orders.php');
    exit;
}

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT * FROM orders";
$params = [];
if ($statusFilter !== '') {
    $sql .= " WHERE status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY created_at DESC";
$orders = $db->fetchAll($sql, $params);

adminLayoutStart('Pedidos', 'orders');
?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Pedidos</h2>
        <p class="text-sm text-gray-400"><?php echo count($orders); ?> pedidos</p>
    </div>
    <button onclick="document.getElementById('orderModal').style.display='flex'" class="rounded-lg bg-yellow-500 px-4 py-2 font-bold text-black">+ Pedido manual</button>
</div>

<div class="mb-4 flex flex-wrap gap-2">
    <?php
    $filters = ['' => 'Todos', 'pending' => 'Pendientes', 'confirmed' => 'Confirmados', 'processing' => 'Procesando', 'shipped' => 'Enviados', 'delivered' => 'Entregados', 'cancelled' => 'Cancelados'];
    foreach ($filters as $key => $label):
    ?>
        <a href="?status=<?php echo urlencode($key); ?>" class="rounded-full px-3 py-1.5 text-xs <?php echo $statusFilter === $key ? 'bg-yellow-500 text-black' : 'bg-gray-800 text-gray-300'; ?>">
            <?php echo $label; ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="overflow-hidden rounded-2xl border border-gray-800 bg-gray-900">
    <table class="w-full text-sm">
        <thead class="bg-gray-800 text-left text-gray-400">
            <tr>
                <th class="px-4 py-3">ID</th>
                <th class="px-4 py-3">Cliente</th>
                <th class="px-4 py-3">Total</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3">Fecha</th>
                <th class="px-4 py-3">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$orders): ?>
                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">No hay pedidos</td></tr>
            <?php endif; ?>
            <?php foreach ($orders as $order): ?>
                <tr class="border-t border-gray-800">
                    <td class="px-4 py-3 font-medium">#<?php echo (int)$order['id']; ?></td>
                    <td class="px-4 py-3">
                        <p><?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($order['customer_phone'] ?: ($order['customer_email'] ?? '')); ?></p>
                    </td>
                    <td class="px-4 py-3 font-bold text-yellow-500">$<?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                    <td class="px-4 py-3">
                        <form method="POST">
                            <input type="hidden" name="update_status" value="1">
                            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                            <select name="status" onchange="this.form.submit()" class="rounded border border-gray-700 bg-gray-800 px-2 py-1 text-xs">
                                <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo $order['status'] === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td class="px-4 py-3 text-gray-400"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                    <td class="px-4 py-3">
                        <a href="order-details.php?id=<?php echo (int)$order['id']; ?>" class="mr-2 text-blue-400">Ver</a>
                        <a href="orders.php?delete=<?php echo (int)$order['id']; ?>" onclick="return confirm('¿Eliminar pedido?')" class="text-red-400">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="orderModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4" style="display:none" onclick="if(event.target===this)this.style.display='none'">
    <form method="POST" class="w-full max-w-md space-y-3 rounded-2xl border border-gray-700 bg-gray-900 p-6" onclick="event.stopPropagation()">
        <h3 class="text-lg font-bold">Pedido manual</h3>
        <input type="hidden" name="action" value="create_order">
        <input name="customer_name" required placeholder="Nombre cliente" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
        <input name="customer_phone" placeholder="Teléfono / WhatsApp" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
        <input name="customer_email" type="email" placeholder="Email" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
        <input name="total_amount" type="number" required placeholder="Total COP" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
        <textarea name="notes" rows="3" placeholder="Notas / productos" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2"></textarea>
        <select name="status" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
            <option value="pending">Pendiente</option>
            <option value="confirmed">Confirmado</option>
        </select>
        <div class="flex gap-2">
            <button class="flex-1 rounded-lg bg-yellow-500 py-2 font-bold text-black">Crear</button>
            <button type="button" onclick="document.getElementById('orderModal').style.display='none'" class="flex-1 rounded-lg border border-gray-700 py-2">Cancelar</button>
        </div>
    </form>
</div>

<?php adminLayoutEnd(); ?>
