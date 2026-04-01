<?php
/**
 * Onboarding overlay for new employees.
 * Include this in dashboard.php — only shows when onboarding_completado = 0
 * Checks user permissions to show only accessible modules.
 */
if (!isset($userOnboarding) || $userOnboarding || $isAdmin) return;

// Build steps based on user permissions
$steps = [];

// Dashboard is always visible
$steps[] = [
    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
    'title' => 'Dashboard',
    'desc' => 'Tu panel principal. Aquí verás tus estadísticas personales: clientes asignados, producción del mes, meta y próximos eventos.',
    'tip' => 'Revísalo cada mañana para planificar tu día.',
    'color' => 'nexo',
];

if (!isset($_permisos['clientes']) || $_permisos['clientes']['puede_ver']) {
    $steps[] = [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'title' => 'Clientes',
        'desc' => 'Aquí gestionas tus clientes. Puedes ver sus datos, historial de interacciones, estado y notas.',
        'tip' => 'Usa los filtros para encontrar clientes por estado: Nuevo, Contactado, Negociando, etc.',
        'color' => 'blue',
    ];
}

if (!isset($_permisos['pipeline']) || $_permisos['pipeline']['puede_ver']) {
    $steps[] = [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>',
        'title' => 'Pipeline',
        'desc' => 'Tu embudo de ventas. Arrastra clientes entre etapas para avanzar los negocios. Puedes mover clientes de la pestaña Clientes hasta aquí.',
        'tip' => 'Para mover un cliente al pipeline: en Clientes, cambia su estado (Nuevo → Contactado → Negociando).',
        'color' => 'emerald',
    ];
}

if (!isset($_permisos['calendario']) || $_permisos['calendario']['puede_ver']) {
    $steps[] = [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        'title' => 'Calendario',
        'desc' => 'Agenda reuniones, llamadas, seguimientos y tareas. Los eventos pueden ser asignados a ti o a otro miembro del equipo.',
        'tip' => 'Haz clic en cualquier día para crear un evento rápido.',
        'color' => 'amber',
    ];
}

if (!isset($_permisos['chat']) || $_permisos['chat']['puede_ver']) {
    $steps[] = [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',
        'title' => 'Chat',
        'desc' => 'Comunícate con tu equipo en tiempo real. Puedes crear grupos, enviar archivos, imágenes y notas de audio.',
        'tip' => 'Mantén presionado el botón de micrófono para enviar un audio rápido.',
        'color' => 'cyan',
    ];
}

if (!isset($_permisos['facturas']) || $_permisos['facturas']['puede_ver']) {
    $steps[] = [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'title' => 'Facturas',
        'desc' => 'Crea y gestiona facturas para tus clientes. Controla el estado: borrador, enviada, pagada o vencida.',
        'tip' => 'Vincula cada factura a un cliente para un mejor seguimiento.',
        'color' => 'red',
    ];
}

if (!isset($_permisos['finanzas']) || $_permisos['finanzas']['puede_ver']) {
    $steps[] = [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'title' => 'Finanzas',
        'desc' => 'Visualiza ingresos y gastos. Revisa reportes financieros y el flujo de caja de la empresa.',
        'tip' => 'Tu producción personal se muestra en tu Dashboard.',
        'color' => 'purple',
    ];
}

$totalSteps = count($steps);
$stepsJson = json_encode($steps);
$colorMap = ['nexo'=>'text-nexo-400 bg-nexo-500/15','blue'=>'text-blue-400 bg-blue-500/15','emerald'=>'text-emerald-400 bg-emerald-500/15','amber'=>'text-amber-400 bg-amber-500/15','cyan'=>'text-cyan-400 bg-cyan-500/15','red'=>'text-red-400 bg-red-500/15','purple'=>'text-purple-400 bg-purple-500/15'];
$dotColorMap = ['nexo'=>'bg-nexo-500','blue'=>'bg-blue-500','emerald'=>'bg-emerald-500','amber'=>'bg-amber-500','cyan'=>'bg-cyan-500','red'=>'bg-red-500','purple'=>'bg-purple-500'];
?>

