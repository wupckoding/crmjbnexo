-- =============================================
-- CRM JBNEXO - Tablas adicionales v2
-- =============================================
USE crmjbnexo;

-- Tabla de facturas
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

-- Items de factura
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

-- Tabla de gastos
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

-- Tabla de ingresos (pagos recibidos)
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

-- Tabla de conversaciones de chat
CREATE TABLE IF NOT EXISTS conversaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('privada','grupo') DEFAULT 'privada',
    nombre VARCHAR(100) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Participantes de conversación
CREATE TABLE IF NOT EXISTS conversacion_participantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    ultimo_leido DATETIME DEFAULT NULL,
    UNIQUE KEY unique_participant (conversacion_id, usuario_id),
    FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Mensajes de chat
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

-- Tabla de eventos del calendario
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

-- Configuraciones del usuario
CREATE TABLE IF NOT EXISTS configuraciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    tema ENUM('dark','light') DEFAULT 'dark',
    idioma VARCHAR(5) DEFAULT 'es',
    notificaciones TINYINT(1) DEFAULT 1,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insertar configuración para usuarios existentes
INSERT IGNORE INTO configuraciones (usuario_id, tema) 
SELECT id, 'dark' FROM usuarios;

-- Datos de ejemplo
INSERT INTO clientes (nombre, email, telefono, empresa, sitio_web, estado, asignado_a) VALUES
('Carlos Mendoza', 'carlos@empresa.com', '+54 11 5555-1234', 'Mendoza Digital', 'mendozadigital.com', 'ganado', 2),
('María López', 'maria@tiendaonline.com', '+52 55 4444-5678', 'Tienda Online MX', 'tiendaonlinemx.com', 'negociando', 2),
('Andrés Gutiérrez', 'andres@startup.io', '+57 300 333-9999', 'StartUp IO', 'startup.io', 'contactado', 2),
('Lucía Fernández', 'lucia@moda.ar', '+54 11 6666-7890', 'Moda AR', 'moda.ar', 'nuevo', 2),
('Pedro Ramírez', 'pedro@restaurante.cl', '+56 9 7777-1234', 'Restaurante Chileno', NULL, 'contactado', 2),
('Ana Torres', 'ana@consultoria.co', '+57 310 888-4567', 'Consultoría Torres', 'consultoriatorres.co', 'negociando', 2),
('Diego Morales', 'diego@gym.com.mx', '+52 33 9999-6789', 'PowerGym MX', 'powergym.mx', 'ganado', 2),
('Valentina Ruiz', 'val@inmobiliaria.com', '+54 351 1111-2345', 'Inmobiliaria VR', 'inmobiliariavr.com', 'nuevo', 2);

INSERT INTO facturas (numero, cliente_id, usuario_id, subtotal, impuesto_porcentaje, impuesto_monto, total, estado, fecha_emision, fecha_vencimiento) VALUES
('INV-2026-001', 1, 2, 1299.00, 0, 0, 1299.00, 'pagada', '2026-01-15', '2026-02-15'),
('INV-2026-002', 7, 2, 1999.00, 0, 0, 1999.00, 'pagada', '2026-02-01', '2026-03-01'),
('INV-2026-003', 2, 2, 2999.00, 0, 0, 2999.00, 'enviada', '2026-03-10', '2026-04-10'),
('INV-2026-004', 6, 2, 1299.00, 0, 0, 1299.00, 'enviada', '2026-03-20', '2026-04-20'),
('INV-2026-005', 3, 2, 599.00, 0, 0, 599.00, 'borrador', '2026-03-28', '2026-04-28');

INSERT INTO gastos (categoria, descripcion, monto, fecha, recurrente, frecuencia, usuario_id) VALUES
('hosting', 'Hostinger Business Plan', 49.99, '2026-01-05', 1, 'mensual', 2),
('software', 'Figma Pro', 15.00, '2026-01-05', 1, 'mensual', 2),
('publicidad', 'Google Ads - Campañas', 300.00, '2026-01-10', 1, 'mensual', 2),
('publicidad', 'Meta Ads - Instagram/Facebook', 200.00, '2026-01-10', 1, 'mensual', 2),
('dominio', 'Registro dominio cliente Mendoza', 12.99, '2026-01-15', 0, 'anual', 2),
('software', 'ChatGPT Plus', 20.00, '2026-02-01', 1, 'mensual', 2),
('hosting', 'VPS para proyectos', 24.00, '2026-02-01', 1, 'mensual', 2),
('publicidad', 'Google Ads - Campañas', 350.00, '2026-02-10', 1, 'mensual', 2),
('publicidad', 'Meta Ads', 250.00, '2026-02-10', 1, 'mensual', 2),
('hosting', 'Hostinger Business Plan', 49.99, '2026-03-05', 1, 'mensual', 2),
('software', 'Figma Pro + ChatGPT', 35.00, '2026-03-05', 1, 'mensual', 2),
('publicidad', 'Google Ads', 400.00, '2026-03-10', 1, 'mensual', 2),
('publicidad', 'Meta Ads', 280.00, '2026-03-10', 1, 'mensual', 2);

INSERT INTO ingresos (factura_id, cliente_id, descripcion, monto, metodo_pago, fecha, usuario_id) VALUES
(1, 1, 'Sitio Web Corporativo - Mendoza Digital', 1299.00, 'transferencia', '2026-01-20', 2),
(2, 7, 'E-commerce - PowerGym MX', 1999.00, 'paypal', '2026-02-15', 2),
(NULL, 1, 'Mantenimiento mensual - Mendoza Digital', 99.00, 'transferencia', '2026-02-20', 2),
(NULL, 7, 'SEO mensual - PowerGym', 399.00, 'paypal', '2026-03-01', 2),
(NULL, 1, 'Mantenimiento mensual - Mendoza Digital', 99.00, 'transferencia', '2026-03-20', 2);

INSERT INTO eventos (titulo, descripcion, tipo, color, fecha_inicio, fecha_fin, todo_el_dia, usuario_id, cliente_id) VALUES
('Reunión María López', 'Presentar propuesta e-commerce', 'reunion', '#7c3aed', '2026-03-31 10:00:00', '2026-03-31 11:00:00', 0, 2, 2),
('Entrega Sitio StartUp IO', 'Entregar portafolio digital', 'tarea', '#22c55e', '2026-04-05 00:00:00', '2026-04-05 23:59:59', 1, 2, 3),
('Pago Google Ads', 'Renovar campañas mensuales', 'recordatorio', '#f59e0b', '2026-04-10 09:00:00', NULL, 0, 2, NULL),
('Llamada Ana Torres', 'Seguimiento propuesta consultoría', 'reunion', '#7c3aed', '2026-04-02 15:00:00', '2026-04-02 15:30:00', 0, 2, 6);
