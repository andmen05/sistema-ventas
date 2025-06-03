<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verificar permisos
requirePermission('manage_users');

// Incluir el header después de verificar permisos
require_once '../includes/header.php';

// Obtener usuarios
$users = fetchAll("SELECT u.*, r.name as role_name 
                  FROM users u 
                  LEFT JOIN roles r ON u.role_id = r.id 
                  ORDER BY u.created_at DESC");

// Obtener roles para el formulario
$roles = fetchAll("SELECT * FROM roles ORDER BY name");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Gestión de Usuarios</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
        <i class="fas fa-plus"></i> Nuevo Usuario
    </button>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <?php echo $_SESSION['message']; ?>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="usersTable">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre Completo</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último Login</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                        <td>
                            <?php if ($user['role_name']): ?>
                            <span class="badge bg-<?php echo $user['role_name'] === 'admin' ? 'danger' : 
                                                       ($user['role_name'] === 'supervisor' ? 'success' : 
                                                       ($user['role_name'] === 'vendedor' ? 'primary' : 'info')); ?>">
                                <?php echo ucfirst($user['role_name']); ?>
                            </span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Sin rol</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo $user['status'] === 'active' ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </td>
                        <td><?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Nunca'; ?></td>
                        <td>
                            <button class="btn btn-sm btn-info edit-user" 
                                    data-bs-toggle="modal"
                                    data-bs-target="#userModal"
                                    data-id="<?php echo $user['id']; ?>"
                                    data-username="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
                                    data-fullname="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                    data-email="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                    data-role="<?php echo $user['role_id'] ?? ''; ?>"
                                    data-status="<?php echo $user['status'] ?? 'active'; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($user['id'] !== $_SESSION['user_id'] && $user['role_name'] !== 'admin'): ?>
                            <button class="btn btn-sm btn-danger delete-user" 
                                    data-id="<?php echo $user['id']; ?>"
                                    data-username="<?php echo htmlspecialchars($user['username'] ?? ''); ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para crear/editar usuario -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm" action="user_action.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="user_id" id="userId">

                    <div class="mb-3">
                        <label for="username" class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="form-text text-muted">Dejar en blanco para mantener la contraseña actual al editar</small>
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="role_id" class="form-label">Rol</label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <option value="">Seleccionar rol...</option>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>">
                                <?php echo htmlspecialchars($role['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Estado</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                        </select>
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
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTables en español
    $('#usersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[1, 'asc']] // Ordenar por nombre
    });

    // Limpiar modal al abrir para nuevo usuario
    $('#userModal').on('show.bs.modal', function(e) {
        if (!e.relatedTarget.classList.contains('edit-user')) {
            this.querySelector('form').reset();
            this.querySelector('[name=action]').value = 'create';
            this.querySelector('.modal-title').textContent = 'Nuevo Usuario';
            $('#userId').val('');
            $('#password').closest('.mb-3').find('small').show();
        }
    });

    // Configurar modal para editar usuario
    $('.edit-user').on('click', function() {
        const modal = $('#userModal');
        const form = modal.find('form');
        const username = $(this).data('username');

        // Actualizar título y acción
        modal.find('.modal-title').text('Editar Usuario');
        form.find('[name=action]').val('update');

        // Llenar datos del usuario
        $('#userId').val($(this).data('id'));
        $('#username').val(username);
        $('#username').prop('readonly', true);
        $('#full_name').val($(this).data('fullname'));
        $('#email').val($(this).data('email'));
        $('#role_id').val($(this).data('role'));
        $('#status').val($(this).data('status'));

        // La contraseña es opcional al editar
        $('#password').prop('required', false);

        // Limpiar y actualizar mensaje de contraseña
        $('#password').val('');
        $('#password').closest('.mb-3').find('small')
            .text('Dejar en blanco para mantener la contraseña actual')
            .show();
    });

    // Nuevo usuario
    $('[data-bs-target="#userModal"]').click(function() {
        const modal = $('#userModal');
        const form = modal.find('form')[0];
        
        // Reiniciar formulario
        form.reset();
        
        // Configurar para nuevo usuario
        modal.find('.modal-title').text('Nuevo Usuario');
        modal.find('[name=action]').val('create');
        $('#userId').val('');
        $('#username').prop('readonly', false);
        $('#password').prop('required', true);
        
        // Mostrar mensaje de contraseña requerida
        $('#password').closest('.mb-3').find('small')
            .text('La contraseña es requerida para nuevos usuarios')
            .show();
        modal.find('#password').attr('required', 'required');
    });

    // Validar formulario antes de enviar
    $('#userForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validar contraseña para usuarios nuevos
        if ($('#userId').val() === '' && $('#password').val() === '') {
            alert('La contraseña es requerida para nuevos usuarios');
            return false;
        }
        
        // Enviar formulario
        this.submit();
        modal.modal('show');
    });

    // Eliminar usuario
    $('.delete-user').click(function() {
        if (confirm('¿Estás seguro de que deseas eliminar este usuario?')) {
            const userId = $(this).data('id');
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'user_action.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            
            const userIdInput = document.createElement('input');
            userIdInput.type = 'hidden';
            userIdInput.name = 'user_id';
            userIdInput.value = userId;
            
            form.appendChild(actionInput);
            form.appendChild(userIdInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
