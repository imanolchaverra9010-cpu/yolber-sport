<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireAdmin();
$db = Database::getInstance();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM products WHERE id = ?", [$id]);
    flashSet('success', 'Producto eliminado');
    header('Location: products.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $handle = trim($_POST['handle'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $imageUrl = trim($_POST['image_url'] ?? '');
    $variantTitle = trim($_POST['variant_title'] ?? 'Único');

    if ($title === '') {
        flashSet('error', 'El título es obligatorio');
        header('Location: products.php');
        exit;
    }
    if ($handle === '') $handle = adminSlugify($title);

    $exists = $db->fetchOne("SELECT id FROM products WHERE handle = ?", [$handle]);
    if ($exists) $handle .= '-' . time();

    $db->query(
        "INSERT INTO products (title, description, handle, category_id, status) VALUES (?, ?, ?, ?, ?)",
        [$title, $description, $handle, $categoryId, $status]
    );
    $productId = (int)$db->lastInsertId();

    $db->query(
        "INSERT INTO product_variants (product_id, title, price, available_for_sale, inventory_quantity, sku) VALUES (?, ?, ?, 1, ?, ?)",
        [$productId, $variantTitle ?: 'Único', $price, $stock, strtoupper(substr($handle, 0, 8)) . '-1']
    );
    $variantId = (int)$db->lastInsertId();
    $db->query(
        "INSERT INTO variant_options (variant_id, option_name, option_value) VALUES (?, 'Talla', ?)",
        [$variantId, $variantTitle ?: 'Único']
    );
    $db->query(
        "INSERT INTO product_options (product_id, name, option_values) VALUES (?, 'Talla', ?)",
        [$productId, json_encode([$variantTitle ?: 'Único'])]
    );

    if ($imageUrl !== '') {
        $db->query(
            "INSERT INTO product_images (product_id, url, alt_text, position) VALUES (?, ?, ?, 0)",
            [$productId, $imageUrl, $title]
        );
    }

    flashSet('success', 'Producto creado');
    header('Location: edit.php?id=' . $productId);
    exit;
}

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = " WHERE 1=1";
$params = [];
if ($q !== '') {
    $where .= " AND (p.title LIKE ? OR p.handle LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($status !== '') {
    $where .= " AND p.status = ?";
    $params[] = $status;
}

$countRow = $db->fetchOne("SELECT COUNT(*) AS c FROM products p" . $where, $params);
$total = (int)($countRow['c'] ?? 0);
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$sql = "SELECT p.*,
    (SELECT MIN(price) FROM product_variants WHERE product_id = p.id) as min_price,
    (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) as variant_count,
    (SELECT url FROM product_images WHERE product_id = p.id ORDER BY position LIMIT 1) as image_url,
    c.name as category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    $where
    ORDER BY p.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}";
$products = $db->fetchAll($sql, $params);

try {
    $categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY sort_order, name");
} catch (Throwable $e) {
    $categories = [];
}

function productsPageUrl(int $page, string $q, string $status): string {
    $params = [];
    if ($q !== '') $params['q'] = $q;
    if ($status !== '') $params['status'] = $status;
    if ($page > 1) $params['page'] = $page;
    $qs = http_build_query($params);
    return 'products.php' . ($qs !== '' ? '?' . $qs : '');
}

adminLayoutStart('Productos', 'products');
?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Productos</h2>
        <p class="text-sm text-gray-400"><?php echo $total; ?> resultados · página <?php echo $page; ?> de <?php echo $totalPages; ?></p>
    </div>
    <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="rounded-lg bg-yellow-500 px-4 py-2 font-bold text-black hover:bg-yellow-400">
        <i class="fas fa-plus mr-2"></i>Nuevo producto
    </button>
</div>

<form class="mb-4 flex flex-wrap gap-2" method="GET">
    <input type="search" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Buscar..."
        class="rounded-lg border border-gray-700 bg-gray-900 px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none">
    <select name="status" class="rounded-lg border border-gray-700 bg-gray-900 px-3 py-2 text-sm">
        <option value="">Todos los estados</option>
        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Activo</option>
        <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Borrador</option>
        <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>>Archivado</option>
    </select>
    <button class="rounded-lg border border-gray-700 px-4 py-2 text-sm hover:bg-gray-800">Filtrar</button>
