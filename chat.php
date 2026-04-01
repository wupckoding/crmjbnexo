<?php
require_once 'includes/auth_check.php';
$pageTitle = 'Chat';
$currentPage = 'chat';
$uid = $_SESSION['user_id'];

// Get conversations with last message + unread count
$stmt = $pdo->prepare("
    SELECT c.*,
        (SELECT m.contenido FROM mensajes m WHERE m.conversacion_id = c.id ORDER BY m.creado_en DESC LIMIT 1) as ultimo_msg,
        (SELECT m.tipo FROM mensajes m WHERE m.conversacion_id = c.id ORDER BY m.creado_en DESC LIMIT 1) as ultimo_msg_tipo,
        (SELECT m.creado_en FROM mensajes m WHERE m.conversacion_id = c.id ORDER BY m.creado_en DESC LIMIT 1) as ultimo_msg_fecha,
        (SELECT COUNT(*) FROM mensajes m WHERE m.conversacion_id = c.id AND m.leido = 0 AND m.usuario_id != :uid) as no_leidos
    FROM conversaciones c
    JOIN conversacion_participantes cp ON cp.conversacion_id = c.id
    WHERE cp.usuario_id = :uid2
    ORDER BY ultimo_msg_fecha DESC
");
$stmt->execute(['uid'=>$uid, 'uid2'=>$uid]);
$convs = $stmt->fetchAll();

// Enrich conversations with participants
foreach ($convs as &$c) {
    $parts = $pdo->prepare("SELECT u.id, u.nombre, u.avatar FROM conversacion_participantes cp JOIN usuarios u ON u.id = cp.usuario_id WHERE cp.conversacion_id = :cid");
    $parts->execute(['cid'=>$c['id']]);
    $c['participantes'] = $parts->fetchAll();
    $c['display_name'] = null;
    $c['display_avatar'] = null;
    if ($c['tipo'] === 'privada') {
        foreach ($c['participantes'] as $p) {
            if ($p['id'] != $uid) { $c['display_name'] = $p['nombre']; $c['display_avatar'] = $p['avatar']; break; }
        }
    } else {
        $c['display_name'] = $c['nombre'] ?: 'Grupo';
    }
    $c['display_name'] = $c['display_name'] ?? 'Chat';
}
unset($c);

// All active users
$usuarios = $pdo->query("SELECT id, nombre, avatar, email FROM usuarios WHERE activo = 1 ORDER BY nombre")->fetchAll();

// Total unread
$totalUnread = array_sum(array_column($convs, 'no_leidos'));

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-hidden" style="animation:none">
<?php include 'includes/topbar.php'; ?>

<div x-data="chatApp()" x-init="init()" x-cloak>
<div class="flex h-[calc(100vh-4rem)]">

    <!-- ========== LEFT PANEL ========== -->
    <div class="w-80 xl:w-96 shrink-0 dark:bg-dark-900 bg-white border-r dark:border-white/[0.06] border-gray-200 flex flex-col"
         :class="convActiva !== null ? 'hidden lg:flex' : 'flex w-full lg:w-80 xl:w-96'">

        <!-- Header -->
        <div class="px-4 py-3 border-b dark:border-white/[0.06] border-gray-200">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-nexo-500/10 border border-nexo-500/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-sm font-bold dark:text-white text-gray-900">Mensajes</h1>
                        <p class="text-[10px] dark:text-white/30 text-gray-400"><?php echo $totalUnread; ?> sin leer</p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button @click="showNewGroup = true" class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 flex items-center justify-center transition-colors" title="Crear grupo">
                        <svg class="w-4 h-4 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                    <button @click="showNewChat = true" class="w-8 h-8 rounded-lg bg-nexo-600 hover:bg-nexo-700 flex items-center justify-center transition-colors" title="Nuevo chat">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
            </div>
            <!-- Search -->
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 dark:text-white/25 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="busqueda" placeholder="Buscar conversación..." class="w-full pl-9 pr-4 py-2 text-xs rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50 transition-colors">
            </div>
            <!-- Tabs -->
            <div class="flex mt-3 gap-1 p-0.5 rounded-lg dark:bg-white/5 bg-gray-100">
                <button @click="tab='todos'" :class="tab==='todos' ? 'dark:bg-white/10 bg-white shadow-sm dark:text-white text-gray-900' : 'dark:text-white/40 text-gray-500'" class="flex-1 text-[11px] font-medium py-1.5 rounded-md transition-all">Todos</button>
                <button @click="tab='privados'" :class="tab==='privados' ? 'dark:bg-white/10 bg-white shadow-sm dark:text-white text-gray-900' : 'dark:text-white/40 text-gray-500'" class="flex-1 text-[11px] font-medium py-1.5 rounded-md transition-all">Privados</button>
                <button @click="tab='grupos'" :class="tab==='grupos' ? 'dark:bg-white/10 bg-white shadow-sm dark:text-white text-gray-900' : 'dark:text-white/40 text-gray-500'" class="flex-1 text-[11px] font-medium py-1.5 rounded-md transition-all">Grupos</button>
            </div>
        </div>

        <!-- Conversation list -->
        <div class="flex-1 overflow-y-auto">
            <?php foreach ($convs as $i => $c):
                $name = htmlspecialchars($c['display_name']);
                $avatar = $c['display_avatar'];
                $initial = mb_strtoupper(mb_substr($c['display_name'], 0, 1));
                $isGroup = $c['tipo'] === 'grupo';
                $lastMsg = $c['ultimo_msg'] ? htmlspecialchars(mb_substr($c['ultimo_msg'], 0, 40)) : 'Sin mensajes aún';
                if ($c['ultimo_msg_tipo'] === 'imagen') $lastMsg = '📷 Imagen';
                elseif ($c['ultimo_msg_tipo'] === 'audio') $lastMsg = '🎵 Audio';
                elseif ($c['ultimo_msg_tipo'] === 'archivo') $lastMsg = '📎 Archivo';
                $time = $c['ultimo_msg_fecha'] ? date('H:i', strtotime($c['ultimo_msg_fecha'])) : '';
                $partCount = count($c['participantes']);
            ?>
            <div @click="loadConv(<?php echo $c['id']; ?>, '<?php echo addslashes($c['display_name']); ?>', '<?php echo $isGroup ? 'grupo' : 'privada'; ?>')"
                 x-show="(tab==='todos' || (tab==='privados' && '<?php echo $c['tipo']; ?>'==='privada') || (tab==='grupos' && '<?php echo $c['tipo']; ?>'==='grupo')) && (busqueda==='' || '<?php echo strtolower(addslashes($c['display_name'])); ?>'.includes(busqueda.toLowerCase()))"
                 class="flex items-center gap-3 px-4 py-3 cursor-pointer transition-all duration-150 border-l-2"
                 :class="convActiva === <?php echo $c['id']; ?> ? 'dark:bg-nexo-500/[0.08] bg-nexo-50 border-nexo-500' : 'border-transparent dark:hover:bg-white/[0.03] hover:bg-gray-50'">
                <!-- Avatar -->
                <div class="relative shrink-0">
                    <?php if ($avatar && !$isGroup): ?>
                    <img src="uploads/avatars/<?php echo htmlspecialchars($avatar); ?>" class="w-11 h-11 rounded-full object-cover ring-2 ring-white/10" alt="">
                    <?php elseif ($isGroup): ?>
                    <div class="w-11 h-11 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <?php else: ?>
                    <div class="w-11 h-11 rounded-full bg-gradient-to-br from-nexo-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold"><?php echo $initial; ?></div>
                    <?php endif; ?>
                    <?php if ($c['no_leidos'] > 0): ?>
                    <span class="absolute -top-0.5 -right-0.5 w-5 h-5 rounded-full bg-nexo-500 text-white text-[10px] font-bold flex items-center justify-center ring-2 dark:ring-dark-900 ring-white"><?php echo $c['no_leidos']; ?></span>
                    <?php endif; ?>
                </div>
                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="text-sm font-semibold truncate dark:text-white text-gray-900"><?php echo $name; ?></span>
                            <?php if ($isGroup): ?><span class="text-[9px] font-medium px-1.5 py-0.5 rounded-full dark:bg-blue-500/10 bg-blue-50 dark:text-blue-400 text-blue-500"><?php echo $partCount; ?></span><?php endif; ?>
                        </div>
                        <span class="text-[10px] dark:text-white/25 text-gray-400 shrink-0 ml-2"><?php echo $time; ?></span>
                    </div>
                    <p class="text-xs dark:text-white/35 text-gray-400 truncate mt-0.5"><?php echo $lastMsg; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($convs)): ?>
            <div class="flex flex-col items-center justify-center h-full py-12 px-6 text-center">
                <div class="w-14 h-14 rounded-2xl dark:bg-white/5 bg-gray-100 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 dark:text-white/20 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <p class="text-sm dark:text-white/40 text-gray-400">No hay conversaciones</p>
                <p class="text-xs dark:text-white/20 text-gray-300 mt-1">Inicia un chat nuevo</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========== CHAT AREA ========== -->
    <div class="flex-1 flex flex-col dark:bg-dark-950 bg-gray-50 relative" :class="convActiva === null ? 'hidden lg:flex' : 'flex'">

        <!-- Chat header -->
        <div x-show="convActiva !== null" class="h-16 border-b dark:border-white/[0.06] border-gray-200 flex items-center justify-between px-4 dark:bg-dark-900/50 bg-white/80 backdrop-blur-sm">
            <div class="flex items-center gap-3">
                <button @click="convActiva = null; clearInterval(polling)" class="lg:hidden w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center">
                    <svg class="w-4 h-4 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-nexo-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold overflow-hidden" x-ref="headerAvatar">
                    <span x-text="convNombre.charAt(0).toUpperCase()"></span>
                </div>
                <div>
                    <p class="text-sm font-semibold dark:text-white text-gray-900" x-text="convNombre"></p>
                    <p class="text-[10px] dark:text-white/30 text-gray-400" x-text="convTipo === 'grupo' ? convParticipantes + ' participantes' : 'En línea'"></p>
                </div>
            </div>
            <div class="flex items-center gap-1" x-show="convTipo === 'grupo'">
                <button @click="showGroupInfo = !showGroupInfo" class="w-8 h-8 rounded-lg dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 flex items-center justify-center transition-colors">
                    <svg class="w-4 h-4 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
            </div>
        </div>

        <!-- Messages -->
        <div x-ref="msgContainer" @scroll="updateScrollBtn()" class="flex-1 overflow-y-auto px-4 py-4 space-y-1" x-show="convActiva !== null">
            <template x-for="(msg, idx) in mensajes" :key="msg.id">
                <div>
                    <!-- Date separator -->
                    <div x-show="idx === 0 || msg.fecha !== mensajes[idx-1]?.fecha" class="flex items-center justify-center my-4">
                        <span class="text-[10px] font-medium dark:text-white/20 text-gray-400 dark:bg-dark-800 bg-gray-100 px-3 py-1 rounded-full" x-text="msg.fecha"></span>
                    </div>
                    <!-- Message bubble -->
                    <div class="flex mb-1" :class="msg.usuario_id == <?php echo $uid; ?> ? 'justify-end' : 'justify-start'">
                        <!-- Other user avatar -->
                        <div class="w-7 shrink-0 mr-2" :class="msg.usuario_id == <?php echo $uid; ?> ? 'hidden' : ''">
                            <template x-if="msg.usuario_id != <?php echo $uid; ?> && (idx === 0 || mensajes[idx-1]?.usuario_id !== msg.usuario_id)">
                                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-nexo-500 to-purple-600 flex items-center justify-center overflow-hidden">
                                    <template x-if="msg.avatar">
                                        <img :src="'uploads/avatars/' + msg.avatar" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!msg.avatar">
                                        <span class="text-white text-[10px] font-bold" x-text="msg.user_name.charAt(0).toUpperCase()"></span>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <div class="max-w-[70%] group relative">
                            <!-- Sender name in groups -->
                            <p x-show="convTipo === 'grupo' && msg.usuario_id != <?php echo $uid; ?> && (idx === 0 || mensajes[idx-1]?.usuario_id !== msg.usuario_id)" class="text-[10px] font-semibold dark:text-nexo-400 text-nexo-600 mb-0.5 ml-1" x-text="msg.user_name"></p>

                            <!-- Context menu trigger (own messages) -->
                            <div x-show="msg.usuario_id == <?php echo $uid; ?> && !msg.eliminado" class="absolute -left-8 top-1 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                <button @click.stop="contextMsg = contextMsg === msg.id ? null : msg.id" class="w-6 h-6 rounded-full dark:bg-white/10 bg-gray-200 dark:hover:bg-white/20 hover:bg-gray-300 flex items-center justify-center transition-all">
                                    <svg class="w-3.5 h-3.5 dark:text-white/50 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/></svg>
                                </button>
                                <!-- Dropdown menu -->
                                <div x-show="contextMsg === msg.id" @click.outside="contextMsg = null" x-transition class="absolute right-0 top-8 dark:bg-dark-800 bg-white border dark:border-white/10 border-gray-200 rounded-xl shadow-2xl py-1 w-48 z-20">
                                    <button @click="deleteMsg(msg.id); contextMsg = null" class="w-full flex items-center gap-2.5 px-3 py-2.5 text-xs font-medium text-red-400 dark:hover:bg-white/5 hover:bg-gray-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Eliminar para todos
                                    </button>
                                </div>
                            </div>
                            <!-- Context menu for OTHER user messages (only for group admins or all) -->
                            <div x-show="msg.usuario_id != <?php echo $uid; ?> && !msg.eliminado" class="absolute -right-8 top-1 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                <button @click.stop="contextMsg = contextMsg === msg.id ? null : msg.id" class="w-6 h-6 rounded-full dark:bg-white/10 bg-gray-200 dark:hover:bg-white/20 hover:bg-gray-300 flex items-center justify-center transition-all">
                                    <svg class="w-3.5 h-3.5 dark:text-white/50 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/></svg>
                                </button>
                            </div>

                            <!-- DELETED MESSAGE -->
                            <template x-if="msg.eliminado">
                                <div class="px-3.5 py-2.5 rounded-2xl dark:bg-white/[0.03] bg-gray-50 border dark:border-white/[0.04] border-gray-100 shadow-sm" :class="msg.usuario_id == <?php echo $uid; ?> ? 'rounded-br-sm' : 'rounded-bl-sm'">
                                    <p class="text-[13px] italic dark:text-white/25 text-gray-400 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        <span x-text="msg.usuario_id == <?php echo $uid; ?> ? 'Eliminaste este mensaje' : 'Mensaje eliminado'"></span>
                                    </p>
                                    <p class="text-[10px] mt-1 dark:text-white/15 text-gray-300" x-text="msg.hora"></p>
                                </div>
                            </template>

                            <!-- Text message -->
                            <template x-if="(msg.tipo === 'texto' || !msg.tipo) && !msg.eliminado">
                                <div :class="msg.usuario_id == <?php echo $uid; ?> ? 'bg-nexo-600 text-white rounded-2xl rounded-br-sm' : 'dark:bg-white/[0.06] bg-white border dark:border-white/[0.04] border-gray-100 rounded-2xl rounded-bl-sm'" class="px-3.5 py-2.5 shadow-sm">
                                    <p class="text-[13px] leading-relaxed whitespace-pre-wrap break-words" x-text="msg.mensaje"></p>
                                    <div class="flex items-center justify-end gap-1 mt-1">
                                        <p class="text-[10px]" :class="msg.usuario_id == <?php echo $uid; ?> ? 'text-white/50' : 'dark:text-white/25 text-gray-400'" x-text="msg.hora"></p>
                                        <template x-if="msg.usuario_id == <?php echo $uid; ?>">
                                            <svg class="w-3.5 h-3.5" :class="msg.leido ? 'text-blue-300' : 'text-white/40'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7M5 13l4 4L19 7" transform="translate(-2,0)"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" transform="translate(2,0)"/></svg>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <!-- Image message -->
                            <template x-if="msg.tipo === 'imagen' && !msg.eliminado">
                                <div :class="msg.usuario_id == <?php echo $uid; ?> ? 'rounded-2xl rounded-br-sm' : 'rounded-2xl rounded-bl-sm'" class="overflow-hidden shadow-sm">
                                    <img :src="msg.archivo_url" @click="previewImg = msg.archivo_url; showPreview = true" class="max-w-full max-h-64 rounded-2xl cursor-pointer hover:opacity-90 transition-opacity" alt="Imagen">
                                    <div class="flex items-center justify-end gap-1 mt-1">
                                        <p class="text-[10px] dark:text-white/25 text-gray-400" x-text="msg.hora"></p>
                                        <template x-if="msg.usuario_id == <?php echo $uid; ?>">
                                            <svg class="w-3.5 h-3.5" :class="msg.leido ? 'text-blue-400' : 'dark:text-white/30 text-gray-300'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" transform="translate(-2,0)"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" transform="translate(2,0)"/></svg>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <!-- Audio message -->
                            <template x-if="msg.tipo === 'audio' && !msg.eliminado">
                                <div :class="msg.usuario_id == <?php echo $uid; ?> ? 'bg-nexo-600 text-white rounded-2xl rounded-br-sm' : 'dark:bg-white/[0.06] bg-white border dark:border-white/[0.04] border-gray-100 rounded-2xl rounded-bl-sm'" class="px-3.5 py-2.5 shadow-sm">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                                        <audio :src="msg.archivo_url" controls class="h-8 max-w-[200px]" style="filter: brightness(1.2);"></audio>
                                    </div>
                                    <div class="flex items-center justify-end gap-1 mt-1">
                                        <p class="text-[10px]" :class="msg.usuario_id == <?php echo $uid; ?> ? 'text-white/50' : 'dark:text-white/25 text-gray-400'" x-text="msg.hora"></p>
                                        <template x-if="msg.usuario_id == <?php echo $uid; ?>">
                                            <svg class="w-3.5 h-3.5" :class="msg.leido ? 'text-blue-300' : 'text-white/40'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" transform="translate(-2,0)"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" transform="translate(2,0)"/></svg>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <!-- File message -->
                            <template x-if="msg.tipo === 'archivo' && !msg.eliminado">
                                <div :class="msg.usuario_id == <?php echo $uid; ?> ? 'bg-nexo-600 text-white rounded-2xl rounded-br-sm' : 'dark:bg-white/[0.06] bg-white border dark:border-white/[0.04] border-gray-100 rounded-2xl rounded-bl-sm'" class="px-3.5 py-2.5 shadow-sm">
                                    <a :href="msg.archivo_url" target="_blank" class="flex items-center gap-2.5 hover:opacity-80 transition-opacity">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" :class="msg.usuario_id == <?php echo $uid; ?> ? 'bg-white/20' : 'dark:bg-white/10 bg-gray-100'">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium truncate" x-text="msg.archivo_nombre || 'Archivo'"></p>
                                            <p class="text-[10px] opacity-50" x-text="msg.archivo_size || ''"></p>
                                        </div>
                                    </a>
                                    <div class="flex items-center justify-end gap-1 mt-1">
                                        <p class="text-[10px]" :class="msg.usuario_id == <?php echo $uid; ?> ? 'text-white/50' : 'dark:text-white/25 text-gray-400'" x-text="msg.hora"></p>
                                        <template x-if="msg.usuario_id == <?php echo $uid; ?>">
                                            <svg class="w-3.5 h-3.5" :class="msg.leido ? 'text-blue-300' : 'text-white/40'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" transform="translate(-2,0)"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" transform="translate(2,0)"/></svg>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Scroll to bottom button -->
        <button x-show="showScrollBtn" x-transition @click="scrollBottom()" class="absolute bottom-20 right-6 w-10 h-10 rounded-full bg-nexo-600 hover:bg-nexo-700 text-white shadow-lg shadow-nexo-600/30 flex items-center justify-center transition-all z-10">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
        </button>

        <!-- Empty state -->
        <div x-show="convActiva === null" class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <div class="w-20 h-20 mx-auto rounded-2xl dark:bg-white/5 bg-gray-100 flex items-center justify-center mb-4">
                    <svg class="w-9 h-9 dark:text-white/15 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <p class="text-sm font-medium dark:text-white/40 text-gray-400">Selecciona una conversación</p>
                <p class="text-xs dark:text-white/20 text-gray-300 mt-1">O inicia un chat nuevo</p>
            </div>
        </div>

        <!-- Input area -->
        <div x-show="convActiva !== null" class="px-4 py-3 border-t dark:border-white/[0.06] border-gray-200 dark:bg-dark-900/50 bg-white/80 backdrop-blur-sm">
            <!-- File preview -->
            <div x-show="filePreview" class="mb-2 flex items-center gap-2 px-3 py-2 rounded-xl dark:bg-white/5 bg-gray-100">
                <template x-if="filePreview && fileType === 'imagen'">
                    <img :src="filePreview" class="w-12 h-12 rounded-lg object-cover">
                </template>
                <template x-if="filePreview && fileType !== 'imagen'">
                    <div class="w-12 h-12 rounded-lg dark:bg-white/10 bg-gray-200 flex items-center justify-center">
                        <svg class="w-5 h-5 dark:text-white/40 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                </template>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium truncate dark:text-white/70 text-gray-600" x-text="fileName"></p>
                    <p class="text-[10px] dark:text-white/30 text-gray-400" x-text="fileSize"></p>
                </div>
                <button @click="clearFile()" class="w-6 h-6 rounded-full dark:bg-white/10 bg-gray-200 flex items-center justify-center dark:hover:bg-white/20 hover:bg-gray-300 transition-colors">
                    <svg class="w-3 h-3 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="enviar()" class="flex items-end gap-2">
                <!-- Attachment button -->
                <div class="relative" x-data="{ attachOpen: false }">
                    <button type="button" @click="attachOpen = !attachOpen" class="w-10 h-10 rounded-xl dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 flex items-center justify-center transition-colors shrink-0">
                        <svg class="w-5 h-5 dark:text-white/40 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    </button>
                    <!-- Attachment menu -->
                    <div x-show="attachOpen" @click.outside="attachOpen = false" x-transition class="absolute bottom-12 left-0 dark:bg-dark-800 bg-white border dark:border-white/10 border-gray-200 rounded-xl shadow-2xl py-1 w-44 z-10" x-cloak>
                        <button type="button" @click="$refs.imgInput.click(); attachOpen = false" class="w-full flex items-center gap-2.5 px-3 py-2.5 text-xs font-medium dark:hover:bg-white/5 hover:bg-gray-50 transition-colors">
                            <div class="w-7 h-7 rounded-lg bg-blue-500/10 flex items-center justify-center"><svg class="w-3.5 h-3.5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                            <span class="dark:text-white/70 text-gray-700">Imagen</span>
                        </button>

                        <button type="button" @click="$refs.fileInput.click(); attachOpen = false" class="w-full flex items-center gap-2.5 px-3 py-2.5 text-xs font-medium dark:hover:bg-white/5 hover:bg-gray-50 transition-colors">
                            <div class="w-7 h-7 rounded-lg bg-amber-500/10 flex items-center justify-center"><svg class="w-3.5 h-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
                            <span class="dark:text-white/70 text-gray-700">Documento</span>
                        </button>
                    </div>
                </div>
                <!-- Hidden file inputs -->
                <input type="file" x-ref="imgInput" @change="handleFile($event, 'imagen')" accept="image/*" class="hidden">
                <input type="file" x-ref="fileInput" @change="handleFile($event, 'archivo')" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.txt,.csv" class="hidden">
                <!-- Text input / Recording indicator -->
                <template x-if="!recording">
                    <input type="text" x-model="nuevoMsg" placeholder="Escribe un mensaje..." class="flex-1 px-4 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50 transition-colors" autocomplete="off" @keydown.enter.prevent="enviar()">
                </template>
                <template x-if="recording">
                    <div class="flex-1 flex items-center gap-3 px-4 py-2.5 rounded-xl dark:bg-red-500/10 bg-red-50 border border-red-500/30">
                        <div class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></div>
                        <span class="text-sm font-medium text-red-500" x-text="recordTime"></span>
                        <div class="flex-1 flex items-center gap-0.5">
                            <template x-for="i in 20" :key="i">
                                <div class="flex-1 rounded-full bg-red-500/40" :style="'height:' + (Math.random() * 16 + 4) + 'px; animation: waveAnim 0.5s ease infinite alternate; animation-delay:' + (i * 0.05) + 's'"></div>
                            </template>
                        </div>
                        <button type="button" @click="cancelRecording()" class="w-7 h-7 rounded-full bg-red-500/20 hover:bg-red-500/30 flex items-center justify-center transition-colors">
                            <svg class="w-3.5 h-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
                <!-- Mic / Send -->
                <template x-if="!recording && !nuevoMsg.trim() && !pendingFile">
                    <button type="button" @click="startRecording()" class="w-10 h-10 rounded-xl dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 flex items-center justify-center shrink-0 transition-colors">
                        <svg class="w-5 h-5 dark:text-white/40 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4M12 15a3 3 0 003-3V5a3 3 0 00-6 0v7a3 3 0 003 3z"/></svg>
                    </button>
                </template>
                <template x-if="recording">
                    <button type="button" @click="stopRecording()" class="w-10 h-10 rounded-xl bg-red-500 hover:bg-red-600 flex items-center justify-center text-white shrink-0 transition-colors shadow-lg shadow-red-500/30">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </template>
                <template x-if="!recording && (nuevoMsg.trim() || pendingFile)">
                    <button type="submit" class="w-10 h-10 rounded-xl bg-nexo-600 hover:bg-nexo-700 flex items-center justify-center text-white shrink-0 transition-colors shadow-lg shadow-nexo-600/20" :disabled="sending">
                        <svg x-show="!sending" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <svg x-show="sending" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </button>
                </template>
            </form>
        </div>
    </div>

    <!-- ========== GROUP INFO PANEL ========== -->
    <div x-show="showGroupInfo" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="w-72 shrink-0 border-l dark:border-white/[0.06] border-gray-200 dark:bg-dark-900 bg-white flex flex-col overflow-hidden" x-cloak>
        <div class="px-4 py-3 border-b dark:border-white/[0.06] border-gray-200 flex items-center justify-between">
            <span class="text-sm font-semibold">Info del Grupo</span>
            <button @click="showGroupInfo = false" class="w-7 h-7 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center">
                <svg class="w-3.5 h-3.5 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center mb-2">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <p class="text-sm font-bold dark:text-white text-gray-900" x-text="convNombre"></p>
                <p class="text-xs dark:text-white/30 text-gray-400" x-text="convParticipantes + ' participantes'"></p>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider dark:text-white/30 text-gray-400 mb-2">Participantes</p>
                <div class="space-y-1">
                    <template x-for="p in groupMembers" :key="p.id">
                        <div class="flex items-center gap-2.5 p-2 rounded-lg dark:hover:bg-white/5 hover:bg-gray-50 transition-colors">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-nexo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold overflow-hidden shrink-0">
                                <template x-if="p.avatar">
                                    <img :src="'uploads/avatars/' + p.avatar" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!p.avatar">
                                    <span x-text="p.nombre.charAt(0).toUpperCase()"></span>
                                </template>
                            </div>
                            <span class="text-xs font-medium dark:text-white/70 text-gray-600 truncate" x-text="p.nombre"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========== MODAL: NEW CHAT ========== -->
