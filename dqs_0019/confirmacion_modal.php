<?php
error_reporting(E_ERROR);
include_once 'conexion.php';

$codigo_invitado = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';

$nombre_invitado = '';
$invitados = '';
$max_mayores = 0;
$max_menores = 0;
$confirmacion_actual = '';
$cant_mayores_actual = 0;
$cant_menores_actual = 0;
$alimento_actual = 'No';
$contenido_actual = '';
$invitado_id = 0;
$invitados_listado = [];
$is_single = false;
$single_id = 0;

if (empty($codigo_invitado)) {
    echo '<div class="modal-body"><p class="alert alert-danger">No se proporcionó un código de invitación.</p></div>';
    echo '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>';
    exit;
}

$sql_invitado = "SELECT
        CASE WHEN cantidad_mayores>1 THEN e.titulo_invitados ELSE CONCAT(titulo_invitados,' ',apellido) END nombre,
        a.id id_invitados,
        a.codigo,
        cantidad_mayores,
        cantidad_menores,
        e.invitados,
        a.confirmacion,
        a.confirmacion_mayores,
        a.confirmacion_menores,
        a.alimento,
        a.confirmacion_comentario
    FROM invitados a
    LEFT JOIN (
        SELECT
            aa.id_invitados,
            bb.invitados,
            bb.titulo_invitados,
            ROW_NUMBER() OVER (PARTITION BY aa.id_invitados ORDER BY aa.id_invitados ASC) AS numero_fila
        FROM invitados_listado_mesa aa
        INNER JOIN (
            SELECT
                a.id_invitados,
                SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ' y '), ' y ', 2) AS titulo_invitados,
                CASE WHEN cantidad_mayores<2 THEN nombre_invitado ELSE
                CONCAT(
                    IF(COUNT(*) > 1,
                    SUBSTRING_INDEX(
                        GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '),
                        ', ',
                        COUNT(*) - 1
                    ),
                    GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', ')
                    ),
                    ' y ',
                    SUBSTRING_INDEX(GROUP_CONCAT(nombre_invitado ORDER BY a.id ASC SEPARATOR ', '), ', ', -1)
                ) END AS invitados
            FROM invitados_listado_mesa a
            INNER JOIN invitados b ON a.id_invitados = b.id
            GROUP BY a.id_invitados
        ) bb ON aa.id_invitados = bb.id_invitados
        GROUP BY aa.id_invitados
    ) e ON a.id = e.id_invitados
    WHERE a.codigo = ?
      AND activo = 1
    GROUP BY a.id";

$stmt_invitado = $conn->prepare($sql_invitado);
if (!$stmt_invitado) {
    echo '<div class="modal-body"><p class="alert alert-danger">No se pudo preparar la consulta.</p></div>';
    echo '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>';
    exit;
}

$stmt_invitado->bind_param('s', $codigo_invitado);
$stmt_invitado->execute();
$result_invitado = $stmt_invitado->get_result();

if (!$result_invitado || $result_invitado->num_rows === 0) {
    echo '<div class="modal-body"><p class="alert alert-danger">Código de invitación no válido.</p></div>';
    echo '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>';
    exit;
}

$data_invitado = $result_invitado->fetch_assoc();
$nombre_invitado = $data_invitado['nombre'];
$invitados = $data_invitado['invitados'];
$max_mayores = (int)$data_invitado['cantidad_mayores'];
$max_menores = (int)$data_invitado['cantidad_menores'];
$confirmacion_actual = $data_invitado['confirmacion'];
$cant_mayores_actual = (int)$data_invitado['confirmacion_mayores'];
$cant_menores_actual = (int)$data_invitado['confirmacion_menores'];
$alimento_actual = $data_invitado['alimento'];
$contenido_actual = $data_invitado['confirmacion_comentario'];
$invitado_id = (int)$data_invitado['id_invitados'];
$stmt_invitado->close();

