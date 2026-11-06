<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['alogin'])=="")
	{   
	header("Location: index.php"); 
	}
	else{
if(isset($_POST['update']))
{
$nombres=$_POST['nombres']; 
$cedula=$_POST['cedula'];
$indicador=$_POST['indicador']; 
$negocio=$_POST['negocio'];
$cid=intval($_GET['id']);
$sql="update  tblusuarios set nombres=:nombres,cedula=:cedula,indicador=:indicador,negocio=:negocio where id=:cid ";
$query = $dbh->prepare($sql);
$query->bindParam(':nombres',$nombres,PDO::PARAM_STR);
$query->bindParam(':cedula',$cedula,PDO::PARAM_STR);
$query->bindParam(':indicador',$indicador,PDO::PARAM_STR);
$query->bindParam(':negocio',$negocio,PDO::PARAM_STR);
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
									<h2 class="title">Editar Usuario Registrado</h2>
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
													<h5>Actualizar Información del Usuario</h5>
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
$sql = "SELECT * from tblusuarios where id=:cid";
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
														<label for="success" class="control-label">&nbsp;Nombres y Apellidos</label>
														<div class="">
															<input type="text" name="nombres" value="<?php echo htmlentities($result->nombres);?>" required="required" class="form-control" id="success">
															<span class="help-block"></span>
														</div>
													</div>
													   <div class="form-group has-success">
														<label for="success" class="control-label">&nbsp;Cedula</label>
														<div class="">
															<input type="text" name="cedula" value="<?php echo htmlentities($result->cedula);?>" required="required" class="form-control" id="success">
															<span class="help-block"></span>
														</div>
													</div>
													 <div class="form-group has-success">
														<label for="success" class="control-label">&nbsp;Indicador</label>
														<div class="">
															<input type="text" name="indicador" value="<?php echo htmlentities($result->indicador);?>" class="form-control" required="required" id="success">
															<span class="help-block"></span>
														</div>
													</div>
													         <div class="form-group has-success">
														<label for="success" class="control-label">&nbsp;Negocio</label>
														<div class="">
															<input type="text" name="negocio" value="<?php echo htmlentities($result->negocio);?>" class="form-control" required="required" id="success">
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
