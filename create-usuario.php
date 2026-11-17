<?php
require_once __DIR__.'/includes/config.php'; require_once __DIR__.'/includes/auth.php'; sisatRequireRoles([1]);
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$error=''; $ok='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $nombre=trim($_POST['nombre_completo']??''); $usuario=trim($_POST['usuario']??''); $correo=trim($_POST['correo']??''); $clave=(string)($_POST['clave']??''); $rol=(int)($_POST['rol_id']??0);
 if($nombre===''||$usuario===''||strlen($clave)<8||!in_array($rol,[1,2,3],true)) $error='Completa los campos. La clave debe tener mínimo 8 caracteres.';
 elseif($correo!==''&&!filter_var($correo,FILTER_VALIDATE_EMAIL)) $error='El correo no es válido.';
 else { try{$q=$dbh->prepare("INSERT INTO usuarios_sistema(rol_id,nombre_completo,usuario,correo,password_hash,estado) VALUES(?,?,?,?,?,'ACTIVO')");$q->execute([$rol,$nombre,$usuario,$correo?:null,password_hash($clave,PASSWORD_DEFAULT)]);$ok='Usuario creado correctamente.';}catch(PDOException $e){$error=$e->getCode()==='23000'?'El nombre de usuario ya existe.':'No se pudo crear el usuario.';error_log($e->getMessage());} }
}
include __DIR__.'/includes/topbar.php'; ?>
<div class="content-wrapper"><div class="content-container"><?php include __DIR__.'/includes/leftbar.php';?><div class="main-page"><div class="container-fluid"><h2 class="title">Registrar usuario del sistema</h2></div><section class="section"><div class="container-fluid"><div class="panel"><div class="panel-body p-20">
<?php if($error):?><div class="alert alert-danger"><?=h($error)?></div><?php endif;?><?php if($ok):?><div class="alert alert-success"><?=h($ok)?></div><?php endif;?>
<form method="post"><div class="form-group"><label>Nombre completo</label><input class="form-control" name="nombre_completo" required maxlength="150"></div><div class="form-group"><label>Usuario</label><input class="form-control" name="usuario" required maxlength="100"></div><div class="form-group"><label>Correo</label><input class="form-control" type="email" name="correo" maxlength="150"></div><div class="form-group"><label>Clave (mínimo 8 caracteres)</label><input class="form-control" type="password" name="clave" minlength="8" required></div><div class="form-group"><label>Perfil</label><select class="form-control" name="rol_id" required><option value="2">Técnico</option><option value="3">Gerencia / Consulta</option></select></div><button class="btn btn-primary">Guardar usuario</button> <a class="btn btn-default" href="manage-usuarios.php">Volver</a></form>
</div></div></div></section></div></div></div><?php include __DIR__.'/includes/footer.php';?>
