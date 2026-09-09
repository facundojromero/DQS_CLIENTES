<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include_once '../conexion.php';

if (!isset($conn)) {
    die("Error: La variable \$conn no está definida en conexion.php");
}
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Se conservan los nombres POST y el destino de guardado existentes.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $telefono = $_POST['telefono'];
    $telefono2 = $_POST['telefono2'];
    $direccion = $_POST['direccion'];
    $provincia = $_POST['provincia'];
    $ciudad = $_POST['ciudad'];
    $cbu = $_POST['cbu'];
    $cbu_titular = $_POST['cbu_titular'];
    $alias = $_POST['alias'];
    $cbu_dolar = $_POST['cbu_dolar'];
    $alias_dolar = $_POST['alias_dolar'];
    $cotizacion_dolar = $_POST['cotizacion_dolar'];

    $sql = "SELECT * FROM cliente a
            INNER JOIN `user` b
            ON a.user_id = b.id WHERE user_id = $user_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $sql = "UPDATE cliente SET nombre='$nombre', apellido='$apellido', telefono='$telefono', telefono2='$telefono2', direccion='$direccion', provincia='$provincia', ciudad='$ciudad', cbu='$cbu', alias='$alias' , cbu_titular='$cbu_titular', cbu_dolar='$cbu_dolar', alias_dolar='$alias_dolar', cotizacion_dolar='$cotizacion_dolar' WHERE user_id = $user_id";
        if ($conn->query($sql) === TRUE) {
            header("Location: index.php?new=datos");
            exit();
        }
        echo "Error al actualizar la información: " . $conn->error;
    } else {
        $sql = "INSERT INTO cliente (user_id, nombre, apellido, telefono, telefono2, direccion, provincia, ciudad, cbu_titular, cbu, alias, cbu_dolar, alias_dolar, cotizacion_dolar) VALUES ($user_id, '$nombre', '$apellido', '$telefono', '$telefono2', '$direccion', '$provincia', '$ciudad', '$cbu_titular', '$cbu', '$alias', '$cbu_dolar', '$alias_dolar', '$cotizacion_dolar')";
        if ($conn->query($sql) === TRUE) {
            echo "Información guardada correctamente.";
        } else {
            echo "Error al guardar la información: " . $conn->error;
        }
    }
}

$sql = "SELECT * FROM cliente a
        INNER JOIN `user` b
        ON a.user_id = b.id WHERE user_id = $user_id";
$result = $conn->query($sql);
$cliente = $result->fetch_assoc();
$escape = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};
?>
<link rel="stylesheet" href="datos.css">

<main class="data-page" aria-labelledby="data-page-title">
    <header class="data-page__header">
        <div>
            <span class="data-page__eyebrow">Configuración de la cuenta</span>
            <h1 id="data-page-title">Tu información</h1>
            <p>Revisá tus datos personales y la información utilizada para transferencias.</p>
        </div>
        <a href="?new=modificardatos" class="data-button data-button--primary">
            <i class="fas fa-edit" aria-hidden="true"></i> Editar información
        </a>
    </header>

    <?php if ($cliente): ?>
        <div class="data-grid">
            <section class="data-card data-card--wide" aria-labelledby="main-data-title">
                <div class="data-card__heading">
                    <span class="data-card__icon"><i class="fas fa-user" aria-hidden="true"></i></span>
                    <div><h2 id="main-data-title">Datos principales</h2><p>Información de contacto y ubicación.</p></div>
                </div>
                <dl class="data-list data-list--two-columns">
                    <div><dt>Nombre</dt><dd><?= $escape($cliente['nombre']) ?: '—' ?></dd></div>
                    <div><dt>Apellido</dt><dd><?= $escape($cliente['apellido']) ?: '—' ?></dd></div>
                    <div><dt>Email</dt><dd><?= $escape($cliente['email']) ?: '—' ?></dd></div>
                    <div><dt>Teléfono</dt><dd><?= $escape($cliente['telefono']) ?: '—' ?></dd></div>
                    <div><dt>Teléfono alternativo</dt><dd><?= $escape($cliente['telefono2']) ?: '—' ?></dd></div>
                    <div><dt>Dirección</dt><dd><?= $escape($cliente['direccion']) ?: '—' ?></dd></div>
                    <div><dt>Provincia</dt><dd><?= $escape($cliente['provincia']) ?: '—' ?></dd></div>
                    <div><dt>Ciudad</dt><dd><?= $escape($cliente['ciudad']) ?: '—' ?></dd></div>
                </dl>
            </section>

            <section class="data-card" aria-labelledby="ars-data-title">
                <div class="data-card__heading">
                    <span class="data-card__icon"><i class="fas fa-university" aria-hidden="true"></i></span>
                    <div><h2 id="ars-data-title">Cuenta en pesos</h2><p>Datos visibles para transferencias en pesos.</p></div>
                </div>
                <dl class="data-list">
                    <div><dt>Titular</dt><dd><?= $escape($cliente['cbu_titular']) ?: '—' ?></dd></div>
                    <div><dt>CBU / CVU</dt><dd class="data-list__code"><?= $escape($cliente['cbu']) ?: '—' ?></dd></div>
                    <div><dt>Alias</dt><dd class="data-list__code"><?= $escape($cliente['alias']) ?: '—' ?></dd></div>
                </dl>
            </section>

            <section class="data-card" aria-labelledby="usd-data-title">
                <div class="data-card__heading">
                    <span class="data-card__icon"><i class="fas fa-dollar-sign" aria-hidden="true"></i></span>
                    <div><h2 id="usd-data-title">Cuenta en dólares</h2><p>Datos disponibles para transferencias en dólares.</p></div>
                </div>
                <dl class="data-list">
                    <div><dt>CBU / CVU dólares</dt><dd class="data-list__code"><?= $escape($cliente['cbu_dolar']) ?: '—' ?></dd></div>
                    <div><dt>Alias dólares</dt><dd class="data-list__code"><?= $escape($cliente['alias_dolar']) ?: '—' ?></dd></div>
                    <div><dt>Cotización del dólar</dt><dd><?= $escape($cliente['cotizacion_dolar']) !== '' ? '$' . $escape($cliente['cotizacion_dolar']) : '—' ?></dd></div>
                </dl>
            </section>
        </div>

        <footer class="data-page__footer">
            <a href="?new=pass" class="data-button data-button--secondary">
                <i class="fas fa-key" aria-hidden="true"></i> Cambiar contraseña
            </a>
        </footer>
    <?php else: ?>
        <section class="data-card data-empty">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            <h2>Todavía no hay información cargada</h2>
            <p>Completá tus datos para configurar tu cuenta.</p>
        </section>
    <?php endif; ?>
</main>
<?php $conn->close(); ?>
