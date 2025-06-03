// Variables globales
let salesChart = null;
let productsChart = null;
let paymentsChart = null;

// Configuración de Chart.js
Chart.register(ChartDataLabels);
Chart.defaults.font.family = '"Montserrat", -apple-system, system-ui, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';

// Función para formatear números
const formatNumber = (number, decimals = 0) => {
    return new Intl.NumberFormat('es-MX', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
};

// Función para formatear moneda
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(amount);
};

// Función para actualizar las variaciones
const updateVariation = (elementId, variation) => {
    const element = document.getElementById(elementId);
    if (!element) return;

    const isPositive = variation > 0;
    const icon = isPositive ? 'fa-arrow-up' : 'fa-arrow-down';
    const className = isPositive ? 'positive' : 'negative';
    
    element.className = `variation ${className}`;
    element.innerHTML = `
        <i class="fas ${icon}"></i>
        ${Math.abs(variation).toFixed(1)}%
    `;
    element.setAttribute('data-bs-toggle', 'tooltip');
    element.setAttribute('data-bs-placement', 'right');
    element.setAttribute('title', `${isPositive ? 'Incremento' : 'Decremento'} del ${Math.abs(variation).toFixed(1)}% respecto al período anterior`);
};

// Función para actualizar KPIs
const updateKPIs = (data) => {
    // Destruir contadores existentes
    Object.values(counters).forEach(counter => counter.reset());

    // Ventas totales
    counters.sales = new CountUp('kpi-sales', data.current_period.total_sales, countUpOptions);
    counters.sales.start();
    updateVariation('var-sales', data.variations.sales);

    // Ingresos
    counters.revenue = new CountUp('kpi-revenue', data.current_period.total_revenue, {
        ...countUpOptions,
        prefix: '$',
        decimals: 2
    });
    counters.revenue.start();
    updateVariation('var-revenue', data.variations.revenue);

    // Ticket promedio
    counters.ticket = new CountUp('kpi-ticket', data.current_period.avg_ticket, {
        ...countUpOptions,
        prefix: '$',
        decimals: 2
    });
    counters.ticket.start();
    updateVariation('var-ticket', data.variations.ticket);

    // Clientes únicos
    counters.customers = new CountUp('kpi-customers', data.current_period.unique_customers, countUpOptions);
    counters.customers.start();
    updateVariation('var-customers', data.variations.customers);

    // Reinicializar tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(el => new bootstrap.Tooltip(el));
};

// Función para actualizar el gráfico de ventas
const updateSalesChart = (data) => {
    if (salesChart) {
        salesChart.destroy();
    }

    const ctx = document.getElementById('monthlySalesChart');
    if (!ctx) return;

    salesChart = new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                datalabels: {
                    display: false
                }
            }
        }
    });
};

// Función para actualizar el gráfico de productos
const updateProductsChart = (data) => {
    if (productsChart) {
        productsChart.destroy();
    }

    const ctx = document.getElementById('topProductsChart');
    if (!ctx) return;

    productsChart = new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                datalabels: {
                    display: false
                }
            }
        }
    });
};

// Función para actualizar el gráfico de métodos de pago
const updatePaymentsChart = (data) => {
    if (paymentsChart) {
        paymentsChart.destroy();
    }

    const ctx = document.getElementById('paymentMethodsChart');
    if (!ctx) return;

    paymentsChart = new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                datalabels: {
                    display: false
                }
            },
            cutout: '60%'
        }
    });
};

// Función para cargar datos del dashboard
const loadDashboardData = async () => {
    try {
        // Cargar datos de ventas mensuales
        const salesResponse = await fetch('get_chart_data.php?chart=monthly_sales');
        const salesData = await salesResponse.json();
        updateSalesChart(salesData);

        // Cargar datos de productos
        const productsResponse = await fetch('get_chart_data.php?chart=top_products');
        const productsData = await productsResponse.json();
        updateProductsChart(productsData);

        // Cargar datos de métodos de pago
        const paymentsResponse = await fetch('get_chart_data.php?chart=payment_methods');
        const paymentsData = await paymentsResponse.json();
        updatePaymentsChart(paymentsData);

    } catch (error) {
        console.error('Error al cargar datos:', error);
    }
};

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    // Cargar datos iniciales
    loadDashboardData(currentPeriod);

    // Manejar cambios en el período
    document.querySelectorAll('[data-period]').forEach(button => {
        button.addEventListener('click', (e) => {
            // Actualizar UI
            document.querySelector('[data-period].active').classList.remove('active');
            e.target.classList.add('active');

            // Cargar nuevos datos
            currentPeriod = e.target.dataset.period;
            loadDashboardData(currentPeriod);
        });
    });
});

// Actualizar gráficos cuando cambie el tamaño de la ventana
window.addEventListener('resize', () => {
    Object.values(charts).forEach(chart => chart.resize());
});
