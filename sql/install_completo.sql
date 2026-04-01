-- =============================================
-- CRM JBNEXO - Instalación Completa
-- Ejecutar este archivo en phpMyAdmin o MySQL CLI
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS crmjbnexo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE crmjbnexo;

-- =============================================
-- 1. TABLA USUARIOS
-- =============================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'vendedor', 'soporte') DEFAULT 'vendedor',
    avatar VARCHAR(255) DEFAULT NULL,
    activo TINYINT(1) DEFAULT 1,
    ultimo_acceso DATETIME DEFAULT NULL,
    token_recuperacion VARCHAR(255) DEFAULT NULL,
    token_expiracion DATETIME DEFAULT NULL,
    totp_secret VARCHAR(255) DEFAULT NULL,
    totp_activo TINYINT(1) DEFAULT 0,
    comision_porcentaje DECIMAL(5,2) DEFAULT 20.00,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- 2. TABLA CLIENTES
-- =============================================
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    telefono VARCHAR(30) DEFAULT NULL,
    empresa VARCHAR(150) DEFAULT NULL,
    sitio_web VARCHAR(255) DEFAULT NULL,
    direccion TEXT DEFAULT NULL,
    notas TEXT DEFAULT NULL,
    estado ENUM('nuevo', 'contactado', 'negociando', 'ganado', 'perdido') DEFAULT 'nuevo',
    foto VARCHAR(255) DEFAULT NULL,
    archivado TINYINT(1) DEFAULT 0,
    asignado_a INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (asignado_a) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================
-- 3. TABLA SERVICIOS
-- =============================================
CREATE TABLE IF NOT EXISTS servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- 4. TABLA COTIZACIONES
-- =============================================
CREATE TABLE IF NOT EXISTS cotizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    usuario_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    valor_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado ENUM('borrador', 'enviada', 'aceptada', 'rechazada') DEFAULT 'borrador',
    fecha_vencimiento DATE DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 5. TABLA COTIZACION ITEMS
-- =============================================
CREATE TABLE IF NOT EXISTS cotizacion_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT NOT NULL,
    servicio_id INT DEFAULT NULL,
    descripcion VARCHAR(255) NOT NULL,
    cantidad INT DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================
-- 6. TABLA TAREAS
-- =============================================
CREATE TABLE IF NOT EXISTS tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    cliente_id INT DEFAULT NULL,
    usuario_id INT NOT NULL,
    prioridad ENUM('baja', 'media', 'alta', 'urgente') DEFAULT 'media',
    estado ENUM('pendiente', 'en_progreso', 'completada', 'cancelada') DEFAULT 'pendiente',
    fecha_limite DATETIME DEFAULT NULL,
    completada_en DATETIME DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 7. TABLA INTERACCIONES
-- =============================================
CREATE TABLE IF NOT EXISTS interacciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('llamada', 'email', 'reunion', 'whatsapp', 'nota') DEFAULT 'nota',
    contenido TEXT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 8. TABLA PIPELINE
-- =============================================
CREATE TABLE IF NOT EXISTS pipeline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    orden INT DEFAULT 0,
    color VARCHAR(7) DEFAULT '#84cc16',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- 9. TABLA FACTURAS
-- =============================================
CREATE TABLE IF NOT EXISTS facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL UNIQUE,
    cliente_id INT NOT NULL,
    usuario_id INT NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    impuesto_porcentaje DECIMAL(5,2) DEFAULT 0.00,
    impuesto_monto DECIMAL(12,2) DEFAULT 0.00,
    descuento DECIMAL(12,2) DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    moneda VARCHAR(3) DEFAULT 'USD',
    estado ENUM('borrador','enviada','pagada','vencida','cancelada') DEFAULT 'borrador',
    fecha_emision DATE NOT NULL,
    fecha_vencimiento DATE DEFAULT NULL,
    fecha_pago DATE DEFAULT NULL,
    notas TEXT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 10. TABLA FACTURA ITEMS
-- =============================================
CREATE TABLE IF NOT EXISTS factura_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    servicio_id INT DEFAULT NULL,
    descripcion VARCHAR(255) NOT NULL,
    cantidad INT DEFAULT 1,
    precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================
-- 11. TABLA GASTOS
-- =============================================
CREATE TABLE IF NOT EXISTS gastos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria ENUM('hosting','dominio','software','publicidad','personal','oficina','impuestos','otros') DEFAULT 'otros',
    descripcion VARCHAR(255) NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    moneda VARCHAR(3) DEFAULT 'USD',
    fecha DATE NOT NULL,
    recurrente TINYINT(1) DEFAULT 0,
    frecuencia ENUM('unico','mensual','anual') DEFAULT 'unico',
    comprobante VARCHAR(255) DEFAULT NULL,
    usuario_id INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 12. TABLA INGRESOS
