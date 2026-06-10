<?php
// Incluir el archivo de conexión
include_once '../../conexion.php'; 
// Mostrar errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Obtener el id_invitados desde el parámetro GET
$id_invitados = isset($_GET['id_invitados']) ? intval($_GET['id_invitados']) : 0;
// Consulta para obtener los datos de los invitados
$query = "
SELECT 
CASE WHEN a.id_invitados>999 THEN a.id_invitados
 WHEN a.id_invitados>99 THEN CONCAT('0',a.id_invitados)
 WHEN a.id_invitados>9 THEN CONCAT('00',a.id_invitados)
 ELSE CONCAT('000',a.id_invitados) END id_invitados
, e.invitados
-- , f.tel_enviar
, CONCAT('*',e.invitados,'*'
 , CASE WHEN e.cantidad_mayores=1 
 THEN '%0A%0ACon gran alegria queremos invitarte a nuestro casamiento ðŸ’•ðŸ’Œ' 
 WHEN e.cantidad_mayores>1 
 THEN '%0A%0ACon gran alegria queremos invitarlos a nuestro casamiento ðŸ’•ðŸ’Œ' END
 , '%0A%0APor favor confirmar asistencia ingresando al link'
 , '%0Awww.feliyfacu.com' 
 , '%0A%0ACodigo de Invitacion *',b.codigo,'*'
 , CASE WHEN e.cantidad_mayores=1 THEN '%0A%0AÂ¡Te esperamos!' 
 WHEN e.cantidad_mayores>1 THEN '%0A%0A¡Los esperamos!' END 
 , '%0A%0AFeli y Facu'
 ) mensaje
