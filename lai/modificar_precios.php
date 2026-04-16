<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Precios de Productos</title>
    <link rel="stylesheet" href="formato.css">

	
	      <link rel="stylesheet" href="formato.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
		  
</head>
<body>
    <div class="container">
        <div class="center-panel">
            <h1>Modificar o Agregar Productos</h1>    
			
			<div class="menu-buttons">
        <a href="index.php" class="menu-button">Volver a Caja</a>
		<button class="action-button" onclick="openNewProductModal()">Agregar Producto</button>

    </div>
  
        
		
		
<?php
include 'conexion.php';


session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}



if (isset($_POST['toggle_activo'])) {
    $id = intval($_POST['id']);
    $nuevoActivo = intval($_POST['activo']);

    $conn->query("UPDATE productos SET activo = $nuevoActivo WHERE id = $id");

    echo "Estado actualizado";
    exit;
}

if (isset($_POST['nuevo'])) {
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $activo = $_POST['activo'];
    $orden = isset($_POST['orden']) ? intval($_POST['orden']) : null;
    if ($orden < 1 || $orden > 9) {
        $orden = "NULL";
    }

    $query_insert = "INSERT INTO productos (nombre, precio, orden, activo) 
                     VALUES ('$nombre', '$precio', " . ($orden === "NULL" ? "NULL" : $orden) . ", '$activo')";

    if ($conn->query($query_insert) === TRUE) {
        echo "Producto agregado correctamente.";
    } else {
        echo "Error al agregar el producto: " . $conn->error;
    }
    exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $activo = $_POST['activo'];
    $orden = isset($_POST['orden']) ? intval($_POST['orden']) : null;

    // Validar si orden está entre 1 y 9, si no -> NULL
    if ($orden < 1 || $orden > 9) {
        $orden = "NULL";
    } else {
        // Verificar si ya hay otro producto con ese orden (distinto ID)
        $query_check = "SELECT id, orden FROM productos WHERE orden = $orden AND id != $id";
        $result_check = $conn->query($query_check);

        if ($result_check->num_rows > 0) {
            $row_check = $result_check->fetch_assoc();
            $id_to_swap = $row_check['id'];

            // Obtener el orden actual del producto que estamos actualizando
            $query_current = "SELECT orden FROM productos WHERE id = $id";
            $result_current = $conn->query($query_current);
            $row_current = $result_current->fetch_assoc();
            $orden_actual = $row_current['orden'];

            // Intercambiar valores de orden
            if ($orden_actual !== null) {
                $conn->query("UPDATE productos SET orden = $orden_actual WHERE id = $id_to_swap");
            } else {
                $conn->query("UPDATE productos SET orden = NULL WHERE id = $id_to_swap");
            }
        }
    }

    // Actualizar producto con nuevo orden
    $query_update = "UPDATE productos 
                     SET nombre = '$nombre', 
                         precio = '$precio', 
                         orden = " . ($orden === "NULL" ? "NULL" : $orden) . ", 
                         activo = '$activo' 
                     WHERE id = '$id'";

    if ($conn->query($query_update) === TRUE) {
        echo "<p>Producto actualizado correctamente.</p>";
    } else {
        echo "<p>Error al actualizar el producto: " . $conn->error . "</p>";
    }
}

// Mostrar productos
$query = "SELECT * FROM productos ORDER BY orden IS NULL, orden ASC";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Nombre</th><th>Precio</th><th>Orden</th><th>Activo</th><th>Acciones</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['nombre'] . "</td>";
		echo "<td>$" . number_format($row['precio'], 0, '', '.') . "</td>";
        echo "<td>" . $row['orden'] . "</td>";
		echo "<td>
			<a href='#' onclick='toggleActivo(" . $row['id'] . ", " . $row['activo'] . ")'>
				" . ($row['activo'] == 1 
				? '<i class="fa-solid fa-circle-check" style="color:green;"></i> ' 
				: '<i class="fa-solid fa-circle-xmark" style="color:red;"></i> ') . "
			</a>
		</td>";


        echo "<td>
            <button class='action-button' onclick='openModal(" . $row['id'] . ", \"" . $row['nombre'] . "\", " . $row['precio'] . ", " . ($row['orden'] === null ? 'null' : $row['orden']) . ", " . $row['activo'] . ")'>Modificar</button>
        </td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No hay productos disponibles.</p>";
}

