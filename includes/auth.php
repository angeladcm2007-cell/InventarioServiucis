<?php
// Control centralizado de acceso SISAT. Roles: 1 Administrador, 2 Técnico, 3 Gerencia/Consulta.
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
function sisatLoggedIn() { return !empty($_SESSION['uid']) && !empty($_SESSION['alogin']); }
function sisatRoleId() { return (int)($_SESSION['rol_id'] ?? 0); }
function sisatRequireLogin() { if (!sisatLoggedIn()) { header('Location: index.php'); exit; } }
function sisatRequireRoles($roles) {
    sisatRequireLogin();
    if (!in_array(sisatRoleId(), array_map('intval', (array)$roles), true)) {
        http_response_code(403);
        exit('Acceso denegado: tu perfil no tiene permisos para realizar esta acción.');
    }
}
function sisatIsAdmin() { return sisatRoleId() === 1; }
function sisatCanWrite() { return in_array(sisatRoleId(), [1,2], true); }
?>
