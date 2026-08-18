<?php
require_once '../includes/site_settings_writer.php';

function dqs_admin_save_section_text(mysqli $conn, string $section, array $fields, string &$mensaje, string &$error): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['guardar_texto_' . $section])) {
        return;
    }
    $changes = [];
    foreach ($fields as $key => $field) {
        $type = is_array($field) ? ($field['type'] ?? '') : $field;
        if ($type === 'visible') {
            $changes[$key] = ($_POST[$key] ?? '0') === '1' ? '1' : '0';
            continue;
        }
        $value = trim(strip_tags((string)($_POST[$key] ?? '')));
        $changes[$key] = str_replace(["\r\n", "\r"], "\n", $value);
    }
    try {
        dqs_save_site_settings($conn, $changes);
        $mensaje = 'Texto de la sección actualizado correctamente.';
    } catch (Throwable $exception) {
        $error = 'No se pudo guardar el texto de la sección.';
    }
}

function dqs_admin_render_section_text(string $section, string $heading, array $fields, array $config): void
{
    static $stylesRendered = false;
    if (!$stylesRendered) {
        $stylesRendered = true;
        ?>
        <style>
            .section-text-card { background:#fff; border:1px solid #e2e6ea; border-radius:10px; padding:24px; margin:20px 0 30px; color:#273142; box-shadow:0 3px 12px rgba(39,49,66,.04); }
            .section-text-card * { box-sizing:border-box; }
            .section-text-card__header { border-bottom:1px solid #edf0f3; margin-bottom:22px; padding-bottom:16px; }
            .section-text-card__header h2 { color:#273142; font-size:22px; margin:0 0 6px; text-align:left; }
            .section-text-card__header p { color:#687386; line-height:1.5; margin:0; }
            .section-text-card__grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; }
            .section-text-field { margin:0; }
            .section-text-field--wide { grid-column:1 / -1; }
            .section-text-field > label:first-child { color:#344054; display:block; font-weight:700; margin-bottom:8px; }
            .section-text-field input[type="text"], .section-text-field textarea { background:#fff; border:1px solid #ccd2da; border-radius:8px; color:#273142; font:inherit; padding:11px 12px; width:100%; }
            .section-text-field textarea { min-height:110px; resize:vertical; }
            .section-text-field input:focus, .section-text-field textarea:focus { border-color:#555; box-shadow:0 0 0 3px rgba(68,68,68,.12); outline:none; }
            .section-text-choices { display:flex; gap:12px; }
            .section-text-choice { align-items:center; background:#f9f9f9; border:1px solid #e2e6ea; border-radius:8px; cursor:pointer; display:flex; gap:8px; min-width:100px; padding:11px 14px; }
            .section-text-choice input { margin:0; }
            .section-text-card__actions { border-top:1px solid #edf0f3; margin-top:22px; padding-top:18px; }
            .section-text-card__submit { background:#444; border:1px solid #444; border-radius:9px; color:#fff; cursor:pointer; font:inherit; font-weight:700; min-height:44px; padding:10px 18px; }
            .section-text-card__submit:hover { background:#555; }
            @media (max-width:700px) { .section-text-card { padding:19px 16px; } .section-text-card__grid { grid-template-columns:1fr; } .section-text-field--wide { grid-column:auto; } .section-text-card__submit { width:100%; } }
        </style>
        <?php
    }
    ?>
    <section class="section-text-card">
        <div class="section-text-card__header">
            <h2><?php echo htmlspecialchars($heading, ENT_QUOTES, 'UTF-8'); ?></h2>
            <p>Personalizá los textos que se muestran en la invitación pública.</p>
        </div>
        <form method="post" action="">
            <div class="section-text-card__grid">
            <?php foreach ($fields as $key => $field): ?>
                <div class="section-text-field <?php echo $field['type'] === 'textarea' ? 'section-text-field--wide' : ''; ?>">
                    <label for="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($field['label']); ?>:</label>
                    <?php if ($field['type'] === 'visible'): ?>
                        <div class="section-text-choices">
                            <label class="section-text-choice"><input id="<?php echo htmlspecialchars($key); ?>" type="radio" name="<?php echo htmlspecialchars($key); ?>" value="1" <?php echo $config[$key] === '1' ? 'checked' : ''; ?>> Sí</label>
                            <label class="section-text-choice"><input type="radio" name="<?php echo htmlspecialchars($key); ?>" value="0" <?php echo $config[$key] === '0' ? 'checked' : ''; ?>> No</label>
                        </div>
                    <?php elseif ($field['type'] === 'textarea'): ?>
                        <textarea id="<?php echo htmlspecialchars($key); ?>" name="<?php echo htmlspecialchars($key); ?>" maxlength="2000"><?php echo htmlspecialchars($config[$key], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <?php else: ?>
                        <input id="<?php echo htmlspecialchars($key); ?>" type="text" name="<?php echo htmlspecialchars($key); ?>" maxlength="255" value="<?php echo htmlspecialchars($config[$key], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
            <div class="section-text-card__actions"><button class="section-text-card__submit" type="submit" name="guardar_texto_<?php echo htmlspecialchars($section); ?>" value="1">Guardar textos</button></div>
        </form>
    </section>
    <?php
}