-- =============================================
CREATE TABLE IF NOT EXISTS ingresos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT DEFAULT NULL,
    cliente_id INT DEFAULT NULL,
    descripcion VARCHAR(255) NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    moneda VARCHAR(3) DEFAULT 'USD',
    metodo_pago ENUM('transferencia','paypal','stripe','efectivo','crypto','otro') DEFAULT 'transferencia',
    fecha DATE NOT NULL,
    usuario_id INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE SET NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 13. TABLA CONVERSACIONES (CHAT)
-- =============================================
CREATE TABLE IF NOT EXISTS conversaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('privada','grupo') DEFAULT 'privada',
    nombre VARCHAR(100) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- 14. TABLA PARTICIPANTES CONVERSACIÓN
-- =============================================
CREATE TABLE IF NOT EXISTS conversacion_participantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    ultimo_leido DATETIME DEFAULT NULL,
    UNIQUE KEY unique_participant (conversacion_id, usuario_id),
    FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 15. TABLA MENSAJES
-- =============================================
CREATE TABLE IF NOT EXISTS mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    contenido TEXT NOT NULL,
    tipo ENUM('texto','imagen','archivo','audio') DEFAULT 'texto',
    archivo_url VARCHAR(255) DEFAULT NULL,
    leido TINYINT(1) DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 16. TABLA EVENTOS (CALENDARIO)
-- =============================================
CREATE TABLE IF NOT EXISTS eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    tipo ENUM('reunion','tarea','recordatorio','evento','feriado') DEFAULT 'evento',
    color VARCHAR(7) DEFAULT '#7c3aed',
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME DEFAULT NULL,
    todo_el_dia TINYINT(1) DEFAULT 0,
    usuario_id INT NOT NULL,
    cliente_id INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================
-- 17. TABLA CONFIGURACIONES (POR USUARIO)
-- =============================================
CREATE TABLE IF NOT EXISTS configuraciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    tema ENUM('dark','light') DEFAULT 'dark',
    idioma VARCHAR(5) DEFAULT 'es',
    notificaciones TINYINT(1) DEFAULT 1,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 18. TABLA CONFIGURACIÓN GLOBAL (BRANDING)
-- =============================================
CREATE TABLE IF NOT EXISTS configuracion_global (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT DEFAULT NULL
) ENGINE=InnoDB;

-- =============================================
-- 19. TABLA PERMISOS
-- =============================================
CREATE TABLE IF NOT EXISTS permisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol VARCHAR(30) NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    puede_ver TINYINT(1) DEFAULT 1,
    puede_crear TINYINT(1) DEFAULT 0,
    puede_editar TINYINT(1) DEFAULT 0,
    puede_eliminar TINYINT(1) DEFAULT 0,
    UNIQUE KEY unique_rol_modulo (rol, modulo)
) ENGINE=InnoDB;

-- =============================================
-- 20. TABLA NOTIFICACIONES
-- =============================================
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo VARCHAR(20) DEFAULT 'info',
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT DEFAULT NULL,
    enlace VARCHAR(255) DEFAULT NULL,
    leida TINYINT(1) DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 21. TABLA ACTIVIDAD LOG (AUDITORÍA)
-- =============================================
CREATE TABLE IF NOT EXISTS actividad_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    accion VARCHAR(50) NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    detalle TEXT DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 22. TABLA LEADS (LEADSCRAPER)
-- =============================================
CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_empresa VARCHAR(200) NOT NULL,
    nombre_contacto VARCHAR(150) DEFAULT NULL,
    cargo VARCHAR(100) DEFAULT NULL,
    nicho VARCHAR(100) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    telefono VARCHAR(50) DEFAULT NULL,
    whatsapp VARCHAR(50) DEFAULT NULL,
    sitio_web VARCHAR(255) DEFAULT NULL,
    direccion TEXT DEFAULT NULL,
    descripcion TEXT DEFAULT NULL,
    google_place_id VARCHAR(100) DEFAULT NULL,
    rating DECIMAL(3,1) DEFAULT NULL,
    estado VARCHAR(30) DEFAULT 'nuevo',
    asignado_a INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asignado_a) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================
-- 23. TABLA AVISOS
-- =============================================
CREATE TABLE IF NOT EXISTS avisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    contenido TEXT NOT NULL,
    prioridad ENUM('normal','importante','urgente') DEFAULT 'normal',
    fijado TINYINT(1) DEFAULT 0,
    imagen VARCHAR(255) DEFAULT NULL,
    creado_por INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 24. TABLA BÓVEDA CATEGORÍAS
-- =============================================
CREATE TABLE IF NOT EXISTS boveda_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#7c3aed',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- 25. TABLA BÓVEDA ITEMS
-- =============================================
CREATE TABLE IF NOT EXISTS boveda_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT DEFAULT NULL,
    tipo VARCHAR(30) DEFAULT 'password',
    titulo VARCHAR(200) NOT NULL,
    usuario_campo VARCHAR(200) DEFAULT NULL,
    password_enc LONGBLOB DEFAULT NULL,
    url VARCHAR(500) DEFAULT NULL,
    notas TEXT DEFAULT NULL,
    archivo_nombre VARCHAR(255) DEFAULT NULL,
    archivo_ruta VARCHAR(255) DEFAULT NULL,
    archivo_size INT DEFAULT NULL,
    archivo_mime VARCHAR(100) DEFAULT NULL,
    creado_por INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES boveda_categorias(id) ON DELETE SET NULL,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- 26. TABLA METAS DIARIAS
