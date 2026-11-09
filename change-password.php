<?php
require_once __DIR__ . '/includes/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// La autenticación nueva establece ambos valores al iniciar sesión.
if (empty($_SESSION['alogin']) || empty($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_change_password'])) {
    $_SESSION['csrf_change_password'] = bin2hex(random_bytes(32));
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $token = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
    $currentPassword = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $newPassword = isset($_POST['newpassword']) ? (string) $_POST['newpassword'] : '';
    $confirmPassword = isset($_POST['confirmpassword']) ? (string) $_POST['confirmpassword'] : '';

    if (!hash_equals($_SESSION['csrf_change_password'], $token)) {
        $error = 'La sesión del formulario venció. Actualiza la página e inténtalo nuevamente.';
    } elseif ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'Completa todos los campos.';
    } elseif (strlen($newPassword) < 12) {
        $error = 'La nueva contraseña debe tener al menos 12 caracteres.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'La nueva contraseña y su confirmación no coinciden.';
    } elseif ($currentPassword === $newPassword) {
        $error = 'La nueva contraseña debe ser diferente de la actual.';
    } else {
        try {
            $sql = "SELECT password_hash FROM usuarios_sistema
                    WHERE id = :id AND usuario = :usuario AND estado = 'ACTIVO'
                    LIMIT 1";
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(':id', (int) $_SESSION['uid'], PDO::PARAM_INT);
            $stmt->bindValue(':usuario', (string) $_SESSION['alogin'], PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                $error = 'La contraseña actual es incorrecta.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $dbh->prepare("UPDATE usuarios_sistema
                                         SET password_hash = :hash, fecha_actualizacion = NOW()
                                         WHERE id = :id AND estado = 'ACTIVO'");
                $update->bindValue(':hash', $newHash, PDO::PARAM_STR);
                $update->bindValue(':id', (int) $_SESSION['uid'], PDO::PARAM_INT);
                $update->execute();

                if ($update->rowCount() === 1) {
                    $msg = 'La contraseña se actualizó correctamente.';
                    $_SESSION['csrf_change_password'] = bin2hex(random_bytes(32));
                } else {
                    $error = 'No se pudo confirmar la actualización de la contraseña. Inténtalo nuevamente.';
                }
            }
        } catch (PDOException $e) {
            error_log('Error al cambiar contraseña: ' . $e->getMessage());
            $error = 'Ocurrió un error al actualizar la contraseña. Contacta al administrador del sistema.';
        }
    }
}
?>
            <?php include('includes/topbar.php');?>   
            <div class="content-wrapper">
                <div class="content-container">
<?php include('includes/leftbar.php');?>                   
 <!-- /.left-sidebar -->

                    <div class="main-page">
                        <div class="container-fluid">
                            <div class="row page-title-div">
                                <div class="col-md-6">
                                    <h2 class="title">Gestión de Usuario</h2>
                                </div>
                                
                            </div>
                            <!-- /.row -->
                            <div class="row breadcrumb-div">
                                <div class="col-md-6">
                                    <ul class="breadcrumb">
            							<li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
            						
            							<li class="active">Gestión de Usuario</li>
            						</ul>
                                </div>
                               
                            </div>
                            <!-- /.row -->
                        </div>
                        <!-- /.container-fluid -->

                        <section class="section">
                            <div class="container-fluid">

                             

                              

                                <div class="row">
                                    <div class="col-md-8 col-md-offset-2">
                                        <div class="panel">
                                            <div class="panel-heading">
                                                <div class="panel-title">
                                                    <h5>Cambiar Contraseña</h5>
                                                </div>
                                            </div>
           <?php if($msg){?>
<div class="alert alert-success left-icon-alert" role="alert">
 <strong>Exitoso!</strong><?php echo htmlentities($msg); ?>
 </div><?php } 
else if($error){?>
    <div class="alert alert-danger left-icon-alert" role="alert">
                                            <strong>Error!</strong> <?php echo htmlentities($error); ?>
                                        </div>
                                        <?php } ?>
  
                                            <div class="panel-body">

                                                <form name="chngpwd" method="post" onSubmit="return valid();">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_change_password'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <div class="form-group has-success">
                                                        <label for="success" class="control-label">Contraseña Actual</label>
                                                		<div class="">
                                    <input type="password" name="password" class="form-control" required="required" id="success">
                                                      
                                                		</div>
                                                	</div>
                                                       <div class="form-group has-success">
                                                        <label for="success" class="control-label">Nueva Contraseña</label>
                                                        <div class="">
                                                            <input type="password" name="newpassword" minlength="12" required="required" class="form-control" id="success">
                                                        </div>
                                                    </div>
                                                     <div class="form-group has-success">
                                                        <label for="success" class="control-label">Confirme Contraseña</label>
                                                        <div class="">
                                                            <input type="password" name="confirmpassword" minlength="12" class="form-control" required="required" id="success">
                                                        </div>
                                                    </div>
  <div class="form-group has-success">

                                                        <div class="">
                                                           <button type="submit" name="submit" class="btn btn-success">Actualizar</button>
                                                    </div>


                                                    
                                                </form>

                                              
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.col-md-8 col-md-offset-2 -->
                                </div>
                                <!-- /.row -->

                               
                               

                            </div>
                            <!-- /.container-fluid -->
                        </section>
                        <!-- /.section -->

                    </div>
                    <!-- /.main-page -->

                </div>
                <!-- /.content-container -->
            </div>
            <!-- /.content-wrapper -->
<?php include('includes/footer.php');?>

<!--  Author Name: Mayuri K. 
 for any PHP, Codeignitor, Laravel OR Python work contact me at mayuri.infospace@gmail.com  
 Visit website : www.mayurik.com -->  

       
