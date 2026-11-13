<?php
session_start();
require_once __DIR__ . '/includes/config.php';
if (empty($_SESSION['alogin']) || empty($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}
function h($v)
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
$msg = '';
$error = '';
// Los activos se consultan bajo demanda mediante buscar-activos-asignacion.php.
if (empty($_SESSION['csrf_asignacion'])) $_SESSION['csrf_asignacion'] = bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_asignacion'])) {
    try {
        if (!hash_equals($_SESSION['csrf_asignacion'], (string)($_POST['csrf_token'] ?? ''))) throw new RuntimeException('La sesión del formulario venció. Actualiza la página e inténtalo nuevamente.');
        $activoId = (int)($_POST['activo_id'] ?? 0);
        $tipo = (string)($_POST['tipo_asignacion'] ?? 'SEDE');
        $fecha = (string)($_POST['fecha_asignacion'] ?? date('Y-m-d'));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        if ($activoId < 1 || !in_array($tipo, array('SEDE', 'PERSONA'), true)) throw new RuntimeException('Selecciona un activo y un tipo de asignación válido.');
        $personaId = null; // Se conserva NULL para compatibilidad con la relación histórica a personas.
        $funcionarioReceptor = trim((string)($_POST['funcionario_receptor'] ?? ''));
        $areaDepartamentoAsignacion = trim((string)($_POST['area_departamento_asignacion'] ?? ''));
        if ($tipo === 'PERSONA') {
            if ($funcionarioReceptor === '') throw new RuntimeException('Escribe el nombre del funcionario que recibe el activo.');
            if (mb_strlen($funcionarioReceptor, 'UTF-8') > 150) throw new RuntimeException('El nombre del funcionario no puede superar 150 caracteres.');
            if (mb_strlen($areaDepartamentoAsignacion, 'UTF-8') > 150) throw new RuntimeException('El área o departamento no puede superar 150 caracteres.');
        } else {
            $funcionarioReceptor = '';
            $areaDepartamentoAsignacion = '';
        }
        $espacios = array();
        if ($tipo === 'SEDE') {
            $ubicaciones = $_POST['ubicacion_espacio'] ?? array();
            $areas = $_POST['area_departamento'] ?? array();
            foreach ($ubicaciones as $i => $espacio) {
                $espacio = trim((string)$espacio);
                $area = trim((string)($areas[$i] ?? ''));
                if ($espacio === '' && $area === '') continue;
                if ($espacio === '') throw new RuntimeException('Completa Ubicación / espacio en cada oficina agregada.');
                $espacios[] = array($espacio, $area);
            }
        }
        $dbh->beginTransaction();
        $check = $dbh->prepare("SELECT a.id, u.area_departamento FROM activos a LEFT JOIN ubicaciones u ON u.id=a.ubicacion_id WHERE a.id=:id AND a.estado <> 'DADO_DE_BAJA_OBSOLETO' FOR UPDATE");
        $check->execute(array(':id' => $activoId));
        $activoSeleccionado = $check->fetch(PDO::FETCH_ASSOC);
        if (!$activoSeleccionado) throw new RuntimeException('El activo seleccionado no existe o está dado de baja.');
        // Si el área no fue modificada o llegó vacía, conservar el área registrada en el inventario.
        if ($tipo === 'PERSONA' && $areaDepartamentoAsignacion === '') {
            $areaDepartamentoAsignacion = trim((string)($activoSeleccionado['area_departamento'] ?? ''));
        }
        $dup = $dbh->prepare("SELECT numero_control FROM asignaciones WHERE activo_id=:id AND estado_asignacion IN ('ACTIVA','PENDIENTE_FIRMA') LIMIT 1");
        $dup->execute(array(':id' => $activoId));
        if ($dup->fetchColumn()) throw new RuntimeException('Este activo ya tiene una asignación activa. Cierra la asignación anterior antes de registrar otra.');
        $numeroControl = 'ASIG-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $ins = $dbh->prepare("INSERT INTO asignaciones (activo_id, persona_id, funcionario_receptor, area_departamento_asignacion, numero_control, fecha_asignacion, estado_asignacion, descripcion, registrado_por, tipo_asignacion) VALUES (:activo_id,:persona_id,:funcionario_receptor,:area_departamento_asignacion,:numero_control,:fecha,:estado_asignacion,:descripcion,:registrado_por,:tipo)");
        $estadoInicial = ($tipo === 'PERSONA') ? 'PENDIENTE_FIRMA' : 'ACTIVA';
        $ins->execute(array(':estado_asignacion' => $estadoInicial, ':activo_id' => $activoId, ':persona_id' => $personaId, ':funcionario_receptor' => ($funcionarioReceptor !== '' ? $funcionarioReceptor : null), ':area_departamento_asignacion' => ($areaDepartamentoAsignacion !== '' ? $areaDepartamentoAsignacion : null), ':numero_control' => $numeroControl, ':fecha' => $fecha, ':descripcion' => ($descripcion !== '' ? $descripcion : null), ':registrado_por' => (int)$_SESSION['uid'], ':tipo' => $tipo));
        $asignacionId = (int)$dbh->lastInsertId();
        // Conserva evidencia de quién registró la asignación y los datos capturados.
        $audit = $dbh->prepare("INSERT INTO auditoria (usuario_id, tabla_afectada, registro_id, accion, descripcion, direccion_ip) VALUES (:usuario, 'asignaciones', :registro, 'INSERT', :detalle, :ip)");
        $detalleAuditoria = 'Asignación ' . $numeroControl . ' creada. Modalidad: ' . $tipo . '. Activo ID: ' . $activoId . '. Funcionario receptor: ' . ($funcionarioReceptor !== '' ? $funcionarioReceptor : 'No aplica') . '. Área/departamento: ' . ($areaDepartamentoAsignacion !== '' ? $areaDepartamentoAsignacion : 'No aplica') . '.';
        $audit->execute(array(':usuario' => (int)$_SESSION['uid'], ':registro' => $asignacionId, ':detalle' => $detalleAuditoria, ':ip' => ($_SERVER['REMOTE_ADDR'] ?? null)));
        if ($tipo === 'SEDE' && $espacios) {
            $areaIns = $dbh->prepare("INSERT INTO asignacion_areas_usuarias (asignacion_id, ubicacion_espacio, area_departamento) VALUES (:asignacion_id,:espacio,:area)");
            foreach ($espacios as $fila) $areaIns->execute(array(':asignacion_id' => $asignacionId, ':espacio' => $fila[0], ':area' => ($fila[1] !== '' ? $fila[1] : null)));
        }
        $dbh->commit();
        header('Location: manage-asignacion.php?registrada=1&estado=' . urlencode($estadoInicial) . '&id=' . $asignacionId);
        exit;
    } catch (Throwable $e) {
        if ($dbh->inTransaction()) $dbh->rollBack();
        $error = $e instanceof PDOException ? 'No fue posible guardar la asignación. Verifica que hayas ejecutado la actualización SQL del módulo.' : $e->getMessage();
        error_log('Error al registrar asignación: ' . $e->getMessage());
    }
}
?>
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="content-wrapper">
    <style>
        #resultadosActivos {
            overflow-x: auto !important;
            overflow-y: auto !important;
        }

        .resultado-activo {
            display: block;
            width: max-content;
            min-width: 100%;
            padding: 9px 12px;
            border: 0;
            border-bottom: 1px solid #eee;
            background: #fff;
            color: #333;
            text-align: left;
            white-space: nowrap;
            cursor: pointer;
        }

        .resultado-activo:hover:not(:disabled) {
            background: #eaf3ff;
        }

        .resultado-activo:disabled {
            color: #999;
            background: #f3f3f3;
            cursor: not-allowed;
        }

        .resultado-activo.activo-seleccionado {
            background: #d9edf7;
            color: #23527c;
            font-weight: bold;
        }
    </style>
    <div class="content-container"><?php include __DIR__ . '/includes/leftbar.php'; ?><div class="main-page">
            <div class="container-fluid">
                <div class="row page-title-div">
                    <div class="col-md-12">
                        <h2 class="title">Asignación de activos</h2>
                    </div>
                </div>
                <div class="row breadcrumb-div">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li><a href="dashboard.php">Inicio</a></li>
                            <li><a href="manage-asignacion.php">Asignaciones</a></li>
                            <li class="active">Agregar asignación</li>
                        </ul>
                    </div>
                </div>
            </div>
            <section class="section">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <h5>Detalles de asignación</h5>
                                    </div>
                                </div>
                                <div class="panel-body">
                                    <?php if ($error): ?><div class="alert alert-danger"><?php echo h($error); ?></div><?php endif; ?>
                                    <form method="post" id="formAsignacion"><input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_asignacion']); ?>">
                                        <div class="row">
                                            <div class="col-md-6 form-group"><label for="buscarActivo">Buscar activo *</label><input type="text" class="form-control" id="buscarActivo" autocomplete="off" placeholder="Código, placa, marca, modelo, serial, categoría o sede"><small class="text-muted">Escribe al menos 2 caracteres. Se consultan hasta 100 coincidencias.</small>
                                                <div
                                                    id="resultadosActivos"
                                                    style="
        display:none;
        width:100%;
        max-width:100%;
        height:260px;
        overflow:auto;
        border:1px solid #ccc;
        border-radius:4px;
        margin-top:8px;
        background:#fff;
    "
                                                    aria-label="Resultados de activos">
                                                </div><input type="hidden" name="activo_id" id="activo_id">
                                                <div id="estadoBusqueda" class="help-block">Ningún activo seleccionado.</div>
                                            </div>
                                            <div class="col-md-6 form-group"><label>Fecha de asignación *</label><input class="form-control" type="date" name="fecha_asignacion" value="<?php echo date('Y-m-d'); ?>" required></div>
                                            <div class="col-md-12">
                                                <div id="datosActivo" class="alert alert-info" style="display:none"><strong>Información tomada del inventario</strong>
                                                    <div class="row" style="margin-top:8px">
                                                        <div class="col-md-3"><b>Código:</b> <span id="vCodigo"></span></div>
                                                        <div class="col-md-3"><b>Sede:</b> <span id="vSede"></span></div>
                                                        <div class="col-md-3"><b>Ubicación física:</b> <span id="vUbicacion"></span></div>
                                                        <div class="col-md-3"><b>Área registrada:</b> <span id="vArea"></span></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12 form-group"><label>Tipo de asignación *</label><select class="form-control" name="tipo_asignacion" id="tipo_asignacion" required>
                                                    <option value="SEDE">Activo permanece en sede / uso compartido</option>
                                                    <option value="PERSONA">Entrega directa a una persona</option>
                                                </select></div>
                                            <div id="seccionCompartido" class="col-md-12">
                                                <hr>
                                                <h4>Comparte con</h4>
                                                <p class="text-muted">Registra las oficinas o áreas adicionales que pueden utilizar el activo. La ubicación física principal se conserva desde el inventario.</p>
                                                <div id="listaOficinas">
                                                    <div class="oficina-item row" style="border:1px solid #eee;padding:12px;margin:8px 0;border-radius:4px">
                                                        <div class="col-md-6 form-group"><label>Ubicación / espacio</label><input class="form-control" name="ubicacion_espacio[]" placeholder="Ej. Oficina de sistemas, cuarto técnico"></div>
                                                        <div class="col-md-6 form-group"><label>Área o departamento</label><input class="form-control" name="area_departamento[]" placeholder="Ej. Contabilidad, Recepción, Gerencia"></div>
                                                    </div>
                                                </div><button type="button" class="btn btn-default btn-sm" id="agregarOficina"><i class="fa fa-plus"></i> Agregar otra oficina</button>
                                            </div>
                                            <div id="seccionPersona" class="col-md-12" style="display:none">
                                                <hr>
                                                <h4>Entrega individual</h4>
                                                <div class="row">
                                                    <div class="col-md-6 form-group"><label>Funcionario que recibe *</label><input type="text" class="form-control" name="funcionario_receptor" id="funcionario_receptor" maxlength="150" placeholder="Escriba el nombre del funcionario que recibe el activo"></div>
                                                    <div class="col-md-6 form-group"><label>Área o departamento</label><input type="text" class="form-control" name="area_departamento_asignacion" id="area_departamento_asignacion" maxlength="150" placeholder="Área tomada del inventario; puede modificarla"></div>
                                                </div>
                                                <div class="alert alert-warning">El nombre del funcionario se registra como texto libre. El área o departamento se carga desde el inventario y puede modificarse para dejar constancia del destino al momento de la entrega.</div>
                                            </div>
                                            <div class="col-md-12 form-group"><label>Descripción / observaciones</label><textarea class="form-control" name="descripcion" rows="2" placeholder="Información adicional de la asignación"></textarea></div>
                                            <div class="col-md-12 form-group"><button type="submit" name="guardar_asignacion" class="btn btn-primary"><i class="fa fa-save"></i> Guardar asignación</button> <a href="manage-asignacion.php" class="btn btn-danger">Cancelar</a></div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
