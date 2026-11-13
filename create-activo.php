<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/identificadores_activos.php';

if (empty($_SESSION['alogin']) || empty($_SESSION['uid'])) {
  header('Location: index.php');
  exit;
}

$categorias = array(
  'COMPUTO' => 'Equipos de cómputo',
  'IMPRESION' => 'Dispositivos de impresión',
  'PERIFERICO' => 'Periféricos',
  'TELEFONIA' => 'Telefonía',
  'SERVIDOR' => 'Servidores',
  'INFRAESTRUCTURA' => 'Infraestructura tecnológica'
);
$estados = array(
  'OPERATIVO_EN_USO' => 'Operativo / En uso',
  'EN_STOCK' => 'En stock',
  'EN_REPARACION' => 'En reparación',
  'REGULAR' => 'Regular',
  'DADO_DE_BAJA_OBSOLETO' => 'Dado de baja / Obsoleto'
);

$msg = '';
$error = '';
$codigoGenerado = '';
$sedes = array();
try {
  $sedesStmt = $dbh->query("SELECT id, nombre FROM sedes WHERE estado = 'ACTIVA' ORDER BY nombre");
  $sedes = $sedesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log('No se pudieron cargar las sedes: ' . $e->getMessage());
  $error = 'No fue posible consultar las sedes. Verifica la estructura de la base de datos.';
}

