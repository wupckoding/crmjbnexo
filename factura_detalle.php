<?php
require_once 'includes/auth_check.php';
$pageTitle = __('fdet_titulo', 'Detalle Factura');
$currentPage = 'facturas';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: facturas.php'); exit; }

$stmt = $pdo->prepare("SELECT f.*, c.nombre as cn, c.email as ce, c.telefono as ct, c.empresa as cem FROM facturas f JOIN clientes c ON f.cliente_id = c.id WHERE f.id = :id");
$stmt->execute(['id'=>$id]);
$factura = $stmt->fetch();
if (!$factura) { header('Location: facturas.php'); exit; }

$items = $pdo->prepare("SELECT fi.*, s.nombre as servicio_nombre FROM factura_items fi LEFT JOIN servicios s ON fi.servicio_id = s.id WHERE fi.factura_id = :f ORDER BY fi.id");
$items->execute(['f'=>$id]);
$items = $items->fetchAll();

$statusColors = ['pagada'=>'bg-emerald-400/10 text-emerald-400 border-emerald-400/20','enviada'=>'bg-blue-400/10 text-blue-400 border-blue-400/20','borrador'=>'bg-gray-400/10 text-gray-400 border-gray-400/20','vencida'=>'bg-red-400/10 text-red-400 border-red-400/20','cancelada'=>'bg-red-400/10 text-red-400 border-red-400/20'];
$sc = $statusColors[$factura['estado']] ?? 'bg-gray-400/10 text-gray-400';

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>
<div class="p-4 sm:p-6 max-w-4xl space-y-4">
    
    <div class="flex items-center gap-3">
        <a href="facturas.php" class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">←</a>
        <h2 class="text-lg font-bold"><?php echo $factura['numero']; ?></h2>
        <span class="text-[10px] font-medium px-2 py-0.5 rounded-full border <?php echo $sc; ?>"><?php echo ucfirst($factura['estado']); ?></span>
        <a href="factura_pdf.php?id=<?php echo $factura['id']; ?>" target="_blank" class="ml-auto flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-nexo-600/20 text-nexo-400 text-xs font-semibold hover:bg-nexo-600/30 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            PDF
        </a>
    </div>

    <!-- Invoice card -->
    <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-6" id="invoice-content">
        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-2xl font-bold bg-gradient-to-r from-nexo-400 to-purple-400 bg-clip-text text-transparent">JB NEXO</h1>
                <p class="text-xs dark:text-white/40 text-gray-400 mt-1">Soluciones Digitales</p>
            </div>
            <div class="text-right">
                <p class="text-sm font-mono dark:text-white/60 text-gray-500"><?php echo $factura['numero']; ?></p>
                <p class="text-xs dark:text-white/40 text-gray-400"><?php echo __('fdet_emitida', 'Emitida:'); ?> <?php echo date('d/m/Y', strtotime($factura['fecha_emision'])); ?></p>
                <?php if ($factura['fecha_vencimiento']): ?>
                <p class="text-xs dark:text-white/40 text-gray-400"><?php echo __('fdet_vence', 'Vence:'); ?> <?php echo date('d/m/Y', strtotime($factura['fecha_vencimiento'])); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Client info -->
        <div class="mb-6 pb-6 border-b dark:border-white/[0.06] border-gray-200">
            <p class="text-xs dark:text-white/30 text-gray-400 mb-1 uppercase tracking-wider"><?php echo __('fdet_facturar_a', 'Facturar a:'); ?></p>
            <p class="font-semibold"><?php echo htmlspecialchars($factura['cn']); ?></p>
            <?php if ($factura['cem']): ?><p class="text-sm dark:text-white/50 text-gray-500"><?php echo htmlspecialchars($factura['cem']); ?></p><?php endif; ?>
            <p class="text-sm dark:text-white/50 text-gray-500"><?php echo htmlspecialchars($factura['ce']); ?></p>
            <?php if ($factura['ct']): ?><p class="text-sm dark:text-white/50 text-gray-500"><?php echo htmlspecialchars($factura['ct']); ?></p><?php endif; ?>
        </div>

        <!-- Items table -->
        <table class="w-full text-sm mb-6">
            <thead><tr class="border-b dark:border-white/[0.06] border-gray-200"><th class="pb-2 text-left text-xs dark:text-white/40 text-gray-400 font-medium"><?php echo __('fdet_descripcion', 'Descripción'); ?></th><th class="pb-2 text-center text-xs dark:text-white/40 text-gray-400 font-medium w-20"><?php echo __('fdet_cant', 'Cant.'); ?></th><th class="pb-2 text-right text-xs dark:text-white/40 text-gray-400 font-medium w-28"><?php echo __('fdet_precio', 'Precio'); ?></th><th class="pb-2 text-right text-xs dark:text-white/40 text-gray-400 font-medium w-28"><?php echo __('fdet_total', 'Total'); ?></th></tr></thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                <tr class="border-b dark:border-white/[0.04] border-gray-50">
                    <td class="py-3"><?php echo htmlspecialchars($it['descripcion']); ?><?php echo $it['servicio_nombre'] ? '<br><span class="text-xs dark:text-white/30 text-gray-400">' . htmlspecialchars($it['servicio_nombre']) . '</span>' : ''; ?></td>
                    <td class="py-3 text-center dark:text-white/60 text-gray-500"><?php echo $it['cantidad']; ?></td>
                    <td class="py-3 text-right dark:text-white/60 text-gray-500">$<?php echo number_format($it['precio_unitario'], 2, ',', '.'); ?></td>
                    <td class="py-3 text-right font-medium">$<?php echo number_format($it['total'], 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="flex justify-end">
            <div class="w-64 space-y-2">
                <div class="flex justify-between text-sm"><span class="dark:text-white/50 text-gray-400"><?php echo __('fdet_subtotal', 'Subtotal'); ?></span><span>$<?php echo number_format($factura['subtotal'], 2, ',', '.'); ?></span></div>
                <?php if (($factura['impuesto_monto'] ?? 0) > 0): ?>
                <div class="flex justify-between text-sm"><span class="dark:text-white/50 text-gray-400"><?php echo __('fdet_impuesto', 'Impuesto'); ?> (<?php echo $factura['impuesto_porcentaje']; ?>%)</span><span>$<?php echo number_format($factura['impuesto_monto'], 2, ',', '.'); ?></span></div>
                <?php endif; ?>
                <?php if ($factura['descuento'] > 0): ?>
                <div class="flex justify-between text-sm"><span class="dark:text-white/50 text-gray-400"><?php echo __('fdet_descuento', 'Descuento'); ?></span><span class="text-red-400">-$<?php echo number_format($factura['descuento'], 2, ',', '.'); ?></span></div>
                <?php endif; ?>
                <div class="flex justify-between text-lg font-bold pt-2 border-t dark:border-white/[0.06] border-gray-200"><span><?php echo __('fdet_total', 'Total'); ?></span><span>$<?php echo number_format($factura['total'], 2, ',', '.'); ?></span></div>
            </div>
        </div>

        <?php if ($factura['notas']): ?>
        <div class="mt-6 pt-4 border-t dark:border-white/[0.06] border-gray-200">
            <p class="text-xs dark:text-white/30 text-gray-400 mb-1"><?php echo __('fdet_notas', 'Notas:'); ?></p>
            <p class="text-sm dark:text-white/60 text-gray-500"><?php echo nl2br(htmlspecialchars($factura['notas'])); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Actions -->
    <div class="flex flex-wrap gap-2">
        <?php if ($factura['estado'] === 'borrador'): ?>
        <button onclick="updateStatus(<?php echo $id; ?>, 'enviada')" class="btn-purple px-4 py-2 rounded-xl text-sm font-medium text-white"><?php echo __('fdet_marcar_enviada', 'Marcar como Enviada'); ?></button>
        <?php endif; ?>
        <?php if (in_array($factura['estado'], ['enviada','vencida'])): ?>
        <button onclick="updateStatus(<?php echo $id; ?>, 'pagada')" class="px-4 py-2 rounded-xl text-sm font-medium bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition-colors"><?php echo __('fdet_marcar_pagada', 'Marcar como Pagada'); ?></button>
        <?php endif; ?>
        <?php if ($factura['estado'] !== 'cancelada' && $factura['estado'] !== 'pagada'): ?>
        <button onclick="updateStatus(<?php echo $id; ?>, 'cancelada')" class="px-4 py-2 rounded-xl text-sm font-medium bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-colors"><?php echo __('fdet_cancelar', 'Cancelar'); ?></button>
        <?php endif; ?>
        <button onclick="window.print()" class="px-4 py-2 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors"><?php echo __('fdet_imprimir', 'Imprimir / PDF'); ?></button>
    </div>
</div>
</main>

<script>
async function updateStatus(id, estado) {
    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('id', id);
    fd.append('estado', estado);
    const r = await fetch('api/facturas.php', { method: 'POST', body: fd });
    if (r.ok) location.reload();
}
</script>

<style>
@media print {
    nav, .topbar, button, .no-print { display: none !important; }
    main { margin: 0; padding: 0; }
    #invoice-content { border: none; box-shadow: none; }
}
</style>
<?php include 'includes/footer.php'; ?>
