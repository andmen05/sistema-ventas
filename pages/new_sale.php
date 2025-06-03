<?php
require_once '../includes/header.php';
require_once '../includes/db.php';
?>
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<!-- Toastr CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
<?php

// Obtener productos
$products = fetchAll("SELECT id, code, name, price, stock FROM products WHERE stock > 0 ORDER BY name");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Nueva Venta</h1>
</div>

<form action="sale_action.php" method="POST" id="saleForm">
    <input type="hidden" name="products" id="products" value="[]">
    <input type="hidden" name="total_amount" id="total_amount_hidden" value="0">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Detalles de la Venta</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="product_search" class="form-label">Buscar Producto</label>
                        <select class="form-control select2" id="product_search">
                            <option value="">Seleccione un producto</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" 
                                        data-code="<?php echo $product['code']; ?>"
                                        data-name="<?php echo $product['name']; ?>"
                                        data-price="<?php echo $product['price']; ?>"
                                        data-stock="<?php echo $product['stock']; ?>">
                                    <?php echo $product['code'] . ' - ' . $product['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table" id="saleDetails">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Resumen</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Cliente</label>
                        <select class="form-control select2" id="customer_id" name="customer_id" required>
                            <option value="">Seleccione un cliente</option>
                            <?php
                            $customers = fetchAll("SELECT id, document_number, name FROM customers ORDER BY name");
                            foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>">
                                    <?php echo $customer['document_number'] . ' - ' . $customer['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#quickCustomerModal">
                                <i class="fas fa-plus"></i> Cliente Rápido
                            </button>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <h5>Total:</h5>
                        <h5>$<span id="total">0.00</span></h5>
                    </div>

                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Método de Pago</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="">Seleccione un método</option>
                            <option value="cash">Efectivo</option>
                            <option value="card">Tarjeta</option>
                            <option value="transfer">Transferencia</option>
                        </select>
                    </div>

                    <div id="payment_details" class="mb-3" style="display: none;">
                        <!-- Campos para efectivo -->
                        <div id="cash_details" style="display: none;">
                            <div class="mb-2">
                                <label for="cash_received" class="form-label">Efectivo Recibido</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="cash_received" name="cash_received" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label for="cash_change" class="form-label">Cambio</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="text" class="form-control" id="cash_change" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Campos para tarjeta -->
                        <div id="card_details" style="display: none;">
                            <div class="mb-2">
                                <label for="card_reference" class="form-label">Número de Referencia</label>
                                <input type="text" class="form-control" id="card_reference" name="card_reference">
                            </div>
                        </div>

                        <!-- Campos para transferencia -->
                        <div id="transfer_details" style="display: none;">
                            <div class="mb-2">
                                <label for="transfer_reference" class="form-label">Número de Referencia</label>
                                <input type="text" class="form-control" id="transfer_reference" name="transfer_reference">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save"></i> Completar Venta
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Select2 con búsqueda mejorada
    $('.select2').select2({
        theme: 'bootstrap-5',
        placeholder: 'Buscar producto por código o nombre',
        width: '100%',
        language: 'es',
        allowClear: true,
        dropdownParent: $('#saleForm')
    });

    let total = 0;
    const products = {};

    // Agregar producto a la venta
    $('#product_search').on('change', function() {
        const option = $(this).find('option:selected');
        if (!option.val()) return;

        const productId = option.val();
        const code = option.data('code');
        const name = option.data('name');
        const price = option.data('price');
        const stock = option.data('stock');

        // Si el producto ya está en la lista, incrementar cantidad en vez de mostrar error
        if (products[productId]) {
            const row = $(`tr[data-id="${productId}"]`);
            const quantityInput = row.find('.quantity');
            const currentQuantity = parseInt(quantityInput.val());
            if (currentQuantity < stock) {
                quantityInput.val(currentQuantity + 1).trigger('change');
            } else {
                alert('Stock máximo alcanzado para este producto');
            }
            $(this).val('').trigger('change');
            return;
        }

        const row = `
            <tr data-id="${productId}">
                <td>${code}</td>
                <td>${name}</td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control form-control-sm price" 
                               value="${price}" step="1" min="0">
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm" style="width: 120px">
                        <button type="button" class="btn btn-outline-secondary btn-sm decrease-qty">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="form-control form-control-sm quantity text-center" 
                               min="1" max="${stock}" value="1">
                        <button type="button" class="btn btn-outline-secondary btn-sm increase-qty">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </td>
                <td>$<span class="subtotal">${formatPrice(price)}</span></td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-product">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#saleDetails tbody').append(row);
        products[productId] = { price, stock };
        updateTotal();
        $(this).val('').trigger('change');

        // Mantener el foco en el select para seguir agregando productos
        setTimeout(() => {
            $('#product_search').select2('open');
        }, 100);
    });

    // Manejar cambios en el método de pago
    $('#payment_method').on('change', function() {
        const method = $(this).val();
        $('#payment_details').hide();
        $('#cash_details, #card_details, #transfer_details').hide();

        if (method) {
            $('#payment_details').show();
            $(`#${method}_details`).show();

            // Limpiar campos
            $('#cash_received, #card_reference, #transfer_reference').val('');
            $('#cash_change').val('');

            // Si es efectivo, enfocar el campo de efectivo recibido
            if (method === 'cash') {
                $('#cash_received').focus();
            }
        }
    });

    // Calcular cambio en efectivo
    $('#cash_received').on('input', function() {
        const received = parseFloat($(this).val()) || 0;
        const total = parseFloat($('#total').text());
        const change = received - total;
        $('#cash_change').val(change >= 0 ? formatPrice(change) : '');

        // Validar que el efectivo sea suficiente
        if (change < 0) {
            $(this).addClass('is-invalid');
            $('#completeSale').prop('disabled', true);
        } else {
            $(this).removeClass('is-invalid');
            $('#completeSale').prop('disabled', false);
        }
    });

    // Aumentar cantidad con botón
    $(document).on('click', '.increase-qty', function() {
        const input = $(this).siblings('.quantity');
        const currentQty = parseInt(input.val());
        const maxQty = parseInt(input.attr('max'));
        if (currentQty < maxQty) {
            input.val(currentQty + 1).trigger('change');
        }
    });

    // Disminuir cantidad con botón
    $(document).on('click', '.decrease-qty', function() {
        const input = $(this).siblings('.quantity');
        const currentQty = parseInt(input.val());
        if (currentQty > 1) {
            input.val(currentQty - 1).trigger('change');
        }
    });

    // Actualizar al cambiar precio o cantidad
    $(document).on('change', '.price, .quantity', function() {
        const row = $(this).closest('tr');
        const productId = parseInt(row.data('id'));
        const quantity = parseInt(row.find('.quantity').val());
        const price = parseFloat(row.find('.price').val());
        const subtotal = price * quantity;
        
        row.find('.subtotal').text(formatPrice(subtotal));
        updateTotal();
    });

    // Eliminar producto
    $(document).on('click', '.remove-product', function() {
        const row = $(this).closest('tr');
        row.remove();
        updateTotal();
    });

    // Actualizar total y productos
    function updateTotal() {
        let total = 0;
        let currentProducts = [];
        
        $('#saleDetails tbody tr').each(function() {
            const row = $(this);
            const productId = parseInt(row.data('id'));
            const quantity = parseInt(row.find('.quantity').val());
            const price = parseFloat(row.find('.price').val());
            const subtotal = price * quantity;
            
            currentProducts.push({
                id: productId,
                quantity: quantity,
                price: price
            });
            
            total += subtotal;
            row.find('.subtotal').text(formatPrice(subtotal));
        });
        
        // Actualizar total visible
        $('#total').text(formatPrice(total));
        
        // Actualizar campos hidden
        document.getElementById('total_amount_hidden').value = total;
        document.getElementById('products').value = JSON.stringify(currentProducts);
        
        // Debug
        console.log('Actualizando venta:', {
            productos: currentProducts,
            total: total
        });
    }

    // Formatear precio en pesos colombianos
    function formatPrice(price) {
        return new Intl.NumberFormat('es-CO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(price);
    }

    // Atajos de teclado
    $(document).on('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 'b': // Ctrl/Cmd + B para buscar producto
                    e.preventDefault();
                    $('#product_search').select2('open');
                    break;
                case 's': // Ctrl/Cmd + S para guardar venta
                    e.preventDefault();
                    if ($('#saleDetails tbody tr').length > 0) {
                        $('#saleForm').submit();
                    }
                    break;
            }
        }
    });

    // Validar formulario
    $('#saleForm').on('submit', function(e) {
        if ($('#saleDetails tbody tr').length === 0) {
            e.preventDefault();
            alert('Debe agregar al menos un producto a la venta');
            return;
        }

        // Actualizar productos y total antes de enviar
        updateTotal();

        // Validar cliente
        if (!$('#customer_id').val()) {
            e.preventDefault();
            alert('Debe seleccionar un cliente');
            return;
        }

        // Validar método de pago
        if (!$('#payment_method').val()) {
            e.preventDefault();
            alert('Debe seleccionar un método de pago');
            return;
        }
    });
});
</script>

