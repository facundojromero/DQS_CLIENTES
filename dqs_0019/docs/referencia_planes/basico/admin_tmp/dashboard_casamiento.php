<?php
session_start(); // Iniciar la sesión al principio del script

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirigir a login.php si no hay sesión
    exit();
}

// Incluir conexión desde el archivo conexion.php
include '../conexion.php';

// 2. Obtener los valores de los filtros de la URL (GET)
$confirmacionFiltro = isset($_GET['confirmacion']) ? $conn->real_escape_string($_GET['confirmacion']) : '';
$busquedaFiltro     = isset($_GET['busqueda'])     ? $conn->real_escape_string($_GET['busqueda'])     : '';
$statusFiltro       = isset($_GET['status'])       ? $conn->real_escape_string($_GET['status'])       : ''; // 0 o 1
$ingresoFiltro      = isset($_GET['ingreso'])      ? $conn->real_escape_string($_GET['ingreso'])      : '';
$prioridadFiltro    = isset($_GET['prioridad'])    ? $conn->real_escape_string($_GET['prioridad'])    : ''; // ID de prioridad

// 3. Construir la cláusula WHERE base para aplicar a las consultas de invitados (excluye borrados)
$whereClause = " WHERE a.activo < 2 ";

if ($statusFiltro !== '') {
    $whereClause .= " AND a.activo = '$statusFiltro' ";
}
if ($confirmacionFiltro !== '') {
    if ($confirmacionFiltro == 'NULL') { // Filtra por invitados con confirmacion IS NULL (Pendientes)
        $whereClause .= " AND a.confirmacion IS NULL ";
    } else { // Filtra por 'Si' o 'No'
        $whereClause .= " AND a.confirmacion = '$confirmacionFiltro' ";
    }
}
if ($busquedaFiltro !== '') {
    // Asumo que 'nombre' y 'apellido' son las columnas para la búsqueda, ajusta si es necesario
    $whereClause .= " AND (a.nombre LIKE '%$busquedaFiltro%' OR a.apellido LIKE '%$busquedaFiltro%') ";
}
if ($ingresoFiltro !== '') {
    $whereClause .= " AND a.ingreso = '$ingresoFiltro' ";
}
if ($prioridadFiltro !== '') {
    $whereClause .= " AND a.id_prioridad = '$prioridadFiltro' ";
}


// 3.1 Cláusula WHERE para KPIs de Mensajes (sin considerar 'activo < 2' inicialmente)
$whereClauseMensajes = " WHERE 1=1 ";

if ($statusFiltro !== '') {
    $whereClauseMensajes .= " AND a.activo = '$statusFiltro' ";
}
if ($confirmacionFiltro !== '') {
    if ($confirmacionFiltro == 'NULL') {
        $whereClauseMensajes .= " AND a.confirmacion IS NULL ";
    } else {
        $whereClauseMensajes .= " AND a.confirmacion = '$confirmacionFiltro' ";
    }
}
if ($busquedaFiltro !== '') {
    $whereClauseMensajes .= " AND (a.nombre LIKE '%$busquedaFiltro%' OR a.apellido LIKE '%$busquedaFiltro%') ";
}
if ($ingresoFiltro !== '') {
    $whereClauseMensajes .= " AND a.ingreso = '$ingresoFiltro' ";
}
if ($prioridadFiltro !== '') {
    $whereClauseMensajes .= " AND a.id_prioridad = '$prioridadFiltro' ";
}


// 4. Consultas para KPIs principales (ahora aplicando los filtros)

// KPI: Total de personas esperadas
$sql_total = "SELECT SUM(cantidad_mayores + cantidad_menores) AS total FROM invitados a " . $whereClause;
$total_result = $conn->query($sql_total);
$total = $total_result ? $total_result->fetch_assoc()['total'] : 0;


// Nuevo KPI: Total de mensajes de registro de enviados
$sql_mensajes_registrados = "SELECT COUNT(*) cantidad FROM registro_mensajes_enviados";
$mensajes_registrados_result = $conn->query($sql_mensajes_registrados);
$mensajes_registrados = $mensajes_registrados_result ? $mensajes_registrados_result->fetch_assoc()['cantidad'] : 0;

