<?php
session_start();
require_once __DIR__ . '/includes/config.php';
if (empty($_SESSION['alogin']) || empty($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}
function h($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$sql = "SELECT
            x.*,
            a.codigo_inventario,
            COALESCE(NULLIF(a.descripcion, ''), c.nombre) AS activo,
            a.estado AS estado_activo,
            x.funcionario_receptor AS persona,
            x.area_departamento_asignacion,
            u.nombre AS ubicacion,
            s.nombre AS sede,
            (SELECT GROUP_CONCAT(
                CONCAT(au.ubicacion_espacio,
                    IF(au.area_departamento IS NULL OR au.area_departamento = '', '', CONCAT(' — ', au.area_departamento)))
                SEPARATOR '; '
             ) FROM asignacion_areas_usuarias au WHERE au.asignacion_id = x.id) AS areas_compartidas,
            dev.id AS acta_devolucion_id,
            dev.numero_acta AS numero_acta_devolucion,
            dev.estado_documental AS estado_documental_devolucion,
            dev.estado_fisico_devolucion,
            dev.observaciones_devolucion,
            dev.fecha_generacion AS fecha_generacion_devolucion,
            dev.fecha_carga_firmada AS fecha_firma_devolucion
        FROM asignaciones x
        INNER JOIN activos a ON a.id = x.activo_id
        INNER JOIN categorias_activos c ON c.id = a.categoria_id
        LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
        LEFT JOIN sedes s ON s.id = u.sede_id
        LEFT JOIN (
            SELECT aa.*
            FROM actas_asignaciones aa
            INNER JOIN (
                SELECT asignacion_id, MAX(id) AS ultimo_id
                FROM actas_asignaciones
                WHERE tipo_acta = 'DEVOLUCION'
                GROUP BY asignacion_id
            ) ult ON ult.ultimo_id = aa.id
        ) dev ON dev.asignacion_id = x.id
        ORDER BY x.fecha_creacion DESC, x.id DESC";

try {
    $rows = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Gestión de asignaciones: ' . $e->getMessage());
    $rows = array();
    $error = 'No fue posible consultar las asignaciones y sus devoluciones. Verifica que la tabla actas_asignaciones esté instalada.';
}
?>
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="content-wrapper"><div class="content-container"><?php include __DIR__ . '/includes/leftbar.php'; ?><div class="main-page">
<div class="container-fluid">
    <div class="row page-title-div"><div class="col-md-12"><h2 class="title">Gestión de asignaciones</h2></div></div>
    <div class="row breadcrumb-div"><div class="col-md-12"><ul class="breadcrumb"><li><a href="dashboard.php">Inicio</a></li><li class="active">Gestión de asignaciones</li></ul></div></div>
</div>
<section class="section"><div class="container-fluid"><div class="panel">
    <div class="panel-heading"><div class="panel-title"><h5>Asignaciones y devoluciones registradas</h5></div></div>
    <div class="panel-body">
        <?php if (isset($_GET['registrada'])): ?><div class="alert alert-success">El registro administrativo se creó. La entrega aún está pendiente de firma y no se considera activa hasta guardar el PDF firmado.</div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo h($error); ?></div><?php endif; ?>
        <div class="clearfix" style="margin-bottom:15px"><a href="add-asignacion.php" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> Agregar asignación</a></div>
        <div class="table-responsive">
            <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                <thead><tr>
                    <th>N.º control</th><th>Código inventario</th><th>Activo</th><th>Modalidad</th>
                    <th>Persona receptora</th><th>Área/departamento de entrega</th><th>Sede</th><th>Ubicación física</th>
                    <th>Áreas / oficinas usuarias</th><th>Fecha asignación</th><th>Estado asignación</th>
                    <th>Devolución</th><th>Fecha devolución</th><th>Condición al recibir</th><th>Acta de devolución</th><th>Actas / documentos</th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo h($r['numero_control']); ?></td>
                        <td><?php echo h($r['codigo_inventario']); ?></td>
                        <td><?php echo h($r['activo']); ?></td>
                        <td><?php echo $r['tipo_asignacion'] === 'PERSONA' ? 'Entrega a persona' : 'Uso compartido en sede'; ?></td>
                        <td><?php echo h($r['persona'] ?: 'No aplica'); ?></td>
                        <td><?php echo h($r['area_departamento_asignacion'] ?: '—'); ?></td>
                        <td><?php echo h($r['sede'] ?: '—'); ?></td>
                        <td><?php echo h($r['ubicacion'] ?: '—'); ?></td>
                        <td><?php echo h($r['areas_compartidas'] ?: '—'); ?></td>
                        <td><?php echo h($r['fecha_asignacion']); ?></td>
                        <td><?php echo $r['estado_asignacion'] === 'PENDIENTE_FIRMA' ? 'PENDIENTE DE FIRMA' : h($r['estado_asignacion']); ?></td>
                        <td>
                            <?php if (empty($r['acta_devolucion_id'])): ?>
                                <span class="label label-default">Sin devolución</span>
                            <?php elseif ($r['estado_documental_devolucion'] === 'FIRMADA'): ?>
                                <span class="label label-success">Devuelta / firmada</span>
                            <?php else: ?>
                                <span class="label label-warning">Devolución pendiente de firma</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo h($r['fecha_devolucion'] ?: '—'); ?></td>
                        <td><?php echo h($r['estado_fisico_devolucion'] ?: '—'); ?></td>
                        <td>
                            <?php if (!empty($r['acta_devolucion_id'])): ?>
                                <a href="imprimir-acta-asignacion.php?id=<?php echo (int)$r['acta_devolucion_id']; ?>" target="_blank"><?php echo h($r['numero_acta_devolucion'] ?: 'Ver acta'); ?></a>
                                <br><small><?php echo h($r['estado_documental_devolucion']); ?></small>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td><a class="btn btn-xs btn-info" href="actas-asignacion.php?id=<?php echo (int)$r['id']; ?>"><i class="fa fa-file-pdf-o"></i> Gestionar actas</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="16" class="text-center">Todavía no hay asignaciones registradas.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div></div></section>
</div></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
