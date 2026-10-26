<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireAdmin();
$db = Database::getInstance();
$productId = (int)($_GET['id'] ?? 0);
$product = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$productId]);
if (!$product) {
    flashSet('error', 'Producto no encontrado');
    header('Location: products.php');
    exit;
}

if (isset($_GET['delete_variant'])) {
    $db->query("DELETE FROM product_variants WHERE id = ? AND product_id = ?", [(int)$_GET['delete_variant'], $productId]);
    flashSet('success', 'Variante eliminada');
    header("Location: edit.php?id=$productId");
    exit;
}
if (isset($_GET['delete_image'])) {
    $db->query("DELETE FROM product_images WHERE id = ? AND product_id = ?", [(int)$_GET['delete_image'], $productId]);
    flashSet('success', 'Imagen eliminada');
    header("Location: edit.php?id=$productId");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_product';

    if ($action === 'update_product') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $handle = trim($_POST['handle'] ?? '') ?: adminSlugify($title);
        $status = $_POST['status'] ?? 'active';
        $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
        $db->query(
            "UPDATE products SET title=?, description=?, handle=?, status=?, category_id=? WHERE id=?",
            [$title, $description, $handle, $status, $categoryId, $productId]
        );
        flashSet('success', 'Producto actualizado');
    }

    if ($action === 'add_variant') {
        $vTitle = trim($_POST['variant_title'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $sku = trim($_POST['sku'] ?? '');
        $available = isset($_POST['available']) ? 1 : 0;
        if ($vTitle !== '') {
            $db->query(
                "INSERT INTO product_variants (product_id, title, price, available_for_sale, inventory_quantity, sku) VALUES (?,?,?,?,?,?)",
                [$productId, $vTitle, $price, $available, $stock, $sku]
            );
            $vid = (int)$db->lastInsertId();
            $db->query("INSERT INTO variant_options (variant_id, option_name, option_value) VALUES (?, 'Talla', ?)", [$vid, $vTitle]);
            flashSet('success', 'Variante agregada');
        }
    }

    if ($action === 'update_variant') {
        $vid = (int)($_POST['variant_id'] ?? 0);
        $db->query(
            "UPDATE product_variants SET title=?, price=?, inventory_quantity=?, sku=?, available_for_sale=? WHERE id=? AND product_id=?",
            [
                trim($_POST['variant_title'] ?? ''),
                (float)($_POST['price'] ?? 0),
                (int)($_POST['stock'] ?? 0),
                trim($_POST['sku'] ?? ''),
                isset($_POST['available']) ? 1 : 0,
                $vid,
                $productId,
            ]
        );
        flashSet('success', 'Variante actualizada');
    }

    if ($action === 'add_image') {
        $url = trim($_POST['image_url'] ?? '');
        $alt = trim($_POST['alt_text'] ?? $product['title']);
        if ($url !== '') {
            $max = $db->fetchOne("SELECT MAX(position) as m FROM product_images WHERE product_id = ?", [$productId]);
            $pos = (int)($max['m'] ?? -1) + 1;
            $db->query(
                "INSERT INTO product_images (product_id, url, alt_text, position) VALUES (?,?,?,?)",
                [$productId, $url, $alt, $pos]
            );
            flashSet('success', 'Imagen agregada');
        }
    }

    header("Location: edit.php?id=$productId");
    exit;
}

$product = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$productId]);
$variants = $db->fetchAll("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id", [$productId]);
$images = $db->fetchAll("SELECT * FROM product_images WHERE product_id = ? ORDER BY position", [$productId]);
try {
    $categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY sort_order, name");
} catch (Throwable $e) {
    $categories = [];
}

