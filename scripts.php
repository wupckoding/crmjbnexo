<?php
require_once 'includes/auth_check.php';
$pageTitle = __('scr_titulo', 'Scripts de Ventas');
$currentPage = 'scripts';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="flex-1 min-w-0 overflow-auto">
<?php include 'includes/topbar.php'; ?>

<div class="p-4 sm:p-6" x-data="scriptsApp()">

    <!-- Hero Section -->
    <div class="relative overflow-hidden rounded-2xl border dark:border-nexo-500/20 border-nexo-200 dark:bg-gradient-to-br dark:from-nexo-900/60 dark:via-dark-800 dark:to-dark-900 bg-gradient-to-br from-nexo-50 via-white to-nexo-50 p-6 sm:p-8 mb-6">
        <div class="absolute top-0 right-0 w-72 h-72 bg-nexo-500/5 rounded-full -translate-y-36 translate-x-36 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-red-500/5 rounded-full translate-y-24 -translate-x-24 blur-2xl"></div>
        <div class="relative">
            <div class="flex items-start gap-4 mb-4">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-red-500 to-nexo-600 flex items-center justify-center shrink-0 shadow-lg shadow-nexo-500/20">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/></svg>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Scripts de Ventas</h1>
                    <p class="text-sm dark:text-white/50 text-gray-500 mt-1 max-w-xl">Guiones probados para llamadas en frío de <span class="font-bold text-nexo-400">60-90 segundos</span>. Tu ÚNICO objetivo: <span class="font-bold text-red-400 uppercase">agendar la reunión</span>. No vendas por teléfono. Solo agenda.</p>
                </div>
            </div>
            <!-- Key Rules -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="flex items-center gap-3 px-4 py-3 rounded-xl dark:bg-red-500/10 bg-red-50 border dark:border-red-500/20 border-red-200">
                    <div class="w-8 h-8 rounded-lg bg-red-500/20 flex items-center justify-center shrink-0"><span class="text-sm font-black text-red-400">1</span></div>
                    <p class="text-xs font-semibold dark:text-red-300 text-red-700">NUNCA vendas por teléfono. Solo agendas.</p>
                </div>
                <div class="flex items-center gap-3 px-4 py-3 rounded-xl dark:bg-amber-500/10 bg-amber-50 border dark:border-amber-500/20 border-amber-200">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center shrink-0"><span class="text-sm font-black text-amber-400">2</span></div>
                    <p class="text-xs font-semibold dark:text-amber-300 text-amber-700">Máximo 90 segundos por llamada. No charles.</p>
                </div>
                <div class="flex items-center gap-3 px-4 py-3 rounded-xl dark:bg-emerald-500/10 bg-emerald-50 border dark:border-emerald-500/20 border-emerald-200">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center shrink-0"><span class="text-sm font-black text-emerald-400">3</span></div>
                    <p class="text-xs font-semibold dark:text-emerald-300 text-emerald-700">Siempre da DOS opciones de horario. No preguntes "cuándo puede".</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab navigation -->
    <div class="flex items-center gap-1 p-1 rounded-xl dark:bg-white/5 bg-gray-100 mb-6 overflow-x-auto">
        <template x-for="(tab, idx) in tabs" :key="idx">
            <button @click="activeTab = idx" class="px-4 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap transition-all" :class="activeTab === idx ? 'dark:bg-dark-800 bg-white dark:text-white text-gray-900 shadow-sm' : 'dark:text-white/40 text-gray-500 hover:text-gray-700 dark:hover:text-white/60'" x-text="tab"></button>
        </template>
    </div>

    <!-- ========================================================= -->
    <!-- TAB 0: SCRIPT PRINCIPAL — LLAMADA EN FRÍO -->
    <!-- ========================================================= -->
    <div x-show="activeTab === 0" x-transition>

        <!-- Script Flow -->
        <div class="space-y-4">

            <!-- PASO 1: APERTURA (5 seg) -->
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
                <button @click="openStep = openStep === 1 ? 0 : 1" class="w-full flex items-center justify-between p-5 text-left hover:bg-white/[0.02] transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shrink-0 shadow-lg shadow-blue-500/20">
                            <span class="text-sm font-black text-white">1</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-base">Apertura — Engancha en 5 Segundos</h3>
                            <p class="text-xs dark:text-white/40 text-gray-400 mt-0.5">Capta su atención inmediata. Sin rodeos.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-blue-500/10 text-blue-400">5 SEG</span>
                        <svg class="w-5 h-5 dark:text-white/40 text-gray-400 transition-transform" :class="openStep === 1 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </button>
                <div x-show="openStep === 1" x-transition class="px-5 pb-5 border-t dark:border-white/[0.06] border-gray-100">
                    <div class="mt-4 space-y-4">
                        <div class="p-4 rounded-xl dark:bg-nexo-500/5 bg-nexo-50 border dark:border-nexo-500/10 border-nexo-200">
                            <p class="text-[10px] uppercase font-bold text-nexo-400 mb-2 tracking-wide">🎯 Script Textual</p>
                            <p class="text-sm dark:text-white/90 text-gray-800 leading-relaxed">
                                <span class="font-bold text-nexo-400">"Hola [NOMBRE], soy [TU NOMBRE] de [EMPRESA]. Te llamo rápido porque vi tu negocio y tengo algo que puede ayudarte a conseguir más clientes en las próximas semanas. ¿Tienes 30 segundos?"</span>
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="p-3 rounded-xl dark:bg-emerald-500/5 bg-emerald-50 border dark:border-emerald-500/10 border-emerald-200">
                                <p class="text-[10px] uppercase font-bold text-emerald-400 mb-1">✅ Si dice SÍ → Sigue al Paso 2</p>
                                <p class="text-xs dark:text-white/60 text-gray-600">Perfecto, continúa con el pitch.</p>
                            </div>
                            <div class="p-3 rounded-xl dark:bg-amber-500/5 bg-amber-50 border dark:border-amber-500/10 border-amber-200">
                                <p class="text-[10px] uppercase font-bold text-amber-400 mb-1">⚡ Si dice "ESTOY OCUPADO"</p>
                                <p class="text-xs dark:text-white/60 text-gray-600"><span class="font-semibold">"Perfecto, justamente por eso te llamo rápido. Solo 20 segundos y te dejo trabajar."</span> → Sigue al Paso 2.</p>
                            </div>
                        </div>
                        <div class="p-3 rounded-xl dark:bg-red-500/5 bg-red-50 border dark:border-red-500/10 border-red-200">
                            <p class="text-[10px] uppercase font-bold text-red-400 mb-1">🔑 Regla de oro</p>
                            <p class="text-xs dark:text-white/60 text-gray-600">NUNCA empieces con "¿Cómo estás?" ni "¿Tendrás un momento?". Eso grita vendedor. Sé directo, profesional, con energía.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASO 2: PROBLEMA + PROPUESTA DE VALOR (20 seg) -->
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
                <button @click="openStep = openStep === 2 ? 0 : 2" class="w-full flex items-center justify-between p-5 text-left hover:bg-white/[0.02] transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center shrink-0 shadow-lg shadow-amber-500/20">
                            <span class="text-sm font-black text-white">2</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-base">Problema + Propuesta de Valor — 20 Segundos</h3>
                            <p class="text-xs dark:text-white/40 text-gray-400 mt-0.5">Identifica su dolor. Muestra que tienes la solución.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-amber-500/10 text-amber-400">20 SEG</span>
                        <svg class="w-5 h-5 dark:text-white/40 text-gray-400 transition-transform" :class="openStep === 2 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </button>
                <div x-show="openStep === 2" x-transition class="px-5 pb-5 border-t dark:border-white/[0.06] border-gray-100">
                    <div class="mt-4 space-y-4">
                        <div class="p-4 rounded-xl dark:bg-nexo-500/5 bg-nexo-50 border dark:border-nexo-500/10 border-nexo-200">
                            <p class="text-[10px] uppercase font-bold text-nexo-400 mb-2 tracking-wide">🎯 Script Textual</p>
                            <p class="text-sm dark:text-white/90 text-gray-800 leading-relaxed">
                                <span class="font-bold text-nexo-400">"Mira, trabajo con [TIPO DE NEGOCIO] como el tuyo y lo que veo siempre es que [PROBLEMA COMÚN: 'quieren más clientes pero no tienen presencia digital' / 'pierden clientes porque su competencia sí tiene una página profesional' / 'dependen solo del boca a boca']. Nosotros ayudamos a negocios como el tuyo a [RESULTADO: 'aparecer en Google cuando la gente busca lo que tú vendes' / 'captar clientes nuevos todas las semanas por internet']. Ya lo hicimos con [X NEGOCIOS SIMILARES] y los resultados fueron brutales."</span>
                            </p>
                        </div>
                        <div class="p-3 rounded-xl dark:bg-red-500/5 bg-red-50 border dark:border-red-500/10 border-red-200">
                            <p class="text-[10px] uppercase font-bold text-red-400 mb-1">⚠️ IMPORTANTE</p>
                            <p class="text-xs dark:text-white/60 text-gray-600">NO des precios. NO expliques el servicio en detalle. NO digas "página web" ni "marketing digital". Habla solo en términos de RESULTADOS: más clientes, más ventas, más visibilidad. Los detalles son para la reunión.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASO 3: EL CIERRE — AGENDA LA REUNIÓN (30 seg) -->
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
                <button @click="openStep = openStep === 3 ? 0 : 3" class="w-full flex items-center justify-between p-5 text-left hover:bg-white/[0.02] transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center shrink-0 shadow-lg shadow-red-500/20">
                            <span class="text-sm font-black text-white">3</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-base">Cierre — Agenda la Reunión</h3>
                            <p class="text-xs dark:text-white/40 text-gray-400 mt-0.5">EL MOMENTO CLAVE. No pidas permiso. Asume la reunión.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-red-500/10 text-red-400">30 SEG</span>
                        <svg class="w-5 h-5 dark:text-white/40 text-gray-400 transition-transform" :class="openStep === 3 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </button>
                <div x-show="openStep === 3" x-transition class="px-5 pb-5 border-t dark:border-white/[0.06] border-gray-100">
                    <div class="mt-4 space-y-4">
                        <div class="p-4 rounded-xl dark:bg-nexo-500/5 bg-nexo-50 border dark:border-nexo-500/10 border-nexo-200">
                            <p class="text-[10px] uppercase font-bold text-nexo-400 mb-2 tracking-wide">🎯 Script Textual — Técnica del "O/O" (Opción / Opción)</p>
                            <p class="text-sm dark:text-white/90 text-gray-800 leading-relaxed">
                                <span class="font-bold text-nexo-400">"Mira, lo que quiero es mostrarte exactamente cómo podemos hacer esto para tu negocio. Te toma solo 15 minutos y sin compromiso. ¿Te queda mejor el martes a las 10 de la mañana o el miércoles a las 3 de la tarde?"</span>
                            </p>
                        </div>
                        <div class="p-4 rounded-xl dark:bg-emerald-500/10 bg-emerald-50 border dark:border-emerald-500/20 border-emerald-200">
                            <p class="text-[10px] uppercase font-bold text-emerald-400 mb-2 tracking-wide">🔥 Frases de Cierre Alternativas</p>
                            <ul class="space-y-2 text-xs dark:text-white/70 text-gray-700">
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-400 font-bold shrink-0">→</span>
                                    <span><strong>"¿Cuándo es tu día menos caótico, lunes o jueves?"</strong> — Funciona porque acepta que está ocupado.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-400 font-bold shrink-0">→</span>
                                    <span><strong>"Son solo 15 minutitos. ¿Te sirve mañana temprano u otro día esta semana?"</strong> — Minimiza el compromiso.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-400 font-bold shrink-0">→</span>
                                    <span><strong>"Te propongo algo: nos vemos 15 minutos, te muestro los resultados que tuvimos con [negocio similar], y si no te interesa me voy sin presión. ¿Martes o jueves?"</strong> — Elimina el riesgo.</span>
                                </li>
                            </ul>
                        </div>
                        <div class="p-3 rounded-xl dark:bg-red-500/5 bg-red-50 border dark:border-red-500/10 border-red-200">
                            <p class="text-[10px] uppercase font-bold text-red-400 mb-1">🚫 NUNCA DIGAS ESTO</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs dark:text-white/60 text-gray-600">
                                <div><span class="line-through opacity-50">"¿Cuándo te queda bien?"</span> — Muy abierto, te dice "ya te llamo".</div>
                                <div><span class="line-through opacity-50">"¿Estarías interesado?"</span> — Invita a decir NO.</div>
                                <div><span class="line-through opacity-50">"¿Qué opinas?"</span> — Le das espacio para escapar.</div>
                                <div><span class="line-through opacity-50">"Te mando info por email"</span> — Nunca la van a leer.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASO 4: CONFIRMAR Y COLGAR -->
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
                <button @click="openStep = openStep === 4 ? 0 : 4" class="w-full flex items-center justify-between p-5 text-left hover:bg-white/[0.02] transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center shrink-0 shadow-lg shadow-emerald-500/20">
                            <span class="text-sm font-black text-white">4</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-base">Confirmar + Colgar Rápido</h3>
                            <p class="text-xs dark:text-white/40 text-gray-400 mt-0.5">Confirma, agradece y cuelga. No sigas hablando.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-400">15 SEG</span>
                        <svg class="w-5 h-5 dark:text-white/40 text-gray-400 transition-transform" :class="openStep === 4 && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </button>
                <div x-show="openStep === 4" x-transition class="px-5 pb-5 border-t dark:border-white/[0.06] border-gray-100">
                    <div class="mt-4 space-y-4">
                        <div class="p-4 rounded-xl dark:bg-nexo-500/5 bg-nexo-50 border dark:border-nexo-500/10 border-nexo-200">
                            <p class="text-[10px] uppercase font-bold text-nexo-400 mb-2 tracking-wide">🎯 Script Textual</p>
                            <p class="text-sm dark:text-white/90 text-gray-800 leading-relaxed">
                                <span class="font-bold text-nexo-400">"Perfecto, entonces quedamos el [DÍA] a las [HORA]. Te voy a enviar un mensajito con la confirmación para que lo tengas. ¡Gracias [NOMBRE], hablamos ese día!"</span>
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="p-3 rounded-xl dark:bg-emerald-500/5 bg-emerald-50 border dark:border-emerald-500/10 border-emerald-200">
                                <p class="text-[10px] uppercase font-bold text-emerald-400 mb-1">✅ Inmediatamente después</p>
                                <ol class="text-xs dark:text-white/60 text-gray-600 space-y-1 list-decimal list-inside">
                                    <li>Agrega el evento al <strong>Calendario del CRM</strong></li>
                                    <li>Envía WhatsApp: <em>"Hola [NOMBRE], soy [TU NOMBRE]. Confirmado nuestra reunión el [DÍA] a las [HORA]. ¡Nos vemos!"</em></li>
                                    <li>Actualiza el estado del cliente a <strong>Contactado</strong></li>
                                    <li>Marca +1 en <strong>Llamadas</strong> y <strong>Seguimientos</strong> de tus Metas Diarias</li>
                                </ol>
                            </div>
                            <div class="p-3 rounded-xl dark:bg-amber-500/5 bg-amber-50 border dark:border-amber-500/10 border-amber-200">
                                <p class="text-[10px] uppercase font-bold text-amber-400 mb-1">⚡ Un día antes de la reunión</p>
                                <p class="text-xs dark:text-white/60 text-gray-600">
                                    Envía recordatorio: <strong>"Hola [NOMBRE], solo confirmando nuestra reunión mañana a las [HORA]. ¿Todo bien? 👍"</strong> — Reduce no-shows un 40%.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ========================================================= -->
    <!-- TAB 1: ROMPER OBJECIONES -->
    <!-- ========================================================= -->
    <div x-show="activeTab === 1" x-transition>
        <div class="space-y-4">

            <!-- Intro -->
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-red-500/15 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg">Rompedor de Objeciones</h3>
                        <p class="text-xs dark:text-white/40 text-gray-400">Las objeciones NO son un NO. Son una señal de que necesitan más confianza. Cada objeción tiene una respuesta. Memorízalas.</p>
                    </div>
                </div>
            </div>

            <!-- Objection cards -->
            <template x-for="(obj, idx) in objections" :key="idx">
                <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 overflow-hidden">
                    <button @click="openObj = openObj === idx ? -1 : idx" class="w-full flex items-center justify-between p-5 text-left hover:bg-white/[0.02] transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" :class="obj.color">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase font-bold dark:text-white/30 text-gray-400 tracking-wide">El cliente dice:</p>
                                <h3 class="font-bold" x-text="obj.objection"></h3>
                            </div>
                        </div>
                        <svg class="w-5 h-5 dark:text-white/40 text-gray-400 transition-transform shrink-0" :class="openObj === idx && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openObj === idx" x-transition class="px-5 pb-5 border-t dark:border-white/[0.06] border-gray-100">
                        <div class="mt-4 space-y-3">
                            <div class="p-4 rounded-xl dark:bg-nexo-500/5 bg-nexo-50 border dark:border-nexo-500/10 border-nexo-200">
                                <p class="text-[10px] uppercase font-bold text-nexo-400 mb-2 tracking-wide">🔥 Tu respuesta:</p>
                                <p class="text-sm dark:text-white/90 text-gray-800 leading-relaxed font-bold text-nexo-400" x-text="obj.response"></p>
                            </div>
                            <div class="p-3 rounded-xl dark:bg-white/[0.02] bg-gray-50">
                                <p class="text-[10px] uppercase font-bold dark:text-white/30 text-gray-400 mb-1">💡 Por qué funciona:</p>
                                <p class="text-xs dark:text-white/60 text-gray-600" x-text="obj.why"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- TAB 2: MENTALIDAD + REGLAS DE ORO -->
    <!-- ========================================================= -->
    <div x-show="activeTab === 2" x-transition>
        <div class="space-y-4">

            <!-- Mindset banner -->
            <div class="dark:bg-gradient-to-r dark:from-red-900/30 dark:to-dark-800 bg-gradient-to-r from-red-50 to-white rounded-2xl border dark:border-red-500/20 border-red-200 p-6">
                <h3 class="text-xl font-black mb-2">🧠 Mentalidad de un Closer</h3>
                <p class="text-sm dark:text-white/60 text-gray-600 max-w-2xl">No estás pidiendo favores. Estás <strong class="text-red-400">ofreciendo una oportunidad</strong> que les va a generar dinero. Si te dicen que no, el que pierde es el cliente, no tú. Tu trabajo es hacer tantas llamadas que los números jueguen a tu favor.</p>
            </div>

            <!-- Numbers game -->
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
                <h3 class="font-bold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    El Juego de los Números
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <div class="text-center p-4 rounded-xl dark:bg-white/[0.03] bg-gray-50 border dark:border-white/[0.04] border-gray-100">
                        <p class="text-2xl font-black text-nexo-400">25</p>
                        <p class="text-[10px] dark:text-white/40 text-gray-400 mt-1">Llamadas diarias</p>
                    </div>
                    <div class="flex items-center justify-center"><svg class="w-6 h-6 dark:text-white/20 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></div>
                    <div class="text-center p-4 rounded-xl dark:bg-white/[0.03] bg-gray-50 border dark:border-white/[0.04] border-gray-100">
                        <p class="text-2xl font-black text-amber-400">15</p>
                        <p class="text-[10px] dark:text-white/40 text-gray-400 mt-1">Contestan</p>
                    </div>
                    <div class="flex items-center justify-center"><svg class="w-6 h-6 dark:text-white/20 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></div>
                    <div class="text-center p-4 rounded-xl dark:bg-emerald-500/5 bg-emerald-50 border dark:border-emerald-500/20 border-emerald-200">
                        <p class="text-2xl font-black text-emerald-400">3-5</p>
                        <p class="text-[10px] dark:text-white/40 text-gray-400 mt-1">Reuniones agendadas</p>
                    </div>
                </div>
                <p class="text-xs dark:text-white/40 text-gray-400 mt-3 text-center">25 llamadas × 5 días = 125/semana → <strong class="text-emerald-400">15-25 reuniones por semana</strong>. Si cierras el 30%, son <strong class="text-emerald-400">5-8 ventas semanales</strong>.</p>
            </div>

            <!-- Golden Rules -->
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
                <h3 class="font-bold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    10 Reglas de Oro del Vendedor
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <?php
                    $rules = [
                        ['La llamada es para AGENDAR, no para VENDER.', 'bg-red-500/10 text-red-400 dark:border-red-500/20 border-red-200'],
                        ['Siempre da 2 opciones de horario. Nunca preguntes "cuándo puedes".', 'bg-amber-500/10 text-amber-400 dark:border-amber-500/20 border-amber-200'],
                        ['Habla solo de RESULTADOS. No menciones el producto.', 'bg-nexo-500/10 text-nexo-400 dark:border-nexo-500/20 border-nexo-200'],
                        ['La energía se contagia. Si suenas aburrido, cuelgan.', 'bg-blue-500/10 text-blue-400 dark:border-blue-500/20 border-blue-200'],
                        ['Cada objeción es OportunidAD. nunca aceptar el primer NO.', 'bg-emerald-500/10 text-emerald-400 dark:border-emerald-500/20 border-emerald-200'],
                        ['Después de pedir la reunión: CÁLLATE. El primero que habla pierde.', 'bg-red-500/10 text-red-400 dark:border-red-500/20 border-red-200'],
                        ['"Envíame un email" = NO. Siempre redirige a la reunión.', 'bg-amber-500/10 text-amber-400 dark:border-amber-500/20 border-amber-200'],
                        ['25 llamadas diarias es tu MÍNIMO. No negocies contigo mismo.', 'bg-nexo-500/10 text-nexo-400 dark:border-nexo-500/20 border-nexo-200'],
                        ['Confirma reunión por WhatsApp en los primeros 2 minutos.', 'bg-blue-500/10 text-blue-400 dark:border-blue-500/20 border-blue-200'],
                        ['Si cuelgan, pasa al siguiente. No te lo tomes personal.', 'bg-emerald-500/10 text-emerald-400 dark:border-emerald-500/20 border-emerald-200'],
                    ];
                    foreach ($rules as $i => $rule): ?>
                    <div class="flex items-start gap-3 p-3 rounded-xl border <?php echo $rule[1]; ?>">
                        <span class="text-sm font-black w-6 h-6 rounded-lg flex items-center justify-center dark:bg-white/5 bg-white shrink-0"><?php echo $i + 1; ?></span>
                        <p class="text-xs font-semibold"><?php echo $rule[0]; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Schedule template -->
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
                <h3 class="font-bold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Rutina Diaria del Vendedor Agresivo
                </h3>
                <div class="space-y-2">
                    <?php
                    $schedule = [
                        ['08:30 - 09:00', 'Preparación', 'Revisar lista de leads, CRM, preparar guiones personalizados', 'bg-blue-500/10 text-blue-400'],
                        ['09:00 - 11:00', 'Bloque de Llamadas #1', '12-15 llamadas seguidas. Sin parar. Sin distracciones.', 'bg-red-500/10 text-red-400'],
                        ['11:00 - 11:30', 'Follow-ups', 'Enviar WhatsApps de confirmación, actualizar CRM', 'bg-amber-500/10 text-amber-400'],
                        ['11:30 - 12:30', 'Reuniones', 'Realizar reuniones agendadas (la venta real ocurre aquí)', 'bg-emerald-500/10 text-emerald-400'],
                        ['14:00 - 16:00', 'Bloque de Llamadas #2', '10-13 llamadas más. Cerrar el día fuerte.', 'bg-red-500/10 text-red-400'],
                        ['16:00 - 17:00', 'Más reuniones + cierre día', 'Reuniones, propuestas, actualizar pipeline, completar metas', 'bg-nexo-500/10 text-nexo-400'],
                    ];
                    foreach ($schedule as $item): ?>
                    <div class="flex items-start gap-4 p-3 rounded-xl dark:bg-white/[0.02] bg-gray-50">
                        <div class="text-right shrink-0 w-28">
                            <span class="text-xs font-bold dark:text-white/80 text-gray-700"><?php echo $item[0]; ?></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full <?php echo $item[3]; ?>"><?php echo $item[1]; ?></span>
                            <p class="text-xs dark:text-white/50 text-gray-500 mt-1"><?php echo $item[2]; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- TAB 3: SEGUIMIENTO POR WHATSAPP -->
    <!-- ========================================================= -->
    <div x-show="activeTab === 3" x-transition>
        <div class="space-y-4">

            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-green-500/15 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-400" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18a8 8 0 01-4.243-1.216l-.256-.16-2.876.855.804-2.876-.16-.256A8 8 0 1112 20z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg">Templates de WhatsApp</h3>
                        <p class="text-xs dark:text-white/40 text-gray-400">Mensajes pre-escritos para cada situación. Copia, personaliza y envía.</p>
                    </div>
                </div>
            </div>

            <!-- WhatsApp templates -->
            <?php
            $templates = [
                ['label' => 'Confirmación después de la llamada', 'emoji' => '📞', 'color' => 'emerald',
                 'msg' => "Hola [NOMBRE] 👋, soy [TU NOMBRE] de [EMPRESA]. Como quedamos, te confirmo nuestra reunión para el [DÍA] a las [HORA]. Va a ser algo rápido de 15 min donde te voy a mostrar cómo podemos ayudar a [SU NEGOCIO] a captar más clientes. ¡Nos vemos! 🚀"],
                ['label' => 'Recordatorio 1 día antes', 'emoji' => '🔔', 'color' => 'amber',
                 'msg' => "Hola [NOMBRE]! 👋 Solo quería confirmarte nuestra reunión de mañana [DÍA] a las [HORA]. ¿Todo bien para esa hora? 👍"],
                ['label' => 'No contestó la llamada', 'emoji' => '📱', 'color' => 'blue',
                 'msg' => "Hola [NOMBRE], soy [TU NOMBRE] de [EMPRESA]. Intenté llamarte pero no pude comunicarme. Te escribo porque vi tu negocio [NOMBRE DEL NEGOCIO] y tenemos algo que puede ayudarte a conseguir más clientes esta semana. ¿Tienes 2 minutos para hablar mañana por la mañana? 🙌"],
                ['label' => 'Follow-up después de reunión sin cierre', 'emoji' => '🔄', 'color' => 'nexo',
                 'msg' => "Hola [NOMBRE]! Fue un gusto hablar contigo hoy. Como te mencioné, los negocios como el tuyo que ya implementaron esto están viendo resultados en las primeras semanas. Te dejo pensarlo hoy, pero sinceramente te recomiendo que no lo dejes pasar porque los espacios son limitados. ¿Qué dices, arrancamos esta semana? 💪"],
                ['label' => 'Prospecto frío que no respondió', 'emoji' => '❄️', 'color' => 'red',
                 'msg' => "Hola [NOMBRE], soy [TU NOMBRE]. Te escribí hace unos días sobre una oportunidad para [SU NEGOCIO]. Entiendo que estás ocupado, por eso solo te pido 15 minutos esta semana. Si después de esos 15 minutos no te interesa, lo respeto 100%. ¿Te queda mejor martes o jueves? 📅"],
                ['label' => 'Recuperar reunión cancelada / no-show', 'emoji' => '⚡', 'color' => 'amber',
                 'msg' => "Hey [NOMBRE]! Vi que no pudimos conectarnos hoy. Sin problema, sé que las cosas se ponen locas. ¿Reagendamos para mañana a la misma hora o prefieres otra? Solo son 15 minutos y te prometo que va a valer la pena 🔥"],
            ];
            foreach ($templates as $tpl): 
                $c = $tpl['color'];
                $borderClass = "dark:border-{$c}-500/20 border-{$c}-200";
                $bgClass = "dark:bg-{$c}-500/5 bg-{$c}-50";
                $textClass = "text-{$c}-400";
            ?>
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-bold text-sm flex items-center gap-2">
                        <span><?php echo $tpl['emoji']; ?></span>
                        <?php echo $tpl['label']; ?>
                    </h4>
                    <button onclick="copyTemplate(this)" class="text-xs px-3 py-1.5 rounded-lg dark:bg-white/5 bg-gray-100 dark:hover:bg-white/10 hover:bg-gray-200 transition-colors dark:text-white/50 text-gray-500 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        Copiar
                    </button>
                </div>
                <div class="p-4 rounded-xl dark:bg-green-900/10 bg-green-50 border dark:border-green-500/10 border-green-200 relative">
                    <div class="absolute top-2 right-2 w-5 h-5 rounded-full bg-green-500/20 flex items-center justify-center">
                        <svg class="w-3 h-3 text-green-400" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                    </div>
                    <p class="text-sm dark:text-white/80 text-gray-700 leading-relaxed template-text"><?php echo $tpl['msg']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- TAB 4: CHECKLIST PRÉ-LLAMADA -->
    <!-- ========================================================= -->
    <div x-show="activeTab === 4" x-transition>
        <div class="space-y-4">

            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
                <h3 class="font-bold text-lg mb-1 flex items-center gap-2">
                    <svg class="w-5 h-5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Checklist Antes de Llamar
                </h3>
                <p class="text-xs dark:text-white/40 text-gray-400 mb-5">Completa esto antes de cada bloque de llamadas.</p>

                <div class="space-y-3">
                    <?php
                    $checklist = [
                        ['¿Tienes tu lista de al menos 25 leads cargados en el CRM?', 'Sin leads no hay llamadas. Usa el LeadScraper para cargar antes de empezar.'],
                        ['¿Tienes tu guión listo y personalizado para el nicho de hoy?', 'Adapta el problema y la propuesta de valor al tipo de negocio.'],
                        ['¿Tienes tu calendario abierto con los horarios disponibles?', 'Nunca digas "déjame ver". Ten los slots listos.'],
                        ['¿Estás en un lugar sin distracciones?', 'Cierra WhatsApp Web, redes sociales, notificaciones.'],
                        ['¿Tu tono de voz está listo? (Energético, seguro, sonriendo)', 'Haz 2-3 llamadas de calentamiento. Sonríe cuando hablas, se nota.'],
                        ['¿Tienes agua y estás cómodo?', 'Vas a hablar 2 horas seguidas. Cuida tu voz.'],
                        ['¿Tu meta del día está clara? (X llamadas, X reuniones)', 'Anota la meta en un papel y ponlo donde lo veas.'],
                    ];
                    foreach ($checklist as $i => $item): ?>
                    <label class="flex items-start gap-3 p-3 rounded-xl dark:bg-white/[0.02] bg-gray-50 border dark:border-white/[0.04] border-gray-100 cursor-pointer hover:dark:border-nexo-500/20 hover:border-nexo-200 transition-colors group">
                        <div class="mt-0.5 shrink-0">
                            <input type="checkbox" class="w-5 h-5 rounded border-gray-300 text-nexo-500 focus:ring-nexo-500">
                        </div>
                        <div>
                            <p class="text-sm font-semibold group-hover:text-nexo-400 transition-colors"><?php echo $item[0]; ?></p>
                            <p class="text-xs dark:text-white/40 text-gray-400 mt-0.5"><?php echo $item[1]; ?></p>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Reference Card -->
            <div class="dark:bg-dark-800 bg-white rounded-2xl border dark:border-white/[0.06] border-gray-200 p-5">
                <h3 class="font-bold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Tarjeta de Referencia Rápida
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl dark:bg-blue-500/5 bg-blue-50 border dark:border-blue-500/10 border-blue-200">
                        <p class="text-xs font-bold text-blue-400 mb-2">🔊 PALABRAS DE PODER</p>
                        <div class="flex flex-wrap gap-1.5">
                            <?php foreach (['Resultados', 'Garantizado', 'Rápido', 'Probado', 'Gratis', 'Sin compromiso', 'Exclusivo', 'Limitado', 'Ahora', 'Ya lo hicimos'] as $word): ?>
                            <span class="text-[10px] font-bold px-2 py-1 rounded-lg dark:bg-blue-500/10 bg-blue-100 text-blue-400"><?php echo $word; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="p-4 rounded-xl dark:bg-red-500/5 bg-red-50 border dark:border-red-500/10 border-red-200">
                        <p class="text-xs font-bold text-red-400 mb-2">🚫 PALABRAS PROHIBIDAS</p>
                        <div class="flex flex-wrap gap-1.5">
                            <?php foreach (['Comprar', 'Precio', 'Costo', 'Contrato', 'Vender', 'Barato', 'Inversión', 'Pagar', 'Marketing', 'Página web'] as $word): ?>
                            <span class="text-[10px] font-bold px-2 py-1 rounded-lg dark:bg-red-500/10 bg-red-100 text-red-400 line-through"><?php echo $word; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
