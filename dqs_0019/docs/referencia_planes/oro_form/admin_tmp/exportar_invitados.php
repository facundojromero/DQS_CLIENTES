<?php
session_start();
// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Incluir el archivo de conexión a la base de datos
// Asegúrate de que la ruta sea correcta. Si 'exportar_invitados.php' está en la misma carpeta que 'conexion.php', puedes usar 'conexion.php'
// Si 'exportar_invitados.php' está en una subcarpeta y 'conexion.php' está un nivel arriba, '../conexion.php' es correcto.
require_once '../conexion.php'; 

// Consulta SQL
$sql = "SELECT 
 a.id id_clientes,
 h.apellido2 apellido,
 h.nombre2 nombre,
 h.nombre_invitado,
 e.invitados,
 a.confirmacion_comentario2 mensaje,
 b.categoria_acompanante acompanado,
 a.cantidad_mayores,
 a.cantidad_menores,
 a.ingreso,
 -- f.id id_prioridad,
 f.categoria_prioridad,
 g.tel_enviar tel,
  a.confirmacion,
 a.confirmacion_fecha,
 a.alimento,
 a.confirmacion_comentario alimento_comentario,
 -- a.fecha_registro,
 a.confirmacion_mayores,
 a.confirmacion_menores,
 a.activo
FROM invitados a
LEFT JOIN intivados_acompanante b ON a.acompanado = b.id
LEFT JOIN (
 SELECT 
 a.id_invitados,
 CASE WHEN cantidad_mayores+cantidad_menores<2 THEN nombre_invitado ELSE 
 CONCAT(
 IF(COUNT(*) > 1,
 SUBSTRING_INDEX(
 GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '),
 ', ',
 COUNT(*) - 1
 ),
 GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', ')
 ) ,
 ' y ',
 SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '), ', ', -1)
 ) END AS invitados
 FROM invitados_listado_mesa a
 INNER JOIN invitados b
 ON a.id_invitados=b.id
 GROUP BY a.id_invitados
) e ON a.id = e.id_invitados
LEFT JOIN invitados_prioridad f ON a.id_prioridad = f.id
LEFT JOIN 
 (
 SELECT 
    id_invitados, 
    GROUP_CONCAT(tel_enviar SEPARATOR ', ') AS tel_enviar
FROM 
    invitados_tel a
GROUP BY 
    id_invitados
 
 ) 

 g ON a.id = g.id_invitados
 LEFT JOIN invitados_listado_mesa h
ON a.id = h.id_invitados
WHERE 1=1
AND a.activo < 2
ORDER BY id_clientes
";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Establecer las cabeceras para la descarga de un archivo Excel (CSV)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=invitados.csv');

    // Crear un puntero de archivo conectado al flujo de salida
    $output = fopen('php://output', 'w');

    // Imprimir los encabezados de las columnas (nombres de los campos)
    $firstRow = $result->fetch_assoc();
    if ($firstRow) {
        fputcsv($output, array_keys($firstRow));
        // Reiniciar el puntero de resultados al principio para que se imprima la primera fila de datos
        $result->data_seek(0);
    }

    // Imprimir todas las filas de datos
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    // Cerrar el puntero del archivo
    fclose($output);
} else {
    echo "No se encontraron resultados para exportar.";
}

// Cerrar la conexión a la base de datos
$conn->close();

?>