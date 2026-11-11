</div>

<!-- ========== COMMON JS FILES ========== -->
<script src="assets/js/jquery/jquery-2.2.4.min.js"></script>
<!-- <script src="assets/js/jquery/jquery-2.2.4.init.js"></script> -->
<script src="assets/js/jquery-ui/jquery-ui.min.js"></script>
<script src="assets/js/bootstrap/bootstrap.min.js"></script>
<script src="assets/js/lobipanel/lobipanel.min.js"></script>
<script src="assets/js/pace/pace.min.js"></script>
<script src="assets/js/iscroll/iscroll.js"></script>

<!-- ========== DATATABLES Y EXPORTACIÓN ========== -->
<script src="assets/js/DataTables/datatables.min.js"></script>

<script src="assets/js/DataTables/JSZip-2.5.0/jszip.min.js"></script>
<script src="assets/js/DataTables/pdfmake-0.1.18/build/pdfmake.min.js"></script>
<script src="assets/js/DataTables/pdfmake-0.1.18/build/vfs_fonts.js"></script>

<script src="assets/js/DataTables/Buttons-1.2.4/js/dataTables.buttons.min.js"></script>
<script src="assets/js/DataTables/Buttons-1.2.4/js/buttons.bootstrap.min.js"></script>
<script src="assets/js/DataTables/Buttons-1.2.4/js/buttons.html5.min.js"></script>
<script src="assets/js/DataTables/Buttons-1.2.4/js/buttons.print.min.js"></script>

<!-- ========== PAGE JS FILES ========== -->
<script src="assets/js/prism/prism.js"></script>
<script src="assets/js/waypoint/waypoints.min.js"></script>
<script src="assets/js/counterUp/jquery.counterup.min.js"></script>

<script src="assets/js/amcharts/amcharts.js"></script>
<script src="assets/js/amcharts/serial.js"></script>
<script src="assets/js/amcharts/plugins/export/export.min.js"></script>
<link rel="stylesheet" href="assets/js/amcharts/plugins/export/export.css" type="text/css" media="all" />
<script src="assets/js/amcharts/themes/light.js"></script>

<!-- <script src="assets/js/toastr/toastr.min.js"></script> -->
<script src="assets/js/icheck/icheck.min.js"></script>

<!-- ========== THEME JS ========== -->
<script src="assets/js/main.js"></script>
<script src="assets/js/production-chart.js"></script>
<script src="assets/js/traffic-chart.js"></script>
<script src="assets/js/task-list.js"></script>

<!-- ========== DATATABLES ========== -->
<script>
    $(document).ready(function() {

        // Configuración general en español
        var idiomaEspanol = {
            "decimal": "",
            "emptyTable": "No hay información disponible en la tabla",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros en total)",
            "lengthMenu": "Mostrar _MENU_ registros",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "searchPlaceholder": "Escriba para buscar...",
            "zeroRecords": "No se encontraron resultados",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "aria": {
                "sortAscending": ": activar para ordenar ascendentemente",
                "sortDescending": ": activar para ordenar descendentemente"
            }
        };

        // ========== TABLA PRINCIPAL ==========
        if ($('#example').length && !$.fn.DataTable.isDataTable('#example')) {

            // Solo activa exportación en las tablas marcadas
            var permiteExportar = $('#example').attr('data-exportar') === '1';

            var botonesExportacion = [];

            if (permiteExportar) {
                botonesExportacion = [{
                        extend: 'print',
                        text: '<i class="fa fa-print"></i> Imprimir',
                        title: 'Inventario de activos',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<i class="fa fa-file-pdf-o"></i> Guardar PDF',
                        title: 'Inventario de activos',
                        filename: 'Inventario_de_activos',
                        orientation: 'landscape',
                        pageSize: 'A3',
                        exportOptions: {
                            columns: ':visible'
                        },
                        customize: function(doc) {
                            doc.defaultStyle.fontSize = 7;
                            doc.styles.tableHeader.fontSize = 8;
                            doc.styles.title.fontSize = 14;
                            doc.pageMargins = [15, 20, 15, 20];
                        }
                    }
                ];
            }

            var tabla = $('#example').DataTable({
                language: idiomaEspanol,

                // Conserva el selector de cantidad de registros

                dom: permiteExportar ?
                    '<"row"<"col-sm-6"l><"col-sm-6 text-right"B>>frtip' :
                    'lfrtip',

                buttons: botonesExportacion,

                pageLength: 10,

                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Todos"]
                ],

                ordering: true,
                searching: true,
                responsive: false
            });

            // ========== FILTRO POR TIPO DE ACTIVO ==========
            if ($('#filtroTipo').length) {

                $('#filtroTipo').on('change', function() {

                    var categoria = $(this).val();

                    if (categoria) {
                        var valorSeguro = $.fn.dataTable.util.escapeRegex(categoria);

                        // La columna 2 corresponde al tipo de activo
                        tabla.column(2).search(
                            '^' + valorSeguro + '$',
                            true,
                            false
                        ).draw();

                    } else {
                        tabla.column(2).search('').draw();
                    }

                });

            }

        }

        // ========== SEGUNDA TABLA ==========
        if ($('#example2').length && !$.fn.DataTable.isDataTable('#example2')) {

            $('#example2').DataTable({
                language: idiomaEspanol,
                scrollY: "300px",
                scrollCollapse: true,
                paging: false
            });

        }

        // ========== TERCERA TABLA ==========
        if ($('#example3').length && !$.fn.DataTable.isDataTable('#example3')) {

            $('#example3').DataTable({
                language: idiomaEspanol
            });

        }

    });
