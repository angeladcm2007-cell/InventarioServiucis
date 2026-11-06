<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['alogin'])=="")
    {   
    header("Location: index.php"); 
    }
    else{
		$cid=intval($_GET['cid']);
if(isset($_POST['submit']))
{
$equipoID=$_POST['equipo'];
$usuarioID=$_POST['usuario'];
$sql="update tblasignaciones set equipoID=:equipoID,usuarioID=:usuarioID ";
$query = $dbh->prepare($sql);
$query->bindParam(':equipoID',$equipoID,PDO::PARAM_STR);
$query->bindParam(':usuarioID',$usuarioID,PDO::PARAM_STR);
$query->bindParam(':cid',$cid,PDO::PARAM_STR);
$query->execute();

$msg="Datos Actualizados Correctamente";
}


?>

            <!-- ========== TOP NAVBAR ========== -->
  <?php include('includes/topbar.php');?> 
            <!-- ========== WRAPPER FOR BOTH SIDEBARS & MAIN CONTENT ========== -->
            <div class="content-wrapper">
                <div class="content-container">

                    <!-- ========== LEFT SIDEBAR ========== -->
                   <?php include('includes/leftbar.php');?>  
                    <!-- /.left-sidebar -->

                    <div class="main-page">

                     <div class="container-fluid">
                            <div class="row page-title-div">
                                <div class="col-md-6">
                                    <h2 class="title">Actualización de Asignación</h2>
                                
                                </div>
                                
                                <!-- /.col-md-6 text-right -->
                            </div>
                            <!-- /.row -->
                            <div class="row breadcrumb-div">
                                <div class="col-md-6">
                                    <ul class="breadcrumb">
                                        <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                                
                                        <li class="active">Actualización de Asignación</li>
                                    </ul>
                                </div>
                             
                            </div>
                            <!-- /.row -->
                        </div>
                        <div class="container-fluid">
                           
                        <div class="row">
                                    <div class="col-md-12">
                                        <div class="panel">
                                            <div class="panel-heading">
                                                <div class="panel-title">
                                                    <h5>Ingrese la Información </h5>
                                                </div>
                                            </div>
                                            <div class="panel-body">
<?php if($msg){?>
<div class="alert alert-success left-icon-alert" role="alert">
 <strong>Exitoso!</strong><?php echo htmlentities($msg); ?>
 </div><?php } 
else if($error){?>
    <div class="alert alert-danger left-icon-alert" role="alert">
                                            <strong>Error!</strong> <?php echo htmlentities($error); ?>
                                        </div>
                                        <?php } ?>
                                                <form class="form-horizontal" method="post">
<?php 

$sql = "SELECT * from tblasignaciones
 join tblusuarios on tblusuarios.id=tblasignaciones.usuarioID inner join tblactivos on tblactivos.id=tblasignaciones.equipoID where tblasignaciones.equipoID=:cid";
$query = $dbh->prepare($sql);
$query->bindParam(':cid',$cid,PDO::PARAM_STR);
$query->execute();
$results=$query->fetchAll(PDO::FETCH_OBJ);
$cnt=1;
if($query->rowCount() > 0)
{
foreach($results as $result)
{  ?>




 <div class="form-group">
                                                        <label for="default" class="col-sm-2 control-label">Equipo Asignado</label>
                                                        <div class="col-sm-10">
<input type="text" name="equipo" class="form-control" id="classname" value="<?php echo htmlentities($result->descrip)?>(<?php echo htmlentities($result->Section)?>)" >
                                                        </div>
                                                    </div>
 <div class="form-group">
                                                        <label for="default" class="col-sm-2 control-label">usuario Asignado</label>
                                                        <div class="col-sm-10">
<input type="text" name="usuario" class="form-control" id="classname" value="<?php echo htmlentities($result->nombres)?>(<?php echo htmlentities($result->Section)?>)" >
                                                        </div>
                                                    </div>










<?php }} ?>                                                    

                                                    
                                                    <div class="form-group">
                                                        <div class="col-sm-offset-2 col-sm-10">
                                                            <button type="submit" name="submit" class="btn btn-primary">Actualizar</button>
                                                        </div>
                                                    </div>
                                                </form>

                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.col-md-12 -->
                                </div>
                    </div>
                </div>
                <!-- /.content-container -->
            </div>
            <!-- /.content-wrapper -->
<?php include('includes/footer.php');?>

<!--  Author Name: Mayuri K. 
 for any PHP, Codeignitor, Laravel OR Python work contact me at mayuri.infospace@gmail.com  
 Visit website : www.mayurik.com -->  
       
<?PHP } ?>
