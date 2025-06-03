<?php
require_once 'database.php';

echo "=== Verificando usuario admin ===\n";
$user = $conn->query("SELECT u.*, r.name as role_name 
                      FROM users u 
                      LEFT JOIN roles r ON u.role_id = r.id 
                      WHERE u.username = 'admin'")->fetch_assoc();

if ($user) {
    echo "Usuario admin encontrado:\n";
    echo "- ID: {$user['id']}\n";
    echo "- Role ID: {$user['role_id']}\n";
    echo "- Role Name: {$user['role_name']}\n";
    echo "- Status: {$user['status']}\n\n";
    
    echo "=== Verificando rol admin ===\n";
    $role = $conn->query("SELECT * FROM roles WHERE name = 'admin'")->fetch_assoc();
    if ($role) {
        echo "Rol admin encontrado:\n";
        echo "- ID: {$role['id']}\n";
        echo "- Name: {$role['name']}\n\n";
        
        echo "=== Verificando permisos del rol admin ===\n";
        $perms = $conn->query("SELECT p.name, p.description 
                              FROM role_permissions rp
                              JOIN permissions p ON rp.permission_id = p.id
                              WHERE rp.role_id = {$role['id']}");
        
        echo "Permisos asignados:\n";
        while ($perm = $perms->fetch_assoc()) {
            echo "- {$perm['name']}: {$perm['description']}\n";
        }
    } else {
        echo "ERROR: Rol admin no encontrado\n";
    }
} else {
    echo "ERROR: Usuario admin no encontrado\n";
}

// Verificar si el permiso manage_users existe
echo "\n=== Verificando permiso manage_users ===\n";
$perm = $conn->query("SELECT * FROM permissions WHERE name = 'manage_users'")->fetch_assoc();
if ($perm) {
    echo "Permiso encontrado:\n";
    echo "- ID: {$perm['id']}\n";
    echo "- Name: {$perm['name']}\n";
    echo "- Description: {$perm['description']}\n";
} else {
    echo "ERROR: Permiso manage_users no encontrado\n";
}