<div x-show="showNewChat" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div @click="showNewChat = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-sm dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl overflow-hidden">
        <div class="px-5 py-4 border-b dark:border-white/[0.06] border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold dark:text-white text-gray-900">Nueva Conversación</h3>
            <button @click="showNewChat = false" class="w-7 h-7 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center">
                <svg class="w-3.5 h-3.5 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-2 max-h-72 overflow-y-auto">
            <?php foreach ($usuarios as $u):
                if ($u['id'] == $uid) continue;
                $uAvatar = $u['avatar'];
                $uInitial = mb_strtoupper(mb_substr($u['nombre'], 0, 1));
            ?>
            <button @click="newConv(<?php echo $u['id']; ?>)" class="w-full flex items-center gap-3 p-3 rounded-xl dark:hover:bg-white/[0.04] hover:bg-gray-50 transition-colors text-left">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-nexo-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold shrink-0 overflow-hidden">
                    <?php if ($uAvatar): ?><img src="uploads/avatars/<?php echo htmlspecialchars($uAvatar); ?>" class="w-full h-full object-cover"><?php else: echo $uInitial; endif; ?>
                </div>
                <div class="min-w-0">
                    <span class="text-sm font-medium dark:text-white text-gray-900"><?php echo htmlspecialchars($u['nombre']); ?></span>
                    <p class="text-[10px] dark:text-white/30 text-gray-400"><?php echo htmlspecialchars($u['email']); ?></p>
                </div>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ========== MODAL: NEW GROUP ========== -->
