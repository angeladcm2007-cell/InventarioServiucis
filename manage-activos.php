<?php
session_start();
error_reporting(0);
include('includes/config.php');
if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
} else {
?>
    <?php include('includes/topbar.php'); ?>
<link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css">

<style>
/* ==========================================
   TIPOGRAFÍA DE LA TABLA - VER ACTIVOS
   Mantener la fuente Montserrat del sistema
========================================== */

/* Controles de DataTables */
#example_wrapper,
#example_wrapper label,
#example_wrapper .dataTables_info,
#example_wrapper .dataTables_paginate,
#example_wrapper .paginate_button,
#example_wrapper .dataTables_length,
#example_wrapper .dataTables_filter {
    font-family: "Montserrat", sans-serif !important;
    font-weight: 400;
    color: #494949;
    font-size: inherit;
}

/* Campos de búsqueda y selección */
#example_wrapper input,
#example_wrapper select {
    font-family: "Montserrat", sans-serif !important;
    font-size: 13px;
    font-weight: 400;
    color: #494949;
}

/* Botones de paginación */
#example_wrapper .paginate_button {
    font-weight: 500;
}

/* Texto de información de registros */
#example_wrapper .dataTables_info {
    font-size: 12px;
}

/* Encabezados y contenido de la tabla */
#example {
    font-family: "Montserrat", sans-serif;
}

#example th,
#example td {
    font-family: "Montserrat", sans-serif;
    font-size: 13px;
}
</style>

<style>
@media print {
    body * { visibility: hidden; }
    #example, #example * { visibility: visible; }
    #example { position: absolute; left: 0; top: 0; width: 100%; }
    .btn, .panel-heading, .nav, footer, .footer,
    .dataTables_length, .dataTables_filter, .dataTables_info,
    .dataTables_paginate { display: none !important; }
}
</style>
<div class="content-wrapper">
                <div class="content-container">
                    <!-- ========== LEFTBAR ========== -->
                    <?php include('includes/leftbar.php'); ?>

                    <div class="main-page">
                        <div class="container-fluid">
                            <div class="row page-title-div">
                                <div class="col-md-6">
                                    <hr>
                                    <h2 class="title">Gestión de Activos</h2>
                                </div>
                            </div>
                            <div class="row breadcrumb-div">
                                <div class="col-md-6">
                                    <ul class="breadcrumb">
                                        <li><a href="dashboard.php"><i class="fa fa-home"></i> Inicio</a></li>
                                        <li> Gestión de Activos</li>
                                        <li class="active">Ver Activos</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <section class="section">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="panel">
                                            <div class="panel-heading">
                                                <div class="panel-title" style="display: inline-block;">
                                                    <h5>Ver información de Activos</h5>
                                                </div>

                                                <!-- Controles de Filtro e Impresión -->
                                                <div class="panel-btn" style="float: right;">
                                                    <!-- Selector para filtrar por Tipo de Activo -->
                                                    <select id="filtroTipo" class="form-control" style="display: inline-block; width: 200px; margin-right: 10px;">
                                                        <option value="">Todos los tipos</option>
                                                        <?php
                                                        $sqlTipos = "SELECT DISTINCT tipo_activo FROM tblactivos";
                                                        $queryTipos = $dbh->prepare($sqlTipos);
                                                        $queryTipos->execute();
                                                        $tipos = $queryTipos->fetchAll(PDO::FETCH_OBJ);
                                                        foreach ($tipos as $t) {
                                                            echo '<option value="' . htmlentities($t->tipo_activo) . '">' . htmlentities($t->tipo_activo) . '</option>';
                                                        }
                                                        ?>
                                                    </select>

                                                    <!-- Botón de Impresión -->
                                                    <button onclick="imprimirFiltrado()" class="btn btn-default">
                                                        <i class="fa fa-print"></i> Imprimir / PDF
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="panel-body p-20">
                                                <table id="example" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Tipo de Activo</th>
                                                            <th>Descripción</th>
                                                            <th>Etiqueta</th>
                                                            <th>Marca</th>
                                                            <th>Modelo</th>
                                                            <th>Modificar</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $sql = "SELECT * from tblactivos";
                                                        $query = $dbh->prepare($sql);
                                                        $query->execute();
                                                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                                                        $cnt = 1;
                                                        if ($query->rowCount() > 0) {
                                                            foreach ($results as $result) {
                                                        ?>
                                                                <tr>
                                                                    <td><?php echo htmlentities($cnt); ?></td>
                                                                    <td><?php echo htmlentities($result->tipo_activo); ?></td>
                                                                    <td><?php echo htmlentities($result->descrip); ?></td>
                                                                    <td><?php echo htmlentities($result->etiqueta); ?></td>
                                                                    <td><?php echo htmlentities($result->marca); ?></td>
                                                                    <td><?php echo htmlentities($result->modelo); ?></td>
                                                                    <td>
                                                                        <a href="edit-activos.php?id=<?php echo htmlentities($result->id); ?>" class="btn btn-info"><i class="fa fa-edit" title="Edit Record"></i></a>
                                                                    </td>
                                                                </tr>
                                                        <?php
                                                                $cnt = $cnt + 1;
                                                            }
                                                        }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
            <?php include('includes/footer.php'); ?>
<script>
$(function () {
    // Reutilizar la instancia inicializada en includes/footer.php.
    var tabla = $('#example').DataTable();

    $('#filtroTipo').on('change', function () {
        tabla.column(1).search(this.value).draw();
    });
});

function imprimirFiltrado() {
    window.print();
}
</script>
<?php } ?>
