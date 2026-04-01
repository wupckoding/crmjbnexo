<?php
require_once 'includes/auth_check.php';
$pageTitle = __('nav_avisos');
$currentPage = 'avisos';
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>
<div class="p-4 sm:p-6 space-y-4" x-data="avisosApp()" x-init="load()">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-nexo-600/10 flex items-center justify-center">
                <svg class="w-5 h-5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            </div>
            <div>
                <h2 class="text-xl font-bold"><?php echo __('avi_tablero'); ?></h2>
                <p class="text-xs dark:text-white/40 text-gray-400 mt-0.5"><?php echo __('avi_desc'); ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <!-- Filter pills -->
            <div class="flex gap-1.5">
                <template x-for="f in [{k:'',l:'<?php echo __('filtro_todos'); ?>'},{k:'urgente',l:'<?php echo __('avi_urgente'); ?>'},{k:'importante',l:'<?php echo __('avi_importante'); ?>'},{k:'normal',l:'<?php echo __('avi_normal'); ?>'}]" :key="f.k">
                    <button @click="filtro = f.k" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors flex items-center gap-1.5" :class="filtro === f.k ? 'bg-nexo-600 text-white' : 'dark:bg-white/5 bg-gray-100 dark:text-white/60 text-gray-500 hover:dark:bg-white/10 hover:bg-gray-200'">
                        <span x-show="f.k" class="w-2 h-2 rounded-full shrink-0" :class="{'bg-red-400': f.k==='urgente', 'bg-amber-400': f.k==='importante', 'bg-emerald-400': f.k==='normal'}"></span>
                        <span x-text="f.l"></span>
                    </button>
                </template>
            </div>
            <?php if ($isAdmin): ?>
            <button @click="openNew()" class="btn-purple px-4 py-2 rounded-xl text-sm font-medium text-white flex items-center gap-2 shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?php echo __('avi_nuevo'); ?>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pinned section -->
    <template x-if="pinned().length > 0">
        <div class="space-y-3">
            <p class="text-xs font-semibold dark:text-white/40 text-gray-400 uppercase tracking-wider flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.5 16a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.977A4.5 4.5 0 1113.5 16h-8z" clip-rule="evenodd"/></svg>
                <?php echo __('avi_fijados'); ?>
            </p>
            <template x-for="aviso in pinned()" :key="aviso.id">
                <div class="relative">
                    <div class="absolute top-3 right-3 text-amber-400">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                    <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border-2 dark:border-amber-500/20 border-amber-200 p-5 space-y-3"
                         :class="prioridadBorder(aviso.prioridad)">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" :class="prioridadBg(aviso.prioridad)">
                                <span class="w-3 h-3 rounded-full" :class="prioridadDot(aviso.prioridad)"></span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full" :class="prioridadBadge(aviso.prioridad)" x-text="prioridadLabel(aviso.prioridad)"></span>
                                </div>
                                <h3 class="font-bold text-base" x-text="aviso.titulo"></h3>
                                <div class="mt-2 text-sm dark:text-white/60 text-gray-600 whitespace-pre-line leading-relaxed" x-text="aviso.contenido"></div>
                                <!-- Image -->
                                <template x-if="aviso.imagen">
                                    <div class="mt-3"><img :src="'uploads/avisos/'+aviso.imagen" class="rounded-xl max-h-64 w-auto border dark:border-white/10 border-gray-200 cursor-pointer hover:opacity-90 transition" @click="openImg('uploads/avisos/'+aviso.imagen)" alt="Imagen del aviso"></div>
                                </template>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-3 border-t dark:border-white/[0.04] border-gray-100">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-nexo-600/20 flex items-center justify-center">
                                    <span class="text-[10px] font-bold text-nexo-400" x-text="(aviso.autor||'A')[0].toUpperCase()"></span>
                                </div>
                                <span class="text-xs dark:text-white/40 text-gray-400" x-text="aviso.autor"></span>
                                <span class="text-xs dark:text-white/20 text-gray-300">·</span>
                                <span class="text-xs dark:text-white/30 text-gray-400" x-text="timeAgo(aviso.creado_en)"></span>
                            </div>
                            <?php if ($isAdmin): ?>
                            <div class="flex items-center gap-1">
                                <button @click="togglePin(aviso.id)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/5 hover:bg-gray-100 text-amber-400 transition-colors" title="Desfijar"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg></button>
                                <button @click="openEdit(aviso)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/5 hover:bg-gray-100 text-blue-400 transition-colors"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                                <button @click="confirmDel(aviso.id, aviso.titulo)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/5 hover:bg-gray-100 text-red-400 transition-colors"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </template>

    <!-- Regular avisos feed -->
    <div class="space-y-3">
        <template x-if="regular().length > 0">
            <p class="text-xs font-semibold dark:text-white/40 text-gray-400 uppercase tracking-wider" x-text="pinned().length > 0 ? '<?php echo __('avi_recientes'); ?>' : ''"></p>
        </template>
        <template x-for="aviso in regular()" :key="aviso.id">
            <div class="stat-card dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5 space-y-3">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" :class="prioridadBg(aviso.prioridad)">
                        <span class="w-3 h-3 rounded-full" :class="prioridadDot(aviso.prioridad)"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full" :class="prioridadBadge(aviso.prioridad)" x-text="prioridadLabel(aviso.prioridad)"></span>
                        </div>
                        <h3 class="font-bold text-base" x-text="aviso.titulo"></h3>
                        <div class="mt-2 text-sm dark:text-white/60 text-gray-600 whitespace-pre-line leading-relaxed" x-text="aviso.contenido"></div>
                        <!-- Image -->
                        <template x-if="aviso.imagen">
                            <div class="mt-3"><img :src="'uploads/avisos/'+aviso.imagen" class="rounded-xl max-h-64 w-auto border dark:border-white/10 border-gray-200 cursor-pointer hover:opacity-90 transition" @click="openImg('uploads/avisos/'+aviso.imagen)" alt="Imagen del aviso"></div>
                        </template>
                    </div>
                </div>
                <div class="flex items-center justify-between pt-3 border-t dark:border-white/[0.04] border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-nexo-600/20 flex items-center justify-center">
                            <span class="text-[10px] font-bold text-nexo-400" x-text="(aviso.autor||'A')[0].toUpperCase()"></span>
                        </div>
                        <span class="text-xs dark:text-white/40 text-gray-400" x-text="aviso.autor"></span>
                        <span class="text-xs dark:text-white/20 text-gray-300">·</span>
                        <span class="text-xs dark:text-white/30 text-gray-400" x-text="timeAgo(aviso.creado_en)"></span>
                    </div>
                    <?php if ($isAdmin): ?>
                    <div class="flex items-center gap-1">
                        <button @click="togglePin(aviso.id)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/5 hover:bg-gray-100 dark:text-white/30 text-gray-400 transition-colors" title="Fijar"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg></button>
                        <button @click="openEdit(aviso)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/5 hover:bg-gray-100 text-blue-400 transition-colors"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                        <button @click="confirmDel(aviso.id, aviso.titulo)" class="w-7 h-7 flex items-center justify-center rounded-lg dark:hover:bg-white/5 hover:bg-gray-100 text-red-400 transition-colors"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty state -->
    <template x-if="filtered().length === 0 && !loading">
        <div class="text-center py-20 dark:text-white/30 text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            <p class="text-lg font-medium mb-1"><?php echo __('avi_sin_avisos'); ?></p>
            <p class="text-sm"><?php echo __('avi_sin_desc'); ?></p>
        </div>
    </template>

    <!-- ========== MODAL CREATE/EDIT ========== -->
    <div x-show="showModal" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showModal = false" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-lg dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl p-6 max-h-[90vh] overflow-y-auto" @click.outside="showModal = false">
            <h3 class="text-lg font-bold mb-4" x-text="editId ? '<?php echo __('avi_editar'); ?>' : '<?php echo __('avi_nuevo'); ?>'"></h3>
            <form @submit.prevent="saveAviso()" class="space-y-4">
                <div>
                    <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('avi_titulo_campo'); ?> *</label>
                    <input type="text" x-model="form.titulo" required class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50" placeholder="<?php echo __('avi_titulo_ph'); ?>">
                </div>
                <div>
                    <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('avi_contenido'); ?> *</label>
                    <textarea x-model="form.contenido" required rows="6" class="w-full px-3 py-2.5 text-sm rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 outline-none focus:border-nexo-500/50 resize-none" placeholder="<?php echo __('avi_contenido_ph'); ?>"></textarea>
                </div>
                <!-- Image upload -->
                <div>
                    <label class="text-xs dark:text-white/50 text-gray-500 mb-1 block"><?php echo __('avi_imagen'); ?></label>
                    <!-- Existing image preview -->
                    <div x-show="editId && existingImg && !removeImg" class="mb-2 relative inline-block">
                        <img :src="'uploads/avisos/'+existingImg" class="rounded-xl max-h-32 border dark:border-white/10 border-gray-200">
                        <button type="button" @click="removeImg = true" class="absolute -top-2 -right-2 w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-700 transition-colors" title="Quitar imagen">&times;</button>
                    </div>
                    <div x-show="!editId || removeImg || !existingImg" class="border-2 border-dashed dark:border-white/10 border-gray-200 rounded-xl p-4 text-center cursor-pointer hover:border-nexo-500/50 transition-colors" @click="$refs.imgInput.click()">
                        <template x-if="!imgPreview">
                            <div>
                                <svg class="w-6 h-6 mx-auto mb-1 dark:text-white/20 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <p class="text-xs dark:text-white/40 text-gray-400"><?php echo __('avi_subir_img'); ?></p>
                                <p class="text-[10px] dark:text-white/20 text-gray-300">JPG, PNG, GIF, WebP</p>
                            </div>
                        </template>
                        <template x-if="imgPreview">
                            <div class="relative inline-block">
                                <img :src="imgPreview" class="rounded-lg max-h-32">
                                <p class="text-[10px] dark:text-white/40 text-gray-400 mt-1" x-text="imgName"></p>
                            </div>
                        </template>
                    </div>
                    <input type="file" x-ref="imgInput" @change="handleImg($event)" accept="image/*" class="hidden">
                    <button type="button" x-show="imgPreview" @click="clearImg()" class="mt-1 text-xs text-red-400 hover:text-red-300"><?php echo __('avi_quitar_img'); ?></button>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs dark:text-white/50 text-gray-500 mb-2 block"><?php echo __('avi_prioridad'); ?></label>
                        <div class="flex gap-2">
                            <template x-for="p in [{k:'normal',l:'<?php echo __('avi_normal'); ?>',c:'bg-emerald-400'},{k:'importante',l:'<?php echo __('avi_importante'); ?>',c:'bg-amber-400'},{k:'urgente',l:'<?php echo __('avi_urgente'); ?>',c:'bg-red-400'}]" :key="p.k">
                                <button type="button" @click="form.prioridad = p.k" class="flex-1 px-2 py-2 rounded-xl text-xs font-medium border-2 transition-all text-center flex items-center justify-center gap-1.5" :class="form.prioridad === p.k ? 'border-nexo-500/50 dark:bg-white/5 bg-gray-50' : 'border-transparent dark:bg-white/5 bg-gray-50 opacity-50'">
                                    <span class="w-2 h-2 rounded-full shrink-0" :class="p.c"></span>
                                    <span x-text="p.l"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer px-3 py-2.5 rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/10 border-gray-200 w-full">
                            <input type="checkbox" x-model="form.fijado" class="w-4 h-4 rounded accent-nexo-600">
                            <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            <span class="text-xs dark:text-white/60 text-gray-600"><?php echo __('avi_fijar_arriba'); ?></span>
                        </label>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors"><?php echo __('btn_cancelar'); ?></button>
                    <button type="submit" class="flex-1 btn-purple px-4 py-2.5 rounded-xl text-sm font-medium text-white" x-text="saving ? '<?php echo __('usr_guardando'); ?>' : (editId ? '<?php echo __('btn_guardar'); ?>' : '<?php echo __('avi_publicar'); ?>')" :disabled="saving"></button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========== LIGHTBOX ========== -->
    <div x-show="lightboxSrc" x-transition x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80" @click="lightboxSrc = ''">
        <img :src="lightboxSrc" class="max-w-full max-h-[90vh] rounded-2xl shadow-2xl" @click.stop>
        <button @click="lightboxSrc = ''" class="absolute top-4 right-4 w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center text-white text-xl transition-colors">&times;</button>
    </div>

    <!-- ========== MODAL DELETE ========== -->
    <div x-show="showDel" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showDel = false" class="absolute inset-0 bg-black/60"></div>
        <div class="relative w-full max-w-sm dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/10 border-gray-200 shadow-2xl p-6 text-center">
            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-red-500/10 flex items-center justify-center"><svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></div>
            <h3 class="text-lg font-bold mb-1"><?php echo __('avi_eliminar_aviso'); ?></h3>
            <p class="text-sm dark:text-white/50 text-gray-500 mb-4">¿Eliminar "<strong x-text="delName"></strong>"? Esto no se puede deshacer.</p>
            <div class="flex gap-3">
                <button @click="showDel = false" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100"><?php echo __('btn_cancelar'); ?></button>
                <button @click="deleteAviso()" class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium bg-red-600 hover:bg-red-700 text-white transition-colors"><?php echo __('btn_eliminar'); ?></button>
            </div>
        </div>
    </div>
