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
$tipo_activo=$_POST['tipo_activo'];
$descrip=$_POST['descrip'];
$estado=$_POST['estado'];
$etiqueta=$_POST['etiqueta'];
$serial=$_POST['serial'];
$modelo=$_POST['modelo'];
$marca=$_POST['marca'];
$ip_mac=$_POST['ip_mac'];
$ubicacion=$_POST['ubicacion'];
$sql="INSERT INTO  tblactivos(tipo_activo,descrip,estado,etiqueta,serial,modelo,marca,ip_mac,ubicacion) VALUES(:tipo_activo,:descrip,:estado,:etiqueta,:serial,:modelo,:marca,:ip_mac,:ubicacion)";
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
$query->execute();
$lastInsertId = $dbh->lastInsertId();
if($lastInsertId)
{
$msg="Registro Exitoso";
}
else 
{
$error="Ocurrio un Error Intente Nuevamente";
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
                                    <h2 class="title">Registrar Activos de Telecomunicaciones</h2>
                                
                                </div>
                                
                                <!-- /.col-md-6 text-right -->
                            </div>
                            <!-- /.row -->
                            <div class="row breadcrumb-div">
                                <div class="col-md-6">
                                    <ul class="breadcrumb">
                                        <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                                        <li> Activos</li>
                                        <li class="active">Registrar Activos </li>
                                    </ul>
                                </div>
                             
                            </div>
                            <!-- /.row -->
                        </div>
                         <section class="section">
                        <div class="container-fluid">
                           
                        <div class="row">
                                    <div class="col-md-8 col-md-offset-2">
                                        <div class="panel">
                                            <div class="panel-heading">
                                                <div class="panel-title">
                                                    <h5>Registrar Activos de Telecomunicaciones</h5>
                                                </div>
                                            </div>
                                            <div class="panel-body">
<?php if($msg){?>
<div class="alert alert-success left-icon-alert" role="alert">
 <strong>EXITOSO!</strong><?php echo htmlentities($msg); ?>
 </div><?php } 
else if($error){?>
    <div class="alert alert-danger left-icon-alert" role="alert">
                                            <strong>ERROR!</strong> <?php echo htmlentities($error); ?>
                                        </div>
                                        <?php } ?>
                                                <form class="" method="post">
												
													<div class="form-group">
                                                     <label for="date" class=" control-label"></label>
														<select name="tipo_activo" class="form-control" id="default" required="required">
<option value="">Seleccione Tipo de Activo</option>
<option value="Telecomunicaciones">Telecomunicaciones </option>
<option value="Automatizacion">Automatización</option>


 </select>
                                                    </div>
												
												
												
                                                    <div class="form-group">
                                                        <label for="default" class="control-label">Descripción</label>
 <input type="text" name="descrip" class="form-control" id="default" placeholder="Ingrese los datos" required="required">
                                                    </div>
												
	<div class="form-group">
                                                     <label for="date" class=" control-label"></label>
														<select name="estado" class="form-control" id="default" required="required">
<option value="">Seleccione Estatus del Equipo </option>
<option value="Nuevo">Nuevo </option>
<option value="Asignado">Asignado</option>
<option value="En Deposito">En Deposito</option>

 </select>
                                                    </div>
                                                    
													<div class="form-group">
                                                        <label for="default" class="control-label">Etiqueta</label>
 <input type="text" name="etiqueta" class="form-control" id="default" placeholder="Ingrese los datos" required="required">
                                                    </div>
                                                    
													<div class="form-group">
                                                        <label for="default" class="control-label">Serial</label>
 <input type="text" name="serial" class="form-control" id="default" placeholder="Ingrese los datos" required="required">
                                                    </div>
                                                    	<div class="form-group">
                                                        <label for="default" class="control-label">Modelo</label>
 <input type="text" name="modelo" class="form-control" id="default" placeholder="Ingrese los datos" required="required">
                                                    </div>
														<div class="form-group">
                                                        <label for="default" class="control-label">Marca</label>
 <input type="text" name="marca" class="form-control" id="default" placeholder="Ingrese los datos" required="required">
                                                    </div>
														<div class="form-group">
                                                        <label for="default" class="control-label">Dirección IP O MAC</label>
 <input type="text" name="ip_mac" class="form-control" id="default" placeholder="Ingrese los datos" required="required">
                                                    </div>
	<div class="form-group">
	
	      <label for="date" class=" control-label"></label>
														<select name="ubicacion" class="form-control" id="default" required="required">
<option value="">Seleccione Ubicación</option>
<option value="Asignado">Asignado</option>
<option value="Deposito División Junin">Deposito División Junin</option>
<option value="Deposito Petro SanFelix">Deposito Petro SanFelix</option>
<option value="Deposito Petrocedeno">Deposito Petrocedeño</option>
<option value="Deposito IndoVenezolana">Deposito IndoVenezolana</option>

 </select>
                                                    </div>

                                                    
                                                    <div class="form-group">
                                                        
                                                            <button type="submit" name="submit" class="btn btn-primary">Guardar</button>
                                                       
                                                            <button type="reset" name="reset" class="btn btn-danger">Limpiar</button>
                                                       
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
        </div>
            <!-- /.content-wrapper -->
<?php include('includes/footer.php');?>

<!--  Author Name: Mayuri K. 
 for any PHP, Codeignitor, Laravel OR Python work contact me at mayuri.infospace@gmail.com  
 Visit website : www.mayurik.com -->  

<?PHP } ?>
