/*
SQLyog Community
MySQL - 11.8.3-MariaDB-log : Database - u385461681_dqs_0015
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`u385461681_dqs_0015` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `u385461681_dqs_0015`;

/*Table structure for table `admin_config` */

DROP TABLE IF EXISTS `admin_config`;

CREATE TABLE `admin_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_carpeta` varchar(255) NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `admin_config` */

LOCK TABLES `admin_config` WRITE;

insert  into `admin_config`(`id`,`nombre_carpeta`,`fecha_creacion`) values 
(1,adminhOD9yE4Yeg,2026-03-02 21:13:45);

UNLOCK TABLES;

/*Table structure for table `carrito` */

DROP TABLE IF EXISTS `carrito`;

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

/*Data for the table `carrito` */

LOCK TABLES `carrito` WRITE;

UNLOCK TABLES;

/*Table structure for table `cliente` */

DROP TABLE IF EXISTS `cliente`;

CREATE TABLE `cliente` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) DEFAULT NULL,
  `apellido` varchar(50) DEFAULT NULL,
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
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `cliente` */

LOCK TABLES `cliente` WRITE;

insert  into `cliente`(`user_id`,`nombre`,`apellido`,`telefono`,`telefono2`,`direccion`,`cbu_titular`,`cbu`,`alias`,`ciudad`,`provincia`,`plan`,`cbu_dolar`,`alias_dolar`,`cotizacion_dolar`) values 
(1,Agustina,Micheloud,1141645608,,Pilar,MARIA AGUSTINA MICHELOUD,0070217330004028943447,ArturoyAgus,,,0,0070217331004028928021,ArturoyAgus.usd,0);

UNLOCK TABLES;

/*Table structure for table `imagenes` */

DROP TABLE IF EXISTS `imagenes`;

CREATE TABLE `imagenes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producto_id` int(11) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `imagenes_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `imagenes` */

LOCK TABLES `imagenes` WRITE;

UNLOCK TABLES;

/*Table structure for table `info_casamiento` */

DROP TABLE IF EXISTS `info_casamiento`;

CREATE TABLE `info_casamiento` (
  `portada_titulo` varchar(255) DEFAULT NULL,
  `portada_frase` varchar(255) DEFAULT NULL,
  `portada_fecha` varchar(255) DEFAULT NULL,
  `portada_fecha_hora` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_nopad_ci;

/*Data for the table `info_casamiento` */

LOCK TABLES `info_casamiento` WRITE;

insert  into `info_casamiento`(`portada_titulo`,`portada_frase`,`portada_fecha`,`portada_fecha_hora`) values 
(Arturo & Agus ,¡Nos casamos!,2 de mayo del 2026,2026-05-03 15:30:00);

UNLOCK TABLES;

/*Table structure for table `info_eventos` */

DROP TABLE IF EXISTS `info_eventos`;

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `info_eventos` */

LOCK TABLES `info_eventos` WRITE;

insert  into `info_eventos`(`id`,`fecha`,`titulo`,`descripcion`,`direccion`,`url`,`tipo_visual`,`imagen`,`icono`,`orden`,`activo`) values 
(1,2026-05-02,Ceremonia Religiosa,Lugar:Schoenstatt Sede "Los Olmos" - Pilar

Hora:15hs,Dirección: Raúl Alfonsín, B1629 Pilar, Provincia de Buenos Aires,https://maps.app.goo.gl/snoiYwK9xcTaRJaU9,imagen,1772840789_los olmoa1.jpg,fas fa-cross,0,1),
(2,2026-05-02,Fiesta,Después de la ceremonia ¡te esperamos para festejar!

Lugar: Estancia La Mimosa

Hora:A partir de las 17hs

¡Te Esperamos!,Dirección: RP6 km 171,5, Exaltación de la Cruz, Provincia de Buenos Aires,https://maps.app.goo.gl/JKACjrd6dFwaeay39,imagen,1772846850_descarga (1).jpg,fas fa-music,0,1),
(3,0000-00-00,Baile,Armamos un sector especial del salón, más despejado y cómodo, para que puedas disfrutar la música sin que las mesas molesten.
Es el lugar ideal para moverse, compartir y dejarse llevar por el ritmo.
¡Vení con ganas de bailar y pasarla increíble!,,,imagen,1754172590_salon_2.jpeg,fas fa-glass-cheers,0,0),
(4,0000-00-00,Otro evento,Descripción del evento,,,icono,,fas fa-glass-cheers,0,0),
(5,0000-00-00,Otro evento 2,Descripción del evento,,,icono,,fas fa-hotel,0,0),
(6,0000-00-00,Otro evento 3,Descripción del evento,,,icono,,fas fa-birthday-cake,0,0);

UNLOCK TABLES;

/*Table structure for table `info_historia` */

DROP TABLE IF EXISTS `info_historia`;

CREATE TABLE `info_historia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `titulo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `texto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `info_historia` */

