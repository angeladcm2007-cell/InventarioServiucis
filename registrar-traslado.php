<?php
require_once __DIR__ . '/includes/auth.php'; sisatRequireRoles([1]);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/includes/config.php';
if (empty($_SESSION['alogin']) || empty($_SESSION['uid'])) { header('Location: index.php'); exit; }
function h($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function normalizarNombreSedeTraslado($nombre) {
    $nombre = mb_strtolower(trim((string)$nombre), 'UTF-8');
    return strtr($nombre, array('á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ü'=>'u'));
}

// Búsqueda AJAX de activos por código, placa, descripción, marca, modelo o serial.
if (isset($_GET['buscar'])) {
    header('Content-Type: application/json; charset=utf-8');
    $term = trim((string)$_GET['buscar']);
    if (mb_strlen($term, 'UTF-8') < 2) { echo json_encode(array()); exit; }
    $like = '%' . $term . '%';
    $q = $dbh->prepare("SELECT a.id, a.codigo_inventario, a.codigo_placa, a.descripcion, a.marca, a.modelo, a.numero_serial, a.estado,
        u.id AS ubicacion_id, u.sede_id, u.nombre AS ubicacion, s.nombre AS sede
        FROM activos a LEFT JOIN ubicaciones u ON u.id=a.ubicacion_id LEFT JOIN sedes s ON s.id=u.sede_id
        WHERE a.codigo_inventario LIKE :c OR a.codigo_placa LIKE :p OR a.descripcion LIKE :d OR a.marca LIKE :m OR a.modelo LIKE :mo OR a.numero_serial LIKE :se
        ORDER BY a.codigo_inventario LIMIT 15");
    $q->execute(array(':c'=>$like, ':p'=>$like, ':d'=>$like, ':m'=>$like, ':mo'=>$like, ':se'=>$like));
    echo json_encode($q->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['csrf_traslado'])) { $_SESSION['csrf_traslado'] = bin2hex(random_bytes(32)); }
$error = '';
$success = '';
$requiereConfirmacionSede = false;
$areasAsignadasSede = array();
$activoSeleccionado = null;
$valores = array('activo_id'=>'', 'sede_destino_id'=>'', 'piso_destino'=>'', 'oficina_destino'=>'', 'departamento_destino'=>'', 'fecha_movimiento'=>date('Y-m-d\TH:i'), 'motivo'=>'', 'autorizado_por'=>'', 'observaciones'=>'');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($valores as $k => $v) { $valores[$k] = trim((string)($_POST[$k] ?? $v)); }
    try {
        if (!hash_equals($_SESSION['csrf_traslado'], (string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('La sesión del formulario expiró. Actualiza la página e inténtalo nuevamente.');
        }
        $activoId = (int)$valores['activo_id'];
        $sedeDestinoId = (int)$valores['sede_destino_id'];
        if ($activoId < 1 || $sedeDestinoId < 1) throw new RuntimeException('Selecciona el activo y la sede de destino.');
        if ($valores['piso_destino'] === '' || $valores['oficina_destino'] === '' || $valores['departamento_destino'] === '') throw new RuntimeException('Indica el piso, la oficina y el departamento de destino.');
        if (mb_strlen($valores['piso_destino'], 'UTF-8') > 30 || mb_strlen($valores['oficina_destino'], 'UTF-8') > 150 || mb_strlen($valores['departamento_destino'], 'UTF-8') > 120) throw new RuntimeException('Uno de los datos de ubicación supera la longitud permitida.');
        if ($valores['motivo'] === '') throw new RuntimeException('El motivo del traslado es obligatorio.');
        if ($valores['autorizado_por'] === '') throw new RuntimeException('Indica quién autoriza el traslado.');
        if (mb_strlen($valores['motivo'], 'UTF-8') > 255) throw new RuntimeException('El motivo no puede superar 255 caracteres.');
        if (mb_strlen($valores['autorizado_por'], 'UTF-8') > 150) throw new RuntimeException('El nombre de quien autoriza no puede superar 150 caracteres.');
        $fecha = str_replace('T', ' ', $valores['fecha_movimiento']);
        $dt = DateTime::createFromFormat('Y-m-d H:i', $fecha);
        if (!$dt || $dt->format('Y-m-d H:i') !== $fecha) throw new RuntimeException('La fecha y hora del traslado no son válidas.');

        $dbh->beginTransaction();
        $q = $dbh->prepare("SELECT a.id,a.codigo_inventario,a.descripcion,a.estado,a.ubicacion_id,u.nombre AS ubicacion,u.sede_id,s.nombre AS sede
            FROM activos a LEFT JOIN ubicaciones u ON u.id=a.ubicacion_id LEFT JOIN sedes s ON s.id=u.sede_id
            WHERE a.id=:id FOR UPDATE");
        $q->execute(array(':id'=>$activoId));
        $activo = $q->fetch(PDO::FETCH_ASSOC);
        if (!$activo) throw new RuntimeException('El activo seleccionado ya no existe.');
        if ($activo['estado'] === 'DADO_DE_BAJA_OBSOLETO') throw new RuntimeException('No se permite trasladar un activo dado de baja u obsoleto.');
        $origenId = (int)$activo['ubicacion_id'];
        $sedeOrigenId = (int)$activo['sede_id'];
        if ($sedeOrigenId < 1) throw new RuntimeException('El activo no tiene una sede de origen válida.');
        if (!in_array(normalizarNombreSedeTraslado($activo['sede'] ?? ''), array('rionegro', 'apartado'), true)) {
            throw new RuntimeException('La sede de origen del activo no corresponde a Rionegro ni Apartadó. Verifica primero su ubicación en el inventario.');
        }
        if ($sedeDestinoId === $sedeOrigenId) throw new RuntimeException('La sede de destino debe ser diferente de la sede donde se encuentra actualmente el activo.');

        $q = $dbh->prepare("SELECT id,nombre FROM sedes WHERE id=:id AND estado='ACTIVA' LIMIT 1 FOR UPDATE");
        $q->execute(array(':id'=>$sedeDestinoId));
        $sedeDestino = $q->fetch(PDO::FETCH_ASSOC);
        if (!$sedeDestino || !in_array(normalizarNombreSedeTraslado($sedeDestino['nombre']), array('rionegro', 'apartado'), true)) {
            throw new RuntimeException('Selecciona como destino Rionegro o Apartadó.');
        }

        // Reutiliza una ubicación activa idéntica; si no existe, la crea dentro de la misma transacción.
        $q = $dbh->prepare("SELECT id FROM ubicaciones WHERE sede_id=:sede AND piso=:piso AND nombre=:oficina AND area_departamento=:departamento AND estado='ACTIVA' LIMIT 1 FOR UPDATE");
        $q->execute(array(':sede'=>$sedeDestinoId, ':piso'=>$valores['piso_destino'], ':oficina'=>$valores['oficina_destino'], ':departamento'=>$valores['departamento_destino']));
        $destinoId = (int)$q->fetchColumn();
        if ($destinoId < 1) {
            $insUbicacion = $dbh->prepare("INSERT INTO ubicaciones (sede_id,tipo_ubicacion,nombre,piso,area_departamento,estado) VALUES (:sede,'Oficina',:nombre,:piso,:area,'ACTIVA')");
            $insUbicacion->execute(array(':sede'=>$sedeDestinoId, ':nombre'=>$valores['oficina_destino'], ':piso'=>$valores['piso_destino'], ':area'=>$valores['departamento_destino']));
            $destinoId = (int)$dbh->lastInsertId();
        }
        if ($destinoId === $origenId) throw new RuntimeException('La ubicación de destino debe ser diferente de la ubicación actual.');

        $q = $dbh->prepare("SELECT tipo_asignacion, estado_asignacion, numero_control FROM asignaciones WHERE activo_id=:id AND estado_asignacion IN ('ACTIVA','PENDIENTE_FIRMA') ORDER BY id DESC FOR UPDATE");
        $q->execute(array(':id'=>$activoId));
        $asignacionesActivas = $q->fetchAll(PDO::FETCH_ASSOC);
        foreach ($asignacionesActivas as $asig) {
            if ($asig['tipo_asignacion'] === 'PERSONA') {
                throw new RuntimeException('Traslado bloqueado: el activo tiene una asignación a persona en estado ' . $asig['estado_asignacion'] . ' (' . $asig['numero_control'] . '). Cierra o regulariza esa asignación antes de trasladarlo.');
            }
        }
        $hayAsignacionSede = false;
        foreach ($asignacionesActivas as $asig) { if ($asig['tipo_asignacion'] === 'SEDE') $hayAsignacionSede = true; }
        if ($hayAsignacionSede && (string)($_POST['confirmar_asignacion_sede'] ?? '') !== '1') {
            $dbh->rollBack();
            throw new RuntimeException('CONFIRMACION_SEDE: El activo tiene una asignación de sede activa. Verifica las áreas usuarias y confirma expresamente el traslado.');
        }

        $ins = $dbh->prepare("INSERT INTO movimientos_activos (activo_id,ubicacion_origen_id,ubicacion_destino_id,fecha_movimiento,motivo,autorizado_por,registrado_por,observaciones) VALUES (:activo,:origen,:destino,:fecha,:motivo,:autorizado,:usuario,:observaciones)");
        $ins->execute(array(':activo'=>$activoId, ':origen'=>$origenId, ':destino'=>$destinoId, ':fecha'=>$dt->format('Y-m-d H:i:s'), ':motivo'=>$valores['motivo'], ':autorizado'=>$valores['autorizado_por'], ':usuario'=>(int)$_SESSION['uid'], ':observaciones'=>$valores['observaciones'] !== '' ? $valores['observaciones'] : null));
        $upd = $dbh->prepare("UPDATE activos SET ubicacion_id=:destino, fecha_actualizacion=NOW() WHERE id=:id AND ubicacion_id=:origen");
        $upd->execute(array(':destino'=>$destinoId, ':id'=>$activoId, ':origen'=>$origenId));
        if ($upd->rowCount() !== 1) throw new RuntimeException('No se pudo actualizar la ubicación actual del activo. No se guardó el traslado.');
        $dbh->commit();
        $success = 'Traslado registrado correctamente. La ubicación actual del activo fue actualizada y el movimiento quedó en el historial.';
        $valores = array('activo_id'=>'', 'sede_destino_id'=>'', 'piso_destino'=>'', 'oficina_destino'=>'', 'departamento_destino'=>'', 'fecha_movimiento'=>date('Y-m-d\TH:i'), 'motivo'=>'', 'autorizado_por'=>'', 'observaciones'=>'');
        $_SESSION['csrf_traslado'] = bin2hex(random_bytes(32));
    } catch (Throwable $e) {
        if ($dbh->inTransaction()) $dbh->rollBack();
        $error = $e->getMessage();
        if (strpos($error, 'CONFIRMACION_SEDE:') === 0) { $requiereConfirmacionSede = true; $error = trim(substr($error, strlen('CONFIRMACION_SEDE:'))); $qa=$dbh->prepare("SELECT aa.ubicacion_espacio,aa.area_departamento FROM asignaciones a INNER JOIN asignacion_areas_usuarias aa ON aa.asignacion_id=a.id WHERE a.activo_id=? AND a.tipo_asignacion='SEDE' AND a.estado_asignacion='ACTIVA' ORDER BY aa.id"); $qa->execute(array((int)$valores['activo_id'])); $areasAsignadasSede=$qa->fetchAll(PDO::FETCH_ASSOC); }
    }
}

$sedesBD = $dbh->query("SELECT id,nombre FROM sedes WHERE estado='ACTIVA' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$sedesActivas = array();
foreach ($sedesBD as $sedeFila) {
    $sedeNormalizada = normalizarNombreSedeTraslado($sedeFila['nombre']);
    if (!in_array($sedeNormalizada, array('rionegro', 'apartado'), true)) continue;
    $sedeFila['nombre_mostrar'] = $sedeNormalizada === 'apartado' ? 'Apartadó' : 'Rionegro';
    $sedesActivas[] = $sedeFila;
}
if ($valores['activo_id'] !== '') { $q=$dbh->prepare("SELECT a.id,a.codigo_inventario,a.descripcion,a.estado,a.ubicacion_id,u.nombre AS ubicacion,u.sede_id,s.nombre AS sede FROM activos a LEFT JOIN ubicaciones u ON u.id=a.ubicacion_id LEFT JOIN sedes s ON s.id=u.sede_id WHERE a.id=?"); $q->execute(array((int)$valores['activo_id'])); $activoSeleccionado=$q->fetch(PDO::FETCH_ASSOC); }
$sedeOrigenMostrar = '';
if ($activoSeleccionado && !empty($activoSeleccionado['sede'])) {
    $nombreOrigenNormalizado = normalizarNombreSedeTraslado($activoSeleccionado['sede']);
    if ($nombreOrigenNormalizado === 'rionegro') $sedeOrigenMostrar = 'Rionegro';
    elseif ($nombreOrigenNormalizado === 'apartado') $sedeOrigenMostrar = 'Apartadó';
}
include __DIR__ . '/includes/topbar.php';
?>
<div class="content-wrapper"><div class="content-container"><?php include __DIR__ . '/includes/leftbar.php'; ?><div class="main-page"><div class="container-fluid">
<div class="row page-title-div"><div class="col-md-12"><h2 class="title">Registrar traslado de activo</h2><p>Registre el cambio físico de ubicación de un activo entre oficinas o sedes.</p></div></div>
<div class="row breadcrumb-div"><div class="col-md-12"><ul class="breadcrumb"><li><a href="dashboard.php">Inicio</a></li><li>Gestión de traslados</li><li class="active">Registrar traslado</li></ul></div></div></div>
<section class="section"><div class="container-fluid"><div class="row"><div class="col-md-12"><div class="panel"><div class="panel-heading"><div class="panel-title"><h5>Datos del traslado</h5></div></div><div class="panel-body p-20">
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?php echo h($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success" role="alert"><?php echo h($success); ?></div><?php endif; ?>
<form method="post" id="formTraslado" autocomplete="off"><input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_traslado']); ?>"><input type="hidden" name="activo_id" id="activo_id" value="<?php echo h($valores['activo_id']); ?>">
<div class="row"><div class="form-group col-md-8"><label for="buscarActivo">Buscar activo *</label><input type="text" id="buscarActivo" class="form-control" placeholder="Código, placa, descripción, marca, modelo o serial" value="" autocomplete="off"><div id="resultadosActivos" class="list-group" style="position:relative;z-index:20;max-height:240px;overflow-y:auto;display:none;margin-top:3px"></div><small class="text-muted">Escribe al menos dos caracteres y selecciona un resultado.</small></div>
<div class="form-group col-md-4"><label>Estado del activo</label><input type="text" id="estadoActivo" class="form-control" readonly></div></div>
<div id="resumenActivo" class="alert alert-info" style="<?php echo $activoSeleccionado ? 'display:block' : 'display:none'; ?>"><strong>Activo seleccionado:</strong> <span id="datoActivo"><?php echo h($activoSeleccionado ? $activoSeleccionado['codigo_inventario'].' — '.$activoSeleccionado['descripcion'] : ''); ?></span><br><strong>Ubicación de origen:</strong> <span id="origenActivo"><?php echo h($activoSeleccionado ? (($activoSeleccionado['sede']??'Sin sede').' — '.($activoSeleccionado['ubicacion']??'Sin ubicación')) : ''); ?></span></div>
<?php if ($requiereConfirmacionSede): ?><div class="alert alert-warning"><strong>Asignación activa a sede.</strong> Antes de continuar, verifica las áreas usuarias registradas: <?php if ($areasAsignadasSede): ?><ul><?php foreach($areasAsignadasSede as $area): ?><li><?php echo h($area['ubicacion_espacio'].($area['area_departamento']?' — '.$area['area_departamento']:'')); ?></li><?php endforeach; ?></ul><?php else: ?>No hay áreas usuarias detalladas en el registro.<?php endif; ?><label style="font-weight:normal"><input type="checkbox" name="confirmar_asignacion_sede" value="1" required> Confirmo que revisé las áreas usuarias y autorizo el traslado.</label></div><?php endif; ?>
<div class="row">
<div class="form-group col-md-6"><label for="sede_origen_nombre">Sede de origen *</label><input type="text" id="sede_origen_nombre" class="form-control" value="<?php echo h($sedeOrigenMostrar); ?>" placeholder="Se completa al seleccionar un activo" readonly><small class="text-muted">Se completa automáticamente según la sede donde está ubicado el activo seleccionado.</small></div>
<div class="form-group col-md-6"><label for="sede_destino_nombre">Sede de destino *</label><input type="text" id="sede_destino_nombre" class="form-control" value="" placeholder="Se completa automáticamente" readonly><input type="hidden" name="sede_destino_id" id="sede_destino_id" value="<?php echo h($valores['sede_destino_id']); ?>"><small class="text-muted">Se asigna automáticamente la sede contraria a la sede de origen.</small></div>
</div>
<div class="row">
<div class="form-group col-md-4"><label for="piso_destino">Piso *</label><input type="text" name="piso_destino" id="piso_destino" maxlength="30" class="form-control" value="<?php echo h($valores['piso_destino']); ?>" placeholder="Ej. 1" required></div>
<div class="form-group col-md-4"><label for="oficina_destino">Oficina *</label><input type="text" name="oficina_destino" id="oficina_destino" maxlength="150" class="form-control" value="<?php echo h($valores['oficina_destino']); ?>" placeholder="Nombre de la oficina" required></div>
<div class="form-group col-md-4"><label for="departamento_destino">Departamento *</label><input type="text" name="departamento_destino" id="departamento_destino" maxlength="120" class="form-control" value="<?php echo h($valores['departamento_destino']); ?>" placeholder="Área o departamento" required></div>
</div>
<div class="row"><div class="form-group col-md-6"><label for="fecha_movimiento">Fecha y hora del traslado *</label><input type="datetime-local" name="fecha_movimiento" id="fecha_movimiento" class="form-control" value="<?php echo h($valores['fecha_movimiento']); ?>" required></div></div>
<div class="row"><div class="form-group col-md-6"><label for="motivo">Motivo del traslado *</label><input type="text" name="motivo" id="motivo" maxlength="255" class="form-control" value="<?php echo h($valores['motivo']); ?>" placeholder="Ej. Reubicación por necesidades del servicio" required></div>
<div class="form-group col-md-6"><label for="autorizado_por">Autorizado por *</label><input type="text" name="autorizado_por" id="autorizado_por" maxlength="150" class="form-control" value="<?php echo h($valores['autorizado_por']); ?>" placeholder="Nombre de quien autoriza" required></div></div>
<div class="form-group"><label for="observaciones">Observaciones</label><textarea name="observaciones" id="observaciones" rows="3" class="form-control"><?php echo h($valores['observaciones']); ?></textarea></div>
<div class="alert alert-warning"><strong>Validaciones:</strong> No se permiten traslados de activos dados de baja/obsoletos ni con asignación a persona activa o pendiente de firma. Si existe una asignación activa a sede, el sistema solicitará confirmación adicional.</div>
<div class="form-group"><button type="submit" class="btn btn-primary" id="btnGuardar"><i class="fa fa-exchange"></i> Registrar traslado</button> <a href="manage-traslados.php" class="btn btn-default">Consultar historial</a></div></form>
</div></div></div></div></div></section></div></div></div>
<script>
(function(){
    const input = document.getElementById('buscarActivo');
    const results = document.getElementById('resultadosActivos');
    const hidden = document.getElementById('activo_id');
    const summary = document.getElementById('resumenActivo');
    const estadoActivo = document.getElementById('estadoActivo');
    const sedeOrigen = document.getElementById('sede_origen_nombre');
    const sedeSelect = document.getElementById('sede_destino_id');
    const sedeDestinoNombre = document.getElementById('sede_destino_nombre');
    const form = document.getElementById('formTraslado');

    const opcionesSedes = <?php echo json_encode(array_map(function($s) { return array('id'=>(string)$s['id'], 'nombre'=>$s['nombre_mostrar']); }, $sedesActivas), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

    let timer = null;
    let seleccionado = null;

    function esc(valor) {
        return String(valor ?? '').replace(/[&<>"']/g, caracter => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[caracter]));
    }

    function nombreSede(id) {
        const sede = opcionesSedes.find(item => item.id === String(id || ''));
        return sede ? sede.nombre : '';
    }

    function actualizarSedes(origenId, destinoPreferido) {
        const origen = String(origenId || '');
        const destinosDisponibles = opcionesSedes.filter(sede => sede.id !== origen);

        // Solo existen como destinos permitidos Rionegro y Apartadó: se toma la contraria.
        if (origen && destinosDisponibles.length === 1) {
            sedeSelect.value = destinosDisponibles[0].id;
            sedeDestinoNombre.value = destinosDisponibles[0].nombre;
        } else {
            sedeSelect.value = '';
            sedeDestinoNombre.value = '';
        }
    }

    function limpiarActivo() {
        hidden.value = '';
        seleccionado = null;
        summary.style.display = 'none';
        estadoActivo.value = '';
        sedeOrigen.value = '';
        actualizarSedes('', '');
    }

    input.addEventListener('input', function(){
        limpiarActivo();
        clearTimeout(timer);

        const termino = input.value.trim();
        if (termino.length < 2) {
            results.style.display = 'none';
            results.innerHTML = '';
            return;
        }

        timer = setTimeout(function(){
            fetch('registrar-traslado.php?buscar=' + encodeURIComponent(termino))
                .then(response => {
                    if (!response.ok) throw new Error('Error en la consulta');
                    return response.json();
                })
                .then(items => {
                    results.innerHTML = '';

                    if (!items.length) {
                        results.innerHTML = '<div class="list-group-item">No se encontraron activos.</div>';
                        results.style.display = 'block';
                        return;
                    }

                    items.forEach(activo => {
                        const boton = document.createElement('button');
                        boton.type = 'button';
                        boton.className = 'list-group-item';
                        boton.innerHTML = '<strong>' + esc(activo.codigo_inventario) + '</strong> — ' +
                            esc(activo.descripcion || 'Sin descripción') + '<br><small>' +
                            esc([
                                activo.marca,
                                activo.modelo,
                                activo.numero_serial ? 'Serial: ' + activo.numero_serial : '',
                                activo.codigo_placa ? 'Placa: ' + activo.codigo_placa : ''
                            ].filter(Boolean).join(' | ')) +
                            ' | ' + esc(activo.sede || 'Sin sede') +
                            ' / ' + esc(activo.ubicacion || 'Sin ubicación') +
                            ' | ' + esc(activo.estado) + '</small>';

                        if (activo.estado === 'DADO_DE_BAJA_OBSOLETO') {
                            boton.disabled = true;
                            boton.title = 'Activo dado de baja u obsoleto';
                        }

                        boton.addEventListener('click', function(){
                            seleccionado = activo;
                            hidden.value = activo.id;

                            // El origen se muestra automáticamente desde la sede real del activo.
                            const origenId = String(activo.sede_id || '');
                            sedeOrigen.value = nombreSede(origenId);

                            // El destino queda automáticamente en la única sede contraria.
                            actualizarSedes(origenId, '');

                            input.value = activo.codigo_inventario + ' — ' + (activo.descripcion || '');
                            estadoActivo.value = activo.estado;
                            document.getElementById('datoActivo').textContent =
                                activo.codigo_inventario + ' — ' + (activo.descripcion || '');
                            document.getElementById('origenActivo').textContent =
                                (activo.sede || 'Sin sede') + ' — ' + (activo.ubicacion || 'Sin ubicación');

                            summary.style.display = 'block';
                            results.style.display = 'none';
                        });

                        results.appendChild(boton);
                    });

                    results.style.display = 'block';
                })
                .catch(function(){
                    results.innerHTML = '<div class="list-group-item text-danger">No fue posible consultar los activos.</div>';
                    results.style.display = 'block';
                });
        }, 250);
    });

    document.addEventListener('click', function(event){
        if (!results.contains(event.target) && event.target !== input) {
            results.style.display = 'none';
        }
    });

    form.addEventListener('submit', function(event){
        if (!hidden.value) {
            event.preventDefault();
            alert('Debes seleccionar un activo de los resultados de búsqueda.');
            return;
        }

        if (seleccionado && seleccionado.estado === 'DADO_DE_BAJA_OBSOLETO') {
            event.preventDefault();
            alert('No se permite trasladar un activo dado de baja u obsoleto.');
            return;
        }

        if (!sedeOrigen.value) {
            event.preventDefault();
            alert('El activo seleccionado no tiene una sede de origen válida.');
            return;
        }

        if (!sedeSelect.value) {
            event.preventDefault();
            alert('No hay una sede de destino contraria disponible. Verifica las sedes activas.');
            return;
        }

        const origenId = seleccionado ? String(seleccionado.sede_id || '') : '';
        if (origenId && origenId === sedeSelect.value) {
            event.preventDefault();
            alert('La sede de destino debe ser la sede contraria a la sede de origen.');
        }
    });

    // Si se vuelve a mostrar el formulario después de una validación, reconstruye las sedes.
    const activoInicialId = <?php echo $activoSeleccionado ? (int)$activoSeleccionado['sede_id'] : 0; ?>;
    const destinoInicialId = <?php echo (int)$valores['sede_destino_id']; ?>;
    if (activoInicialId > 0) {
        actualizarSedes(String(activoInicialId), String(destinoInicialId || ''));
    } else {
        actualizarSedes('', '');
    }
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