</script>

<!-- ========== MAYÚSCULAS EN BÚSQUEDA DATATABLES ========== -->
<script>
    document.addEventListener('input', function(e) {

        if (e.target.matches('.dataTables_filter input[type="search"]')) {

            var campo = e.target;
            var inicio = campo.selectionStart;
            var fin = campo.selectionEnd;

            var nuevoValor = campo.value.toLocaleUpperCase('es-CO');

            if (campo.value !== nuevoValor) {
                campo.value = nuevoValor;

                if (inicio !== null && fin !== null) {
                    campo.setSelectionRange(inicio, fin);
                }
            }

        }

    }, true);

    // Mostrar visualmente las letras en mayúsculas
    $(document).on('focus', '.dataTables_filter input[type="search"]', function() {
        $(this).css('text-transform', 'uppercase');
    });
</script>

<!-- ========== DASHBOARD COUNTERS AND NOTIFICATIONS ========== -->
<script>
    $(function() {

        // Contadores del panel principal
        if ($.fn.counterUp) {
            $('.counter').counterUp({
                delay: 10,
                time: 1000
            });
        }

        // Notificación de bienvenida
        if (typeof toastr !== 'undefined') {

            toastr.options = {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": false,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };

            toastr["success"]("Welcome to student Result Management System!");

        }

    });
</script>

<!-- ========== SELECT2 ========== -->
<script>
    $(function($) {

        if ($.fn.select2) {

            $(".js-states").select2();

            $(".js-states-limit").select2({
                maximumSelectionLength: 2
            });

            $(".js-states-hide").select2({
                minimumResultsForSearch: Infinity
            });

        }

    });
</script>

<!-- ========== MAYÚSCULAS AUTOMÁTICAS EN CAMPOS ========== -->
<script>
    $(document).ready(function() {

        function convertirMayusculas(elemento) {

            var valor = elemento.value;

            if (!valor) {
                return;
            }

            var inicio = elemento.selectionStart;
            var fin = elemento.selectionEnd;

            var nuevoValor = valor.toLocaleUpperCase('es-CO');

            if (valor !== nuevoValor) {

                elemento.value = nuevoValor;

                if (inicio !== null && fin !== null) {
                    elemento.setSelectionRange(inicio, fin);
                }

            }

        }

        // Campos de texto permitidos
        var camposTexto = 'input[type="text"], input:not([type]), textarea';

        // Convertir los campos que ya contienen información
        $(camposTexto).each(function() {
            convertirMayusculas(this);
        });

        // Convertir automáticamente al escribir
        $(document).on('input', camposTexto, function() {
            convertirMayusculas(this);
        });

        // Convertir también el texto pegado
        $(document).on('paste', camposTexto, function() {

            var elemento = this;

            setTimeout(function() {
                convertirMayusculas(elemento);
            }, 0);

        });

    });
</script>

<!-- ========== GOOGLE TRANSLATE ========== -->
<script type="text/javascript">
    function googleTranslateElementInit() {

        new google.translate.TranslateElement({
                pageLanguage: 'en'
            },
            'google_translate_element'
        );

    }
</script>

<script
    type="text/javascript"
    src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit">
</script>

<style>
    .goog-logo-link {
        display: none !important;
    }

    .goog-te-gadget {
        color: transparent;
    }

    .goog-te-gadget .goog-te-combo {
        margin: 0;
        padding: 8px;
        color: #000;
    }

    #google_translate_element {
        padding-top: 11px;
        color: #000;
        position: absolute;
        right: 20px;
    }
</style>

</body>

</html>