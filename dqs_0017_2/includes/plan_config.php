<?php
/**
 * Helper central para leer la configuración de plan/flujo de DQS.
 *
 * UNI-001: esta capa solo lee configuración y aplica defaults en memoria.
 * UNI-002: agrega configuración efectiva por plan/rol sin aplicar cambios visibles.
 * No crea tablas, no inserta valores y no modifica el comportamiento visible.
 */

const DQS_PLAN_CONFIG_DEFAULTS = [
    'plan_servicio' => 'oro',
    'rsvp_modo' => 'codigo',
    'fuente_envios_whatsapp' => 'invitados',
    'whatsapp_enabled' => '1',
    'regalos_enabled' => '1',
    'rsvp_form_persist_enabled' => '0',
    'rsvp_form_adult_companions_enabled' => '1',
    'rsvp_form_max_adult_companions' => '1',
    'rsvp_form_minors_enabled' => '0',
    'rsvp_form_max_minors' => '0',
    'rsvp_form_food_enabled' => '1',
    'rsvp_form_phone_visible' => '0',
    'rsvp_form_general_message_enabled' => '0',
    'historia_section_title' => 'Nuestra Historia',
    'historia_section_subtitle' => 'Desde el primer encuentro hasta el compromiso, nuestra historia está llena de momentos inolvidables y amor verdadero.',
    'historia_section_header_visible' => '1',
    'eventos_section_title' => 'Eventos',
    'eventos_section_subtitle' => "Estamos muy felices y queremos compartir este día con vos!\nTe dejamos toda la información de nuestro casamiento, para que nos acompañes en este gran día!",
    'eventos_section_header_visible' => '1',
    'mas_info_section_title' => 'Más Info',
    'mas_info_section_subtitle' => 'Descubre más sobre nuestro evento.',
    'mas_info_section_header_visible' => '1',
    'contacto_section_title' => 'Contactar con nosotros',
    'contacto_section_subtitle' => 'Si quieres enviarnos un mensaje.',
    'contacto_section_header_visible' => '1',
    'rsvp_section_title' => 'Confirmar Asistencia',
    'rsvp_section_header_visible' => '1',
    'rsvp_form_intro_title' => 'Confirmar asistencia',
    'rsvp_form_intro_subtitle' => 'Completá tus datos para confirmar tu asistencia.',
];

const DQS_PLAN_CONFIG_RANGE_0_20 = [
    '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10',
    '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
];

const DQS_PLAN_CONFIG_ALLOWED_VALUES = [
    'plan_servicio' => ['basico', 'oro'],
    'rsvp_modo' => ['codigo', 'form'],
    'fuente_envios_whatsapp' => ['ninguno', 'invitados', 'pre_invitados'],
    'whatsapp_enabled' => ['0', '1'],
    'regalos_enabled' => ['0', '1'],
    'rsvp_form_persist_enabled' => ['0', '1'],
    'rsvp_form_adult_companions_enabled' => ['0', '1'],
    'rsvp_form_max_adult_companions' => DQS_PLAN_CONFIG_RANGE_0_20,
    'rsvp_form_minors_enabled' => ['0', '1'],
    'rsvp_form_max_minors' => DQS_PLAN_CONFIG_RANGE_0_20,
    'rsvp_form_food_enabled' => ['0', '1'],
    'rsvp_form_phone_visible' => ['0', '1'],
    'rsvp_form_general_message_enabled' => ['0', '1'],
    'historia_section_header_visible' => ['0', '1'],
    'eventos_section_header_visible' => ['0', '1'],
    'mas_info_section_header_visible' => ['0', '1'],
    'contacto_section_header_visible' => ['0', '1'],
    'rsvp_section_header_visible' => ['0', '1'],
];

const DQS_PLAN_CONFIG_TEXT_MAX_LENGTHS = [
    'historia_section_title' => 255,
    'historia_section_subtitle' => 2000,
    'eventos_section_title' => 255,
    'eventos_section_subtitle' => 2000,
    'mas_info_section_title' => 255,
    'mas_info_section_subtitle' => 2000,
    'contacto_section_title' => 255,
    'contacto_section_subtitle' => 2000,
    'rsvp_section_title' => 255,
    'rsvp_form_intro_title' => 255,
    'rsvp_form_intro_subtitle' => 2000,
];

/**
 * Devuelve la configuración completa de plan/flujo.
 *
 * Si existe la tabla site_settings y contiene alguna de las claves soportadas,
 * usa esos valores. Si no existe configuración guardada, devuelve los defaults
 * definidos en memoria sin persistirlos en base.
 *
 * @param mysqli|null $conn Conexión activa opcional. Si se omite, usa $GLOBALS['conn'].
 * @return array<string,string>
 */