if (empty($_SESSION['csrf_activo'])) {
  $_SESSION['csrf_activo'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_activo'])) {
  $csrf = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
  $categoriaCodigo = isset($_POST['categoria']) ? strtoupper(trim((string) $_POST['categoria'])) : '';

  if (!hash_equals($_SESSION['csrf_activo'], $csrf)) {
    $error = 'La sesión del formulario venció. Actualiza la página e inténtalo nuevamente.';
  } elseif (!isset($categorias[$categoriaCodigo])) {
    $error = 'Selecciona un tipo de activo válido.';
  } else {
    $sedeId = isset($_POST['sede_id']) ? (int) $_POST['sede_id'] : 0;
    $nombreUbicacion = trim(isset($_POST['ubicacion_nombre']) ? (string) $_POST['ubicacion_nombre'] : '');
    $tipoUbicacion = trim(isset($_POST['tipo_ubicacion']) ? (string) $_POST['tipo_ubicacion'] : '');
    $piso = trim(isset($_POST['piso']) ? (string) $_POST['piso'] : '');
    $area = trim(isset($_POST['area_departamento']) ? (string) $_POST['area_departamento'] : '');
    $detalleUbicacion = trim(isset($_POST['detalle_ubicacion']) ? (string) $_POST['detalle_ubicacion'] : '');
    $estado = isset($_POST['estado']) ? (string) $_POST['estado'] : 'EN_STOCK';

    if ($sedeId < 1 || $nombreUbicacion === '') {
      $error = 'Selecciona la sede e indica la ubicación del activo.';
    } elseif (!isset($estados[$estado])) {
      $error = 'Selecciona un estado válido.';
    } else {
      try {
        $dbh->beginTransaction();

        $sedeCheck = $dbh->prepare("SELECT id FROM sedes WHERE id = :id AND estado = 'ACTIVA' LIMIT 1");
        $sedeCheck->execute(array(':id' => $sedeId));
        if (!$sedeCheck->fetchColumn()) {
          throw new RuntimeException('La sede seleccionada no está disponible.');
        }

        // Reutiliza una ubicación coincidente; si no existe, la crea para mantener normalizada la relación sede-ubicación.
        $ubicacionCheck = $dbh->prepare("SELECT id FROM ubicaciones WHERE sede_id = :sede_id AND nombre = :nombre AND COALESCE(piso, '') = :piso AND COALESCE(area_departamento, '') = :area AND estado = 'ACTIVA' LIMIT 1");
        $ubicacionCheck->execute(array(
          ':sede_id' => $sedeId,
          ':nombre' => $nombreUbicacion,
          ':piso' => $piso,
          ':area' => $area
        ));
        $ubicacionId = (int) $ubicacionCheck->fetchColumn();
        if ($ubicacionId < 1) {
          $ubicacionInsert = $dbh->prepare("INSERT INTO ubicaciones (sede_id, tipo_ubicacion, nombre, piso, area_departamento, detalle) VALUES (:sede_id, :tipo, :nombre, :piso, :area, :detalle)");
          $ubicacionInsert->execute(array(
            ':sede_id' => $sedeId,
            ':tipo' => ($tipoUbicacion !== '' ? $tipoUbicacion : null),
            ':nombre' => $nombreUbicacion,
            ':piso' => ($piso !== '' ? $piso : null),
            ':area' => ($area !== '' ? $area : null),
            ':detalle' => ($detalleUbicacion !== '' ? $detalleUbicacion : null)
          ));
          $ubicacionId = (int) $dbh->lastInsertId();
        }

        $catStmt = $dbh->prepare("SELECT id FROM categorias_activos WHERE codigo = :codigo AND estado = 'ACTIVA' LIMIT 1");
        $catStmt->execute(array(':codigo' => $categoriaCodigo));
        $categoriaId = (int) $catStmt->fetchColumn();
        if ($categoriaId < 1) {
          throw new RuntimeException('La categoría seleccionada no existe en el catálogo de activos.');
        }

        $placa = trim(isset($_POST['codigo_placa']) ? (string) $_POST['codigo_placa'] : '');
        $marca = trim(isset($_POST['marca']) ? (string) $_POST['marca'] : '');
        $modelo = trim(isset($_POST['modelo']) ? (string) $_POST['modelo'] : '');
        $serial = trim(isset($_POST['numero_serial']) ? (string) $_POST['numero_serial'] : '');
        if (invNormalizarSerial($serial) === null) {
          $serial = '';
        }
        $mac = in_array($categoriaCodigo, array('COMPUTO', 'IMPRESION', 'INFRAESTRUCTURA'), true) ? trim((string)($_POST['mac'] ?? '')) : '';
        if ($mac !== '' && invNormalizarMac($mac) === null) {
          $mac = '';
        }
        invValidarIdentificadores($dbh, $serial, $mac);
        $descripcion = trim(isset($_POST['descripcion']) ? (string) $_POST['descripcion'] : '');
        $codigoGenerado = 'PENDIENTE-' . strtoupper(bin2hex(random_bytes(8)));

        $reparada = (($_POST['reparada'] ?? 'NO') === 'SI') ? 'SI' : 'NO';
        $descripcionReparacion = trim((string) ($_POST['descripcion_reparacion'] ?? ''));
        if ($reparada === 'SI' && $descripcionReparacion === '') {
          throw new RuntimeException('Describe la causa de la reparación o el trabajo realizado.');
        }

        $activoInsert = $dbh->prepare("
    INSERT INTO activos (
        categoria_id,
        ubicacion_id,
        codigo_placa,
        codigo_inventario,
        descripcion,
        marca,
        modelo,
        numero_serial,
        estado,
        observaciones,
        reparada,
        costo_reparacion,
        fecha_reparacion,
        fecha_mantenimiento,
        descripcion_reparacion,
        creado_por
    ) VALUES (
        :categoria_id,
        :ubicacion_id,
        :placa,
        :codigo,
        :descripcion,
        :marca,
        :modelo,
        :serial,
        :estado,
        :observaciones,
        :reparada,
        :costo_reparacion,
        :fecha_reparacion,
        :fecha_mantenimiento,
        :descripcion_reparacion,
        :creado_por
    )
");

        $activoInsert->execute(array(
          ':categoria_id' => $categoriaId,
          ':ubicacion_id' => $ubicacionId,
          ':placa' => ($placa !== '' ? $placa : null),
          ':codigo' => $codigoGenerado,
          ':descripcion' => ($descripcion !== '' ? $descripcion : null),
          ':marca' => ($marca !== '' ? $marca : null),
          ':modelo' => ($modelo !== '' ? $modelo : null),
          ':serial' => ($serial !== '' ? $serial : null),
          ':estado' => $estado,
          ':observaciones' => (
            trim((string)($_POST['observaciones'] ?? '')) !== ''
            ? trim((string)$_POST['observaciones'])
            : null
          ),
          ':reparada' => $reparada,
          ':costo_reparacion' => (($_POST['costo_reparacion'] ?? '') !== '' ? (float) $_POST['costo_reparacion'] : null),
          ':fecha_reparacion' => (($_POST['fecha_reparacion'] ?? '') !== '' ? $_POST['fecha_reparacion'] : null),
          ':fecha_mantenimiento' => (($_POST['fecha_mantenimiento'] ?? '') !== '' ? $_POST['fecha_mantenimiento'] : null),
          ':descripcion_reparacion' => ($descripcionReparacion !== '' ? $descripcionReparacion : null),
          ':creado_por' => (int) $_SESSION['uid']
        ));
        $activoId = (int) $dbh->lastInsertId();
        $codigoGenerado = 'SER-INV-' . str_pad((string) $activoId, 6, '0', STR_PAD_LEFT);
        $actualizarCodigo = $dbh->prepare("UPDATE activos SET codigo_inventario = :codigo WHERE id = :id");
        $actualizarCodigo->execute(array(':codigo' => $codigoGenerado, ':id' => $activoId));

        if ($categoriaCodigo === 'COMPUTO') {
          $stmt = $dbh->prepare("INSERT INTO equipos_computo (activo_id, tipo_equipo, capacidad_disco, tipo_disco, capacidad_ram, procesador, direccion_ip, mac, version_so, descripcion_equipo) VALUES (:activo_id, :tipo_equipo, :capacidad_disco, :tipo_disco, :ram, :procesador, :ip, :mac, :so, :descripcion)");
          $stmt->execute(array(
            ':activo_id' => $activoId,
            ':tipo_equipo' => $_POST['tipo_equipo'],
            ':capacidad_disco' => trim((string) $_POST['capacidad_disco']),
            ':tipo_disco' => trim((string) $_POST['tipo_disco']),
            ':ram' => trim((string) $_POST['capacidad_ram']),
            ':procesador' => trim((string) $_POST['procesador']),
            ':ip' => trim((string) $_POST['direccion_ip']),
            ':mac' => ($mac !== '' ? $mac : null),
            ':so' => trim((string) $_POST['version_so']),
            ':descripcion' => trim((string) $_POST['descripcion_equipo'])
          ));
        } elseif ($categoriaCodigo === 'IMPRESION') {
          $stmt = $dbh->prepare("INSERT INTO dispositivos_impresion (activo_id, tipo_impresora, direccion_ip, mac, fecha_ultimo_cambio_suministro, tipo_suministro, observaciones_suministro) VALUES (:activo_id, :tipo, :ip, :mac, :fecha, :suministro, :observaciones)");
          $stmt->execute(array(
            ':activo_id' => $activoId,
            ':tipo' => $_POST['tipo_impresora'],
            ':ip' => trim((string) $_POST['direccion_ip']),
            ':mac' => ($mac !== '' ? $mac : null),
            ':fecha' => (($_POST['fecha_ultimo_cambio_suministro'] ?? '') !== '' ? $_POST['fecha_ultimo_cambio_suministro'] : null),
            ':suministro' => trim((string) $_POST['tipo_suministro']),
            ':observaciones' => trim((string) $_POST['observaciones_suministro'])
          ));
        } elseif ($categoriaCodigo === 'PERIFERICO') {
          $stmt = $dbh->prepare("INSERT INTO perifericos (activo_id, tipo_periferico, descripcion_periferico) VALUES (:activo_id, :tipo, :descripcion)");
          $stmt->execute(array(':activo_id' => $activoId, ':tipo' => $_POST['tipo_periferico'], ':descripcion' => trim((string) $_POST['descripcion_periferico'])));
        } elseif ($categoriaCodigo === 'TELEFONIA') {
          $stmt = $dbh->prepare("INSERT INTO telefonia (activo_id, numero_extension, proveedor, tipo_equipo, descripcion_telefonia) VALUES (:activo_id, :numero, :proveedor, :tipo, :descripcion)");
          $stmt->execute(array(
            ':activo_id' => $activoId,
            ':numero' => trim((string) $_POST['numero_extension']),
            ':proveedor' => trim((string) $_POST['proveedor']),
            ':tipo' => $_POST['tipo_telefonia'],
            ':descripcion' => trim((string) $_POST['descripcion_telefonia'])
          ));
        } elseif ($categoriaCodigo === 'SERVIDOR') {
          $stmt = $dbh->prepare("INSERT INTO servidores (activo_id, hostname, estado_equipo, piso, rack_gabinete, direccion_ip, procesador, memoria_ram, sistema_operativo, version_so, aplicaciones_roles, descripcion_servidor) VALUES (:activo_id, :hostname, :estado_equipo, :piso, :rack, :ip, :procesador, :ram, :so, :version, :roles, :descripcion)");
          $stmt->execute(array(
            ':activo_id' => $activoId,
            ':hostname' => trim((string) $_POST['hostname']),
            ':estado_equipo' => $estados[$estado],
            ':piso' => ($piso !== '' ? $piso : null),
            ':rack' => trim((string) $_POST['rack_gabinete']),
            ':ip' => trim((string) $_POST['direccion_ip']),
            ':procesador' => trim((string) $_POST['procesador']),
            ':ram' => trim((string) $_POST['memoria_ram']),
            ':so' => trim((string) $_POST['sistema_operativo']),
            ':version' => trim((string) $_POST['version_so']),
            ':roles' => trim((string) $_POST['aplicaciones_roles']),
            ':descripcion' => trim((string) $_POST['descripcion_servidor'])
          ));
          $servidorId = (int) $dbh->lastInsertId();
          $tiposDisco = isset($_POST['disco_tipo']) && is_array($_POST['disco_tipo']) ? $_POST['disco_tipo'] : array();
          $capacidades = isset($_POST['disco_capacidad']) && is_array($_POST['disco_capacidad']) ? $_POST['disco_capacidad'] : array();
          $modelos = isset($_POST['disco_modelo']) && is_array($_POST['disco_modelo']) ? $_POST['disco_modelo'] : array();
          $seriales = isset($_POST['disco_serial']) && is_array($_POST['disco_serial']) ? $_POST['disco_serial'] : array();
          $raids = isset($_POST['disco_raid']) && is_array($_POST['disco_raid']) ? $_POST['disco_raid'] : array();
          $discoStmt = $dbh->prepare("INSERT INTO servidor_discos (servidor_id, numero_disco, tipo_disco, capacidad, modelo, numero_serial, configuracion_raid) VALUES (:servidor_id, :numero, :tipo, :capacidad, :modelo, :serial, :raid)");
          $numeroDisco = 0;
          foreach ($capacidades as $i => $capacidad) {
            $capacidad = trim((string) $capacidad);
            if ($capacidad === '') {
              continue;
            }
            $numeroDisco++;
            $discoStmt->execute(array(
              ':servidor_id' => $servidorId,
              ':numero' => $numeroDisco,
              ':tipo' => isset($tiposDisco[$i]) && $tiposDisco[$i] !== '' ? $tiposDisco[$i] : null,
              ':capacidad' => $capacidad,
              ':modelo' => isset($modelos[$i]) && $modelos[$i] !== '' ? $modelos[$i] : null,
              ':serial' => isset($seriales[$i]) && $seriales[$i] !== '' ? $seriales[$i] : null,
              ':raid' => isset($raids[$i]) && $raids[$i] !== '' ? $raids[$i] : null
            ));
          }
          if ($numeroDisco === 0) {
            throw new RuntimeException('Registra al menos un disco y su capacidad para el servidor.');
          }
        } elseif ($categoriaCodigo === 'INFRAESTRUCTURA') {
          $stmt = $dbh->prepare("INSERT INTO infraestructura (activo_id, tipo_dispositivo, cantidad_puertos, direccion_ip, mac, piso, descripcion_infraestructura) VALUES (:activo_id, :tipo, :puertos, :ip, :mac, :piso, :descripcion)");
          $puertos = trim((string) $_POST['cantidad_puertos']);
          $stmt->execute(array(
            ':activo_id' => $activoId,
            ':tipo' => $_POST['tipo_dispositivo'],
            ':puertos' => ($puertos !== '' ? (int) $puertos : null),
            ':ip' => trim((string) $_POST['direccion_ip']),
            ':mac' => ($mac !== '' ? $mac : null),
            ':piso' => ($piso !== '' ? $piso : null),
            ':descripcion' => trim((string) $_POST['descripcion_infraestructura'])
          ));
        }

        invSincronizarIdentificadores($dbh, $activoId, $serial, $mac);
        $dbh->commit();
        $msg = 'Activo registrado correctamente. Código de inventario: ' . $codigoGenerado;
        $_SESSION['csrf_activo'] = bin2hex(random_bytes(32));
      } catch (Throwable $e) {
        if ($dbh->inTransaction()) {
          $dbh->rollBack();
        }
        error_log('Error al registrar activo: ' . $e->getMessage());
        $error = ($e instanceof RuntimeException) ? $e->getMessage() : 'No fue posible registrar el activo. Verifica los datos e inténtalo nuevamente.';
      }
    }
  }
}
?>
<?php include __DIR__ . '/includes/topbar.php'; ?>
<div class="content-wrapper">
  <div class="content-container">
    <?php include __DIR__ . '/includes/leftbar.php'; ?>
    <div class="main-page">
      <div class="container-fluid">
        <div class="row page-title-div">
          <div class="col-md-12">
            <h2 class="title">Registrar activo tecnológico</h2>
          </div>
        </div>
        <div class="row breadcrumb-div">
          <div class="col-md-12">
            <ul class="breadcrumb">
              <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
              <li> Gestión de activos</li>
              <li class="active"> Registrar activo</li>
            </ul>
          </div>
        </div>
      </div>
      <section class="section">
        <div class="container-fluid">
          <div class="row">
            <div class="col-md-10 col-md-offset-1">
              <div class="panel">
                <div class="panel-heading">
                  <div class="panel-title">
                    <h5>Nuevo registro de inventario</h5>
                  </div>
                </div>
                <div class="panel-body">
                  <?php if ($msg !== ''): ?><div class="alert alert-success" role="alert"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                  <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                  <form method="post" id="formActivo" autocomplete="off">


                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_activo'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="form-group">
                      <label for="categoria">Tipo de activo <span class="text-danger">*</span></label>
                      <select class="form-control" id="categoria" name="categoria" required>
                        <option value="">Seleccione un tipo de activo</option>
                        <?php foreach ($categorias as $codigo => $nombre): ?><option value="<?php echo htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
                      </select>
                    </div>

                    <div id="formularioActivo" style="display:none;">
                      <!-- IMPORTACIÓN DE ARCHIVO EXCEL -->
                      <div class="panel panel-default" id="panelImportacionExcel"
                        style="border-left:4px solid #337ab7; margin-bottom:20px;">

                        <div class="panel-body">

                          <h4 style="margin-top:0;">
                            <i class="fa fa-file-excel-o"></i>
                            Importar información desde Excel
                          </h4>

                          <p class="text-muted">
                            Seleccione un archivo Excel para rellenar automáticamente
                            los campos del formulario de registro de activos.
                          </p>

                          <input
                            type="file"
                            id="archivoExcel"
                            accept=".xlsx,.xls"
                            style="display:none;">

                          <button
                            type="button"
                            id="btnImportarExcel"
                            class="btn btn-success">
                            <i class="fa fa-upload"></i>
                            Importar desde Excel
                          </button>

                          <button
                            type="button"
                            id="btnLimpiarExcel"
                            class="btn btn-default">
                            <i class="fa fa-eraser"></i>
                            Limpiar importación
                          </button>

                          <div id="resultadoImportacion"
                            style="margin-top:15px;"
                            aria-live="polite">
                          </div>

                          <small class="text-muted">
                            Se utilizará la primera hoja del archivo y la primera fila
                            como encabezados. Los datos se tomarán de la segunda fila.
                            Los encabezados no reconocidos se incorporarán a observaciones.
                          </small>

                        </div>
                      </div>
                      <!-- FIN IMPORTACIÓN DE EXCEL -->

                      <hr>
                      <h4>Información general del activo</h4>
                      <div class="row">
                        <div class="col-md-6 form-group"><label for="codigo_placa">Número de placa</label><input class="form-control" id="codigo_placa" name="codigo_placa" maxlength="60"></div>
                        <div class="col-md-6 form-group"><label>Código de inventario</label><input class="form-control" value="Se genera automáticamente al guardar" readonly></div>
                        <div class="col-md-6 form-group"><label for="marca">Marca</label><input class="form-control" id="marca" name="marca" maxlength="100"></div>
                        <div class="col-md-6 form-group"><label for="modelo">Modelo</label><input class="form-control" id="modelo" name="modelo" maxlength="120"></div>
                        <div class="col-md-6 form-group"><label for="numero_serial">Número de serial</label><input class="form-control" id="numero_serial" name="numero_serial" maxlength="150"></div>
                        <div class="col-md-6 form-group"><label for="estado">Estado <span class="text-danger">*</span></label><select class="form-control" id="estado" name="estado" required><?php foreach ($estados as $codigo => $nombre): ?><option value="<?php echo htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
                      </div>
                      <h4>Ubicación del activo</h4>
                      <div class="row">
                        <div class="col-md-6 form-group"><label for="sede_id">Sede <span class="text-danger">*</span></label><select class="form-control" id="sede_id" name="sede_id" required>
                            <option value="">Seleccione sede</option><?php foreach ($sedes as $sede): ?><option value="<?php echo (int) $sede['id']; ?>"><?php echo htmlspecialchars($sede['nombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?>
                          </select></div>
                        <div class="col-md-6 form-group"><label for="tipo_ubicacion">Tipo de ubicación</label><select class="form-control" id="tipo_ubicacion" name="tipo_ubicacion">
                            <option value="">Seleccione</option>
                            <option>Cuarto técnico</option>
                            <option>Oficina</option>
                            <option>Área común</option>
                            <option>Centro de datos</option>
                            <option>Almacén</option>
                            <option>Otro</option>
                          </select></div>
                        <div class="col-md-6 form-group"><label for="ubicacion_nombre">Ubicación / espacio <span class="text-danger">*</span></label><input class="form-control" id="ubicacion_nombre" name="ubicacion_nombre" placeholder="Ej. Oficina de sistemas, cuarto técnico" required maxlength="150"></div>
                        <div class="col-md-6 form-group"><label for="piso">Piso</label><input class="form-control" id="piso" name="piso" maxlength="30"></div>
                        <div class="col-md-6 form-group"><label for="area_departamento">Área o departamento</label><input class="form-control" id="area_departamento" name="area_departamento" placeholder="Ej. Contabilidad, Recepción, Gerencia" maxlength="120"></div>
                        <div class="col-md-6 form-group"><label for="detalle_ubicacion">Detalle adicional</label><input class="form-control" id="detalle_ubicacion" name="detalle_ubicacion" maxlength="255"></div>
                      </div>
                      <div class="form-group"><label for="descripcion">Descripción general / observaciones</label><textarea class="form-control" id="descripcion" name="descripcion" rows="2"></textarea></div>

                      <div class="form-group">
                        <label for="observaciones">
                          Observaciones adicionales
                        </label>

                        <textarea
                          class="form-control"
                          id="observaciones"
                          name="observaciones"
                          rows="4"
                          placeholder="Aquí aparecerán los datos adicionales importados desde Excel..."></textarea>

                        <small class="text-muted">
                          Los encabezados adicionales del Excel que no correspondan
                          a campos del formulario se agregarán automáticamente aquí.
                        </small>
                      </div>

                      <hr>
                      <h4>Reparación y mantenimiento</h4>
                      <div class="row">
                        <div class="col-md-6 form-group"><label>¿Reparada?</label><select class="form-control" name="reparada" id="reparada">
                            <option value="NO" selected>No</option>
                            <option value="SI">Sí</option>
                          </select></div>
                        <div class="col-md-6 form-group"><label>Costo de reparación <small>(opcional)</small></label><input class="form-control" type="number" name="costo_reparacion" min="0" step="0.01" placeholder="Dejar vacío si la reparación la realizó TI sin costo"></div>
                        <div class="col-md-6 form-group"><label>Fecha de reparación</label><input class="form-control" type="date" name="fecha_reparacion"></div>
                        <div class="col-md-6 form-group"><label>Fecha de mantenimiento</label><input class="form-control" type="date" name="fecha_mantenimiento"></div>
                        <div class="col-md-12 form-group"><label>Descripción / causa de la reparación o trabajo realizado <span class="text-danger" id="reparacionObligatoria" style="display:none">*</span></label><textarea class="form-control" name="descripcion_reparacion" id="descripcion_reparacion" rows="3" placeholder="Indica la falla que originó la reparación o el trabajo realizado por TI o un proveedor"></textarea></div>
                      </div>

                      <fieldset class="category-fields" data-category="COMPUTO" disabled style="display:none;">
                        <hr>
                        <h4>Formulario: equipos de cómputo</h4>
                        <div class="row">
                          <div class="col-md-6 form-group"><label>Tipo de equipo *</label><select class="form-control" name="tipo_equipo" required>
                              <option value="">Seleccione</option>
                              <option value="ESCRITORIO">Escritorio</option>
                              <option value="PORTATIL">Portátil</option>
                            </select></div>
                          <div class="col-md-6 form-group"><label>Capacidad de disco</label><input class="form-control" name="capacidad_disco" placeholder="Ej. 512 GB"></div>
                          <div class="col-md-6 form-group"><label>Tipo de disco</label><input class="form-control" name="tipo_disco" placeholder="Ej. SSD, HDD, NVMe"></div>
                          <div class="col-md-6 form-group"><label>Capacidad RAM</label><input class="form-control" name="capacidad_ram" placeholder="Ej. 16 GB"></div>
                          <div class="col-md-6 form-group"><label>Procesador</label><input class="form-control" name="procesador"></div>
                          <div class="col-md-6 form-group"><label>Dirección IP</label><input class="form-control" name="direccion_ip"></div>
                          <div class="col-md-6 form-group"><label>MAC</label><input class="form-control" name="mac"></div>
                          <div class="col-md-6 form-group"><label>Versión del sistema operativo</label><input class="form-control" name="version_so"></div>
                          <div class="col-md-12 form-group"><label>Descripción del equipo</label><textarea class="form-control" name="descripcion_equipo" rows="2"></textarea></div>
                        </div>
                      </fieldset>

                      <fieldset class="category-fields" data-category="IMPRESION" disabled style="display:none;">
                        <hr>
                        <h4>Formulario: dispositivos de impresión</h4>
                        <div class="row">
                          <div class="col-md-6 form-group"><label>Tipo de impresora *</label><select class="form-control" name="tipo_impresora" required>
                              <option value="">Seleccione</option>
                              <option value="LASER">Láser</option>
                              <option value="INYECCION_TINTA">Inyección de tinta</option>
                              <option value="MATRICIAL">Matricial</option>
                              <option value="TERMICA">Térmica</option>
                              <option value="MULTIFUNCIONAL">Multifuncional</option>
                            </select></div>
                          <div class="col-md-6 form-group"><label>Dirección IP</label><input class="form-control" name="direccion_ip"></div>
                          <div class="col-md-6 form-group"><label>MAC</label><input class="form-control" name="mac"></div>
                          <div class="col-md-6 form-group"><label>Último cambio de suministro</label><input class="form-control" type="date" name="fecha_ultimo_cambio_suministro"></div>
                          <div class="col-md-6 form-group"><label>Tipo de suministro</label><input class="form-control" name="tipo_suministro" placeholder="Tóner, tinta, cinta, etc."></div>
                          <div class="col-md-12 form-group"><label>Observaciones de suministro</label><textarea class="form-control" name="observaciones_suministro" rows="2"></textarea></div>
                        </div>
                      </fieldset>

                      <fieldset class="category-fields" data-category="PERIFERICO" disabled style="display:none;">
                        <hr>
                        <h4>Formulario: periféricos</h4>
                        <div class="row">
                          <div class="col-md-6 form-group"><label>Tipo de periférico *</label><select class="form-control" name="tipo_periferico" required>
                              <option value="">Seleccione</option>
                              <option>Monitor</option>
                              <option>Teclado</option>
                              <option>Mouse</option>
                              <option>Auriculares / Diadema</option>
                              <option>Webcam</option>
                              <option>Disco duro externo</option>
                              <option>Otro</option>
                            </select></div>
                          <div class="col-md-12 form-group"><label>Descripción del periférico</label><textarea class="form-control" name="descripcion_periferico" rows="2"></textarea></div>
                        </div>
                      </fieldset>

                      <fieldset class="category-fields" data-category="TELEFONIA" disabled style="display:none;">
                        <hr>
                        <h4>Formulario: telefonía</h4>
                        <div class="row">
                          <div class="col-md-6 form-group"><label>Número telefónico / extensión</label><input class="form-control" name="numero_extension" maxlength="60"></div>
                          <div class="col-md-6 form-group"><label>Proveedor</label><input class="form-control" name="proveedor" maxlength="120"></div>
                          <div class="col-md-6 form-group"><label>Tipo de equipo *</label><select class="form-control" name="tipo_telefonia" required>
                              <option value="">Seleccione</option>
                              <option value="SMARTPHONE">Smartphone</option>
                              <option value="TELEFONO_ESCRITORIO">Teléfono de escritorio</option>
                              <option value="SIM_CARD">SIM Card</option>
                            </select></div>
                          <div class="col-md-12 form-group"><label>Descripción</label><textarea class="form-control" name="descripcion_telefonia" rows="2"></textarea></div>
                        </div>
                      </fieldset>

                      <fieldset class="category-fields" data-category="SERVIDOR" disabled style="display:none;">
                        <hr>
                        <h4>Formulario: servidores</h4>
                        <div class="row">
                          <div class="col-md-6 form-group"><label>Nombre del servidor / Hostname</label><input class="form-control" name="hostname" maxlength="120"></div>
                          <div class="col-md-6 form-group"><label>Rack / Gabinete</label><input class="form-control" name="rack_gabinete" maxlength="100"></div>
                          <div class="col-md-6 form-group"><label>Dirección IP</label><input class="form-control" name="direccion_ip"></div>
                          <div class="col-md-6 form-group"><label>Procesador</label><input class="form-control" name="procesador"></div>
                          <div class="col-md-6 form-group"><label>Memoria RAM</label><input class="form-control" name="memoria_ram"></div>
                          <div class="col-md-6 form-group"><label>Sistema operativo</label><input class="form-control" name="sistema_operativo"></div>
                          <div class="col-md-6 form-group"><label>Versión</label><input class="form-control" name="version_so"></div>
                          <div class="col-md-12 form-group"><label>Aplicaciones / roles principales</label><textarea class="form-control" name="aplicaciones_roles" rows="2"></textarea></div>
                          <div class="col-md-12 form-group"><label>Descripción del servidor</label><textarea class="form-control" name="descripcion_servidor" rows="2"></textarea></div>
                        </div>
                        <div class="clearfix">
                          <h4 class="pull-left">Almacenamiento: discos del servidor</h4><button type="button" class="btn btn-default btn-sm pull-right" id="agregarDisco"><i class="fa fa-plus"></i> Agregar disco</button>
                        </div>
                        <div id="listaDiscos">
                          <div class="row disco-item" style="border:1px solid #eee;padding:10px;margin:8px 0;">
                            <div class="col-md-3 form-group"><label>Tipo</label><input class="form-control" name="disco_tipo[]" placeholder="SSD / HDD"></div>
                            <div class="col-md-3 form-group"><label>Capacidad *</label><input class="form-control" name="disco_capacidad[]" placeholder="Ej. 2 TB" required></div>
                            <div class="col-md-2 form-group"><label>Modelo</label><input class="form-control" name="disco_modelo[]"></div>
                            <div class="col-md-2 form-group"><label>Serial</label><input class="form-control" name="disco_serial[]"></div>
                            <div class="col-md-2 form-group"><label>RAID</label><input class="form-control" name="disco_raid[]"></div>
                          </div>
                        </div>
                      </fieldset>

                      <fieldset class="category-fields" data-category="INFRAESTRUCTURA" disabled style="display:none;">
                        <hr>
                        <h4>Formulario: infraestructura tecnológica</h4>
                        <div class="row">
                          <div class="col-md-6 form-group"><label>Nombre / Tipo de dispositivo *</label><select class="form-control" name="tipo_dispositivo" required>
                              <option value="">Seleccione</option>
                              <option>Switch</option>
                              <option>Router</option>
                              <option>Access Point</option>
                              <option>Otro</option>
                            </select></div>
                          <div class="col-md-6 form-group"><label>Cantidad de puertos</label><input class="form-control" type="number" min="0" name="cantidad_puertos"></div>
                          <div class="col-md-6 form-group"><label>Dirección IP</label><input class="form-control" name="direccion_ip"></div>
                          <div class="col-md-6 form-group"><label>MAC</label><input class="form-control" name="mac"></div>
                          <div class="col-md-12 form-group"><label>Descripción</label><textarea class="form-control" name="descripcion_infraestructura" rows="2"></textarea></div>
                        </div>
                      </fieldset>

                      <div class="form-group" style="margin-top:20px;"><button type="submit" name="guardar_activo" class="btn btn-primary"><i class="fa fa-save"></i> Guardar activo</button> <button type="reset" class="btn btn-danger" id="limpiarActivo">Limpiar</button></div>
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
<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- Biblioteca para leer archivos Excel -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<!-- Código de importación de Excel -->
<script>
  (function() {

    'use strict';

    const archivo = document.getElementById('archivoExcel');
    const botonImportar = document.getElementById('btnImportarExcel');
    const botonLimpiar = document.getElementById('btnLimpiarExcel');
    const resultado = document.getElementById('resultadoImportacion');

    const formulario = document.getElementById('formActivo');
    const categoria = document.getElementById('categoria');

    const camposImportados = new Set();

    // Normaliza los encabezados para facilitar su comparación.
    function normalizar(valor) {

      return String(valor || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]/g, '');

    }

    // Relación entre encabezados de Excel y campos del formulario.
    const equivalencias = {

      'categoria': 'categoria',
      'tipoactivo': 'categoria',
      'categoriaactivo': 'categoria',

      'placa': 'codigo_placa',
      'numeroplaca': 'codigo_placa',
      'codigoplaca': 'codigo_placa',
      'etiqueta': 'codigo_placa',

      'marca': 'marca',
      'modelo': 'modelo',

      'serial': 'numero_serial',
      'numeroserial': 'numero_serial',
      'numerodeserial': 'numero_serial',
      'serialequipo': 'numero_serial',

      'estado': 'estado',

      'sede': 'sede_id',
      'nombresede': 'sede_id',
      'sedeid': 'sede_id',

      'tipoubicacion': 'tipo_ubicacion',
      'tipodeubicacion': 'tipo_ubicacion',

      'ubicacion': 'ubicacion_nombre',
      'ubicacionespacio': 'ubicacion_nombre',
      'espacio': 'ubicacion_nombre',
      'nombreubicacion': 'ubicacion_nombre',

      'piso': 'piso',

      'area': 'area_departamento',
      'departamento': 'area_departamento',
      'areadepartamento': 'area_departamento',

      'detalleubicacion': 'detalle_ubicacion',
      'detalledubicacion': 'detalle_ubicacion',

      'descripciongeneral': 'descripcion',
      'descripcionactivo': 'descripcion',

      'observaciones': 'observaciones',
      'observacionesadicionales': 'observaciones',
      'observacionesgenerales': 'observaciones',
      'observaciongeneral': 'observaciones',
      'observacion': 'observaciones',

      'reparada': 'reparada',
      'costoreparacion': 'costo_reparacion',
      'fechareparacion': 'fecha_reparacion',
      'fechamantenimiento': 'fecha_mantenimiento',
      'descripcionreparacion': 'descripcion_reparacion',

      'tipodisco': 'tipo_disco',
      'tipodedisco': 'tipo_disco',

      'capacidaddisco': 'capacidad_disco',
      'capacidaddedisco': 'capacidad_disco',

      'ram': 'capacidad_ram',
      'capacidadram': 'capacidad_ram',

      'procesador': 'procesador',
      'direccionip': 'direccion_ip',
      'ip': 'direccion_ip',
      'mac': 'mac',
      'direccionmac': 'mac',

      'versionso': 'version_so',
      'sistemaoperativo': 'sistema_operativo',
      'descripcionequipo': 'descripcion_equipo',

      'tipoimpresora': 'tipo_impresora',
      'fechaultimocambiosuministro': 'fecha_ultimo_cambio_suministro',
      'tiposuministro': 'tipo_suministro',
      'observacionessuministro': 'observaciones_suministro',

      'tipoperiferico': 'tipo_periferico',
      'descripcionperiferico': 'descripcion_periferico',

      'numeroextension': 'numero_extension',
      'extension': 'numero_extension',
      'proveedor': 'proveedor',
      'tipotelefonia': 'tipo_telefonia',
      'descripciontelefonia': 'descripcion_telefonia',

      'hostname': 'hostname',
      'rack': 'rack_gabinete',
      'rackgabinete': 'rack_gabinete',
      'memoriaram': 'memoria_ram',
      'aplicaciones': 'aplicaciones_roles',
      'roles': 'aplicaciones_roles',
      'aplicacionesroles': 'aplicaciones_roles',
      'descripcionservidor': 'descripcion_servidor',

      'tipodispositivo': 'tipo_dispositivo',
      'cantidadpuertos': 'cantidad_puertos',
      'descripcioninfraestructura': 'descripcion_infraestructura',

      'discotipo': 'disco_tipo[]',
      'discocapacidad': 'disco_capacidad[]',
      'discomodelo': 'disco_modelo[]',
      'discoserial': 'disco_serial[]',
      'disco': 'disco_capacidad[]',
      'raid': 'disco_raid[]',
      'configuracionraid': 'disco_raid[]'

    };

    // Identifica el campo correspondiente a un encabezado.
    function identificarCampo(encabezado, tipoActivo) {

      const clave = normalizar(encabezado);

      let campo = equivalencias[clave] || null;

      // RAM según la categoría.
      if (clave === 'memoriaram' || clave === 'ram') {

        if (tipoActivo === 'SERVIDOR') {
          campo = 'memoria_ram';
        } else if (tipoActivo === 'COMPUTO') {
          campo = 'capacidad_ram';
        }

      }

      // Discos: en cómputo se utilizan los campos simples;
      // en servidores se utilizan los campos de discos individuales.
      if (tipoActivo === 'COMPUTO') {

        if (clave === 'discotipo' || clave === 'tipodedisco') {
          campo = 'tipo_disco';
        }

        if (clave === 'discocapacidad' || clave === 'capacidaddedisco') {
          campo = 'capacidad_disco';
        }

      } else if (tipoActivo === 'SERVIDOR') {

        if (clave === 'discotipo') {
          campo = 'disco_tipo[]';
        }

        if (clave === 'discocapacidad') {
          campo = 'disco_capacidad[]';
        }

        if (clave === 'discomodelo') {
          campo = 'disco_modelo[]';
        }

        if (clave === 'discoserial') {
          campo = 'disco_serial[]';
        }

        if (clave === 'raid' || clave === 'configuracionraid') {
          campo = 'disco_raid[]';
        }

      }

      return campo;
    }

    // Convierte nombres de categorías a los códigos internos.
    function identificarCategoria(valor) {

      const texto = normalizar(valor);

      const categorias = {

        'computo': 'COMPUTO',
        'equiposdecomputo': 'COMPUTO',
        'computadora': 'COMPUTO',
        'computador': 'COMPUTO',
        'portatil': 'COMPUTO',
        'escritorio': 'COMPUTO',

        'impresion': 'IMPRESION',
        'impresora': 'IMPRESION',
        'dispositivosdeimpresion': 'IMPRESION',

        'periferico': 'PERIFERICO',
        'perifericos': 'PERIFERICO',

        'telefonia': 'TELEFONIA',
        'telefono': 'TELEFONIA',

        'servidor': 'SERVIDOR',
        'servidores': 'SERVIDOR',

        'infraestructura': 'INFRAESTRUCTURA',
        'infraestructuratecnologica': 'INFRAESTRUCTURA'

      };

      return categorias[texto] || String(valor).toUpperCase().trim();

    }

    // Convierte fechas a formato YYYY-MM-DD.
    function convertirFecha(valor) {

      if (!valor) return '';

      const texto = String(valor).trim();

      if (/^\d{4}-\d{2}-\d{2}$/.test(texto)) {
        return texto;
      }

      let coincidencia = texto.match(
        /^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/
      );

      if (coincidencia) {

        const dia = coincidencia[1].padStart(2, '0');
        const mes = coincidencia[2].padStart(2, '0');
        const anio = coincidencia[3];

        return anio + '-' + mes + '-' + dia;

      }

      return texto;

    }

    // Encuentra el campo correspondiente dentro del formulario.
    function obtenerCampo(nombre) {

      const campos = Array.from(
        formulario.querySelectorAll('[name]')
      );

      return campos.filter(function(campo) {

        const nombreCampo = campo.name.replace(/\[\]$/, '');

        return nombreCampo === nombre.replace(/\[\]$/, '') &&
          !campo.disabled &&
          campo.type !== 'hidden' &&
          campo.type !== 'submit' &&
          campo.type !== 'reset';

      });

    }

    // Asigna valores a inputs, textareas y select.
    function asignarValor(nombre, valor) {

      if (valor === null || valor === undefined || valor === '') {
        return false;
      }

      const campos = obtenerCampo(nombre);

      if (!campos.length) return false;

      // Si existen campos repetidos, utiliza el que corresponda
      // a la categoría activa.
      let campo = campos.find(function(elemento) {

        const fieldset = elemento.closest('.category-fields');

        return !fieldset ||
          fieldset.getAttribute('data-category') === categoria.value;

      });

      if (!campo) return false;

      if (campo.tagName === 'SELECT') {

        const valorNormalizado = normalizar(valor);

        const opcion = Array.from(campo.options).find(function(op) {

          return normalizar(op.value) === valorNormalizado ||
            normalizar(op.textContent) === valorNormalizado;

        });

        // Si no existe una opción equivalente, no modifica el SELECT.
        // El dato será enviado a observaciones por el proceso de importación.
        if (!opcion) return false;

        campo.value = opcion.value;

      } else {

        if (campo.type === 'date') {
          valor = convertirFecha(valor);
        }

        campo.value = String(valor).trim();

      }

      campo.setAttribute('data-importado-excel', '1');

      camposImportados.add(campo);

      campo.dispatchEvent(new Event('input', {
        bubbles: true
      }));
      campo.dispatchEvent(new Event('change', {
        bubbles: true
      }));

      return true;

    }

    // Mensajes de resultado.
    function mostrarMensaje(tipo, mensaje) {

      resultado.className = 'alert alert-' + tipo;
      resultado.textContent = mensaje;

    }

    // Inicia la lectura del Excel.
    botonImportar.addEventListener('click', function() {

      archivo.click();

    });

    archivo.addEventListener('change', function(evento) {

      const file = evento.target.files[0];

      if (!file) return;

      if (typeof XLSX === 'undefined') {

        mostrarMensaje(
          'danger',
          'No se pudo cargar la biblioteca para leer Excel.'
        );

        return;

      }

      const extension = file.name.split('.').pop().toLowerCase();

      if (!['xlsx', 'xls'].includes(extension)) {

        mostrarMensaje(
          'danger',
          'Seleccione un archivo Excel con extensión .xlsx o .xls.'
        );

        archivo.value = '';
        return;

      }

      const lector = new FileReader();

      lector.onload = function(e) {

        try {

          const libro = XLSX.read(e.target.result, {
            type: 'array',
            cellDates: true
          });

          const primeraHoja = libro.SheetNames[0];

          if (!primeraHoja) {
            throw new Error('El archivo no contiene hojas.');
          }

          const hoja = libro.Sheets[primeraHoja];

          const filas = XLSX.utils.sheet_to_json(hoja, {
            header: 1,
            defval: '',
            raw: false
          });

          if (filas.length < 2) {

            throw new Error(
              'El archivo debe contener encabezados y al menos una fila de datos.'
            );

          }

          const encabezados = filas[0];
          const datos = filas[1];

          let reconocidos = 0;
          let adicionales = [];

          // Primero se identifica la categoría.
          let categoriaExcel = '';

          encabezados.forEach(function(encabezado, indice) {

            const campo = identificarCampo(encabezado, '');

            if (campo === 'categoria') {
              categoriaExcel = datos[indice];
            }

          });

          // Si el Excel incluye categoría, se selecciona.
          if (categoriaExcel) {

            const codigo = identificarCategoria(categoriaExcel);

            asignarValor('categoria', codigo);

            categoria.dispatchEvent(
              new Event('change', {
                bubbles: true
              })
            );

          }

          // Se procesan todos los encabezados.
          encabezados.forEach(function(encabezado, indice) {

            if (
              encabezado === null ||
              String(encabezado).trim() === ''
            ) {
              return;
            }

            const valor = datos[indice];

            if (
              valor === null ||
              valor === undefined ||
              String(valor).trim() === ''
            ) {
              return;
            }

            const campo = identificarCampo(
              encabezado,
              categoria.value
            );

            if (!campo) {

              adicionales.push({
                encabezado: String(encabezado).trim(),
                valor: String(valor).trim()
              });

              return;

            }

            if (campo === 'categoria') return;

            if (asignarValor(campo, valor)) {

              reconocidos++;

            } else {

              adicionales.push({
                encabezado: String(encabezado).trim(),
                valor: String(valor).trim()
              });

            }

          });

          // Los campos adicionales se guardan en observaciones.
          if (adicionales.length > 0) {

            const observaciones = document.getElementById(
              'observaciones'
            );

            const contenido = adicionales.map(function(dato) {

              return dato.encabezado + ': ' + dato.valor;

            }).join('\n');

            const anterior = observaciones.value.trim();

            observaciones.value = anterior ?
              anterior + '\n' + contenido :
              contenido;

            observaciones.setAttribute(
              'data-importado-excel',
              '1'
            );

            camposImportados.add(observaciones);

          }

          const cantidadAdicionales = adicionales.length;

          mostrarMensaje(
            'success',
            'Excel importado correctamente. Campos reconocidos: ' +
            reconocidos +
            '. Datos adicionales enviados a observaciones: ' +
            cantidadAdicionales +
            '. Revise el formulario antes de guardar.'
          );

          // Permite volver a seleccionar el mismo archivo.
          archivo.value = '';

        } catch (error) {

          console.error('Error al importar Excel:', error);

          mostrarMensaje(
            'danger',
            'No fue posible importar el archivo: ' + error.message
          );

          archivo.value = '';

        }

      };

      lector.onerror = function() {

        mostrarMensaje(
          'danger',
          'No fue posible leer el archivo seleccionado.'
        );

      };

      lector.readAsArrayBuffer(file);

    });

    // Limpia exclusivamente los campos que fueron importados.
    botonLimpiar.addEventListener('click', function() {

      camposImportados.forEach(function(campo) {

        campo.value = '';

        campo.removeAttribute('data-importado-excel');

        campo.dispatchEvent(
          new Event('input', {
            bubbles: true
          })
        );

        campo.dispatchEvent(
          new Event('change', {
            bubbles: true
          })
        );

      });

      camposImportados.clear();

      resultado.textContent = '';
      resultado.className = '';

      if (categoria.value === '') {
        categoria.dispatchEvent(
          new Event('change', {
            bubbles: true
          })
        );
      }

    });

  })();
</script>

<script>
  (function() {
    var s = document.getElementById('reparada'),
      d = document.getElementById('descripcion_reparacion'),
      r = document.getElementById('reparacionObligatoria');

    function validar() {
      var si = s && s.value === 'SI';
      if (d) d.required = !!si;
      if (r) r.style.display = si ? 'inline' : 'none';
    }
    if (s) s.addEventListener('change', validar);
    validar();
  })();
</script>
<script>
  (function() {
    var selector = document.getElementById('categoria');
    var wrapper = document.getElementById('formularioActivo');
    var fieldsets = document.querySelectorAll('.category-fields');

    function actualizarFormulario() {
      var categoria = selector.value;
      wrapper.style.display = categoria ? 'block' : 'none';
      fieldsets.forEach(function(fieldset) {
        var activo = fieldset.getAttribute('data-category') === categoria;
        fieldset.style.display = activo ? 'block' : 'none';
        fieldset.disabled = !activo;
      });
    }
    selector.addEventListener('change', actualizarFormulario);
    document.getElementById('limpiarActivo').addEventListener('click', function() {
      window.setTimeout(function() {
        selector.value = '';
        actualizarFormulario();
      }, 0);
    });
    var addDisk = document.getElementById('agregarDisco');
    if (addDisk) {
      addDisk.addEventListener('click', function() {
        var template = document.querySelector('#listaDiscos .disco-item');
        var clone = template.cloneNode(true);
        clone.querySelectorAll('input').forEach(function(input) {
          input.value = '';
        });
        var remove = document.createElement('div');
        remove.className = 'col-md-12 text-right';
        remove.innerHTML = '<button type="button" class="btn btn-danger btn-xs quitar-disco">Quitar disco</button>';
        clone.appendChild(remove);
        clone.querySelector('.quitar-disco').addEventListener('click', function() {
          clone.remove();
        });
        document.getElementById('listaDiscos').appendChild(clone);
      });
    }
    actualizarFormulario();
  })();
</script>
<?php
?>