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
                            <th>المزود</th>
                            <th>الحالة</th>
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
    document.addEventListener('DOMContentLoaded', function() {
        const tableElement = document.getElementById('clients-table');
        const activateModal = new bootstrap.Modal(document.getElementById('activateClientModal'));
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmStatusModal'));

        // Initialize DataTable with AJAX
        const dataTable = new DataTable(tableElement, {
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
                { data: 'provider', name: 'provider', orderable: false },
                { data: 'status', name: 'status', orderable: false },
                { data: 'activation_expires_at', name: 'activation_expires_at', orderable: false },
                { data: 'last_login_at', name: 'last_login_at', orderable: false },
                { data: 'created_at', name: 'created_at' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[7, 'desc']], // Order by created_at descending
            pageLength: 10,
            responsive: true,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        });

        // Add action buttons column
        dataTable.on('draw', function() {
            // Activate Client
            document.querySelectorAll('.activate-client').forEach(btn => {
                btn.addEventListener('click', function() {
                    const firebaseUid = this.dataset.firebaseUid;
                    const clientName = this.dataset.name;

                    document.getElementById('activate_client_firebase_uid').value = firebaseUid;
                    document.getElementById('activate_client_name').textContent = `هل تريد تفعيل العميل: ${clientName}؟`;
                    document.getElementById('activation_months').value = 1;
                    activateModal.show();
                });
            });

            // Ban Client
            document.querySelectorAll('.ban-client').forEach(btn => {
                btn.addEventListener('click', function() {
                    const firebaseUid = this.dataset.firebaseUid;
                    const clientName = this.dataset.name;

                    document.getElementById('confirm_client_firebase_uid').value = firebaseUid;
                    document.getElementById('confirm_status').value = 'banned';
                    document.getElementById('confirm_message').textContent = `هل أنت متأكد من حظر العميل: ${clientName}؟`;
                    document.getElementById('confirm_btn').className = 'btn btn-danger';
                    document.getElementById('confirm_btn').textContent = 'حظر';
                    confirmModal.show();
                });
            });

            // Set Pending
            document.querySelectorAll('.pending-client').forEach(btn => {
                btn.addEventListener('click', function() {
                    const firebaseUid = this.dataset.firebaseUid;
                    const clientName = this.dataset.name;

                    document.getElementById('confirm_client_firebase_uid').value = firebaseUid;
                    document.getElementById('confirm_status').value = 'pending';
                    document.getElementById('confirm_message').textContent = `هل تريد وضع العميل: ${clientName} في قائمة الانتظار؟`;
                    document.getElementById('confirm_btn').className = 'btn btn-warning';
                    document.getElementById('confirm_btn').textContent = 'وضع في الانتظار';
                    confirmModal.show();
                });
            });

            // Set Expired
            document.querySelectorAll('.expire-client').forEach(btn => {
                btn.addEventListener('click', function() {
                    const firebaseUid = this.dataset.firebaseUid;
                    const clientName = this.dataset.name;

                    document.getElementById('confirm_client_firebase_uid').value = firebaseUid;
                    document.getElementById('confirm_status').value = 'expired';
                    document.getElementById('confirm_message').textContent = `هل تريد تعيين حالة العميل: ${clientName} كأنهاء اشتراك؟`;
                    document.getElementById('confirm_btn').className = 'btn btn-info';
                    document.getElementById('confirm_btn').textContent = 'انتهى الاشتراك';
                    confirmModal.show();
                });
            });
        });

        // Activate Form Submit
        document.getElementById('activate-client-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const firebaseUid = document.getElementById('activate_client_firebase_uid').value;
            const months = document.getElementById('activation_months').value;

            fetch(`/admin/clients/${firebaseUid}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: 'active',
                    months: parseInt(months)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('نجاح!', data.message, 'success').then(() => {
                        dataTable.ajax.reload();
                    });
                } else {
                    Swal.fire('خطأ!', data.message || 'حدث خطأ ما', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('خطأ!', 'حدث خطأ أثناء التفعيل', 'error');
            })
            .finally(() => {
                activateModal.hide();
            });
        });

        // Confirm Status Form Submit
        document.getElementById('confirm-status-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const firebaseUid = document.getElementById('confirm_client_firebase_uid').value;
            const status = document.getElementById('confirm_status').value;

            fetch(`/admin/clients/${firebaseUid}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('نجاح!', data.message, 'success').then(() => {
                        dataTable.ajax.reload();
                    });
                } else {
                    Swal.fire('خطأ!', data.message || 'حدث خطأ ما', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('خطأ!', 'حدث خطأ أثناء تغيير الحالة', 'error');
            })
            .finally(() => {
                confirmModal.hide();
            });
        });
    });
</script>
<!-- App js -->
<script type="module" src="{{ asset('assets/js/app.js') }}"></script>
@endsection
