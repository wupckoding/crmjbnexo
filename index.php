<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

// Load global config for dynamic branding
require_once 'config/database.php';
$_gcRows = $pdo->query("SELECT clave, valor FROM configuracion_global")->fetchAll(PDO::FETCH_ASSOC);
$_gc = [];
foreach ($_gcRows as $_gcR) $_gc[$_gcR['clave']] = $_gcR['valor'];
$_empresaNombre = $_gc['empresa_nombre'] ?? 'NEXO';
$_empresaLogo   = $_gc['logo_url'] ?? '';
$_initials = mb_strtoupper(mb_substr($_empresaNombre, 0, 2));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#09090b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="CRM <?php echo htmlspecialchars($_empresaNombre, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="description" content="CRM profesional de <?php echo htmlspecialchars($_empresaNombre, ENT_QUOTES, 'UTF-8'); ?> - Gestión de clientes y ventas">
    
    <!-- PWA -->
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
    
    <title>Iniciar Sesión | CRM <?php echo htmlspecialchars($_empresaNombre, ENT_QUOTES, 'UTF-8'); ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nexo: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95',
                            950: '#2e1065',
                        },
                        dark: {
                            950: '#09090b',
                            900: '#0c0a14',
                            800: '#12101c',
                            700: '#1a1726',
                            600: '#221e30',
                            500: '#2a253b',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- GSAP -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
    
    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #09090b;
            overflow: hidden;
        }
        .gsap-hidden { opacity: 0; }
        @keyframes gridMove {
            from { transform: translate(0, 0); }
            to { transform: translate(60px, 60px); }
        }
        @keyframes gridMove2 {
            from { transform: translate(0, 0); }
            to { transform: translate(-120px, -120px); }
        }
        @keyframes float2 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -25px) scale(1.05); }
            66% { transform: translate(-20px, 15px) scale(0.95); }
        }
        @keyframes float3 {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-40px, 30px); }
        }
    </style>
