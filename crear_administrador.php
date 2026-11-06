<?php
require_once __DIR__ . '/includes/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$message = '';
$created = false;
try {
    $count = (int) $dbh->query('SELECT COUNT(*) FROM usuarios_sistema')->fetchColumn();
} catch (PDOException $e) {
    error_log('No se pudo verificar usuarios_sistema: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo consultar la tabla usuarios_sistema. Verifica que la estructura de la base de datos esté importada.');
}

if ($count > 0) {
    http_response_code(403);
    exit('Ya existe al menos un usuario. Por seguridad, el asistente de creación inicial está bloqueado.');
}
if (empty($_SESSION['csrf_admin_init'])) { $_SESSION['csrf_admin_init'] = bin2hex(random_bytes(32)); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
    $nombre = trim(isset($_POST['nombre_completo']) ? (string) $_POST['nombre_completo'] : '');
    $usuario = trim(isset($_POST['usuario']) ? (string) $_POST['usuario'] : '');
    $clave = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $confirmacion = isset($_POST['confirm_password']) ? (string) $_POST['confirm_password'] : '';

    if (!hash_equals($_SESSION['csrf_admin_init'], $token)) {
        $message = 'La sesión del formulario venció. Actualiza la página.';
    } elseif ($nombre === '' || $usuario === '' || !preg_match('/^[A-Za-z0-9._-]{4,100}$/', $usuario)) {
        $message = 'Completa el nombre y usa un usuario de 4 a 100 caracteres (letras, números, punto, guion o guion bajo).';
    } elseif (strlen($clave) < 12) {
        $message = 'La clave debe tener al menos 12 caracteres.';
    } elseif ($clave !== $confirmacion) {
        $message = 'Las claves no coinciden.';
    } else {
        try {
            // Revalidar dentro de la operación para evitar crear un segundo administrador.
            $dbh->beginTransaction();
            $again = (int) $dbh->query('SELECT COUNT(*) FROM usuarios_sistema')->fetchColumn();
            if ($again > 0) {
                $dbh->rollBack();
                http_response_code(403);
                exit('Ya existe un usuario. El asistente inicial está bloqueado.');
            }
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $stmt = $dbh->prepare("INSERT INTO usuarios_sistema (rol_id, nombre_completo, usuario, password_hash, estado) VALUES (1, :nombre, :usuario, :hash, 'ACTIVO')");
            $stmt->execute(array(':nombre' => $nombre, ':usuario' => $usuario, ':hash' => $hash));
            $dbh->commit();
            $created = true;
            unset($_SESSION['csrf_admin_init']);
        } catch (PDOException $e) {
            if ($dbh->inTransaction()) { $dbh->rollBack(); }
            error_log('Error creando administrador inicial: ' . $e->getMessage());
            $message = 'No se pudo crear el administrador. Verifica que el usuario no exista y que el rol Administrador esté creado.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Crear administrador inicial - SERVIUCIS</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body style="background:#f4f6f9;padding:30px">
<div class="container" style="max-width:560px;background:#fff;padding:28px;border-radius:8px">
<h3>Administrador inicial</h3>
<?php if ($created): ?>
<div class="alert alert-success">Administrador creado correctamente. Elimina ahora el archivo <strong>crear_administrador.php</strong> del servidor y luego ingresa desde el inicio de sesión.</div>
<a class="btn btn-primary" href="index.php">Ir al inicio de sesión</a>
<?php else: ?>
<p>Este asistente solo funciona mientras no exista ningún usuario del sistema.</p>
<?php if ($message !== ''): ?><div class="alert alert-danger"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<form method="post" autocomplete="off">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_admin_init'], ENT_QUOTES, 'UTF-8'); ?>">
<div class="form-group"><label>Nombre completo</label><input class="form-control" name="nombre_completo" maxlength="150" required></div>
<div class="form-group"><label>Usuario</label><input class="form-control" name="usuario" minlength="4" maxlength="100" required></div>
<div class="form-group"><label>Clave (mínimo 12 caracteres)</label><input class="form-control" type="password" name="password" minlength="12" required></div>
<div class="form-group"><label>Confirmar clave</label><input class="form-control" type="password" name="confirm_password" minlength="12" required></div>
<button class="btn btn-primary" type="submit">Crear administrador</button>
</form>
<?php endif; ?>
</div>
</body></html>
