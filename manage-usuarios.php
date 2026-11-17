<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
sisatRequireRoles([1]);

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Activar o desactivar cuentas, excepto la cuenta que tiene abierta esta sesión.
if (isset($_GET['estado'], $_GET['id'])) {
    $id = (int) $_GET['id'];

    if ($id > 0 && $id !== (int) $_SESSION['uid']) {
        $nuevoEstado = $_GET['estado'] === 'ACTIVO' ? 'ACTIVO' : 'INACTIVO';
        $stmtEstado = $dbh->prepare('UPDATE usuarios_sistema SET estado = ?, fecha_actualizacion = NOW() WHERE id = ?');
        $stmtEstado->execute([$nuevoEstado, $id]);
    }

    header('Location: manage-usuarios.php');
    exit;
}

try {
    $sqlActual = "SELECT u.nombre_completo, u.usuario, u.estado, r.nombre AS rol
                  FROM usuarios_sistema u
                  LEFT JOIN roles r ON r.id = u.rol_id
                  WHERE u.id = ? LIMIT 1";
    $stmtActual = $dbh->prepare($sqlActual);
    $stmtActual->execute([(int) $_SESSION['uid']]);
    $usuarioActual = $stmtActual->fetch(PDO::FETCH_ASSOC);

    $sqlUsuarios = "SELECT u.id, u.nombre_completo, u.usuario, u.correo,
                           u.estado, u.ultimo_acceso, r.nombre AS rol
                    FROM usuarios_sistema u
                    LEFT JOIN roles r ON r.id = u.rol_id
                    ORDER BY u.id";
    $usuarios = $dbh->query($sqlUsuarios)->fetchAll(PDO::FETCH_ASSOC);

    $totalActivos = (int) $dbh->query("SELECT COUNT(*) FROM usuarios_sistema WHERE estado = 'ACTIVO'")->fetchColumn();
    $totalInactivos = (int) $dbh->query("SELECT COUNT(*) FROM usuarios_sistema WHERE estado = 'INACTIVO'")->fetchColumn();
} catch (PDOException $e) {
    error_log('Error al consultar usuarios: ' . $e->getMessage());
    http_response_code(500);
    exit('No fue posible consultar los usuarios del sistema.');
}

