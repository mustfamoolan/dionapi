@extends('partials.Layouts.master')

@section('title', 'العملاء | Herozi')
@section('sub-title', 'العملاء')
@section('pagetitle', 'العملاء')

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endsection

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card mb-0 h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">قائمة العملاء</h5>
            </div>
            <div class="card-body">
                <table class="data-table-basic table-hover align-middle table table-nowrap w-100" id="clients-table">
                    <thead class="bg-light bg-opacity-30">
                        <tr>
                            <th>الاسم</th>
                            <th>البريد الإلكتروني</th>
                            <th>رقم الهاتف</th>
                            <th>العنوان</th>
                            <th>المحافظة</th>
                            <th>المدينة</th>
                            <th>المزود</th>
                            <th>الحالة</th>
                            <th>مفعل</th>
                            <th>تاريخ انتهاء التفعيل</th>
                            <th>آخر تسجيل دخول</th>
                            <th>تاريخ التسجيل</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Client Modal -->
<div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="editClientModalLabel">تعديل بيانات العميل</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="edit-client-form">
                @csrf
                <input type="hidden" id="edit_client_firebase_uid" name="firebase_uid">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_name" class="form-label">الاسم <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_phone" class="form-label">رقم الهاتف</label>
                            <input type="text" class="form-control" id="edit_phone" name="phone">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="edit_address" class="form-label">العنوان التفصيلي</label>
                            <textarea class="form-control" id="edit_address" name="address" rows="2"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_governorate" class="form-label">المحافظة</label>
                            <input type="text" class="form-control" id="edit_governorate" name="governorate">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_city" class="form-label">المدينة</label>
                            <input type="text" class="form-control" id="edit_city" name="city">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_status" class="form-label">الحالة</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="pending">في الانتظار</option>
                                <option value="active">مفعل</option>
                                <option value="banned">محظور</option>
                                <option value="expired">انتهى الاشتراك</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_is_active" class="form-label">مفعل</label>
                            <select class="form-select" id="edit_is_active" name="is_active">
                                <option value="1">نعم</option>
                                <option value="0">لا</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="edit_activation_expires_at" class="form-label">تاريخ انتهاء التفعيل</label>
                            <input type="date" class="form-control" id="edit_activation_expires_at" name="activation_expires_at">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Activate Client Modal -->
<div class="modal fade" id="activateClientModal" tabindex="-1" aria-labelledby="activateClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="activateClientModalLabel">تفعيل العميل</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="activate-client-form">
                @csrf
                <input type="hidden" id="activate_client_firebase_uid" name="firebase_uid">
                <div class="modal-body">
                    <p id="activate_client_name" class="mb-3"></p>
                    <div class="mb-3">
                        <label for="activation_months" class="form-label">مدة التفعيل (بالأشهر) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="activation_months" name="months" min="1" max="120" value="1" required>
                        <small class="form-text text-muted">اختر عدد الأشهر من 1 إلى 120</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success">تفعيل</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirm Status Change Modal -->