</form>

<?php if (!$products): ?>
    <div class="rounded-2xl border border-dashed border-gray-800 px-4 py-10 text-center text-gray-500">No hay productos</div>
<?php else: ?>

<!-- Mobile cards: acciones siempre visibles -->
<div class="space-y-3 md:hidden">
    <?php foreach ($products as $p):
        $imgSrc = adminMediaUrl($p['image_url'] ?? '');
        $pid = (int)$p['id'];
    ?>
        <article class="rounded-2xl border border-gray-800 bg-gray-900 p-3">
            <div class="flex gap-3">
                <div class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-xl bg-gray-800">
                    <?php if ($imgSrc): ?>
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" class="h-full w-full object-cover" alt="" loading="lazy"
                             onerror="this.style.display='none'">
                    <?php else: ?>
                        <div class="grid h-full w-full place-items-center text-[10px] text-gray-600">N/A</div>
                    <?php endif; ?>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium"><?php echo htmlspecialchars($p['title']); ?></p>
                    <p class="truncate text-xs text-gray-500"><?php echo htmlspecialchars($p['handle']); ?></p>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-400">
                        <span><?php echo htmlspecialchars($p['category_name'] ?? '—'); ?></span>
                        <span class="text-gray-600">·</span>
                        <span class="font-bold text-yellow-500">$<?php echo number_format($p['min_price'] ?? 0, 0, ',', '.'); ?></span>
                        <span class="text-gray-600">·</span>
                        <span><?php echo (int)$p['variant_count']; ?> var.</span>
                        <span class="rounded-full px-2 py-0.5 <?php echo $p['status'] === 'active' ? 'bg-green-500/15 text-green-400' : 'bg-gray-700 text-gray-300'; ?>">
                            <?php echo htmlspecialchars($p['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <a href="edit.php?id=<?php echo $pid; ?>"
                   class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-500/15 px-3 py-2.5 text-sm font-semibold text-blue-400">
                    <i class="fas fa-pen text-xs"></i> Editar
                </a>
                <a href="products.php?delete=<?php echo $pid; ?>"
                   onclick="return confirm('¿Eliminar producto?')"
                   class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-500/15 px-3 py-2.5 text-sm font-semibold text-red-400">
                    <i class="fas fa-trash text-xs"></i> Eliminar
                </a>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<!-- Desktop table -->
<div class="hidden overflow-x-auto rounded-2xl border border-gray-800 bg-gray-900 md:block">
    <table class="w-full min-w-[720px] text-sm">
        <thead class="bg-gray-800 text-left text-gray-400">
            <tr>
                <th class="px-4 py-3">Producto</th>
                <th class="px-4 py-3">Categoría</th>
                <th class="px-4 py-3">Precio</th>
                <th class="px-4 py-3">Variantes</th>
                <th class="px-4 py-3">Estado</th>
                <th class="sticky right-0 bg-gray-800 px-4 py-3">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p):
                $imgSrc = adminMediaUrl($p['image_url'] ?? '');
                $pid = (int)$p['id'];
            ?>
                <tr class="border-t border-gray-800 hover:bg-gray-800/40">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="h-12 w-12 overflow-hidden rounded-lg bg-gray-800">
                                <?php if ($imgSrc): ?>
                                    <img src="<?php echo htmlspecialchars($imgSrc); ?>" class="h-full w-full object-cover" alt="" loading="lazy"
                                         onerror="this.style.display='none'">
                                <?php else: ?>
                                    <div class="grid h-full w-full place-items-center text-[10px] text-gray-600">N/A</div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="font-medium"><?php echo htmlspecialchars($p['title']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($p['handle']); ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-400"><?php echo htmlspecialchars($p['category_name'] ?? '—'); ?></td>
                    <td class="px-4 py-3 font-bold text-yellow-500">$<?php echo number_format($p['min_price'] ?? 0, 0, ',', '.'); ?></td>
                    <td class="px-4 py-3"><?php echo (int)$p['variant_count']; ?></td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2 py-1 text-xs <?php echo $p['status'] === 'active' ? 'bg-green-500/15 text-green-400' : 'bg-gray-700 text-gray-300'; ?>">
                            <?php echo htmlspecialchars($p['status']); ?>
                        </span>
                    </td>
                    <td class="sticky right-0 bg-gray-900 px-4 py-3 whitespace-nowrap">
                        <a href="edit.php?id=<?php echo $pid; ?>" class="mr-3 text-blue-400 hover:text-blue-300">Editar</a>
                        <a href="products.php?delete=<?php echo $pid; ?>" onclick="return confirm('¿Eliminar producto?')" class="text-red-400 hover:text-red-300">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php if ($totalPages > 1): ?>
<nav class="mt-6 flex flex-wrap items-center justify-center gap-2" aria-label="Paginación">
    <?php if ($page > 1): ?>
        <a href="<?php echo htmlspecialchars(productsPageUrl($page - 1, $q, $status)); ?>"
           class="rounded-lg border border-yellow-500/30 px-3 py-2 text-sm text-yellow-500 hover:bg-yellow-500/10">Anterior</a>
    <?php else: ?>
        <span class="rounded-lg border border-gray-800 px-3 py-2 text-sm text-gray-600">Anterior</span>
    <?php endif; ?>

    <?php
    $window = 2;
    for ($i = 1; $i <= $totalPages; $i++):
        if ($i === 1 || $i === $totalPages || ($i >= $page - $window && $i <= $page + $window)):
    ?>
        <a href="<?php echo htmlspecialchars(productsPageUrl($i, $q, $status)); ?>"
           class="min-w-9 rounded-lg border px-3 py-2 text-center text-sm <?php echo $i === $page ? 'border-yellow-500 bg-yellow-500/15 font-bold text-yellow-500' : 'border-gray-700 text-gray-300 hover:border-yellow-500/40'; ?>">
            <?php echo $i; ?>
        </a>
    <?php
        elseif ($i === $page - $window - 1 || $i === $page + $window + 1):
            echo '<span class="px-1 text-gray-600">…</span>';
        endif;
    endfor;
    ?>

    <?php if ($page < $totalPages): ?>
        <a href="<?php echo htmlspecialchars(productsPageUrl($page + 1, $q, $status)); ?>"
           class="rounded-lg border border-yellow-500/30 px-3 py-2 text-sm text-yellow-500 hover:bg-yellow-500/10">Siguiente</a>
    <?php else: ?>
        <span class="rounded-lg border border-gray-800 px-3 py-2 text-sm text-gray-600">Siguiente</span>
    <?php endif; ?>
</nav>
<?php endif; ?>

<div id="createModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4" style="display:none" onclick="if(event.target===this){this.style.display='none';this.classList.add('hidden')}">
    <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-gray-700 bg-gray-900 p-6" onclick="event.stopPropagation()">
        <h3 class="mb-4 text-xl font-bold">Nuevo producto</h3>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="action" value="create">
            <div>
                <label class="mb-1 block text-xs text-gray-400">Título</label>
                <input name="title" required class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-xs text-gray-400">Descripción</label>
                <textarea name="description" rows="3" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs text-gray-400">Handle</label>
                    <input name="handle" placeholder="auto" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-gray-400">Estado</label>
                    <select name="status" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
                        <option value="active">Activo</option>
                        <option value="draft">Borrador</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs text-gray-400">Categoría</label>
                <select name="category_id" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
                    <option value="">Sin categoría</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="mb-1 block text-xs text-gray-400">Talla/Opción</label>
                    <input name="variant_title" value="M" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-gray-400">Precio</label>
                    <input type="number" name="price" value="89000" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-gray-400">Stock</label>
                    <input type="number" name="stock" value="10" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs text-gray-400">URL imagen</label>
                <input name="image_url" placeholder="uploads/products/... o https://..." class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
            </div>
            <div class="flex gap-2 pt-2">
                <button class="flex-1 rounded-lg bg-yellow-500 py-2 font-bold text-black">Crear</button>
                <button type="button" onclick="document.getElementById('createModal').style.display='none';document.getElementById('createModal').classList.add('hidden')" class="flex-1 rounded-lg border border-gray-700 py-2">Cancelar</button>
            </div>
        </form>
    </div>
</div>
<script>
document.querySelector('[onclick*=createModal]')?.addEventListener('click', () => {
  const m = document.getElementById('createModal');
  m.classList.remove('hidden');
  m.style.display = 'flex';
});
</script>

<?php adminLayoutEnd(); ?>
