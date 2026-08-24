<?php
require_once __DIR__ . '/../includes/gift_feature_guard.php';

const REGALO_LIBRE_TITULO = 'Gift Card';
const REGALO_LIBRE_DESCRIPCION = 'Elegí el monto que quieras regalar.';
const REGALO_LIBRE_MONTO_MAXIMO = 100000000;
const REGALO_LIBRE_TITULOS_COMPATIBLES = array('Regalo libre', 'Gift Card');

function esTituloRegaloLibre($titulo) {
    if ($titulo === null) {
        return false;
    }

    foreach (REGALO_LIBRE_TITULOS_COMPATIBLES as $tituloCompatible) {
        if (strcasecmp(trim((string)$titulo), $tituloCompatible) === 0) {
            return true;
        }
    }

    return false;
}

function tablaTieneColumna(mysqli $conn, $tabla, $columna) {
    $sql = "SHOW COLUMNS FROM `" . $conn->real_escape_string($tabla) . "` LIKE ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $columna);
    $stmt->execute();
    $result = $stmt->get_result();
    $existe = $result && $result->num_rows > 0;
    $stmt->close();

    return $existe;
}

function asegurarEstructuraRegaloLibre(mysqli $conn) {
    if (!tablaTieneColumna($conn, 'regalos_detalles', 'monto_libre')) {
        $conn->query('ALTER TABLE regalos_detalles ADD COLUMN monto_libre DECIMAL(10,2) NULL AFTER cantidad');
    }

    if (!tablaTieneColumna($conn, 'carrito', 'monto_libre')) {
        $conn->query('ALTER TABLE carrito ADD COLUMN monto_libre DECIMAL(10,2) NULL AFTER cantidad');
    }
}

function asegurarTablaSiteSettings(mysqli $conn) {
    $sql = "
        CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value VARCHAR(255) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    $conn->query($sql);
}

function obtenerSetting(mysqli $conn, $clave, $valorPorDefecto = null) {
    asegurarTablaSiteSettings($conn);

    $sql = 'SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return $valorPorDefecto;
    }

    $stmt->bind_param('s', $clave);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $valor = $valorPorDefecto;
    if ($resultado && $resultado->num_rows > 0) {
        $row = $resultado->fetch_assoc();
        $valor = $row['setting_value'];
    }

    $stmt->close();
    return $valor;
}

function guardarSetting(mysqli $conn, $clave, $valor) {
    asegurarTablaSiteSettings($conn);

    $valorStr = (string)$valor;
    $sql = '
        INSERT INTO site_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $clave, $valorStr);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}


function obtenerConfiguracionVisualRegalos(mysqli $conn) {
    return array(
        'mostrar_lista_regalos' => obtenerSetting($conn, 'mostrar_lista_regalos', '1') === '1',
        'mostrar_transferencia_regalos' => obtenerSetting($conn, 'mostrar_transferencia_regalos', '0') === '1',
        'titulo_transferencia_regalos' => obtenerSetting($conn, 'titulo_transferencia_regalos', '¿Nos querés hacer un regalo?'),
        'titulo_cuenta_pesos_regalos' => obtenerSetting($conn, 'titulo_cuenta_pesos_regalos', 'Cuenta en pesos'),
        'titulo_cuenta_dolares_regalos' => obtenerSetting($conn, 'titulo_cuenta_dolares_regalos', 'Cuenta en dólares'),
    );
}

function obtenerDestinoLinkRegalar(mysqli $conn, $esTienda = false) {
    if (!dqs_can_use_gifts($conn)) {
        return array(
            'mostrar' => false,
            'href' => '',
            'mostrar_lista_regalos' => false,
            'mostrar_transferencia_regalos' => false,
        );
    }

    $config = obtenerConfiguracionVisualRegalos($conn);
    $mostrarListaRegalos = !empty($config['mostrar_lista_regalos']);
    $mostrarTransferenciaRegalos = !empty($config['mostrar_transferencia_regalos']);

    if ($mostrarListaRegalos) {
        return array(
            'mostrar' => true,
            'href' => $esTienda ? '../tienda/' : 'tienda/',
            'mostrar_lista_regalos' => $mostrarListaRegalos,
            'mostrar_transferencia_regalos' => $mostrarTransferenciaRegalos,
        );
    }

    if ($mostrarTransferenciaRegalos) {
        return array(
            'mostrar' => true,
            'href' => $esTienda ? '../#regalar' : '#regalar',
            'mostrar_lista_regalos' => $mostrarListaRegalos,
            'mostrar_transferencia_regalos' => $mostrarTransferenciaRegalos,
        );
    }

    return array(
        'mostrar' => false,
        'href' => '',
        'mostrar_lista_regalos' => $mostrarListaRegalos,
        'mostrar_transferencia_regalos' => $mostrarTransferenciaRegalos,
    );
}

