<?php
require_once '../includes/header.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Obtener todos los clientes
$customers = fetchAll("SELECT * FROM customers ORDER BY name");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Gestión de Clientes</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal">
        <i class="fas fa-plus"></i> Nuevo Cliente
    </button>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="customersTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Puntos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(($customer['document_type'] ?? '') . ': ' . ($customer['document_number'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($customer['name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($customer['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($customer['phone'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($customer['points'] ?? '0'); ?></td>
                        <td>
                            <button class="btn btn-sm btn-info edit-customer" data-id="<?php echo $customer['id']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-success view-history" data-id="<?php echo $customer['id']; ?>">
                                <i class="fas fa-history"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Nuevo/Editar Cliente -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="customerForm" action="customer_action.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="customer_id" id="customer_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="document_type" class="form-label">Tipo Doc.</label>
                            <select class="form-select" id="document_type" name="document_type" required>
                                <option value="DNI">DNI</option>
                                <option value="RUC">RUC</option>
                                <option value="PASSPORT">Pasaporte</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label for="document_number" class="form-label">Número</label>
                            <input type="text" class="form-control" id="document_number" name="document_number" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre/Razón Social</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Dirección</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
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

<!-- Modal para Historial de Cliente -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Historial del Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="customerHistory"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#customersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });

    // Editar Cliente
    $('.edit-customer').click(function() {
        const id = $(this).data('id');
        $.get('customer_action.php', { action: 'get', id: id }, function(data) {
            const customer = JSON.parse(data);
            $('#customer_id').val(customer.id);
            $('#document_type').val(customer.document_type);
            $('#document_number').val(customer.document_number);
            $('#name').val(customer.name);
            $('#email').val(customer.email);
            $('#phone').val(customer.phone);
            $('#address').val(customer.address);
            $('#modalTitle').text('Editar Cliente');
            $('#customerModal').modal('show');
        });
    });

    // Ver Historial
    $('.view-history').click(function() {
        const id = $(this).data('id');
        $.get('customer_action.php', { action: 'history', id: id }, function(data) {
            $('#customerHistory').html(data);
            $('#historyModal').modal('show');
        });
    });

    // Limpiar modal al cerrarse
    $('#customerModal').on('hidden.bs.modal', function() {
        $('#customerForm')[0].reset();
        $('#customer_id').val('');
        $('#modalTitle').text('Nuevo Cliente');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
