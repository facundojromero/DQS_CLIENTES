<?php
/**
 * UNI-007 RSVP código probe.
 *
 * Herramienta CLI read-only para inspeccionar el estado de un código RSVP sin
 * modificar datos ni exponer teléfonos, credenciales, tokens o secretos.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

error_reporting(E_ERROR);

function dqs_rsvp_probe_help()
{
    echo "Uso:\n";
    echo "  php tools/dqs_rsvp_codigo_probe.php --help\n";
    echo "  php tools/dqs_rsvp_codigo_probe.php --codigo=CODIGO\n\n";
    echo "Descripción:\n";
    echo "  Inspecciona en modo solo lectura el RSVP actual por código sobre invitados/invitados_listado_mesa.\n";
    echo "  No muestra teléfonos, credenciales, tokens ni secretos.\n";
}

$options = getopt('', ['help', 'codigo:']);
if (isset($options['help'])) {
    dqs_rsvp_probe_help();
    exit(0);
}

$codigo = isset($options['codigo']) ? trim((string)$options['codigo']) : '';
if ($codigo === '') {
    fwrite(STDERR, "Error: falta --codigo=CODIGO. Use --help para ver el uso.\n");
    exit(1);
}

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/guest_source.php';

if (!$conn) {
    fwrite(STDERR, "Error: no se pudo abrir conexión a la base de datos.\n");
    exit(1);
}

$guest = dqs_guest_source_find_by_codigo($conn, $codigo, 'invitados');

echo "RSVP código probe (read-only)\n";
echo "Código: " . $codigo . "\n";
echo "Existe invitado: " . ($guest ? 'sí' : 'no') . "\n";

if (!$guest) {
    $conn->close();
    exit(0);
}

$guestId = isset($guest['id']) ? (int)$guest['id'] : 0;
$members = dqs_guest_source_get_members($conn, $guestId, 'invitados');

$totalMembers = count($members);
$currentAttendees = 0;
$calcAdults = 0;
$calcMinors = 0;
$hasDietaryRestrictions = false;

foreach ($members as $member) {
    $attends = isset($member['asiste']) && (int)$member['asiste'] === 1;
    $isMinor = isset($member['es_menor']) && (int)$member['es_menor'] === 1;
    $food = isset($member['alimento']) ? trim((string)$member['alimento']) : 'No';
    $foodComment = isset($member['alimento_comentario']) ? trim((string)$member['alimento_comentario']) : '';

    if ($attends) {
        $currentAttendees++;
        if ($isMinor) {
            $calcMinors++;
        } else {
            $calcAdults++;
        }
    }

    if ($attends && ($food !== '' && $food !== 'No' || $foodComment !== '')) {
        $hasDietaryRestrictions = true;
    }
}

$savedAdults = isset($guest['confirmacion_mayores']) ? (int)$guest['confirmacion_mayores'] : 0;
$savedMinors = isset($guest['confirmacion_menores']) ? (int)$guest['confirmacion_menores'] : 0;
$warnings = [];

if ($savedAdults !== $calcAdults) {
    $warnings[] = 'Diferencia en mayores: resumen=' . $savedAdults . ', integrantes=' . $calcAdults;
}
if ($savedMinors !== $calcMinors) {
    $warnings[] = 'Diferencia en menores: resumen=' . $savedMinors . ', integrantes=' . $calcMinors;
}
if (($savedAdults + $savedMinors) !== $currentAttendees) {
    $warnings[] = 'Diferencia en asistentes totales: resumen=' . ($savedAdults + $savedMinors) . ', integrantes=' . $currentAttendees;
}

$summaryHasRestriction = isset($guest['alimento']) && trim((string)$guest['alimento']) !== '' && trim((string)$guest['alimento']) !== 'No';
if ($summaryHasRestriction !== $hasDietaryRestrictions) {
    $warnings[] = 'Diferencia en restricciones alimentarias entre resumen e integrantes';
}

echo "ID invitado: " . $guestId . "\n";
echo "Confirmación actual: " . (isset($guest['confirmacion']) && $guest['confirmacion'] !== '' ? $guest['confirmacion'] : '(sin confirmar)') . "\n";
echo "Cantidad de integrantes: " . $totalMembers . "\n";
echo "Cantidad de asistentes actuales: " . $currentAttendees . "\n";
echo "Mayores/menores calculados desde integrantes: " . $calcAdults . "/" . $calcMinors . "\n";
echo "Mayores/menores guardados en resumen: " . $savedAdults . "/" . $savedMinors . "\n";
echo "Hay restricciones alimentarias: " . ($hasDietaryRestrictions ? 'sí' : 'no') . "\n";

echo "Advertencias:\n";
if (count($warnings) === 0) {
    echo "  - Ninguna\n";
} else {
    foreach ($warnings as $warning) {
        echo "  - " . $warning . "\n";
    }
}

$conn->close();
