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
$studentname=$_POST['fullanme'];
$roolid=$_POST['rollid']; 
$StudentEmail=$_POST['StudentEmail']; 
$Gender=$_POST['Gender']; 
$DOB=$_POST['DOB']; 
$classid=$_POST['class']; 
$status=1;
$sql="INSERT INTO  tblstudents(StudentName,RollId,StudentEmail,Gender,DOB,ClassId,Status) VALUES(:studentname,:roolid,:StudentEmail,:Gender,:DOB,:classid,:status)";
$query = $dbh->prepare($sql);
$query->bindParam(':studentname',$studentname,PDO::PARAM_STR);
$query->bindParam(':roolid',$roolid,PDO::PARAM_STR);
$query->bindParam(':StudentEmail',$StudentEmail,PDO::PARAM_STR);
$query->bindParam(':Gender',$Gender,PDO::PARAM_STR);
$query->bindParam(':DOB',$DOB,PDO::PARAM_STR);
$query->bindParam(':classid',$classid,PDO::PARAM_STR);
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
                                                <form class="row" method="post">

<div class="form-group col-md-6">
<label for="default" class="control-label">Nombre del Trabajador</label>
<input type="text" name="fullanme" class="form-control" id="fullanme" required="required" autocomplete="off">
</div>

<div class="form-group col-md-6">
<label for="default" class="control-label">Numero de Carnet</label>
<input type="text" name="rollid" class="form-control" id="rollid" maxlength="10" required="required" autocomplete="off">

</div>

<div class="form-group col-md-6">
<label for="default" class="control-label">Indicador</label>

<input type="email" name="StudentEmail" class="form-control" id="email" required="required" autocomplete="off">
</div>

<div class="form-group col-md-6">
                                                       <label for="default" class="control-label">Gerencia </label>
														<select name="Gender" class="form-control" id="default" required="required">
<option value="">Seleccione Gerencia</option>
<option value="Ait">Ait</option>
<option value="Salud">Salud</option>
<option value="RRHH">RRHH </option>
<option value="DSI">DSI</option>
<option value="PERFORACIÓN">PERFORACIÓN</option>
<option value="SSGG">SSGG</option>
 </select>
                                                    </div>
                                                  
<div class="form-group col-md-6">
                                                        <label for="date" class=" control-label"></label>
														<select name="DOB" class="form-control" id="default" required="required">
<option value="">Seleccione Ubicación</option>
<option value="Paraiso Plaza">Paraiso Plaza</option>
<option value="Edificio División Junin">Edificio División Junin</option>
<option value="Trailers de Salud">Trailers de Salud </option>
<option value="Area Operacional San diego">Area Operacional San diego</option>
<option value="Area Operacional Zuata">Area Operacional Zuata</option>
<option value="Area Operacional Pariaguán">Area Operacional Pariaguán</option>
 </select>
                                                    </div>
<div class="form-group col-md-6">
<label for="default" class="control-label">Equipos Asignar</label>             
 <select name="class" class="form-control" id="default" required="required">
<option value="">Seleccione el Equipo</option>
<?php $sql = "SELECT * from tblclasses";
$query = $dbh->prepare($sql);
$query->execute();
$results=$query->fetchAll(PDO::FETCH_OBJ);
if($query->rowCount() > 0)
{
foreach($results as $result)
{   ?>
<option value="<?php echo htmlentities($result->id); ?>"><?php echo htmlentities($result->ClassName); ?>&nbsp; Serial-<?php echo htmlentities($result->Section); ?></option>
<?php }} ?>
 </select>
                                                    </div>

                                                    

                                                    
                                                    <div class="form-group col-md-12">
                                                            <button type="submit" name="submit" class="btn btn-success">Asignar</button>
                                                                          <button type="reset" name="submit" class="btn btn-success">Limpiar</button>
													
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
