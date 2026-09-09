<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../conexion.php';

/** Evita caracteres que XML 1.0 no admite y escapa el contenido de las celdas. */
function xlsxXml($value)
{
    $value = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string) $value);
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function xlsxColumnName($number)
{
    $name = '';
    while ($number > 0) {
        $number--;
        $name = chr(65 + ($number % 26)) . $name;
        $number = intdiv($number, 26);
    }
    return $name;
}

function xlsxSheetXml(array $headers, array $rows)
{
    $lastColumn = xlsxColumnName(count($headers));
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="15"/><cols>';
    foreach ($headers as $index => $header) {
        $width = min(55, max(12, strlen($header) + 2));
        $column = $index + 1;
        $xml .= '<col min="' . $column . '" max="' . $column . '" width="' . $width . '" customWidth="1"/>';
    }
    $xml .= '</cols><sheetData>';
    $allRows = array_merge([$headers], $rows);
    foreach ($allRows as $rowIndex => $row) {
        $excelRow = $rowIndex + 1;
        $xml .= '<row r="' . $excelRow . '">';
        foreach (array_values($row) as $columnIndex => $value) {
            $cell = xlsxColumnName($columnIndex + 1) . $excelRow;
            $style = $rowIndex === 0 ? ' s="1"' : '';
            $xml .= '<c r="' . $cell . '" t="inlineStr"' . $style . '><is><t xml:space="preserve">'
                . xlsxXml($value) . '</t></is></c>';
        }
        $xml .= '</row>';
    }
    $range = 'A1:' . $lastColumn . max(1, count($allRows));
    return $xml . '</sheetData><autoFilter ref="' . $range . '"/></worksheet>';
}