LOCK TABLES `info_historia` WRITE;

insert  into `info_historia`(`id`,`fecha`,`titulo`,`texto`,`activo`) values 
(1,2021-06-15,Nos conocimos,Una tarde de invierno, en una librería de San Telmo, nuestras vidas se cruzaron por casualidad. Sofía buscaba un libro de Cortázar, y José, sin pensarlo, le recomendó uno de Borges. Entre risas y charla sobre literatura, intercambiamos números.,1),
(2,2021-07-10,Primera cita,Después de varias conversaciones por WhatsApp, nos animamos a salir. Nos encontramos en un café en Palermo, y entre café y medialunas, pasamos horas hablando de nuestros sueños y pasiones. Sentimos una conexión especial desde el primer momento.,1),
(3,2021-12-24,Primeras fiestas juntos,Fue nuestra primera Navidad juntos. Nos conocimos un poco más al compartir con nuestras familias. En Año Nuevo, vimos los fuegos artificiales desde la Costanera y prometimos que el próximo año sería aún mejor.,1),
(4,2022-05-15,Viaje a Bariloche,Decidimos hacer nuestro primer viaje juntos a Bariloche. Entre caminatas por los senderos del Llao Llao y chocolates calientes en el centro, nos dimos cuenta de lo bien que nos llevábamos en cualquier lugar.,1),
(5,2022-10-02,Nos fuimos a vivir juntos,Después de un año y medio de relación, decidimos dar el siguiente paso: alquilamos un departamento en Belgrano. Aunque la convivencia tenía sus desafíos, amábamos compartir el día a día, desde cocinar juntos hasta elegir qué película ver cada noche.,1),
(6,2023-02-14,Nos comprometimos,José preparó una sorpresa para el día de San Valentín. Me llevó a Tigre, a nuestro lugar favorito junto al río, y sacó un anillo. “¿Querés casarte conmigo?” preguntó, nervioso pero con una sonrisa. Sin dudarlo, dije que sí, entre lágrimas y abrazos.,1),
(7,2023-09-15,Preparativos de la boda,Entre prueba de vestidos, elección del catering y lista de invitados, los meses pasaban volando. Queríamos que la boda reflejara nuestra historia, sencilla pero llena de amor.,1),
(8,2024-10-20,El gran día,Después de tres años juntos, llegó el día que tanto soñamos. Con nuestras familias y amigos como testigos, nos dimos el “sí, quiero” en una hermosa ceremonia al aire libre. Bailamos hasta el amanecer, celebrando nuestro amor y el comienzo de una nueva etapa.,1);

UNLOCK TABLES;

/*Table structure for table `info_mostrar` */

DROP TABLE IF EXISTS `info_mostrar`;

CREATE TABLE `info_mostrar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seccion` varchar(50) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `info_mostrar` */

LOCK TABLES `info_mostrar` WRITE;

insert  into `info_mostrar`(`id`,`seccion`,`activo`) values 
(1,about,0),
(2,story,0),
(3,gallery,0),
(4,events,1),
(5,wedding,1),
(6,contact,1),
(7,cronometro,1),
(8,logo,1);

UNLOCK TABLES;

/*Table structure for table `info_nosotros` */

DROP TABLE IF EXISTS `info_nosotros`;

