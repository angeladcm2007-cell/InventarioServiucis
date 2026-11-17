<?php
require_once __DIR__ . '/includes/auth.php';
sisatRequireRoles([1, 2, 3]); // Administrador, Técnico y Consulta pueden ver la jornada.
require_once __DIR__ . '/includes/config.php';

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function formatoTiempo($segundos) {
    $segundos = max(0, (int)$segundos);
    return sprintf('%02d:%02d:%02d', intdiv($segundos, 3600), intdiv($segundos % 3600, 60), $segundos % 60);
}

$id = (int)($_GET['id'] ?? $_POST['jornada_id'] ?? 0);
if ($id <= 0) { header('Location: manage-mantenimientos.php'); exit; }
$error = '';
$puedeEditar = sisatCanWrite();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sisatRequireRoles([1, 2]);
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'iniciar') {
            $dbh->beginTransaction();
            $q = $dbh->prepare("SELECT estado FROM jornadas_mantenimiento WHERE id = ? FOR UPDATE");
            $q->execute([$id]);
            $estadoJornada = $q->fetchColumn();
            if ($estadoJornada !== 'PROGRAMADA') {
                throw new Exception('La jornada ya fue iniciada o no está disponible.');
            }
            $dbh->prepare("UPDATE jornadas_mantenimiento SET estado='EN_EJECUCION', fecha_hora_inicio=NOW(), fecha_hora_cierre=NULL, tiempo_total_segundos=NULL WHERE id=?")
                ->execute([$id]);
            // Todos los mantenimientos de la jornada empiezan con el mismo instante inicial.
            $dbh->prepare("UPDATE mantenimientos SET fecha_hora_inicio=NOW(), fecha_hora_fin=NULL, tiempo_ejecucion_segundos=NULL WHERE jornada_id=?")
                ->execute([$id]);
            $dbh->commit();
            header('Location: ejecutar-jornada.php?id=' . $id . '&iniciada=1'); exit;
        }

        if ($accion === 'resultado') {
            $mid = (int)($_POST['mantenimiento_id'] ?? 0);
            $estado = $_POST['estado_atencion'] ?? '';
            $novedad = trim($_POST['novedad'] ?? '');
            $trabajo = trim($_POST['trabajo_realizado'] ?? '');

            if ($mid <= 0) throw new Exception('No se identificó el mantenimiento que desea actualizar.');
            if (!in_array($estado, ['PENDIENTE', 'REALIZADO', 'NOVEDAD', 'NO_REALIZADO'], true)) {
                throw new Exception('Seleccione un resultado válido.');
            }
            if ($estado === 'REALIZADO' && $trabajo === '') {
                throw new Exception('Describa el trabajo realizado antes de guardar el resultado REALIZADO.');
            }
            if (in_array($estado, ['NOVEDAD', 'NO_REALIZADO'], true) && $novedad === '') {
                throw new Exception('Describa la novedad o justificación.');
            }

            $dbh->beginTransaction();
            $q = $dbh->prepare("SELECT j.estado, m.id, m.fecha_hora_inicio FROM jornadas_mantenimiento j JOIN mantenimientos m ON m.jornada_id=j.id WHERE j.id=? AND m.id=? FOR UPDATE");
            $q->execute([$id, $mid]);
            $registro = $q->fetch(PDO::FETCH_ASSOC);
            if (!$registro) throw new Exception('El mantenimiento seleccionado no pertenece a esta jornada. Actualice la página e intente nuevamente.');
            if ($registro['estado'] !== 'EN_EJECUCION') throw new Exception('Solo puede registrar resultados cuando la jornada está en ejecución.');

            if ($estado === 'PENDIENTE') {
                // Si se devuelve a pendiente, vuelve a contar desde el inicio de la jornada.
                $sql = "UPDATE mantenimientos SET estado_atencion='PENDIENTE', novedad=NULL, trabajo_realizado=NULL, fecha_hora_fin=NULL, tiempo_ejecucion_segundos=NULL WHERE id=? AND jornada_id=?";
                $dbh->prepare($sql)->execute([$mid, $id]);
            } else {
                // Cada equipo detiene su propio tiempo al guardar un resultado final.
                $sql = "UPDATE mantenimientos
                        SET estado_atencion=?, novedad=?, trabajo_realizado=?,
                            fecha_hora_fin=COALESCE(fecha_hora_fin, NOW()),
                            tiempo_ejecucion_segundos=COALESCE(tiempo_ejecucion_segundos, TIMESTAMPDIFF(SECOND, fecha_hora_inicio, NOW()))
                        WHERE id=? AND jornada_id=?";
                $dbh->prepare($sql)->execute([$estado, $novedad !== '' ? $novedad : null, $trabajo !== '' ? $trabajo : null, $mid, $id]);
            }
            $dbh->commit();
            header('Location: ejecutar-jornada.php?id=' . $id . '&guardado=' . $mid); exit;
        }

        if ($accion === 'cerrar') {
            $dbh->beginTransaction();
            $q = $dbh->prepare("SELECT * FROM jornadas_mantenimiento WHERE id=? FOR UPDATE");
            $q->execute([$id]);
            $jornada = $q->fetch(PDO::FETCH_ASSOC);
            if (!$jornada || $jornada['estado'] !== 'EN_EJECUCION') {
                throw new Exception('Solo se puede cerrar una jornada que esté en ejecución.');
            }

            $q = $dbh->prepare("SELECT COUNT(*) FROM mantenimientos WHERE jornada_id=? AND estado_atencion='PENDIENTE'");
            $q->execute([$id]);
            if ((int)$q->fetchColumn() > 0) {
                throw new Exception('Aún hay equipos pendientes. Registre un resultado para cada equipo antes de cerrar la jornada.');
            }

            $q = $dbh->prepare("SELECT id, activo_id, estado_atencion FROM mantenimientos WHERE jornada_id=? FOR UPDATE");
            $q->execute([$id]);
            $itemsCerrar = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($itemsCerrar as $m) {
                if ($m['estado_atencion'] === 'REALIZADO' || $m['estado_atencion'] === 'NOVEDAD') {
                    $resultadoActivo = $m['estado_atencion'] === 'REALIZADO' ? 'OPERATIVO' : 'OPERATIVO_CON_OBSERVACIONES';
                    $proxima = $jornada['proxima_fecha_manual'];
                    if (!empty($jornada['periodicidad_meses'])) {
                        $calc = $dbh->prepare("SELECT DATE_ADD(CURDATE(), INTERVAL ? MONTH)");
                        $calc->execute([(int)$jornada['periodicidad_meses']]);
                        $proxima = $calc->fetchColumn();
                    }
                    $dbh->prepare("UPDATE mantenimientos SET resultado=?, fecha_finalizacion=CURDATE(), proximo_mantenimiento=?, fecha_hora_fin=COALESCE(fecha_hora_fin,NOW()), tiempo_ejecucion_segundos=COALESCE(tiempo_ejecucion_segundos,TIMESTAMPDIFF(SECOND,fecha_hora_inicio,NOW())) WHERE id=?")
                        ->execute([$resultadoActivo, $proxima, $m['id']]);
                    $dbh->prepare("UPDATE activos SET fecha_mantenimiento=CURDATE(), fecha_actualizacion=NOW() WHERE id=?")
                        ->execute([$m['activo_id']]);
                } else {
                    // NO_REALIZADO no actualiza la hoja de vida como si el trabajo se hubiera completado.
                    $dbh->prepare("UPDATE mantenimientos SET resultado='PENDIENTE', fecha_finalizacion=NULL, fecha_hora_fin=COALESCE(fecha_hora_fin,NOW()), tiempo_ejecucion_segundos=COALESCE(tiempo_ejecucion_segundos,TIMESTAMPDIFF(SECOND,fecha_hora_inicio,NOW())) WHERE id=?")
                        ->execute([$m['id']]);
                }
            }

            $dbh->prepare("UPDATE jornadas_mantenimiento SET estado='CERRADA', fecha_hora_cierre=NOW(), tiempo_total_segundos=TIMESTAMPDIFF(SECOND, fecha_hora_inicio, NOW()) WHERE id=?")
                ->execute([$id]);
            $dbh->prepare("INSERT INTO auditoria(usuario_id,tabla_afectada,registro_id,accion,descripcion,direccion_ip) VALUES(?, 'jornadas_mantenimiento', ?, 'UPDATE', ?, ?)")
                ->execute([(int)$_SESSION['uid'], $id, 'Cierre de jornada. Se actualizaron las hojas de vida de los equipos realizados o con novedad; los no realizados conservan resultado pendiente.', $_SERVER['REMOTE_ADDR'] ?? null]);
            $dbh->commit();
            header('Location: ejecutar-jornada.php?id=' . $id . '&cerrada=1'); exit;
        }

        throw new Exception('La acción solicitada no es válida.');
    } catch (Throwable $e) {
        if ($dbh->inTransaction()) $dbh->rollBack();
        $error = $e->getMessage();
    }
}