function fetchAllRows($conn, $sql)
{
    $result = $conn->query($sql);
    if (!$result) {
        throw new RuntimeException('No se pudo preparar la exportación.');
    }
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function isRealFoodRestriction($value)
{
    $normalized = function_exists('mb_strtolower')
        ? mb_strtolower(trim((string) $value), 'UTF-8')
        : strtolower(trim((string) $value));
    return $normalized !== '' && !in_array($normalized, ['no', 'ninguna'], true);
}

// Conserva los filtros históricos cuando llegan en el enlace. Los valores se
// escapan y los nombres de columnas nunca provienen del request.
$conditions = ['i.activo < 2'];
$simpleFilters = ['activo' => 'i.activo', 'ingreso' => 'i.ingreso'];
foreach ($simpleFilters as $parameter => $column) {
    if (isset($_GET[$parameter]) && $_GET[$parameter] !== '') {
        $conditions[] = $column . " = '" . $conn->real_escape_string((string) $_GET[$parameter]) . "'";
    }
}
if (isset($_GET['confirmacion']) && $_GET['confirmacion'] !== '') {
    $conditions[] = $_GET['confirmacion'] === 'NULL'
        ? 'i.confirmacion IS NULL'
        : "i.confirmacion = '" . $conn->real_escape_string((string) $_GET['confirmacion']) . "'";
}
if (isset($_GET['id_invitados']) && ctype_digit((string) $_GET['id_invitados'])) {
    $conditions[] = 'i.id = ' . (int) $_GET['id_invitados'];
}
$where = implode(' AND ', $conditions);

try {
    $guests = fetchAllRows($conn, "SELECT i.id, i.codigo, i.nombre, i.apellido, i.confirmacion,
        i.confirmacion_fecha, i.activo, i.ingreso, i.cantidad_mayores, i.cantidad_menores,
        i.confirmacion_mayores, i.confirmacion_menores, i.acompanado,
        COALESCE(ac.categoria_acompanante, i.acompanado) AS acompanado_descripcion,
        i.alimento, i.confirmacion_comentario
        FROM invitados i
        LEFT JOIN intivados_acompanante ac ON ac.id = i.acompanado
        WHERE $where ORDER BY i.apellido, i.nombre, i.id");

    $people = fetchAllRows($conn, "SELECT lm.id, lm.id_invitados, lm.nombre_invitado, lm.nombre2,
        lm.apellido2, lm.es_menor, lm.asiste, lm.confirm_date, lm.mesa, lm.alimento,
        lm.alimento_comentario, i.codigo, i.nombre AS principal_nombre,
        i.apellido AS principal_apellido, i.confirmacion, i.ingreso
        FROM invitados_listado_mesa lm
        INNER JOIN invitados i ON i.id = lm.id_invitados
        WHERE $where ORDER BY i.apellido, i.nombre, lm.id");

    $phones = fetchAllRows($conn, "SELECT t.id_invitados, i.nombre, i.apellido, t.tel_enviar,
        i.confirmacion, i.ingreso
        FROM invitados_tel t INNER JOIN invitados i ON i.id = t.id_invitados
        WHERE $where ORDER BY i.apellido, i.nombre, t.id");
} catch (Throwable $error) {
    http_response_code(500);
    exit('No se pudo generar la exportación.');
}

$peopleByGuest = [];
foreach ($people as $person) {
    $peopleByGuest[$person['id_invitados']][] = $person;
}
$phonesByGuest = [];
foreach ($phones as $phone) {
    $phonesByGuest[$phone['id_invitados']][] = trim((string) $phone['tel_enviar']);
}

$guestHeaders = ['ID invitado', 'Código', 'Nombre principal', 'Apellido principal', 'Confirmación',
    'Fecha confirmación', 'Activo', 'Ingreso', 'Cantidad mayores', 'Cantidad menores',
    'Confirmación mayores', 'Confirmación menores', 'Acompañado', 'Teléfonos',
    'Restricciones alimentarias resumen', 'Comentarios alimentarios resumen', 'Invitados del grupo resumen'];
$guestRows = [];
foreach ($guests as $guest) {
    $members = $peopleByGuest[$guest['id']] ?? [];
    $restrictions = [];
    $foodComments = [];
    $memberNames = [];
    foreach ($members as $member) {
        $displayName = trim((string) $member['nombre_invitado']);
        if ($displayName === '') {
            $displayName = trim($member['nombre2'] . ' ' . $member['apellido2']);
        }
        $memberNames[] = $displayName;
        if (isRealFoodRestriction($member['alimento'])) {
            $detail = $displayName . ': ' . trim((string) $member['alimento']);
            $comment = trim((string) $member['alimento_comentario']);
            if ($comment !== '') {
                $detail .= ' — ' . $comment;
                $foodComments[] = $displayName . ': ' . $comment;
            }
            $restrictions[] = $detail;
        }
    }
    // Sólo se consulta el campo legacy cuando el grupo aún no tiene integrantes.
    if (!$members && isRealFoodRestriction($guest['alimento'])) {
        $legacyName = trim($guest['nombre'] . ' ' . $guest['apellido']);
        $legacyComment = trim((string) $guest['confirmacion_comentario']);
        $detail = $legacyName . ': ' . trim((string) $guest['alimento']);
        if ($legacyComment !== '') {
            $detail .= ' — ' . $legacyComment;
            $foodComments[] = $legacyName . ': ' . $legacyComment;
        }
        $restrictions[] = $detail;
    }
    $guestRows[] = [$guest['id'], $guest['codigo'], $guest['nombre'], $guest['apellido'],
        $guest['confirmacion'], $guest['confirmacion_fecha'], $guest['activo'], $guest['ingreso'],
        $guest['cantidad_mayores'], $guest['cantidad_menores'], $guest['confirmacion_mayores'],
        $guest['confirmacion_menores'], $guest['acompanado_descripcion'],
        implode(', ', $phonesByGuest[$guest['id']] ?? []), implode('; ', $restrictions),
        implode('; ', $foodComments), implode('; ', $memberNames)];
}

$personHeaders = ['ID invitado principal', 'ID persona/listado', 'Nombre persona', 'Nombre', 'Apellido',
    'Tipo persona', 'Es menor', 'Asiste', 'Fecha confirmación persona', 'Mesa',
    'Restricción alimentaria', 'Comentario alimentario', 'Código invitado principal',
    'Nombre principal', 'Apellido principal', 'Confirmación del grupo', 'Ingreso'];
$personRows = [];
$seenGuests = [];
foreach ($people as $person) {
    // El titular es la primera fila del grupo: tanto el alta admin como RSVP lo insertan primero.
    $isHolder = !isset($seenGuests[$person['id_invitados']]);
    $seenGuests[$person['id_invitados']] = true;
    $type = $isHolder ? 'Titular' : ((int) $person['es_menor'] === 1 ? 'Menor' : 'Acompañante adulto');
    $personRows[] = [$person['id_invitados'], $person['id'], $person['nombre_invitado'],
        $person['nombre2'], $person['apellido2'], $type, $person['es_menor'], $person['asiste'],
        $person['confirm_date'], $person['mesa'], $person['alimento'], $person['alimento_comentario'],
        $person['codigo'], $person['principal_nombre'], $person['principal_apellido'],
        $person['confirmacion'], $person['ingreso']];
}

$phoneHeaders = ['ID invitado', 'Nombre principal', 'Apellido principal', 'Teléfono', 'Confirmación', 'Ingreso'];
$phoneRows = [];
foreach ($phones as $phone) {
    $phoneRows[] = [$phone['id_invitados'], $phone['nombre'], $phone['apellido'],
        $phone['tel_enviar'], $phone['confirmacion'], $phone['ingreso']];
}

$format = strtolower((string) ($_GET['format'] ?? 'xlsx'));
$timestamp = date('Ymd_Hi');

// ZipArchive es necesario para empaquetar un XLSX real. En hosts sin la
// extensión se entrega automáticamente el CSV actualizado para no romper el link.
if ($format === 'csv' || !class_exists('ZipArchive')) {
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="invitados_' . $timestamp . '.csv"');
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, $guestHeaders);
    foreach ($guestRows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    $conn->close();
    exit();
}

$temporaryFile = tempnam(sys_get_temp_dir(), 'invitados_xlsx_');
$zip = new ZipArchive();
if ($temporaryFile === false || $zip->open($temporaryFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit('No se pudo crear el archivo XLSX.');
}
$zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
$zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Invitados" sheetId="1" r:id="rId1"/><sheet name="Personas" sheetId="2" r:id="rId2"/><sheet name="Teléfonos" sheetId="3" r:id="rId3"/></sheets></workbook>');
$zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/><Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
$zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF4472C4"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs></styleSheet>');
$zip->addFromString('xl/worksheets/sheet1.xml', xlsxSheetXml($guestHeaders, $guestRows));
$zip->addFromString('xl/worksheets/sheet2.xml', xlsxSheetXml($personHeaders, $personRows));
$zip->addFromString('xl/worksheets/sheet3.xml', xlsxSheetXml($phoneHeaders, $phoneRows));
$zip->close();

if (ob_get_length()) {
    ob_clean();
}
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="invitados_' . $timestamp . '.xlsx"');
header('Content-Length: ' . filesize($temporaryFile));
header('Cache-Control: max-age=0, no-store');
readfile($temporaryFile);
unlink($temporaryFile);
$conn->close();
exit();
