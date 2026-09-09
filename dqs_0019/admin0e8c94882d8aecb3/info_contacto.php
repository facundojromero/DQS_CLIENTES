<?php
require_once 'section_text_settings.php';
$mensaje = '';
$error = '';
$contacto_text_fields = [
    'contacto_section_header_visible' => ['type' => 'visible', 'label' => 'Mostrar título y bajada de la sección'],
    'contacto_section_title' => ['type' => 'text', 'label' => 'Título de sección'],
    'contacto_section_subtitle' => ['type' => 'textarea', 'label' => 'Bajada / descripción'],
];
dqs_admin_save_section_text($conn, 'contacto', $contacto_text_fields, $mensaje, $error);
$config = dqs_get_plan_config($conn);
?>
<h1>Contactar</h1>
<?php if ($mensaje): ?><div class="alert"><p><?php echo htmlspecialchars($mensaje); ?></p></div><?php endif; ?>
<?php if ($error): ?><div class="alert"><p><?php echo htmlspecialchars($error); ?></p></div><?php endif; ?>
<?php dqs_admin_render_section_text('contacto', 'Texto de la sección Contactar', $contacto_text_fields, $config); ?>
