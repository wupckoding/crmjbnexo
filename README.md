<p align="center">
  <img src="https://img.shields.io/badge/CRM-JBNEXO-7c3aed?style=for-the-badge&logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJ3aGl0ZSI+PHBhdGggZD0iTTEyIDJMMyA3djEwbDkgNSA5LTVWN2wtOS01eiIvPjwvc3ZnPg==&logoColor=white" alt="CRM JBNEXO"/>
  <br/>
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP"/>
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL"/>
  <img src="https://img.shields.io/badge/Tailwind_CSS-3.x-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind"/>
  <img src="https://img.shields.io/badge/GSAP-3.12-88CE02?style=flat-square&logo=greensock&logoColor=white" alt="GSAP"/>
  <img src="https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=flat-square&logo=alpinedotjs&logoColor=white" alt="Alpine.js"/>
</p>

# 🟣 CRM JBNEXO

**Sistema CRM completo para agencias digitales y equipos de ventas.** Gestiona clientes, pipeline de ventas, facturas, finanzas, leads, chat interno, calendario y mucho más — todo desde una interfaz dark mode con diseño moderno.

---

## ✨ Features

| Módulo | Descripción |
|--------|-------------|
| **Dashboard** | KPIs en tiempo real, tasa de conversión, comisiones, metas diarias con progreso |
| **Clientes** | CRUD completo, historial de interacciones, asignación por vendedor |
| **Pipeline** | Kanban visual con etapas personalizables (drag & drop) |
| **Facturas** | Creación, envío, estados, generación PDF |
| **Finanzas** | Ingresos vs gastos, gráficos mensuales, métricas de rentabilidad |
| **LeadScraper** | Búsqueda de leads vía Brave Search, scraping de datos de contacto, asignación masiva |
| **Chat** | Mensajería interna en tiempo real entre usuarios del CRM |
| **Calendario** | Eventos, reuniones, tareas y recordatorios |
| **Bóveda** | Gestor de contraseñas encriptado (AES-256-CBC) con categorías |
| **Avisos** | Tablón de anuncios interno con prioridades e imágenes |
| **Scripts** | Guías de venta: script de llamada, objeciones, templates WhatsApp, checklist |
| **Permisos** | Control granular por rol (admin/vendedor/soporte) y módulo |
| **Actividad** | Log de auditoría completo de todas las acciones del sistema |
| **2FA** | Autenticación de dos factores con TOTP |
| **PWA** | Instalable como app en móviles y escritorio |

---

## 🖼️ Stack

- **Backend:** PHP 8.x (vanilla, sin frameworks)
- **Base de datos:** MySQL 8.0 / MariaDB 10.x
- **Frontend:** Tailwind CSS 3 (CDN) + Alpine.js 3 + GSAP 3.12
- **Charts:** Chart.js
- **Auth:** Sessions + CSRF + Rate Limiting + TOTP 2FA
- **Encryption:** AES-256-CBC (bóveda)

---

## 📁 Estructura

```
crmjbnexo/
├── api/                  # Endpoints AJAX (JSON)
│   ├── actividad.php
│   ├── avisos.php
│   ├── boveda.php
│   ├── buscar.php
│   ├── calendario.php
│   ├── chat.php
│   ├── clientes.php
│   ├── facturas.php
│   ├── finanzas.php
│   ├── leadscraper.php
│   ├── metas_diarias.php
│   ├── notificaciones.php
│   ├── servicios.php
│   ├── toggle_theme.php
│   ├── twofactor.php
│   └── usuarios.php
├── assets/
│   ├── css/custom.css    # Animaciones y estilos custom
│   ├── icons/            # PWA icons
│   └── js/sounds.js
├── auth/
│   ├── login_process.php
│   ├── logout.php
│   └── verify_2fa.php
├── config/
│   └── database.php      # ⚙️ Configurar credenciales aquí
├── includes/
│   ├── auth_check.php    # Guard de autenticación
│   ├── header.php
│   ├── footer.php
│   ├── helpers.php       # Logger + notificaciones + permisos
│   ├── sidebar.php
│   └── topbar.php
├── sql/
│   └── install_completo.sql  # 🗄️ Todas las 26 tablas
├── uploads/              # Archivos subidos
├── index.php             # Login page
├── dashboard.php
├── clientes.php
├── pipeline.php
├── facturas.php
├── finanzas.php
├── leadscraper.php
├── scripts.php
├── chat.php
├── calendario.php
├── boveda.php
├── avisos.php
├── permisos.php
├── actividad.php
├── servicios.php
├── ajustes.php
├── perfil.php
├── sw.js                 # Service Worker (PWA)
├── manifest.json
├── .htaccess             # Seguridad + rewrites
└── README.md
```

---

## 🚀 Instalación

### Requisitos
- PHP 8.0+
- MySQL 8.0 / MariaDB 10.4+
- Apache con `mod_rewrite`

### Local (XAMPP)

```bash
# 1. Clonar el repositorio
git clone https://github.com/wupckoding/crmjbnexo.git

# 2. Mover a htdocs (XAMPP)
# El proyecto debe quedar en: htdocs/crmjbnexo/

# 3. Crear la base de datos
# Abrir phpMyAdmin → Importar → sql/install_completo.sql

# 4. Configurar credenciales (si no es root sin password)
# Editar config/database.php

# 5. Acceder
# http://localhost/crmjbnexo/
```

### Hosting (Hostinger / cPanel)

```bash
# 1. Crear base de datos MySQL en el panel del hosting
# 2. Importar sql/install_completo.sql en phpMyAdmin
# 3. Editar config/database.php con las credenciales del hosting:
#    DB_HOST = 'localhost'
#    DB_USER = 'u123456789_usuario'
#    DB_PASS = 'tu_contraseña_segura'
#    DB_NAME = 'u123456789_crmjbnexo'
# 4. Subir archivos a public_html/ (o public_html/crmjbnexo/)
# 5. Listo!
```

---

## 🔐 Login por defecto

| Campo | Valor |
|-------|-------|
| Email | `admin@jbnexo.com` |
| Password | `admin123` |

> ⚠️ **Cambiar la contraseña inmediatamente después del primer login.**

---

## 🎨 Temas

El CRM usa un dark theme con paleta **nexo purple**:

| Token | Hex | Color |
|-------|-----|-------|
| `nexo-400` | `#a78bfa` | 🟣 |
| `nexo-500` | `#8b5cf6` | 🟣 |
| `nexo-600` | `#7c3aed` | 🟣 |
| `nexo-700` | `#6b28e6` | 🟣 |
| `dark-900` | `#0c0a14` | ⚫ |
| `dark-800` | `#12101c` | ⚫ |

---

## 🛡️ Seguridad

- CSRF tokens en todos los formularios
- Rate limiting en login (5 intentos / 5 min)
- Session regeneration post-login
- Passwords con `bcrypt` (password_hash)
- Bóveda encriptada con AES-256-CBC
- `.htaccess` bloquea acceso a `config/`, `includes/`, `sql/`
- Prepared statements (PDO) en todas las queries

---

## 📄 Licencia

Uso privado — © JBNEXO

---

<p align="center">
  <b>Built with 💜 by JBNEXO</b>
</p>
