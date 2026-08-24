<?php
/**
 * UNI-008 RSVP form/pre_ probe.
 *
 * Herramienta CLI read-only para inspeccionar tablas pre_invitados sin
 * modificar datos ni exponer teléfonos completos, credenciales, tokens o secretos.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

error_reporting(E_ERROR);

function dqs_rsvp_form_pre_probe_help()
{
    echo "Uso:\n";
    echo "  php tools/dqs_rsvp_form_pre_probe.php --help\n";
    echo "  php tools/dqs_rsvp_form_pre_probe.php --source=pre_invitados\n";
    echo "  php tools/dqs_rsvp_form_pre_probe.php --codigo=CODIGO --source=pre_invitados\n\n";
    echo "Descripción:\n";
    echo "  Inspecciona en modo solo lectura la fuente RSVP formulario/pre_.\n";
    echo "  No crea, altera, inserta ni actualiza datos; no muestra teléfonos completos.\n";
}

function dqs_probe_columns(mysqli $conn, $tableName)
{
    $sql = 'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION';
    $statement = @$conn->prepare($sql);
    if (!$statement) {
        return [];
    }

    $statement->bind_param('s', $tableName);
    if (!$statement->execute()) {
        $statement->close();
        return [];
    }

    $result = $statement->get_result();
    $columns = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['COLUMN_NAME'];
        }
    }
    $statement->close();

    return $columns;
}

$options = getopt('', ['help', 'source:', 'codigo:']);
if (isset($options['help'])) {
    dqs_rsvp_form_pre_probe_help();
    exit(0);
}

$source = isset($options['source']) ? trim((string)$options['source']) : 'pre_invitados';
$codigo = isset($options['codigo']) ? trim((string)$options['codigo']) : '';

if ($source !== 'pre_invitados') {
    fwrite(STDERR, "Error: UNI-008 solo inspecciona --source=pre_invitados.\n");
    exit(1);
}

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

require_once __DIR__ . '/../includes/guest_source.php';

function dqs_probe_read_connection_settings($path)
{
    $settings = [
        'servername' => '',
        'username' => '',
        'password' => '',
        'dbname' => '',
    ];

    $contents = @file_get_contents($path);
    if ($contents === false) {
        return $settings;
    }

    foreach (array_keys($settings) as $name) {
        if (preg_match('/\$' . preg_quote($name, '/') . '\s*=\s*([\'\"])(.*?)\1\s*;/', $contents, $matches)) {
            $settings[$name] = $matches[2];
        }
    }

    return $settings;
}

$settings = dqs_probe_read_connection_settings(__DIR__ . '/../conexion.php');
$conn = @new mysqli($settings['servername'], $settings['username'], $settings['password'], $settings['dbname']);
if ($conn->connect_error) {
    fwrite(STDERR, "Error: no se pudo abrir conexión a la base de datos.\n");
    exit(1);
}

$map = dqs_guest_source_get_table_map($source);
$tables = [$map['main_table'], $map['members_table'], $map['phones_table']];
$missing = [];

echo "RSVP form/pre_ probe (read-only)\n";
echo "Fuente: " . $source . "\n";
echo "Tablas esperadas:\n";
foreach ($tables as $table) {
    $exists = dqs_guest_source_table_exists($conn, $table);
    echo "  - " . $table . ": " . ($exists ? 'existe' : 'no existe') . "\n";
    if (!$exists) {
        $missing[] = $table;
    }

    $columns = $exists ? dqs_probe_columns($conn, $table) : [];
    echo "    Campos detectados: " . (count($columns) > 0 ? implode(', ', $columns) : '(no disponibles)') . "\n";
}

if (count($missing) > 0) {
    echo "Advertencias:\n";
    echo "  - Faltan tablas pre_: " . implode(', ', $missing) . ". Esto puede ser normal en bases activas con RSVP por código.\n";
    echo "  - No se buscó código porque la fuente pre_ no está completa.\n";
    $conn->close();
    exit(0);
}

if ($codigo === '') {
    echo "Código: (no indicado)\n";
    echo "Advertencias:\n";
    echo "  - Use --codigo=CODIGO para verificar existencia puntual sin modificar datos.\n";
    $conn->close();
    exit(0);
}

$guest = dqs_guest_source_find_by_codigo($conn, $codigo, $source);
echo "Código: " . $codigo . "\n";
echo "Existe pre_invitado: " . ($guest ? 'sí' : 'no') . "\n";

if ($guest) {
    $guestId = isset($guest['id']) ? (int)$guest['id'] : 0;
    $members = dqs_guest_source_get_members($conn, $guestId, $source);
    $phones = dqs_guest_source_get_phones($conn, $guestId, $source);
    echo "ID pre_invitado: " . $guestId . "\n";
    echo "Cantidad de integrantes: " . count($members) . "\n";
    echo "Cantidad de teléfonos: " . count($phones) . "\n";
    echo "Confirmación actual: " . (isset($guest['confirmacion']) && $guest['confirmacion'] !== '' ? $guest['confirmacion'] : '(sin confirmar)') . "\n";
}

echo "Advertencias:\n";
echo "  - Teléfonos completos omitidos intencionalmente.\n";
echo "  - Herramienta read-only: no ejecuta WhatsApp, Node ni confirmaciones.\n";

$conn->close();