CREATE TABLE `info_nosotros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `texto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` int(1) NOT NULL,
  `orden` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `info_nosotros` */

LOCK TABLES `info_nosotros` WRITE;

insert  into `info_nosotros`(`id`,`nombre`,`texto`,`activo`,`orden`) values 
(1,Maria,está viviendo un momento único en su vida: está a punto de casarse con él, el hombre con quien comparte su presente y sueña su futuro. Desde siempre, imaginó este momento, pero ahora que está tan cerca, lo vive con emoción, nervios y mucha ilusión.

Es diseñadora gráfica y ama todo lo relacionado con el arte y la creatividad. Su trabajo le permite expresar su estilo y crear piezas visuales que transmiten emociones. En su tiempo libre, le gusta pintar, hacer lettering y sacar fotos con su cámara analógica. También disfruta recorrer ferias de diseño y descubrir pequeños cafés escondidos en la ciudad.

Sofía es una persona sociable y cariñosa, siempre rodeada de amigos y familia. Organiza encuentros en su casa con mates y medialunas, y le encanta conversar durante horas. Es fanática de los libros de romance y siempre tiene uno en su cartera. También le gusta la música indie y el cine, en especial las películas con historias profundas y visuales impactantes.

En cuanto a su estilo de vida, es relajada pero organizada. Le gusta hacer yoga para desconectar del estrés y salir a caminar por la ciudad sin rumbo fijo. Disfruta cocinar, aunque admite que lo suyo son más los postres que las comidas elaboradas.

Ahora que está por casarse, Sofía siente que está en una montaña rusa de emociones. Quiere que la boda refleje su personalidad y que cada detalle sea especial. Más allá de la fiesta, lo que más le importa es la vida que va a construir con Martín, llena de amor, complicidad y proyectos en común.,1,NULL),
(2,Jose,es un joven entusiasta y soñador que está a punto de dar uno de los pasos más importantes de su vida: casarse con el amor de su vida, Sofía. Desde pequeño, siempre imaginó formar una familia y construir un hogar lleno de amor y compañerismo.

Le encanta la tecnología y trabaja como ingeniero en una empresa de software, donde desarrolla aplicaciones móviles. Es una persona meticulosa, organizada y siempre busca mejorar las cosas a su alrededor. En su tiempo libre, disfruta de los videojuegos, salir a correr por los parques de Palermo y probar nuevas cafeterías con Sofía.

Si bien es fanático de la tecnología, también le apasiona la música. Toca la guitarra desde los 15 años y siempre sueña con armar una banda con sus amigos. Tiene gustos variados: desde rock nacional hasta música indie. Además, le gusta el cine y tiene un especial cariño por las películas de ciencia ficción.

En cuanto a su estilo de vida, es una persona activa. Le gusta mantenerse en forma, pero no es de los que van religiosamente al gimnasio; prefiere deportes al aire libre como el fútbol y el ciclismo. También es un amante de la comida casera y suele preparar cenas especiales para Sofía los fines de semana.

Ahora que está a punto de casarse, Martín se siente emocionado y un poco nervioso. Quiere que todo salga perfecto, pero también ha aprendido a disfrutar del proceso. Sabe que el matrimonio no es solo una ceremonia, sino un viaje de aprendizaje y crecimiento junto a la persona que ama.,1,NULL);

UNLOCK TABLES;

/*Table structure for table `info_otra` */

DROP TABLE IF EXISTS `info_otra`;

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `info_otra` */

LOCK TABLES `info_otra` WRITE;

insert  into `info_otra`(`id`,`titulo`,`descripcion`,`direccion`,`url`,`icono`,`activo`,`orden`) values 
(1,Dress Code,Formal pero sin perder tu esencia ¡Sorprendenos!,,,fas fa-user-tie,1,1),
(2,Redes,Seguinos en nuestra red social para enterarte de todas las novedades,,https://instagram.com/dijequesi.ar,fab fa-instagram,0,4),
(3,Instagram,Seguinos en instagram para estar al tanto de la preparación. Y así tambien despues no podes etiquetar en la foto que quieras,,https://www.instagram.com/dijequesi.ar,fab fa-instagram,0,5),
(4,¿Qué canción no puede faltar?,¡Ayudanos sumando las canciones que pensas que no pueden faltar en la fiesta!,,https://open.spotify.com/playlist/2tmFlVOK1a4i0lLUkVTN4G?si=1XTHLfHmRYus5yyqZWvsNg&pt=bb60f79a95b37105e1fad942cc7707ca&pi=2x7jIQxiQdOM6,fas fa-music,1,6);

UNLOCK TABLES;

/*Table structure for table `intivados_acompanante` */

DROP TABLE IF EXISTS `intivados_acompanante`;

CREATE TABLE `intivados_acompanante` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_acompanante` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `intivados_acompanante` */

LOCK TABLES `intivados_acompanante` WRITE;

insert  into `intivados_acompanante`(`id`,`categoria_acompanante`) values 
(1,Solo/a),
(2,Flia),
(3,Novio/a),
(4,Sr/a),
(5,Amigo/a);

UNLOCK TABLES;

/*Table structure for table `invitaciones_estado` */

DROP TABLE IF EXISTS `invitaciones_estado`;

