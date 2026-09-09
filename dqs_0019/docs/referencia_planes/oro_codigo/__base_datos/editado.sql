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
  `plan` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `cliente` */


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
(1,'Heladera Bespoke freezer inferior 328L Satin Gray','La heladera Samsung Bespoke Freezer Inferior tiene un diseño fino e innovador que combina en cualquier tipo de cocina, con una capacidad de 328 litros. En su interior, cuenta con estantes de vidrio templado y cajón para frutas y verduras. Además, de puertas reversibles, es decir, que siempre será ideal para la disposición preferida de tu cocina. Cada puerta puede invertirse simplemente cambiando las bisagras. Así, se adapta a casi cualquier lugar y puedes disfrutar de la comodidad de hacer las cosas como a ti te gusta.',650000.00,1),
(2,'Microondas Whirlpool 20 Litros WMS20AS Plata','El microondas WMS20AS Whirlpool tiene un diseño moderno color gris con negro.',90000.00,1),
(3,'Mesa estilo provenzal','Mesa estilo provenzal con 2 tablas extensibles en madera tallada , Mide 206 cm por 100 cm y 80 cm de alto',210000.00,1),
(4,'Sillas estilo provenzal','Precio por unidad. 6 sillas estilo provenzal con asientos empajado.',35000.00,1),
(5,'Pasajes aeros','Regala boletos de vuelo para tu próximo viaje.',500000.00,1),
(6,'Hotel all inclusive','Regala de una estadía completa con todo incluido en un lujoso hotel.',400000.00,1),
(7,'Set tazas de cafe','Conjunto de elegantes tazas para saborear tu café favorito.',16000.00,1),
(8,'Horno grill Atma 30 litros','Horno grill compacto de 30 litros de capacidad para cocinar deliciosas comidas.',70600.00,1),
(9,'Juego de toallas','Juego completo de suaves toallas para tu baño.',50000.00,1),
(10,'Lavarropas carga frontal samsung 6 KG','Lavadora de carga frontal Samsung con capacidad de 6 KG.',225000.00,1),
(11,'Pava Electrica','Hervidor eléctrico para calentar agua de manera rápida y eficiente.',35000.00,1),
(12,'Cafetera Dolce Gusto','Máquina de café Dolce Gusto para preparar café de alta calidad en casa.',72000.00,1),
(13,'Puff','Asiento informal tipo puff para relajarte cómodamente.',25000.00,1),
(14,'Lampara Paz','Lámpara de mesa con un diseño que inspira paz y tranquilidad.',42000.00,1),
(15,'Banco','Asiento sin respaldo ideal para espacios al aire libre.',55000.00,1),
(16,'Respaldo de cama','Cabecera de cama elegante y funcional.',85000.00,1),
(17,'Sillon','Cómodo asiento acolchado para descansar.',250000.00,1),
(18,'Banqueta de cuero','Taburete de cuero de alta calidad.',80000.00,1),
(19,'Traslado aeropuerto','Servicio de transporte conveniente desde y hacia el aeropuerto.',15000.00,1),
(20,'Espejo de hierro','Espejo de diseño robusto y moderno con marco de hierro.',43000.00,1),
(21,'Set de cubiertos','Conjunto completo de cubiertos para tu mesa.',30000.00,1),
(22,'Vela aromatica','Vela perfumada para crear un ambiente relajante y aromático.',10000.00,1),
(23,'Alfombra de bano','Una suave alfombra diseñada para absorber el agua después de la ducha.',9000.00,1),
(24,'Lampara','Una fuente de luz elegante y funcional que ilumina cualquier habitación con estilo.',20000.00,1),
(25,'Alfombra de yuste','Una alfombra tejida de forma natural que agrega un toque rústico a tu decoración.',45000.00,1),
(26,'Sillon de un cuerpo','Un asiento acogedor y compacto perfecto para relajarse.',150000.00,1),
(27,'Silla roble','Una silla de madera resistente y clásica que combina con cualquier mesa.',70000.00,1),
(28,'Licuadora','Un aparato de cocina que mezcla y pulveriza ingredientes para hacer deliciosos batidos y sopas.',25000.00,1),
(29,'Tostadora','Un electrodoméstico que tuesta rebanadas de pan para un desayuno rápido.',30000.00,1),
(30,'Cafetera express moderna','Una máquina de café de alta gama que prepara espressos y cappuccinos perfectos.',110000.00,1),
(31,'Espumadora de leche','Un utensilio que crea espuma para dar un toque especial a tu café.',40000.00,1),
(32,'Minipimer','Una batidora de mano versátil para preparar sopas, purés y salsas.',50000.00,1),
(33,'Batidora','Un aparato que mezcla ingredientes para hacer pasteles, batidos y más.',65000.00,1),
(34,'Masajes en la playa','Un servicio de relajantes masajes en la playa para disfrutar de las olas y la brisa.',38000.00,1),
(35,'Vasos copones','Vasos elegantes y grandes para servir cócteles y bebidas especiales.',17000.00,1),
(36,'Vasos de cerveza','Vasos diseñados para realzar el sabor de la cerveza.',16000.00,1),
(37,'Copas de vino','Copas de cristal ideales para degustar y disfrutar de vinos finos.',20000.00,1),
(38,'Mantel impermeable','Un mantel resistente al agua para proteger tu mesa de derrames.',25000.00,1),
(39,'Individuales de mesa','Tapetes individuales que agregan estilo y protegen tu mesa.',9000.00,1),
(40,'Individuales de yute','Tapetes individuales tejidos de yute, perfectos para una mesa rústica.',20000.00,1),
(41,'Comoda petibiri','Un mueble de almacenamiento elegante y funcional para tu dormitorio.',200000.00,1),
(42,'Vajillero roma','Un mueble diseñado para guardar y exhibir tu vajilla de manera elegante.',250000.00,1),
(43,'Mesa ratona petibiri','Una mesa de centro pequeña y elegante para tu sala de estar.',150000.00,1),
(44,'Florero de ceramica','Un jarrón decorativo para exhibir tus flores favoritas.',10000.00,1),
(45,'Tazas de ceramica','Tazas elegantes y duraderas para disfrutar de tu bebida caliente favorita.',12000.00,1),
(46,'Platos de ceramica','Platos resistentes y estilosos para presentar tus comidas con elegancia.',45000.00,1),
(47,'Noche de bodas','Una experiencia romántica y especial para celebrar tu matrimonio.',200000.00,1),
(48,'Jarra de ceramica','Una jarra decorativa y funcional para servir bebidas.',9800.00,1),
(49,'Perchero de pie','Un soporte para colgar abrigos y accesorios en la entrada de tu hogar.',50000.00,1),
(50,'Perchero','Un accesorio práctico para organizar y colgar ropa y accesorios.',35000.00,1),
(51,'Set Cazuela','Un conjunto de ollas y sartenes ideales para cocinar guisos y platos deliciosos.',20000.00,1),
(52,'Juego de sabanas','Un juego de sábanas suaves y cómodas para tu cama.',80000.00,1),
(53,'Plumon de microfibra','Un edredón ligero y acogedor para mantenerse cálido durante la noche.',135000.00,1),
(54,'Pie de cama','Un accesorio decorativo que se coloca al pie de la cama para dar estilo.',25000.00,1),
(55,'Almohadones plain','Cojines simples y elegantes para decorar tu sofá o cama.',30000.00,1),
(56,'Almohadon de algodon','Un cojín suave y mullido para mayor comodidad.',25000.00,1),
(57,'Cacerola essen','Una cacerola de alta calidad para cocinar todo tipo de recetas.',200000.00,1),
(58,'Canasto organizadores','Cestas versátiles para organizar y almacenar objetos en tu hogar.',25000.00,1),
(59,'Pizzera','Una bandeja especial para hornear deliciosas pizzas caseras.',15000.00,1),
(60,'Tabla de madera asado','Una tabla resistente para cortar carne asada y otros alimentos.',30000.00,1),
(61,'Plato de madera cavado','Un plato tallado en madera para presentar aperitivos con estilo.',20000.00,1),
(62,'Set de cuchillos de ceramica','Cuchillos afilados y modernos para cortar y preparar alimentos.',30000.00,1),
(63,'Escurridor seca platos','Un soporte práctico para secar platos y utensilios de cocina.',40000.00,1),
(64,'Freidora de aire','Un aparato de cocina que cocina alimentos crujientes con menos aceite.',130000.00,1),
(65,'Waflera y sanwichera','Una máquina versátil para preparar deliciosos waffles y sándwiches.',40000.00,1),
(66,'Plancha a vapor','Una plancha que elimina las arrugas de la ropa con facilidad.',60000.00,1),
(67,'Espejo rectangular de hierr','Un espejo de forma elegante y moderna para tu hogar.',90000.00,1),
(68,'Smart TV 4K UHD 50','Un televisor inteligente de alta definición con tecnología 4K.',250000.00,1),
(69,'Freezer horizontal gafa','Un congelador horizontal espacioso para almacenar alimentos congelados.',350000.00,1),
(70,'Split inverter LG','Un sistema de aire acondicionado eficiente y moderno.',480000.00,1),
(71,'Sommier La Cardeuse','Un colchón de calidad para un descanso confortable.',700000.00,1),
(72,'Jarra enlozada','Jarra de alta calidad con esmalte duradero para un vertido preciso y resistente a manchas.',14000.00,1),
(73,'Cazuelas enlozada','Cazuelas premium enlozadas que distribuyen el calor de manera uniforme para una cocción perfecta.',12000.00,1),
(74,'Bolw dips enlozada','Bolw enlozado de primera calidad para servir salsas y aperitivos con elegancia y durabilidad.',7500.00,1),
(75,'Bolw 12 cm enlozada','Bolw de 12 cm enlozado de alta calidad para servir porciones individuales con una superficie resistente y fácil de limpiar.',7800.00,1),
(76,'Bolw mediano enlozada','Bolw mediano enlozado de primera categoría, versátil y duradero para múltiples usos en la cocina.',20100.00,1),
(77,'Plato racion enlozada','Plato de ración enlozado de calidad superior para presentar comidas principales con estilo y robustez.',8700.00,1),
(78,'Bolw batidor enlozada','Bolw enlozado diseñado para batir ingredientes de manera eficiente, con una construcción resistente y duradera.',2400.00,1),
(79,'Espejo para bano','Espejo para baño',15000.00,1),
(80,'Gabinete para bano mercantil','Gabinite para baño',300000.00,1),
(81,'Vanitory Jame estilo restoration hardware','Vanitorio',370000.00,1);

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


/*Data for the table `invitados` */

LOCK TABLES `invitados` WRITE;


