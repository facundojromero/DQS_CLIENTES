<div class="modal-header">
    <h5 class="modal-title" id="confirmacionModalLabel">RSVP - Confirmación</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>

<div class="modal-body">
    <div id="modalMessage" class="mb-3" style="display:none;"></div>

    <div id="introTextConfirmacion">
        <p>Por favor, completa tus datos para confirmar asistencia.</p>
    </div>

    <form id="formConfirmacion" action="procesar_confirmacion.php" method="POST">
        
        <h6 class="border-bottom pb-2 mb-3">Tus Datos</h6>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required placeholder="Tu nombre">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="apellido">Apellido *</label>
                    <input type="text" class="form-control" id="apellido" name="apellido" required placeholder="Tu apellido">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="entrada">¿Asistirás a la boda? *</label>
            <select class="form-control" id="entrada" name="confirmar_asistencia" required>
                <option value="" disabled selected>Selecciona una opción</option>
                <option value="Si">Sí, asistiré</option>
                <option value="No">No podré asistir</option>
            </select>
        </div>

        <div id="campos-asistencia" style="display: none;">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="cantidad_mayores">Total Adultos *</label>
                        <input type="number" class="form-control trigger-acompanantes" id="cantidad_mayores" name="cantidad_mayores" min="1" value="1" required>
                        <small class="form-text text-muted">Contándote a ti. Mínimo 1.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="cantidad_menores">Total Menores</label>
                        <input type="number" class="form-control trigger-acompanantes" id="cantidad_menores" name="cantidad_menores" min="0" value="0">
                        <small class="form-text text-muted">Menores de edad.</small>
                    </div>
                </div>
            </div>

            <div id="lista-acompanantes" class="mt-2 mb-3"></div>

            <div class="form-group mt-3">
                <label for="alimento">Restricción Alimentaria (General)</label>
                <select class="form-control" name="alimento" id="alimento">
                    <option value="No">Ninguna</option>
                    <option value="Vegetariano">Vegetariano</option>
                    <option value="Vegano">Vegano</option>
                    <option value="Celiaco">Celíaco</option>
                    <option value="Otro">Otra</option>
                </select>
            </div>

            <div class="form-group" id="contenido-group" style="display: none;">
                <label for="contenido">Detalles de dieta / Alergias:</label>
                <textarea class="form-control" id="contenido" name="contenido" rows="2" placeholder="Especifica quién tiene la restricción..."></textarea>
            </div>
            
        </div>
        <button type="submit" class="btn btn-primary btn-block mt-3">Enviar Confirmación</button>
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
</div>

<script>
    $(document).ready(function() {
        // Mostrar/Ocultar campos según asistencia
        $('#entrada').change(function() {
            if ($(this).val() === 'Si') {
                $('#campos-asistencia').slideDown();
                $('#cantidad_mayores').prop('required', true);
                generarCamposAcompanantes(); 
            } else {
                $('#campos-asistencia').slideUp();
                $('#cantidad_mayores').prop('required', false);
                $('#lista-acompanantes').empty(); 
            }
        });

        // Mostrar/Ocultar detalle alimento
        $('#alimento').change(function() {
            if ($(this).val() !== 'No') {
                $('#contenido-group').show();
            } else {
                $('#contenido-group').hide();
            }
        });

        // Escuchar cambios en los números para generar inputs
        $('.trigger-acompanantes').on('input change', function() {
            generarCamposAcompanantes();
        });

        function generarCamposAcompanantes() {
            var cantMayores = parseInt($('#cantidad_mayores').val()) || 1;
            var cantMenores = parseInt($('#cantidad_menores').val()) || 0;
            var contenedor = $('#lista-acompanantes');
            
            contenedor.empty(); 

            // Generar campos para Adultos Extra
            if (cantMayores > 1) {
                contenedor.append('<h6 class="text-primary mt-2">Acompañantes Adultos</h6>');
                for (var i = 1; i < cantMayores; i++) {
                    var html = `
                        <div class="card p-2 mb-2 bg-light border-0">
                            <small class="text-muted">Acompañante Adulto #${i}</small>
                            <div class="row">
                                <div class="col-6">
                                    <input type="text" class="form-control form-control-sm" name="acompanantes[adulto_${i}][nombre]" placeholder="Nombre" required>
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control form-control-sm" name="acompanantes[adulto_${i}][apellido]" placeholder="Apellido" required>
                                </div>
                            </div>
                        </div>
                    `;
                    contenedor.append(html);
                }
            }

            // Generar campos para Menores
            if (cantMenores > 0) {
                contenedor.append('<h6 class="text-primary mt-2">Menores</h6>');
                for (var j = 1; j <= cantMenores; j++) {
                    var html = `
                        <div class="card p-2 mb-2 bg-light border-0">
                            <small class="text-muted">Menor #${j}</small>
                            <div class="row">
                                <div class="col-6">
                                    <input type="text" class="form-control form-control-sm" name="acompanantes[menor_${j}][nombre]" placeholder="Nombre" required>
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control form-control-sm" name="acompanantes[menor_${j}][apellido]" placeholder="Apellido" required>
                                </div>
                            </div>
                        </div>
                    `;
                    contenedor.append(html);
                }
            }
        }
    });
</script>