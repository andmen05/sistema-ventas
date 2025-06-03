<?php
require_once '../includes/header.php';
require_once '../includes/db.php';

// Manejar eliminación
if (isset($_POST['delete'])) {
    $id = (int)$_POST['id'];
    try {
        query("DELETE FROM categories WHERE id = ?", [$id]);
        $_SESSION['message'] = getAlert('success', 'Categoría eliminada correctamente');
    } catch (Exception $e) {
        $_SESSION['message'] = getAlert('danger', 'No se puede eliminar la categoría porque tiene productos asociados');
    }
    header('Location: categories.php');
    exit();
}

// Obtener categorías
$categories = fetchAll("SELECT c.*, COUNT(p.id) as product_count 
                      FROM categories c 
                      LEFT JOIN products p ON c.id = p.category_id 
                      GROUP BY c.id 
                      ORDER BY c.name");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Categorías</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
        <i class="fas fa-plus"></i> Nueva Categoría
    </button>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <?php echo $_SESSION['message']; ?>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="categoriesTable">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Productos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo $category['name']; ?></td>
                        <td><?php echo $category['description']; ?></td>
                        <td><?php echo $category['product_count']; ?></td>
                        <td>
                            <button class="btn btn-sm btn-info edit-category" 
                                    data-id="<?php echo $category['id']; ?>"
                                    data-name="<?php echo $category['name']; ?>"
                                    data-description="<?php echo $category['description']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar esta categoría?');">
                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                <button type="submit" name="delete" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Categoría -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="categoryForm" action="category_action.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="categoryId">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre</label>
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
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    $('#categoriesTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });

    // Manejar edición de categoría
    $('.edit-category').click(function() {
        const data = $(this).data();
        $('#categoryId').val(data.id);
        $('#name').val(data.name);
        $('#description').val(data.description);
        $('#categoryModal').modal('show');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
