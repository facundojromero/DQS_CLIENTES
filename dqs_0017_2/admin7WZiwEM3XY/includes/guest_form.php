<?php
/** Vista única usada por Nuevo Invitado y Cargar contacto de envío. */
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$formInitial = is_array($formInitial ?? null) ? $formInitial : [];
$initial = static fn(string $key, $default = '') => $formInitial[$key] ?? $default;
?>
<div class="guest-form-page">
 <header class="admin-page-header guest-form-header"><div><h1><?= $h($formTitle) ?></h1><?php if ($formNotice !== ''): ?><p class="admin-page-subtitle"><?= $h($formNotice) ?></p><?php endif; ?></div></header>
 <?php if ($formMessage !== ''): ?><div class="admin-message <?= str_contains($formMessage, 'Error') || str_contains($formMessage, 'posible') ? 'admin-message-error' : '' ?>" role="alert"><i class="fas fa-exclamation-circle" aria-hidden="true"></i><p><?= $h($formMessage) ?></p></div><?php endif; ?>
 <form method="post" action="<?= $h($formAction) ?>" class="formulario" onsubmit="return validarFormulario()">
  <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
  <?php foreach (($formHidden ?? []) as $key=>$value): ?><input type="hidden" name="<?= $h($key) ?>" value="<?= $h($value) ?>"><?php endforeach; ?>
  <div class="form-group"><label for="nombre">Nombre:</label><input type="text" id="nombre" name="nombre" value="<?= $h($initial('nombre')) ?>" required></div>
  <div class="form-group"><label for="apellido">Apellido:</label><input type="text" id="apellido" name="apellido" value="<?= $h($initial('apellido')) ?>" required></div>
  <div class="form-group"><label for="nombre_invitado">Apodo del invitado:</label><input type="text" id="nombre_invitado" name="nombre_invitado" value="<?= $h($initial('nombre_invitado')) ?>" placeholder="Ej: Fede"></div>
  <div class="form-group"><label><input type="checkbox" name="titular_es_menor" value="1" <?= (int)$initial('titular_es_menor',0)===1?'checked':'' ?>> Titular es menor</label></div>
  <div class="form-group"><label for="tel_enviar">Teléfono:</label><input type="text" id="tel_enviar" name="telefonos[]" value="<?= $h(($initial('telefonos',[])[0]??'')) ?>" placeholder="Sin 0 y sin 15" maxlength="10" required></div>
  <div class="form-group"><label for="acompanado">Acompañado:</label><select id="acompanado" name="acompanado" required><?php foreach($acompananteOpciones as $o): ?><option value="<?= $h($o['id']) ?>" <?= (string)$initial('acompanado')===(string)$o['id']?'selected':'' ?>><?= $h($o['categoria_acompanante']) ?></option><?php endforeach; ?></select></div>
  <div class="form-group"><label for="cantidad_mayores">Cantidad de Mayores:</label><input type="number" id="cantidad_mayores" name="cantidad_mayores" value="<?= $h($initial('cantidad_mayores',1)) ?>" required min="0"></div>
  <div class="form-group"><label for="cantidad_menores">Cantidad de Menores:</label><input type="number" id="cantidad_menores" name="cantidad_menores" value="<?= $h($initial('cantidad_menores',0)) ?>" required min="0"></div>
  <div class="form-group"><label for="ingreso">Ingreso:</label><select id="ingreso" name="ingreso" required><option value="Inicio" <?= $initial('ingreso','Inicio')==='Inicio'?'selected':'' ?>>Inicio</option><option value="Tarde" <?= $initial('ingreso')==='Tarde'?'selected':'' ?>>Tarde</option></select></div>
  <div class="form-group"><label for="id_prioridad">Prioridad:</label><select id="id_prioridad" name="id_prioridad" required><?php foreach($prioridadOpciones as $o): ?><option value="<?= $h($o['id']) ?>" <?= (string)$initial('id_prioridad')===(string)$o['id']?'selected':'' ?>><?= $h($o['categoria_prioridad']) ?></option><?php endforeach; ?></select></div>
  <section id="acompanante-container" aria-labelledby="acompanantes-title"><h2 id="acompanantes-title"><i class="fas fa-users" aria-hidden="true"></i> Acompañantes</h2><div class="compartido-labels"><p>Nombre</p><p>Apellido</p><p>Apodo</p><p>Teléfono</p><p>Menor</p></div><button type="button" id="addAcompanante" class="navbar-link admin-action-compact"><i class="fas fa-plus" aria-hidden="true"></i> Agregar acompañante</button></section>
  <div class="confirmacion-body"><button type="submit" class="navbar-link"><i class="fas fa-save" aria-hidden="true"></i> Guardar</button><a class="navbar-link admin-secondary-action" href="<?= $h($cancelUrl) ?>"><i class="fas fa-times" aria-hidden="true"></i> Cancelar</a></div>
 </form>
</div>
<script>
function agregarAcompanante(d){d=d||{};var esc=function(v){var x=document.createElement('div');x.textContent=v||'';return x.innerHTML};var row=document.createElement('div');row.className='acompanante-row';row.innerHTML='<div class="form-group"><label>Nombre</label><input type="text" name="acompanante_nombre[]" value="'+esc(d.nombre)+'" placeholder="Nombre"></div><div class="form-group"><label>Apellido</label><input type="text" name="acompanante_apellido[]" value="'+esc(d.apellido)+'" placeholder="Apellido"></div><div class="form-group"><label>Apodo</label><input type="text" name="acompanante_apodo[]" value="'+esc(d.apodo)+'" placeholder="Apodo"></div><div class="form-group"><label>Teléfono</label><input type="text" name="telefonos[]" value="'+esc(d.telefono)+'" placeholder="Teléfono" maxlength="10"></div><div class="form-group"><label>Menor</label><select name="acompanante_es_menor[]"><option value="0">Mayor</option><option value="1" '+(Number(d.es_menor)===1?'selected':'')+'>Menor</option></select></div>';var c=document.getElementById('acompanante-container');c.insertBefore(row,document.getElementById('addAcompanante'))}
document.getElementById('addAcompanante').addEventListener('click',function(){agregarAcompanante({})});
<?= '('.json_encode(array_values($initial('acompanantes',[])), JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).').forEach(agregarAcompanante);' ?>
function validarFormulario(){var total=(parseInt(document.getElementById('cantidad_mayores').value)||0)+(parseInt(document.getElementById('cantidad_menores').value)||0),nombres=1;document.querySelectorAll('input[name="acompanante_nombre[]"]').forEach(function(i){if(i.value.trim())nombres++});if(total!==nombres){alert('La suma de mayores y menores debe coincidir con el total de personas (titular + acompañantes).');return false}return true}
</script>
