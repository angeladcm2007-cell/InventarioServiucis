<?php
/** Normalización y control centralizado de seriales y direcciones MAC. */
function invNormalizarSerial($valor) {
    $valor = strtoupper(preg_replace('/\s+/', '', trim((string)$valor)));
    if ($valor === '' || in_array($valor, array('N/A','NA','SIN SERIAL','SINSERIAL','NO TIENE','NOTIENE','000000','0000000','00000000','0000000000','123456789'), true)) return null;
    return $valor;
}
function invNormalizarMac($valor) {
    $original = strtoupper(trim((string)$valor));
    if ($original === '' || in_array($original, array('N/A','NA','SIN MAC','SINMAC','NO TIENE','NOTIENE','000000000000'), true)) return null;
    $normal = preg_replace('/[.\:\-\s]/', '', $original);
    if (!preg_match('/^[0-9A-F]{12}$/', $normal)) throw new RuntimeException('La dirección MAC no tiene un formato válido. Usa, por ejemplo, AA:BB:CC:DD:EE:FF.');
    return $normal;
}
function invValidarIdentificadores(PDO $dbh, $serial, $mac, $activoId = null) {
    $items = array();
    $s = invNormalizarSerial($serial); if ($s !== null) $items[] = array('SERIAL', $s, trim((string)$serial));
    $m = invNormalizarMac($mac); if ($m !== null) $items[] = array('MAC', $m, trim((string)$mac));
    $q = $dbh->prepare("SELECT ia.activo_id, ia.tipo_identificador, a.codigo_inventario FROM identificadores_activos ia INNER JOIN activos a ON a.id=ia.activo_id WHERE ia.tipo_identificador=:tipo AND ia.valor_normalizado=:valor AND (:excluir_nulo IS NULL OR ia.activo_id<>:excluir_id) LIMIT 1");
    foreach ($items as $item) {
        $q->execute(array(':tipo'=>$item[0], ':valor'=>$item[1], ':excluir_nulo'=>$activoId, ':excluir_id'=>$activoId));
        $found = $q->fetch(PDO::FETCH_ASSOC);
        if ($found) throw new RuntimeException('No se puede guardar: el '.$item[0].' '.$item[2].' ya está registrado en el activo '.$found['codigo_inventario'].'.');
    }
    return $items;
}
function invSincronizarIdentificadores(PDO $dbh, $activoId, $serial, $mac) {
    $items = array();
    $s = invNormalizarSerial($serial); if ($s !== null) $items[] = array('SERIAL',$s,trim((string)$serial));
    $m = invNormalizarMac($mac); if ($m !== null) $items[] = array('MAC',$m,trim((string)$mac));
    $del = $dbh->prepare('DELETE FROM identificadores_activos WHERE activo_id=:id'); $del->execute(array(':id'=>$activoId));
    $ins = $dbh->prepare('INSERT INTO identificadores_activos (activo_id,tipo_identificador,valor_original,valor_normalizado) VALUES (:id,:tipo,:original,:normal)');
    foreach ($items as $item) $ins->execute(array(':id'=>$activoId,':tipo'=>$item[0],':original'=>$item[2],':normal'=>$item[1]));
}
