<?php
/**
 * UNI-009: herramienta CLI read-only para diagnosticar rsvp_modo.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../includes/rsvp_mode.php';

function dqs_rsvp_mode_check_help()
{
    echo "DQS UNI-009 - Diagnóstico read-only de modo RSVP\n";
    echo "\nUso:\n";
    echo "  php tools/dqs_rsvp_mode_check.php --help\n";
    echo "  php tools/dqs_rsvp_mode_check.php --show\n";
    echo "  php tools/dqs_rsvp_mode_check.php --mode=codigo\n";
    echo "  php tools/dqs_rsvp_mode_check.php --mode=form\n";
    echo "\nNo escribe en site_settings, no crea/altera tablas, no modifica invitados y no ejecuta WhatsApp/Node.\n";
}

function dqs_rsvp_mode_check_read_connection_settings($file)
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

function dqs_rsvp_mode_check_connect()
{
    $settings = dqs_rsvp_mode_check_read_connection_settings(__DIR__ . '/../conexion.php');
    if ($settings === null) {
        fwrite(STDERR, "Advertencia: no se pudo leer la configuración de conexión; se usará diagnóstico sin DB.\n");
        return null;
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($settings['servername'], $settings['username'], $settings['password'], $settings['dbname']);
    if ($conn->connect_error) {
        fwrite(STDERR, "Advertencia: no se pudo conectar a la base de datos; se usará diagnóstico sin DB.\n");
        return null;
    }
    $conn->set_charset('utf8mb4');

    return $conn;
}

function dqs_rsvp_mode_check_print_config($title, array $config)
{
    echo "\n{$title}\n";
    foreach (DQS_PLAN_CONFIG_DEFAULTS as $key => $_default) {
        $value = array_key_exists($key, $config) ? $config[$key] : '(sin valor)';
        echo "  {$key}: {$value}\n";
    }
}

function dqs_rsvp_mode_check_print_diagnostics(array $diagnostics)
{
    dqs_rsvp_mode_check_print_config('Configuración guardada/base calculada', $diagnostics['saved_config']);
    dqs_rsvp_mode_check_print_config('Configuración efectiva', $diagnostics['effective_config']);

    echo "\nModo RSVP\n";
    echo "  modo_efectivo: {$diagnostics['effective_mode']}\n";
    echo "  modo_analizado: {$diagnostics['analyzed_mode']}\n";
    echo "  modo_valido: " . ($diagnostics['is_valid_mode'] ? 'si' : 'no') . "\n";
    echo "  fuente_esperada: " . ($diagnostics['expected_guest_source'] ?: '(ninguna)') . "\n";
    echo "  seguro_instalacion_actual: " . ($diagnostics['is_safe_for_current_installation'] ? 'si' : 'no') . "\n";

    echo "\nTablas esperadas verificadas\n";
    if (count($diagnostics['tables']) === 0) {
        echo "  No verificadas o no aplican.\n";
    } else {
        foreach ($diagnostics['tables'] as $table => $exists) {
            echo "  {$table}: " . ($exists ? 'existe' : 'ausente') . "\n";
        }
    }

    echo "\nAdvertencias\n";
    if (count($diagnostics['warnings']) === 0) {
        echo "  Ninguna.\n";
    } else {
        foreach ($diagnostics['warnings'] as $warning) {
            echo "  - {$warning}\n";
        }
    }
}

try {
    $args = array_slice($argv, 1);
    if (count($args) === 0 || in_array('--help', $args, true)) {
        dqs_rsvp_mode_check_help();
        exit(0);
    }

    $mode = null;
    $show = in_array('--show', $args, true);
    foreach ($args as $arg) {
        if (strpos($arg, '--mode=') === 0) {
            $mode = substr($arg, strlen('--mode='));
            continue;
        }
        if ($arg !== '--show') {
            throw new InvalidArgumentException("Opción desconocida: {$arg}");
        }
    }

    if (!$show && $mode === null) {
        throw new InvalidArgumentException('Use --show, --mode=codigo, --mode=form o --help.');
    }

    $conn = dqs_rsvp_mode_check_connect();
    dqs_rsvp_mode_check_print_diagnostics(dqs_rsvp_get_mode_diagnostics($conn, $mode));
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
