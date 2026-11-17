<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
sisatRequireLogin();

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function obtenerTotal(PDO $dbh, string $sql): int {
    return (int) $dbh->query($sql)->fetchColumn();
}

try {
    $totalActivos = obtenerTotal($dbh, "SELECT COUNT(*) FROM activos");
    $asignacionesActivas = obtenerTotal($dbh, "SELECT COUNT(*) FROM asignaciones WHERE estado_asignacion = 'ACTIVA'");

    // Personas del catálogo institucional: NO son las cuentas que inician sesión.
    $totalPersonas = obtenerTotal($dbh, "SELECT COUNT(*) FROM personas WHERE estado = 'ACTIVA'");

    // Cuentas habilitadas para ingresar a SISAT.
    $usuariosActivos = obtenerTotal($dbh, "SELECT COUNT(*) FROM usuarios_sistema WHERE estado = 'ACTIVO'");

    $mantenimientosPendientes = obtenerTotal($dbh, "SELECT COUNT(*) FROM mantenimientos WHERE resultado = 'PENDIENTE'");

    // Obtener los datos vigentes del usuario conectado y el nombre de su perfil.
    $sqlUsuario = "SELECT u.nombre_completo, u.usuario, u.estado, r.nombre AS rol_nombre
                   FROM usuarios_sistema u
                   LEFT JOIN roles r ON r.id = u.rol_id
                   WHERE u.id = :id
                   LIMIT 1";
    $stmtUsuario = $dbh->prepare($sqlUsuario);
    $stmtUsuario->execute([':id' => (int) $_SESSION['uid']]);
    $usuarioActual = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    if (!$usuarioActual) {
        $usuarioActual = [
            'nombre_completo' => $_SESSION['nombre_completo'] ?? 'Usuario del sistema',
            'usuario' => $_SESSION['alogin'] ?? '',
            'estado' => 'ACTIVO',
            'rol_nombre' => $_SESSION['rol'] ?? 'Perfil no identificado'
        ];
    }

    $sqlRecientes = "SELECT a.id, a.numero_control, a.fecha_asignacion,
                            ac.codigo_inventario, ac.descripcion AS activo_descripcion,
                            p.nombres AS persona_nombre, a.tipo_asignacion,
                            u.nombre AS ubicacion_nombre, s.nombre AS sede_nombre
                     FROM asignaciones a
                     INNER JOIN activos ac ON ac.id = a.activo_id
                     LEFT JOIN personas p ON p.id = a.persona_id
                     LEFT JOIN ubicaciones u ON u.id = ac.ubicacion_id
                     LEFT JOIN sedes s ON s.id = u.sede_id
                     ORDER BY a.fecha_creacion DESC, a.id DESC
                     LIMIT 10";
    $stmtRecientes = $dbh->query($sqlRecientes);
    $asignacionesRecientes = $stmtRecientes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error en dashboard: ' . $e->getMessage());
    http_response_code(500);
    exit('No fue posible cargar el panel. Verifique la estructura de la base de datos y la conexión.');
}
?>
<?php include __DIR__ . '/includes/topbar.php'; ?>
<style>
    .main-page .dashboard-summary-row {
        display: flex;
        flex-wrap: wrap;
        margin-left: -8px;
        margin-right: -8px;
        margin-bottom: 38px !important;
    }
    .main-page .dashboard-summary-row > [class*="col-"] {
        float: none;
        width: 20%;
        flex: 0 0 20%;
        max-width: 20%;
        padding-left: 8px;
        padding-right: 8px;
        margin-bottom: 15px;
    }
    .dashboard-stat {
        min-height: 125px;
        border-radius: 10px;
        border: 1px solid #e7edf0;
        box-shadow: 0 3px 12px rgba(15, 55, 65, .07);
        padding: 20px 14px;
        position: relative;
        overflow: hidden;
    }
    .dashboard-stat .number {
        display: block;
        color: #123f4a;
        font-size: 30px;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 7px;
    }
    .dashboard-stat .name {
        display: block;
        color: #62747b;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.35;
    }
    .dashboard-stat .bg-icon {
        color: #167d8d;
        opacity: .12;
        font-size: 48px;
        right: 12px;
        bottom: 8px;
    }
    .dashboard-user-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 19px 22px;
        margin: 10px 0 25px;
        border: 1px solid #dce9ec;
        border-left: 5px solid #167d8d;
        border-radius: 10px;
        background: linear-gradient(110deg, #f2f8f9 0%, #ffffff 75%);
        box-shadow: 0 4px 14px rgba(15, 55, 65, .06);
    }
    .dashboard-user-main { display: flex; align-items: center; gap: 14px; min-width: 0; }
    .dashboard-user-icon {
        width: 52px; height: 52px; min-width: 52px;
        border-radius: 50%; background: #0c4d58; color: #fff;
        display: flex; align-items: center; justify-content: center; font-size: 23px;
    }
    .dashboard-user-eyebrow { color: #167d8d; text-transform: uppercase; font-size: 10px; font-weight: 800; letter-spacing: 1px; margin-bottom: 3px; }
    .dashboard-user-name { color: #183f48; font-size: 17px; font-weight: 700; margin: 0 0 4px; overflow-wrap: anywhere; }
    .dashboard-user-meta { color: #64777d; font-size: 12px; margin: 0; }
    .dashboard-user-role {
        display: inline-block; padding: 7px 12px; border-radius: 20px;
        background: #e1f1f2; color: #0c5965; font-size: 11px; font-weight: 700;
        white-space: nowrap;
    }
    .dashboard-recent-row { margin-top: 0 !important; clear: both; }
    .dashboard-recent-row .panel { margin-top: 0; border-radius: 9px; overflow: hidden; }
    @media (max-width: 991px) {
        .main-page .dashboard-summary-row > [class*="col-"] { width: 33.333%; flex-basis: 33.333%; max-width: 33.333%; }
    }
    @media (max-width: 767px) {
        .main-page .dashboard-summary-row > [class*="col-"] { width: 50%; flex-basis: 50%; max-width: 50%; }
        .dashboard-user-card { align-items: flex-start; flex-direction: column; }
    }
    @media (max-width: 480px) {
        .main-page .dashboard-summary-row > [class*="col-"] { width: 100%; flex-basis: 100%; max-width: 100%; }
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
                        <p>Resumen general de activos, asignaciones, personas, usuarios y mantenimientos.</p>
                    </div>
                </div>

                <!-- Identificación destacada de la cuenta que inició sesión -->
                <div class="dashboard-user-card">
                    <div class="dashboard-user-main">
                        <div class="dashboard-user-icon"><i class="fa fa-user-circle"></i></div>
                        <div>
                            <div class="dashboard-user-eyebrow"><i class="fa fa-shield"></i> Sesión actual</div>
                            <p class="dashboard-user-name"><?php echo h($usuarioActual['nombre_completo']); ?></p>
                            <p class="dashboard-user-meta">
                                Usuario: <strong><?php echo h($usuarioActual['usuario']); ?></strong>
                                &nbsp;·&nbsp; Estado: <strong><?php echo h($usuarioActual['estado']); ?></strong>
                            </p>
                        </div>
                    </div>
                    <span class="dashboard-user-role"><i class="fa fa-id-badge"></i> <?php echo h($usuarioActual['rol_nombre'] ?: 'Perfil no asignado'); ?></span>
                </div>
            </div>

            <section class="section">
                <div class="container-fluid">
                    <div class="row dashboard-summary-row">
                        <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
                            <div class="dashboard-stat bg-white">
                                <span class="bg-icon"><i class="fa fa-laptop"></i></span>
                                <span class="number counter"><?php echo h($totalActivos); ?></span>
                                <span class="name">Activos registrados</span>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
                            <div class="dashboard-stat bg-white">
                                <span class="bg-icon"><i class="fa fa-share"></i></span>
                                <span class="number counter"><?php echo h($asignacionesActivas); ?></span>
                                <span class="name">Asignaciones activas</span>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
                            <div class="dashboard-stat bg-white">
                                <span class="bg-icon"><i class="fa fa-address-book"></i></span>
                                <span class="number counter"><?php echo h($totalPersonas); ?></span>
                                <span class="name">Personas activas (catálogo)</span>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
                            <div class="dashboard-stat bg-white">
                                <span class="bg-icon"><i class="fa fa-user"></i></span>
                                <span class="number counter"><?php echo h($usuariosActivos); ?></span>
                                <span class="name">Usuarios activos del sistema</span>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
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
                                                    <th>Modalidad / receptor</th>
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
                                                    <td><?php echo h(($fila['tipo_asignacion'] ?? 'PERSONA') === 'PERSONA' ? ($fila['persona_nombre'] ?: 'Sin persona') : 'Uso compartido en sede'); ?></td>
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