-- , '' estado 
, b.nombre
, b.apellido
, e.titulo_invitados
, CONCAT('Cantidad Mayores: ',e.cantidad_mayores) cantidad_mayores
, CASE WHEN e.cantidad_menores>0 THEN CONCAT('Cantidad Menores: ',e.cantidad_menores) ELSE '' END cantidad_menores
, (e.cantidad_mayores + e.cantidad_menores) cantidad_personas
, b.ingreso
FROM
(
 SELECT 
 aa.id_invitados
 , aa.nombre_invitado
 , bb.invitados
 , bb.titulo_invitados
 , ROW_NUMBER() OVER (ORDER BY aa.id_invitados ASC) AS numero_fila
 FROM invitados_listado_mesa aa
 INNER JOIN (
 SELECT id_invitados
 , SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY id ASC SEPARATOR ' y '), ' y ', 2) AS titulo_invitados
 , GROUP_CONCAT(nombre_invitado ORDER BY id ASC SEPARATOR ', ') AS invitados
 FROM invitados_listado_mesa
 GROUP BY id_invitados
 ) bb
 ON aa.id_invitados = bb.id_invitados
 WHERE 1=1
) a
INNER JOIN invitados b
ON a.id_invitados = b.id
LEFT JOIN invitados_prioridad c
ON b.id_prioridad = c.id
LEFT JOIN intivados_acompanante d
ON b.acompanado = d.id
LEFT JOIN 
(
 SELECT 
 CASE 
 WHEN cantidad_mayores > 1 THEN e.titulo_invitados 
 ELSE CONCAT(titulo_invitados, ' ', apellido) 
 END AS nombre, 
 nombre AS nombre_revision, 
 apellido AS apellido_revision,
 CASE 
 WHEN LENGTH(apellido) > 3 THEN CONCAT(SUBSTRING(apellido, 1, 3), '.') 
 ELSE CONCAT(SUBSTRING(apellido, 1, 2), '.') 
 END AS apellido,
 -- CONCAT('xxxxxx', SUBSTRING(tel, 7, 5)) AS cel,
 -- tel,
 a.id id_invitados,
 TO_BASE64(a.id) AS base64_id_invitados,
 CASE 
 WHEN LENGTH(HEX(a.id * 10)) = 1 THEN CONCAT('AB', HEX(a.id * 10)) 
 WHEN LENGTH(HEX(a.id * 10)) = 2 THEN CONCAT('A', HEX(a.id * 10)) 
 ELSE HEX(a.id * 10) 
 END AS codigo,
 cantidad_mayores,
 cantidad_menores,
 ingreso, 
 categoria_acompanante AS acompanado,
 e.invitados,
 e.titulo_invitados
 -- , REPLACE(tel_enviar_concatenado, ',', ' ó ') AS tel_enviar_concat 
 FROM invitados a
 LEFT JOIN intivados_acompanante b ON a.acompanado = b.id
 LEFT JOIN invitados_prioridad c ON a.id_prioridad = c.id
 LEFT JOIN (
 SELECT 
 aa.id_invitados,
 bb.invitados,
 bb.titulo_invitados,
 ROW_NUMBER() OVER (PARTITION BY aa.id_invitados ORDER BY aa.id_invitados ASC) AS numero_fila
 -- , GROUP_CONCAT(CONCAT('xxxxxx', SUBSTRING(tel_enviar, 7, 5))) AS tel_enviar_concatenado
 FROM invitados_listado_mesa aa
 INNER JOIN (
 SELECT 
 a.id_invitados,
 SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ' y '), ' y ', 2) AS titulo_invitados,
 CASE 
 WHEN cantidad_mayores < 2 THEN nombre_invitado 
 ELSE CONCAT(
 IF(COUNT(*) > 1,
 SUBSTRING_INDEX(
 GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '),
 ', ',
 COUNT(*) - 1
 ),
 GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', ')
 ), 
 ' y ', 
 SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '), ', ', -1)
 ) 
 END AS invitados
 -- , GROUP_CONCAT(CONCAT('xxxxxx', SUBSTRING(tel_enviar, 7, 5))) AS tel_enviar_concatenado
 FROM invitados_listado_mesa a
 INNER JOIN invitados b
 ON a.id_invitados = b.id
 WHERE 1=1
 GROUP BY a.id_invitados
 ) bb 
 ON aa.id_invitados = bb.id_invitados
 GROUP BY id
 ) e 
 ON a.id = e.id_invitados
 WHERE 1 = 1
 GROUP BY a.id
) e
ON a.id_invitados = e.id_invitados
LEFT JOIN (
 SELECT 
 id_invitados, 
 GROUP_CONCAT(tel_enviar SEPARATOR ', ') AS tel_enviar
FROM 
 invitados_tel a
GROUP BY 
 id_invitados
) f
ON a.id_invitados = f.id_invitados
WHERE 1=1
AND activo<2
AND a.id_invitados = $id_invitados
GROUP BY 1,2,3,4,5,6
;";
$result = mysqli_query($conn, $query);
// Ruta de la plantilla de imagen
$plantilla = '';
// Ruta de la fuente TTF
$fuente = __DIR__ . '/Alegreya-Regular.ttf';
// Verificar si la fuente existe
if (!file_exists($fuente)) {
    die('La fuente no fue encontrada en: ' . $fuente);
} else {
    echo 'Fuente encontrada en: ' . $fuente . '<br>';
}
// Iterar sobre los resultados de la consulta
while ($row = mysqli_fetch_assoc($result)) {
    // Definir la plantilla según las condiciones
    if ($row['ingreso'] == 'Inicio' && $row['cantidad_personas'] == 1) {
        $plantilla = 'plantilla_inicio_singular.jpg';
    } elseif ($row['ingreso'] == 'Inicio' && $row['cantidad_personas'] > 1) {
        $plantilla = 'plantilla_inicio_plural.jpg';
    } elseif ($row['ingreso'] == 'Tarde' && $row['cantidad_personas'] == 1) {
        $plantilla = 'plantilla_tarde_singular.jpg';
    } elseif ($row['ingreso'] == 'Tarde' && $row['cantidad_personas'] > 1) {
        $plantilla = 'plantilla_tarde_plural.jpg';
    }

    // Verificar si la plantilla existe
    if (!file_exists($plantilla)) {
        die('La plantilla de imagen no existe.');
    }

    $imagen = imagecreatefromjpeg($plantilla);
    if (!$imagen) {
        die('Error al crear la imagen desde la plantilla.');
    }

    // Definir el color del texto
    $color_texto = imagecolorallocate($imagen, 1, 0, 0);
    if (!$color_texto) {
        die('Error al asignar el color del texto.');
    }

    // Coordenadas y tamaño del texto
    $y = 350; 
    $tamanio_fuente = 36;
    // Nombre completo del invitado en mayúsculas y con espaciado adicional
    $nombre_completo = strtoupper($row['titulo_invitados'] );
    $nombre_completo_espaciado = implode(' ', str_split($nombre_completo));
    // Calcular el tamaño del cuadro de texto
    $bbox = imagettfbbox($tamanio_fuente, 0, $fuente, $nombre_completo_espaciado);
    $ancho_texto = $bbox[2] - $bbox[0];
    // Calcular la coordenada x para centrar el texto
    $ancho_imagen = 1240;
    $x = ($ancho_imagen - $ancho_texto) / 2;
    // Agregar el texto a la imagen con la fuente TTF
    $resultado_texto = imagettftext($imagen, $tamanio_fuente, 0, $x, $y, $color_texto, $fuente, $nombre_completo_espaciado);
    if (!$resultado_texto) {
        die('Error al escribir el texto en la imagen.');
    }

    // Guardar la imagen generada
    $nombre_archivo = $row['id_invitados'] . '.jpg';
    if (!imagejpeg($imagen, $nombre_archivo)) {
        die('Error al guardar la imagen generada.');
    }
    imagedestroy($imagen);
    echo 'Imagen generada: ' . $nombre_archivo . '<br>';
}
mysqli_close($conn);
echo 'Imágenes generadas exitosamente.';
?>