insert into `invitados`(`id`,`nombre`,`apellido`,`activo`,`acompanado`,`cantidad_mayores`,`id_prioridad`,`ingreso`,`cantidad_menores`,`fecha_registro`,`confirmacion`,`confirmacion_fecha`,`confirmacion_comentario`,`confirmacion_mayores`,`confirmacion_menores`,`alimento`,`codigo`) values 
(1,'Lucas','Pérez',1,1,1,2,'Inicio',0,'2023-01-29','Si','2025-07-08 03:29:16','',1,0,'Otro','727209'),
(2,'Sofía','Gómez',1,1,1,3,'Inicio',0,'2023-01-29',NULL,NULL,NULL,NULL,NULL,NULL,'940609'),
(3,'Mateo','Rodríguez',1,1,1,2,'Inicio',0,'2023-01-29','Si','2023-11-10 02:14:20','',1,0,'No','521420'),
(4,'Valentina','Fernández',1,1,2,1,'Inicio',0,'2023-01-29','Si','2025-02-24 21:54:33','',2,0,'No','785275'),
(5,'Diego','López',1,2,3,1,'Inicio',0,'2023-01-29','Si','2025-02-24 21:07:51','',3,0,'No','362112'),
(6,'Isabella','Martínez',0,1,1,2,'Inicio',0,'2023-01-29','Si','2023-11-02 19:20:02','',1,0,'No','454737'),
(7,'Franco','González',1,1,1,3,'Inicio',0,'2023-01-29','Si','2023-11-26 21:34:18','',1,0,'No','187348'),
(8,'Camila','Díaz',1,1,1,3,'Tarde',0,'2023-01-29','Si','2023-11-10 00:16:11','',1,0,'No','572533'),
(9,'Lautaro','Sánchez',1,1,1,3,'Inicio',0,'2023-01-29','Si','2023-10-12 14:42:33','',1,0,'No','300618'),
(10,'Emilia','Romero',1,1,1,3,'Inicio',0,'2023-01-29','Si','2023-10-27 01:11:30','',1,0,'No','785493'),
(11,'Benjamín','Alvarez',1,1,1,2,'Inicio',0,'2023-01-29','Si','2023-10-12 15:32:52','',1,0,'No','025612'),
(12,'Julieta','Torres',0,1,1,3,'Inicio',0,'2023-01-29','Si','2023-11-21 14:10:35','',1,0,'No','771583'),
(13,'Facundo','Ruiz',1,1,1,3,'Inicio',0,'2023-01-29','No','2023-11-19 21:05:35','',0,0,'No','781078'),
(14,'Martina','Benítez',1,1,1,3,'Tarde',0,'2023-01-29','Si','2023-10-15 09:47:36','',1,0,'No','590640'),
(15,'Nicolás','Silva',1,2,2,2,'Inicio',0,'2023-01-29','Si','2023-10-15 13:48:25','',2,0,'No','609966'),
(16,'Sofía','Núñez',1,3,1,1,'Inicio',0,'2023-01-29','Si','2023-10-13 01:13:16','',1,0,'No','277912'),
(17,'Gabriel','Castro',1,1,1,3,'Inicio',0,'2023-01-29','Si','2023-11-16 02:31:15','',1,0,'No','559662'),
(18,'Andrea','Moreno',0,1,1,4,'Tarde',0,'2023-01-29',NULL,NULL,NULL,NULL,NULL,NULL,'964573'),
(19,'Daniela','Molina',0,1,1,4,'Tarde',0,'2023-01-29',NULL,NULL,NULL,NULL,NULL,NULL,'143882'),
(20,'Felipe','Ortiz',1,1,1,2,'Inicio',0,'2023-01-29','Si','2023-10-17 04:08:32','',1,0,'No','825691'),
(21,'Florencia','Delgado',1,1,1,1,'Inicio',0,'2023-01-29','Si','2023-11-18 21:14:24','',1,0,'No','696809'),
(22,'Lucas','Vázquez',1,1,1,2,'Inicio',0,'2023-01-29','Si','2023-10-13 16:23:29','',1,0,'Celiaco','006973'),
(23,'Agustina','Rojas',1,3,2,1,'Inicio',0,'2023-01-29','Si','2023-10-12 14:41:18','',2,0,'No','944438'),
(24,'Valentín','Acosta',0,1,1,3,'Inicio',0,'2023-01-29','No','2023-11-18 21:03:26','',0,0,'No','701273'),
(25,'Julia','Herrera',1,2,3,1,'Inicio',1,'2023-01-29','Si','2023-10-12 12:54:55','',3,1,'No','673049'),
(26,'Mariano','Iglesias',1,1,2,2,'Inicio',0,'2023-01-29','Si','2023-11-25 21:56:28','',2,0,'No','261426'),
(27,'Paula','Paz',1,1,1,3,'Inicio',0,'2023-01-29','No','2023-11-21 14:10:36','',1,0,'No','287985'),
(28,'Ignacio','Vega',1,1,1,2,'Inicio',0,'2023-01-29','Si','2023-10-12 14:57:28','',1,0,'No','655647'),
(29,'Laura','Cabrera',1,1,1,3,'Inicio',0,'2023-01-29','No','2023-11-28 20:51:41','',0,0,'No','414279'),
(30,'Pedro','Blanco',0,1,1,4,'Tarde',0,'2023-01-29',NULL,NULL,NULL,NULL,NULL,NULL,'104455'),
(31,'Victoria','Moretti',1,1,1,2,'Inicio',0,'2023-01-29','Si','2023-10-18 16:55:05','',1,0,'No','279439'),
(32,'Santiago','Castro',1,3,1,1,'Inicio',0,'2023-01-29','Si','2023-10-22 02:43:35','',1,0,'No','083829'),
(33,'Lucía','Ferrer',1,3,3,2,'Inicio',0,'2023-01-29','Si','2023-10-17 15:03:27','',2,0,'No','580829'),
(34,'Manuel','Aguilar',1,1,1,3,'Inicio',0,'2023-01-29','No','2023-11-19 21:05:34','',0,0,'No','652660'),
(35,'Rocío','Navarro',1,1,1,3,'Inicio',0,'2023-01-29','Si','2023-10-12 16:12:39','',1,0,'No','520812'),
(36,'Sofía','Pereyra',1,1,1,2,'Inicio',0,'2023-01-29','Si','2023-10-13 18:02:14','',1,0,'No','646082'),
(37,'Agustín','Gutiérrez',1,4,2,1,'Inicio',0,'2023-01-29',NULL,NULL,NULL,NULL,NULL,NULL,'667972'),
(38,'Carla','Duarte',1,1,1,3,'Tarde',0,'2023-01-29','No','2023-10-19 15:48:38','',0,0,'No','401616'),
(39,'Federico','Vargas',1,3,2,1,'Inicio',0,'2023-01-29','Si','2023-10-12 15:21:42','',2,0,'No','004165'),
(40,'Ana','Morales',1,1,1,3,'Inicio',0,'2023-01-29','Si','2023-11-10 14:33:02','',1,0,'No','815976'),
(41,'José','Jiménez',1,1,1,1,'Inicio',0,'2023-01-29','Si','2023-11-18 21:14:23','',1,0,'No','067384'),
(42,'Micaela','Ruiz Díaz',1,1,1,1,'Inicio',0,'2023-01-29','Si','2023-10-12 16:27:14','',1,0,'No','888995'),
(43,'Martín','González',1,1,2,3,'Inicio',0,'2023-01-29','Si','2023-11-10 02:16:57','',2,0,'No','242823'),
(44,'Luciana','Benítez',1,1,1,2,'Inicio',0,'2023-01-29','Si','2023-11-19 21:14:20','',1,0,'No','547132'),
(45,'Tomás','Cano',1,1,1,2,'Inicio',0,'2023-01-29','No','2023-10-12 15:04:57','',0,0,'No','007191'),
(46,'Victoria','Flores',0,1,1,4,'Tarde',0,'2023-01-29',NULL,NULL,NULL,NULL,NULL,NULL,'394561'),
(47,'Juan','Vega',1,1,1,2,'Inicio',0,'2023-01-29','Si','2023-11-21 18:49:14','',1,0,'No','951230'),
(48,'Romina','García',1,1,1,1,'Inicio',0,'2023-01-29','Si','2023-10-12 14:59:40','',1,0,'No','572468'),
(49,'Pablo','Ramírez',1,3,1,2,'Inicio',0,'2023-01-29','Si','2023-10-15 13:44:58','',1,0,'No','008651'),
(50,'Laura','Paz',1,3,1,1,'Inicio',0,'2023-01-29','Si','2023-11-18 21:14:24','',1,0,'No','325850'),
(51,'Carlos','Costa',1,3,2,1,'Inicio',0,'2023-01-29','Si','2023-11-10 23:36:23','',2,0,'No','603300'),
(52,'Natalia','Medina',1,3,2,1,'Tarde',0,'2023-01-29','Si','2023-11-16 21:49:42','',2,0,'No','038951'),
(53,'Roberto','Godoy',1,4,2,2,'Tarde',0,'2023-01-29','Si','2023-10-12 17:56:34','',2,0,'No','384853'),
(54,'Sofía','Cáceres',1,1,1,3,'Inicio',0,'2023-01-29','No','2023-11-10 02:42:18','',0,0,'No','807412'),
(55,'Emiliano','Peralta',1,2,2,1,'Inicio',1,'2023-01-29','Si','2023-10-12 13:33:36','',2,1,'No','882501'),
(56,'Carolina','Pereyra',1,1,1,2,'Inicio',0,'2023-01-29','Si','2023-10-12 17:55:14','',1,0,'No','990270'),
(57,'Santiago','García',0,1,1,3,'Inicio',0,'2023-01-29','Si','2023-11-21 14:10:35','',1,0,'No','303846'),
(58,'Agustina','Moreno',1,3,2,1,'Inicio',0,'2023-01-29','Si','2023-10-12 13:25:10','',2,0,'No','548422'),
(59,'Gabriel','Ferrari',1,1,1,1,'Inicio',0,'2023-01-29','Si','2023-10-12 16:24:58','',1,0,'No','830571'),
(60,'Lucía','Bianchi',1,1,1,1,'Inicio',0,'2023-01-29','Si','2023-11-18 21:14:23','',1,0,'No','507592'),
(61,'Rodrigo','Vidal',1,1,1,3,'Inicio',0,'2023-01-29','Si','2023-11-10 01:44:33','',1,0,'No','046247'),
(62,'Florencia','Romero',1,2,2,1,'Inicio',1,'2023-01-29','Si','2023-10-12 13:48:15','Jajaja sirenil como la\r\nNovia aunq ese día tenemos permitido ',2,1,'Otro','708460'),
(63,'Gonzalo','Méndez',1,1,2,1,'Inicio',0,'2023-01-29','Si','2023-11-19 19:34:05','',2,0,'No','403558'),
(64,'Valeria','Fernández',1,1,1,1,'Inicio',0,'2023-01-29','Si','2023-10-22 02:37:39','',1,0,'No','892412'),
(65,'Martín','Flores',1,3,2,2,'Inicio',0,'2023-01-29','Si','2023-11-10 02:23:47','',2,0,'No','251385'),
(66,'Mariana','Luna',0,1,1,4,'Tarde',0,'2023-01-29',NULL,NULL,NULL,NULL,NULL,NULL,'579692'),
(67,'Diego','Sosa',1,1,1,3,'Inicio',0,'2023-01-29','No','2023-11-26 21:33:04','',0,0,'No','144304'),
(68,'Sofía','Godoy',1,1,2,2,'Inicio',0,'2023-01-29','No','2023-11-18 21:03:26','',0,0,'No','982444'),
(69,'Lucas','Miranda',1,3,2,1,'Inicio',0,'2023-01-29','Si','2023-10-16 20:31:04','',2,0,'No','479311'),
(70,'Valentina','Juárez',1,1,1,3,'Inicio',0,'2023-01-29','Si','2023-11-10 02:22:03','',1,0,'No','449221'),
(71,'Juan','Gallardo',1,1,1,2,'Inicio',0,'2023-01-29','Si','2023-11-10 02:27:28','',1,0,'No','808175'),
(72,'Emilia','Figueroa',1,1,1,2,'Inicio',0,'2023-01-29','Si','2023-11-21 12:06:23','',1,0,'No','693210'),
(73,'Pablo','Soto',1,3,2,1,'Inicio',0,'2023-01-29','Si','2023-10-26 02:16:55','',2,0,'No','041526'),
(74,'Mariana','Quiroga',1,3,2,2,'Inicio',0,'2023-01-29','No','2023-11-10 23:47:56','',0,0,'No','128002'),
(75,'Agustín','Blanco',1,1,1,2,'Inicio',0,'2023-01-29','Si','2023-10-29 22:32:16','',1,0,'No','515430'),
(76,'Sofía','Pérez',1,1,1,2,'Inicio',0,'2023-01-29','No','2023-11-25 21:31:33','',0,0,'No','193146'),
(77,'Martín','Suárez',1,1,1,2,'Inicio',0,'2023-01-29','Si','2023-11-10 11:57:24','',1,0,'No','419440'),
(78,'Diego','Fuentes',1,1,1,3,'Inicio',0,'2023-01-29','Si','2023-11-10 15:22:00','',1,0,'No','517761'),
(79,'Carolina','Cabrera',1,1,1,1,'Inicio',0,'2023-01-29','Si','2023-11-10 03:05:48','',1,0,'No','330486'),
(80,'Javier','Vega',0,2,2,1,'Inicio',3,'2023-01-29','Si','2023-10-12 14:37:07','',2,3,'No','099147'),
(81,'Laura','Flores',1,1,1,3,'Inicio',0,'2023-01-29','Si','2023-11-21 14:10:35','',1,0,'No','504277'),
(82,'Guillermo','Luna',1,1,2,3,'Inicio',0,'2023-01-29','No','2023-11-10 23:46:51','',0,0,'No','223946'),
(83,'Valeria','Acosta',1,1,1,3,'Inicio',0,'2023-01-29','Si','2023-10-23 13:52:06','',1,0,'No','606900'),
(84,'Juan','Díaz',0,1,1,4,'Tarde',0,'2023-01-29',NULL,NULL,NULL,NULL,NULL,NULL,'362662'),
(85,'Paula','Moreno',0,1,1,4,'Tarde',0,'2023-01-29',NULL,NULL,NULL,NULL,NULL,NULL,'992609'),
(86,'Ricardo','Sánchez',1,1,1,3,'Inicio',0,'2023-01-29','Si','2023-11-18 21:19:56','',1,0,'No','875062'),
(87,'Natalia','Ortiz',1,1,1,3,'Inicio',0,'2023-01-29','No','2023-11-10 16:05:24','',0,0,'No','397482'),
(88,'Andrea','Ferrer',1,1,1,3,'Inicio',0,'2023-01-29','No','2023-11-18 21:03:26','',0,0,'No','362225'),
(89,'José','Vargas',1,1,1,3,'Inicio',0,'2023-01-29','No','2023-10-25 16:46:52','',0,0,'No','618679'),
(90,'María','Peralta',1,3,2,1,'Inicio',0,'2023-01-29','Si','2023-10-12 13:51:09','',2,0,'No','006721'),
(91,'Federico','Juárez',0,1,1,3,'Inicio',0,'2023-01-29','Si','2023-11-03 11:39:03','',1,0,'No','177569'),
(92,'Luisa','Gallardo',1,1,1,3,'Inicio',0,'2023-01-29','No','2023-10-18 16:52:01','',0,0,'No','867681'),
(93,'Martina','Figueroa',1,1,1,1,'Inicio',0,'2023-01-29','Si','2023-10-25 21:03:39','sushi 5 piezas y nada de negros de mercedes por favor',1,0,'Vegetariano','805700'),
(94,'Carlos','Soto',1,1,1,1,'Inicio',0,'2023-01-29','Si','2023-10-13 01:46:51','',1,0,'No','425455'),
(95,'Daniela','Quiroga',1,1,1,3,'Tarde',0,'2023-01-29','Si','2023-11-23 00:44:58','',1,0,'No','710177'),
(96,'Miguel','Blanco',1,1,1,2,'Inicio',0,'2023-01-29','No','2023-10-25 16:47:40','',0,0,'No','274522'),
(97,'Victoria','Vega',1,1,1,1,'Inicio',0,'2023-01-29','Si','2023-10-21 01:07:39','',1,0,'No','242077'),
(98,'Jorge','Luna',1,4,2,3,'Inicio',0,'2023-01-29','No','2023-11-10 23:47:21','',0,0,'No','386819'),
(99,'Ana','Acosta',0,3,2,3,'Inicio',0,'2023-01-29','Si','2023-11-01 12:14:27','',2,0,'No','207866'),
(100,'Pedro','Diaz',1,1,1,2,'Tarde',0,'2023-01-29','Si','2023-10-12 16:35:21','',1,0,'No','878873'),
(101,'Emilia','Herrera',1,1,1,3,'Inicio',0,'2023-01-29','Si','2023-11-26 22:32:36','',1,0,'No','770769'),
(102,'Lucas','Martínez',1,1,1,1,'Inicio',0,'2023-01-29','Si','2023-11-10 02:50:05','',1,0,'No','217225'),
(103,'Valeria','Romero',1,1,1,3,'Inicio',0,'2023-01-29','Si','2023-10-16 22:41:34','',1,0,'No','773817'),
(104,'Santiago','Gómez',1,1,1,3,'Inicio',0,'2023-01-29','No','2023-10-19 18:46:55','',0,0,'No','217412'),
(105,'Martín','Sánchez',1,1,1,3,'Inicio',0,'2023-01-29','Si','2023-10-12 20:16:19','',1,0,'No','765609'),
(106,'Julia','Torres',1,4,2,1,'Inicio',0,'2023-01-29','Si','2023-10-14 00:56:56','',2,0,'No','175808'),
(107,'Facundo','Ruiz',1,1,1,1,'Inicio',0,'2023-01-29','Si','2023-10-12 16:04:36','',1,0,'No','582215'),
(108,'Gabriela','Benítez',1,2,1,2,'Inicio',0,'2023-01-29','Si','2023-11-10 04:34:01','',1,0,'No','383652'),
(109,'Ricardo','Silva',1,2,1,2,'Inicio',0,'2023-02-07','No','2023-11-18 21:03:21','',0,0,'No','171615'),
(110,'Fernanda','Fernández',0,1,1,1,'Inicio',0,'2023-02-09',NULL,NULL,NULL,NULL,NULL,NULL,'707118'),
(111,'Agustín','Díaz',0,1,1,4,'Tarde',0,'2023-02-09',NULL,NULL,NULL,NULL,NULL,NULL,'020748'),
(112,'Valeria','Gutiérrez',1,1,1,1,'Inicio',0,'2023-02-09','Si','2023-11-19 19:38:33','',1,0,'No','982388'),
(113,'Emiliano','Vargas',1,4,2,1,'Inicio',0,'2023-02-09','Si','2023-10-18 16:52:24','',2,0,'No','849693'),
(114,'Carolina','Alvarez',1,1,1,1,'Inicio',0,'2023-02-09','Si','2023-10-17 22:10:45','',1,0,'No','301303'),
(115,'Javier','Moretti',1,1,1,2,'Inicio',0,'2023-02-09','Si','2023-10-24 13:48:44','',1,0,'No','957436'),
(116,'Florencia','Delgado',1,1,1,2,'Inicio',0,'2023-02-09','No','2023-10-25 16:43:31','',0,0,'No','883274'),
(117,'Martina','Molina',1,1,1,3,'Inicio',0,'2023-02-09','Si','2023-11-21 20:42:50','',1,0,'No','544061'),
(118,'Laura','Jiménez',1,1,1,3,'Inicio',0,'2023-02-09','Si','2023-11-23 15:35:42','',1,0,'No','070482'),
(119,'Diego','Costa',1,3,2,2,'Tarde',0,'2023-02-09','Si','2023-10-13 14:03:27','',2,0,'No','720230'),
(120,'Lucía','Sosa',1,4,2,3,'Inicio',0,'2023-02-09','No','2023-10-17 17:52:40','',0,0,'No','389706'),
(121,'Pablo','Miranda',0,1,1,4,'Tarde',0,'2023-02-09',NULL,NULL,NULL,NULL,NULL,NULL,'787837'),
(122,'Ana','Gallardo',1,1,1,3,'Inicio',0,'2023-02-14','No','2023-11-10 23:46:09','',0,0,'No','770067'),
(123,'Sofía','Figueroa',0,1,1,3,'Inicio',0,'2023-04-07',NULL,NULL,NULL,NULL,NULL,NULL,'486828'),
(124,'Ricardo','Benítez',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-10-19 21:07:49','',2,0,'No','123937'),
(125,'Natalia','Silva',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-13 01:37:08','',2,0,'No','159202'),
(126,'José','Quiroga',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-23 19:05:45','',2,0,'No','424200'),
(127,'Romina','Blanco',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-13 12:37:30','',1,0,'No','643396'),
(128,'Santiago','Pérez',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-16 00:33:31','',2,0,'No','944379'),
(129,'Julieta','Gómez',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-13 13:50:34','',1,0,'No','791709'),
(130,'Martina','Rodríguez',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-22 01:52:46','',2,0,'No','125407'),
(131,'Lucía','Fernández',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-13 22:36:54','',2,0,'No','251909'),
(132,'Fernando','López',0,1,1,2,'Tarde',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'883327'),
(133,'Victoria','Martínez',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:34:06','',2,0,'No','660905'),
(134,'Agustín','González',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-13 13:51:30','',1,0,'No','654544'),
(135,'Florencia','Díaz',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-12 11:37:36','',1,0,'No','290008'),
(136,'Gabriela','Sánchez',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-11 23:27:22','',2,0,'No','486409'),
(137,'Guillermo','Romero',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-13 13:53:46','',2,0,'Celiaco','562021'),
(138,'Laura','Alvarez',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-20 09:48:00','',1,0,'No','350877'),
(139,'Federico','Torres',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-11 23:39:34','Holis!!! soy la complicada- jejje \r\nSoy vegetariana, intolerante a la lactosa y si puede ser sin gluten ideal y sino no pasa nada. Lo más importante es vegetariana sin lactosa (pueden decir vegana si les simplifica la vida)-huevo SI como. jejej\r\nLos quiero mucho, vamos a enfiestar todooooooooooooo, bailar, llorar de emoción, abrazarnos, y disfrutar! gracias por hacernos parteeeee!! wiii que alegriaaaaaaaaaaaaaaaaaaaaaa',2,0,'Vegano','068325'),
(140,'Micaela','Ruiz',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-13 13:51:38','',2,0,'No','288994'),
(141,'Tomás','Benítez',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-12 11:06:59','',2,0,'No','239997'),
(142,'Juan','Silva',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:34:07','',2,0,'No','333003'),
(143,'Rocío','Fernández',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-16 22:10:34','',2,0,'No','945023'),
(144,'Manuel','Moretti',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:34:07','',1,0,'No','726107'),
(145,'Daniela','Ortiz',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-12 19:53:20','',1,0,'Celiaco','795466'),
(146,'Ignacio','Vargas',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-12 03:38:03','',1,0,'No','799010'),
(147,'Andrea','Jiménez',1,3,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:34:06','',1,0,'No','608653'),
(148,'Carlos','Costa',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-12 12:19:11','',1,0,'No','646234'),
(149,'Valentina','Sánchez',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-11 23:02:34','',2,0,'No','405211'),
(150,'Agustina','Gutiérrez',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 13:09:22','',1,0,'No','087356'),
(151,'Emilia','Alvarez',0,1,1,1,'Inicio',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'221145'),
(152,'Santiago','Torres',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-31 15:11:19','',1,0,'Vegetariano','843661'),
(153,'Mariano','Ruiz',1,1,1,1,'Inicio',0,'2023-04-20','SI','2023-11-19 19:34:06','',1,0,'No','554867'),
(154,'Lucía','Benítez',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-12 23:26:52','',2,0,'No','243356'),
(155,'Pablo','Silva',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:19:36','',2,0,'No','552177'),
(156,'Romina','Quiroga',1,1,1,2,'Inicio',0,'2023-04-20','Si','2023-10-17 11:55:05','',1,0,'No','030818'),
(157,'Juan','Blanco',1,1,1,2,'Inicio',0,'2023-04-20','Si','2023-11-19 19:38:33','',1,0,'No','497558'),
(158,'Natalia','Gómez',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:35:37','',2,0,'No','395336'),
(159,'Valeria','Rodríguez',0,1,1,1,'Inicio',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'484010'),
(160,'Javier','Fernández',1,1,1,1,'Inicio',0,'2023-04-20','No','2023-11-19 20:44:42','',0,0,'No','234039'),
(161,'Florencia','López',0,1,1,1,'Inicio',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'718167'),
(162,'Diego','Martínez',0,1,1,1,'Tarde',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'888718'),
(163,'Carla','González',0,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-12 04:14:18','',1,0,'No','289088'),
(164,'Manuel','Díaz',0,1,1,1,'Inicio',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'779290'),
(165,'Mariana','Sánchez',1,1,1,2,'Inicio',0,'2023-04-20','Si','2023-11-03 13:59:40','',1,0,'No','029184'),
(166,'Gabriel','Romero',1,1,1,2,'Inicio',0,'2023-04-20','Si','2023-11-18 21:38:16','',1,0,'No','808051'),
(167,'Isabella','Alvarez',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-12 04:16:53','',1,0,'Celiaco','952702'),
(168,'Tomás','Torres',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-12 17:27:21','',1,0,'No','339359'),
(169,'Agustina','Ruiz',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-05 22:54:31','',1,0,'No','838690'),
(170,'Luciana','Benítez',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:34:06','',1,0,'No','175371'),
(171,'Pedro','Silva',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-13 12:11:20','',1,0,'No','360787'),
(172,'Julia','Quiroga',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-06 11:15:07','',1,0,'Celiaco','277821'),
(173,'Santiago','Blanco',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-19 12:00:58','',1,0,'No','306745'),
(174,'Valeria','Pérez',1,1,1,2,'Tarde',0,'2023-04-20','Si','2023-10-26 20:28:34','',1,0,'No','700263'),
(175,'Martín','Gómez',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-12 21:28:57','',1,0,'No','581080'),
(176,'Florencia','Rodríguez',0,1,1,2,'Tarde',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'804611'),
(177,'Emilia','Fernández',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:36:20','',1,0,'No','279815'),
(178,'Javier','López',0,1,1,3,'Tarde',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'985244'),
(179,'Carolina','Martínez',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-13 11:03:26','',1,0,'No','086775'),
(180,'Diego','González',0,1,1,2,'Tarde',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'478145'),
(181,'Manuel','Díaz',0,1,1,2,'Tarde',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'130398'),
(182,'Mariana','Sánchez',1,1,1,2,'Inicio',0,'2023-04-20','Si','2023-10-13 21:38:33','',1,0,'No','217558'),
(183,'Gabriel','Romero',0,1,0,3,'Inicio',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'696597'),
(184,'Isabella','Alvarez',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-11 23:03:14','',2,0,'No','830309'),
(185,'Tomás','Torres',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-12 21:26:35','',1,0,'No','061755'),
(186,'Agustina','Ruiz',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:34:06','',1,0,'No','817848'),
(187,'Luciana','Benítez',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-12 14:29:51','',1,0,'No','903977'),
(188,'Pedro','Silva',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:34:08','',1,0,'No','066340'),
(189,'Julia','Quiroga',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-06 15:57:19','',1,0,'No','619770'),
(190,'Santiago','Blanco',0,1,1,2,'Tarde',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'899829'),
(191,'Valeria','Pérez',1,1,1,1,'Inicio',0,'2023-04-20','No','2023-11-05 22:51:59','',0,0,'No','639838'),
(192,'Martín','Gómez',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-31 01:08:28','',1,0,'No','499705'),
(193,'Florencia','Rodríguez',0,1,1,1,'Tarde',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'579008'),
(194,'Emilia','Fernández',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-02 13:25:56','',1,0,'No','395926'),
(195,'Javier','López',1,1,1,2,'Tarde',0,'2023-04-20','Si','2023-11-03 15:55:38','',1,0,'No','242606'),
(196,'Carolina','Martínez',1,1,1,2,'Tarde',0,'2023-04-20','Si','2023-10-18 00:41:54','',1,0,'No','025253'),
(197,'Diego','González',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:34:08','',2,0,'No','398447'),
(198,'Manuel','Díaz',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-12 11:06:59','No como carnes rojas',1,0,'Vegetariano','916476'),
(199,'Mariana','Sánchez',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:41:26','',1,0,'No','387042'),
(200,'Gabriel','Romero',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-12 19:06:24','',1,0,'Vegetariano','185781'),
(201,'Isabella','Alvarez',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:34:07','',1,0,'No','767782'),
(202,'Tomás','Torres',1,3,2,3,'Inicio',0,'2023-04-20','No','2023-10-30 22:00:53','',0,0,'No','281564'),
(203,'Agustina','Ruiz',1,3,2,3,'Inicio',0,'2023-04-20','No','2023-10-30 21:57:14','',0,0,'No','104476'),
(204,'Luciana','Benítez',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-11 23:01:05','',2,0,'No','677689'),
(205,'Pedro','Silva',0,1,1,1,'Tarde',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'075016'),
(206,'Julia','Quiroga',0,1,1,4,'Tarde',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'342012'),
(207,'Santiago','Blanco',0,1,1,4,'Tarde',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'485016'),
(208,'Valeria','Pérez',0,1,1,4,'Tarde',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'399043'),
(209,'Martín','Gómez',1,1,2,1,'Inicio',0,'2023-04-20','Si','2023-11-05 22:46:02','',2,0,'No','540167'),
(210,'Florencia','Rodríguez',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:41:27','',2,0,'No','503707'),
(211,'Emilia','Fernández',1,1,1,1,'Inicio',0,'2023-04-20','No','2023-11-05 22:19:33','',0,0,'No','898036'),
(213,'Javier','López',0,3,2,1,'Tarde',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'201193'),
(214,'Carolina','Martínez',2,1,1,4,'Inicio',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'068786'),
(215,'Diego','González',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-12 12:39:34','',1,0,'No','740350'),
(216,'Manuel','Díaz',0,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:41:26','',1,0,'No','495393'),
(217,'Mariana','Sánchez',1,1,1,2,'Inicio',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'255915'),
(218,'Gabriel','Romero',0,3,2,2,'Tarde',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'793396'),
(219,'Isabella','Alvarez',1,3,2,3,'Inicio',0,'2023-04-20','No','2023-11-22 21:19:44','',0,0,'No','199238'),
(220,'Tomás','Torres',0,3,2,4,'Tarde',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'616001'),
(221,'Agustina','Ruiz',1,4,2,1,'Tarde',0,'2023-04-20','Si','2025-02-23 23:56:35','',2,0,'No','482293'),
(222,'Luciana','Benítez',0,4,2,3,'Tarde',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'563461'),
(223,'Pedro','Silva',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-01 11:59:11','',1,0,'No','370427'),
(224,'Julia','Quiroga',1,3,2,3,'Tarde',0,'2023-04-20','No','2023-11-08 13:18:28','',0,0,'No','161752'),
(225,'Santiago','Blanco',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-10-13 10:45:29','',2,0,'No','697480'),
(226,'Valeria','Pérez',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-06 22:52:14','La dieta de la selva, como lo que haya',1,0,'No','002146'),
(227,'Martín','Gómez',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-13 03:22:05','',2,0,'No','918290'),
(228,'Florencia','Rodríguez',1,3,2,1,'Inicio',1,'2023-04-20','Si','2023-10-13 01:59:22','',2,1,'No','585012'),
(229,'Emilia','Fernández',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:48:19','',1,0,'No','170192'),
(230,'Javier','López',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:48:20','',1,0,'No','095921'),
(231,'Carolina','Martínez',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-11-07 01:03:16','',2,0,'No','969033'),
(232,'Diego','González',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:48:20','',1,0,'No','557403'),
(233,'Manuel','Díaz',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-13 01:42:58','',2,0,'No','879914'),
(234,'Mariana','Sánchez',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:48:19','',1,0,'No','727364'),
(235,'Gabriel','Romero',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:48:19','',1,0,'No','997078'),
(236,'Isabella','Alvarez',1,2,2,1,'Inicio',1,'2023-04-20','Si','2023-10-13 14:16:58','',2,1,'No','803299'),
(237,'Tomás','Torres',1,2,2,1,'Inicio',0,'2023-04-20','No','2023-10-30 22:22:02','',0,0,'No','025263'),
(238,'Agustina','Ruiz',1,2,2,1,'Inicio',0,'2023-04-20','No','2023-10-30 22:23:56','',0,0,'No','716416'),
(239,'Luciana','Benítez',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:48:19','',1,0,'No','506291'),
(240,'Pedro','Silva',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-20 17:49:00','',1,0,'No','382209'),
(241,'Julia','Quiroga',1,2,2,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:48:19','',2,0,'No','392171'),
(242,'Santiago','Blanco',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:10:07','',2,0,'No','814231'),
(243,'Valeria','Pérez',1,2,2,1,'Inicio',0,'2023-04-20','Si','2023-10-13 01:42:41','',2,0,'No','894642'),
(244,'Martín','Gómez',1,3,1,1,'Inicio',0,'2023-04-20','Si','2023-10-30 11:02:42','',1,0,'No','030515'),
(245,'Florencia','Rodríguez',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-11-18 17:22:10','',2,0,'No','468653'),
(246,'Emilia','Fernández',1,2,2,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:34:05','',2,0,'No','251720'),
(247,'Javier','López',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-11-19 19:48:20','',1,0,'No','852639'),
(248,'Carolina','Martínez',1,3,2,1,'Inicio',0,'2023-04-20','No','2023-10-30 22:22:25','',0,0,'No','508038'),
(249,'Diego','González',1,3,1,1,'Inicio',0,'2023-04-20','Si','2023-10-19 15:37:02','',1,0,'No','982275'),
(250,'Manuel','Díaz',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-14 01:19:34','',2,0,'No','387258'),
(251,'Mariana','Sánchez',1,3,2,1,'Inicio',0,'2023-04-20','No','2023-11-21 14:13:11','',0,0,'No','989467'),
(252,'Gabriel','Romero',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-11-15 17:40:45','Delfina es celiaca e intolerante a la lactosa',2,0,'Otro','785562'),
(253,'Isabella','Alvarez',1,2,4,1,'Inicio',0,'2023-04-20','Si','2023-10-16 13:20:16','',4,0,'No','959408'),
(254,'Tomás','Torres',1,2,3,1,'Inicio',2,'2023-04-20','Si','2023-10-20 17:08:10','',3,2,'No','440355'),
(255,'Agustina','Ruiz',1,2,3,1,'Inicio',1,'2023-04-20','Si','2023-10-13 13:35:01','',3,1,'No','323551'),
(256,'Luciana','Benítez',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-12 02:57:54','',2,0,'No','296690'),
(257,'Pedro','Silva',1,1,1,1,'Tarde',0,'2023-04-20','Si','2023-10-23 00:02:56','',1,0,'No','512798'),
(258,'Julia','Quiroga',1,1,1,1,'Tarde',0,'2023-04-20','Si','2023-10-23 00:44:01','',1,0,'No','673921'),
(259,'Santiago','Blanco',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-11-03 02:36:03','',2,0,'No','831211'),
(260,'Valeria','Pérez',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-10-12 01:57:34','',2,0,'No','134293'),
(261,'Martín','Gómez',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-10-25 01:00:54','',2,0,'No','177831'),
(262,'Florencia','Rodríguez',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-11-20 17:44:45','',2,0,'No','486278'),
(263,'Emilia','Fernández',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-12 00:23:54','',1,0,'No','897899'),
(264,'Javier','López',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-12 00:29:16','',1,0,'No','030658'),
(265,'Carolina','Martínez',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-11-26 19:37:30','',2,0,'No','459597'),
(266,'Diego','González',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-11-07 14:42:58','',2,0,'No','206008'),
(267,'Manuel','Díaz',1,4,2,1,'Inicio',0,'2023-04-20','No','2023-11-05 22:51:07','',0,0,'No','651252'),
(268,'Mariana','Sánchez',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-11-05 21:52:39','',2,0,'No','638235'),
(269,'Gabriel','Romero',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-10-31 15:30:22','',2,0,'No','237422'),
(270,'Isabella','Alvarez',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-11-20 17:47:05','',2,0,'No','272404'),
(271,'Tomás','Torres',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-10-24 11:49:45','',2,0,'No','649754'),
(272,'Agustina','Ruiz',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-11-20 17:43:47','',2,0,'No','431560'),
(273,'Luciana','Benítez',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-10-14 12:06:28','',2,0,'No','208537'),
(274,'Pedro','Silva',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-10-12 02:20:42','',2,0,'No','748005'),
(275,'Julia','Quiroga',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-10-12 01:22:09','',2,0,'No','114416'),
(276,'Santiago','Blanco',0,4,2,1,'Inicio',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'328064'),
(277,'Valeria','Pérez',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-11-02 14:58:54','',2,0,'No','297073'),
(278,'Martín','Gómez',1,4,2,1,'Inicio',0,'2023-04-20','No','2023-10-19 18:06:41','',0,0,'No','501174'),
(279,'Florencia','Rodríguez',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-10-12 08:52:04','',2,0,'No','614651'),
(280,'Emilia','Fernández',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-11-05 21:47:25','',2,0,'No','569733'),
(281,'Javier','López',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-11-05 21:44:57','',2,0,'No','004713'),
(282,'Carolina','Martínez',0,4,2,4,'Inicio',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'314365'),
(283,'Diego','González',0,4,2,4,'Inicio',0,'2023-04-20',NULL,NULL,NULL,NULL,NULL,NULL,'557688'),
(284,'Manuel','Díaz',0,4,2,1,'Inicio',0,'2023-04-20','Si','2023-10-12 02:41:38','',2,0,'No','845346'),
(285,'Mariana','Sánchez',1,3,2,1,'Inicio',0,'2023-04-20','Si','2023-10-31 02:05:25','',2,0,'No','553667'),
(286,'Gabriel','Romero',1,4,2,1,'Inicio',0,'2023-04-20','Si','2023-10-17 13:26:48','Uno de los 2 es celiaco el otro normal ',2,0,'Celiaco','232296'),
(287,'Isabella','Alvarez',1,4,2,1,'Inicio',0,'2023-04-20','No','2023-11-05 21:43:14','',0,0,'No','500479'),
(288,'Tomás','Torres',1,1,1,1,'Tarde',0,'2023-04-20','Si','2023-10-26 18:07:07','',1,0,'No','805509'),
(289,'Agustina','Ruiz',1,1,1,1,'Tarde',0,'2023-04-20','Si','2023-11-20 17:40:00','',1,0,'No','526110'),
(290,'Luciana','Benítez',1,1,1,1,'Tarde',0,'2023-04-20','Si','2023-10-26 16:17:59','',1,0,'No','214022'),
(291,'Pedro','Silva',1,1,1,1,'Inicio',0,'2023-04-20','Si','2023-10-13 02:05:47','',1,0,'No','491781'),
(292,'Julia','Quiroga',1,1,1,3,'Inicio',0,'2023-05-02','No','2023-11-13 22:10:02','',0,0,'No','816840')
;

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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `invitados_listado_mesa` */

LOCK TABLES `invitados_listado_mesa` WRITE;

insert into `invitados_listado_mesa`(`id_invitados`,`nombre_invitado`,`mesa`,`id`) values
(1,'Luqui',NULL,1), -- Lucas Pérez (cantidad_mayores: 1, cantidad_menores: 0)
(2,'Sofi',NULL,2), -- Sofía Gómez (cantidad_mayores: 1, cantidad_menores: 0)
(3,'Mati',NULL,3), -- Mateo Rodríguez (cantidad_mayores: 1, cantidad_menores: 0)
(4,'Vale',NULL,4), -- Valentina Fernández (cantidad_mayores: 2, cantidad_menores: 0)
(4,'Fede',NULL,5),
(5,'Diegui',NULL,6), -- Diego López (cantidad_mayores: 3, cantidad_menores: 0)
(5,'Ana',NULL,7),
(5,'Pablo',NULL,8),
(6,'Isa',NULL,9), -- Isabella Martínez (cantidad_mayores: 1, cantidad_menores: 0)
(7,'Fran',NULL,10), -- Franco González (cantidad_mayores: 1, cantidad_menores: 0)
(8,'Cami',NULL,11), -- Camila Díaz (cantidad_mayores: 1, cantidad_menores: 0)
(9,'Laucha',NULL,12), -- Lautaro Sánchez (cantidad_mayores: 1, cantidad_menores: 0)
(10,'Emi',NULL,13), -- Emilia Romero (cantidad_mayores: 1, cantidad_menores: 0)
(11,'Benja',NULL,14), -- Benjamín Alvarez (cantidad_mayores: 1, cantidad_menores: 0)
(12,'Juli',NULL,15), -- Julieta Torres (cantidad_mayores: 1, cantidad_menores: 0)
(13,'Facu',NULL,16), -- Facundo Ruiz (cantidad_mayores: 1, cantidad_menores: 0)
(14,'Marti',NULL,17), -- Martina Benítez (cantidad_mayores: 1, cantidad_menores: 0)
(15,'Nico',NULL,18), -- Nicolás Silva (cantidad_mayores: 2, cantidad_menores: 0)
(15,'Javi',NULL,19),
(16,'Sofi',NULL,20), -- Sofía Núñez (cantidad_mayores: 1, cantidad_menores: 0)
(17,'Gaby',NULL,21), -- Gabriel Castro (cantidad_mayores: 1, cantidad_menores: 0)
(18,'Andi',NULL,22), -- Andrea Moreno (cantidad_mayores: 1, cantidad_menores: 0)
(19,'Dani',NULL,23), -- Daniela Molina (cantidad_mayores: 1, cantidad_menores: 0)
(20,'Feli',NULL,24), -- Felipe Ortiz (cantidad_mayores: 1, cantidad_menores: 0)
(21,'Flor',NULL,25), -- Florencia Delgado (cantidad_mayores: 1, cantidad_menores: 0)
(22,'Luqui',NULL,26), -- Lucas Vázquez (cantidad_mayores: 1, cantidad_menores: 0)
(23,'Agus',NULL,27), -- Agustina Rojas (cantidad_mayores: 2, cantidad_menores: 0)
(23,'Vale',NULL,28),
(24,'Valen',NULL,29), -- Valentín Acosta (cantidad_mayores: 1, cantidad_menores: 0)
(25,'Juli',NULL,30), -- Julia Herrera (cantidad_mayores: 3, cantidad_menores: 1)
(25,'Pau',NULL,31),
(25,'Majo',NULL,32),
(25,'Nico',NULL,33), -- Niño
(26,'Mariano',NULL,34), -- Mariano Iglesias (cantidad_mayores: 2, cantidad_menores: 0)
(26,'Moni',NULL,35),
(27,'Pau',NULL,36), -- Paula Paz (cantidad_mayores: 1, cantidad_menores: 0)
(28,'Nacho',NULL,37), -- Ignacio Vega (cantidad_mayores: 1, cantidad_menores: 0)
(29,'Lau',NULL,38), -- Laura Cabrera (cantidad_mayores: 1, cantidad_menores: 0)
(30,'Pedrito',NULL,39), -- Pedro Blanco (cantidad_mayores: 1, cantidad_menores: 0)
(31,'Vicky',NULL,40), -- Victoria Moretti (cantidad_mayores: 1, cantidad_menores: 0)
(32,'Santi',NULL,41), -- Santiago Castro (cantidad_mayores: 1, cantidad_menores: 0)
(33,'Luci',NULL,42), -- Lucía Ferrer (cantidad_mayores: 3, cantidad_menores: 0)
(33,'Fran',NULL,43),
(33,'Toto',NULL,44),
(34,'Manu',NULL,45), -- Manuel Aguilar (cantidad_mayores: 1, cantidad_menores: 0)
(35,'Ro',NULL,46), -- Rocío Navarro (cantidad_mayores: 1, cantidad_menores: 0)
(36,'Sofi',NULL,47), -- Sofía Pereyra (cantidad_mayores: 1, cantidad_menores: 0)
(37,'Agus',NULL,48), -- Agustín Gutiérrez (cantidad_mayores: 2, cantidad_menores: 0)
(37,'Mati',NULL,49),
(38,'Carli',NULL,50), -- Carla Duarte (cantidad_mayores: 1, cantidad_menores: 0)
(39,'Fede',NULL,51), -- Federico Vargas (cantidad_mayores: 2, cantidad_menores: 0)
(39,'Vale',NULL,52),
(40,'Anita',NULL,53), -- Ana Morales (cantidad_mayores: 1, cantidad_menores: 0)
(41,'Pepe',NULL,54), -- José Jiménez (cantidad_mayores: 1, cantidad_menores: 0)
(42,'Mica',NULL,55), -- Micaela Ruiz Díaz (cantidad_mayores: 1, cantidad_menores: 0)
(43,'Martín',NULL,56), -- Martín González (cantidad_mayores: 2, cantidad_menores: 0)
(43,'Gabi',NULL,57),
(44,'Luci',NULL,58), -- Luciana Benítez (cantidad_mayores: 1, cantidad_menores: 0)
(45,'Tomi',NULL,59), -- Tomás Cano (cantidad_mayores: 1, cantidad_menores: 0)
(46,'Vicky',NULL,60), -- Victoria Flores (cantidad_mayores: 1, cantidad_menores: 0)
(47,'Juan',NULL,61), -- Juan Vega (cantidad_mayores: 1, cantidad_menores: 0)
(48,'Romi',NULL,62), -- Romina García (cantidad_mayores: 1, cantidad_menores: 0)
(49,'Pablito',NULL,63), -- Pablo Ramírez (cantidad_mayores: 1, cantidad_menores: 0)
(50,'Lau',NULL,64), -- Laura Paz (cantidad_mayores: 1, cantidad_menores: 0)
(51,'Carlo',NULL,65), -- Carlos Costa (cantidad_mayores: 2, cantidad_menores: 0)
(51,'Eve',NULL,66),
(52,'Nati',NULL,67), -- Natalia Medina (cantidad_mayores: 2, cantidad_menores: 0)
(52,'Fran',NULL,68),
(53,'Rober',NULL,69), -- Roberto Godoy (cantidad_mayores: 2, cantidad_menores: 0)
(53,'Lali',NULL,70),
(54,'Sofi',NULL,71), -- Sofía Cáceres (cantidad_mayores: 1, cantidad_menores: 0)
(55,'Emi',NULL,72), -- Emiliano Peralta (cantidad_mayores: 2, cantidad_menores: 1)
(55,'Luci',NULL,73),
(55,'Tomi',NULL,74), -- Niño
(56,'Caro',NULL,75), -- Carolina Pereyra (cantidad_mayores: 1, cantidad_menores: 0)
(57,'Santi',NULL,76), -- Santiago García (cantidad_mayores: 1, cantidad_menores: 0)
(58,'Agus',NULL,77), -- Agustina Moreno (cantidad_mayores: 2, cantidad_menores: 0)
(58,'Gonza',NULL,78),
(59,'Gaby',NULL,79), -- Gabriel Ferrari (cantidad_mayores: 1, cantidad_menores: 0)
(60,'Luci',NULL,80), -- Lucía Bianchi (cantidad_mayores: 1, cantidad_menores: 0)
(61,'Rodri',NULL,81), -- Rodrigo Vidal (cantidad_mayores: 1, cantidad_menores: 0)
(62,'Flor',NULL,82), -- Florencia Romero (cantidad_mayores: 2, cantidad_menores: 1)
(62,'Pato',NULL,83),
(62,'Juli',NULL,84), -- Niño
(63,'Gonza',NULL,85), -- Gonzalo Méndez (cantidad_mayores: 2, cantidad_menores: 0)
(63,'Vale',NULL,86),
(64,'Vale',NULL,87), -- Valeria Fernández (cantidad_mayores: 1, cantidad_menores: 0)
(65,'Martín',NULL,88), -- Martín Flores (cantidad_mayores: 2, cantidad_menores: 0)
(65,'Mari',NULL,89),
(66,'Mari',NULL,90), -- Mariana Luna (cantidad_mayores: 1, cantidad_menores: 0)
(67,'Diegui',NULL,91), -- Diego Sosa (cantidad_mayores: 1, cantidad_menores: 0)
(68,'Sofi',NULL,92), -- Sofía Godoy (cantidad_mayores: 2, cantidad_menores: 0)
(68,'Cris',NULL,93),
(69,'Luqui',NULL,94), -- Lucas Miranda (cantidad_mayores: 2, cantidad_menores: 0)
(69,'Vero',NULL,95),
(70,'Vale',NULL,96), -- Valentina Juárez (cantidad_mayores: 1, cantidad_menores: 0)
(71,'Juan',NULL,97), -- Juan Gallardo (cantidad_mayores: 1, cantidad_menores: 0)
(72,'Emi',NULL,98), -- Emilia Figueroa (cantidad_mayores: 1, cantidad_menores: 0)
(73,'Pablito',NULL,99), -- Pablo Soto (cantidad_mayores: 2, cantidad_menores: 0)
(73,'Lau',NULL,100),
(74,'Mari',NULL,101), -- Mariana Quiroga (cantidad_mayores: 2, cantidad_menores: 0)
(74,'Fede',NULL,102),
(75,'Agus',NULL,103), -- Agustín Blanco (cantidad_mayores: 1, cantidad_menores: 0)
(76,'Sofi',NULL,104), -- Sofía Pérez (cantidad_mayores: 1, cantidad_menores: 0)
(77,'Martín',NULL,105), -- Martín Suárez (cantidad_mayores: 1, cantidad_menores: 0)
(78,'Diegui',NULL,106), -- Diego Fuentes (cantidad_mayores: 1, cantidad_menores: 0)
(79,'Caro',NULL,107), -- Carolina Cabrera (cantidad_mayores: 1, cantidad_menores: 0)
(80,'Javi',NULL,108), -- Javier Vega (cantidad_mayores: 2, cantidad_menores: 3)
(80,'Vale',NULL,109),
(80,'Mili',NULL,110), -- Niño
(80,'Fran',NULL,111), -- Niño
(80,'Tomito',NULL,112), -- Niño
(81,'Lau',NULL,113), -- Laura Flores (cantidad_mayores: 1, cantidad_menores: 0)
(82,'Guille',NULL,114), -- Guillermo Luna (cantidad_mayores: 2, cantidad_menores: 0)
(82,'Mari',NULL,115),
(83,'Vale',NULL,116), -- Valeria Acosta (cantidad_mayores: 1, cantidad_menores: 0)
(84,'Juancho',NULL,117), -- Juan Díaz (cantidad_mayores: 1, cantidad_menores: 0)
(85,'Pau',NULL,118), -- Paula Moreno (cantidad_mayores: 1, cantidad_menores: 0)
(86,'Richy',NULL,119), -- Ricardo Sánchez (cantidad_mayores: 1, cantidad_menores: 0)
(87,'Nati',NULL,120), -- Natalia Ortiz (cantidad_mayores: 1, cantidad_menores: 0)
(88,'Andi',NULL,121), -- Andrea Ferrer (cantidad_mayores: 1, cantidad_menores: 0)
(89,'Pepe',NULL,122), -- José Vargas (cantidad_mayores: 1, cantidad_menores: 0)
(90,'Mari',NULL,123), -- María Peralta (cantidad_mayores: 2, cantidad_menores: 0)
(90,'Leo',NULL,124),
(91,'Fede',NULL,125), -- Federico Juárez (cantidad_mayores: 1, cantidad_menores: 0)
(92,'Lui',NULL,126), -- Luisa Gallardo (cantidad_mayores: 1, cantidad_menores: 0)
(93,'Marti',NULL,127), -- Martina Figueroa (cantidad_mayores: 1, cantidad_menores: 0)
(94,'Carli',NULL,128), -- Carlos Soto (cantidad_mayores: 1, cantidad_menores: 0)
(95,'Dani',NULL,129), -- Daniela Quiroga (cantidad_mayores: 1, cantidad_menores: 0)
(96,'Migue',NULL,130), -- Miguel Blanco (cantidad_mayores: 1, cantidad_menores: 0)
(97,'Vicky',NULL,131), -- Victoria Vega (cantidad_mayores: 1, cantidad_menores: 0)
(98,'Jorge',NULL,132), -- Jorge Luna (cantidad_mayores: 2, cantidad_menores: 0)
(98,'Ani',NULL,133),
(99,'Ani',NULL,134), -- Ana Acosta (cantidad_mayores: 2, cantidad_menores: 0)
(99,'Juani',NULL,135),
(100,'Pedrito',NULL,136), -- Pedro Diaz (cantidad_mayores: 1, cantidad_menores: 0)
(101,'Emi',NULL,137), -- Emilia Herrera (cantidad_mayores: 1, cantidad_menores: 0)
(102,'Luqui',NULL,138), -- Lucas Martínez (cantidad_mayores: 1, cantidad_menores: 0)
(103,'Vale',NULL,139), -- Valeria Romero (cantidad_mayores: 1, cantidad_menores: 0)
(104,'Santi',NULL,140), -- Santiago Gómez (cantidad_mayores: 1, cantidad_menores: 0)
(105,'Martín',NULL,141), -- Martín Sánchez (cantidad_mayores: 1, cantidad_menores: 0)
(106,'Juli',NULL,142), -- Julia Torres (cantidad_mayores: 2, cantidad_menores: 0)
(106,'Panchito',NULL,143),
(107,'Facu',NULL,144), -- Facundo Ruiz (cantidad_mayores: 1, cantidad_menores: 0)
(108,'Gabi',NULL,145), -- Gabriela Benítez (cantidad_mayores: 1, cantidad_menores: 0)
(109,'Richy',NULL,146), -- Ricardo Silva (cantidad_mayores: 1, cantidad_menores: 0)
(110,'Fer',NULL,147), -- Fernanda Fernández (cantidad_mayores: 1, cantidad_menores: 0)
(111,'Agus',NULL,148), -- Agustín Díaz (cantidad_mayores: 1, cantidad_menores: 0)
(112,'Vale',NULL,149), -- Valeria Gutiérrez (cantidad_mayores: 1, cantidad_menores: 0)
(113,'Emi',NULL,150), -- Emiliano Vargas (cantidad_mayores: 2, cantidad_menores: 0)
(113,'Sofi',NULL,151),
(114,'Caro',NULL,152), -- Carolina Alvarez (cantidad_mayores: 1, cantidad_menores: 0)
(115,'Javi',NULL,153), -- Javier Moretti (cantidad_mayores: 1, cantidad_menores: 0)
(116,'Flor',NULL,154), -- Florencia Delgado (cantidad_mayores: 1, cantidad_menores: 0)
(117,'Marti',NULL,155), -- Martina Molina (cantidad_mayores: 1, cantidad_menores: 0)
(118,'Lau',NULL,156), -- Laura Jiménez (cantidad_mayores: 1, cantidad_menores: 0)
(119,'Diegui',NULL,157), -- Diego Costa (cantidad_mayores: 2, cantidad_menores: 0)
(119,'Mari',NULL,158),
(120,'Luci',NULL,159), -- Lucía Sosa (cantidad_mayores: 2, cantidad_menores: 0)
(120,'Juancho',NULL,160),
(121,'Pablito',NULL,161), -- Pablo Miranda (cantidad_mayores: 1, cantidad_menores: 0)
(122,'Anita',NULL,162), -- Ana Gallardo (cantidad_mayores: 1, cantidad_menores: 0)
(123,'Sofi',NULL,163), -- Sofía Figueroa (cantidad_mayores: 1, cantidad_menores: 0)
(124,'Richy',NULL,164), -- Ricardo Benítez (cantidad_mayores: 2, cantidad_menores: 0)
(124,'Caro',NULL,165),
(125,'Nati',NULL,166), -- Natalia Medina (cantidad_mayores: 2, cantidad_menores: 0)
(125,'Gus',NULL,167),
(126,'Pepe',NULL,168), -- José Quiroga (cantidad_mayores: 2, cantidad_menores: 0)
(126,'Clau',NULL,169),
(127,'Romi',NULL,170), -- Romina Blanco (cantidad_mayores: 1, cantidad_menores: 0)
(128,'Santi',NULL,171), -- Santiago Pérez (cantidad_mayores: 2, cantidad_menores: 0)
(128,'Ale',NULL,172),
(129,'Juli',NULL,173), -- Julieta Gómez (cantidad_mayores: 1, cantidad_menores: 0)
(130,'Marti',NULL,174), -- Martina Rodríguez (cantidad_mayores: 2, cantidad_menores: 0)
(130,'Tomi',NULL,175),
(131,'Luci',NULL,176), -- Lucía Fernández (cantidad_mayores: 2, cantidad_menores: 0)
(131,'Andi',NULL,177),
(132,'Fer',NULL,178), -- Fernando López (cantidad_mayores: 1, cantidad_menores: 0)
(133,'Vicky',NULL,179), -- Victoria Martínez (cantidad_mayores: 2, cantidad_menores: 0)
(133,'Emi',NULL,180),
(134,'Agus',NULL,181), -- Agustín González (cantidad_mayores: 1, cantidad_menores: 0)
(135,'Flor',NULL,182), -- Florencia Díaz (cantidad_mayores: 1, cantidad_menores: 0)
(136,'Gabi',NULL,183), -- Gabriela Sánchez (cantidad_mayores: 2, cantidad_menores: 0)
(136,'Fede',NULL,184),
(137,'Guille',NULL,185), -- Guillermo Romero (cantidad_mayores: 2, cantidad_menores: 0)
(137,'Caro',NULL,186),
(138,'Lau',NULL,187), -- Laura Alvarez (cantidad_mayores: 1, cantidad_menores: 0)
(139,'Fede',NULL,188), -- Federico Torres (cantidad_mayores: 2, cantidad_menores: 0)
(139,'Majo',NULL,189),
(140,'Mica',NULL,190), -- Micaela Ruiz (cantidad_mayores: 2, cantidad_menores: 0)
(140,'Tomi',NULL,191),
(141,'Tomi',NULL,192), -- Tomás Benítez (cantidad_mayores: 2, cantidad_menores: 0)
(141,'Vale',NULL,193),
(142,'Juancho',NULL,194), -- Juan Silva (cantidad_mayores: 2, cantidad_menores: 0)
(142,'Sofi',NULL,195),
(143,'Ro',NULL,196), -- Rocío Fernández (cantidad_mayores: 2, cantidad_menores: 0)
(143,'Gonza',NULL,197),
(144,'Manu',NULL,198), -- Manuel Moretti (cantidad_mayores: 1, cantidad_menores: 0)
(145,'Dani',NULL,199), -- Daniela Ortiz (cantidad_mayores: 1, cantidad_menores: 0)
(146,'Nacho',NULL,200), -- Ignacio Vargas (cantidad_mayores: 1, cantidad_menores: 0)
(147,'Andi',NULL,201), -- Andrea Jiménez (cantidad_mayores: 1, cantidad_menores: 0)
(148,'Carli',NULL,202), -- Carlos Costa (cantidad_mayores: 1, cantidad_menores: 0)
(149,'Vale',NULL,203), -- Valentina Sánchez (cantidad_mayores: 2, cantidad_menores: 0)
(149,'Javi',NULL,204),
(150,'Agus',NULL,205), -- Agustina Gutiérrez (cantidad_mayores: 1, cantidad_menores: 0)
(151,'Emi',NULL,206), -- Emilia Alvarez (cantidad_mayores: 1, cantidad_menores: 0)
(152,'Santi',NULL,207), -- Santiago Torres (cantidad_mayores: 1, cantidad_menores: 0)
(153,'Mariano',NULL,208), -- Mariano Ruiz (cantidad_mayores: 1, cantidad_menores: 0)
(154,'Luci',NULL,209), -- Lucía Benítez (cantidad_mayores: 2, cantidad_menores: 0)
(154,'Fran',NULL,210),
(155,'Pablito',NULL,211), -- Pablo Silva (cantidad_mayores: 2, cantidad_menores: 0)
(155,'Cami',NULL,212),
(156,'Romi',NULL,213), -- Romina Quiroga (cantidad_mayores: 1, cantidad_menores: 0)
(157,'Juan',NULL,214), -- Juan Blanco (cantidad_mayores: 1, cantidad_menores: 0)
(158,'Nati',NULL,215), -- Natalia Gómez (cantidad_mayores: 2, cantidad_menores: 0)
(158,'Emi',NULL,216),
(159,'Vale',NULL,217), -- Valeria Rodríguez (cantidad_mayores: 1, cantidad_menores: 0)
(160,'Javi',NULL,218), -- Javier Fernández (cantidad_mayores: 1, cantidad_menores: 0)
(161,'Flor',NULL,219), -- Florencia López (cantidad_mayores: 1, cantidad_menores: 0)
(162,'Diegui',NULL,220), -- Diego Martínez (cantidad_mayores: 1, cantidad_menores: 0)
(163,'Carli',NULL,221), -- Carla González (cantidad_mayores: 1, cantidad_menores: 0)
(164,'Manu',NULL,222), -- Manuel Díaz (cantidad_mayores: 1, cantidad_menores: 0)
(165,'Mari',NULL,223), -- Mariana Sánchez (cantidad_mayores: 1, cantidad_menores: 0)
(166,'Gabi',NULL,224), -- Gabriel Romero (cantidad_mayores: 1, cantidad_menores: 0)
(167,'Isa',NULL,225), -- Isabella Alvarez (cantidad_mayores: 1, cantidad_menores: 0)
(168,'Tomi',NULL,226), -- Tomás Torres (cantidad_mayores: 1, cantidad_menores: 0)
(169,'Agus',NULL,227), -- Agustina Ruiz (cantidad_mayores: 1, cantidad_menores: 0)
(170,'Luci',NULL,228), -- Luciana Benítez (cantidad_mayores: 1, cantidad_menores: 0)
(171,'Pedrito',NULL,229), -- Pedro Silva (cantidad_mayores: 1, cantidad_menores: 0)
(172,'Juli',NULL,230), -- Julia Quiroga (cantidad_mayores: 1, cantidad_menores: 0)
(173,'Santi',NULL,231), -- Santiago Blanco (cantidad_mayores: 1, cantidad_menores: 0)
(174,'Vale',NULL,232), -- Valeria Pérez (cantidad_mayores: 1, cantidad_menores: 0)
(175,'Martín',NULL,233), -- Martín Gómez (cantidad_mayores: 1, cantidad_menores: 0)
(176,'Flor',NULL,234), -- Florencia Rodríguez (cantidad_mayores: 1, cantidad_menores: 0)
(177,'Emi',NULL,235), -- Emilia Fernández (cantidad_mayores: 1, cantidad_menores: 0)
(178,'Javi',NULL,236), -- Javier López (cantidad_mayores: 1, cantidad_menores: 0)
(179,'Caro',NULL,237), -- Carolina Martínez (cantidad_mayores: 1, cantidad_menores: 0)
(180,'Diegui',NULL,238), -- Diego González (cantidad_mayores: 1, cantidad_menores: 0)
(181,'Manu',NULL,239), -- Manuel Díaz (cantidad_mayores: 1, cantidad_menores: 0)
(182,'Mari',NULL,240), -- Mariana Sánchez (cantidad_mayores: 1, cantidad_menores: 0)
(183,'Gabi',NULL,241), -- Gabriel Romero (cantidad_mayores: 0, cantidad_menores: 0) - No tiene adultos, se asume 0
(184,'Isa',NULL,242), -- Isabella Alvarez (cantidad_mayores: 2, cantidad_menores: 0)
(184,'Tomi',NULL,243),
(185,'Tomi',NULL,244), -- Tomás Torres (cantidad_mayores: 1, cantidad_menores: 0)
(186,'Agus',NULL,245), -- Agustina Ruiz (cantidad_mayores: 1, cantidad_menores: 0)
(187,'Luci',NULL,246), -- Luciana Benítez (cantidad_mayores: 1, cantidad_menores: 0)
(188,'Pedrito',NULL,247), -- Pedro Silva (cantidad_mayores: 1, cantidad_menores: 0)
(189,'Juli',NULL,248), -- Julia Quiroga (cantidad_mayores: 1, cantidad_menores: 0)
(190,'Santi',NULL,249), -- Santiago Blanco (cantidad_mayores: 1, cantidad_menores: 0)
(191,'Vale',NULL,250), -- Valeria Pérez (cantidad_mayores: 1, cantidad_menores: 0)
(192,'Martín',NULL,251), -- Martín Gómez (cantidad_mayores: 1, cantidad_menores: 0)
(193,'Flor',NULL,252), -- Florencia Rodríguez (cantidad_mayores: 1, cantidad_menores: 0)
(194,'Emi',NULL,253), -- Emilia Fernández (cantidad_mayores: 1, cantidad_menores: 0)
(195,'Javi',NULL,254), -- Javier López (cantidad_mayores: 1, cantidad_menores: 0)
(196,'Caro',NULL,255), -- Carolina Martínez (cantidad_mayores: 1, cantidad_menores: 0)
(197,'Diegui',NULL,256), -- Diego González (cantidad_mayores: 2, cantidad_menores: 0)
(197,'Lau',NULL,257),
(198,'Manu',NULL,258), -- Manuel Díaz (cantidad_mayores: 1, cantidad_menores: 0)
(199,'Mari',NULL,259), -- Mariana Sánchez (cantidad_mayores: 1, cantidad_menores: 0)
(200,'Gabi',NULL,260), -- Gabriel Romero (cantidad_mayores: 1, cantidad_menores: 0)
(201,'Isa',NULL,261), -- Isabella Alvarez (cantidad_mayores: 1, cantidad_menores: 0)
(202,'Tomi',NULL,262), -- Tomás Torres (cantidad_mayores: 2, cantidad_menores: 0)
(202,'Mica',NULL,263),
(203,'Agus',NULL,264), -- Agustina Ruiz (cantidad_mayores: 2, cantidad_menores: 0)
(203,'Vale',NULL,265),
(204,'Luci',NULL,266), -- Luciana Benítez (cantidad_mayores: 2, cantidad_menores: 0)
(204,'Fran',NULL,267),
(205,'Pedrito',NULL,268), -- Pedro Silva (cantidad_mayores: 1, cantidad_menores: 0)
(206,'Juli',NULL,269), -- Julia Quiroga (cantidad_mayores: 1, cantidad_menores: 0)
(207,'Santi',NULL,270), -- Santiago Blanco (cantidad_mayores: 1, cantidad_menores: 0)
(208,'Vale',NULL,271), -- Valeria Pérez (cantidad_mayores: 1, cantidad_menores: 0)
(209,'Martín',NULL,272), -- Martín Gómez (cantidad_mayores: 2, cantidad_menores: 0)
(209,'Gabi',NULL,273),
(210,'Flor',NULL,274), -- Florencia Rodríguez (cantidad_mayores: 2, cantidad_menores: 0)
(210,'Pau',NULL,275),
(211,'Emi',NULL,276), -- Emilia Fernández (cantidad_mayores: 1, cantidad_menores: 0)
(213,'Javi',NULL,277), -- Javier López (cantidad_mayores: 2, cantidad_menores: 0)
(213,'Caro',NULL,278),
(214,'Caro',NULL,279), -- Carolina Martínez (cantidad_mayores: 1, cantidad_menores: 0)
(215,'Diegui',NULL,280), -- Diego González (cantidad_mayores: 1, cantidad_menores: 0)
(216,'Manu',NULL,281), -- Manuel Díaz (cantidad_mayores: 1, cantidad_menores: 0)
(217,'Mari',NULL,282), -- Mariana Sánchez (cantidad_mayores: 1, cantidad_menores: 0)
(218,'Gabi',NULL,283), -- Gabriel Romero (cantidad_mayores: 2, cantidad_menores: 0)
(218,'Andi',NULL,284),
(219,'Isa',NULL,285), -- Isabella Alvarez (cantidad_mayores: 2, cantidad_menores: 0)
(219,'Juli',NULL,286),
(220,'Tomi',NULL,287), -- Tomás Torres (cantidad_mayores: 2, cantidad_menores: 0)
(220,'Mica',NULL,288),
(221,'Agus',NULL,289), -- Agustina Ruiz (cantidad_mayores: 2, cantidad_menores: 0)
(221,'Leo',NULL,290),
(222,'Luci',NULL,291), -- Luciana Benítez (cantidad_mayores: 2, cantidad_menores: 0)
(222,'Fran',NULL,292),
(223,'Pedrito',NULL,293), -- Pedro Silva (cantidad_mayores: 1, cantidad_menores: 0)
(224,'Juli',NULL,294), -- Julia Quiroga (cantidad_mayores: 2, cantidad_menores: 0)
(224,'Romi',NULL,295),
(225,'Santi',NULL,296), -- Santiago Blanco (cantidad_mayores: 2, cantidad_menores: 0)
(225,'Vale',NULL,297),
(226,'Vale',NULL,298), -- Valeria Pérez (cantidad_mayores: 1, cantidad_menores: 0)
(227,'Martín',NULL,299), -- Martín Gómez (cantidad_mayores: 2, cantidad_menores: 0)
(227,'Lau',NULL,300),
(228,'Flor',NULL,301), -- Florencia Rodríguez (cantidad_mayores: 2, cantidad_menores: 1)
(228,'Emi',NULL,302),
(228,'Pau',NULL,303), -- Niño
(229,'Emi',NULL,304), -- Emilia Fernández (cantidad_mayores: 1, cantidad_menores: 0)
(230,'Javi',NULL,305), -- Javier López (cantidad_mayores: 1, cantidad_menores: 0)
(231,'Caro',NULL,306), -- Carolina Martínez (cantidad_mayores: 2, cantidad_menores: 0)
(231,'Nico',NULL,307),
(232,'Diegui',NULL,308), -- Diego González (cantidad_mayores: 1, cantidad_menores: 0)
(233,'Manu',NULL,309), -- Manuel Díaz (cantidad_mayores: 2, cantidad_menores: 0)
(233,'Ana',NULL,310),
(234,'Mari',NULL,311), -- Mariana Sánchez (cantidad_mayores: 1, cantidad_menores: 0)
(235,'Gabi',NULL,312), -- Gabriel Romero (cantidad_mayores: 1, cantidad_menores: 0)
(236,'Isa',NULL,313), -- Isabella Alvarez (cantidad_mayores: 2, cantidad_menores: 1)
(236,'Juan',NULL,314),
(236,'Mati',NULL,315), -- Niño
(237,'Tomi',NULL,316), -- Tomás Torres (cantidad_mayores: 2, cantidad_menores: 0)
(237,'Flor',NULL,317),
(238,'Agus',NULL,318), -- Agustina Ruiz (cantidad_mayores: 2, cantidad_menores: 0)
(238,'Fede',NULL,319),
(239,'Luci',NULL,320), -- Luciana Benítez (cantidad_mayores: 1, cantidad_menores: 0)
(240,'Pedrito',NULL,321), -- Pedro Silva (cantidad_mayores: 1, cantidad_menores: 0)
(241,'Juli',NULL,322), -- Julia Quiroga (cantidad_mayores: 2, cantidad_menores: 0)
(241,'Ro',NULL,323),
(242,'Santi',NULL,324), -- Santiago Blanco (cantidad_mayores: 2, cantidad_menores: 0)
(242,'Cami',NULL,325),
(243,'Vale',NULL,326), -- Valeria Pérez (cantidad_mayores: 2, cantidad_menores: 0)
(243,'Tomi',NULL,327),
(244,'Martín',NULL,328), -- Martín Gómez (cantidad_mayores: 1, cantidad_menores: 0)
(245,'Flor',NULL,329), -- Florencia Rodríguez (cantidad_mayores: 2, cantidad_menores: 0)
(245,'Emi',NULL,330),
(246,'Emi',NULL,331), -- Emilia Fernández (cantidad_mayores: 2, cantidad_menores: 0)
(246,'Javi',NULL,332),
(247,'Javi',NULL,333), -- Javier López (cantidad_mayores: 1, cantidad_menores: 0)
(248,'Caro',NULL,334), -- Carolina Martínez (cantidad_mayores: 2, cantidad_menores: 0)
(248,'Gonza',NULL,335),
(249,'Diegui',NULL,336), -- Diego González (cantidad_mayores: 1, cantidad_menores: 0)
(250,'Manu',NULL,337), -- Manuel Díaz (cantidad_mayores: 2, cantidad_menores: 0)
(250,'Sofi',NULL,338),
(251,'Mari',NULL,339), -- Mariana Sánchez (cantidad_mayores: 2, cantidad_menores: 0)
(251,'Leo',NULL,340),
(252,'Gabi',NULL,341), -- Gabriel Romero (cantidad_mayores: 2, cantidad_menores: 0)
(252,'Delfi',NULL,342),
(253,'Isa',NULL,343), -- Isabella Alvarez (cantidad_mayores: 4, cantidad_menores: 0)
(253,'Juan',NULL,344),
(253,'Mati',NULL,345),
(253,'Pau',NULL,346),
(254,'Tomi',NULL,347), -- Tomás Torres (cantidad_mayores: 3, cantidad_menores: 2)
(254,'Flor',NULL,348),
(254,'Juli',NULL,349),
(254,'Benja',NULL,350), -- Niño
(254,'Mili',NULL,351), -- Niño
(255,'Agus',NULL,352), -- Agustina Ruiz (cantidad_mayores: 3, cantidad_menores: 1)
(255,'Vale',NULL,353),
(255,'Fede',NULL,354),
(255,'Tomi',NULL,355), -- Niño
(256,'Luci',NULL,356), -- Luciana Benítez (cantidad_mayores: 2, cantidad_menores: 0)
(256,'Fran',NULL,357),
(257,'Pedrito',NULL,358), -- Pedro Silva (cantidad_mayores: 1, cantidad_menores: 0)
(258,'Juli',NULL,359), -- Julia Quiroga (cantidad_mayores: 1, cantidad_menores: 0)
(259,'Santi',NULL,360), -- Santiago Blanco (cantidad_mayores: 2, cantidad_menores: 0)
(259,'Cami',NULL,361),
(260,'Vale',NULL,362), -- Valeria Pérez (cantidad_mayores: 2, cantidad_menores: 0)
(260,'Tomi',NULL,363),
(261,'Martín',NULL,364), -- Martín Gómez (cantidad_mayores: 2, cantidad_menores: 0)
(261,'Lau',NULL,365),
(262,'Flor',NULL,366), -- Florencia Rodríguez (cantidad_mayores: 2, cantidad_menores: 0)
(262,'Emi',NULL,367),
(263,'Emi',NULL,368), -- Emilia Fernández (cantidad_mayores: 1, cantidad_menores: 0)
(264,'Javi',NULL,369), -- Javier López (cantidad_mayores: 1, cantidad_menores: 0)
(265,'Caro',NULL,370), -- Carolina Martínez (cantidad_mayores: 2, cantidad_menores: 0)
(265,'Nico',NULL,371),
(266,'Diegui',NULL,372), -- Diego González (cantidad_mayores: 2, cantidad_menores: 0)
(266,'Ana',NULL,373),
(267,'Manu',NULL,374), -- Manuel Díaz (cantidad_mayores: 2, cantidad_menores: 0)
(267,'Sofi',NULL,375),
(268,'Mari',NULL,376), -- Mariana Sánchez (cantidad_mayores: 2, cantidad_menores: 0)
(268,'Leo',NULL,377),
(269,'Gabi',NULL,378), -- Gabriel Romero (cantidad_mayores: 2, cantidad_menores: 0)
(269,'Delfi',NULL,379),
(270,'Isa',NULL,380), -- Isabella Alvarez (cantidad_mayores: 2, cantidad_menores: 0)
(270,'Juan',NULL,381),
(271,'Tomi',NULL,382), -- Tomás Torres (cantidad_mayores: 2, cantidad_menores: 0)
(271,'Mati',NULL,383),
(272,'Agus',NULL,384), -- Agustina Ruiz (cantidad_mayores: 2, cantidad_menores: 0)
(272,'Pau',NULL,385),
(273,'Luci',NULL,386), -- Luciana Benítez (cantidad_mayores: 2, cantidad_menores: 0)
(273,'Fran',NULL,387),
(274,'Pedrito',NULL,388), -- Pedro Silva (cantidad_mayores: 2, cantidad_menores: 0)
(274,'Lau',NULL,389),
(275,'Juli',NULL,390), -- Julia Quiroga (cantidad_mayores: 2, cantidad_menores: 0)
(275,'Romi',NULL,391),
(276,'Santi',NULL,392), -- Santiago Blanco (cantidad_mayores: 2, cantidad_menores: 0)
(276,'Vale',NULL,393),
(277,'Vale',NULL,394), -- Valeria Pérez (cantidad_mayores: 2, cantidad_menores: 0)
(277,'Tomi',NULL,395),
(278,'Martín',NULL,396), -- Martín Gómez (cantidad_mayores: 2, cantidad_menores: 0)
(278,'Lau',NULL,397),
(279,'Flor',NULL,398), -- Florencia Rodríguez (cantidad_mayores: 2, cantidad_menores: 0)
(279,'Emi',NULL,399),
(280,'Emi',NULL,400), -- Emilia Fernández (cantidad_mayores: 2, cantidad_menores: 0)
(280,'Javi',NULL,401),
(281,'Javi',NULL,402), -- Javier López (cantidad_mayores: 2, cantidad_menores: 0)
(281,'Caro',NULL,403),
(282,'Caro',NULL,404), -- Carolina Martínez (cantidad_mayores: 2, cantidad_menores: 0)
(282,'Nico',NULL,405),
(283,'Diegui',NULL,406), -- Diego González (cantidad_mayores: 2, cantidad_menores: 0)
(283,'Ana',NULL,407),
(284,'Manu',NULL,408), -- Manuel Díaz (cantidad_mayores: 2, cantidad_menores: 0)
(284,'Sofi',NULL,409),
(285,'Mari',NULL,410), -- Mariana Sánchez (cantidad_mayores: 2, cantidad_menores: 0)
(285,'Leo',NULL,411),
(286,'Gabi',NULL,412), -- Gabriel Romero (cantidad_mayores: 2, cantidad_menores: 0)
(286,'Delfi',NULL,413),
(287,'Isa',NULL,414), -- Isabella Alvarez (cantidad_mayores: 2, cantidad_menores: 0)
(287,'Juan',NULL,415),
(288,'Tomi',NULL,416), -- Tomás Torres (cantidad_mayores: 1, cantidad_menores: 0)
(289,'Agus',NULL,417), -- Agustina Ruiz (cantidad_mayores: 1, cantidad_menores: 0)
(290,'Luci',NULL,418), -- Luciana Benítez (cantidad_mayores: 1, cantidad_menores: 0)
(291,'Pedrito',NULL,419), -- Pedro Silva (cantidad_mayores: 1, cantidad_menores: 0)
(292,'Juli',NULL,420);  -- Julia Quiroga (cantidad_mayores: 1, cantidad_menores: 0)



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



/*Data for the table `invitados_tel` */

LOCK TABLES `invitados_tel` WRITE;

INSERT INTO `invitados_tel` (`id_invitados`, `tel_enviar`) VALUES
(1, 1123456789),
(2, 1134567890),
(3, 1145678901),
(4, 1156789012),
(4, 1167890123), -- Segundo número para ID 4
(5, 1178901234),
(5, 1189012345), -- Segundo número para ID 5
(6, 1190123456),
(7, 1101234567),
(8, 1112345678),
(9, 1123456789),
(10, 1134567890),
(11, 1145678901),
(12, 1156789012),
(13, 1167890123),
(14, 1178901234),
(15, 1189012345),
(15, 1190123456), -- Segundo número para ID 15
(16, 1101234567),
(17, 1112345678),
(18, 1123456789),
(19, 1134567890),
(20, 1145678901),
(21, 1156789012),
(22, 1167890123),
(23, 1178901234),
(23, 1189012345), -- Segundo número para ID 23
(24, 1190123456),
(25, 1101234567),
(25, 1112345678), -- Segundo número para ID 25
(26, 1123456789),
(27, 1134567890),
(28, 1145678901),
(29, 1156789012),
(30, 1167890123),
(31, 1178901234),
(32, 1189012345),
(33, 1190123456),
(33, 1101234567), -- Segundo número para ID 33
(34, 1112345678),
(35, 1123456789),
(36, 1134567890),
(37, 1145678901),
(37, 1156789012), -- Segundo número para ID 37
(38, 1167890123),
(39, 1178901234),
(39, 1189012345), -- Segundo número para ID 39
(40, 1190123456),
(41, 1101234567),
(42, 1112345678),
(43, 1123456789),
(43, 1134567890), -- Segundo número para ID 43
(44, 1145678901),
(45, 1156789012),
(46, 1167890123),
(47, 1178901234),
(48, 1189012345),
(49, 1190123456),
(49, 1101234567), -- Segundo número para ID 49
(50, 1112345678),
(50, 1123456789), -- Segundo número para ID 50
(51, 1134567890),
(51, 1145678901), -- Segundo número para ID 51
(52, 1156789012),
(52, 1167890123), -- Segundo número para ID 52
(53, 1178901234),
(53, 1189012345), -- Segundo número para ID 53
(54, 1190123456),
(55, 1101234567),
(55, 1112345678), -- Segundo número para ID 55
(56, 1123456789),
(57, 1134567890),
(58, 1145678901),
(58, 1156789012), -- Segundo número para ID 58
(59, 1167890123),
(60, 1178901234),
(61, 1189012345),
(62, 1190123456),
(62, 1101234567), -- Segundo número para ID 62
(63, 1112345678),
(63, 1123456789), -- Segundo número para ID 63
(64, 1134567890),
(65, 1145678901),
(65, 1156789012), -- Segundo número para ID 65
(66, 1167890123),
(67, 1178901234),
(68, 1189012345),
(69, 1190123456),
(69, 1101234567), -- Segundo número para ID 69
(70, 1112345678),
(71, 1123456789),
(72, 1134567890),
(73, 1145678901),
(73, 1156789012), -- Segundo número para ID 73
(74, 1167890123),
(74, 1178901234), -- Segundo número para ID 74
(75, 1189012345),
(76, 1190123456),
(77, 1101234567),
(78, 1112345678),
(79, 1123456789),
(80, 1134567890),
(80, 1145678901), -- Segundo número para ID 80
(81, 1156789012),
(82, 1167890123),
(82, 1178901234), -- Segundo número para ID 82
(83, 1189012345),
(84, 1190123456),
(85, 1101234567),
(86, 1112345678),
(87, 1123456789),
(88, 1134567890),
(89, 1145678901),
(90, 1156789012),
(90, 1167890123), -- Segundo número para ID 90
(91, 1178901234),
(92, 1189012345),
(93, 1190123456),
(94, 1101234567),
(95, 1112345678),
(96, 1123456789),
(97, 1134567890),
(98, 1145678901),
(98, 1156789012), -- Segundo número para ID 98
(99, 1167890123),
(99, 1178901234), -- Segundo número para ID 99
(100, 1189012345),
(101, 1190123456),
(102, 1101234567),
(103, 1112345678),
(104, 1123456789),
(105, 1134567890),
(106, 1145678901),
(106, 1156789012), -- Segundo número para ID 106
(107, 1167890123),
(108, 1178901234),
(108, 1189012345), -- Segundo número para ID 108
(109, 1190123456),
(109, 1101234567), -- Segundo número para ID 109
(110, 1112345678),
(111, 1123456789),
(112, 1134567890),
(113, 1145678901),
(113, 1156789012), -- Segundo número para ID 113
(114, 1167890123),
(115, 1178901234),
(116, 1189012345),
(117, 1190123456),
(118, 1101234567),
(119, 1112345678),
(119, 1123456789), -- Segundo número para ID 119
(120, 1134567890),
(120, 1145678901), -- Segundo número para ID 120
(121, 1156789012),
(122, 1167890123),
(123, 1178901234),
(124, 1189012345),
(124, 1190123456), -- Segundo número para ID 124
(125, 1101234567),
(125, 1112345678), -- Segundo número para ID 125
(126, 1123456789),
(126, 1134567890), -- Segundo número para ID 126
(127, 1145678901),
(128, 1156789012),
(128, 1167890123), -- Segundo número para ID 128
(129, 1178901234),
(130, 1189012345),
(130, 1190123456), -- Segundo número para ID 130
(131, 1101234567),
(131, 1112345678), -- Segundo número para ID 131
(132, 1123456789),
(133, 1134567890),
(133, 1145678901), -- Segundo número para ID 133
(134, 1156789012),
(135, 1167890123),
(136, 1178901234),
(136, 1189012345), -- Segundo número para ID 136
(137, 1190123456),
(137, 1101234567), -- Segundo número para ID 137
(138, 1112345678),
(139, 1123456789),
(139, 1134567890), -- Segundo número para ID 139
(140, 1145678901),
(140, 1156789012), -- Segundo número para ID 140
(141, 1167890123),
(141, 1178901234), -- Segundo número para ID 141
(142, 1189012345),
(142, 1190123456), -- Segundo número para ID 142
(143, 1101234567),
(143, 1112345678), -- Segundo número para ID 143
(144, 1123456789),
(145, 1134567890),
(146, 1145678901),
(147, 1156789012),
(148, 1167890123),
(149, 1178901234),
(149, 1189012345), -- Segundo número para ID 149
(150, 1190123456),
(151, 1101234567),
(152, 1112345678),
(153, 1123456789),
(154, 1134567890),
(154, 1145678901), -- Segundo número para ID 154
(155, 1156789012),
(155, 1167890123), -- Segundo número para ID 155
(156, 1178901234),
(157, 1189012345),
(158, 1190123456),
(158, 1101234567), -- Segundo número para ID 158
(159, 1112345678),
(160, 1123456789),
(161, 1134567890),
(162, 1145678901),
(163, 1156789012),
(164, 1167890123),
(165, 1178901234),
(166, 1189012345),
(167, 1190123456),
(168, 1101234567),
(169, 1112345678),
(170, 1123456789),
(171, 1134567890),
(172, 1145678901),
(173, 1156789012),
(174, 1167890123),
(175, 1178901234),
(176, 1189012345),
(177, 1190123456),
(178, 1101234567),
(179, 1112345678),
(180, 1123456789),
(181, 1134567890),
(182, 1145678901),
(183, 1156789012),
(184, 1167890123),
(184, 1178901234), -- Segundo número para ID 184
(185, 1189012345),
(186, 1190123456),
(187, 1101234567),
(188, 1112345678),
(189, 1123456789),
(190, 1134567890),
(191, 1145678901),
(192, 1156789012),
(193, 1167890123),
(194, 1178901234),
(195, 1189012345),
(196, 1190123456),
(197, 1101234567),
(197, 1112345678), -- Segundo número para ID 197
(198, 1123456789),
(199, 1134567890),
(200, 1145678901),
(201, 1156789012),
(202, 1167890123),
(202, 1178901234), -- Segundo número para ID 202
(203, 1189012345),
(203, 1190123456), -- Segundo número para ID 203
(204, 1101234567),
(204, 1112345678), -- Segundo número para ID 204
(205, 1123456789),
(206, 1134567890),
(207, 1145678901),
(208, 1156789012),
(209, 1167890123),
(209, 1178901234), -- Segundo número para ID 209
(210, 1189012345),
(210, 1190123456), -- Segundo número para ID 210
(211, 1101234567),
(213, 1112345678),
(213, 1123456789), -- Segundo número para ID 213
(214, 1134567890),
(215, 1145678901),
(216, 1156789012),
(217, 1167890123),
(218, 1178901234),
(218, 1189012345), -- Segundo número para ID 218
(219, 1190123456),
(219, 1101234567), -- Segundo número para ID 219
(220, 1112345678),
(220, 1123456789), -- Segundo número para ID 220
(221, 1134567890),
(221, 1145678901), -- Segundo número para ID 221
(222, 1156789012),
(222, 1167890123), -- Segundo número para ID 222
(223, 1178901234),
(224, 1189012345),
(224, 1190123456), -- Segundo número para ID 224
(225, 1101234567),
(225, 1112345678), -- Segundo número para ID 225
(226, 1123456789),
(227, 1134567890),
(227, 1145678901), -- Segundo número para ID 227
(228, 1156789012),
(228, 1167890123), -- Segundo número para ID 228
(229, 1178901234),
(230, 1189012345),
(231, 1190123456),
(231, 1101234567), -- Segundo número para ID 231
(232, 1112345678),
(233, 1123456789),
(233, 1134567890), -- Segundo número para ID 233
(234, 1145678901),
(235, 1156789012),
(236, 1167890123),
(236, 1178901234), -- Segundo número para ID 236
(237, 1189012345),
(237, 1190123456), -- Segundo número para ID 237
(238, 1101234567),
(238, 1112345678), -- Segundo número para ID 238
(239, 1123456789),
(240, 1134567890),
(241, 1145678901),
(241, 1156789012), -- Segundo número para ID 241
(242, 1167890123),
(242, 1178901234), -- Segundo número para ID 242
(243, 1189012345),
(243, 1190123456), -- Segundo número para ID 243
(244, 1101234567),
(245, 1112345678),
(245, 1123456789), -- Segundo número para ID 245
(246, 1134567890),
(246, 1145678901), -- Segundo número para ID 246
(247, 1156789012),
(248, 1167890123),
(248, 1178901234), -- Segundo número para ID 248
(249, 1189012345),
(249, 1190123456), -- Segundo número para ID 249
(250, 1101234567),
(250, 1112345678), -- Segundo número para ID 250
(251, 1123456789),
(251, 1134567890), -- Segundo número para ID 251
(252, 1145678901),
(252, 1156789012), -- Segundo número para ID 252
(253, 1167890123),
(253, 1178901234), -- Segundo número para ID 253
(254, 1189012345),
(254, 1190123456), -- Segundo número para ID 254
(255, 1101234567),
(255, 1112345678), -- Segundo número para ID 255
(256, 1123456789),
(256, 1134567890), -- Segundo número para ID 256
(257, 1145678901),
(258, 1156789012),
(259, 1167890123),
(259, 1178901234), -- Segundo número para ID 259
(260, 1189012345),
(260, 1190123456), -- Segundo número para ID 260
(261, 1101234567),
(261, 1112345678), -- Segundo número para ID 261
(262, 1123456789),
(262, 1134567890), -- Segundo número para ID 262
(263, 1145678901),
(264, 1156789012),
(265, 1167890123),
(265, 1178901234), -- Segundo número para ID 265
(266, 1189012345),
(266, 1190123456), -- Segundo número para ID 266
(267, 1101234567),
(267, 1112345678), -- Segundo número para ID 267
(268, 1123456789),
(268, 1134567890), -- Segundo número para ID 268
(269, 1145678901),
(269, 1156789012), -- Segundo número para ID 269
(270, 1167890123),
(270, 1178901234), -- Segundo número para ID 270
(271, 1189012345),
(271, 1190123456), -- Segundo número para ID 271
(272, 1101234567),
(272, 1112345678), -- Segundo número para ID 272
(273, 1123456789),
(273, 1134567890), -- Segundo número para ID 273
(274, 1145678901),
(274, 1156789012), -- Segundo número para ID 274
(275, 1167890123),
(275, 1178901234), -- Segundo número para ID 275
(276, 1189012345),
(276, 1190123456), -- Segundo número para ID 276
(277, 1101234567),
(277, 1112345678), -- Segundo número para ID 277
(278, 1123456789),
(278, 1134567890), -- Segundo número para ID 278
(279, 1145678901),
(279, 1156789012), -- Segundo número para ID 279
(280, 1167890123),
(280, 1178901234), -- Segundo número para ID 280
(281, 1189012345),
(281, 1190123456), -- Segundo número para ID 281
(282, 1101234567),
(282, 1112345678), -- Segundo número para ID 282
(283, 1123456789),
(283, 1134567890), -- Segundo número para ID 283
(284, 1145678901),
(284, 1156789012), -- Segundo número para ID 284
(285, 1156789012),
(285, 1167890123), -- Segundo número para ID 285
(286, 1178901234),
(286, 1189012345), -- Segundo número para ID 286
(287, 1190123456),
(287, 1101234567), -- Segundo número para ID 287
(288, 1112345678),
(289, 1123456789),
(290, 1134567890),
(291, 1145678901),
(292, 1156789012);

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



/*Table structure for table `cliente` */

DROP TABLE IF EXISTS `cliente`;

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
  `plan` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*Table structure for table `visitas` */
DROP TABLE IF EXISTS `visitas`;

CREATE TABLE `visitas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_visita` timestamp NULL DEFAULT current_timestamp(),
  `ip_usuario` varchar(45) NOT NULL,
  `pagina_visitada` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci