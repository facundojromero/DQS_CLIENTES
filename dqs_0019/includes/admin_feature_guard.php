<?php
/**
 * Guards simples para features comerciales dentro del admin activo.
 *
 * UNI-005: bloquea herramientas de envíos WhatsApp cuando la configuración
 * efectiva indica whatsapp_enabled = 0, sin modificar datos ni ejecutar envíos.
 */

require_once __DIR__ . '/plan_config.php';
require_once __DIR__ . '/gift_feature_guard.php';

const DQS_WHATSAPP_DISABLED_MESSAGE = 'La funcionalidad de envíos WhatsApp no está habilitada para este evento.';
const DQS_LEGACY_WHATSAPP_ENDPOINT_DISABLED_MESSAGE = 'Endpoint de envío PHP deshabilitado. El envío de invitaciones se realiza mediante el proceso externo configurado.';
const DQS_CONTACTOS_ENVIO_DISABLED_MESSAGE = 'El módulo Contactos de envío solo está disponible para eventos Oro con RSVP formulario, WhatsApp activo y fuente pre_invitados.';

/**
 * Indica si el staging de contactos de envío está habilitado.
 *
 * La comparación es deliberadamente estricta: ninguna fuente desconocida o
 * alternativa obtiene acceso ni se reemplaza por invitados.
 *
 * @param mysqli|null $conn Conexión activa opcional.
 * @return bool
 */
function dqs_admin_can_view_contactos_envio($conn = null)
{
    $config = dqs_get_effective_plan_config($conn);

    return dqs_admin_config_allows_contactos_envio($config);
}

/**
 * Evalúa la combinación efectiva exacta requerida por el módulo.
 *
 * @param array<string,string> $config Configuración efectiva.
 * @return bool
 */
function dqs_admin_config_allows_contactos_envio(array $config)
{

    return ($config['plan_servicio'] ?? null) === 'oro'
        && ($config['rsvp_modo'] ?? null) === 'form'
        && ($config['whatsapp_enabled'] ?? null) === '1'
        && ($config['fuente_envios_whatsapp'] ?? null) === 'pre_invitados';
}

/**
 * Bloquea el acceso directo al staging antes de cualquier consulta operativa.
 *
 * @param mysqli|null $conn Conexión activa opcional.
 * @return void
 */
function dqs_require_admin_contactos_envio($conn = null)
{
    if (dqs_admin_can_view_contactos_envio($conn)) {
        return;
    }

    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Acceso no disponible</title></head><body>';
    echo '<p>' . htmlspecialchars(DQS_CONTACTOS_ENVIO_DISABLED_MESSAGE, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</body></html>';
    exit();
}

/**
 * Indica si las herramientas admin de envíos WhatsApp están habilitadas.
 *
 * @param mysqli|null $conn Conexión activa opcional.
 * @return bool
 */
function dqs_admin_can_use_whatsapp($conn = null)
{
    return dqs_is_plan_oro($conn) && dqs_is_whatsapp_enabled($conn);
}

/**
 * Bloquea una pantalla/end-point admin de WhatsApp cuando whatsapp_enabled = 0.
 *
 * @param mysqli|null $conn Conexión activa opcional.
 * @param string $format html|json|text
 * @return void
 */
function dqs_require_admin_whatsapp_enabled($conn = null, $format = 'html')
{
    if (dqs_admin_can_use_whatsapp($conn)) {
        return;
    }

    http_response_code(403);

    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'disabled',
            'message' => DQS_WHATSAPP_DISABLED_MESSAGE,
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if ($format === 'text') {
        header('Content-Type: text/plain; charset=utf-8');
        echo DQS_WHATSAPP_DISABLED_MESSAGE;
        exit();
    }

    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>WhatsApp deshabilitado</title></head><body>';
    echo '<p>' . htmlspecialchars(DQS_WHATSAPP_DISABLED_MESSAGE, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</body></html>';
    exit();
}

/**
 * Finaliza el acceso a una herramienta del flujo PHP histórico de envíos.
 *
 * Este bloqueo no consulta configuración ni base de datos: los endpoints están
 * retirados para todos los planes porque el transporte vigente es externo.
 *
 * @param string $format html|json|text
 * @return void
 */
function dqs_disable_legacy_whatsapp_endpoint($format = 'html')
{
    http_response_code(410);

    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'disabled',
            'message' => DQS_LEGACY_WHATSAPP_ENDPOINT_DISABLED_MESSAGE,
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if ($format === 'text') {
        header('Content-Type: text/plain; charset=utf-8');
        echo DQS_LEGACY_WHATSAPP_ENDPOINT_DISABLED_MESSAGE;
        exit();
    }

    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Endpoint deshabilitado</title></head><body>';
    echo '<p>' . htmlspecialchars(DQS_LEGACY_WHATSAPP_ENDPOINT_DISABLED_MESSAGE, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</body></html>';
    exit();
}

/**
 * Indica si las herramientas admin de regalos están habilitadas.
 *
 * @param mysqli|null $conn Conexión activa opcional.
 * @return bool
 */
function dqs_admin_can_use_gifts($conn = null)
{
    return dqs_can_use_gifts($conn);
}

/**
 * Bloquea una pantalla/end-point admin de regalos cuando regalos_enabled = 0.
 *
 * @param mysqli|null $conn Conexión activa opcional.
 * @param string $format html|json|text
 * @return void
 */
function dqs_require_admin_gifts_enabled($conn = null, $format = 'html')
{
    dqs_require_gifts_enabled($conn, $format);
}
