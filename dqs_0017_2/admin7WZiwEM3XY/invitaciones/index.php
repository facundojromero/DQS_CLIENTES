<?php
// Generador del flujo PHP histórico: retirado antes de abrir la conexión.
require_once __DIR__ . '/../../includes/admin_feature_guard.php';

// Incluir el archivo de conexión
include_once '../../conexion.php'; 
// Mostrar errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Obtener el id_invitados desde el parámetro GET
$id_invitados = isset($_GET['id_invitados']) ? intval($_GET['id_invitados']) : 0;

// Resolver la familia de tablas exclusivamente desde la fuente efectiva configurada.
$effective_plan_config = dqs_get_effective_plan_config($conn);
$contact_source = $effective_plan_config['fuente_envios_whatsapp'] ?? 'ninguno';
$raw_source_result = @$conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'fuente_envios_whatsapp' LIMIT 1");
if ($raw_source_result && ($raw_source_row = $raw_source_result->fetch_assoc())) {
    $raw_source = (string)($raw_source_row['setting_value'] ?? '');
    if (!in_array($raw_source, ['invitados', 'pre_invitados', 'ninguno'], true)) {
        $contact_source = 'ninguno';
    }
}

$pre_member_name_parts = [];
$pre_column_names = [];
$pre_columns_result = @$conn->query('SHOW COLUMNS FROM pre_invitados_listado_mesa');
if ($pre_columns_result) {
    while ($pre_column = $pre_columns_result->fetch_assoc()) {
        $pre_column_names[] = $pre_column['Field'];
    }
    foreach (['nombre_invitado', 'apodo'] as $friendly_column) {
        if (in_array($friendly_column, $pre_column_names, true)) {
            $pre_member_name_parts[] = "NULLIF(TRIM(m.$friendly_column), '')";
        }
    }
}
$pre_member_name_parts[] = "NULLIF(TRIM(CONCAT(m.nombre, ' ', m.apellido)), '')";
$pre_member_name = 'COALESCE(' . implode(', ', $pre_member_name_parts) . ')';

$source_queries = [
    'invitados' => [
        'contacts' => 'invitados',
        'members' => 'invitados_listado_mesa',
        'member_fk' => 'id_invitados',
        'member_name' => 'nombre_invitado',
        'member_order' => 'id',
        'phones' => 'invitados_tel',
        'phone_fk' => 'id_invitados',
        'phone_value' => 'tel_enviar',
        'active_condition' => 'a.activo < 2',
        'person_count' => '(a.cantidad_mayores + a.cantidad_menores)',
    ],
    'pre_invitados' => [
        'contacts' => 'pre_invitados',
        'members' => 'pre_invitados_listado_mesa',
        'member_fk' => 'id_pre_invitado',
        'member_name' => $pre_member_name,
        'member_order' => 'orden',
        'phones' => 'pre_invitados_tel',
        'phone_fk' => 'id_pre_invitado',
        'phone_value' => 'telefono',
        'active_condition' => 'a.activo = 1',
        'person_count' => "COALESCE(NULLIF(a.total_personas, 0), NULLIF(COALESCE(a.cantidad_mayores, 0) + COALESCE(a.cantidad_menores, 0), 0), 1 + COALESCE(a.cantidad_acompanantes, 0))",
    ],
];

if (!isset($source_queries[$contact_source])) {
    http_response_code(403);
    echo '<p>No hay una fuente válida de invitados habilitada para generar imágenes.</p>';
    mysqli_close($conn);
    exit();
}

$source_query = $source_queries[$contact_source];
$contacts_table = $source_query['contacts'];
$members_table = $source_query['members'];
$member_fk = $source_query['member_fk'];
$member_name = $source_query['member_name'];
$member_order = $source_query['member_order'];
$phones_table = $source_query['phones'];
$phone_fk = $source_query['phone_fk'];
$phone_value = $source_query['phone_value'];
$active_condition = $source_query['active_condition'];
$person_count = $source_query['person_count'];

