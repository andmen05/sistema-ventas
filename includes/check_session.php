<?php
// Iniciar sesión solo si no está ya activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// Verificar si hay una sesión activa
if (!isset($_SESSION['user_id'])) {
    // No hay sesión, redirigir al login
    header('Location: /sistema-ventas/index.php');
    exit();
}

// Verificar si el usuario existe y está activo
$user = fetchOne("SELECT u.*, r.name as role_name 
                 FROM users u 
                 JOIN roles r ON u.role_id = r.id 
                 WHERE u.id = ? AND u.status = 'active'", 
                 [$_SESSION['user_id']]);

if (!$user) {
    // Usuario no existe o no está activo
    session_destroy();
    header('Location: /sistema-ventas/index.php?error=session_expired');
    exit();
}

// Actualizar datos de sesión
$_SESSION['username'] = $user['username'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['role'] = $user['role_name'];
?>
