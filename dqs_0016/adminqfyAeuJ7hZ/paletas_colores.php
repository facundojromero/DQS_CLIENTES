<?php
// paletas_colores.php

// Esta sección de código solo se ejecuta si la petición al script es de tipo POST.
// Esto es lo que sucede cuando el JavaScript en el navegador envía la selección de color.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificamos si la variable 'styleId' se ha enviado en la petición POST.
    if (isset($_POST['styleId'])) {
        $styleId = $_POST['styleId']; // Obtenemos el ID numérico (ej. "1", "15")

        // Es buena práctica validar el input para asegurar que es un número y está dentro del rango esperado.
        // Asumo que tienes 20 paletas, del 1 al 20.
        if (ctype_digit($styleId) && intval($styleId) >= 1 && intval($styleId) <= 20) {
            // Definimos la ruta al archivo donde guardaremos el nombre del estilo.
            $filePath = '../current_style.txt';

            // Formateamos el ID para que siempre tenga dos dígitos, añadiendo un cero al principio si es necesario (ej. "1" se convierte en "01").
            $formattedStyleId = str_pad($styleId, 2, '0', STR_PAD_LEFT);

            // Construimos la cadena completa del nombre del archivo de estilo que queremos insertar.
            $contentToWrite = 'style_' . $formattedStyleId . '.css';
            
            $colormostrar =  'Estilo ' . $formattedStyleId . '';

            // Intentamos escribir esta cadena en el archivo.
            // file_put_contents() sobrescribe el contenido del archivo si ya existe,
            // o crea el archivo si no existe.
            if (file_put_contents($filePath, $contentToWrite) !== false) {
                // Si la escritura fue exitosa, enviamos una respuesta JSON al navegador indicando éxito.
                echo json_encode(['success' => true, 'message' => 'Paleta seleccionada y guardada: ' . $colormostrar]);
            } else {
                // Si hubo un error al escribir (por ejemplo, problemas de permisos en el archivo),
                // enviamos una respuesta JSON de error.
                echo json_encode(['success' => false, 'message' => 'Error al escribir en el archivo. Verifica los permisos de ' . $filePath]);
            }
        } else {
            // Si el ID de estilo no es válido (no es un número, o está fuera del rango),
            // enviamos una respuesta JSON de error.
            echo json_encode(['success' => false, 'message' => 'ID de estilo no válido.']);
        }
    } else {
        // Si 'styleId' no se recibió en la petición POST, enviamos una respuesta JSON de error.
        echo json_encode(['success' => false, 'message' => 'No se recibió el ID de estilo.']);
    }
    // Es crucial usar 'exit;' aquí para detener la ejecución del script PHP.
    // Sin esto, el resto del código HTML de la página también se enviaría en la respuesta AJAX,
    // lo cual no es deseado cuando solo esperamos una respuesta JSON.
    exit;
}

// Si la petición no es de tipo POST (es decir, es una petición GET para cargar la página),
// el script continuaría para renderizar el HTML completo de la interfaz de usuario.

// Leer el estilo actual del archivo al cargar la página
$currentStyle = '';
$filePath = '../current_style.txt';
if (file_exists($filePath)) {
    $currentStyle = trim(file_get_contents($filePath));
}

