
<?php
session_start();
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['alogin']) || empty($_SESSION['uid'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Sesión no válida']);
  exit;
}

$q = trim((string)($_GET['q'] ?? ''));

$longitud = function_exists('mb_strlen')
  ? mb_strlen($q, 'UTF-8')
  : strlen($q);

if ($longitud < 2) {
  echo json_encode(['results' => []], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $sql = "SELECT
                a.id,
                a.codigo_inventario,
                a.codigo_placa,
                a.marca,
                a.modelo,
                a.numero_serial,
                COALESCE(NULLIF(a.descripcion, ''), c.nombre) AS descripcion,
                c.nombre AS categoria,
                s.nombre AS sede,
                u.nombre AS ubicacion,
                u.area_departamento AS area_departamento,
                a.estado,
                CASE
    WHEN EXISTS (
        SELECT 1
        FROM asignaciones x
        WHERE x.activo_id = a.id
          AND x.estado_asignacion IN ('ACTIVA', 'PENDIENTE_FIRMA')
          AND NOT EXISTS (
              SELECT 1
              FROM actas_asignaciones d
              WHERE d.asignacion_id = x.id
                AND d.tipo_acta = 'DEVOLUCION'
                AND d.estado_documental = 'FIRMADA'
          )
    ) THEN 0
    ELSE 1
END AS disponible
            FROM activos a
            INNER JOIN categorias_activos c ON c.id = a.categoria_id
            LEFT JOIN ubicaciones u ON u.id = a.ubicacion_id
            LEFT JOIN sedes s ON s.id = u.sede_id
            WHERE (a.estado IS NULL OR a.estado <> 'DADO_DE_BAJA_OBSOLETO')
              AND (
                    a.codigo_inventario LIKE :q1
                 OR a.codigo_placa LIKE :q2
                 OR a.marca LIKE :q3
                 OR a.modelo LIKE :q4
                 OR a.numero_serial LIKE :q5
                 OR a.descripcion LIKE :q6
                 OR c.nombre LIKE :q7
                 OR s.nombre LIKE :q8
                 OR u.nombre LIKE :q9
                 OR u.area_departamento LIKE :q10
              )
            ORDER BY disponible DESC, a.codigo_inventario ASC, a.id ASC";

  $st = $dbh->prepare($sql);

  $like = '%' . $q . '%';

  $params = [
    ':q1',
    ':q2',
    ':q3',
    ':q4',
    ':q5',
    ':q6',
    ':q7',
    ':q8',
    ':q9',
    ':q10'
  ];

  foreach ($params as $param) {
    $st->bindValue($param, $like, PDO::PARAM_STR);
  }

  $st->execute();

  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  foreach ($rows as &$row) {
    $row['id'] = (int)$row['id'];
    $row['disponible'] = (bool)$row['disponible'];
  }

  unset($row);

  echo json_encode(
    [
      'results' => $rows,
      'total' => count($rows)
    ],
    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
  );
} catch (Throwable $e) {
  error_log('Buscar activos asignación: ' . $e->getMessage());

  http_response_code(500);

  echo json_encode([
    'error' => 'No fue posible consultar los activos.'
  ]);
}
