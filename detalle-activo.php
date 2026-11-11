<?php
session_start();
require_once __DIR__ . '/includes/config.php';
if (empty($_SESSION['alogin']) || empty($_SESSION['uid'])) { header('Location: index.php'); exit; }
function h($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) { header('Location: manage-activos.php'); exit; }
$sql = "SELECT a.*, ca.nombre AS categoria, ca.codigo AS categoria_codigo,
 u.nombre AS ubicacion, u.tipo_ubicacion, u.piso, u.area_departamento, u.detalle AS detalle_ubicacion,
 s.id AS sede_id, s.nombre AS sede,
 ec.tipo_equipo AS computo_tipo, ec.capacidad_disco, ec.tipo_disco, ec.capacidad_ram, ec.procesador AS computo_procesador, ec.direccion_ip AS computo_ip, ec.mac AS computo_mac, ec.version_so, ec.descripcion_equipo,
 di.tipo_impresora, di.direccion_ip AS impresion_ip, di.mac AS impresion_mac, di.fecha_ultimo_cambio_suministro, di.tipo_suministro, di.observaciones_suministro,
 pe.tipo_periferico, pe.descripcion_periferico,
 te.numero_extension, te.proveedor, te.tipo_equipo AS telefonia_tipo, te.descripcion_telefonia,
 se.hostname, se.estado_equipo, se.rack_gabinete, se.direccion_ip AS servidor_ip, se.procesador AS servidor_procesador, se.memoria_ram, se.sistema_operativo, se.version_so AS servidor_version, se.aplicaciones_roles, se.descripcion_servidor,
 inf.tipo_dispositivo, inf.cantidad_puertos, inf.direccion_ip AS infraestructura_ip, inf.mac AS infraestructura_mac, inf.descripcion_infraestructura
 FROM activos a INNER JOIN categorias_activos ca ON ca.id=a.categoria_id
 LEFT JOIN ubicaciones u ON u.id=a.ubicacion_id LEFT JOIN sedes s ON s.id=u.sede_id
 LEFT JOIN equipos_computo ec ON ec.activo_id=a.id LEFT JOIN dispositivos_impresion di ON di.activo_id=a.id
 LEFT JOIN perifericos pe ON pe.activo_id=a.id LEFT JOIN telefonia te ON te.activo_id=a.id
 LEFT JOIN servidores se ON se.activo_id=a.id LEFT JOIN infraestructura inf ON inf.activo_id=a.id WHERE a.id=:id LIMIT 1";
