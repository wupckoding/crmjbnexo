-- =============================================
-- CRM JBNEXO - Fix Hosting Database
-- Execute no phpMyAdmin da Hostinger
-- SEGURO: pode rodar múltiplas vezes sem erro
-- =============================================

SET NAMES utf8mb4;

-- =============================================
-- 1. TABELA pipeline_etapas (usada em clientes.php e pipeline.php)
-- =============================================
CREATE TABLE IF NOT EXISTS pipeline_etapas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    estado_clave VARCHAR(20) NOT NULL DEFAULT 'nuevo',
    orden INT DEFAULT 0,
    color VARCHAR(7) DEFAULT '#84cc16',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Adicionar coluna estado_clave se não existir (DBs antigos)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'pipeline_etapas' AND column_name = 'estado_clave');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE pipeline_etapas ADD COLUMN estado_clave VARCHAR(20) NOT NULL DEFAULT ''nuevo'' AFTER nombre',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Preencher estado_clave para etapas existentes (cobre ambos formatos de nome)
UPDATE pipeline_etapas SET estado_clave = 'nuevo' WHERE LOWER(nombre) IN ('prospecto', 'nuevo') AND estado_clave = 'nuevo';
UPDATE pipeline_etapas SET estado_clave = 'contactado' WHERE LOWER(nombre) IN ('contacto inicial', 'contactado');
UPDATE pipeline_etapas SET estado_clave = 'propuesta' WHERE LOWER(nombre) IN ('propuesta enviada', 'propuesta');
UPDATE pipeline_etapas SET estado_clave = 'negociando' WHERE LOWER(nombre) IN ('negociando') OR LOWER(nombre) LIKE 'negociaci%';
UPDATE pipeline_etapas SET estado_clave = 'ganado' WHERE LOWER(nombre) IN ('cerrado ganado', 'ganado');
UPDATE pipeline_etapas SET estado_clave = 'perdido' WHERE LOWER(nombre) IN ('cerrado perdido', 'perdido');

-- Dados padrão (IGNORE = não duplica se já existir)
INSERT IGNORE INTO pipeline_etapas (nombre, estado_clave, orden, color) VALUES
('Prospecto', 'nuevo', 1, '#84cc16'),
('Contacto inicial', 'contactado', 2, '#a3e635'),
('Propuesta enviada', 'propuesta', 3, '#facc15'),
('Negociación', 'negociando', 4, '#fb923c'),
('Cerrado ganado', 'ganado', 5, '#22c55e'),
('Cerrado perdido', 'perdido', 6, '#ef4444');

-- =============================================
-- 2. TABELA categorias_financieras (usada em finanzas.php)
-- =============================================
CREATE TABLE IF NOT EXISTS categorias_financieras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('gasto','ingreso','ambos') DEFAULT 'gasto',
    color VARCHAR(7) DEFAULT '#7c3aed',
    activo TINYINT(1) DEFAULT 1,
    usuario_id INT DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO categorias_financieras (nombre, tipo, color) VALUES
('Hosting', 'gasto', '#22c55e'),
('Dominio', 'gasto', '#3b82f6'),
('Software', 'gasto', '#f59e0b'),
('Publicidad', 'gasto', '#ec4899'),
('Personal', 'gasto', '#8b5cf6'),
('Oficina', 'gasto', '#6b7280'),
('Impuestos', 'gasto', '#ef4444'),
('Otros', 'ambos', '#94a3b8');

-- =============================================
-- 3. Coluna 'categoria' em servicios
-- =============================================
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'servicios' AND column_name = 'categoria');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE servicios ADD COLUMN categoria VARCHAR(50) DEFAULT ''desarrollo_web'' AFTER activo',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================
-- 4. FIX ENUMs (seguros — MODIFY não perde dados existentes)
-- =============================================
ALTER TABLE eventos MODIFY COLUMN tipo 
    ENUM('reunion','llamada','tarea','recordatorio','seguimiento','entrega','evento','feriado') 
    DEFAULT 'evento';

ALTER TABLE clientes MODIFY COLUMN estado 
    ENUM('nuevo','contactado','negociando','propuesta','ganado','perdido') 
    DEFAULT 'nuevo';

ALTER TABLE usuarios MODIFY COLUMN rol 
    ENUM('admin','gerente','vendedor','soporte') 
    DEFAULT 'vendedor';

-- =============================================
-- 5. Colunas que podem faltar
-- =============================================
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'meta_mensual');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE usuarios ADD COLUMN meta_mensual DECIMAL(12,2) DEFAULT 0.00 AFTER comision_porcentaje',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'onboarding_completado');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE usuarios ADD COLUMN onboarding_completado TINYINT(1) DEFAULT 0 AFTER meta_mensual',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'eventos' AND column_name = 'asignado_a');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE eventos ADD COLUMN asignado_a INT DEFAULT NULL AFTER usuario_id',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================
-- 6. Coluna creado_por em conversaciones (chat groups)
-- =============================================
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'conversaciones' AND column_name = 'creado_por');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE conversaciones ADD COLUMN creado_por INT DEFAULT NULL AFTER nombre',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================
-- 7. Coluna eliminado em mensajes (chat)
-- =============================================
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'mensajes' AND column_name = 'eliminado');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE mensajes ADD COLUMN eliminado TINYINT(1) DEFAULT 0 AFTER leido',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================
-- 8. Permisos do gerente
-- =============================================
INSERT IGNORE INTO permisos (rol, modulo, puede_ver, puede_crear, puede_editar, puede_eliminar) VALUES
('gerente', 'clientes', 1, 1, 1, 1),
('gerente', 'facturas', 1, 1, 1, 1),
('gerente', 'finanzas', 1, 1, 1, 0),
('gerente', 'pipeline', 1, 1, 1, 1),
('gerente', 'chat', 1, 1, 1, 0),
('gerente', 'calendario', 1, 1, 1, 1),
('gerente', 'usuarios', 1, 0, 0, 0),
('gerente', 'permisos', 1, 0, 0, 0),
('gerente', 'avisos', 1, 1, 1, 0),
('gerente', 'boveda', 1, 1, 1, 0),
('gerente', 'leadscraper', 1, 1, 1, 0),
('gerente', 'actividad', 1, 0, 0, 0),
('gerente', 'servicios', 1, 1, 1, 0),
('gerente', 'scripts', 1, 0, 0, 0);

-- =============================================
-- FIN DE CORRECCIONES DE SCHEMA
-- =============================================