</head>
<body class="min-h-screen bg-dark-950 text-white antialiased">

    <!-- ==========================================
         WAVE TEXTURE BACKGROUND (como no site jbnexo.com)
         ========================================== -->
    <div class="wave-bg">
        <!-- Wave layer 1 -->
        <svg class="wave-layer-1 opacity-[0.07]" viewBox="0 0 1440 900" preserveAspectRatio="none">
            <path d="M0,300 C120,250 240,350 360,300 C480,250 600,200 720,280 C840,360 960,250 1080,300 C1200,350 1320,280 1440,300 L1440,0 L0,0 Z" fill="none" stroke="rgba(124,58,237,0.5)" stroke-width="1.5"/>
            <path d="M0,400 C160,350 320,450 480,380 C640,310 800,420 960,370 C1120,320 1280,400 1440,360 L1440,0 L0,0 Z" fill="none" stroke="rgba(124,58,237,0.3)" stroke-width="1"/>
            <path d="M0,500 C200,450 400,550 600,480 C800,410 1000,520 1200,470 C1300,440 1380,490 1440,460" fill="none" stroke="rgba(124,58,237,0.4)" stroke-width="1.2"/>
            <path d="M0,200 C180,170 360,230 540,190 C720,150 900,230 1080,200 C1260,170 1380,210 1440,190" fill="none" stroke="rgba(124,58,237,0.25)" stroke-width="0.8"/>
        </svg>

        <!-- Wave layer 2 -->
        <svg class="wave-layer-2 opacity-[0.08]" viewBox="0 0 1440 900" preserveAspectRatio="none">
            <path d="M0,350 C100,300 200,380 360,330 C520,280 680,370 840,320 C1000,270 1160,350 1320,310 C1380,290 1440,320 1440,320" fill="none" stroke="rgba(167,139,250,0.4)" stroke-width="1.5"/>
            <path d="M0,450 C140,400 280,480 420,430 C560,380 700,460 840,420 C980,380 1120,440 1260,410 C1360,390 1440,420 1440,420" fill="none" stroke="rgba(167,139,250,0.3)" stroke-width="1"/>
            <path d="M0,550 C180,510 360,580 540,540 C720,500 900,570 1080,530 C1200,505 1340,550 1440,530" fill="none" stroke="rgba(167,139,250,0.25)" stroke-width="0.8"/>
            <path d="M0,250 C160,220 320,270 480,240 C640,210 800,260 960,235 C1120,210 1280,250 1440,230" fill="none" stroke="rgba(167,139,250,0.2)" stroke-width="0.6"/>
        </svg>

        <!-- Wave layer 3 - mais densa -->
        <svg class="wave-layer-3 opacity-[0.06]" viewBox="0 0 1440 900" preserveAspectRatio="none">
            <path d="M0,320 C180,280 360,370 540,310 C720,260 900,350 1080,300 C1260,255 1380,320 1440,290" fill="none" stroke="rgba(192,132,252,0.5)" stroke-width="2"/>
            <path d="M0,420 C120,380 240,440 420,400 C600,360 780,430 960,390 C1140,350 1300,410 1440,380" fill="none" stroke="rgba(192,132,252,0.3)" stroke-width="1"/>
            <path d="M0,520 C200,490 400,540 600,510 C800,480 1000,530 1200,500 C1340,480 1440,510 1440,510" fill="none" stroke="rgba(192,132,252,0.2)" stroke-width="0.8"/>
        </svg>
    </div>

    <!-- Animated grid texture -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
        <div class="absolute inset-0" style="background-image: linear-gradient(rgba(124,58,237,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(124,58,237,0.04) 1px, transparent 1px); background-size: 60px 60px; animation: gridMove 25s linear infinite;"></div>
        <div class="absolute inset-0" style="background-image: linear-gradient(rgba(167,139,250,0.025) 1px, transparent 1px), linear-gradient(90deg, rgba(167,139,250,0.025) 1px, transparent 1px); background-size: 120px 120px; background-position: 30px 30px; animation: gridMove2 35s linear infinite;"></div>
    </div>

    <!-- Ambient radial glows -->
    <div class="fixed inset-0 pointer-events-none z-0">
        <div class="orb-float-1 absolute -top-20 -right-20 w-[500px] h-[500px] bg-nexo-600/[0.09] rounded-full blur-[120px]"></div>
        <div class="orb-float-2 absolute bottom-0 -left-32 w-[400px] h-[400px] bg-nexo-500/[0.07] rounded-full blur-[100px]"></div>
        <div class="orb-float-3 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-nexo-700/[0.05] rounded-full blur-[140px]"></div>
        <div class="absolute top-1/4 right-1/4 w-[300px] h-[300px] bg-nexo-400/[0.03] rounded-full blur-[100px]" style="animation: float3 20s ease-in-out infinite;"></div>
        <div class="absolute bottom-1/4 left-1/3 w-[350px] h-[350px] bg-purple-500/[0.04] rounded-full blur-[110px]" style="animation: float2 18s ease-in-out infinite;"></div>
    </div>

    <!-- Particle background -->
    <div id="particles" class="fixed inset-0 pointer-events-none z-0"></div>

    <!-- ==========================================
         MAIN LOGIN CARD
         ========================================== -->
    <div class="relative z-10 min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8">
        
        <div id="loginCard" class="glass-card glow-purple rounded-3xl w-full max-w-[960px] min-h-[600px] grid grid-cols-1 lg:grid-cols-2 overflow-hidden gsap-hidden">
            
            <!-- ========================
                 LEFT - Visual Side
                 ======================== -->
            <div class="relative hidden lg:flex flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-dark-900/50 to-transparent p-10">
                
                <!-- Animated wave lines inside card -->
                <svg class="absolute inset-0 w-full h-full opacity-[0.08] wave-layer-1" viewBox="0 0 480 600" preserveAspectRatio="none">
                    <path d="M0,150 C60,120 120,180 180,150 C240,120 300,170 360,140 C420,110 480,160 480,140" fill="none" stroke="#7c3aed" stroke-width="1.5"/>
                    <path d="M0,200 C80,170 160,230 240,195 C320,160 400,210 480,185" fill="none" stroke="#a78bfa" stroke-width="1"/>
                    <path d="M0,260 C60,235 120,280 200,255 C280,230 360,275 480,245" fill="none" stroke="#7c3aed" stroke-width="1.2"/>
                    <path d="M0,320 C90,290 180,340 270,310 C360,280 420,330 480,305" fill="none" stroke="#a78bfa" stroke-width="0.8"/>
                    <path d="M0,380 C70,355 140,400 240,370 C340,340 400,390 480,365" fill="none" stroke="#c084fc" stroke-width="1"/>
                    <path d="M0,440 C100,415 200,460 300,430 C380,408 440,450 480,430" fill="none" stroke="#7c3aed" stroke-width="0.8"/>
                    <path d="M0,500 C80,480 160,520 260,495 C360,470 420,510 480,490" fill="none" stroke="#a78bfa" stroke-width="0.6"/>
                </svg>

                <!-- Orbs decorativos -->
                <div class="orb-float-1 absolute top-16 left-8 w-32 h-32 bg-nexo-600/10 rounded-full blur-2xl"></div>
                <div class="orb-float-2 absolute bottom-20 right-8 w-24 h-24 bg-nexo-500/8 rounded-full blur-xl"></div>

                <!-- Purple dot indicator (como no site) -->
                <div class="absolute top-6 left-6 flex items-center gap-3 z-10">
                    <div class="relative">
                        <div class="w-3 h-3 bg-nexo-600 rounded-full"></div>
                        <div class="absolute inset-0 w-3 h-3 bg-nexo-500 rounded-full dot-ping"></div>
                    </div>
                </div>

                <!-- Central content -->
                <div class="relative z-10 text-center">
                    <!-- Badge -->
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-white/10 bg-white/[0.03] mb-8">
                        <svg class="w-4 h-4 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z"/>
                        </svg>
                        <span class="text-xs font-medium text-white/60 tracking-wide uppercase">CRM Profesional</span>
                    </div>

                    <!-- Big headline -->
                    <h2 class="text-4xl font-black text-white leading-tight mb-2 tracking-tight">
                        GESTIONA TUS
                    </h2>
                    <h2 class="text-4xl font-black leading-tight mb-6 tracking-tight text-gradient-animated">
                        VENTAS DIGITALES
                    </h2>

                    <p class="text-sm text-white/40 max-w-[280px] mx-auto leading-relaxed">
                        Controla clientes, cotizaciones y el pipeline de ventas de tu agencia desde un solo lugar.
                    </p>
                </div>

                <!-- Bottom decorative line -->
                <div class="absolute bottom-8 left-1/2 -translate-x-1/2 w-8 h-8 flex items-center justify-center">
                    <div class="w-1.5 h-1.5 bg-nexo-500/50 rounded-full"></div>
                </div>

                <!-- Vignette -->
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-transparent to-dark-900/60"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-dark-900/40 via-transparent to-dark-900/40"></div>
            </div>
            
            <!-- ========================
                 RIGHT - Login Form
                 ======================== -->
            <div class="relative flex flex-col justify-center px-8 sm:px-12 lg:px-14 py-12">
                
                <!-- Logo Dynamic -->
                <div id="logo" class="absolute top-6 right-6 gsap-hidden">
                    <div class="flex items-center gap-2.5 logo-pulse">
                        <?php if ($_empresaLogo && file_exists($_empresaLogo)): ?>
                        <img src="<?php echo htmlspecialchars($_empresaLogo, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo" class="w-9 h-9 rounded-full object-cover border-2 border-nexo-500/60">
                        <?php else: ?>
                        <div class="w-9 h-9 rounded-full border-2 border-nexo-500/60 flex items-center justify-center bg-nexo-600/10">
                            <span class="text-[11px] font-bold text-nexo-400 tracking-tight"><?php echo htmlspecialchars($_initials, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <?php endif; ?>
                        <span class="text-sm font-bold text-white tracking-wider"><?php echo htmlspecialchars($_empresaNombre, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>

                <!-- Header -->
                <div class="mb-10">
                    <h1 id="title" class="text-3xl sm:text-4xl font-extrabold text-white mb-2.5 gsap-hidden tracking-tight">
                        Bienvenido
                    </h1>
                    <p id="subtitle" class="text-white/40 text-sm sm:text-base gsap-hidden">
                        Ingresa tus datos para acceder al CRM
                    </p>
                </div>

                <!-- Error message -->
                <?php if ($error): ?>
                <div id="errorMsg" class="mb-6 p-3.5 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm flex items-center gap-2.5 fade-in-up">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form id="loginForm" action="auth/login_process.php" method="POST" class="space-y-6" autocomplete="on">
                    
                    <?php
                    if (empty($_SESSION['csrf_token'])) {
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    }
                    ?>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Email -->
                    <div id="fieldEmail" class="gsap-hidden">
                        <label for="email" class="block text-sm font-medium text-white/60 mb-2">E-mail</label>
                        <div class="input-underline relative">
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                required 
                                autocomplete="email"
                                placeholder="Ingresa tu e-mail"
                                class="input-glow w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-3.5 text-white placeholder-white/25 outline-none transition-all duration-300 focus:border-nexo-600/50 focus:bg-white/[0.06] text-sm"
                            >
                            <div class="absolute right-3.5 top-1/2 -translate-y-1/2 text-white/20">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Password -->
                    <div id="fieldPassword" class="gsap-hidden">
                        <label for="password" class="block text-sm font-medium text-white/60 mb-2">Contraseña</label>
                        <div class="input-underline relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required 
                                autocomplete="current-password"
                                placeholder="••••••••••"
                                class="input-glow w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-3.5 text-white placeholder-white/25 outline-none transition-all duration-300 focus:border-nexo-600/50 focus:bg-white/[0.06] text-sm pr-12"
                            >
                            <button type="button" id="togglePassword" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-white/20 hover:text-nexo-400 transition-colors duration-200">
                                <svg id="eyeOff" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                                <svg id="eyeOn" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember me & Forgot password -->
                    <div id="fieldOptions" class="flex items-center justify-between gsap-hidden">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" name="remember" class="custom-checkbox w-4 h-4 rounded border-white/20 bg-white/5 cursor-pointer">
                            <span class="text-sm text-white/40 group-hover:text-white/60 transition-colors">Recordarme</span>
                        </label>
                        <a href="forgot_password.php" class="text-sm text-nexo-400/80 hover:text-nexo-400 transition-colors hover:underline underline-offset-4">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <div id="fieldSubmit" class="pt-2 gsap-hidden">
                        <button 
                            type="submit" 
                            id="btnLogin"
                            class="btn-purple w-full text-white font-semibold py-3.5 px-6 rounded-full transition-all duration-300 text-sm flex items-center justify-center gap-2"
                        >
                            <span id="btnText">Iniciar Sesión</span>
                            <svg id="btnArrow" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                            <div id="btnSpinner" class="spinner hidden"></div>
                        </button>
                    </div>
                </form>

                <!-- Branding -->
                <div id="branding" class="mt-10 text-center gsap-hidden">
                    <p class="text-xs text-white/15">© <?php echo date('Y'); ?> <?php echo htmlspecialchars($_empresaNombre, ENT_QUOTES, 'UTF-8'); ?> · Todos los derechos reservados</p>
                </div>
            </div>
        </div>
    </div>

    <!-- PWA Install Prompt -->
    <div id="installPrompt" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-50 slide-up">
        <div class="glass-card glow-purple rounded-2xl px-6 py-4 flex items-center gap-4 max-w-sm">
            <div class="w-10 h-10 rounded-xl bg-nexo-600/20 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-nexo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-white">Instalar CRM <?php echo htmlspecialchars($_empresaNombre, ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="text-xs text-white/40">Accede más rápido desde tu pantalla</p>
            </div>
            <button id="installBtn" class="px-4 py-2 bg-nexo-600/20 hover:bg-nexo-600/30 text-nexo-400 rounded-lg text-xs font-medium transition-colors">
                Instalar
            </button>
            <button id="dismissInstall" class="text-white/30 hover:text-white/50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <script>
    // ==========================================
    // GSAP ENTRANCE ANIMATIONS
    // ==========================================
    document.addEventListener('DOMContentLoaded', () => {
        const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
        
        tl.to('#loginCard', { opacity: 1, duration: 0.6 })
          .fromTo('#loginCard', { scale: 0.92, y: 30 }, { scale: 1, y: 0, duration: 0.9, ease: 'back.out(1.4)' }, '<')
          .to('#logo', { opacity: 1, duration: 0.5 }, '-=0.4')
          .fromTo('#logo', { y: -15, x: 15 }, { y: 0, x: 0, duration: 0.5, ease: 'power2.out' }, '<')
          .to('#title', { opacity: 1, duration: 0.6 }, '-=0.3')
          .fromTo('#title', { y: 20 }, { y: 0, duration: 0.6, ease: 'power2.out' }, '<')
          .to('#subtitle', { opacity: 1, duration: 0.5 }, '-=0.35')
          .fromTo('#subtitle', { y: 12 }, { y: 0, duration: 0.5, ease: 'power2.out' }, '<')
          .to('#fieldEmail', { opacity: 1, duration: 0.5 }, '-=0.25')
          .fromTo('#fieldEmail', { x: -25 }, { x: 0, duration: 0.5, ease: 'power2.out' }, '<')
          .to('#fieldPassword', { opacity: 1, duration: 0.5 }, '-=0.25')
          .fromTo('#fieldPassword', { x: -25 }, { x: 0, duration: 0.5, ease: 'power2.out' }, '<')
          .to('#fieldOptions', { opacity: 1, duration: 0.4 }, '-=0.15')
          .to('#fieldSubmit', { opacity: 1, duration: 0.5 }, '-=0.1')
          .fromTo('#fieldSubmit', { y: 10 }, { y: 0, duration: 0.5, ease: 'power2.out' }, '<')
          .to('#branding', { opacity: 1, duration: 0.3 }, '-=0.1');

        // Subtle hover tilt on card
        const card = document.getElementById('loginCard');
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;
            gsap.to(card, {
                rotateY: x * 2,
                rotateX: -y * 2,
                duration: 0.5,
                ease: 'power2.out',
                transformPerspective: 1200,
            });
        });
        card.addEventListener('mouseleave', () => {
            gsap.to(card, { rotateY: 0, rotateX: 0, duration: 0.6, ease: 'power2.out' });
        });
    });

    // ==========================================
    // PARTICLES
    // ==========================================
    (function createParticles() {
        const container = document.getElementById('particles');
        const count = window.innerWidth < 768 ? 20 : 45;
        for (let i = 0; i < count; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            p.style.left = Math.random() * 100 + '%';
            p.style.top = Math.random() * 100 + '%';
            const size = Math.random() * 3 + 1.5;
            p.style.width = size + 'px';
            p.style.height = size + 'px';
            p.style.opacity = (Math.random() * 0.4 + 0.15).toFixed(2);
            p.style.animationDuration = (Math.random() * 18 + 12) + 's';
            p.style.animationDelay = (Math.random() * 8) + 's';
            container.appendChild(p);
        }
    })();

    // ==========================================
    // TOGGLE PASSWORD
    // ==========================================
    const toggleBtn = document.getElementById('togglePassword');
    const passInput = document.getElementById('password');
    const eyeOff = document.getElementById('eyeOff');
    const eyeOn = document.getElementById('eyeOn');
    toggleBtn.addEventListener('click', () => {
        const isPass = passInput.type === 'password';
        passInput.type = isPass ? 'text' : 'password';
        eyeOff.classList.toggle('hidden');
        eyeOn.classList.toggle('hidden');
    });

    // ==========================================
    // FORM LOADING STATE
    // ==========================================
    document.getElementById('loginForm').addEventListener('submit', () => {
        const btn = document.getElementById('btnLogin');
        document.getElementById('btnText').textContent = 'Ingresando...';
        document.getElementById('btnArrow').classList.add('hidden');
        document.getElementById('btnSpinner').classList.remove('hidden');
        btn.disabled = true;
        btn.classList.add('opacity-70', 'cursor-not-allowed');
    });

    // ==========================================
    // INPUT FOCUS MICRO-ANIMATION
    // ==========================================
    document.querySelectorAll('input[type="email"], input[type="password"]').forEach(input => {
        input.addEventListener('focus', () => {
            gsap.to(input.closest('.input-underline'), { scale: 1.01, duration: 0.25, ease: 'power2.out' });
        });
        input.addEventListener('blur', () => {
            gsap.to(input.closest('.input-underline'), { scale: 1, duration: 0.25, ease: 'power2.out' });
        });
    });

    // ==========================================
    // PWA
    // ==========================================
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        setTimeout(() => document.getElementById('installPrompt').classList.remove('hidden'), 3000);
    });
    document.getElementById('installBtn')?.addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            await deferredPrompt.userChoice;
            deferredPrompt = null;
            document.getElementById('installPrompt').classList.add('hidden');
        }
    });
    document.getElementById('dismissInstall')?.addEventListener('click', () => {
        document.getElementById('installPrompt').classList.add('hidden');
    });

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js').catch(() => {});
    }
    </script>
</body>
</html>