$stmt=$dbh->prepare($sql); $stmt->execute(array(':id'=>$id)); $a=$stmt->fetch(PDO::FETCH_ASSOC);
if (!$a) { http_response_code(404); exit('El activo solicitado no existe.'); }
$discos=array(); $mantenimientos=array(); $movimientos=array();
if ($a['categoria_codigo']==='SERVIDOR') { $q=$dbh->prepare("SELECT * FROM servidor_discos WHERE servidor_id=(SELECT id FROM servidores WHERE activo_id=:id LIMIT 1) ORDER BY numero_disco"); $q->execute(array(':id'=>$id)); $discos=$q->fetchAll(PDO::FETCH_ASSOC); }
$q=$dbh->prepare("SELECT * FROM mantenimientos WHERE activo_id=:id ORDER BY fecha_creacion DESC, id DESC"); $q->execute(array(':id'=>$id)); $mantenimientos=$q->fetchAll(PDO::FETCH_ASSOC);
// Historial físico: traslados registrados en movimientos_activos.
$q=$dbh->prepare("SELECT m.fecha_movimiento AS fecha, 'TRASLADO FÍSICO' AS tipo, uo.nombre AS origen, ud.nombre AS destino, m.motivo, m.observaciones, NULL AS funcionario, NULL AS area
 FROM movimientos_activos m
 LEFT JOIN ubicaciones uo ON uo.id=m.ubicacion_origen_id
 LEFT JOIN ubicaciones ud ON ud.id=m.ubicacion_destino_id
 WHERE m.activo_id=:id");
$q->execute(array(':id'=>$id));
$movimientosFisicos=$q->fetchAll(PDO::FETCH_ASSOC);
// Las asignaciones son eventos administrativos; se muestran en el mismo historial sin inventar traslados físicos.
$q=$dbh->prepare("SELECT CONCAT(a.fecha_asignacion, ' 00:00:00') AS fecha,
 CASE WHEN a.tipo_asignacion='PERSONA' THEN 'ENTREGA A FUNCIONARIO' ELSE 'ASIGNACIÓN EN SEDE / USO COMPARTIDO' END AS tipo,
 NULL AS origen,
 CASE WHEN a.tipo_asignacion='PERSONA' THEN CONCAT('Funcionario: ', COALESCE(NULLIF(a.funcionario_receptor,''),'No especificado')) ELSE CONCAT('Sede: ', COALESCE(s.nombre,'No especificada'), ' / Ubicación: ', COALESCE(u.nombre,'No especificada')) END AS destino,
 COALESCE(a.descripcion, a.observaciones, a.numero_control) AS motivo,
 a.observaciones,
 a.funcionario_receptor AS funcionario,
 a.area_departamento_asignacion AS area
 FROM asignaciones a
 LEFT JOIN activos ac ON ac.id=a.activo_id
 LEFT JOIN ubicaciones u ON u.id=ac.ubicacion_id
 LEFT JOIN sedes s ON s.id=u.sede_id
 WHERE a.activo_id=:id");
$q->execute(array(':id'=>$id));
$asignacionesHistorial=$q->fetchAll(PDO::FETCH_ASSOC);
$movimientos=array_merge($movimientosFisicos,$asignacionesHistorial);
usort($movimientos, function($x,$y){ return strcmp((string)$y['fecha'], (string)$x['fecha']); });
$estados=array('OPERATIVO_EN_USO'=>'Operativo / En uso','EN_STOCK'=>'En stock','EN_REPARACION'=>'En reparación','REGULAR'=>'Regular','DADO_DE_BAJA_OBSOLETO'=>'Dado de baja / Obsoleto');
$campos=array(
 'COMPUTO'=>array('Tipo de equipo'=>'computo_tipo','Capacidad de disco'=>'capacidad_disco','Tipo de disco'=>'tipo_disco','Capacidad RAM'=>'capacidad_ram','Procesador'=>'computo_procesador','Dirección IP'=>'computo_ip','MAC'=>'computo_mac','Versión del sistema operativo'=>'version_so','Descripción del equipo'=>'descripcion_equipo'),
 'IMPRESION'=>array('Tipo de impresora'=>'tipo_impresora','Dirección IP'=>'impresion_ip','MAC'=>'impresion_mac','Último cambio de suministro'=>'fecha_ultimo_cambio_suministro','Tipo de suministro'=>'tipo_suministro','Observaciones de suministro / reparación'=>'observaciones_suministro'),
 'PERIFERICO'=>array('Tipo de periférico'=>'tipo_periferico','Descripción'=>'descripcion_periferico'),
 'TELEFONIA'=>array('Número / extensión'=>'numero_extension','Proveedor'=>'proveedor','Tipo de equipo'=>'telefonia_tipo','Descripción'=>'descripcion_telefonia'),
 'SERVIDOR'=>array('Hostname'=>'hostname','Estado del equipo'=>'estado_equipo','Rack / gabinete'=>'rack_gabinete','Dirección IP'=>'servidor_ip','Procesador'=>'servidor_procesador','Memoria RAM'=>'memoria_ram','Sistema operativo'=>'sistema_operativo','Versión SO'=>'servidor_version','Aplicaciones / roles'=>'aplicaciones_roles','Descripción'=>'descripcion_servidor'),
 'INFRAESTRUCTURA'=>array('Tipo de dispositivo'=>'tipo_dispositivo','Cantidad de puertos'=>'cantidad_puertos','Dirección IP'=>'infraestructura_ip','MAC'=>'infraestructura_mac','Descripción'=>'descripcion_infraestructura')
);
?>
<?php include __DIR__.'/includes/topbar.php'; ?>
<div class="content-wrapper"><div class="content-container"><?php include __DIR__.'/includes/leftbar.php'; ?><div class="main-page"><div class="container-fluid">
<div class="row page-title-div"><div class="col-md-12"><h2 class="title">Ficha del activo</h2></div></div>
<div class="row breadcrumb-div"><div class="col-md-12"><ul class="breadcrumb"><li><a href="dashboard.php">Inicio</a></li><li><a href="manage-activos.php">Ver activos</a></li><li class="active">Ficha del activo</li></ul></div></div></div>
<section class="section"><div class="container-fluid"><div class="panel"><div class="panel-heading"><div class="panel-title"><h5><?php echo h($a['codigo_inventario']); ?> — <?php echo h($a['categoria']); ?></h5></div></div><div class="panel-body">
<div class="clearfix" style="margin-bottom:20px"><a class="btn btn-primary pull-right" href="edit-activo.php?id=<?php echo $id; ?>"><i class="fa fa-pencil"></i> Editar información</a><a class="btn btn-default" href="manage-activos.php"><i class="fa fa-arrow-left"></i> Volver al listado</a></div>
<h4>Identificación y datos generales</h4><div class="table-responsive"><table class="table table-bordered"><tbody>
<?php $generales=array('Código de inventario'=>$a['codigo_inventario'],'Número de placa'=>$a['codigo_placa'],'Categoría'=>$a['categoria'],'Descripción'=>$a['descripcion'],'Marca'=>$a['marca'],'Modelo'=>$a['modelo'],'Número de serial'=>$a['numero_serial'],'Estado'=>$estados[$a['estado']]??$a['estado'],'Fecha de adquisición'=>$a['fecha_adquisicion'],'Valor de adquisición'=>$a['valor_adquisicion'],'¿Reparada?'=>($a['reparada']==='SI'?'Sí':'No'),'Costo de reparación'=>$a['costo_reparacion'],'Fecha de reparación'=>$a['fecha_reparacion'],'Fecha de mantenimiento'=>$a['fecha_mantenimiento'],'Descripción / causa de reparación o trabajo realizado'=>$a['descripcion_reparacion'],'Sede'=>$a['sede'],'Ubicación'=>$a['ubicacion'],'Tipo de ubicación'=>$a['tipo_ubicacion'],'Piso'=>$a['piso'],'Área / departamento'=>$a['area_departamento'],'Detalle de ubicación'=>$a['detalle_ubicacion'],'Observaciones'=>$a['observaciones'],'Fecha de registro'=>$a['fecha_creacion']); foreach($generales as $k=>$v): ?><tr><th style="width:28%"><?php echo h($k); ?></th><td><?php echo h(($v!==null && $v!=='')?$v:'—'); ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<h4>Características específicas</h4><div class="table-responsive"><table class="table table-bordered"><tbody><?php foreach(($campos[$a['categoria_codigo']]??array()) as $label=>$field): ?><tr><th style="width:28%"><?php echo h($label); ?></th><td><?php echo h(($a[$field]!==null && $a[$field]!=='')?$a[$field]:'—'); ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php if($a['categoria_codigo']==='SERVIDOR'): ?><h4>Discos del servidor</h4><div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>N.º</th><th>Tipo</th><th>Capacidad</th><th>Modelo</th><th>Serial</th><th>RAID</th></tr></thead><tbody><?php if(!$discos): ?><tr><td colspan="6">No hay discos registrados.</td></tr><?php else: foreach($discos as $d): ?><tr><td><?php echo (int)$d['numero_disco']; ?></td><td><?php echo h($d['tipo_disco']); ?></td><td><?php echo h($d['capacidad']); ?></td><td><?php echo h($d['modelo']); ?></td><td><?php echo h($d['numero_serial']); ?></td><td><?php echo h($d['configuracion_raid']); ?></td></tr><?php endforeach; endif; ?></tbody></table></div><?php endif; ?>
<h4>Historial de mantenimientos y reparaciones</h4><div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>Fecha</th><th>Tipo</th><th>Diagnóstico</th><th>Trabajo realizado</th><th>Resultado</th></tr></thead><tbody><?php if(!$mantenimientos): ?><tr><td colspan="5">No hay mantenimientos registrados.</td></tr><?php else: foreach($mantenimientos as $m): ?><tr><td><?php echo h($m['fecha_inicio']?:$m['fecha_programada']); ?></td><td><?php echo h($m['tipo_mantenimiento']); ?></td><td><?php echo h($m['diagnostico']); ?></td><td><?php echo h($m['trabajo_realizado']); ?></td><td><?php echo h($m['resultado']); ?></td></tr><?php endforeach; endif; ?></tbody></table></div>
<h4>Historial de movimientos y asignaciones</h4><div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>Fecha</th><th>Tipo de evento</th><th>Origen</th><th>Destino / receptor</th><th>Área / departamento</th><th>Motivo / observaciones</th></tr></thead><tbody><?php if(!$movimientos): ?><tr><td colspan="6">No hay movimientos ni asignaciones registrados.</td></tr><?php else: foreach($movimientos as $m): ?><tr><td><?php echo h($m['fecha']); ?></td><td><?php echo h($m['tipo']); ?></td><td><?php echo h($m['origen']?:'—'); ?></td><td><?php echo h($m['destino']?:'—'); ?></td><td><?php echo h($m['area']?:'—'); ?></td><td><?php echo h($m['motivo']?:$m['observaciones']?:'—'); ?></td></tr><?php endforeach; endif; ?></tbody></table></div>
</div></div></div></section></div></div></div><?php include __DIR__.'/includes/footer.php'; ?>
