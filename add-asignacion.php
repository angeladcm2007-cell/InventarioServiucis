<?php
session_start();
require_once __DIR__ . '/includes/config.php';
if (empty($_SESSION['alogin']) || empty($_SESSION['uid'])) { header('Location: index.php'); exit; }
function h($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
$msg = ''; $error = '';
$activos = $dbh->query("SELECT a.id, a.codigo_inventario, COALESCE(a.descripcion, ca.nombre) AS descripcion_activo,
    s.nombre AS sede, u.nombre AS ubicacion, u.area_departamento
    FROM activos a INNER JOIN categorias_activos ca ON ca.id=a.categoria_id
    LEFT JOIN ubicaciones u ON u.id=a.ubicacion_id LEFT JOIN sedes s ON s.id=u.sede_id
    WHERE a.estado <> 'DADO_DE_BAJA_OBSOLETO' ORDER BY a.codigo_inventario")->fetchAll(PDO::FETCH_ASSOC);
if (empty($_SESSION['csrf_asignacion'])) $_SESSION['csrf_asignacion'] = bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['guardar_asignacion'])) {
    try {
        if (!hash_equals($_SESSION['csrf_asignacion'], (string)($_POST['csrf_token'] ?? ''))) throw new RuntimeException('La sesión del formulario venció. Actualiza la página e inténtalo nuevamente.');
        $activoId = (int)($_POST['activo_id'] ?? 0);
        $tipo = (string)($_POST['tipo_asignacion'] ?? 'SEDE');
        $fecha = (string)($_POST['fecha_asignacion'] ?? date('Y-m-d'));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        if ($activoId < 1 || !in_array($tipo, array('SEDE','PERSONA'), true)) throw new RuntimeException('Selecciona un activo y un tipo de asignación válido.');
        $personaId = null; // Se conserva NULL para compatibilidad con la relación histórica a personas.
        $funcionarioReceptor = trim((string)($_POST['funcionario_receptor'] ?? ''));
        $areaDepartamentoAsignacion = trim((string)($_POST['area_departamento_asignacion'] ?? ''));
        if ($tipo === 'PERSONA') {
            if ($funcionarioReceptor === '') throw new RuntimeException('Escribe el nombre del funcionario que recibe el activo.');
            if (mb_strlen($funcionarioReceptor, 'UTF-8') > 150) throw new RuntimeException('El nombre del funcionario no puede superar 150 caracteres.');
            if (mb_strlen($areaDepartamentoAsignacion, 'UTF-8') > 150) throw new RuntimeException('El área o departamento no puede superar 150 caracteres.');
        } else { $funcionarioReceptor = ''; $areaDepartamentoAsignacion = ''; }
        $espacios = array();
        if ($tipo === 'SEDE') {
            $ubicaciones = $_POST['ubicacion_espacio'] ?? array(); $areas = $_POST['area_departamento'] ?? array();
            foreach ($ubicaciones as $i=>$espacio) {
                $espacio = trim((string)$espacio); $area = trim((string)($areas[$i] ?? ''));
                if ($espacio === '' && $area === '') continue;
                if ($espacio === '') throw new RuntimeException('Completa Ubicación / espacio en cada oficina agregada.');
                $espacios[] = array($espacio, $area);
            }
        }
        $dbh->beginTransaction();
        $check = $dbh->prepare("SELECT id FROM activos WHERE id=:id AND estado <> 'DADO_DE_BAJA_OBSOLETO' FOR UPDATE");
        $check->execute(array(':id'=>$activoId));
        if (!$check->fetchColumn()) throw new RuntimeException('El activo seleccionado no existe o está dado de baja.');
        $dup = $dbh->prepare("SELECT numero_control FROM asignaciones WHERE activo_id=:id AND estado_asignacion='ACTIVA' LIMIT 1");
        $dup->execute(array(':id'=>$activoId));
        if ($dup->fetchColumn()) throw new RuntimeException('Este activo ya tiene una asignación activa. Cierra la asignación anterior antes de registrar otra.');
        $numeroControl = 'ASIG-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $ins = $dbh->prepare("INSERT INTO asignaciones (activo_id, persona_id, funcionario_receptor, area_departamento_asignacion, numero_control, fecha_asignacion, estado_asignacion, descripcion, registrado_por, tipo_asignacion) VALUES (:activo_id,:persona_id,:funcionario_receptor,:area_departamento_asignacion,:numero_control,:fecha,'ACTIVA',:descripcion,:registrado_por,:tipo)");
        $ins->execute(array(':activo_id'=>$activoId, ':persona_id'=>$personaId, ':funcionario_receptor'=>($funcionarioReceptor!==''?$funcionarioReceptor:null), ':area_departamento_asignacion'=>($areaDepartamentoAsignacion!==''?$areaDepartamentoAsignacion:null), ':numero_control'=>$numeroControl, ':fecha'=>$fecha, ':descripcion'=>($descripcion!==''?$descripcion:null), ':registrado_por'=>(int)$_SESSION['uid'], ':tipo'=>$tipo));
        $asignacionId=(int)$dbh->lastInsertId();
        // Conserva evidencia de quién registró la asignación y los datos capturados.
        $audit = $dbh->prepare("INSERT INTO auditoria (usuario_id, tabla_afectada, registro_id, accion, descripcion, direccion_ip) VALUES (:usuario, 'asignaciones', :registro, 'INSERT', :detalle, :ip)");
        $detalleAuditoria = 'Asignación '.$numeroControl.' creada. Modalidad: '.$tipo.'. Activo ID: '.$activoId.'. Funcionario receptor: '.($funcionarioReceptor !== '' ? $funcionarioReceptor : 'No aplica').'. Área/departamento: '.($areaDepartamentoAsignacion !== '' ? $areaDepartamentoAsignacion : 'No aplica').'.';
        $audit->execute(array(':usuario'=>(int)$_SESSION['uid'], ':registro'=>$asignacionId, ':detalle'=>$detalleAuditoria, ':ip'=>($_SERVER['REMOTE_ADDR'] ?? null)));
        if ($tipo==='SEDE' && $espacios) {
            $areaIns=$dbh->prepare("INSERT INTO asignacion_areas_usuarias (asignacion_id, ubicacion_espacio, area_departamento) VALUES (:asignacion_id,:espacio,:area)");
            foreach($espacios as $fila) $areaIns->execute(array(':asignacion_id'=>$asignacionId, ':espacio'=>$fila[0], ':area'=>($fila[1]!==''?$fila[1]:null)));
        }
        $dbh->commit();
        header('Location: manage-asignacion.php?registrada=1'); exit;
    } catch (Throwable $e) {
        if ($dbh->inTransaction()) $dbh->rollBack();
        $error = $e instanceof PDOException ? 'No fue posible guardar la asignación. Verifica que hayas ejecutado la actualización SQL del módulo.' : $e->getMessage();
        error_log('Error al registrar asignación: '.$e->getMessage());
    }
}
?>
<?php include __DIR__.'/includes/topbar.php'; ?>
<div class="content-wrapper"><div class="content-container"><?php include __DIR__.'/includes/leftbar.php'; ?><div class="main-page">
<div class="container-fluid"><div class="row page-title-div"><div class="col-md-12"><h2 class="title">Asignación de activos</h2></div></div><div class="row breadcrumb-div"><div class="col-md-12"><ul class="breadcrumb"><li><a href="dashboard.php">Inicio</a></li><li><a href="manage-asignacion.php">Asignaciones</a></li><li class="active">Agregar asignación</li></ul></div></div></div>
<section class="section"><div class="container-fluid"><div class="row"><div class="col-md-12"><div class="panel"><div class="panel-heading"><div class="panel-title"><h5>Detalles de asignación</h5></div></div><div class="panel-body">
<?php if($error): ?><div class="alert alert-danger"><?php echo h($error); ?></div><?php endif; ?>
<form method="post" id="formAsignacion"><input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_asignacion']); ?>">
<div class="row">
<div class="col-md-6 form-group"><label>Activo *</label><select class="form-control" name="activo_id" id="activo_id" required><option value="">Seleccione un activo</option><?php foreach($activos as $a): ?><option value="<?php echo (int)$a['id']; ?>" data-codigo="<?php echo h($a['codigo_inventario']); ?>" data-descripcion="<?php echo h($a['descripcion_activo']); ?>" data-sede="<?php echo h($a['sede']); ?>" data-ubicacion="<?php echo h($a['ubicacion']); ?>" data-area="<?php echo h($a['area_departamento']); ?>"><?php echo h($a['codigo_inventario'].' — '.$a['descripcion_activo']); ?></option><?php endforeach; ?></select></div>
<div class="col-md-6 form-group"><label>Fecha de asignación *</label><input class="form-control" type="date" name="fecha_asignacion" value="<?php echo date('Y-m-d'); ?>" required></div>
<div class="col-md-12"><div id="datosActivo" class="alert alert-info" style="display:none"><strong>Información tomada del inventario</strong><div class="row" style="margin-top:8px"><div class="col-md-3"><b>Código:</b> <span id="vCodigo"></span></div><div class="col-md-3"><b>Sede:</b> <span id="vSede"></span></div><div class="col-md-3"><b>Ubicación física:</b> <span id="vUbicacion"></span></div><div class="col-md-3"><b>Área registrada:</b> <span id="vArea"></span></div></div></div></div>
<div class="col-md-12 form-group"><label>Tipo de asignación *</label><select class="form-control" name="tipo_asignacion" id="tipo_asignacion" required><option value="SEDE">Activo permanece en sede / uso compartido</option><option value="PERSONA">Entrega directa a una persona</option></select></div>
<div id="seccionCompartido" class="col-md-12"><hr><h4>Comparte con</h4><p class="text-muted">Registra las oficinas o áreas adicionales que pueden utilizar el activo. La ubicación física principal se conserva desde el inventario.</p><div id="listaOficinas"><div class="oficina-item row" style="border:1px solid #eee;padding:12px;margin:8px 0;border-radius:4px"><div class="col-md-6 form-group"><label>Ubicación / espacio</label><input class="form-control" name="ubicacion_espacio[]" placeholder="Ej. Oficina de sistemas, cuarto técnico"></div><div class="col-md-6 form-group"><label>Área o departamento</label><input class="form-control" name="area_departamento[]" placeholder="Ej. Contabilidad, Recepción, Gerencia"></div></div></div><button type="button" class="btn btn-default btn-sm" id="agregarOficina"><i class="fa fa-plus"></i> Agregar otra oficina</button></div>
<div id="seccionPersona" class="col-md-12" style="display:none"><hr><h4>Entrega individual</h4><div class="row"><div class="col-md-6 form-group"><label>Funcionario que recibe *</label><input type="text" class="form-control" name="funcionario_receptor" id="funcionario_receptor" maxlength="150" placeholder="Escriba el nombre del funcionario que recibe el activo"></div><div class="col-md-6 form-group"><label>Área o departamento</label><input type="text" class="form-control" name="area_departamento_asignacion" id="area_departamento_asignacion" maxlength="150" placeholder="Área tomada del inventario; puede modificarla"></div></div><div class="alert alert-warning">El nombre del funcionario se registra como texto libre. El área o departamento se carga desde el inventario y puede modificarse para dejar constancia del destino al momento de la entrega.</div></div>
<div class="col-md-12 form-group"><label>Descripción / observaciones</label><textarea class="form-control" name="descripcion" rows="2" placeholder="Información adicional de la asignación"></textarea></div>
<div class="col-md-12 form-group"><button type="submit" name="guardar_asignacion" class="btn btn-primary"><i class="fa fa-save"></i> Guardar asignación</button> <a href="manage-asignacion.php" class="btn btn-danger">Cancelar</a></div>
</div></form></div></div></div></div></div></section></div></div></div>
<script>
(function(){
 var tipo=document.getElementById('tipo_asignacion'), secSede=document.getElementById('seccionCompartido'), secPersona=document.getElementById('seccionPersona'), receptor=document.getElementById('funcionario_receptor'), activo=document.getElementById('activo_id');
 function cambiarTipo(){var individual=tipo.value==='PERSONA';secSede.style.display=individual?'none':'block';secPersona.style.display=individual?'block':'none';receptor.required=individual;document.querySelectorAll('#listaOficinas input').forEach(function(x){x.required=false;});}
 function mostrarActivo(){var o=activo.options[activo.selectedIndex], box=document.getElementById('datosActivo');if(!o||!o.value){box.style.display='none';document.getElementById('area_departamento_asignacion').value='';return;}box.style.display='block';['Codigo','Sede','Ubicacion','Area'].forEach(function(k){var key=k==='Codigo'?'codigo':(k==='Sede'?'sede':(k==='Ubicacion'?'ubicacion':'area'));document.getElementById('v'+k).textContent=o.getAttribute('data-'+key)||'No registrado';});document.getElementById('area_departamento_asignacion').value=o.getAttribute('data-area')||'';}
 tipo.addEventListener('change',cambiarTipo);activo.addEventListener('change',mostrarActivo);
 document.getElementById('agregarOficina').addEventListener('click',function(){var div=document.createElement('div');div.className='oficina-item row';div.style.cssText='border:1px solid #eee;padding:12px;margin:8px 0;border-radius:4px';div.innerHTML='<div class="col-md-6 form-group"><label>Ubicación / espacio</label><input class="form-control" name="ubicacion_espacio[]" placeholder="Ej. Oficina de sistemas, cuarto técnico"></div><div class="col-md-5 form-group"><label>Área o departamento</label><input class="form-control" name="area_departamento[]" placeholder="Ej. Contabilidad, Recepción, Gerencia"></div><div class="col-md-1"><button type="button" class="btn btn-danger btn-sm quitar-oficina" style="margin-top:25px" title="Quitar oficina"><i class="fa fa-trash"></i></button></div>';document.getElementById('listaOficinas').appendChild(div);});
 document.getElementById('listaOficinas').addEventListener('click',function(e){var b=e.target.closest('.quitar-oficina');if(b){b.closest('.oficina-item').remove();}});
 cambiarTipo();mostrarActivo();
})();
</script>
<?php include __DIR__.'/includes/footer.php'; ?>