$conn->close();
?>
		
    </div>

<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Modificar Producto</h2>
        <form id="form-modificar-producto" class="form-modal">

            <input type="hidden" id="modal-id" name="id">

            <label for="modal-nombre">Nombre:</label>
            <input type="text" id="modal-nombre" name="nombre" required>

            <label for="modal-precio">Precio:</label>
            <input type="number" step="0.01" id="modal-precio" name="precio" required>

            <label for="modal-orden">Orden:</label>
            <input type="number" id="modal-orden" name="orden" min="0" max="9">

            <label for="modal-activo">Activo:</label>
            <select id="modal-activo" name="activo" required>
                <option value="1">Activo</option>
                <option value="0">Desactivado</option>
            </select>

            <button type="submit" class="action-button">Actualizar</button>
        </form>
    </div>
</div>


<div id="newProductModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeNewProductModal()">&times;</span>
    <h2>Agregar Nuevo Producto</h2>
    <form id="form-nuevo-producto" class="form-modal">
      <label for="nuevo-nombre">Nombre:</label>
      <input type="text" id="nuevo-nombre" name="nombre" required>

      <label for="nuevo-precio">Precio:</label>
      <input type="number" step="0.01" id="nuevo-precio" name="precio" required>

      <label for="nuevo-orden">Orden:</label>
      <input type="number" id="nuevo-orden" name="orden" min="0" max="9">

      <label for="nuevo-activo">Activo:</label>
      <select id="nuevo-activo" name="activo" required>
        <option value="1">Activo</option>
        <option value="0">Desactivado</option>
      </select>

      <button type="submit" class="action-button">Agregar</button>
    </form>
  </div>
</div>


	
	<script>
    function openModal(id, nombre, precio, orden, activo) {
        document.getElementById('modal-id').value = id;
        document.getElementById('modal-nombre').value = nombre;
        document.getElementById('modal-precio').value = precio;
        document.getElementById('modal-orden').value = orden;
        document.getElementById('modal-activo').value = activo;
        document.getElementById('myModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('myModal').style.display = 'none';
    }

    document.getElementById('form-modificar-producto').addEventListener('submit', function(e) {
        e.preventDefault(); // Evita submit tradicional

        const formData = new FormData(this);

        fetch('modificar_precios.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(data => {
            console.log(data); // Opcional: mostrar mensaje
            closeModal(); // Cierra el modal
            location.reload(); // Recarga la tabla para ver cambios
        })
        .catch(err => console.error('Error:', err));
    });
	
	
function openNewProductModal() {
    document.getElementById('form-nuevo-producto').reset();
    document.getElementById('newProductModal').style.display = 'block';
}

function closeNewProductModal() {
    document.getElementById('newProductModal').style.display = 'none';
}

document.getElementById('form-nuevo-producto').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('nuevo', '1'); // flag para identificar alta

    fetch('modificar_precios.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        console.log(data);
        closeNewProductModal();
        location.reload();
    })
    .catch(err => console.error('Error:', err));
});


function toggleActivo(id, estadoActual) {
    const nuevoEstado = estadoActual === 1 ? 0 : 1;

    const formData = new FormData();
    formData.append('id', id);
    formData.append('toggle_activo', '1'); // Flag especial para este tipo de acción
    formData.append('activo', nuevoEstado);

    fetch('modificar_precios.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        console.log(data);
        location.reload(); // Recarga la tabla para ver el nuevo estado
    })
    .catch(err => console.error('Error:', err));
}	
	
</script>
	
</body>
</html>
