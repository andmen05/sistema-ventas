<?php
require_once '../includes/header.php';

// Verificar si es administrador
if ($_SESSION['role'] !== 'admin') {
    header('Location: /sistema-ventas/pages/dashboard.php');
    exit();
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Gestión de Roles</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#roleModal">
            <i class="fas fa-plus"></i> Nuevo Rol
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="rolesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Rol -->
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rol</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="roleForm">
                <div class="modal-body">
                    <input type="hidden" id="roleId" name="roleId">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre del Rol</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    var table = $('#rolesTable').DataTable({
        ajax: {
            url: 'role_action.php?action=list',
            dataSrc: ''
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'description' },
            { data: 'created_at' },
            {
                data: null,
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-sm btn-warning edit-role" data-id="${row.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-role" data-id="${row.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });

    // Manejar envío del formulario
    $('#roleForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            id: $('#roleId').val(),
            name: $('#name').val(),
            description: $('#description').val()
        };

        $.ajax({
            url: 'role_action.php?action=' + (formData.id ? 'update' : 'create'),
            type: 'POST',
            data: formData,
            success: function(response) {
                $('#roleModal').modal('hide');
                table.ajax.reload();
                alert('Rol guardado correctamente');
            },
            error: function() {
                alert('Error al guardar el rol');
            }
        });
    });

    // Editar rol
    $('#rolesTable').on('click', '.edit-role', function() {
        var id = $(this).data('id');
        $.get('role_action.php?action=get&id=' + id, function(role) {
            $('#roleId').val(role.id);
            $('#name').val(role.name);
            $('#description').val(role.description);
            $('#roleModal').modal('show');
        });
    });

    // Eliminar rol
    $('#rolesTable').on('click', '.delete-role', function() {
        if (confirm('¿Está seguro de eliminar este rol?')) {
            var id = $(this).data('id');
            $.post('role_action.php?action=delete', { id: id }, function() {
                table.ajax.reload();
            });
        }
    });

    // Limpiar modal al abrirlo para nuevo rol
    $('#roleModal').on('show.bs.modal', function(e) {
        if (!$(e.relatedTarget).hasClass('edit-role')) {
            $('#roleForm')[0].reset();
            $('#roleId').val('');
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
