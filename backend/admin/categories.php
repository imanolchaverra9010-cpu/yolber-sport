<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireAdmin();
$db = Database::getInstance();

function categoryImageDir(): string {
    $dir = dirname(__DIR__) . '/uploads/categories';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function isCategoryImagePath(?string $value): bool {
    $value = trim((string)$value);
    if ($value === '') return false;
    return (bool)preg_match('#^(https?:)?//#i', $value)
        || str_contains($value, '/')
        || str_contains($value, '.');
}

function categoryImagePublicUrl(?string $value): string {
    $value = trim((string)$value);
    if (!isCategoryImagePath($value)) return '';
    if (preg_match('#^(https?:)?//#i', $value) || str_starts_with($value, 'data:')) {
        return $value;
    }
    return '../' . ltrim($value, '/');
}

function uploadCategoryImage(array $file, string $slug, string $subdir = 'categories'): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir la imagen');
    }
    if (($file['size'] ?? 0) > 4 * 1024 * 1024) {
        throw new Exception('La imagen no puede superar 4 MB');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: '';
    $map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($map[$mime])) {
        throw new Exception('Formato no permitido. Usa JPG, PNG, WEBP o GIF');
    }

    $dir = dirname(__DIR__) . '/uploads/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $safeSlug = preg_replace('/[^a-z0-9\-]+/', '-', strtolower($slug)) ?: 'categoria';
    $filename = $safeSlug . '-' . time() . '.' . $map[$mime];
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception('No se pudo guardar la imagen');
    }
    return 'uploads/' . $subdir . '/' . $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save_banner') {
            try {
                $db->query("CREATE TABLE IF NOT EXISTS settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) UNIQUE NOT NULL,
                    setting_value TEXT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            } catch (Throwable $e) {}

            $current = getSetting($db, 'categories_banner_image', '');
            $manualUrl = trim($_POST['banner_url'] ?? '');
            $remove = isset($_POST['remove_banner']);
            $uploaded = uploadCategoryImage($_FILES['banner_image'] ?? [], 'categories-banner', 'banners');

            $next = $current;
            if ($remove) $next = '';
            if ($uploaded !== '') $next = $uploaded;
            elseif ($manualUrl !== '') $next = $manualUrl;

            setSetting($db, 'categories_banner_image', $next);
            setSetting($db, 'categories_banner_title', trim($_POST['banner_title'] ?? 'Categorías'));
            setSetting($db, 'categories_banner_subtitle', trim($_POST['banner_subtitle'] ?? ''));
            flashSet('success', 'Banner de categorías guardado');
        } elseif ($action === 'create' || $action === 'update') {
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            $manualUrl = trim($_POST['image_url'] ?? '');
            $removeImage = isset($_POST['remove_image']);

            if ($name === '') throw new Exception('El nombre es obligatorio');
            if ($slug === '') $slug = adminSlugify($name);
            else $slug = adminSlugify($slug);

            $uploaded = uploadCategoryImage($_FILES['image'] ?? [], $slug);
            $iconUrl = '';

            if ($action === 'create') {
                if ($db->fetchOne("SELECT id FROM categories WHERE slug = ?", [$slug])) {
                    throw new Exception('Slug duplicado');
                }
                $iconUrl = $uploaded ?: $manualUrl;
                if ($iconUrl === '') {
                    throw new Exception('Sube una imagen o pega una URL para la categoría');
                }
                $db->query(
                    "INSERT INTO categories (name, slug, icon_url, sort_order, is_active) VALUES (?,?,?,?,?)",
                    [$name, $slug, $iconUrl, $sortOrder, $isActive]
                );
                flashSet('success', 'Categoría creada');
            } else {
                $id = (int)($_POST['id'] ?? 0);
                $current = $db->fetchOne("SELECT * FROM categories WHERE id = ?", [$id]);
                if (!$current) throw new Exception('Categoría no encontrada');
                if ($db->fetchOne("SELECT id FROM categories WHERE slug = ? AND id != ?", [$slug, $id])) {
                    throw new Exception('Slug duplicado');
                }

                $iconUrl = $current['icon_url'] ?? '';
                if ($removeImage) {
                    $iconUrl = '';
                }
                if ($uploaded !== '') {
                    $iconUrl = $uploaded;
                } elseif ($manualUrl !== '') {
                    $iconUrl = $manualUrl;
                }
                if ($iconUrl === '') {
                    throw new Exception('La categoría necesita una imagen');
                }

                $db->query(
                    "UPDATE categories SET name=?, slug=?, icon_url=?, sort_order=?, is_active=? WHERE id=?",
                    [$name, $slug, $iconUrl, $sortOrder, $isActive, $id]
                );
                flashSet('success', 'Categoría actualizada');
            }
        } elseif ($action === 'delete') {
            $db->query("DELETE FROM categories WHERE id = ?", [(int)($_POST['id'] ?? 0)]);
            flashSet('success', 'Categoría eliminada');
        }
    } catch (Exception $e) {
        flashSet('error', $e->getMessage());
    }
    header('Location: categories.php' . (!empty($_POST['id']) && ($_POST['action'] ?? '') === 'update' ? '?edit=' . (int)$_POST['id'] : ''));
    exit;
}

