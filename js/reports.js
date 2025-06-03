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

    // Gráfico de ventas por día
    const dailySalesChart = document.getElementById('dailySalesChart');
    if (dailySalesChart) {
        const dailySalesData = JSON.parse(document.getElementById('dailySalesData').value || '[]');
        
        if (dailySalesData.length > 0) {
            new Chart(dailySalesChart.getContext('2d'), {
                type: 'line',
                data: {
                    labels: dailySalesData.map(item => {
                        const date = new Date(item.date);
                        return date.toLocaleDateString('es-ES', {
                            day: '2-digit',
                            month: 'short'
                        });
                    }),
                    datasets: [{
                        label: 'Ventas ($)',
                        data: dailySalesData.map(item => item.total_amount),
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: false
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // Exportar a CSV
    document.getElementById('exportExcel')?.addEventListener('click', function() {
        const data = {
            start_date: document.getElementById('start_date').value,
            end_date: document.getElementById('end_date').value
        };

        const params = new URLSearchParams(data);
        fetch(`export_report.php?${params.toString()}`)
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'reporte_ventas.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            })
            .catch(error => {
                console.error('Error al exportar:', error);
                alert('Error al exportar el reporte');
            });
    });
});
