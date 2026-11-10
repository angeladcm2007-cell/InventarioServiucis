
<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (strlen($_SESSION['alogin']) == "") {
    header("Location: index.php");
    exit;
} else {
?>

<?php include('includes/topbar.php'); ?>

<link rel="stylesheet" type="text/css" href="assets/js/DataTables/datatables.min.css">

<style>
@media print {
    body * {
        visibility: hidden;
    }

    #example, #example * {
        visibility: visible;
    }

    #example {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    .btn, .panel-heading, .nav, footer, .footer,
    .dataTables_length, .dataTables_filter,
    .dataTables_info, .dataTables_paginate {
        display: none !important;
    }
}
</style>

<div class="content-wrapper">
    <div class="content-container">

        <!-- LEFTBAR -->
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
                            <li>
                                <a href="dashboard.php">
                                    <i class="fa fa-home"></i> Inicio
                                </a>
                            </li>
                            <li>Gestión de Activos</li>
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

                                    <div class="panel-btn" style="float: right;">

                                        <select id="filtroTipo"
                                            class="form-control"
                                            style="display: inline-block; width: 200px; margin-right: 10px;">

                                            <option value="">Todos los tipos</option>
                                            <option value="Equipo de cómputo">Equipo de cómputo</option>
                                            <option value="Impresora">Impresora</option>

                                        </select>

                                        <button onclick="imprimirFiltrado()" class="btn btn-default">
                                            <i class="fa fa-print"></i> Imprimir / PDF
                                        </button>

                                    </div>
                                </div>

                                <div class="panel-body p-20">

                                    <table id="example"
                                        class="display table table-striped table-bordered"
                                        cellspacing="0"
                                        width="100%">

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

                                            /*
                                             * CONSULTA PRINCIPAL
                                             * Ahora se consultan las tablas que
                                             * contienen los registros existentes.
                                             *
                                             * LEFT JOIN permite mostrar los
                                             * registros aunque todavía no exista
                                             * su ficha en la tabla activos.
                                             */

                                            $sql = "

                                                SELECT
                                                    ec.id AS id,
                                                    ec.activo_id,

                                                    'Equipo de cómputo' AS tipo_activo,

                                                    COALESCE(
                                                        NULLIF(a.descripcion, ''),
                                                        NULLIF(ec.descripcion_equipo, ''),
                                                        ec.tipo_equipo,
                                                        'No registrado'
                                                    ) AS descrip,

                                                    COALESCE(
                                                        NULLIF(a.codigo_placa, ''),
                                                        NULLIF(a.codigo_inventario, ''),
                                                        CONCAT('ACT-', ec.activo_id)
                                                    ) AS etiqueta,

                                                    COALESCE(a.marca, '') AS marca,
                                                    COALESCE(a.modelo, '') AS modelo

                                                FROM equipos_computo ec

                                                LEFT JOIN activos a
                                                    ON a.id = ec.activo_id


                                                UNION ALL


                                                SELECT
                                                    di.id AS id,
                                                    di.activo_id,

                                                    'Impresora' AS tipo_activo,

                                                    COALESCE(
                                                        NULLIF(a.descripcion, ''),
                                                        NULLIF(di.observaciones_suministro, ''),
                                                        di.tipo_impresora,
                                                        'No registrado'
                                                    ) AS descrip,

                                                    COALESCE(
                                                        NULLIF(a.codigo_placa, ''),
                                                        NULLIF(a.codigo_inventario, ''),
                                                        CONCAT('ACT-', di.activo_id)
                                                    ) AS etiqueta,

                                                    COALESCE(a.marca, '') AS marca,
                                                    COALESCE(a.modelo, '') AS modelo

                                                FROM dispositivos_impresion di

                                                LEFT JOIN activos a
                                                    ON a.id = di.activo_id

                                                ORDER BY activo_id DESC

                                            ";

                                            $query = $dbh->prepare($sql);
                                            $query->execute();

                                            $results = $query->fetchAll(PDO::FETCH_OBJ);

                                            $cnt = 1;

                                            foreach ($results as $result) {

                                            ?>

                                                <tr>

                                                    <td>
                                                        <?php echo htmlentities($cnt); ?>
                                                    </td>

                                                    <td>
                                                        <?php echo htmlentities($result->tipo_activo); ?>
                                                    </td>

                                                    <td>
                                                        <?php echo htmlentities($result->descrip ?: 'No registrado'); ?>
                                                    </td>

                                                    <td>
                                                        <?php echo htmlentities($result->etiqueta); ?>
                                                    </td>

                                                    <td>
                                                        <?php echo htmlentities($result->marca ?: 'No registrado'); ?>
                                                    </td>

                                                    <td>
                                                        <?php echo htmlentities($result->modelo ?: 'No registrado'); ?>
                                                    </td>

                                                    <td>
                                                        <button
                                                            type="button"
                                                            class="btn btn-default"
                                                            disabled
                                                            title="Este registro todavía no tiene una ficha principal en activos">

                                                            <i class="fa fa-lock"></i>

                                                        </button>
                                                    </td>

                                                </tr>

                                            <?php
                                                $cnt++;
                                            }

                                            ?>

                                        </tbody>

                                    </table>

                                    <?php if (empty($results)) { ?>
                                        <div class="alert alert-info">
                                            Todavía no hay activos registrados.
                                        </div>
                                    <?php } ?>

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

    // Inicializar DataTables
    var tabla = $('#example').DataTable();

    // Filtro por tipo de activo
    $('#filtroTipo').on('change', function () {

        tabla.column(1).search(this.value).draw();

    });

});

function imprimirFiltrado() {

    window.print();

}

</script>

<?php } ?>