try {
    $categories = $db->fetchAll("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
} catch (Exception $e) {
    $categories = [];
    flashSet('error', 'Ejecuta migrations/001_categories.sql');
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editCategory = null;
foreach ($categories as $cat) {
    if ((int)$cat['id'] === $editId) { $editCategory = $cat; break; }
}
$editImageUrl = categoryImagePublicUrl($editCategory['icon_url'] ?? '');
$bannerPath = getSetting($db, 'categories_banner_image', '');
$bannerPreview = categoryImagePublicUrl($bannerPath);
$bannerTitle = getSetting($db, 'categories_banner_title', 'Categorías');
$bannerSubtitle = getSetting($db, 'categories_banner_subtitle', 'Elige una categoría e inicia tu próximo look');

adminLayoutStart('Categorías', 'categories');
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold">Categorías</h2>
    <p class="text-sm text-gray-400">Banner de la página + imágenes de cada círculo del carrusel</p>
</div>

<form method="POST" enctype="multipart/form-data" class="mb-6 space-y-4 rounded-2xl border border-yellow-500/30 bg-gray-900 p-5">
    <input type="hidden" name="action" value="save_banner">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="font-bold text-yellow-500">Banner página Categorías</h3>
            <p class="text-xs text-gray-400">Imagen de fondo del encabezado “CATEGORÍAS” en la tienda</p>
        </div>
        <?php if ($bannerPreview): ?>
            <img src="<?php echo htmlspecialchars($bannerPreview); ?>" alt="" class="h-20 w-36 rounded-lg object-cover border border-yellow-500/30">
        <?php endif; ?>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1 block text-xs text-gray-400">Título</label>
            <input name="banner_title" value="<?php echo htmlspecialchars($bannerTitle); ?>" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
        </div>
        <div>
            <label class="mb-1 block text-xs text-gray-400">Subtítulo</label>
            <input name="banner_subtitle" value="<?php echo htmlspecialchars($bannerSubtitle); ?>" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
        </div>
    </div>
    <div>
        <label class="mb-1 block text-xs text-gray-400">Subir imagen de fondo</label>
        <input type="file" name="banner_image" accept="image/jpeg,image/png,image/webp,image/gif" class="block w-full text-sm text-gray-300 file:mr-3 file:rounded-lg file:border-0 file:bg-yellow-500 file:px-3 file:py-2 file:font-bold file:text-black">
    </div>
    <div>
        <label class="mb-1 block text-xs text-gray-400">O URL de imagen</label>
        <input name="banner_url" value="<?php echo htmlspecialchars($bannerPath); ?>" placeholder="uploads/banners/... o https://..." class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm">
    </div>
    <?php if ($bannerPath): ?>
        <label class="flex items-center gap-2 text-sm text-gray-300">
            <input type="checkbox" name="remove_banner"> Quitar imagen del banner
        </label>
    <?php endif; ?>
    <button class="rounded-lg bg-yellow-500 px-4 py-2 font-bold text-black">Guardar banner</button>
</form>

<div class="grid gap-6 lg:grid-cols-2">
    <form method="POST" enctype="multipart/form-data" class="space-y-4 rounded-2xl border border-gray-800 bg-gray-900 p-5">
        <h3 class="font-bold"><?php echo $editCategory ? 'Editar' : 'Nueva'; ?> categoría</h3>
        <input type="hidden" name="action" value="<?php echo $editCategory ? 'update' : 'create'; ?>">
        <?php if ($editCategory): ?><input type="hidden" name="id" value="<?php echo (int)$editCategory['id']; ?>"><?php endif; ?>

        <div>
            <label class="mb-1 block text-xs text-gray-400">Nombre</label>
            <input name="name" required value="<?php echo htmlspecialchars($editCategory['name'] ?? ''); ?>" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
        </div>
        <div>
            <label class="mb-1 block text-xs text-gray-400">Slug</label>
            <input name="slug" value="<?php echo htmlspecialchars($editCategory['slug'] ?? ''); ?>" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2" placeholder="auto">
        </div>

        <div>
            <label class="mb-2 block text-xs text-gray-400">Imagen del círculo</label>
            <?php if ($editImageUrl): ?>
                <div class="mb-3 flex items-center gap-3">
                    <img src="<?php echo htmlspecialchars($editImageUrl); ?>" alt="" class="h-20 w-20 rounded-full object-cover border border-yellow-500/40">
                    <label class="flex items-center gap-2 text-sm text-gray-300">
                        <input type="checkbox" name="remove_image"> Quitar imagen actual
                    </label>
                </div>
            <?php endif; ?>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" class="block w-full text-sm text-gray-300 file:mr-3 file:rounded-lg file:border-0 file:bg-yellow-500 file:px-3 file:py-2 file:font-bold file:text-black">
            <p class="mt-2 text-[11px] text-gray-500">JPG, PNG, WEBP o GIF · máx. 3 MB · se ve redonda en la tienda</p>
        </div>

        <div>
            <label class="mb-1 block text-xs text-gray-400">O URL de imagen</label>
            <input name="image_url" value="<?php echo htmlspecialchars(isCategoryImagePath($editCategory['icon_url'] ?? '') ? ($editCategory['icon_url'] ?? '') : ''); ?>" placeholder="uploads/categories/... o https://..." class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm">
        </div>

        <div class="flex items-center gap-4">
            <div class="flex-1">
                <label class="mb-1 block text-xs text-gray-400">Orden</label>
                <input type="number" name="sort_order" value="<?php echo htmlspecialchars((string)($editCategory['sort_order'] ?? '0')); ?>" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
            </div>
            <label class="mt-5 flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" <?php echo !isset($editCategory) || !empty($editCategory['is_active']) ? 'checked' : ''; ?>> Activa</label>
        </div>
        <div class="flex gap-2">
            <button class="rounded-lg bg-yellow-500 px-4 py-2 font-bold text-black"><?php echo $editCategory ? 'Guardar' : 'Crear'; ?></button>
            <?php if ($editCategory): ?><a href="categories.php" class="rounded-lg border border-gray-700 px-4 py-2">Cancelar</a><?php endif; ?>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-gray-800 bg-gray-900">
        <div class="border-b border-gray-800 px-4 py-3 font-semibold">Listado</div>
        <div class="divide-y divide-gray-800">
            <?php foreach ($categories as $cat):
                $img = categoryImagePublicUrl($cat['icon_url'] ?? '');
            ?>
                <div class="flex items-center gap-3 px-4 py-3">
                    <?php if ($img): ?>
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="" class="h-12 w-12 rounded-full object-cover border border-yellow-500/30">
                    <?php else: ?>
                        <div class="grid h-12 w-12 place-items-center rounded-full bg-yellow-500/15 text-sm font-bold text-yellow-500">
                            <?php echo strtoupper(substr($cat['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium"><?php echo htmlspecialchars($cat['name']); ?></p>
                        <p class="truncate text-xs text-gray-500"><?php echo htmlspecialchars($cat['slug']); ?><?php echo $img ? '' : ' · sin imagen'; ?></p>
                    </div>
                    <a href="categories.php?edit=<?php echo (int)$cat['id']; ?>" class="text-sm text-blue-400">Editar</a>
                    <form method="POST" onsubmit="return confirm('¿Eliminar?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
                        <button class="text-sm text-red-400">Eliminar</button>
                    </form>
                </div>
            <?php endforeach; ?>
            <?php if (!$categories): ?><p class="p-6 text-sm text-gray-500">Sin categorías</p><?php endif; ?>
        </div>
    </div>
</div>

<?php adminLayoutEnd(); ?>