// La consulta conserva el contrato consumido por el generador y cambia solo su origen.
$query = "
SELECT
    CASE WHEN a.id > 999 THEN a.id
        WHEN a.id > 99 THEN CONCAT('0', a.id)
        WHEN a.id > 9 THEN CONCAT('00', a.id)
        ELSE CONCAT('000', a.id)
    END AS id_invitados,
    members.invitados,
    a.nombre,
    a.apellido,
    members.titulo_invitados,
    CONCAT('Cantidad Mayores: ', a.cantidad_mayores) AS cantidad_mayores,
    CASE WHEN a.cantidad_menores > 0 THEN CONCAT('Cantidad Menores: ', a.cantidad_menores) ELSE '' END AS cantidad_menores,
    $person_count AS cantidad_personas,
    a.ingreso
FROM $contacts_table a
INNER JOIN (
    SELECT
        m.$member_fk,
        SUBSTRING_INDEX(
            GROUP_CONCAT($member_name ORDER BY m.$member_order ASC SEPARATOR ' y '),
            ' y ',
            2
        ) AS titulo_invitados,
        CASE WHEN COUNT(*) > 1 THEN
            CONCAT(
                SUBSTRING_INDEX(
                    GROUP_CONCAT($member_name ORDER BY m.$member_order ASC SEPARATOR ', '),
                    ', ',
                    COUNT(*) - 1
                ),
                ' y ',
                SUBSTRING_INDEX(
                    GROUP_CONCAT($member_name ORDER BY m.$member_order ASC SEPARATOR ', '),
                    ', ',
                    -1
                )
            )
        ELSE GROUP_CONCAT($member_name ORDER BY m.$member_order ASC SEPARATOR ', ')
        END AS invitados
    FROM $members_table m
    GROUP BY m.$member_fk
) members ON a.id = members.$member_fk
LEFT JOIN (
    SELECT p.$phone_fk, GROUP_CONCAT(p.$phone_value SEPARATOR ', ') AS tel_enviar
    FROM $phones_table p
    GROUP BY p.$phone_fk
) phones ON a.id = phones.$phone_fk
WHERE $active_condition
AND a.id = $id_invitados
GROUP BY a.id, members.invitados, a.nombre, a.apellido, members.titulo_invitados,
    a.cantidad_mayores, a.cantidad_menores, a.ingreso
;";
$result = mysqli_query($conn, $query);
// Ruta de la plantilla de imagen
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
    $plantilla = null;
    $ingreso = trim((string)($row['ingreso'] ?? ''));
    $cantidadPersonas = (int)($row['cantidad_personas'] ?? 0);

    // Definir la plantilla según las condiciones
    if ($ingreso === 'Inicio' && $cantidadPersonas === 1) {
        $plantilla = __DIR__ . '/plantilla_inicio_singular.jpg';
    } elseif ($ingreso === 'Inicio' && $cantidadPersonas > 1) {
        $plantilla = __DIR__ . '/plantilla_inicio_plural.jpg';
    } elseif ($ingreso === 'Tarde' && $cantidadPersonas === 1) {
        $plantilla = __DIR__ . '/plantilla_tarde_singular.jpg';
    } elseif ($ingreso === 'Tarde' && $cantidadPersonas > 1) {
        $plantilla = __DIR__ . '/plantilla_tarde_plural.jpg';
    }

    if ($plantilla === null) {
        $diagnostico = 'No se pudo determinar la plantilla para ID '
            . htmlspecialchars((string)$row['id_invitados'], ENT_QUOTES, 'UTF-8')
            . ': fuente="' . htmlspecialchars($contact_source, ENT_QUOTES, 'UTF-8')
            . '" ingreso="' . htmlspecialchars($ingreso, ENT_QUOTES, 'UTF-8')
            . '" cantidad_personas="' . $cantidadPersonas . '".';
        die($diagnostico);
    }

    // Verificar si la plantilla existe
    if (!file_exists($plantilla)) {
        die('Plantilla física no encontrada: ' . htmlspecialchars($plantilla, ENT_QUOTES, 'UTF-8'));
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
    $ruta_archivo = __DIR__ . '/' . $nombre_archivo;
    if (!imagejpeg($imagen, $ruta_archivo)) {
        die('Error al guardar la imagen generada.');
    }
    imagedestroy($imagen);
    echo 'Imagen generada: ' . $nombre_archivo . '<br>';
}
mysqli_close($conn);
echo 'Imágenes generadas exitosamente.';
?>
