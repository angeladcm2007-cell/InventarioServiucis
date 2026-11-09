<?php
session_start();
require_once __DIR__ . '/includes/config.php';

if (empty($_SESSION['alogin'])) {
    header('Location: index.php');
    exit;
}

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$activos = array();
$errorListado = '';

/*
 * El inventario nuevo se almacena en activos y en una tabla de detalle
 * según la categoría. Se consultan todas las categorías mediante LEFT JOIN
 * para que un activo siga apareciendo aunque no tenga datos opcionales.
 */
$sql = "
    SELECT
        a.id,
        a.codigo_inventario,
        a.codigo_placa,
        a.descripcion,
        a.marca,
        a.modelo,
        a.numero_serial,
        a.estado,
        a.fecha_creacion,
        ca.nombre AS categoria,
        u.nombre AS ubicacion,
        s.nombre AS sede,
        COALESCE(
            ec.tipo_equipo,
            di.tipo_impresora,
            pe.tipo_periferico,
            te.tipo_equipo,
            se.hostname,
            inf.tipo_dispositivo,
            ''
        ) AS tipo_detalle,
        COALESCE(
            NULLIF(ec.descripcion_equipo, ''),
            NULLIF(di.observaciones_suministro, ''),
            NULLIF(pe.descripcion_periferico, ''),
            NULLIF(te.descripcion_telefonia, ''),
            NULLIF(se.descripcion_servidor, ''),
            NULLIF(inf.descripcion_infraestructura, ''),
            ''
        ) AS descripcion_detalle
    FROM activos a
    INNER JOIN categorias_activos ca ON ca.id = a.categoria_id
    LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
    LEFT JOIN sedes s ON s.id = u.sede_id
    LEFT JOIN equipos_computo ec ON ec.activo_id = a.id
    LEFT JOIN dispositivos_impresion di ON di.activo_id = a.id
    LEFT JOIN perifericos pe ON pe.activo_id = a.id
    LEFT JOIN telefonia te ON te.activo_id = a.id
    LEFT JOIN servidores se ON se.activo_id = a.id
    LEFT JOIN infraestructura inf ON inf.activo_id = a.id
    ORDER BY a.fecha_creacion DESC, a.id DESC
";

try {
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $activos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error al listar activos: ' . $e->getMessage());
    $errorListado = 'No fue posible cargar los activos. Verifica que la estructura de las tablas del inventario esté actualizada.';
}

$estados = array(
    'OPERATIVO_EN_USO' => 'Operativo / En uso',
    'EN_STOCK' => 'En stock',
    'EN_REPARACION' => 'En reparación',
    'REGULAR' => 'Regular',
    'DADO_DE_BAJA_OBSOLETO' => 'Dado de baja / Obsoleto'
);

$tipos = array(
    'ESCRITORIO' => 'Escritorio',
    'PORTATIL' => 'Portátil',
    'LASER' => 'Láser',
    'INYECCION_TINTA' => 'Inyección de tinta',
    'MATRICIAL' => 'Matricial',
    'TERMICA' => 'Térmica',
    'MULTIFUNCIONAL' => 'Multifuncional',
    'SMARTPHONE' => 'Smartphone',
    'TELEFONO_ESCRITORIO' => 'Teléfono de escritorio',
    'SIM_CARD' => 'SIM Card'
);
?>
<link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css"/>
<?php include('includes/topbar.php'); ?>
<div class="content-wrapper">
    <div class="content-container">
        <?php include('includes/leftbar.php'); ?>
        <div class="main-page">
            <div class="container-fluid">
                <div class="row page-title-div">
                    <div class="col-md-12">
                        <h2 class="title">Gestión de Activos</h2>
                    </div>
                </div>
                <div class="row breadcrumb-div">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                            <li>Gestión de Activos</li>
                            <li class="active">Ver Activos</li>
                        </ul>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Ver información de activos</h5>
                                    </div>
                                </div>
                                <div class="panel-body p-20">
                                    <?php if ($errorListado !== ''): ?>
                                        <div class="alert alert-danger" role="alert"><?php echo h($errorListado); ?></div>
                                    <?php elseif (count($activos) > 0): ?>
                                        <div class="table-responsive">
                                            <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Código de inventario</th>
                                                        <th>Tipo de activo</th>
                                                        <th>Tipo / referencia</th>
                                                        <th>Descripción</th>
                                                        <th>Etiqueta / placa</th>
                                                        <th>Marca</th>
                                                        <th>Modelo</th>
                                                        <th>Serial</th>
                                                        <th>Sede</th>
                                                        <th>Ubicación</th>
                                                        <th>Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($activos as $i => $activo): ?>
                                                        <?php
                                                        $tipo = $activo['tipo_detalle'];
                                                        if (isset($tipos[$tipo])) {
                                                            $tipo = $tipos[$tipo];
                                                        }
                                                        $descripcion = trim((string)$activo['descripcion']);
                                                        if ($descripcion === '') {
                                                            $descripcion = trim((string)$activo['descripcion_detalle']);
                                                        }
                                                        ?>
                                                        <tr>
                                                            <td><?php echo $i + 1; ?></td>
                                                            <td><a href="detalle-activo.php?id=<?php echo (int) $activo['id']; ?>" title="Consultar ficha completa y editar activo"><strong><?php echo h($activo['codigo_inventario']); ?></strong></a></td>
                                                            <td><?php echo h($activo['categoria']); ?></td>
                                                            <td><?php echo h($tipo !== '' ? $tipo : '—'); ?></td>
                                                            <td><?php echo h($descripcion !== '' ? $descripcion : '—'); ?></td>
                                                            <td><?php echo h($activo['codigo_placa'] !== '' ? $activo['codigo_placa'] : '—'); ?></td>
                                                            <td><?php echo h($activo['marca'] !== '' ? $activo['marca'] : '—'); ?></td>
                                                            <td><?php echo h($activo['modelo'] !== '' ? $activo['modelo'] : '—'); ?></td>
                                                            <td><?php echo h($activo['numero_serial'] !== '' ? $activo['numero_serial'] : '—'); ?></td>
                                                            <td><?php echo h($activo['sede'] !== '' ? $activo['sede'] : '—'); ?></td>
                                                            <td><?php echo h($activo['ubicacion'] !== '' ? $activo['ubicacion'] : '—'); ?></td>
                                                            <td><?php echo h(isset($estados[$activo['estado']]) ? $estados[$activo['estado']] : $activo['estado']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info" role="status">
                                            Todavía no hay activos registrados.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
<?php include('includes/footer.php'); ?>