<div x-show="showNewGroup" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div @click="showNewGroup = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-md dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl overflow-hidden">
        <div class="px-5 py-4 border-b dark:border-white/[0.06] border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold dark:text-white text-gray-900">Crear Grupo</h3>
            <button @click="showNewGroup = false" class="w-7 h-7 rounded-lg dark:bg-white/5 bg-gray-100 flex items-center justify-center">
                <svg class="w-3.5 h-3.5 dark:text-white/50 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-5 space-y-4">
            <div>
                <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block">Nombre del grupo</label>
                <input type="text" x-model="groupName" placeholder="Ej: Equipo de ventas" class="w-full px-3.5 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 outline-none focus:border-nexo-500/50">
            </div>
            <div>
                <label class="text-xs font-medium dark:text-white/50 text-gray-500 mb-1.5 block">Participantes <span class="dark:text-white/25 text-gray-400" x-text="'(' + selectedUsers.length + ' seleccionados)'"></span></label>
                <div class="space-y-1 max-h-48 overflow-y-auto rounded-xl dark:bg-white/[0.02] bg-gray-50 p-2 border dark:border-white/[0.04] border-gray-100">
                    <?php foreach ($usuarios as $u):
                        if ($u['id'] == $uid) continue;
                        $uAvatar = $u['avatar'];
                        $uInitial = mb_strtoupper(mb_substr($u['nombre'], 0, 1));
                    ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg cursor-pointer dark:hover:bg-white/5 hover:bg-gray-100 transition-colors" :class="selectedUsers.includes(<?php echo $u['id']; ?>) ? 'dark:bg-white/[0.06] bg-nexo-50' : ''">
                        <input type="checkbox" value="<?php echo $u['id']; ?>" @change="toggleUser(<?php echo $u['id']; ?>)" :checked="selectedUsers.includes(<?php echo $u['id']; ?>)" class="sr-only peer">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-nexo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold shrink-0 overflow-hidden">
                            <?php if ($uAvatar): ?><img src="uploads/avatars/<?php echo htmlspecialchars($uAvatar); ?>" class="w-full h-full object-cover"><?php else: echo $uInitial; endif; ?>
                        </div>
                        <span class="text-xs font-medium dark:text-white/70 text-gray-700 flex-1"><?php echo htmlspecialchars($u['nombre']); ?></span>
                        <div class="w-5 h-5 rounded-md border-2 flex items-center justify-center transition-colors" :class="selectedUsers.includes(<?php echo $u['id']; ?>) ? 'bg-nexo-600 border-nexo-600' : 'dark:border-white/20 border-gray-300'">
                            <svg x-show="selectedUsers.includes(<?php echo $u['id']; ?>)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <button @click="createGroup()" :disabled="!groupName.trim() || selectedUsers.length < 1" class="w-full py-2.5 rounded-xl text-sm font-medium text-white bg-nexo-600 hover:bg-nexo-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                Crear Grupo
            </button>
        </div>
    </div>
