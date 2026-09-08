-- DQS canonical installation schema v0.1.0 (UNI-048.1)
-- Schema-only: no customer data, credentials, database name, or historical counters.
-- Source: docs/DQS_SCHEMA_SNAPSHOT_HOSTINGER_20260719_012551.sql
-- Create/select the target database before running this file.


-- ============================================================================
-- Núcleo de instalación
-- ============================================================================

-- Table: user
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: admin_config
CREATE TABLE `admin_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_carpeta` varchar(255) NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: cliente
CREATE TABLE `cliente` (
  `user_id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `telefono2` varchar(20) DEFAULT NULL,
  `direccion` varchar(100) DEFAULT NULL,
  `cbu_titular` varchar(100) DEFAULT NULL,
  `cbu` varchar(22) DEFAULT NULL,
  `alias` varchar(100) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `plan` int(10) DEFAULT NULL,
  `cbu_dolar` varchar(100) DEFAULT NULL,
  `alias_dolar` varchar(100) DEFAULT NULL,
  `cotizacion_dolar` int(10) DEFAULT NULL,
  `cbu_dolar_2` varchar(100) DEFAULT NULL,
  `alias_dolar_2` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: site_settings
CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Contenido público
-- ============================================================================

-- Table: productos
CREATE TABLE `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `activo` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table: info_casamiento
CREATE TABLE `info_casamiento` (
  `portada_titulo` varchar(255) DEFAULT NULL,
  `portada_frase` varchar(255) DEFAULT NULL,
  `portada_fecha` varchar(255) DEFAULT NULL,
  `portada_fecha_hora` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table: info_mostrar
CREATE TABLE `info_mostrar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seccion` varchar(50) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: info_eventos
CREATE TABLE `info_eventos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `url` text DEFAULT NULL,
  `tipo_visual` enum('imagen','icono') DEFAULT 'imagen',
  `imagen` varchar(255) DEFAULT NULL,
  `icono` varchar(255) DEFAULT NULL,
  `orden` int(10) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table: info_historia
CREATE TABLE `info_historia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `titulo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `texto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table: info_nosotros
CREATE TABLE `info_nosotros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `texto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` int(1) NOT NULL,
  `orden` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table: info_otra
CREATE TABLE `info_otra` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `icono` varchar(255) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table: imagenes
CREATE TABLE `imagenes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producto_id` int(11) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `imagenes_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table: visitas
CREATE TABLE `visitas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_visita` timestamp NULL DEFAULT current_timestamp(),
  `ip_usuario` varchar(45) NOT NULL,
  `pagina_visitada` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- RSVP
-- ============================================================================

-- Table: invitados
CREATE TABLE `invitados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(25) NOT NULL,
  `apellido` varchar(25) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `acompanado` int(11) NOT NULL,
  `cantidad_mayores` int(11) NOT NULL,
  `id_prioridad` int(11) NOT NULL,
  `ingreso` varchar(25) NOT NULL,
  `cantidad_menores` int(11) NOT NULL,
  `fecha_registro` date NOT NULL,
  `confirmacion` varchar(25) DEFAULT NULL,
  `confirmacion_fecha` datetime DEFAULT NULL,
  `confirmacion_comentario` text DEFAULT NULL,
  `confirmacion_mayores` int(11) DEFAULT NULL,
  `confirmacion_menores` int(11) DEFAULT NULL,
  `alimento` varchar(15) DEFAULT NULL,
  `codigo` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table: invitados_listado_mesa
CREATE TABLE `invitados_listado_mesa` (
  `id_invitados` int(11) DEFAULT NULL,
  `nombre_invitado` varchar(50) DEFAULT NULL,
  `es_menor` tinyint(1) NOT NULL DEFAULT 0,
  `asiste` tinyint(1) DEFAULT NULL,
  `confirm_date` timestamp NULL DEFAULT NULL,
  `alimento` varchar(30) NOT NULL DEFAULT 'No',
  `alimento_comentario` varchar(255) DEFAULT NULL,
  `mesa` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre2` varchar(50) DEFAULT NULL,
  `apellido2` varchar(50) DEFAULT NULL,
  UNIQUE KEY `orden_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: invitados_prioridad
CREATE TABLE `invitados_prioridad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_prioridad` varchar(25) NOT NULL,
  `categoria_precio` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table: invitados_tel
CREATE TABLE `invitados_tel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_invitados` int(11) DEFAULT NULL,
  `tel_enviar` bigint(20) DEFAULT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: intivados_acompanante
CREATE TABLE `intivados_acompanante` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_acompanante` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table: invitaciones_estado
CREATE TABLE `invitaciones_estado` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_invitado` int(11) NOT NULL,
  `id_invitados_tel` int(11) NOT NULL,
  `fecha_envio` datetime DEFAULT NULL,
  `estado_api` varchar(20) DEFAULT NULL,
  `detalle_api` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- RSVP / staging de formulario
-- ============================================================================

-- Table: pre_invitados
CREATE TABLE `pre_invitados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `confirmacion` varchar(10) NOT NULL,
  `restriccion_alimentaria` varchar(50) DEFAULT 'No',
  `comentario` text DEFAULT NULL,
  `cantidad_acompanantes` int(11) DEFAULT 0,
  `total_personas` int(11) DEFAULT 0,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `origen` varchar(30) DEFAULT 'form_public',
  `activo` tinyint(1) DEFAULT 1,
  `acompanado` int(11) DEFAULT NULL,
  `cantidad_mayores` int(11) DEFAULT NULL,
  `cantidad_menores` int(11) DEFAULT NULL,
  `id_prioridad` int(11) DEFAULT NULL,
  `ingreso` varchar(25) DEFAULT NULL,
  `codigo` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: pre_invitados_listado_mesa
CREATE TABLE `pre_invitados_listado_mesa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_pre_invitado` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `restriccion_alimentaria` varchar(50) DEFAULT 'No',
  `comentario` text DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `id_invitados` int(11) DEFAULT NULL,
  `nombre_invitado` varchar(50) DEFAULT NULL,
  `nombre2` varchar(50) DEFAULT NULL,
  `apellido2` varchar(50) DEFAULT NULL,
  `es_menor` tinyint(1) NOT NULL DEFAULT 0,
  `asiste` tinyint(1) DEFAULT NULL,
  `confirm_date` timestamp NULL DEFAULT NULL,
  `alimento` varchar(30) NOT NULL DEFAULT 'No',
  `alimento_comentario` varchar(255) DEFAULT NULL,
  `mesa` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_pre_invitado` (`id_pre_invitado`),
  KEY `idx_pre_invitados_listado_id_invitados` (`id_invitados`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: pre_invitados_tel
CREATE TABLE `pre_invitados_tel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_pre_invitado` int(11) NOT NULL,
  `telefono` varchar(30) NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `id_invitados` int(11) DEFAULT NULL,
  `tel_enviar` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_pre_invitado` (`id_pre_invitado`),
  KEY `idx_pre_invitados_tel_id_invitados` (`id_invitados`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- WhatsApp y registro de envíos
-- ============================================================================

-- Table: invitados_a_enviar
CREATE TABLE `invitados_a_enviar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_invitados` int(11) NOT NULL,
  `id_invitados_tel` int(11) NOT NULL,
  `tel_enviar` varchar(255) DEFAULT NULL,
  `fecha_agregado` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_invitado_tel` (`id_invitados`,`id_invitados_tel`),
  KEY `fk_invitado_enviar` (`id_invitados`),
  KEY `fk_tel_enviar` (`id_invitados_tel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: invitados_enviados
CREATE TABLE `invitados_enviados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_invitados` int(11) NOT NULL,
  `id_invitados_tel` int(11) NOT NULL,
  `tel_enviar` varchar(255) DEFAULT NULL,
  `fecha_envio` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_invitado_tel_enviado` (`id_invitados`,`id_invitados_tel`),
  KEY `fk_invitado_enviado` (`id_invitados`),
  KEY `fk_tel_enviado` (`id_invitados_tel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: registro_mensajes_enviados
CREATE TABLE `registro_mensajes_enviados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_invitados` int(11) NOT NULL,
  `id_invitados_tel` int(11) NOT NULL,
  `tel_enviar` varchar(20) DEFAULT NULL,
  `fecha_envio` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_invitados` (`id_invitados`),
  KEY `id_invitados_tel` (`id_invitados_tel`),
  CONSTRAINT `registro_mensajes_enviados_ibfk_1` FOREIGN KEY (`id_invitados`) REFERENCES `invitados` (`id`),
  CONSTRAINT `registro_mensajes_enviados_ibfk_2` FOREIGN KEY (`id_invitados_tel`) REFERENCES `invitados_tel` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Regalos y tienda
-- ============================================================================

-- Table: carrito
CREATE TABLE `carrito` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT 1,
  `monto_libre` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table: regalos
CREATE TABLE `regalos` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(30) NOT NULL,
  `apellido` varchar(30) NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `forma_pago` varchar(30) DEFAULT NULL,
  `monto_total` decimal(10,2) DEFAULT NULL,
  `productos` text DEFAULT NULL,
  `reg_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `compartido` varchar(255) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `pago_con` int(10) DEFAULT NULL,
  `activo` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table: regalos_confirmacion
CREATE TABLE `regalos_confirmacion` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `regalo_id` int(6) unsigned NOT NULL,
  `confirm_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `regalo_id` (`regalo_id`),
  CONSTRAINT `regalos_confirmacion_ibfk_1` FOREIGN KEY (`regalo_id`) REFERENCES `regalos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table: regalos_detalles
CREATE TABLE `regalos_detalles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `regalo_id` int(6) unsigned NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `monto_libre` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `regalo_id` (`regalo_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `regalos_detalles_ibfk_1` FOREIGN KEY (`regalo_id`) REFERENCES `regalos` (`id`),
  CONSTRAINT `regalos_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ============================================================================
-- Triggers
-- ============================================================================
-- The UNIQUE key on invitados.codigo is the final integrity safeguard. A rare
-- concurrent collision can still make an INSERT fail; callers should retry it.
DELIMITER $$
CREATE TRIGGER `generar_codigo_invitado`
BEFORE INSERT ON `invitados`
FOR EACH ROW
BEGIN
    DECLARE nuevo_codigo VARCHAR(10);
    DECLARE codigo_existe INT;

    REPEAT
        SET nuevo_codigo = LPAD(FLOOR(RAND() * 1000000), 6, '0');
        SELECT COUNT(*) INTO codigo_existe
          FROM `invitados`
         WHERE `codigo` = nuevo_codigo;
    UNTIL codigo_existe = 0
    END REPEAT;

    SET NEW.codigo = nuevo_codigo;
END$$
DELIMITER ;