// KPI: Total de personas confirmadas
$sql_confirmados = "SELECT SUM(cantidad_mayores + cantidad_menores) AS confirmados FROM invitados a " . $whereClause . " AND a.confirmacion = 'Si'";
$confirmados_result = $conn->query($sql_confirmados);
$confirmados = $confirmados_result ? $confirmados_result->fetch_assoc()['confirmados'] : 0;

// KPI: Total de personas NO confirmadas
$sql_no_confirmados = "SELECT SUM(cantidad_mayores + cantidad_menores) AS no_confirmados FROM invitados a " . $whereClause . " AND a.confirmacion = 'No'";
$no_confirmados_result = $conn->query($sql_no_confirmados);
$no_confirmados = $no_confirmados_result ? $no_confirmados_result->fetch_assoc()['no_confirmados'] : 0;

// KPI: Total de personas PENDIENTES de confirmación
$sql_pendientes = "SELECT SUM(cantidad_mayores + cantidad_menores) AS pendientes FROM invitados a " . $whereClause . " AND a.confirmacion IS NULL";
$pendientes_result = $conn->query($sql_pendientes);
$pendientes = $pendientes_result ? $pendientes_result->fetch_assoc()['pendientes'] : 0;

// KPI: Porcentaje de confirmación
$porcentaje_confirmados = ($total > 0) ? round(($confirmados / $total) * 100, 2) : 0;

// NUEVOS KPIs de Mensajes
$sql_mensajes_enviados = "SELECT COUNT(*) AS cantidad FROM invitados a LEFT JOIN invitados_tel b ON a.id = b.id_invitados INNER JOIN invitaciones_estado c ON a.id = c.id_invitado " . $whereClauseMensajes . " AND c.estado_api='enviado'";
$mensajes_enviados_result = $conn->query($sql_mensajes_enviados);
$mensajes_enviados = $mensajes_enviados_result ? $mensajes_enviados_result->fetch_assoc()['cantidad'] : 0;

$sql_mensajes_error = "SELECT COUNT(*) AS cantidad FROM invitados a LEFT JOIN invitados_tel b ON a.id = b.id_invitados LEFT JOIN invitaciones_estado c ON a.id = c.id_invitado " . $whereClauseMensajes . " AND c.estado_api <> 'enviado'";
$mensajes_error_result = $conn->query($sql_mensajes_error);
$mensajes_error = $mensajes_error_result ? $mensajes_error_result->fetch_assoc()['cantidad'] : 0;

$sql_mensajes_para_enviar = "SELECT COUNT(*) AS cantidad FROM invitados a LEFT JOIN invitados_tel b ON a.id = b.id_invitados LEFT JOIN invitaciones_estado c ON a.id = c.id_invitado " . $whereClauseMensajes . " AND a.activo=1 AND c.id_invitado IS NULL AND a.confirmacion IS NULL";
$mensajes_para_enviar_result = $conn->query($sql_mensajes_para_enviar);
$mensajes_para_enviar = $mensajes_para_enviar_result ? $mensajes_para_enviar_result->fetch_assoc()['cantidad'] : 0;


// 5. Datos para gráficos (aplicando filtros y agrupando)

// Gráfico: Por Tipo de Acompañante
$sql_por_acompanante = "
    SELECT IFNULL(b.categoria_acompanante, 'Sin Asignar') AS categoria, COUNT(a.id) AS cantidad
    FROM invitados a
    LEFT JOIN intivados_acompanante b ON a.acompanado = b.id
    " . $whereClause . "
    GROUP BY b.categoria_acompanante
    ORDER BY categoria ASC
";
$por_acompanante = $conn->query($sql_por_acompanante);

