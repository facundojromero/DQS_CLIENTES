<?php
/**
 * GuestSource: capa interna read-only para leer invitados desde distintas fuentes.
 *
 * UNI-004: este helper no se aplica todavía a pantallas visibles.
 * No abre conexiones, no ejecuta consultas al incluirse y no modifica datos.
 */

if (!function_exists('dqs_guest_source_allowed_sources')) {
    /**
     * Devuelve las fuentes soportadas por GuestSource.
     *
     * @return array<int,string>
     */
    function dqs_guest_source_allowed_sources()
    {
        return ['invitados', 'pre_invitados'];
    }
}

if (!function_exists('dqs_guest_source_is_valid')) {
    /**
     * Indica si la fuente está soportada.
     *
     * @param string $source
     * @return bool
     */
    function dqs_guest_source_is_valid($source)
    {
        return in_array($source, dqs_guest_source_allowed_sources(), true);
    }
}

if (!function_exists('dqs_guest_source_get_table_map')) {
    /**
     * Devuelve el mapa de tablas y columnas FK para una fuente soportada.
     *
     * Nota de compatibilidad: las referencias revisadas de pre_invitados usan
     * id_invitados como FK en pre_invitados_listado_mesa y pre_invitados_tel.
     *
     * @param string $source
     * @return array<string,string>
     */
    function dqs_guest_source_get_table_map($source)
    {
        $maps = [
            'invitados' => [
                'source' => 'invitados',
                'main_table' => 'invitados',
                'members_table' => 'invitados_listado_mesa',
                'phones_table' => 'invitados_tel',
                'members_fk' => 'id_invitados',
                'phones_fk' => 'id_invitados',
            ],
            'pre_invitados' => [
                'source' => 'pre_invitados',
                'main_table' => 'pre_invitados',
                'members_table' => 'pre_invitados_listado_mesa',
                'phones_table' => 'pre_invitados_tel',
                'members_fk' => 'id_invitados',
                'phones_fk' => 'id_invitados',
            ],
        ];

        return isset($maps[$source]) ? $maps[$source] : [];
    }
}

if (!function_exists('dqs_guest_source_table_exists')) {
    /**
     * Verifica si existe una tabla en la base actual sin provocar fatal error.
     *
     * @param mysqli $conn
     * @param string $tableName
     * @return bool
     */
    function dqs_guest_source_table_exists(mysqli $conn, $tableName)
    {
        if (!is_string($tableName) || $tableName === '') {
            return false;
        }

        $sql = 'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1';
        $statement = @$conn->prepare($sql);
        if (!$statement) {
            return false;
        }

        $statement->bind_param('s', $tableName);
        if (!$statement->execute()) {
            $statement->close();
            return false;
        }

        $result = $statement->get_result();
        $exists = $result && $result->num_rows > 0;
        $statement->close();

        return $exists;
    }
}

if (!function_exists('dqs_guest_source_find_by_codigo')) {
    /**
     * Busca un invitado/preinvitado por código.
     *
     * @param mysqli $conn
     * @param string $codigo
     * @param string $source
     * @return array<string,mixed>|null
     */
    function dqs_guest_source_find_by_codigo(mysqli $conn, $codigo, $source = 'invitados')
    {
        $map = dqs_guest_source_get_table_map($source);
        if (!$map || $codigo === '' || !dqs_guest_source_table_exists($conn, $map['main_table'])) {
            return null;
        }

        $sql = 'SELECT * FROM `' . $map['main_table'] . '` WHERE codigo = ? LIMIT 1';
        $statement = @$conn->prepare($sql);
        if (!$statement) {
            return null;
        }

        $statement->bind_param('s', $codigo);
        if (!$statement->execute()) {
            $statement->close();
            return null;
        }

        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();

        return $row ?: null;
    }
}

