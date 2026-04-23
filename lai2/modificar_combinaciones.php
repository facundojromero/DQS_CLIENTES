<?php
include 'conexion.php';


session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id_combinacion = "";
$nombre = "";
$precio = "";
$precio_mercadopago = "";
$activo = 0;
$productos_seleccionados = array();


//acticar o desactivar
if (isset($_POST['toggle_activo'])) {
    $id = intval($_POST['id']);
    $nuevoActivo = intval($_POST['activo']);

    $conn->query("UPDATE combinaciones SET activo = $nuevoActivo WHERE id = $id");

    echo "Estado actualizado";
    exit;
}



// Eliminar combinación
if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    $conn->query("DELETE FROM combinaciones_detalles WHERE id_combinacion = $id_eliminar");
    $conn->query("DELETE FROM combinaciones WHERE id = $id_eliminar");
    header("Location: modificar_combinaciones.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_combinacion = $_POST['id_combinacion'];
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $precio_mercadopago = $_POST['precio_mercadopago'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    $productos = isset($_POST['productos']) ? $_POST['productos'] : array();

    if ($id_combinacion) {
        $stmt = $conn->prepare("UPDATE combinaciones SET nombre=?, precio=?, precio_mercadopago=?, activo=? WHERE id=?");
        $stmt->bind_param("sddii", $nombre, $precio, $precio_mercadopago, $activo, $id_combinacion);
        $stmt->execute();
        $conn->query("DELETE FROM combinaciones_detalles WHERE id_combinacion = " . (int)$id_combinacion);
    } else {
        $stmt = $conn->prepare("INSERT INTO combinaciones (nombre, precio, precio_mercadopago, activo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sddi", $nombre, $precio, $precio_mercadopago, $activo);
        $stmt->execute();
        $id_combinacion = $conn->insert_id;
    }

    foreach ($productos as $id_producto) {
        $stmt = $conn->prepare("INSERT INTO combinaciones_detalles (id_combinacion, id_producto) VALUES (?, ?)");
        $stmt->bind_param("ii", $id_combinacion, $id_producto);
        $stmt->execute();
    }

    header("Location: modificar_combinaciones.php");
    exit;
}

if (isset($_GET['editar'])) {
    $id_combinacion = $_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM combinaciones WHERE id = ?");
    $stmt->bind_param("i", $id_combinacion);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();

    if ($fila) {
        $nombre = $fila['nombre'];
        $precio = $fila['precio'];
        $precio_mercadopago = $fila['precio_mercadopago'];
        $activo = $fila['activo'];
        $res = $conn->query("SELECT id_producto FROM combinaciones_detalles WHERE id_combinacion = " . (int)$id_combinacion);
        $temp = array();
        while ($row = $res->fetch_assoc()) {
            $temp[] = $row['id_producto'];
        }
        $productos_seleccionados = $temp;
    }
}

$res = $conn->query("SELECT * FROM combinaciones ORDER BY id DESC");
$combinaciones = array();
while ($row = $res->fetch_assoc()) {
    $combinaciones[] = $row;
}

$res = $conn->query("SELECT id, nombre FROM productos");
$productos = array();
while ($row = $res->fetch_assoc()) {
    $productos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Combinaciones</title>
    <link rel="stylesheet" href="formato.css">
</head>



<body>
    <div class="container">
        <div class="center-panel">
            <h1>Modificar o Agregar Combinaciones de Promos</h1>    
			
<div class="menu-buttons">
    <a href="index.php" class="menu-button">Volver a Caja</a>
<button class="action-button" onclick="openModal('', '', '', '', 1)">Agregar Promocion</button>

</div>


 
<?php if (count($combinaciones) > 0): ?>
    <table>
        <tr>
            <th>Nombre</th>
            <th>Precio Efectivo/Gratis</th>
            <th>Precio Mercado Pago</th>
            <th>Activo</th>
            <th>Acciones</th>
        </tr>
        <?php foreach ($combinaciones as $c): ?>
            <tr>
                <td><?php echo $c['nombre']; ?></td>
                <td>$<?php echo number_format($c['precio'], 0, '', '.'); ?></td>
                <td>$<?php echo number_format($c['precio_mercadopago'], 0, '', '.'); ?></td>
                <td>
                    <a href="#" onclick="toggleActivo(<?php echo $c['id']; ?>, <?php echo $c['activo']; ?>)">
                        <?php echo $c['activo'] == 1
                            ? '<strong style="color:green;">✓</strong>'
                            : '<strong style="color:red;">✕</strong>'; ?>
                    </a>
                </td>
                <td>
                    <button class="action-button" onclick="openModal(
                        <?php echo $c['id']; ?>,
                        '<?php echo addslashes($c['nombre']); ?>',
                        <?php echo $c['precio']; ?>,
                        <?php echo $c['precio_mercadopago']; ?>,
                        <?php echo $c['activo']; ?>
                    )">Modificar</button>
                    <a href="?eliminar=<?php echo $c['id']; ?>" onclick="return confirm('¿Estás seguro de eliminar esta combinación?');">
                        <strong style="color:#c00;">✕</strong>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>No hay combinaciones disponibles.</p>
<?php endif; ?>

        </div>
    </div>
	
	
<!-- Modal para Agregar/Editar Combinaciones -->
<div id="combinacionModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeCombinacionModal()">&times;</span>
    <h2 id="modal-title">Editar Combinación</h2>

    <form id="form-combinacion" class="form-modal">
      <input type="hidden" id="modal-id" name="id_combinacion">

      <label for="modal-nombre">Nombre:</label>
      <input type="text" id="modal-nombre" name="nombre" required>

      <label for="modal-precio">Precio Efectivo/Gratis:</label>
      <input type="number" step="0.01" id="modal-precio" name="precio" required>

      <label for="modal-precio-mercadopago">Precio Mercado Pago:</label>
      <input type="number" step="0.01" id="modal-precio-mercadopago" name="precio_mercadopago" required>

      <label for="modal-activo">Activo:</label>
      <select id="modal-activo" name="activo" required>
        <option value="1">Activo</option>
        <option value="0">Desactivado</option>
      </select>

      <label>Productos:</label>
      <div id="productos-container-modal"></div>
 <button class="add-button" onclick="agregarProducto()">Agregar producto</button>



 
      <button type="submit" class="action-button">Guardar</button>
    </form>
  </div>
</div>
	
	
	
<script>
function agregarProducto() {
    var contenedor = document.getElementById('productos-container');
    var div = document.createElement('div');
    div.className = 'producto-item';
    div.innerHTML = `
        <select name="productos[]">
            <?php foreach ($productos as $p): ?>
                <option value="<?php echo $p['id']; ?>"><?php echo $p['nombre']; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" onclick="eliminarProducto(this)">Eliminar</button><br>`;
    contenedor.appendChild(div);
}

function eliminarProducto(boton) {
    var div = boton.parentNode;
    div.parentNode.removeChild(div);
}
</script>


<script>
function openModal(id, nombre, precio, precioMercadoPago, activo, productos = []) {
    document.getElementById('modal-title').textContent = id ? 'Editar Combinación' : 'Nueva Combinación';

    document.getElementById('modal-id').value = id || '';
    document.getElementById('modal-nombre').value = nombre || '';
    document.getElementById('modal-precio').value = precio || '';
    document.getElementById('modal-precio-mercadopago').value = precioMercadoPago || '';
    document.getElementById('modal-activo').value = activo ?? 1;

    const contenedor = document.getElementById('productos-container-modal');
    contenedor.innerHTML = '';

    if (productos.length > 0) {
        productos.forEach(pId => agregarProducto(pId));
    } else {
        agregarProducto();
    }

    document.getElementById('combinacionModal').style.display = 'block';
}

function openNewProductModal() {
    document.getElementById('form-nuevo-producto').reset();
    document.getElementById('newProductModal').style.display = 'block';
}

function closeCombinacionModal() {
    document.getElementById('combinacionModal').style.display = 'none';
}

function agregarProducto(idSeleccionado = null) {
    const contenedor = document.getElementById('productos-container-modal');
    const div = document.createElement('div');
    div.classList.add('producto-item');

    const select = document.createElement('select');
    select.name = 'productos[]';

    <?php foreach ($productos as $p): ?>
        const option_<?php echo $p['id']; ?> = document.createElement('option');
        option_<?php echo $p['id']; ?>.value = '<?php echo $p['id']; ?>';
        option_<?php echo $p['id']; ?>.text = '<?php echo $p['nombre']; ?>';
        if (idSeleccionado == '<?php echo $p['id']; ?>') option_<?php echo $p['id']; ?>.selected = true;
        select.appendChild(option_<?php echo $p['id']; ?>);
    <?php endforeach; ?>

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = 'Eliminar';
    btn.onclick = () => div.remove();

    div.appendChild(select);
    div.appendChild(btn);
    contenedor.appendChild(div);
}

document.getElementById('form-combinacion').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('guardar_combinacion', '1');

    fetch('modificar_combinaciones.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        console.log(data);
        closeCombinacionModal();
        location.reload();
    })
    .catch(err => console.error('Error:', err));
});

function toggleActivo(id, estadoActual) {
    const nuevoEstado = estadoActual === 1 ? 0 : 1;

    const formData = new FormData();
    formData.append('id', id);
    formData.append('toggle_activo', '1');
    formData.append('activo', nuevoEstado);

    fetch('modificar_combinaciones.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        console.log(data);
        location.reload();
    })
    .catch(err => console.error('Error:', err));
}
</script>


	
</body>
</html>
