<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/check_session.php';

// Verificar permisos
requirePermission('manage_users');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'create':
            // Validar datos
            $username = cleanInput($_POST['username']);
            $password = $_POST['password'];
            $fullName = cleanInput($_POST['full_name']);
            $email = cleanInput($_POST['email']);
            $roleId = (int)$_POST['role_id'];
            $status = $_POST['status'];
            
            // Verificar si el usuario ya existe
            $exists = fetchOne("SELECT 1 FROM users WHERE username = ?", [$username]);
            if ($exists) {
                $_SESSION['message'] = getAlert('danger', 'El nombre de usuario ya existe');
                header('Location: users.php');
                exit();
            }
            
            // Crear usuario
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            query("INSERT INTO users (username, password, full_name, email, role_id, status) 
                   VALUES (?, ?, ?, ?, ?, ?)", 
                   [$username, $hashedPassword, $fullName, $email, $roleId, $status]);
            
            $_SESSION['message'] = getAlert('success', 'Usuario creado correctamente');
            header('Location: users.php');
            exit();
            
        case 'update':
            $userId = (int)$_POST['user_id'];
            $fullName = cleanInput($_POST['full_name']);
            $email = cleanInput($_POST['email']);
            $roleId = (int)$_POST['role_id'];
            $status = $_POST['status'];
            
            // Verificar si el usuario existe
            $user = fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
            if (!$user) {
                $_SESSION['message'] = getAlert('danger', 'Usuario no encontrado');
                header('Location: users.php');
                exit();
            }
            
            // Preparar campos a actualizar
            $updateFields = [];
            $updateParams = [];
            
            // Agregar campos obligatorios
            $updateFields[] = "full_name = ?";
            $updateParams[] = $fullName;
            
            $updateFields[] = "email = ?";
            $updateParams[] = $email;
            
            $updateFields[] = "role_id = ?";
            $updateParams[] = $roleId;
            
            $updateFields[] = "status = ?";
            $updateParams[] = $status;
            
            // Si se proporcionó una nueva contraseña, actualizarla
            if (!empty($_POST['password'])) {
                $updateFields[] = "password = ?";
                $updateParams[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            
            // Agregar el ID al final de los parámetros
            $updateParams[] = $userId;
            
            // Construir y ejecutar la consulta
            $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $result = query($sql, $updateParams);
            
            if ($result) {
                $_SESSION['message'] = getAlert('success', 'Usuario actualizado correctamente');
            } else {
                $_SESSION['message'] = getAlert('danger', 'Error al actualizar usuario');
            }
            header('Location: users.php');
            exit();
            
        case 'delete':
            $userId = (int)$_POST['user_id'];
            
            // No permitir eliminar el propio usuario
            if ($userId === (int)$_SESSION['user_id']) {
                $_SESSION['message'] = getAlert('danger', 'No puedes eliminar tu propio usuario');
                header('Location: users.php');
                exit();
            }
            
            // Verificar que no sea un admin
            $user = fetchOne("SELECT r.name as role_name 
                            FROM users u 
                            JOIN roles r ON u.role_id = r.id 
                            WHERE u.id = ?", [$userId]);
                            
            if ($user['role_name'] === 'admin') {
                $_SESSION['message'] = getAlert('danger', 'No se puede eliminar un usuario administrador');
                header('Location: users.php');
                exit();
            }
            
            // Eliminar usuario
            query("DELETE FROM users WHERE id = ?", [$userId]);
            $_SESSION['message'] = getAlert('success', 'Usuario eliminado correctamente');
            header('Location: users.php');
            exit();
            
        default:
            $_SESSION['message'] = getAlert('danger', 'Acción no válida');
            header('Location: users.php');
            exit();
    }
} else {
    header('Location: users.php');
    exit();
}
