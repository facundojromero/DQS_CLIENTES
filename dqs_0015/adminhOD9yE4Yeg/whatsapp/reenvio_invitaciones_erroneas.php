<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = parse_ini_file(__DIR__ . "/config.txt");

// Token y URL de WhatsApp API desde el archivo
$token = $config['TOKEN'];
$url   = $config['URL'];

// Conexión MySQL
include_once("../../conexion.php");

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Consulta
$sql = "
SELECT 
mi.telefono, mi.telefono2,
a.id
, b.id id_invitados_tel
, e.invitados
, a.nombre
, a.apellido
, a.ingreso
, b.tel_enviar
, a.codigo
, (a.cantidad_mayores + a.cantidad_menores) cantidad_invitados
FROM invitados a
INNER JOIN invitados_tel b ON a.id = b.id_invitados
LEFT JOIN (
		SELECT 
		    a.id_invitados,
		    CASE WHEN cantidad_mayores+cantidad_menores<2 THEN nombre_invitado ELSE 
		    CONCAT(
			IF(COUNT(*) > 1,
			    SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '), ', ', COUNT(*) - 1),
			    GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', ')
			),
			' y ',
			SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '), ', ', -1)
		    ) END AS invitados
		FROM invitados_listado_mesa a
		INNER JOIN invitados b ON a.id_invitados=b.id
		GROUP BY a.id_invitados
) e ON a.id = e.id_invitados
INNER JOIN (
	SELECT a.id_invitado, a.id_invitados_tel
	FROM (SELECT DISTINCT id_invitado, id_invitados_tel FROM invitaciones_estado WHERE estado_api<>'enviado') a
	LEFT JOIN (SELECT DISTINCT id_invitado, id_invitados_tel FROM invitaciones_estado WHERE estado_api='enviado') b
	ON a.id_invitado = b.id_invitado
	AND a.id_invitados_tel = b.id_invitados_tel
	WHERE b.id_invitado IS NULL
) f
ON b.id_invitados = f.id_invitado
AND b.id = f.id_invitados_tel
INNER JOIN cliente mi 
WHERE a.activo=1
AND tel_enviar IN (1131648789,9999999999,9999999998)
LIMIT 20
;
";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $mitelefono1 = $row["telefono"];
        $mitelefono2 = $row["telefono2"];
        $id = $row["id"];
        $id_invitados_tel = $row["id_invitados_tel"];        
        $nombre = $row["nombre"];
        $apellido = $row["apellido"];
        $ingreso = $row["ingreso"];
        $telefono_enviar = "54" . $row["tel_enviar"];
        $imagen_nombre = str_pad($id, 4, "0", STR_PAD_LEFT) . ".jpg";
        

        // Ruta dinámica a la carpeta 'invitaciones'
        $base_url = dirname($_SERVER['REQUEST_URI'], 2); // sube de /whatsapp/ a /adminA0KxlHeVGc/
        $imagen_url = "https://" . $_SERVER['HTTP_HOST'] . $base_url . "/invitaciones/" . $imagen_nombre;        
        
        $invitados = $row["invitados"];
        $codigo = $row["codigo"];

        $ruta= dirname($_SERVER['REQUEST_URI'], 3); // Subimos 3 niveles
        $link_mensaje = "www." . $_SERVER['HTTP_HOST'] . $ruta;    

        
        $link_dinamico = $row["codigo"] . "#rsvp";
   

        // Construir JSON con plantilla
        $data = [
            "messaging_product" => "whatsapp",
            "to" => $telefono_enviar,
            "type" => "template",
            "template" => [
                "name" => "plantilla_cliente_0002", // nombre exacto de tu plantilla
                "language" => [ "code" => "en_US" ],
                "components" => [
                    [
                        "type" => "header",
                        "parameters" => [
                            [
                                "type" => "image",
                                "image" => [
                                    "link" => $imagen_url
                                ]
                            ]
                        ]
                    ],
                    [
                        "type" => "body",
                        "parameters" => [
                            [
                                "type" => "text",
                                "text" => $invitados
                            ],
                            [
                                "type" => "text",
                                "text" => $codigo
                            ]
                        ]
                    ],
                    [
                        "type" => "button",
                        "sub_type" => "url",
                        "index" => "0",
                        "parameters" => [
                            [
                                "type" => "text",
                                "text" => $link_dinamico // Por ejemplo: código único del invitado
                            ]
                        ]
                    ]
                ]
            ]
        ];


        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Mostrar
        echo "ID: $id<br>";
        echo "Teléfono: $telefono<br>";
        echo "Imagen: $imagen_url<br>";
        echo "<pre>JSON enviado:\n$json</pre>";

        // Enviar por CURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "<pre>Respuesta API:\n$response</pre>";

        // Registrar resultado
        $estado = ($status_code == 200) ? 'enviado' : 'error';
        $fecha_envio = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO invitaciones_estado (id_invitado, id_invitados_tel, fecha_envio, estado_api, detalle_api) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $id, $id_invitados_tel, $fecha_envio, $estado, $response);
        $stmt->execute();
        $stmt->close();

        sleep(1); // Pausa para evitar límite de velocidad
        echo "<hr>";
    }
} else {
    echo "No se encontraron registros pendientes de envío.";
}

$conn->close();
?>
