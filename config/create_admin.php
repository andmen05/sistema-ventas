<?php
require_once 'database.php';

// Verificar si existe el rol de admin
$admin_role = $conn->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1")->fetch_assoc();
if (!$admin_role) {
    echo "Error: El rol de administrador no existe. Por favor, ejecute primero permissions_schema.php\n";
    exit;
}

// Datos del usuario administrador
$admin_user = [
    'username' => 'admin',
    'password' => password_hash('admin123', PASSWORD_DEFAULT),
    'full_name' => 'Administrador Principal',
    'role_id' => $admin_role['id'],
    'status' => 'active'
];

// Verificar si el usuario admin ya existe
$existing_admin = $conn->query("SELECT id FROM users WHERE username = 'admin'")->fetch_assoc();

if ($existing_admin) {
    // Actualizar el usuario existente
    $stmt = $conn->prepare("UPDATE users SET password = ?, full_name = ?, role_id = ?, status = ? WHERE username = 'admin'");
    $stmt->bind_param("ssis", $admin_user['password'], $admin_user['full_name'], $admin_user['role_id'], $admin_user['status']);
    $stmt->execute();
    echo "Usuario administrador actualizado con éxito.\n";
} else {
    // Crear nuevo usuario admin
    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role_id, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $admin_user['username'], $admin_user['password'], $admin_user['full_name'], $admin_user['role_id'], $admin_user['status']);
    $stmt->execute();
    echo "Usuario administrador creado con éxito.\n";
}

echo "\nCredenciales de acceso:\n";
echo "Usuario: admin\n";
echo "Contraseña: admin123\n";
