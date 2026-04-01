<?php
require_once 'includes/auth_check.php';
if ($_SESSION['user_role'] !== 'admin') { header('Location: dashboard.php'); exit; }

$pageTitle = __('backup_titulo', 'Respaldo de Base de Datos');
$currentPage = 'backup';

// Handle download
if (isset($_GET['download'])) {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $sql = "-- Respaldo CRM JBNEXO\n";
    $sql .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Base de datos: crmjbnexo\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        // CREATE TABLE
        $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $create['Create Table'] . ";\n\n";
        
        // Data
        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            $cols = array_keys($rows[0]);
            $colStr = '`' . implode('`, `', $cols) . '`';
            
            foreach (array_chunk($rows, 100) as $chunk) {
                $sql .= "INSERT INTO `{$table}` ({$colStr}) VALUES\n";
                $vals = [];
                foreach ($chunk as $row) {
                    $escaped = array_map(function($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote($v);
                    }, array_values($row));
                    $vals[] = '(' . implode(', ', $escaped) . ')';
                }
                $sql .= implode(",\n", $vals) . ";\n";
            }
            $sql .= "\n";
        }
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="crmjbnexo_backup_' . date('Y-m-d_His') . '.sql"');
    header('Content-Length: ' . strlen($sql));
    echo $sql;
    exit;
}

// Stats
$tables = $pdo->query("SHOW TABLE STATUS")->fetchAll();
$totalSize = 0;
$totalRows = 0;
foreach ($tables as $t) {
    $totalSize += $t['Data_length'] + $t['Index_length'];
    $totalRows += $t['Rows'];
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>
<div class="p-4 sm:p-6 space-y-5" x-data="{ search: '' }">

    <!-- Header -->
    <div>
        <h2 class="text-xl font-bold"><?php echo __('backup_titulo', 'Respaldo de Base de Datos'); ?></h2>
        <p class="text-xs dark:text-white/40 text-gray-400 mt-0.5"><?php echo __('backup_subtitulo', 'Administra y descarga copias de seguridad del sistema'); ?></p>
    </div>

    <!-- Top: Stats + Download side by side on desktop -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Stats Column -->
        <div class="lg:col-span-1 grid grid-cols-3 lg:grid-cols-1 gap-3">
            <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-nexo-600/10 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold leading-none"><?php echo count($tables); ?></p>
                    <p class="text-[11px] dark:text-white/40 text-gray-400 mt-0.5"><?php echo __('backup_tablas', 'Tablas'); ?></p>
                </div>
            </div>
            <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600/10 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 dark:text-blue-400 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold leading-none"><?php echo number_format($totalRows); ?></p>
                    <p class="text-[11px] dark:text-white/40 text-gray-400 mt-0.5"><?php echo __('backup_registros', 'Registros'); ?></p>
                </div>
            </div>
            <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-600/10 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 dark:text-emerald-400 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2 2 4 4 4h8c2 0 4-2 4-4V7M9 3h6l2 4H7l2-4z"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold leading-none"><?php echo round($totalSize / 1024, 1); ?> KB</p>
                    <p class="text-[11px] dark:text-white/40 text-gray-400 mt-0.5"><?php echo __('backup_tamano_total', 'Tamaño total'); ?></p>
                </div>
            </div>
        </div>

        <!-- Download Card -->
        <div class="lg:col-span-2 dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-6 flex flex-col items-center justify-center text-center">
            <div class="w-14 h-14 rounded-2xl bg-nexo-600/10 flex items-center justify-center mb-4">
                <svg class="w-7 h-7 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/></svg>
            </div>
            <h3 class="font-semibold mb-1"><?php echo __('backup_descargar_completo', 'Descargar Respaldo Completo'); ?></h3>
            <p class="text-sm dark:text-white/40 text-gray-400 mb-5 max-w-sm"><?php echo __('backup_genera_desc', 'Genera un archivo .sql con la estructura y datos de todas las tablas de la base de datos'); ?></p>
            <a href="backup.php?download=1" class="inline-flex items-center gap-2 btn-purple px-6 py-2.5 rounded-xl text-sm font-medium text-white">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <?php echo __('backup_descargar_sql', 'Descargar .SQL'); ?>
            </a>
            <p class="text-[10px] dark:text-white/25 text-gray-300 mt-3"><?php echo __('backup_ultimo', 'Último respaldo disponible al instante'); ?></p>
        </div>
    </div>

    <!-- Table details -->
    <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b dark:border-white/[0.06] border-gray-100 flex items-center justify-between gap-3">
            <h3 class="font-semibold text-sm"><?php echo __('backup_detalle', 'Detalle de Tablas'); ?></h3>
            <div class="relative">
                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" placeholder="<?php echo __('backup_filtrar', 'Filtrar tabla...'); ?>" class="w-40 pl-8 pr-3 py-1.5 text-xs rounded-lg dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b dark:border-white/[0.06] border-gray-100">
                    <th class="text-left px-5 py-2.5 text-xs font-medium dark:text-white/40 text-gray-400 w-12">#</th>
                    <th class="text-left px-5 py-2.5 text-xs font-medium dark:text-white/40 text-gray-400"><?php echo __('backup_tabla', 'Tabla'); ?></th>
                    <th class="text-right px-5 py-2.5 text-xs font-medium dark:text-white/40 text-gray-400"><?php echo __('backup_registros', 'Registros'); ?></th>
                    <th class="text-right px-5 py-2.5 text-xs font-medium dark:text-white/40 text-gray-400"><?php echo __('backup_tamano', 'Tamaño'); ?></th>
                    <th class="text-right px-5 py-2.5 text-xs font-medium dark:text-white/40 text-gray-400 hidden sm:table-cell"><?php echo __('backup_motor', 'Motor'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($tables as $i => $t): ?>
                <tr class="border-b dark:border-white/[0.04] border-gray-50 hover:dark:bg-white/[0.02] hover:bg-gray-50 transition-colors"
                    x-show="!search || '<?php echo strtolower($t['Name']); ?>'.includes(search.toLowerCase())">
                    <td class="px-5 py-2.5 text-xs dark:text-white/25 text-gray-300 tabular-nums"><?php echo $i + 1; ?></td>
                    <td class="px-5 py-2.5">
                        <span class="font-mono text-xs"><?php echo htmlspecialchars($t['Name']); ?></span>
                    </td>
                    <td class="px-5 py-2.5 text-right tabular-nums">
                        <?php if ($t['Rows'] > 0): ?>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                <span class="dark:text-white/70 text-gray-600 font-medium"><?php echo number_format($t['Rows']); ?></span>
                            </span>
                        <?php else: ?>
                            <span class="dark:text-white/25 text-gray-300">0</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-2.5 text-right dark:text-white/50 text-gray-500 tabular-nums text-xs"><?php echo round(($t['Data_length'] + $t['Index_length']) / 1024, 1); ?> KB</td>
                    <td class="px-5 py-2.5 text-right dark:text-white/30 text-gray-400 text-xs hidden sm:table-cell"><?php echo $t['Engine']; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</main>
<?php include 'includes/footer.php'; ?>