-- =============================================
CREATE TABLE IF NOT EXISTS metas_diarias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    icono VARCHAR(10) DEFAULT '📋',
    meta_cantidad INT DEFAULT 1,
    progreso INT DEFAULT 0,
    fecha DATE NOT NULL,
    creado_por INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_meta (usuario_id, titulo, fecha),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- DATOS INICIALES
-- =============================================

-- Etapas del pipeline
INSERT INTO pipeline (nombre, orden, color) VALUES
('Prospecto', 1, '#84cc16'),
('Contacto inicial', 2, '#a3e635'),
('Propuesta enviada', 3, '#facc15'),
('Negociación', 4, '#fb923c'),
('Cerrado ganado', 5, '#22c55e'),
('Cerrado perdido', 6, '#ef4444');

-- Servicios por defecto
INSERT INTO servicios (nombre, descripcion, precio) VALUES
('Landing Page', 'Página de aterrizaje profesional con diseño moderno', 499.00),
('Sitio Web Corporativo', 'Sitio web completo para empresas con múltiples páginas', 1299.00),
('Tienda Online (E-commerce)', 'Tienda virtual completa con carrito de compras', 1999.00),
('Blog Profesional', 'Blog con diseño personalizado y sistema de gestión', 699.00),
('Portafolio Digital', 'Portafolio para mostrar trabajos y proyectos', 599.00),
('Aplicación Web', 'Aplicación web personalizada según necesidades', 2999.00),
('Mantenimiento Mensual', 'Servicio de mantenimiento y actualizaciones mensuales', 99.00),
('SEO y Optimización', 'Optimización para motores de búsqueda', 399.00);

-- Usuario administrador (contraseña: admin123)
INSERT INTO usuarios (nombre, email, password, rol, comision_porcentaje) VALUES
('Administrador', 'admin@jbnexo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0.00);

-- Configuración del admin
INSERT INTO configuraciones (usuario_id, tema) VALUES (1, 'dark');

-- Configuración global (branding)
INSERT INTO configuracion_global (clave, valor) VALUES
('empresa_nombre', 'JBNEXO'),
('empresa_email', 'contacto@jbnexo.com'),
('empresa_telefono', ''),
('empresa_direccion', ''),
('logo_url', ''),
('color_primario', '#7c3aed'),
('idioma', 'es');

-- Permisos por defecto
INSERT INTO permisos (rol, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar) VALUES
-- Admin: acceso total
('admin', 'clientes', 1, 1, 1, 1),
('admin', 'facturas', 1, 1, 1, 1),
('admin', 'finanzas', 1, 1, 1, 1),
('admin', 'pipeline', 1, 1, 1, 1),
('admin', 'chat', 1, 1, 1, 1),
('admin', 'calendario', 1, 1, 1, 1),
('admin', 'usuarios', 1, 1, 1, 1),
('admin', 'permisos', 1, 1, 1, 1),
('admin', 'avisos', 1, 1, 1, 1),
('admin', 'boveda', 1, 1, 1, 1),
('admin', 'leadscraper', 1, 1, 1, 1),
('admin', 'actividad', 1, 1, 1, 1),
('admin', 'servicios', 1, 1, 1, 1),
('admin', 'ajustes', 1, 1, 1, 1),
('admin', 'scripts', 1, 1, 1, 1),
('admin', 'backup', 1, 1, 1, 1),
-- Vendedor: acceso limitado
('vendedor', 'clientes', 1, 1, 1, 0),
('vendedor', 'facturas', 1, 1, 1, 0),
('vendedor', 'finanzas', 1, 0, 0, 0),
('vendedor', 'pipeline', 1, 1, 1, 0),
('vendedor', 'chat', 1, 1, 1, 0),
('vendedor', 'calendario', 1, 1, 1, 1),
('vendedor', 'leadscraper', 1, 1, 1, 0),
('vendedor', 'scripts', 1, 0, 0, 0),
('vendedor', 'avisos', 1, 0, 0, 0),
-- Soporte: mínimo
('soporte', 'clientes', 1, 0, 1, 0),
('soporte', 'chat', 1, 1, 1, 0),
('soporte', 'calendario', 1, 1, 1, 0),
('soporte', 'avisos', 1, 0, 0, 0);

-- Bóveda categorías por defecto
INSERT INTO boveda_categorias (nombre, color) VALUES
('Hosting', '#22c55e'),
('Dominios', '#3b82f6'),
('APIs', '#f59e0b'),
('Redes Sociales', '#ec4899'),
('Clientes', '#8b5cf6'),
('General', '#6b7280');

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- INSTALACIÓN COMPLETA ✅
-- Login: admin@jbnexo.com / admin123
-- ¡CAMBIA LA CONTRASEÑA DEL ADMIN INMEDIATAMENTE!
-- =============================================