<!-- Onboarding Overlay -->
<div x-data="onboardingApp()" x-show="show" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-md" @click.stop></div>
    <div class="relative w-full max-w-lg dark:bg-dark-800 bg-white rounded-3xl border dark:border-white/10 border-gray-200 shadow-2xl overflow-hidden" x-transition>
        <!-- Progress bar -->
        <div class="h-1 dark:bg-white/5 bg-gray-100">
            <div class="h-full bg-nexo-500 transition-all duration-500 ease-out" :style="'width:' + ((step + 1) / total * 100) + '%'"></div>
        </div>

        <!-- Welcome screen -->
        <template x-if="step === -1">
            <div class="p-8 text-center">
                <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-nexo-500 to-nexo-700 flex items-center justify-center mb-5 shadow-xl shadow-nexo-600/30">
                    <span class="text-3xl font-black text-white">JB</span>
                </div>
                <h2 class="text-xl font-bold dark:text-white text-gray-900 mb-2">¡Bienvenido a NEXO!</h2>
                <p class="text-sm dark:text-white/50 text-gray-500 mb-6 max-w-sm mx-auto">Te guiaremos por las principales funciones de la plataforma para que puedas empezar a trabajar de inmediato.</p>
                <div class="flex items-center justify-center gap-2 mb-6">
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg dark:bg-white/5 bg-gray-50">
                        <svg class="w-4 h-4 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-xs dark:text-white/50 text-gray-500"><?php echo $totalSteps; ?> pasos · ~2 min</span>
                    </div>
                </div>
                <button @click="step = 0" class="w-full px-6 py-3 rounded-xl text-sm font-semibold text-white bg-nexo-600 hover:bg-nexo-700 transition-colors shadow-lg shadow-nexo-600/20">
                    Comenzar Tour
                </button>
                <button @click="skip()" class="mt-3 text-xs dark:text-white/30 text-gray-400 hover:dark:text-white/50 hover:text-gray-600 transition-colors">Saltar y explorar solo</button>
            </div>
        </template>

        <!-- Step screens -->
        <template x-if="step >= 0 && step < total">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0" :class="stepIconBg()">
                        <svg class="w-6 h-6" :class="stepIconColor()" fill="none" viewBox="0 0 24 24" stroke="currentColor" x-html="steps[step].icon"></svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider dark:text-white/30 text-gray-400" x-text="'Paso ' + (step + 1) + ' de ' + total"></p>
                        <h3 class="text-lg font-bold dark:text-white text-gray-900" x-text="steps[step].title"></h3>
                    </div>
                </div>

                <p class="text-sm dark:text-white/60 text-gray-600 leading-relaxed mb-4" x-text="steps[step].desc"></p>

                <!-- Tip box -->
                <div class="flex items-start gap-2.5 p-3.5 rounded-xl dark:bg-white/5 bg-gray-50 border dark:border-white/[0.06] border-gray-200 mb-6">
                    <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    <p class="text-xs dark:text-white/50 text-gray-500 leading-relaxed" x-text="steps[step].tip"></p>
                </div>

                <!-- Navigation -->
                <div class="flex items-center justify-between">
                    <button @click="step > 0 ? step-- : step = -1" class="px-4 py-2 rounded-xl text-sm font-medium dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors dark:text-white/60 text-gray-600">
                        <span x-text="step > 0 ? 'Anterior' : 'Inicio'"></span>
                    </button>
                    <div class="flex items-center gap-1">
                        <template x-for="(s, i) in steps" :key="i">
                            <div class="w-2 h-2 rounded-full transition-all duration-300" :class="i === step ? stepDotColor() + ' scale-125' : 'dark:bg-white/10 bg-gray-200'"></div>
                        </template>
                    </div>
                    <template x-if="step < total - 1">
                        <button @click="step++" class="px-5 py-2 rounded-xl text-sm font-semibold text-white bg-nexo-600 hover:bg-nexo-700 transition-colors">
                            Siguiente
                        </button>
                    </template>
                    <template x-if="step === total - 1">
                        <button @click="finish()" class="px-5 py-2 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors">
                            ¡Empezar!
                        </button>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function onboardingApp() {
    const steps = <?php echo $stepsJson; ?>;
    const colorBg = {nexo:'bg-nexo-500/15',blue:'bg-blue-500/15',emerald:'bg-emerald-500/15',amber:'bg-amber-500/15',cyan:'bg-cyan-500/15',red:'bg-red-500/15',purple:'bg-purple-500/15'};
    const colorText = {nexo:'text-nexo-400',blue:'text-blue-400',emerald:'text-emerald-400',amber:'text-amber-400',cyan:'text-cyan-400',red:'text-red-400',purple:'text-purple-400'};
    const colorDot = {nexo:'bg-nexo-500',blue:'bg-blue-500',emerald:'bg-emerald-500',amber:'bg-amber-500',cyan:'bg-cyan-500',red:'bg-red-500',purple:'bg-purple-500'};

    return {
        show: true,
        step: -1,
        steps,
        total: steps.length,
        stepIconBg() { return colorBg[this.steps[this.step]?.color] || colorBg.nexo; },
        stepIconColor() { return colorText[this.steps[this.step]?.color] || colorText.nexo; },
        stepDotColor() { return colorDot[this.steps[this.step]?.color] || colorDot.nexo; },
        async finish() {
            this.show = false;
            try {
                const fd = new FormData();
                fd.append('action', 'complete_onboarding');
                await fetch('api/onboarding.php', { method: 'POST', body: fd });
            } catch(e) {}
        },
        async skip() {
            this.show = false;
            try {
                const fd = new FormData();
                fd.append('action', 'complete_onboarding');
                await fetch('api/onboarding.php', { method: 'POST', body: fd });
            } catch(e) {}
        }
    };
}
</script>
