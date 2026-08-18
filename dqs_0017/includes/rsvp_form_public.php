<?php
/**
 * UNI-033: formulario público RSVP unificado y configurable.
 *
 * Renderiza el formulario público usando la configuración admin efectiva.
 * No persiste datos; el envío queda delegado al endpoint público.
 */

require_once __DIR__ . '/plan_config.php';
require_once __DIR__ . '/rsvp_form_contract.php';

$dqsRsvpConfig = dqs_get_effective_plan_config($GLOBALS['conn'] ?? null);
$dqsRsvpLimits = dqs_rsvp_form_effective_limits($dqsRsvpConfig);
$dqsRsvpPublicConfig = [
    'adultCompanionsEnabled' => (bool)$dqsRsvpLimits['adult_companions_enabled'],
    'maxAdultCompanions' => (int)$dqsRsvpLimits['max_adult_companions'],
    'maxAdults' => (int)$dqsRsvpLimits['max_adults'],
    'minorsEnabled' => (bool)$dqsRsvpLimits['minors_enabled'],
    'maxMinors' => (int)$dqsRsvpLimits['max_minors'],
    'foodEnabled' => (bool)$dqsRsvpLimits['food_enabled'],
    'phoneVisible' => (bool)$dqsRsvpLimits['phone_visible'],
    'generalMessageEnabled' => (bool)$dqsRsvpLimits['general_message_enabled'],
];
?>
<style>
    .dqs-rsvp-form-public .dqs-rsvp-section-title,
    #rsvpFormPublicModal .dqs-rsvp-section-title { font-size: 0.82rem; letter-spacing: 1px; text-transform: uppercase; color: #555; margin: 22px 0 14px; font-weight: 700; }
    #rsvpFormPublicModal .dqs-rsvp-soft-card { border: 1px solid rgba(0,0,0,.08); border-radius: 14px; background: #fff; box-shadow: 0 8px 24px rgba(0,0,0,.04); margin-bottom: 16px; }
    #rsvpFormPublicModal .dqs-rsvp-soft-card .card-body { padding: 18px; }
    #rsvpFormPublicModal .dqs-rsvp-required { color: #9b6750; }
    #rsvpFormPublicModal .dqs-rsvp-hidden { display: none !important; }
    #rsvpFormPublicModal .form-control[aria-invalid="true"] { border-color: #b94a48; }
    #rsvpFormPublicModal .dqs-rsvp-help { font-size: .86rem; color: #777; margin-top: 4px; }
    @media (max-width: 575.98px) { #rsvpFormPublicModal .modal-body { padding: 18px; } #rsvpFormPublicModal .modal-footer { display: block; } #rsvpFormPublicModal .modal-footer .btn { width: 100%; margin: 6px 0; } }
</style>

<div class="dqs-rsvp-form-public" style="max-width: 760px; margin: 0 auto;">
    <div class="text-center mb-4">
        <?php if ($dqsRsvpConfig['rsvp_form_intro_title'] !== ''): ?><h3 style="font-family: 'the-seasons-regular', serif; letter-spacing: 2px; color: #333;"><?php echo htmlspecialchars($dqsRsvpConfig['rsvp_form_intro_title'], ENT_QUOTES, 'UTF-8'); ?></h3><?php endif; ?>
        <?php if ($dqsRsvpConfig['rsvp_form_intro_subtitle'] !== ''): ?><p class="text-muted" style="margin-bottom: 25px;"><?php echo nl2br(htmlspecialchars($dqsRsvpConfig['rsvp_form_intro_subtitle'], ENT_QUOTES, 'UTF-8')); ?></p><?php endif; ?>
        <button type="button" class="btn btn-common" data-toggle="modal" data-target="#rsvpFormPublicModal">Confirmar asistencia</button>
    </div>
</div>

<div class="modal fade" id="rsvpFormPublicModal" tabindex="-1" role="dialog" aria-labelledby="rsvpFormPublicModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rsvpFormPublicModalLabel">Confirmar asistencia</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="dqs-rsvp-form-public-shell" method="post" action="" aria-describedby="dqs-rsvp-form-public-status" novalidate>
                <div class="modal-body">
                    <p class="text-muted">Completá tus datos para confirmar tu asistencia.</p>

                    <div class="dqs-rsvp-section-title">Invitado principal</div>
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label for="dqs-rsvp-form-nombre">Nombre <span class="dqs-rsvp-required">*</span></label><input type="text" class="form-control" id="dqs-rsvp-form-nombre" name="nombre" autocomplete="given-name" required></div></div>
                        <div class="col-md-6"><div class="form-group"><label for="dqs-rsvp-form-apellido">Apellido <span class="dqs-rsvp-required">*</span></label><input type="text" class="form-control" id="dqs-rsvp-form-apellido" name="apellido" autocomplete="family-name" required></div></div>
                        <?php if ($dqsRsvpPublicConfig['phoneVisible']): ?>
                        <div class="col-md-6"><div class="form-group"><label for="dqs-rsvp-form-telefono">Teléfono</label><input type="tel" class="form-control" id="dqs-rsvp-form-telefono" name="telefono" autocomplete="tel"></div></div>
                        <?php endif; ?>
                        <div class="col-md-12"><div class="form-group"><label style="display: block; margin-bottom: 8px;">¿Vas a asistir? <span class="dqs-rsvp-required">*</span></label><label style="margin-right: 20px;" for="dqs-rsvp-form-confirmacion-si"><input type="radio" id="dqs-rsvp-form-confirmacion-si" name="confirmacion" value="Si" required> Sí, asistiré</label><label for="dqs-rsvp-form-confirmacion-no"><input type="radio" id="dqs-rsvp-form-confirmacion-no" name="confirmacion" value="No" required> No podré asistir</label></div></div>
                    </div>

                    <div id="dqs-rsvp-attending-fields" class="dqs-rsvp-hidden">
                        <?php if ($dqsRsvpPublicConfig['adultCompanionsEnabled'] || $dqsRsvpPublicConfig['minorsEnabled']): ?>
                        <div class="dqs-rsvp-section-title">Personas que asistirán</div>
                        <div class="row">
                            <?php if ($dqsRsvpPublicConfig['adultCompanionsEnabled']): ?>
                            <div class="col-md-6"><div class="form-group"><label for="dqs-rsvp-form-cantidad-adultos">Adultos</label><select class="form-control" id="dqs-rsvp-form-cantidad-adultos" name="cantidad_adultos" aria-describedby="dqs-rsvp-form-adultos-ayuda"><?php for ($i = 1; $i <= $dqsRsvpPublicConfig['maxAdults']; $i++): ?><option value="<?= (int)$i ?>"><?= (int)$i ?></option><?php endfor; ?></select><small id="dqs-rsvp-form-adultos-ayuda" class="form-text text-muted">Incluyéndote a vos. Máximo <?= (int)$dqsRsvpPublicConfig['maxAdults'] ?>.</small></div></div>
                            <?php else: ?>
                            <input type="hidden" id="dqs-rsvp-form-cantidad-adultos" name="cantidad_adultos" value="1">
                            <?php endif; ?>

                            <?php if ($dqsRsvpPublicConfig['minorsEnabled']): ?>
                            <div class="col-md-6"><div class="form-group"><label for="dqs-rsvp-form-cantidad-menores">Menores</label><select class="form-control" id="dqs-rsvp-form-cantidad-menores" name="cantidad_menores" aria-describedby="dqs-rsvp-form-menores-ayuda"><?php for ($i = 0; $i <= $dqsRsvpPublicConfig['maxMinors']; $i++): ?><option value="<?= (int)$i ?>"><?= (int)$i ?></option><?php endfor; ?></select><small id="dqs-rsvp-form-menores-ayuda" class="form-text text-muted">Máximo <?= (int)$dqsRsvpPublicConfig['maxMinors'] ?>.</small></div></div>
                            <?php else: ?>
                            <input type="hidden" id="dqs-rsvp-form-cantidad-menores" name="cantidad_menores" value="0">
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <input type="hidden" id="dqs-rsvp-form-cantidad-adultos" name="cantidad_adultos" value="1">
                        <input type="hidden" id="dqs-rsvp-form-cantidad-menores" name="cantidad_menores" value="0">
                        <?php endif; ?>

                        <?php if ($dqsRsvpPublicConfig['foodEnabled']): ?>
                        <div class="dqs-rsvp-section-title">Tu restricción alimentaria</div>
                        <div id="dqs-rsvp-principal-food"></div>
                        <?php endif; ?>

                        <div id="dqs-rsvp-form-adultos-container" aria-live="polite"></div>
                        <div id="dqs-rsvp-form-menores-container" aria-live="polite"></div>
                    </div>

                    <?php if ($dqsRsvpPublicConfig['generalMessageEnabled']): ?>
                    <div id="dqs-rsvp-general-message" class="dqs-rsvp-hidden"><div class="dqs-rsvp-section-title">Mensaje general</div><div class="form-group"><label for="dqs-rsvp-form-mensaje-general">Mensaje para los novios</label><textarea class="form-control" id="dqs-rsvp-form-mensaje-general" name="mensaje_general" rows="3" maxlength="500"></textarea></div></div>
                    <?php endif; ?>

                    <div id="dqs-rsvp-form-public-status" class="alert alert-info" style="display: none;" role="status" aria-live="polite"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button><button type="submit" class="btn btn-common" id="dqs-rsvp-form-submit">Enviar confirmación</button></div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    var config = <?= json_encode($dqsRsvpPublicConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var form = document.getElementById('dqs-rsvp-form-public-shell');
    var status = document.getElementById('dqs-rsvp-form-public-status');
    var attendingFields = document.getElementById('dqs-rsvp-attending-fields');
    var principalFood = document.getElementById('dqs-rsvp-principal-food');
    var adultsSelect = document.getElementById('dqs-rsvp-form-cantidad-adultos');
    var minorsSelect = document.getElementById('dqs-rsvp-form-cantidad-menores');
    var adultsContainer = document.getElementById('dqs-rsvp-form-adultos-container');
    var minorsContainer = document.getElementById('dqs-rsvp-form-menores-container');
    var generalMessage = document.getElementById('dqs-rsvp-general-message');
    var confirmacionSi = document.getElementById('dqs-rsvp-form-confirmacion-si');
    var confirmacionNo = document.getElementById('dqs-rsvp-form-confirmacion-no');
    var submitButton = document.getElementById('dqs-rsvp-form-submit');
    if (!form || !status) return;

    function foodFields(prefix, label, indexName) {
        return '<div class="row dqs-rsvp-food-row"><div class="col-md-6"><div class="form-group"><label for="' + prefix + '-alimento">Restricción alimentaria</label><select class="form-control dqs-rsvp-food-select" id="' + prefix + '-alimento" name="' + indexName + '[alimento]"><option value="No">Ninguna</option><option value="Vegetariano">Vegetariano</option><option value="Vegano">Vegano</option><option value="Celíaco">Celíaco</option><option value="Otros">Otros</option></select></div></div><div class="col-md-6 dqs-rsvp-food-detail dqs-rsvp-hidden"><div class="form-group"><label for="' + prefix + '-alimento-comentario">Detalle de la restricción <span class="dqs-rsvp-required">*</span></label><input type="text" class="form-control" id="' + prefix + '-alimento-comentario" name="' + indexName + '[alimento_comentario]" autocomplete="off" maxlength="500"></div></div></div>';
    }
    function principalFoodFields() { return '<div class="dqs-rsvp-soft-card card"><div class="card-body">' + foodFields('dqs-rsvp-form-principal', 'Titular', 'principal').replace(/name="principal\[/g, 'name="').replace(/\]"/g, '"') + '</div></div>'; }
    function intValue(el, fallback) { var v = parseInt(el && el.value, 10); return isNaN(v) ? fallback : v; }
    function clampSelect(el, min, max) { if (!el) return min; var v = Math.max(min, Math.min(max, intValue(el, min))); el.value = String(v); return v; }
    function renderAdults() { if (!adultsContainer || !config.adultCompanionsEnabled) return; var total = clampSelect(adultsSelect, 1, config.maxAdults); var html = ''; for (var i = 1; i <= total - 1; i++) { html += '<div class="dqs-rsvp-soft-card card"><div class="card-body"><h6 class="card-title">Acompañante adulto #' + i + '</h6><div class="row"><div class="col-md-6"><div class="form-group"><label>Nombre <span class="dqs-rsvp-required">*</span></label><input type="text" class="form-control" name="adultos[' + i + '][nombre]" autocomplete="off" required></div></div><div class="col-md-6"><div class="form-group"><label>Apellido <span class="dqs-rsvp-required">*</span></label><input type="text" class="form-control" name="adultos[' + i + '][apellido]" autocomplete="off" required></div></div></div>' + (config.foodEnabled ? foodFields('dqs-rsvp-form-adulto-' + i, 'Acompañante adulto #' + i, 'adultos[' + i + ']') : '') + '</div></div>'; } adultsContainer.innerHTML = html; syncFoodDetails(); }
    function renderMinors() { if (!minorsContainer || !config.minorsEnabled) return; var total = clampSelect(minorsSelect, 0, config.maxMinors); var html = ''; for (var i = 1; i <= total; i++) { html += '<div class="dqs-rsvp-soft-card card"><div class="card-body"><h6 class="card-title">Menor #' + i + '</h6><div class="row"><div class="col-md-6"><div class="form-group"><label>Nombre <span class="dqs-rsvp-required">*</span></label><input type="text" class="form-control" name="menores[' + i + '][nombre]" autocomplete="off" required></div></div><div class="col-md-6"><div class="form-group"><label>Apellido <span class="dqs-rsvp-required">*</span></label><input type="text" class="form-control" name="menores[' + i + '][apellido]" autocomplete="off" required></div></div></div>' + (config.foodEnabled ? foodFields('dqs-rsvp-form-menor-' + i, 'Menor #' + i, 'menores[' + i + ']') : '') + '</div></div>'; } minorsContainer.innerHTML = html; syncFoodDetails(); }
    function syncFoodDetails() { Array.prototype.forEach.call(form.querySelectorAll('.dqs-rsvp-food-select'), function(select) { var detail = select.closest('.dqs-rsvp-food-row').querySelector('.dqs-rsvp-food-detail'); var input = detail ? detail.querySelector('input') : null; var show = select.value === 'Otros'; if (detail) detail.classList.toggle('dqs-rsvp-hidden', !show); if (input) { input.required = show; if (!show) input.value = ''; } }); }
    function isAttending() { return confirmacionSi && confirmacionSi.checked; }
    function syncConfirmacion() { var yes = isAttending(); attendingFields.classList.toggle('dqs-rsvp-hidden', !yes); if (generalMessage) generalMessage.classList.toggle('dqs-rsvp-hidden', !yes); if (!yes) { if (adultsSelect) adultsSelect.value = '0'; if (minorsSelect) minorsSelect.value = '0'; if (adultsContainer) adultsContainer.innerHTML = ''; if (minorsContainer) minorsContainer.innerHTML = ''; } else { if (adultsSelect && adultsSelect.tagName !== 'SELECT') adultsSelect.value = '1'; renderAdults(); renderMinors(); } }
    function showStatus(type, html) { status.className = 'alert alert-' + type; status.innerHTML = html; status.style.display = 'block'; }
    function publicMessage(data, httpStatus) { var ok = httpStatus === 200 && data && data.ok === true; if (ok && data.deduped === true) return ['success', '<strong>Tu confirmación ya había sido recibida. No necesitás enviarla nuevamente.</strong>']; if (ok && data.persisted === true) return ['success', '<strong>¡Gracias! Recibimos tu confirmación correctamente.</strong>']; if (ok && data.valid === true) return ['success', '<strong>Validamos tus datos correctamente.</strong>']; return ['warning', '<strong>Revisá los datos marcados e intentá nuevamente.</strong>']; }
    function validateFrontend() { var ok = form.checkValidity(); syncFoodDetails(); Array.prototype.forEach.call(form.querySelectorAll('[aria-invalid="true"]'), function(el) { el.removeAttribute('aria-invalid'); }); if (!ok) { Array.prototype.forEach.call(form.querySelectorAll(':invalid'), function(el) { el.setAttribute('aria-invalid', 'true'); }); } if (isAttending()) { if (config.adultCompanionsEnabled) clampSelect(adultsSelect, 1, config.maxAdults); else adultsSelect.value = '1'; if (config.minorsEnabled) clampSelect(minorsSelect, 0, config.maxMinors); else minorsSelect.value = '0'; } return ok; }
    function setSubmitVisible(visible) { if (submitButton) submitButton.style.display = visible ? '' : 'none'; }

    if (config.foodEnabled && principalFood) principalFood.innerHTML = principalFoodFields();
    form.addEventListener('change', function(event) { if (event.target.classList.contains('dqs-rsvp-food-select')) syncFoodDetails(); });
    if (adultsSelect) adultsSelect.addEventListener('change', renderAdults);
    if (minorsSelect) minorsSelect.addEventListener('change', renderMinors);
    if (confirmacionSi) confirmacionSi.addEventListener('change', syncConfirmacion);
    if (confirmacionNo) confirmacionNo.addEventListener('change', syncConfirmacion);
    form.addEventListener('submit', function(event) { event.preventDefault(); if (!validateFrontend()) { showStatus('warning', '<strong>Revisá los datos marcados e intentá nuevamente.</strong>'); return; } setSubmitVisible(true); showStatus('info', '<strong>Estamos validando tus datos...</strong>'); fetch('rsvp_form_validate.php', { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' } }).then(function(response) { return response.text().then(function(text) { return { httpStatus: response.status, data: JSON.parse(text) }; }); }).then(function(result) { var message = publicMessage(result.data, result.httpStatus); showStatus(message[0], message[1]); if (message[0] === 'success' && result.data && (result.data.persisted === true || result.data.deduped === true)) setSubmitVisible(false); }).catch(function() { showStatus('danger', '<strong>Revisá los datos marcados e intentá nuevamente.</strong>'); }); });
    syncConfirmacion();
})();
</script>
