<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($conn)) {
    include_once '../conexion.php';
}

require_once '../includes/plan_config.php';
require_once '../includes/site_settings_writer.php';
require_once 'section_text_settings.php';

if (!isset($conn)) {
    die('Error: La variable $conn no está definida en conexion.php');
}
if ($conn->connect_error) {
    die('Conexión fallida: ' . $conn->connect_error);
}

$mensaje = '';
$error = '';
$rsvp_text_fields = [
    'rsvp_section_header_visible' => ['type' => 'visible', 'label' => 'Mostrar cabecera de Confirmar Asistencia'],
    'rsvp_section_title' => ['type' => 'text', 'label' => 'Título de sección'],
    'rsvp_form_intro_title' => ['type' => 'text', 'label' => 'Título del formulario'],
    'rsvp_form_intro_subtitle' => ['type' => 'textarea', 'label' => 'Bajada del formulario'],
];
dqs_admin_save_section_text($conn, 'rsvp', $rsvp_text_fields, $mensaje, $error);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_rsvp_config'])) {
    $modoSolicitado = $_POST['rsvp_modo_admin'] ?? '';

    try {
        if ($modoSolicitado === 'codigo') {
            dqs_save_site_settings($conn, [
                'rsvp_modo' => 'codigo',
                'rsvp_form_persist_enabled' => '0',
            ]);
            $mensaje = 'Configuración actualizada. La confirmación vuelve a funcionar por código.';
        } elseif ($modoSolicitado === 'form') {
            $confirmado = isset($_POST['confirmar_formulario_real']) && $_POST['confirmar_formulario_real'] === '1';
            if (!$confirmado) {
                $error = 'Para activar el formulario web, confirmá que entendés que se guardarán respuestas reales.';
            } else {
                dqs_save_site_settings($conn, [
                    'rsvp_modo' => 'form',
                    'rsvp_form_persist_enabled' => '1',
                ]);
                $mensaje = 'Configuración actualizada. El formulario web quedó activo y guardará confirmaciones reales.';
            }
        } else {
            $error = 'Seleccioná un modo de confirmación válido.';
        }
    } catch (Throwable $e) {
        $error = 'No se pudo guardar la configuración RSVP.';
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_rsvp_form_options'])) {
    $adultCompanionsEnabled = ($_POST['rsvp_form_adult_companions_enabled'] ?? '0') === '1' ? '1' : '0';
    $minorsEnabled = ($_POST['rsvp_form_minors_enabled'] ?? '0') === '1' ? '1' : '0';
    $maxAdultCompanions = 0;
    $maxMinors = 0;

    if ($adultCompanionsEnabled === '1') {
        $maxAdultCompanions = filter_var($_POST['rsvp_form_max_adult_companions'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'max_range' => 20],
        ]);
    }
    if ($minorsEnabled === '1') {
        $maxMinors = filter_var($_POST['rsvp_form_max_minors'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'max_range' => 20],
        ]);
    }

    if ($maxAdultCompanions === false || $maxMinors === false) {
        $error = 'No se pudo guardar la configuración del formulario.';
    } else {
        try {
            dqs_save_site_settings($conn, [
                'rsvp_form_adult_companions_enabled' => $adultCompanionsEnabled,
                'rsvp_form_max_adult_companions' => (string)$maxAdultCompanions,
                'rsvp_form_minors_enabled' => $minorsEnabled,
                'rsvp_form_max_minors' => (string)$maxMinors,
                'rsvp_form_food_enabled' => ($_POST['rsvp_form_food_enabled'] ?? '0') === '1' ? '1' : '0',
                'rsvp_form_phone_visible' => ($_POST['rsvp_form_phone_visible'] ?? '0') === '1' ? '1' : '0',
                'rsvp_form_general_message_enabled' => ($_POST['rsvp_form_general_message_enabled'] ?? '0') === '1' ? '1' : '0',
            ]);
            $mensaje = 'Configuración del formulario actualizada correctamente.';
        } catch (Throwable $e) {
            $error = 'No se pudo guardar la configuración del formulario.';
        }
    }
}

