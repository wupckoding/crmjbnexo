<?php
require_once 'includes/auth_check.php';
$pageTitle = __('perf_titulo', 'Mi Perfil');
$currentPage = 'usuarios';

$id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $id]);
$perfil = $stmt->fetch();

if (!$perfil) { header('Location: dashboard.php'); exit; }

$esPropio = ($perfil['id'] == $_SESSION['user_id']);
$esAdmin = ($_SESSION['user_role'] === 'admin');

// Stats
$totalClientes = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE asignado_a = :u");
$totalClientes->execute(['u'=>$id]);
$totalClientes = $totalClientes->fetchColumn();

$totalFacturas = $pdo->prepare("SELECT COUNT(*) FROM facturas WHERE usuario_id = :u");
$totalFacturas->execute(['u'=>$id]);
$totalFacturas = $totalFacturas->fetchColumn();

$ingresoTotal = $pdo->prepare("SELECT COALESCE(SUM(f.total),0) FROM facturas f WHERE f.usuario_id = :u AND f.estado = 'pagada'");
$ingresoTotal->execute(['u'=>$id]);
$ingresoTotal = $ingresoTotal->fetchColumn();

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && ($esPropio || $esAdmin)) {
    $file = $_FILES['avatar'];
    if ($file['error'] === UPLOAD_ERR_OK && in_array(mime_content_type($file['tmp_name']), ['image/jpeg','image/png','image/webp','image/gif'])) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fname = 'avatar_' . $id . '_' . time() . '.' . $ext;
        $dest = __DIR__ . '/uploads/avatars/' . $fname;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            // Delete old avatar
            if ($perfil['avatar'] && file_exists(__DIR__ . '/uploads/avatars/' . $perfil['avatar'])) {
                unlink(__DIR__ . '/uploads/avatars/' . $perfil['avatar']);
            }
            $pdo->prepare("UPDATE usuarios SET avatar = :a WHERE id = :id")->execute(['a'=>$fname, 'id'=>$id]);
            header('Location: perfil.php?id=' . $id . '&msg=avatar');
            exit;
        }
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && ($esPropio || $esAdmin)) {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if ($nombre && $email) {
        $pdo->prepare("UPDATE usuarios SET nombre=:n, email=:e WHERE id=:id")
            ->execute(['n'=>$nombre, 'e'=>$email, 'id'=>$id]);
        
        if (isset($_POST['new_password']) && !empty($_POST['new_password'])) {
            $hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE usuarios SET password = :p WHERE id = :id")->execute(['p'=>$hash, 'id'=>$id]);
        }
        
        if ($esPropio) {
            $_SESSION['user_name'] = $nombre;
        }
        header('Location: perfil.php?id=' . $id . '&msg=updated');
        exit;
    }
}

// Refresh data after updates
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $id]);
$perfil = $stmt->fetch();

