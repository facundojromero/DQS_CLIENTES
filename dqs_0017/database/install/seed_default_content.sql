-- DQS optional editable starter content v0.1.1 (UNI-048.1.1)
-- Demo content for a clean installation. Every row may be edited in admin.
-- Apply only after database/install/seed.sql.

START TRANSACTION;

-- Wedding cover.
INSERT INTO `info_casamiento`
  (`portada_titulo`, `portada_frase`, `portada_fecha`, `portada_fecha_hora`)
VALUES
  ('#Maria y #Jose', 'Nos casamos', 'El 8 de diciembre del 2025', '2025-12-08 17:00:00');

-- Couple introductions.
INSERT INTO `info_nosotros` (`id`, `nombre`, `texto`, `activo`, `orden`) VALUES
  (1, 'Maria', 'está viviendo un momento único en su vida: está a punto de casarse con él, el hombre con quien comparte su presente y sueña su futuro. Desde siempre, imaginó este momento, pero ahora que está tan cerca, lo vive con emoción, nervios y mucha ilusión.\r\n\r\nEs diseñadora gráfica y ama todo lo relacionado con el arte y la creatividad. Su trabajo le permite expresar su estilo y crear piezas visuales que transmiten emociones. En su tiempo libre, le gusta pintar, hacer lettering y sacar fotos con su cámara analógica. También disfruta recorrer ferias de diseño y descubrir pequeños cafés escondidos en la ciudad.\r\n\r\nSofía es una persona sociable y cariñosa, siempre rodeada de amigos y familia. Organiza encuentros en su casa con mates y medialunas, y le encanta conversar durante horas. Es fanática de los libros de romance y siempre tiene uno en su cartera. También le gusta la música indie y el cine, en especial las películas con historias profundas y visuales impactantes.\r\n\r\nEn cuanto a su estilo de vida, es relajada pero organizada. Le gusta hacer yoga para desconectar del estrés y salir a caminar por la ciudad sin rumbo fijo. Disfruta cocinar, aunque admite que lo suyo son más los postres que las comidas elaboradas.\r\n\r\nAhora que está por casarse, Sofía siente que está en una montaña rusa de emociones. Quiere que la boda refleje su personalidad y que cada detalle sea especial. Más allá de la fiesta, lo que más le importa es la vida que va a construir con Martín, llena de amor, complicidad y proyectos en común.', 1, NULL),
  (2, 'Jose', 'es un joven entusiasta y soñador que está a punto de dar uno de los pasos más importantes de su vida: casarse con el amor de su vida, Sofía. Desde pequeño, siempre imaginó formar una familia y construir un hogar lleno de amor y compañerismo.\r\n\r\nLe encanta la tecnología y trabaja como ingeniero en una empresa de software, donde desarrolla aplicaciones móviles. Es una persona meticulosa, organizada y siempre busca mejorar las cosas a su alrededor. En su tiempo libre, disfruta de los videojuegos, salir a correr por los parques de Palermo y probar nuevas cafeterías con Sofía.\r\n\r\nSi bien es fanático de la tecnología, también le apasiona la música. Toca la guitarra desde los 15 años y siempre sueña con armar una banda con sus amigos. Tiene gustos variados: desde rock nacional hasta música indie. Además, le gusta el cine y tiene un especial cariño por las películas de ciencia ficción.\r\n\r\nEn cuanto a su estilo de vida, es una persona activa. Le gusta mantenerse en forma, pero no es de los que van religiosamente al gimnasio; prefiere deportes al aire libre como el fútbol y el ciclismo. También es un amante de la comida casera y suele preparar cenas especiales para Sofía los fines de semana.\r\n\r\nAhora que está a punto de casarse, Martín se siente emocionado y un poco nervioso. Quiere que todo salga perfecto, pero también ha aprendido a disfrutar del proceso. Sabe que el matrimonio no es solo una ceremonia, sino un viaje de aprendizaje y crecimiento junto a la persona que ama.', 1, NULL);

