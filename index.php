<?php
require_once __DIR__ . '/includes/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Si ya existe una sesión válida, no volver a mostrar el formulario.
if (!empty($_SESSION['alogin']) && !empty($_SESSION['uid'])) {
    header('Location: dashboard.php');
    exit;
}

if (empty($_SESSION['csrf_login'])) {
    $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
}

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $csrf = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
    $username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

    if (!hash_equals($_SESSION['csrf_login'], $csrf)) {
        $loginError = 'La sesión del formulario venció. Actualiza la página e inténtalo nuevamente.';
    } elseif ($username === '' || $password === '') {
        $loginError = 'Ingresa el usuario y la clave.';
    } else {
        try {
            $sql = "SELECT u.id, u.usuario, u.nombre_completo, u.password_hash, u.rol_id,
                           r.nombre AS rol_nombre
                    FROM usuarios_sistema u
                    INNER JOIN roles r ON r.id = u.rol_id
                    WHERE u.usuario = :usuario AND u.estado = 'ACTIVO'
                    LIMIT 1";
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(':usuario', $username, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['alogin'] = $user['usuario']; // Se conserva para compatibilidad con las páginas actuales.
                $_SESSION['uid'] = (int) $user['id'];
                $_SESSION['nombre_completo'] = $user['nombre_completo'];
                $_SESSION['rol_id'] = (int) $user['rol_id'];
                $_SESSION['rol'] = $user['rol_nombre'];
                unset($_SESSION['csrf_login']);

                $update = $dbh->prepare('UPDATE usuarios_sistema SET ultimo_acceso = NOW() WHERE id = :id');
                $update->bindValue(':id', $user['id'], PDO::PARAM_INT);
                $update->execute();

                header('Location: dashboard.php');
                exit;
            }
            $loginError = 'Usuario o clave incorrectos, o cuenta inactiva.';
        } catch (PDOException $e) {
            error_log('Error de autenticación SISAT: ' . $e->getMessage());
            $loginError = 'No fue posible validar el acceso. Verifica la conexión con la base de datos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
   <head>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Inicio de Sesión :.</title>
      <link rel="icon" type="image/x-icon" href="assets/images/logoNom.png">
      <link rel="stylesheet" href="assets/css/bootstrap.min.css" media="screen" >
      <link rel="stylesheet" href="assets/css/font-awesome.min.css" media="screen" >
      <link rel="stylesheet" href="assets/css/animate-css/animate.min.css" media="screen" >
      <link rel="stylesheet" href="assets/css/prism/prism.css" media="screen" >
      <!-- USED FOR DEMO HELP - YOU CAN REMOVE IT -->
      <link rel="stylesheet" href="assets/css/main.css" media="screen" >
      <script src="assets/js/modernizr/modernizr.min.js"></script>
   </head>
   <body class="" style="background-image: url(assets/images/back2.jpg);
      background-color: #ffffff;
      background-size: cover;
      height: 100%;

      background-position: center;
      background-repeat: no-repeat;
      background-size: cover;">
      <div class="main-wrapper">
         <div class="">
            <div class="row">
               <div class="col-md-offset-7 col-lg-5">
                  <section class="section">
                     <div class="row mt-40">
                        <div class="col-md-offset-2 col-md-10  pt-50">
                           <div class="row mt-30 ">
                              <div class="col-md-11">
                                <div class="panel login-box" style="    background: #EFEFFB;">
                                    <div class="panel-heading">
                                       <div class="text-center"><br>
                                          <a href="#">
                                            <img style="height: 70px" src="assets/images/logo-footer.png"></a>
                                            <br>
                                             <h5 style="color: #000000;"> <strong>[ SISAT V.1.1 ]</strong></h5>
                                       </div>
                                    </div>
                                    <div class="panel-body p-20">
                                       <?php if ($loginError !== ''): ?>
                                          <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></div>
                                       <?php endif; ?>
                                       <form class="admin-login" method="post" autocomplete="on">
                                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_login'], ENT_QUOTES, 'UTF-8'); ?>">
                                          <div class="form-group">
                                             <label for="inputEmail3" class="control-label" style="color: #000000;" >Indicador</label>
                                             <input type="text" name="username" class="form-control" id="inputEmail3" placeholder="Usuario" autocomplete="username" required>
                                          </div>
                                          <div class="form-group">
                                             <label for="inputPassword3" class="control-label" style="color: #000000;" >Clave</label>
                                             <input type="password" name="password" class="form-control" id="inputPassword3" placeholder="Clave" autocomplete="current-password" required>
                                          </div><br>
                                          <div class="form-group mt-20">
                                                <button type="submit" name="login" class="btn login-btn">Ingresar</button>
                                          </div>
                                          <br>
                                       </form>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </section>
               </div>
            </div>
         </div>
      </div>
      <!-- ========== COMMON JS FILES ========== -->
      <script src="assets/js/jquery/jquery-2.2.4.min.js"></script>
      <script src="assets/js/jquery-ui/jquery-ui.min.js"></script>
      <script src="assets/js/bootstrap/bootstrap.min.js"></script>
      <script src="assets/js/pace/pace.min.js"></script>
      <script src="assets/js/lobipanel/lobipanel.min.js"></script>
      <script src="assets/js/iscroll/iscroll.js"></script>
      <!-- ========== PAGE JS FILES ========== -->
      <!-- ========== THEME JS ========== -->
      <script src="assets/js/main.js"></script>
      <script>
         $(function(){
         
         });
      </script>
   </body>
</html>