if (!function_exists('dqs_guest_source_get_members')) {
    /**
     * Devuelve integrantes/personas de un invitado/preinvitado.
     *
     * @param mysqli $conn
     * @param int $guestId
     * @param string $source
     * @return array<int,array<string,mixed>>
     */
    function dqs_guest_source_get_members(mysqli $conn, $guestId, $source = 'invitados')
    {
        $map = dqs_guest_source_get_table_map($source);
        if (!$map || $guestId <= 0 || !dqs_guest_source_table_exists($conn, $map['members_table'])) {
            return [];
        }

        $sql = 'SELECT * FROM `' . $map['members_table'] . '` WHERE `' . $map['members_fk'] . '` = ? ORDER BY id ASC';
        $statement = @$conn->prepare($sql);
        if (!$statement) {
            return [];
        }

        $guestId = (int)$guestId;
        $statement->bind_param('i', $guestId);
        if (!$statement->execute()) {
            $statement->close();
            return [];
        }

        $result = $statement->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $statement->close();

        return $rows;
    }
}

if (!function_exists('dqs_guest_source_get_phones')) {
    /**
     * Devuelve teléfonos de un invitado/preinvitado.
     *
     * @param mysqli $conn
     * @param int $guestId
     * @param string $source
     * @return array<int,array<string,mixed>>
     */
    function dqs_guest_source_get_phones(mysqli $conn, $guestId, $source = 'invitados')
    {
        $map = dqs_guest_source_get_table_map($source);
        if (!$map || $guestId <= 0 || !dqs_guest_source_table_exists($conn, $map['phones_table'])) {
            return [];
        }

        $sql = 'SELECT * FROM `' . $map['phones_table'] . '` WHERE `' . $map['phones_fk'] . '` = ? ORDER BY id ASC';
        $statement = @$conn->prepare($sql);
        if (!$statement) {
            return [];
        }

        $guestId = (int)$guestId;
        $statement->bind_param('i', $guestId);
        if (!$statement->execute()) {
            $statement->close();
            return [];
        }

        $result = $statement->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $statement->close();

        return $rows;
    }
}

if (!function_exists('dqs_guest_source_get_rsvp_status')) {
    /**
     * Devuelve estado RSVP agregado desde la tabla principal.
     *
     * @param mysqli $conn
     * @param int $guestId
     * @param string $source
     * @return array<string,mixed>
     */
    function dqs_guest_source_get_rsvp_status(mysqli $conn, $guestId, $source = 'invitados')
    {
        $map = dqs_guest_source_get_table_map($source);
        if (!$map || $guestId <= 0 || !dqs_guest_source_table_exists($conn, $map['main_table'])) {
            return [];
        }

        $sql = 'SELECT id, confirmacion, confirmacion_fecha, confirmacion_comentario, confirmacion_mayores, confirmacion_menores FROM `' . $map['main_table'] . '` WHERE id = ? LIMIT 1';
        $statement = @$conn->prepare($sql);
        if (!$statement) {
            return [];
        }

        $guestId = (int)$guestId;
        $statement->bind_param('i', $guestId);
        if (!$statement->execute()) {
            $statement->close();
            return [];
        }

        $result = $statement->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $statement->close();

        return $row ?: [];
    }
}

if (!function_exists('dqs_guest_source_from_plan_config')) {
    /**
     * Devuelve la fuente de invitados desde la configuración efectiva del plan.
     * No aplica la fuente a ningún flujo visible.
     *
     * @param mysqli|null $conn
     * @return string
     */
    function dqs_guest_source_from_plan_config($conn = null)
    {
        if (!function_exists('dqs_get_effective_plan_config')) {
            $planConfigPath = __DIR__ . '/plan_config.php';
            if (is_file($planConfigPath)) {
                require_once $planConfigPath;
            }
        }

        if (!function_exists('dqs_get_effective_plan_config')) {
            return 'invitados';
        }

        $config = dqs_get_effective_plan_config($conn);
        $source = isset($config['fuente_envios_whatsapp']) ? $config['fuente_envios_whatsapp'] : 'invitados';

        return dqs_guest_source_is_valid($source) ? $source : 'invitados';
    }
}