adminLayoutStart('Editar producto', 'products');
?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="products.php" class="text-xs text-gray-400 hover:text-yellow-500">← Productos</a>
        <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($product['title']); ?></h2>
    </div>
    <a href="../../product/<?php echo htmlspecialchars($product['handle']); ?>" target="_blank" class="text-sm text-yellow-500">Ver en tienda →</a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-6">
        <form method="POST" class="rounded-2xl border border-gray-800 bg-gray-900 p-5 space-y-4">
            <input type="hidden" name="action" value="update_product">
            <h3 class="font-bold">Datos generales</h3>
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs text-gray-400">Título</label>
                    <input name="title" required value="<?php echo htmlspecialchars($product['title']); ?>" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-gray-400">Handle</label>
                    <input name="handle" value="<?php echo htmlspecialchars($product['handle']); ?>" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs text-gray-400">Descripción</label>
                <textarea name="description" rows="4" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs text-gray-400">Categoría</label>
                    <select name="category_id" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
                        <option value="">Sin categoría</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>" <?php echo (int)$product['category_id'] === (int)$c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs text-gray-400">Estado</label>
                    <select name="status" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
                        <?php foreach (['active','draft','archived'] as $st): ?>
                            <option value="<?php echo $st; ?>" <?php echo $product['status'] === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button class="rounded-lg bg-yellow-500 px-4 py-2 font-bold text-black">Guardar producto</button>
        </form>

        <div class="rounded-2xl border border-gray-800 bg-gray-900 p-5">
            <h3 class="mb-4 font-bold">Variantes / tallas</h3>
            <div class="space-y-3 mb-6">
                <?php foreach ($variants as $v): ?>
                    <form method="POST" class="grid gap-2 rounded-xl border border-gray-800 bg-gray-950/50 p-3 md:grid-cols-6 md:items-end">
                        <input type="hidden" name="action" value="update_variant">
                        <input type="hidden" name="variant_id" value="<?php echo (int)$v['id']; ?>">
                        <div>
                            <label class="text-[10px] text-gray-500">Talla</label>
                            <input name="variant_title" value="<?php echo htmlspecialchars($v['title']); ?>" class="w-full rounded border border-gray-700 bg-gray-800 px-2 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="text-[10px] text-gray-500">Precio</label>
                            <input type="number" name="price" value="<?php echo htmlspecialchars($v['price']); ?>" class="w-full rounded border border-gray-700 bg-gray-800 px-2 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="text-[10px] text-gray-500">Stock</label>
                            <input type="number" name="stock" value="<?php echo (int)$v['inventory_quantity']; ?>" class="w-full rounded border border-gray-700 bg-gray-800 px-2 py-1.5 text-sm">
                        </div>
                        <div>
                            <label class="text-[10px] text-gray-500">SKU</label>
                            <input name="sku" value="<?php echo htmlspecialchars($v['sku'] ?? ''); ?>" class="w-full rounded border border-gray-700 bg-gray-800 px-2 py-1.5 text-sm">
                        </div>
                        <label class="flex items-center gap-2 text-xs text-gray-300 pb-2">
                            <input type="checkbox" name="available" <?php echo $v['available_for_sale'] ? 'checked' : ''; ?>> Disponible
                        </label>
                        <div class="flex gap-2">
                            <button class="rounded bg-blue-600 px-3 py-1.5 text-xs font-bold">Guardar</button>
                            <a href="edit.php?id=<?php echo $productId; ?>&delete_variant=<?php echo (int)$v['id']; ?>" onclick="return confirm('¿Eliminar?')" class="rounded bg-red-600/80 px-3 py-1.5 text-xs">Borrar</a>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>

            <form method="POST" class="grid gap-2 rounded-xl border border-dashed border-gray-700 p-3 md:grid-cols-5 md:items-end">
                <input type="hidden" name="action" value="add_variant">
                <div>
                    <label class="text-[10px] text-gray-500">Nueva talla</label>
                    <input name="variant_title" placeholder="L" required class="w-full rounded border border-gray-700 bg-gray-800 px-2 py-1.5 text-sm">
                </div>
                <div>
                    <label class="text-[10px] text-gray-500">Precio</label>
                    <input type="number" name="price" value="89000" class="w-full rounded border border-gray-700 bg-gray-800 px-2 py-1.5 text-sm">
                </div>
                <div>
                    <label class="text-[10px] text-gray-500">Stock</label>
                    <input type="number" name="stock" value="10" class="w-full rounded border border-gray-700 bg-gray-800 px-2 py-1.5 text-sm">
                </div>
                <div>
                    <label class="text-[10px] text-gray-500">SKU</label>
                    <input name="sku" class="w-full rounded border border-gray-700 bg-gray-800 px-2 py-1.5 text-sm">
                </div>
                <button class="rounded-lg bg-yellow-500 py-2 text-sm font-bold text-black">Agregar</button>
            </form>
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-800 bg-gray-900 p-5">
            <h3 class="mb-4 font-bold">Imágenes</h3>
            <div class="mb-4 grid grid-cols-2 gap-3">
                <?php foreach ($images as $im): ?>
                    <div class="relative overflow-hidden rounded-xl border border-gray-800">
                        <img src="<?php echo htmlspecialchars(adminMediaUrl($im['url'] ?? '')); ?>" class="aspect-square w-full object-cover" alt="">
                        <a href="edit.php?id=<?php echo $productId; ?>&delete_image=<?php echo (int)$im['id']; ?>" onclick="return confirm('¿Eliminar imagen?')" class="absolute right-2 top-2 rounded bg-black/70 px-2 py-1 text-xs text-red-300">✕</a>
                    </div>
                <?php endforeach; ?>
            </div>
            <form method="POST" class="space-y-2">
                <input type="hidden" name="action" value="add_image">
                <input name="image_url" required placeholder="URL de la imagen" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm">
                <input name="alt_text" placeholder="Texto alt" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm">
                <button class="w-full rounded-lg bg-yellow-500 py-2 text-sm font-bold text-black">Agregar imagen</button>
            </form>
        </div>
    </div>
</div>

<?php adminLayoutEnd(); ?>
