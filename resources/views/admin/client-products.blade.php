@extends('partials.Layouts.master')

@section('title', 'منتجات العميل | Herozi')
@section('sub-title', 'منتجات العميل')
@section('pagetitle', 'منتجات: ' . $client->name)
@section('buttonTitle', '<i class="ri-add-line me-1"></i> إضافة منتج')
@section('modalTarget', 'addProductModal')

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endsection

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card mb-0 h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">قائمة منتجات: {{ $client->name }}</h5>
            </div>
            <div class="card-body">
                <table class="data-table-basic table-hover align-middle table table-nowrap w-100" id="products-table">
                    <thead class="bg-light bg-opacity-30">
                        <tr>
                            <th>الاسم</th>
                            <th>SKU</th>
                            <th>سعر الشراء</th>
                            <th>سعر الجملة</th>
                            <th>سعر المفرد</th>
                            <th>نوع الوحدة</th>
                            <th>العدد الكلي</th>
                            <th>المتبقي</th>
                            <th>الحد الأدنى</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                        <tr class="{{ $product->is_low_stock ? 'table-danger' : '' }}">
                            <td>{{ $product->name }}</td>
                            <td><span class="badge bg-secondary">{{ $product->sku }}</span></td>
                            <td>{{ number_format($product->purchase_price, 2) }}</td>
                            <td>{{ number_format($product->wholesale_price, 2) }}</td>
                            <td>{{ number_format($product->retail_price, 2) }}</td>
                            <td>
                                @if($product->unit_type == 'weight')
                                    <span class="badge bg-info">وزن: {{ $product->weight }} {{ $product->weight_unit == 'kg' ? 'كيلو' : 'غرام' }}</span>
                                @elseif($product->unit_type == 'piece')
                                    <span class="badge bg-primary">قطعة</span>
                                @elseif($product->unit_type == 'carton')
                                    <span class="badge bg-warning">كارتون ({{ $product->pieces_per_carton }} قطعة)</span>
                                @endif
                            </td>
                            <td>{{ number_format($product->total_quantity, 2) }}</td>
                            <td>
                                <span class="{{ $product->is_low_stock ? 'text-danger fw-bold' : '' }}">
                                    {{ number_format($product->remaining_quantity, 2) }}
                                </span>
                            </td>
                            <td>{{ number_format($product->min_quantity, 2) }}</td>
                            <td>
                                @if($product->is_low_stock)
                                    <span class="badge bg-danger">منخفض</span>
                                @else
                                    <span class="badge bg-success">طبيعي</span>
                                @endif
                            </td>
                            <td>
                                <div class="hstack gap-2">
                                    <button type="button" class="btn btn-sm btn-primary edit-product" data-id="{{ $product->id }}" title="تعديل">
                                        <i class="ri-pencil-line"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger delete-product" data-id="{{ $product->id }}" title="حذف">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="addProductModalLabel">إضافة منتج جديد</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="product-form">
                @csrf
                <input type="hidden" name="_method" id="_method" value="POST">
                <input type="hidden" name="product_id" id="product_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">اسم المنتج <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="sku" class="form-label">SKU (كود المنتج) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sku" name="sku" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="purchase_price" class="form-label">سعر الشراء <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="purchase_price" name="purchase_price" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="wholesale_price" class="form-label">سعر البيع جملة <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="wholesale_price" name="wholesale_price" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="retail_price" class="form-label">سعر البيع مفرد <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="retail_price" name="retail_price" min="0" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="unit_type" class="form-label">نوع الوحدة <span class="text-danger">*</span></label>
                        <select class="form-select" id="unit_type" name="unit_type" required>
                            <option value="">اختر نوع الوحدة</option>
                            <option value="weight">وزن</option>
                            <option value="piece">قطعة</option>
                            <option value="carton">كارتون</option>
                        </select>
                    </div>

                    <!-- Weight fields (shown when unit_type = weight) -->
                    <div id="weight-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="weight" class="form-label">الوزن <span class="text-danger">*</span></label>
                                <input type="number" step="0.001" class="form-control" id="weight" name="weight" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="weight_unit" class="form-label">وحدة الوزن <span class="text-danger">*</span></label>
                                <select class="form-select" id="weight_unit" name="weight_unit">
                                    <option value="">اختر الوحدة</option>
                                    <option value="kg">كيلو</option>
                                    <option value="g">غرام</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Carton fields (shown when unit_type = carton) -->
                    <div id="carton-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="pieces_per_carton" class="form-label">عدد القطع في الكارتون <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="pieces_per_carton" name="pieces_per_carton" min="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="piece_price_in_carton" class="form-label">سعر القطعة داخل الكارتون <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="piece_price_in_carton" name="piece_price_in_carton" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="total_quantity" class="form-label">العدد الكلي <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="total_quantity" name="total_quantity" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="remaining_quantity" class="form-label">العدد المتبقي <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="remaining_quantity" name="remaining_quantity" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="min_quantity" class="form-label">الحد الأدنى (للتنبيه) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="min_quantity" name="min_quantity" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="save-product-btn">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableElement = document.getElementById('products-table');
        const productForm = document.getElementById('product-form');
        const addProductModal = new bootstrap.Modal(document.getElementById('addProductModal'));
        const addProductModalLabel = document.getElementById('addProductModalLabel');
        const methodField = document.getElementById('_method');
        const productIdField = document.getElementById('product_id');
        const unitTypeSelect = document.getElementById('unit_type');
        const weightFields = document.getElementById('weight-fields');
        const cartonFields = document.getElementById('carton-fields');
        const totalQuantityInput = document.getElementById('total_quantity');
        const remainingQuantityInput = document.getElementById('remaining_quantity');

        // Initialize DataTable
        const dataTable = new DataTable(tableElement, {
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json'
            },
            order: [[0, 'asc']],
            pageLength: 10,
            responsive: true,
        });

        // Show/hide fields based on unit type
        unitTypeSelect.addEventListener('change', function() {
            const unitType = this.value;
            weightFields.style.display = unitType === 'weight' ? 'block' : 'none';
            cartonFields.style.display = unitType === 'carton' ? 'block' : 'none';

            // Clear fields when hidden
            if (unitType !== 'weight') {
                document.getElementById('weight').value = '';
                document.getElementById('weight_unit').value = '';
            }
            if (unitType !== 'carton') {
                document.getElementById('pieces_per_carton').value = '';
                document.getElementById('piece_price_in_carton').value = '';
            }
        });

        // Auto-set remaining quantity = total quantity when total quantity changes
        totalQuantityInput.addEventListener('input', function() {
            if (!productIdField.value) { // Only on add, not edit
                remainingQuantityInput.value = this.value;
            }
            // Update max for remaining quantity
            remainingQuantityInput.setAttribute('max', this.value);
        });

        // Show Add Product Modal
        document.querySelector('.breadcrumb-arrow').closest('.hstack').querySelector('.btn-primary').addEventListener('click', function() {
            productForm.reset();
            addProductModalLabel.textContent = 'إضافة منتج جديد';
            methodField.value = 'POST';
            productIdField.value = '';
            weightFields.style.display = 'none';
            cartonFields.style.display = 'none';
            addProductModal.show();
        });

        // Edit Product
        tableElement.querySelector('tbody').addEventListener('click', function(e) {
            if (e.target.closest('.edit-product')) {
                const productId = e.target.closest('.edit-product').dataset.id;
                fetch(`/admin/products/${productId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const product = data.product;
                            document.getElementById('name').value = product.name;
                            document.getElementById('sku').value = product.sku;
                            document.getElementById('purchase_price').value = product.purchase_price;
                            document.getElementById('wholesale_price').value = product.wholesale_price;
                            document.getElementById('retail_price').value = product.retail_price;
                            document.getElementById('unit_type').value = product.unit_type;
                            document.getElementById('total_quantity').value = product.total_quantity;
                            document.getElementById('remaining_quantity').value = product.remaining_quantity;
                            document.getElementById('min_quantity').value = product.min_quantity;

                            // Show/hide fields based on unit type
                            unitTypeSelect.dispatchEvent(new Event('change'));

                            if (product.unit_type === 'weight') {
                                document.getElementById('weight').value = product.weight;
                                document.getElementById('weight_unit').value = product.weight_unit;
                            } else if (product.unit_type === 'carton') {
                                document.getElementById('pieces_per_carton').value = product.pieces_per_carton;
                                document.getElementById('piece_price_in_carton').value = product.piece_price_in_carton;
                            }

                            addProductModalLabel.textContent = 'تعديل منتج';
                            methodField.value = 'PUT';
                            productIdField.value = product.id;
                            addProductModal.show();
                        }
                    });
            }

            // Delete Product
            if (e.target.closest('.delete-product')) {
                const productId = e.target.closest('.delete-product').dataset.id;
                Swal.fire({
                    title: 'هل أنت متأكد؟',
                    text: "لن تتمكن من التراجع عن هذا!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'نعم، احذفه!',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/admin/products/${productId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('تم الحذف!', data.message, 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('خطأ!', data.message || 'حدث خطأ أثناء الحذف.', 'error');
                            }
                        });
                    }
                });
            }
        });

        // Form submit
        productForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const productId = productIdField.value;
            const clientId = {{ $client->id }};
            const url = productId ? `/admin/products/${productId}` : `/admin/clients/${clientId}/products`;
            const method = methodField.value;

            if (method === 'PUT') {
                formData.append('_method', 'PUT');
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('نجاح!', data.message, 'success').then(() => {
                        addProductModal.hide();
                        location.reload();
                    });
                } else {
                    let errorMessages = '';
                    if (data.errors) {
                        for (const key in data.errors) {
                            errorMessages += `<li>${data.errors[key][0]}</li>`;
                        }
                    }
                    Swal.fire('خطأ!', errorMessages ? `<ul>${errorMessages}</ul>` : (data.message || 'حدث خطأ ما.'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('خطأ!', 'حدث خطأ غير متوقع.', 'error');
            });
        });
    });
</script>
<!-- App js -->
<script type="module" src="{{ asset('assets/js/app.js') }}"></script>
@endsection

