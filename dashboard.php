<?php
session_start();
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['alogin']) || $_SESSION['alogin'] === '') {
    header('Location: index.php');
    exit;
}

function h($value) {
    return htmlentities((string)$value, ENT_QUOTES, 'UTF-8');
}

function obtenerTotal($dbh, $sql) {
    $stmt = $dbh->query($sql);
    return (int)$stmt->fetchColumn();
}


try {
    // Contar los activos registrados en las tablas secundarias
    $totalActivos = obtenerTotal(
        $dbh,
        "SELECT
            (SELECT COUNT(*) FROM equipos_computo) +
            (SELECT COUNT(*) FROM dispositivos_impresion)"
    );

    // Los demás indicadores permanecen sin cambios
    $asignacionesActivas = obtenerTotal($dbh, "SELECT COUNT(*) FROM asignaciones WHERE estado_asignacion = 'ACTIVA'");
    $totalPersonas = obtenerTotal($dbh, "SELECT COUNT(*) FROM personas WHERE estado = 'ACTIVA'");
    $mantenimientosPendientes = obtenerTotal($dbh, "SELECT COUNT(*) FROM mantenimientos WHERE resultado = 'PENDIENTE'");

    $sqlRecientes = "SELECT a.id, a.numero_control, a.fecha_asignacion,
                            ac.codigo_inventario, ac.descripcion AS activo_descripcion,
                            p.nombres AS persona_nombre,
                            u.nombre AS ubicacion_nombre, s.nombre AS sede_nombre
                     FROM asignaciones a
                     INNER JOIN activos ac ON ac.id = a.activo_id
                     INNER JOIN personas p ON p.id = a.persona_id
                     LEFT JOIN ubicaciones u ON u.id = ac.ubicacion_id
                     LEFT JOIN sedes s ON s.id = u.sede_id
                     ORDER BY a.fecha_creacion DESC, a.id DESC
                     LIMIT 10";

    $stmtRecientes = $dbh->query($sqlRecientes);
    $asignacionesRecientes = $stmtRecientes->fetchAll(PDO::FETCH_ASSOC);
}
 catch (PDOException $e) {
    error_log('Error en dashboard: ' . $e->getMessage());
    http_response_code(500);
    exit('No fue posible cargar el panel. Verifique la estructura de la base de datos y la conexión.');
}
?>
<?php include __DIR__ . '/includes/topbar.php'; ?>
<style>
/* Separación clara entre el resumen y el panel de asignaciones */
.main-page .dashboard-summary-row {
    margin-bottom: 50px !important;
}
.main-page .dashboard-recent-row {
    margin-top: 0 !important;
    clear: both;
}
.main-page .dashboard-recent-row > [class*="col-"] {
    margin-top: 0;
}
.main-page .dashboard-recent-row .panel {
    margin-top: 0;
}
@media (max-width: 767px) {
    .main-page .dashboard-summary-row {
        margin-bottom: 30px !important;
    }
}
</style>

<div class="content-wrapper">
    <div class="content-container">
        <?php include __DIR__ . '/includes/leftbar.php'; ?>
        <div class="main-page">
            <div class="container-fluid">
                <div class="row page-title-div">
                    <div class="col-sm-12">
                        <h2 class="title">Panel principal - Inventario tecnológico SERVIUCIS</h2>
                        <p>Resumen general de activos, asignaciones, responsables y mantenimientos.</p>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="container-fluid">
                    <div class="row dashboard-summary-row">
                        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                            <div class="dashboard-stat bg-white">
                                <span class="bg-icon"><i class="fa fa-laptop"></i></span>
                                <span class="number counter"><?php echo h($totalActivos); ?></span>
                                <span class="name">Activos registrados</span>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                            <div class="dashboard-stat bg-white">
                                <span class="bg-icon"><i class="fa fa-share"></i></span>
                                <span class="number counter"><?php echo h($asignacionesActivas); ?></span>
                                <span class="name">Asignaciones activas</span>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                            <div class="dashboard-stat bg-white">
                                <span class="bg-icon"><i class="fa fa-users"></i></span>
                                <span class="number counter"><?php echo h($totalPersonas); ?></span>
                                <span class="name">Personas activas</span>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                            <div class="dashboard-stat bg-white">
                                <span class="bg-icon"><i class="fa fa-wrench"></i></span>
                                <span class="number counter"><?php echo h($mantenimientosPendientes); ?></span>
                                <span class="name">Mantenimientos pendientes</span>
                            </div>
                        </div>
                    </div>

                    <div class="row dashboard-recent-row">
                        <div class="col-md-12">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title"><h5>Asignaciones recientes</h5></div>
                                </div>
                                <div class="panel-body p-20">
                                    <div class="table-responsive">
                                        <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                            <thead>
                                                <tr>
                                                    <th>N.º</th>
                                                    <th>N.º de control</th>
                                                    <th>Código de inventario</th>
                                                    <th>Activo</th>
                                                    <th>Persona asignada</th>
                                                    <th>Sede</th>
                                                    <th>Ubicación</th>
                                                    <th>Fecha de asignación</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php if (count($asignacionesRecientes) > 0) { $contador = 1; ?>
                                                <?php foreach ($asignacionesRecientes as $fila) { ?>
                                                <tr>
                                                    <td><?php echo $contador++; ?></td>
                                                    <td><?php echo h($fila['numero_control']); ?></td>
                                                    <td><?php echo h($fila['codigo_inventario']); ?></td>
                                                    <td><?php echo h($fila['activo_descripcion']); ?></td>
                                                    <td><?php echo h($fila['persona_nombre']); ?></td>
                                                    <td><?php echo h($fila['sede_nombre']); ?></td>
                                                    <td><?php echo h($fila['ubicacion_nombre']); ?></td>
                                                    <td><?php echo h($fila['fecha_asignacion']); ?></td>
                                                </tr>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <tr><td colspan="8" class="text-center">Todavía no hay asignaciones registradas.</td></tr>
                                            <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
