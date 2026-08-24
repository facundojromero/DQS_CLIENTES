<?php
/** Alta definitiva: usa la misma persistencia y vista que Contactos de envío. */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if (!isset($conn) || !$conn instanceof mysqli) include_once '../conexion.php';
if (!isset($conn) || !$conn instanceof mysqli || $conn->connect_error) die('Conexión fallida.');
require_once __DIR__ . '/includes/guest_create_shared.php';
if (empty($_SESSION['guest_create_csrf'])) $_SESSION['guest_create_csrf'] = bin2hex(random_bytes(32));

$acompananteOpciones = []; $result = $conn->query('SELECT id, categoria_acompanante FROM intivados_acompanante');
while ($result && $row = $result->fetch_assoc()) $acompananteOpciones[] = $row;
$prioridadOpciones = []; $result = $conn->query('SELECT id, categoria_prioridad FROM invitados_prioridad');
while ($result && $row = $result->fetch_assoc()) $prioridadOpciones[] = $row;
$formMessage = (string)($_SESSION['mensaje'] ?? ''); unset($_SESSION['mensaje']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['guest_create_csrf'], (string)($_POST['csrf_token'] ?? ''))) {
        $formMessage = 'Error: la sesión del formulario venció.';
    } else {
        $saved = dqs_guest_process($conn, 'invitado', $_POST); // contexto fijo: nunca cambia por GET/POST
        $formMessage = $saved['error'];
        if ($formMessage === '') {
            $_SESSION['mensaje'] = 'Se ha registrado nuevo invitado con sus acompañantes.';
            header('Location: ?new=invitados&nuevo=0&open_card=1&id_invitados_to_open=' . $saved['id']); exit();
        }
    }
}
$formTitle = 'Registrar Invitado'; $formNotice = '';
$formAction = '?new=invitados&nuevo=0'; $cancelUrl = '?new=invitados';
$csrfToken = $_SESSION['guest_create_csrf'];
require __DIR__ . '/includes/guest_form.php';
