<?php
session_start();
header('Content-Type: application/json');
include '../conexion.php';
include_once 'regalo_libre_helper.php';

asegurarEstructuraRegaloLibre($conn);
$regaloLibreId = obtenerOCrearProductoRegaloLibre($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('status' => 'error', 'message' => 'Método no permitido'));
    exit;
}

$producto_id = isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : 0;
$currency = isset($_POST['currency']) ? (int)$_POST['currency'] : 1;
$session_id = session_id();

if ($currency !== 1 && $currency !== 2) {
    $currency = 1;
}

if ($producto_id <= 0) {
    echo json_encode(array('status' => 'error', 'message' => 'Producto inválido'));
    exit;
}

$montoLibre = null;
if (isset($_POST['monto_libre'])) {
    $montoLibre = sanitizarMontoRegaloLibre($_POST['monto_libre']);
    if ($montoLibre === null) {
        echo json_encode(array('status' => 'error', 'message' => 'Monto inválido para Gift Card'));
        exit;
    }
}

$query_dolar = 'SELECT cotizacion_dolar FROM cliente WHERE user_id=1';
$result_dolar = $conn->query($query_dolar);
$cotizacion_dolar = 1;
if ($result_dolar && $result_dolar->num_rows > 0) {
    $row_dolar = $result_dolar->fetch_assoc();
    $cotizacion_dolar = (float)$row_dolar['cotizacion_dolar'];
}

if ($producto_id === $regaloLibreId && $montoLibre === null) {
    echo json_encode(array('status' => 'error', 'message' => 'Debe ingresar un monto para Gift Card'));
    exit;
}

if ($producto_id === $regaloLibreId) {
    $montoLibreEnPesos = $montoLibre;
    if ($currency === 2 && $cotizacion_dolar > 0) {
        $montoLibreEnPesos = $montoLibre * $cotizacion_dolar;
    }

    $sql = 'SELECT id FROM carrito WHERE session_id = ? AND producto_id = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $session_id, $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $sqlUpdate = 'UPDATE carrito SET cantidad = 1, monto_libre = ? WHERE session_id = ? AND producto_id = ?';
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param('dsi', $montoLibreEnPesos, $session_id, $producto_id);
        $ok = $stmtUpdate->execute();
        $stmtUpdate->close();
    } else {
        $cantidad = 1;
        $sqlInsert = 'INSERT INTO carrito (session_id, producto_id, cantidad, monto_libre) VALUES (?, ?, ?, ?)';
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param('siid', $session_id, $producto_id, $cantidad, $montoLibreEnPesos);
        $ok = $stmtInsert->execute();
        $stmtInsert->close();
    }

    $stmt->close();
} else {
    $sql = 'SELECT id FROM carrito WHERE session_id = ? AND producto_id = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $session_id, $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $sqlUpdate = 'UPDATE carrito SET cantidad = cantidad + 1, monto_libre = NULL WHERE session_id = ? AND producto_id = ?';
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param('si', $session_id, $producto_id);
        $ok = $stmtUpdate->execute();
        $stmtUpdate->close();
    } else {
        $sqlInsert = 'INSERT INTO carrito (session_id, producto_id, cantidad, monto_libre) VALUES (?, ?, 1, NULL)';
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param('si', $session_id, $producto_id);
        $ok = $stmtInsert->execute();
        $stmtInsert->close();
    }

    $stmt->close();
}

if ($ok) {
    echo json_encode(array('status' => 'success', 'message' => 'Producto agregado al carrito'));
} else {
    echo json_encode(array('status' => 'error', 'message' => 'No se pudo actualizar el carrito'));
}

$conn->close();
?>
