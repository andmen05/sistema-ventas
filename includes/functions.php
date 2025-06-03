<?php
// Incluir funciones de base de datos
require_once __DIR__ . '/db.php';

// Función para verificar permisos
function hasPermission($permission_name) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // El admin siempre tiene todos los permisos
    if ($_SESSION['role'] === 'admin') {
        return true;
    }
    
    // Verificar permiso específico
    $sql = "SELECT COUNT(*) as count 
           FROM users u
           JOIN roles r ON u.role_id = r.id
           JOIN role_permissions rp ON r.id = rp.role_id
           JOIN permissions p ON rp.permission_id = p.id
           WHERE u.id = ? AND p.name = ?";
    
    $result = fetchOne($sql, [$_SESSION['user_id'], $permission_name]);
    return $result['count'] > 0;
}

// Función para verificar múltiples permisos (cualquiera)
function hasAnyPermission($permissions) {
    if (!is_array($permissions)) {
        $permissions = [$permissions];
    }
    
    foreach ($permissions as $permission) {
        if (hasPermission($permission)) {
            return true;
        }
    }
    return false;
}

// Función para requerir permiso
function requirePermission($permission) {
    if (!isset($_SESSION['user_id'])) {
        error_log("requirePermission: No hay sesión iniciada");
        header('Location: /sistema-ventas/pages/login.php');
        exit();
    }

    error_log("requirePermission: Verificando permiso '$permission' para usuario {$_SESSION['username']} (ID: {$_SESSION['user_id']}) con rol {$_SESSION['role']}");
    
    if (!hasPermission($permission)) {
        error_log("requirePermission: Permiso denegado '$permission' para usuario {$_SESSION['username']}");
        header('Location: /sistema-ventas/pages/unauthorized.php');
        exit();
    }

    error_log("requirePermission: Permiso '$permission' concedido para usuario {$_SESSION['username']}");
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para redirigir si no está logueado
function checkLogin() {
    if (!isLoggedIn()) {
        header('Location: /sistema-ventas/pages/login.php');
        exit();
    }
}

// Función para verificar permiso y redirigir si no lo tiene
function checkPermission($permission) {
    if (!hasPermission($permission)) {
        $_SESSION['message'] = getAlert('danger', 'No tienes permiso para acceder a esta función');
        header('Location: /sistema-ventas/pages/dashboard.php');
        exit();
    }
}

// Función para obtener todos los permisos de un usuario
function getUserPermissions($userId) {
    return fetchAll("SELECT DISTINCT p.name, p.description 
                    FROM permissions p 
                    JOIN role_permissions rp ON p.id = rp.permission_id 
                    JOIN users u ON rp.role_id = u.role_id 
                    WHERE u.id = ?", [$userId]);
}

// Función para limpiar datos de entrada
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para generar número de factura
function generateInvoiceNumber() {
    global $conn;
    $year = date('Y');
    $month = date('m');
    
    // Obtener el último número de factura del mes actual
    $result = fetchOne("SELECT MAX(SUBSTRING_INDEX(invoice_number, '-', -1)) as last_number 
                       FROM sales 
                       WHERE invoice_number LIKE ?",["F$year$month-%"]);
    
    $last_number = $result['last_number'] ?? 0;
    $next_number = str_pad($last_number + 1, 4, '0', STR_PAD_LEFT);
    
    return "F$year$month-$next_number";
}

// Función para formatear precio
function formatPrice($price) {
    // Formatear precio en pesos colombianos (sin decimales, punto como separador de miles)
    if (!is_numeric($price)) {
        return '0';
    }
    return number_format((float)$price, 0, '', '.');
}

// Función para obtener mensaje de alerta
function getAlert($type, $message) {
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>";
}
