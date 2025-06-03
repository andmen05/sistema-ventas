<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verificar sesión
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('No autorizado');
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $roles = fetchAll("SELECT * FROM roles ORDER BY id");
        echo json_encode($roles);
        break;

    case 'create':
        $name = cleanInput($_POST['name']);
        $description = cleanInput($_POST['description']);
        
        try {
            query("INSERT INTO roles (name, description) VALUES (?, ?)", 
                  [$name, $description]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'get':
        $id = (int)$_GET['id'];
        $role = fetchOne("SELECT * FROM roles WHERE id = ?", [$id]);
        
        if ($role) {
            echo json_encode($role);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Rol no encontrado']);
        }
        break;

    case 'update':
        $id = (int)$_POST['id'];
        $name = cleanInput($_POST['name']);
        $description = cleanInput($_POST['description']);
        
        try {
            query("UPDATE roles SET name = ?, description = ? WHERE id = ?", 
                  [$name, $description, $id]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'delete':
        $id = (int)$_POST['id'];
        
        // Verificar si hay usuarios usando este rol
        $users = fetchOne("SELECT COUNT(*) as count FROM users WHERE role_id = ?", [$id]);
        
        if ($users['count'] > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'No se puede eliminar el rol porque hay usuarios asignados']);
            break;
        }
        
        try {
            query("DELETE FROM roles WHERE id = ?", [$id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
}
?>
