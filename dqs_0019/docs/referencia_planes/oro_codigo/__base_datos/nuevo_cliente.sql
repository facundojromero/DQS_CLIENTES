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
  `nombre` VARCHAR(50) NOT NULL,
  `apellido` VARCHAR(50) NOT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL,
  `telefono2` VARCHAR(20) DEFAULT NULL,
  `direccion` VARCHAR(100) DEFAULT NULL,
  `cbu_titular` VARCHAR(100) DEFAULT NULL,
  `cbu` VARCHAR(22) DEFAULT NULL,
  `alias` VARCHAR(100) DEFAULT NULL,
  `ciudad` VARCHAR(100) DEFAULT NULL,
  `provincia` VARCHAR(100) DEFAULT NULL,
  `plan` INT(10) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  AUTO_INCREMENT=1;





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

/*Data for the table `productos` */

LOCK TABLES `productos` WRITE;

insert  into `productos`(`id`,`titulo`,`descripcion`,`precio`,`activo`) values 
(1,'Heladera Bespoke freezer inferior 328L Satin Gray','La heladera Samsung Bespoke Freezer Inferior tiene un diseño fino e innovador que combina en cualquier tipo de cocina, con una capacidad de 328 litros. En su interior, cuenta con estantes de vidrio templado y cajón para frutas y verduras. Además, de puertas reversibles, es decir, que siempre será ideal para la disposición preferida de tu cocina. Cada puerta puede invertirse simplemente cambiando las bisagras. Así, se adapta a casi cualquier lugar y puedes disfrutar de la comodidad de hacer las cosas como a ti te gusta.',1800000,1),
(2,'Microondas Whirlpool 20 Litros WMS20AS Plata','El microondas WMS20AS Whirlpool tiene un diseño moderno color gris con negro.',180000,1),
(3,'Mesa estilo provenzal','Mesa estilo provenzal con 2 tablas extensibles en madera tallada , Mide 206 cm por 100 cm y 80 cm de alto',550000,1),
(4,'Sillas estilo provenzal','Precio por unidad. 6 sillas estilo provenzal con asientos empajado.',115000,1),
(5,'Pasajes aeros','Regala boletos de vuelo para tu próximo viaje.',850000,1),
(6,'Hotel all inclusive','Regala de una estadía completa con todo incluido en un lujoso hotel.',750000,1),
(7,'Set tazas de cafe','Conjunto de elegantes tazas para saborear tu café favorito.',48000,1),
(8,'Horno grill Atma 30 litros','Horno grill compacto de 30 litros de capacidad para cocinar deliciosas comidas.',150000,1),
(9,'Juego de toallas','Juego completo de suaves toallas para tu baño.',150000,1),
(10,'Lavarropas carga frontal samsung 6 KG','Lavadora de carga frontal Samsung con capacidad de 6 KG.',700000,1),
(11,'Pava Electrica','Hervidor eléctrico para calentar agua de manera rápida y eficiente.',35000,1),
(12,'Cafetera Dolce Gusto','Máquina de café Dolce Gusto para preparar café de alta calidad en casa.',155000,1),
(13,'Puff','Asiento informal tipo puff para relajarte cómodamente.',40000,1),
(14,'Lampara Paz','Lámpara de mesa con un diseño que inspira paz y tranquilidad.',85000,1),
(15,'Banco','Asiento sin respaldo ideal para espacios al aire libre.',95000,1),
(16,'Respaldo de cama','Cabecera de cama elegante y funcional.',130000,1),
(17,'Sillon','Cómodo asiento acolchado para descansar.',550000,1),
(18,'Banqueta de cuero','Taburete de cuero de alta calidad.',1630000,1),
(19,'Traslado aeropuerto','Servicio de transporte conveniente desde y hacia el aeropuerto.',70000,1),
(20,'Espejo de hierro','Espejo de diseño robusto y moderno con marco de hierro.',125000,1),
(21,'Set de cubiertos','Conjunto completo de cubiertos para tu mesa.',68000,1),
(22,'Vela aromatica','Vela perfumada para crear un ambiente relajante y aromático.',21000,1),
(23,'Alfombra de bano','Una suave alfombra diseñada para absorber el agua después de la ducha.',19000,1),
(24,'Lampara','Una fuente de luz elegante y funcional que ilumina cualquier habitación con estilo.',43000,1),
(25,'Alfombra de yuste','Una alfombra tejida de forma natural que agrega un toque rústico a tu decoración.',95000,1),
(26,'Sillon de un cuerpo','Un asiento acogedor y compacto perfecto para relajarse.',259000,1),
(27,'Silla roble','Una silla de madera resistente y clásica que combina con cualquier mesa.',138000,1),
(28,'Licuadora','Un aparato de cocina que mezcla y pulveriza ingredientes para hacer deliciosos batidos y sopas.',68000,1),
(29,'Tostadora','Un electrodoméstico que tuesta rebanadas de pan para un desayuno rápido.',32000,1),
(30,'Cafetera express moderna','Una máquina de café de alta gama que prepara espressos y cappuccinos perfectos.',198000,1),
(31,'Espumadora de leche','Un utensilio que crea espuma para dar un toque especial a tu café.',47000,1),
(32,'Minipimer','Una batidora de mano versátil para preparar sopas, purés y salsas.',68000,1),
(33,'Batidora','Un aparato que mezcla ingredientes para hacer pasteles, batidos y más.',78000,1),
(34,'Masajes en la playa','Un servicio de relajantes masajes en la playa para disfrutar de las olas y la brisa.',76000,1),
(35,'Vasos copones','Vasos elegantes y grandes para servir cócteles y bebidas especiales.',34000,1),
(36,'Vasos de cerveza','Vasos diseñados para realzar el sabor de la cerveza.',28000,1),
(37,'Copas de vino','Copas de cristal ideales para degustar y disfrutar de vinos finos.',23000,1),
(38,'Mantel impermeable','Un mantel resistente al agua para proteger tu mesa de derrames.',61000,1),
(39,'Individuales de mesa','Tapetes individuales que agregan estilo y protegen tu mesa.',13000,1),
(40,'Individuales de yute','Tapetes individuales tejidos de yute, perfectos para una mesa rústica.',23000,1),
(41,'Comoda petibiri','Un mueble de almacenamiento elegante y funcional para tu dormitorio.',700000,1),
(42,'Vajillero roma','Un mueble diseñado para guardar y exhibir tu vajilla de manera elegante.',650000,1),
(43,'Mesa ratona petibiri','Una mesa de centro pequeña y elegante para tu sala de estar.',480000,1),
(44,'Florero de ceramica','Un jarrón decorativo para exhibir tus flores favoritas.',22000,1),
(45,'Tazas de ceramica','Tazas elegantes y duraderas para disfrutar de tu bebida caliente favorita.',38000,1),
(46,'Platos de ceramica','Platos resistentes y estilosos para presentar tus comidas con elegancia.',112000,1),
(47,'Noche de bodas','Una experiencia romántica y especial para celebrar tu matrimonio.',440000,1),
(48,'Jarra de ceramica','Una jarra decorativa y funcional para servir bebidas.',25000,1),
(49,'Perchero de pie','Un soporte para colgar abrigos y accesorios en la entrada de tu hogar.',110000,1),
(50,'Perchero','Un accesorio práctico para organizar y colgar ropa y accesorios.',70000,1),
(51,'Set Cazuela','Un conjunto de ollas y sartenes ideales para cocinar guisos y platos deliciosos.',48000,1),
(52,'Juego de sabanas','Un juego de sábanas suaves y cómodas para tu cama.',155000,1),
(53,'Plumon de microfibra','Un edredón ligero y acogedor para mantenerse cálido durante la noche.',230000,1),
(54,'Pie de cama','Un accesorio decorativo que se coloca al pie de la cama para dar estilo.',55000,1),
(55,'Almohadones plain','Cojines simples y elegantes para decorar tu sofá o cama.',62000,1),
(56,'Almohadon de algodon','Un cojín suave y mullido para mayor comodidad.',53000,1),
(57,'Cacerola essen','Una cacerola de alta calidad para cocinar todo tipo de recetas.',530000,1),
(58,'Canasto organizadores','Cestas versátiles para organizar y almacenar objetos en tu hogar.',55000,1),
(59,'Pizzera','Una bandeja especial para hornear deliciosas pizzas caseras.',30000,1),
(60,'Tabla de madera asado','Una tabla resistente para cortar carne asada y otros alimentos.',48000,1),
(61,'Plato de madera cavado','Un plato tallado en madera para presentar aperitivos con estilo.',49000,1),
(62,'Set de cuchillos de ceramica','Cuchillos afilados y modernos para cortar y preparar alimentos.',45000,1),
(63,'Escurridor seca platos','Un soporte práctico para secar platos y utensilios de cocina.',64000,1),
(64,'Freidora de aire','Un aparato de cocina que cocina alimentos crujientes con menos aceite.',130000,1),
(65,'Waflera y sanwichera','Una máquina versátil para preparar deliciosos waffles y sándwiches.',45000,1),
(66,'Plancha a vapor','Una plancha que elimina las arrugas de la ropa con facilidad.',60000,1),
(67,'Espejo rectangular de hierr','Un espejo de forma elegante y moderna para tu hogar.',120000,1),
(68,'Smart TV 4K UHD 50','Un televisor inteligente de alta definición con tecnología 4K.',400000,1),
(69,'Freezer horizontal gafa','Un congelador horizontal espacioso para almacenar alimentos congelados.',595000,1),
(70,'Split inverter LG','Un sistema de aire acondicionado eficiente y moderno.',980000,1),
(71,'Sommier La Cardeuse','Un colchón de calidad para un descanso confortable.',890000,1),
(72,'Jarra enlozada','Jarra de alta calidad con esmalte duradero para un vertido preciso y resistente a manchas.',28000,1),
(73,'Cazuelas enlozada','Cazuelas premium enlozadas que distribuyen el calor de manera uniforme para una cocción perfecta.',21000,1),
(74,'Bolw dips enlozada','Bolw enlozado de primera calidad para servir salsas y aperitivos con elegancia y durabilidad.',16000,1),
(75,'Bolw 12 cm enlozada','Bolw de 12 cm enlozado de alta calidad para servir porciones individuales con una superficie resistente y fácil de limpiar.',17000,1),
(76,'Bolw mediano enlozada','Bolw mediano enlozado de primera categoría, versátil y duradero para múltiples usos en la cocina.',32000,1),
(77,'Plato racion enlozada','Plato de ración enlozado de calidad superior para presentar comidas principales con estilo y robustez.',19000,1),
(78,'Bolw batidor enlozada','Bolw enlozado diseñado para batir ingredientes de manera eficiente, con una construcción resistente y duradera.',5000,1),
(79,'Espejo para bano','Espejo para baño',68000,1),
(80,'Gabinete para bano mercantil','Gabinite para baño',400000,1),
(81,'Vanitory Jame estilo restoration hardware','Vanitorio',520000,1);