</div>
</main>

<script>
function avisosApp() {
    return {
        avisos: [], loading: true,
        showModal: false, showDel: false,
        editId: null, delId: null, delName: '',
        saving: false, filtro: '',
        imgFile: null, imgPreview: '', imgName: '', existingImg: '', removeImg: false,
        lightboxSrc: '',
        form: { titulo: '', contenido: '', prioridad: 'normal', fijado: false },

        async load() {
            this.loading = true;
            const r = await fetch('api/avisos.php?action=list');
            const d = await r.json();
            if (d.ok) this.avisos = d.avisos;
            this.loading = false;
        },

        filtered() {
            if (!this.filtro) return this.avisos;
            return this.avisos.filter(a => a.prioridad === this.filtro);
        },
        pinned() { return this.filtered().filter(a => parseInt(a.fijado) === 1); },
        regular() { return this.filtered().filter(a => parseInt(a.fijado) !== 1); },

        prioridadDot(p) { return {urgente:'bg-red-400',importante:'bg-amber-400',normal:'bg-emerald-400'}[p] || 'bg-emerald-400'; },
        prioridadLabel(p) { return {urgente:<?php echo json_encode(__('avi_urgente')); ?>,importante:<?php echo json_encode(__('avi_importante')); ?>,normal:<?php echo json_encode(__('avi_normal')); ?>}[p] || p; },
        prioridadBg(p) { return {urgente:'bg-red-500/10',importante:'bg-amber-500/10',normal:'bg-emerald-500/10'}[p] || 'bg-emerald-500/10'; },
        prioridadBadge(p) { return {urgente:'bg-red-500/10 text-red-400',importante:'bg-amber-500/10 text-amber-400',normal:'bg-emerald-500/10 text-emerald-400'}[p] || ''; },
        prioridadBorder(p) { return ''; /* pinned cards already have amber border */ },

        timeAgo(d) {
            if (!d) return '';
            const diff = Math.floor((Date.now() - new Date(d).getTime()) / 1000);
            if (diff < 60) return 'Justo ahora';
            if (diff < 3600) return Math.floor(diff / 60) + ' min';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h';
            const days = Math.floor(diff / 86400);
            if (days === 1) return 'Ayer';
            if (days < 30) return 'Hace ' + days + ' días';
            return new Date(d).toLocaleDateString('es');
        },

        handleImg(e) {
            const f = e.target.files[0];
            if (!f) return;
            this.imgFile = f;
            this.imgName = f.name;
            const reader = new FileReader();
            reader.onload = (ev) => this.imgPreview = ev.target.result;
            reader.readAsDataURL(f);
        },
        clearImg() { this.imgFile = null; this.imgPreview = ''; this.imgName = ''; },
        openImg(src) { this.lightboxSrc = src; },

        openNew() {
            this.editId = null;
            this.form = { titulo: '', contenido: '', prioridad: 'normal', fijado: false };
            this.clearImg(); this.existingImg = ''; this.removeImg = false;
            this.showModal = true;
        },

        openEdit(aviso) {
            this.editId = aviso.id;
            this.form = {
                titulo: aviso.titulo,
                contenido: aviso.contenido,
                prioridad: aviso.prioridad,
                fijado: parseInt(aviso.fijado) === 1
            };
            this.clearImg(); this.existingImg = aviso.imagen || ''; this.removeImg = false;
            this.showModal = true;
        },

        async saveAviso() {
            if (!this.form.titulo.trim() || !this.form.contenido.trim()) return;
            this.saving = true;
            const fd = new FormData();
            fd.append('action', this.editId ? 'update' : 'create');
            if (this.editId) fd.append('id', this.editId);
            fd.append('titulo', this.form.titulo);
            fd.append('contenido', this.form.contenido);
            fd.append('prioridad', this.form.prioridad);
            fd.append('fijado', this.form.fijado ? 1 : 0);
            if (this.imgFile) fd.append('imagen', this.imgFile);
            if (this.removeImg) fd.append('remove_imagen', 1);
            try {
                const r = await fetch('api/avisos.php', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.ok || d.id) { this.showModal = false; await this.load(); }
            } catch (e) { console.error(e); }
            this.saving = false;
        },

        async togglePin(id) {
            const fd = new FormData();
            fd.append('action', 'toggle_pin');
            fd.append('id', id);
            const r = await fetch('api/avisos.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.ok) await this.load();
        },

        confirmDel(id, name) { this.delId = id; this.delName = name; this.showDel = true; },

        async deleteAviso() {
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', this.delId);
            const r = await fetch('api/avisos.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.ok) { this.showDel = false; await this.load(); }
        }
    };
}
</script>
<?php include 'includes/footer.php'; ?>
