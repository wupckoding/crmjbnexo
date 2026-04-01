-- =============================================
-- CRM JBNEXO - Fix para banco de dados existente
-- Rodar no phpMyAdmin DEPOIS de importar install_completo.sql
-- Adiciona colunas que faltavam na versão anterior
-- =============================================

-- 1. Adicionar meta_mensual na tabela usuarios (se não existir)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'meta_mensual');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE usuarios ADD COLUMN meta_mensual DECIMAL(12,2) DEFAULT 0.00 AFTER comision_porcentaje', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Adicionar onboarding_completado na tabela usuarios (se não existir)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'onboarding_completado');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE usuarios ADD COLUMN onboarding_completado TINYINT(1) DEFAULT 0 AFTER meta_mensual', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Adicionar asignado_a na tabela eventos (se não existir)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'eventos' AND COLUMN_NAME = 'asignado_a');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE eventos ADD COLUMN asignado_a INT DEFAULT NULL AFTER usuario_id', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Adicionar FK de asignado_a em eventos (ignora se já existir)
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'eventos' AND COLUMN_NAME = 'asignado_a' AND REFERENCED_TABLE_NAME = 'usuarios');
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE eventos ADD FOREIGN KEY (asignado_a) REFERENCES usuarios(id) ON DELETE SET NULL', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =============================================
-- FIX COMPLETO ✅
-- Pode rodar quantas vezes quiser, é seguro.
-- =============================================