CREATE TABLE `invitaciones_estado` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_invitado` int(11) NOT NULL,
  `id_invitados_tel` int(11) NOT NULL,
  `fecha_envio` datetime DEFAULT NULL,
  `estado_api` varchar(20) DEFAULT NULL,
  `detalle_api` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `invitaciones_estado` */

LOCK TABLES `invitaciones_estado` WRITE;

UNLOCK TABLES;

/*Table structure for table `invitados` */

DROP TABLE IF EXISTS `invitados`;

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
  `confirmacion_comentario2` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `invitados` */

LOCK TABLES `invitados` WRITE;

insert  into `invitados`(`id`,`nombre`,`apellido`,`activo`,`acompanado`,`cantidad_mayores`,`id_prioridad`,`ingreso`,`cantidad_menores`,`fecha_registro`,`confirmacion`,`confirmacion_fecha`,`confirmacion_comentario`,`confirmacion_mayores`,`confirmacion_menores`,`alimento`,`codigo`,`confirmacion_comentario2`) values 
(1,Facundo,Romero,2,1,2,1,Inicio,1,2026-03-09,Si,2026-03-09 16:30:18,,2,1,No,988518,Acá iria un mensaje de prueba);

UNLOCK TABLES;

/*Table structure for table `invitados_a_enviar` */

DROP TABLE IF EXISTS `invitados_a_enviar`;

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

/*Data for the table `invitados_a_enviar` */

LOCK TABLES `invitados_a_enviar` WRITE;

UNLOCK TABLES;

/*Table structure for table `invitados_enviados` */

DROP TABLE IF EXISTS `invitados_enviados`;

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

/*Data for the table `invitados_enviados` */

LOCK TABLES `invitados_enviados` WRITE;

UNLOCK TABLES;

/*Table structure for table `invitados_listado_mesa` */

DROP TABLE IF EXISTS `invitados_listado_mesa`;

CREATE TABLE `invitados_listado_mesa` (
  `id_invitados` int(11) DEFAULT NULL,
  `nombre_invitado` varchar(50) DEFAULT NULL,
  `mesa` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre2` varchar(50) DEFAULT NULL,
  `apellido2` varchar(50) DEFAULT NULL,
  UNIQUE KEY `orden_id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `invitados_listado_mesa` */

LOCK TABLES `invitados_listado_mesa` WRITE;

insert  into `invitados_listado_mesa`(`id_invitados`,`nombre_invitado`,`mesa`,`id`,`nombre2`,`apellido2`) values 
(1,Facundo,NULL,1,Facundo,Romero),
(1,Felicitas,NULL,2,Felicitas,Bullrich),
(1,Baltazar,NULL,3,Baltazar,Romero Bullrich);

UNLOCK TABLES;

/*Table structure for table `invitados_prioridad` */

DROP TABLE IF EXISTS `invitados_prioridad`;

CREATE TABLE `invitados_prioridad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_prioridad` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `invitados_prioridad` */

LOCK TABLES `invitados_prioridad` WRITE;

insert  into `invitados_prioridad`(`id`,`categoria_prioridad`) values 
(1,Importante),
(2,Medio Importante),
(3,Normal),
(4,No necesario);

UNLOCK TABLES;

/*Table structure for table `invitados_tel` */

DROP TABLE IF EXISTS `invitados_tel`;

CREATE TABLE `invitados_tel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_invitados` int(11) DEFAULT NULL,
  `tel_enviar` bigint(20) DEFAULT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `invitados_tel` */

LOCK TABLES `invitados_tel` WRITE;

UNLOCK TABLES;

/*Table structure for table `pre_invitados` */

DROP TABLE IF EXISTS `pre_invitados`;

CREATE TABLE `pre_invitados` (
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `pre_invitados` */

LOCK TABLES `pre_invitados` WRITE;

insert  into `pre_invitados`(`id`,`nombre`,`apellido`,`activo`,`acompanado`,`cantidad_mayores`,`id_prioridad`,`ingreso`,`cantidad_menores`,`fecha_registro`,`confirmacion`,`confirmacion_fecha`,`confirmacion_comentario`,`confirmacion_mayores`,`confirmacion_menores`,`alimento`,`codigo`) values 
(1,Jose,Moreno,1,1,1,1,Inicio,0,2026-03-09,NULL,NULL,NULL,NULL,NULL,NULL,675211);

UNLOCK TABLES;

/*Table structure for table `pre_invitados_listado_mesa` */

DROP TABLE IF EXISTS `pre_invitados_listado_mesa`;

CREATE TABLE `pre_invitados_listado_mesa` (
  `id_invitados` int(11) DEFAULT NULL,
  `nombre_invitado` varchar(50) DEFAULT NULL,
  `mesa` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre2` varchar(50) DEFAULT NULL,
  `apellido2` varchar(50) DEFAULT NULL,
  UNIQUE KEY `orden_id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `pre_invitados_listado_mesa` */

LOCK TABLES `pre_invitados_listado_mesa` WRITE;

insert  into `pre_invitados_listado_mesa`(`id_invitados`,`nombre_invitado`,`mesa`,`id`,`nombre2`,`apellido2`) values 
(1,Pepe,NULL,1,NULL,NULL);

UNLOCK TABLES;

/*Table structure for table `pre_invitados_tel` */

DROP TABLE IF EXISTS `pre_invitados_tel`;

CREATE TABLE `pre_invitados_tel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_invitados` int(11) DEFAULT NULL,
  `tel_enviar` bigint(20) DEFAULT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `pre_invitados_tel` */

LOCK TABLES `pre_invitados_tel` WRITE;

insert  into `pre_invitados_tel`(`id`,`id_invitados`,`tel_enviar`) values 
(1,1,1478569855);

UNLOCK TABLES;

/*Table structure for table `productos` */

DROP TABLE IF EXISTS `productos`;

CREATE TABLE `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `activo` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `productos` */