$initial = mb_strtoupper(mb_substr($perfil['nombre'], 0, 1));

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>
<div class="p-4 sm:p-6 space-y-4">
    
    <!-- Profile header -->
    <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-6">
        <div class="flex flex-col sm:flex-row items-center gap-6">
            <!-- Avatar -->
            <div class="relative group">
                <?php if ($perfil['avatar']): ?>
                <img src="uploads/avatars/<?php echo htmlspecialchars($perfil['avatar']); ?>" class="w-24 h-24 rounded-full object-cover">
                <?php else: ?>
                <div class="w-24 h-24 rounded-full bg-gradient-to-br from-nexo-500 to-purple-600 flex items-center justify-center text-white text-3xl font-bold"><?php echo $initial; ?></div>
                <?php endif; ?>
                <?php if ($esPropio || $esAdmin): ?>
                <form method="POST" enctype="multipart/form-data" class="absolute inset-0 rounded-full bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                    <label class="cursor-pointer flex items-center justify-center w-full h-full">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <input type="file" name="avatar" accept="image/*" class="hidden" onchange="this.form.submit()">
                    </label>
                </form>
                <?php endif; ?>
            </div>
            <div class="text-center sm:text-left">
                <h2 class="text-xl font-bold"><?php echo htmlspecialchars($perfil['nombre']); ?></h2>
                <p class="dark:text-white/40 text-gray-400 text-sm"><?php echo htmlspecialchars($perfil['email']); ?></p>
                <div class="flex items-center gap-2 justify-center sm:justify-start mt-2">
                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-full <?php echo $perfil['rol'] === 'admin' ? 'bg-nexo-500/20 text-nexo-400' : 'bg-blue-500/20 text-blue-400'; ?>"><?php echo ucfirst($perfil['rol']); ?></span>
                    <span class="text-[10px] dark:text-white/30 text-gray-400"><?php echo __('perf_desde', 'Desde'); ?> <?php echo date('M Y', strtotime($perfil['creado_en'])); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t dark:border-white/[0.06] border-gray-100">
            <div class="text-center"><p class="text-2xl font-bold"><?php echo $totalClientes; ?></p><p class="text-xs dark:text-white/40 text-gray-400"><?php echo __('perf_clientes', 'Clientes'); ?></p></div>
            <div class="text-center"><p class="text-2xl font-bold"><?php echo $totalFacturas; ?></p><p class="text-xs dark:text-white/40 text-gray-400"><?php echo __('perf_facturas', 'Facturas'); ?></p></div>
            <div class="text-center"><p class="text-2xl font-bold text-emerald-400">$<?php echo number_format($ingresoTotal, 0, ',', '.'); ?></p><p class="text-xs dark:text-white/40 text-gray-400"><?php echo __('perf_ingreso_total', 'Ingreso Total'); ?></p></div>
        </div>
    </div>

    <?php if ($esPropio || $esAdmin): ?>
    <!-- Edit form -->
    <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-6">
        <h3 class="font-semibold mb-4"><?php echo __('perf_editar', 'Editar Perfil'); ?></h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="update_profile" value="1">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('perf_nombre', 'Nombre'); ?></label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($perfil['nombre']); ?>" required class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('perf_email', 'Email'); ?></label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($perfil['email']); ?>" required class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
            </div>
            <div>
                <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('perf_nueva_contrasena', 'Nueva Contraseña'); ?></label>
                <input type="password" name="new_password" placeholder="<?php echo __('perf_dejar_vacio', 'Dejar vacío para no cambiar'); ?>" minlength="6" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
            </div>
            <button type="submit" class="btn-purple px-6 py-2.5 rounded-xl text-sm font-medium text-white"><?php echo __('perf_guardar', 'Guardar Cambios'); ?></button>
        </form>
    </div>

    <!-- 2FA Section -->
    <?php if ($esPropio): ?>
    <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-6" x-data="twoFactorApp()">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-semibold flex items-center gap-2">🔐 <?php echo __('perf_2fa_titulo', 'Autenticación de Dos Factores (2FA)'); ?></h3>
                <p class="text-xs dark:text-white/40 text-gray-400 mt-0.5"><?php echo __('perf_2fa_desc', 'Protege tu cuenta con un código temporal'); ?></p>
            </div>
            <?php if ($perfil['totp_activo']): ?>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400"><?php echo __('perf_2fa_activo', 'Activo'); ?></span>
            <?php else: ?>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-yellow-500/20 text-yellow-400"><?php echo __('perf_2fa_inactivo', 'Inactivo'); ?></span>
            <?php endif; ?>
        </div>

        <?php if (!$perfil['totp_activo']): ?>
        <!-- Enable 2FA -->
        <div x-show="!setupMode">
            <p class="text-sm dark:text-white/60 text-gray-500 mb-3"><?php echo __('perf_2fa_intro', 'Agrega una capa extra de seguridad a tu cuenta usando una app como Google Authenticator o Authy.'); ?></p>
            <button @click="startSetup()" class="btn-purple px-4 py-2 rounded-xl text-sm font-medium text-white"><?php echo __('perf_activar_2fa', 'Activar 2FA'); ?></button>
        </div>
        <div x-show="setupMode" x-cloak class="space-y-4">
            <p class="text-sm dark:text-white/60 text-gray-500"><?php echo __('perf_2fa_escanear', 'Escanea el código QR con tu app de autenticación:'); ?></p>
            <div class="flex flex-col sm:flex-row items-center gap-6">
                <div class="bg-white p-3 rounded-xl">
                    <img :src="qrUrl" class="w-48 h-48" x-show="qrUrl">
                </div>
                <div class="space-y-3 flex-1">
                    <div>
                        <p class="text-xs dark:text-white/40 text-gray-400 mb-1"><?php echo __('perf_2fa_manual', 'O ingresa este código manualmente:'); ?></p>
                        <code class="text-sm font-mono bg-nexo-600/10 text-nexo-400 px-3 py-1.5 rounded-lg block" x-text="secret"></code>
                    </div>
                    <div>
                        <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('perf_2fa_codigo', 'Código de verificación'); ?></label>
                        <div class="flex gap-2">
                            <input type="text" x-model="verifyCode" maxlength="6" pattern="[0-9]*" inputmode="numeric" placeholder="000000" class="w-32 px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 text-center font-mono tracking-widest">
                            <button @click="enableTwoFactor()" class="btn-purple px-4 py-2 rounded-xl text-sm font-medium text-white" :disabled="loading"><?php echo __('perf_2fa_verificar', 'Verificar'); ?></button>
                        </div>
                        <p x-show="error" class="text-xs text-red-400 mt-1" x-text="error"></p>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Disable 2FA -->
        <div class="space-y-3">
            <p class="text-sm dark:text-white/60 text-gray-500"><?php echo __('perf_2fa_activo_desc', 'La autenticación de dos factores está activa. Para desactivarla, ingresa un código de tu app:'); ?></p>
            <div class="flex gap-2">
                <input type="text" x-model="verifyCode" maxlength="6" pattern="[0-9]*" inputmode="numeric" placeholder="000000" class="w-32 px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 text-center font-mono tracking-widest">
                <button @click="disableTwoFactor()" class="px-4 py-2 rounded-xl text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors" :disabled="loading"><?php echo __('perf_desactivar_2fa', 'Desactivar 2FA'); ?></button>
            </div>
            <p x-show="error" class="text-xs text-red-400 mt-1" x-text="error"></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
</main>

<script>
function twoFactorApp() {
    return {
        setupMode: false,
        secret: '',
        qrUrl: '',
        verifyCode: '',
        error: '',
        loading: false,

        async startSetup() {
            const fd = new FormData();
            fd.append('action', 'setup');
            const r = await fetch('api/twofactor.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.ok) {
                this.secret = d.secret;
                this.qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=192x192&data=' + encodeURIComponent(d.otpauth);
                this.setupMode = true;
            }
        },
        async enableTwoFactor() {
            this.error = '';
            this.loading = true;
            const fd = new FormData();
            fd.append('action', 'enable');
            fd.append('code', this.verifyCode);
            const r = await fetch('api/twofactor.php', { method: 'POST', body: fd });
            const d = await r.json();
            this.loading = false;
            if (d.ok) location.reload();
            else this.error = d.error;
        },
        async disableTwoFactor() {
            this.error = '';
            this.loading = true;
            const fd = new FormData();
            fd.append('action', 'disable');
            fd.append('code', this.verifyCode);
            const r = await fetch('api/twofactor.php', { method: 'POST', body: fd });
            const d = await r.json();
            this.loading = false;
            if (d.ok) location.reload();
            else this.error = d.error;
        }
    };
}
</script>
<?php include 'includes/footer.php'; ?>