<div class="modal fade" id="confirmStatusModal" tabindex="-1" aria-labelledby="confirmStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="confirmStatusModalLabel">تأكيد تغيير الحالة</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="confirm-status-form">
                @csrf
                <input type="hidden" id="confirm_client_firebase_uid" name="firebase_uid">
                <input type="hidden" id="confirm_status" name="status">
                <div class="modal-body">
                    <p id="confirm_message"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="confirm_btn">تأكيد</button>
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
    $(document).ready(function() {
        const tableElement = $('#clients-table');
        const editModal = new bootstrap.Modal(document.getElementById('editClientModal'));
        const activateModal = new bootstrap.Modal(document.getElementById('activateClientModal'));
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmStatusModal'));

        // Initialize DataTable with AJAX
        const dataTable = tableElement.DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json'
            },
            processing: true,
            serverSide: false,
            ajax: {
                url: '{{ route("admin.clients.data") }}',
                type: 'GET',
            },
            columns: [
                { data: 'name', name: 'name' },
                { data: 'email', name: 'email' },
                { data: 'phone', name: 'phone' },
                { data: 'address', name: 'address' },
                { data: 'governorate', name: 'governorate' },
                { data: 'city', name: 'city' },
                { data: 'provider', name: 'provider', orderable: false },
                { data: 'status', name: 'status', orderable: false },
                { data: 'is_active', name: 'is_active', orderable: false },
                { data: 'activation_expires_at', name: 'activation_expires_at', orderable: false },
                { data: 'last_login_at', name: 'last_login_at', orderable: false },
                { data: 'created_at', name: 'created_at' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[11, 'desc']], // Order by created_at descending
            pageLength: 10,
            responsive: true,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        });

        // Use jQuery event delegation for action buttons (works with dynamically loaded content)
        $(document).on('click', '.edit-client', function(e) {
            e.preventDefault();
            const firebaseUid = $(this).data('firebase-uid');
            loadClientData(firebaseUid);
        });

        $(document).on('click', '.activate-client', function(e) {
            e.preventDefault();
            const firebaseUid = $(this).data('firebase-uid');
            const clientName = $(this).data('name');

            $('#activate_client_firebase_uid').val(firebaseUid);
            $('#activate_client_name').text(`هل تريد تفعيل العميل: ${clientName}؟`);
            $('#activation_months').val(1);
            activateModal.show();
        });

        $(document).on('click', '.ban-client', function(e) {
            e.preventDefault();
            const firebaseUid = $(this).data('firebase-uid');
            const clientName = $(this).data('name');

            $('#confirm_client_firebase_uid').val(firebaseUid);
            $('#confirm_status').val('banned');
            $('#confirm_message').text(`هل أنت متأكد من حظر العميل: ${clientName}؟`);
            $('#confirm_btn').removeClass().addClass('btn btn-danger').text('حظر');
            confirmModal.show();
        });

        $(document).on('click', '.pending-client', function(e) {
            e.preventDefault();
            const firebaseUid = $(this).data('firebase-uid');
            const clientName = $(this).data('name');

            $('#confirm_client_firebase_uid').val(firebaseUid);
            $('#confirm_status').val('pending');
            $('#confirm_message').text(`هل تريد وضع العميل: ${clientName} في قائمة الانتظار؟`);
            $('#confirm_btn').removeClass().addClass('btn btn-warning').text('وضع في الانتظار');
            confirmModal.show();
        });

        $(document).on('click', '.expire-client', function(e) {
            e.preventDefault();
            const firebaseUid = $(this).data('firebase-uid');
            const clientName = $(this).data('name');

            $('#confirm_client_firebase_uid').val(firebaseUid);
            $('#confirm_status').val('expired');
            $('#confirm_message').text(`هل تريد تعيين حالة العميل: ${clientName} كأنهاء اشتراك؟`);
            $('#confirm_btn').removeClass().addClass('btn btn-info').text('انتهى الاشتراك');
            confirmModal.show();
        });

        // Load client data for editing
        function loadClientData(firebaseUid) {
            $.ajax({
                url: `/admin/clients/${firebaseUid}`,
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(data) {
                    if (data.success) {
                        const client = data.client;
                        $('#edit_client_firebase_uid').val(client.firebase_uid);
                        $('#edit_name').val(client.name || '');
                        $('#edit_phone').val(client.phone || '');
                        $('#edit_address').val(client.address || '');
                        $('#edit_governorate').val(client.governorate || '');
                        $('#edit_city').val(client.city || '');
                        $('#edit_status').val(client.status || 'pending');
                        $('#edit_is_active').val(client.is_active ? '1' : '0');
                        $('#edit_activation_expires_at').val(client.activation_expires_at || '');
                        editModal.show();
                    } else {
                        Swal.fire('خطأ!', data.message || 'حدث خطأ ما', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire('خطأ!', 'حدث خطأ أثناء جلب بيانات العميل', 'error');
                }
            });
        }

        // Edit Form Submit
        $('#edit-client-form').on('submit', function(e) {
            e.preventDefault();

            const firebaseUid = $('#edit_client_firebase_uid').val();
            const formData = {
                name: $('#edit_name').val(),
                phone: $('#edit_phone').val(),
                address: $('#edit_address').val(),
                governorate: $('#edit_governorate').val(),
                city: $('#edit_city').val(),
                status: $('#edit_status').val(),
                is_active: $('#edit_is_active').val() === '1',
                activation_expires_at: $('#edit_activation_expires_at').val() || null,
            };

            $.ajax({
                url: `/admin/clients/${firebaseUid}`,
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                data: JSON.stringify(formData),
                success: function(data) {
                    if (data.success) {
                        Swal.fire('نجاح!', data.message, 'success').then(() => {
                            dataTable.ajax.reload(null, false);
                        });
                    } else {
                        Swal.fire('خطأ!', data.message || 'حدث خطأ ما', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire('خطأ!', 'حدث خطأ أثناء تحديث البيانات', 'error');
                },
                complete: function() {
                    editModal.hide();
                }
            });
        });

        // Activate Form Submit
        $('#activate-client-form').on('submit', function(e) {
            e.preventDefault();

            const firebaseUid = $('#activate_client_firebase_uid').val();
            const months = $('#activation_months').val();

            $.ajax({
                url: `/admin/clients/${firebaseUid}/status`,
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                data: JSON.stringify({
                    status: 'active',
                    months: parseInt(months)
                }),
                success: function(data) {
                    if (data.success) {
                        Swal.fire('نجاح!', data.message, 'success').then(() => {
                            dataTable.ajax.reload(null, false);
                        });
                    } else {
                        Swal.fire('خطأ!', data.message || 'حدث خطأ ما', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire('خطأ!', 'حدث خطأ أثناء التفعيل', 'error');
                },
                complete: function() {
                    activateModal.hide();
                }
            });
        });

        // Confirm Status Form Submit
        $('#confirm-status-form').on('submit', function(e) {
            e.preventDefault();

            const firebaseUid = $('#confirm_client_firebase_uid').val();
            const status = $('#confirm_status').val();

            $.ajax({
                url: `/admin/clients/${firebaseUid}/status`,
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                data: JSON.stringify({
                    status: status
                }),
                success: function(data) {
                    if (data.success) {
                        Swal.fire('نجاح!', data.message, 'success').then(() => {
                            dataTable.ajax.reload(null, false);
                        });
                    } else {
                        Swal.fire('خطأ!', data.message || 'حدث خطأ ما', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire('خطأ!', 'حدث خطأ أثناء تغيير الحالة', 'error');
                },
                complete: function() {
                    confirmModal.hide();
                }
            });
        });

        // Polling for real-time updates (every 30 seconds)
        setInterval(function() {
            if (!document.hidden) {
                dataTable.ajax.reload(null, false); // false = don't reset paging
            }
        }, 30000); // 30 seconds
    });
</script>
<!-- App js -->
<script type="module" src="{{ asset('assets/js/app.js') }}"></script>
@endsection
