<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT u.*, r.name as role_name 
           FROM users u 
           LEFT JOIN roles r ON u.role_id = r.id 
           WHERE u.username = ? AND u.status = 'active'";
    $user = fetch($sql, [$username]);
    
    if ($user && password_verify($password, $user['password'])) {
        // Guardar datos en sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role_name'] ?? 'no_role';
        
        // Actualizar último login
        query("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?", [$user['id']]);
        
        // Registrar el inicio de sesión en el log
        error_log("Usuario {$user['username']} (ID: {$user['id']}) inició sesión con rol: {$_SESSION['role']}");
        
        header('Location: /sistema-ventas/pages/dashboard.php');
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Ventas</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../css/styles.css" rel="stylesheet">
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="card-body p-4 p-md-5">
                <div class="login-header">
                    <h1 class="login-title">Sistema de Ventas</h1>
                    <p class="login-subtitle">Ingresa tus credenciales para continuar</p>
                </div>
                <?php if (isset($error)): ?>
                    <div class="login-alert alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="" class="login-form">
                    <div class="form-group">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Usuario" required>
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                        <i class="fas fa-lock"></i>
                    </div>
                    <button type="submit" class="login-btn btn btn-primary w-100">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                    </button>
                </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
