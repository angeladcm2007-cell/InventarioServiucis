<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
sisatRequireRoles([1, 2]);

function h($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function normalizarNombreSedeSolicitud($nombre) {
    $nombre = mb_strtolower(trim((string)$nombre), 'UTF-8');
    return strtr($nombre, array(
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u'
    ));
}

$error = '';
$ok = '';

$valores = array(
    'activo_id' => '',
    'piso_destino' => '',
    'oficina_destino' => '',
    'departamento_destino' => '',
    'fecha_movimiento' => date('Y-m-d\TH:i'),
    'motivo' => '',
    'observaciones' => ''
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($valores as $campo => $predeterminado) {
        $valores[$campo] = trim((string)($_POST[$campo] ?? $predeterminado));
    }

    $sedeDestinoId = (int)($_POST['sede_destino_id'] ?? 0);

    try {
        $activoId = (int)$valores['activo_id'];

        if ($activoId < 1 || $sedeDestinoId < 1) {
            throw new RuntimeException('Selecciona un activo válido. La sede de destino debe calcularse automáticamente.');
        }

        if (
            $valores['piso_destino'] === '' ||
            $valores['oficina_destino'] === '' ||
            $valores['departamento_destino'] === '' ||
            $valores['motivo'] === ''
        ) {
            throw new RuntimeException('Completa todos los campos obligatorios.');
        }

        if (
            mb_strlen($valores['piso_destino'], 'UTF-8') > 30 ||
            mb_strlen($valores['oficina_destino'], 'UTF-8') > 150 ||
            mb_strlen($valores['departamento_destino'], 'UTF-8') > 120 ||
            mb_strlen($valores['motivo'], 'UTF-8') > 255
        ) {
            throw new RuntimeException('Uno de los campos supera la longitud permitida.');
        }

        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $valores['fecha_movimiento']);
        if (!$dt || $dt->format('Y-m-d\TH:i') !== $valores['fecha_movimiento']) {
            throw new RuntimeException('Fecha u hora no válida.');
        }

        $dbh->beginTransaction();

        // Se vuelve a consultar la ubicación real del activo dentro de la transacción.
        // No se confía en la sede enviada por el navegador.
        $q = $dbh->prepare("
            SELECT
                a.id,
                a.ubicacion_id,
                a.estado,
                u.sede_id,
                u.estado AS estado_ubicacion,
                s.nombre AS sede_origen
            FROM activos a
            INNER JOIN ubicaciones u ON u.id = a.ubicacion_id
            INNER JOIN sedes s ON s.id = u.sede_id
            WHERE a.id = ?
            FOR UPDATE
        ");
        $q->execute(array($activoId));
        $activo = $q->fetch(PDO::FETCH_ASSOC);

        if (!$activo) {
            throw new RuntimeException('El activo no existe o no tiene una ubicación y sede válidas.');
        }

        if ($activo['estado'] === 'DADO_DE_BAJA_OBSOLETO') {
            throw new RuntimeException('No se permite solicitar el traslado de un activo dado de baja u obsoleto.');
        }

        if ($activo['estado_ubicacion'] !== 'ACTIVA') {
            throw new RuntimeException('La ubicación actual del activo está inactiva. Actualiza su ubicación en el inventario antes de solicitar el traslado.');
        }

        $origenNormalizado = normalizarNombreSedeSolicitud($activo['sede_origen']);
        if (!in_array($origenNormalizado, array('rionegro', 'apartado'), true)) {
            throw new RuntimeException('La sede actual del activo no corresponde a Rionegro ni Apartadó. Verifica primero su ubicación en el inventario.');
        }

        // Las sedes permitidas se consultan nuevamente en el servidor.
        $q = $dbh->query("SELECT id, nombre FROM sedes WHERE estado = 'ACTIVA' FOR UPDATE");
        $sedesActivas = $q->fetchAll(PDO::FETCH_ASSOC);

        $sedeOrigenValida = null;
        $sedeDestinoEsperada = null;

        foreach ($sedesActivas as $sedeFila) {
            $nombreNormalizado = normalizarNombreSedeSolicitud($sedeFila['nombre']);

            if ((int)$sedeFila['id'] === (int)$activo['sede_id']) {
                $sedeOrigenValida = $nombreNormalizado;
            }

            if (
                ($origenNormalizado === 'rionegro' && $nombreNormalizado === 'apartado') ||
                ($origenNormalizado === 'apartado' && $nombreNormalizado === 'rionegro')
            ) {
                $sedeDestinoEsperada = (int)$sedeFila['id'];
            }
        }

        if (!$sedeOrigenValida || !$sedeDestinoEsperada) {
            throw new RuntimeException('No se encontró la sede contraria activa. Verifica que Rionegro y Apartadó estén activas.');
        }

        if ($sedeDestinoId !== $sedeDestinoEsperada) {
            throw new RuntimeException('La sede de destino no corresponde a la sede contraria a la ubicación actual del activo.');
        }

        $ins = $dbh->prepare("
            INSERT INTO solicitudes_traslado (
                activo_id,
                ubicacion_origen_id,
                sede_destino_id,
                piso_destino,
                oficina_destino,
                departamento_destino,
                fecha_movimiento,
                motivo,
                observaciones,
                solicitado_por
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $ins->execute(array(
            $activoId,
            (int)$activo['ubicacion_id'],
            $sedeDestinoEsperada,
            $valores['piso_destino'],
            $valores['oficina_destino'],
            $valores['departamento_destino'],
            $dt->format('Y-m-d H:i:s'),
            $valores['motivo'],
            $valores['observaciones'] !== '' ? $valores['observaciones'] : null,
            (int)$_SESSION['uid']
        ));

        $dbh->commit();

        $ok = 'Solicitud enviada al administrador. El activo no cambiará de ubicación hasta su aprobación.';

        $valores = array(
            'activo_id' => '',
            'piso_destino' => '',
            'oficina_destino' => '',
            'departamento_destino' => '',
            'fecha_movimiento' => date('Y-m-d\TH:i'),
            'motivo' => '',
            'observaciones' => ''
        );
    } catch (Throwable $e) {
        if ($dbh->inTransaction()) {
            $dbh->rollBack();
        }
        $error = $e->getMessage();
    }
}

// Solo se ofrecen activos con una ubicación y una sede válida en Rionegro o Apartadó.
$q = $dbh->query("
    SELECT
        a.id,
        a.codigo_inventario,
        a.descripcion,
        a.estado,
        u.nombre AS ubicacion,
        u.sede_id,
        s.nombre AS sede
    FROM activos a
    INNER JOIN ubicaciones u ON u.id = a.ubicacion_id
    INNER JOIN sedes s ON s.id = u.sede_id
    WHERE a.estado <> 'DADO_DE_BAJA_OBSOLETO'
      AND u.estado = 'ACTIVA'
      AND s.estado = 'ACTIVA'
    ORDER BY a.codigo_inventario
");
$activos = array();
foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $activoFila) {
    $sedeNormalizada = normalizarNombreSedeSolicitud($activoFila['sede']);
    if (!in_array($sedeNormalizada, array('rionegro', 'apartado'), true)) {
        continue;
    }

    $activoFila['sede_mostrar'] = $sedeNormalizada === 'apartado' ? 'Apartadó' : 'Rionegro';
    $activos[] = $activoFila;
}

// Las opciones se limitan a las dos sedes utilizadas por el flujo de traslados.
$q = $dbh->query("SELECT id, nombre FROM sedes WHERE estado = 'ACTIVA' ORDER BY nombre");
$sedes = array();
foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $sedeFila) {
    $sedeNormalizada = normalizarNombreSedeSolicitud($sedeFila['nombre']);
    if (!in_array($sedeNormalizada, array('rionegro', 'apartado'), true)) {
        continue;
    }

    $sedeFila['nombre_mostrar'] = $sedeNormalizada === 'apartado' ? 'Apartadó' : 'Rionegro';
    $sedes[] = $sedeFila;
}

$sedesParaJs = array_map(function ($sedeFila) {
    return array(
        'id' => (string)$sedeFila['id'],
        'nombre' => $sedeFila['nombre_mostrar'],
        'normalizado' => normalizarNombreSedeSolicitud($sedeFila['nombre'])
    );
}, $sedes);

include __DIR__ . '/includes/topbar.php';
?>
<div class="content-wrapper">
    <div class="content-container">
        <?php include __DIR__ . '/includes/leftbar.php'; ?>
        <div class="main-page">
            <div class="container-fluid">
                <h2>Solicitar traslado de activo</h2>
                <p>El movimiento queda pendiente hasta que el administrador lo apruebe.</p>
            </div>

            <section class="section">
                <div class="container-fluid">
                    <div class="panel">
                        <div class="panel-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= h($error) ?></div>
                            <?php endif; ?>

                            <?php if ($ok): ?>
                                <div class="alert alert-success"><?= h($ok) ?></div>
                            <?php endif; ?>

                            <form method="post" id="formSolicitudTraslado" autocomplete="off">
                                <div class="form-group">
                                    <label for="activo_id">Activo *</label>
                                    <select class="form-control" name="activo_id" id="activo_id" required>
                                        <option value="">Seleccione un activo</option>
                                        <?php foreach ($activos as $a): ?>
                                            <option
                                                value="<?= (int)$a['id'] ?>"
                                                data-sede-id="<?= (int)$a['sede_id'] ?>"
                                                data-sede="<?= h($a['sede_mostrar']) ?>"
                                                data-sede-normalizada="<?= h(normalizarNombreSedeSolicitud($a['sede'])) ?>"
                                                data-ubicacion="<?= h($a['ubicacion']) ?>"
                                                <?= (string)$valores['activo_id'] === (string)$a['id'] ? 'selected' : '' ?>
                                            >
                                                <?= h($a['codigo_inventario'] . ' - ' . $a['descripcion'] . ' — ' . $a['sede_mostrar'] . ' / ' . $a['ubicacion']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Al seleccionar el activo, el sistema identificará su sede actual y establecerá automáticamente la sede contraria como destino.</small>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="sede_origen_nombre">Sede de origen *</label>
                                        <input type="text" class="form-control" id="sede_origen_nombre" readonly placeholder="Se completa al seleccionar el activo">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="sede_destino_nombre">Sede de destino *</label>
                                        <input type="text" class="form-control" id="sede_destino_nombre" readonly placeholder="Se asigna automáticamente">
                                        <input type="hidden" name="sede_destino_id" id="sede_destino_id" value="">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="piso_destino">Piso *</label>
                                    <input class="form-control" id="piso_destino" name="piso_destino" maxlength="30" value="<?= h($valores['piso_destino']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="oficina_destino">Oficina *</label>
                                    <input class="form-control" id="oficina_destino" name="oficina_destino" maxlength="150" value="<?= h($valores['oficina_destino']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="departamento_destino">Departamento / área *</label>
                                    <input class="form-control" id="departamento_destino" name="departamento_destino" maxlength="120" value="<?= h($valores['departamento_destino']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="fecha_movimiento">Fecha y hora *</label>
                                    <input class="form-control" type="datetime-local" id="fecha_movimiento" name="fecha_movimiento" value="<?= h($valores['fecha_movimiento']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="motivo">Motivo *</label>
                                    <textarea class="form-control" id="motivo" name="motivo" maxlength="255" required><?= h($valores['motivo']) ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="observaciones">Observaciones</label>
                                    <textarea class="form-control" id="observaciones" name="observaciones"><?= h($valores['observaciones']) ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary" id="btnEnviarSolicitud">Enviar solicitud</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
(function () {
    const activoSelect = document.getElementById('activo_id');
    const sedeOrigen = document.getElementById('sede_origen_nombre');
    const sedeDestinoNombre = document.getElementById('sede_destino_nombre');
    const sedeDestinoId = document.getElementById('sede_destino_id');
    const form = document.getElementById('formSolicitudTraslado');

    const sedes = <?= json_encode($sedesParaJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    function limpiarSedes() {
        sedeOrigen.value = '';
        sedeDestinoNombre.value = '';
        sedeDestinoId.value = '';
    }

    function actualizarSedes() {
        limpiarSedes();

        const opcion = activoSelect.options[activoSelect.selectedIndex];
        if (!opcion || !opcion.value) {
            return;
        }

        const origenId = String(opcion.dataset.sedeId || '');
        const origenNormalizado = String(opcion.dataset.sedeNormalizada || '');

        if (!origenId || !['rionegro', 'apartado'].includes(origenNormalizado)) {
            return;
        }

        const destinoEsperado = origenNormalizado === 'rionegro' ? 'apartado' : 'rionegro';
        const destino = sedes.find(function (sede) {
            return sede.normalizado === destinoEsperado;
        });

        if (!destino) {
            return;
        }

        sedeOrigen.value = opcion.dataset.sede || '';
        sedeDestinoNombre.value = destino.nombre;
        sedeDestinoId.value = destino.id;
    }

    activoSelect.addEventListener('change', actualizarSedes);

    form.addEventListener('submit', function (event) {
        if (!activoSelect.value) {
            event.preventDefault();
            alert('Selecciona un activo.');
            return;
        }

        if (!sedeOrigen.value || !sedeDestinoId.value || !sedeDestinoNombre.value) {
            event.preventDefault();
            alert('No fue posible determinar automáticamente las sedes de origen y destino. Verifica que Rionegro y Apartadó estén activas y que el activo tenga una ubicación válida.');
        }
    });

    // También reconstruye las sedes si el formulario vuelve a mostrarse tras un error.
    actualizarSedes();
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
