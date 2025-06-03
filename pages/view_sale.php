<?php
require_once '../includes/header.php';
require_once '../includes/db.php';

$sale_id = (int)$_GET['id'];

// Obtener datos de la venta
$sale = fetchOne("SELECT 
                s.*, 
                u.username, 
                c.document_number, 
                c.name as customer_name, 
                c.phone as customer_phone,
                p.payment_method,
                p.reference_number,
                p.created_at as payment_date,
                p.amount as payment_amount,
                cr.id as cash_register_id,
                cr.initial_amount,
                cr.final_amount,
                cr.status as cash_register_status,
                (SELECT SUM(quantity * price) FROM sale_details WHERE sale_id = s.id) as total_amount
                FROM sales s 
                LEFT JOIN users u ON s.user_id = u.id 
                LEFT JOIN customers c ON s.customer_id = c.id 
                LEFT JOIN payments p ON s.id = p.sale_id
                LEFT JOIN cash_register cr ON p.cash_register_id = cr.id
                WHERE s.id = ?", [$sale_id]);

if (!$sale) {
    $_SESSION['message'] = getAlert('danger', 'Venta no encontrada');
    header('Location: sales.php');
    exit();
}

// Obtener detalles de la venta
$details = fetchAll("SELECT sd.*, p.code, p.name 
                    FROM sale_details sd 
                    JOIN products p ON sd.product_id = p.id 
                    WHERE sd.sale_id = ?", [$sale_id]);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Detalles de Venta</h1>
    <div>
        <a href="print_invoice.php?id=<?php echo $sale_id; ?>" class="btn btn-secondary" target="_blank">
            <i class="fas fa-print"></i> Imprimir
        </a>
        <a href="sales.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <?php echo $_SESSION['message']; ?>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> Información de la Venta</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="150">N° Factura:</th>
                        <td><?php echo $sale['invoice_number']; ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Cliente:</th>
                        <td class="fw-500"><?php echo $sale['customer_id'] ? htmlspecialchars($sale['document_number'] . ' - ' . $sale['customer_name']) : 'Cliente no registrado'; ?></td>
                    </tr>
                    <?php if ($sale['customer_phone']): ?>
                    <tr>
                        <th class="text-muted">Teléfono:</th>
                        <td class="fw-500"><?php echo htmlspecialchars($sale['customer_phone']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th class="text-muted">Monto Total:</th>
                        <td class="fw-500">$<?php echo formatPrice($sale['total_amount']); ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Fecha:</th>
                        <td class="fw-500"><?php echo !empty($sale['sale_date']) ? DateTime::createFromFormat('Y-m-d H:i:s', $sale['sale_date'])->format('d/m/Y H:i') : 'No especificada'; ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Vendedor:</th>
                        <td class="fw-500"><?php echo $sale['username']; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-money-bill-wave"></i> Información del Pago</h5>
            </div>
            <div class="card-body">
                <div class="payment-info">
                    <!-- Estado del Pago -->
                    <div class="payment-status mb-4">
                        <?php 
                        $status = !empty($sale['payment_date']) ? 'success' : 'warning';
                        $status_text = !empty($sale['payment_date']) ? 'Pago Completado' : 'Pago Pendiente';
                        $status_icon = !empty($sale['payment_date']) ? 'check-circle' : 'clock';
                        ?>
                        <div class="text-<?php echo $status; ?> d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-<?php echo $status_icon; ?> fa-2x"></i>
                            <span class="h5 mb-0"><?php echo $status_text; ?></span>
                        </div>
                        <?php if (!empty($sale['cash_register_id'])): ?>
                        <div class="text-muted small">
                            <i class="fas fa-cash-register me-1"></i>
                            Caja #<?php echo $sale['cash_register_id']; ?> 
                            <span class="badge bg-<?php echo $sale['cash_register_status'] === 'open' ? 'success' : 'secondary'; ?>">
                                <?php echo $sale['cash_register_status'] === 'open' ? 'Abierta' : 'Cerrada'; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Detalles del Pago -->
                    <div class="payment-details mb-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="payment-detail-item">
                                    <label class="text-muted small text-uppercase">Método de Pago</label>
                                    <div class="mt-1">
                                        <?php 
                                        $payment_methods = [
                                            'cash' => '<div class="payment-method cash"><i class="fas fa-money-bill-wave"></i> Efectivo</div>',
                                            'card' => '<div class="payment-method card"><i class="fas fa-credit-card"></i> Tarjeta</div>',
                                            'transfer' => '<div class="payment-method transfer"><i class="fas fa-exchange-alt"></i> Transferencia</div>'
                                        ];
                                        echo $payment_methods[$sale['payment_method']] ?? '<div class="payment-method pending"><i class="fas fa-question-circle"></i> No especificado</div>';
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="payment-detail-item">
                                    <label class="text-muted small text-uppercase">Fecha de Pago</label>
                                    <div class="mt-1 d-flex align-items-center gap-2">
                                        <i class="far fa-calendar text-muted"></i>
                                        <span class="fw-500"><?php echo !empty($sale['payment_date']) ? date('d/m/Y H:i', strtotime($sale['payment_date'])) : 'No registrado'; ?></span>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($sale['reference_number'])): ?>
                            <div class="col-12">
                                <div class="payment-detail-item">
                                    <label class="text-muted small text-uppercase">Número de Referencia</label>
                                    <div class="mt-1 d-flex align-items-center gap-2">
                                        <i class="fas fa-hashtag text-muted"></i>
                                        <span class="fw-500"><?php echo htmlspecialchars($sale['reference_number']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($sale['payment_method'] === 'cash' && !empty($sale['cash_register_id'])): ?>
                            <div class="col-12">
                                <div class="payment-detail-item">
                                    <label class="text-muted small text-uppercase">Detalles de Caja</label>
                                    <div class="mt-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-muted">Monto Inicial:</span>
                                            <span class="fw-500">$<?php echo formatPrice($sale['initial_amount']); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted">Monto Final:</span>
                                            <span class="fw-500">$<?php echo formatPrice($sale['final_amount']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="payment-total bg-light rounded p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase">Total Pagado</span>
                                <div class="h3 mb-0 mt-1">$<?php echo formatPrice($sale['total_amount']); ?></div>
                            </div>
                            <i class="fas fa-receipt fa-2x text-primary opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-box"></i> Productos</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th class="text-end">Precio</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($details as $detail): ?>
                    <tr>
                        <td><?php echo $detail['code']; ?></td>
                        <td><?php echo $detail['name']; ?></td>
                        <td class="text-end">$<?php echo formatPrice($detail['price']); ?></td>
                        <td class="text-end"><?php echo $detail['quantity']; ?></td>
                        <td class="text-end">$<?php echo formatPrice($detail['price'] * $detail['quantity']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end fw-bold">Total:</td>
                        <td class="text-end fw-bold">$<?php echo formatPrice($sale['total_amount']); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