// Gráfico: Por Prioridad
$sql_por_prioridad = "
    SELECT IFNULL(f.categoria_prioridad, 'Sin Asignar') AS categoria, COUNT(a.id) AS cantidad
    FROM invitados a
    LEFT JOIN invitados_prioridad f ON a.id_prioridad = f.id
    " . $whereClause . "
    GROUP BY f.categoria_prioridad
    ORDER BY categoria ASC
";
$por_prioridad = $conn->query($sql_por_prioridad);

// Gráfico: Por Tipo de Ingreso al Evento
$sql_por_ingreso = "
    SELECT IFNULL(ingreso, 'Sin Especificar') AS categoria, COUNT(a.id) AS cantidad
    FROM invitados a
    " . $whereClause . "
    GROUP BY ingreso
    ORDER BY categoria ASC
";
$por_ingreso = $conn->query($sql_por_ingreso);

// Gráfico: Por Restricciones Alimentarias
$sql_por_alimento = "
    SELECT IFNULL(alimento, 'Sin Restricción') AS categoria, COUNT(a.id) AS cantidad
    FROM invitados a
    " . $whereClause . "
    GROUP BY alimento
    ORDER BY categoria ASC
";
$por_alimento = $conn->query($sql_por_alimento);

// NUEVAS CONSULTAS
// Gráfico: Usuarios únicos por día (requiere MySQL 8.0+)
$sql_usuarios_por_dia = "
    WITH visitas_con_sesion AS (
        SELECT 
            *,
            CASE 
                WHEN TIMESTAMPDIFF(MINUTE,
                    LAG(fecha_visita) OVER (PARTITION BY ip_usuario ORDER BY fecha_visita),
                    fecha_visita
                ) <= 30
                THEN 0
                ELSE 1
            END AS nueva_sesion
        FROM visitas
    )
    , sesiones AS (
        SELECT
            *,
            SUM(nueva_sesion) OVER (PARTITION BY ip_usuario ORDER BY fecha_visita) AS id_sesion
        FROM visitas_con_sesion
    )
    SELECT 
        DATE(fecha_visita) AS categoria,
        COUNT(DISTINCT CONCAT(ip_usuario, '-', id_sesion)) AS cantidad
    FROM sesiones
    GROUP BY categoria
    ORDER BY categoria;
";
$usuarios_por_dia_result = $conn->query($sql_usuarios_por_dia);

// Gráfico: Usuarios únicos por página y día (requiere MySQL 8.0+)
$sql_paginas_por_dia = "
    WITH visitas_con_sesion AS (
        SELECT 
            id,
            fecha_visita,
            ip_usuario,
                CASE 
        WHEN pagina_visitada LIKE '%tienda%' THEN 'Tienda'
        WHEN pagina_visitada LIKE '%%' THEN 'Inicio'
        ELSE pagina_visitada
    END pagina_visitada,
            CASE 
                WHEN TIMESTAMPDIFF(MINUTE,
                    LAG(fecha_visita) OVER (PARTITION BY ip_usuario,     CASE 
        WHEN pagina_visitada LIKE '%tienda%' THEN 'Tienda'
        WHEN pagina_visitada LIKE '%%' THEN 'Inicio'
        ELSE pagina_visitada
	END ORDER BY fecha_visita),
                    fecha_visita
                ) <= 30
                THEN 0
                ELSE 1
            END AS nueva_sesion
        FROM visitas
    )
    , sesiones AS (
        SELECT
            *,
            SUM(nueva_sesion) OVER (PARTITION BY ip_usuario, pagina_visitada ORDER BY fecha_visita) AS id_sesion
        FROM visitas_con_sesion
    )
    SELECT 
        DATE(fecha_visita) AS dia,
        pagina_visitada,
        COUNT(DISTINCT CONCAT(ip_usuario, '-', id_sesion)) AS usuarios_unicos
    FROM sesiones
    GROUP BY dia, pagina_visitada
    ORDER BY dia, pagina_visitada;
";
$paginas_por_dia_result = $conn->query($sql_paginas_por_dia);


