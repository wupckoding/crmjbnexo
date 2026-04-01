<?php
require_once 'includes/auth_check.php';
if ($_SESSION['user_role'] !== 'admin') { header('Location: dashboard.php'); exit; }

$pageTitle = 'Ajustes';
$currentPage = 'ajustes';

// Load current config
$config = [];
$rows = $pdo->query("SELECT clave, valor FROM configuracion_global")->fetchAll();
foreach ($rows as $r) $config[$r['clave']] = $r['valor'];

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = ['empresa_nombre','empresa_email','empresa_telefono','empresa_direccion','color_primario','logo_url','idioma'];
    foreach ($campos as $c) {
        $val = trim($_POST[$c] ?? '');
        $pdo->prepare("INSERT INTO configuracion_global (clave, valor) VALUES (:c, :v) ON DUPLICATE KEY UPDATE valor = :v2")
            ->execute(['c'=>$c, 'v'=>$val, 'v2'=>$val]);
        $config[$c] = $val;
    }

    // Handle logo upload
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['logo_file'];
        $allowed = ['image/jpeg','image/png','image/webp','image/svg+xml'];
        if (in_array(mime_content_type($file['tmp_name']), $allowed)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fname = 'logo_' . time() . '.' . $ext;
            $dest = __DIR__ . '/uploads/' . $fname;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $pdo->prepare("INSERT INTO configuracion_global (clave, valor) VALUES ('logo_url', :v) ON DUPLICATE KEY UPDATE valor = :v2")
                    ->execute(['v'=>'uploads/'.$fname, 'v2'=>'uploads/'.$fname]);
                $config['logo_url'] = 'uploads/' . $fname;
            }
        }
    }
    // Reload language if changed
    if (!empty($_POST['idioma']) && in_array($_POST['idioma'], ['es','pt','en'])) {
        $_idioma = $_POST['idioma'];
        $_langFile = __DIR__ . '/config/lang/' . $_idioma . '.php';
        if (file_exists($_langFile)) $_LANG = require $_langFile;
    }
    $msg = __('ajus_guardado');
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>
<div class="p-4 sm:p-6 space-y-5" x-data="{ saved: <?php echo isset($msg) ? 'true' : 'false'; ?>, colorVal: '<?php echo htmlspecialchars($config['color_primario'] ?? '#7c3aed'); ?>' }">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold"><?php echo __('ajus_titulo'); ?></h2>
            <p class="text-xs dark:text-white/40 text-gray-400 mt-0.5"><?php echo __('ajus_subtitulo'); ?></p>
        </div>
    </div>

    <!-- Success message -->
    <div x-show="saved" x-transition x-init="if(saved) setTimeout(()=> saved = false, 4000)" class="flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-3.5">
        <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm dark:text-emerald-400 text-emerald-600 font-medium"><?php echo __('ajus_guardado'); ?></p>
    </div>

    <form method="POST" enctype="multipart/form-data" class="space-y-5">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            <!-- Left column: Company data (2 cols) -->
            <div class="lg:col-span-2 dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
                <div class="px-5 py-3.5 border-b dark:border-white/[0.06] border-gray-100 flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-nexo-600/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <h3 class="font-semibold text-sm"><?php echo __('ajus_datos_empresa'); ?></h3>
                </div>
                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs dark:text-white/50 text-gray-500 mb-1.5 block font-medium"><?php echo __('ajus_nombre_empresa'); ?></label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 dark:text-white/20 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                <input type="text" name="empresa_nombre" value="<?php echo htmlspecialchars($config['empresa_nombre'] ?? ''); ?>" placeholder="Ej: Mi Empresa S.A." class="w-full pl-10 pr-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
                            </div>
                        </div>
                        <div>
                            <label class="text-xs dark:text-white/50 text-gray-500 mb-1.5 block font-medium"><?php echo __('ajus_email_contacto'); ?></label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 dark:text-white/20 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <input type="email" name="empresa_email" value="<?php echo htmlspecialchars($config['empresa_email'] ?? ''); ?>" placeholder="contacto@empresa.com" class="w-full pl-10 pr-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
                            </div>
                        </div>
                        <div>
                            <label class="text-xs dark:text-white/50 text-gray-500 mb-1.5 block font-medium"><?php echo __('ajus_telefono'); ?></label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 dark:text-white/20 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <input type="text" name="empresa_telefono" value="<?php echo htmlspecialchars($config['empresa_telefono'] ?? ''); ?>" placeholder="+1 234 567 890" class="w-full pl-10 pr-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
                            </div>
                        </div>
                        <div>
                            <label class="text-xs dark:text-white/50 text-gray-500 mb-1.5 block font-medium"><?php echo __('ajus_direccion'); ?></label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 dark:text-white/20 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <input type="text" name="empresa_direccion" value="<?php echo htmlspecialchars($config['empresa_direccion'] ?? ''); ?>" placeholder="Calle, Ciudad, País" class="w-full pl-10 pr-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right column: Logo -->
            <div class="lg:col-span-1 dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
                <div class="px-5 py-3.5 border-b dark:border-white/[0.06] border-gray-100 flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-600/10 flex items-center justify-center">
                        <svg class="w-4 h-4 dark:text-blue-400 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="font-semibold text-sm"><?php echo __('ajus_logotipo'); ?></h3>
                </div>
                <div class="p-5 flex flex-col items-center text-center">
                    <div class="w-24 h-24 rounded-2xl dark:bg-white/5 bg-gray-50 border-2 border-dashed dark:border-white/10 border-gray-200 flex items-center justify-center mb-3 overflow-hidden">
                        <?php if (!empty($config['logo_url']) && file_exists(__DIR__ . '/' . $config['logo_url'])): ?>
                        <img src="<?php echo htmlspecialchars($config['logo_url']); ?>" class="w-full h-full object-contain p-2">
                        <?php else: ?>
                        <svg class="w-10 h-10 dark:text-white/10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <?php endif; ?>
                    </div>
                    <p class="text-[11px] dark:text-white/30 text-gray-400 mb-3">PNG, JPG, SVG o WebP</p>
                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors border dark:border-white/10 border-gray-200">
                        <svg class="w-3.5 h-3.5 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        <?php echo __('ajus_subir_logo'); ?>
                        <input type="file" name="logo_file" accept="image/*" class="hidden">
                    </label>
                    <input type="hidden" name="logo_url" value="<?php echo htmlspecialchars($config['logo_url'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- Color & Appearance -->
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
            <div class="px-5 py-3.5 border-b dark:border-white/[0.06] border-gray-100 flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-amber-600/10 flex items-center justify-center">
                    <svg class="w-4 h-4 dark:text-amber-400 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                </div>
                <h3 class="font-semibold text-sm"><?php echo __('ajus_marca'); ?></h3>
            </div>
            <div class="p-5">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                    <div>
                        <label class="text-xs dark:text-white/50 text-gray-500 mb-1.5 block font-medium"><?php echo __('ajus_color_primario'); ?></label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="color_primario" x-model="colorVal" :value="colorVal" class="w-10 h-10 rounded-xl cursor-pointer border dark:border-white/10 border-gray-200 bg-transparent p-0.5">
                            <input type="text" x-model="colorVal" readonly class="w-24 px-3 py-2 text-xs rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none font-mono tracking-wider">
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-1">
                        <p class="text-xs dark:text-white/30 text-gray-400"><?php echo __('ajus_vista_previa'); ?></p>
                        <div class="flex items-center gap-1.5">
                            <div class="w-8 h-8 rounded-lg" :style="'background:' + colorVal"></div>
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-[10px] font-bold" :style="'background:' + colorVal">Aa</div>
                            <div class="px-3 py-1.5 rounded-lg text-white text-[10px] font-medium" :style="'background:' + colorVal">Botón</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Language -->
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
            <div class="px-5 py-3.5 border-b dark:border-white/[0.06] border-gray-100 flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-600/10 flex items-center justify-center">
                    <svg class="w-4 h-4 dark:text-emerald-400 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                </div>
                <h3 class="font-semibold text-sm"><?php echo __('ajus_idioma'); ?></h3>
            </div>
            <div class="p-5">
                <p class="text-xs dark:text-white/40 text-gray-400 mb-3"><?php echo __('ajus_idioma_desc'); ?></p>
                <div class="flex flex-wrap gap-3" x-data="{ lang: '<?php echo htmlspecialchars($config['idioma'] ?? $_idioma ?? 'es'); ?>' }">
                    <input type="hidden" name="idioma" :value="lang">
                    <?php
                    $langs = [
                        'es' => ['flag'=>'🇪🇸','name'=>'Español'],
                        'pt' => ['flag'=>'🇧🇷','name'=>'Português'],
                        'en' => ['flag'=>'🇺🇸','name'=>'English'],
                    ];
                    foreach ($langs as $code => $info): ?>
                    <button type="button" @click="lang = '<?php echo $code; ?>'"
                        :class="lang === '<?php echo $code; ?>' ? 'border-nexo-500 dark:bg-nexo-600/10 bg-nexo-50 ring-1 ring-nexo-500/30' : 'dark:border-white/10 border-gray-200 dark:hover:border-white/20 hover:border-gray-300'"
                        class="flex items-center gap-2.5 px-4 py-3 rounded-xl border transition-all text-sm font-medium">
                        <span class="text-xl"><?php echo $info['flag']; ?></span>
                        <span><?php echo $info['name']; ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Save button -->
        <div class="flex items-center justify-between pt-1">
            <p class="text-[11px] dark:text-white/20 text-gray-300"><?php echo __('ajus_cambios_nota'); ?></p>
            <button type="submit" class="btn-purple px-6 py-2.5 rounded-xl text-sm font-medium text-white inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <?php echo __('ajus_guardar'); ?>
            </button>
        </div>
    </form>
</div>
</main>
<?php include 'includes/footer.php'; ?>