function dqs_get_plan_config($conn = null)
{
    $config = DQS_PLAN_CONFIG_DEFAULTS;
    $db = $conn ?: ($GLOBALS['conn'] ?? null);

    if (!$db instanceof mysqli) {
        return $config;
    }

    $keys = array_keys(DQS_PLAN_CONFIG_DEFAULTS);
    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $sql = "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ($placeholders)";

    $statement = @$db->prepare($sql);
    if (!$statement) {
        return $config;
    }

    $types = str_repeat('s', count($keys));
    $statement->bind_param($types, ...$keys);

    if (!$statement->execute()) {
        $statement->close();
        return $config;
    }

    $result = $statement->get_result();
    if (!$result) {
        $statement->close();
        return $config;
    }

    while ($row = $result->fetch_assoc()) {
        $key = $row['setting_key'] ?? '';
        $value = (string)($row['setting_value'] ?? '');

        if (dqs_is_valid_plan_config_value($key, $value)) {
            $config[$key] = $value;
        }
    }

    $statement->close();

    return $config;
}

/**
 * Devuelve una clave puntual de configuración con validación y fallback.
 *
 * @param string $key Nombre de clave soportada.
 * @param mysqli|null $conn Conexión activa opcional. Si se omite, usa $GLOBALS['conn'].
 * @return string|null Valor configurado/default, o null si la clave no existe.
 */
function dqs_get_plan_config_value($key, $conn = null)
{
    if (!array_key_exists($key, DQS_PLAN_CONFIG_DEFAULTS)) {
        return null;
    }

    $config = dqs_get_plan_config($conn);

    return $config[$key];
}

/**
 * Devuelve la configuración efectiva aplicando reglas comerciales por plan/rol.
 *
 * Esta función no persiste valores ni cambia pantallas. Solo normaliza en memoria:
 * - plan básico fuerza RSVP por formulario.
 * - WhatsApp deshabilitado fuerza fuente de envíos a ninguno.
 * - plan oro conserva el modo RSVP configurado y permite elección futura del cliente.
 *
 * @param mysqli|null $conn Conexión activa opcional. Si se omite, usa $GLOBALS['conn'].
 * @return array<string,string>
 */
function dqs_get_effective_plan_config($conn = null)
{
    $config = dqs_get_plan_config($conn);

    if ($config['plan_servicio'] === 'basico') {
        $config['rsvp_modo'] = 'form';
    }

    if ($config['whatsapp_enabled'] === '0') {
        $config['fuente_envios_whatsapp'] = 'ninguno';
    }

    return $config;
}

/**
 * Devuelve el modo RSVP efectivo según el plan comercial.
 *
 * @param mysqli|null $conn Conexión activa opcional. Si se omite, usa $GLOBALS['conn'].
 * @return string codigo|form
 */
function dqs_get_effective_rsvp_modo($conn = null)
{
    $config = dqs_get_effective_plan_config($conn);

    return $config['rsvp_modo'];
}

/**
 * Indica si el plan configurado es Básico.
 *
 * @param mysqli|null $conn Conexión activa opcional. Si se omite, usa $GLOBALS['conn'].
 * @return bool
 */
function dqs_is_plan_basico($conn = null)
{
    return dqs_get_plan_config_value('plan_servicio', $conn) === 'basico';
}

/**
 * Indica si el plan configurado es Oro.
 *
 * @param mysqli|null $conn Conexión activa opcional. Si se omite, usa $GLOBALS['conn'].
 * @return bool
 */
function dqs_is_plan_oro($conn = null)
{
    return dqs_get_plan_config_value('plan_servicio', $conn) === 'oro';
}

/**
 * Indica si el cliente/admin puede elegir modo RSVP.
 *
 * Por ahora solo plan Oro puede elegir entre codigo y form en PRs futuros.
 * Plan Básico queda fijo en form.
 *
 * @param mysqli|null $conn Conexión activa opcional. Si se omite, usa $GLOBALS['conn'].
 * @return bool
 */
function dqs_can_cliente_choose_rsvp_mode($conn = null)
{
    return dqs_is_plan_oro($conn);
}

/**
 * Indica si WhatsApp está habilitado en la configuración comercial.
 *
 * @param mysqli|null $conn Conexión activa opcional. Si se omite, usa $GLOBALS['conn'].
 * @return bool
 */
function dqs_is_whatsapp_enabled($conn = null)
{
    return dqs_get_plan_config_value('whatsapp_enabled', $conn) === '1';
}

/**
 * Indica si regalos está habilitado en la configuración comercial.
 *
 * @param mysqli|null $conn Conexión activa opcional. Si se omite, usa $GLOBALS['conn'].
 * @return bool
 */
function dqs_is_regalos_enabled($conn = null)
{
    return dqs_get_plan_config_value('regalos_enabled', $conn) === '1';
}

/**
 * Valida si un valor pertenece al dominio permitido de una clave.
 *
 * @param string $key Nombre de clave.
 * @param string $value Valor a validar.
 * @return bool
 */
function dqs_is_valid_plan_config_value($key, $value)
{
    if (array_key_exists($key, DQS_PLAN_CONFIG_TEXT_MAX_LENGTHS)) {
        return mb_strlen($value) <= DQS_PLAN_CONFIG_TEXT_MAX_LENGTHS[$key];
    }

    return array_key_exists($key, DQS_PLAN_CONFIG_ALLOWED_VALUES)
        && in_array($value, DQS_PLAN_CONFIG_ALLOWED_VALUES[$key], true);
}