UNLOCK TABLES;


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

/*Data for the table `imagenes` */

LOCK TABLES `imagenes` WRITE;

insert  into `imagenes`(`id`,`producto_id`,`url`) values 
(1,1,'heladera.jpg'),
(2,2,'microondas.jpg'),
(3,3,'mesa_provenzal.jpg'),
(4,4,'silla_provenzal.jpg'),
(5,5,'pasajes_aeros.jpg'),
(6,6,'hotel_all_inclusive.jpg'),
(7,7,'set_tazas_de_cafe.jpg'),
(8,8,'horno_grill_atma_30_litros.jpg'),
(9,9,'juego_de_toallas.jpg'),
(10,10,'lavarropas_carga_frontal_samsung_6_kg.jpg'),
(11,11,'pava_electrica.jpg'),
(12,12,'cafetera_dolce_gusto.jpg'),
(13,13,'puff.jpg'),
(14,14,'lampara_paz.jpg'),
(15,15,'banco.jpg'),
(16,16,'respaldo_de_cama.jpg'),
(17,17,'sillon.jpg'),
(18,18,'banqueta_de_cuero.jpg'),
(19,19,'traslado_aeropuerto.jpg'),
(20,20,'espejo_de_hierro.jpg'),
(21,21,'set_de_cubiertos.jpg'),
(22,22,'vela_aromatica.jpg'),
(23,23,'alfombra_de_bano.jpg'),
(24,24,'lampara.jpg'),
(25,25,'alfombra_de_yuste.jpg'),
(26,26,'sillon_de_un_cuerpo.jpg'),
(27,27,'silla_roble.jpg'),
(28,28,'licuadora.jpg'),
(29,29,'tostadora.jpg'),
(30,30,'cafetera_express_moderna.jpg'),
(31,31,'espumadora_de_leche.jpg'),
(32,32,'minipimer.jpg'),
(33,33,'batidora.jpg'),
(34,34,'masajes_en_la_playa.jpg'),
(35,35,'vasos_copones.jpg'),
(36,36,'vasos_de_cerveza.jpg'),
(37,37,'copas_de_vino.jpg'),
(38,38,'mantel_impermeable.jpg'),
(39,39,'individuales_de_mesa.jpg'),
(40,40,'individuales_de_yute.jpg'),
(41,41,'comoda_petibiri.jpg'),
(42,42,'vajillero_roma.jpg'),
(43,43,'mesa_ratona_petibiri.jpg'),
(44,44,'florero_de_ceramica.jpg'),
(45,45,'tazas_de_ceramica.jpg'),
(46,46,'platos_de_ceramica.jpg'),
(47,47,'noche_de_bodas.jpg'),
(48,48,'jarra_de_ceramica.jpg'),
(49,49,'perchero_de_pie.jpg'),
(50,50,'perchero.jpg'),
(51,51,'set_cazuela.jpg'),
(52,52,'juego_de_sabanas.jpg'),
(53,53,'plumon_de_microfibra.jpg'),
(54,54,'pie_de_cama.jpg'),
(55,55,'almohadones_plain.jpg'),
(56,56,'almohadon_de_algodon.jpg'),
(57,57,'cacerola_essen.jpg'),
(58,58,'canasto_organizadores.jpg'),
(59,59,'pizzera.jpg'),
(60,60,'tabla_de_madera_asado.jpg'),
(61,61,'plato_de_madera_cavado.jpg'),
(62,62,'set_de_cuchillos_de_ceramica.jpg'),
(63,63,'escurridor_seca_platos.jpg'),
(64,64,'freidora_de_aire.jpg'),
(65,65,'waflera_y_sanwichera.jpg'),
(66,66,'plancha_a_vapor.jpg'),
(67,67,'espejo_rectangular_de_hierr.jpg'),
(68,68,'smart_tv_4k_uhd_50.jpg'),
(69,69,'freezer_horizontal_gafa.jpg'),
(70,70,'split_inverter_lg.jpg'),
(71,71,'sommier_la_cardeuse.jpg'),
(72,72,'jarra_enlozada.jpg'),
(73,73,'cazuelas_enlozada.jpg'),
(74,74,'bolw_dips_enlozada.jpg'),
(75,75,'bolw_12_cm_enlozada.jpg'),
(76,76,'bolw_mediano_enlozada.jpg'),
(77,77,'plato_racion_enlozada.jpg'),
(78,78,'bolw_batidor_enlozada.jpg'),
(79,79,'espejo_para_bano.jpg'),
(80,80,'gabinete_para_bano_mercantil.jpg'),
(81,81,'vanitory_jame_estilo_restoration_hardware.jpg');

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


