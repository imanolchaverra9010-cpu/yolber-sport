<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireAdmin();
$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);
$order = $db->fetchOne("SELECT * FROM orders WHERE id = ?", [$id]);
if (!$order) {
    flashSet('error', 'Pedido no encontrado');
    header('Location: orders.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update') {
        $db->query(
            "UPDATE orders SET customer_name=?, customer_email=?, customer_phone=?, total_amount=?, status=?, notes=? WHERE id=?",
            [
                trim($_POST['customer_name'] ?? ''),
                trim($_POST['customer_email'] ?? ''),
                trim($_POST['customer_phone'] ?? ''),
                (float)($_POST['total_amount'] ?? 0),
                $_POST['status'] ?? 'pending',
                trim($_POST['notes'] ?? ''),
                $id,
            ]
        );
        flashSet('success', 'Pedido actualizado');
    }
    if ($action === 'add_item') {
        $db->query(
            "INSERT INTO order_items (order_id, product_id, variant_id, product_title, variant_title, quantity, price) VALUES (?,?,?,?,?,?,?)",
            [
                $id,
                (int)($_POST['product_id'] ?? 0),
                (int)($_POST['variant_id'] ?? 0),
                trim($_POST['product_title'] ?? 'Producto'),
                trim($_POST['variant_title'] ?? ''),
                (int)($_POST['quantity'] ?? 1),
                (float)($_POST['price'] ?? 0),
            ]
        );
        $sum = $db->fetchOne("SELECT COALESCE(SUM(quantity * price),0) as t FROM order_items WHERE order_id = ?", [$id]);
        $db->query("UPDATE orders SET total_amount = ? WHERE id = ?", [(float)$sum['t'], $id]);
        flashSet('success', 'Ítem agregado');
    }
    if ($action === 'delete_item') {
        $db->query("DELETE FROM order_items WHERE id = ? AND order_id = ?", [(int)$_POST['item_id'], $id]);
        $sum = $db->fetchOne("SELECT COALESCE(SUM(quantity * price),0) as t FROM order_items WHERE order_id = ?", [$id]);
        $db->query("UPDATE orders SET total_amount = ? WHERE id = ?", [(float)$sum['t'], $id]);
        flashSet('success', 'Ítem eliminado');
    }
    header("Location: order-details.php?id=$id");
    exit;
}

$order = $db->fetchOne("SELECT * FROM orders WHERE id = ?", [$id]);
$items = $db->fetchAll("SELECT * FROM order_items WHERE order_id = ? ORDER BY id", [$id]);
$products = $db->fetchAll("SELECT id, title FROM products WHERE status = 'active' ORDER BY title");

adminLayoutStart('Pedido #' . $id, 'orders');
?>

<div class="mb-6">
    <a href="orders.php" class="text-xs text-gray-400 hover:text-yellow-500">← Pedidos</a>
    <h2 class="text-2xl font-bold">Pedido #<?php echo $id; ?></h2>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <form method="POST" class="space-y-3 rounded-2xl border border-gray-800 bg-gray-900 p-5">
        <input type="hidden" name="action" value="update">
        <h3 class="font-bold">Datos del cliente</h3>
        <input name="customer_name" required value="<?php echo htmlspecialchars($order['customer_name']); ?>" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2" placeholder="Nombre">
        <input name="customer_phone" value="<?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?>" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2" placeholder="Teléfono">
        <input name="customer_email" value="<?php echo htmlspecialchars($order['customer_email'] ?? ''); ?>" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2" placeholder="Email">
        <input type="number" name="total_amount" value="<?php echo htmlspecialchars($order['total_amount']); ?>" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2" placeholder="Total">
        <select name="status" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
            <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $st): ?>
                <option value="<?php echo $st; ?>" <?php echo $order['status'] === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
            <?php endforeach; ?>
        </select>
        <textarea name="notes" rows="4" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2" placeholder="Notas"><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
        <button class="rounded-lg bg-yellow-500 px-4 py-2 font-bold text-black">Guardar pedido</button>
        <?php if (!empty($order['customer_phone'])): ?>
            <a class="ml-2 text-sm text-green-400" target="_blank" href="https://wa.me/<?php echo preg_replace('/\D+/', '', $order['customer_phone']); ?>">WhatsApp cliente</a>
        <?php endif; ?>
    </form>

    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-800 bg-gray-900 p-5">
            <h3 class="mb-4 font-bold">Ítems</h3>
            <?php if (!$items): ?>
                <p class="mb-4 text-sm text-gray-500">Sin ítems registrados (pedido manual / WhatsApp)</p>
            <?php else: ?>
                <div class="mb-4 space-y-2">
                    <?php foreach ($items as $item): ?>
                        <div class="flex items-center justify-between rounded-xl bg-gray-800/60 px-3 py-2 text-sm">
                            <div>
                                <p><?php echo htmlspecialchars($item['product_title']); ?> · <?php echo htmlspecialchars($item['variant_title']); ?></p>
                                <p class="text-xs text-gray-500">x<?php echo (int)$item['quantity']; ?> · $<?php echo number_format($item['price'], 0, ',', '.'); ?></p>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
                                <button class="text-red-400 text-xs">Quitar</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-2 border-t border-gray-800 pt-4">
                <input type="hidden" name="action" value="add_item">
                <p class="text-xs text-gray-400">Agregar ítem</p>
                <input name="product_title" required placeholder="Nombre producto" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm">
                <div class="grid grid-cols-3 gap-2">
                    <input name="variant_title" placeholder="Talla" class="rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm">
                    <input type="number" name="quantity" value="1" class="rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm">
                    <input type="number" name="price" placeholder="Precio" class="rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm">
                </div>
                <input type="hidden" name="product_id" value="0">
                <input type="hidden" name="variant_id" value="0">
                <button class="w-full rounded-lg bg-yellow-500 py-2 text-sm font-bold text-black">Agregar</button>
            </form>
        </div>
    </div>
</div>

<?php adminLayoutEnd(); ?>