$stmt_listado = $conn->prepare('SELECT id, nombre_invitado, es_menor, asiste, alimento, alimento_comentario FROM invitados_listado_mesa WHERE id_invitados = ? ORDER BY id ASC');
if ($stmt_listado) {
    $stmt_listado->bind_param('i', $invitado_id);
    $stmt_listado->execute();
    $result_listado = $stmt_listado->get_result();
    while ($row = $result_listado->fetch_assoc()) {
        $invitados_listado[] = [
            'id' => (int)$row['id'],
            'nombre_invitado' => $row['nombre_invitado'],
            'es_menor' => (int)$row['es_menor'],
            'asiste' => isset($row['asiste']) ? (int)$row['asiste'] : 0,
            'alimento' => !empty($row['alimento']) ? $row['alimento'] : 'No',
            'alimento_comentario' => $row['alimento_comentario'],
        ];
    }
    $stmt_listado->close();
}

$is_single = count($invitados_listado) === 1;
if ($is_single) {
    $single_id = (int)$invitados_listado[0]['id'];
}

$ya_confirmo = ($confirmacion_actual === 'Si' || $confirmacion_actual === 'No');
$total_personas = $max_mayores + $max_menores;
$frase = ($total_personas > 1) ? 'están a punto de confirmar la asistencia.' : 'estás a punto de confirmar la asistencia.';
?>

<div class="modal-header">
    <h5 class="modal-title" id="confirmacionModalLabel">Confirmar Asistencia</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>

<style>
    .invitado-card {
        border: 1px solid #e7e7e7;
        border-radius: 12px;
        padding: 10px 12px;
        margin-bottom: 10px;
        background: #fff;
    }

    .invitado-card .form-check-label {
        font-weight: 600;
        line-height: 1.35;
    }

    .alimento-persona {
        background: #f8fafc;
        border-radius: 8px;
        padding: 8px;
        margin-top: 8px;
        margin-left: 0 !important;
    }

    .alimento-persona-label {
        font-size: 12px;
        color: #4b5563;
        margin-bottom: 6px;
        display: inline-block;
    }

    @media (max-width: 576px) {
        .invitado-card {
            padding: 10px;
        }

        .alimento-persona {
            padding: 8px;
        }
    }
</style>

