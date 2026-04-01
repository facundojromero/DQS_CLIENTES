<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Incluir el archivo de conexión
include_once '../conexion.php'; // Ajusta la ruta según la ubicación de tu archivo

// Depuración: Verificar si la conexión a la BD está cargada
if (!isset($conn)) {
    die("Error: La variable \$conn no está definida en conexion.php");
}
// La verificación $conn->connect_error ya se realiza en conexion.php,
// así que no es estrictamente necesario aquí, pero se deja por si acaso.
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Manejo de la lógica PHP (determina si es una solicitud de datos o de actualización)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si la solicitud es POST, verificamos si es una actualización de toggleli
    if (isset($_POST['action']) && $_POST['action'] === 'update_toggle_status') {
        header('Content-Type: text/html'); // Cambiamos el header ya que no será JSON

        $section = $_POST['section'] ?? '';
        $active = (int)($_POST['active'] ?? 0); // Convertir a entero (0 o 1)

        // CAMBIO: Obtenemos la query string completa del formulario
        $redirect_query_string = $_POST['current_full_query_string'] ?? '';

        if (empty($section)) {
            die("Sección no especificada.");
        }

        $stmt = $conn->prepare("UPDATE info_mostrar SET activo = ? WHERE seccion = ?");
        $stmt->bind_param("is", $active, $section);

        if ($stmt->execute()) {
            // Redirigir para evitar reenvío de formulario y mostrar el estado actualizado
            // Usamos la query string completa para la redirección.
            // Solo agregamos el '?' si la query string no está vacía.
            // Quita "index.php" si está presente en la URL
            $redirect_base = str_replace('index.php', '', $_SERVER['PHP_SELF']);
            $redirect_url = $redirect_base;
            if (!empty($redirect_query_string)) {
                $redirect_url .= '?' . $redirect_query_string;
            }
            header("Location: $redirect_url");
            exit();

        } else {
            echo "Error al actualizar: " . $stmt->error;
        }

        $stmt->close(); // Cierra el statement aquí para asegurar
        $conn->close(); // Cierra la conexión aquí
        exit(); // Termina la ejecución PHP después de enviar la respuesta
    } else {
        // Esto se ejecutará si la solicitud POST no es para actualizar el toggle.
        // Aquí podés poner la lógica para manejar otras solicitudes POST
        // que lleguen a este archivo, o simplemente ignorarlas.
    }
}

// Render del menú para GET y para POSTs que no sean del toggle
    $sql = "SELECT seccion, activo FROM info_mostrar";
    $result = $conn->query($sql);

    $sections_status = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $sections_status[$row['seccion']] = (bool)$row['activo'];
        }
    }

    // CAMBIO: Capturamos la QUERY_STRING completa de la URL actual
    $current_full_query_string = $_SERVER['QUERY_STRING'] ?? '';


    // --- INICIO: DEFINICIÓN DE LA ESTRUCTURA DEL MENÚ EN PHP ---
    // Aquí es donde defines los links y los toggles.
    // Para elementos que son solo enlaces, usas una cadena (ej. "invitados_page").
    // Para elementos que son AMBAS COSAS (link y toggle), usas un array asociativo
    // con 'db_section' y 'url_name'.
    $menu_structure = [
        "Inicio" => "?new=inicio&", // Solo link (elemento principal sin submenú)        
        "Regalos" => [
            "Confirmar" => "?new=ventas&view=confirmarPago", // Solo link
            "Recibidos" => "?new=ventas&view=yaConfirmados",  // Solo link
            "Lista de regalos" => "?new=regalos"  // Solo link            
            
        ],
        "Invitados" => [
            "Lista de invitados" => "?new=invitados", // Solo link
            "Nuevo invitado" => "?new=invitados&nuevo=0", // Solo link 
            "Enviar Invitaciones" => "?new=envioinvitaciones",  // Solo link            
            "Envio Automatico" => "?new=invitaciones",  // Solo link
            "Exportar Invitados" => "exportar_invitados.php"  // Solo link            
        ],        

        "Web" => [
            "Colores" => "?new=paletas",           // Solo link            
            "Logo" => ["db_section" => "logo", "url_name" => "?new=logo"],     // Link Y Toggle                
            "Portada" => "?new=info_casamiento",           // Solo link
            "Imagen Portada" => "?new=imagenes", // Solo link
            "Cronometro" => ["db_section" => "cronometro", "url_name" => "?new=cronometro"],     // Link Y Toggle
            "Nosotros" => ["db_section" => "about", "url_name" => "?new=nosotros"],       // Link Y Toggle
            "Historia" => ["db_section" => "story", "url_name" => "?new=historia"],       // Link Y Toggle
            "Fotos" => ["db_section" => "gallery", "url_name" => "?new=fotos"],       // Link Y Toggle
            "Eventos" => ["db_section" => "events", "url_name" => "?new=eventos"],       // Link Y Toggle
            "Mas info" => ["db_section" => "wedding", "url_name" => "?new=masinfo"],   // Link Y Toggle
            "Contactar" => ["db_section" => "contact", "url_name" => "#"]    // Link Y Toggle
        ],
        "Datos" => "?new=datos", // Solo link (elemento principal sin submenú)
     
        "Cerrar Sesión" => "logout.php" // Solo link (elemento principal sin submenú)
    ];

    // --- FIN: DEFINICIÓN DE LA ESTRUCTURA DEL MENÚ EN PHP ---

