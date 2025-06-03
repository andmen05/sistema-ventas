<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$sale_id = (int)$_GET['id'];

// Obtener información de la venta
$sale = fetchOne("SELECT s.*, u.username, c.name as customer_name, c.document_number,
              p.payment_method, p.reference_number,
              (SELECT SUM(quantity * price) FROM sale_details WHERE sale_id = s.id) as total_amount
              FROM sales s 
              JOIN users u ON s.user_id = u.id 
              LEFT JOIN customers c ON s.customer_id = c.id
              LEFT JOIN payments p ON s.id = p.sale_id
              WHERE s.id = ?", [$sale_id]);

if (!$sale) {
    die('Venta no encontrada');
}

// Obtener detalles de la venta
$details = fetchAll("SELECT sd.*, p.code, p.name 
                    FROM sale_details sd 
                    JOIN products p ON sd.product_id = p.id 
                    WHERE sd.sale_id = ?", [$sale_id]);

// Mapeo de métodos de pago
$payment_methods = [
    'cash' => 'Efectivo',
    'card' => 'Tarjeta',
    'transfer' => 'Transferencia'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo $sale['invoice_number']; ?></title>
    <style>
        body {
            font-family: monospace;
            margin: 0;
            padding: 10px;
            font-size: 12px;
            max-width: 300px;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .info {
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .items {
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .total {
            text-align: right;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        table { width: 100%; }
        td { padding: 2px 0; }
        @media print {
            body { margin: 0; padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2 style="margin:0 0 5px 0">SISTEMA DE VENTAS</h2>
        <div>Ticket #<?php echo $sale['invoice_number']; ?></div>
        <div><?php echo date('d/m/Y H:i', strtotime($sale['sale_date'])); ?></div>
    </div>

    <div class="info">
        <div><strong>Cliente:</strong> <?php echo $sale['customer_name'] ?: 'Cliente General'; ?></div>
        <?php if ($sale['document_number']): ?>
        <div><strong>Documento:</strong> <?php echo $sale['document_number']; ?></div>
        <?php endif; ?>
        <div><strong>Vendedor:</strong> <?php echo $sale['username']; ?></div>
        <div><strong>Método de Pago:</strong> <?php echo $payment_methods[$sale['payment_method']] ?? 'No especificado'; ?></div>
        <?php if ($sale['reference_number']): ?>
        <div><strong>Referencia:</strong> <?php echo $sale['reference_number']; ?></div>
        <?php endif; ?>
    </div>

    <div class="items">
        <table>
            <tr>
                <td colspan="3" style="border-bottom: 1px dashed #000;">PRODUCTOS</td>
                <td>CANT</td>
                <td>DESCRIPCIÓN</td>
                <td class="text-right">TOTAL</td>
            </tr>
            <?php foreach ($details as $detail): ?>
            <tr>
                <td><?php echo $detail['quantity']; ?></td>
                <td><?php echo $detail['name']; ?></td>
                <td class="text-right">$<?php echo number_format($detail['quantity'] * $detail['price'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="total">
        <table>
            <tr>
                <td><strong>TOTAL:</strong></td>
                <td class="text-right">$<?php echo number_format($sale['total_amount'], 2); ?></td>
            </tr>
        </table>
    </div>

    <div class="text-center" style="margin-top:20px;">
        ¡Gracias por su compra!
    </div>

    <div class="no-print">
        <button onclick="window.print()">Imprimir</button>
        <button onclick="window.close()">Cerrar</button>
    </div>
</body>
</html>