<div class="modal-body">
    <div id="modalMessage" class="mb-3" style="display:none;"></div>

    <div id="introTextConfirmacion">
        <p>Hola <strong><?php echo htmlspecialchars($invitados); ?></strong>, <?php echo $frase; ?></p>
        <p>El código de la invitación es: <strong><?php echo htmlspecialchars($codigo_invitado); ?></strong></p>
    </div>

    <form id="formConfirmacion" action="procesar_confirmacion.php" method="POST">
        <input type="hidden" name="codigo_invitado" value="<?php echo htmlspecialchars($codigo_invitado); ?>">

        <div class="form-group">
            <label for="entrada">Confirmo asistencia:</label>
            <select class="form-control" id="entrada" name="confirmar_asistencia" required>
                <option value="" <?php echo !$ya_confirmo ? 'selected' : ''; ?> disabled>Selecciona una opción</option>
                <option value="Si" <?php echo ($confirmacion_actual === 'Si') ? 'selected' : ''; ?>>Si</option>
                <option value="No" <?php echo ($confirmacion_actual === 'No') ? 'selected' : ''; ?>>No</option>
            </select>
        </div>

        <div class="form-group" id="invitados-checklist-container" style="display:none;">
            <?php if ($is_single) {
                $singlePersona = $invitados_listado[0];
                $singleAlimento = $singlePersona['alimento'];
                $singleComentario = $singlePersona['alimento_comentario'];
                $singleChecked = ((int)$singlePersona['asiste'] === 1);
            ?>
                <p class="mb-2">Confirmando asistencia para: <strong><?php echo htmlspecialchars($singlePersona['nombre_invitado']); ?></strong></p>
                <input type="hidden" name="seleccionados[]" value="<?php echo $single_id; ?>">

                <div class="alimento-persona" id="alimento-wrap-single">
                    <label class="alimento-persona-label" for="alimento-single">Restricción alimentaria</label>
                    <select class="form-control form-control-sm" id="alimento-single" name="alimento_persona[<?php echo $single_id; ?>]" <?php echo $singleChecked ? '' : 'disabled'; ?>>
                        <option value="No" <?php echo ($singleAlimento === 'No') ? 'selected' : ''; ?>>No</option>
                        <option value="Vegetariano" <?php echo ($singleAlimento === 'Vegetariano') ? 'selected' : ''; ?>>Vegetariano</option>
                        <option value="Vegano" <?php echo ($singleAlimento === 'Vegano') ? 'selected' : ''; ?>>Vegano</option>
                        <option value="Celiaco" <?php echo ($singleAlimento === 'Celiaco') ? 'selected' : ''; ?>>Celiaco</option>
                        <option value="Otro" <?php echo ($singleAlimento === 'Otro') ? 'selected' : ''; ?>>Otro</option>
                    </select>

                    <textarea class="form-control form-control-sm mt-2" id="comentario-single" name="comentario_persona[<?php echo $single_id; ?>]" placeholder="Aclaración para esta persona" rows="2" <?php echo $singleChecked ? '' : 'disabled'; ?> style="display: <?php echo ($singleChecked && $singleAlimento !== 'No') ? 'block' : 'none'; ?>;"><?php echo htmlspecialchars((string)$singleComentario); ?></textarea>
                </div>
            <?php } else { ?>
                <label>Seleccioná quiénes asisten:</label>
                <?php foreach ($invitados_listado as $persona) {
                    $pid = (int)$persona['id'];
                    $checked = ((int)$persona['asiste'] === 1);
                    $alimentoPersona = $persona['alimento'];
                    $comentarioPersona = $persona['alimento_comentario'];
                ?>
                    <div class="invitado-card">
                        <div class="form-check">
                            <input class="form-check-input invitado-checkbox" type="checkbox" name="seleccionados[]" value="<?php echo $pid; ?>" id="invitado-<?php echo $pid; ?>" <?php echo $checked ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="invitado-<?php echo $pid; ?>">
                                <?php echo htmlspecialchars($persona['nombre_invitado']); ?>
                                <?php if ((int)$persona['es_menor'] === 1) { ?>
                                    <small class="badge badge-secondary">Menor</small>
                                <?php } ?>
                            </label>
                        </div>

                        <div class="alimento-persona" id="alimento-wrap-<?php echo $pid; ?>">
                            <label class="alimento-persona-label" for="alimento-<?php echo $pid; ?>">Restricción alimentaria</label>
                            <select class="form-control form-control-sm alimento-select" id="alimento-<?php echo $pid; ?>" name="alimento_persona[<?php echo $pid; ?>]" <?php echo $checked ? '' : 'disabled'; ?>>
                                <option value="No" <?php echo ($alimentoPersona === 'No') ? 'selected' : ''; ?>>No</option>
                                <option value="Vegetariano" <?php echo ($alimentoPersona === 'Vegetariano') ? 'selected' : ''; ?>>Vegetariano</option>
                                <option value="Vegano" <?php echo ($alimentoPersona === 'Vegano') ? 'selected' : ''; ?>>Vegano</option>
                                <option value="Celiaco" <?php echo ($alimentoPersona === 'Celiaco') ? 'selected' : ''; ?>>Celiaco</option>
                                <option value="Otro" <?php echo ($alimentoPersona === 'Otro') ? 'selected' : ''; ?>>Otro</option>
                            </select>

                            <textarea class="form-control form-control-sm mt-2 alimento-comentario" id="comentario-<?php echo $pid; ?>" name="comentario_persona[<?php echo $pid; ?>]" placeholder="Aclaración para esta persona" rows="2" <?php echo $checked ? '' : 'disabled'; ?> style="display: <?php echo ($checked && $alimentoPersona !== 'No') ? 'block' : 'none'; ?>;"><?php echo htmlspecialchars((string)$comentarioPersona); ?></textarea>
                        </div>
                    </div>
                <?php } ?>
                <small class="form-text text-muted">Si seleccionás "Si", debés tildar al menos una persona.</small>
            <?php } ?>
        </div>

        <button type="submit" class="btn btn-primary">Enviar Confirmación</button>
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
</div>

