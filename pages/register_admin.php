<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verificar si ya existe algún usuario administrador
$admin_exists = fetchOne("SELECT COUNT(*) as count FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'admin'");
if ($admin_exists && $admin_exists['count'] > 0) {
    // Si ya existe un admin, redirigir al login
    header('Location: login.php');
    exit();
}

// Verificar si está permitido el registro de admin
if (!file_exists('../config/install_config.php')) {
    die('El sistema ya está instalado.');
}

require_once '../config/install_config.php';
if (!defined('ALLOW_ADMIN_REGISTRATION') || !ALLOW_ADMIN_REGISTRATION) {
    die('El registro de administrador está deshabilitado.');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $installation_code = cleanInput($_POST['installation_code']);
    if ($installation_code !== INSTALLATION_CODE) {
        $error = "Código de instalación inválido";
    } else {
        $username = cleanInput($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $full_name = cleanInput($_POST['full_name']);

        try {
            // Iniciar transacción
            query("BEGIN");

            // Verificar si existe el rol de administrador
            $admin_role = fetchOne("SELECT id FROM roles WHERE name = 'admin'");
            if (!$admin_role) {
                // Crear rol de administrador si no existe
                query("INSERT INTO roles (name, description) VALUES ('admin', 'Administrador del sistema')");
                $admin_role = fetchOne("SELECT id FROM roles WHERE name = 'admin'");
            }
            
            // Insertar usuario con rol de administrador y estado activo
            $sql = "INSERT INTO users (username, password, full_name, role_id, status) VALUES (?, ?, ?, ?, 'active')";
            query($sql, [$username, $password, $full_name, $admin_role['id']]);

            // Confirmar transacción
            query("COMMIT");

            // Desactivar el registro de admin
            if (is_writable('../config/install_config.php')) {
                $config_content = "<?php\ndefine('INSTALLATION_CODE', '" . INSTALLATION_CODE . "');\ndefine('ALLOW_ADMIN_REGISTRATION', false);\n?>";
                file_put_contents('../config/install_config.php', $config_content);
            }

            // Mostrar mensaje de éxito
            $success = "Usuario administrador creado correctamente. Por favor, inicia sesión.";
            header("refresh:3;url=login.php");
        } catch (Exception $e) {
            query("ROLLBACK");
            $error = "Error al crear el usuario: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Sistema de Ventas</title>
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
                    <h1 class="login-title">Instalación Inicial</h1>
                    <p class="login-subtitle">Configuración del Administrador Principal</p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="login-alert alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="login-alert alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="login-form">
                    <div class="form-group">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Nombre de usuario" required>
                        <i class="fas fa-user"></i>
                    </div>

                    <div class="form-group">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                        <i class="fas fa-lock"></i>
                    </div>

                    <div class="form-group">
                        <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Nombre completo" required>
                        <i class="fas fa-id-card"></i>
                    </div>

                    <div class="form-group">
                        <input type="text" class="form-control" id="installation_code" name="installation_code" placeholder="Código de instalación" required>
                        <i class="fas fa-key"></i>
                    </div>

                    <button type="submit" class="login-btn btn btn-primary w-100">
                        <i class="fas fa-user-shield me-2"></i>Crear Administrador
                    </button>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Esta página solo estará disponible durante la instalación inicial
                        </small>
                    </div>
                </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
