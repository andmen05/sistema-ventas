<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validar datos requeridos
        $required_fields = ['customer_id', 'payment_method', 'products', 'total_amount'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception('Faltan datos requeridos: ' . implode(', ', $missing_fields));
        }

        // Obtener datos del formulario
        $products = json_decode($_POST['products'], true);
        $total_amount = floatval($_POST['total_amount']);
        $customer_id = intval($_POST['customer_id']);
        $payment_method = $_POST['payment_method'];
        $reference_number = $_POST['reference_number'] ?? null;

        if (empty($products)) {
            throw new Exception('No hay productos en la venta');
        }

        // Validar datos
        if (!is_numeric($total_amount) || $total_amount <= 0) {
            throw new Exception('El monto total es inválido');
        }

        if (!in_array($payment_method, ['cash', 'card', 'transfer'])) {
            throw new Exception('Método de pago inválido');
        }

        global $conn;
        $conn->begin_transaction();

        // Insertar venta
        $invoice_number = generateInvoiceNumber();
        $sql = "INSERT INTO sales (invoice_number, customer_id, user_id, total_amount, payment_method, payment_reference, sale_date) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('siidss', $invoice_number, $customer_id, $_SESSION['user_id'], $total_amount, $payment_method, $reference_number);
        $stmt->execute();
        $sale_id = $conn->insert_id;

        if (!$sale_id) {
            throw new Exception('Error al crear la venta');
        }

        // Insertar detalles y actualizar stock
        foreach ($products as $product) {
            $product_id = (int)$product['id'];
            $quantity = (int)$product['quantity'];
            $price = (float)$product['price'];
            
            // Validar datos del producto
            if ($quantity <= 0 || $price <= 0) {
                throw new Exception('Cantidad o precio inválido para el producto ID: ' . $product_id);
            }
            
            // Verificar stock disponible
            $stock_check = fetchOne("SELECT stock FROM products WHERE id = ?", [$product_id]);
            if (!$stock_check || $stock_check['stock'] < $quantity) {
                throw new Exception('Stock insuficiente para el producto ID: ' . $product_id);
            }
            
            // Insertar detalle
            $sql = "INSERT INTO sale_details (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iiid', $sale_id, $product_id, $quantity, $price);
            $stmt->execute();
            
            // Actualizar stock
            $sql = "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iii', $quantity, $product_id, $quantity);
            $stmt->execute();
            
            if ($conn->affected_rows === 0) {
                throw new Exception('Error al actualizar el stock del producto ID: ' . $product_id);
            }
        }

        // Registrar el pago
        $sql = "INSERT INTO payments (sale_id, amount, payment_method, reference_number, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Error preparando la consulta: ' . $conn->error);
        }
        $stmt->bind_param('iiss', $sale_id, $total_amount, $payment_method, $reference_number);
        if (!$stmt->execute()) {
            throw new Exception('Error registrando el pago: ' . $stmt->error);
        }

        // Hacer commit de toda la transacción
        $conn->commit();
        header('Location: view_sale.php?id=' . $sale_id);
        exit();

    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        // Redirigir de vuelta al formulario en caso de error
        header('Location: new_sale.php');
        exit();
    }
}

header('Location: sales.php');
exit();