$q = $dbh->prepare("SELECT j.*, TIMESTAMPDIFF(SECOND, j.fecha_hora_inicio, COALESCE(j.fecha_hora_cierre, NOW())) AS segundos_transcurridos FROM jornadas_mantenimiento j WHERE j.id=?");
$q->execute([$id]);
$j = $q->fetch(PDO::FETCH_ASSOC);
if (!$j) { http_response_code(404); exit('Jornada no encontrada.'); }

$q = $dbh->prepare("SELECT m.*, a.codigo_inventario, a.descripcion AS activo, s.nombre AS sede, u.nombre AS ubicacion,
                    CASE WHEN m.tiempo_ejecucion_segundos IS NOT NULL THEN m.tiempo_ejecucion_segundos
                         WHEN m.fecha_hora_inicio IS NOT NULL THEN TIMESTAMPDIFF(SECOND,m.fecha_hora_inicio,NOW()) ELSE 0 END AS segundos_equipo
                    FROM mantenimientos m
                    JOIN activos a ON a.id=m.activo_id
                    LEFT JOIN ubicaciones u ON u.id=a.ubicacion_id
                    LEFT JOIN sedes s ON s.id=u.sede_id
                    WHERE m.jornada_id=? ORDER BY a.codigo_inventario, m.id");
$q->execute([$id]);
$items = $q->fetchAll(PDO::FETCH_ASSOC);
$pend = $real = $nov = $no = 0;
foreach ($items as $x) {
    if ($x['estado_atencion'] === 'PENDIENTE') $pend++;
    elseif ($x['estado_atencion'] === 'REALIZADO') $real++;
    elseif ($x['estado_atencion'] === 'NOVEDAD') $nov++;
    elseif ($x['estado_atencion'] === 'NO_REALIZADO') $no++;
}
$segundosJornada = max(0, (int)($j['segundos_transcurridos'] ?? 0));

include __DIR__ . '/includes/topbar.php';
?>
<div class="content-wrapper"><div class="content-container">
<?php include __DIR__ . '/includes/leftbar.php'; ?>
<div class="main-page"><div class="container-fluid">
    <div class="row page-title-div"><div class="col-sm-12"><h2 class="title">Jornada de mantenimiento #<?= (int)$id ?></h2><p><?= h($j['nombre']) ?> | <?= h($j['fecha_programada']) ?></p></div></div>
</div>
<section class="section"><div class="container-fluid">
<?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
<?php if (isset($_GET['iniciada'])): ?><div class="alert alert-success">Jornada iniciada</div><?php endif; ?>
<?php if (isset($_GET['guardado'])): ?><div class="alert alert-success">El resultado y las notas del equipo fueron guardados correctamente.</div><?php endif; ?>
<?php if (isset($_GET['cerrada'])): ?><div class="alert alert-success">Jornada cerrada. El cronómetro quedó detenido y se actualizaron las hojas de vida correspondientes.</div><?php endif; ?>

<div class="panel"><div class="panel-body p-20">
    <div class="row">
        <div class="col-md-2"><strong>Estado:</strong><br><?= h($j['estado']) ?></div>
        <div class="col-md-2"><strong>Equipos:</strong><br><?= count($items) ?></div>
        <div class="col-md-2"><strong>Realizados:</strong><br><?= $real ?></div>
        <div class="col-md-2"><strong>Con novedad:</strong><br><?= $nov ?></div>
        <div class="col-md-2"><strong>No realizados:</strong><br><?= $no ?></div>
        <div class="col-md-2"><strong>Pendientes:</strong><br><?= $pend ?></div>
    </div><hr>
    <div class="row">
        <div class="col-md-6"><h3 id="timer" data-segundos="<?= $segundosJornada ?>" style="font-weight:700;margin-top:5px"><?= h(formatoTiempo($segundosJornada)) ?></h3><small>Tiempo total de la jornada (se detiene al cerrar la jornada)</small></div>
        <div class="col-md-6 text-right">
        <?php if ($puedeEditar && $j['estado'] === 'PROGRAMADA'): ?>
            <form method="post"><input type="hidden" name="jornada_id" value="<?= (int)$id ?>"><input type="hidden" name="accion" value="iniciar"><button type="submit" class="btn btn-success">Iniciar mantenimiento</button></form>
        <?php elseif ($puedeEditar && $j['estado'] === 'EN_EJECUCION'): ?>
            <form method="post" onsubmit="return confirm('¿Cerrar la jornada? Verifique que todos los equipos tengan un resultado.');"><input type="hidden" name="jornada_id" value="<?= (int)$id ?>"><input type="hidden" name="accion" value="cerrar"><button type="submit" class="btn btn-danger" <?= $pend ? 'disabled' : '' ?>>Cerrar jornada</button></form>
        <?php elseif ($j['estado'] === 'CERRADA'): ?><span class="label label-success">Jornada finalizada</span><?php endif; ?>
        </div>
    </div>
</div></div>

<div class="panel"><div class="panel-heading"><h5>Equipos de la jornada y registro individual de resultados</h5><p class="text-muted">Cada fila guarda únicamente el mantenimiento de ese equipo. Puede registrar las notas de todos los equipos por separado.</p></div>
<div class="panel-body p-20"><div class="table-responsive"><table class="table table-bordered table-striped">
<thead><tr><th style="min-width:180px">Código / activo</th><th style="min-width:130px">Ubicación</th><th style="min-width:115px">Resultado</th><th style="min-width:170px">Tiempo del equipo</th><th style="min-width:280px">Trabajo realizado / notas</th><th style="min-width:300px">Guardar resultado</th></tr></thead><tbody>
<?php foreach ($items as $m): $formId = 'resultado_' . (int)$m['id']; ?>
<tr>
    <td><strong><?= h($m['codigo_inventario']) ?></strong><br><?= h($m['activo']) ?></td>
    <td><?= h($m['sede']) ?><br><?= h($m['ubicacion']) ?></td>
    <td><strong><?= h($m['estado_atencion']) ?></strong></td>
    <td><span class="tiempo-equipo" data-segundos="<?= (int)$m['segundos_equipo'] ?>" data-activo="<?= ($j['estado'] === 'EN_EJECUCION' && empty($m['fecha_hora_fin']) && $m['estado_atencion'] === 'PENDIENTE') ? '1' : '0' ?>"><?= h(formatoTiempo((int)$m['segundos_equipo'])) ?></span>
        <?php if (!empty($m['fecha_hora_fin'])): ?><br><small class="text-success">Tiempo detenido</small><?php elseif ($m['estado_atencion'] !== 'PENDIENTE'): ?><br><small class="text-success">Resultado guardado</small><?php endif; ?></td>
    <td><?= nl2br(h($m['trabajo_realizado'])) ?><?php if (!empty($m['novedad'])): ?><br><span class="text-danger"><?= nl2br(h($m['novedad'])) ?></span><?php endif; ?></td>
    <td>
    <?php if ($puedeEditar && $j['estado'] === 'EN_EJECUCION'): ?>
        <form id="<?= h($formId) ?>" method="post" class="form-resultado" style="min-width:260px">
            <input type="hidden" name="jornada_id" value="<?= (int)$id ?>"><input type="hidden" name="accion" value="resultado"><input type="hidden" name="mantenimiento_id" value="<?= (int)$m['id'] ?>">
            <label>Resultado</label><select name="estado_atencion" class="form-control estado-equipo" required>
                <option value="PENDIENTE" <?= $m['estado_atencion'] === 'PENDIENTE' ? 'selected' : '' ?>>Pendiente</option>
                <option value="REALIZADO" <?= $m['estado_atencion'] === 'REALIZADO' ? 'selected' : '' ?>>Realizado</option>
                <option value="NOVEDAD" <?= $m['estado_atencion'] === 'NOVEDAD' ? 'selected' : '' ?>>Con novedad</option>
                <option value="NO_REALIZADO" <?= $m['estado_atencion'] === 'NO_REALIZADO' ? 'selected' : '' ?>>No realizado</option>
            </select>
            <label style="margin-top:7px">Trabajo realizado / notas</label><textarea name="trabajo_realizado" class="form-control trabajo-equipo" rows="2" placeholder="Describa las actividades realizadas"><?= h($m['trabajo_realizado']) ?></textarea>
            <label style="margin-top:7px">Novedad / justificación</label><textarea name="novedad" class="form-control novedad-equipo" rows="2" placeholder="Obligatoria para Con novedad o No realizado"><?= h($m['novedad']) ?></textarea>
            <button type="submit" class="btn btn-primary btn-xs" style="margin-top:8px">Guardar este equipo</button>
        </form>
    <?php else: ?><span class="text-muted"><?= $j['estado'] === 'CERRADA' ? 'Jornada cerrada' : 'Solo consulta' ?></span><?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="6" class="text-center">Esta jornada no tiene mantenimientos asociados. Revise la programación masiva.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<a href="manage-mantenimientos.php" class="btn btn-default">Volver a consultar mantenimientos</a>
</div></section></div></div></div>
<script>
(function () {
    // El valor inicial lo calcula MySQL con su propio reloj; así se evita el desfase de 5 horas
    // que se producía al interpretar NOW() con la zona horaria de PHP y compararlo con el navegador.
    var timer = document.getElementById('timer');
    if (timer && <?= $j['estado'] === 'EN_EJECUCION' ? 'true' : 'false' ?>) {
        var total = parseInt(timer.getAttribute('data-segundos'), 10) || 0;
        var inicioCliente = Date.now();
        function pintar(seg, elemento) {
            seg = Math.max(0, Math.floor(seg));
            var hh = String(Math.floor(seg / 3600)).padStart(2, '0');
            var mm = String(Math.floor((seg % 3600) / 60)).padStart(2, '0');
            var ss = String(seg % 60).padStart(2, '0');
            elemento.textContent = hh + ':' + mm + ':' + ss;
        }
        function tickJornada() { pintar(total + Math.floor((Date.now() - inicioCliente) / 1000), timer); }
        tickJornada(); window.setInterval(tickJornada, 1000);
    }
    document.querySelectorAll('.tiempo-equipo[data-activo="1"]').forEach(function (el) {
        var base = parseInt(el.getAttribute('data-segundos'), 10) || 0;
        var inicio = Date.now();
        function tickEquipo() { pintarEquipo(base + Math.floor((Date.now() - inicio) / 1000), el); }
        function pintarEquipo(seg, elemento) {
            seg = Math.max(0, Math.floor(seg));
            elemento.textContent = String(Math.floor(seg / 3600)).padStart(2, '0') + ':' + String(Math.floor((seg % 3600) / 60)).padStart(2, '0') + ':' + String(seg % 60).padStart(2, '0');
        }
        tickEquipo(); window.setInterval(tickEquipo, 1000);
    });
    document.querySelectorAll('.form-resultado').forEach(function (form) {
        var estado = form.querySelector('.estado-equipo');
        var trabajo = form.querySelector('.trabajo-equipo');
        var novedad = form.querySelector('.novedad-equipo');
        function validarCampos() {
            trabajo.required = estado.value === 'REALIZADO';
            novedad.required = estado.value === 'NOVEDAD' || estado.value === 'NO_REALIZADO';
        }
        estado.addEventListener('change', validarCampos); validarCampos();
    });
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