// Extraer el ID numérico del nombre del archivo de estilo
$currentStyleId = '';
if (preg_match('/style_(\d{2})\.css/', $currentStyle, $matches)) {
    $currentStyleId = intval($matches[1]);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selector de Paletas de Colores</title>

</head>
<body>
    <h1>Selecciona una Paleta de Colores</h1>

    <div id="message-area"></div>

    <div class="file-section" data-style-id="1">
        <h2>Estilo 01</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #666666;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body)</span>
                    <span class="color-code">#666666</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #1f1f1f;"></div>
                <div class="color-details">
                    <span class="color-name">Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#1f1f1f</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #63c7bd;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#63c7bd</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #dd666c;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#dd666c</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="2">
        <h2>Estilo 02</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #666666;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body)</span>
                    <span class="color-code">#666666</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #444444;"></div>
                <div class="color-details">
                    <span class="color-name">Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#444444</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #80deea;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#80deea</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #ff8a80;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#ff8a80</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="3">
        <h2>Estilo 03</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #ba68c8;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body) / Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#ba68c8</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #4fc3f7;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#4fc3f7</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #ff8a65;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#ff8a65</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="4">
        <h2>Estilo 04</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #607d8b;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body)</span>
                    <span class="color-code">#607d8b</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #455a64;"></div>
                <div class="color-details">
                    <span class="color-name">Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#455a64</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #37474f;"></div>
                <div class="color-details">
                    <span class="color-name">Enlaces en Títulos (h1-h6 a)</span>
                    <span class="color-code">#37474f</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #90a4ae;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#90a4ae</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #607d8b;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#607d8b</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="5">
        <h2>Estilo 05</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #666666;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body)</span>
                    <span class="color-code">#666666</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #212121;"></div>
                <div class="color-details">
                    <span class="color-name">Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#212121</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #2196f3;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover y Activo</span>
                    <span class="color-code">#2196f3</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="6">
        <h2>Estilo 06</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #00bcd4;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body)</span>
                    <span class="color-code">#00bcd4</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #ff9800;"></div>
                <div class="color-details">
                    <span class="color-name">Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#ff9800</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #00bcd4;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#00bcd4</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #e91e63;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#e91e63</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="7">
        <h2>Estilo 07</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #333333;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body)</span>
                    <span class="color-code">#333333</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #1f1f1f;"></div>
                <div class="color-details">
                    <span class="color-name">Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#1f1f1f</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #6CC3B5;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#6CC3B5</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #AEDFF7;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#AEDFF7</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="8">
        <h2>Estilo 08</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #000000;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body) / Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#000000</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #880e4f;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover y Activo</span>
                    <span class="color-code">#880e4f</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="9">
        <h2>Estilo 09</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #2E2E2E;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body)</span>
                    <span class="color-code">#2E2E2E</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #1f1f1f;"></div>
                <div class="color-details">
                    <span class="color-name">Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#1f1f1f</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #A2CDB0;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#A2CDB0</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #EBD9D9;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#EBD9D9</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="10">
        <h2>Estilo 10</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #90a4ae;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body) / Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#90a4ae</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #c8e6c9;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#c8e6c9</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #bbdefb;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#bbdefb</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="11">
        <h2>Estilo 11</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #1A1A1A;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body)</span>
                    <span class="color-code">#1A1A1A</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #1f1f1f;"></div>
                <div class="color-details">
                    <span class="color-name">Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#1f1f1f</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #A3BFFA;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#A3BFFA</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #B8C0FF;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#B8C0FF</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="12">
        <h2>Estilo 12</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #90a4ae;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body)</span>
                    <span class="color-code">#90a4ae</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #90a4ae;"></div>
                <div class="color-details">
                    <span class="color-name">Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#90a4ae</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #a5d6a7;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#a5d6a7</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #ff8a65;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#ff8a65</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="13">
        <h2>Estilo 13</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #111111;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body)</span>
                    <span class="color-code">#111111</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #1f1f1f;"></div>
                <div class="color-details">
                    <span class="color-name">Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#1f1f1f</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #C0C0C0;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#C0C0C0</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #D4AF37;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#D4AF37</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="14">
        <h2>Estilo 14</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #4A4A4A;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body)</span>
                    <span class="color-code">#4A4A4A</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #1f1f1f;"></div>
                <div class="color-details">
                    <span class="color-name">Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#1f1f1f</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #FFC1CC;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#FFC1CC</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #FADADD;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#FADADD</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="15">
        <h2>Estilo 15</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #ad1457;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body) / Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#ad1457</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #ad1457;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#ad1457</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #f8bbd0;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#f8bbd0</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="16">
        <h2>Estilo 16</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #2B2B2B;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body)</span>
                    <span class="color-code">#2B2B2B</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #1f1f1f;"></div>
                <div class="color-details">
                    <span class="color-name">Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#1f1f1f</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #B3DFFC;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#B3DFFC</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #D7EAFB;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#D7EAFB</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="17">
        <h2>Estilo 17</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #8d6e63;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body)</span>
                    <span class="color-code">#8d6e63</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #90a4ae;"></div>
                <div class="color-details">
                    <span class="color-name">Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#90a4ae</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #90a4ae;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#90a4ae</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #a5d6a7;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#a5d6a7</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="18">
        <h2>Estilo 18</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #2B2B2B;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body)</span>
                    <span class="color-code">#2B2B2B</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #1f1f1f;"></div>
                <div class="color-details">
                    <span class="color-name">Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#1f1f1f</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #B3DFFC;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#B3DFFC</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #D7EAFB;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#D7EAFB</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="19">
        <h2>Estilo 19</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #b0bec5;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body) / Enlaces (a) / Títulos (h1-h6)</span>
                    <span class="color-code">#b0bec5</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #c5e1a5;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#c5e1a5</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #d7ccc8;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#d7ccc8</span>
                </div>
            </div>
        </div>
    </div>

    <div class="file-section" data-style-id="20">
        <h2>Estilo 20</h2>
        <div class="color-palette">
            <div class="color-item">
                <div class="color-box" style="background-color: #cfd8dc;"></div>
                <div class="color-details">
                    <span class="color-name">Texto General (body) / Enlaces (a)</span>
                    <span class="color-code">#cfd8dc</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #b3e5fc;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Hover</span>
                    <span class="color-code">#b3e5fc</span>
                </div>
            </div>
            <div class="color-item">
                <div class="color-box" style="background-color: #aed581;"></div>
                <div class="color-details">
                    <span class="color-name">Paginación/Botón Activo</span>
                    <span class="color-code">#aed581</span>
                </div>
            </div>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fileSections = document.querySelectorAll('.file-section');
            const messageArea = document.getElementById('message-area');

            // Función para actualizar la clase 'active'
            const updateActiveClass = (activeId) => {
                fileSections.forEach(section => {
                    section.classList.remove('active');
                    // Compara el data-style-id con el ID recibido, convertido a string
                    if (section.dataset.styleId === String(activeId)) {
                        section.classList.add('active');
                    }
                });
            };

            // Función para cargar el estilo actual al iniciar la página
            const loadCurrentStyle = async () => {
                // Obtener el ID de estilo actual desde el PHP
                // (el valor de currentStyleId se inyecta directamente en el JavaScript desde PHP)
                const currentId = <?php echo json_encode($currentStyleId); ?>;
                if (currentId) {
                    updateActiveClass(currentId);
                    messageArea.textContent = `Paleta actual cargada: Estilo ${String(currentId).padStart(2, '0')}`;
                    messageArea.className = 'message-area message-success';
                } else {
                    messageArea.textContent = 'No se encontró un estilo predeterminado o el archivo está vacío.';
                    messageArea.className = 'message-area message-error';
                }
            };

            // Event listener para cada sección de archivo
            fileSections.forEach(section => {
                section.addEventListener('click', async () => {
                    const styleId = section.dataset.styleId;

                    try {
                        const response = await fetch('paletas_colores.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `styleId=${styleId}`
                        });
                        const data = await response.json();

                        if (data.success) {
                            messageArea.textContent = data.message;
                            messageArea.className = 'message-area message-success';
                            updateActiveClass(styleId); // Actualiza la clase 'active'
                        } else {
                            messageArea.textContent = data.message;
                            messageArea.className = 'message-area message-error';
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        messageArea.textContent = 'Error de conexión con el servidor.';
                        messageArea.className = 'message-area message-error';
                    }
                });
            });

            // Cargar el estilo actual al cargar la página
            loadCurrentStyle();
        });
    </script>
</body>
</html>