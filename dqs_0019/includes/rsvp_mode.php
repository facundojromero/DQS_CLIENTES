<?php
/**
 * UNI-009: selector interno read-only para interpretar rsvp_modo.
 *
 * Este helper no se aplica todavía al front activo. No abre conexiones, no
 * ejecuta consultas al incluirse, no imprime salida y no escribe en base.
 */

require_once __DIR__ . '/plan_config.php';
require_once __DIR__ . '/guest_source.php';

if (!function_exists('dqs_rsvp_allowed_modes')) {
    /**
     * Devuelve los modos RSVP soportados por la capa interna.
     *
     * @return array<int,string>
     */
    function dqs_rsvp_allowed_modes()
    {
        return ['codigo', 'form'];
    }
}

if (!function_exists('dqs_rsvp_is_valid_mode')) {
    /**
     * Indica si un modo RSVP pertenece al dominio permitido.
     *
     * @param mixed $mode
     * @return bool
     */
    function dqs_rsvp_is_valid_mode($mode)
    {
        return is_string($mode) && in_array($mode, dqs_rsvp_allowed_modes(), true);
    }
}

if (!function_exists('dqs_rsvp_get_effective_mode')) {
    /**
     * Devuelve el modo RSVP efectivo en memoria.
     *
     * @param mysqli|null $conn Conexión opcional. Si es null, usa defaults/fallbacks de plan_config.
     * @return string codigo|form
     */
    function dqs_rsvp_get_effective_mode($conn = null)
    {
        $mode = dqs_get_effective_rsvp_modo($conn);

        return dqs_rsvp_is_valid_mode($mode) ? $mode : 'codigo';
    }
}

if (!function_exists('dqs_rsvp_is_codigo_mode')) {
    /**
     * Indica si el modo RSVP efectivo es por código.
     *
     * @param mysqli|null $conn
     * @return bool
     */
    function dqs_rsvp_is_codigo_mode($conn = null)
    {
        return dqs_rsvp_get_effective_mode($conn) === 'codigo';
    }
}

if (!function_exists('dqs_rsvp_is_form_mode')) {
    /**
     * Indica si el modo RSVP efectivo es formulario/pre_ futuro.
     *
     * @param mysqli|null $conn
     * @return bool
     */
    function dqs_rsvp_is_form_mode($conn = null)
    {
        return dqs_rsvp_get_effective_mode($conn) === 'form';
    }
}

if (!function_exists('dqs_rsvp_expected_guest_source_for_mode')) {
    /**
     * Mapea modo RSVP a la fuente GuestSource esperada.
     *
     * @param string $mode
     * @return string|null invitados|pre_invitados|null
     */
    function dqs_rsvp_expected_guest_source_for_mode($mode)
    {
        if ($mode === 'codigo') {
            return 'invitados';
        }

        if ($mode === 'form') {
            return 'pre_invitados';
        }

        return null;
    }
}

if (!function_exists('dqs_rsvp_get_mode_diagnostics')) {
    /**
     * Devuelve diagnóstico read-only del modo RSVP y sus tablas esperadas.
     *
     * No modifica site_settings ni datos. Si no hay conexión, devuelve advertencia
     * y evita consultar tablas. La ausencia de tablas pre_ se reporta como warning.
     *
     * @param mysqli|null $conn
     * @param string|null $mode Modo a analizar; si es null usa el efectivo.
     * @return array<string,mixed>
     */
    function dqs_rsvp_get_mode_diagnostics(mysqli $conn = null, $mode = null)
    {
        $savedConfig = dqs_get_plan_config($conn);
        $effectiveConfig = dqs_get_effective_plan_config($conn);
        $analyzedMode = $mode === null ? ($effectiveConfig['rsvp_modo'] ?? 'codigo') : (string)$mode;
        $isValid = dqs_rsvp_is_valid_mode($analyzedMode);
        $expectedSource = $isValid ? dqs_rsvp_expected_guest_source_for_mode($analyzedMode) : null;
        $warnings = [];
        $tables = [];

        if (!$isValid) {
            $warnings[] = 'Modo RSVP inválido; se recomienda conservar codigo como modo seguro.';
        }

        if ($expectedSource !== null && !dqs_guest_source_is_valid($expectedSource)) {
            $warnings[] = 'La fuente esperada no está soportada por GuestSource.';
        }

        if ($conn === null) {
            $warnings[] = 'Sin conexión mysqli: no se verificó existencia de tablas.';
        } elseif ($expectedSource !== null) {
            $map = dqs_guest_source_get_table_map($expectedSource);
            foreach (['main_table', 'members_table', 'phones_table'] as $key) {
                if (!isset($map[$key])) {
                    continue;
                }
                $tableName = $map[$key];
                $exists = dqs_guest_source_table_exists($conn, $tableName);
                $tables[$tableName] = $exists;
                if (!$exists && $expectedSource === 'pre_invitados') {
                    $warnings[] = "Tabla {$tableName} ausente para modo form/pre_; advertencia no fatal.";
                }
            }
        }

        $safe = $isValid && $analyzedMode === 'codigo';
        if ($isValid && $analyzedMode === 'form') {
            $safe = $conn !== null && count($tables) > 0 && !in_array(false, $tables, true);
            if (!$safe) {
                $warnings[] = 'Modo form preparado pero no seguro para activar si faltan tablas pre_.';
            }
        }

        return [
            'saved_config' => $savedConfig,
            'effective_config' => $effectiveConfig,
            'effective_mode' => dqs_rsvp_get_effective_mode($conn),
            'analyzed_mode' => $analyzedMode,
            'is_valid_mode' => $isValid,
            'expected_guest_source' => $expectedSource,
            'is_safe_for_current_installation' => $safe,
            'tables' => $tables,
            'warnings' => $warnings,
        ];
    }
}
