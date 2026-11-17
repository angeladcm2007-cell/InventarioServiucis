<?php
require_once __DIR__ . '/includes/auth.php';
sisatRequireRoles([1, 2]);
require_once __DIR__ . '/includes/config.php';

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activoId = (int)($_POST['activo_id'] ?? 0);
    $tipo = $_POST['tipo_mantenimiento'] ?? '';
    $fecha = trim($_POST['fecha_programada'] ?? '');
    $actividad = trim($_POST['diagnostico'] ?? '');
    $tecnico = trim($_POST['tecnico_responsable'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');

    if (!$activoId || !in_array($tipo, ['PREVENTIVO', 'CORRECTIVO', 'REPARACION'], true) || !$fecha || !$actividad) {
        $error = 'Complete el activo, tipo, fecha programada y actividad.';
    } else {
        $check = $dbh->prepare("SELECT id FROM activos WHERE id = ? AND estado <> 'DADO_DE_BAJA_OBSOLETO'");
        $check->execute([$activoId]);
        if (!$check->fetchColumn()) {
            $error = 'El activo seleccionado no existe o está dado de baja.';
        } else {
            try {
                $dbh->beginTransaction();
                // Cada programación individual obtiene una jornada propia de un solo equipo.
                $nombre = 'Jornada individual - ' . $tipo . ' - ' . $fecha;
                $j = $dbh->prepare("INSERT INTO jornadas_mantenimiento
                    (nombre, tipo_mantenimiento, fecha_programada, actividad, tecnico_responsable,
                     periodicidad_meses, proxima_fecha_manual, observaciones, registrado_por)
                    VALUES (?, ?, ?, ?, ?, NULL, NULL, ?, ?)");
                $j->execute([$nombre, $tipo, $fecha, $actividad, $tecnico ?: null,
                    $observaciones ?: null, (int)$_SESSION['uid']]);
                $jornadaId = (int)$dbh->lastInsertId();

                $m = $dbh->prepare("INSERT INTO mantenimientos
                    (activo_id, tipo_mantenimiento, fecha_programada, diagnostico, tecnico_responsable,
                     observaciones, resultado, registrado_por, jornada_id, estado_atencion)
                    VALUES (?, ?, ?, ?, ?, ?, 'PENDIENTE', ?, ?, 'PENDIENTE')");
                $m->execute([$activoId, $tipo, $fecha, $actividad, $tecnico ?: null,
                    $observaciones ?: null, (int)$_SESSION['uid'], $jornadaId]);
                $mantenimientoId = (int)$dbh->lastInsertId();

                $audit = $dbh->prepare("INSERT INTO auditoria
                    (usuario_id, tabla_afectada, registro_id, accion, descripcion, direccion_ip)
                    VALUES (?, 'jornadas_mantenimiento', ?, 'INSERT', ?, ?)");
                $audit->execute([(int)$_SESSION['uid'], $jornadaId,
                    'Creación de jornada individual para mantenimiento ID ' . $mantenimientoId . ' y activo ID ' . $activoId,
                    $_SERVER['REMOTE_ADDR'] ?? null]);
                $dbh->commit();
                header('Location: manage-mantenimientos.php?creado=1');
                exit;
            } catch (Throwable $e) {
                if ($dbh->inTransaction()) $dbh->rollBack();
                $error = 'No fue posible crear la programación individual: ' . $e->getMessage();
            }
        }
    }
}
$activos = $dbh->query("SELECT a.id, a.codigo_inventario, a.descripcion, a.marca, a.modelo,
    s.nombre AS sede, u.nombre AS ubicacion
    FROM activos a LEFT JOIN ubicaciones u ON u.id=a.ubicacion_id
    LEFT JOIN sedes s ON s.id=u.sede_id
    WHERE a.estado <> 'DADO_DE_BAJA_OBSOLETO' ORDER BY a.codigo_inventario")->fetchAll(PDO::FETCH_ASSOC);
include __DIR__ . '/includes/topbar.php';
?>
<div class="content-wrapper"><div class="content-container"><?php include __DIR__ . '/includes/leftbar.php'; ?><div class="main-page"><div class="container-fluid"><div class="row page-title-div"><div class="col-sm-12"><h2 class="title">Programar mantenimiento individual</h2><p>Se creará una jornada independiente para el activo seleccionado.</p></div></div></div>
<section class="section"><div class="container-fluid"><div class="row"><div class="col-md-12"><div class="panel"><div class="panel-heading"><div class="panel-title"><h5>Datos de programación</h5></div></div><div class="panel-body p-20">
<?php if ($error): ?><div class="alert alert-danger"><?=h($error)?></div><?php endif; ?>
<form method="post" class="row">
<div class="form-group col-md-6"><label>Activo *</label><select name="activo_id" class="form-control" required><option value="">Seleccione un activo</option><?php foreach ($activos as $a): ?><option value="<?= (int)$a['id'] ?>" <?=((int)($_POST['activo_id']??0)===(int)$a['id'])?'selected':''?>><?=h($a['codigo_inventario'].' - '.$a['descripcion'].' | '.$a['sede'].' | '.$a['ubicacion'])?></option><?php endforeach; ?></select></div>
<div class="form-group col-md-6"><label>Tipo de mantenimiento *</label><select name="tipo_mantenimiento" class="form-control" required><option value="">Seleccione</option><option value="PREVENTIVO">Preventivo</option><option value="CORRECTIVO">Correctivo</option><option value="REPARACION">Reparación</option></select></div>
<div class="form-group col-md-6"><label>Fecha programada *</label><input type="date" name="fecha_programada" class="form-control" min="<?=date('Y-m-d')?>" value="<?=h($_POST['fecha_programada']??'')?>" required></div>
<div class="form-group col-md-6"><label>Técnico / responsable</label><input type="text" name="tecnico_responsable" class="form-control" maxlength="150" placeholder="Nombre del responsable" value="<?=h($_POST['tecnico_responsable']??'')?>"></div>
<div class="form-group col-md-12"><label>Actividad / mantenimiento requerido *</label><textarea name="diagnostico" class="form-control" rows="3" required><?=h($_POST['diagnostico']??'')?></textarea></div>
<div class="form-group col-md-12"><label>Observaciones</label><textarea name="observaciones" class="form-control" rows="2"><?=h($_POST['observaciones']??'')?></textarea></div>
<div class="form-group col-md-12"><button type="submit" class="btn btn-primary">Guardar programación individual</button> <a href="manage-mantenimientos.php" class="btn btn-default">Cancelar</a></div>
</form></div></div></div></div></div></section></div></div></div><?php include __DIR__ . '/includes/footer.php'; ?>