LOCK TABLES `productos` WRITE;

insert  into `productos`(`id`,`titulo`,`descripcion`,`precio`,`activo`) values 
(1,Gift Card,Elegí el monto que quieras regalar.,0.00,1);

UNLOCK TABLES;

/*Table structure for table `regalos` */

DROP TABLE IF EXISTS `regalos`;

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

/*Data for the table `regalos` */

LOCK TABLES `regalos` WRITE;

UNLOCK TABLES;

/*Table structure for table `regalos_confirmacion` */

DROP TABLE IF EXISTS `regalos_confirmacion`;

CREATE TABLE `regalos_confirmacion` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `regalo_id` int(6) unsigned NOT NULL,
  `confirm_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `regalo_id` (`regalo_id`),
  CONSTRAINT `regalos_confirmacion_ibfk_1` FOREIGN KEY (`regalo_id`) REFERENCES `regalos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `regalos_confirmacion` */

LOCK TABLES `regalos_confirmacion` WRITE;

UNLOCK TABLES;

/*Table structure for table `regalos_detalles` */

DROP TABLE IF EXISTS `regalos_detalles`;

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

/*Data for the table `regalos_detalles` */

LOCK TABLES `regalos_detalles` WRITE;

UNLOCK TABLES;

/*Table structure for table `registro_mensajes_enviados` */

DROP TABLE IF EXISTS `registro_mensajes_enviados`;

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

/*Data for the table `registro_mensajes_enviados` */

LOCK TABLES `registro_mensajes_enviados` WRITE;

UNLOCK TABLES;

/*Table structure for table `site_settings` */

DROP TABLE IF EXISTS `site_settings`;

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

/*Data for the table `site_settings` */

LOCK TABLES `site_settings` WRITE;

UNLOCK TABLES;

/*Table structure for table `user` */

DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `user` */

LOCK TABLES `user` WRITE;

insert  into `user`(`id`,`email`,`password`,`fecha_registro`) values 
(1,micheloudagustina@gmail.com,$2y$10$Kh4.Fjz1aIZQO9RbqkAMIOazHfUCCJS/uKQb2HJNwyQDprjz3m6uK,2026-03-02 21:13:45);

UNLOCK TABLES;

/*Table structure for table `visitas` */

DROP TABLE IF EXISTS `visitas`;

CREATE TABLE `visitas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_visita` timestamp NULL DEFAULT current_timestamp(),
  `ip_usuario` varchar(45) NOT NULL,
  `pagina_visitada` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=219 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `visitas` */

LOCK TABLES `visitas` WRITE;

