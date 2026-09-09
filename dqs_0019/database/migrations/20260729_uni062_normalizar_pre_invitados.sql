-- UNI-062: extensión aditiva del contrato pre_* para MariaDB/Hostinger.
-- MariaDB admite IF NOT EXISTS en ADD COLUMN/INDEX; el script puede repetirse.
ALTER TABLE `pre_invitados`
  ADD COLUMN IF NOT EXISTS `acompanado` INT NULL,
  ADD COLUMN IF NOT EXISTS `cantidad_mayores` INT NULL,
  ADD COLUMN IF NOT EXISTS `cantidad_menores` INT NULL,
  ADD COLUMN IF NOT EXISTS `id_prioridad` INT NULL,
  ADD COLUMN IF NOT EXISTS `ingreso` VARCHAR(25) NULL,
  ADD COLUMN IF NOT EXISTS `codigo` VARCHAR(10) NULL;

ALTER TABLE `pre_invitados_tel`
  ADD COLUMN IF NOT EXISTS `id_invitados` INT NULL,
  ADD COLUMN IF NOT EXISTS `tel_enviar` BIGINT NULL,
  ADD INDEX IF NOT EXISTS `idx_pre_invitados_tel_id_invitados` (`id_invitados`);

UPDATE `pre_invitados_tel`
SET `id_invitados` = `id_pre_invitado`
WHERE `id_invitados` IS NULL;

-- Sólo convierte dígitos dentro del rango positivo de BIGINT; el texto original se conserva.
UPDATE `pre_invitados_tel`
SET `tel_enviar` = CAST(TRIM(`telefono`) AS UNSIGNED)
WHERE `tel_enviar` IS NULL
  AND TRIM(`telefono`) REGEXP '^[0-9]+$'
  AND CAST(TRIM(`telefono`) AS DECIMAL(65,0)) <= 9223372036854775807;

ALTER TABLE `pre_invitados_listado_mesa`
  ADD COLUMN IF NOT EXISTS `id_invitados` INT NULL,
  ADD COLUMN IF NOT EXISTS `nombre_invitado` VARCHAR(50) NULL,
  ADD COLUMN IF NOT EXISTS `nombre2` VARCHAR(50) NULL,
  ADD COLUMN IF NOT EXISTS `apellido2` VARCHAR(50) NULL,
  ADD COLUMN IF NOT EXISTS `es_menor` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `asiste` TINYINT(1) NULL,
  ADD COLUMN IF NOT EXISTS `confirm_date` TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `alimento` VARCHAR(30) NOT NULL DEFAULT 'No',
  ADD COLUMN IF NOT EXISTS `alimento_comentario` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `mesa` INT NULL,
  ADD INDEX IF NOT EXISTS `idx_pre_invitados_listado_id_invitados` (`id_invitados`);

UPDATE `pre_invitados_listado_mesa`
SET `id_invitados` = `id_pre_invitado`
WHERE `id_invitados` IS NULL;

UPDATE `pre_invitados_listado_mesa`
SET `nombre_invitado` = `nombre`
WHERE `nombre_invitado` IS NULL OR TRIM(`nombre_invitado`) = '';

UPDATE `pre_invitados_listado_mesa`
SET `nombre2` = `nombre`
WHERE `nombre2` IS NULL OR TRIM(`nombre2`) = '';

UPDATE `pre_invitados_listado_mesa`
SET `apellido2` = `apellido`
WHERE `apellido2` IS NULL OR TRIM(`apellido2`) = '';

UPDATE `pre_invitados_listado_mesa`
SET `es_menor` = 0
WHERE `es_menor` IS NULL;
