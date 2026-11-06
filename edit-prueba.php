<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['alogin'])=="")
    {   
    header("Location: index.php"); 
    }
    else{

$stid=intval($_GET['stid']);

if(isset($_POST['submit']))
{
$fecha=$_POST['fecha'];
$number=$_POST['number']; 
$nota=$_POST['nota']; 
$classid=$_POST['equipo']; 
$classid2=$_POST['usuario']; 
$status=$_POST['status'];
$sql="update tblasig set fecha=:fecha,number=:number,nota=:nota,Status=:status where StudentId=:stid ";
$query = $dbh->prepare($sql);
$query->bindParam(':fecha',$fecha,PDO::PARAM_STR);
$query->bindParam(':number',$number,PDO::PARAM_STR);
$query->bindParam(':nota',$nota,PDO::PARAM_STR);
$query->bindParam(':status',$status,PDO::PARAM_STR);
$query->bindParam(':stid',$stid,PDO::PARAM_STR);
$query->execute();

$msg="Student info updated successfully";
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
                                    <h2 class="title">Gestión de Asignaciones</h2>
                                
                                </div>
                                
                                <!-- /.col-md-6 text-right -->
                            </div>
                               <!-- /.row -->
                            <div class="row breadcrumb-div">
                                <div class="col-md-6">
                                    <ul class="breadcrumb">
            							<li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                                        <li> Asignaciones</li>
            							<li class="active">Gestión de Asignaciones</li>
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
                                                    <h5>Actualizar Información</h5>
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

$sql = "SELECT *
from tblasig  join tblactivos 
on tblactivos.id=tblasig.ClassId inner join tblusuarios on tblusuarios.id=tblasig.usuarioID
where tblasig.StudentId=:stid";
$query = $dbh->prepare($sql);
$query->bindParam(':stid',$stid,PDO::PARAM_STR);
$query->execute();
$results=$query->fetchAll(PDO::FETCH_OBJ);
$cnt=1;
if($query->rowCount() > 0)
{
foreach($results as $result)
{  ?>


<div class="form-group">
<label for="default" class="col-sm-2 control-label">Fecha de Asignación</label>
<div class="col-sm-10">
<input type="text" name="fecha" class="form-control" readonly id="fullanme" value="<?php echo htmlentities($result->fecha)?>" required="required" autocomplete="off">
</div>
</div>

<div class="form-group">
<label for="default" class="col-sm-2 control-label">N° de Control</label>
<div class="col-sm-10">
<input type="text" name="number" class="form-control" readonly id="rollid" value="<?php echo htmlentities($result->number)?>" maxlength="5" required="required" autocomplete="off">
</div>
</div>

<div class="form-group">
<label for="default" class="col-sm-2 control-label">Nota</label>
<div class="col-sm-10">
<input type="text" name="nota" class="form-control" id="email" value="<?php echo htmlentities($result->nota)?>" required="required" autocomplete="off">
</div>
</div>

                                                    <div class="form-group">
                                                        <label for="default" class="col-sm-2 control-label">Equipo  Asignado</label>
                                                        <div class="col-sm-10">
<input type="text" name="equipo" class="form-control" id="classname" value="<?php echo htmlentities($result->descrip)?>(<?php echo htmlentities($result->etiqueta)?>)" readonly>
                                                        </div>
                                                    </div>
   <div class="form-group">
                                                        <label for="default" class="col-sm-2 control-label">Usuario  Asignado</label>
                                                        <div class="col-sm-10">
<input type="text" name="usuario" class="form-control" id="classname" value="<?php echo htmlentities($result->nombres)?>(<?php echo htmlentities($result->indicador)?>)" readonly>
                                                        </div>
                                                    </div>


<div class="form-group">
<label for="default" class="col-sm-2 control-label">Estatus</label>
<div class="col-sm-10">
<?php  $stats=$result->Status;
if($stats=="1")
{
?>
<input type="radio" name="status" value="1" required="required" checked>Activo <input type="radio" name="status" value="0" required="required">Inactivo 
<?php }?>
<?php  
if($stats=="0")
{
?>
<input type="radio" name="status" value="1" required="required" >Activo <input type="radio" name="status" value="0" required="required" checked>Inactivo 
<?php }?>



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
