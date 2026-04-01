<?php
// $pageTitle must be set before including this file
$pageTitle = $pageTitle ?? 'Dashboard';
$currentPage = $currentPage ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="es" class="<?php echo $tema === 'dark' ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="<?php echo $tema === 'dark' ? '#09090b' : '#f8fafc'; ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/icon-192.png">
    <title><?php echo htmlspecialchars($pageTitle); ?> | CRM JBNEXO</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        nexo: {
                            50:'#f5f3ff',100:'#ede9fe',200:'#ddd6fe',300:'#c4b5fd',
                            400:'#a78bfa',500:'#8b5cf6',600:'#7c3aed',700:'#6d28d9',
                            800:'#5b21b6',900:'#4c1d95',950:'#2e1065'
                        },
                        dark: {
                            950:'#09090b',900:'#0c0a14',800:'#12101c',
                            700:'#1a1726',600:'#221e30',500:'#2a253b'
                        }
                    },
                    fontFamily: { sans: ['Inter','system-ui','sans-serif'] }
                }
            }
        }
    </script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js" defer></script>
    <script src="assets/js/sounds.js"></script>
    
    <link rel="stylesheet" href="assets/css/custom.css">
    
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        [x-cloak] { display: none !important; }
        .sidebar-link.active { background: rgba(124,58,237,0.15); color: #a78bfa; border-right: 3px solid #7c3aed; }
        .dark .sidebar-link.active { background: rgba(124,58,237,0.15); }
        .sidebar-link:hover:not(.active) { background: rgba(124,58,237,0.08); }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(124,58,237,0.1); }
        .sidebar-transition { transition: width 0.3s cubic-bezier(0.4,0,0.2,1); }
        /* Fix native select/option in dark mode */
        .dark select, .dark select option { background-color: #1a1726; color: #fff; }
        select, select option { background-color: #fff; color: #111827; }
        /* Smooth page transition */
        @keyframes pageIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        main { animation: pageIn 0.35s cubic-bezier(0.4,0,0.2,1) both; }
        @keyframes waveAnim { from { transform: scaleY(0.4); } to { transform: scaleY(1); } }
    </style>
</head>
<body class="dark:bg-dark-950 bg-gray-50 dark:text-white text-gray-900 antialiased min-h-screen">
<div x-data="{ sidebarOpen: true, sidebarMobile: false }" class="flex min-h-screen">