?>
<header>
        <nav id="main-nav">
            <a href="?new=inicio&" class="logo-link">
                <img src="image/logo.png" alt="Logo de tu sitio" class="logo-img">
            </a>
            <button class="hamburger" aria-label="Toggle navigation">&#9776;</button>
            <ul class="nav-menu">
                <?php
                foreach ($menu_structure as $main_item_text => $sub_items) {
                    echo '<li>';
                    $main_link_href = "#";
                    $has_submenu_class = "";

                    if (is_array($sub_items)) {
                        $has_submenu_class = "has-submenu";
                        // Si tiene submenú, el padre no debe tener un enlace directo de navegación
                        $main_link_href = "javascript:void(0);"; // Deshabilita el enlace
                        echo '<a href="' . $main_link_href . '" ' . ($has_submenu_class ? 'class="' . $has_submenu_class . '"' : '') . ' style="' . ($has_submenu_class ? 'cursor: default;' : '') . '">' . htmlspecialchars($main_item_text) . '</a>'; // Agregué htmlspecialchars

                        echo '<ul class="submenu">';
                        foreach ($sub_items as $sub_text => $details) {
                            echo '<li>';
                            if (is_array($details) && isset($details['db_section']) && isset($details['url_name'])) {
                                // Es un link y un toggle
                                $db_section = $details['db_section'];
                                $url_name = $details['url_name'];
                                $is_active = isset($sections_status[$db_section]) ? $sections_status[$db_section] : false;

                                echo '<a href="' . htmlspecialchars($url_name) . '">' . htmlspecialchars($sub_text) . '</a>';

                                // Formulario para el toggle
                                echo '<form class="toggle-form" method="POST" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">';
                                echo '<input type="hidden" name="action" value="update_toggle_status">'; // Campo de acción para diferenciar
                                echo '<input type="hidden" name="section" value="' . htmlspecialchars($db_section) . '">';
                                echo '<input type="hidden" name="active" value="' . ($is_active ? '0' : '1') . '">'; // Invertimos el valor para el próximo clic
                                // CAMBIO ACÁ: Pasamos la query string completa
                                echo '<input type="hidden" name="current_full_query_string" value="' . htmlspecialchars($current_full_query_string) . '">';
                                echo '<button type="submit" class="toggle-checkbox ' . ($is_active ? 'active' : '') . '"></button>';
                                echo '</form>';

                            } elseif (is_string($details)) {
                                // Es solo un link
                                echo '<a href="' . htmlspecialchars($details) . '">' . htmlspecialchars($sub_text) . '</a>';
                            }
                            echo '</li>';
                        }
                        echo '</ul>';
                    } else {
                        // Es un elemento principal sin submenú
                        $main_link_href = "" . htmlspecialchars($sub_items);
                        echo '<a href="' . $main_link_href . '">' . htmlspecialchars($main_item_text) . '</a>';
                    }
                    echo '</li>';
                }
                ?>
            </ul>
        </nav>
    </header>

    <script>
        // JavaScript - ¡INICIO!
        document.addEventListener('DOMContentLoaded', () => {
            const navMenu = document.querySelector('.nav-menu');
            const hamburger = document.querySelector('.hamburger');

            // Lógica para el menú responsive (hamburguesa)
            hamburger.addEventListener('click', () => {
                navMenu.classList.toggle('active');
            });

            // Abrir/cerrar submenús en modo móvil
            navMenu.addEventListener('click', (event) => {
                const targetLi = event.target.closest('li.has-submenu');
                if (targetLi && window.innerWidth <= 768) { // Solo en dispositivos móviles
                    // Si el clic es en el ENLACE DIRECTO del elemento padre que tiene un submenú
                    // Y ese elemento padre NO tiene una URL propia (solo se usa para expandir)
                    // entonces prevenimos el comportamiento por defecto (navegación).
                    // Los enlaces DENTRO del submenú deben funcionar normalmente.
                    if (event.target.tagName === 'A' && !event.target.closest('.submenu')) {
                        event.preventDefault(); // Previene la navegación del elemento padre del submenú
                        targetLi.classList.toggle('open');
                    } else if (!event.target.closest('.toggle-form')) {
                        // Si el clic no fue en el toggle-form ni en el enlace principal del submenú padre,
                        // y el target es un li.has-submenu (o un elemento dentro),
                        // entonces permitimos expandir/contraer el submenú.
                        // Esto es para el caso cuando el usuario hace clic en el nombre de la sección principal del menú,
                        // para expandir el submenú en móvil, sin que haya un link directo en el padre.
                        if (event.target.closest('.has-submenu') === targetLi) {
                            targetLi.classList.toggle('open');
                        }
                    }
                }
            });

            // NOTA: La lógica de actualización de toggles ya no está aquí,
            // porque cada toggle es un formulario POST que recarga la página.
        });
        // JavaScript - ¡FIN!
    </script>
