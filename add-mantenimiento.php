<?php
session_start();
require_once __DIR__ . '/includes/config.php';
if (empty($_SESSION['alogin']) || empty($_SESSION['uid'])) { header('Location: index.php'); exit; }
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activoId = (int)($_POST['activo_id'] ?? 0);
    $tipo = $_POST['tipo_mantenimiento'] ?? '';
    $fecha = trim($_POST['fecha_programada'] ?? '');
    $actividad = trim($_POST['diagnostico'] ?? '');
    $tecnico = trim($_POST['tecnico_responsable'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    if (!$activoId || !in_array($tipo, ['PREVENTIVO','CORRECTIVO','REPARACION'], true) || !$fecha || !$actividad) {
        $error = 'Complete el activo, tipo, fecha programada y actividad.';
    } else {
        $check = $dbh->prepare("SELECT id FROM activos WHERE id = :id AND estado <> 'DADO_DE_BAJA_OBSOLETO'");
        $check->execute([':id'=>$activoId]);
        if (!$check->fetchColumn()) $error = 'El activo seleccionado no existe o está dado de baja.';
        else {
            $sql = "INSERT INTO mantenimientos (activo_id,tipo_mantenimiento,fecha_programada,diagnostico,tecnico_responsable,observaciones,resultado,registrado_por) VALUES (:activo,:tipo,:fecha,:actividad,:tecnico,:observaciones,'PENDIENTE',:usuario)";
            $st = $dbh->prepare($sql);
            $st->execute([':activo'=>$activoId,':tipo'=>$tipo,':fecha'=>$fecha,':actividad'=>$actividad,':tecnico'=>$tecnico ?: null,':observaciones'=>$observaciones ?: null,':usuario'=>(int)$_SESSION['uid']]);
            $id = (int)$dbh->lastInsertId();
            $audit = $dbh->prepare("INSERT INTO auditoria (usuario_id,tabla_afectada,registro_id,accion,descripcion,direccion_ip) VALUES (:usuario,'mantenimientos',:registro,'INSERT',:descripcion,:ip)");
            $audit->execute([':usuario'=>(int)$_SESSION['uid'],':registro'=>$id,':descripcion'=>'Programación de mantenimiento '.$tipo.' para activo ID '.$activoId.'; fecha '.$fecha,':ip'=>$_SERVER['REMOTE_ADDR'] ?? null]);
            header('Location: manage-mantenimientos.php?creado=1'); exit;
        }
    }
}
$activos = $dbh->query("SELECT a.id,a.codigo_inventario,a.descripcion,a.marca,a.modelo,s.nombre AS sede,u.nombre AS ubicacion FROM activos a LEFT JOIN ubicaciones u ON u.id=a.ubicacion_id LEFT JOIN sedes s ON s.id=u.sede_id WHERE a.estado <> 'DADO_DE_BAJA_OBSOLETO' ORDER BY a.codigo_inventario")->fetchAll(PDO::FETCH_ASSOC);
include __DIR__ . '/includes/topbar.php';
?>
<div class="content-wrapper"><div class="content-container"><?php include __DIR__ . '/includes/leftbar.php'; ?><div class="main-page"><div class="container-fluid"><div class="row page-title-div"><div class="col-sm-12"><h2 class="title">Programar mantenimiento</h2><p>Registre una actividad de mantenimiento para un activo existente.</p></div></div></div>
<section class="section"><div class="container-fluid"><div class="row"><div class="col-md-12"><div class="panel"><div class="panel-heading"><div class="panel-title"><h5>Datos de programación</h5></div></div><div class="panel-body p-20">
<?php if($error): ?><div class="alert alert-danger"><?php echo h($error); ?></div><?php endif; ?>
<form method="post" class="row">
<div class="form-group col-md-6"><label>Activo *</label><select name="activo_id" class="form-control" required><option value="">Seleccione un activo</option><?php foreach($activos as $a): ?><option value="<?php echo (int)$a['id']; ?>" <?php echo ((int)($_POST['activo_id']??0)===(int)$a['id'])?'selected':''; ?>><?php echo h($a['codigo_inventario'].' - '.$a['descripcion'].' | '.$a['sede'].' | '.$a['ubicacion']); ?></option><?php endforeach; ?></select></div>
<div class="form-group col-md-6"><label>Tipo de mantenimiento *</label><select name="tipo_mantenimiento" class="form-control" required><option value="">Seleccione</option><option value="PREVENTIVO">Preventivo</option><option value="CORRECTIVO">Correctivo</option><option value="REPARACION">Reparación</option></select></div>
<div class="form-group col-md-6"><label>Fecha programada *</label><input type="date" name="fecha_programada" class="form-control" min="<?php echo date('Y-m-d'); ?>" value="<?php echo h($_POST['fecha_programada']??''); ?>" required></div>
<div class="form-group col-md-6"><label>Técnico / responsable (texto libre)</label><input type="text" name="tecnico_responsable" class="form-control" maxlength="150" placeholder="Nombre del responsable"></div>
<div class="form-group col-md-12"><label>Actividad / mantenimiento requerido *</label><textarea name="diagnostico" class="form-control" rows="3" required placeholder="Describa qué actividad se debe realizar"><?php echo h($_POST['diagnostico']??''); ?></textarea></div>
<div class="form-group col-md-12"><label>Observaciones</label><textarea name="observaciones" class="form-control" rows="2"><?php echo h($_POST['observaciones']??''); ?></textarea></div>
<div class="form-group col-md-12"><button type="submit" class="btn btn-primary">Guardar programación</button> <a href="manage-mantenimientos.php" class="btn btn-default">Cancelar</a></div>
</form></div></div></div></div></div></section></div></div></div><?php include __DIR__ . '/includes/footer.php'; ?>
