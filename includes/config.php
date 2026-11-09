<<<<<<< HEAD
<?php 
// DB credentials.
define('DB_HOST','localhost');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','pdvsa_ait');
// Establish database connection.
try
{
$dbh = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME,DB_USER, DB_PASS,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
}
catch (PDOException $e)
{
exit("Error: " . $e->getMessage());
}
?>
=======
<?php
/**
 * Conexión PDO para SERVIUCIS.
 * Ajusta DB_USER y DB_PASS según las credenciales reales de MySQL/MariaDB.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'pdvsa_ait');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $dbh = new PDO($dsn, DB_USER, DB_PASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ));
} catch (PDOException $e) {
    error_log('Error de conexión a pdvsa_ait: ' . $e->getMessage());
    http_response_code(500);
    exit('No fue posible conectar con la base de datos. Verifica la configuración del servidor.');
}
?>
>>>>>>> main
