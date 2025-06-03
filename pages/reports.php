<?php
require_once '../includes/header.php';
require_once '../includes/db.php';

// Obtener fechas por defecto
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Obtener resumen de ventas
$sales_summary = fetchOne("SELECT 
    COUNT(*) as total_sales,
    COALESCE(SUM(
        (SELECT COALESCE(SUM(sd.quantity * sd.price), 0) 
         FROM sale_details sd 
         WHERE sd.sale_id = s.id)
    ), 0) as total_amount
FROM sales s
WHERE DATE(sale_date) BETWEEN ? AND ?", 
[$start_date, $end_date]);

// Si no hay resultados, inicializar con valores por defecto
if (!$sales_summary) {
    $sales_summary = ['total_sales' => 0, 'total_amount' => 0];
}

// Obtener productos más vendidos
$top_products = fetchAll("SELECT 
    p.code,
    p.name,
    SUM(sd.quantity) as total_quantity,
    SUM(sd.quantity * sd.price) as total_amount
FROM sale_details sd
JOIN products p ON sd.product_id = p.id
JOIN sales s ON sd.sale_id = s.id
WHERE DATE(s.sale_date) BETWEEN ? AND ?
GROUP BY p.id, p.code, p.name
ORDER BY total_quantity DESC
LIMIT 10",
[$start_date, $end_date]);

// Si no hay resultados, inicializar como array vacío
if (!$top_products) {
    $top_products = [];    
}

// Obtener ventas por día
$daily_sales = fetchAll("SELECT 
    DATE(s.sale_date) as date,
    COUNT(*) as total_sales,
    COALESCE(SUM(
        (SELECT COALESCE(SUM(sd.quantity * sd.price), 0) 
         FROM sale_details sd 
         WHERE sd.sale_id = s.id)
    ), 0) as total_amount
FROM sales s
WHERE DATE(s.sale_date) BETWEEN ? AND ?
GROUP BY DATE(s.sale_date)
ORDER BY date",
[$start_date, $end_date]);

// Si no hay resultados, inicializar como array vacío
if (!$daily_sales) {
    $daily_sales = [];
}
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Reportes</h1>
        <button class="btn btn-success" id="exportExcel">
            <i class="fas fa-file-excel"></i> Exportar Excel
        </button>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo $start_date; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">Fecha Fin</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo $end_date; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas de Resumen -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Ventas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $sales_summary['total_sales']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Monto Total</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                $<?php echo number_format($sales_summary['total_amount'], 2); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line"></i> Ventas por Día
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="dailySalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar"></i> Ventas por Día
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr class="table-primary text-center">
                                <th>Fecha</th>
                                <th>N° Ventas</th>
                                <th>Total</th>
                                <th>Promedio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($daily_sales)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    <i class="fas fa-info-circle me-2"></i>No hay ventas en el período seleccionado
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($daily_sales as $sale): 
                                    $avg = $sale['total_sales'] > 0 ? $sale['total_amount'] / $sale['total_sales'] : 0;
                                ?>
                                <tr>
                                    <td class="text-center align-middle">
                                        <?php echo date('d/m/Y', strtotime($sale['date'])); ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <?php echo $sale['total_sales']; ?>
                                    </td>
                                    <td class="text-end align-middle">
                                        $<?php echo number_format($sale['total_amount'], 2); ?>
                                    </td>
                                    <td class="text-end align-middle">
                                        $<?php echo number_format($avg, 2); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validar fechas
    document.querySelector('form').addEventListener('submit', function(e) {
        const startDate = new Date(document.getElementById('start_date').value);
        const endDate = new Date(document.getElementById('end_date').value);
        
        if (startDate > endDate) {
            e.preventDefault();
            alert('La fecha de inicio no puede ser mayor que la fecha fin');
        }
    });
});
</script>

<!-- Hidden inputs para datos de gráficos -->
<input type="hidden" id="dailySalesData" value='<?php echo json_encode($daily_sales); ?>'>
<input type="hidden" id="topProductsData" value='<?php echo json_encode($top_products); ?>'>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../js/reports.js"></script>

<?php require_once '../includes/footer.php'; ?>
