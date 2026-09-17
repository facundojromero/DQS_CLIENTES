<?php
// --- CONFIGURACIÓN ---
define('CLAVE_SECRETA', 'Virgen.Itati'); // Cambiá esto por una clave segura
define('VECTOR_INICIAL', 'vector123'); // Cambiá esto también

function cifrar($texto) {
    $metodo = 'AES-256-CBC';
    $iv = substr(hash('sha256', VECTOR_INICIAL), 0, 16);
    $clave = hash('sha256', CLAVE_SECRETA);
    $cifrado = openssl_encrypt($texto, $metodo, $clave, 0, $iv);
    return base64_encode($cifrado);
}

function descifrar($texto_cifrado) {
    $metodo = 'AES-256-CBC';
    $iv = substr(hash('sha256', VECTOR_INICIAL), 0, 16);
    $clave = hash('sha256', CLAVE_SECRETA);
    $texto_cifrado = base64_decode($texto_cifrado);
    return openssl_decrypt($texto_cifrado, $metodo, $clave, 0, $iv);
}

// --- LÓGICA PRINCIPAL ---
if (!isset($_GET['k'])) {
    http_response_code(400);
    echo "Enlace inválido.";
    exit;
}

$archivo = descifrar($_GET['k']);
$archivo = basename($archivo); // Evita inyecciones de ruta
$ruta_imagen = __DIR__ . "/adminA0KxlHeVGc/invitaciones/" . $archivo;


if (!file_exists($ruta_imagen)) {
    http_response_code(404);
    echo "Imagen no encontrada.";
    exit;
}



// Mostrar imagen directamente
header('Content-Type: image/jpeg');
readfile($ruta_imagen);
exit;
?>
