<?php
require_once '../includes/header.php';
require_once '../includes/db.php';

// Manejar eliminación
if (isset($_POST['delete'])) {
    $id = (int)$_POST['id'];
    query("DELETE FROM products WHERE id = ?", [$id]);
    $_SESSION['message'] = getAlert('success', 'Producto eliminado correctamente');
    header('Location: products.php');
    exit();
}

// Obtener categorías para el formulario
$categories = fetchAll("SELECT * FROM categories ORDER BY name");

// Obtener productos con sus categorías
$products = fetchAll("SELECT p.*, c.name as category_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id 
                     ORDER BY p.name");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Productos</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
        <i class="fas fa-plus"></i> Nuevo Producto
    </button>
</div>

<?php if (isset($_SESSION['message'])): ?>
    <?php echo $_SESSION['message']; ?>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="productsTable">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product['code']; ?></td>
                        <td><?php echo $product['name']; ?></td>
                        <td><?php echo $product['category_name']; ?></td>
                        <td>$<?php echo formatPrice($product['price']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $product['stock'] <= 5 ? 'danger' : 'success'; ?>">
                                <?php echo $product['stock']; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info edit-product" 
                                    data-id="<?php echo $product['id']; ?>"
                                    data-code="<?php echo $product['code']; ?>"
                                    data-name="<?php echo $product['name']; ?>"
                                    data-description="<?php echo $product['description']; ?>"
                                    data-price="<?php echo $product['price']; ?>"
                                    data-stock="<?php echo $product['stock']; ?>"
                                    data-category="<?php echo $product['category_id']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este producto?');">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
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

<!-- Modal para Crear/Editar Producto -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="productForm" action="product_action.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="productId">
                    <div class="mb-3">
                        <label for="code" class="form-label">Código</label>
                        <input type="text" class="form-control" id="code" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Precio</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="stock" name="stock" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Categoría</label>
                        <select class="form-control" id="category_id" name="category_id">
                            <option value="">Seleccione una categoría</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
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
    $('#productsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });

    // Manejar edición de producto
    $('.edit-product').click(function() {
        const data = $(this).data();
        $('#productId').val(data.id);
        $('#code').val(data.code);
        $('#name').val(data.name);
        $('#description').val(data.description);
        $('#price').val(data.price);
        $('#stock').val(data.stock);
        $('#category_id').val(data.category);
        $('#productModal').modal('show');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