$config = dqs_get_plan_config($conn);
$effectiveConfig = dqs_get_effective_plan_config($conn);
$planActual = $config['plan_servicio'];
$modoGuardado = $config['rsvp_modo'];
$persistenciaActiva = $config['rsvp_form_persist_enabled'] === '1';
$modoFuncional = ($effectiveConfig['rsvp_modo'] === 'form' && $persistenciaActiva)
    ? 'Confirmación por formulario web'
    : 'Confirmación por código';
$puedeElegir = dqs_can_cliente_choose_rsvp_mode($conn);
?>
<?php
$modoActualTexto = $modoFuncional === 'Confirmación por formulario web'
    ? 'Confirmación por formulario web'
    : 'Confirmación por código';
$guardadoRealTexto = $persistenciaActiva ? 'Activo' : 'Desactivado';
$estadoFuncionalTexto = ($modoFuncional === 'Confirmación por formulario web' && $persistenciaActiva)
    ? 'Formulario activo / Guarda confirmaciones reales'
    : 'Seguro / No escribe confirmaciones reales';
$modoCss = $modoActualTexto === 'Confirmación por formulario web' ? 'is-form' : 'is-code';
$guardadoCss = $persistenciaActiva ? 'is-warning' : 'is-muted';
$estadoCss = ($modoFuncional === 'Confirmación por formulario web' && $persistenciaActiva) ? 'is-warning' : 'is-safe';
?>
<style>
    .rsvp-config-admin { width: 100%; padding: 18px 0 42px; color: #273142; }
    .rsvp-config-admin * { box-sizing: border-box; }
    .rsvp-hero { margin: 0 0 24px; }
    .rsvp-hero h1 { margin: 0 0 7px; font-size: clamp(1.8rem, 4vw, 2.4rem); line-height: 1.15; color: #333; text-align: left; }
    .rsvp-hero p { margin: 0; color: #687386; font-size: 16px; line-height: 1.5; }
    .rsvp-card { background: #fff; border: 1px solid #e2e6ea; border-radius: 10px; padding: 24px; margin-bottom: 20px; }
    .rsvp-status-grid, .rsvp-options-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; }
    .rsvp-options-grid { grid-template-columns: repeat(3, minmax(0,1fr)); }
    .rsvp-status-item { background: #f9f9f9; border-radius: 8px; padding: 15px; border: 1px solid #e2e6ea; }
    .rsvp-label { display: block; color: #758094; font-size: 13px; margin-bottom: 8px; }
    .rsvp-value { font-weight: 700; color: #273142; }
    .rsvp-badge { display: inline-flex; align-items: center; gap: 6px; border: 1px solid transparent; border-radius: 6px; padding: 6px 9px; font-size: 13px; font-weight: 700; }
    .rsvp-badge.is-safe, .rsvp-badge.is-form { color: #25633b; background: #e8f3eb; border-color: #cce3d2; }
    .rsvp-badge.is-code, .rsvp-badge.is-muted { color: #465165; background: #f2f2f2; border-color: #dde1e5; }
    .rsvp-badge.is-warning { color: #765b1d; background: #f8f1df; border-color: #ead9aa; }
    .rsvp-section-title { margin: 0 0 6px; color: #273142; font-size: 22px; }
    .rsvp-section-help, .rsvp-help { color: #687386; line-height: 1.5; margin: 0 0 16px; }
    .rsvp-mode-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 16px; margin: 16px 0; }
    .rsvp-radio-card { position: relative; display: flex; gap: 12px; align-items: flex-start; min-height: 126px; padding: 20px; border: 1px solid #d9dee5; border-radius: 9px; background: #fff; cursor: pointer; transition: .18s ease; }
    .rsvp-radio-card:hover, .rsvp-radio-card:has(input:checked) { border-color: #555; background: #f7f8fa; box-shadow: 0 0 0 2px rgba(68,68,68,.08); }
    .rsvp-radio-card input { margin-top: 5px; }
    .rsvp-radio-card strong { display:block; color:#273142; margin-bottom: 7px; font-size: 17px; }
    .rsvp-confirm-box { border: 1px solid #ead9aa; background: #f8f1df; border-radius: 8px; padding: 14px 16px; margin: 12px 0 18px; color: #765b1d; }
    .rsvp-option-card { border: 1px solid #e2e6ea; border-radius: 9px; padding: 18px; background: #f9f9f9; }
    .rsvp-option-card h3 { margin: 0 0 8px; font-size: 18px; color: #273142; }
    .rsvp-field { margin-top: 14px; }
    .rsvp-field label { display:block; font-weight: 700; margin-bottom: 7px; color: #344054; }
    .rsvp-field select, .rsvp-field input[type="number"] { width: 100%; border: 1px solid #ccd2da; border-radius: 8px; padding: 10px 12px; background: #fff; min-height: 42px; color: #273142; }
    .rsvp-field select:focus, .rsvp-field input[type="number"]:focus { border-color: #555; outline: none; box-shadow: 0 0 0 3px rgba(68,68,68,.12); }
    .rsvp-preview-list { margin: 0; padding-left: 20px; color: #344054; line-height: 1.7; }
    .rsvp-actions { display:flex; gap: 12px; align-items:center; margin-top: 18px; }
    .rsvp-primary, .rsvp-secondary { min-height: 44px; border-radius: 9px; padding: 10px 17px; font: inherit; font-weight: 700; text-decoration: none; border: 1px solid transparent; cursor: pointer; }
    .rsvp-primary { background: #444; color: #fff; }
    .rsvp-primary:hover { background: #555; }
    .rsvp-secondary { border-color: #d9dee5; background: #fff; color: #465165; display:inline-flex; align-items: center; }
    .rsvp-secondary:hover { background: #f7f8fa; color: #273142; text-decoration: none; }
    @media (max-width: 900px) { .rsvp-status-grid, .rsvp-options-grid, .rsvp-mode-grid { grid-template-columns: 1fr; } }
    @media (max-width: 560px) { .rsvp-config-admin { padding: 12px 0 36px; } .rsvp-card { padding: 19px 16px; } .rsvp-actions { flex-direction: column; align-items: stretch; } .rsvp-primary, .rsvp-secondary { width: 100%; justify-content: center; text-align:center; } }
</style>
<section class="rsvp-config-admin">
    <div class="rsvp-hero">
        <h1>Configuración RSVP</h1>
        <p>Definí cómo van a confirmar asistencia tus invitados.</p>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php dqs_admin_render_section_text('rsvp', 'Textos públicos de Confirmar Asistencia', $rsvp_text_fields, $config); ?>

    <div class="rsvp-card" aria-label="Estado actual de RSVP">
        <h2 class="rsvp-section-title">Estado actual</h2>
        <p class="rsvp-section-help">Resumen rápido de la configuración que está funcionando ahora.</p>
        <div class="rsvp-status-grid">
            <div class="rsvp-status-item"><span class="rsvp-label">Plan actual</span><span class="rsvp-value"><?php echo htmlspecialchars(ucfirst($planActual)); ?></span></div>
            <div class="rsvp-status-item"><span class="rsvp-label">Modo actual</span><span class="rsvp-badge <?php echo $modoCss; ?>"><?php echo htmlspecialchars($modoActualTexto); ?></span></div>
            <div class="rsvp-status-item"><span class="rsvp-label">Guardado real</span><span class="rsvp-badge <?php echo $guardadoCss; ?>"><?php echo htmlspecialchars($guardadoRealTexto); ?></span></div>
            <div class="rsvp-status-item"><span class="rsvp-label">Estado funcional</span><span class="rsvp-badge <?php echo $estadoCss; ?>"><?php echo htmlspecialchars($estadoFuncionalTexto); ?></span></div>
        </div>
    </div>

    <?php if ($planActual === 'basico'): ?>
        <div class="alert alert-info">El plan Básico usa formulario web. El plan de servicio no se cambia desde esta pantalla.</div>
    <?php endif; ?>

    <?php if ($puedeElegir): ?>
        <form method="post" action="index.php?new=rsvp_config" class="rsvp-config-form rsvp-card" id="rsvpModeForm">
            <h2 class="rsvp-section-title">Modo de confirmación</h2>
            <p class="rsvp-section-help">Elegí si tus invitados confirman con código o completando el formulario web.</p>

            <div class="rsvp-mode-grid">
                <label class="rsvp-radio-card">
                    <input type="radio" name="rsvp_modo_admin" value="codigo" <?php echo $modoFuncional === 'Confirmación por código' ? 'checked' : ''; ?>>
                    <span><strong>Confirmación por código</strong>Los invitados confirman usando su código de invitación.</span>
                </label>
                <label class="rsvp-radio-card">
                    <input type="radio" name="rsvp_modo_admin" value="form" <?php echo $modoFuncional === 'Confirmación por formulario web' ? 'checked' : ''; ?>>
                    <span><strong>Formulario web</strong>Los invitados completan un formulario y las respuestas se guardan en la lista de invitados.</span>
                </label>
            </div>

            <label class="rsvp-confirm-box" id="rsvpRealConfirmBox">
                <input type="checkbox" name="confirmar_formulario_real" value="1">
                Entiendo que el formulario web guardará confirmaciones reales en la lista de invitados.
            </label>

            <div class="rsvp-actions">
                <button class="rsvp-primary" type="submit" name="guardar_rsvp_config" value="1">Guardar configuración RSVP</button>
                <a class="rsvp-secondary" href="index.php">Volver al panel</a>
            </div>
        </form>
    <?php else: ?>
        <div class="rsvp-card"><p>Este plan no permite cambiar el modo RSVP desde esta pantalla.</p><a class="rsvp-secondary" href="index.php">Volver al panel</a></div>
    <?php endif; ?>

    <form method="post" action="index.php?new=rsvp_config" class="rsvp-config-form rsvp-card" id="rsvpOptionsForm">
        <h2 class="rsvp-section-title">Opciones del formulario</h2>
        <p class="rsvp-section-help">Estos cambios definen qué campos podrá ver el invitado cuando el RSVP por formulario esté activo.</p>

        <div class="rsvp-options-grid">
            <div class="rsvp-option-card">
                <h3>Acompañantes adultos</h3>
                <p class="rsvp-help">Define cuántos adultos adicionales puede cargar cada invitado.</p>
                <div class="rsvp-field"><label for="rsvp_form_adult_companions_enabled">Permitir acompañantes adultos</label><select id="rsvp_form_adult_companions_enabled" name="rsvp_form_adult_companions_enabled"><option value="1" <?php echo $config['rsvp_form_adult_companions_enabled'] === '1' ? 'selected' : ''; ?>>Sí</option><option value="0" <?php echo $config['rsvp_form_adult_companions_enabled'] === '0' ? 'selected' : ''; ?>>No</option></select></div>
                <div class="rsvp-field"><label for="rsvp_form_max_adult_companions">Límite de acompañantes adultos</label><input type="number" id="rsvp_form_max_adult_companions" name="rsvp_form_max_adult_companions" min="0" max="20" step="1" value="<?php echo htmlspecialchars($config['rsvp_form_max_adult_companions']); ?>"></div>
            </div>
            <div class="rsvp-option-card">
                <h3>Menores</h3>
                <p class="rsvp-help">Define si el invitado puede cargar menores y cuántos.</p>
                <div class="rsvp-field"><label for="rsvp_form_minors_enabled">Permitir menores</label><select id="rsvp_form_minors_enabled" name="rsvp_form_minors_enabled"><option value="1" <?php echo $config['rsvp_form_minors_enabled'] === '1' ? 'selected' : ''; ?>>Sí</option><option value="0" <?php echo $config['rsvp_form_minors_enabled'] === '0' ? 'selected' : ''; ?>>No</option></select></div>
                <div class="rsvp-field"><label for="rsvp_form_max_minors">Límite de menores</label><input type="number" id="rsvp_form_max_minors" name="rsvp_form_max_minors" min="0" max="20" step="1" value="<?php echo htmlspecialchars($config['rsvp_form_max_minors']); ?>"></div>
            </div>
            <div class="rsvp-option-card">
                <h3>Datos adicionales</h3>
                <p class="rsvp-help">Elegí qué información extra se solicita o muestra al invitado.</p>
                <div class="rsvp-field"><label for="rsvp_form_food_enabled">Restricción alimentaria por persona</label><select id="rsvp_form_food_enabled" name="rsvp_form_food_enabled"><option value="1" <?php echo $config['rsvp_form_food_enabled'] === '1' ? 'selected' : ''; ?>>Sí</option><option value="0" <?php echo $config['rsvp_form_food_enabled'] === '0' ? 'selected' : ''; ?>>No</option></select><p class="rsvp-help">Permite registrar necesidades alimentarias de cada persona confirmada.</p></div>
                <div class="rsvp-field"><label for="rsvp_form_phone_visible">Mostrar teléfono</label><select id="rsvp_form_phone_visible" name="rsvp_form_phone_visible"><option value="0" <?php echo $config['rsvp_form_phone_visible'] === '0' ? 'selected' : ''; ?>>No</option><option value="1" <?php echo $config['rsvp_form_phone_visible'] === '1' ? 'selected' : ''; ?>>Sí</option></select><p class="rsvp-help">Muestra u oculta el pedido de teléfono de contacto.</p></div>
                <div class="rsvp-field"><label for="rsvp_form_general_message_enabled">Mostrar mensaje general</label><select id="rsvp_form_general_message_enabled" name="rsvp_form_general_message_enabled"><option value="0" <?php echo $config['rsvp_form_general_message_enabled'] === '0' ? 'selected' : ''; ?>>No</option><option value="1" <?php echo $config['rsvp_form_general_message_enabled'] === '1' ? 'selected' : ''; ?>>Sí</option></select><p class="rsvp-help">Permite mostrar un espacio para comentarios generales.</p></div>
            </div>
        </div>

        <div class="rsvp-card" style="box-shadow:none;margin:20px 0 0;background:#f8fafc;">
            <h2 class="rsvp-section-title">Resumen de cómo se verá el formulario</h2>
            <ul class="rsvp-preview-list" id="rsvpPreviewList"></ul>
        </div>

        <div class="rsvp-actions">
            <button class="rsvp-primary" type="submit" name="guardar_rsvp_form_options" value="1">Guardar configuración RSVP</button>
            <a class="rsvp-secondary" href="index.php">Volver al panel</a>
        </div>
    </form>
</section>
<script>
(function () {
    const byId = (id) => document.getElementById(id);
    const adultEnabled = byId('rsvp_form_adult_companions_enabled');
    const adultLimit = byId('rsvp_form_max_adult_companions');
    const minorsEnabled = byId('rsvp_form_minors_enabled');
    const minorsLimit = byId('rsvp_form_max_minors');
    const food = byId('rsvp_form_food_enabled');
    const phone = byId('rsvp_form_phone_visible');
    const message = byId('rsvp_form_general_message_enabled');
    const preview = byId('rsvpPreviewList');
    const confirmBox = byId('rsvpRealConfirmBox');
    function plural(n, one, many) { return Number(n) === 1 ? one : many; }
    function toggleLimits() {
        adultLimit.disabled = adultEnabled.value !== '1';
        minorsLimit.disabled = minorsEnabled.value !== '1';
    }
    function updatePreview() {
        const items = [];
        if (adultEnabled.value === '1' && Number(adultLimit.value) > 0) items.push('El invitado podrá cargar hasta ' + adultLimit.value + ' ' + plural(adultLimit.value, 'acompañante adulto.', 'acompañantes adultos.'));
        else items.push('El invitado podrá confirmar solo por sí mismo.');
        if (minorsEnabled.value === '1' && Number(minorsLimit.value) > 0) items.push('El invitado podrá cargar hasta ' + minorsLimit.value + ' ' + plural(minorsLimit.value, 'menor.', 'menores.'));
        else items.push('No se permitirá cargar menores.');
        items.push(food.value === '1' ? 'Se solicitará restricción alimentaria por persona.' : 'No se solicitará restricción alimentaria por persona.');
        items.push(phone.value === '1' ? 'Se solicitará teléfono.' : 'No se solicitará teléfono.');
        items.push(message.value === '1' ? 'Se mostrará mensaje general.' : 'No se mostrará mensaje general.');
        preview.innerHTML = items.map((item) => '<li>' + item + '</li>').join('');
    }
    function updateConfirmVisibility() {
        const selected = document.querySelector('input[name="rsvp_modo_admin"]:checked');
        if (confirmBox) confirmBox.style.display = selected && selected.value === 'form' ? 'block' : 'none';
    }
    [adultEnabled, adultLimit, minorsEnabled, minorsLimit, food, phone, message].forEach((el) => el && el.addEventListener('input', function () { toggleLimits(); updatePreview(); }));
    document.querySelectorAll('input[name="rsvp_modo_admin"]').forEach((el) => el.addEventListener('change', updateConfirmVisibility));
    toggleLimits();
    updatePreview();
    updateConfirmVisibility();
}());
</script>
