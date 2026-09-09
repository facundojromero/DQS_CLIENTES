<?php
/**
 * Escritura segura de claves conocidas en site_settings.
 */

require_once __DIR__ . '/plan_config.php';

/**
 * Guarda valores validados en site_settings usando prepared statements.
 *
 * @param mysqli $conn Conexión activa.
 * @param array<string,string> $changes Claves/valores de configuración.
 * @throws InvalidArgumentException Si una clave o valor no está permitido.
 * @throws RuntimeException Si no se puede preparar o ejecutar la escritura.
 */
function dqs_save_site_settings(mysqli $conn, array $changes)
{
    if (count($changes) === 0) {
        return;
    }

    foreach ($changes as $key => $value) {
        if (!array_key_exists($key, DQS_PLAN_CONFIG_DEFAULTS)) {
            throw new InvalidArgumentException("Clave no permitida: {$key}");
        }
        if (!dqs_is_valid_plan_config_value($key, (string)$value)) {
            throw new InvalidArgumentException("Valor inválido para {$key}: {$value}");
        }
    }

    $stmt = $conn->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar la escritura en site_settings.');
    }

    foreach ($changes as $key => $value) {
        $value = (string)$value;
        $stmt->bind_param('ss', $key, $value);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("No se pudo guardar {$key}.");
        }
    }

    $stmt->close();
}