<!-- Modal para Cliente Rápido -->
<div class="modal fade" id="quickCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cliente Rápido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickCustomerForm" action="customer_action.php" method="POST">
                <input type="hidden" name="is_quick_add" value="1">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="quick_document_type" class="form-label">Tipo Doc.</label>
                            <select class="form-select" id="quick_document_type" name="document_type" required>
                                <option value="DNI">DNI</option>
                                <option value="RUC">RUC</option>
                                <option value="PASSPORT">Pasaporte</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label for="quick_document_number" class="form-label">Número</label>
                            <input type="text" class="form-control" id="quick_document_number" name="document_number" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="quick_name" class="form-label">Nombre/Razón Social</label>
                        <input type="text" class="form-control" id="quick_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="quick_phone" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="quick_phone" name="phone">
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
// Manejar la creación rápida de cliente
$('#quickCustomerForm').on('submit', function(e) {
    e.preventDefault();
    const $form = $(this);
    const $submitBtn = $form.find('button[type="submit"]');
    
    // Debug - mostrar datos que se envían
    console.log('Enviando datos:', $form.serialize());
    
    // Deshabilitar botón y mostrar loading
    $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
    
    $.ajax({
        url: 'customer_action.php',
        method: 'POST',
        data: $form.serialize(),
        dataType: 'json'
    })
    .done(function(data) {
        console.log('Respuesta del servidor:', data);
        
        if (data.success) {
            // Agregar el nuevo cliente al select
            const newOption = new Option(
                data.customer.document_number + ' - ' + data.customer.name,
                data.customer.id,
                true,
                true
            );
            $('#customer_id').append(newOption).trigger('change');
            
            // Cerrar modal y limpiar formulario
            $('#quickCustomerModal').modal('hide');
            $form[0].reset();
            
            // Mostrar mensaje de éxito
            toastr.success(data.message || 'Cliente creado exitosamente');
        } else {
            toastr.error(data.message || 'Error al crear el cliente');
        }
    })
    .fail(function(xhr, status, error) {
        console.error('Error en la petición:', {
            status: status,
            error: error,
            response: xhr.responseText
        });
        toastr.error('Error de conexión al servidor');
    })
    .always(function() {
        // Restaurar botón
        $submitBtn.prop('disabled', false).html('Guardar');
    });
});
</script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Select2 Spanish -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
// Configurar Toastr
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    timeOut: 3000
};
</script>

<?php require_once '../includes/footer.php'; ?>