<script>
    (function () {
        var entradaSelect = document.getElementById('entrada');
        var invitadosContainer = document.getElementById('invitados-checklist-container');
        var isSingle = <?php echo $is_single ? 'true' : 'false'; ?>;

        function refreshSingleState() {
            if (!isSingle) {
                return;
            }

            var alimentoSelect = document.getElementById('alimento-single');
            var comentario = document.getElementById('comentario-single');
            if (!alimentoSelect || !comentario) {
                return;
            }

            var enabled = entradaSelect.value === 'Si';
            alimentoSelect.disabled = !enabled;
            comentario.disabled = !enabled;

            if (!enabled) {
                comentario.style.display = 'none';
                return;
            }

            comentario.style.display = alimentoSelect.value !== 'No' ? 'block' : 'none';
        }

        function refreshPersonaState(personId) {
            var checkbox = document.getElementById('invitado-' + personId);
            var alimentoSelect = document.getElementById('alimento-' + personId);
            var comentario = document.getElementById('comentario-' + personId);
            if (!checkbox || !alimentoSelect || !comentario) {
                return;
            }

            var enabled = checkbox.checked && entradaSelect.value === 'Si';
            alimentoSelect.disabled = !enabled;
            comentario.disabled = !enabled;

            if (!enabled) {
                comentario.style.display = 'none';
                return;
            }

            comentario.style.display = alimentoSelect.value !== 'No' ? 'block' : 'none';
        }

        function toggleAsistenciaVisibility() {
            var asiste = entradaSelect.value === 'Si';
            invitadosContainer.style.display = asiste ? 'block' : 'none';

            document.querySelectorAll('.invitado-checkbox').forEach(function (checkbox) {
                var pid = checkbox.value;
                refreshPersonaState(pid);
            });

            refreshSingleState();
        }

        if (entradaSelect) {
            entradaSelect.addEventListener('change', toggleAsistenciaVisibility);
        }

        document.querySelectorAll('.invitado-checkbox').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                refreshPersonaState(this.value);
            });
        });

        document.querySelectorAll('.alimento-select').forEach(function (select) {
            select.addEventListener('change', function () {
                var pid = this.id.replace('alimento-', '');
                refreshPersonaState(pid);
            });
        });

        var alimentoSingle = document.getElementById('alimento-single');
        if (alimentoSingle) {
            alimentoSingle.addEventListener('change', refreshSingleState);
        }

        toggleAsistenciaVisibility();

        $('#formConfirmacion').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            var submitButton = form.find('button[type="submit"]');
            var messageDiv = $('#modalMessage');

            if (entradaSelect.value === 'Si' && !isSingle && $('.invitado-checkbox:checked').length === 0) {
                messageDiv.removeClass('alert-success').addClass('msg_error alert alert-danger').text('Si confirmás asistencia, debés seleccionar al menos una persona.').show();
                return;
            }

            submitButton.prop('disabled', true).text('Enviando...');

            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        messageDiv.removeClass('alert-danger msg_error').addClass('alert-success').text(response.message).show();
                        submitButton.prop('disabled', false).text('Enviar Confirmación');
                    } else {
                        messageDiv.removeClass('alert-success').addClass('msg_error alert alert-danger').text(response.message).show();
                        submitButton.prop('disabled', false).text('Enviar Confirmación');
                    }
                },
                error: function () {
                    messageDiv.removeClass('alert-success').addClass('msg_error alert alert-danger').text('Hubo un error de conexión. Inténtalo de nuevo.').show();
                    submitButton.prop('disabled', false).text('Enviar Confirmación');
                }
            });
        });
    })();
</script>
