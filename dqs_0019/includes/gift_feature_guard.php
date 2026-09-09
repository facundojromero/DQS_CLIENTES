<?php
/**
 * Guard central para UNI-006: feature comercial de regalos/tienda/carrito.
 */

require_once __DIR__ . '/plan_config.php';

const DQS_GIFTS_DISABLED_MESSAGE = 'La funcionalidad de regalos no está habilitada para este evento.';

function dqs_can_use_gifts($conn = null)
{
    return dqs_is_regalos_enabled($conn);
}

function dqs_require_gifts_enabled($conn = null, $format = 'html')
{
    if (dqs_can_use_gifts($conn)) {
        return;
    }

    http_response_code(403);

    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'disabled',
            'message' => DQS_GIFTS_DISABLED_MESSAGE,
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if ($format === 'text') {
        header('Content-Type: text/plain; charset=utf-8');
        echo DQS_GIFTS_DISABLED_MESSAGE;
        exit();
    }

    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Regalos deshabilitados</title>';
    echo '<style>body{font-family:Arial,sans-serif;margin:40px;color:#333}.dqs-disabled{max-width:720px;margin:auto;padding:24px;border:1px solid #eee;border-radius:12px;background:#fff}</style>';
    echo '</head><body><div class="dqs-disabled">';
    echo '<p>' . htmlspecialchars(DQS_GIFTS_DISABLED_MESSAGE, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</div></body></html>';
    exit();
}
