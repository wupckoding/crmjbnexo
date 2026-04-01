<?php
require_once 'includes/auth_check.php';
$pageTitle = __('cdet_titulo', 'Detalle Cliente');
$currentPage = 'clientes';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: clientes.php'); exit; }

$isAdmin = ($_SESSION['usuario_rol'] ?? '') === 'admin';
$userId  = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("SELECT c.*, u.nombre as vendedor, u.avatar as vendedor_avatar FROM clientes c LEFT JOIN usuarios u ON c.asignado_a = u.id WHERE c.id = :id");
$stmt->execute(['id'=>$id]);
$cliente = $stmt->fetch();
if (!$cliente) { header('Location: clientes.php'); exit; }

// Permission check: only admin or assigned user can view
if (!$isAdmin && (int)$cliente['asignado_a'] !== (int)$userId) {
    header('Location: clientes.php');
    exit;
}

// Invoices
$facturas = $pdo->prepare("SELECT * FROM facturas WHERE cliente_id = :c ORDER BY creado_en DESC");
$facturas->execute(['c'=>$id]);
$facturas = $facturas->fetchAll();

// Events
$eventos = $pdo->prepare("SELECT * FROM eventos WHERE cliente_id = :c ORDER BY fecha_inicio DESC LIMIT 5");
$eventos->execute(['c'=>$id]);
$eventos = $eventos->fetchAll();

// Interactions
$interacciones = $pdo->prepare("SELECT i.*, u.nombre as un FROM interacciones i LEFT JOIN usuarios u ON i.usuario_id = u.id WHERE i.cliente_id = :c ORDER BY i.creado_en DESC LIMIT 10");
$interacciones->execute(['c'=>$id]);
$interacciones = $interacciones->fetchAll();

$totalFacturado = array_sum(array_column($facturas, 'total'));
$totalPagado = array_sum(array_map(function($f) { return $f['estado'] === 'pagada' ? $f['total'] : 0; }, $facturas));

$statusColors = ['nuevo'=>'bg-blue-400/10 text-blue-400','contactado'=>'bg-amber-400/10 text-amber-400','negociando'=>'bg-purple-400/10 text-purple-400','ganado'=>'bg-emerald-400/10 text-emerald-400','perdido'=>'bg-red-400/10 text-red-400'];
$sc = $statusColors[$cliente['estado']] ?? 'bg-gray-400/10 text-gray-400';

