/*Table structure for table `admin_config` */

DROP TABLE IF EXISTS `admin_config`;

CREATE TABLE `admin_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_carpeta` varchar(255) NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `admin_config` */


/*Table structure for table `cliente` */

DROP TABLE IF EXISTS `cliente`;

CREATE TABLE `cliente` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) DEFAULT NULL,
  `apellido` VARCHAR(50) DEFAULT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL,
  `telefono2` VARCHAR(20) DEFAULT NULL,
  `direccion` VARCHAR(100) DEFAULT NULL,
  `cbu_titular` VARCHAR(100) DEFAULT NULL,
  `cbu` VARCHAR(22) DEFAULT NULL,
  `alias` VARCHAR(100) DEFAULT NULL,
  `ciudad` VARCHAR(100) DEFAULT NULL,
  `provincia` VARCHAR(100) DEFAULT NULL,
  `plan` INT(10) DEFAULT NULL,
  `cbu_dolar` VARCHAR(100) DEFAULT NULL,
  `alias_dolar` VARCHAR(100) DEFAULT NULL,
  `cotizacion_dolar` INT(10) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;




/*Table structure for table `user` */

DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




/*Table structure for table `visitas` */
DROP TABLE IF EXISTS `visitas`;

CREATE TABLE `visitas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_visita` timestamp NULL DEFAULT current_timestamp(),
  `ip_usuario` varchar(45) NOT NULL,
  `pagina_visitada` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;



/*Table structure for table `productos` */

DROP TABLE IF EXISTS `productos`;

CREATE TABLE `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `activo` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;



/*Table structure for table `carrito` */
DROP TABLE IF EXISTS `carrito`;

CREATE TABLE `carrito` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `carrito` */



/*Table structure for table `imagenes` */

DROP TABLE IF EXISTS `imagenes`;

CREATE TABLE `imagenes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producto_id` int(11) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `imagenes_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


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
('#Maria y #Jose','Nos casamos','El 8 de diciembre del 2025','2025-12-08 17:00:00');

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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `info_eventos` */

LOCK TABLES `info_eventos` WRITE;

insert  into `info_eventos`(`id`,`fecha`,`titulo`,`descripcion`,`direccion`,`url`,`tipo_visual`,`imagen`,`icono`,`orden`,`activo`) values 
(1,'0000-00-00','Ceremonia','Basílica Nuestra Señora del Pilar','Junín 1898, Cdad. Autónoma de Buenos Aires','https://maps.app.goo.gl/chfBEb6dxNg3RSCNA','imagen','1754171387_pilar-recoleta.jpg','fas fa-cross',0,1),
(2,'0000-00-00','Fiesta','La fiesta la aremos en el salón más lindo','Av. Corrientes, Cdad. Autónoma de Buenos Aires','https://maps.app.goo.gl/Rrq9EijK5yXBrzmCA','imagen','1754172086_salon.jpeg','fas fa-music',0,1),
(3,'0000-00-00','Baile','Armamos un sector especial del salón, más despejado y cómodo, para que puedas disfrutar la música sin que las mesas molesten.\r\nEs el lugar ideal para moverse, compartir y dejarse llevar por el ritmo.\r\n¡Vení con ganas de bailar y pasarla increíble!','','','imagen','1754172590_salon_2.jpeg','fas fa-glass-cheers',0,1),
(4,'0000-00-00','Otro evento','Descripción del evento','','','icono','','fas fa-glass-cheers',0,0),
(5,'0000-00-00','Otro evento 2','Descripción del evento','','','icono','','fas fa-hotel',0,0),
(6,'0000-00-00','Otro evento 3','Descripción del evento','','','icono','','fas fa-birthday-cake',0,0);

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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `info_historia` */

LOCK TABLES `info_historia` WRITE;

