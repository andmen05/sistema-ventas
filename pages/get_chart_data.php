<?php
require_once '../includes/db.php';

try {
    header('Content-Type: application/json');

    if (!isset($_GET['chart'])) {
        throw new Exception('No chart specified');
    }

    $response = [];

switch ($_GET['chart']) {
    case 'monthly_sales':
        // Generar array con últimos 12 meses
        $months = [];
        $current_date = new DateTime();
        for ($i = 11; $i >= 0; $i--) {
            $date = clone $current_date;
            $date->modify("-$i months");
            $months[$date->format('Y-m')] = [
                'month' => $date->format('Y-m'),
                'total_sales' => 0,
                'total_amount' => 0
            ];
        }

        // Obtener datos de ventas
        $data = fetchAll("
            SELECT 
                DATE_FORMAT(sale_date, '%Y-%m') as month,
                COUNT(*) as total_sales,
                COALESCE(SUM(total_amount), 0) as total_amount
            FROM sales
            WHERE sale_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
            ORDER BY month ASC
        ");

        // Combinar datos reales con meses vacíos
        foreach ($data as $row) {
            if (isset($months[$row['month']])) {
                $months[$row['month']] = [
                    'month' => $row['month'],
                    'total_sales' => (int)$row['total_sales'],
                    'total_amount' => (float)$row['total_amount']
                ];
            }
        }
        
        $months_data = array_values($months);
        
        $response = [
            'labels' => array_map(function($row) {
                return date('M Y', strtotime($row['month'] . '-01'));
            }, $months_data),
            'datasets' => [
                [
                    'label' => 'Ventas',
                    'data' => array_map(function($row) {
                        return $row['total_sales'];
                    }, $months_data),
                    'borderColor' => '#4e73df',
                    'backgroundColor' => 'rgba(78, 115, 223, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y'
                ],
                [
                    'label' => 'Ingresos ($)',
                    'data' => array_map(function($row) {
                        return $row['total_amount'];
                    }, $months_data),
                    'borderColor' => '#1cc88a',
                    'backgroundColor' => 'rgba(28, 200, 138, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y2'
                ]
            ]
        ];
        break;

    case 'top_products':
        // Top 10 productos más vendidos
        $data = fetchAll("
            SELECT 
                p.name,
                COALESCE(SUM(sd.quantity), 0) as total_quantity,
                COALESCE(SUM(sd.quantity * sd.price), 0) as total_amount
            FROM sale_details sd
            JOIN products p ON sd.product_id = p.id
            JOIN sales s ON sd.sale_id = s.id
            WHERE s.sale_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
            GROUP BY sd.product_id, p.name
            ORDER BY total_quantity DESC
            LIMIT 10
        ");
        
        $colors = [
            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
            '#858796', '#5a5c69', '#2e59d9', '#17a673', '#2c9faf'
        ];
        
        $response = [
            'labels' => array_column($data, 'name'),
            'datasets' => [[
                'label' => 'Cantidad Vendida',
                'data' => array_column($data, 'total_quantity'),
                'backgroundColor' => array_slice($colors, 0, count($data)),
                'borderColor' => array_map(function($color) {
                    return str_replace(')', ', 0.8)', str_replace('rgb', 'rgba', $color));
                }, array_slice($colors, 0, count($data))),
                'borderWidth' => 1
            ]]
        ];
        break;

    case 'payment_methods':
        // Distribución por método de pago
        $data = fetchAll("
            SELECT 
                payment_method as method,
                COUNT(*) as total,
                COALESCE(SUM(total_amount), 0) as total_amount
            FROM sales
            WHERE sale_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
            GROUP BY payment_method
            ORDER BY total DESC
        ");
        
        if (empty($data)) {
            $data = [[
                'method' => 'Sin ventas',
                'total' => 1,
                'total_amount' => 0
            ]];
        }
        
        $colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'];
        
        $response = [
            'labels' => array_map(function($row) {
                return $row['method'] . ' ($' . number_format($row['total_amount'], 2) . ')';
            }, $data),
            'datasets' => [[
                'data' => array_column($data, 'total'),
                'backgroundColor' => array_slice($colors, 0, count($data)),
                'borderColor' => array_map(function($color) {
                    return str_replace(')', ', 0.8)', str_replace('rgb', 'rgba', $color));
                }, array_slice($colors, 0, count($data))),
                'borderWidth' => 1
            ]]
        ];
        break;
}

echo json_encode($response);

} catch (Exception $e) {
    handleAjaxError($e);
}
?>