function guardarConfiguracionVisualRegalos(mysqli $conn, array $config) {
    $ok = true;
    $ok = guardarSetting($conn, 'mostrar_lista_regalos', !empty($config['mostrar_lista_regalos']) ? '1' : '0') && $ok;
    $ok = guardarSetting($conn, 'mostrar_transferencia_regalos', !empty($config['mostrar_transferencia_regalos']) ? '1' : '0') && $ok;
    $ok = guardarSetting($conn, 'titulo_transferencia_regalos', trim((string)$config['titulo_transferencia_regalos'])) && $ok;
    $ok = guardarSetting($conn, 'titulo_cuenta_pesos_regalos', trim((string)$config['titulo_cuenta_pesos_regalos'])) && $ok;
    $ok = guardarSetting($conn, 'titulo_cuenta_dolares_regalos', trim((string)$config['titulo_cuenta_dolares_regalos'])) && $ok;

    return $ok;
}


function mostrarListaRegalosHabilitada(mysqli $conn) {
    if (!dqs_can_use_gifts($conn)) {
        return false;
    }

    $config = obtenerConfiguracionVisualRegalos($conn);
    return !empty($config['mostrar_lista_regalos']);
}

function responderListaRegalosNoDisponible($formato = 'html') {
    $mensaje = DQS_GIFTS_DISABLED_MESSAGE;
    if (dqs_can_use_gifts()) {
        $mensaje = 'La lista de regalos no está disponible en este momento.';
    }

    if ($formato === 'json') {
        header('Content-Type: application/json');
        echo json_encode(array('status' => 'error', 'message' => $mensaje));
    } else {
        echo '<p>' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    exit;
}

function mostrarGiftCardHabilitada(mysqli $conn) {
    $valor = obtenerSetting($conn, 'show_giftcard', '1');
    return (string)$valor === '1';
}

function obtenerOCrearProductoRegaloLibre(mysqli $conn) {
    $sql = 'SELECT id, titulo FROM productos ORDER BY id ASC';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

    $productoId = 0;
    if ($resultado && $resultado->num_rows > 0) {
        while ($row = $resultado->fetch_assoc()) {
            if (esTituloRegaloLibre($row['titulo'])) {
                $productoId = (int)$row['id'];
                break;
            }
        }
    }

    $stmt->close();

    if ($productoId > 0) {
        $sqlUpdate = 'UPDATE productos SET titulo = ?, descripcion = ?, precio = 0, activo = 1 WHERE id = ?';
        $stmtUpdate = $conn->prepare($sqlUpdate);
        if ($stmtUpdate) {
            $titulo = REGALO_LIBRE_TITULO;
            $descripcion = REGALO_LIBRE_DESCRIPCION;
            $stmtUpdate->bind_param('ssi', $titulo, $descripcion, $productoId);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        }

        return $productoId;
    }

    $precio = 0;
    $activo = 1;
    $titulo = REGALO_LIBRE_TITULO;
    $descripcion = REGALO_LIBRE_DESCRIPCION;
    $sqlInsert = 'INSERT INTO productos (titulo, descripcion, precio, activo) VALUES (?, ?, ?, ?)';
    $stmtInsert = $conn->prepare($sqlInsert);
    if (!$stmtInsert) {
        return 0;
    }

    $stmtInsert->bind_param('ssii', $titulo, $descripcion, $precio, $activo);
    $stmtInsert->execute();
    $nuevoId = (int)$conn->insert_id;
    $stmtInsert->close();

    return $nuevoId;
}

function sanitizarMontoRegaloLibre($valor) {
    if (!is_numeric($valor)) {
        return null;
    }

    $monto = round((float)$valor, 2);
    if ($monto <= 0 || $monto > REGALO_LIBRE_MONTO_MAXIMO) {
        return null;
    }

    return $monto;
}
