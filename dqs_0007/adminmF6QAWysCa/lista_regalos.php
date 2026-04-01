<?php
require_once '../conexion.php';
require_once '../tienda/regalo_libre_helper.php';

// Función para subir imágenes
function subirImagen($file) {
    $target_dir = "../tienda/imagenes/";
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid('img_', true) . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;

    if (getimagesize($file["tmp_name"]) === false) {
        return false;
    }
    if (file_exists($target_file)) {
        return false;
    }
    if ($file["size"] > 5000000) {
        return false;
    }
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        return false;
    }
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $new_filename;
    } else {
        return false;
    }
}

// Lógica para manejar las peticiones AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $response = ['success' => false, 'message' => ''];
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $titulo = $_POST['titulo'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $precio = intval($_POST['precio'] ?? 0);

            $sql = "UPDATE productos SET titulo = ?, descripcion = ?, precio = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssii", $titulo, $descripcion, $precio, $id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Producto actualizado correctamente.';
            } else {
                $response['message'] = 'Error al actualizar el producto: ' . $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $sql = "UPDATE productos SET activo = 0 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Producto eliminado correctamente.';
            } else {
                $response['message'] = 'Error al eliminar el producto: ' . $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'insert':
            $titulo = $_POST['titulo'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $precio = intval($_POST['precio'] ?? 0);
            $url_imagen = '';

            if (isset($_FILES['imagen']['tmp_name'])) {
                $uploaded_filename = subirImagen($_FILES['imagen']);
                if ($uploaded_filename) {
                    $url_imagen = $uploaded_filename;
                }
            }
            
            $sql_prod = "INSERT INTO productos (titulo, descripcion, precio, activo) VALUES (?, ?, ?, 1)";
            $stmt_prod = $conn->prepare($sql_prod);
            $stmt_prod->bind_param("ssi", $titulo, $descripcion, $precio);
            if ($stmt_prod->execute()) {
                $new_product_id = $conn->insert_id;
                $response['success'] = true;
                $response['message'] = 'Producto insertado correctamente.';
                
                if ($new_product_id && $url_imagen) {
                    $sql_img = "INSERT INTO imagenes (producto_id, url) VALUES (?, ?)";
                    $stmt_img = $conn->prepare($sql_img);
                    $stmt_img->bind_param("is", $new_product_id, $url_imagen);
                    $stmt_img->execute();
                    $stmt_img->close();
                    
                    $response['id'] = $new_product_id;
                    $response['imagen'] = $url_imagen;
                }
            } else {
                $response['message'] = 'Error al insertar el producto: ' . $stmt_prod->error;
            }
            $stmt_prod->close();
            break;

        case 'update_image':
            $id = intval($_POST['id'] ?? 0);
            if (isset($_FILES['imagen']['tmp_name'])) {
                $uploaded_filename = subirImagen($_FILES['imagen']);
                if ($uploaded_filename) {
                    $sql = "UPDATE imagenes SET url = ? WHERE producto_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $uploaded_filename, $id);
                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Imagen actualizada correctamente.';
                        $response['nueva_imagen_url'] = $uploaded_filename;
                    } else {
                        $response['message'] = 'Error al actualizar la URL de la imagen: ' . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $response['message'] = 'Error al subir la nueva imagen.';
                }
            }
            break;
    }
    
    echo json_encode($response);
    $conn->close();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_giftcard_visibility') {
    $showGiftcard = isset($_POST['show_giftcard']) ? 1 : 0;
    guardarSetting($conn, 'show_giftcard', (string)$showGiftcard);
}

// Lógica de carga inicial de la página (GET request)
$sql = "
SELECT 
    a.id, 
    CONCAT(UPPER(SUBSTRING(titulo, 1, 1)), LOWER(SUBSTRING(titulo, 2))) AS titulo, 
    descripcion, 
    precio, 
    url AS imagen
FROM productos a
LEFT JOIN imagenes b ON a.id = b.producto_id
WHERE activo = 1;
";

$result = $conn->query($sql);

if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

$productos = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
}

$showGiftcard = mostrarGiftCardHabilitada($conn);
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Regalos</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .product-item.selected {
            /*border-color: #4CAF50;*/
            /*box-shadow: 0 0 10px rgba(76, 175, 80, 0.5);*/
        }
        .product-item.new-item {
            border-color: #2196F3;
            box-shadow: 0 0 10px rgba(33, 150, 243, 0.5);
        }
        /* ESTILOS PARA LA VISUALIZACIÓN DE LA IMAGEN */
        .image-upload-area {
            position: relative;
            cursor: pointer;
            overflow: hidden;
            border: 2px dashed #ccc;
            background-color: #f9f9f9;
            width: 100%;
            padding-bottom: 100%; /* Truco para mantener una proporción cuadrada */
        }

        .image-upload-area img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain; /* Ajusta la imagen manteniendo su proporción */
            display: block;
        }

        .image-upload-area input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .image-upload-area .change-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 2em;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            padding: 10px;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .image-upload-area:hover .change-icon {
            opacity: 1;
        }

        /* Estilos para el estado inicial de productos nuevos */
        .image-upload-area .upload-icon, .image-upload-area .upload-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #888;
        }
        .image-upload-area .upload-icon {
            font-size: 2em;
        }
        .image-upload-area img.hidden {
            display: none;
        }
    </style>
