<?php
require_once __DIR__.'/includes/config.php';require_once __DIR__.'/includes/auth.php';sisatRequireRoles([1]);
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$sql="SELECT s.*,a.codigo_inventario,a.descripcion,so.nombre AS sede_origen,sd.nombre AS sede_destino,u.nombre_completo AS solicitante FROM solicitudes_traslado s JOIN activos a ON a.id=s.activo_id LEFT JOIN ubicaciones uo ON uo.id=s.ubicacion_origen_id LEFT JOIN sedes so ON so.id=uo.sede_id JOIN sedes sd ON sd.id=s.sede_destino_id JOIN usuarios_sistema u ON u.id=s.solicitado_por ORDER BY FIELD(s.estado,'PENDIENTE','APROBADA','RECHAZADA'),s.fecha_creacion DESC";
$items=$dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);include __DIR__.'/includes/topbar.php';?><div class="content-wrapper"><div class="content-container"><?php include __DIR__.'/includes/leftbar.php';?><div class="main-page"><div class="container-fluid"><h2>Solicitudes de traslado</h2><p>Las solicitudes pendientes requieren aprobación antes de actualizar la ubicación del activo.</p></div><section class="section"><div class="container-fluid"><div class="panel"><div class="panel-body"><div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Activo</th><th>Origen</th><th>Destino</th><th>Fecha</th><th>Motivo</th><th>Solicitante</th><th>Estado / acción</th></tr></thead><tbody><?php foreach($items as $s):?><tr><td><?=h($s['codigo_inventario'].' - '.$s['descripcion'])?></td><td><?=h($s['sede_origen'])?></td><td><?=h($s['sede_destino'].' / '.$s['piso_destino'].' / '.$s['oficina_destino'].' / '.$s['departamento_destino'])?></td><td><?=h($s['fecha_movimiento'])?></td><td><?=h($s['motivo'])?></td><td><?=h($s['solicitante'])?></td><td><strong><?=h($s['estado'])?></strong>
<?php if($s['estado']==='PENDIENTE'):?>
<form method="post" action="procesar-solicitud-traslado.php" style="margin-top:8px">
    <input type="hidden" name="id" value="<?=$s['id']?>">
    <input type="text" name="comentario" class="form-control input-sm" placeholder="Comentario (opcional)" style="width:100%; min-width:220px; margin-bottom:5px;">

    <button name="accion" value="APROBAR" class="btn btn-xs btn-success" style="margin-right:10px; margin-top:8px;" onclick="return confirm('¿Aprobar y actualizar la ubicación del activo?')">
        Aprobar
    </button>

    <button name="accion" value="RECHAZAR" class="btn btn-xs btn-danger" style="margin-top:8px;" onclick="return confirm('¿Rechazar la solicitud?')">
        Rechazar
    </button>
</form>
<?php elseif($s['comentario_admin']):?>
    <small><?=h($s['comentario_admin'])?></small>
<?php endif;?></td></tr><?php endforeach;?></tbody></table></div></div></div></div></section></div></div></div><?php include __DIR__.'/includes/footer.php';?>

