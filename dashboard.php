<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['alogin'])=="")
{   header("Location: index.php"); }else{
?>


              <?php include('includes/topbar.php');?>
            <div class="content-wrapper">
                <div class="content-container">

                    <?php include('includes/leftbar.php');?>  

                    <div class="main-page">
                        <div class="container-fluid">
                            <div class="row page-title-div">
                                <div class="col-sm-6">
                                    <h2 class="title">Inicio</h2>
                                  
                                </div>
                                <!-- /.col-sm-6 -->
                            </div>
                            <!-- /.row -->
                      
                        </div>
                        <!-- /.container-fluid -->

                        <section class="section">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 text-center">
                                        <a class="dashboard-stat bg-white" href="manage-asignacion.php">
<?php 
$sql1 ="SELECT number from tblasig ";
$query1 = $dbh -> prepare($sql1);
$query1->execute();
$results1=$query1->fetchAll(PDO::FETCH_OBJ);
$totalstudents=$query1->rowCount();
?>
<span class="bg-icon"><i class="fa fa-share"></i></span>
                                            <span class="number counter"><?php echo htmlentities($totalstudents);?></span>
                                            <span class="name">Activos Asignados</span>
                                            
                                        </a>
                                        <!-- /.dashboard-stat -->
                                    </div>
                 

                                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                        <a class="dashboard-stat bg-white" href="manage-activos.php">
                                        
										
										
										
										
										<?php 
$sql2 ="SELECT id from  tblactivos where tipo_activo='telecomunicaciones' ";
$query2 = $dbh -> prepare($sql2);
$query2->execute();
$results2=$query2->fetchAll(PDO::FETCH_OBJ);
$totalclasses=$query2->rowCount();
?><span class="bg-icon"><i class="fa fa-server"></i></span>
                                            <span class="number counter"><?php echo htmlentities($totalclasses);?></span>
                                            <span class="name">Activos Telecomunicaciones </span>
                                            
                                        </a>
                                        <!-- /.dashboard-stat -->
                                    </div>
									         <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                        <a class="dashboard-stat bg-white" href="manage-activos.php">
                                        
										
										
										
										
										<?php 
$sql2 ="SELECT id from  tblactivos where tipo_activo='automatizacion' ";
$query2 = $dbh -> prepare($sql2);
$query2->execute();
$results2=$query2->fetchAll(PDO::FETCH_OBJ);
$totalclasses=$query2->rowCount();
?><span class="bg-icon"><i class="fa fa-server"></i></span>
                                            <span class="number counter"><?php echo htmlentities($totalclasses);?></span>
                                            <span class="name">Activos Automatización </span>
                                            
                                        </a>
                                        <!-- /.dashboard-stat -->
                                    </div>
									   <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                        <a class="dashboard-stat bg-white" href="manage-usuarios.php">
                                        
										
										
										
										
										<?php 
$sql2 ="SELECT id from  tblactivos ";
$query2 = $dbh -> prepare($sql2);
$query2->execute();
$results2=$query2->fetchAll(PDO::FETCH_OBJ);
$totalclasses=$query2->rowCount();
?><span class="bg-icon"><i class="fa fa-users"></i></span>
                                            <span class="number counter"><?php echo htmlentities($totalclasses);?></span>
                                            <span class="name">Usuarios Registrados </span>
                                            
                                        </a>
                                        <!-- /.dashboard-stat -->
                                    </div>
									
                                    
                                </div>
<section class="section">
                            <div class="container-fluid">

                             

                                <div class="row">
                                    <div class="col-md-12">

                                        <div class="panel">
                                            <div class="panel-heading">
                                                <div class="panel-title">
                                                    <h5>Asignaciones Recientes</h5>
                                                </div>
                                            </div>
<?php if($msg){?>
<div class="alert alert-success left-icon-alert" role="alert">
 <strong>EXITOSO</strong><?php echo htmlentities($msg); ?>
 </div><?php } 
else if($error){?>
    <div class="alert alert-danger left-icon-alert" role="alert">
                                            <strong>ERROR!</strong> <?php echo htmlentities($error); ?>
                                        </div>
                                        <?php } ?>
                                            <div class="panel-body p-20">

                                                <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                                    <thead>
                                                       <tr>
                                                            <th>N °</th>
                                                            <th>N° Control </th>
                                                            <th>Equipo Asignado</th>
                                                            <th>Usuario Asignado</th>
                                                            <th>Fecha Registro</th>
                                                 
                                                       
															            
                                                        </tr>
                                                    </thead>
                                                    <tfoot>
                                                          <tr>
                                                            <th>N °</th>
                                                            <th>N° Control </th>
                                                            <th>Equipo Asignado</th>
                                                            <th>Usuario Asignado</th>
                                                            <th>Fecha Registro</th>
                                                     
                                              
															            
                                                        </tr>
                                                    </tfoot>
                                                    <tbody>
<?php $sql = "SELECT * 
from tblasig  join tblactivos 
on tblactivos.id=tblasig.ClassId inner join tblusuarios on tblusuarios.id=tblasig.usuarioID";
$query = $dbh->prepare($sql);
$query->execute();
$results=$query->fetchAll(PDO::FETCH_OBJ);
$cnt=1;
if($query->rowCount() > 0)
{
foreach($results as $result)
{   ?>
<tr>
 <td><?php echo htmlentities($cnt);?></td>
                                                             <td><?php echo htmlentities($result->number);?></td>
														
                                                            <td><?php echo htmlentities($result->descrip);?>&nbsp;  Etiqueta:<?php echo htmlentities($result->etiqueta);?></td>
                                                            <td><?php echo htmlentities($result->nombres);?>&nbsp;  Negocio:<?php echo htmlentities($result->negocio);?></td>
                                                            <td><?php echo htmlentities($result->fecha);?></td>
                                              
</tr>
<?php $cnt=$cnt+1;}} ?>
                                                       
                                                    
                                                    </tbody>
                                                </table>

                                         
                                                <!-- /.col-md-12 -->
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.col-md-6 -->

                                                               
                                                </div>
                                                <!-- /.col-md-12 -->
                                            </div>
                                        </div>
                                        <!-- /.panel -->
                                    </div>
                                    <!-- /.col-md-6 -->

                                </div>
                                <!-- /.row -->

                            </div>
                            <!-- /.container-fluid -->
                        </section>
                                <!-- /.row -->
                            </div>
                            <!-- /.container-fluid -->
                        </section>
                        <!-- /.section -->

                    </div>
                    <!-- /.main-page -->

                    
                </div>
                <!-- /.content-container -->
            </div>
            <!-- /.content-wrapper -->

    <?php include('includes/footer.php');?>

<?php } ?>
