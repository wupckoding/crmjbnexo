-- =============================================
-- CRM JBNEXO - Base de datos
-- =============================================

CREATE DATABASE IF NOT EXISTS crmjbnexo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE crmjbnexo;

-- Tabla de usuarios del CRM
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
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de clientes
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
    asignado_a INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (asignado_a) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabla de servicios/productos (tipos de sitios web)
CREATE TABLE IF NOT EXISTS servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de cotizaciones/presupuestos
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

-- Items de cada cotización
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

-- Tabla de tareas/actividades
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

-- Tabla de notas/interacciones
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

-- Tabla de pipeline de ventas
CREATE TABLE IF NOT EXISTS pipeline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    orden INT DEFAULT 0,
    color VARCHAR(7) DEFAULT '#84cc16',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insertar etapas del pipeline por defecto
INSERT INTO pipeline (nombre, orden, color) VALUES
('Prospecto', 1, '#84cc16'),
('Contacto inicial', 2, '#a3e635'),
('Propuesta enviada', 3, '#facc15'),
('Negociación', 4, '#fb923c'),
('Cerrado ganado', 5, '#22c55e'),
('Cerrado perdido', 6, '#ef4444');

-- Insertar servicios por defecto
INSERT INTO servicios (nombre, descripcion, precio) VALUES
('Landing Page', 'Página de aterrizaje profesional con diseño moderno', 499.00),
('Sitio Web Corporativo', 'Sitio web completo para empresas con múltiples páginas', 1299.00),
('Tienda Online (E-commerce)', 'Tienda virtual completa con carrito de compras', 1999.00),
('Blog Profesional', 'Blog con diseño personalizado y sistema de gestión', 699.00),
('Portafolio Digital', 'Portafolio para mostrar trabajos y proyectos', 599.00),
('Aplicación Web', 'Aplicación web personalizada según necesidades', 2999.00),
('Mantenimiento Mensual', 'Servicio de mantenimiento y actualizaciones mensuales', 99.00),
('SEO y Optimización', 'Optimización para motores de búsqueda', 399.00);

-- Insertar usuario administrador por defecto (contraseña: admin123)
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@jbnexo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