include __DIR__ . '/includes/topbar.php';
?>
<style>
    .users-current-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 17px 20px;
        margin: 15px 0 22px;
        border: 1px solid #dce9ec;
        border-left: 5px solid #167d8d;
        border-radius: 9px;
        background: #f3f8f9;
        box-shadow: 0 3px 12px rgba(15, 55, 65, .06);
    }
    .users-current-main { display: flex; align-items: center; gap: 13px; }
    .users-current-icon {
        width: 46px; height: 46px; min-width: 46px; border-radius: 50%;
        background: #0c4d58; color: #fff; display: flex;
        align-items: center; justify-content: center; font-size: 20px;
    }
    .users-current-label { color: #167d8d; text-transform: uppercase; font-size: 10px; font-weight: 800; letter-spacing: .9px; margin-bottom: 3px; }
    .users-current-name { color: #183f48; font-size: 15px; font-weight: 700; margin: 0 0 3px; }
    .users-current-detail { color: #64777d; font-size: 12px; margin: 0; }
    .users-current-role { background: #e1f1f2; color: #0c5965; border-radius: 18px; padding: 7px 12px; font-size: 11px; font-weight: 700; white-space: nowrap; }
    .users-summary { margin-bottom: 18px; }
    .users-summary .summary-box {
        background: #fff; border: 1px solid #e5ecef; border-radius: 8px;
        padding: 13px 16px; box-shadow: 0 2px 8px rgba(15,55,65,.04);
    }
    .users-summary .summary-number { font-size: 23px; font-weight: 700; color: #123f4a; }
    .users-summary .summary-label { color: #65777d; font-size: 11px; font-weight: 600; }
    .row-current-account { background: #f0f8f8 !important; }
    .badge-current-account {
        display: inline-block; padding: 5px 8px; border-radius: 12px;
        color: #0c5965; background: #dceff0; font-size: 10px; font-weight: 700;
        white-space: nowrap;
    }
    @media (max-width: 600px) {
        .users-current-card { align-items: flex-start; flex-direction: column; }
    }
</style>

<div class="content-wrapper">
    <div class="content-container">
        <?php include __DIR__ . '/includes/leftbar.php'; ?>
        <div class="main-page">
            <div class="container-fluid">
                <div class="row page-title-div">
                    <div class="col-sm-8">
                        <h2 class="title">Gestión de usuarios del sistema</h2>
                        <p>Administra las cuentas, perfiles y estados de acceso a SISAT.</p>
                    </div>
                    <br>
                    <div class="col-sm-4 text-right" style="padding-top: 8px;">
                        <a class="btn btn-primary" href="create-usuario.php"><i class="fa fa-user-plus"></i> Registrar usuario</a>
                    </div>
                </div>

                <?php if ($usuarioActual): ?>
                <div class="users-current-card">
                    <div class="users-current-main">
                        <div class="users-current-icon"><i class="fa fa-user-circle"></i></div>
                        <div>
                            <div class="users-current-label"><i class="fa fa-shield"></i> Cuenta con sesión iniciada</div>
                            <p class="users-current-name"><?php echo h($usuarioActual['nombre_completo']); ?></p>
                            <p class="users-current-detail">Usuario: <strong><?php echo h($usuarioActual['usuario']); ?></strong> · Estado: <strong><?php echo h($usuarioActual['estado']); ?></strong></p>
                        </div>
                    </div>
                    <span class="users-current-role"><i class="fa fa-id-badge"></i> <?php echo h($usuarioActual['rol'] ?: 'Perfil no asignado'); ?></span>
                </div>
                <?php endif; ?>

                <div class="row users-summary">
                    <div class="col-sm-3 col-xs-6">
                        <div class="summary-box">
                            <div class="summary-number"><?php echo $totalActivos; ?></div>
                            <div class="summary-label"><i class="fa fa-check-circle text-success"></i> Usuarios activos</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-xs-6">
                        <div class="summary-box">
                            <div class="summary-number"><?php echo $totalInactivos; ?></div>
                            <div class="summary-label"><i class="fa fa-ban text-muted"></i> Usuarios inactivos</div>
                        </div>
                    </div>
                </div>
            </div>

            <section class="section">
                <div class="container-fluid">
                    <div class="panel">
                        <div class="panel-heading">
                            <div class="panel-title"><h5>Usuarios registrados</h5></div>
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th>Nombre completo</th>
                                            <th>Usuario</th>
                                            <th>Correo</th>
                                            <th>Perfil</th>
                                            <th>Estado</th>
                                            <th>Último acceso</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (count($usuarios) > 0): ?>
                                        <?php foreach ($usuarios as $u): ?>
                                            <?php $esUsuarioActual = ((int) $u['id'] === (int) $_SESSION['uid']); ?>
                                            <tr class="<?php echo $esUsuarioActual ? 'row-current-account' : ''; ?>">
                                                <td>
                                                    <?php echo h($u['nombre_completo']); ?>
                                                    <?php if ($esUsuarioActual): ?>
                                                        <br><span class="badge-current-account"><i class="fa fa-user"></i> Sesión actual</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo h($u['usuario']); ?></td>
                                                <td><?php echo h($u['correo']); ?></td>
                                                <td><?php echo h($u['rol']); ?></td>
                                                <td>
                                                    <?php if ($u['estado'] === 'ACTIVO'): ?>
                                                        <span class="label label-success">ACTIVO</span>
                                                    <?php else: ?>
                                                        <span class="label label-default">INACTIVO</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo h($u['ultimo_acceso'] ?: 'Sin ingresos registrados'); ?></td>
                                                <td>
                                                    <a class="btn btn-xs btn-info" href="edit-usuario.php?id=<?php echo (int) $u['id']; ?>">
                                                        <i class="fa fa-pencil"></i> Editar
                                                    </a>
                                                    <?php if (!$esUsuarioActual): ?>
                                                        <a class="btn btn-xs btn-<?php echo $u['estado'] === 'ACTIVO' ? 'warning' : 'success'; ?>"
                                                           href="?id=<?php echo (int) $u['id']; ?>&estado=<?php echo $u['estado'] === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO'; ?>"
                                                           onclick="return confirm('¿Deseas cambiar el estado de esta cuenta?');">
                                                            <i class="fa fa-<?php echo $u['estado'] === 'ACTIVO' ? 'ban' : 'check'; ?>"></i>
                                                            <?php echo $u['estado'] === 'ACTIVO' ? 'Desactivar' : 'Activar'; ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted"><i class="fa fa-lock"></i> Cuenta protegida</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center">No hay usuarios registrados.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
