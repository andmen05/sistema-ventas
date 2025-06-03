<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $code = cleanInput($_POST['code']);
    $name = cleanInput($_POST['name']);
    $description = cleanInput($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

    try {
        if ($id) {
            // Actualizar producto
            $sql = "UPDATE products SET 
                    code = ?, 
                    name = ?, 
                    description = ?, 
                    price = ?, 
                    stock = ?, 
                    category_id = ? 
                    WHERE id = ?";
            query($sql, [$code, $name, $description, $price, $stock, $category_id, $id]);
            $_SESSION['message'] = getAlert('success', 'Producto actualizado correctamente');
        } else {
            // Crear nuevo producto
            $sql = "INSERT INTO products (code, name, description, price, stock, category_id) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            query($sql, [$code, $name, $description, $price, $stock, $category_id]);
            $_SESSION['message'] = getAlert('success', 'Producto creado correctamente');
        }
    } catch (Exception $e) {
        $_SESSION['message'] = getAlert('danger', 'Error: ' . $e->getMessage());
    }
}

header('Location: products.php');
