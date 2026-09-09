<?php
/**
 * UNI-003: herramienta CLI interna para configurar el plan comercial del cliente.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../includes/plan_config.php';
require_once __DIR__ . '/../includes/site_settings_writer.php';

const DQS_PROVIDER_CONFIG_KEYS = [
    'plan_servicio',
    'rsvp_modo',
    'fuente_envios_whatsapp',
    'whatsapp_enabled',
    'regalos_enabled',
    'rsvp_form_persist_enabled',
    'rsvp_form_adult_companions_enabled',
    'rsvp_form_max_adult_companions',
    'rsvp_form_minors_enabled',
    'rsvp_form_max_minors',
    'rsvp_form_food_enabled',
    'rsvp_form_phone_visible',
    'rsvp_form_general_message_enabled',
];

function dqs_provider_print_help()
{
    echo "DQS UNI-003 - Configuración CLI del proveedor\n";
    echo "\nUso:\n";
    echo "  php tools/dqs_provider_config.php --help\n";
    echo "  php tools/dqs_provider_config.php --show\n";
    echo "  php tools/dqs_provider_config.php --set clave=valor [clave=valor ...] [--apply]\n";
    echo "\nClaves permitidas:\n";
    foreach (DQS_PROVIDER_CONFIG_KEYS as $key) {
        echo "  - {$key}: " . implode(', ', DQS_PLAN_CONFIG_ALLOWED_VALUES[$key]) . "\n";
    }
    echo "\nEjemplos (dry-run por defecto, no escribe sin --apply):\n";
    echo "  php tools/dqs_provider_config.php --set plan_servicio=basico rsvp_modo=form whatsapp_enabled=0 regalos_enabled=1 fuente_envios_whatsapp=ninguno\n";
    echo "  php tools/dqs_provider_config.php --set plan_servicio=oro rsvp_modo=codigo whatsapp_enabled=1 regalos_enabled=1 fuente_envios_whatsapp=invitados\n";
    echo "  php tools/dqs_provider_config.php --set plan_servicio=oro rsvp_modo=form whatsapp_enabled=1 regalos_enabled=1 fuente_envios_whatsapp=pre_invitados\n";
    echo "\nAplicar cambios:\n";
    echo "  Agregue --apply al final del comando --set.\n";
}

function dqs_provider_read_connection_settings($file)
{
    if (!is_readable($file)) {
        return null;
    }

    $source = file_get_contents($file);
    $tokens = token_get_all($source);
    $settings = [];
    $allowed = ['servername', 'username', 'password', 'dbname'];

    for ($i = 0, $count = count($tokens); $i < $count; $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_VARIABLE) {
            continue;
        }

        $name = substr($tokens[$i][1], 1);
        if (!in_array($name, $allowed, true)) {
            continue;
        }

        $j = $i + 1;
        while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }
        if (($tokens[$j] ?? null) !== '=') {
            continue;
        }
        $j++;
        while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }
        if (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_CONSTANT_ENCAPSED_STRING) {
            $settings[$name] = stripcslashes(substr($tokens[$j][1], 1, -1));
        }
    }

    foreach ($allowed as $required) {
        if (!array_key_exists($required, $settings)) {
            return null;
        }
    }

    return $settings;
}

function dqs_provider_connect()
{
    $settings = dqs_provider_read_connection_settings(__DIR__ . '/../conexion.php');
    if ($settings === null) {
        fwrite(STDERR, "No se pudo leer la configuración de conexión.\n");
        return null;
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($settings['servername'], $settings['username'], $settings['password'], $settings['dbname']);
    if ($conn->connect_error) {
        fwrite(STDERR, "No se pudo conectar a la base de datos. Revise el entorno local/SSH.\n");
        return null;
    }
    $conn->set_charset('utf8mb4');

    return $conn;
}

function dqs_provider_fetch_saved_config(mysqli $conn)
{
    $saved = [];
    $keys = DQS_PROVIDER_CONFIG_KEYS;
    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $stmt = @$conn->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ($placeholders)");
    if (!$stmt) {
        return null;
    }

    $types = str_repeat('s', count($keys));
    $stmt->bind_param($types, ...$keys);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $saved[$row['setting_key']] = (string)$row['setting_value'];
        }
    }
    $stmt->close();

    return $saved;
}

function dqs_provider_print_config_block($title, array $config)
{
    echo "\n{$title}\n";
    foreach (DQS_PROVIDER_CONFIG_KEYS as $key) {
        $value = array_key_exists($key, $config) ? $config[$key] : '(sin valor guardado)';
        echo "  {$key}: {$value}\n";
    }
}

function dqs_provider_show(mysqli $conn = null)
{
    $saved = $conn ? dqs_provider_fetch_saved_config($conn) : null;
    if ($saved === null) {
        echo "\nConfiguración guardada en site_settings\n";
        echo "  No disponible: tabla ausente, conexión no disponible o consulta no ejecutable.\n";
    } else {
        dqs_provider_print_config_block('Configuración guardada en site_settings', $saved);
    }

    dqs_provider_print_config_block('Defaults en memoria cuando faltan claves', DQS_PLAN_CONFIG_DEFAULTS);
    dqs_provider_print_config_block('Configuración base calculada por dqs_get_plan_config()', dqs_get_plan_config($conn));
    dqs_provider_print_config_block('Configuración efectiva calculada por dqs_get_effective_plan_config()', dqs_get_effective_plan_config($conn));
}

function dqs_provider_parse_set_args(array $argv)
{
    $changes = [];
    $collect = false;
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--set') {
            $collect = true;
            continue;
        }
        if ($arg === '--apply') {
            continue;
        }
        if (strpos($arg, '--') === 0) {
            throw new InvalidArgumentException("Opción desconocida: {$arg}");
        }
        if (!$collect) {
            throw new InvalidArgumentException("Argumento inesperado: {$arg}");
        }
        if (strpos($arg, '=') === false) {
            throw new InvalidArgumentException("Formato inválido: {$arg}. Use clave=valor.");
        }
        [$key, $value] = explode('=', $arg, 2);
        if (!in_array($key, DQS_PROVIDER_CONFIG_KEYS, true)) {
            throw new InvalidArgumentException("Clave no permitida: {$key}");
        }
        if (!dqs_is_valid_plan_config_value($key, $value)) {
            throw new InvalidArgumentException("Valor inválido para {$key}: {$value}");
        }
        $changes[$key] = $value;
    }
    if (!$collect || count($changes) === 0) {
        throw new InvalidArgumentException('Debe indicar --set con al menos una clave=valor.');
    }

    return $changes;
}

function dqs_provider_apply_changes(mysqli $conn, array $changes)
{
    dqs_save_site_settings($conn, $changes);
}

try {
    $args = array_slice($argv, 1);
    if (count($args) === 0 || in_array('--help', $args, true)) {
        dqs_provider_print_help();
        exit(0);
    }

    $needsDb = in_array('--show', $args, true) || in_array('--set', $args, true) || in_array('--apply', $args, true);
    $conn = $needsDb ? dqs_provider_connect() : null;

    if (in_array('--show', $args, true)) {
        dqs_provider_show($conn);
        exit($conn ? 0 : 1);
    }

    if (in_array('--set', $args, true)) {
        $changes = dqs_provider_parse_set_args($argv);
        $current = dqs_get_plan_config($conn);
        $resulting = array_merge($current, $changes);
        $effective = $resulting;
        if ($effective['plan_servicio'] === 'basico') {
            $effective['rsvp_modo'] = 'form';
        }
        if ($effective['whatsapp_enabled'] === '0') {
            $effective['fuente_envios_whatsapp'] = 'ninguno';
        }

        echo in_array('--apply', $args, true) ? "Modo APPLY\n" : "Modo DRY-RUN (no escribe sin --apply)\n";
        echo "\nCambios solicitados\n";
        foreach ($changes as $key => $value) {
            $before = $current[$key] ?? '(sin valor)';
            echo "  {$key}: {$before} => {$value}\n";
        }
        dqs_provider_print_config_block('Configuración resultante base', $resulting);
        dqs_provider_print_config_block('Configuración resultante efectiva', $effective);

        if (in_array('--apply', $args, true)) {
            if (!$conn) {
                throw new RuntimeException('No hay conexión a base de datos para aplicar cambios.');
            }
            dqs_provider_apply_changes($conn, $changes);
            echo "\nCambios aplicados en site_settings.\n";
        } else {
            echo "\nNo se escribió en base de datos. Use --apply para aplicar.\n";
        }
        exit(0);
    }

    throw new InvalidArgumentException('Opción no reconocida. Use --help.');
} catch (Throwable $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