</div>

<!-- ========== IMAGE PREVIEW ========== -->
<div x-show="showPreview" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click="showPreview = false">
    <img :src="previewImg" class="max-w-full max-h-[90vh] rounded-xl shadow-2xl" @click.stop>
</div>

<script>
function chatApp() {
    return {
        busqueda: '',
        tab: 'todos',
        convActiva: null,
        convNombre: '',
        convTipo: 'privada',
        convParticipantes: 0,
        mensajes: [],
        nuevoMsg: '',
        polling: null,
        showNewChat: false,
        showNewGroup: false,
        showGroupInfo: false,
        showPreview: false,
        previewImg: '',
        sending: false,
        pendingFile: null,
        filePreview: null,
        fileName: '',
        fileSize: '',
        fileType: '',
        groupName: '',
        selectedUsers: [],
        groupMembers: [],
        contextMsg: null,
        showScrollBtn: false,
        recording: false,
        mediaRecorder: null,
        audioChunks: [],
        recordTimer: null,
        recordSeconds: 0,
        recordTime: '0:00',

        init() {},

        updateScrollBtn() {
            if (!this.$refs.msgContainer) return;
            const el = this.$refs.msgContainer;
            this.showScrollBtn = el.scrollHeight - el.scrollTop - el.clientHeight > 150;
        },

        async loadConv(id, nombre, tipo) {
            this.convActiva = id;
            this.convNombre = nombre || 'Chat';
            this.convTipo = tipo || 'privada';
            this.showGroupInfo = false;
            this.clearFile();
            try {
                const r = await fetch('api/chat.php?action=messages&conv_id=' + id);
                const data = await r.json();
                this.mensajes = data.mensajes || [];
                this.convNombre = data.nombre || nombre || 'Chat';
                this.convParticipantes = data.participantes_count || 0;
                this.groupMembers = data.participantes || [];
                this.$nextTick(() => this.scrollBottom());
                fetch('api/chat.php?action=read&conv_id=' + id);
                clearInterval(this.polling);
                this.polling = setInterval(() => this.refresh(), 4000);
            } catch(e) { console.error(e); }
        },

        async refresh() {
            if (!this.convActiva) return;
            try {
                const r = await fetch('api/chat.php?action=messages&conv_id=' + this.convActiva);
                const data = await r.json();
                if (data.mensajes && data.mensajes.length > this.mensajes.length) {
                    if (typeof NexoSounds !== 'undefined') NexoSounds.message();
                    this.mensajes = data.mensajes;
                    this.$nextTick(() => this.scrollBottom());
                }
            } catch(e) {}
        },

        scrollBottom() {
            if (this.$refs.msgContainer) {
                this.$refs.msgContainer.scrollTo({ top: this.$refs.msgContainer.scrollHeight, behavior: 'smooth' });
            }
        },

        handleFile(event, type) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 10 * 1024 * 1024) { alert('Archivo demasiado grande (máx 10MB)'); return; }
            this.pendingFile = file;
            this.fileType = type;
            this.fileName = file.name;
            this.fileSize = this.formatSize(file.size);
            if (type === 'imagen') {
                const reader = new FileReader();
                reader.onload = (e) => this.filePreview = e.target.result;
                reader.readAsDataURL(file);
            } else {
                this.filePreview = 'file';
            }
            event.target.value = '';
        },

        async startRecording() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.audioChunks = [];
                this.mediaRecorder = new MediaRecorder(stream, { mimeType: MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : 'audio/mp4' });
                this.mediaRecorder.ondataavailable = (e) => { if (e.data.size > 0) this.audioChunks.push(e.data); };
                this.mediaRecorder.onstop = () => {
                    stream.getTracks().forEach(t => t.stop());
                    if (this.audioChunks.length === 0) return;
                    const ext = this.mediaRecorder.mimeType.includes('webm') ? 'webm' : 'm4a';
                    const blob = new Blob(this.audioChunks, { type: this.mediaRecorder.mimeType });
                    const file = new File([blob], 'audio_' + Date.now() + '.' + ext, { type: this.mediaRecorder.mimeType });
                    this.pendingFile = file;
                    this.fileType = 'audio';
                    this.fileName = file.name;
                    this.fileSize = this.formatSize(file.size);
                    this.filePreview = 'file';
                    this.sendAudioDirect();
                };
                this.mediaRecorder.start();
                this.recording = true;
                this.recordSeconds = 0;
                this.recordTime = '0:00';
                this.recordTimer = setInterval(() => {
                    this.recordSeconds++;
                    const m = Math.floor(this.recordSeconds / 60);
                    const s = this.recordSeconds % 60;
                    this.recordTime = m + ':' + String(s).padStart(2, '0');
                }, 1000);
            } catch(e) {
                alert('No se pudo acceder al micrófono. Verifica los permisos.');
            }
        },

        stopRecording() {
            if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                this.mediaRecorder.stop();
            }
            this.recording = false;
            clearInterval(this.recordTimer);
        },

        cancelRecording() {
            if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                this.audioChunks = [];
                this.mediaRecorder.onstop = () => {
                    this.mediaRecorder.stream.getTracks().forEach(t => t.stop());
                };
                this.mediaRecorder.stop();
            }
            this.recording = false;
            clearInterval(this.recordTimer);
            this.clearFile();
        },

        async sendAudioDirect() {
            if (!this.convActiva || !this.pendingFile) return;
            this.sending = true;
            try {
                const fd = new FormData();
                fd.append('action', 'send');
                fd.append('conv_id', this.convActiva);
                fd.append('archivo', this.pendingFile);
                fd.append('tipo', 'audio');
                fd.append('mensaje', '');
                const r = await fetch('api/chat.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.ok) {
                    if (typeof NexoSounds !== 'undefined') NexoSounds.sent();
                    this.mensajes.push(data.mensaje);
                    this.clearFile();
                    this.$nextTick(() => this.scrollBottom());
                }
            } catch(e) { console.error(e); }
            this.sending = false;
        },

        clearFile() {
            this.pendingFile = null;
            this.filePreview = null;
            this.fileName = '';
            this.fileSize = '';
            this.fileType = '';
        },

        formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024*1024) return (bytes/1024).toFixed(1) + ' KB';
            return (bytes/(1024*1024)).toFixed(1) + ' MB';
        },

        async enviar() {
            if (this.sending) return;
            if (!this.convActiva) return;
            if (!this.nuevoMsg.trim() && !this.pendingFile) return;

            this.sending = true;
            try {
                const fd = new FormData();
                fd.append('action', 'send');
                fd.append('conv_id', this.convActiva);
                if (this.pendingFile) {
                    fd.append('archivo', this.pendingFile);
                    fd.append('tipo', this.fileType);
                    fd.append('mensaje', this.nuevoMsg.trim() || '');
                } else {
                    fd.append('mensaje', this.nuevoMsg.trim());
                    fd.append('tipo', 'texto');
                }
                const r = await fetch('api/chat.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.ok) {
                    if (typeof NexoSounds !== 'undefined') NexoSounds.sent();
                    this.mensajes.push(data.mensaje);
                    this.nuevoMsg = '';
                    this.clearFile();
                    this.$nextTick(() => this.scrollBottom());
                }
            } catch(e) { console.error(e); }
            this.sending = false;
        },

        async newConv(userId) {
            this.showNewChat = false;
            try {
                const fd = new FormData();
                fd.append('action', 'new_conv');
                fd.append('usuario_id', userId);
                const r = await fetch('api/chat.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.ok && data.conv_id) location.reload();
            } catch(e) { console.error(e); }
        },

        toggleUser(id) {
            const idx = this.selectedUsers.indexOf(id);
            if (idx === -1) this.selectedUsers.push(id);
            else this.selectedUsers.splice(idx, 1);
        },

        async createGroup() {
            if (!this.groupName.trim() || this.selectedUsers.length < 1) return;
            try {
                const fd = new FormData();
                fd.append('action', 'new_group');
                fd.append('nombre', this.groupName.trim());
                fd.append('usuarios', JSON.stringify(this.selectedUsers));
                const r = await fetch('api/chat.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.ok) {
                    this.showNewGroup = false;
                    this.groupName = '';
                    this.selectedUsers = [];
                    location.reload();
                }
            } catch(e) { console.error(e); }
        },

        async deleteMsg(msgId) {
            if (!confirm('¿Eliminar este mensaje para todos?')) return;
            try {
                const fd = new FormData();
                fd.append('action', 'delete_msg');
                fd.append('msg_id', msgId);
                const r = await fetch('api/chat.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.ok) {
                    const msg = this.mensajes.find(m => m.id === msgId);
                    if (msg) {
                        msg.eliminado = true;
                        msg.mensaje = '';
                        msg.tipo = 'texto';
                        msg.archivo_url = null;
                    }
                }
            } catch(e) { console.error(e); }
        }
    }
}
</script>
</div>
</main>
<?php include 'includes/footer.php'; ?>
