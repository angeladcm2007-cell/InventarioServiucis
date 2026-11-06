<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['alogin'])=="")
	{   
	header("Location: index.php"); 
	}
	else{
$cid=intval($_GET['id']);
if(isset($_POST['update']))
{
$tipo_activo=$_POST['tipo_activo']; 
$descrip=$_POST['descrip']; 
$estado=$_POST['estado'];
$etiqueta=$_POST['etiqueta']; 
$serial=$_POST['serial'];
$modelo=$_POST['modelo']; 
$marca=$_POST['marca'];
$ip_mac=$_POST['ip_mac']; 
$ubicacion=$_POST['ubicacion'];

$sql="update  tblactivos set
                               tipo_activo=:tipo_activo,
							   descrip=:descrip,
							   estado=:estado,
							   etiqueta=:etiqueta,
							   serial=:serial,
							   modelo=:modelo,
							   marca=:marca,
							   ip_mac=:ip_mac,
							   ubicacion=:ubicacion 
							   
							   
							   where id=:cid ";
$query = $dbh->prepare($sql);
$query->bindParam(':tipo_activo',$tipo_activo,PDO::PARAM_STR);
$query->bindParam(':descrip',$descrip,PDO::PARAM_STR);
$query->bindParam(':estado',$estado,PDO::PARAM_STR);
$query->bindParam(':etiqueta',$etiqueta,PDO::PARAM_STR);
$query->bindParam(':serial',$serial,PDO::PARAM_STR);
$query->bindParam(':modelo',$modelo,PDO::PARAM_STR);
$query->bindParam(':marca',$marca,PDO::PARAM_STR);
$query->bindParam(':ip_mac',$ip_mac,PDO::PARAM_STR);
$query->bindParam(':ubicacion',$ubicacion,PDO::PARAM_STR);
$query->bindParam(':cid',$cid,PDO::PARAM_STR);
$query->execute();
$msg="Datos Actualizados";
}
?>


			<!-- ========== TOP NAVBAR ========== -->
			<?php include('includes/topbar.php');?>   
		  <!-----End Top bar>
		========== WRAPPER FOR BOTH SIDEBARS & MAIN CONTENT ========== -->
			<div class="content-wrapper">
				<div class="content-container">

<!-- ========== LEFT SIDEBAR ========== -->
<?php include('includes/leftbar.php');?>                   
 <!-- /.left-sidebar -->

					<div class="main-page">
						<div class="container-fluid">
							<div class="row page-title-div">
								<div class="col-md-6">
									<h2 class="title">Editar Activo Registrado</h2>
								</div>
								
							</div>
							<!-- /.row -->
							<div class="row breadcrumb-div">
								<div class="col-md-6">
									<ul class="breadcrumb">
										<li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
										<li><a href="#">Activos</a></li>
										<li class="active">Editar Registro</li>
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
													<h5>Actualizar Información del Activo</h5>
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

												<form method="post">
<?php 
$cid=intval($_GET['id']);
$sql = "SELECT * from tblactivos where id=:cid";
$query = $dbh->prepare($sql);
$query->bindParam(':cid',$cid,PDO::PARAM_STR);
$query->execute();
$results=$query->fetchAll(PDO::FETCH_OBJ);
$cnt=1;
if($query->rowCount() > 0)
{
foreach($results as $result)
{   ?>
	<div class="form-group has-success">
														<label for="success" class="control-label">&nbsp;Tipo de Activo</label>
														<div class="">
															<input type="text" name="tipo_activo" value="<?php echo htmlentities($result->tipo_activo);?>" required="required" class="form-control" id="success">
															<span class="help-block"></span>
														</div>
													</div>
													<div class="form-group has-success">
														<label for="success" class="control-label">&nbsp;Descripción</label>
														<div class="">
															<input type="text" name="descrip" value="<?php echo htmlentities($result->descrip);?>" required="required" class="form-control" id="success">
															<span class="help-block"></span>
														</div>
													</div>
													   <div class="form-group has-success">
														<label for="success" class="control-label">&nbsp;Estatus del Equipo</label>
														<div class="">
															<input type="text" name="estado" value="<?php echo htmlentities($result->estado);?>" required="required" class="form-control" id="success">
															<span class="help-block"></span>
														</div>
													</div>
													 <div class="form-group has-success">
														<label for="success" class="control-label">&nbsp;Etiqueta</label>
														<div class="">
															<input type="text" name="etiqueta" value="<?php echo htmlentities($result->etiqueta);?>" class="form-control" required="required" id="success">
															<span class="help-block"></span>
														</div>
													</div>
													         <div class="form-group has-success">
														<label for="success" class="control-label">&nbsp;Serial</label>
														<div class="">
															<input type="text" name="serial" value="<?php echo htmlentities($result->serial);?>" class="form-control" required="required" id="success">
															<span class="help-block"></span>
														</div>
													</div>
													    <div class="form-group has-success">
														<label for="success" class="control-label">&nbsp;	Modelo</label>
														<div class="">
															<input type="text" name="modelo" value="<?php echo htmlentities($result->modelo);?>" class="form-control" required="required" id="success">
															<span class="help-block"></span>
														</div>
													</div>
													    <div class="form-group has-success">
														<label for="success" class="control-label">&nbsp;Marca</label>
														<div class="">
															<input type="text" name="marca" value="<?php echo htmlentities($result->marca);?>" class="form-control" required="required" id="success">
															<span class="help-block"></span>
														</div>
													</div>
													    <div class="form-group has-success">
														<label for="success" class="control-label">&nbsp;Direción IP ó MAC</label>
														<div class="">
															<input type="text" name="ip_mac" value="<?php echo htmlentities($result->ip_mac);?>" class="form-control" required="required" id="success">
															<span class="help-block"></span>
														</div>
													</div>
													    <div class="form-group has-success">
														<label for="success" class="control-label">&nbsp;Ubicación</label>
														<div class="">
															<input type="text" name="ubicacion" value="<?php echo htmlentities($result->ubicacion);?>" class="form-control" required="required" id="success">
															<span class="help-block"></span>
														</div>
													</div>
													        
													<?php }} ?>
  <div class="form-group has-success">

														<div class="">
														   <button type="submit" name="update" class="btn btn-primary btn-labeled">Actualizar<span class="btn-label btn-label-right"><i class="fa fa-check"></i></span></button>
													
													
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

			 
					<!-- /.right-sidebar -->

				</div>
				<!-- /.content-container -->
			</div>
			<!-- /.content-wrapper -->
<?php include('includes/footer.php');?>

<!--  Author Name: Mayuri K. 
 for any PHP, Codeignitor, Laravel OR Python work contact me at mayuri.infospace@gmail.com  
 Visit website : www.mayurik.com -->  

	   
<?php  } ?>
