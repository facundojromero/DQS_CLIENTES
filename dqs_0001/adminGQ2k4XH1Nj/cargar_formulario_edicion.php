<?php
session_start();
include_once '../conexion.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "SELECT a.*, e.invitados FROM invitados a
            LEFT JOIN (SELECT id_invitados, nombre_invitado AS invitados FROM invitados_listado_mesa WHERE id_invitados = ?) e
            ON a.id = e.id_invitados
            WHERE a.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    echo "<form method='post' action='editar_invitado.php'>
            <input type='hidden' name='id' value='{$row['id']}'>
            <label>Nombre: <input type='text' name='nombre' value='{$row['nombre']}'></label>
            <label>Apellido: <input type='text' name='apellido' value='{$row['apellido']}'></label>
            <label>Acompañado: <input type='text' name='acompanado' value='{$row['acompanado']}'></label>
            <label>Cantidad Mayores: <input type='number' name='cantidad_mayores' value='{$row['cantidad_mayores']}'></label>
            <label>Cantidad Menores: <input type='number' name='cantidad_menores' value='{$row['cantidad_menores']}'></label>
            <label>Ingreso: <input type='text' name='ingreso' value='{$row['ingreso']}'></label>
            <label>Prioridad: <input type='number' name='id_prioridad' value='{$row['id_prioridad']}'></label>
            <label>Teléfono: <input type='text' name='tel' value='{$row['tel']}'></label>
            <label>Nombre Invitado: <input type='text' name='nombre_invitado' value='{$row['invitados']}'></label>
            <button type='submit'>Guardar Cambios</button>
        </form>";
}
?>