<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $name = cleanInput($_POST['name']);
    $description = cleanInput($_POST['description']);

    try {
        if ($id) {
            // Actualizar categoría
            $sql = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
            query($sql, [$name, $description, $id]);
            $_SESSION['message'] = getAlert('success', 'Categoría actualizada correctamente');
        } else {
            // Crear nueva categoría
            $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
            query($sql, [$name, $description]);
            $_SESSION['message'] = getAlert('success', 'Categoría creada correctamente');
        }
    } catch (Exception $e) {
        $_SESSION['message'] = getAlert('danger', 'Error: ' . $e->getMessage());
    }
}

header('Location: categories.php');