// Función para obtener los datos de un resultado de consulta para Chart.js
function getChartData($mysqli_result) {
    $labels = [];
    $data = [];
    if ($mysqli_result) {
        while ($row = $mysqli_result->fetch_assoc()) {
            $labels[] = $row['categoria'];
            $data[] = (int)$row['cantidad'];
        }
    }
    return ['labels' => json_encode($labels), 'data' => json_encode($data)];
}

// Obtener los datos preparados para JavaScript
$acompananteData = getChartData($por_acompanante);
$prioridadData   = getChartData($por_prioridad);
$ingresoData     = getChartData($por_ingreso);
$alimentoData    = getChartData($por_alimento);
$usuarios_por_dia_data = getChartData($usuarios_por_dia_result);

// Procesamiento específico para el gráfico de múltiples líneas
$paginas_labels = [];
$paginas_data_sets = [];
if ($paginas_por_dia_result) {
    $temp_data = [];
    $all_dates = [];
    $all_pages = [];
    while ($row = $paginas_por_dia_result->fetch_assoc()) {
        $date = $row['dia'];
        $page = $row['pagina_visitada'];
        $count = $row['usuarios_unicos'];
        $all_dates[$date] = true;
        $all_pages[$page] = true;
        if (!isset($temp_data[$page])) {
            $temp_data[$page] = [];
        }
        $temp_data[$page][$date] = $count;
    }
    $sorted_dates = array_keys($all_dates);
    sort($sorted_dates);
    $paginas_labels = $sorted_dates;
    $colors = ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)', 'rgba(255, 206, 86, 1)', 'rgba(75, 192, 192, 1)'];
    $color_index = 0;
    foreach ($all_pages as $page => $val) {
        $data_array = [];
        foreach ($sorted_dates as $date) {
            $data_array[] = isset($temp_data[$page][$date]) ? (int)$temp_data[$page][$date] : 0;
        }
        $paginas_data_sets[] = [
            'label' => $page,
            'data' => $data_array,
            'borderColor' => $colors[$color_index % count($colors)],
            'backgroundColor' => $colors[$color_index % count($colors)],
            'borderWidth' => 2,
            'fill' => false
        ];
        $color_index++;
    }
}
$paginas_data_sets_json = json_encode($paginas_data_sets);
$paginas_labels_json = json_encode($paginas_labels);

// Cerrar la conexión
// $conn->close();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Invitados para Casamiento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OerWCXHryh3fLqQoFwX/U9Qk9ccbX3sI10Mh2H2x9xJ/K1" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-Fo3rlrZj/k7ujTnHg4CGR2D7kSs0pReG7oNlRS48R2z4z0G2K/e3W2n1p0A0M3h6t2/8Q0N3h0P0z0T0K0z0Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="combined-styles.css"> <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="combined-styles.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> </head>
<body>

