<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['alogin'])=="")
    {   
    header("Location: index.php"); 
    }
    else{
if(isset($_POST['submit']))
{
$fecha=$_POST['fecha'];
$number=$_POST['number']; 
$nota=$_POST['nota']; 
$ClassId=$_POST['equipo']; 
$usuarioID=$_POST['usuario']; 
$status=1;
$sql="INSERT INTO tblasig (fecha,number,nota,ClassId,usuarioID,status) 
VALUES(:fecha,:number,:nota,:ClassId,:usuarioID,:status)";
$query = $dbh->prepare($sql);
$query->bindParam(':fecha',$fecha,PDO::PARAM_STR);
$query->bindParam(':number',$number,PDO::PARAM_STR);
$query->bindParam(':nota',$nota,PDO::PARAM_STR);
$query->bindParam(':ClassId',$ClassId,PDO::PARAM_STR);
$query->bindParam(':usuarioID',$usuarioID,PDO::PARAM_STR);
$query->bindParam(':status',$status,PDO::PARAM_STR);
$query->execute();
$lastInsertId = $dbh->lastInsertId();
if($lastInsertId)
{
$msg="Asignación Completada";
}
else 
{
$error="Ocurrio un error, Intente Nuevamente";
}

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
                                    <h2 class="title">Asignación de Activos</h2>
                                
                                </div>
                                
                                <!-- /.col-md-6 text-right -->
                            </div>
                            <!-- /.row -->
                            <div class="row breadcrumb-div">
                                <div class="col-md-6">
                                    <ul class="breadcrumb">
                                        <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                                
                                        <li class="active">Asignación de Activos</li>
                                    </ul>
                                </div>
                             
                            </div>
                            <!-- /.row -->
                        </div>
                        <section class="section">
                        <div class="container-fluid">
                           
                        <div class="row">
                                    <div class="col-md-12">
                                        <div class="panel">
                                            <div class="panel-heading">
                                                <div class="panel-title">
                                                    <h5>Detalles de Asignación</h5>
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
										
										
										   <?php
                function generate_string(){
                   $strength =5;
                   $input = '9876543210';
                   $input_length = strlen($input);
                   $random_string ='ASIG-';
                   for($i = 0;$i < $strength; $i++) 
                   {
                   $random_character = $input[mt_rand(1,$input_length - 1)];
                   $random_string .= $random_character;
                   

                   }

                       return $random_string;
                }

?>
                                                <form class="row" method="post">

<div class="form-group col-md-6">
<label for="default" class="control-label">Fecha de Asignación</label>
<input type="text" name="fecha" class="form-control" value='<?php echo date('Y-m-d');?>' readonly>
</div>

<div class="form-group col-md-6">
<label for="default" class="control-label">Numero de Control </label>
<input type="text" name="number" value='<?php echo generate_string();?>' readonly class="form-control" ">

</div>

<div class="form-group col-md-6">
<label for="default" class="control-label">Breve Descripción</label>

<input type="text" name="nota" class="form-control" id="email" required="required" autocomplete="off">
</div>
<br><br>

<br>




   <div class="form-group col-md-6">
<label for="default" class="control-label">Equipo por Asignar</label>             
 <select name="equipo" class="form-control" id="default" required="required">
<option value="">Seleccione el Equipo</option>
<?php $sql = "SELECT * from tblactivos";
$query = $dbh->prepare($sql);
$query->execute();
$results=$query->fetchAll(PDO::FETCH_OBJ);
if($query->rowCount() > 0)
{
foreach($results as $result)
{   ?>
<option value="<?php echo htmlentities($result->id); ?>"><?php echo htmlentities($result->descrip); ?>&nbsp; Etiqueta-<?php echo htmlentities($result->etiqueta); ?></option>
<?php }} ?>
 </select>
                                                    </div>                                               
<div class="form-group col-md-6">
<label for="default" class="control-label">Usuario por Asignar </label>             
 <select name="usuario" class="form-control" id="default" required="required">
<option value="">Seleccione el Usuario</option>
<?php $sql = "SELECT * from tblusuarios";
$query = $dbh->prepare($sql);
$query->execute();
$results=$query->fetchAll(PDO::FETCH_OBJ);
if($query->rowCount() > 0)
{
foreach($results as $result)
{   ?>
<option value="<?php echo htmlentities($result->id); ?>"><?php echo htmlentities($result->nombres); ?>&nbsp; Indicador-<?php echo htmlentities($result->indicador); ?></option>
<?php }} ?>
 </select>
                                                    </div>

                                                    

                                                    
                                                    <div class="form-group col-md-12">
                                                            <button type="submit" name="submit" class="btn btn-primary">Asignar</button>
                                                         <button type="reset" name="submit" class="btn btn-danger">Limpiar</button>
													
													</div>
                                                </form>

                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.col-md-12 -->
                                </div>
                    </div>
                </section>
                </div>
                <!-- /.content-container -->
            </div>
            <!-- /.content-wrapper -->
<?php include('includes/footer.php');?>

<!--  Author Name: Mayuri K. 
 for any PHP, Codeignitor, Laravel OR Python work contact me at mayuri.infospace@gmail.com  
 Visit website : www.mayurik.com -->  

<?PHP } ?>
