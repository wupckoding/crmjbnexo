<?php
require_once 'includes/auth_check.php';
require_once 'includes/helpers.php';
$pageTitle = 'Usuarios';
$currentPage = 'usuarios';

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$usuarios = $pdo->query("
    SELECT u.id, u.nombre, u.email, u.rol, u.activo, u.avatar, u.ultimo_acceso, u.creado_en,
        (SELECT COUNT(*) FROM clientes c WHERE c.asignado_a = u.id) as total_clientes, 
        (SELECT COUNT(*) FROM facturas f WHERE f.usuario_id = u.id) as total_facturas,
        (SELECT COALESCE(SUM(f.total),0) FROM facturas f WHERE f.usuario_id = u.id AND f.estado = 'pagada') as ingresos_generados
    FROM usuarios u ORDER BY u.creado_en DESC
")->fetchAll();

$totalUsers = count($usuarios);
$totalActive = count(array_filter($usuarios, fn($u) => $u['activo']));
$totalAdmins = count(array_filter($usuarios, fn($u) => $u['rol'] === 'admin'));
$totalOnline = count(array_filter($usuarios, fn($u) => $u['ultimo_acceso'] && strtotime($u['ultimo_acceso']) > strtotime('-15 minutes')));
$roles = array_unique(array_column($usuarios, 'rol'));

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>
<div class="p-4 sm:p-6 space-y-4" x-data="usersApp()">

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs dark:text-white/40 text-gray-400">Total</span>
                <div class="w-8 h-8 rounded-lg bg-nexo-600/15 flex items-center justify-center">
                    <svg class="w-4 h-4 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold"><?php echo $totalUsers; ?></p>
        </div>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs dark:text-white/40 text-gray-400">Activos</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/15 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold dark:text-emerald-400 text-emerald-600"><?php echo $totalActive; ?></p>
        </div>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs dark:text-white/40 text-gray-400">Administradores</span>
                <div class="w-8 h-8 rounded-lg bg-amber-500/15 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold dark:text-amber-400 text-amber-600"><?php echo $totalAdmins; ?></p>
        </div>
        <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs dark:text-white/40 text-gray-400">En línea</span>
                <div class="w-8 h-8 rounded-lg bg-blue-500/15 flex items-center justify-center">
                    <div class="w-3 h-3 rounded-full bg-blue-400 animate-pulse"></div>
                </div>
            </div>
            <p class="text-2xl font-bold dark:text-blue-400 text-blue-600"><?php echo $totalOnline; ?></p>
        </div>
    </div>

    <!-- Toolbar: Search + Filters + Actions -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
        <div class="relative flex-1 w-full sm:max-w-xs">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 dark:text-white/30 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" x-model="search" placeholder="Buscar usuario..." class="w-full pl-9 pr-3 py-2 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50">
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <select x-model="filterRol" class="px-3 py-2 text-xs rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none">
                <option value="">Todos los roles</option>
                <?php foreach ($roles as $r): ?>
                <option value="<?php echo $r; ?>"><?php echo ucfirst($r); ?></option>
                <?php endforeach; ?>
            </select>
            <select x-model="filterStatus" class="px-3 py-2 text-xs rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none">
                <option value="">Todos</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
            </select>
            <button @click="viewMode = viewMode === 'grid' ? 'table' : 'grid'" class="w-9 h-9 flex items-center justify-center rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 dark:hover:bg-white/10 hover:bg-gray-100 transition-colors">
                <svg x-show="viewMode === 'grid'" class="w-4 h-4 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                <svg x-show="viewMode === 'table'" class="w-4 h-4 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            </button>
            <button @click="exportCSV()" class="px-3 py-2 text-xs rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 dark:hover:bg-white/10 hover:bg-gray-100 transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                CSV
            </button>
            <button @click="showModal = true" class="btn-purple px-4 py-2 rounded-xl text-xs font-medium text-white flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuevo
            </button>
        </div>
    </div>

    <p class="text-xs dark:text-white/30 text-gray-400" x-text="filtered().length + ' de ' + users.length + ' usuarios'"></p>

    <!-- GRID VIEW -->
    <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <template x-for="u in filtered()" :key="u.id">
            <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5 transition-all hover:shadow-lg hover:dark:border-white/[0.12] hover:border-gray-300">
                <div class="flex items-start justify-between mb-3">
                    <div class="relative">
                        <template x-if="u.avatar">
                            <img :src="'uploads/avatars/'+u.avatar" class="w-14 h-14 rounded-full object-cover">
                        </template>
                        <template x-if="!u.avatar">
                            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-nexo-500 to-purple-600 flex items-center justify-center text-white text-lg font-bold" x-text="u.nombre[0]?.toUpperCase()"></div>
                        </template>
                        <div class="absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full border-2 dark:border-dark-800 border-white" :class="u.activo ? 'bg-emerald-400' : 'bg-gray-400'"></div>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] font-medium px-2 py-0.5 rounded-full" :class="u.rol === 'admin' ? 'bg-nexo-500/20 text-nexo-400' : u.rol === 'gerente' ? 'bg-blue-500/20 text-blue-400' : 'bg-emerald-500/20 text-emerald-400'" x-text="u.rol.charAt(0).toUpperCase() + u.rol.slice(1)"></span>
                    </div>
                </div>
                <h3 class="font-semibold text-sm" x-text="u.nombre"></h3>
                <p class="text-xs dark:text-white/40 text-gray-400 truncate" x-text="u.email"></p>


                <!-- Stats row -->
                <div class="grid grid-cols-3 gap-2 mt-3 pt-3 border-t dark:border-white/[0.06] border-gray-100">
                    <div class="text-center">
                        <p class="text-sm font-bold" x-text="u.total_clientes"></p>
                        <p class="text-[9px] dark:text-white/30 text-gray-400">Clientes</p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm font-bold" x-text="u.total_facturas"></p>
                        <p class="text-[9px] dark:text-white/30 text-gray-400">Facturas</p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm font-bold dark:text-emerald-400 text-emerald-600" x-text="'$' + Number(u.ingresos_generados).toLocaleString()"></p>
                        <p class="text-[9px] dark:text-white/30 text-gray-400">Ingresos</p>
                    </div>
                </div>

                <!-- Last access -->
                <div class="flex items-center gap-1.5 mt-2">
                    <div class="w-1.5 h-1.5 rounded-full" :class="isOnline(u.ultimo_acceso) ? 'bg-emerald-400' : 'bg-gray-500'"></div>
                    <span class="text-[10px] dark:text-white/25 text-gray-300" x-text="isOnline(u.ultimo_acceso) ? 'En línea' : (u.ultimo_acceso ? 'Hace ' + timeAgo(u.ultimo_acceso) : 'Nunca')"></span>
                </div>

                <!-- Actions -->
                <div class="flex gap-2 mt-3">
                    <a :href="'perfil.php?id='+u.id" class="flex-1 px-3 py-1.5 text-xs rounded-lg dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 text-center transition-colors font-medium">Ver</a>
                    <button @click="openEdit(u)" class="flex-1 px-3 py-1.5 text-xs rounded-lg bg-nexo-600/10 text-nexo-400 hover:bg-nexo-600/20 transition-colors font-medium">Editar</button>
                    <button @click="confirmToggle(u)" class="px-3 py-1.5 text-xs rounded-lg transition-colors font-medium" :class="u.activo ? 'bg-red-500/10 text-red-400 hover:bg-red-500/20' : 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20'" x-text="u.activo ? 'Des.' : 'Act.'"></button>
                    <button x-show="u.id != <?php echo $_SESSION['user_id']; ?>" @click="confirmDelete(u)" class="px-3 py-1.5 text-xs rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-colors font-medium" title="Eliminar">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- TABLE VIEW -->
    <div x-show="viewMode === 'table'" class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead><tr class="border-b dark:border-white/[0.06] border-gray-100">
                <th class="text-left px-4 py-3 text-xs font-medium dark:text-white/40 text-gray-400">Usuario</th>
                <th class="text-left px-4 py-3 text-xs font-medium dark:text-white/40 text-gray-400">Rol</th>
                <th class="text-center px-4 py-3 text-xs font-medium dark:text-white/40 text-gray-400">Clientes</th>
                <th class="text-center px-4 py-3 text-xs font-medium dark:text-white/40 text-gray-400">Facturas</th>
                <th class="text-right px-4 py-3 text-xs font-medium dark:text-white/40 text-gray-400">Ingresos</th>
                <th class="text-center px-4 py-3 text-xs font-medium dark:text-white/40 text-gray-400">Estado</th>
                <th class="text-left px-4 py-3 text-xs font-medium dark:text-white/40 text-gray-400">Último acceso</th>
                <th class="text-center px-4 py-3 text-xs font-medium dark:text-white/40 text-gray-400">Acciones</th>
            </tr></thead>
            <tbody>
            <template x-for="u in filtered()" :key="u.id">
            <tr class="border-b dark:border-white/[0.04] border-gray-50 hover:dark:bg-white/[0.02] hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="relative shrink-0">
                            <template x-if="u.avatar">
                                <img :src="'uploads/avatars/'+u.avatar" class="w-8 h-8 rounded-full object-cover">
                            </template>
                            <template x-if="!u.avatar">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-nexo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold" x-text="u.nombre[0]?.toUpperCase()"></div>
                            </template>
                            <div class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 dark:border-dark-800 border-white" :class="u.activo ? 'bg-emerald-400' : 'bg-gray-400'"></div>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-sm truncate" x-text="u.nombre"></p>
                            <p class="text-xs dark:text-white/40 text-gray-400 truncate" x-text="u.email"></p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-full" :class="u.rol === 'admin' ? 'bg-nexo-500/20 text-nexo-400' : u.rol === 'gerente' ? 'bg-blue-500/20 text-blue-400' : 'bg-emerald-500/20 text-emerald-400'" x-text="u.rol.charAt(0).toUpperCase() + u.rol.slice(1)"></span>
                </td>
                <td class="px-4 py-3 text-center font-medium" x-text="u.total_clientes"></td>
                <td class="px-4 py-3 text-center font-medium" x-text="u.total_facturas"></td>
                <td class="px-4 py-3 text-right font-medium dark:text-emerald-400 text-emerald-600" x-text="'$' + Number(u.ingresos_generados).toLocaleString()"></td>
                <td class="px-4 py-3 text-center">
                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-full" :class="u.activo ? 'bg-emerald-500/15 text-emerald-400' : 'bg-red-500/15 text-red-400'" x-text="u.activo ? 'Activo' : 'Inactivo'"></span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-1.5">
                        <div class="w-1.5 h-1.5 rounded-full" :class="isOnline(u.ultimo_acceso) ? 'bg-emerald-400' : 'bg-gray-500'"></div>
                        <span class="text-xs dark:text-white/40 text-gray-400" x-text="isOnline(u.ultimo_acceso) ? 'En línea' : (u.ultimo_acceso ? timeAgo(u.ultimo_acceso) : 'Nunca')"></span>
                    </div>
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <a :href="'perfil.php?id='+u.id" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100 transition-colors">
                            <svg class="w-3.5 h-3.5 dark:text-white/40 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </a>
                        <button @click="openEdit(u)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100 transition-colors">
                            <svg class="w-3.5 h-3.5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button @click="confirmToggle(u)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100 transition-colors">
                            <svg class="w-3.5 h-3.5" :class="u.activo ? 'text-red-400' : 'text-emerald-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </button>
                        <button x-show="u.id != <?php echo $_SESSION['user_id']; ?>" @click="confirmDelete(u)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/10 hover:bg-gray-100 transition-colors" title="Eliminar">
                            <svg class="w-3.5 h-3.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
            </template>
            </tbody>
        </table>
    </div>

    <!-- Create Modal -->
    <div x-show="showModal" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showModal = false" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-md dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl p-6">
            <h3 class="text-lg font-bold mb-4">Nuevo Usuario</h3>
            <form method="POST" action="api/usuarios.php" class="space-y-3">
                <input type="hidden" name="action" value="create">
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2"><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block">Nombre *</label>
                        <input type="text" name="nombre" required class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                    <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block">Email *</label>
                        <input type="email" name="email" required class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>

                    <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block">Contraseña *</label>
                        <input type="password" name="password" required minlength="6" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                    <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block">Rol</label>
                        <select name="rol" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none">
                            <option value="vendedor">Vendedor</option><option value="gerente">Gerente</option><option value="admin">Administrador</option><option value="soporte">Soporte</option></select></div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">Cancelar</button>
                    <button type="submit" class="flex-1 btn-purple px-4 py-2.5 rounded-xl text-sm font-medium text-white">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="showEdit" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showEdit = false" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-md dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl p-6">
            <h3 class="text-lg font-bold mb-4">Editar Usuario</h3>
            <form @submit.prevent="saveEdit()" class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2"><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block">Nombre *</label>
                        <input type="text" x-model="editUser.nombre" required class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                    <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block">Email *</label>
                        <input type="email" x-model="editUser.email" required class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>

                    <div><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block">Rol</label>
                        <select x-model="editUser.rol" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none">
                            <option value="vendedor">Vendedor</option><option value="gerente">Gerente</option><option value="admin">Administrador</option><option value="soporte">Soporte</option></select></div>
                    <div class="col-span-2"><label class="text-xs dark:text-white/50 text-gray-500 mb-1 block">Nueva Contraseña</label>
                        <input type="password" x-model="editUser.password" placeholder="Dejar vacío para no cambiar" minlength="6" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50"></div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showEdit = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">Cancelar</button>
                    <button type="submit" class="flex-1 btn-purple px-4 py-2.5 rounded-xl text-sm font-medium text-white" :disabled="saving" x-text="saving ? 'Guardando...' : 'Guardar'"></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirm Toggle Modal -->
    <div x-show="showConfirm" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showConfirm = false" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-sm dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl p-6 text-center">
            <div class="w-14 h-14 rounded-full mx-auto mb-4 flex items-center justify-center" :class="confirmAction.activo ? 'bg-red-500/15' : 'bg-emerald-500/15'">
                <svg class="w-7 h-7" :class="confirmAction.activo ? 'text-red-400' : 'text-emerald-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </div>
            <h3 class="font-bold mb-1" x-text="confirmAction.activo ? '¿Desactivar usuario?' : '¿Activar usuario?'"></h3>
            <p class="text-sm dark:text-white/50 text-gray-500 mb-4" x-text="confirmAction.nombre + ' (' + confirmAction.email + ')'"></p>
            <div class="flex gap-3">
                <button @click="showConfirm = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">Cancelar</button>
                <button @click="doToggle()" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-white transition-colors" :class="confirmAction.activo ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700'" x-text="confirmAction.activo ? 'Desactivar' : 'Activar'"></button>
            </div>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div x-show="showDeleteConfirm" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showDeleteConfirm = false" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-sm dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl p-6 text-center">
            <div class="w-14 h-14 rounded-full mx-auto mb-4 flex items-center justify-center bg-red-500/15">
                <svg class="w-7 h-7 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="font-bold mb-1">¿Eliminar usuario permanentemente?</h3>
            <p class="text-sm dark:text-white/50 text-gray-500 mb-1" x-text="deleteTarget.nombre + ' (' + deleteTarget.email + ')'"></p>
            <p class="text-xs text-red-400 mb-4">Esta acción no se puede deshacer. Sus clientes serán desvinculados.</p>
            <div class="flex gap-3">
                <button @click="showDeleteConfirm = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors">Cancelar</button>
                <button @click="doDelete()" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors">Eliminar</button>
            </div>
        </div>
    </div>