</main>

<script>
function scriptsApp() {
    return {
        tabs: [<?php echo json_encode(__('scr_principal')); ?>, <?php echo json_encode(__('scr_objeciones')); ?>, <?php echo json_encode(__('scr_mentalidad')); ?>, <?php echo json_encode(__('scr_whatsapp')); ?>, <?php echo json_encode(__('scr_checklist')); ?>],
        activeTab: 0,
        openStep: 1,
        openObj: -1,
        objections: [
            {
                objection: '"No tengo tiempo ahora"',
                response: '"Justamente por eso te llamo, porque sé que estás ocupado. Son literalmente 15 minutos. ¿Te queda mejor el martes a las 10 o el jueves a las 3? Elegís vos."',
                why: 'No le pides permiso. Reconoces que está ocupado (empatía) y das opciones específicas. Su cerebro pasa de decidir SI/NO a decidir CUÁNDO.',
                color: 'bg-red-500/15 text-red-400'
            },
            {
                objection: '"Ya tenemos quien nos hace eso"',
                response: '"Genial, eso me dice que ya sabes lo importante que es. Justamente trabajo con negocios que ya tienen proveedor y les muestro una comparación en 15 minutos. Si lo que tienes ya es perfecto, te digo y no pierdes nada. ¿Martes o miércoles?"',
                why: 'No atacas al competidor. Posicionas la reunión como una comparación sin riesgo. La curiosidad les gana.',
                color: 'bg-amber-500/15 text-amber-400'
            },
            {
                objection: '"Envíame la información por email"',
                response: '"Claro, te la puedo mandar, pero honestamente en un email se pierde mucho y no puedo personalizarla para tu negocio. Dame 15 minutos y te muestro específicamente cómo aplica a [SU NEGOCIO]. ¿Te sirve mañana en la mañana o en la tarde?"',
                why: 'El email es un cementerio de propuestas. Nunca aceptes esto. Redirige siempre a la reunión.',
                color: 'bg-blue-500/15 text-blue-400'
            },
            {
                objection: '"No tengo presupuesto"',
                response: '"Totalmente entiendo, y no te estoy pidiendo que compres nada hoy. Solo quiero mostrarte cómo otros negocios como el tuyo están generando más ingresos con poco. Si te sirve perfecto, si no, al menos te llevas ideas gratis. ¿Mañana a las 10 o pasado a las 2?"',
                why: 'Eliminas la presión de compra. La reunión se convierte en una consultoría gratuita. Nadie dice no a algo gratis.',
                color: 'bg-emerald-500/15 text-emerald-400'
            },
            {
                objection: '"Déjame pensarlo"',
                response: '"Perfecto, me parece bien. Solo una cosa — entre tú y yo, cuando alguien me dice déjame pensarlo normalmente hay algo que no le convenció. ¿Qué es lo que te frena? Así lo resolvemos rápido."',
                why: 'Confrontas la objeción real que está escondida. "Déjame pensarlo" nunca es la verdadera razón. Descubre qué hay detrás.',
                color: 'bg-nexo-500/15 text-nexo-400'
            },
            {
                objection: '"No me interesa"',
                response: '"Lo respeto. Solo una pregunta rápida: ¿no te interesa tener más clientes o no te interesa la llamada? Porque si es la llamada lo entiendo, pero los resultados que hemos logrado con negocios como el tuyo son bastante impresionantes. Dame 15 minutos esta semana, si no te convence, me voy y no te molesto más."',
                why: 'Separas el "no" genérico de la realidad. Pocas personas dirán "no me interesa tener más clientes". Les atrapas en su propia lógica.',
                color: 'bg-red-500/15 text-red-400'
            },
            {
                objection: '"¿Cuánto cuesta?"',
                response: '"Depende de lo que necesite tu negocio, por eso quiero sentarme contigo 15 minutos y hacerte una propuesta personalizada. No es lo mismo una cosa que otra. ¿El miércoles a las 11 o el viernes a las 9?"',
                why: 'NUNCA des precio por teléfono. El precio sin contexto siempre suena caro. La reunión justifica el valor.',
                color: 'bg-amber-500/15 text-amber-400'
            },
            {
                objection: '"Llámame la próxima semana"',
                response: '"Claro, puedo hacer eso. Pero mira, la próxima semana vas a estar igual de ocupado. Mejor agendamos ahora 15 minutos y así ya queda hecho. ¿Te va mejor lunes o martes de la semana que viene?"',
                why: 'Aceptas pero conviertes el "llámame" en agendar ahora. La próxima semana nunca llega si no la agendas hoy.',
                color: 'bg-blue-500/15 text-blue-400'
            },
            {
                objection: '"Mi socio decide eso"',
                response: '"Perfecto, entonces agendamos una reunión rápida los tres juntos. Así tu socio también ve los resultados. ¿Cuándo estarían los dos disponibles, martes o jueves?"',
                why: 'No aceptas el filtro. Incluyes al socio en la reunión. Nunca dejes que un intermediario decida por ti.',
                color: 'bg-emerald-500/15 text-emerald-400'
            },
            {
                objection: '"Ya fracasé con algo parecido antes"',
                response: '"Lo entiendo, y es una lástima que te haya pasado eso. Justamente por eso quiero mostrarte qué hacemos diferente y por qué nuestros clientes sí ven resultados. Son 15 minutos donde te muestro casos reales, no promesas. ¿Jueves a las 10?"',
                why: 'Validas su frustración, no la invalidas. Te diferencias del que le falló. La reunión se convierte en demostrar pruebas reales.',
                color: 'bg-nexo-500/15 text-nexo-400'
            },
        ]
    };
}

function copyTemplate(btn) {
    const text = btn.closest('.p-5').querySelector('.template-text').innerText;
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> <span class="text-emerald-400">Copiado!</span>';
        setTimeout(() => btn.innerHTML = orig, 2000);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
