<?php
require_once __DIR__ . '/includes/auth.php';
sisatRequireRoles([1, 2, 3]);
require_once __DIR__ . '/includes/config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: manage-mantenimientos.php'); exit; }

try {
    $dbh->beginTransaction();
    $q = $dbh->prepare("SELECT * FROM mantenimientos WHERE id = ? FOR UPDATE");
    $q->execute([$id]);
    $m = $q->fetch(PDO::FETCH_ASSOC);
    if (!$m) throw new Exception('No se encontró el mantenimiento solicitado.');

    $jornadaId = (int)($m['jornada_id'] ?? 0);
    if ($jornadaId <= 0) {
        $nombre = 'Jornada individual - ' . $m['tipo_mantenimiento'] . ' - ' . $m['fecha_programada'];
        $j = $dbh->prepare("INSERT INTO jornadas_mantenimiento
            (nombre, tipo_mantenimiento, fecha_programada, actividad, tecnico_responsable,
             periodicidad_meses, proxima_fecha_manual, observaciones, registrado_por)
            VALUES (?, ?, ?, ?, ?, NULL, NULL, ?, ?)");
        $j->execute([$nombre, $m['tipo_mantenimiento'], $m['fecha_programada'], $m['diagnostico'],
            $m['tecnico_responsable'] ?: null, $m['observaciones'] ?: null, (int)$m['registrado_por']]);
        $jornadaId = (int)$dbh->lastInsertId();
        $up = $dbh->prepare("UPDATE mantenimientos SET jornada_id = ?, estado_atencion = COALESCE(NULLIF(estado_atencion, ''), 'PENDIENTE') WHERE id = ?");
        $up->execute([$jornadaId, $id]);
        $audit = $dbh->prepare("INSERT INTO auditoria
            (usuario_id, tabla_afectada, registro_id, accion, descripcion, direccion_ip)
            VALUES (?, 'jornadas_mantenimiento', ?, 'INSERT', ?, ?)");
        $audit->execute([(int)$_SESSION['uid'], $jornadaId,
            'Conversión de mantenimiento individual ID ' . $id . ' a jornada individual para permitir su seguimiento.',
            $_SERVER['REMOTE_ADDR'] ?? null]);
    }
    $dbh->commit();
    header('Location: ejecutar-jornada.php?id=' . $jornadaId);
    exit;
} catch (Throwable $e) {
    if ($dbh->inTransaction()) $dbh->rollBack();
    http_response_code(500);
    echo 'No fue posible abrir la jornada individual: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