</div>
</main>

<script>
function usersApp() {
    return {
        users: <?php echo json_encode($usuarios); ?>,
        search: '',
        filterRol: '',
        filterStatus: '',
        viewMode: 'grid',
        showModal: false,
        showEdit: false,
        showConfirm: false,
        showDeleteConfirm: false,
        saving: false,
        editUser: { id:0, nombre:'', email:'', rol:'', password:'' },
        confirmAction: { id:0, nombre:'', email:'', activo:0 },
        deleteTarget: { id:0, nombre:'', email:'' },

        filtered() {
            return this.users.filter(u => {
                const s = this.search.toLowerCase();
                if (s && !u.nombre.toLowerCase().includes(s) && !u.email.toLowerCase().includes(s)) return false;
                if (this.filterRol && u.rol !== this.filterRol) return false;
                if (this.filterStatus !== '' && String(u.activo) !== this.filterStatus) return false;
                return true;
            });
        },

        isOnline(d) {
            if (!d) return false;
            return (Date.now() - new Date(d).getTime()) < 900000;
        },

        timeAgo(d) {
            if (!d) return 'nunca';
            const s = Math.floor((Date.now() - new Date(d).getTime()) / 1000);
            if (s < 60) return s + 's';
            if (s < 3600) return Math.floor(s/60) + 'min';
            if (s < 86400) return Math.floor(s/3600) + 'h';
            const days = Math.floor(s/86400);
            if (days === 1) return '1 día';
            if (days < 30) return days + ' días';
            return Math.floor(days/30) + ' meses';
        },

        openEdit(u) {
            this.editUser = { id: u.id, nombre: u.nombre, email: u.email, rol: u.rol, password: '' };
            this.showEdit = true;
        },

        async saveEdit() {
            this.saving = true;
            const fd = new FormData();
            fd.append('action', 'update');
            fd.append('id', this.editUser.id);
            fd.append('nombre', this.editUser.nombre);
            fd.append('email', this.editUser.email);
            fd.append('rol', this.editUser.rol);
            if (this.editUser.password) fd.append('password', this.editUser.password);
            const r = await fetch('api/usuarios.php', { method: 'POST', body: fd });
            const d = await r.json();
            this.saving = false;
            if (d.ok) {
                const u = this.users.find(x => x.id == this.editUser.id);
                if (u) { u.nombre = this.editUser.nombre; u.email = this.editUser.email; u.rol = this.editUser.rol; }
                this.showEdit = false;
            }
        },

        confirmToggle(u) {
            if (u.id == <?php echo $_SESSION['user_id']; ?>) return;
            this.confirmAction = { id: u.id, nombre: u.nombre, email: u.email, activo: u.activo };
            this.showConfirm = true;
        },

        async doToggle() {
            const fd = new FormData();
            fd.append('action', 'toggle');
            fd.append('id', this.confirmAction.id);
            fd.append('activo', this.confirmAction.activo ? 0 : 1);
            const r = await fetch('api/usuarios.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.ok) {
                const u = this.users.find(x => x.id == this.confirmAction.id);
                if (u) u.activo = this.confirmAction.activo ? 0 : 1;
            }
            this.showConfirm = false;
        },

        confirmDelete(u) {
            if (u.id == <?php echo $_SESSION['user_id']; ?>) return;
            this.deleteTarget = { id: u.id, nombre: u.nombre, email: u.email };
            this.showDeleteConfirm = true;
        },

        async doDelete() {
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', this.deleteTarget.id);
            const r = await fetch('api/usuarios.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.ok) {
                this.users = this.users.filter(u => u.id != this.deleteTarget.id);
            }
            this.showDeleteConfirm = false;
        },

        exportCSV() {
            const rows = [['Nombre','Email','Rol','Clientes','Facturas','Ingresos','Estado','Último Acceso']];
            this.filtered().forEach(u => {
                rows.push([u.nombre, u.email, u.rol, u.total_clientes, u.total_facturas, u.ingresos_generados, u.activo ? 'Activo' : 'Inactivo', u.ultimo_acceso || 'Nunca']);
            });
            const csv = rows.map(r => r.map(c => '"' + String(c).replace(/"/g,'""') + '"').join(',')).join('\n');
            const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'usuarios_' + new Date().toISOString().slice(0,10) + '.csv';
            a.click();
        }
    };
}
</script>
<?php include 'includes/footer.php'; ?>
