<?php
require_once 'includes/auth_check.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: facturas.php'); exit; }

$factura = $pdo->prepare("
    SELECT f.*, c.nombre as cliente_nombre, c.email as cliente_email, c.empresa as cliente_empresa, 
           c.telefono as cliente_telefono, c.direccion as cliente_direccion
    FROM facturas f LEFT JOIN clientes c ON f.cliente_id = c.id WHERE f.id = :id
");
$factura->execute(['id' => $id]);
$factura = $factura->fetch();
if (!$factura) { header('Location: facturas.php'); exit; }

$items = $pdo->prepare("SELECT * FROM factura_items WHERE factura_id = :id");
$items->execute(['id' => $id]);
$items = $items->fetchAll();

// Empresa config
$config = [];
$rows = $pdo->query("SELECT clave, valor FROM configuracion_global")->fetchAll();
foreach ($rows as $r) $config[$r['clave']] = $r['valor'];
$empresa = $config['empresa_nombre'] ?? 'NEXO CRM';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura <?php echo htmlspecialchars($factura['numero']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; color: #1a1a2e; background: #fff; }
        .page { max-width: 800px; margin: 0 auto; padding: 40px; }
        
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
        .brand { font-size: 28px; font-weight: 800; color: #7c3aed; letter-spacing: -0.5px; }
        .brand-sub { font-size: 11px; color: #999; margin-top: 2px; }
        .invoice-title { text-align: right; }
        .invoice-title h1 { font-size: 32px; font-weight: 200; color: #333; text-transform: uppercase; letter-spacing: 6px; }
        .invoice-num { font-size: 13px; color: #7c3aed; font-weight: 600; margin-top: 4px; }
        
        .meta { display: flex; justify-content: space-between; margin-bottom: 30px; padding: 20px; background: #f8f7ff; border-radius: 8px; }
        .meta-block h4 { font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: #999; margin-bottom: 6px; }
        .meta-block p { font-size: 13px; color: #333; line-height: 1.6; }
        .meta-block .name { font-weight: 600; font-size: 14px; }
        
        .status { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-pagada { background: #d1fae5; color: #065f46; }
        .status-pendiente { background: #fef3c7; color: #92400e; }
        .status-vencida { background: #fee2e2; color: #991b1b; }
        .status-cancelada { background: #e5e7eb; color: #4b5563; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        thead th { font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: #999; border-bottom: 2px solid #7c3aed; padding: 10px 12px; text-align: left; }
        thead th:last-child, thead th:nth-child(3), thead th:nth-child(4) { text-align: right; }
        tbody td { padding: 12px; font-size: 13px; border-bottom: 1px solid #f0f0f0; }
        tbody td:last-child, tbody td:nth-child(3), tbody td:nth-child(4) { text-align: right; }
        tbody td.desc { font-weight: 500; }
        
        .totals { display: flex; justify-content: flex-end; margin-bottom: 40px; }
        .totals-table { width: 260px; }
        .totals-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; color: #666; }
        .totals-row.total { border-top: 2px solid #7c3aed; padding-top: 10px; margin-top: 6px; font-size: 18px; font-weight: 700; color: #1a1a2e; }
        
        .footer { text-align: center; padding-top: 30px; border-top: 1px solid #f0f0f0; }
        .footer p { font-size: 11px; color: #999; }
        
        .actions { display: flex; gap: 10px; justify-content: center; margin-bottom: 30px; }
        .btn { padding: 10px 24px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: #7c3aed; color: #fff; }
        .btn-secondary { background: #f3f4f6; color: #333; }
        .btn:hover { opacity: 0.9; }
        
        @media print {
            .actions { display: none !important; }
            body { background: #fff; }
            .page { padding: 20px; max-width: 100%; }
        }
    </style>
</head>
<body>
<div class="page">
    <!-- Actions (hidden on print) -->
    <div class="actions">
        <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir / PDF</button>
        <a href="factura_detalle.php?id=<?php echo $factura['id']; ?>" class="btn btn-secondary">← Volver</a>
    </div>

    <!-- Header -->
    <div class="header">
        <div>
            <div class="brand"><?php echo htmlspecialchars($empresa); ?></div>
            <div class="brand-sub">Sistema de Gestión Empresarial</div>
        </div>
        <div class="invoice-title">
            <h1>Factura</h1>
            <div class="invoice-num"><?php echo htmlspecialchars($factura['numero']); ?></div>
        </div>
    </div>

    <!-- Meta -->
    <div class="meta">
        <div class="meta-block">
            <h4>Facturar a</h4>
            <p class="name"><?php echo htmlspecialchars($factura['cliente_nombre']); ?></p>
            <?php if ($factura['cliente_empresa']): ?><p><?php echo htmlspecialchars($factura['cliente_empresa']); ?></p><?php endif; ?>
            <?php if ($factura['cliente_email']): ?><p><?php echo htmlspecialchars($factura['cliente_email']); ?></p><?php endif; ?>
            <?php if ($factura['cliente_direccion']): ?><p><?php echo htmlspecialchars($factura['cliente_direccion']); ?></p><?php endif; ?>
        </div>
        <div class="meta-block" style="text-align: right;">
            <h4>Detalles</h4>
            <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($factura['fecha_emision'])); ?></p>
            <?php if ($factura['fecha_vencimiento']): ?>
            <p><strong>Vencimiento:</strong> <?php echo date('d/m/Y', strtotime($factura['fecha_vencimiento'])); ?></p>
            <?php endif; ?>
            <p style="margin-top: 6px;">
                <?php 
                $estadoClass = 'status-' . $factura['estado'];
                ?>
                <span class="status <?php echo $estadoClass; ?>"><?php echo ucfirst($factura['estado']); ?></span>
            </p>
        </div>
    </div>

    <!-- Items -->
    <table>
        <thead>
            <tr>
                <th style="width: 45%;">Descripción</th>
                <th>Cantidad</th>
                <th>Precio Unit.</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($items) > 0): ?>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="desc"><?php echo htmlspecialchars($item['descripcion']); ?></td>
                    <td style="text-align: center;"><?php echo $item['cantidad']; ?></td>
                    <td>$<?php echo number_format($item['precio_unitario'], 2); ?></td>
                    <td>$<?php echo number_format($item['cantidad'] * $item['precio_unitario'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td class="desc">Servicios según factura</td>
                    <td style="text-align: center;">1</td>
                    <td>$<?php echo number_format($factura['subtotal'], 2); ?></td>
                    <td>$<?php echo number_format($factura['subtotal'], 2); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <div class="totals-table">
            <div class="totals-row"><span>Subtotal</span><span>$<?php echo number_format($factura['subtotal'], 2); ?></span></div>
            <?php if ($factura['impuesto_monto'] > 0): ?>
            <div class="totals-row"><span>Impuesto</span><span>$<?php echo number_format($factura['impuesto_monto'], 2); ?></span></div>
            <?php endif; ?>
            <?php if (($factura['descuento'] ?? 0) > 0): ?>
            <div class="totals-row"><span>Descuento</span><span>-$<?php echo number_format($factura['descuento'], 2); ?></span></div>
            <?php endif; ?>
            <div class="totals-row total"><span>Total</span><span>$<?php echo number_format($factura['total'], 2); ?></span></div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Gracias por su confianza • <?php echo htmlspecialchars($empresa); ?></p>
        <p style="margin-top: 4px;"><?php echo htmlspecialchars($config['empresa_email'] ?? ''); ?></p>
    </div>
</div>
</body>
</html>
