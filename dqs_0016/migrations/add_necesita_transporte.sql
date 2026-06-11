-- dqs_0016 - Agrega indicador de necesidad de transporte para RSVP.
-- Idempotente para MySQL/MariaDB: agrega la columna solo si no existe.

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'invitados'
      AND COLUMN_NAME = 'necesita_transporte'
);

SET @ddl := IF(
    @column_exists = 0,
    'ALTER TABLE invitados ADD COLUMN necesita_transporte TINYINT(1) NOT NULL DEFAULT 0 AFTER codigo',
    'SELECT ''La columna necesita_transporte ya existe'' AS info'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