insert  into `info_historia`(`id`,`fecha`,`titulo`,`texto`,`activo`) values 
(1,'2021-06-15','Nos conocimos','Una tarde de invierno, en una librería de San Telmo, nuestras vidas se cruzaron por casualidad. Sofía buscaba un libro de Cortázar, y José, sin pensarlo, le recomendó uno de Borges. Entre risas y charla sobre literatura, intercambiamos números.',1),
(2,'2021-07-10','Primera cita','Después de varias conversaciones por WhatsApp, nos animamos a salir. Nos encontramos en un café en Palermo, y entre café y medialunas, pasamos horas hablando de nuestros sueños y pasiones. Sentimos una conexión especial desde el primer momento.',1),
(3,'2021-12-24','Primeras fiestas juntos','Fue nuestra primera Navidad juntos. Nos conocimos un poco más al compartir con nuestras familias. En Año Nuevo, vimos los fuegos artificiales desde la Costanera y prometimos que el próximo año sería aún mejor.',1),
(4,'2022-05-15','Viaje a Bariloche','Decidimos hacer nuestro primer viaje juntos a Bariloche. Entre caminatas por los senderos del Llao Llao y chocolates calientes en el centro, nos dimos cuenta de lo bien que nos llevábamos en cualquier lugar.',1),
(5,'2022-10-02','Nos fuimos a vivir juntos','Después de un año y medio de relación, decidimos dar el siguiente paso: alquilamos un departamento en Belgrano. Aunque la convivencia tenía sus desafíos, amábamos compartir el día a día, desde cocinar juntos hasta elegir qué película ver cada noche.',1),
(6,'2023-02-14','Nos comprometimos','José preparó una sorpresa para el día de San Valentín. Me llevó a Tigre, a nuestro lugar favorito junto al río, y sacó un anillo. “¿Querés casarte conmigo?” preguntó, nervioso pero con una sonrisa. Sin dudarlo, dije que sí, entre lágrimas y abrazos.',1),
(7,'2023-09-15','Preparativos de la boda','Entre prueba de vestidos, elección del catering y lista de invitados, los meses pasaban volando. Queríamos que la boda reflejara nuestra historia, sencilla pero llena de amor.',1),
(8,'2024-10-20','El gran día','Después de tres años juntos, llegó el día que tanto soñamos. Con nuestras familias y amigos como testigos, nos dimos el “sí, quiero” en una hermosa ceremonia al aire libre. Bailamos hasta el amanecer, celebrando nuestro amor y el comienzo de una nueva etapa.',1);

UNLOCK TABLES;

/*Table structure for table `info_mostrar` */

DROP TABLE IF EXISTS `info_mostrar`;

CREATE TABLE `info_mostrar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seccion` varchar(50) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `info_mostrar` */

LOCK TABLES `info_mostrar` WRITE;

insert  into `info_mostrar`(`id`,`seccion`,`activo`) values 
(1,'about',1),
(2,'story',1),
(3,'gallery',1),
(4,'events',1),
(5,'wedding',1),
(6,'contact',1),
(7,'cronometro',1),
(8,'logo',1);

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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `info_nosotros` */

LOCK TABLES `info_nosotros` WRITE;