<h2>Resumen</h2>

    <div class="search-container">
        <div class="search-item">
            <label for="searchInput">Buscar invitados:</label>
            <input type="text" id="searchInput" class="search-input" placeholder="Buscar invitados..." value="<?php echo htmlspecialchars($busquedaFiltro); ?>" onkeyup="applyFilter()">
        </div>
        <div class="search-item">
            <label for="confirmationFilter">Filtrar por confirmación:</label>
            <select id="confirmationFilter" class="search-input" onchange="applyFilter()">
                <option value="">Todos</option>
                <option value="Si" <?php if ($confirmacionFiltro == 'Si') echo 'selected'; ?>>Si</option>
                <option value="No" <?php if ($confirmacionFiltro == 'No') echo 'selected'; ?>>No</option>
                <option value="NULL" <?php if ($confirmacionFiltro == 'NULL') echo 'selected'; ?>>No Confirmado</option>
            </select>
        </div>
        <div class="search-item">
            <label for="statusFilter">Filtrar por estado:</label>
            <select id="statusFilter" class="search-input" onchange="applyFilter()">
                <option value="">Todos</option>
                <option value="1" <?php if ($statusFiltro == '1') echo 'selected'; ?>>Activos</option>
                <option value="0" <?php if ($statusFiltro == '0') echo 'selected'; ?>>Inactivos</option>
            </select>
        </div>
        <div class="search-item">
            <label for="ingresoFilter">Filtrar por ingreso:</label>
            <select id="ingresoFilter" class="search-input" onchange="applyFilter()">
                <option value="">Todos</option>
                <option value="Inicio" <?php if ($ingresoFiltro == 'Inicio') echo 'selected'; ?>>Inicio</option>
                <option value="Tarde" <?php if ($ingresoFiltro == 'Tarde') echo 'selected'; ?>>Tarde</option>
                </select>
        </div>
        <div class="search-item">
            <label for="prioridadFilter">Filtrar por prioridad:</label>
            <select id="prioridadFilter" class="search-input" onchange="applyFilter()">
                <option value="">Todos</option>
                <option value="1" <?php if ($prioridadFiltro == '1') echo 'selected'; ?>>Importante</option>
                <option value="2" <?php if ($prioridadFiltro == '2') echo 'selected'; ?>>Medio Importante</option>
                <option value="3" <?php if ($prioridadFiltro == '3') echo 'selected'; ?>>Normal</option>
                <option value="4" <?php if ($prioridadFiltro == '4') echo 'selected'; ?>>No Necesario</option>
            </select>
        </div>
            <div class="search-item">
            <button type="button" class="navbar-link" onclick="resetFilters()">
            <i class="fas fa-redo navbar-icon"></i> Resetear
            </button>
            </div>
    </div>

        
    <div class="kpi-row">
        <div class="col-md-4 col-lg-2">
            <div class="kpi-card kpi-card-total">
                <div class="icon"><i class="fas fa-users"></i></div>
                <h3>Total Esperado</h3>
                <div class="value"><?php echo $total; ?></div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="kpi-card kpi-card-confirmados">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <h3>Confirmados</h3>
                <div class="value"><?php echo $confirmados; ?></div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="kpi-card kpi-card-no-confirmados">
                <div class="icon"><i class="fas fa-times-circle"></i></div>
                <h3>No Confirmados</h3>
                <div class="value"><?php echo $no_confirmados; ?></div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="kpi-card kpi-card-pendientes">
                <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                <h3>Pendientes</h3>
                <div class="value"><?php echo $pendientes; ?></div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="kpi-card kpi-card-porcentaje">
                <div class="icon"><i class="fas fa-percent"></i></div>
                <h3>% de Confirmación</h3>
                <div class="value"><?php echo $porcentaje_confirmados; ?>%</div>
            </div>
        </div>
        
        <div class="col-md-4 col-lg-2">
            <div class="kpi-card kpi-card-total-mensajes-registrados">
                <div class="icon"><i class="fab fa-whatsapp"></i></div>
                <h3>Enviados</h3>
                <div class="value"><?php echo $mensajes_registrados; ?></div>
            </div>
        </div>
        
        <div class="col-md-4 col-lg-2">
            <div class="kpi-card kpi-card-mensajes-enviados">
                <div class="icon"><i class="fas fa-paper-plane"></i></div>
                <h3>Enviados Automaticos</h3>
                <div class="value"><?php echo $mensajes_enviados; ?></div>
            </div>
        </div>
        
        <!--
        <div class="col-md-4 col-lg-2">
            <div class="kpi-card kpi-card-mensajes-error">
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                <h3>Msj. Con Error</h3>
                <div class="value"><?php echo $mensajes_error; ?></div>
            </div>
        </div>
        -->
        
        <div class="col-md-4 col-lg-2">
            <div class="kpi-card kpi-card-mensajes-para-enviar">
                <div class="icon"><i class="fas fa-envelope"></i></div>
                <h3>Enviar automaticamente</h3>
                <div class="value"><?php echo $mensajes_para_enviar; ?></div>
            </div>
        </div>
    </div>


<div class="chart-row">
    
    
        <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-container">
                <canvas id="usuariosPorDiaChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-container">
                <canvas id="paginasPorDiaChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-container">
                <canvas id="acompananteChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-container">
                <canvas id="prioridadChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-container">
                <canvas id="ingresoChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="chart-card">
            <div class="chart-container">
                <canvas id="alimentoChart"></canvas>
            </div>
        </div>
    </div>

