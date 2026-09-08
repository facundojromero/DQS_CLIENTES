<?php
/** Alta Oro + FORM: controlador del formulario compartido en contexto staging. */
if (!isset($conn) || !$conn instanceof mysqli) { http_response_code(500); echo '<p>No se pudo abrir el alta.</p>'; return; }
require_once __DIR__ . '/../includes/admin_feature_guard.php';
require_once __DIR__ . '/includes/guest_create_shared.php';
dqs_require_admin_contactos_envio($conn);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['guest_create_csrf'])) $_SESSION['guest_create_csrf'] = bin2hex(random_bytes(32));

$acompananteOpciones = []; $result = $conn->query('SELECT id, categoria_acompanante FROM intivados_acompanante');
while ($result && $row = $result->fetch_assoc()) $acompananteOpciones[] = $row;
$prioridadOpciones = []; $result = $conn->query('SELECT id, categoria_prioridad FROM invitados_prioridad');
while ($result && $row = $result->fetch_assoc()) $prioridadOpciones[] = $row;
$formMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    dqs_require_admin_contactos_envio($conn);
    if (!hash_equals($_SESSION['guest_create_csrf'], (string)($_POST['csrf_token'] ?? ''))) {
        $formMessage = 'Error: la sesión del formulario venció.';
    } else {
        $saved = dqs_guest_process($conn, 'contacto_envio', $_POST); // contexto fijo del servidor
        $formMessage = $saved['error'];
        if ($formMessage === '') {
            $_SESSION['contactos_envio_mensaje'] = 'El contacto de envío se guardó correctamente.';
            header('Location: ?new=contactos_envio'); exit();
        }
    }
}
$formTitle = 'Cargar contacto de envío';
$formNotice = 'Este contacto se usará para envío de invitaciones. No confirma asistencia RSVP.';
$formAction = '?new=contactos_envio&accion=nuevo'; $cancelUrl = '?new=contactos_envio';
$csrfToken = $_SESSION['guest_create_csrf'];
require __DIR__ . '/includes/guest_form.php';