</head>
<body>
    
        <h2>Gestión de Regalos</h2>

        <form method="POST" style="margin-bottom: 20px;">
            <input type="hidden" name="action" value="save_giftcard_visibility">
            <label style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600;">
                <input type="checkbox" name="show_giftcard" value="1" <?php echo $showGiftcard ? 'checked' : ''; ?>>
                Mostrar Gift Card en la web de regalos
            </label>
            <button type="submit" class="action-btn" style="margin-left: 10px;">Guardar</button>
        </form>
        
        <div class="product-list">
            <?php foreach ($productos as $producto): ?>
                <div class="product-item selected" data-id="<?php echo $producto['id']; ?>">
                    <div class="image-upload-area">
                        <img src="../tienda/imagenes/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['titulo']); ?>">
                        <input type="file" accept="image/*" onchange="cambiarImagen(this, <?php echo $producto['id']; ?>)">
                        <span class="change-icon"><i class="fas fa-camera"></i></span>
                    </div>
                    <div class="product-details">
                        <button type="button" class="remove-btn" onclick="eliminarProducto(this, <?php echo $producto['id']; ?>)"><i class="fas fa-trash"></i></button>
                        <div class="input-group">
                            <label>Título:</label>
                            <input type="text" 
                                   class="input-text" 
                                   value="<?php echo htmlspecialchars($producto['titulo']); ?>" 
                                   onchange="actualizarProducto(this)">
                        </div>
                        <div class="input-group">
                            <label>Descripción:</label>
                            <textarea class="input-textarea" 
                                      onchange="actualizarProducto(this)"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                        </div>
                        <div class="price-control" onclick="event.stopPropagation();">
                            <div class="price-quick-adjust">
                                <button type="button" class="price-btn" onclick="cambiarPrecio(this, -5000)">-5k</button>
                                <span class="price-display" data-precio-valor="<?php echo htmlspecialchars($producto['precio']); ?>">
                                    $<?php echo htmlspecialchars(number_format($producto['precio'], 0, ',', '.')); ?>
                                </span>
                                <button type="button" class="price-btn" onclick="cambiarPrecio(this, 5000)">+5k</button>
                            </div>
                            <div class="price-manual-input">
                                <label>Monto:</label>
                                <input type="number" 
                                       class="price-input-custom" 
                                       value="<?php echo htmlspecialchars($producto['precio']); ?>" 
                                       step="100" min="0" 
                                       onchange="actualizarProducto(this)"
                                       oninput="actualizarDisplay(this)">
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="add-product-container">
            <button type="button" class="action-btn add-new-btn" onclick="agregarProducto()">
                <i class="fas fa-plus"></i> Agregar nuevo producto
            </button>
        </div>

    
    <template id="nuevo-producto-template">
        <div class="product-item new-item">
            <div class="image-upload-area">
                <input type="file" name="nueva_imagen" accept="image/*" onchange="previewImage(this)">
                <span class="upload-icon"><i class="fas fa-plus-circle"></i></span>
                <span class="upload-text">Subir imagen</span>
                <img src="#" alt="Vista previa de la imagen" class="hidden">
            </div>
            <div class="product-details">
                <button type="button" class="remove-btn" onclick="eliminarProducto(this)"><i class="fas fa-trash"></i></button>
                <div class="input-group">
                    <label>Título:</label>
                    <input type="text" class="input-text" placeholder="Título del producto" required>
                </div>
                <div class="input-group">
                    <label>Descripción:</label>
                    <textarea class="input-textarea" placeholder="Descripción del producto" required></textarea>
                </div>
                <div class="price-control" onclick="event.stopPropagation();">
                    <div class="price-quick-adjust">
                        <button type="button" class="price-btn" onclick="cambiarPrecio(this, -5000)">-5k</button>
                        <span class="price-display">$0</span>
                        <button type="button" class="price-btn" onclick="cambiarPrecio(this, 5000)">+5k</button>
                    </div>
                    <div class="price-manual-input">
                        <label>Monto:</label>
                        <input type="number" 
                                class="price-input-custom" 
                                value="0" 
                                step="100" min="0" 
                                oninput="actualizarDisplay(this)">
                    </div>
                </div>
                <button type="button" class="action-btn save-new-btn" onclick="guardarNuevoProducto(this)">Guardar</button>
            </div>
        </div>
    </template>
    
    <script>
        function cambiarPrecio(btn, monto) {
            const priceControl = btn.closest('.price-control');
            const customInput = priceControl.querySelector('.price-input-custom');
            
            let precioActual = parseInt(customInput.value);
            let nuevoPrecio = precioActual + monto;

            if (nuevoPrecio >= 0) {
                customInput.value = nuevoPrecio;
                actualizarDisplay(customInput);
                if (btn.closest('.product-item').dataset.id) {
                    actualizarProducto(customInput);
                }
            }
        }

        function actualizarDisplay(input) {
            const priceControl = input.closest('.price-control');
            const priceDisplay = priceControl.querySelector('.price-display');
            
            let nuevoPrecio = parseInt(input.value);
            if (isNaN(nuevoPrecio)) nuevoPrecio = 0;
            if (nuevoPrecio < 0) nuevoPrecio = 0;

            priceDisplay.textContent = '$' + nuevoPrecio.toLocaleString('es-AR', { minimumFractionDigits: 0 });
        }
        
        function actualizarProducto(element) {
            const productItem = element.closest('.product-item');
            const id = productItem.dataset.id;
            
            if (!id) return;

            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('id', id);
            formData.append('titulo', productItem.querySelector('.input-text').value);
            formData.append('descripcion', productItem.querySelector('.input-textarea').value);
            formData.append('precio', productItem.querySelector('.price-input-custom').value);

            fetch('lista_regalos.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log(data.message);
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function eliminarProducto(btn, id) {
            if (confirm("¿Estás seguro de que deseas eliminar este producto?")) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                fetch('lista_regalos.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        btn.closest('.product-item').remove();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }

        function agregarProducto() {
            const template = document.getElementById('nuevo-producto-template').content.cloneNode(true);
            const productList = document.querySelector('.product-list');
            productList.appendChild(template);
        }

        function previewImage(input) {
            const file = input.files[0];
            const previewImg = input.closest('.image-upload-area').querySelector('img');
            const uploadIcon = input.closest('.image-upload-area').querySelector('.upload-icon');
            const uploadText = input.closest('.image-upload-area').querySelector('.upload-text');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.classList.remove('hidden');
                    if (uploadIcon) uploadIcon.style.display = 'none';
                    if (uploadText) uploadText.style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                previewImg.classList.add('hidden');
                if (uploadIcon) uploadIcon.style.display = 'block';
                if (uploadText) uploadText.style.display = 'block';
            }
        }

        function guardarNuevoProducto(btn) {
            const productItem = btn.closest('.product-item');
            const title = productItem.querySelector('.input-text').value;
            const description = productItem.querySelector('.input-textarea').value;
            const price = productItem.querySelector('.price-input-custom').value;
            const imageFile = productItem.querySelector('input[type="file"]').files[0];

            if (!title || !description || !imageFile) {
                alert('Por favor, completa todos los campos y sube una imagen.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'insert');
            formData.append('titulo', title);
            formData.append('descripcion', description);
            formData.append('precio', price);
            formData.append('imagen', imageFile);

            fetch('lista_regalos.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    const newProductHtml = `
                        <div class="product-item selected" data-id="${data.id}">
                            <div class="image-upload-area">
                                <img src="../tienda/imagenes/${data.imagen}" alt="${title}">
                                <input type="file" accept="image/*" onchange="cambiarImagen(this, ${data.id})">
                                <span class="change-icon"><i class="fas fa-camera"></i></span>
                            </div>
                            <div class="product-details">
                                <button type="button" class="remove-btn" onclick="eliminarProducto(this, ${data.id})"><i class="fas fa-trash"></i></button>
                                <div class="input-group">
                                    <label>Título:</label>
                                    <input type="text" class="input-text" value="${title}" onchange="actualizarProducto(this)">
                                </div>
                                <div class="input-group">
                                    <label>Descripción:</label>
                                    <textarea class="input-textarea" onchange="actualizarProducto(this)">${description}</textarea>
                                </div>
                                <div class="price-control" onclick="event.stopPropagation();">
                                    <div class="price-quick-adjust">
                                        <button type="button" class="price-btn" onclick="cambiarPrecio(this, -5000)">-5k</button>
                                        <span class="price-display" data-precio-valor="${price}">$${parseInt(price).toLocaleString('es-AR', { minimumFractionDigits: 0 })}</span>
                                        <button type="button" class="price-btn" onclick="cambiarPrecio(this, 5000)">+5k</button>
                                    </div>
                                    <div class="price-manual-input">
                                        <label>Monto:</label>
                                        <input type="number" class="price-input-custom" value="${price}" step="100" min="0" onchange="actualizarProducto(this)" oninput="actualizarDisplay(this)">
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    productItem.outerHTML = newProductHtml;

                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function cambiarImagen(input, id) {
            const formData = new FormData();
            formData.append('action', 'update_image');
            formData.append('id', id);
            formData.append('imagen', input.files[0]);

            fetch('lista_regalos.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    input.closest('.image-upload-area').querySelector('img').src = '../tienda/imagenes/' + data.nueva_imagen_url;
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
