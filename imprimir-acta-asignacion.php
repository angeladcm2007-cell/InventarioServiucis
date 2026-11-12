<?php
session_start();
require_once __DIR__ . '/includes/config.php';
if (empty($_SESSION['alogin']) || empty($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}
function h($v)
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
$id = (int)($_GET['id'] ?? 0);
$q = $dbh->prepare("SELECT x.*,a.numero_control,u.nombre_completo FROM actas_asignaciones x JOIN asignaciones a ON a.id=x.asignacion_id LEFT JOIN usuarios_sistema u ON u.id=x.generada_por WHERE x.id=?");
$q->execute(array($id));
$acta = $q->fetch(PDO::FETCH_ASSOC);
if (!$acta) {
    http_response_code(404);
    exit('Acta no encontrada.');
}
$d = json_decode($acta['datos_snapshot'], true);
if (!is_array($d)) $d = array();
$activo = $d['activo'] ?? array();
$detalle = $activo['detalle_tecnico'] ?? array();
$last = $activo['ultimo_mantenimiento'] ?? null;
function renderValue($v)
{
    if (is_array($v)) return '';
    return (string)($v ?? '');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title><?php echo h($acta['numero_acta']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #222;
            margin: 28px;
            font-size: 12px
        }

        .head {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 12px
        }

        .head h2 {
            margin: 4px
        }

        .meta {
            margin: 18px 0
        }

        .meta span {
            display: inline-block;
            margin-right: 24px
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0
        }

        th,
        td {
            border: 1px solid #777;
            padding: 7px;
            text-align: left;
            vertical-align: top
        }

        th {
            background: #eee;
            width: 28%
        }

        .section {
            font-weight: bold;
            background: #e9eef2;
            padding: 7px;
            margin-top: 16px
        }

        .signatures {
            display: flex;
            gap: 60px;
            margin-top: 70px
        }

        .sign {
            width: 45%;
            text-align: center;
            border-top: 1px solid #222;
            padding-top: 8px
        }

        .actions {
            margin-bottom: 18px
        }

        @media print {
            .actions {
                display: none
            }

            body {
                margin: 15mm
            }

            .section {
                background: #eee !important;
                print-color-adjust: exact
            }
        }
    </style>
</head>

<body>
    <<style>
        /* Contenedor de la botonera */
        .actas-acciones-header {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        margin-bottom: 20px;
        }

        /* Estilo base para todos los botones / enlaces de acción */
        .actas-acciones-header .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 500;
        font-family: inherit;
        text-decoration: none;
        border-radius: 6px;
        border: 1px solid transparent;
        cursor: pointer;
        transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        /* Botón Imprimir (Color principal - Azul corporativo) */
        .btn-print {
        background-color: #0284c7;
        color: #ffffff;
        }
        .btn-print:hover {
        background-color: #0369a1;
        }

        /* Botón Volver / Cargar PDF (Color secundario - Gris claro elegante) */
        .btn-secondary {
        background-color: #f1f5f9;
        color: #334155;
        border-color: #cbd5e1;
        }
        .btn-secondary:hover {
        background-color: #e2e8f0;
        border-color: #94a3b8;
        }

        /* Botón Cerrar (Color neutral / sutil) */
        .btn-close {
        background-color: #ffffff;
        color: #64748b;
        border-color: #cbd5e1;
        }
        .btn-close:hover {
        background-color: #f8fafc;
        color: #0f172a;
        border-color: #94a3b8;
        }

        /* Ocultar la botonera automáticamente al momento de imprimir */
        @media print {
        .actas-acciones-header {
        display: none !important;
        }
        }
        </style>

        <div class="actas-acciones-header">
            <!-- Botón de Imprimir -->
            <button onclick="window.print()" class="btn btn-print">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Imprimir acta
            </button>

            <!-- Botón Cerrar -->
            <button onclick="window.close()" class="btn btn-close">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
                Volver a gestión de Actas
            </button>
        </div>

        <!-- Encabezado del Acta -->
        <div class="head">
            <h2>SERVIUCIS S.A.S.</h2>
            <h3>ACTA DE <?php echo h($acta['tipo_acta']); ?> DE ACTIVO TECNOLÓGICO</h3>
            <strong><?php echo h($acta['numero_acta']); ?></strong>
        </div>
        <div class="meta"><span><b>Asignación:</b> <?php echo h($acta['numero_control']); ?></span><span><b>Versión:</b> <?php echo (int)$acta['version']; ?></span><span><b>Fecha de generación:</b> <?php echo h($acta['fecha_generacion']); ?></span></div>
        <div class="section">1. Identificación del activo</div>
        <table><?php foreach (array('codigo_inventario' => 'Código de inventario', 'placa' => 'Placa', 'categoria' => 'Categoría', 'descripcion' => 'Descripción', 'marca' => 'Marca', 'modelo' => 'Modelo', 'serial' => 'Número de serial', 'estado' => 'Estado del activo', 'sede' => 'Sede', 'ubicacion' => 'Ubicación física', 'area_inventario' => 'Área registrada') as $k => $label): ?><tr>
                    <th><?php echo h($label); ?></th>
                    <td><?php echo h($activo[$k] ?? 'No registrado'); ?></td>
                </tr><?php endforeach; ?></table>
        <div class="section">2. Características técnicas</div>
        <table><?php if ($detalle): foreach ($detalle as $k => $v) {
                        if ($k === 'id' || $k === 'activo_id' || $k === 'discos' || is_array($v) || $v === null || $v === '') continue; ?><tr>
                        <th><?php echo h(ucwords(str_replace('_', ' ', $k))); ?></th>
                        <td><?php echo nl2br(h($v)); ?></td>
                    </tr><?php }
                        if (!empty($detalle['discos'])): ?><tr>
                        <th>Discos del servidor</th>
                        <td><?php foreach ($detalle['discos'] as $disco): ?>Disco <?php echo h($disco['numero_disco']); ?>: <?php echo h($disco['tipo_disco'] . ' ' . $disco['capacidad'] . ' ' . $disco['modelo'] . ' Serial ' . $disco['numero_serial'] . ' RAID ' . $disco['configuracion_raid']); ?><br><?php endforeach; ?></td>
                    </tr><?php endif;
                    else: ?><tr>
                    <td colspan="2">No hay características técnicas adicionales registradas.</td>
                </tr><?php endif; ?></table>
        <div class="section">3. Información de entrega / devolución</div>
        <table>
            <tr>
                <th>Funcionario receptor</th>
                <td><?php echo h($d['funcionario_receptor'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>Área o departamento</th>
                <td><?php echo h($d['area_departamento'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>Responsable de Sistemas</th>
                <td><?php echo h($d['responsable_sistemas'] ?? $d['responsable_recibe'] ?? ''); ?></td>
            </tr>
            <tr>
                <th>Fecha de asignación</th>
                <td><?php echo h($d['fecha_asignacion'] ?? ''); ?></td>
            </tr><?php if ($acta['tipo_acta'] === 'DEVOLUCION'): ?><tr>
                    <th>Fecha de devolución</th>
                    <td><?php echo h($d['fecha_devolucion'] ?? ''); ?></td>
                </tr>
                <tr>
                    <th>Estado físico al devolver</th>
                    <td><?php echo h($d['estado_fisico_devolucion'] ?? ''); ?></td>
                </tr>
                <tr>
                    <th>Observaciones de devolución</th>
                    <td><?php echo nl2br(h($d['observaciones_devolucion'] ?? '')); ?></td>
                </tr><?php else: ?><tr>
                    <th>Observaciones</th>
                    <td><?php echo nl2br(h($d['observaciones'] ?? '')); ?></td>
                </tr><?php endif; ?>
        </table>
        <div class="section">4. Último mantenimiento registrado</div>
        <table><?php if ($last): ?><tr>
                    <th>Tipo</th>
                    <td><?php echo h($last['tipo_mantenimiento']); ?></td>
                </tr>
                <tr>
                    <th>Fecha</th>
                    <td><?php echo h($last['fecha']); ?></td>
                </tr>
                <tr>
                    <th>Resultado</th>
                    <td><?php echo h($last['resultado']); ?></td>
                </tr>
                <tr>
                    <th>Trabajo realizado</th>
                    <td><?php echo nl2br(h($last['trabajo_realizado'])); ?></td>
                </tr>
                <tr>
                    <th>Observaciones</th>
                    <td><?php echo nl2br(h($last['observaciones'])); ?></td>
                </tr><?php else: ?><tr>
                    <td colspan="2">No registra mantenimientos finalizados o programados con fecha.</td>
                </tr><?php endif; ?>
        </table>
        <p>Mediante la firma de este documento, las personas intervinientes dejan constancia de la entrega o devolución del activo descrito y de las condiciones aquí registradas.</p>
        <div class="signatures">
            <div class="sign"><b><?php echo h($d['funcionario_receptor'] ?? 'Funcionario receptor'); ?></b><br>Recibe / entrega el activo<br>Identificación: __________________</div>
            <div class="sign"><b><?php echo h($d['responsable_sistemas'] ?? $d['responsable_recibe'] ?? 'Responsable de Sistemas'); ?></b><br>Departamento de Sistemas<br>Identificación: __________________</div>
        </div>
        <p style="margin-top:35px;font-size:10px">Documento generado por el sistema de inventario tecnológico SERVIUCIS. La copia firmada debe cargarse al expediente digital de la asignación.</p>
</body>

</html>