insert  into `info_nosotros`(`id`,`nombre`,`texto`,`activo`,`orden`) values 
(1,'Maria','está viviendo un momento único en su vida: está a punto de casarse con él, el hombre con quien comparte su presente y sueña su futuro. Desde siempre, imaginó este momento, pero ahora que está tan cerca, lo vive con emoción, nervios y mucha ilusión.\r\n\r\nEs diseñadora gráfica y ama todo lo relacionado con el arte y la creatividad. Su trabajo le permite expresar su estilo y crear piezas visuales que transmiten emociones. En su tiempo libre, le gusta pintar, hacer lettering y sacar fotos con su cámara analógica. También disfruta recorrer ferias de diseño y descubrir pequeños cafés escondidos en la ciudad.\r\n\r\nSofía es una persona sociable y cariñosa, siempre rodeada de amigos y familia. Organiza encuentros en su casa con mates y medialunas, y le encanta conversar durante horas. Es fanática de los libros de romance y siempre tiene uno en su cartera. También le gusta la música indie y el cine, en especial las películas con historias profundas y visuales impactantes.\r\n\r\nEn cuanto a su estilo de vida, es relajada pero organizada. Le gusta hacer yoga para desconectar del estrés y salir a caminar por la ciudad sin rumbo fijo. Disfruta cocinar, aunque admite que lo suyo son más los postres que las comidas elaboradas.\r\n\r\nAhora que está por casarse, Sofía siente que está en una montaña rusa de emociones. Quiere que la boda refleje su personalidad y que cada detalle sea especial. Más allá de la fiesta, lo que más le importa es la vida que va a construir con Martín, llena de amor, complicidad y proyectos en común.',1,NULL),
(2,'Jose','es un joven entusiasta y soñador que está a punto de dar uno de los pasos más importantes de su vida: casarse con el amor de su vida, Sofía. Desde pequeño, siempre imaginó formar una familia y construir un hogar lleno de amor y compañerismo.\r\n\r\nLe encanta la tecnología y trabaja como ingeniero en una empresa de software, donde desarrolla aplicaciones móviles. Es una persona meticulosa, organizada y siempre busca mejorar las cosas a su alrededor. En su tiempo libre, disfruta de los videojuegos, salir a correr por los parques de Palermo y probar nuevas cafeterías con Sofía.\r\n\r\nSi bien es fanático de la tecnología, también le apasiona la música. Toca la guitarra desde los 15 años y siempre sueña con armar una banda con sus amigos. Tiene gustos variados: desde rock nacional hasta música indie. Además, le gusta el cine y tiene un especial cariño por las películas de ciencia ficción.\r\n\r\nEn cuanto a su estilo de vida, es una persona activa. Le gusta mantenerse en forma, pero no es de los que van religiosamente al gimnasio; prefiere deportes al aire libre como el fútbol y el ciclismo. También es un amante de la comida casera y suele preparar cenas especiales para Sofía los fines de semana.\r\n\r\nAhora que está a punto de casarse, Martín se siente emocionado y un poco nervioso. Quiere que todo salga perfecto, pero también ha aprendido a disfrutar del proceso. Sabe que el matrimonio no es solo una ceremonia, sino un viaje de aprendizaje y crecimiento junto a la persona que ama.',1,NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `info_otra` */

LOCK TABLES `info_otra` WRITE;

insert  into `info_otra`(`id`,`titulo`,`descripcion`,`direccion`,`url`,`icono`,`activo`,`orden`) values 
(1,'Dress Codes','Elegante y cómodo. Queremos que te veas bien y te sientas mejor.\r\nVestite con estilo, pero sin complicaciones.','','','fas fa-user-tie',1,1),
(2,'Redes','Seguinos en nuestra red social para enterarte de todas las novedades','','https://instagram.com/dijequesi.ar','fab fa-instagram',1,4),
(3,'Instagram','Seguinos en instagram para estar al tanto de la preparación. Y así tambien despues no podes etiquetar en la foto que quieras','','https://www.instagram.com/dijequesi.ar','fab fa-instagram',0,5),
(4,'Ayúdanos con la música ','No dejes de sumar tu canción favorita a nuestra lista de Spotify ','','https://open.spotify.com/','fas fa-music',1,6);

UNLOCK TABLES;

/*Table structure for table `intivados_acompanante` */


DROP TABLE IF EXISTS `intivados_acompanante`;

CREATE TABLE `intivados_acompanante` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_acompanante` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `intivados_acompanante` */

LOCK TABLES `intivados_acompanante` WRITE;

insert  into `intivados_acompanante`(`id`,`categoria_acompanante`) values 
(1,'Solo/a'),
(2,'Flia'),
(3,'Novio/a'),
(4,'Sr/a'),
(5,'Amigo/a');

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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `invitaciones_estado` */



/*Table structure for table `invitados` */

/* ALTER TABLE invitados ADD UNIQUE (codigo);*/

DROP TABLE IF EXISTS `invitados`;

CREATE TABLE `invitados` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(25) NOT NULL,
  `apellido` VARCHAR(25) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `acompanado` INT(11) NOT NULL,
  `cantidad_mayores` INT(11) NOT NULL,
  `id_prioridad` INT(11) NOT NULL,
  `ingreso` VARCHAR(25) NOT NULL,
  `cantidad_menores` INT(11) NOT NULL,
  `fecha_registro` DATE NOT NULL,
  `confirmacion` VARCHAR(25) DEFAULT NULL,
  `confirmacion_fecha` DATETIME DEFAULT NULL,
  `confirmacion_comentario` TEXT DEFAULT NULL,
  `confirmacion_mayores` INT(11) DEFAULT NULL,
  `confirmacion_menores` INT(11) DEFAULT NULL,
  `alimento` VARCHAR(15) DEFAULT NULL,
  `codigo` VARCHAR(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=INNODB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;



/*TIGGERS PARA QUE GENERE CODIGO CUANDO SE IMPORTA LOS INVITADOS*/
DELIMITER $$

/*DROP TRIGGER `generar_codigo_invitado`$$*/

CREATE

    TRIGGER `generar_codigo_invitado` BEFORE INSERT ON `invitados` 
    FOR EACH ROW 
BEGIN
    DECLARE nuevo_codigo VARCHAR(10);
    DECLARE codigo_existe INT;


    REPEAT

        SET nuevo_codigo = LPAD(FLOOR(RAND() * 1000000), 6, '0');


        SELECT COUNT(*) INTO codigo_existe FROM invitados WHERE codigo = nuevo_codigo;


    UNTIL codigo_existe = 0
    END REPEAT;

    SET NEW.codigo = nuevo_codigo;
END;
$$

DELIMITER ;





/*Table structure for table `invitados_listado_mesa` */

CREATE TABLE `invitados_listado_mesa` (
  `id_invitados` int(11) DEFAULT NULL,
  `nombre_invitado` varchar(50) DEFAULT NULL,
  `mesa` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre2` varchar(50) DEFAULT NULL,
  `apellido2` varchar(50) DEFAULT NULL,
  UNIQUE KEY `orden_id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



/*Table structure for table `invitados_a_enviar` */

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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Table structure for table `invitados_enviados` */

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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




/*Table structure for table `invitados_prioridad` */

DROP TABLE IF EXISTS `invitados_prioridad`;

CREATE TABLE `invitados_prioridad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_prioridad` varchar(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Data for the table `invitados_prioridad` */

LOCK TABLES `invitados_prioridad` WRITE;

insert  into `invitados_prioridad`(`id`,`categoria_prioridad`) values 
(1,'Importante'),
(2,'Medio Importante'),
(3,'Normal'),
(4,'No necesario');

UNLOCK TABLES;



/*Table structure for table `invitados_tel` */

DROP TABLE IF EXISTS `invitados_tel`;

CREATE TABLE `invitados_tel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_invitados` int(11) DEFAULT NULL,
  `tel_enviar` bigint(20) DEFAULT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




/*Table structure for table `registro_mensajes_enviados` */

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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

/*Table structure for table `regalos_confirmacion` */

DROP TABLE IF EXISTS `regalos_confirmacion`;

CREATE TABLE `regalos_confirmacion` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `regalo_id` int(6) unsigned NOT NULL,
  `confirm_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `regalo_id` (`regalo_id`),
  CONSTRAINT `regalos_confirmacion_ibfk_1` FOREIGN KEY (`regalo_id`) REFERENCES `regalos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;



/*Table structure for table `regalos_detalles` */

DROP TABLE IF EXISTS `regalos_detalles`;

CREATE TABLE `regalos_detalles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `regalo_id` int(6) unsigned NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `regalo_id` (`regalo_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `regalos_detalles_ibfk_1` FOREIGN KEY (`regalo_id`) REFERENCES `regalos` (`id`),
  CONSTRAINT `regalos_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


