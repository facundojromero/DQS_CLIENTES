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
$sql = "SELECT * FROM cliente WHERE user_id = $user_id";
$result = $conn->query($sql);
$cliente = $result->fetch_assoc();
$escape = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};
?>
<link rel="stylesheet" href="datos.css">

<main class="data-page" aria-labelledby="data-form-title">
    <header class="data-page__header">
        <div>
            <a href="?new=datos" class="data-back"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver a tu información</a>
            <h1 id="data-form-title">Editar tu información</h1>
            <p>Los campos marcados con <span aria-hidden="true">*</span> son obligatorios.</p>
        </div>
    </header>

    <form action="index.php?new=datos" method="post" class="data-form">
        <section class="data-card data-card--wide" aria-labelledby="form-main-title">
            <div class="data-card__heading">
                <span class="data-card__icon"><i class="fas fa-user" aria-hidden="true"></i></span>
                <div><h2 id="form-main-title">Datos principales</h2><p>Información personal, de contacto y ubicación.</p></div>
            </div>
            <div class="data-fields">
                <div class="data-field"><label for="nombre">Nombre <span>*</span></label><input type="text" id="nombre" name="nombre" value="<?= $escape($cliente['nombre'] ?? '') ?>" required></div>
                <div class="data-field"><label for="apellido">Apellido <span>*</span></label><input type="text" id="apellido" name="apellido" value="<?= $escape($cliente['apellido'] ?? '') ?>" required></div>
                <div class="data-field"><label for="telefono">Teléfono</label><input type="text" id="telefono" name="telefono" value="<?= $escape($cliente['telefono'] ?? '') ?>" inputmode="tel"></div>
                <div class="data-field"><label for="telefono2">Teléfono alternativo</label><input type="text" id="telefono2" name="telefono2" value="<?= $escape($cliente['telefono2'] ?? '') ?>" inputmode="tel"></div>
                <div class="data-field data-field--wide"><label for="direccion">Dirección</label><input type="text" id="direccion" name="direccion" value="<?= $escape($cliente['direccion'] ?? '') ?>"></div>
                <div class="data-field"><label for="provincia">Provincia</label><input type="text" id="provincia" name="provincia" value="<?= $escape($cliente['provincia'] ?? '') ?>"></div>
                <div class="data-field"><label for="ciudad">Ciudad</label><input type="text" id="ciudad" name="ciudad" value="<?= $escape($cliente['ciudad'] ?? '') ?>"></div>
            </div>
        </section>

        <div class="data-grid">
            <section class="data-card" aria-labelledby="form-ars-title">
                <div class="data-card__heading">
                    <span class="data-card__icon"><i class="fas fa-university" aria-hidden="true"></i></span>
                    <div><h2 id="form-ars-title">Cuenta en pesos</h2><p>Estos datos se utilizan para recibir transferencias.</p></div>
                </div>
                <div class="data-fields data-fields--single">
                    <div class="data-field"><label for="cbu_titular">Titular <span>*</span></label><input type="text" id="cbu_titular" name="cbu_titular" value="<?= $escape($cliente['cbu_titular'] ?? '') ?>" required></div>
                    <div class="data-field"><label for="cbu">CBU / CVU <span>*</span></label><input type="text" id="cbu" name="cbu" value="<?= $escape($cliente['cbu'] ?? '') ?>" required inputmode="numeric"><small>Ingresá el número completo, sin espacios.</small></div>
                    <div class="data-field"><label for="alias">Alias <span>*</span></label><input type="text" id="alias" name="alias" value="<?= $escape($cliente['alias'] ?? '') ?>" required></div>
                </div>
            </section>

            <section class="data-card" aria-labelledby="form-usd-title">
                <div class="data-card__heading">
                    <span class="data-card__icon"><i class="fas fa-dollar-sign" aria-hidden="true"></i></span>
                    <div><h2 id="form-usd-title">Cuenta en dólares</h2><p>Completá únicamente los datos que correspondan.</p></div>
                </div>
                <div class="data-fields data-fields--single">
                    <div class="data-field"><label for="cbu_dolar">CBU / CVU dólares</label><input type="text" id="cbu_dolar" name="cbu_dolar" value="<?= $escape($cliente['cbu_dolar'] ?? '') ?>" inputmode="numeric"></div>
                    <div class="data-field"><label for="alias_dolar">Alias dólares</label><input type="text" id="alias_dolar" name="alias_dolar" value="<?= $escape($cliente['alias_dolar'] ?? '') ?>"></div>
                    <div class="data-field"><label for="cotizacion_dolar">Cotización del dólar</label><div class="data-input-prefix"><span>$</span><input type="text" id="cotizacion_dolar" name="cotizacion_dolar" value="<?= $escape($cliente['cotizacion_dolar'] ?? '') ?>" inputmode="decimal"></div><small>Valor de referencia utilizado actualmente.</small></div>
                </div>
            </section>
        </div>

        <div class="data-form__actions">
            <a href="?new=datos" class="data-button data-button--secondary">Cancelar</a>
            <button type="submit" name="guardar" class="data-button data-button--primary"><i class="fas fa-save" aria-hidden="true"></i> Guardar cambios</button>
        </div>
    </form>
</main>
<?php $conn->close(); ?>
