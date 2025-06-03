// Variables globales
let products = [];
let total = 0;

// Inicializar componentes cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Select2 para búsqueda de productos
    $('#product_search').select2({
        theme: 'bootstrap-5',
        language: 'es',
        placeholder: 'Buscar producto por código o nombre',
        allowClear: true
    });

    // Inicializar Select2 para clientes
    $('#customer_id').select2({
        theme: 'bootstrap-5',
        language: 'es',
        placeholder: 'Seleccionar cliente',
        allowClear: true
    });

    // Manejar la selección de productos
    $('#product_search').on('select2:select', function(e) {
        const option = e.params.data.element;
        const product = {
            id: option.value,
            code: $(option).data('code'),
            name: $(option).data('name'),
            price: parseFloat($(option).data('price')),
            stock: parseInt($(option).data('stock')),
            quantity: 1
        };
        
        addProduct(product);
        $(this).val('').trigger('change');
    });

    // Manejar el envío del formulario
    $('#sale-form').on('submit', function(e) {
        // Obtener los productos de la tabla
        var products = [];
        $('#products-table tbody tr').each(function() {
            var product = {
                id: $(this).find('.product-id').val(),
                quantity: $(this).find('.quantity').val(),
                price: $(this).find('.price').text().replace('$', '')
            };
            products.push(product);
        });

        // Agregar los productos al formulario
        $('<input>').attr({
            type: 'hidden',
            name: 'products',
            value: JSON.stringify(products)
        }).appendTo(this);
    });

    // Mostrar/ocultar campo de referencia según método de pago
    $('#payment_method').on('change', function() {
        const method = $(this).val();
        const referenceGroup = $('#reference_group');
        
        if (method === 'card' || method === 'transfer') {
            referenceGroup.show();
            $('#reference_number').prop('required', true);
        } else {
            referenceGroup.hide();
            $('#reference_number').prop('required', false);
        }
    });

    // Atajos de teclado
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'b') { // Ctrl + B para buscar producto
            e.preventDefault();
            $('#product_search').select2('open');
        } else if (e.ctrlKey && e.key === 'g') { // Ctrl + G para guardar venta
            e.preventDefault();
            $('#saleForm').submit();
        }
    });
});

// Función para agregar un producto a la venta
function addProduct(product) {
    // Verificar si el producto ya existe
    const existingProduct = products.find(p => p.id === product.id);
    if (existingProduct) {
        if (existingProduct.quantity >= product.stock) {
            toastr.error('Stock insuficiente');
            return;
        }
        existingProduct.quantity++;
        updateProductRow(existingProduct);
    } else {
        products.push(product);
        addProductRow(product);
    }
    
    updateTotal();
}

// Función para agregar una fila de producto
function addProductRow(product) {
    const row = `
        <tr id="product-${product.id}">
            <td>${product.code}</td>
            <td>${product.name}</td>
            <td class="text-end">$${formatPrice(product.price)}</td>
            <td class="text-end">
                <div class="input-group input-group-sm">
                    <button type="button" class="btn btn-outline-secondary" onclick="updateQuantity(${product.id}, -1)">-</button>
                    <input type="number" class="form-control text-end quantity-input" value="${product.quantity}" 
                           min="1" max="${product.stock}" onchange="updateQuantityInput(${product.id}, this.value)">
                    <button type="button" class="btn btn-outline-secondary" onclick="updateQuantity(${product.id}, 1)">+</button>
                </div>
            </td>
            <td class="text-end">$${formatPrice(product.price * product.quantity)}</td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeProduct(${product.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    
    $('#saleDetails tbody').append(row);
}

// Función para actualizar una fila de producto
function updateProductRow(product) {
    const row = $(`#product-${product.id}`);
    row.find('.quantity-input').val(product.quantity);
    row.find('td:eq(4)').text(`$${formatPrice(product.price * product.quantity)}`);
}

// Función para actualizar la cantidad de un producto
function updateQuantity(productId, change) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    
    const newQuantity = product.quantity + change;
    if (newQuantity < 1 || newQuantity > product.stock) {
        toastr.error('Cantidad no válida');
        return;
    }
    
    product.quantity = newQuantity;
    updateProductRow(product);
    updateTotal();
}

// Función para actualizar cantidad desde input
function updateQuantityInput(productId, value) {
    const quantity = parseInt(value);
    const product = products.find(p => p.id === productId);
    if (!product) return;
    
    if (quantity < 1 || quantity > product.stock || isNaN(quantity)) {
        toastr.error('Cantidad no válida');
        updateProductRow(product);
        return;
    }
    
    product.quantity = quantity;
    updateProductRow(product);
    updateTotal();
}

// Función para eliminar un producto
function removeProduct(productId) {
    products = products.filter(p => p.id !== productId);
    $(`#product-${productId}`).remove();
    updateTotal();
}

// Función para actualizar el total
function updateTotal() {
    total = products.reduce((sum, product) => sum + (product.price * product.quantity), 0);
    $('#total_display').text(`$${formatPrice(total)}`);
}

// Función para formatear precios
function formatPrice(price) {
    return Math.round(price).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}
