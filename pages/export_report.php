<?php
// Desactivar todos los errores
error_reporting(0);

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Validar sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener parámetros
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Obtener datos de ventas por día
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
[$start_date, $end_date]) ?: [];

// Calcular totales
$total_sales = 0;
$total_amount = 0;
foreach ($daily_sales as $sale) {
    $total_sales += $sale['total_sales'];
    $total_amount += $sale['total_amount'];
}

// Obtener productos más vendidos
$top_products = fetchAll("SELECT 
    p.code,
    p.name,
    SUM(sd.quantity) as total_quantity,
    SUM(sd.quantity * sd.price) as total_amount,
    SUM(sd.quantity * sd.price) / SUM(sd.quantity) as precio_promedio
FROM sale_details sd
JOIN products p ON sd.product_id = p.id
JOIN sales s ON sd.sale_id = s.id
WHERE DATE(s.sale_date) BETWEEN ? AND ?
GROUP BY p.id, p.code, p.name
ORDER BY total_quantity DESC
LIMIT 10",
[$start_date, $end_date]) ?: [];

// Preparar el archivo CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=reporte_ventas.csv');

// Crear el archivo
$output = fopen('php://output', 'w');

// UTF-8 BOM para Excel
fputs($output, "\xEF\xBB\xBF");

// Título del reporte
fputcsv($output, ['REPORTE DE VENTAS']);
fputcsv($output, ['Sistema de Gestión de Ventas']);
fputcsv($output, []);

// Información del reporte
fputcsv($output, ['Información del Reporte', '', '', '']);
fputcsv($output, ['Fecha de generación', date('d/m/Y H:i:s'), '', '']);
fputcsv($output, ['Período', date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)), '', '']);
fputcsv($output, ['Usuario', $_SESSION['username'] ?? 'Sistema', '', '']);
fputcsv($output, []);

// Resumen General
fputcsv($output, ['RESUMEN GENERAL', '', '', '']);
fputcsv($output, ['Indicador', 'Valor', '', '']);
fputcsv($output, ['Total de Ventas', $total_sales, '', '']);
fputcsv($output, ['Monto Total', '$ ' . number_format($total_amount, 2), '', '']);
fputcsv($output, ['Promedio por Venta', '$ ' . number_format($total_amount / ($total_sales ?: 1), 2), '', '']);
fputcsv($output, []);

// Ventas Diarias
fputcsv($output, ['DETALLE DE VENTAS POR DÍA', '', '', '']);
fputcsv($output, ['Fecha', 'N° Ventas', 'Total ($)', 'Promedio ($)']);

// Datos de ventas diarias
if (!empty($daily_sales)) {
    foreach ($daily_sales as $sale) {
        $avg = $sale['total_sales'] > 0 ? $sale['total_amount'] / $sale['total_sales'] : 0;
        fputcsv($output, [
            date('d/m/Y', strtotime($sale['date'])),
            $sale['total_sales'],
            number_format($sale['total_amount'], 2),
            number_format($avg, 2)
        ]);
    }
    
    // Totales de la tabla
    fputcsv($output, [
        'TOTAL',
        $total_sales,
        number_format($total_amount, 2),
        number_format($total_amount / ($total_sales ?: 1), 2)
    ]);
} else {
    fputcsv($output, ['No se encontraron ventas en el período seleccionado', '', '', '']);
}
fputcsv($output, []);

// Productos más vendidos
fputcsv($output, ['TOP 10 PRODUCTOS MÁS VENDIDOS', '', '', '', '']);
fputcsv($output, ['Código', 'Producto', 'Unidades', 'Precio Prom. ($)', 'Total ($)']);

// Datos de productos
if (!empty($top_products)) {
    $total_units = 0;
    $total_amount_products = 0;
    
    foreach ($top_products as $product) {
        fputcsv($output, [
            $product['code'],
            $product['name'],
            $product['total_quantity'],
            number_format($product['precio_promedio'], 2),
            number_format($product['total_amount'], 2)
        ]);
        
        $total_units += $product['total_quantity'];
        $total_amount_products += $product['total_amount'];
    }
    
    // Totales de productos
    fputcsv($output, [
        'TOTAL',
        '',
        $total_units,
        '',
        number_format($total_amount_products, 2)
    ]);
} else {
    fputcsv($output, ['No se encontraron productos vendidos en el período seleccionado', '', '', '', '']);
}

// Pie del reporte
fputcsv($output, []);
fputcsv($output, ['Nota: Este reporte fue generado automáticamente por el Sistema de Gestión de Ventas', '', '', '', '']);

// Cerrar el archivo
fclose($output);
