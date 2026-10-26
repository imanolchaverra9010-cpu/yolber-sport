<?php
require_once __DIR__ . '/includes/bootstrap.php';
requireAdmin();
$db = Database::getInstance();

if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    if ($userId !== (int)$_SESSION['user_id']) {
        $db->query("DELETE FROM users WHERE id = ?", [$userId]);
        flashSet('success', 'Usuario eliminado');
    } else {
        flashSet('error', 'No puedes eliminar tu propia cuenta');
    }
    header('Location: users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
        if ($email === '' || strlen($password) < 6) {
            flashSet('error', 'Email y contraseña (mín. 6) son obligatorios');
        } else {
            $exists = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
            if ($exists) {
                flashSet('error', 'El email ya existe');
            } else {
                try {
                    $db->query(
                        "INSERT INTO users (email, password_hash, name, is_admin) VALUES (?,?,?,?)",
                        [$email, password_hash($password, PASSWORD_DEFAULT), $name, $isAdmin]
                    );
                } catch (Throwable $e) {
                    $db->query(
                        "INSERT INTO users (email, password_hash, name) VALUES (?,?,?)",
                        [$email, password_hash($password, PASSWORD_DEFAULT), $name]
                    );
                }
                flashSet('success', 'Usuario creado');
            }
        }
    }
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
        $password = $_POST['password'] ?? '';
        try {
            if ($password !== '') {
                $db->query("UPDATE users SET name=?, email=?, is_admin=?, password_hash=? WHERE id=?", [
                    $name, $email, $isAdmin, password_hash($password, PASSWORD_DEFAULT), $id
                ]);
            } else {
                $db->query("UPDATE users SET name=?, email=?, is_admin=? WHERE id=?", [$name, $email, $isAdmin, $id]);
            }
        } catch (Throwable $e) {
            if ($password !== '') {
                $db->query("UPDATE users SET name=?, email=?, password_hash=? WHERE id=?", [
                    $name, $email, password_hash($password, PASSWORD_DEFAULT), $id
                ]);
            } else {
                $db->query("UPDATE users SET name=?, email=? WHERE id=?", [$name, $email, $id]);
            }
        }
        flashSet('success', 'Usuario actualizado');
    }
    header('Location: users.php');
    exit;
}

try {
    $users = $db->fetchAll("SELECT id, email, name, is_admin, created_at FROM users ORDER BY created_at DESC");
} catch (Throwable $e) {
    $users = $db->fetchAll("SELECT id, email, name, created_at FROM users ORDER BY created_at DESC");
    foreach ($users as &$u) { $u['is_admin'] = 0; }
}

adminLayoutStart('Usuarios', 'users');
?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Usuarios</h2>
        <p class="text-sm text-gray-400">Administradores y cuentas</p>
    </div>
    <button onclick="document.getElementById('userModal').style.display='flex'" class="rounded-lg bg-yellow-500 px-4 py-2 font-bold text-black">+ Usuario</button>
</div>

<div class="overflow-hidden rounded-2xl border border-gray-800 bg-gray-900">
    <table class="w-full text-sm">
        <thead class="bg-gray-800 text-left text-gray-400">
            <tr>
                <th class="px-4 py-3">Nombre</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Rol</th>
                <th class="px-4 py-3">Alta</th>
                <th class="px-4 py-3">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr class="border-t border-gray-800">
                    <td class="px-4 py-3"><?php echo htmlspecialchars($user['name'] ?? '—'); ?></td>
                    <td class="px-4 py-3 text-gray-400"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2 py-1 text-xs <?php echo !empty($user['is_admin']) ? 'bg-yellow-500/15 text-yellow-400' : 'bg-gray-700 text-gray-300'; ?>">
                            <?php echo !empty($user['is_admin']) ? 'Admin' : 'Usuario'; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                    <td class="px-4 py-3">
                        <details class="inline">
                            <summary class="cursor-pointer text-blue-400">Editar</summary>
                            <form method="POST" class="mt-2 space-y-2 rounded-xl border border-gray-700 bg-gray-950 p-3">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                                <input name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" class="w-full rounded border border-gray-700 bg-gray-800 px-2 py-1 text-xs">
                                <input name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full rounded border border-gray-700 bg-gray-800 px-2 py-1 text-xs">
                                <input name="password" type="password" placeholder="Nueva contraseña (opcional)" class="w-full rounded border border-gray-700 bg-gray-800 px-2 py-1 text-xs">
                                <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_admin" <?php echo !empty($user['is_admin']) ? 'checked' : ''; ?>> Admin</label>
                                <button class="rounded bg-yellow-500 px-3 py-1 text-xs font-bold text-black">Guardar</button>
                            </form>
                        </details>
                        <?php if ((int)$user['id'] !== (int)$_SESSION['user_id']): ?>
                            <a href="users.php?delete=<?php echo (int)$user['id']; ?>" onclick="return confirm('¿Eliminar?')" class="ml-3 text-red-400">Eliminar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="userModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4" style="display:none" onclick="if(event.target===this)this.style.display='none'">
    <form method="POST" class="w-full max-w-md space-y-3 rounded-2xl border border-gray-700 bg-gray-900 p-6" onclick="event.stopPropagation()">
        <h3 class="text-lg font-bold">Nuevo usuario</h3>
        <input type="hidden" name="action" value="create">
        <input name="name" placeholder="Nombre" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
        <input name="email" type="email" required placeholder="Email" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
        <input name="password" type="password" required placeholder="Contraseña" class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_admin" checked> Administrador</label>
        <div class="flex gap-2">
            <button class="flex-1 rounded-lg bg-yellow-500 py-2 font-bold text-black">Crear</button>
            <button type="button" onclick="document.getElementById('userModal').style.display='none'" class="flex-1 rounded-lg border border-gray-700 py-2">Cancelar</button>
        </div>
    </form>
</div>

<?php adminLayoutEnd(); ?>