</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcqNVwzJ8L2FfQfK3T" crossorigin="anonymous"></script>

    <script>
        // Función para aplicar filtros y actualizar la URL
        function applyFilter() {
            var confirmation = document.getElementById('confirmationFilter').value;
            var search = document.getElementById('searchInput').value;
            var status = document.getElementById('statusFilter').value;
            var ingreso = document.getElementById('ingresoFilter').value;
            var prioridad = document.getElementById('prioridadFilter').value;
            var url = "?new=inicio&";
            var params = [];
            if (confirmation) params.push("confirmacion=" + confirmation);
            if (search) params.push("busqueda=" + encodeURIComponent(search));
            if (status) params.push("status=" + status);
            if (ingreso) params.push("ingreso=" + encodeURIComponent(ingreso));
            if (prioridad) params.push("prioridad=" + prioridad);
            window.location.href = url + params.join("&");
        }

        // Función para resetear los filtros
        function resetFilters() {
            window.location.href = "?new=inicio&";
        }

        // Función para renderizar un gráfico Chart.js
        function renderChart(canvasId, title, labels, data, type = 'bar') {
            const ctx = document.getElementById(canvasId);
            if (!ctx) {
                console.error('Canvas ID not found:', canvasId);
                return;
            }
            new Chart(ctx.getContext('2d'), {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        label: title,
                        data: data,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(199, 199, 199, 0.7)',
                            'rgba(83, 102, 255, 0.7)',
                            'rgba(255, 0, 255, 0.7)',
                            'rgba(0, 255, 255, 0.7)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(199, 199, 199, 1)',
                            'rgba(83, 102, 255, 1)',
                            'rgba(255, 0, 255, 1)',
                            'rgba(0, 255, 255, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    size: 14
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: title,
                            font: {
                                size: 18,
                                weight: 'bold'
                            },
                            padding: {
                                top: 10,
                                bottom: 20
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // Llamadas a la función renderChart para cada gráfico
        renderChart('acompananteChart', 'Personas por Tipo de Acompañante', <?php echo $acompananteData['labels']; ?>, <?php echo $acompananteData['data']; ?>, 'pie');
        renderChart('prioridadChart', 'Personas por Prioridad', <?php echo $prioridadData['labels']; ?>, <?php echo $prioridadData['data']; ?>, 'doughnut');
        renderChart('ingresoChart', 'Personas por Tipo de Ingreso al Evento', <?php echo $ingresoData['labels']; ?>, <?php echo $ingresoData['data']; ?>, 'bar');
        renderChart('alimentoChart', 'Personas con Restricciones Alimentarias', <?php echo $alimentoData['labels']; ?>, <?php echo $alimentoData['data']; ?>, 'bar');

        // Gráfico de Usuarios Únicos por Día (gráfico de líneas)
        const ctxUsuarios = document.getElementById('usuariosPorDiaChart');
        if (ctxUsuarios) {
            new Chart(ctxUsuarios.getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?php echo $usuarios_por_dia_data['labels']; ?>,
                    datasets: [{
                        label: 'Visitas únicas',
                        data: <?php echo $usuarios_por_dia_data['data']; ?>,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Visitas Únicos por Día',
                            font: {
                                size: 18,
                                weight: 'bold'
                            },
                            padding: {
                                top: 10,
                                bottom: 20
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Gráfico de Usuarios Únicos por Página y Día (gráfico de líneas con múltiples datasets)
        const ctxPaginas = document.getElementById('paginasPorDiaChart');
        if (ctxPaginas) {
            new Chart(ctxPaginas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?php echo $paginas_labels_json; ?>,
                    datasets: <?php echo $paginas_data_sets_json; ?>
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Visitas Únicos por Página y Día',
                            font: {
                                size: 18,
                                weight: 'bold'
                            },
                            padding: {
                                top: 10,
                                bottom: 20
                            }
                        },
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>