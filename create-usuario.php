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
$nombres=$_POST['nombres'];
$cedula=$_POST['cedula'];
$indicador=$_POST['indicador'];
$negocio=$_POST['negocio'];  
$sql="INSERT INTO  tblusuarios(nombres,cedula,indicador,negocio) VALUES(:nombres,:cedula,:indicador,:negocio)";
$query = $dbh->prepare($sql);
$query->bindParam(':nombres',$nombres,PDO::PARAM_STR);
$query->bindParam(':cedula',$cedula,PDO::PARAM_STR);
$query->bindParam(':indicador',$indicador,PDO::PARAM_STR);
$query->bindParam(':negocio',$negocio,PDO::PARAM_STR);
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
                                    <h2 class="title">Registrar Usuarios</h2>
                                
                                </div>
                                
                                <!-- /.col-md-6 text-right -->
                            </div>
                            <!-- /.row -->
                            <div class="row breadcrumb-div">
                                <div class="col-md-6">
                                    <ul class="breadcrumb">
                                        <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                                        <li> Usuarios</li>
                                        <li class="active">Registrar Usuarios</li>
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
                                                    <h5>Registrar Usuario</h5>
                                                </div>
                                            </div>
                                            <div class="panel-body">
<?php if($msg){?>
<div class="alert alert-success left-icon-alert" role="alert">
 <strong>Well done!</strong><?php echo htmlentities($msg); ?>
 </div><?php } 
else if($error){?>
    <div class="alert alert-danger left-icon-alert" role="alert">
                                            <strong>Oh snap!</strong> <?php echo htmlentities($error); ?>
                                        </div>
                                        <?php } ?>
                                                <form class="" method="post">
                                                    <div class="form-group">
                                                        <label for="default" class="control-label">Nombre y Apellido</label>
 <input type="text" name="nombres" class="form-control" id="default" placeholder="Ingrese los datos" required="required">
                                                    </div>
<div class="form-group">
                                                        <label for="default" class="control-label">Cedula</label>
 <input type="text" name="cedula" class="form-control" id="default" placeholder="Cedula de Identidad" required="required">
                                                    </div>
                                                    
													<div class="form-group">
                                                        <label for="default" class="control-label">Indicador</label>
 <input type="text" name="indicador" class="form-control" id="default" placeholder="Indicador Pdvsa" required="required">
                                                    </div>
                                                    
													<div class="form-group">
                                                        <label for="default" class="control-label">Negocio</label>
 <input type="text" name="negocio" class="form-control" id="default" placeholder="Negocio al Cual Pertenece" required="required">
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
