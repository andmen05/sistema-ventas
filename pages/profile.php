<?php
require_once '../includes/header.php';
require_once '../includes/db.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Obtener información del usuario
$user = fetchOne("SELECT u.*, r.name as role_name 
FROM users u 
LEFT JOIN roles r ON u.role_id = r.id 
WHERE u.id = ?", [$_SESSION['user_id']]);

// Procesar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validar campos obligatorios
    if (empty($username) || empty($email)) {
        $error_message = 'El nombre de usuario y email son obligatorios';
    } else {
        // Verificar si el username ya existe (excluyendo el usuario actual)
        $existing_user = fetchOne("SELECT id FROM users WHERE username = ? AND id != ?", 
            [$username, $_SESSION['user_id']]);
        
        if ($existing_user) {
            $error_message = 'El nombre de usuario ya está en uso';
        } else {
            $update_fields = [];
            $params = [];

            // Preparar actualización de username y email
            $update_fields[] = "username = ?";
            $params[] = $username;
            $update_fields[] = "email = ?";
            $params[] = $email;

            // Si se proporciona contraseña actual y nueva
            if (!empty($current_password) && !empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error_message = 'Las contraseñas nuevas no coinciden';
                } else if (!password_verify($current_password, $user['password'])) {
                    $error_message = 'La contraseña actual es incorrecta';
                } else {
                    $update_fields[] = "password = ?";
                    $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                }
            }

            if (empty($error_message)) {
                // Agregar el ID del usuario a los parámetros
                $params[] = $_SESSION['user_id'];

                // Ejecutar la actualización
                $query = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
                if (execute($query, $params)) {
                    $success_message = 'Perfil actualizado correctamente';
                    // Actualizar la información del usuario
                    $user = fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
                } else {
                    $error_message = 'Error al actualizar el perfil';
                }
            }
        }
    }
}
?>

<div class="container-fluid px-4 py-4">
    <h1 class="h3 mb-4">Mi Perfil</h1>

    <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-xl-4">
            <!-- Tarjeta de Perfil -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-user-circle"></i> Información de Perfil</h5>
                </div>
                <div class="card-body">
                    <div class="profile-section">
                        <div class="profile-avatar">
                            <?php if (isset($user['avatar']) && file_exists('../uploads/avatars/' . $user['avatar'])): ?>
                                <img src="../uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                                    alt="Avatar de <?php echo htmlspecialchars($user['username']); ?>">
                            <?php else: ?>
                                <img src="../assets/img/default-avatar.png" 
                                    alt="Avatar por defecto">
                            <?php endif; ?>
                            <button type="button" class="edit-avatar" 
                                    data-bs-toggle="modal" data-bs-target="#avatarModal">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        <div class="profile-info">
                            <h4 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h4>
                            <p class="profile-role"><?php echo htmlspecialchars($user['role_name'] ?? 'Usuario'); ?></p>
                            <div class="profile-stats">
                                <div class="stat-item">
                                    <div class="stat-value">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div class="stat-label">
                                        Miembro desde<br><?php echo date('M Y', strtotime($user['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjeta de Actividad -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Actividad Reciente</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Obtener las últimas ventas del usuario
                    $recent_sales = fetchAll("SELECT s.*, 
                        (SELECT SUM(quantity * price) FROM sale_details WHERE sale_id = s.id) as total_amount 
                        FROM sales s 
                        WHERE user_id = ? 
                        ORDER BY sale_date DESC LIMIT 5", 
                        [$_SESSION['user_id']]);
                    ?>
                    <div class="timeline">
                        <?php if (empty($recent_sales)): ?>
                            <p class="text-muted text-center mb-0">No hay actividad reciente</p>
                        <?php else: ?>
                            <?php foreach ($recent_sales as $sale): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon bg-primary">
                                    <i class="fas fa-receipt text-white"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0">Venta #<?php echo $sale['invoice_number']; ?></h6>
                                        <span class="badge bg-success-soft text-success">
                                            $<?php echo formatPrice($sale['total_amount']); ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($sale['sale_date'])); ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <!-- Formulario de Edición -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-edit"></i> Editar Perfil</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre de Usuario</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Correo Electrónico</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>

                            <div class="col-12">
                                <hr class="my-4">
                                <h6 class="mb-3">Cambiar Contraseña</h6>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Contraseña Actual</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="current_password">
                                </div>
                                <small class="text-muted">Dejar en blanco si no desea cambiar la contraseña</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nueva Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" name="new_password">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Confirmar Nueva Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" name="confirm_password">
                                </div>
                            </div>

                            <div class="col-12">
                                <hr class="my-4">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Guardar Cambios
                                    </button>
                                    <button type="reset" class="btn btn-light">
                                        <i class="fas fa-undo me-2"></i>Restablecer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Estadísticas</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <?php
                        // Obtener estadísticas del usuario
                        $stats = fetchOne("SELECT 
                            COUNT(*) as total_sales,
                            COALESCE(SUM(
                                (SELECT SUM(quantity * price) FROM sale_details WHERE sale_id = s.id)
                            ), 0) as total_amount,
                            COUNT(DISTINCT DATE(sale_date)) as active_days
                            FROM sales s 
                            WHERE user_id = ?", 
                            [$_SESSION['user_id']]);
                        ?>
                        <div class="col-md-4">
                            <div class="stat-card text-center p-3">
                                <div class="stat-icon mb-2">
                                    <i class="fas fa-shopping-cart fa-2x text-primary opacity-75"></i>
                                </div>
                                <h3 class="mb-1"><?php echo $stats['total_sales']; ?></h3>
                                <p class="text-muted mb-0">Ventas Totales</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card text-center p-3">
                                <div class="stat-icon mb-2">
                                    <i class="fas fa-dollar-sign fa-2x text-success opacity-75"></i>
                                </div>
                                <h3 class="mb-1">$<?php echo formatPrice($stats['total_amount']); ?></h3>
                                <p class="text-muted mb-0">Monto Total</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card text-center p-3">
                                <div class="stat-icon mb-2">
                                    <i class="fas fa-calendar-check fa-2x text-info opacity-75"></i>
                                </div>
                                <h3 class="mb-1"><?php echo $stats['active_days']; ?></h3>
                                <p class="text-muted mb-0">Días Activos</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para cambiar avatar -->
<div class="modal fade" id="avatarModal" tabindex="-1" aria-labelledby="avatarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="avatarModalLabel">Cambiar Avatar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="upload_avatar.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="avatarFile" class="form-label">Selecciona una imagen</label>
                        <input type="file" class="form-control" id="avatarFile" name="avatar" accept="image/*" required>
                        <div class="form-text">Formatos permitidos: JPG, PNG. Tamaño máximo: 2MB</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Validación del formulario
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.needs-validation');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        const newPassword = form.querySelector('[name="new_password"]');
        const confirmPassword = form.querySelector('[name="confirm_password"]');
        
        if (newPassword.value && newPassword.value !== confirmPassword.value) {
            event.preventDefault();
            alert('Las contraseñas nuevas no coinciden');
        }
        
        form.classList.add('was-validated');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
