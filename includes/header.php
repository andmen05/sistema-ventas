<?php
require_once __DIR__ . '/check_session.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Ventas</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../css/styles.css" rel="stylesheet">
    
    <!-- jQuery (necesario para Bootstrap y DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/sistema-ventas/pages/dashboard.php">Sistema de Ventas</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/sistema-ventas/pages/dashboard.php"><i class="fas fa-home"></i> Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/sistema-ventas/pages/products.php"><i class="fas fa-box"></i> Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/sistema-ventas/pages/customers.php"><i class="fas fa-users"></i> Clientes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/sistema-ventas/pages/categories.php"><i class="fas fa-tags"></i> Categorías</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/sistema-ventas/pages/sales.php"><i class="fas fa-shopping-cart"></i> Ventas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/sistema-ventas/pages/reports.php"><i class="fas fa-chart-bar"></i> Reportes</a>
                    </li>
                    <?php if (hasPermission('manage_users')): ?>
                    <li class="nav-item dropdown" style="padding-bottom: 0.5rem;">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> Administración
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="/sistema-ventas/pages/users.php">
                                    <i class="fas fa-users"></i> Usuarios
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/sistema-ventas/pages/roles.php">
                                    <i class="fas fa-user-tag"></i> Roles
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown" style="padding-bottom: 0.5rem;">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/sistema-ventas/pages/profile.php"><i class="fas fa-user-cog"></i> Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/sistema-ventas/pages/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
