<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Prevenir cualquier salida antes de los headers
ob_start();

// Procesar peticiones GET
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'get') {
        header('Content-Type: application/json');
        $id = cleanInput($_GET['id']);
        $customer = fetchOne("SELECT * FROM customers WHERE id = ?", [$id]);
        echo json_encode($customer);
        exit;
    }
    
    if ($_GET['action'] == 'history') {
        $id = cleanInput($_GET['id']);
        $customer = fetchOne("SELECT name FROM customers WHERE id = ?", [$id]);
        $sales = fetchAll("
            SELECT s.*, p.payment_method, u.username as seller
            FROM sales s
            LEFT JOIN payments p ON s.id = p.sale_id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.customer_id = ?
            ORDER BY s.sale_date DESC
        ", [$id]);

        echo "<h4>Historial de Compras - " . htmlspecialchars($customer['name']) . "</h4>";
        if (empty($sales)) {
            echo "<p>No hay compras registradas.</p>";
        } else {
            echo "<div class='table-responsive'>";
            echo "<table class='table table-bordered'>";
            echo "<thead><tr><th>Fecha</th><th>Total</th><th>Método de Pago</th><th>Vendedor</th></tr></thead>";
            echo "<tbody>";
            foreach ($sales as $sale) {
                echo "<tr>";
                echo "<td>" . date('d/m/Y H:i', strtotime($sale['sale_date'])) . "</td>";
                echo "<td>$" . number_format($sale['total_amount'], 2) . "</td>";
                echo "<td>" . ucfirst($sale['payment_method'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($sale['seller']) . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table></div>";
        }
        exit;
    }
}

// Procesar peticiones POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Limpiar cualquier salida anterior
    ob_clean();
    
    $is_quick_add = isset($_POST['is_quick_add']);
    $response = ['success' => false, 'message' => '', 'customer' => null];
    
    try {
        // Validar datos requeridos
        $required_fields = ['document_type', 'document_number', 'name'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                throw new Exception("El campo " . ucfirst(str_replace('_', ' ', $field)) . " es requerido");
            }
        }
        
        // Obtener y limpiar datos del formulario
        $customer_id = isset($_POST['customer_id']) ? cleanInput($_POST['customer_id']) : null;
        $document_type = cleanInput($_POST['document_type']);
        $document_number = cleanInput($_POST['document_number']);
        $name = cleanInput($_POST['name']);
        $phone = isset($_POST['phone']) ? cleanInput($_POST['phone']) : '';
        
        // Verificar si el documento ya existe
        $existing = fetchOne(
            "SELECT id FROM customers WHERE document_number = ? AND id != ?", 
            [$document_number, $customer_id ?? 0]
        );
            
        if ($existing) {
            throw new Exception('Ya existe un cliente con este número de documento');
        }
        
        if ($customer_id) {
            // Actualizar cliente existente
            query(
                "UPDATE customers SET document_type = ?, document_number = ?, name = ?, phone = ? WHERE id = ?",
                [$document_type, $document_number, $name, $phone, $customer_id]
            );
            $message = 'Cliente actualizado exitosamente';
            
            // Obtener cliente actualizado
            $customer = fetchOne("SELECT * FROM customers WHERE id = ?", [$customer_id]);
            if ($customer) {
                $response['customer'] = $customer;
            }
        } else {
            // Crear nuevo cliente
            query(
                "INSERT INTO customers (document_type, document_number, name, phone) VALUES (?, ?, ?, ?)",
                [$document_type, $document_number, $name, $phone]
            );
            
            // Obtener el cliente recién creado
            $new_customer = fetchOne(
                "SELECT * FROM customers WHERE document_number = ?", 
                [$document_number]
            );
            
            if ($new_customer) {
                $response['customer'] = $new_customer;
                $message = 'Cliente creado exitosamente';
            } else {
                throw new Exception('Error al crear el cliente');
            }
        }
        
        // Establecer mensaje de éxito
        $response['success'] = true;
        $response['message'] = $message;
        
        if (!$is_quick_add) {
            $_SESSION['message'] = getAlert('success', $message);
            header('Location: customers.php');
            exit;
        }
        
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
        
        if (!$is_quick_add) {
            $_SESSION['message'] = getAlert('danger', $e->getMessage());
            header('Location: customers.php');
            exit;
        }
    }
    
    // Asegurar que no hay salida antes del JSON
    if (ob_get_length()) ob_clean();
    
    // Devolver respuesta JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
