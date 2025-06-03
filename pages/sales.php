<?php
require_once '../includes/header.php';
require_once '../includes/db.php';

// Primero obtenemos las ventas únicas
$sales = fetchAll("SELECT 
                    s.id,
                    s.invoice_number,
                    s.sale_date,
                    s.total_amount,
                    u.username as vendedor,
                    COALESCE(c.name, 'Cliente no registrado') as customer_name
                  FROM sales s 
                  INNER JOIN users u ON s.user_id = u.id 
                  LEFT JOIN customers c ON s.customer_id = c.id 
                  ORDER BY s.sale_date DESC");

// Luego obtenemos los pagos agrupados por venta
$payments = [];
$payment_results = fetchAll("SELECT 
                                sale_id,
                                GROUP_CONCAT(
                                    CONCAT(
                                        CASE 
                                            WHEN payment_method = 'cash' THEN 'Efectivo' 
                                            WHEN payment_method = 'card' THEN 'Tarjeta' 
                                            WHEN payment_method = 'transfer' THEN 'Transferencia' 
                                            ELSE 'No especificado' 
                                        END,
                                        IF(reference_number IS NOT NULL AND reference_number != '', 
                                           CONCAT(' (', reference_number, ')'), 
                                           '')
                                    ) 
                                    SEPARATOR ' + '
                                ) as payment_details
                            FROM payments 
                            GROUP BY sale_id");

// Convertir a un array indexado por sale_id para fácil acceso
foreach ($payment_results as $payment) {
    $payments[$payment['sale_id']] = $payment['payment_details'];
}

// Agregar los pagos a cada venta
foreach ($sales as &$sale) {
    $sale['payment_details'] = $payments[$sale['id']] ?? null;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Ventas</h1>
    <a href="new_sale.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nueva Venta
    </a>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <?php echo $_SESSION['message']; ?>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="salesTable">
                <thead>
                    <tr>
                        <th>Factura</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Método de Pago</th>
                        <th>Vendedor</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td>
                            <a href="view_sale.php?id=<?php echo $sale['id']; ?>" class="text-primary fw-500">
                                <?php echo $sale['invoice_number']; ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                        <td class="text-end">$<?php echo formatPrice($sale['total_amount']); ?></td>
                        <td>
                            <?php if (!empty($sale['payment_details'])): ?>
                                <?php 
                                // Separar los métodos de pago
                                $payment_methods = explode(' + ', $sale['payment_details']);
                                foreach ($payment_methods as $method): 
                                    // Determinar la clase del badge basado en el método de pago
                                    $badge_class = 'bg-secondary';
                                    if (strpos($method, 'Efectivo') !== false) {
                                        $badge_class = 'bg-success';
                                    } elseif (strpos($method, 'Tarjeta') !== false) {
                                        $badge_class = 'bg-primary';
                                    } elseif (strpos($method, 'Transferencia') !== false) {
                                        $badge_class = 'bg-info';
                                    }
                                ?>
                                <span class="badge <?php echo $badge_class; ?> mb-1">
                                    <?php echo htmlspecialchars(trim($method)); ?>
                                </span><br>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary">No especificado</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($sale['vendedor']); ?></td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="small fw-500"><?php echo date('d/m/Y', strtotime($sale['sale_date'])); ?></span>
                                <small class="text-muted"><?php echo date('H:i', strtotime($sale['sale_date'])); ?></small>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="view_sale.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="print_invoice.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-secondary" target="_blank" title="Imprimir factura">
                                    <i class="fas fa-print"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    $('#salesTable').DataTable({
        order: [[4, 'desc']], // Ordenar por fecha descendente
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
