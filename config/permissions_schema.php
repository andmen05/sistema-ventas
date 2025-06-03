<?php
require_once 'database.php';

// Eliminar tablas si existen
$conn->query("DROP TABLE IF EXISTS role_permissions");
$conn->query("DROP TABLE IF EXISTS permissions");

// Crear tabla de permisos
$sql = "CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    module VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Crear tabla de roles_permissions (relación muchos a muchos)
$sql = "CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT,
    permission_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
)";
$conn->query($sql);

// Limpiar datos existentes
$conn->query("DELETE FROM role_permissions");
$conn->query("DELETE FROM permissions");

// Insertar permisos básicos
$basic_permissions = [
    // Usuarios y Roles
    ['name' => 'manage_users', 'description' => 'Gestionar usuarios', 'module' => 'admin'],
    ['name' => 'manage_roles', 'description' => 'Gestionar roles', 'module' => 'admin'],
    
    // Productos
    ['name' => 'view_products', 'description' => 'Ver productos', 'module' => 'products'],
    ['name' => 'create_products', 'description' => 'Crear productos', 'module' => 'products'],
    ['name' => 'edit_products', 'description' => 'Editar productos', 'module' => 'products'],
    ['name' => 'delete_products', 'description' => 'Eliminar productos', 'module' => 'products'],
    
    // Ventas
    ['name' => 'view_sales', 'description' => 'Ver ventas', 'module' => 'sales'],
    ['name' => 'create_sales', 'description' => 'Crear ventas', 'module' => 'sales'],
    ['name' => 'cancel_sales', 'description' => 'Anular ventas', 'module' => 'sales'],
    
    // Clientes
    ['name' => 'view_customers', 'description' => 'Ver clientes', 'module' => 'customers'],
    ['name' => 'create_customers', 'description' => 'Crear clientes', 'module' => 'customers'],
    ['name' => 'edit_customers', 'description' => 'Editar clientes', 'module' => 'customers'],
    ['name' => 'delete_customers', 'description' => 'Eliminar clientes', 'module' => 'customers'],
    
    // Reportes
    ['name' => 'view_reports', 'description' => 'Ver reportes', 'module' => 'reports'],
    ['name' => 'export_reports', 'description' => 'Exportar reportes', 'module' => 'reports']
];

// Insertar permisos
foreach ($basic_permissions as $perm) {
    $stmt = $conn->prepare("INSERT IGNORE INTO permissions (name, description, module) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $perm['name'], $perm['description'], $perm['module']);
    $stmt->execute();
}

// Crear roles predefinidos
$predefined_roles = [
    ['name' => 'admin', 'description' => 'Administrador con acceso total'],
    ['name' => 'supervisor', 'description' => 'Supervisor con acceso a reportes y monitoreo'],
    ['name' => 'vendedor', 'description' => 'Vendedor con acceso a ventas y clientes'],
    ['name' => 'inventario', 'description' => 'Encargado de inventario']
];

// Insertar roles predefinidos
foreach ($predefined_roles as $role) {
    $stmt = $conn->prepare("INSERT IGNORE INTO roles (name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $role['name'], $role['description']);
    $stmt->execute();
}

// Asignar permisos a roles
$role_permissions = [
    // Admin tiene todos los permisos
    'admin' => array_column($basic_permissions, 'name'),
    
    // Supervisor tiene acceso a reportes y visualización
    'supervisor' => [
        'view_products', 'view_sales', 'view_customers',
        'view_reports', 'export_reports'
    ],
    
    // Vendedor tiene acceso a ventas y clientes
    'vendedor' => [
        'view_products', 
        'view_sales', 'create_sales',
        'view_customers', 'create_customers', 'edit_customers'
    ],
    
    // Inventario tiene acceso a productos
    'inventario' => [
        'view_products', 'create_products', 'edit_products', 'delete_products'
    ]
];

// Asignar permisos a cada rol
foreach ($role_permissions as $role_name => $permissions) {
    // Obtener ID del rol
    $role = $conn->query("SELECT id FROM roles WHERE name = '$role_name' LIMIT 1")->fetch_assoc();
    if (!$role) continue;
    
    foreach ($permissions as $permission_name) {
        // Obtener ID del permiso
        $permission = $conn->query("SELECT id FROM permissions WHERE name = '$permission_name' LIMIT 1")->fetch_assoc();
        if (!$permission) continue;
        
        // Asignar permiso al rol
        $stmt = $conn->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $role['id'], $permission['id']);
        $stmt->execute();
    }
}

// Asegurarse de que el admin tenga todos los permisos
$admin_role = $conn->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1")->fetch_assoc();
$permissions = $conn->query("SELECT id FROM permissions");

// Asignar todos los permisos al admin
while ($perm = $permissions->fetch_assoc()) {
    $stmt = $conn->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $admin_role['id'], $perm['id']);
    $stmt->execute();
}

echo "Sistema de permisos configurado correctamente.";
?>