$initial = mb_strtoupper(mb_substr($cliente['nombre'], 0, 1));

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>
<div class="p-4 sm:p-6 space-y-4">
    
    <div class="flex items-center gap-3">
        <a href="clientes.php" class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">←</a>
        <h2 class="text-lg font-bold"><?php echo htmlspecialchars($cliente['nombre']); ?></h2>
        <span class="text-[10px] font-medium px-2 py-0.5 rounded-full border <?php echo $sc; ?>"><?php echo ucfirst($cliente['estado']); ?></span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Client info -->
        <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5" x-data="{ uploading: false }">
            <!-- Photo section -->
            <div class="flex flex-col items-center mb-5">
                <div class="relative group mb-3">
                    <?php if ($cliente['foto']): ?>
                    <img src="uploads/clientes/<?php echo htmlspecialchars($cliente['foto']); ?>" class="w-20 h-20 rounded-2xl object-cover border-2 dark:border-white/10 border-gray-200" id="clientPhoto">
                    <?php else: ?>
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-nexo-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold border-2 dark:border-white/10 border-gray-200" id="clientPhoto"><?php echo $initial; ?></div>
                    <?php endif; ?>
                    <!-- Upload overlay -->
                    <label class="absolute inset-0 flex items-center justify-center rounded-2xl bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <input type="file" accept="image/*" class="hidden" @change="
                            uploading = true;
                            const fd = new FormData();
                            fd.append('action', 'upload_foto');
                            fd.append('id', <?php echo $id; ?>);
                            fd.append('foto', $event.target.files[0]);
                            fetch('api/clientes.php', { method: 'POST', body: fd })
                                .then(r => r.json())
                                .then(d => { if (d.ok) location.reload(); })
                                .finally(() => uploading = false);
                        ">
                    </label>
                    <div x-show="uploading" class="absolute inset-0 flex items-center justify-center rounded-2xl bg-black/60">
                        <svg class="w-5 h-5 text-white animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </div>
                </div>
                <p class="font-semibold text-base"><?php echo htmlspecialchars($cliente['nombre']); ?></p>
                <?php if ($cliente['empresa']): ?><p class="text-xs dark:text-white/40 text-gray-400"><?php echo htmlspecialchars($cliente['empresa']); ?></p><?php endif; ?>
            </div>

            <div class="space-y-3 text-sm">
                <?php if ($cliente['email']): ?><div class="flex items-center gap-2.5"><div class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-50 flex items-center justify-center shrink-0"><svg class="w-4 h-4 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div><span class="dark:text-white/60 text-gray-500 truncate"><?php echo htmlspecialchars($cliente['email']); ?></span></div><?php endif; ?>
                <?php if ($cliente['telefono']): ?><div class="flex items-center gap-2.5"><div class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-50 flex items-center justify-center shrink-0"><svg class="w-4 h-4 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg></div><span class="dark:text-white/60 text-gray-500"><?php echo htmlspecialchars($cliente['telefono']); ?></span></div><?php endif; ?>
                <?php if ($cliente['sitio_web']): ?><div class="flex items-center gap-2.5"><div class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-50 flex items-center justify-center shrink-0"><svg class="w-4 h-4 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg></div><a href="<?php echo htmlspecialchars($cliente['sitio_web']); ?>" target="_blank" class="dark:text-nexo-400 text-nexo-600 hover:underline truncate"><?php echo htmlspecialchars($cliente['sitio_web']); ?></a></div><?php endif; ?>
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-50 flex items-center justify-center shrink-0"><svg class="w-4 h-4 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div>
                    <div class="flex items-center gap-1.5">
                        <?php if ($cliente['vendedor_avatar']): ?>
                        <img src="uploads/avatars/<?php echo htmlspecialchars($cliente['vendedor_avatar']); ?>" class="w-5 h-5 rounded-full object-cover">
                        <?php endif; ?>
                        <span class="dark:text-white/60 text-gray-500"><?php echo htmlspecialchars($cliente['vendedor'] ?? '—'); ?></span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5"><div class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-50 flex items-center justify-center shrink-0"><svg class="w-4 h-4 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div><span class="dark:text-white/60 text-gray-500"><?php echo __('cdet_desde', 'Desde:'); ?> <?php echo date('d/m/Y', strtotime($cliente['creado_en'])); ?></span></div>
            </div>

            <?php if ($cliente['notas']): ?>
            <div class="mt-4 pt-4 border-t dark:border-white/[0.06] border-gray-100">
                <p class="text-xs font-medium dark:text-white/40 text-gray-400 mb-1"><?php echo __('cdet_notas', 'Notas'); ?></p>
                <p class="text-sm dark:text-white/60 text-gray-500"><?php echo nl2br(htmlspecialchars($cliente['notas'])); ?></p>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 gap-3 mt-4 pt-4 border-t dark:border-white/[0.06] border-gray-100">
                <div class="text-center"><p class="text-lg font-bold">$<?php echo number_format($totalFacturado, 0, ',', '.'); ?></p><p class="text-[10px] dark:text-white/30 text-gray-400"><?php echo __('cdet_facturado', 'Facturado'); ?></p></div>
                <div class="text-center"><p class="text-lg font-bold text-emerald-400">$<?php echo number_format($totalPagado, 0, ',', '.'); ?></p><p class="text-[10px] dark:text-white/30 text-gray-400"><?php echo __('cdet_pagado', 'Pagado'); ?></p></div>
            </div>
        </div>

        <!-- Invoices -->
        <div class="lg:col-span-2 space-y-4">
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
                <h3 class="font-semibold text-sm mb-3"><?php echo __('cdet_facturas', 'Facturas'); ?></h3>
                <?php if (empty($facturas)): ?>
                <p class="text-sm dark:text-white/40 text-gray-400"><?php echo __('cdet_sin_facturas', 'Sin facturas'); ?></p>
                <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($facturas as $f):
                        $fsc = $statusColors[str_replace(['pagada','enviada','borrador','vencida','cancelada'],['ganado','contactado','nuevo','perdido','perdido'], $f['estado'])] ?? 'bg-gray-400/10 text-gray-400';
                    ?>
                    <a href="factura_detalle.php?id=<?php echo $f['id']; ?>" class="flex items-center justify-between py-2 px-3 rounded-xl dark:hover:bg-white/[0.04] hover:bg-gray-50 transition-colors">
                        <div><p class="text-sm font-medium"><?php echo $f['numero']; ?></p><p class="text-xs dark:text-white/40 text-gray-400"><?php echo date('d/m/Y', strtotime($f['fecha_emision'])); ?></p></div>
                        <div class="flex items-center gap-3"><span class="text-sm font-bold">$<?php echo number_format($f['total'], 0, ',', '.'); ?></span><span class="text-[10px] px-2 py-0.5 rounded-full <?php echo $fsc; ?>"><?php echo ucfirst($f['estado']); ?></span></div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Interactions -->
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
                <h3 class="font-semibold text-sm mb-3"><?php echo __('cdet_historial', 'Historial de Interacciones'); ?></h3>
                <?php if (empty($interacciones)): ?>
                <p class="text-sm dark:text-white/40 text-gray-400"><?php echo __('cdet_sin_interacciones', 'Sin interacciones registradas'); ?></p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($interacciones as $int):
                        $typeIcon = ['llamada'=>'M3 5a2 2 0 012-2h3.28','email'=>'M3 8l7.89 5.26','reunion'=>'M8 7V3m8 4V3','whatsapp'=>'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8'];
                    ?>
                    <div class="flex gap-3">
                        <div class="w-2 h-2 rounded-full bg-nexo-500 mt-2 shrink-0"></div>
                        <div>
                            <p class="text-sm"><?php echo htmlspecialchars($int['notas']); ?></p>
                            <p class="text-xs dark:text-white/30 text-gray-400 mt-0.5"><?php echo ucfirst($int['tipo']); ?> · <?php echo $int['un'] ?? ''; ?> · <?php echo date('d/m/Y H:i', strtotime($int['fecha'])); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</main>
<?php include 'includes/footer.php'; ?>
