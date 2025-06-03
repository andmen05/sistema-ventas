<?php
require_once '../includes/header.php';
require_once '../includes/db.php';

// Obtener estadísticas
$stats = fetchOne("SELECT 
    (SELECT COUNT(*) FROM products) as total_products,
    (SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE()) as total_sales,
    (SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(sale_date) = CURDATE()) as total_amount,
    (SELECT COUNT(*) FROM products WHERE stock <= 5) as low_stock");

$totalProducts = $stats['total_products'];
$totalSales = $stats['total_sales'];
$totalAmount = $stats['total_amount'];
$lowStock = $stats['low_stock'];

// Obtener últimas ventas
$recentSales = fetchAll("SELECT 
    s.id,
    s.invoice_number,
    s.sale_date,
    u.username,
    COALESCE(c.name, 'Cliente no registrado') as customer_name,
    s.total_amount
FROM sales s 
JOIN users u ON s.user_id = u.id 
LEFT JOIN customers c ON s.customer_id = c.id
ORDER BY s.sale_date DESC LIMIT 5");
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Productos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalProducts; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-box fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Ventas Totales</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalSales; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Ingresos</div>
                        <h4 class="small font-weight-bold">Ventas del Mes <span class="float-end">$<?php echo formatPrice($totalAmount); ?></span></h4>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Stock Bajo</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $lowStock; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos -->
<div class="row">
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3" style="background: linear-gradient(90deg, #4361ee 0%, #3f37c9 100%);">
                <h6 class="m-0 font-weight-bold text-white">Ventas Mensuales (Últimos 12 meses)</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="monthlySalesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3" style="background: linear-gradient(90deg, #4361ee 0%, #3f37c9 100%);">
                <h6 class="m-0 font-weight-bold text-white">Métodos de Pago (Últimos 30 días)</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie">
                    <canvas id="paymentMethodsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3" style="background: linear-gradient(90deg, #4361ee 0%, #3f37c9 100%);">
                <h6 class="m-0 font-weight-bold text-white">Productos Más Vendidos (Últimos 30 días)</h6>
            </div>
            <div class="card-body">
                <div class="chart-bar">
                    <canvas id="topProductsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3" style="background: linear-gradient(90deg, #4361ee 0%, #3f37c9 100%);">
                <h6 class="m-0 font-weight-bold text-white">Últimas Ventas</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Factura</th>
                                <th>Cliente</th>
                                <th>Monto</th>
                                <th>Vendedor</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSales as $sale): ?>
                            <tr>
                                <td><?php echo $sale['invoice_number']; ?></td>
                                <td><?php echo $sale['customer_name']; ?></td>
                                <td>$<?php echo number_format($sale['total_amount'], 2); ?></td>
                                <td><?php echo $sale['username']; ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($sale['sale_date'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js y scripts necesarios -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- JavaScript para los gráficos -->
<script>
console.log('Iniciando carga de gráficos...');

// Verificar que Chart.js esté cargado
if (typeof Chart === 'undefined') {
    console.error('Chart.js no está cargado!');
} else {
    console.log('Chart.js cargado correctamente');
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando gráficos...');
    
    // Verificar que los elementos canvas existan
    const monthlySalesCanvas = document.getElementById('monthlySalesChart');
    const topProductsCanvas = document.getElementById('topProductsChart');
    const paymentMethodsCanvas = document.getElementById('paymentMethodsChart');
    
    console.log('Canvas encontrados:', {
        monthlySales: !!monthlySalesCanvas,
        topProducts: !!topProductsCanvas,
        paymentMethods: !!paymentMethodsCanvas
    });

    // Configuración común para los gráficos
    Chart.defaults.color = '#858796';
    Chart.defaults.font.family = '"Nunito", -apple-system,system-ui,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';

    // Cargar datos de ventas mensuales
    console.log('Cargando datos de ventas mensuales...');
    fetch('get_chart_data.php?chart=monthly_sales')
        .then(response => {
            console.log('Respuesta de ventas mensuales:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Datos de ventas mensuales:', data);
            new Chart(monthlySalesCanvas, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Ventas',
                            data: data.datasets[0].data,
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            fill: true,
                            tension: 0.3,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Ingresos ($)',
                            data: data.datasets[1].data,
                            borderColor: '#1cc88a',
                            backgroundColor: 'rgba(28, 200, 138, 0.1)',
                            fill: true,
                            tension: 0.3,
                            yAxisID: 'y2'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    stacked: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Número de Ventas'
                            }
                        },
                        y2: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Ingresos ($)'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
            console.log('Gráfico de ventas mensuales creado');
        })
        .catch(error => {
            console.error('Error al cargar ventas mensuales:', error);
        });

    // Cargar datos de productos más vendidos
    console.log('Cargando datos de productos más vendidos...');
    fetch('get_chart_data.php?chart=top_products')
        .then(response => {
            console.log('Respuesta de productos:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Datos de productos:', data);
            new Chart(topProductsCanvas, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Cantidad Vendida'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            console.log('Gráfico de productos creado');
        })
        .catch(error => {
            console.error('Error al cargar productos:', error);
        });

    // Cargar datos de métodos de pago
    console.log('Cargando datos de métodos de pago...');
    fetch('get_chart_data.php?chart=payment_methods')
        .then(response => {
            console.log('Respuesta de métodos de pago:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Datos de métodos de pago:', data);
            new Chart(paymentMethodsCanvas, {
                type: 'doughnut',
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    cutout: '60%'
                }
            });
            console.log('Gráfico de métodos de pago creado');
        })
        .catch(error => {
            console.error('Error al cargar métodos de pago:', error);
        });
});
</script>

<?php require_once '../includes/footer.php'; ?>