-- Editable timeline.
INSERT INTO `info_historia` (`id`, `fecha`, `titulo`, `texto`, `activo`) VALUES
  (1, '2021-06-15', 'Nos conocimos', 'Una tarde de invierno, en una librería de San Telmo, nuestras vidas se cruzaron por casualidad. Sofía buscaba un libro de Cortázar, y José, sin pensarlo, le recomendó uno de Borges. Entre risas y charla sobre literatura, intercambiamos números.', 1),
  (2, '2021-07-10', 'Primera cita', 'Después de varias conversaciones por WhatsApp, nos animamos a salir. Nos encontramos en un café en Palermo, y entre café y medialunas, pasamos horas hablando de nuestros sueños y pasiones. Sentimos una conexión especial desde el primer momento.', 1),
  (3, '2021-12-24', 'Primeras fiestas juntos', 'Fue nuestra primera Navidad juntos. Nos conocimos un poco más al compartir con nuestras familias. En Año Nuevo, vimos los fuegos artificiales desde la Costanera y prometimos que el próximo año sería aún mejor.', 1),
  (4, '2022-05-15', 'Viaje a Bariloche', 'Decidimos hacer nuestro primer viaje juntos a Bariloche. Entre caminatas por los senderos del Llao Llao y chocolates calientes en el centro, nos dimos cuenta de lo bien que nos llevábamos en cualquier lugar.', 1),
  (5, '2022-10-02', 'Nos fuimos a vivir juntos', 'Después de un año y medio de relación, decidimos dar el siguiente paso: alquilamos un departamento en Belgrano. Aunque la convivencia tenía sus desafíos, amábamos compartir el día a día, desde cocinar juntos hasta elegir qué película ver cada noche.', 1),
  (6, '2023-02-14', 'Nos comprometimos', 'José preparó una sorpresa para el día de San Valentín. Me llevó a Tigre, a nuestro lugar favorito junto al río, y sacó un anillo. “¿Querés casarte conmigo?” preguntó, nervioso pero con una sonrisa. Sin dudarlo, dije que sí, entre lágrimas y abrazos.', 1),
  (7, '2023-09-15', 'Preparativos de la boda', 'Entre prueba de vestidos, elección del catering y lista de invitados, los meses pasaban volando. Queríamos que la boda reflejara nuestra historia, sencilla pero llena de amor.', 1),
  (8, '2024-10-20', 'El gran día', 'Después de tres años juntos, llegó el día que tanto soñamos. Con nuestras familias y amigos como testigos, nos dimos el “sí, quiero” en una hermosa ceremonia al aire libre. Bailamos hasta el amanecer, celebrando nuestro amor y el comienzo de una nueva etapa.', 1);

-- Editable events.
INSERT INTO `info_eventos`
  (`id`, `fecha`, `titulo`, `descripcion`, `direccion`, `url`, `tipo_visual`, `imagen`, `icono`, `orden`, `activo`)
VALUES
  (1, '0000-00-00', 'Ceremonia', 'Basílica Nuestra Señora del Pilar', 'Junín 1898, Cdad. Autónoma de Buenos Aires', 'https://maps.app.goo.gl/chfBEb6dxNg3RSCNA', 'imagen', '1754171387_pilar-recoleta.jpg', 'fas fa-cross', 0, 1),
  (2, '0000-00-00', 'Fiesta', 'La fiesta la aremos en el salón más lindo', 'Av. Corrientes, Cdad. Autónoma de Buenos Aires', 'https://maps.app.goo.gl/Rrq9EijK5yXBrzmCA', 'imagen', '1754172086_salon.jpeg', 'fas fa-music', 0, 1),
  (3, '0000-00-00', 'Baile', 'Armamos un sector especial del salón, más despejado y cómodo, para que puedas disfrutar la música sin que las mesas molesten.\r\nEs el lugar ideal para moverse, compartir y dejarse llevar por el ritmo.\r\n¡Vení con ganas de bailar y pasarla increíble!', '', '', 'imagen', '1754172590_salon_2.jpeg', 'fas fa-glass-cheers', 0, 1),
  (4, '0000-00-00', 'Otro evento', 'Descripción del evento', '', '', 'icono', '', 'fas fa-glass-cheers', 0, 0),
  (5, '0000-00-00', 'Otro evento 2', 'Descripción del evento', '', '', 'icono', '', 'fas fa-hotel', 0, 0),
  (6, '0000-00-00', 'Otro evento 3', 'Descripción del evento', '', '', 'icono', '', 'fas fa-birthday-cake', 0, 0);

-- Additional editable information.
INSERT INTO `info_otra`
  (`id`, `titulo`, `descripcion`, `direccion`, `url`, `icono`, `activo`, `orden`)
VALUES
  (1, 'Dress Codes', 'Elegante y cómodo. Queremos que te veas bien y te sientas mejor.\r\nVestite con estilo, pero sin complicaciones.', '', '', 'fas fa-user-tie', 1, 1),
  (2, 'Redes', 'Seguinos en nuestra red social para enterarte de todas las novedades', '', 'https://instagram.com/dijequesi.ar', 'fab fa-instagram', 1, 4),
  (3, 'Instagram', 'Seguinos en instagram para estar al tanto de la preparación. Y así tambien despues no podes etiquetar en la foto que quieras', '', 'https://www.instagram.com/dijequesi.ar', 'fab fa-instagram', 0, 5),
  (4, 'Ayúdanos con la música ', 'No dejes de sumar tu canción favorita a nuestra lista de Spotify ', '', 'https://open.spotify.com/', 'fas fa-music', 1, 6);

COMMIT;