insert  into `visitas`(`id`,`fecha_visita`,`ip_usuario`,`pagina_visitada`) values 
(1,2026-03-02 21:17:25,186.152.181.188,/),
(2,2026-03-02 21:19:33,2800:2130:4a40:6fb:5cf8:1e9b:22a8:45c3,/),
(3,2026-03-02 21:19:36,66.249.85.71,/),
(4,2026-03-02 21:19:37,66.102.8.163,/),
(5,2026-03-02 21:19:37,66.102.8.167,/),
(6,2026-03-02 23:58:06,103.71.161.54,/),
(7,2026-03-03 00:31:36,45.161.118.102,/),
(8,2026-03-03 00:34:30,45.161.118.109,/),
(9,2026-03-03 00:47:37,45.161.118.100,/),
(10,2026-03-03 00:48:01,111.243.103.7,/),
(11,2026-03-03 00:50:24,45.161.118.110,/),
(12,2026-03-03 00:51:32,45.161.118.104,/),
(13,2026-03-03 01:10:39,45.161.118.109,/),
(14,2026-03-03 01:15:09,45.161.118.108,/),
(15,2026-03-03 01:16:55,45.161.118.105,/tienda/),
(16,2026-03-03 01:17:24,45.161.118.105,/),
(17,2026-03-03 02:50:44,34.106.189.69,/),
(18,2026-03-03 03:57:23,49.205.44.165,/),
(19,2026-03-03 03:57:25,49.205.44.165,/tienda/),
(20,2026-03-03 04:13:05,34.91.49.50,/),
(21,2026-03-03 04:38:20,198.13.62.122,/),
(22,2026-03-03 05:49:40,2401:4900:8847:d5b9:dc70:6d1a:1ef2:aab5,/),
(23,2026-03-03 06:49:50,2a02:4780:b:b::2,/),
(24,2026-03-03 07:45:23,77.245.163.188,/),
(25,2026-03-03 09:41:00,167.172.226.232,/),
(26,2026-03-03 09:48:06,197.35.181.91,/),
(27,2026-03-03 09:50:22,121.127.245.210,/),
(28,2026-03-03 09:50:23,121.127.245.210,/index.php),
(29,2026-03-03 10:44:53,34.23.18.183,/),
(30,2026-03-03 13:19:31,100.27.4.33,/),
(31,2026-03-03 13:43:56,15.134.129.13,/),
(32,2026-03-03 13:58:42,100.54.233.33,/),
(33,2026-03-03 13:58:56,15.135.187.195,/),
(34,2026-03-03 14:38:02,104.236.251.206,/),
(35,2026-03-03 15:57:52,35.232.66.94,/),
(36,2026-03-03 16:10:28,35.232.166.255,/),
(37,2026-03-03 16:22:31,157.143.20.242,/),
(38,2026-03-03 16:31:43,103.157.204.219,/),
(39,2026-03-03 16:31:44,103.157.204.219,/index.php),
(40,2026-03-03 17:07:22,2600:1900:0:4005::1d00,/),
(41,2026-03-03 17:14:23,23.27.145.170,/),
(42,2026-03-03 17:20:46,210.64.24.100,/),
(43,2026-03-03 17:45:32,84.32.41.136,/),
(44,2026-03-03 17:45:34,84.32.41.136,/tienda/),
(45,2026-03-03 18:08:44,23.27.145.68,/),
(46,2026-03-03 18:39:56,185.242.177.58,/),
(47,2026-03-03 18:39:57,185.242.177.54,/),
(48,2026-03-03 19:01:50,185.242.177.55,/),
(49,2026-03-03 19:01:50,185.242.177.56,/),
(50,2026-03-03 19:54:29,185.242.177.4,/),
(51,2026-03-03 21:09:27,2600:1f18:66cd:1a50:160c:f7cf:4cb7:b5a8,/),
(52,2026-03-03 21:33:38,143.92.32.96,/index.php),
(53,2026-03-03 22:24:01,2001:bc8:1da0:1e:da5e:d3ff:fe43:f710,/),
(54,2026-03-03 23:28:18,103.171.34.27,/),
(55,2026-03-03 23:28:19,103.171.34.27,/index.php),
(56,2026-03-03 23:41:28,195.211.77.141,/),
(57,2026-03-03 23:59:03,20.245.161.103,/),
(58,2026-03-04 00:05:36,198.199.118.83,/),
(59,2026-03-04 02:07:19,84.32.41.136,/),
(60,2026-03-04 02:07:21,84.32.41.136,/tienda/),
(61,2026-03-04 02:57:40,2407:3640:2304:9::1,/),
(62,2026-03-04 02:57:40,2407:3640:2304:9::1,/index.php),
(63,2026-03-04 04:46:10,44.249.133.92,/),
(64,2026-03-04 04:46:11,184.32.177.117,/),
(65,2026-03-04 07:59:30,2a02:4780:b:b::2,/),
(66,2026-03-04 08:33:03,47.237.191.136,/),
(67,2026-03-04 08:33:04,47.237.191.136,/index.php),
(68,2026-03-04 10:30:49,219.100.37.234,/),
(69,2026-03-04 10:45:20,104.233.211.33,/),
(70,2026-03-04 10:45:20,104.233.211.33,/index.php),
(71,2026-03-04 11:41:30,159.89.183.159,/),
(72,2026-03-04 12:24:47,123.57.111.10,/),
(73,2026-03-04 12:24:48,123.57.111.10,/index.php),
(74,2026-03-04 12:47:49,159.65.247.131,/tienda/),
(75,2026-03-04 12:54:06,35.175.135.200,/),
(76,2026-03-04 15:10:42,3.96.222.128,/),
(77,2026-03-04 15:31:13,93.158.91.235,/),
(78,2026-03-04 15:56:02,54.213.15.59,/),
(79,2026-03-04 16:02:38,54.81.62.158,/),
(80,2026-03-04 17:55:09,107.151.244.171,/),
(81,2026-03-04 17:55:09,107.151.244.171,/index.php),
(82,2026-03-04 18:17:12,2407:3640:2304:9::1,/),
(83,2026-03-04 18:17:13,2407:3640:2304:9::1,/index.php),
(84,2026-03-04 18:25:21,45.161.118.107,/),
(85,2026-03-04 18:37:27,38.145.220.158,/),
(86,2026-03-04 18:37:28,38.145.220.158,/index.php),
(87,2026-03-04 19:52:25,2a02:c207:2301:586::1,/),
(88,2026-03-04 19:52:25,2a02:c207:2301:586::1,/index.php),
(89,2026-03-04 20:56:19,121.127.245.210,/),
(90,2026-03-04 20:56:20,121.127.245.210,/index.php),
(91,2026-03-04 23:09:35,141.51.110.126,/),
(92,2026-03-05 03:32:41,134.122.140.83,/),
(93,2026-03-05 03:32:41,134.122.140.83,/index.php),
(94,2026-03-05 04:24:17,147.92.45.210,/),
(95,2026-03-05 04:24:18,147.92.45.210,/index.php),
(96,2026-03-05 05:27:45,107.151.244.171,/),
(97,2026-03-05 05:27:45,107.151.244.171,/index.php),
(98,2026-03-05 05:40:35,2a02:4780:b:b::2,/),
(99,2026-03-05 06:42:50,2001:bc8:1da0:1f:da5e:d3ff:fe43:f790,/),
(100,2026-03-05 10:18:33,74.7.241.5,/),
(101,2026-03-05 10:19:36,74.7.241.5,/tienda/),
(102,2026-03-05 12:38:19,2001:bc8:701:51:da5e:d3ff:fe44:a4,/),
(103,2026-03-05 12:42:54,54.234.215.188,/),
(104,2026-03-05 12:51:35,34.169.249.34,/),
(105,2026-03-05 13:57:03,37.19.223.109,/),
(106,2026-03-05 14:27:14,115.190.248.146,/),
(107,2026-03-05 14:27:15,115.190.248.146,/index.php),
(108,2026-03-05 16:11:59,98.87.13.238,/),
(109,2026-03-05 16:26:28,45.158.21.217,/),
(110,2026-03-05 16:26:28,45.158.21.217,/index.php),
(111,2026-03-05 19:26:42,107.151.244.171,/),
(112,2026-03-05 19:26:43,107.151.244.171,/index.php),
(113,2026-03-05 20:47:28,152.32.141.62,/),
(114,2026-03-05 20:47:30,152.32.141.62,/index.php),
(115,2026-03-05 21:21:00,104.233.211.33,/),
(116,2026-03-05 21:21:00,104.233.211.33,/index.php),
(117,2026-03-06 04:13:16,47.237.9.124,/),
(118,2026-03-06 04:13:17,47.237.9.124,/index.php),
(119,2026-03-06 06:00:42,34.127.33.101,/),
(120,2026-03-06 07:29:33,2a02:c207:2282:2578::1,/),
(121,2026-03-06 07:29:33,2a02:c207:2282:2578::1,/index.php),
(122,2026-03-06 07:31:31,154.86.20.5,/),
(123,2026-03-06 07:31:32,154.86.20.5,/index.php),
(124,2026-03-06 09:15:57,34.134.86.1,/),
(125,2026-03-06 10:21:18,34.228.225.232,/),
(126,2026-03-06 12:27:17,198.44.249.36,/),
(127,2026-03-06 12:27:18,198.44.249.36,/index.php),
(128,2026-03-06 12:58:24,32.192.201.81,/),
(129,2026-03-06 13:12:13,2001:bc8:1da0:1f:da5e:d3ff:fe43:f764,/),
(130,2026-03-06 15:53:58,54.81.245.146,/),
(131,2026-03-06 16:41:50,178.22.106.230,/),
(132,2026-03-06 17:37:48,74.7.243.237,/),
(133,2026-03-06 17:38:10,74.7.243.237,/tienda/),
(134,2026-03-06 20:48:49,2001:df1:7880:100::a30,/),
(135,2026-03-06 20:48:50,2001:df1:7880:100::a30,/index.php),
(136,2026-03-06 21:36:02,2601:1c0:5000:5ea9:3608:eabc:c2b:f530,/),
(137,2026-03-06 22:07:06,194.230.158.210,/),
(138,2026-03-06 22:10:10,182.69.176.68,/),
(139,2026-03-06 22:10:12,182.69.176.68,/tienda/),
(140,2026-03-06 22:44:55,34.239.163.187,/),
(141,2026-03-06 23:35:23,45.161.118.108,/),
(142,2026-03-06 23:38:04,45.161.118.100,/),
(143,2026-03-06 23:45:27,45.161.118.103,/),
(144,2026-03-06 23:49:27,45.161.118.101,/),
(145,2026-03-06 23:51:50,45.161.118.104,/tienda/),
(146,2026-03-06 23:52:00,45.161.118.104,/tienda/?currency=2&sort=default),
(147,2026-03-06 23:52:31,45.161.118.104,/),
(148,2026-03-07 00:21:39,66.232.15.198,/),
(149,2026-03-07 00:21:40,66.232.15.198,/index.php),
(150,2026-03-07 00:53:35,45.161.118.106,/),
(151,2026-03-07 00:54:59,45.161.118.106,/tienda/),
(152,2026-03-07 01:11:56,45.161.118.100,/),
(153,2026-03-07 01:17:43,45.161.118.102,/),
(154,2026-03-07 01:19:31,45.161.118.109,/),
(155,2026-03-07 01:29:08,45.161.118.105,/),
(156,2026-03-07 01:30:39,45.161.118.101,/),
(157,2026-03-07 02:44:39,62.234.46.149,/),
(158,2026-03-07 02:44:41,62.234.46.149,/index.php),
(159,2026-03-07 02:57:50,103.49.11.114,/),
(160,2026-03-07 02:57:51,103.49.11.114,/index.php),
(161,2026-03-07 03:25:39,185.239.84.54,/),
(162,2026-03-07 03:25:40,185.239.84.54,/index.php),
(163,2026-03-07 09:08:58,50.114.92.180,/),
(164,2026-03-07 10:24:40,45.161.118.103,/tienda/),
(165,2026-03-07 10:25:01,45.161.118.103,/),
(166,2026-03-07 12:34:43,54.82.217.140,/),
(167,2026-03-07 14:24:03,5.133.192.189,/),
(168,2026-03-07 14:33:35,192.71.142.176,/),
(169,2026-03-07 15:02:53,138.68.120.52,/),
(170,2026-03-07 15:02:53,88.97.165.242,/),
(171,2026-03-07 15:11:13,87.52.106.76,/),
(172,2026-03-07 15:12:29,212.102.39.69,/),
(173,2026-03-07 15:29:20,103.157.204.219,/),
(174,2026-03-07 15:29:21,103.157.204.219,/index.php),
(175,2026-03-07 17:26:09,210.64.24.100,/),
(176,2026-03-07 18:37:55,45.202.1.22,/),
(177,2026-03-07 18:37:56,45.202.1.22,/index.php),
(178,2026-03-07 19:40:34,2620:96:e000::c2,/),
(179,2026-03-07 20:35:44,2620:96:e000::12d,/),
(180,2026-03-08 02:52:23,45.197.149.104,/),
(181,2026-03-08 02:52:24,45.197.149.104,/index.php),
(182,2026-03-08 03:37:24,2a02:4780:b:b::2,/),
(183,2026-03-08 06:45:44,39.109.113.59,/),
(184,2026-03-08 06:45:45,39.109.113.59,/index.php),
(185,2026-03-08 07:57:13,112.121.173.122,/),
(186,2026-03-08 07:57:14,112.121.173.122,/index.php),
(187,2026-03-08 12:26:53,54.157.187.207,/),
(188,2026-03-08 12:31:11,35.185.209.55,/),
(189,2026-03-08 14:31:05,45.161.118.104,/),
(190,2026-03-08 14:37:24,2a02:c207:2301:586::1,/),
(191,2026-03-08 14:37:24,2a02:c207:2301:586::1,/index.php),
(192,2026-03-08 15:00:05,45.161.118.108,/),
(193,2026-03-08 15:00:38,45.161.118.103,/),
(194,2026-03-08 16:25:00,123.57.111.10,/),
(195,2026-03-08 16:25:01,123.57.111.10,/index.php),
(196,2026-03-08 19:02:16,45.161.118.109,/),
(197,2026-03-08 19:31:42,45.161.118.108,/),
(198,2026-03-08 19:37:25,45.161.118.109,/),
(199,2026-03-08 19:38:25,45.161.118.103,/),
(200,2026-03-08 20:08:49,45.161.118.102,/),
(201,2026-03-08 20:09:16,45.161.118.108,/),
(202,2026-03-08 20:14:49,45.161.118.106,/),
(203,2026-03-08 20:26:45,45.161.118.103,/),
(204,2026-03-08 23:17:49,2a07:e05:3:2c::1,/),
(205,2026-03-09 01:26:15,107.151.244.171,/),
(206,2026-03-09 01:26:15,107.151.244.171,/index.php),
(207,2026-03-09 06:46:05,2001:bc8:1f90:23:da5e:d3ff:fe49:9a4c,/),
(208,2026-03-09 06:54:13,74.7.243.237,/),
(209,2026-03-09 09:06:01,74.7.241.5,/),
(210,2026-03-09 09:38:28,43.242.128.48,/),
(211,2026-03-09 09:38:29,43.242.128.48,/index.php),
(212,2026-03-09 12:08:56,100.27.194.119,/),
(213,2026-03-09 15:25:20,2407:3640:2304:9::1,/),
(214,2026-03-09 15:25:21,2407:3640:2304:9::1,/index.php),
(215,2026-03-09 16:01:33,98.80.104.114,/),
(216,2026-03-09 16:10:10,186.152.181.188,/),
(217,2026-03-09 16:11:08,186.152.181.188,/clientes/dqs_0015/),
(218,2026-03-09 16:24:11,186.152.181.188,/tienda/);

UNLOCK TABLES;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
