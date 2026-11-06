
<?php
session_start();
error_reporting(0);
include('includes/config.php');
if(strlen($_SESSION['alogin'])=="")
    {   
    header("Location: index.php"); 
    }
    else{

?>

<link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css"/>
            <!-- ========== TOP NAVBAR ========== -->
   <?php include('includes/topbar.php');?> 
            <!-- ========== WRAPPER FOR BOTH SIDEBARS & MAIN CONTENT ========== -->
            <div class="content-wrapper">
                <div class="content-container">
<?php include('includes/leftbar.php');?>  

                    <div class="main-page">
                        <div class="container-fluid">
                            <div class="row page-title-div">
                                <div class="col-md-6">
                                    <h2 class="title">Gestion de Asignaciones</h2>
                                
                                </div>
                                
                                <!-- /.col-md-6 text-right -->
                            </div>
                            <!-- /.row -->
                            <div class="row breadcrumb-div">
                                <div class="col-md-6">
                                    <ul class="breadcrumb">
            							<li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                                        <li> Asignaciones</li>
            							<li class="active">Gestion de Asignaciones</li>
            						</ul>
                                </div>
                             
                            </div>
                            <!-- /.row -->
                        </div>
                        <!-- /.container-fluid -->

                        <section class="section">
                            <div class="container-fluid">

                             

                                <div class="row">
                                    <div class="col-md-12">

                                        <div class="panel">
                                            <div class="panel-heading">
                                                <div class="panel-title">
                                                    <h5>Registro de Asignaciones</h5>
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
                                            <div class="panel-body p-20">

                                                <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                                    <thead>
                                                           <tr>
                                                   
                                                            <th>N° de Control</th>
															
                                                            <th>Equipo Asignado</th>
															<th>Usuario Asignado</th>
                                                       
															 <th>Actualizacion</th>
                                                            <th>Estatus</th>
                                                                 <th>Editar</th>
													
															      
                                                        </tr>
                                                    </thead>
                                                    <tfoot>
                                                        <tr>
                                                   
                                                            <th>N° de Control</th>
													
                                                            <th>Equipo Asignado</th>
															<th>Usuario Asignado</th>
                                                        
															 <th>Actualizacion</th>
                                                            <th>Estatus</th>
                                                                 <th>Editar</th>
														      
													
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

                                                            <td><?php echo htmlentities($result->number);?></td>
                                                    
                                                            <td><?php echo htmlentities($result->descrip);?>(<?php echo htmlentities($result->etiqueta);?>)</td>
                                                            <td><?php echo htmlentities($result->nombres);?>(<?php echo htmlentities($result->indicador);?>)</td>

												
															<td><?php echo htmlentities($result->RegDate);?></td>
                                                             <td><?php if($result->Status==1){
echo htmlentities('Activo');
}
else{
   echo htmlentities('Inactivo'); 
}
                                                                ?></td>
<td>
<a href="edit-prueba.php?stid=<?php echo htmlentities($result->StudentId);?>" class="btn btn-info"><i class="fa fa-edit" title="Edit Record"></i> </a> 

</td>

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
                        <!-- /.section -->

                    </div>
                    <!-- /.main-page -->

                    

                </div>
                <!-- /.content-container -->
            </div>
            <!-- /.content-wrapper -->
<?php include('includes/footer.php');?>

<!--  Author Name: Mayuri K. 
 for any PHP, Codeignitor, Laravel OR Python work contact me at mayuri.infospace@gmail.com  
 Visit website : www.mayurik.com -->  

        
<?php } ?>