<script>
    (function() {
        var tipo = document.getElementById('tipo_asignacion');
        var secSede = document.getElementById('seccionCompartido');
        var secPersona = document.getElementById('seccionPersona');
        var receptor = document.getElementById('funcionario_receptor');
        var buscar = document.getElementById('buscarActivo');
        var resultados = document.getElementById('resultadosActivos');
        var activoId = document.getElementById('activo_id');
        var estado = document.getElementById('estadoBusqueda');
        var timer = null,
            consultaActual = 0;

        function cambiarTipo() {
            var individual = tipo.value === 'PERSONA';
            secSede.style.display = individual ? 'none' : 'block';
            secPersona.style.display = individual ? 'block' : 'none';
            receptor.required = individual;
            document.querySelectorAll('#listaOficinas input').forEach(function(x) {
                x.required = false;
            });
        }

        function limpiarSeleccion() {
            activoId.value = '';
            document.getElementById('datosActivo').style.display = 'none';
            var area = document.getElementById('area_departamento_asignacion');
            if (area) area.value = '';
            estado.textContent = 'Ningún activo seleccionado.';
        }

        function mostrarActivo(a) {
            activoId.value = a.id;
            estado.textContent = 'Seleccionado: ' + (a.codigo_inventario || 'Sin código') + ' — ' + (a.descripcion || 'Sin descripción') + ' | ' + (a.sede || 'Sin sede') + ' | ' + (a.estado || 'Sin estado');
            document.getElementById('datosActivo').style.display = 'block';
            document.getElementById('vCodigo').textContent = a.codigo_inventario || 'No registrado';
            document.getElementById('vSede').textContent = a.sede || 'No registrada';
            document.getElementById('vUbicacion').textContent = a.ubicacion || 'No registrada';
            document.getElementById('vArea').textContent = a.area_departamento || 'No registrada';
            var area = document.getElementById('area_departamento_asignacion');
            if (area) area.value = a.area_departamento || '';
        }

        function consultar() {
            var q = buscar.value.trim();
            var numero = ++consultaActual;
            limpiarSeleccion();
            resultados.innerHTML = '';
            if (q.length < 2) {
                resultados.style.display = 'none';
                estado.textContent = 'Escribe al menos 2 caracteres para buscar.';
                return;
            }
            estado.textContent = 'Buscando activos...';
            fetch('buscar-activos-asignacion.php?q=' + encodeURIComponent(q), {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(function(r) {
                    if (!r.ok) throw new Error('No fue posible consultar.');
                    return r.json();
                })
                .then(function(data) {
                    if (numero !== consultaActual) return;
                    resultados.innerHTML = '';
                    var lista = Array.isArray(data.results) ? data.results : [];
                    lista.forEach(function(a) {

                        var boton = document.createElement('button');

                        boton.type = 'button';
                        boton.className = 'resultado-activo';

                        boton.disabled = !a.disponible;

                        var equipo = ((a.marca || '') + ' ' + (a.modelo || '')).trim();

                        var placa = a.codigo_placa ?
                            ' | Placa: ' + a.codigo_placa :
                            '';

                        var texto =
                            (a.codigo_inventario || 'Sin código') +
                            placa +
                            ' | ' + (a.descripcion || 'Sin descripción') +
                            ' | ' + equipo +
                            ' | ' + (a.sede || 'Sin sede') +
                            ' | ' + (a.disponible ? 'DISPONIBLE' : 'YA ASIGNADO');

                        boton.textContent = texto;
                        boton.title = texto;

                        boton.addEventListener('click', function() {

                            if (!a.disponible) return;

                            mostrarActivo(a);

                            // Mostrar el activo elegido y cerrar la lista de resultados.
                            buscar.value = (a.codigo_inventario || 'Activo seleccionado') +
                                ' | ' + (a.descripcion || 'Sin descripción');
                            resultados.style.display = 'none';
                            resultados.innerHTML = '';
                            estado.textContent = 'Activo seleccionado correctamente: ' +
                                (a.codigo_inventario || 'Sin código') +
                                '. Puedes continuar con el registro de la asignación.';
                        });

                        resultados.appendChild(boton);
                    });
                    resultados.style.display = lista.length ? 'block' : 'none';
                    if (lista.length) {
                        var disponibles = lista.filter(function(a) {
                            return a.disponible;
                        }).length;
                        estado.textContent = 'Se encontraron ' + lista.length + ' resultados (' + disponibles + ' disponibles). Selecciona un dispositivo disponible. Los que tienen una asignación activa o pendiente aparecen deshabilitados.';
                    } else {
                        estado.textContent = 'No se encontraron coincidencias. Prueba con el código, placa, serial o una palabra de la descripción.';
                    }
                })
                .catch(function() {
                    if (numero !== consultaActual) return;
                    resultados.style.display = 'none';
                    estado.textContent = 'Error al buscar activos. Actualiza la página e inténtalo de nuevo.';
                });
        }
        buscar.addEventListener('input', function() {
            clearTimeout(timer);
            consultaActual++;
            limpiarSeleccion();
            timer = setTimeout(consultar, 300);
        });
        tipo.addEventListener('change', cambiarTipo);
        document.getElementById('agregarOficina').addEventListener('click', function() {
            var div = document.createElement('div');
            div.className = 'oficina-item row';
            div.style.cssText = 'border:1px solid #eee;padding:12px;margin:8px 0;border-radius:4px';
            div.innerHTML = '<div class="col-md-6 form-group"><label>Ubicación / espacio</label><input class="form-control" name="ubicacion_espacio[]" placeholder="Ej. Oficina de sistemas, cuarto técnico"></div><div class="col-md-5 form-group"><label>Área o departamento</label><input class="form-control" name="area_departamento[]" placeholder="Ej. Contabilidad, Recepción, Gerencia"></div><div class="col-md-1"><button type="button" class="btn btn-danger btn-sm quitar-oficina" style="margin-top:25px" title="Quitar oficina"><i class="fa fa-trash"></i></button></div>';
            document.getElementById('listaOficinas').appendChild(div);
        });
        document.getElementById('listaOficinas').addEventListener('click', function(e) {
            var b = e.target.closest('.quitar-oficina');
            if (b) b.closest('.oficina-item').remove();
        });
        document.getElementById('formAsignacion').addEventListener('submit', function(e) {
            if (!activoId.value) {
                e.preventDefault();
                alert('Busca y selecciona un activo disponible antes de guardar.');
            }
        });
        cambiarTipo();
    })();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>